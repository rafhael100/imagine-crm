<?php

declare(strict_types=1);

require __DIR__.'/private-crm-toolbox.php';
umask(0077);
$file='/private/fixtures.json';
$mode=$argv[1]??'seed';
if (is_file('/private/publication-opened.json')) throw new RuntimeException('Private ingress is open; automated fixtures are unavailable.');
if ($mode==='clear-cache') {
    app('cache')->store('redis')->flush();
    echo "Private cache cleared for isolated pre-publication tests.\n";
    exit;
}
$db=privateCrmOwner();
if ($mode==='seed') {
    if (is_file($file)) throw new RuntimeException('Existing fixture vault; no implicit replacement.');
    $suffix=bin2hex(random_bytes(4));$records=[];$db->beginTransaction();
    try {
        foreach(['admin'=>'admin','clientA'=>'client','clientB'=>'client'] as $key=>$role) {
            $login='lab-'.strtolower($key).'-'.$suffix;
            $records[$key]=privateCrmSeedAccount($db,$login,bin2hex(random_bytes(16)),$role,'EMPRESA_TESTE_FIXTURE_'.strtoupper($key).'_'.strtoupper($suffix));
        }
        $db->commit();
    } catch(Throwable $error) { $db->rollBack(); throw new RuntimeException('Private fixture creation failed.'); }
    if (file_put_contents($file,json_encode($records,JSON_THROW_ON_ERROR),LOCK_EX)===false) throw new RuntimeException('Fixture vault unavailable.');
    chmod($file,0600);
    echo "Private fictional fixtures created; credentials stored only in protected operator directory.\n";
    exit;
}
if (!is_file($file)) throw new RuntimeException('Fixture vault required.');
$records=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if ($mode==='revoke') {
    $db->beginTransaction();
    foreach($records as $record) {
        if (!isset($record['identity'])) {
            $lookup=$db->prepare('SELECT identity_id FROM crm_auth_credentials WHERE login_key=?');
            $lookup->execute([hash('sha256',$record['login'])]);
            $record['identity']=$lookup->fetchColumn();
            if (!$record['identity']) throw new RuntimeException('Fixture identity absent.');
        }
        privateCrmSql($db,"UPDATE crm_auth_credentials SET status='revoked',version=version+1 WHERE identity_id=?",[$record['identity']]);
        privateCrmSql($db,"UPDATE crm_auth_sessions SET state='revoked' WHERE identity_id=?",[$record['identity']]);
        privateCrmSql($db,"UPDATE crm_private_accounts SET status='suspended' WHERE identity_id=?",[$record['identity']]);
    }
    $db->commit();unlink($file);
    echo "Fixture credentials revoked; private vault removed, audit retained.\n";exit;
}
if (in_array($mode,['automation-deny-enqueue','automation-restore-enqueue'],true)) {
    // Fault injection is available only to the explicit disposable-CI CLI.
    // The web app never receives these flags or the protected marker mount.
    $gate='/private/native-followup-enqueue-ci.json';
    if (getenv('CRM_PRIVATE_CI_FAULT_PROFILE')!=='native-followup-enqueue-v1'
        || getenv('CRM_PRIVATE_CI_PROJECT')!=='imagine-crm-journey-ci'
        || !app()->environment('staging') || !is_file($gate) || is_link($gate)
        || (fileperms($gate)&0077)!==0) throw new RuntimeException('Disposable CI fault profile required.');
    $marker=json_decode(file_get_contents($gate),true,8,JSON_THROW_ON_ERROR);
    if (!is_array($marker) || ($marker['profile']??null)!=='native-followup-enqueue-v1'
        || ($marker['project']??null)!=='imagine-crm-journey-ci'
        || !is_int($marker['created_at']??null)) throw new RuntimeException('Invalid CI fault marker.');
    $probe="SELECT CASE WHEN has_table_privilege('crm_homologation_app','public.crm_private_automation_runs','INSERT') THEN 'yes' ELSE 'no' END";
    if ($mode==='automation-deny-enqueue') {
        if (abs(time()-$marker['created_at'])>300 || $db->query($probe)->fetchColumn()!=='yes') {
            throw new RuntimeException('Unexpected native enqueue fixture baseline.');
        }
        $db->exec('REVOKE INSERT ON public.crm_private_automation_runs FROM crm_homologation_app');
        if ($db->query($probe)->fetchColumn()!=='no') throw new RuntimeException('Enqueue denial fixture failed.');
    } else {
        // Restoration may outlive the short injection window after a failed assertion.
        $db->exec('GRANT INSERT ON public.crm_private_automation_runs TO crm_homologation_app');
        if ($db->query($probe)->fetchColumn()!=='yes') throw new RuntimeException('Enqueue fixture restoration failed.');
    }
} elseif (in_array($mode,['automation-fill-capacity','automation-free-capacity'],true)) {
    $scope=$db->query('SELECT organization_id,lead_id FROM crm_private_automation_runs ORDER BY created_at DESC,id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if(!$scope)throw new RuntimeException('Native run fixture required');
    if($mode==='automation-fill-capacity')privateCrmSql($db,"INSERT INTO crm_private_tasks(id,organization_id,lead_id,title,due_on)
      SELECT gen_random_uuid(),?::uuid,?::uuid,'TASK_TESTE_CAPACITY_'||i::text,current_date FROM generate_series(1,
       GREATEST(0,200-(SELECT count(*)::integer FROM crm_private_tasks WHERE organization_id=?::uuid))) i",
       [$scope['organization_id'],$scope['lead_id'],$scope['organization_id']]);
    else privateCrmSql($db,"DELETE FROM crm_private_tasks WHERE organization_id=? AND title LIKE 'TASK_TESTE_CAPACITY_%'",[$scope['organization_id']]);
} elseif ($mode==='expire-clientB') {
    privateCrmSql($db,'UPDATE crm_auth_sessions SET expires_at=? WHERE identity_id=?',[time()-1,$records['clientB']['identity']]);
} elseif ($mode==='revoke-membershipB') {
    privateCrmSql($db,"UPDATE crm_memberships SET status='revoked',version=version+1 WHERE global_identity_id=?",[$records['clientB']['identity']]);
} elseif ($mode==='restore-membershipB') {
    privateCrmSql($db,"UPDATE crm_memberships SET status='active',version=version+1 WHERE global_identity_id=?",[$records['clientB']['identity']]);
} elseif ($mode==='expire-signup') {
    $db->exec('UPDATE crm_private_signup_requests SET issued_at=issued_at-901,expires_at=expires_at-901 WHERE completed_at IS NULL AND consumed_at IS NULL');
} elseif ($mode==='expire-clientA-trial') {
    $now=time();
    privateCrmSql($db,'UPDATE crm_private_trials SET started_at=?,expires_at=? WHERE organization_id=?',[$now-1209601,$now-1,$records['clientA']['organization']]);
} else throw new RuntimeException('Unknown private fixture scenario.');
echo "Private negative test scenario prepared.\n";

