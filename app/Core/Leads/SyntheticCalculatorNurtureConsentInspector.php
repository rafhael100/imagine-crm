<?php
declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * OFFLINE ONLY — structural review of a future calculator lead candidate.
 * Never authenticates consent or tenancy, never stores/sends a lead, never
 * enrolls marketing, never authorizes an email, webhook or CRM mutation.
 */
final class SyntheticCalculatorNurtureConsentInspector
{
    private const KEYS = [
        'source', 'schema', 'email', 'name',
        'channels', 'report_requested', 'marketing_opt_in',
        'notice_version', 'budget_estimate_cents',
    ];
    private const CHANNELS = ['meta_ads','google_ads','chatgpt_ads'];

    /**
     * @param array<string,mixed> $draft Synthetic candidate ONLY
     * @return array<string,mixed> Non-PII summary with hard-denied permissions
     */
    public static function review(array $draft): array
    {
        $keys = array_keys($draft);
        sort($keys, SORT_STRING);
        $expected = self::KEYS;
        sort($expected, SORT_STRING);
        if ($keys !== $expected) {
            throw new InvalidArgumentException('Unexpected calculator candidate fields.');
        }
        if ($draft['source'] !== 'imagine_comercial_calculadora_performance' ||
            $draft['schema'] !== 'synthetic.calculator.lead-intent.v1' ||
            !is_string($draft['notice_version']) ||
            preg_match('/\Alab-notice-v[1-9][0-9]?\z/D', $draft['notice_version']) !== 1) {
            throw new InvalidArgumentException('Synthetic source/version not recognized.');
        }
        if (!is_string($draft['name']) ||
            strlen($draft['name']) < 3 || strlen($draft['name']) > 120 ||
            preg_match('/[<>\x00-\x1f\x7f]/', $draft['name']) ||
            !is_string($draft['email']) ||
            strlen($draft['email']) > 254 ||
            filter_var($draft['email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('Invalid synthetic contact candidate.');
        }
        if (!is_bool($draft['report_requested']) ||
            !is_bool($draft['marketing_opt_in']) ||
            ($draft['report_requested'] === false && $draft['marketing_opt_in'] === false)) {
            throw new InvalidArgumentException('No separate action requested.');
        }
        if (!is_array($draft['channels']) || $draft['channels'] === [] ||
            count($draft['channels']) > 3 ||
            count(array_unique($draft['channels'])) !== count($draft['channels'])) {
            throw new InvalidArgumentException('Invalid channel scope.');
        }
        foreach ($draft['channels'] as $channel) {
            if (!is_string($channel) || !in_array($channel, self::CHANNELS, true)) {
                throw new InvalidArgumentException('Unsupported channel.');
            }
        }
        if (!is_int($draft['budget_estimate_cents']) ||
            $draft['budget_estimate_cents'] < 70000 ||
            $draft['budget_estimate_cents'] > 300000000) {
            throw new InvalidArgumentException('Invalid draft media estimate.');
        }

        // Client-supplied checkboxes are not proof: verified consent requires
        // server-side signed provenance, privacy text version, trusted identity,
        // authenticated delivery binding and successful storage.
        return [
            'schema' => 'synthetic.calculator.lead-intent-review.v1',
            'source' => 'imagine_comercial_calculadora_performance',
            'channel_count' => count($draft['channels']),
            'report_request_claimed' => $draft['report_requested'],
            'marketing_opt_in_claimed' => $draft['marketing_opt_in'],
            'notice_version_claimed' => $draft['notice_version'],
            'tenant_authorized' => false,
            'consent_proven' => false,
            'crm_delivery_proven' => false,
            'report_delivery_allowed' => false,
            'marketing_enrollment_allowed' => false,
            'can_send_email' => false,
            'can_start_nurture_sequence' => false,
            'can_store_personal_data' => false,
            'state' => 'SYNTHETIC_REVIEW_NO_CRM_INTAKE',
        ];
    }
}
