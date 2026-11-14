<?php
declare(strict_types=1);

require_once __DIR__.'/../app/Core/Leads/SyntheticCalculatorContactBoundary.php';
use App\Core\Leads\SyntheticCalculatorContactBoundary as Boundary;

function eqContact(mixed $a,mixed $b,string $name): void {
    if ($a!==$b) throw new RuntimeException('Synthetic contact failed: '.$name);
}
function rejectsContact(callable $run,string $name): void {
    try{$run();}catch(InvalidArgumentException){return;}
    throw new RuntimeException('Synthetic contact incorrectly accepted: '.$name);
}

$key='IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$base=[
    'submission_id'=>'123e4567-e89b-42d3-a456-426614174000',
    'contact_name'=>'Sample Customer',
    'contact_email'=>'client@example.test',
    'report_requested'=>true,
    'marketing_requested'=>false,
    'privacy_notice_version'=>'lab-notice-v1',
];
$record=Boundary::inspect($base,$key);
eqContact($record['state'],'SYNTHETIC_CONTACT_VALIDATION_ONLY','state');
eqContact($record['report_request_claimed'],true,'report requested independently');
eqContact($record['marketing_interest_claimed'],false,'marketing not implied');
eqContact(strlen($record['contact_fingerprint']),64,'keyed contact token');
eqContact($record['contact_fingerprint'],hash_hmac('sha256','client@example.test',$key),'deterministic token');
foreach(['contact_identity_verified','marketing_consent_proven','can_send_report',
  'can_send_marketing','can_store_pii','can_enroll_nurture',
  'real_crm_lead_created','report_requested_by_verified_contact'] as $name)
  eqContact($record[$name],false,'denied '.$name);
eqContact(array_key_exists('contact_name',$record),false,'name omitted');
eqContact(array_key_exists('contact_email',$record),false,'email omitted');
eqContact(str_contains(json_encode($record,JSON_THROW_ON_ERROR),'client@example.test'),
  false,'no contact in JSON');
$opt=$base;$opt['marketing_requested']=true;
$m=Boundary::inspect($opt,$key);
eqContact($m['marketing_interest_claimed'],true,'marketing selection only');
eqContact($m['marketing_consent_proven'],false,'checkbox cannot grant consent');
eqContact($m['can_send_marketing'],false,'no marketing');
eqContact($m['contact_fingerprint'],$record['contact_fingerprint'],'same contact token');

foreach([
    ['contact_name'=>'Actual Person'],['contact_email'=>'someone@gmail.com'],
    ['contact_email'=>'lead@example.com'],['contact_email'=>'client@example.test<script>'],
    ['contact_email'=>str_repeat('a',110).'@example.test'],
    ['contact_email'=>'UPPER@example.test'],['contact_name'=>'Sample <script>'],
    ['contact_name'=>'Sample '],['contact_name'=>str_repeat('a',150)],
    ['privacy_notice_version'=>'real-privacy-2026'],['marketing_requested'=>'true'],
    ['report_requested'=>false,'marketing_requested'=>false],
    ['submission_id'=>'../other'],['contact_verified'=>true],
    ['marketing_consent_proven'=>true],['tenant_id'=>'outside'],
] as $bad){
    rejectsContact(fn()=>Boundary::inspect(array_replace($base,$bad),$key),
      'unexpected/real data or forged contact/consent field');
}
rejectsContact(fn()=>Boundary::inspect($base,'short'),'no fixture HMAC key');
echo "PASS: synthetic-only contacts, redacted HMAC, independent opt-in claims and NO operational permissions\n";
