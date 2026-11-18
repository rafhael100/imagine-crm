<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Automation\RuleDefinition;
use App\Homologation\S2S\OwnerDraftContract;
use App\Homologation\S2S\StrictJson;
use InvalidArgumentException;
use stdClass;

/** Reuse a persisted native plan. This pure check neither authorizes nor executes it. */
final class OwnerRuleDraftSource
{
    private static function number(mixed $value,int $minimum,int $maximum): int
    {
        if (is_string($value) && preg_match('/\A(?:0|[1-9][0-9]*)\z/D',$value)===1 && (string)(int)$value===$value) $value=(int)$value;
        if (!OwnerDraftContract::integer($value,$minimum,$maximum)) throw new InvalidArgumentException('OWNER_DRAFT_SOURCE_NUMBER');
        return $value;
    }

    public static function business(string $company,string $actor,string $draft,stdClass $run,stdClass $lead,AutomationSettings $settings): array
    {
        if (($run->organization_id??null)!==$company || ($run->actor_id??null)!==$actor
            || ($run->state??null)!=='pending' || ($run->template??null)!=='followup_qualified'
            || !in_array('followup_qualified',$settings->rules,true)
            || ($lead->organization_id??null)!==$company || ($lead->id??null)!==($run->lead_id??null)
            || ($lead->stage??null)!=='qualified') throw new InvalidArgumentException('OWNER_DRAFT_SOURCE_INELIGIBLE');
        $version=self::number($run->settings_version??null,0,20);
        $leadVersion=self::number($run->lead_version??null,1,2147483647);
        $created=self::number($run->created_at??null,1,4102444800);
        if ($version!==$settings->version || $leadVersion!==self::number($lead->version??null,1,2147483647)) {
            throw new InvalidArgumentException('OWNER_DRAFT_SOURCE_CHANGED');
        }
        $rule=RuleDefinition::fromArray(['tenant'=>'t-'.$company,'id'=>'followup_qualified','template'=>'followup_qualified',
            'version'=>max(1,$version),'delay_seconds'=>$settings->delaySeconds,'recipient'=>'p-'.$actor]);
        $expected=$rule->plan('t-'.$company,['tenant'=>'t-'.$company,'id'=>'e-'.$lead->id.'-'.$leadVersion,
            'resource'=>'r-'.$lead->id,'stage'=>'qualified','trigger'=>'lead.stage.changed','occurred_at'=>$created],$created);
        $command=StrictJson::decodeObject($run->command??'',4096,8);
        if (!$expected || OwnerDraftContract::canonical($expected)!==OwnerDraftContract::canonical($command)
            || ($run->execution_key??null)!==$expected['idempotency_key']) {
            throw new InvalidArgumentException('OWNER_DRAFT_SOURCE_COMMAND');
        }
        return OwnerDraftContract::business([
            'schema'=>OwnerDraftContract::BUSINESS,'company_id'=>$company,'draft_ref'=>$draft,'creator_id'=>$actor,
            'source_run_id'=>$run->id,'rule_id'=>'followup_qualified','settings_version'=>$version,
            'lead_id'=>$lead->id,'lead_version'=>$leadVersion,'source_created_at'=>$created,
            'command_hash'=>hash('sha256',OwnerDraftContract::canonical($command)),
            'projection'=>['action'=>'task.followup.create','stage'=>'qualified','delay_seconds'=>$settings->delaySeconds,
                'due_at'=>$expected['due_at']],
        ]);
    }
}
