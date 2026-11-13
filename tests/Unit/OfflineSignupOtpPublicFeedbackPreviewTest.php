<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Identity\OfflineSignupOtpPublicFeedbackPreview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OfflineSignupOtpPublicFeedbackPreviewTest extends TestCase
{
    public function test_all_issue_outcomes_have_identical_public_message_and_flags(): void
    {
        $engine = new OfflineSignupOtpPublicFeedbackPreview();
        $statuses = [
            'sample_within_illustrative_budget',
            'sample_contact_budget_exhausted',
            'sample_origin_budget_exhausted',
            'sample_cooldown_active',
        ];
        $reference = null;
        foreach ($statuses as $status) {
            $view = $engine->preview('issue', $status);
            $reference ??= $view;
            self::assertSame($reference, $view);
            self::assertSame('demo_only_no_action', $view['public_status_code']);
            self::assertStringContainsString('nenhum código foi enviado', $view['public_message']);
            self::assertTrue($view['internal_reason_hidden']);
            self::assertFalse($view['can_send']);
            self::assertFalse($view['contact_existence_known']);
        }
    }

    public function test_correct_wrong_expired_and_consumed_claims_have_identical_public_view(): void
    {
        $engine = new OfflineSignupOtpPublicFeedbackPreview();
        $reasons = [
            'candidate_matches_only',
            'candidate_mismatch',
            'before_issue',
            'expired',
            'already_consumed_claim',
            'attempt_budget_exhausted_claim',
        ];
        $reference = null;
        foreach ($reasons as $status) {
            $view = $engine->preview('verify', $status);
            $reference ??= $view;
            self::assertSame($reference, $view);
            self::assertStringContainsString('nenhum acesso foi ativado', $view['public_message']);
            foreach (['contact_existence_known', 'identity_verified',
                'can_send', 'can_register', 'can_authenticate', 'can_grant_access', 'can_persist'] as $flag) {
                self::assertFalse($view[$flag], $flag);
            }
        }
    }

    public function test_unknown_status_and_sensitive_input_fail_closed_without_echo(): void
    {
        $engine = new OfflineSignupOtpPublicFeedbackPreview();
        foreach ([
            [null, 'expired'], ['email', 'candidate_mismatch'],
            ['verify', 'sample_contact_budget_exhausted'],
            ['issue', 'candidate_matches_only'],
            ['issue', 'alice@example.org'], ['issue', ''],
            ['verify', ['candidate_matches_only']], ['verify', "expired\r\nSet-Cookie: session"],
        ] as $args) {
            try {
                $engine->preview(...$args);
                self::fail('Invalid public status accepted.');
            } catch (InvalidArgumentException $e) {
                self::assertSame('Mensagem local OTP invalida.', $e->getMessage());
                self::assertStringNotContainsString('alice@example.org', $e->getMessage());
                self::assertStringNotContainsString('Set-Cookie', $e->getMessage());
            }
        }
    }

    public function test_offline_feedback_does_not_perform_transport_storage_or_authentication(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2).'/app/Core/Identity/OfflineSignupOtpPublicFeedbackPreview.php',
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
