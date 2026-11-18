<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Homologation\S2S\OwnerDraftContract as Contract;
use PHPUnit\Framework\TestCase;

final class PrivateOwnerDraftContractTest extends TestCase
{
    private const NOW=1800000000;
    private const KEY='abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789';

    private function business(): array
    {
        return ['schema'=>Contract::BUSINESS,'company_id'=>'11111111-1111-4111-8111-111111111111',
            'draft_ref'=>'22222222-2222-4222-8222-222222222222','creator_id'=>'33333333-3333-4333-8333-333333333333',
            'source_run_id'=>'44444444-4444-4444-8444-444444444444','rule_id'=>'followup_qualified','settings_version'=>1,
            'lead_id'=>'55555555-5555-4555-8555-555555555555','lead_version'=>2,'source_created_at'=>self::NOW-60,
            'command_hash'=>str_repeat('a',64),'projection'=>['action'=>'task.followup.create','stage'=>'qualified','delay_seconds'=>3600,'due_at'=>self::NOW+3540]];
    }

    private function attempt(string $operation=Contract::CREATE): array
    {
        $business=$this->business();
        $auth=['schema'=>Contract::AUTH,'issuer'=>'imagine-crm','audience'=>'imagine-ai-agents','operation'=>$operation,
            'company_id'=>$business['company_id'],'actor_role'=>'owner','actor_id'=>$business['creator_id'],
            'draft_ref'=>$business['draft_ref'],'business_hash'=>Contract::digest($business),
            'nonce'=>'66666666-6666-4666-8666-666666666666','issued_at'=>self::NOW,'expires_at'=>self::NOW+30,'key_id'=>'crm_owner_create_1'];
        $auth['mac']=Contract::mac($auth,self::KEY);
        $reply=['schema'=>Contract::RESPONSE,'issuer'=>'imagine-ai-agents','audience'=>'imagine-crm','operation'=>$operation,
            'company_id'=>$business['company_id'],'actor_id'=>$business['creator_id'],'draft_ref'=>$business['draft_ref'],
            'business_hash'=>$auth['business_hash'],'nonce'=>$auth['nonce'],'status'=>'DRAFT','business'=>$business,
            'issued_at'=>self::NOW,'expires_at'=>self::NOW+15,'key_id'=>$auth['key_id'],'executed'=>false,'operational'=>false];
        $reply['mac']=Contract::mac($reply,self::KEY,true);
        return [$auth,$business,$reply];
    }

    private function verify(array $auth,array $business,array $reply): array
    {
        return Contract::response(Contract::canonical($reply),$auth,$business,self::KEY,self::NOW);
    }

    public function test_response_is_bound_to_the_exact_attempt_and_original_business(): void
    {
        [$auth,$business,$reply]=$this->attempt();
        self::assertSame('DRAFT',$this->verify($auth,$business,$reply)['status']);
        $reverse=array_reverse($business,true);$reverse['projection']=array_reverse($reverse['projection'],true);
        self::assertSame(Contract::digest($business),Contract::digest($reverse));
        self::assertNotSame(Contract::mac($reply,self::KEY),Contract::mac($reply,self::KEY,true));
    }

    public function test_only_a_current_signed_read_can_confirm_absence(): void
    {
        [$auth,$business,$reply]=$this->attempt(Contract::READ);
        $reply['status']='ABSENT';$reply['business']=null;$reply['mac']=Contract::mac($reply,self::KEY,true);
        self::assertSame('ABSENT',$this->verify($auth,$business,$reply)['status']);
        $auth['operation']=Contract::CREATE;$reply['operation']=Contract::CREATE;$reply['mac']=Contract::mac($reply,self::KEY,true);
        $this->expectException(\InvalidArgumentException::class);$this->verify($auth,$business,$reply);
    }

    public function test_even_resigned_changes_to_authority_state_deadlines_or_projection_are_rejected(): void
    {
        $changes=[
            static function (&$r):void {$r['nonce']='77777777-7777-4777-8777-777777777777';},
            static function (&$r):void {$r['company_id']='77777777-7777-4777-8777-777777777777';},
            static function (&$r):void {$r['actor_id']='77777777-7777-4777-8777-777777777777';},
            static function (&$r):void {$r['business_hash']=str_repeat('0',64);},
            static function (&$r):void {$r['expires_at']=self::NOW;},
            static function (&$r):void {$r['expires_at']=self::NOW+31;},
            static function (&$r):void {$r['issued_at']=self::NOW+6;},
            static function (&$r):void {$r['status']='APPROVED';},
            static function (&$r):void {$r['executed']=true;},
            static function (&$r):void {$r['operational']=true;},
            static function (&$r):void {$r['business']['creator_id']='77777777-7777-4777-8777-777777777777';},
            static function (&$r):void {$r['business']['projection']['due_at']++;},
            static function (&$r):void {$r['business']['lead_title']='PRIVATE';},
        ];
        foreach ($changes as $change) {
            [$auth,$business,$reply]=$this->attempt();$change($reply);$reply['mac']=Contract::mac($reply,self::KEY,true);
            try {$this->verify($auth,$business,$reply);self::fail('A changed response was admitted.');}
            catch (\InvalidArgumentException) {self::addToAssertionCount(1);}
        }
    }

    public function test_duplicate_response_keys_and_invalid_mac_are_not_normalized_away(): void
    {
        [$auth,$business,$reply]=$this->attempt();
        $raw=str_replace('"status":"DRAFT"','"status":"DRAFT","status":"DRAFT"',Contract::canonical($reply));
        try {Contract::response($raw,$auth,$business,self::KEY,self::NOW);self::fail('Duplicate status accepted.');}
        catch (\JsonException) {self::addToAssertionCount(1);}
        $reply['mac']=str_repeat('0',64);
        $this->expectException(\InvalidArgumentException::class);$this->verify($auth,$business,$reply);
    }

    public function test_numeric_coercion_and_unknown_fields_never_enter_the_business_digest(): void
    {
        foreach (['1',1.0,true,false,null,21,-1] as $version) {
            $business=$this->business();$business['settings_version']=$version;
            try {Contract::digest($business);self::fail('Invalid revision accepted.');}
            catch (\InvalidArgumentException) {self::addToAssertionCount(1);}
        }
        $business=$this->business();$business['settings_version']=0;
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/',Contract::digest($business));
        $business['authority_session_id']='PRIVATE';
        $this->expectException(\InvalidArgumentException::class);Contract::digest($business);
    }
}
