<?php

declare(strict_types=1);

namespace App\Core\Commercial;

use Illuminate\Database\Connection;
use LogicException;
use RuntimeException;

/**
 * CRM-STG-DATA-01: synthetic, read-only SQL snapshot inside the private
 * staging profile ONLY. NOT an authenticated business read nor an API.
 *
 * No request value selects an organization. This fixed, server-owned
 * UUID addresses entirely fake data in two CI-only FORCE-RLS tables.
 */
final class StagingPersistedDemoPreview
{
    private const FIXTURE_TENANT = 'a1111111-1111-4111-8111-111111111111';
    private const STAGES = ['new', 'qualified', 'proposal', 'won'];

    /** @return array<string,mixed> */
    public static function presentation(Connection $db, mixed $language): array
    {
        if (getenv('CRM_STAGING_PERSISTED_DEMO') !== 'true'
            || !app()->environment('staging')
            || $db->getDriverName() !== 'pgsql'
            || $db->getDatabaseName() !== 'imagine_crm_staging'
            || $db->transactionLevel() !== 0) {
            throw new LogicException('Synthetic staging SQL demo unavailable.');
        }

        return $db->transaction(static function () use ($db, $language): array {
            // Enforce SELECT-only transaction on the actual non-bypass runtime
            // connection. A query string, cookie or header cannot change this.
            $db->statement('SET TRANSACTION READ ONLY');
            $security = $db->selectOne(<<<'SQL'
SELECT current_user AS runtime_role, current_setting('app.tenant_id', true) AS inherited_scope,
  current_setting('row_security') AS row_security,
  CASE WHEN
    r.rolsuper OR r.rolbypassrls
    OR pg_has_role(current_user, a.relowner, 'MEMBER')
    OR pg_has_role(current_user, t.relowner, 'MEMBER')
    OR NOT a.relrowsecurity OR NOT a.relforcerowsecurity
    OR NOT t.relrowsecurity OR NOT t.relforcerowsecurity
    OR has_table_privilege(current_user, 'public.crm_ci_demo_opportunities', 'UPDATE')
    OR has_table_privilege(current_user, 'public.crm_ci_demo_opportunities', 'DELETE')
    OR has_table_privilege(current_user, 'public.crm_ci_demo_tasks', 'UPDATE')
    OR has_table_privilege(current_user, 'public.crm_ci_demo_tasks', 'DELETE')
    THEN 'unsafe' ELSE 'safe' END AS safety
FROM pg_roles AS r CROSS JOIN pg_class AS a CROSS JOIN pg_class AS t
WHERE r.rolname = current_user
  AND a.oid = 'public.crm_ci_demo_opportunities'::regclass
  AND t.oid = 'public.crm_ci_demo_tasks'::regclass
SQL);

            if (!$security || $security->runtime_role !== 'crm_staging_app'
                || ($security->inherited_scope !== null && $security->inherited_scope !== '')
                || $security->row_security !== 'on'
                || $security->safety !== 'safe') {
                throw new LogicException('Unsafe synthetic demo SQL role/scope.');
            }

            $db->selectOne("SELECT set_config('app.tenant_id', ?, true)", [self::FIXTURE_TENANT]);

            $opportunities = $db->table('public.crm_ci_demo_opportunities')
                ->where('organization_id', self::FIXTURE_TENANT)
                ->orderBy('reference')
                ->get(['reference', 'label', 'detail', 'stage']);
            $tasks = $db->table('public.crm_ci_demo_tasks')
                ->where('organization_id', self::FIXTURE_TENANT)
                ->where('state', 'todo')
                ->orderBy('reference')
                ->get(['reference', 'subject', 'due_day']);

            if ($opportunities->count() !== 6 || $tasks->count() !== 3) {
                // Fail closed if a fixture is incomplete; no fallback to an
                // unrelated tenant, live ledger, or browser-supplied values.
                throw new RuntimeException('Incomplete synthetic demo fixture.');
            }

            $base = StagingDemoPreview::presentation($language);
            $board = array_fill_keys(self::STAGES, []);
            foreach ($opportunities as $lead) {
                if (!array_key_exists($lead->stage, $board)
                    || preg_match('/\ADEMO_[0-9]{3}\z/', $lead->reference) !== 1
                    || preg_match('/\AEXEMPLO_[A-Z]+\z/', $lead->label) !== 1
                    || preg_match('/\AREGISTRO_FICTICIO_[A-Z]+\z/', $lead->detail) !== 1) {
                    throw new RuntimeException('Invalid synthetic opportunity.');
                }
                $board[$lead->stage][] = [
                    'reference' => $lead->reference,
                    'label' => $lead->label,
                    'detail' => $lead->detail,
                ];
            }

            $pending = [];
            foreach ($tasks as $task) {
                if (preg_match('/\ATASK_DEMO_[0-9]{3}\z/', $task->reference) !== 1
                    || preg_match('/\AFOLLOW_UP_DEMO_[A-Z]+\z/', $task->subject) !== 1) {
                    throw new RuntimeException('Invalid synthetic task.');
                }
                $pending[] = [
                    'reference' => $task->reference,
                    'subject' => $task->subject,
                    'day' => date('d/m', strtotime((string) $task->due_day)),
                ];
            }

            $base['pipeline'] = $board;
            $base['tasks'] = $pending;
            $base['opportunity_count'] = $opportunities->count();
            $base['task_count'] = count($pending);
            $base['is_persisted'] = true;
            $base['demo_source'] = 'postgres16_ci_fixture';

            return $base;
        }, 1);
    }
}
