<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\ExecutionClass;

final class AsyncCases
{
    public const SERVICE = 'ffffffff-ffff-4fff-8fff-ffffffffffff';
    public const OPERATION = '12345678-1234-4234-8234-123456789abc';

    public static function request(string $class = 'A'): array
    {
        $execution = ExecutionClass::from($class);
        $actor = match ($execution) {
            ExecutionClass::DeferredHuman, ExecutionClass::NativeAutomation => ['kind' => 'human', 'reference' => AuthorizationCases::SUBJECT],
            ExecutionClass::MandatoryProcess => ['kind' => 'none', 'reference' => null],
            ExecutionClass::ExternalIngress => ['kind' => 'visitor', 'reference' => null],
            ExecutionClass::TechnicalOperation => ['kind' => 'agent', 'reference' => AuthorizationCases::RECORD],
        };
        return [
            'schema_version' => 1, 'policy_version' => 'lab-async-deny-v1', 'execution_class' => $class,
            'environment' => 'laboratory', 'operation_id' => self::OPERATION, 'tenant_id' => AuthorizationCases::TENANT,
            'origin' => ['kind' => $execution->origin(), 'reference' => AuthorizationCases::RECORD, 'proof_reference' => null],
            'causal_actor' => $actor,
            'executor' => ['kind' => 'service', 'reference' => self::SERVICE, 'tenant_id' => AuthorizationCases::TENANT,
                'environment' => 'laboratory', 'evidence_reference' => null],
            'intent' => array_replace(AuthorizationCases::intent(), ['action' => 'export', 'channel' => 'job']),
            'purpose' => 'lab.synthetic', 'authorization_reference' => null, 'mandate_reference' => null,
            'entitlement_reference' => null, 'correlation_id' => AuthorizationCases::RECORD,
            'causation_id' => AuthorizationCases::OTHER, 'issued_at' => 1800000000, 'expires_at' => 1800000060,
            'idempotency_key' => self::OPERATION, 'confirmation_owner' => 'lab.module',
        ];
    }

    public static function classes(): array { return ['A' => ['A'], 'B' => ['B'], 'C' => ['C'], 'D' => ['D'], 'E' => ['E']]; }

    public static function malformed(): array
    {
        $cases = [];
        foreach (array_keys(self::request()) as $key) {
            $input = self::request(); unset($input[$key]);
            $cases['missing '.$key] = [$input];
        }
        foreach (['allow', 'signature_verified', 'roles', 'token', 'payload', 'url', 'sql', 'official_result'] as $key) {
            $cases['forged '.$key] = [self::request() + [$key => 'synthetic-secret-value']];
        }
        foreach (['schema_version' => 2, 'policy_version' => 'lab-async-deny-v2', 'execution_class' => 'F',
            'environment' => 'production', 'tenant_id' => '*', 'issued_at' => '1800000000',
            'expires_at' => 1800000000, 'idempotency_key' => '', 'purpose' => 'send_whatsapp',
            'confirmation_owner' => 'FIN'] as $key => $value) {
            $cases['invalid '.$key] = [array_replace(self::request(), [$key => $value])];
        }
        $input = self::request(); $input['origin']['verified'] = true; $cases['origin verified'] = [$input];
        $input = self::request(); $input['executor']['kind'] = 'human'; $cases['human executor'] = [$input];
        $input = self::request(); $input['executor']['reference'] = strtoupper(AuthorizationCases::SUBJECT); $cases['human laundered as service'] = [$input];
        $input = self::request(); $input['causal_actor'] = ['kind' => 'service', 'reference' => self::SERVICE]; $cases['A requires human'] = [$input];
        $input = self::request('B'); $input['origin']['kind'] = 'external_ingress'; $cases['wrong class origin'] = [$input];
        $input = self::request(); $input['intent']['channel'] = 'interactive'; $cases['laundered interactive channel'] = [$input];
        $input = self::request(); $input['executor']['evidence_reference'] = 'synthetic-token'; $cases['raw token as proof'] = [$input];
        return $cases;
    }
}
