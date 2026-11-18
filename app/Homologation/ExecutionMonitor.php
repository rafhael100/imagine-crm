<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Homologation\S2S\StrictJson;
use InvalidArgumentException;
use stdClass;
use UnexpectedValueException;

/** Closed presentation of locally persisted observations, never execution authority. */
final class ExecutionMonitor
{
    private const UUID = '/\A[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\z/D';
    private const MAX_EPOCH = 4102444800;
    private const MAX_COUNT = 2147483647;

    public static function enabled(): bool
    {
        return getenv('CRM_N8N_EXECUTION_MONITOR') === 'synthetic-private-v1'
            && Environment::enabled();
    }

    /** Browser input may select an event, never its company or authority. */
    public static function selectedEvent(array $query): ?string
    {
        if (($query['section'] ?? null) !== 'n8n'
            || array_diff(array_keys($query), ['section', 'lang', 'event']) !== []
            || (array_key_exists('lang', $query)
                && !in_array($query['lang'], ['pt', 'en', 'es'], true))
            || (array_key_exists('event', $query) && !self::uuid($query['event']))) {
            throw new InvalidArgumentException('Invalid execution monitor query.');
        }

        return $query['event'] ?? null;
    }

    /**
     * Decode only the narrow SQL reader's result inside the verified membership
     * transaction. Receipt wire bytes, hashes and service identities are not a view.
     * A missing selected event remains null so the caller can return a uniform 404.
     */
    public static function read(string $raw, string $company, ?string $selectedEvent = null): array
    {
        if (!self::uuid($company) || ($selectedEvent !== null && !self::uuid($selectedEvent))) {
            self::invalid();
        }
        // Reuse the reviewed duplicate-key and size/depth checks. Preserve JSON
        // container types too: an empty object must not impersonate an empty list.
        StrictJson::decodeObject($raw, 65536, 16);
        $root = self::object(json_decode($raw, false, 16, JSON_THROW_ON_ERROR),
            ['schema', 'company_id', 'generated_at', 'total_events', 'events', 'selected']);
        if ($root['schema'] !== 'CRM_N8N_EXECUTION_MONITOR_V1' || $root['company_id'] !== $company
            || !self::integer($root['generated_at'], 0, self::MAX_EPOCH)
            || !self::integer($root['total_events'], 0, self::MAX_COUNT)
            || !is_array($root['events']) || !array_is_list($root['events'])
            || count($root['events']) !== min(50, $root['total_events'])) {
            self::invalid();
        }

        $events = [];
        $byId = [];
        $previous = null;
        foreach ($root['events'] as $item) {
            $event = self::event($item, false);
            if (isset($byId[$event['event_id']])
                || ($previous !== null && !self::newer($previous, $event))) {
                self::invalid();
            }
            $events[] = $event;
            $byId[$event['event_id']] = $event;
            $previous = $event;
        }

        $selected = $root['selected'] === null ? null : self::event($root['selected'], true);
        if ($selected !== null) {
            if ($selectedEvent === null || $selected['event_id'] !== $selectedEvent) {
                self::invalid();
            }
            $listed = $selected;
            $listed['history'] = [];
            if (isset($byId[$selectedEvent])) {
                if ($byId[$selectedEvent] !== $listed) {
                    self::invalid();
                }
            } elseif ($root['total_events'] <= 50 || $previous === null
                || !self::newer($previous, $selected)) {
                // A selected older event may fall outside the first page; an
                // event that belongs on that page cannot silently be omitted.
                self::invalid();
            }
        } elseif ($selectedEvent !== null && isset($byId[$selectedEvent])) {
            self::invalid();
        }

        return [
            'schema' => 'CRM_N8N_EXECUTION_MONITOR_V1', 'company_id' => $company,
            'generated_at' => $root['generated_at'], 'total_events' => $root['total_events'],
            'events' => $events, 'selected' => $selected,
        ];
    }

    private static function event(mixed $item, bool $detail): array
    {
        $event = self::object($item, ['event_id', 'lead_id', 'lead_title', 'occurred_at',
            'delivery_state', 'observation', 'history_count', 'history']);
        if (!self::uuid($event['event_id']) || !self::uuid($event['lead_id'])
            || !is_string($event['lead_title'])
            || preg_match('/\ALEAD_TESTE_[A-Z0-9_]{1,40}\z/D', $event['lead_title']) !== 1
            || !self::integer($event['occurred_at'], 0, self::MAX_EPOCH)
            || !in_array($event['delivery_state'], ['pending', 'accepted'], true)
            || !self::integer($event['history_count'], 0, self::MAX_COUNT)
            || !is_array($event['history']) || !array_is_list($event['history'])
            || count($event['history']) !== ($detail ? min(20, $event['history_count']) : 0)) {
            self::invalid();
        }
        $observation = $event['observation'] === null ? null : self::observation($event['observation']);
        if (($observation === null) !== ($event['history_count'] === 0)
            || ($observation !== null && $observation['sequence'] !== $event['history_count'])) {
            self::invalid();
        }

        $history = [];
        foreach ($event['history'] as $offset => $item) {
            $entry = self::observation($item);
            if ($entry['sequence'] !== $event['history_count'] - $offset
                || ($offset === 0 && $entry !== $observation)) {
                self::invalid();
            }
            if ($offset > 0) {
                self::transition($entry, $history[$offset - 1]);
            }
            $history[] = $entry;
        }

        return [
            'event_id' => $event['event_id'], 'lead_id' => $event['lead_id'],
            'lead_title' => $event['lead_title'], 'occurred_at' => $event['occurred_at'],
            'delivery_state' => $event['delivery_state'], 'observation' => $observation,
            'history_count' => $event['history_count'], 'history' => $history,
        ];
    }

    private static function observation(mixed $item): array
    {
        $observation = self::object($item, ['sequence', 'execution_state', 'queue_state',
            'result_code', 'attempt', 'observed_at']);
        if (!self::integer($observation['sequence'], 1, self::MAX_COUNT)
            || !self::integer($observation['observed_at'], 0, self::MAX_EPOCH)
            || !self::integer($observation['attempt'], 0, 3)) {
            self::invalid();
        }
        $queue = $observation['queue_state'];
        $result = $observation['result_code'];
        $attempt = $observation['attempt'];
        $valid = match ($observation['execution_state']) {
            'NOT_STAGED' => $queue === 'NONE' && $result === 'NONE' && $attempt === 0,
            'NOT_STARTED' => in_array($queue, ['PENDING', 'LEASED', 'DEAD'], true)
                && $result === 'NONE' && $attempt === 0,
            'STARTED', 'UNKNOWN' => in_array($queue, ['LEASED', 'DEAD'], true)
                && $result === 'NONE' && $attempt >= 1,
            'RESULT' => in_array($queue, ['LEASED', 'DEAD'], true)
                && $result === 'PREPARED' && $attempt >= 1,
            'ACKED' => $queue === 'ACKED' && $result === 'PREPARED' && $attempt >= 1,
            default => false,
        };
        if (!$valid) {
            self::invalid();
        }

        return [
            'sequence' => $observation['sequence'], 'execution_state' => $observation['execution_state'],
            'queue_state' => $queue, 'result_code' => $result, 'attempt' => $attempt,
            'observed_at' => $observation['observed_at'],
        ];
    }

    /** Sequence is authoritative; clock adjustments do not reorder persisted history. */
    private static function transition(array $before, array $after): void
    {
        $states = match ($before['execution_state']) {
            'NOT_STAGED' => ['NOT_STARTED', 'STARTED', 'RESULT', 'ACKED', 'UNKNOWN'],
            'NOT_STARTED' => ['NOT_STARTED', 'STARTED', 'RESULT', 'ACKED', 'UNKNOWN'],
            'STARTED' => ['STARTED', 'RESULT', 'ACKED', 'UNKNOWN'],
            'RESULT' => ['RESULT', 'ACKED'],
            'UNKNOWN' => ['UNKNOWN'],
            default => [],
        };
        $queues = match ($before['queue_state']) {
            'NONE' => ['PENDING', 'LEASED', 'ACKED', 'DEAD'],
            'PENDING' => ['PENDING', 'LEASED', 'ACKED', 'DEAD'],
            'LEASED' => ['LEASED', 'ACKED', 'DEAD'],
            'DEAD' => ['DEAD'],
            default => [],
        };
        if (!in_array($after['execution_state'], $states, true)
            || !in_array($after['queue_state'], $queues, true)
            || ($before['attempt'] > 0 && $before['attempt'] !== $after['attempt'])
            || [$before['execution_state'], $before['queue_state'], $before['result_code'], $before['attempt']]
                === [$after['execution_state'], $after['queue_state'], $after['result_code'], $after['attempt']]) {
            self::invalid();
        }
    }

    private static function object(mixed $value, array $fields): array
    {
        if (!$value instanceof stdClass) {
            self::invalid();
        }
        $data = get_object_vars($value);
        $keys = array_keys($data);
        sort($keys);
        sort($fields);
        if ($keys !== $fields) {
            self::invalid();
        }

        return $data;
    }

    private static function integer(mixed $value, int $min, int $max): bool
    {
        return is_int($value) && $value >= $min && $value <= $max;
    }

    private static function uuid(mixed $value): bool
    {
        return is_string($value) && preg_match(self::UUID, $value) === 1;
    }

    private static function newer(array $first, array $second): bool
    {
        return $first['occurred_at'] > $second['occurred_at']
            || ($first['occurred_at'] === $second['occurred_at']
                && strcmp($first['event_id'], $second['event_id']) > 0);
    }

    private static function invalid(): never
    {
        throw new UnexpectedValueException('Execution monitor unavailable.');
    }
}
