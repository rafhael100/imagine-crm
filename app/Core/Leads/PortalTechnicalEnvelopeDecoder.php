<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;
use JsonException;

/**
 * F3-47: strict *flat technical metadata* JSON decoder for a future private
 * Portal handoff adapter. This is NOT a public request handler, identity,
 * authorization, anti-replay check, payload decoder or CRM persistence.
 *
 * PHP's json_decode() accepts duplicate object keys (last value wins). The
 * lexer rejects that ambiguity BEFORE the ordinary JSON structural check.
 * Only JSON strings and number values are permitted, no nested objects,
 * arrays, booleans or null. The existing F3-11 inspector checks field names
 * and types after the duplicate check.
 */
final class PortalTechnicalEnvelopeDecoder
{
    public const MAX_BYTES = 2048;

    /** @return array<string,mixed> Eight untrusted technical fields. */
    public function decode(string $raw): array
    {
        $length = strlen($raw);
        if ($length < 2 || $length > self::MAX_BYTES) {
            throw new InvalidArgumentException('TECHNICAL_ENVELOPE_SIZE_INVALID');
        }

        // Explicit top-level object lexer. Escape-aware JSON string scanning
        // ensures commas, colons and braces INSIDE values cannot act as syntax.
        $position = 0;
        $this->ws($raw, $position, $length);
        $this->expect($raw, $position, '{');

        $seen = [];
        $count = 0;
        while (true) {
            $this->ws($raw, $position, $length);
            if ($position < $length && $raw[$position] === '}') {
                $position++;
                break;
            }
            if ($count >= 8) {
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_FIELDS_INVALID');
            }
            $token = $this->stringToken($raw, $position, $length);
            try {
                $key = json_decode($token, true, 8, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
            }
            if (!is_string($key)) {
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_FIELDS_INVALID');
            }
            if (array_key_exists($key, $seen)) {
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_DUPLICATE_KEY');
            }
            $seen[$key] = true;
            $count++;

            $this->ws($raw, $position, $length);
            $this->expect($raw, $position, ':');
            $this->ws($raw, $position, $length);
            if ($position >= $length) {
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
            }
            if ($raw[$position] === '"') {
                $this->stringToken($raw, $position, $length);
            } elseif (preg_match(
                '/\G-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+\-]?[0-9]+)?/',
                $raw, $match, 0, $position
            ) === 1) {
                $position += strlen($match[0]);
            } else {
                // No PII objects, arrays, bool, null or other JSON types.
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_VALUE_INVALID');
            }
            $this->ws($raw, $position, $length);
            if ($position < $length && $raw[$position] === ',') {
                $position++;
                $this->ws($raw, $position, $length);
                // A trailing comma is invalid JSON, no implicit field.
                if ($position >= $length || $raw[$position] === '}') {
                    throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
                }
                continue;
            }
            $this->expect($raw, $position, '}');
            break;
        }
        $this->ws($raw, $position, $length);
        if ($position !== $length || $count !== 8) {
            throw new InvalidArgumentException('TECHNICAL_ENVELOPE_FIELDS_INVALID');
        }

        try {
            $decoded = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
        }
        if (!is_array($decoded)
            || (new SyntheticLeadEnvelopeV1Inspector())->inspect($decoded)->reasonCode
                !== 'STRUCTURE_ONLY_UNVERIFIED') {
            throw new InvalidArgumentException('TECHNICAL_ENVELOPE_STRUCTURE_INVALID');
        }

        // The decoded references remain entirely UNTRUSTED. No permission,
        // service identity, consent, nonce uniqueness or durable receipt.
        return $decoded;
    }

    private function ws(string $raw, int &$position, int $length): void
    {
        while ($position < $length) {
            if ($raw[$position] !== ' ' && $raw[$position] !== "\t"
                && $raw[$position] !== "\n" && $raw[$position] !== "\r") {
                break;
            }
            $position++;
        }
    }

    private function expect(string $raw, int &$position, string $character): void
    {
        if (!isset($raw[$position]) || $raw[$position] !== $character) {
            throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
        }
        $position++;
    }

    private function stringToken(string $raw, int &$position, int $length): string
    {
        $start = $position;
        $this->expect($raw, $position, '"');

        while ($position < $length) {
            $byte = $raw[$position];
            if ($byte === '"') {
                $position++;
                return substr($raw, $start, $position - $start);
            }
            if ($byte === '\\') {
                // Let json_decode enforce escape semantics and unicode pairs;
                // avoid interpreting an escaped quote as a string boundary.
                $position += 2;
                continue;
            }
            if (ord($byte) < 0x20) {
                throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
            }
            $position++;
        }
        throw new InvalidArgumentException('TECHNICAL_ENVELOPE_JSON_INVALID');
    }
}
