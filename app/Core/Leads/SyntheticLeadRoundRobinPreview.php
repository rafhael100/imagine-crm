<?php

declare(strict_types=1);

namespace App\Core\Leads;

use InvalidArgumentException;

/**
 * VEN-R32 / VEN-T16/T17: fair, capacity-limited preview of fictional lead
 * distribution. Does NOT use lead IDs or actual user/tenant identifiers.
 *
 * "Assigned", "engaged" or "closed" sample leads are not redistributed.
 * Nothing here locks records, checks tenancy, grants work or persists an
 * assignment. Local slot indices are NOT real staff identifiers.
 */
final class SyntheticLeadRoundRobinPreview
{
    private const MAX_AGENTS = 8;
    private const MAX_LEADS = 20;
    private const LEAD_STATES = ['unassigned', 'assigned', 'engaged', 'closed'];

    /**
     * @param mixed $agents list<array{
     *   enabled:bool,has_required_skill:bool,open_leads:int,capacity:int
     * }>
     * @param mixed $leadStates list<'unassigned'|'assigned'|'engaged'|'closed'>
     * @param mixed $startCursor int local slot index
     * @return array<string,mixed> Anonymous, deny-only projection
     */
    public function preview(
        mixed $agents,
        mixed $leadStates,
        mixed $startCursor = 0,
    ): array {
        if (!is_array($agents)
            || !array_is_list($agents)
            || count($agents) > self::MAX_AGENTS
            || !is_array($leadStates)
            || !array_is_list($leadStates)
            || count($leadStates) > self::MAX_LEADS
            || !is_int($startCursor)
            || $startCursor < 0
            || (count($agents) === 0
                ? $startCursor !== 0
                : $startCursor >= count($agents))) {
            self::invalid();
        }

        $loads = [];
        foreach ($agents as $agent) {
            if (!is_array($agent) || count($agent) !== 4) {
                self::invalid();
            }
            $keys = array_keys($agent);
            sort($keys);
            if ($keys !== ['capacity', 'enabled', 'has_required_skill', 'open_leads']
                || !is_bool($agent['enabled'])
                || !is_bool($agent['has_required_skill'])
                || !is_int($agent['open_leads'])
                || !is_int($agent['capacity'])
                || $agent['capacity'] < 0
                || $agent['capacity'] > self::MAX_LEADS
                || $agent['open_leads'] < 0
                || $agent['open_leads'] > $agent['capacity']) {
                self::invalid();
            }
            $loads[] = $agent['open_leads'];
        }
        foreach ($leadStates as $state) {
            if (!is_string($state)
                || !in_array($state, self::LEAD_STATES, true)) {
                self::invalid();
            }
        }

        $slots = [];
        $planned = 0;
        $skipped = 0;
        $pending = 0;
        $cursor = $startCursor;
        $agentCount = count($agents);

        foreach ($leadStates as $state) {
            if ($state !== 'unassigned') {
                // No rematching for a lead already owned or engaged.
                $slots[] = null;
                ++$skipped;
                continue;
            }

            $chosen = null;
            for ($offset = 0; $offset < $agentCount; ++$offset) {
                $candidate = ($cursor + $offset) % $agentCount;
                $agent = $agents[$candidate];
                if ($agent['enabled']
                    && $agent['has_required_skill']
                    && $loads[$candidate] < $agent['capacity']) {
                    $chosen = $candidate;
                    break;
                }
            }

            $slots[] = $chosen;
            if ($chosen === null) {
                ++$pending;
                continue;
            }

            ++$planned;
            ++$loads[$chosen];
            $cursor = ($chosen + 1) % $agentCount;
        }

        return [
            'agent_count' => $agentCount,
            'lead_count' => count($leadStates),
            'assignment_slots' => $slots,
            'planned_assignments' => $planned,
            'already_owned_or_handled' => $skipped,
            'unassigned_due_to_capacity' => $pending,
            'projected_loads' => $loads,
            'next_cursor' => $cursor,
            'assignment_policy_verified' => false,
            'staff_identity_verified' => false,
            'tenant_verified' => false,
            'concurrency_verified' => false,
            'lead_state_verified' => false,
            'informational_only' => true,
            'authorized' => false,
            'can_assign' => false,
            'can_redistribute' => false,
            'can_notify' => false,
            'can_persist' => false,
        ];
    }

    private static function invalid(): never
    {
        throw new InvalidArgumentException('Distribuicao sintetica de leads invalida.');
    }
}
