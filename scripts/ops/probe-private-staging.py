#!/usr/bin/env python3
"""Read-only ingress/port probe. Does not start containers or read credentials.

Run on the development VPS. A successful probe is NOT deployment admission:
database role, backup/restore and authenticated E2E evidence are still required.
"""
import json
import socket
import ssl
import sys
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen, HTTPRedirectHandler, HTTPSHandler, build_opener

HOST = 'crm-dev.imaginecomunica.com.br'


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def available(port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        try:
            sock.bind(('127.0.0.1', port))
            return True
        except OSError:
            return False


def main():
    result = {'host': HOST, 'read_only': True, 'deployment_admitted': False}
    try:
        result['dns_ipv4'] = sorted({a[4][0] for a in socket.getaddrinfo(HOST, 443, socket.AF_INET)})
    except OSError:
        result['dns_ipv4'] = []
    opener = build_opener(NoRedirect(), HTTPSHandler(context=ssl.create_default_context()))
    status = None
    try:
        with opener.open(Request('https://' + HOST + '/', method='GET'), timeout=10) as response:
            status = response.status
    except HTTPError as error:
        status = error.code
    except (URLError, OSError):
        pass
    result['https_unauthenticated_status'] = status
    result['ingress_denies_anonymous'] = status in (401, 403)
    result['ports'] = {str(port): 'available' if available(port) else 'occupied'
                       for port in (18080, 18081, 18086)}
    result['blockers'] = ['No authenticated end-to-end acceptance recorded for this candidate.',
                          'No VPS backup/restore and rollback evidence for a new persistent CRM database.']
    if result['ports']['18080'] == 'occupied':
        result['blockers'].append('Default CI Compose port 18080 is occupied; preserve existing application.')
    if not result['ingress_denies_anonymous']:
        result['blockers'].append('HTTPS ingress protection not verified.')
    print(json.dumps(result, indent=2))
    return 2  # Intentional NO-GO; infrastructure observation alone never authorizes deployment.


if __name__ == '__main__':
    sys.exit(main())
