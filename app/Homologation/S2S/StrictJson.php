<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use InvalidArgumentException;
use JsonException;

/** Bounded JSON objects with one unambiguous name per object scope. */
final class StrictJson
{
    /** @return array<array-key, mixed> */
    public static function decodeObject(string $raw, int $maxBytes = 4096, int $depth = 8): array
    {
        if ($maxBytes < 2 || $depth < 1 || $depth > 64) {
            throw new InvalidArgumentException('INVALID_JSON_LIMITS');
        }
        $length = strlen($raw);
        if ($length > $maxBytes) {
            throw new JsonException('JSON_SIZE_LIMIT');
        }

        // The native decoder owns syntax, UTF-8, number types and depth limits.
        // The scan below only detects information json_decode would discard.
        $decoded = json_decode($raw, true, $depth, JSON_THROW_ON_ERROR);
        if (!str_starts_with(ltrim($raw, " \t\r\n"), '{') || !is_array($decoded)) {
            throw new JsonException('JSON_OBJECT_REQUIRED');
        }

        // null marks an array; each object has an independent name set.
        $scopes = [];
        for ($offset = 0; $offset < $length; $offset++) {
            $byte = $raw[$offset];
            if ($byte === '{') {
                $scopes[] = [];
            } elseif ($byte === '[') {
                $scopes[] = null;
            } elseif ($byte === '}' || $byte === ']') {
                array_pop($scopes);
            } elseif ($byte === '"') {
                $start = $offset;
                for ($offset++; $offset < $length; $offset++) {
                    if ($raw[$offset] === '\\') {
                        // Escaped quotes/backslashes cannot terminate a string.
                        $offset++;
                    } elseif ($raw[$offset] === '"') {
                        break;
                    }
                }
                $next = $offset + 1;
                while ($next < $length && str_contains(" \t\r\n", $raw[$next])) {
                    $next++;
                }
                // Valid JSON permits ':' after a string only for an object name.
                if ($next < $length && $raw[$next] === ':') {
                    $scope = count($scopes) - 1;
                    if ($scope < 0 || !is_array($scopes[$scope])) {
                        throw new JsonException('JSON_OBJECT_REQUIRED');
                    }
                    $name = json_decode(substr($raw, $start, $offset - $start + 1),
                        true, 2, JSON_THROW_ON_ERROR);
                    // A prefix avoids PHP coercing numeric names to integer keys.
                    // Native decoding also equates Unicode/escaped spellings.
                    $identity = 'key:'.$name;
                    if (isset($scopes[$scope][$identity])) {
                        throw new JsonException('DUPLICATE_JSON_KEY');
                    }
                    $scopes[$scope][$identity] = true;
                }
            }
        }

        return $decoded;
    }
}
