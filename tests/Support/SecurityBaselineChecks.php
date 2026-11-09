<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Authorization\Authorizer;
use App\Core\Authorization\DenyByDefaultAuthorizer;
use Closure;
use ReflectionClass;
use ReflectionFunction;

/** CI assertions only; no provider, authorizer, grant or runtime policy. */
final class SecurityBaselineChecks
{
    public static function authorizerIsContained(Authorizer $authorizer, array $requests): bool
    {
        if ($authorizer::class !== DenyByDefaultAuthorizer::class) { return false; }
        foreach ($requests as $request) { if ($authorizer->decide($request)->allowed !== false) { return false; } }
        return true;
    }

    public static function codexPullRequestTargetsDevelopment(array $event): bool
    {
        $pr = $event['pull_request'] ?? null;
        if (!is_array($pr) || !is_string($pr['head']['ref'] ?? null) || !is_string($pr['base']['ref'] ?? null)) { return false; }
        // Legacy DEV -> main PR #2 is outside this Codex-branch rule; never edit it.
        if (!str_starts_with($pr['head']['ref'], 'codex/')) { return true; }
        return $pr['base']['ref'] === 'dev/fast-track-crm-2026-11-04'
            && ($pr['base']['repo']['full_name'] ?? null) === 'rafhael100/imagine-crm'
            && ($pr['head']['repo']['full_name'] ?? null) === 'rafhael100/imagine-crm';
    }

    public static function hasLaboratoryReference(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (self::hasLaboratoryReference($key) || self::hasLaboratoryReference($item)) { return true; }
            }
            return false;
        }
        if (is_string($value)) {
            $name = strtolower(ltrim($value, '\\'));
            foreach (['Tests\\Fixtures', 'Tests\\Concerns', 'Tests\\Support'] as $prefix) {
                $prefix = strtolower($prefix);
                if ($name === $prefix || str_starts_with($name, $prefix.'\\')) { return true; }
            }
            return false;
        }
        if ($value instanceof Closure) { $file = (new ReflectionFunction($value))->getFileName(); }
        elseif (is_object($value)) { $file = (new ReflectionClass($value))->getFileName(); }
        else { return false; }
        return $file !== false && str_starts_with(str_replace('\\', '/', strtolower($file)), str_replace('\\', '/', strtolower(base_path('tests'))).'/');
    }

    /** Supplemental token check: ignores comments and checks PHP names/literal class references. */
    public static function sourceReferencesLaboratory(string $source): bool
    {
        foreach (token_get_all($source) as $token) {
            if (!is_array($token)) { continue; }
            if (in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING], true)
                && self::hasLaboratoryReference($token[1])) { return true; }
            if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
                $literal = str_replace('\\\\', '\\', substr($token[1], 1, -1));
                if (self::hasLaboratoryReference($literal)) { return true; }
            }
        }
        return false;
    }

    public static function aclMatches(array $actual, array $expected): bool
    {
        foreach ($actual as &$rights) { sort($rights); } unset($rights);
        foreach ($expected as &$rights) { sort($rights); } unset($rights);
        ksort($actual); ksort($expected); return $actual === $expected;
    }
}
