<?php
declare(strict_types=1);
require_once __DIR__.'/../app/Core/Leads/CalculatorStudyPreviewV1.php';
use App\Core\Leads\CalculatorStudyPreviewV1 as Study;

function okStudy(bool $condition,string $label): void {
    if (!$condition) throw new RuntimeException('Calculator study preview failure: '.$label);
}
function deniedStudy(callable $action,string $label): void {
    try {$action();}catch(InvalidArgumentException){return;}
    throw new RuntimeException('Unsafe calculator report accepted: '.$label);
}
$rows=[
    ['channel'=>'meta_ads','media_cents'=>100000],
    ['channel'=>'google_ads','media_cents'=>150000],
    ['channel'=>'chatgpt_ads','media_cents'=>180000],
];
foreach(['pt-BR','en-US','es-ES'] as $locale) {
    $r=Study::render($locale,$rows);
    okStudy($r['locale']===$locale,'locale match');
    okStudy($r['media_cents']===430000,'exact recommended sum');
    okStudy($r['management_preview_cents']===86000,'20 percent management preview');
    okStudy($r['informational_total_cents']===516000,'informational total');
    okStudy(substr_count($r['html'],'<tr>')===4,'exact 3 provider rows plus heading');
    okStudy(str_contains($r['html'],'#F9941D'),'brand color included');
    okStudy(str_contains($r['html'],'ChatGPT Ads'),'ChatGPT as third channel');
    okStudy(str_contains($r['text'],'Google Ads'),'Google as second channel');
    okStudy(str_contains($r['text'],'Meta Ads'),'Meta as first channel');
    okStudy(!str_contains($r['html'],'<script'),'no scripts or tracking');
    okStudy(!str_contains($r['html'],'example.test'),'no recipient fixture');
    okStudy($r['not_a_contract']===true,'simulation only');
    foreach(['marketing_consent_proven','recipient_present','delivery_authorized','can_send','can_bill'] as $flag)
        okStudy($r[$flag]===false,'deny '.$flag);
    okStudy(!str_contains($r['text'],'mailto:'),'not a sender');
}
$only=Study::render('pt-BR',[['channel'=>'chatgpt_ads','media_cents'=>120000]]);
okStudy($only['media_cents']===120000,'min ChatGPT');
okStudy($only['management_preview_cents']===24000,'min ChatGPT fee');
okStudy($only['informational_total_cents']===144000,'min ChatGPT total');
$edge=Study::render('pt-BR',[['channel'=>'meta_ads','media_cents'=>70001]]);
okStudy($edge['management_preview_cents']===14000,'cent-accurate rounding');
$expected=Study::render('pt-BR',$rows);
$reverse=Study::render('pt-BR',array_reverse($rows));
okStudy($expected['text']===$reverse['text'],'permutation canonical');

foreach([
   [],[['channel'=>'meta_ads','media_cents'=>69999]],
   [['channel'=>'google_ads','media_cents'=>99999]],
   [['channel'=>'chatgpt_ads','media_cents'=>119999]],
   [['channel'=>'unapproved','media_cents'=>120000]],
   [['channel'=>'meta_ads','media_cents'=>70000],['channel'=>'meta_ads','media_cents'=>70000]],
   [['channel'=>'meta_ads','media_cents'=>'70000']],
   [['channel'=>'meta_ads','media_cents'=>-1]],
   [['channel'=>'meta_ads','media_cents'=>100000001]],
   [['channel'=>'meta_ads','media_cents'=>70000,'contact_email'=>'actual@example.com']],
   [['media_cents'=>70000,'channel'=>'meta_ads']],
] as $invalid)deniedStudy(fn()=>Study::render('pt-BR',$invalid),'invalid row');
foreach(['pt','en','es','fr-FR','', 'pt-PT'] as $locale)
    deniedStudy(fn()=>Study::render($locale,$rows),'unsupported locale');
echo "PASS: PT/EN/ES study HTML + plain text; 3 provider sums, cent rounding, canonical rows, no recipient or send permissions\n";
