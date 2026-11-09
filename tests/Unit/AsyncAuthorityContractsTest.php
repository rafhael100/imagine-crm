<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Async\AsyncAuthorityRequest;
use App\Core\Async\AsyncLifecycle;
use App\Core\Async\AsyncState;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\AsyncCases as Cases;
use Tests\Fixtures\AuthorizationCases;
use Tests\Fixtures\SyntheticAsyncInbox;

final class AsyncAuthorityContractsTest extends TestCase
{
    public static function malformed(): array { return Cases::malformed(); }
    public static function classes(): array { return Cases::classes(); }

    #[DataProvider('malformed')]
    public function test_unknown_incomplete_or_forged_claims_are_not_contracts(array $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        AsyncAuthorityRequest::fromArray($input);
    }

    #[DataProvider('classes')]
    public function test_each_class_keeps_its_own_authority_dependencies(string $class): void
    {
        $request = AsyncAuthorityRequest::fromArray(Cases::request($class));
        $this->assertSame($class, $request->executionClass->value);
        $this->assertNotEmpty($request->executionClass->dependencies());
        $this->assertSame('lab.module', $request->confirmationOwner);
        $this->assertNotSame($request->executor['reference'], $request->causalActor['reference']);
        if (in_array($class, ['B', 'D'], true)) {
            $this->assertNotContains('CURRENT_HUMAN_SESSION_MEMBERSHIP', $request->executionClass->dependencies());
            $this->assertNull($request->causalActor['reference']);
        }
    }

    public static function transport(): array { return [[200], [201], [202], [204], [500], [null]]; }

    #[DataProvider('transport')]
    public function test_transport_success_or_timeout_never_becomes_official_business_confirmation(?int $status): void
    {
        $trace = AsyncLifecycle::received()->transition(AsyncState::FormatValidated);
        $uncertain = $trace->observeTransport($status);
        $this->assertSame(AsyncState::Inconclusive, $uncertain->state);
        $this->assertSame(AsyncState::ReconciliationRequired, $uncertain->transition(AsyncState::ReconciliationRequired)->state);
        $this->expectException(LogicException::class);
        $uncertain->transition(AsyncState::Forwarded); // No blind retry after uncertain transmission.
    }

    public static function forbiddenTransitions(): array
    {
        $cases = [];
        foreach ([AsyncState::Admitted, AsyncState::Forwarded, AsyncState::OfficiallyConfirmed] as $state) {
            $cases[$state->value] = [$state];
        }
        return $cases;
    }

    #[DataProvider('forbiddenTransitions')]
    public function test_validated_is_not_admitted_forwarded_or_confirmed(AsyncState $next): void
    {
        $this->expectException(LogicException::class);
        AsyncLifecycle::received()->transition(AsyncState::FormatValidated)->transition($next);
    }

    public function test_denial_and_reconciliation_are_terminal_without_a_retry_or_confirmation_factory(): void
    {
        $trace = AsyncLifecycle::received()->transition(AsyncState::Denied);
        $this->expectException(LogicException::class);
        $trace->transition(AsyncState::Received);
    }

    public function test_synthetic_idempotency_distinguishes_duplicate_conflict_and_other_tenant(): void
    {
        $inbox = new SyntheticAsyncInbox();
        $input = Cases::request();
        $request = AsyncAuthorityRequest::fromArray($input);
        $this->assertSame('first_seen', $inbox->observe($request));
        $this->assertSame('duplicate', $inbox->observe(AsyncAuthorityRequest::fromArray($input)));
        $changed = $input; $changed['intent']['action'] = 'update';
        $this->assertSame('conflict', $inbox->observe(AsyncAuthorityRequest::fromArray($changed)));
        $input['tenant_id'] = $input['executor']['tenant_id'] = $input['intent']['scope']['id'] = AuthorizationCases::OTHER;
        $this->assertSame('first_seen', $inbox->observe(AsyncAuthorityRequest::fromArray($input)));
        // No receipt means permission; only a future durable owner inbox could dedupe effects.
        $this->assertFalse(method_exists($inbox, 'dispatch'));
    }
}
