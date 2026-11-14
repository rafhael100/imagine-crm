<?php
declare(strict_types=1);

namespace App\Core\Leads;

use PDO;
use PDOException;
use Throwable;

/**
 * An isolated PostgreSQL16 metadata-only lab repository.
 * Required DB contains only synthetic fixtures and a restricted FORCE RLS role.
 * This class is NOT a CRM lead repository, operational receipt, auth grant,
 * consent service, campaign launcher or worker.
 *
 * The caller must verify disposable CI environment and supply ONLY a static
 * server-bound synthetic tenant. Never use browser-supplied tenant IDs.
 */
final class SyntheticCalculatorPgDelivery
{
    /** @return array<string,mixed> */
    public static function record(
        PDO $db, array $envelope, string $fixtureKey, string $boundTenant, int $now
    ): array {
        $deny = static fn(string $state): array => [
            'state' => $state,
            'synthetic_pg_persisted' => false,
            'real_crm_receipt_proven' => false,
            'consent_proven' => false,
            'tenant_authorized' => false,
            'can_store_pii' => false,
            'can_send_report' => false,
            'can_send_marketing' => false,
            'can_queue_jobs' => false,
        ];
        if ($boundTenant !== 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa') {
            return $deny('SYNTHETIC_TENANT_BINDING_DENIED');
        }
        $assessment=SyntheticCalculatorInterestInteropInspector::inspect(
            $envelope,$fixtureKey,$now);
        if ($assessment['synthetic_mac_matches'] !== true) {
            return $deny('SYNTHETIC_MAC_DENIED');
        }
        $payload = $envelope['payload'];
        $digest=hash('sha256',
            SyntheticCalculatorInterestInteropInspector::signingBytes($payload));
        try {
            $db->beginTransaction();
            // Scope marker is NOT authentication; role and tenant are both
            // controlled by CI, never by an HTTP user.
            $db->prepare("SELECT set_config('app.tenant_id',?,true)")->execute([$boundTenant]);
            $insert=$db->prepare(
                'INSERT INTO public.crm_calc_lab_intents
                   (organization_id,submission_id,signed_nonce,payload_sha256,
                    media_total_cents,report_requested,marketing_requested)
                 VALUES (?::uuid,?::uuid,?::uuid,?,?,?,?)
                 ON CONFLICT (organization_id,submission_id) DO NOTHING
                 RETURNING submission_id'
            );
            $insert->execute([
                $boundTenant,$payload['submission_id'],$payload['nonce'],$digest,
                $payload['media_total_cents'],
                $payload['report_requested']?'true':'false',
                $payload['marketing_requested']?'true':'false',
            ]);
            if (is_array($insert->fetch(PDO::FETCH_ASSOC))) {
                foreach (['report_requested','marketing_requested'] as $kind) {
                    if (!$payload[$kind]) continue;
                    $eventId=self::syntheticEventId(
                        $boundTenant,$payload['submission_id'],$kind);
                    $db->prepare(
                        'INSERT INTO public.crm_calc_lab_consent_events
                          (organization_id,submission_id,event_id,intent_kind)
                         VALUES (?::uuid,?::uuid,?::uuid,?)'
                    )->execute([$boundTenant,$payload['submission_id'],$eventId,$kind]);
                }
                $db->commit();
                return array_replace($deny('SYNTHETIC_PG_INTENT_RECORDED'),[
                    'synthetic_pg_persisted'=>true
                ]);
            }
            // Repeat is only acknowledged if every immutable claim matches.
            $read=$db->prepare(
                'SELECT signed_nonce,payload_sha256,media_total_cents,
                    report_requested,marketing_requested
                 FROM public.crm_calc_lab_intents
                 WHERE organization_id=?::uuid AND submission_id=?::uuid'
            );
            $read->execute([$boundTenant,$payload['submission_id']]);
            $old=$read->fetch(PDO::FETCH_ASSOC);
            $same=is_array($old) &&
                $old['signed_nonce']===$payload['nonce'] &&
                $old['payload_sha256']===$digest &&
                (int)$old['media_total_cents']===$payload['media_total_cents'] &&
                self::pgBool($old['report_requested'])===$payload['report_requested'] &&
                self::pgBool($old['marketing_requested'])===$payload['marketing_requested'];
            $db->commit();
            return $same
                ? array_replace($deny('SYNTHETIC_REPLAY_CONFIRMED'),[
                    'synthetic_pg_persisted'=>true
                  ])
                : $deny('SYNTHETIC_CONFLICT_DENIED');
        } catch (PDOException) {
            if($db->inTransaction())$db->rollBack();
            return $deny('SYNTHETIC_SQL_CONFLICT_DENIED');
        } catch (Throwable) {
            if($db->inTransaction())$db->rollBack();
            return $deny('SYNTHETIC_UNAVAILABLE');
        }
    }

    private static function pgBool(mixed $value): ?bool
    {
        if(in_array($value,[true,'t','true','1'],true))return true;
        if(in_array($value,[false,'f','false','0'],true))return false;
        return null;
    }

    private static function syntheticEventId(string $tenant,string $submission,string $kind): string
    {
        $hex=hash('sha256',$tenant.'|'.$submission.'|'.$kind);
        return substr($hex,0,8).'-'.substr($hex,8,4).'-4'.
            substr($hex,13,3).'-8'.substr($hex,17,3).'-'.substr($hex,20,12);
    }
}
