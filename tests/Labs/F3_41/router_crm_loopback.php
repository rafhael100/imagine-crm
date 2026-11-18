<?php

declare(strict_types=1);

// CRM F3-41: PHP built-in server ROUTER FOR GITHUB ACTIONS ONLY.
// No Laravel route, production API, real customer, secret, worker or CRM ACK.
require_once __DIR__.'/../F3_40/SyntheticPortalHandoffReceiver.php';

use Tests\Labs\F3_40\SyntheticPortalHandoffReceiver;

function f341_reply(int $status,string $reason,?array $receipt=null): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    $out=[
        'schema'=>'imagine.crm.synthetic-portal-http.v1',
        'mode'=>'CI_ONLY_REAL_PG16_NOT_REAL_CRM_RECEIPT',
        'reasonCode'=>$reason,
        'mockCommitted'=>$receipt!==null,
        'realCrmPersisted'=>false,
        'tenantAuthenticatedForProduction'=>false,
        'canAcknowledgeVisitor'=>false,
        'canAutoRetryUnknownOutcome'=>false,
        'productionAuthorized'=>false
    ];
    if ($receipt!==null) {
        $out['receipt']=$receipt;
    }
    echo json_encode($out,JSON_THROW_ON_ERROR);
}
function f341_timestamp(mixed $v): ?int
{
    if (!is_string($v) || preg_match('/\A[1-9][0-9]{0,11}\z/D',$v)!==1) {
        return null;
    }
    $number=(int)$v;
    return (string)$number===$v?$number:null;
}
if (PHP_SAPI!=='cli-server'
    || getenv('GITHUB_ACTIONS')!=='true'
    || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
    || getenv('CRM_F340_LAB')!=='1'
    || getenv('CRM_F341_HTTP_LAB')!=='1'
    || getenv('DB_HOST')!=='127.0.0.1'
    || getenv('DB_DATABASE')!=='imagine_crm_handoff_ci'
    || (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR']!=='127.0.0.1')
    || ($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1') {
    f341_reply(503,'DISPOSABLE_LOOPBACK_REQUIRED');
    return;
}
$path=$_SERVER['REQUEST_URI']??'';
if (($_SERVER['REQUEST_METHOD']??'')!=='POST'
    || !in_array($path,['/__lab/crm/ingest','/__lab/crm/readback'],true)) {
    f341_reply(404,'NO_PUBLIC_ROUTE');
    return;
}
if (($_SERVER['CONTENT_TYPE']??'')!=='application/json') {
    f341_reply(415,'JSON_CONTENT_TYPE_REQUIRED');
    return;
}
$length=$_SERVER['CONTENT_LENGTH']??null;
if ($length!==null && (!is_string($length)
    || !ctype_digit($length) || (int)$length>2048)) {
    f341_reply(413,'SYNTHETIC_BODY_TOO_LARGE');
    return;
}
$raw=file_get_contents('php://input',false,null,0,2049);
if (!is_string($raw)||strlen($raw)>2048) {
    f341_reply(413,'SYNTHETIC_BODY_TOO_LARGE');
    return;
}
try {
    $json=json_decode($raw,true,16,JSON_THROW_ON_ERROR);
} catch (Throwable) {
    f341_reply(400,'INVALID_JSON');
    return;
}
if (!is_array($json)||array_is_list($json)) {
    f341_reply(422,'ENVELOPE_REJECTED');
    return;
}
$issued=f341_timestamp($_SERVER['HTTP_X_LAB_ISSUED_AT']??null);
$expires=f341_timestamp($_SERVER['HTTP_X_LAB_EXPIRES_AT']??null);
$nonce=$_SERVER['HTTP_X_LAB_NONCE']??null;
$mac=$_SERVER['HTTP_X_LAB_SIGNATURE']??null;
if ($issued===null||$expires===null
    || !is_string($nonce)||!is_string($mac)) {
    f341_reply(401,'SYNTHETIC_AUTH_REQUIRED');
    return;
}
$requestHex=getenv('CRM_F341_REQUEST_KEY_HEX');
$receiptHex=getenv('CRM_F341_RECEIPT_KEY_HEX');
if (!is_string($requestHex)||!is_string($receiptHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$requestHex)
    || !preg_match('/\A[0-9a-f]{96}\z/D',$receiptHex)
    || hash_equals($requestHex,$receiptHex)) {
    f341_reply(503,'LAB_KEYS_UNAVAILABLE');
    return;
}
$requestKey=hex2bin($requestHex);
$receiptKey=hex2bin($receiptHex);
if (!is_string($requestKey)||!is_string($receiptKey)) {
    f341_reply(503,'LAB_KEYS_UNAVAILABLE');
    return;
}
$binding=[
    'organization_reference'=>'11111111-1111-4111-8111-111111111111',
    'binding_reference'=>'22222222-2222-4222-8222-222222222222',
    'site_reference'=>'44444444-4444-4444-8444-444444444444',
    'form_reference'=>'55555555-5555-4555-8555-555555555555',
    'destination_reference'=>'66666666-6666-4666-8666-666666666666',
];
try {
    $db=new PDO(
        'pgsql:host=127.0.0.1;port=5432;dbname=imagine_crm_handoff_ci',
        'postgres',(string)getenv('DB_PASSWORD'),
        [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES=>false,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
        ]
    );
    $receiver=new SyntheticPortalHandoffReceiver($binding,$requestKey,$receiptKey);
    $authentication=[
        'issued_at'=>$issued,'expires_at'=>$expires,'nonce'=>$nonce,'mac'=>$mac
    ];
    $result=$path==='/__lab/crm/readback'
        ? $receiver->lookup($db,$json,$authentication,time())
        : $receiver->deliver($db,$json,$authentication,time());
    $reason=$result['reasonCode'];
    if (in_array($reason,[
        'LAB_MOCK_COMMITTED_FIRST','LAB_MOCK_COMMITTED_LOGICAL_REPLAY',
        'LAB_MOCK_READBACK_SIGNED'
    ],true)) {
        // Deliberately simulate the sender losing the HTTP reply AFTER
        // committing to a separate database. Never fabricate a receipt;
        // the sender must query readback, not automatically send another lead.
        if ($path==='/__lab/crm/ingest'
            && ($_SERVER['HTTP_X_LAB_DROP_RECEIPT']??null)==='1') {
            f341_reply(503,'UNKNOWN_OUTCOME_NO_RECEIPT');
            return;
        }
        f341_reply($path==='/__lab/crm/ingest'?202:200,$reason,$result['receipt']);
        return;
    }
    if (in_array($reason,['REQUEST_NONCE_REPLAY','PINNED_REPLAY_CONFLICT'],true)) {
        f341_reply(409,$reason);
    } elseif ($reason==='LAB_RECORD_NOT_VERIFIED') {
        f341_reply(404,$reason);
    } elseif ($reason==='ENVELOPE_REJECTED') {
        f341_reply(422,$reason);
    } elseif ($reason==='PINNED_CONTEXT_CONFLICT') {
        f341_reply(403,$reason);
    } elseif (str_starts_with($reason,'SIGNED_REQUEST_')) {
        f341_reply(401,'SYNTHETIC_AUTH_REJECTED');
    } else {
        f341_reply(503,'LAB_REVIEW_REQUIRED');
    }
} catch (Throwable) {
    // Never expose SQL, UUID, transport headers or credentials in HTTP.
    f341_reply(503,'LAB_REVIEW_REQUIRED');
}
