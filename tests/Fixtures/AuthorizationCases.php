<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/** Descriptions and identifiers are fictitious. No real module/policy/rights. */
final class AuthorizationCases
{
    public const TENANT = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
    public const OTHER = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';
    public const SUBJECT = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    public const RECORD = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';

    public static function intent(): array
    {
        return [
            'schema_version' => 1, 'policy_version' => 'lab-deny-v1',
            'resource' => 'lab.record', 'resource_id' => self::RECORD, 'action' => 'read',
            'fields' => ['lab_status' => 'F-BIZ'],
            'scope' => ['kind' => 'tenant', 'id' => self::TENANT], 'channel' => 'interactive',
        ];
    }

    public static function malformed(): array
    {
        $cases = [];
        foreach (array_keys(self::intent()) as $key) {
            $input = self::intent();
            unset($input[$key]);
            $cases['missing '.$key] = [$input];
        }
        foreach (['principal_id', 'tenant_id', 'correlation_id', 'subject_evidence', 'membership', 'mfa_verified',
            'allow', 'roles', 'grants', 'entitlement', 'support_mandate', 'service_principal'] as $key) {
            $cases['forged '.$key] = [self::intent() + [$key => self::OTHER]];
        }
        foreach (['schema_version' => '1', 'resource_id' => 'not-a-uuid', 'action' => '*',
            'policy_version' => '', 'channel' => 'webhook', 'resource' => ['lab.record'],
            'fields' => [], 'scope' => ['kind' => 'team', 'id' => self::OTHER, 'verified' => true]] as $key => $value) {
            $cases['invalid '.$key] = [array_replace(self::intent(), [$key => $value])];
        }
        $cases['unknown classification'] = [array_replace(self::intent(), ['fields' => ['lab_status' => 'PUBLIC']])];
        $cases['numeric fields'] = [array_replace(self::intent(), ['fields' => ['F-BIZ']])];
        $cases['arbitrary field value'] = [array_replace(self::intent(), ['fields' => ['lab_status' => ['secret' => 'synthetic']]])];
        $cases['missing scope id'] = [array_replace(self::intent(), ['scope' => ['kind' => 'tenant']])];
        $cases['wildcard scope'] = [array_replace(self::intent(), ['scope' => ['kind' => '*', 'id' => self::TENANT]])];

        return $cases;
    }
}
