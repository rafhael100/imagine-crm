<?php
declare(strict_types=1);

/**
 * Synthetic Node -> PHP cross-repository parity check.
 * Reads ONLY one disposable JSON vector from stdin. Never stores PII or sends
 * requests and never authorizes CRM lead capture or marketing.
 */
require_once __DIR__.'/../../app/Core/Leads/SyntheticCalculatorInterestInteropInspector.php';

use App\Core\Leads\SyntheticCalculatorInterestInteropInspector as Inspector;

$raw = stream_get_contents(STDIN, 4097);
if (!is_string($raw) || strlen($raw) < 50 || strlen($raw) > 4096 ||
    preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $raw)) {
    throw new RuntimeException('Invalid synthetic interop fixture stream.');
}
$envelope = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
if (!is_array($envelope)) {
    throw new RuntimeException('Missing calculator fixture envelope.');
}
$key = 'IMAGINE_CALCULATOR_CI_ONLY_KEY_2026_NOT_FOR_PRODUCTION_ABC123';
$expectedSignature='44f3c4d8c6f49830caf6c66a9c295ffa207e67801b3c7c7cabb337518dde7816';
if (($envelope['signature'] ?? null) !== $expectedSignature) {
    throw new RuntimeException('Commercial source vector diverged from audited SHA.');
}
$assessment=Inspector::inspect($envelope,$key,1794672030);
if ($assessment['state'] !== 'SYNTHETIC_MAC_MATCH_UNTRUSTED' ||
    $assessment['synthetic_mac_matches'] !== true ||
    $assessment['consent_proven'] !== false ||
    $assessment['tenant_authorized'] !== false ||
    $assessment['crm_durable_receipt_proven'] !== false ||
    $assessment['can_send_report'] !== false ||
    $assessment['can_send_marketing'] !== false ||
    $assessment['can_store_lead'] !== false) {
    throw new RuntimeException('Synthetic interop denied-status mismatch.');
}

// Replay simulation with no nonce ledger does not authorize action; the token
// remains mathematically verifiable, demonstrating why storage is gated.
$replay=Inspector::inspect($envelope,$key,1794672031);
if ($replay['synthetic_mac_matches'] !== true ||
    $replay['can_store_lead'] !== false) {
    throw new RuntimeException('Replay cannot become authorized lead.');
}

$tampered=$envelope;
$tampered['payload']['marketing_requested']=true;
$no=Inspector::inspect($tampered,$key,1794672030);
if ($no['synthetic_mac_matches'] !== false ||
    $no['can_send_marketing'] !== false) {
    throw new RuntimeException('Payload tampering or marketing elevation.');
}
echo "PASS: Commercial Node signed fixture -> CRM PHP verified; no PII, durable receipt, leads, email or marketing\n";
