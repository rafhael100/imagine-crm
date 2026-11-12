<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R03 / VEN-T01: inspect invented field-presence flags against an
 * untrusted, illustrative stage policy. This is NOT a backend authorization,
 * tenant-owned configuration or permission to move a real opportunity.
 *
 * No personal/customer values are received: the five booleans are only a
 * technical illustration of whether a required field is present.
 */
final class SyntheticStageFieldRequirementsInspector
{
    private const FIELD_CODES = [
        'amount', 'assignee', 'contact', 'expected_close', 'source',
    ];

    /**
     * @param mixed $fromStage integer 0..3
     * @param mixed $toStage integer 0..3; must differ from source
     * @param mixed $presence array{
     *   amount:bool,assignee:bool,contact:bool,
     *   expected_close:bool,source:bool
     * }
     * @param mixed $requirements list<list<string>> exactly four stage sets
     * @return array<string,mixed> Closed numeric/status-only lab readout
     */
    public function inspect(
        mixed $fromStage,
        mixed $toStage,
        mixed $presence,
        mixed $requirements,
    ): array {
        if (!is_int($fromStage) || !is_int($toStage)
            || $fromStage < 0 || $fromStage > 3
            || $toStage < 0 || $toStage > 3
            || $fromStage === $toStage
            || !is_array($presence) || count($presence) !== count(self::FIELD_CODES)
            || !is_array($requirements) || !array_is_list($requirements)
            || count($requirements) !== 4) {
            self::invalid();
        }

        $presenceKeys = array_keys($presence);
        sort($presenceKeys);
        if ($presenceKeys !== self::FIELD_CODES) {
            self::invalid();
        }
        foreach (self::FIELD_CODES as $code) {
            if (!is_bool($presence[$code])) {
                self::invalid();
            }
        }

        foreach ($requirements as $requiredFields) {
            if (!is_array($requiredFields)
                || !array_is_list($requiredFields)
                || count($requiredFields) > count(self::FIELD_CODES)) {
                self::invalid();
            }

            $unique = [];
            foreach ($requiredFields as $code) {
                if (!is_string($code)
                    || !in_array($code, self::FIELD_CODES, true)
                    || isset($unique[$code])) {
                    self::invalid();
                }
                $unique[$code] = true;
            }
        }

        $missing = [];
        // Use canonical output order, independent of caller policy ordering.
        foreach (self::FIELD_CODES as $code) {
            if (in_array($code, $requirements[$toStage], true)
                && $presence[$code] === false) {
                $missing[] = $code;
            }
        }

        return [
            'from_stage' => $fromStage,
            'to_stage' => $toStage,
            'required_count' => count($requirements[$toStage]),
            'missing_count' => count($missing),
            'missing_field_codes' => $missing,
            'requirements_met' => count($missing) === 0,
            'policy_verified' => false,
            'fields_verified' => false,
            'authorization_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_move' => false,
            'can_override_required_fields' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        // No values, email, tenant or policy content in any exception.
        throw new InvalidArgumentException('Campos obrigatorios sinteticos invalidos.');
    }
}
