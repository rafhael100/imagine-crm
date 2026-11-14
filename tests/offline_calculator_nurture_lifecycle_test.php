<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Leads/SyntheticCalculatorNurtureLifecycle.php';

use App\Core\Leads\SyntheticCalculatorNurtureLifecycle as N;

function evt(int $i, string $type, string $when): array {
    return ['event_id'=>'evt_'.str_pad(dechex($i),16,'0',STR_PAD_LEFT),
        'type'=>$type,'occurred_at'=>$when];
}
function sameN(mixed $a,mixed $b,string $label): void {
    if($a!==$b)throw new RuntimeException('Lifecycle check failed: '.$label);
}
function rejectN(callable $task,string $label): void {
    try {$task();}catch(InvalidArgumentException){return;}
    throw new RuntimeException('Expected lifecycle fail-closed rejection: '.$label);
}

$now='2026-11-14T17:00:00Z';
$empty=N::evaluate([], $now);
sameN($empty['marketing_state'],'not_requested','no implicit opt-in');
sameN($empty['candidate_educational_steps'],[],'no implicit messages');

$report=N::evaluate([evt(1,'report_requested','2026-11-14T12:00:00Z')],$now);
sameN($report['report_requested_claimed'],true,'report intent');
sameN($report['marketing_state'],'not_requested','report request not marketing');
sameN($report['can_send_report'],false,'no real delivery');
sameN($report['can_send_marketing'],false,'no real marketing');

$pending=N::evaluate([evt(2,'marketing_requested','2026-11-14T12:00:00Z')],$now);
sameN($pending['marketing_state'],'pending_unverified','email confirmation required');
sameN($pending['candidate_educational_steps'],[],'cannot draft sequence before confirmation');
sameN($pending['opt_in_proven'],false,'front-end event not proof');

$confirmed=N::evaluate([
    evt(3,'report_requested','2026-11-13T12:00:00Z'),
    evt(4,'marketing_requested','2026-11-13T12:05:00Z'),
    evt(5,'marketing_email_confirmed','2026-11-13T12:10:00Z'),
],$now);
sameN($confirmed['marketing_state'],'synthetic_confirmed_unverified','unverified confirmation semantics');
sameN(count($confirmed['candidate_educational_steps']),4,'proposed schedule count');
sameN($confirmed['candidate_educational_steps'][3]['day_offset'],14,'2-week invitation');
sameN($confirmed['opt_in_proven'],false,'no trusted consent proof');
sameN($confirmed['double_opt_in_proven'],false,'lab does not confirm email');
sameN($confirmed['can_send_marketing'],false,'no operational marketer');
sameN($confirmed['can_queue_jobs'],false,'no queue');
sameN($confirmed['can_persist_lead'],false,'no storage');

$revoked=N::evaluate([
    evt(6,'marketing_requested','2026-11-13T12:00:00Z'),
    evt(7,'marketing_email_confirmed','2026-11-13T12:10:00Z'),
    evt(8,'marketing_revoked','2026-11-14T12:00:00Z')
],$now);
sameN($revoked['marketing_state'],'revoked','unsubscribe persists');
sameN($revoked['candidate_educational_steps'],[],'unsubscribe removes planned sends');
sameN($revoked['revoke_claimed'],true,'revocation signal');
sameN($revoked['can_send_marketing'],false,'no delivery');

$resubscribed=N::evaluate([
    evt(9,'marketing_requested','2026-11-11T12:00:00Z'),
    evt(10,'marketing_revoked','2026-11-11T13:00:00Z'),
    evt(11,'marketing_requested','2026-11-12T12:00:00Z'),
],$now);
sameN($resubscribed['marketing_state'],'pending_unverified','fresh request after revoke');
sameN($resubscribed['synthetic_marketing_cycles'],2,'distinct consent cycles');
sameN($resubscribed['candidate_educational_steps'],[],'cannot reuse old confirmation');
rejectN(fn()=>N::evaluate([
    evt(12,'marketing_requested','2026-11-11T12:00:00Z'),
    evt(13,'marketing_revoked','2026-11-11T13:00:00Z'),
    evt(14,'marketing_email_confirmed','2026-11-12T12:00:00Z'),
],$now),'post-revoke confirmation replay');

$expired=N::evaluate([evt(15,'marketing_requested','2026-11-01T12:00:00Z')],$now);
sameN($expired['marketing_state'],'expired_unconfirmed','expired pending opt-in');
rejectN(fn()=>N::evaluate([
    evt(16,'marketing_requested','2026-11-01T12:00:00Z'),
    evt(17,'marketing_email_confirmed','2026-11-14T16:00:00Z')
],$now),'expired token');

$reported=N::evaluate([
    evt(18,'report_requested','2026-11-13T12:00:00Z'),
    evt(19,'report_delivery_claimed','2026-11-13T12:05:00Z')
],$now);
sameN($reported['report_delivery_claimed'],true,'independent report claimed delivered');
sameN($reported['report_delivery_proven'],false,'no real transport confirmation');

rejectN(fn()=>N::evaluate([evt(20,'report_delivery_claimed','2026-11-14T12:00:00Z')],$now),'delivery without request');
rejectN(fn()=>N::evaluate([evt(21,'marketing_email_confirmed','2026-11-14T12:00:00Z')],$now),'confirmation without request');
rejectN(fn()=>N::evaluate([evt(22,'marketing_revoked','2026-11-14T12:00:00Z')],$now),'unsubscribe without prior intent');
rejectN(fn()=>N::evaluate([evt(23,'report_requested','2026-11-15T12:00:00Z')],$now),'future event');
rejectN(fn()=>N::evaluate([evt(24,'report_requested','2026-02-30T12:00:00Z')],$now),'impossible date');
rejectN(fn()=>N::evaluate([evt(25,'report_requested','2026-11-14T12:00:00-03:00')],$now),'ambiguous timezone');
rejectN(fn()=>N::evaluate([evt(26,'report_requested','2026-11-14T13:00:00Z'),evt(27,'marketing_requested','2026-11-14T12:00:00Z')],$now),'unordered events');
rejectN(fn()=>N::evaluate([evt(28,'report_requested','2026-11-14T12:00:00Z'),evt(28,'report_requested','2026-11-14T13:00:00Z')],$now),'duplicate event ID');
rejectN(fn()=>N::evaluate([evt(29,'report_requested','2026-11-14T12:00:00Z'),evt(30,'report_requested','2026-11-14T13:00:00Z')],$now),'repeat report');
rejectN(fn()=>N::evaluate([evt(31,'marketing_requested','2026-11-14T12:00:00Z'),evt(32,'marketing_requested','2026-11-14T13:00:00Z')],$now),'repeat marketing request');
rejectN(fn()=>N::evaluate([evt(33,'report_requested','2026-11-14T12:00:00Z')+['tenant_id'=>'other']],$now),'extra private fields');
$invalid=evt(34,'report_requested','2026-11-14T12:00:00Z');$invalid['claimed_consent_proven']=true;
rejectN(fn()=>N::evaluate([$invalid],$now),'spoofed grant flag');

echo "OK: offline consent cycle, report separation, email confirmation, revocation, expiry and no marketing side effects\n";
