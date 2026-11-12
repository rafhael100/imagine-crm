<?php

declare(strict_types=1);

namespace App\Core\Sales;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * VEN-R11/R15/T10 laboratory: descriptive validity bounds of a fictional
 * proposal version. No verified issue, recipient, policy, link, signature,
 * acceptance, authentication, renewal or financial effects.
 */
final readonly class SyntheticProposalValidityPreview
{
    private const MAX_WINDOW_SECONDS = 366 * 86400;

    private int $issued;
    private int $expires;
    private int $observed;

    public function __construct(
        string $issuedAtUtc,
        string $expiresAtUtc,
        string $observedAtUtc,
    ) {
        $issued = self::timestamp($issuedAtUtc);
        $expires = self::timestamp($expiresAtUtc);
        $observed = self::timestamp($observedAtUtc);

        if ($expires <= $issued || $expires - $issued > self::MAX_WINDOW_SECONDS) {
            self::invalid();
        }

        $this->issued = $issued;
        $this->expires = $expires;
        $this->observed = $observed;
    }

    public function phase(): string
    {
        if ($this->observed < $this->issued) {
            return 'before_issue';
        }

        return $this->observed < $this->expires ? 'within_validity' : 'expired';
    }

    public function secondsUntilIssue(): int
    {
        return $this->observed < $this->issued
            ? $this->issued - $this->observed : 0;
    }

    public function secondsUntilExpiry(): int
    {
        return $this->observed < $this->expires
            ? $this->expires - $this->observed : 0;
    }

    /**
     * @return array{
     *   phase:string,issued_at_epoch:int,expires_at_epoch:int,
     *   validity_seconds:int,seconds_until_issue:int,seconds_until_expiry:int,
     *   validity_policy_verified:false,link_authorized:false,
     *   acceptance_verified:false,informational_only:true,
     *   authorized:false,can_send:false,can_accept:false,
     *   can_renew:false,can_sign:false,can_charge:false,can_persist:false
     * }
     */
    public function readout(): array
    {
        return [
            'phase' => $this->phase(),
            'issued_at_epoch' => $this->issued,
            'expires_at_epoch' => $this->expires,
            'validity_seconds' => $this->expires - $this->issued,
            'seconds_until_issue' => $this->secondsUntilIssue(),
            'seconds_until_expiry' => $this->secondsUntilExpiry(),
            'validity_policy_verified' => false,
            'link_authorized' => false,
            'acceptance_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_send' => false,
            'can_accept' => false,
            'can_renew' => false,
            'can_sign' => false,
            'can_charge' => false,
            'can_persist' => false,
        ];
    }

    private static function timestamp(string $utc): int
    {
        // Only modern illustrative years and exact UTC seconds. Datetime
        // normalizes invalid dates, so require byte-exact calendar round-trip.
        if (preg_match('/\A20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z\z/D', $utc) !== 1) {
            self::invalid();
        }

        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i:s\Z',
            $utc,
            new DateTimeZone('UTC'),
        );
        if ($date === false || $date->format('Y-m-d\TH:i:s\Z') !== $utc) {
            self::invalid();
        }

        return $date->getTimestamp();
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Validade sintetica de proposta invalida.');
    }
}
