<?php

declare(strict_types=1);

namespace App\Support\Agents;

use App\Core\Tenancy\TenantAccessBoundary;
use App\Support\Synthetic\AiaReadOnlyMockBoundary;
use Illuminate\Support\Str;
use LogicException;

/**
 * P0 OP-01: the first *real CRM authorization seam* without operational ALLOW.
 *
 * The authorized subject is never taken from a request or Agents claims;
 * callers must eventually bind principal/tenant through the trusted CRM
 * backend. Until contract + IAM review, ALL outcomes are uniform denial.
 *
 * Nothing here is routed, registered as a service, or allowed to read leads.
 */
final readonly class Op01GatedBackendAdapter
{
    public function __construct(private TenantAccessBoundary $boundary)
    {
    }

    /**
     * The intent comes from the CRM backend's own policy composer, NOT from
     * untrusted user/Agents inputs. Even a policy success cannot read data.
     *
     * @param array<string,mixed> $serverBuiltIntent
     * @return array{contract:string,state:string,projection:null,real_authorization:false,external_calls:0}
     */
    public function inspect(
        string $serverResolvedTenantId,
        string $serverResolvedPrincipalId,
        array $serverBuiltIntent
    ): array {
        $denied = AiaReadOnlyMockBoundary::inspectCanonicalJson('');

        // A configured flag is never proof of a peer, membership, ACL, or
        // business approval. Never enable review probe outside disposable CI.
        if (config('aia_agents.op01_review_probe_enabled') !== true
            || config('aia_agents.op01_business_read_enabled') !== false
            || config('aia_agents.op01_service_ingress_enabled') !== false
            || !app()->environment('testing')
            || getenv('GITHUB_ACTIONS') !== 'true'
            || getenv('GITHUB_REPOSITORY') !== 'rafhael100/imagine-crm'
            || !Str::isUuid($serverResolvedTenantId)
            || !Str::isUuid($serverResolvedPrincipalId)
            || count($serverBuiltIntent) > 16) {
            return $denied;
        }

        try {
            // Existing CRM identity/session + locked membership + authorizer
            // remain the only source of any tentative decision. A synthetic
            // description never grants read permission.
            $this->boundary->executeContract(
                strtolower($serverResolvedTenantId),
                $serverBuiltIntent,
                static function (): never {
                    // Defense in depth if the IAM authorizer changes before
                    // OP-01 business policy is reviewed: NO business effects.
                    throw new LogicException('OP01_READ_NOT_APPROVED');
                },
                strtolower($serverResolvedPrincipalId),
            );
        } catch (\Throwable) {
            // All failures including auth, DB outage and invalid descriptor
            // are externally indistinguishable; never echo identifiers.
        }

        return $denied;
    }
}
