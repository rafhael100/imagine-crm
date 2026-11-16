"""Ephemeral Docker evidence and application-side guarded SQL. No product changes."""
import ipaddress
import json
import os
import re
import subprocess
from pathlib import Path

PROFILES = {
    'crm': ('crm-db', 'crm-app', 'crm_homologation_owner', 'imagine_crm_private_homologation', 'owner.password', ['crm_auto_transport', 'crm_auto_sender']),
    'agents': ('agents-db', 'agents-app', 'agents_ci_owner', 'imagine_agents_ci', 'agents-owner.password', ['aia_private_transport']),
}
IDENTITY_SQL = """SELECT json_build_object('database',current_database(),'port',current_setting('port')::int,
'directory',current_setting('data_directory'),'started',floor(extract(epoch FROM pg_postmaster_start_time()))::bigint::text,
'database_oid',(SELECT oid::text FROM pg_database WHERE datname=current_database()),
'system_identifier',(SELECT system_identifier::text FROM pg_control_system()))"""


def require(ok, reason):
    if not ok:
        raise RuntimeError('P20_TARGET_DENIED_'+reason)


def validate_compose(compose):
    require(compose.get('networks') == {'private': {'internal': True}}, 'NETWORK')
    for service in compose['services'].values():
        require(service.get('networks') == ['private'], 'SERVICE_NETWORK')
        require(not any(service.get(k) for k in ('ports', 'network_mode', 'privileged', 'pid', 'extra_hosts', 'links', 'external_links')), 'EXPOSURE')
        for volume in service.get('volumes', []):
            require(isinstance(volume, str) and 'docker.sock' not in volume, 'SOCKET')
    for name in ('crm_pg', 'agents_pg'):
        require(compose['volumes'].get(name) == {}, 'EXISTING_VOLUME')


def validate_container(container, network, volume, project, service, volume_name):
    labels = container['Config']['Labels']
    require(labels.get('com.docker.compose.project') == project and labels.get('com.docker.compose.service') == service, 'CONTAINER_OWNER')
    require(container['State']['Running'] is True, 'STOPPED')
    host = container['HostConfig']
    require(not host.get('Privileged') and not host.get('PortBindings') and not host.get('ExtraHosts'), 'CONTAINER_EXPOSURE')
    require(host.get('NetworkMode') == project+'_private', 'CONTAINER_NETWORK_MODE')
    networks = container['NetworkSettings']['Networks']
    require(set(networks) == {project+'_private'}, 'MULTIPLE_NETWORKS')
    require(network['Internal'] is True and network['Labels'].get('com.docker.compose.project') == project, 'NETWORK_OWNER')
    require(networks[project+'_private']['NetworkID'] == network['Id'], 'NETWORK_ID')
    require(not any(container['NetworkSettings'].get('Ports', {}).values()), 'HOST_PORTS')
    mounts = [m for m in container['Mounts'] if m['Destination'] == '/var/lib/postgresql/data']
    require(len(mounts) == 1 and mounts[0]['Type'] == 'volume' and mounts[0]['Name'] == volume_name, 'DATA_MOUNT')
    require(volume['Name'] == volume_name and volume['Driver'] == 'local' and not volume.get('Options'), 'VOLUME_DRIVER')
    require(volume['Labels'].get('com.docker.compose.project') == project, 'VOLUME_OWNER')
    address = networks[project+'_private']['IPAddress']
    require(ipaddress.ip_address(address).is_private and not ipaddress.ip_address(address).is_loopback, 'ADDRESS')
    return address


class ServiceTargets:
    def __init__(self, dc, project, folder, certs, run):
        self.dc, self.project, self.folder, self.certs, self.run = dc, project, folder, certs, run
        self.here = Path(__file__).resolve().parent
        self.identities = {}

    def json(self, command):
        return json.loads(self.run(command).stdout)

    def capture(self, product):
        service, app, owner, database, password, roles = PROFILES[product]
        cid = self.run(self.dc+['ps', '-q', service]).stdout.strip()
        require(bool(re.fullmatch('[a-f0-9]{64}', cid)), 'CONTAINER_ID')
        container = self.json(['docker','inspect',cid])[0]
        network = self.json(['docker','network','inspect',self.project+'_private'])[0]
        volume_name = self.project+'_'+('crm_pg' if product == 'crm' else 'agents_pg')
        volume = self.json(['docker','volume','inspect',volume_name])[0]
        address = validate_container(container, network, volume, self.project, service, volume_name)
        # Independent OS cluster identifier, then SQL through that exact disposable container.
        control = self.run(['docker','exec',cid,'env','LC_ALL=C','pg_controldata','-D','/var/lib/postgresql/data']).stdout
        ids = re.findall(r'^Database system identifier:\s+(\d+)\s*$', control, re.M)
        require(len(ids) == 1, 'CONTROL_ID')
        row = self.json(['docker','exec',cid,'psql','-X','-qAt','-v','ON_ERROR_STOP=1','-U',owner,'-d',database,'-c',IDENTITY_SQL])
        require(row.get('system_identifier') == ids[0] and row.get('database') == database, 'SQL_CLUSTER')
        require(row.get('directory') == '/var/lib/postgresql/data' and row.get('port') == 5432, 'SQL_LOCATION')
        row.update(product=product, address=address)
        prior = self.identities.get(product)
        require(prior is None or prior == (cid, row), 'CLUSTER_CHANGED')
        self.identities[product] = (cid, row)
        return row

    def invoke(self, product, role, query=None):
        row = self.capture(product)
        _, app, owner, _, password, _ = PROFILES[product]
        proof = self.folder/(product+'-target.json')
        proof.write_text(json.dumps(row)); proof.chmod(0o444)
        args = self.dc+['run','--rm','--no-deps','-T','--entrypoint','php',
            '-v',str(self.here)+':/p20-code:ro','-v',str(proof)+':/p20/expected.json:ro']
        if role == owner:
            # Bootstrap-only one-shot. This secret is never mounted into product service containers.
            args += ['-v',str(self.certs/password)+':/p20/bootstrap.password:ro']
        args += [app,'/p20-code/service-target.php','check' if query is None else 'prepare',role]
        result = self.run(args, data=query)
        proofs = [json.loads(line) for line in result.stderr.splitlines() if line.startswith('{"schema":"P20_SERVICE_TARGET_V1"')]
        require(len(proofs) == 1 and proofs[0]['product'] == product and proofs[0]['role'] == role, 'MISSING_PROCESS_PROOF')
        return result.stdout.strip()

    def preflight(self):
        for product, (_, _, owner, _, _, _) in PROFILES.items():
            self.invoke(product, owner)

    def sql(self, product, query):
        return self.invoke(product, PROFILES[product][2], query)

    def versions(self):
        images = {}
        for product, profile in PROFILES.items():
            for service in (profile[0], profile[1]):
                cid = self.run(self.dc+['ps','-q',service]).stdout.strip()
                container = self.json(['docker','inspect',cid])[0]
                image = self.json(['docker','image','inspect',container['Image']])[0]
                images[service] = {'image_id':image['Id'],'repo_digests':image.get('RepoDigests',[])}
        versions = {}
        for service, command in [('crm-app',['php','--version']),('agents-app',['php','--version']),
                                 ('portal',['php','--version']),('crm-db',['postgres','--version']),
                                 ('agents-db',['postgres','--version'])]:
            versions[service] = self.run(self.dc+['exec','-T',service]+command).stdout.strip()
        versions['portal-postgres'] = self.run(self.dc+['exec','-T','portal','/usr/lib/postgresql/18/bin/postgres','--version']).stdout.strip()
        versions['portal-composer'] = self.run(self.dc+['exec','-T','portal','composer','--version','--no-ansi']).stdout.strip()
        cid = self.run(self.dc+['ps','-q','portal']).stdout.strip()
        image_id = self.json(['docker','inspect',cid])[0]['Image']
        images['portal'] = {'image_id':image_id,'repo_digests':self.json(['docker','image','inspect',image_id])[0].get('RepoDigests',[])}
        print(json.dumps({'schema':'P20_RUNTIME_VERSIONS_V1','images':images,'versions':versions},sort_keys=True))

    def runtime(self):
        for product, profile in PROFILES.items():
            for role in profile[5]:
                self.invoke(product, role)


def require_local_docker(run, project):
    require(not os.environ.get('DOCKER_HOST') and not os.environ.get('DOCKER_CONTEXT'), 'REMOTE_DOCKER_ENV')
    context = json.loads(run(['docker','context','inspect']).stdout)
    require(len(context) == 1 and context[0]['Endpoints']['docker']['Host'] == 'unix:///var/run/docker.sock', 'REMOTE_DOCKER_CONTEXT')
    for kind in ('container','network','volume'):
        result = run(['docker',kind,'ls','-q','--filter','label=com.docker.compose.project='+project]).stdout
        require(not result.strip(), 'PREEXISTING_PROJECT')
