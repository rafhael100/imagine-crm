<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Homologation\ExecutionMonitor;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ExecutionMonitorTest extends TestCase
{
    private const COMPANY = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    private const EVENT = 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
    private const LEAD = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
    private const NOW = 1800000000;

    private static function observation(int $sequence, string $state, string $queue,
        string $result = 'NONE', int $attempt = 0): array
    {
        return ['sequence' => $sequence, 'execution_state' => $state, 'queue_state' => $queue,
            'result_code' => $result, 'attempt' => $attempt, 'observed_at' => self::NOW];
    }

    private static function event(array $history = []): array
    {
        return ['event_id' => self::EVENT, 'lead_id' => self::LEAD,
            'lead_title' => 'LEAD_TESTE_MONITOR', 'occurred_at' => self::NOW - 100,
            'delivery_state' => 'accepted', 'observation' => $history[0] ?? null,
            'history_count' => count($history), 'history' => $history];
    }

    private static function model(array $history = [], bool $selected = false): array
    {
        $event = self::event($history);
        $summary = $event;
        $summary['history'] = [];

        return ['schema' => 'CRM_N8N_EXECUTION_MONITOR_V1', 'company_id' => self::COMPANY,
            'generated_at' => self::NOW, 'total_events' => 1,
            'events' => [$summary], 'selected' => $selected ? $event : null];
    }

    private static function json(array $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    public function test_empty_and_unobserved_are_not_synthesized_as_not_staged(): void
    {
        $empty = self::model();
        $empty['events'] = [];
        $empty['total_events'] = 0;
        self::assertSame($empty, ExecutionMonitor::read(self::json($empty), self::COMPANY));

        $unobserved = self::model();
        self::assertSame($unobserved, ExecutionMonitor::read(self::json($unobserved), self::COMPANY));
        self::assertNull($unobserved['events'][0]['observation']);
        $observed = self::model([self::observation(1, 'NOT_STAGED', 'NONE')]);
        self::assertSame('NOT_STAGED', ExecutionMonitor::read(self::json($observed), self::COMPANY)
            ['events'][0]['observation']['execution_state']);
    }

    public static function states(): array
    {
        return [
            ['NOT_STAGED', 'NONE', 'NONE', 0],
            ['NOT_STARTED', 'PENDING', 'NONE', 0],
            ['NOT_STARTED', 'LEASED', 'NONE', 0],
            ['NOT_STARTED', 'DEAD', 'NONE', 0],
            ['STARTED', 'LEASED', 'NONE', 1],
            ['STARTED', 'DEAD', 'NONE', 3],
            ['RESULT', 'LEASED', 'PREPARED', 1],
            ['RESULT', 'DEAD', 'PREPARED', 3],
            ['ACKED', 'ACKED', 'PREPARED', 1],
            ['UNKNOWN', 'LEASED', 'NONE', 1],
            ['UNKNOWN', 'DEAD', 'NONE', 3],
        ];
    }

    #[DataProvider('states')]
    public function test_closed_states_preserve_delivery_and_execution_as_separate_facts(
        string $state, string $queue, string $result, int $attempt): void
    {
        foreach (['pending', 'accepted'] as $delivery) {
            $model = self::model([self::observation(1, $state, $queue, $result, $attempt)], true);
            $model['events'][0]['delivery_state'] = $delivery;
            $model['selected']['delivery_state'] = $delivery;
            self::assertSame($model, ExecutionMonitor::read(self::json($model), self::COMPANY, self::EVENT));
        }
    }

    public function test_history_uses_sequence_even_when_clocks_equal_or_move_backwards(): void
    {
        $history = [
            self::observation(6, 'ACKED', 'ACKED', 'PREPARED', 1),
            self::observation(5, 'RESULT', 'LEASED', 'PREPARED', 1),
            self::observation(4, 'STARTED', 'LEASED', 'NONE', 1),
            self::observation(3, 'NOT_STARTED', 'LEASED'),
            self::observation(2, 'NOT_STARTED', 'PENDING'),
            self::observation(1, 'NOT_STAGED', 'NONE'),
        ];
        $history[0]['observed_at'] = self::NOW - 1;
        $model = self::model($history, true);
        self::assertSame($model, ExecutionMonitor::read(self::json($model), self::COMPANY, self::EVENT));
        self::assertSame([6, 5, 4, 3, 2, 1], array_column($model['selected']['history'], 'sequence'));
    }

    public function test_older_selected_event_may_be_outside_the_bounded_first_page(): void
    {
        $model = self::model();
        $model['total_events'] = 51;
        $model['events'] = [];
        for ($index = 1; $index <= 51; $index++) {
            $event = self::event();
            $event['event_id'] = sprintf('cccccccc-cccc-4ccc-8ccc-%012x', $index);
            $event['occurred_at'] = self::NOW - $index;
            if ($index <= 50) {
                $model['events'][] = $event;
            } else {
                $model['selected'] = $event;
            }
        }
        self::assertSame($model, ExecutionMonitor::read(self::json($model), self::COMPANY,
            $model['selected']['event_id']));
    }

    public function test_missing_or_foreign_selection_is_a_uniform_null_for_the_controller(): void
    {
        $model = self::model();
        self::assertNull(ExecutionMonitor::read(self::json($model), self::COMPANY,
            'ffffffff-ffff-4fff-8fff-ffffffffffff')['selected']);
    }

    public static function malformedModels(): array
    {
        $base = self::model([
            self::observation(2, 'NOT_STARTED', 'PENDING'),
            self::observation(1, 'NOT_STAGED', 'NONE'),
        ], true);
        $cases = [];
        $changes = [
            'unknown schema' => fn (&$m) => $m['schema'] = 'CRM_AGENTS_EXECUTION_VIEW_V1',
            'other company' => fn (&$m) => $m['company_id'] = self::LEAD,
            'hidden root evidence' => fn (&$m) => $m['receipt'] = 'SYNTHETIC_WIRE',
            'missing field' => static function (&$m) { unset($m['generated_at']); },
            'epoch string' => fn (&$m) => $m['generated_at'] = (string) self::NOW,
            'epoch float' => fn (&$m) => $m['generated_at'] = 1.5,
            'negative epoch' => fn (&$m) => $m['generated_at'] = -1,
            'epoch bound' => fn (&$m) => $m['generated_at'] = 4102444801,
            'boolean count' => fn (&$m) => $m['total_events'] = true,
            'incomplete page' => fn (&$m) => $m['total_events'] = 2,
            'object instead of events' => fn (&$m) => $m['events'] = (object) [],
            'null events' => fn (&$m) => $m['events'] = null,
            'list instead of selected object' => fn (&$m) => $m['selected'] = [],
            'selected identity drift' => fn (&$m) => $m['selected']['event_id'] = self::LEAD,
            'selected disappears from list' => fn (&$m) => $m['selected'] = null,
            'selected title drift' => fn (&$m) => $m['selected']['lead_title'] = 'LEAD_TESTE_OTHER',
            'hidden company selector' => fn (&$m) => $m['events'][0]['company_id'] = self::LEAD,
            'hidden event payload' => fn (&$m) => $m['events'][0]['payload'] = 'SYNTHETIC_WIRE',
            'uppercase event' => fn (&$m) => $m['events'][0]['event_id'] = strtoupper(self::EVENT),
            'invalid lead' => fn (&$m) => $m['events'][0]['lead_id'] = 'SYNTHETIC_LEAD',
            'unbounded title' => fn (&$m) => $m['events'][0]['lead_title'] = 'LEAD_TESTE_'.str_repeat('X', 41),
            'HTML title' => fn (&$m) => $m['events'][0]['lead_title'] = '<script>SYNTHETIC</script>',
            'event epoch boolean' => fn (&$m) => $m['events'][0]['occurred_at'] = false,
            'delivery is not execution' => fn (&$m) => $m['events'][0]['delivery_state'] = 'ACKED',
            'history count string' => fn (&$m) => $m['events'][0]['history_count'] = '2',
            'observation missing' => fn (&$m) => $m['events'][0]['observation'] = null,
            'observation without history' => fn (&$m) => $m['events'][0]['history_count'] = 0,
            'projection is not latest' => fn (&$m) => $m['events'][0]['observation']['sequence'] = 1,
            'history leaked into list' => fn (&$m) => $m['events'][0]['history'] = $m['selected']['history'],
            'object instead of list history' => fn (&$m) => $m['events'][0]['history'] = (object) [],
            'observation list' => fn (&$m) => $m['events'][0]['observation'] = [],
            'hidden observation signature' => fn (&$m) => $m['events'][0]['observation']['signature'] = str_repeat('a', 64),
            'unknown state' => fn (&$m) => $m['events'][0]['observation']['execution_state'] = 'COMPLETED',
            'wrong queue' => fn (&$m) => $m['events'][0]['observation']['queue_state'] = 'ACKED',
            'wrong result' => fn (&$m) => $m['events'][0]['observation']['result_code'] = 'PREPARED',
            'attempt boolean' => fn (&$m) => $m['events'][0]['observation']['attempt'] = false,
            'attempt over bound' => fn (&$m) => $m['events'][0]['observation']['attempt'] = 4,
            'observation epoch string' => fn (&$m) => $m['events'][0]['observation']['observed_at'] = '1800000000',
            'short detail history' => fn (&$m) => $m['selected']['history'] = [$m['selected']['history'][0]],
            'unordered detail' => fn (&$m) => $m['selected']['history'] = array_reverse($m['selected']['history']),
            'duplicate sequence' => fn (&$m) => $m['selected']['history'][1]['sequence'] = 2,
            'head drift' => fn (&$m) => $m['selected']['history'][0]['observed_at'] = self::NOW - 1,
            'hidden history evidence' => fn (&$m) => $m['selected']['history'][1]['query'] = 'SYNTHETIC_QUERY',
            'unbounded history' => fn (&$m) => $m['selected']['history'] = array_fill(0, 21, $m['selected']['history'][0]),
            'unbounded page' => static function (&$m) { $m['total_events'] = 51; $m['events'] = array_fill(0, 51, $m['events'][0]); },
            'repeated event' => static function (&$m) { $m['total_events'] = 2; $m['events'][] = $m['events'][0]; },
            'wrong event order' => static function (&$m) {
                $m['total_events'] = 2; $other = self::event();
                $other['event_id'] = self::LEAD; $other['occurred_at'] = self::NOW;
                $m['events'][] = $other;
            },
        ];
        foreach ($changes as $label => $change) {
            $model = $base;
            $change($model);
            $cases[$label] = [self::json($model)];
        }

        return $cases;
    }

    #[DataProvider('malformedModels')]
    public function test_invalid_or_overbroad_sql_results_are_not_rendered(string $raw): void
    {
        $this->expectException(UnexpectedValueException::class);
        ExecutionMonitor::read($raw, self::COMPANY, self::EVENT);
    }

    public static function regressions(): array
    {
        return [
            'UNKNOWN cannot become success' => [self::observation(1, 'UNKNOWN', 'LEASED', 'NONE', 1), self::observation(2, 'ACKED', 'ACKED', 'PREPARED', 1)],
            'result cannot become unknown' => [self::observation(1, 'RESULT', 'LEASED', 'PREPARED', 1), self::observation(2, 'UNKNOWN', 'LEASED', 'NONE', 1)],
            'lease cannot become pending' => [self::observation(1, 'NOT_STARTED', 'LEASED'), self::observation(2, 'NOT_STARTED', 'PENDING')],
            'intent attempt cannot change' => [self::observation(1, 'STARTED', 'LEASED', 'NONE', 1), self::observation(2, 'RESULT', 'LEASED', 'PREPARED', 2)],
            'unchanged fact is not another observation' => [self::observation(1, 'STARTED', 'LEASED', 'NONE', 1), self::observation(2, 'STARTED', 'LEASED', 'NONE', 1)],
        ];
    }

    #[DataProvider('regressions')]
    public function test_inconsistent_history_is_not_presented_as_valid_progress(array $before, array $after): void
    {
        $this->expectException(UnexpectedValueException::class);
        ExecutionMonitor::read(self::json(self::model([$after, $before], true)), self::COMPANY, self::EVENT);
    }

    public function test_duplicate_keys_are_rejected_before_their_values_are_discarded(): void
    {
        $raw = str_replace('"sequence":1', '"sequence":1,"\\u0073equence":1',
            self::json(self::model([self::observation(1, 'NOT_STAGED', 'NONE')])));
        $this->expectException(JsonException::class);
        ExecutionMonitor::read($raw, self::COMPANY);
    }

    public function test_large_results_fail_before_json_allocation(): void
    {
        $this->expectException(JsonException::class);
        ExecutionMonitor::read(str_repeat(' ', 65537), self::COMPANY);
    }

    public function test_an_unsolicited_detail_is_not_exposed(): void
    {
        $this->expectException(UnexpectedValueException::class);
        ExecutionMonitor::read(self::json(self::model([], true)), self::COMPANY);
    }

    public function test_query_only_accepts_the_closed_navigation_parameters(): void
    {
        self::assertNull(ExecutionMonitor::selectedEvent(['section' => 'n8n']));
        foreach (['pt', 'en', 'es'] as $language) {
            self::assertSame(self::EVENT, ExecutionMonitor::selectedEvent([
                'section' => 'n8n', 'lang' => $language, 'event' => self::EVENT]));
        }
    }

    public static function invalidQueries(): array
    {
        return array_map(static fn (array $query): array => [$query], [
            [], ['section' => ['n8n']], ['section' => 'N8N'],
            ['section' => 'n8n', 'company_id' => self::COMPANY],
            ['section' => 'n8n', 'action' => 'reconcile'],
            ['section' => 'n8n', 'event' => null], ['section' => 'n8n', 'event' => []],
            ['section' => 'n8n', 'event' => 1], ['section' => 'n8n', 'event' => ''],
            ['section' => 'n8n', 'event' => strtoupper(self::EVENT)],
            ['section' => 'n8n', 'event' => self::EVENT.' or 1=1'],
            ['section' => 'n8n', 'lang' => []], ['section' => 'n8n', 'lang' => null],
            ['section' => 'n8n', 'lang' => 'de'],
        ]);
    }

    #[DataProvider('invalidQueries')]
    public function test_navigation_cannot_carry_company_authority_or_commands(array $query): void
    {
        $this->expectException(InvalidArgumentException::class);
        ExecutionMonitor::selectedEvent($query);
    }
}
