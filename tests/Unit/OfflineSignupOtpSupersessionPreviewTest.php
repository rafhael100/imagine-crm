<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Identity\OfflineSignupOtpSupersessionPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpSupersessionPreviewTest extends TestCase
{
    private const KEY = 'synthetic-signup-otp-key-longer-than-32-bytes';
    private const BINDING = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    public function test_previous_valid_code_is_only_candidate_match_but_superseded_after_resend(): void
    {
        $otp = new OfflineSignupOtpChallenge();
        $engine = new OfflineSignupOtpSupersessionPreview();
        $first = $otp->issue(self::KEY, self::BINDING, 'email', 1000);
        $resend = $otp->issue(self::KEY, self::BINDING, 'email', 1060);

        // A primitive verifier *can* match the earlier code in isolation.
        // A real system must also reject it via a trusted latest pointer.
        $oldCandidate = $otp->inspect(
            $first['challenge'], $first['delivery_code'],
            self::KEY, self::BINDING, 'email', 1061, 0, false,
        );
        self::assertSame('candidate_matches_only', $oldCandidate['status_code']);

        $old = $engine->inspect($first['challenge'], $resend['challenge'], 1061);
        $current = $engine->inspect($resend['challenge'], $resend['challenge'], 1061);
        self::assertSame('earlier_challenge_superseded_claim', $old['reason_code']);
        self::assertSame('latest_challenge_claim_only', $current['reason_code']);
        self::assertFalse($old['can_consume']);
        self::assertFalse($current['can_consume']);
        self::assertFalse($current['latest_pointer_durable_verified']);
        self::assertFalse($current['same_contact_verified']);
        self::assertFalse($current['same_channel_verified']);
        self::assertFalse($current['challenge_mac_verified']);
        self::assertSame(OfflineSignupOtpChallenge::VALIDITY_SECONDS, $old['illustrative_validity_seconds']);

        $encoded = json_encode([$old, $current], JSON_THROW_ON_ERROR);
        foreach ([
            $first['delivery_code'], $resend['delivery_code'],
            $first['challenge']['nonce'], $resend['challenge']['nonce'],
            $first['challenge']['digest'], $resend['challenge']['digest'],
            self::BINDING,
        ] as $private) {
            self::assertStringNotContainsString($private, $encoded);
        }
    }

    public function test_latest_expires_at_exact_900_second_boundary_even_after_resend(): void
    {
        $otp = new OfflineSignupOtpChallenge();
        $engine = new OfflineSignupOtpSupersessionPreview();
        $one = $otp->issue(self::KEY, self::BINDING, 'phone', 1000)['challenge'];
        $two = $otp->issue(self::KEY, self::BINDING, 'phone', 1100)['challenge'];
        self::assertSame(
            'earlier_challenge_superseded_claim',
            $engine->inspect($one, $two, 1999)['reason_code'],
        );
        self::assertSame(
            'latest_expired_claim',
            $engine->inspect($one, $two, 2000)['reason_code'],
        );
        self::assertSame(
            'latest_expired_claim',
            $engine->inspect($two, $two, 2000)['reason_code'],
        );
    }

    public function test_untrusted_sequence_or_modified_record_fails_closed(): void
    {
        $otp = new OfflineSignupOtpChallenge();
        $engine = new OfflineSignupOtpSupersessionPreview();
        $one = $otp->issue(self::KEY, self::BINDING, 'email', 1000)['challenge'];
        $two = $otp->issue(self::KEY, self::BINDING, 'email', 1100)['challenge'];
        $modifiedDigest = $one;
        $modifiedDigest['digest'] = str_repeat('0', 64);
        $modifiedDeadline = $one;
        $modifiedDeadline['expires_at']++;
        foreach ([
            [$two, $one, 1101],
            [$one, $two, 900],
            [$one, $two, '1100'],
            [$one, $one, null],
            [$one, $modifiedDigest, 1100],
            [$modifiedDeadline, $two, 1100],
            [$one, ['nonce' => $one['nonce']], 1100],
        ] as $args) {
            try {
                $engine->inspect(...$args);
                self::fail('Invalid supersession evidence unexpectedly passed.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Revisao de reenvio OTP invalida.', $e->getMessage());
                self::assertStringNotContainsString($one['nonce'], $e->getMessage());
            }
        }
    }

    public function test_no_persistence_or_transport_is_exposed(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Core/Identity/OfflineSignupOtpSupersessionPreview.php',
        );
        self::assertIsString($source);
        foreach ([
            'Mail::', 'Http::', 'DB::', 'Redis::', 'Cache::',
            'file_put_contents(', 'getenv(', 'env(', 'curl_', 'https://', 'http://',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
