<?php

declare(strict_types=1);

namespace App\Core\Async;

/** CORE03 semantic classes, not credentials or permissions. */
enum ExecutionClass: string
{
    case DeferredHuman = 'A';
    case MandatoryProcess = 'B';
    case NativeAutomation = 'C';
    case ExternalIngress = 'D';
    case TechnicalOperation = 'E';

    public function origin(): string
    {
        return match ($this) {
            self::DeferredHuman => 'human_request', self::MandatoryProcess => 'module_event',
            self::NativeAutomation => 'native_automation', self::ExternalIngress => 'external_ingress',
            self::TechnicalOperation => 'technical_request',
        };
    }

    public function dependencies(): array
    {
        $specific = match ($this) {
            self::DeferredHuman => ['CURRENT_HUMAN_SESSION_MEMBERSHIP', 'DEFERRED_DELEGATION'],
            self::MandatoryProcess => ['OFFICIAL_MODULE_EVENT', 'AUTONOMOUS_SERVICE_AUTHORITY'],
            self::NativeAutomation => ['PUBLISHED_NATIVE_DEFINITION', 'CURRENT_ENTITLEMENT_CONSENT'],
            self::ExternalIngress => ['AUTHENTICATED_EXTERNAL_ORIGIN', 'AUTHORIZED_CRM_DESTINATION'],
            self::TechnicalOperation => ['VERSIONED_PLAYBOOK', 'RISK_AUTHORITY_SUPPORT_MANDATE'],
        };
        return [...$specific, 'VERIFIED_EXECUTOR_SCOPE', 'OWNER_POLICY_CONFIRMATION', 'CORE_SEG_ACCEPTANCE'];
    }
}
