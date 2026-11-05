<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/** Independent in-memory expectations from controlled test setup, never a DB-derived authority. */
final readonly class LedgerFixtureManifest
{
    public function __construct(public array $cases)
    {
        if (!array_is_list($cases) || count($cases) > 200) { throw new \InvalidArgumentException('Invalid laboratory manifest.'); }
        $keys = []; $attempts = [];
        foreach ($cases as $case) {
            if (!is_array($case) || count($case) !== 2 || !(($case[0] ?? null) instanceof LedgerDescription)
                || !is_array($case[1]) || !array_is_list($case[1])) { throw new \InvalidArgumentException('Invalid laboratory manifest.'); }
            $key = self::key($case[0]);
            if (isset($keys[$key])) { throw new \InvalidArgumentException('Ambiguous laboratory manifest.'); }
            $keys[$key] = true;
            foreach ($case[1] as $receipt) {
                if (!$receipt instanceof LedgerReceipt || isset($attempts[$receipt->attemptId])
                    || !in_array($receipt->outcome, ['first_seen', 'duplicate', 'conflict'], true)) {
                    throw new \InvalidArgumentException('Invalid laboratory receipt.');
                }
                $attempts[$receipt->attemptId] = true;
                if (count($attempts) > 200) { throw new \InvalidArgumentException('Laboratory sample limit.'); }
            }
        }
    }

    public static function key(LedgerDescription $description): string
    {
        $r = $description->request;
        return implode('|', [$r->tenantId, $r->operationId, $description->revision, $r->intent->scope, $r->intent->scopeId]);
    }
}
