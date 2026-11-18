<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Automation\RuleDefinition;
use App\Homologation\AutomationSettings;
use App\Homologation\OwnerRuleDraftSource;
use App\Homologation\S2S\OwnerDraftContract;
use PHPUnit\Framework\TestCase;

final class PrivateOwnerRuleDraftSourceTest extends TestCase
{
    private const COMPANY='11111111-1111-4111-8111-111111111111';
    private const ACTOR='22222222-2222-4222-8222-222222222222';
    private const LEAD='33333333-3333-4333-8333-333333333333';
    private const RUN='44444444-4444-4444-8444-444444444444';
    private const DRAFT='55555555-5555-4555-8555-555555555555';

    private function fixture(bool $legacy=false): array
    {
        $settings=AutomationSettings::read($legacy?'["followup_qualified"]':'[]');
        if (!$legacy) $settings=$settings->revise(0,['followup_qualified'],self::ACTOR,1800000000,3600);
        $command=RuleDefinition::fromArray(['tenant'=>'t-'.self::COMPANY,'id'=>'followup_qualified',
            'template'=>'followup_qualified','version'=>max(1,$settings->version),
            'delay_seconds'=>$settings->delaySeconds,'recipient'=>'p-'.self::ACTOR])
            ->plan('t-'.self::COMPANY,['tenant'=>'t-'.self::COMPANY,'id'=>'e-'.self::LEAD.'-2',
                'resource'=>'r-'.self::LEAD,'stage'=>'qualified','trigger'=>'lead.stage.changed','occurred_at'=>1800000000],1800000000);
        $lead=(object)['id'=>self::LEAD,'organization_id'=>self::COMPANY,'stage'=>'qualified','version'=>2];
        $run=(object)['id'=>self::RUN,'organization_id'=>self::COMPANY,'actor_id'=>self::ACTOR,'state'=>'pending',
            'template'=>'followup_qualified','settings_version'=>$settings->version,'lead_id'=>self::LEAD,'lead_version'=>2,
            'created_at'=>1800000000,'due_at'=>$command['due_at'],'command'=>json_encode($command,JSON_THROW_ON_ERROR),
            'execution_key'=>$command['idempotency_key'],'authority_session_id'=>'66666666-6666-4666-8666-666666666666'];
        return [$settings,$run,$lead];
    }

    private function business(array $fixture): array
    {
        [$settings,$run,$lead]=$fixture;
        return OwnerRuleDraftSource::business(self::COMPANY,self::ACTOR,self::DRAFT,$run,$lead,$settings);
    }

    public function test_reuses_the_real_native_plan_and_does_not_forward_its_session_or_raw_command(): void
    {
        $fixture=$this->fixture();$before=serialize($fixture);
        $business=$this->business($fixture);
        self::assertSame(['action'=>'task.followup.create','stage'=>'qualified','delay_seconds'=>3600,'due_at'=>1800003600],$business['projection']);
        self::assertSame(1,$business['settings_version']);
        self::assertSame(self::ACTOR,$business['creator_id']);
        self::assertSame($before,serialize($fixture));
        $json=OwnerDraftContract::canonical($business);
        self::assertStringNotContainsString('authority_session_id',$json);
        self::assertStringNotContainsString('66666666-6666-4666-8666-666666666666',$json);
        self::assertStringNotContainsString('CRM_NATIVE_COMMAND_V1',$json);
    }

    public function test_raw_legacy_revision_zero_is_preserved_while_native_command_version_remains_one(): void
    {
        $fixture=$this->fixture(true);
        self::assertSame(1,json_decode($fixture[1]->command,true,8,JSON_THROW_ON_ERROR)['version']);
        self::assertSame(0,$this->business($fixture)['settings_version']);
    }

    public function test_native_retry_schedule_does_not_rewrite_the_original_planned_due_time(): void
    {
        $fixture=$this->fixture();$expected=$this->business($fixture);
        $fixture[1]->due_at=1800009999;
        self::assertSame($expected,$this->business($fixture));
    }

    public function test_changed_rule_lead_actor_company_or_command_cannot_supply_a_draft(): void
    {
        $changes=[
            static function (&$f): void {$f[1]->actor_id=self::LEAD;},
            static function (&$f): void {$f[1]->organization_id=self::LEAD;},
            static function (&$f): void {$f[1]->state='completed';},
            static function (&$f): void {$f[1]->template='followup_proposal';},
            static function (&$f): void {$f[1]->settings_version=0;},
            static function (&$f): void {$f[1]->execution_key=str_repeat('0',64);},
            static function (&$f): void {$f[1]->created_at='1800000000x';},
            static function (&$f): void {$f[2]->stage='proposal';},
            static function (&$f): void {$f[2]->version=3;},
            static function (&$f): void {$f[2]->organization_id=self::ACTOR;},
            static function (&$f): void {$f[0]=$f[0]->revise(1,[],self::ACTOR,1800000001,3600);},
            static function (&$f): void {$v=json_decode($f[1]->command,true);$v['recipient']='p-'.self::LEAD;$f[1]->command=json_encode($v);},
            static function (&$f): void {$v=json_decode($f[1]->command,true);$v['ai_calls']=1;$f[1]->command=json_encode($v);},
            static function (&$f): void {$v=json_decode($f[1]->command,true);$v['due_at']++;$f[1]->command=json_encode($v);},
            static function (&$f): void {$v=json_decode($f[1]->command,true);$v['extra']='private';$f[1]->command=json_encode($v);},
        ];
        foreach ($changes as $change) {
            $fixture=$this->fixture();$change($fixture);
            try {$this->business($fixture);self::fail('An ineligible source produced a draft.');}
            catch (\InvalidArgumentException) {self::addToAssertionCount(1);}
        }
    }
}
