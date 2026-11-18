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
} elseif (in_array($mode,['automation-deny-causal-logout','automation-restore-causal-logout'],true)) {
    // This scoped fault makes the existing IAM runner persist its real hold.
    // It never inserts a hold/operation or changes a session into an accepted state.
    $gate='/private/native-causal-session-ci.json';
    if (getenv('CRM_PRIVATE_CI_CAUSAL_SESSION_PROFILE')!=='native-causal-session-hold-v1'
        || getenv('CRM_PRIVATE_CI_PROJECT')!=='imagine-crm-journey-ci'
        || !app()->environment('staging') || !is_file($gate) || is_link($gate)
        || (fileperms($gate)&0077)!==0) throw new RuntimeException('Disposable causal session profile required.');
    $marker=json_decode(file_get_contents($gate),true,8,JSON_THROW_ON_ERROR);
    if (!is_array($marker) || ($marker['profile']??null)!=='native-causal-session-hold-v1'
        || ($marker['project']??null)!=='imagine-crm-journey-ci'
        || !is_int($marker['created_at']??null)) throw new RuntimeException('Invalid causal session marker.');
    foreach (['probe','actor','company','run','source'] as $field) {
        if (!is_string($marker[$field]??null) || !preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D',$marker[$field])) {
            throw new RuntimeException('Invalid causal session fixture reference.');
        }
    }
    if ($marker['actor']!==($records['clientB']['identity']??null)
        || $marker['company']!==($records['clientB']['organization']??null)) {
        throw new RuntimeException('Causal session fixture must belong to the protected client B.');
    }
    $tag='native-causal-session-hold-v1:'.$marker['probe'];
    $db->beginTransaction();
    try {
        $function=$db->query("SELECT p.oid,obj_description(p.oid,'pg_proc') AS marker
            FROM pg_proc p WHERE p.oid=to_regprocedure('public.crm_private_ci_causal_state_denial()')")->fetch(PDO::FETCH_ASSOC);
        $trigger=$db->query("SELECT t.oid,obj_description(t.oid,'pg_trigger') AS marker FROM pg_trigger t
            WHERE t.tgrelid='public.crm_auth_sessions'::regclass AND t.tgname='crm_private_ci_causal_state_denial'")->fetch(PDO::FETCH_ASSOC);
        if ($mode==='automation-deny-causal-logout') {
            if (abs(time()-$marker['created_at'])>300 || $function || $trigger) {
                throw new RuntimeException('Unexpected causal session fault baseline.');
            }
            $stmt=$db->prepare("SELECT r.state AS run_state,r.due_at,s.state,s.security_hold,s.expires_at,s.absolute_expires_at,
                CASE WHEN EXISTS(SELECT 1 FROM public.crm_auth_operations o WHERE o.id=s.operation_id AND o.state='completed')
                    THEN 'yes' ELSE 'no' END AS origin_completed
                FROM public.crm_private_automation_runs r JOIN public.crm_auth_sessions s
                    ON s.id=r.authority_session_id AND s.identity_id=r.actor_id
                WHERE r.id=?::uuid AND r.actor_id=?::uuid AND r.organization_id=?::uuid AND s.id=?::uuid FOR UPDATE OF r,s");
            $stmt->execute([$marker['run'],$marker['actor'],$marker['company'],$marker['source']]);
            $source=$stmt->fetch(PDO::FETCH_ASSOC);
            if (!$source || $source['run_state']!=='pending' || (int)$source['due_at']<=time()
                || $source['state']!=='active' || $source['security_hold']!==null
                || (int)$source['expires_at']<=time() || (int)$source['absolute_expires_at']<=time()
                || $source['origin_completed']!=='yes') throw new RuntimeException('Usable causal session and future run required.');
            $db->exec("CREATE FUNCTION public.crm_private_ci_causal_state_denial() RETURNS trigger LANGUAGE plpgsql AS \$fault\$
                BEGIN RAISE EXCEPTION USING ERRCODE='42501',MESSAGE='SYNTHETIC_CAUSAL_SESSION_STATE_DENIED'; END; \$fault\$");
            $db->exec("REVOKE ALL ON FUNCTION public.crm_private_ci_causal_state_denial() FROM PUBLIC");
            $db->exec("CREATE TRIGGER crm_private_ci_causal_state_denial BEFORE UPDATE OF state ON public.crm_auth_sessions
                FOR EACH ROW WHEN (OLD.id=".$db->quote($marker['source'])."::uuid AND OLD.identity_id=".$db->quote($marker['actor'])."::uuid
                    AND OLD.state IS DISTINCT FROM NEW.state) EXECUTE FUNCTION public.crm_private_ci_causal_state_denial()");
            $db->exec("COMMENT ON FUNCTION public.crm_private_ci_causal_state_denial() IS ".$db->quote($tag));
            $db->exec("COMMENT ON TRIGGER crm_private_ci_causal_state_denial ON public.crm_auth_sessions IS ".$db->quote($tag));
        } else {
            // Drop only this probe's temporary objects, including after assertion failure.
            // The real prepared operation and unresolved hold remain as denial evidence.
            foreach ([$function,$trigger] as $object) {
                if ($object && $object['marker']!==$tag) throw new RuntimeException('Causal fault ownership mismatch.');
            }
            $db->exec('DROP TRIGGER IF EXISTS crm_private_ci_causal_state_denial ON public.crm_auth_sessions');
            $db->exec('DROP FUNCTION IF EXISTS public.crm_private_ci_causal_state_denial()');
        }
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
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

