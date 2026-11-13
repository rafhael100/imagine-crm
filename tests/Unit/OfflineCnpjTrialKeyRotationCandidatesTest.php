<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Identity\OfflineCnpjTrialKeyRotationCandidates;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineCnpjTrialKeyRotationCandidatesTest extends TestCase
{
    private const KEY_1 = 'fictional-rotated-old-32-byte-plus-key-v1';
    private const KEY_2 = 'fictional-rotated-new-32-byte-plus-key-v2';

    private static function keyring(): array
    {
        return [
            ['version' => 7, 'secret' => self::KEY_1],
            ['version' => 9, 'secret' => self::KEY_2],
        ];
    }

    public function test_numeric_and_alphanumeric_candidates_preserve_historical_key_coverage(): void
    {
        $builder = new OfflineCnpjTrialKeyRotationCandidates();
        $actual = $builder->derive('12.ABC.345/01DE-35', self::keyring());
        $sameCompany = $builder->derive('12abc34501de35', self::keyring());

        self::assertSame($actual, $sameCompany);
        self::assertSame(2, $actual['candidate_count']);
        self::assertSame(9, $actual['current_key_version']);
        self::assertSame([7, 9], array_column($actual['candidate_versions_and_bindings'], 'version'));
        self::assertNotSame(
            $actual['candidate_versions_and_bindings'][0]['internal_binding'],
            $actual['candidate_versions_and_bindings'][1]['internal_binding'],
        );

        $old = (new OfflineCnpjTrialHmacBinding())->derive('12.ABC.345/01DE-35', self::KEY_1);
        $new = (new OfflineCnpjTrialHmacBinding())->derive('12.ABC.345/01DE-35', self::KEY_2);
        self::assertSame($old, $actual['candidate_versions_and_bindings'][0]['internal_binding']);
        self::assertSame($new, $actual['candidate_versions_and_bindings'][1]['internal_binding']);

        $other = $builder->derive('11.222.333/0001-81', self::keyring());
        self::assertNotSame($actual['candidate_versions_and_bindings'][0]['internal_binding'], $other['candidate_versions_and_bindings'][0]['internal_binding']);
        self::assertFalse($actual['historical_key_coverage_verified']);
        self::assertFalse($actual['key_custody_verified']);
        foreach (['can_lookup', 'can_claim_trial', 'can_register', 'can_grant_access', 'can_persist'] as $flag) {
            self::assertFalse($actual[$flag]);
        }
    }

    public function test_dropping_retired_key_loses_historical_candidate_and_does_not_claim_safety(): void
    {
        $engine = new OfflineCnpjTrialKeyRotationCandidates();
        $withHistory = $engine->derive('12ABC34501DE35', self::keyring());
        $onlyNew = $engine->derive('12ABC34501DE35', [
            ['version' => 9, 'secret' => self::KEY_2],
        ]);
        self::assertSame(2, $withHistory['candidate_count']);
        self::assertSame(1, $onlyNew['candidate_count']);
        self::assertSame(
            $withHistory['candidate_versions_and_bindings'][1]['internal_binding'],
            $onlyNew['candidate_versions_and_bindings'][0]['internal_binding'],
        );
        self::assertNotSame(
            $withHistory['candidate_versions_and_bindings'][0]['internal_binding'],
            $onlyNew['candidate_versions_and_bindings'][0]['internal_binding'],
        );
        self::assertFalse($onlyNew['historical_key_coverage_verified']);
        self::assertFalse($onlyNew['can_claim_trial']);
    }

    public function test_untrusted_keyrings_and_invalid_documents_fail_closed_without_echo(): void
    {
        $engine = new OfflineCnpjTrialKeyRotationCandidates();
        foreach ([
            ['SECRET_FAKE', self::keyring()],
            ['12.ABC.345/01DE-36', self::keyring()],
            ['12.ABC.345/01DE-35', []],
            ['12.ABC.345/01DE-35', 'not-an-array'],
            ['12.ABC.345/01DE-35', [['version' => 1, 'secret' => 'short']]],
            ['12.ABC.345/01DE-35', [['version' => 0, 'secret' => self::KEY_1]]],
            ['12.ABC.345/01DE-35', [['version' => 65536, 'secret' => self::KEY_1]]],
            ['12.ABC.345/01DE-35', [
                ['version' => 9, 'secret' => self::KEY_1],
                ['version' => 7, 'secret' => self::KEY_2],
            ]],
            ['12.ABC.345/01DE-35', [
                ['version' => 7, 'secret' => self::KEY_1],
                ['version' => 7, 'secret' => self::KEY_2],
            ]],
            ['12.ABC.345/01DE-35', [
                ['version' => 7, 'secret' => self::KEY_1],
                ['version' => 9, 'secret' => self::KEY_1],
            ]],
            ['12.ABC.345/01DE-35', [['secret' => self::KEY_1, 'version' => 7]]],
            ['12.ABC.345/01DE-35', array_fill(0, 5, ['version' => 7, 'secret' => self::KEY_1])],
        ] as $args) {
            try {
                $engine->derive(...$args);
                self::fail('Malformed keyring must be rejected.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Versoes de vinculo CNPJ trial invalidas.', $e->getMessage());
                self::assertStringNotContainsString('SECRET_FAKE', $e->getMessage());
                self::assertStringNotContainsString(self::KEY_1, $e->getMessage());
            }
        }
    }

    public function test_internal_candidates_do_not_echo_plaintext_cnpj_or_key_bytes(): void
    {
        $result = (new OfflineCnpjTrialKeyRotationCandidates())->derive(
            '12.ABC.345/01DE-35', self::keyring(),
        );
        $internal = json_encode($result, JSON_THROW_ON_ERROR);
        foreach (['12ABC34501DE35', '12.ABC.345/01DE-35', self::KEY_1, self::KEY_2] as $secret) {
            self::assertStringNotContainsString($secret, $internal);
        }
        foreach ($result['candidate_versions_and_bindings'] as $entry) {
            self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $entry['internal_binding']);
        }
    }
}
