<?php

declare(strict_types=1);

namespace App\Core\Async;

use App\Core\Authorization\AuthorizationIntent;
use Illuminate\Support\Str;
use InvalidArgumentException;

/** All references, timestamps and assertions are UNTRUSTED descriptions. */
final readonly class AsyncAuthorityRequest
{
    private function __construct(
        public ExecutionClass $executionClass,
        public string $operationId,
        public string $tenantId,
        public array $origin,
        public array $causalActor,
        public array $executor,
        public AuthorizationIntent $intent,
        public string $purpose,
        public ?string $authorizationReference,
        public ?string $mandateReference,
        public ?string $entitlementReference,
        public string $correlationId,
        public string $causationId,
        public int $issuedAt,
        public int $expiresAt,
        public string $idempotencyKey,
        public string $confirmationOwner,
    ) {}

    public static function fromArray(array $input): self
    {
        self::keys($input, ['schema_version', 'policy_version', 'execution_class', 'environment',
            'operation_id', 'tenant_id', 'origin', 'causal_actor', 'executor', 'intent', 'purpose',
            'authorization_reference', 'mandate_reference', 'entitlement_reference', 'correlation_id',
            'causation_id', 'issued_at', 'expires_at', 'idempotency_key', 'confirmation_owner']);
        if ($input['schema_version'] !== 1 || $input['policy_version'] !== 'lab-async-deny-v1'
            || $input['environment'] !== 'laboratory' || !is_string($input['execution_class'])
            || ($class = ExecutionClass::tryFrom($input['execution_class'])) === null) {
            self::invalid();
        }
        foreach (['operation_id', 'tenant_id', 'correlation_id', 'causation_id', 'idempotency_key'] as $key) {
            self::uuid($input[$key]);
        }
        foreach (['authorization_reference', 'mandate_reference', 'entitlement_reference'] as $key) {
            self::uuid($input[$key], true);
        }
        if ($input['purpose'] !== 'lab.synthetic' || $input['confirmation_owner'] !== 'lab.module'
            || !is_int($input['issued_at']) || !is_int($input['expires_at'])
            || $input['issued_at'] < 1 || $input['expires_at'] <= $input['issued_at']
            || !is_array($input['intent'])) {
            self::invalid();
        }
        $intent = AuthorizationIntent::fromArray($input['intent']);
        if ($intent->channel !== 'job' && $intent->channel !== 'ai' && $intent->channel !== 'support') {
            self::invalid(); // Cannot launder an asynchronous claim into interactive authority.
        }
        foreach (['origin', 'causal_actor', 'executor'] as $key) {
            if (!is_array($input[$key])) { self::invalid(); }
        }
        self::keys($input['origin'], ['kind', 'reference', 'proof_reference']);
        if ($input['origin']['kind'] !== $class->origin()) { self::invalid(); }
        self::uuid($input['origin']['reference']);
        self::uuid($input['origin']['proof_reference'], true);
        self::keys($input['causal_actor'], ['kind', 'reference']);
        $actor = $input['causal_actor'];
        $kinds = match ($class) {
            ExecutionClass::DeferredHuman => ['human'], ExecutionClass::MandatoryProcess => ['none', 'service'],
            ExecutionClass::NativeAutomation => ['human', 'service'], ExecutionClass::ExternalIngress => ['visitor', 'service'],
            ExecutionClass::TechnicalOperation => ['agent', 'support', 'service'],
        };
        if (!in_array($actor['kind'], $kinds, true)) { self::invalid(); }
        self::uuid($actor['reference'], in_array($actor['kind'], ['none', 'visitor'], true));
        if ($actor['kind'] === 'none' && $actor['reference'] !== null) { self::invalid(); }
        self::keys($input['executor'], ['kind', 'reference', 'tenant_id', 'environment', 'evidence_reference']);
        $executor = $input['executor'];
        if (!in_array($executor['kind'], ['service', 'agent'], true) || $executor['environment'] !== 'laboratory') { self::invalid(); }
        self::uuid($executor['reference'], true);
        self::uuid($executor['tenant_id']);
        self::uuid($executor['evidence_reference'], true);
        if ($actor['kind'] === 'human' && is_string($executor['reference'])
            && strtolower($actor['reference']) === strtolower($executor['reference'])) { self::invalid(); }

        return new self($class, strtolower($input['operation_id']), strtolower($input['tenant_id']),
            $input['origin'], $actor, $executor, $intent, $input['purpose'], $input['authorization_reference'],
            $input['mandate_reference'], $input['entitlement_reference'], strtolower($input['correlation_id']),
            strtolower($input['causation_id']), $input['issued_at'], $input['expires_at'],
            strtolower($input['idempotency_key']), $input['confirmation_owner']);
    }

    private static function keys(array $input, array $expected): void
    {
        $actual = array_keys($input);
        sort($actual); sort($expected);
        if ($actual !== $expected) { self::invalid(); }
    }

    private static function uuid(mixed $value, bool $nullable = false): void
    {
        if (($nullable && $value === null) || (is_string($value) && Str::isUuid($value))) { return; }
        self::invalid();
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Invalid asynchronous contract.');
    }
}
