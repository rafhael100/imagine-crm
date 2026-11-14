<?php
declare(strict_types=1);

require_once __DIR__.'/../app/Core/Leads/SyntheticCalculatorEducationCatalog.php';
use App\Core\Leads\SyntheticCalculatorEducationCatalog as Copy;

function checkCopy(bool $test,string $label): void {
    if(!$test)throw new RuntimeException('Education catalog: '.$label);
}
function rejectCopy(callable $callback,string $label): void {
    try{$callback();}catch(InvalidArgumentException){return;}
    throw new RuntimeException('Education catalog accepted invalid input: '.$label);
}
$kinds=[
    0=>'welcome_and_budget_guide',
    3=>'compare_channels',
    7=>'campaign_planning_checklist',
    14=>'optional_consultation_invitation',
];
foreach(['pt-BR','en-US','es-ES'] as $locale) {
    $subjects=[];
    foreach($kinds as $day=>$kind){
        $copy=Copy::preview($locale,$day);
        checkCopy($copy['schema']==='synthetic.calculator.education-copy.v1','schema');
        checkCopy($copy['kind']===$kind && $copy['day_offset']===$day,'schedule');
        checkCopy($copy['locale']===$locale,'locale');
        checkCopy(strlen($copy['subject'])>18 && strlen($copy['subject'])<125,'subject size');
        checkCopy(strlen($copy['body'])>80 && strlen($copy['body'])<600,'body size');
        checkCopy(!isset($subjects[$copy['subject']]),'unique subject');
        $subjects[$copy['subject']]=true;
        foreach(['preview_only','recipient_present','marketing_permission_proven',
            'delivery_authorized','mail_transport_enabled','can_schedule','can_send']
            as $flag){
            checkCopy($copy[$flag]===($flag==='preview_only'),'disabled '.$flag);
        }
        checkCopy(!str_contains(json_encode($copy,JSON_THROW_ON_ERROR),'@'),
            'no e-mail address or recipient in preview');
        checkCopy(!str_contains($copy['body'],'100%'),'no hard result claim');
    }
}
foreach(['pt','es','en','fr-FR','pt-PT','','PT-BR'] as $locale) {
    rejectCopy(fn()=>Copy::preview($locale,0),'unsupported locale');
}
foreach([-1,1,4,6,10,13,15,100] as $day) {
    rejectCopy(fn()=>Copy::preview('pt-BR',$day),'unsupported schedule');
}
echo "PASS: 12 education copy previews PT/EN/ES, days 0/3/7/14, no recipients, no sends or implicit marketing consent\n";
