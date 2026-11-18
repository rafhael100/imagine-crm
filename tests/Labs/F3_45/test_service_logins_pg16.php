<?php

declare(strict_types=1);

// F3-45 CI-only: two real PostgreSQL 16 LOGIN roles, neither superuser,
// each inheriting only its fixed synthetic tenant policy. No service API.
if (getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F345_PRIVILEGE_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='imagine_crm_handoff_ci'
    || getenv('DB_PASSWORD')!=='f345-fixture-only-not-a-real-secret'
    || !extension_loaded('pdo_pgsql')) {
    fwrite(STDERR,"F3-45 refuses non-ephemeral GitHub CI PostgreSQL.\n");
    exit(2);
}
$checks=0;
function check(bool $ok,string $message): void
{
    global $checks;
    $checks++;
    if (!$ok)throw new RuntimeException('F3-45 FAIL: '.$message);
}
function connectRole(string $role): PDO
{
    $credentials=[
        'crm_f345_service_a'=>'f345-synthetic-service-a',
        'crm_f345_service_b'=>'f345-synthetic-service-b'
    ];
    check(array_key_exists($role,$credentials),'role is explicit CI allowlist');
    $db=new PDO(
        'pgsql:host=127.0.0.1;port=5432;dbname=imagine_crm_handoff_ci',
        $role,$credentials[$role],
        [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
        ]
    );
    check($db->query('SELECT current_database()')->fetchColumn()==='imagine_crm_handoff_ci',
        'exact CI-only database');
    $identity=$db->query(<<<'SQL'
SELECT current_user AS current_user_name,
       session_user AS session_user_name,
       r.rolsuper, r.rolbypassrls, r.rolcreaterole, r.rolcreatedb,
       r.rolinherit
FROM pg_roles r WHERE r.rolname=current_user
SQL)->fetch(PDO::FETCH_ASSOC);
    check(is_array($identity),'role is inspectable');
    check($identity['current_user_name']===$role
        && $identity['session_user_name']===$role,'no SET ROLE or superuser impersonation');
    foreach (['rolsuper','rolbypassrls','rolcreaterole','rolcreatedb'] as $flag) {
        check(in_array($identity[$flag],[false,'f',0],true),'denied privileged '.$flag);
    }
    check(in_array($identity['rolinherit'],[true,'t',1],true),
        'only inherited scoped privileges');
    return $db;
}
function sample(string $org,string $submission,string $nonce): array
{
    foreach ([$org,$submission,$nonce] as $ref) {
        check(preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D',$ref)===1,
            'synthetic reference format');
    }
    return [
        'org'=>$org,'submission'=>$submission,'nonce'=>$nonce,
        'binding'=>'22222222-2222-4222-8222-222222222222',
        'site'=>'44444444-4444-4444-8444-444444444444',
        'form'=>'55555555-5555-4555-8555-555555555555',
        'destination'=>'66666666-6666-4666-8666-666666666666'
    ];
}
function insertAtomic(PDO $db,array $refs): array
{
    $db->beginTransaction();
    try {
        $stmt=$db->prepare(<<<'SQL'
WITH nonce_record AS (
  INSERT INTO crm_portal_handoff_ci.request_nonces
    (organization_reference,nonce,submission_reference)
  VALUES(CAST(:org AS uuid),CAST(:nonce AS uuid),CAST(:submission AS uuid))
  ON CONFLICT(organization_reference,nonce) DO NOTHING
  RETURNING organization_reference,submission_reference
), created AS (
  INSERT INTO crm_portal_handoff_ci.metadata_receipts
    (organization_reference,submission_reference,binding_reference,
     site_reference,form_reference,destination_reference)
  SELECT organization_reference,submission_reference,
    CAST(:binding AS uuid),CAST(:site AS uuid),
    CAST(:form AS uuid),CAST(:destination AS uuid)
  FROM nonce_record
  ON CONFLICT(organization_reference,submission_reference) DO NOTHING
  RETURNING submission_reference
)
SELECT (SELECT count(*) FROM nonce_record) AS nonce_rows,
       (SELECT count(*) FROM created) AS created_rows
SQL);
        $stmt->execute($refs);
        $counts=$stmt->fetch(PDO::FETCH_ASSOC);
        check(is_array($counts),'real SQL returned atomic row counts');
        $db->commit();
        return [(int)$counts['nonce_rows'],(int)$counts['created_rows']];
    } catch (Throwable $e) {
        if ($db->inTransaction())$db->rollBack();
        throw $e;
    }
}
function countTable(PDO $db,string $table,string $org): int
{
    check(in_array($table,['metadata_receipts','request_nonces'],true),'table allowlist');
    $stmt=$db->prepare(
        'SELECT count(*) FROM crm_portal_handoff_ci.'.$table.
        ' WHERE organization_reference=CAST(:org AS uuid)'
    );
    $stmt->execute(['org'=>$org]);
    return (int)$stmt->fetchColumn();
}
function expectDenied(PDO $db,callable $action,string $description): void
{
    $db->beginTransaction();
    $rejected=false;
    try {
        $action($db);
        $db->rollBack();
    } catch (PDOException $e) {
        $rejected=true;
        check($e->getCode()==='42501',
            'denial must be privilege/RLS error, not a programming failure: '.$description);
        if ($db->inTransaction())$db->rollBack();
    }
    check($rejected,'role must lack permission for '.$description);
    check(!$db->inTransaction(),'failed command must not leave open transaction');
}

function expectAtomicDenied(PDO $db,array $refs,string $description): void
{
    $rejected=false;
    try {
        insertAtomic($db,$refs); // Owns its transaction and rollback.
    } catch (PDOException $e) {
        $rejected=true;
        check($e->getCode()==='42501',
            'cross-tenant insert denied specifically by RLS: '.$description);
    }
    check($rejected,'cross-tenant atomic insert denied: '.$description);
    check(!$db->inTransaction(),'cross-tenant failure rolls back completely');
}

$root=new PDO(
    'pgsql:host=127.0.0.1;port=5432;dbname=imagine_crm_handoff_ci',
    'postgres',(string)getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);
check($root->query('SELECT current_database()')->fetchColumn()==='imagine_crm_handoff_ci',
    'inspect only disposable superuser fixture');
check((int)$root->query(<<<'SQL'
SELECT count(*) FROM pg_class c JOIN pg_namespace n ON n.oid=c.relnamespace
WHERE n.nspname='crm_portal_handoff_ci'
  AND c.relname IN('metadata_receipts','request_nonces')
  AND c.relrowsecurity AND c.relforcerowsecurity
SQL)->fetchColumn()===2,'both metadata tables FORCE RLS');

$orgA='11111111-1111-4111-8111-111111111111';
$orgB='99999999-9999-4999-8999-999999999999';
$a=connectRole('crm_f345_service_a');
$b=connectRole('crm_f345_service_b');

foreach ([$a,$b] as $db) {
    check($db->query("SELECT current_setting('row_security')")->fetchColumn()==='on',
        'RLS ON for direct LOGIN');
    check(!$db->inTransaction(),'start without inherited transaction context');
}
check(countTable($a,'metadata_receipts',$orgA)===0,'fresh A ledger');
check(countTable($b,'metadata_receipts',$orgB)===0,'fresh B ledger');
check(insertAtomic($a,sample($orgA,
    'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
    'a1a1a1a1-a1a1-4a1a-8a1a-a1a1a1a1a1a1'))===[1,1],
    'non-superuser A inserts nonce + row atomically without SET ROLE');
check(insertAtomic($b,sample($orgB,
    'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
    'b1b1b1b1-b1b1-4b1b-8b1b-b1b1b1b1b1b1'))===[1,1],
    'non-superuser B inserts only own tenant');
check(countTable($a,'metadata_receipts',$orgA)===1,'A sees own row');
check(countTable($b,'metadata_receipts',$orgB)===1,'B sees own row');
check(countTable($a,'metadata_receipts',$orgB)===0,'A cannot read B');
check(countTable($b,'metadata_receipts',$orgA)===0,'B cannot read A');
check(countTable($a,'request_nonces',$orgB)===0,'A cannot read B nonces');
check(countTable($b,'request_nonces',$orgA)===0,'B cannot read A nonces');

check(insertAtomic($a,sample($orgA,
    'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
    'a1a1a1a1-a1a1-4a1a-8a1a-a1a1a1a1a1a1'))===[0,0],
    'same nonce cannot commit a second row');
check(insertAtomic($a,sample($orgA,
    'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
    'a2a2a2a2-a2a2-4a2a-8a2a-a2a2a2a2a2a2'))===[1,0],
    'new nonce is not a new lead');

expectAtomicDenied($a,sample($orgB,
    'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
    'c1c1c1c1-c1c1-4c1c-8c1c-c1c1c1c1c1c1'),'A inserts into tenant B');

expectAtomicDenied($b,sample($orgA,
    'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
    'd1d1d1d1-d1d1-4d1d-8d1d-d1d1d1d1d1d1'),'B inserts into tenant A');

foreach ([$a,$b] as $db) {
    foreach ([
        'UPDATE crm_portal_handoff_ci.metadata_receipts SET destination_reference=destination_reference',
        'DELETE FROM crm_portal_handoff_ci.request_nonces',
        'TRUNCATE crm_portal_handoff_ci.metadata_receipts',
        'ALTER TABLE crm_portal_handoff_ci.metadata_receipts DISABLE ROW LEVEL SECURITY',
        'DROP TABLE crm_portal_handoff_ci.request_nonces',
        'SET ROLE postgres',
        'SET ROLE crm_f340_receiver_a'
    ] as $command)expectDenied($db,static fn(PDO $x)=>$x->exec($command),$command);
    // Spoofing a session GUC does NOT grant a different RLS tenant.
    $db->exec("SELECT set_config('app.tenant_id', '".$orgA."', false)");
}
check(countTable($b,'metadata_receipts',$orgA)===0,
    'GUC spoofing does not authorize tenant B to read A');
check(countTable($b,'request_nonces',$orgA)===0,
    'GUC spoofing does not authorize tenant B to read A nonces');
check(countTable($a,'metadata_receipts',$orgB)===0,
    'A still cannot read B after destructive-command attempts');

check((int)$root->query(
    "SELECT count(*) FROM crm_portal_handoff_ci.metadata_receipts"
)->fetchColumn()===2,'exactly one physical row per tenant');
check((int)$root->query(
    "SELECT count(*) FROM crm_portal_handoff_ci.request_nonces"
)->fetchColumn()===3,'two A attempts and one B attempt');
echo "CRM F3-45 PASS: {$checks} real PG16 LOGIN/no-superuser/tenant RLS and denied privilege checks, no lead delivery.\n";
