<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Core\Async\AsyncAuthorityRequest;
use App\Core\Authorization\ExperimentalResourceCatalog;
use InvalidArgumentException;

/** Closed SYNTHETIC content only. Not a principal, capability or business payload. */
final readonly class LedgerDescription
{
    private function __construct(public AsyncAuthorityRequest $request, public int $revision, public string $digest) {}

    public static function parse(array $input, int $revision, string $content): self
    {
        $request = AsyncAuthorityRequest::fromArray($input);
        if (!in_array($revision, [1, 2], true) || !in_array($content, ['lab.alpha', 'lab.beta'], true)
            || !(new ExperimentalResourceCatalog())->describes($request->intent)
            || !in_array($request->tenantId, [AuthorizationCases::TENANT, AuthorizationCases::OTHER], true)
            || strtolower($request->executor['tenant_id']) !== $request->tenantId
            || ($request->intent->scope === 'tenant' && $request->intent->scopeId !== $request->tenantId)) {
            throw new InvalidArgumentException('Invalid synthetic ledger description.');
        }
        // Transport correlations change per delivery; claims and validity are semantic.
        unset($input['correlation_id'], $input['causation_id']);
        $canonical = self::canonical(['request' => $input, 'revision' => $revision, 'content' => $content]);
        return new self($request, $revision, hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR)));
    }

    private static function canonical(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);
            return array_map(self::canonical(...), $value);
        }
        return is_string($value) && \Illuminate\Support\Str::isUuid($value) ? strtolower($value) : $value;
    }
}
