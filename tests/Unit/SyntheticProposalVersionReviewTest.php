<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Sales\SyntheticProposalVersionReview;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SyntheticProposalVersionReviewTest extends TestCase
{
    private function inspector(): SyntheticProposalVersionReview
    {
        return new SyntheticProposalVersionReview();
    }

    public function test_a_prior_fictitious_accepted_version_does_not_accept_a_new_draft(): void
    {
        $input = [
            ['version' => 1, 'status' => 'accepted', 'viewed' => true],
            ['version' => 2, 'status' => 'draft', 'viewed' => false],
        ];
        $original = $input;
        $out = $this->inspector()->inspect($input);

        self::assertSame(2, $out['versions_seen']);
        self::assertSame(2, $out['latest_version']);
        self::assertSame('draft', $out['latest_claimed_status']);
        self::assertFalse($out['latest_viewed']);
        self::assertSame(1, $out['prior_claimed_accepted_versions']);
        self::assertSame([
            'draft' => 1, 'sent' => 0, 'accepted' => 1,
            'declined' => 0, 'expired' => 0,
        ], $out['claimed_status_counts']);
        self::assertFalse($out['prior_acceptance_applies_to_latest']);
        self::assertFalse($out['viewed_equals_acceptance']);
        self::assertFalse($out['authority_verified']);
        self::assertSame($original, $input);
    }

    public function test_a_viewed_version_is_never_claimed_as_acceptance(): void
    {
        $out = $this->inspector()->inspect([
            ['version' => 1, 'status' => 'sent', 'viewed' => true],
            ['version' => 2, 'status' => 'expired', 'viewed' => true],
            ['version' => 3, 'status' => 'declined', 'viewed' => true],
            ['version' => 4, 'status' => 'accepted', 'viewed' => false],
        ]);
        self::assertSame(3, $out['viewed_without_claimed_acceptance']);
        self::assertSame(0, $out['prior_claimed_accepted_versions']);
        self::assertSame('accepted', $out['latest_claimed_status']);
        self::assertFalse($out['authority_verified']);
        self::assertFalse($out['viewed_equals_acceptance']);
        foreach (['can_send', 'can_accept', 'can_sign', 'can_charge', 'can_persist'] as $ability) {
            self::assertFalse($out[$ability]);
        }
    }

    public function test_empty_and_maximum_bounded_illustrative_versions_are_non_operational(): void
    {
        $empty = $this->inspector()->inspect([]);
        self::assertSame(0, $empty['versions_seen']);
        self::assertNull($empty['latest_version']);
        self::assertNull($empty['latest_claimed_status']);
        self::assertFalse($empty['latest_viewed']);
        self::assertSame(0, array_sum($empty['claimed_status_counts']));

        $items = [];
        for ($n = 1; $n <= 8; ++$n) {
            $items[] = ['version' => $n, 'status' => $n === 8 ? 'draft' : 'accepted', 'viewed' => true];
        }
        $out = $this->inspector()->inspect($items);
        self::assertSame(8, $out['versions_seen']);
        self::assertSame(7, $out['prior_claimed_accepted_versions']);
        self::assertSame(1, $out['viewed_without_claimed_acceptance']);
        self::assertSame(8, $out['latest_version']);
        self::assertSame('draft', $out['latest_claimed_status']);
        self::assertFalse($out['can_accept']);
    }

    public function test_invalid_versions_states_and_private_metadata_are_rejected_generically(): void
    {
        $line = ['version' => 1, 'status' => 'draft', 'viewed' => false];
        $bad = [
            null, 1, false, 'draft',
            [2 => $line],
            [$line, $line],
            [['version' => 2, 'status' => 'draft', 'viewed' => false]],
            [['version' => '1', 'status' => 'draft', 'viewed' => false]],
            [['version' => 1.0, 'status' => 'draft', 'viewed' => false]],
            [['version' => true, 'status' => 'draft', 'viewed' => false]],
            [['version' => 1, 'status' => 'APPROVED', 'viewed' => false]],
            [['version' => 1, 'status' => 'paid', 'viewed' => false]],
            [['version' => 1, 'status' => true, 'viewed' => false]],
            [['version' => 1, 'status' => 'sent', 'viewed' => 1]],
            [['version' => 1, 'status' => 'sent', 'viewed' => 'true']],
            [['version' => 1, 'status' => 'sent']],
            [['version' => 1, 'status' => 'sent', 'viewed' => false, 'tenant' => 'private@example.test']],
            array_fill(0, 9, $line),
        ];
        foreach ($bad as $input) {
            try {
                $this->inspector()->inspect($input);
                self::fail('Malformed proposal review mock was accepted.');
            } catch (InvalidArgumentException $error) {
                self::assertSame('Revisao sintetica de versoes invalida.', $error->getMessage());
                self::assertStringNotContainsString('private@example.test', $error->getMessage());
            }
        }
    }

    public function test_report_contains_only_fixed_status_words_and_permanent_denials(): void
    {
        $out = $this->inspector()->inspect([
            ['status' => 'draft', 'version' => 1, 'viewed' => false],
        ]);
        self::assertSame([
            'versions_seen', 'latest_version', 'latest_claimed_status', 'latest_viewed',
            'claimed_status_counts', 'viewed_without_claimed_acceptance',
            'prior_claimed_accepted_versions', 'prior_acceptance_applies_to_latest',
            'viewed_equals_acceptance', 'authority_verified', 'informational_only',
            'can_send', 'can_accept', 'can_sign', 'can_charge', 'can_persist',
        ], array_keys($out));
        self::assertTrue($out['informational_only']);
        self::assertFalse($out['can_sign']);
        $json = json_encode($out, JSON_THROW_ON_ERROR);
        foreach (['tenant', 'email', 'phone', 'name', 'proposal_id', 'signature', 'url', 'source', 'payment'] as $field) {
            self::assertStringNotContainsString('"'.$field.'"', $json);
        }
    }
}
