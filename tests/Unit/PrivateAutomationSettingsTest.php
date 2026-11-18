<?php

declare(strict_types=1);
namespace Tests\Unit;
use App\Homologation\AutomationSettings;
use PHPUnit\Framework\TestCase;

final class PrivateAutomationSettingsTest extends TestCase
{
 public function test_legacy_rules_are_read_without_losing_state(): void {
  $old=AutomationSettings::read('["followup_qualified"]');
  self::assertSame(0,$old->version); self::assertSame(['followup_qualified'],$old->rules);
  $new=$old->revise(0,[],'synthetic-owner',1800000000);
  self::assertSame(1,$new->version); self::assertSame([],AutomationSettings::read($new->json())->rules);
  self::assertSame('synthetic-owner',$new->history[0]['actor']);
 }
 public function test_stale_changes_fail_closed(): void {
  $current=AutomationSettings::read('[]')->revise(0,['followup_proposal'],'synthetic-owner',1800000000);
  $this->expectException(\LogicException::class);$current->revise(0,[],'synthetic-owner',1800000001);
 }
 public function test_unknown_sensitive_rules_are_denied(): void {
  $this->expectException(\InvalidArgumentException::class); AutomationSettings::read('["billing.charge"]');
 }
 public function test_revision_limit_cannot_silently_discard_history(): void {
  $current=AutomationSettings::read('[]');for($i=0;$i<20;$i++)$current=$current->revise($i,[],'synthetic-owner',1800000000+$i);
  self::assertCount(20,AutomationSettings::read($current->json())->history);
  $this->expectException(\LogicException::class);$current->revise(20,[],'synthetic-owner',1800000030);
 }
}
