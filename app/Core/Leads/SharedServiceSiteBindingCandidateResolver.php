<?php
declare(strict_types=1);

namespace App\Core\Leads;

use PDO;
use Throwable;

/**
 * MULTITENANT candidate: a trusted transport principal and an UNTRUSTED site
 * selector resolve an authorized binding from a server-owned PostgreSQL table.
 * The returned binding MUST then be checked against the SIGNED frame.
 * The selector is never itself an authorization claim or a tenant identity.
 *
 * Dormant in CRM Core; no route, shared credential, lead write, or GO decision.
 */
final class SharedServiceSiteBindingCandidateResolver
{
    private const UUID='/\A[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\z/D';
    private const BINDING=[
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference'
    ];

    public function resolve(PDO $db,string $transportPrincipalId,string $candidateSiteId,int $now):?array
    {
        if($now<=0||!self::uuid($transportPrincipalId)||!self::uuid($candidateSiteId)){
            return null;
        }
        try {
            $stmt=$db->prepare(
                'SELECT organization_reference::text AS organization_reference,
                        binding_reference::text AS binding_reference,
                        site_reference::text AS site_reference,
                        form_reference::text AS form_reference,
                        destination_reference::text AS destination_reference
                   FROM public.crm_service_site_bindings
                  WHERE principal_id=CAST(:principal AS uuid)
                    AND site_reference=CAST(:site AS uuid)
                    AND state=\'active\'
                    AND expires_at_epoch>:now
                  LIMIT 2'
            );
            $stmt->execute([
                'principal'=>$transportPrincipalId,
                'site'=>$candidateSiteId,
                'now'=>$now
            ]);
            $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(Throwable){
            return null;
        }
        if(count($rows)!==1||array_keys($rows[0])!==self::BINDING)return null;
        foreach(self::BINDING as $field){
            if(!self::uuid($rows[0][$field]))return null;
        }
        if(!hash_equals($candidateSiteId,$rows[0]['site_reference']))return null;
        // Internal candidate only. HMAC, nonce and principal verification are separate.
        return $rows[0];
    }

    private static function uuid(mixed $v):bool
    {
        return is_string($v)
            &&preg_match(self::UUID,$v)===1
            &&$v!=='00000000-0000-0000-0000-000000000000';
    }
}
