<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Leads\SyntheticLeadListQueryAssessment;
use App\Core\Leads\SyntheticLeadListQueryInspector;
use PHPUnit\Framework\TestCase;

final class SyntheticLeadListQueryInspectorTest extends TestCase
{
    private const TENANT = '11111111-1111-4111-8111-111111111111';
    private const OTHER_TENANT = '22222222-2222-4222-8222-222222222222';

    /** @return array<string, mixed> */
    private static function fixture(): array
    {
        return [
            'schema_version' => 1,
            'resource' => 'lab.lead',
            'action' => 'list',
            'tenant_reference' => self::TENANT,
            'fields' => ['lead_reference', 'stage_label'],
            'sort_mode' => 'original',
            'page_size' => 20,
            'cursor' => null,
        ];
    }

    private static function assess(mixed $value, string $expected = self::TENANT): SyntheticLeadListQueryAssessment
    {
        return (new SyntheticLeadListQueryInspector())->inspect($expected, $value);
    }

    private static function assertClosed(string $expectedReason, SyntheticLeadListQueryAssessment $result): void
    {
        self::assertSame($expectedReason, $result->reasonCode);
        self::assertSame(['code' => $expectedReason], $result->signal());
        self::assertFalse($result->canListLeads());
        self::assertFalse($result->canReadLead());
        self::assertFalse($result->canExportLeads());
        self::assertFalse($result->canRevealFields());
        self::assertSame(['code'], array_keys($result->signal()));
    }

    public function test_complete_synthetic_descriptor_is_never_permission_to_read(): void
    {
        self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess(self::fixture()));
        self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess(
            array_reverse(self::fixture(), true),
        ));
        $upper = self::fixture();
        $upper['tenant_reference'] = strtoupper(self::TENANT);
        self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess($upper, strtoupper(self::TENANT)));
    }

    public function test_all_literal_field_combinations_are_still_non_authoritative(): void
    {
        $names = ['lead_reference', 'stage_label', 'score_band'];
        $count = 0;

        foreach (['original', 'score_high', 'score_low'] as $sort) {
            foreach ($names as $first) {
                $fixture = self::fixture();
                $fixture['sort_mode'] = $sort;
                $fixture['fields'] = [$first];
                self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess($fixture));
                $count++;

                foreach ($names as $second) {
                    if ($first === $second) {
                        continue;
                    }
                    $fixture['fields'] = [$first, $second];
                    self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess($fixture));
                    $count++;
                }
            }
            $fixture = self::fixture();
            $fixture['sort_mode'] = $sort;
            $fixture['fields'] = $names;
            self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess($fixture));
            $count++;
        }

        self::assertSame(30, $count);
    }

    public function test_bad_shape_and_unknown_claims_are_rejected_without_echoing_values(): void
    {
        self::assertClosed('CONTRACT_INVALID', self::assess(null));
        self::assertClosed('CONTRACT_INVALID', self::assess('tenant=client@example.invalid'));
        self::assertClosed('CONTRACT_INVALID', self::assess([]));
        self::assertClosed('CONTRACT_INVALID', self::assess([self::fixture()]));
        self::assertClosed('CONTRACT_INVALID', self::assess(self::fixture(), 'not-a-uuid'));

        $missing = self::fixture();
        unset($missing['action']);
        self::assertClosed('CONTRACT_INVALID', self::assess($missing));

        foreach (['is_admin', 'allow', 'role', 'mfa_verified', 'field_grants', 'query', 'where', 'include_deleted'] as $key) {
            $injected = self::fixture();
            $injected[$key] = 'person@example.invalid';
            self::assertClosed('CONTRACT_INVALID', self::assess($injected));
        }

        $renamed = self::fixture();
        unset($renamed['action']);
        $renamed['operation'] = 'list';
        self::assertClosed('CONTRACT_INVALID', self::assess($renamed));
    }

    public function test_version_resource_and_action_are_closed_allowlists(): void
    {
        foreach ([0, 2, '1', null, false] as $value) {
            $query = self::fixture();
            $query['schema_version'] = $value;
            self::assertClosed('VERSION_UNSUPPORTED', self::assess($query));
        }

        foreach (['lead', 'leads', 'tenant.lead', 'lab.contact', 'lab.lead;DROP', 10, null] as $value) {
            $query = self::fixture();
            $query['resource'] = $value;
            self::assertClosed('RESOURCE_UNSUPPORTED', self::assess($query));
        }

        foreach (['read', 'search', 'export', 'delete', 'update', 'merge', 'list_all', null, true] as $value) {
            $query = self::fixture();
            $query['action'] = $value;
            self::assertClosed('ACTION_UNSUPPORTED', self::assess($query));
        }
    }

    public function test_untrusted_tenant_claim_is_not_an_authenticated_membership(): void
    {
        $different = self::fixture();
        $different['tenant_reference'] = self::OTHER_TENANT;
        self::assertClosed('SCOPE_MISMATCH', self::assess($different));

        foreach (['', 'header-tenant', '00000000-0000-0000-0000-000000000000', null, 123] as $bad) {
            $query = self::fixture();
            $query['tenant_reference'] = $bad;
            self::assertClosed('REFERENCE_FORMAT_INVALID', self::assess($query));
        }

        // Even if a caller supplies the other tenant as the "expected" one,
        // matching strings never authenticate the caller or authorize a read.
        self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess(
            $different, self::OTHER_TENANT,
        ));
    }

    public function test_projection_blocks_personal_sensitive_custom_and_duplicate_fields(): void
    {
        foreach ([
            [],
            ['lead_reference', 'lead_reference'],
            ['email'],
            ['phone'],
            ['name'],
            ['f_pii'],
            ['lab_secret'],
            ['custom_field_1'],
            ['*'],
            ['stage_label', 'lead_reference', 'score_band', 'email'],
            ['lead_reference' => 'stage_label'],
            [true],
            [12],
            null,
            'lead_reference',
        ] as $fields) {
            $query = self::fixture();
            $query['fields'] = $fields;
            self::assertClosed('FIELDS_UNVERIFIED', self::assess($query));
        }
    }

    public function test_sorting_and_page_size_cannot_expand_into_a_query_language(): void
    {
        foreach (['score desc', 'score_high;DROP', 'stage_label', 'created_at', 'random', '', null, 123] as $mode) {
            $query = self::fixture();
            $query['sort_mode'] = $mode;
            self::assertClosed('SORT_UNVERIFIED', self::assess($query));
        }

        foreach ([0, 21, 500, -1, '20', 1.0, true, null] as $size) {
            $query = self::fixture();
            $query['page_size'] = $size;
            self::assertClosed('PAGE_SIZE_INVALID', self::assess($query));
        }

        foreach ([1, 2, 19, 20] as $size) {
            $query = self::fixture();
            $query['page_size'] = $size;
            self::assertClosed('STRUCTURE_ONLY_UNVERIFIED', self::assess($query));
        }
    }

    public function test_no_unverified_cursor_can_replay_or_change_tenant_or_fields(): void
    {
        foreach ([
            '',
            'cursor',
            'eyJ0ZW5hbnQiOiJhIn0=',
            'person@example.invalid',
            ['tenant' => self::OTHER_TENANT],
            1,
            false,
        ] as $cursor) {
            $query = self::fixture();
            $query['cursor'] = $cursor;
            self::assertClosed('CURSOR_UNVERIFIED', self::assess($query));
        }
    }

    public function test_diagnostics_do_not_serialize_claimed_tenant_fields_or_cursor(): void
    {
        foreach ([
            self::assess(self::fixture()),
            self::assess([...self::fixture(), 'tenant_reference' => self::OTHER_TENANT]),
            self::assess([...self::fixture(), 'fields' => ['email']]),
            self::assess([...self::fixture(), 'cursor' => 'sensitive-token@example.invalid']),
        ] as $outcome) {
            $serialized = json_encode($outcome->signal(), JSON_THROW_ON_ERROR);
            foreach ([
                self::TENANT, self::OTHER_TENANT, 'lead_reference', 'stage_label',
                'email', 'token', 'cursor', 'tenant_reference', 'page_size', 'sort_mode',
            ] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $serialized);
            }
            self::assertFalse($outcome->canListLeads());
            self::assertFalse($outcome->canExportLeads());
        }
    }

    public function test_assessment_has_no_authorizing_or_data_reading_entrypoint(): void
    {
        foreach ([
            'allow', 'grant', 'authorize', 'execute', 'fetch', 'query', 'search',
            'list', 'read', 'export', 'download', 'persist', 'store',
        ] as $forbidden) {
            self::assertFalse(method_exists(SyntheticLeadListQueryInspector::class, $forbidden));
            self::assertFalse(method_exists(SyntheticLeadListQueryAssessment::class, $forbidden));
        }
    }
}
