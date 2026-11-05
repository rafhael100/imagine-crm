<?php

declare(strict_types=1);

namespace App\Core\Authorization;

use Illuminate\Support\Str;
use InvalidArgumentException;

/** Untrusted description, never a credential, delegation, entitlement or grant. */
final readonly class AuthorizationIntent
{
    private function __construct(
        public int $schemaVersion,
        public string $policyVersion,
        public string $resource,
        public string $resourceId,
        public string $action,
        public array $fields,
        public string $scope,
        public string $scopeId,
        public string $channel,
    ) {}

    public static function fromArray(array $input): self
    {
        self::keys($input, ['schema_version', 'policy_version', 'resource', 'resource_id', 'action', 'fields', 'scope', 'channel']);
        if (($input['schema_version'] ?? null) !== 1) {
            self::invalid();
        }
        foreach (['policy_version', 'resource', 'action', 'channel'] as $key) {
            if (!is_string($input[$key]) || !preg_match('/\A[a-z][a-z0-9_.-]{0,63}\z/D', $input[$key])) {
                self::invalid();
            }
        }
        if (!is_string($input['resource_id']) || !Str::isUuid($input['resource_id'])
            || !is_array($input['fields']) || count($input['fields']) < 1 || count($input['fields']) > 9
            || !is_array($input['scope'])) {
            self::invalid();
        }
        foreach ($input['fields'] as $name => $classification) {
            if (!is_string($name) || !preg_match('/\Alab_[a-z_]{1,40}\z/D', $name)
                || !is_string($classification)
                || !in_array($classification, ['F-ID', 'F-BIZ', 'F-PII', 'F-RES', 'F-CONS', 'F-FIN', 'F-SEC', 'F-EVID', 'F-ART'], true)) {
                self::invalid();
            }
        }
        self::keys($input['scope'], ['kind', 'id']);
        if (!is_string($input['scope']['kind']) || !in_array($input['scope']['kind'], ['tenant', 'record', 'team', 'unit'], true)
            || !is_string($input['scope']['id']) || !Str::isUuid($input['scope']['id'])
            || !in_array($input['channel'], ['interactive', 'job', 'ai', 'support'], true)) {
            self::invalid();
        }

        return new self(1, $input['policy_version'], $input['resource'], strtolower($input['resource_id']),
            $input['action'], $input['fields'], $input['scope']['kind'], strtolower($input['scope']['id']), $input['channel']);
    }

    private static function keys(array $input, array $expected): void
    {
        $actual = array_keys($input);
        sort($actual);
        sort($expected);
        if ($actual !== $expected) {
            self::invalid();
        }
    }

    private static function invalid(): never
    {
        // Do not echo arbitrary field names, values or policy details.
        throw new InvalidArgumentException('Invalid authorization contract.');
    }
}
