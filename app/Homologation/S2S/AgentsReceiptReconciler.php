<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use PDO;
use RuntimeException;
use Throwable;

/** Explicit, CI-only receipt lookup. It never restages or resends an event. */
final class AgentsReceiptReconciler
{
    public function reconcile(string $company, string $event): void
    {
        if (!app()->environment('staging')
            || getenv('CRM_AGENTS_S2S_PROFILE') !== 'synthetic-private-v1'
            || getenv('CRM_AGENTS_RECEIPT_PROFILE') !== 'synthetic-private-v1') {
            throw new RuntimeException('RECEIPT_RECONCILER_DISABLED');
        }
        $db = new PDO(getenv('CRM_AUTO_S2S_DSN'), 'crm_auto_sender',
            trim(file_get_contents('/certs/sender.password')),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
        $db->exec("SET statement_timeout='2000ms'; SET lock_timeout='1000ms'; SET idle_in_transaction_session_timeout='5000ms'");
        $db->beginTransaction();
        try {
            // No INSERT: retain the original payload, nonce, request and delivery window.
            // The source grant and outbox locks span lookup, verification and commit.
            $candidate = $db->prepare('SELECT crm_auto_service.receipt_candidate(CAST(? AS uuid),CAST(? AS uuid))');
            $candidate->execute([$company, $event]);
            $row = json_decode($candidate->fetchColumn(), true, 12, JSON_THROW_ON_ERROR);
            if ($row['state'] === 'accepted') {
                $db->commit();
                return;
            }
            $ingressKey = @file_get_contents('/certs/agents-signing.key');
            $receiptKey = @file_get_contents('/certs/agents-receipt.key');
            if (!is_string($ingressKey) || !is_string($receiptKey)
                || hash_equals($ingressKey, $receiptKey)) {
                throw new RuntimeException('RECEIPT_KEY_UNAVAILABLE');
            }
            $query = AgentsReceipt::query($company, $row['tenant_ref'],
                'SYNTHETIC_' . strtoupper(str_replace('-', '', $event)),
                hash('sha256', $row['payload']), $ingressKey, time(),
                'SYNTHETIC_' . strtoupper(bin2hex(random_bytes(16))),
                'SYNTHETIC_' . strtoupper(bin2hex(random_bytes(16))));
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => true, 'verify_peer_name' => true,
                    'allow_self_signed' => false, 'peer_name' => 'agents-s2s',
                    'cafile' => '/certs/ca.crt', 'local_cert' => '/certs/crm.crt',
                    'local_pk' => '/certs/crm.key',
                ],
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nConnection: close\r\n",
                    'content' => json_encode($query, JSON_THROW_ON_ERROR),
                    'timeout' => 3, 'follow_location' => 0, 'max_redirects' => 0,
                    'ignore_errors' => true,
                ],
            ]);
            $raw = @file_get_contents('https://agents-s2s:8443/private/events/receipt',
                false, $context, 0, 4097);
            if (!is_string($raw) || strlen($raw) > 4096
                || !preg_match('/\AHTTP\/\S+ 200(?: |$)/', $http_response_header[0] ?? '')) {
                throw new RuntimeException('RECEIPT_TRANSPORT_DENIED');
            }
            $receiptHash = AgentsReceipt::validate($raw, $query, $receiptKey, time());
            $acknowledge = $db->prepare('SELECT crm_auto_service.ack_outbound(CAST(? AS uuid),CAST(? AS uuid),?)');
            $acknowledge->execute([$company, $event, $receiptHash]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }
}
