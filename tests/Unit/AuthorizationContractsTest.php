<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Authorization\AccessRequest;
use App\Core\Authorization\AuthorizationDecision;
use App\Core\Authorization\AuthorizationEnvelope;
use App\Core\Authorization\AuthorizationIntent;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use App\Core\Authorization\ExperimentalResourceCatalog;
use App\Core\Identity\PrincipalReference;
use App\Core\Tenancy\MembershipSnapshot;
use App\Core\Tenancy\MembershipStatus;
use App\Core\Tenancy\TenantContext;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\AuthorizationCases as Cases;

final class AuthorizationContractsTest extends TestCase
{
    public static function malformed(): array { return Cases::malformed(); }

    #[DataProvider('malformed')]
    public function test_contract_rejects_incomplete_extra_or_ambiguous_claims(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid authorization contract.');
        AuthorizationIntent::fromArray($input);
    }

    public static function syntheticOperations(): array
    {
        $cases = [];
        $fields = ['lab_id' => 'F-ID', 'lab_status' => 'F-BIZ', 'lab_person' => 'F-PII',
            'lab_restricted' => 'F-RES', 'lab_consent' => 'F-CONS', 'lab_amount' => 'F-FIN',
            'lab_secret' => 'F-SEC', 'lab_evidence' => 'F-EVID', 'lab_artifact' => 'F-ART'];
        foreach (['read', 'search', 'update', 'export', 'report', 'execute'] as $action) {
            foreach ($fields as $name => $classification) {
                $cases[$action.' '.$classification] = [array_replace(Cases::intent(), ['action' => $action, 'fields' => [$name => $classification]])];
            }
        }
        return $cases;
    }

    #[DataProvider('syntheticOperations')]
    public function test_every_described_action_and_field_remains_denied(array $input): void
    {
        $intent = AuthorizationIntent::fromArray($input);
        $this->assertTrue((new ExperimentalResourceCatalog())->describes($intent));
        $decision = (new DenyByDefaultAuthorizer())->decide($this->request($intent));
        $this->assertFalse($decision->allowed);
        $this->assertSame('POLICY_NOT_CONFIGURED', $decision->reasonCode);
        $this->assertSame(['code', 'correlation_id'], array_keys($decision->signal()));
    }

    public static function unknownDescriptions(): array
    {
        return [
            'resource' => [['resource' => 'lead']], 'action' => [['action' => 'approve']],
            'policy' => [['policy_version' => 'lab-deny-v2']],
            'field' => [['fields' => ['lab_unknown' => 'F-BIZ']]],
            'reclassification' => [['fields' => ['lab_secret' => 'F-BIZ']]],
            'other tenant' => [['scope' => ['kind' => 'tenant', 'id' => Cases::OTHER]]],
            'other record' => [['scope' => ['kind' => 'record', 'id' => Cases::OTHER]]],
        ];
    }

    #[DataProvider('unknownDescriptions')]
    public function test_unknown_or_mismatched_descriptions_fail_closed(array $change): void
    {
        $decision = (new DenyByDefaultAuthorizer())->decide($this->request(AuthorizationIntent::fromArray(array_replace(Cases::intent(), $change))));
        $this->assertFalse($decision->allowed);
        $this->assertSame('CONTRACT_INVALID', $decision->reasonCode);
    }

    public static function unprovedAuthority(): array
    {
        return [
            'team' => [['scope' => ['kind' => 'team', 'id' => Cases::OTHER]], 'SCOPE_NOT_VERIFIED'],
            'unit' => [['scope' => ['kind' => 'unit', 'id' => Cases::OTHER]], 'SCOPE_NOT_VERIFIED'],
            'record ownership' => [['scope' => ['kind' => 'record', 'id' => Cases::RECORD]], 'SCOPE_NOT_VERIFIED'],
            'job' => [['channel' => 'job'], 'CHANNEL_AUTHORITY_MISSING'],
            'ai' => [['channel' => 'ai'], 'CHANNEL_AUTHORITY_MISSING'],
            'support without mandate' => [['channel' => 'support'], 'CHANNEL_AUTHORITY_MISSING'],
        ];
    }

    #[DataProvider('unprovedAuthority')]
    public function test_scope_channel_and_objects_never_supply_authority(array $change, string $reason): void
    {
        $decision = (new DenyByDefaultAuthorizer())->decide($this->request(AuthorizationIntent::fromArray(array_replace(Cases::intent(), $change))));
        $this->assertFalse($decision->allowed);
        $this->assertSame($reason, $decision->reasonCode);
    }

    public function test_envelope_cannot_be_replayed_against_a_different_request(): void
    {
        $request = $this->request(AuthorizationIntent::fromArray(Cases::intent()));
        $different = new TenantContext(Cases::OTHER, Cases::SUBJECT, Cases::RECORD);
        $decision = (new DenyByDefaultAuthorizer())->decide(new AccessRequest($different, 'lab.record', 'export', envelope: $request->envelope));
        $this->assertFalse($decision->allowed);
        $this->assertSame('CONTRACT_INVALID', $decision->reasonCode);
    }

    public function test_neutral_signal_never_accepts_arbitrary_reason_or_correlation_data(): void
    {
        $this->assertSame(['code' => 'CONTRACT_INVALID', 'correlation_id' => null],
            AuthorizationDecision::deny('synthetic-secret-value', 'synthetic-personal-data')->signal());
        $this->assertFalse(AuthorizationDecision::deny()->allowed);
        $this->assertFalse(method_exists(AuthorizationDecision::class, 'allow'));
    }

    public function test_inconsistent_membership_is_not_an_envelope(): void
    {
        $request = $this->request(AuthorizationIntent::fromArray(Cases::intent()));
        $this->expectException(InvalidArgumentException::class);
        new AuthorizationEnvelope($request->context, $request->envelope->subject,
            new MembershipSnapshot(Cases::OTHER, Cases::SUBJECT, MembershipStatus::Active, 1), $request->envelope->intent);
    }

    private function request(AuthorizationIntent $intent): AccessRequest
    {
        $context = new TenantContext(Cases::TENANT, Cases::SUBJECT, Cases::RECORD);
        $envelope = new AuthorizationEnvelope($context, new PrincipalReference(Cases::SUBJECT),
            new MembershipSnapshot(Cases::TENANT, Cases::SUBJECT, MembershipStatus::Active, 1), $intent);
        // Even a manually constructed, consistent object can never grant anything.
        return new AccessRequest($context, $intent->resource, $intent->action, envelope: $envelope);
    }
}
