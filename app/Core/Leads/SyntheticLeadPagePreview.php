<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * F3-38: cursor-free, bounded pagination of purely synthetic fixture positions.
 *
 * Page numbers are local demo offsets, NOT authenticated pagination tokens.
 * No lead content, principal, tenant, external input execution, API, SQL,
 * persistence, entitlement or access is implemented or implied.
 */
final class SyntheticLeadPagePreview
{
    /**
     * @param list<array{stage:string,score:int}> $fixtures
     * @return array{
     *   positions:list<int>,total:int,matched:int,page:int,page_size:int,
     *   page_count:int,has_previous:bool,has_next:bool,empty:bool,authorized:false
     * }
     */
    public function page(
        array $fixtures,
        string $stage = 'all',
        string $temperature = 'all',
        string $sortMode = 'original',
        int $page = 0,
        int $pageSize = 4,
    ): array {
        // Fixed maximum of 20 synthetic input rows, validated in the source.
        // Never truncate data to make an invalid fixture appear valid.
        if ($page < 0 || $page > 19 || $pageSize < 1 || $pageSize > 20) {
            throw new InvalidArgumentException('Invalid synthetic page parameters.');
        }

        $preview = (new SyntheticLeadListPreview())->preview(
            $fixtures,
            $stage,
            $temperature,
            $sortMode,
        );
        $matched = $preview['matched'];
        $pageCount = $matched === 0 ? 0 : intdiv($matched - 1, $pageSize) + 1;
        if (($matched === 0 && $page !== 0) || ($matched !== 0 && $page >= $pageCount)) {
            throw new InvalidArgumentException('Synthetic page outside bounded fixture range.');
        }

        return [
            'positions' => array_values(array_slice($preview['positions'], $page * $pageSize, $pageSize)),
            'total' => $preview['total'],
            'matched' => $matched,
            'page' => $page,
            'page_size' => $pageSize,
            'page_count' => $pageCount,
            'has_previous' => $page > 0,
            'has_next' => $pageCount > 0 && $page < $pageCount - 1,
            'empty' => $preview['empty'],
            'authorized' => false,
        ];
    }
}
