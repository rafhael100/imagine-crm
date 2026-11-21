<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\PortalIngressPreparationBoundary;
use App\Core\Leads\PortalIngressPreparationAssessment;
use PHPUnit\Framework\TestCase;

final class PortalIngressPreparationBoundaryTest extends TestCase
{
    private const KEY='fixture-f348-not-prod-contains-48-bytes-of-material-00000';
    private const NOW=1800000000;
    private const NONCE='abcdefab-cdef-4abc-8abc-abcdefabcdef';

    private static function envelope(): array
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
    private static function binding(): array
    {
        $e=self::envelope();
        unset($e['protocol'],$e['schema_version'],$e['submission_reference']);
        return $e;
    }
    private static function sign(array $e,string $op='deliver',
        int $iat=self::NOW,int $exp=self::NOW+120,string $nonce=self::NONCE,
        string $key=self::KEY): array
    {
        $domain=$op==='lookup'
            ? 'imagine.portal.synthetic-crm-lookup.v1'
            : 'imagine.portal.synthetic-service-request.v1';
        $parts=[$domain];
        foreach ([
            'organization_reference','binding_reference','submission_reference',
            'site_reference','form_reference','destination_reference'
        ] as $k) $parts[]=strtolower($e[$k]);
        array_push($parts,(string)$iat,(string)$exp,$nonce);
        return [
            'issued_at'=>$iat,'expires_at'=>$exp,'nonce'=>$nonce,
            'mac'=>hash_hmac('sha256',implode("\n",$parts),$key)
        ];
    }
    private static function assess(array $e,mixed $binding=null,mixed $auth=null,
        string $op='deliver',string $key=self::KEY): PortalIngressPreparationAssessment
    {
        $binding??=self::binding();
        $auth??=self::sign($e);
        return (new PortalIngressPreparationBoundary())->inspect(
            json_encode($e,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
            $binding,$auth,self::NOW+10,$key,$op
        );
    }
    private static function inert(PortalIngressPreparationAssessment $r): void
    {
        foreach ([
            'canAuthenticateService','canAcceptLead','canPersistLead',
            'canAcknowledgeVisitor','canQueueDelivery',
            'canRetryUnknownOutcome','canOpenPublicRoute'
        ] as $method) self::assertFalse($r->$method(),$method);
        self::assertSame(['reasonCode'],array_keys((array)$r));
        $out=json_encode($r,JSON_THROW_ON_ERROR);
        foreach ([self::KEY,'11111111-1111',self::NONCE,'33333333-3333'] as $value) {
            self::assertStringNotContainsString($value,$out);
        }
    }

    public function test_integrated_syntactic_match_still_never_authorizes_any_effect(): void
    {
        $r=self::assess(self::envelope());
        self::assertSame('LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED',$r->reasonCode);
        self::assertTrue($r->isMetadataSyntaxAndMacConsistent());
        self::inert($r);
        // A repeated request is indistinguishable here: no replay store.
        self::inert(self::assess(self::envelope()));
    }

    public function test_same_domain_mac_cannot_move_from_lookup_to_delivery(): void
    {
        $e=self::envelope();
        $lookup=self::sign($e,'lookup');
        $ok=(new PortalIngressPreparationBoundary())->inspect(
            json_encode($e,JSON_THROW_ON_ERROR),self::binding(),$lookup,
            self::NOW+10,self::KEY,'lookup'
        );
        self::assertSame('LAB_TECHNICAL_METADATA_MATCH_UNTRUSTED',$ok->reasonCode);
        $bad=self::assess($e,self::binding(),$lookup);
        self::assertSame('AUTH_MAC_MISMATCH',$bad->reasonCode);
        self::inert($bad);
        self::assertSame('OPERATION_UNSUPPORTED',self::assess($e,op:'admin')->reasonCode);
    }

    public function test_binding_and_destination_are_selected_independently_from_client_json(): void
    {
        foreach (array_keys(self::binding()) as $key) {
            $e=self::envelope();
            $e[$key]='77777777-7777-4777-8777-777777777777';
            $r=self::assess($e,auth:self::sign($e));
            self::assertSame('PINNED_CONTEXT_MISMATCH',$r->reasonCode,$key);
            self::inert($r);
        }
    }

    public function test_duplicate_unicode_json_key_is_rejected_before_hmac(): void
    {
        $e=self::envelope();
        $raw=json_encode($e,JSON_THROW_ON_ERROR);
        $duplicate='{"\u0070rotocol":"other",'.substr($raw,1);
        $r=(new PortalIngressPreparationBoundary())->inspect(
            $duplicate,self::binding(),self::sign($e),
            self::NOW+10,self::KEY,'deliver'
        );
        self::assertSame('DUPLICATE_JSON_KEY_REJECTED',$r->reasonCode);
        self::inert($r);
    }

    public function test_extra_personal_fields_nested_json_and_oversize_fail_before_mac(): void
    {
        $e=self::envelope();
        foreach ([
            [...$e,'email'=>'synthetic@example.invalid'],
            [...$e,'phone'=>'5555555'],
            [...$e,'message'=>'synthetic'],
            [...$e,'consent'=>true],
            [...$e,'principal_id'=>'admin'],
            [...$e,'payload'=>['name'=>'SYNTHETIC']],
        ] as $extra) {
            $r=self::assess($extra,auth:self::sign($e));
            self::assertSame('TECHNICAL_JSON_REJECTED',$r->reasonCode);
            self::inert($r);
        }
        $raw=json_encode($e,JSON_THROW_ON_ERROR);
        $r=(new PortalIngressPreparationBoundary())->inspect(
            $raw.str_repeat(' ',2500),self::binding(),self::sign($e),
            self::NOW+10,self::KEY,'deliver'
        );
        self::assertSame('TECHNICAL_JSON_REJECTED',$r->reasonCode);
        self::inert($r);
    }

    public function test_invalid_mac_timing_headers_and_weak_key_fail_closed(): void
    {
        $e=self::envelope();
        $good=self::sign($e);
        foreach ([
            [$good,str_repeat('x',48),'AUTH_MAC_MISMATCH'],
            [[...$good,'mac'=>str_repeat('a',64)],self::KEY,'AUTH_MAC_MISMATCH'],
            [[...$good,'issued_at'=>self::NOW+1000],self::KEY,'AUTH_WINDOW_INVALID'],
            [[...$good,'expires_at'=>self::NOW+301],self::KEY,'AUTH_WINDOW_INVALID'],
            [[...$good,'expires_at'=>self::NOW],self::KEY,'AUTH_WINDOW_INVALID'],
            [[...$good,'nonce'=>'not-a-uuid'],self::KEY,'AUTH_INPUT_INVALID'],
            [$good,'short','AUTH_INPUT_INVALID'],
            [[...$good,'extra'=>'admin'],self::KEY,'AUTH_INPUT_INVALID']
        ] as [$auth,$key,$reason]) {
            $r=self::assess($e,auth:$auth,key:$key);
            self::assertSame($reason,$r->reasonCode);
            self::inert($r);
        }
    }
}
