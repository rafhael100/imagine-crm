<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\PortalServiceHandoffInspector;
use PHPUnit\Framework\TestCase;

final class PortalServiceHandoffInspectorTest extends TestCase
{
    private const KEY = 'fixture-only-f346-really-long-cryptographic-key-not-prod';
    private const ISSUED = 1800000000;
    private const NONCE = '88888888-8888-4888-8888-888888888888';

    private static function frame(): array
    {
        return [
            'protocol'=>'imagine.lead-intake',
            'schema_version'=>1,
            'organization_reference'=>'11111111-1111-4111-8111-111111111111',
            'binding_reference'=>'22222222-2222-4222-8222-222222222222',
            'submission_reference'=>'33333333-3333-4333-8333-333333333333',
            'site_reference'=>'44444444-4444-4444-8444-444444444444',
            'form_reference'=>'55555555-5555-4555-8555-555555555555',
            'destination_reference'=>'66666666-6666-4666-8666-666666666666',
        ];
    }

    private static function pinned(): array
    {
        return array_diff_key(self::frame(), ['protocol'=>true,'schema_version'=>true,'submission_reference'=>true]);
    }

    private static function signed(array $frame, string $operation='deliver',
        int $issued=self::ISSUED, int $exp=self::ISSUED+120,
        string $nonce=self::NONCE,string $key=self::KEY): array
    {
        $domain = $operation === 'lookup'
            ? 'imagine.portal.synthetic-crm-lookup.v1'
            : 'imagine.portal.synthetic-service-request.v1';
        $message=[$domain];
        foreach ([
            'organization_reference','binding_reference','submission_reference',
            'site_reference','form_reference','destination_reference'
        ] as $field) $message[]=strtolower($frame[$field]);
        array_push($message,(string)$issued,(string)$exp,$nonce);
        return [
            'issued_at'=>$issued,
            'expires_at'=>$exp,
            'nonce'=>$nonce,
            'mac'=>hash_hmac('sha256',implode("\n",$message),$key),
        ];
    }

    private static function inspect(mixed $frame, mixed $binding, mixed $auth,
        string $operation='deliver',int $now=self::ISSUED+10,
        string $key=self::KEY): \App\Core\Leads\PortalServiceHandoffAssessment
    {
        return (new PortalServiceHandoffInspector())->inspect(
            $frame,$binding,$auth,$now,$key,$operation
        );
    }

    private static function noneAllowed(\App\Core\Leads\PortalServiceHandoffAssessment $r): void
    {
        self::assertFalse($r->canAuthenticateService());
        self::assertFalse($r->canAcceptLead());
        self::assertFalse($r->canPersistLead());
        self::assertFalse($r->canAcknowledgeVisitor());
        self::assertFalse($r->canRetryUnknownOutcome());
        self::assertFalse($r->canEnableRoute());
        self::assertFalse($r->canDeliverToCrm());
        self::assertSame(['reasonCode'],array_keys((array)$r));
        $out=json_encode($r,JSON_THROW_ON_ERROR);
        foreach ([self::KEY,'11111111-1111','33333333-3333',self::NONCE] as $secret) {
            self::assertStringNotContainsString($secret,$out);
        }
    }

    public function test_matching_request_stays_untrusted_and_authorizes_nothing(): void
    {
        $e=self::frame();
        $r=self::inspect($e,self::pinned(),self::signed($e));
        self::assertSame('LAB_REQUEST_MAC_MATCH_UNTRUSTED',$r->reasonCode);
        self::assertTrue($r->isSyntacticallyConsistent());
        self::noneAllowed($r);
    }

    public function test_lookup_domain_cannot_be_reused_for_delivery(): void
    {
        $e=self::frame();
        $lookup=self::signed($e,'lookup');
        self::assertSame('LAB_REQUEST_MAC_MATCH_UNTRUSTED',
            self::inspect($e,self::pinned(),$lookup,'lookup')->reasonCode);
        self::assertSame('AUTH_MAC_MISMATCH',
            self::inspect($e,self::pinned(),$lookup,'deliver')->reasonCode);
        self::assertSame('AUTH_MAC_MISMATCH',
            self::inspect($e,self::pinned(),self::signed($e),'lookup')->reasonCode);
        self::assertSame('OPERATION_UNSUPPORTED',
            self::inspect($e,self::pinned(),self::signed($e),'admin')->reasonCode);
    }

    public function test_independent_server_binding_is_not_copied_from_request(): void
    {
        foreach (array_keys(self::pinned()) as $field) {
            $e=self::frame();
            $e[$field]='77777777-7777-4777-8777-777777777777';
            $r=self::inspect($e,self::pinned(),self::signed($e));
            self::assertSame('PINNED_CONTEXT_MISMATCH',$r->reasonCode,$field);
            self::noneAllowed($r);
        }
        $good=self::frame();
        foreach ([
            [],array_values(self::pinned()),
            [...self::pinned(),'extra'=>'unexpected'],
            [...self::pinned(),'site_reference'=>false],
            [...self::pinned(),'site_reference'=>'00000000-0000-0000-0000-000000000000']
        ] as $bad) {
            self::assertSame('INDEPENDENT_BINDING_REQUIRED',
                self::inspect($good,$bad,self::signed($good))->reasonCode);
        }
    }

    public function test_rejects_personal_fields_unknown_envelope_and_bad_types(): void
    {
        $e=self::frame();
        foreach (['name','email','phone','message','consent','payload','client_ip','role'] as $field) {
            $bad=$e;
            $bad[$field]='synthetic-value';
            $r=self::inspect($bad,self::pinned(),self::signed($e));
            self::assertSame('ENVELOPE_INVALID',$r->reasonCode,$field);
            self::noneAllowed($r);
        }
        foreach ([null,false,[],array_values($e),['protocol'=>'imagine.lead-intake'],
            [...$e,'schema_version'=>'1'],
            [...$e,'submission_reference'=>'00000000-0000-0000-0000-000000000000']
        ] as $bad) {
            self::assertSame('ENVELOPE_INVALID',
                self::inspect($bad,self::pinned(),self::signed($e))->reasonCode);
        }
    }

    public function test_nonce_mac_header_and_key_format_are_fail_closed(): void
    {
        $e=self::frame();
        $good=self::signed($e);
        foreach ([
            [],['issued_at'=>'1800000000',...$good],
            [...$good,'nonce'=>strtoupper(self::NONCE)],
            [...$good,'nonce'=>'00000000-0000-0000-0000-000000000000'],
            [...$good,'mac'=>strtoupper($good['mac'])],
            [...$good,'mac'=>'not-a-hmac'],
            [...$good,'injected_key'=>'admin'],
            array_values($good),
        ] as $bad) {
            self::assertSame('AUTH_INPUT_INVALID',
                self::inspect($e,self::pinned(),$bad)->reasonCode);
        }
        self::assertSame('AUTH_INPUT_INVALID',
            self::inspect($e,self::pinned(),$good,key:'short')->reasonCode);
        self::assertSame('AUTH_MAC_MISMATCH',
            self::inspect($e,self::pinned(),$good,key:str_repeat('x',48))->reasonCode);
        $changed=$good; $changed['nonce']='99999999-9999-4999-8999-999999999999';
        self::assertSame('AUTH_MAC_MISMATCH',
            self::inspect($e,self::pinned(),$changed)->reasonCode);
    }

    public function test_time_limits_and_skew_boundaries(): void
    {
        $e=self::frame();
        foreach ([
            [self::ISSUED,self::ISSUED, self::ISSUED],
            [self::ISSUED,self::ISSUED+301,self::ISSUED],
            [self::ISSUED,self::ISSUED+120,self::ISSUED+120],
            [self::ISSUED+31,self::ISSUED+120,self::ISSUED],
            [self::ISSUED,self::ISSUED+120,0],
            [-1,self::ISSUED+120,self::ISSUED],
        ] as [$issued,$expires,$now]) {
            self::assertSame('AUTH_WINDOW_INVALID',
                self::inspect($e,self::pinned(),
                    self::signed($e,issued:$issued,exp:$expires),
                    now:$now)->reasonCode);
        }
        self::assertSame('LAB_REQUEST_MAC_MATCH_UNTRUSTED',
            self::inspect($e,self::pinned(),
                self::signed($e,issued:self::ISSUED+30,exp:self::ISSUED+120),
                now:self::ISSUED)->reasonCode);
    }

    public function test_replayed_mac_has_no_durable_uniqueness_evidence(): void
    {
        $e=self::frame();
        $auth=self::signed($e);
        $first=self::inspect($e,self::pinned(),$auth);
        $second=self::inspect($e,self::pinned(),$auth);
        self::assertSame('LAB_REQUEST_MAC_MATCH_UNTRUSTED',$first->reasonCode);
        self::assertSame($first->reasonCode,$second->reasonCode);
        self::noneAllowed($second);
    }
}
