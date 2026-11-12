<?php

declare(strict_types=1);

namespace App\Core\Sales;

use InvalidArgumentException;

/**
 * VEN-R11/R14/R16 laboratory: descriptive version/status evidence only.
 *
 * Every status and view bit is an untrusted fictional claim. The model cannot
 * issue a proposal, verify a customer's acceptance, bind one version to
 * another, authenticate a viewer, sign, charge, persist, or enforce deadlines.
 *
 * A prior-version "accepted" label never carries to the latest version.
 * A "viewed" label never constitutes commercial acceptance.
 */
final class SyntheticProposalVersionReview
{
    private const MAX_VERSIONS = 8;
    private const STATUSES = ['draft', 'sent', 'accepted', 'declined', 'expired'];

    /**
     * @param mixed $versions list<array{version:int,status:string,viewed:bool}>
     * @return array{
     *   versions_seen:int,latest_version:?int,latest_claimed_status:?string,
     *   latest_viewed:bool,claimed_status_counts:array{
     *     draft:int,sent:int,accepted:int,declined:int,expired:int},
     *   viewed_without_claimed_acceptance:int,prior_claimed_accepted_versions:int,
     *   prior_acceptance_applies_to_latest:false,viewed_equals_acceptance:false,
     *   authority_verified:false,informational_only:true,
     *   can_send:false,can_accept:false,can_sign:false,
     *   can_charge:false,can_persist:false
     * }
     */
    public function inspect(mixed $versions): array
    {
        if (!is_array($versions)
            || !array_is_list($versions)
            || count($versions) > self::MAX_VERSIONS) {
            self::invalid();
        }

        $counts = array_fill_keys(self::STATUSES, 0);
        $viewedWithoutAcceptance = 0;
        $priorAccepted = 0;
        $latest = null;
        $latestStatus = null;
        $latestViewed = false;

        foreach ($versions as $index => $version) {
            if (!is_array($version) || count($version) !== 3) {
                self::invalid();
            }
            $keys = array_keys($version);
            sort($keys);
            if ($keys !== ['status', 'version', 'viewed']
                || !is_int($version['version'])
                || $version['version'] !== $index + 1
                || !is_string($version['status'])
                || !in_array($version['status'], self::STATUSES, true)
                || !is_bool($version['viewed'])) {
                self::invalid();
            }

            ++$counts[$version['status']];
            if ($version['viewed'] && $version['status'] !== 'accepted') {
                ++$viewedWithoutAcceptance;
            }
            if ($version['status'] === 'accepted' && $index < count($versions) - 1) {
                ++$priorAccepted;
            }
            $latest = $version['version'];
            $latestStatus = $version['status'];
            $latestViewed = $version['viewed'];
        }

        return [
            'versions_seen' => count($versions),
            'latest_version' => $latest,
            'latest_claimed_status' => $latestStatus,
            'latest_viewed' => $latestViewed,
            'claimed_status_counts' => $counts,
            'viewed_without_claimed_acceptance' => $viewedWithoutAcceptance,
            'prior_claimed_accepted_versions' => $priorAccepted,
            'prior_acceptance_applies_to_latest' => false,
            'viewed_equals_acceptance' => false,
            'authority_verified' => false,
            'informational_only' => true,
            'can_send' => false,
            'can_accept' => false,
            'can_sign' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        // Fail closed without reflecting a proposal, customer, tenant or value.
        throw new InvalidArgumentException('Revisao sintetica de versoes invalida.');
    }
}
