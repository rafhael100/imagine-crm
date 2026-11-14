<?php
declare(strict_types=1);

require_once __DIR__.'/../app/Core/Leads/SyntheticCalculatorInterestInteropInspector.php';

use App\Core\Leads\SyntheticCalculatorInterestInteropInspector as Inspector;

function assertInterop(bool $ok,string $label): void {
    if (!$ok) throw new RuntimeException('Calculator interop failed: '.$label);
}

$key='IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$signature='44f3c4d8c6f49830caf6c66a9c295ffa207e67801b3c7c7cabb337518dde7816';
$payload=[
  'schema'=>'imagine.calculator.synthetic-interest.v1',
  'source'=>'imagine_comercial_calculadora_performance',
  'submission_id'=>'123e4567-e89b-42d3-a456-426614174000',
  'channel_ids'=>['chatgpt_ads','google_ads','meta_ads'],
  'currency'=>'BRL',
  'media_total_cents'=>430000,
  'report_requested'=>true,
  'marketing_requested'=>false,
  'notice_version'=>'lab-notice-v1',
  'issued_at'=>1794672000,
  'expires_at'=>1794672300,
  'nonce'=>'f2e67d4e-39c1-43ba-a304-7db5477a2e66'
];
$signing=Inspector::signingBytes($payload);
assertInterop(substr_count($signing,"\n")===12,'exact 13 canonical fields');
assertInterop(hash_hmac('sha256',$signing,$key)===$signature,'reference agrees with Node producer');
$good=Inspector::inspect(['payload'=>$payload,'signature'=>$signature],$key,1794672030);
assertInterop($good['state']==='SYNTHETIC_MAC_MATCH_UNTRUSTED','only fixture MAC matches');
assertInterop($good['synthetic_mac_matches']===true,'lab HMAC match');
foreach(['tenant_authorized','consent_proven','crm_durable_receipt_proven',
  'can_send_report','can_send_marketing','can_store_lead'] as $flag){
    assertInterop($good[$flag]===false,'deny '.$flag);
}
assertInterop(!array_key_exists('email',$good),'no contact data output');

$mutate=static function(array $changes)use($payload,$signature,$key):array{
    return Inspector::inspect(['payload'=>array_replace($payload,$changes),'signature'=>$signature],
      $key,1794672030);
};
foreach([
    ['media_total_cents'=>430001],
    ['marketing_requested'=>true],
    ['channel_ids'=>['google_ads','meta_ads']],
    ['submission_id'=>'123e4567-e89b-42d3-a456-426614174001'],
    ['currency'=>'USD'],
    ['nonce'=>$payload['submission_id']],
    ['notice_version'=>'production-proof'],
    ['email'=>'private@example.test'],
    ['report_requested'=>false,'marketing_requested'=>false],
] as $mutation){
    assertInterop($mutate($mutation)['synthetic_mac_matches']===false,'tampered or malformed input denied');
}
assertInterop(Inspector::inspect(['payload'=>$payload,'signature'=>$signature],$key,1794672300)
    ['synthetic_mac_matches']===false,'expired');
assertInterop(Inspector::inspect(['payload'=>$payload,'signature'=>$signature],'bad',1794672030)
    ['synthetic_mac_matches']===false,'invalid key');
assertInterop(Inspector::inspect(['payload'=>$payload,'signature'=>str_repeat('f',64)],$key,1794672030)
    ['synthetic_mac_matches']===false,'invalid signature');
$opt=$payload;$opt['marketing_requested']=true;
$sigOpt=hash_hmac('sha256',Inspector::signingBytes($opt),$key);
$marketing=Inspector::inspect(['payload'=>$opt,'signature'=>$sigOpt],$key,1794672030);
assertInterop($marketing['synthetic_mac_matches']===true,'allowed to model true selection');
assertInterop($marketing['consent_proven']===false && $marketing['can_send_marketing']===false,
  'marketing selection is NOT valid opt-in proof');
echo "OK: PHP verifies fixed Node calculator intent signing bytes; no CRM writes, opt-in, email or marketing\n";
