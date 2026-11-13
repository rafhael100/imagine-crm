<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Identity\OfflineCnpjChecksumInspector;
use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Plans\QuotaSnapshotReadout;
use App\Core\Plans\QuotaUsageProjection;
use App\Core\Plans\SyntheticTrialQuotaOverview;
use App\Core\Plans\TrialWindowAdvisory;
use InvalidArgumentException;

/**
 * F3-249 — functional, process-local trial uniqueness lab, Composer autoload-dev.
 *
 * NEVER a trusted eligibility, customer, plan, access, identity or billing
 * service. One PHP object's mutable array is not durable DB uniqueness.
 */
final class TrialEnrollmentLifecycleSandbox
{
    private const TRIAL_SECONDS = TrialWindowAdvisory::WINDOW_DAYS * 86400;

    /** @var array<string,int> HMAC-indexed synthetic starts, not real tenants */
    private array $started = [];

    private OfflineCnpjChecksumInspector $inspector;
    private OfflineCnpjTrialHmacBinding $fingerprints;

    public function __construct(
        #[\SensitiveParameter] private readonly string $key,
    ) {
        if (strlen($key) < 32 || strlen($key) > 128) {
            throw new InvalidArgumentException('Trial ficticio invalido.');
        }
        $this->inspector = new OfflineCnpjChecksumInspector();
        $this->fingerprints = new OfflineCnpjTrialHmacBinding();
    }

    /**
     * @param mixed $cnpj Synthetic numeric or alphanumeric CNPJ
     * @return array<string,mixed> Test-only review, never actual reservation
     */
    public function observeFirstUse(#[\SensitiveParameter] mixed $cnpj, int $at): array
    {
        self::time($at, true);
        $check = $this->inspector->inspect($cnpj);
        if ($check['status_code'] !== 'structurally_valid_only') {
            return self::result('sandbox_cnpj_rejected', 'unknown');
        }

        $fingerprint = $this->fingerprints->derive($cnpj, $this->key);
        if (array_key_exists($fingerprint, $this->started)) {
            return self::result('sandbox_duplicate_trial_claim', $check['format_kind']);
        }

        // This branch ONLY sets an in-memory lab marker. It does NOT create
        // tenants, reservations, trials or accounts in a real system.
        $this->started[$fingerprint] = $at;
        return self::result('sandbox_first_trial_marker', $check['format_kind']);
    }

    /**
     * @param mixed $cnpj Fixed synthetic identifier, never echoed or logged
     * @return array<string,mixed>
     */
    public function describe(#[\SensitiveParameter] mixed $cnpj, int $at): array
    {
        self::time($at, false);
        $check = $this->inspector->inspect($cnpj);
        if ($check['status_code'] !== 'structurally_valid_only') {
            return self::result('sandbox_cnpj_rejected', 'unknown');
        }

        $fingerprint = $this->fingerprints->derive($cnpj, $this->key);
        if (!array_key_exists($fingerprint, $this->started)) {
            return self::result('sandbox_no_trial_marker', $check['format_kind']);
        }

        $time = new TrialWindowAdvisory($this->started[$fingerprint], $at);
        $quotas = new QuotaSnapshotReadout([
            new QuotaUsageProjection('users', 2, 0, 0),
            new QuotaUsageProjection('leads', 20, 0, 0),
            new QuotaUsageProjection('automations', 2, 0, 0),
            new QuotaUsageProjection('pipelines', 2, 0, 0),
            new QuotaUsageProjection('attachments_bytes', 20 * 1024 * 1024, 0, 0),
            new QuotaUsageProjection('ai_initial_credits', 10, 0, 0),
            new QuotaUsageProjection('ai_bonus_credits', 10, 0, 0),
        ]);
        $overview = (new SyntheticTrialQuotaOverview($time, $quotas))->readout();
        $result = self::result('sandbox_trial_snapshot_only', $check['format_kind']);
        $result['trial_phase'] = $overview['trial']['phase'];
        $result['illustrative_trial_days'] = TrialWindowAdvisory::WINDOW_DAYS;
        $result['remaining_days_rounded_up'] = $overview['trial']['remaining_days_rounded_up'];
        $result['quota_meters'] = array_map(
            static fn (array $card): array => [
                'meter' => $card['meter'],
                'illustrative_limit' => $card['limit'],
                'illustrative_used' => $card['used'],
                'remaining' => $card['remaining'],
                'band' => $card['band']->value,
            ],
            $overview['quotas'],
        );
        return $result;
    }

    /** @return array<string,int|bool> */
    public function diagnostics(): array
    {
        return [
            'synthetic_company_markers_in_memory' => count($this->started),
            'durable_uniqueness_verified' => false,
            'tax_authority_verified' => false,
            'can_create_tenant' => false,
            'can_charge' => false,
            'can_allocate_quota' => false,
        ];
    }

    private static function time(int $at, bool $asStart): void
    {
        if ($at < 0 || ($asStart && $at > PHP_INT_MAX - self::TRIAL_SECONDS)) {
            throw new InvalidArgumentException('Trial ficticio invalido.');
        }
    }

    /** @return array<string,mixed> */
    private static function result(string $status, string $kind): array
    {
        return [
            'status' => $status,
            'format_kind' => $kind,
            'trial_phase' => null,
            'informational_only' => true,
            'registered_company_verified' => false,
            'company_owner_verified' => false,
            'tenant_membership_verified' => false,
            'durable_uniqueness_verified' => false,
            'consent_and_lgpd_verified' => false,
            'can_reserve_trial' => false,
            'can_create_tenant' => false,
            'can_register' => false,
            'can_authenticate' => false,
            'can_charge' => false,
            'can_allocate_quota' => false,
            'can_persist' => false,
        ];
    }
}
