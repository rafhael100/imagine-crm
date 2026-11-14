<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Leads/SyntheticCalculatorNurtureConsentInspector.php';

use App\Core\Leads\SyntheticCalculatorNurtureConsentInspector as Lab;

function eqBudget(mixed $a,mixed $b,string $message): void {
    if($a!==$b)throw new RuntimeException('Failure: '.$message);
}
function rejectBudget(callable $fn,string $message): void {
    try {$fn();}catch(InvalidArgumentException){return;}
    throw new RuntimeException('Expected fail closed: '.$message);
}
$draft=[
    'source'=>'imagine_comercial_calculadora_performance',
    'schema'=>'synthetic.calculator.lead-intent.v1',
    'email'=>'sample@example.test',
    'name'=>'Contato Sintetico',
    'channels'=>['meta_ads','google_ads','chatgpt_ads'],
    'report_requested'=>true,
    'marketing_opt_in'=>false,
    'notice_version'=>'lab-notice-v1',
    'budget_estimate_cents'=>430000
];
$a=Lab::review($draft);
eqBudget($a['report_request_claimed'],true,'report claim');
eqBudget($a['marketing_opt_in_claimed'],false,'marketing separate');
eqBudget($a['consent_proven'],false,'no proof');
eqBudget($a['tenant_authorized'],false,'no tenant');
eqBudget($a['report_delivery_allowed'],false,'no email activation');
eqBudget($a['marketing_enrollment_allowed'],false,'no nurture without verified proof');
eqBudget($a['can_store_personal_data'],false,'no data storage');
eqBudget(array_key_exists('email',$a),false,'email never appears in output');
eqBudget(array_key_exists('name',$a),false,'name never appears in output');

$opt=$draft;$opt['marketing_opt_in']=true;
$b=Lab::review($opt);
eqBudget($b['marketing_opt_in_claimed'],true,'marketing claim exists');
eqBudget($b['marketing_enrollment_allowed'],false,'unverified claim cannot enroll');
eqBudget($b['can_start_nurture_sequence'],false,'no side effects');

$bad=$draft;$bad['consent_proven']=true;
rejectBudget(fn()=>Lab::review($bad),'spoofed consent');
$bad=$draft;$bad['marketing_opt_in']='true';
rejectBudget(fn()=>Lab::review($bad),'string flag');
$bad=$draft;$bad['marketing_opt_in']=false;$bad['report_requested']=false;
rejectBudget(fn()=>Lab::review($bad),'no request');
$bad=$draft;$bad['channels'][]='unlisted_ads';
rejectBudget(fn()=>Lab::review($bad),'unknown channel');
$bad=$draft;$bad['channels']=['meta_ads','meta_ads'];
rejectBudget(fn()=>Lab::review($bad),'duplicate channel');
$bad=$draft;$bad['budget_estimate_cents']='430000';
rejectBudget(fn()=>Lab::review($bad),'non-integer money');
$bad=$draft;$bad['email']='not-an-email';
rejectBudget(fn()=>Lab::review($bad),'bad email');
$bad=$draft;$bad['source']='some_other_tenant';
rejectBudget(fn()=>Lab::review($bad),'cross-app source');
$bad=$draft;$bad['notice_version']='privacy-production';
rejectBudget(fn()=>Lab::review($bad),'fake production privacy proof');

echo "OK: calculator lead report and marketing consent claims remain distinct, deny all real deliveries\n";
