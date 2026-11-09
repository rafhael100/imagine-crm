<?php

declare(strict_types=1);

namespace App\Core\Plans;

use InvalidArgumentException;

/**
 * Pure arithmetic projection of an ALREADY PROVIDED integer quota snapshot.
 *
 * The calling service must obtain trusted tenant, plan, membership, usage and
 * limits separately. This class is neither authorization nor a reservation:
 * it does not query storage, enable features, debit credits, create users,
 * choose prices or make a payment.
 *
 * The unit is opaque and consistent (e.g. whole lead count, whole byte count).
 * A value of zero means an actual configured zero, NEVER unlimited.
 */
final readonly class QuotaUsageProjection
{
    public function __construct(
        public string $meter,
        public int $configuredLimit,
        public int $currentlyUsed,
        public int $requestedIncrease,
    ) {
        if (preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $meter) !== 1) {
            throw new InvalidArgumentException('Medidor de franquia invalido.');
        }

        if ($configuredLimit < 0 || $currentlyUsed < 0 || $requestedIncrease < 0) {
            throw new InvalidArgumentException('Quantidade de franquia invalida.');
        }

        if ($requestedIncrease > PHP_INT_MAX - $currentlyUsed) {
            throw new InvalidArgumentException('Quantidade de franquia excede limite inteiro.');
        }
    }

    public function projectedUsed(): int
    {
        return $this->currentlyUsed + $this->requestedIncrease;
    }

    public function remainingBefore(): int
    {
        return $this->configuredLimit > $this->currentlyUsed
            ? $this->configuredLimit - $this->currentlyUsed
            : 0;
    }

    public function remainingAfter(): int
    {
        return $this->configuredLimit > $this->projectedUsed()
            ? $this->configuredLimit - $this->projectedUsed()
            : 0;
    }

    public function existingExcess(): int
    {
        return $this->currentlyUsed > $this->configuredLimit
            ? $this->currentlyUsed - $this->configuredLimit
            : 0;
    }

    public function projectedExcess(): int
    {
        return $this->projectedUsed() > $this->configuredLimit
            ? $this->projectedUsed() - $this->configuredLimit
            : 0;
    }

    public function fitsWithinConfiguredLimit(): bool
    {
        return $this->projectedUsed() <= $this->configuredLimit;
    }

    public function isAlreadyOverLimit(): bool
    {
        return $this->currentlyUsed > $this->configuredLimit;
    }
}
