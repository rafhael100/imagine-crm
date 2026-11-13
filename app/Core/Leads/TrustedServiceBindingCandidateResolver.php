<?php
declare(strict_types=1);

namespace App\Core\Leads;

use PDO;
use Throwable;

/**
 * Candidate read-only resolver for a service identity established OUTSIDE HTTP payload.
 * The PDO role, transport principal, migration, tenant authorization and secrets must
 * be independently authenticated/reviewed before any operational deployment.
 * No Laravel route, lead write, customer ACK or GO decision is implemented here.
 */
final class TrustedServiceBindingCandidateResolver
{
    private const UUID='/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/D';
    private const BINDING=[
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference'
    ];

    public function resolve(PDO $db, string $transportPrincipalId, int $now): ?array
    {
        if ($now<=0 || preg_match(self::UUID,$transportPrincipalId)!==1
            || $transportPrincipalId==='00000000-0000-0000-0000-000000000000') {
            return null;
        }

        try {
            // Deliberately no tenant/destination values taken from the HTTP envelope.
            // RLS + non-superuser DB role form an independent additional restriction.
            $query=$db->prepare(
                'SELECT organization_reference::text AS organization_reference,
                        binding_reference::text AS binding_reference,
                        site_reference::text AS site_reference,
                        form_reference::text AS form_reference,
                        destination_reference::text AS destination_reference
                 FROM public.crm_service_bindings
                 WHERE principal_id=CAST(:principal AS uuid)
                   AND state=\'active\'
                   AND expires_at_epoch>:now
                 LIMIT 2'
            );
            $query->execute(['principal'=>$transportPrincipalId,'now'=>$now]);
            $rows=$query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable) {
            // Do not surface connection, SQL, role, tenant identifiers or credentials.
            return null;
        }
        if (count($rows)!==1 || array_keys($rows[0])!==self::BINDING) {
            return null;
        }
        foreach (self::BINDING as $field) {
            if (!is_string($rows[0][$field])
                || preg_match(self::UUID,$rows[0][$field])!==1
                || $rows[0][$field]==='00000000-0000-0000-0000-000000000000') {
                return null;
            }
        }
        // Internal binding only; MUST NOT be emitted to clients or used as GO.
        return $rows[0];
    }
}
