<?php

declare(strict_types=1);

namespace App\Core\Portability;

use InvalidArgumentException;

/**
 * F3-86: technical CSV of invented F01..F12 Kanban fixture records only.
 *
 * Not a customer export, CRM schema, backup, import endpoint or write grant.
 * No filesystem, database, network, session, logs, PII or side effects.
 */
final class SyntheticKanbanCsvPreviewBuilder
{
    private const HEADER = "id;etapa;prioridade;acompanhamento;valor_ficticio;pontuacao\n";
    private const KEYS = ['id', 'stage', 'priority', 'followup', 'value', 'score'];
    private const MAX_EXACT_JS_INTEGER = 9007199254740991;

    public function render(mixed $records): string
    {
        if (!is_array($records) || !array_is_list($records) || count($records) > 12) {
            self::invalid();
        }

        $seen = [];
        $rows = [];
        $totalValue = 0;

        // All rows are validated before ANY serialized content is returned.
        foreach ($records as $record) {
            if (!is_array($record) || count($record) !== count(self::KEYS)) {
                self::invalid();
            }
            foreach (self::KEYS as $key) {
                if (!array_key_exists($key, $record)) {
                    self::invalid();
                }
            }

            if (!is_string($record['id'])
                || preg_match('/\AF(?:0[1-9]|1[0-2])\z/D', $record['id']) !== 1
                || isset($seen[$record['id']])
                || !is_int($record['stage']) || $record['stage'] < 0 || $record['stage'] > 3
                || !in_array($record['priority'], ['high', 'normal'], true)
                || !in_array($record['followup'], ['pending', 'done'], true)
                || !is_int($record['value']) || $record['value'] < 0 || $record['value'] > self::MAX_EXACT_JS_INTEGER
                || !is_int($record['score']) || $record['score'] < 0 || $record['score'] > 100) {
                self::invalid();
            }

            // The browser displays an aggregate fictional value; individual
            // integers may be safe while their sum is no longer exact in JS.
            if ($record['value'] > self::MAX_EXACT_JS_INTEGER - $totalValue) {
                self::invalid();
            }
            $totalValue += $record['value'];

            $seen[$record['id']] = true;
            $rows[] = implode(';', [
                $record['id'],
                $record['stage'],
                $record['priority'],
                $record['followup'],
                $record['value'],
                $record['score'],
            ]);
        }

        // The fixed header and strictly enumerated technical cells contain
        // no formula prefixes, separators, newlines, quotes or user labels.
        return self::HEADER.($rows === [] ? '' : implode("\n", $rows)."\n");
    }

    public function canExportContacts(): bool
    {
        return false;
    }

    public function canAuthorizeImport(): bool
    {
        return false;
    }

    public function canPersistLead(): bool
    {
        return false;
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Previa CSV ficticia invalida.');
    }
}
