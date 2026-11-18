<?php

declare(strict_types=1);

namespace App\Core\Automation;

use InvalidArgumentException;

/** Immutable native CRM templates. No script, URL, SQL, provider or n8n graph accepted. */
final readonly class RuleDefinition
{
    public const TEMPLATES = [
        'lead_distribution' => ['trigger' => 'lead.created', 'action' => 'lead.assignment.request'],
        'followup_qualified' => ['trigger' => 'lead.stage.changed', 'action' => 'task.followup.create'],
        'followup_proposal' => ['trigger' => 'lead.stage.changed', 'action' => 'task.followup.create'],
        'overdue_notice' => ['trigger' => 'task.overdue', 'action' => 'notification.internal.create'],
    ];

    private function __construct(
        public string $tenant, public string $id, public int $version,
        public string $template, public int $delaySeconds, public string $recipient,
    ) {}

    public static function fromArray(array $value): self
    {
        $keys = array_keys($value); sort($keys);
        if ($keys !== ['delay_seconds', 'id', 'recipient', 'template', 'tenant', 'version']
            || !is_int($value['version']) || $value['version'] < 1 || $value['version'] > 1000000
            || !is_int($value['delay_seconds']) || $value['delay_seconds'] < 0 || $value['delay_seconds'] > 604800) {
            throw new InvalidArgumentException('INVALID_RULE');
        }
        foreach (['tenant', 'id', 'recipient'] as $key) {
            if (!is_string($value[$key]) || preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $value[$key]) !== 1) {
                throw new InvalidArgumentException('INVALID_REFERENCE');
            }
        }
        if (!is_string($value['template']) || !isset(self::TEMPLATES[$value['template']])) {
            throw new InvalidArgumentException('TEMPLATE_NOT_ALLOWED');
        }
        return new self($value['tenant'], $value['id'], $value['version'], $value['template'], $value['delay_seconds'], $value['recipient']);
    }

    /** Backend must independently verify actor, recipient membership and entitlement. */
    public function plan(string $trustedTenant, array $event, int $now): ?array
    {
        $keys = array_keys($event); sort($keys);
        if ($keys !== ['id', 'occurred_at', 'resource', 'stage', 'tenant', 'trigger']
            || $trustedTenant !== $this->tenant || ($event['tenant'] ?? null) !== $trustedTenant
            || !is_int($event['occurred_at']) || $event['occurred_at'] > $now
            || $event['occurred_at'] < $now - 86400 || $now < 0 || $now > 4102444800) {
            throw new InvalidArgumentException('INVALID_EVENT_SCOPE_OR_TIME');
        }
        foreach (['id', 'resource', 'tenant'] as $key) {
            if (!is_string($event[$key]) || preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $event[$key]) !== 1) {
                throw new InvalidArgumentException('INVALID_EVENT_REFERENCE');
            }
        }
        if (!is_string($event['trigger']) || !is_string($event['stage'])
            || !in_array($event['stage'], ['', 'new', 'qualified', 'proposal', 'won', 'lost'], true)) {
            throw new InvalidArgumentException('INVALID_EVENT');
        }
        $template = self::TEMPLATES[$this->template];
        if ($event['trigger'] !== $template['trigger']) return null;
        if (str_starts_with($this->template, 'followup_') && $event['stage'] !== substr($this->template, 9)) return null;
        return [
            'contract' => 'CRM_NATIVE_COMMAND_V1', 'tenant' => $this->tenant,
            'rule' => $this->id, 'version' => $this->version, 'event' => $event['id'],
            'resource' => $event['resource'], 'action' => $template['action'],
            'recipient' => $this->recipient, 'due_at' => $now + $this->delaySeconds,
            'idempotency_key' => hash('sha256', json_encode([$this->tenant, $this->id, $this->version, $event['id']], JSON_THROW_ON_ERROR)),
            'ai_calls' => 0,
        ];
    }

    public function json(): string
    {
        return json_encode(['tenant' => $this->tenant, 'id' => $this->id, 'version' => $this->version,
            'template' => $this->template, 'delay_seconds' => $this->delaySeconds, 'recipient' => $this->recipient], JSON_THROW_ON_ERROR);
    }
}
