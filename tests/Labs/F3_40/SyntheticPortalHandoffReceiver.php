<?php

declare(strict_types=1);

namespace Tests\Labs\F3_40;

use App\Core\Leads\LeadSubmissionIdentity;
use App\Core\Leads\SyntheticIdempotencyClaim;
use App\Core\Leads\SyntheticLeadEnvelopeV1Inspector;
use PDO;
use Throwable;

require_once __DIR__.'/../../../app/Core/Leads/LeadSubmissionIdentity.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticIdempotencyClaim.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadEnvelopeV1Assessment.php';
require_once __DIR__.'/../../../app/Core/Leads/SyntheticLeadEnvelopeV1Inspector.php';

/**
 * F3-40: test-only CRM receiver contract exercised against a temporary PG16 DB.
 *
 * NO Laravel route or service, real tenant binding, payload, consent, API,
 * worker, durable CRM proof, KMS or credentials. A fixture-held HMAC key
 * cannot authenticate real sources. Never wire this into production.
 */
final class SyntheticPortalHandoffReceiver
{
    private const REQUEST_DOMAIN = 'imagine.portal.synthetic-service-request.v1';
    private const LOOKUP_DOMAIN = 'imagine.portal.synthetic-crm-lookup.v1';
    private const RECEIPT_DOMAIN = 'imagine.lead-intake.synthetic-receipt.v1';
    private const REFERENCES = [
        'organization_reference','binding_reference','submission_reference',
        'site_reference','form_reference','destination_reference'
    ];
    private const BINDING = [
        'organization_reference','binding_reference','site_reference',
        'form_reference','destination_reference'
    ];
    private const UUID = '/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/D';
    private const NULL_UUID = '00000000-0000-0000-0000-000000000000';

    /** @param array<string,mixed> $serverFixture */
    public function __construct(
        private readonly array $serverFixture,
        private readonly string $requestTestKey,
        private readonly string $receiptTestKey,
    ) {}

    /** @param array<string,mixed> $envelope @param array<string,mixed> $auth */
    public function deliver(PDO $pdo,array $envelope,array $auth,int $now): array
    {
        $rejection=$this->gate($pdo,$envelope,$auth,$now,self::REQUEST_DOMAIN);
        if ($rejection!==null) {
            return self::result($rejection);
        }
        try {
            $pdo->beginTransaction();
            // CI-only superuser assumes one locked synthetic role. No client
            // input ever chooses an organization, SQL role, table or schema.
            $pdo->exec('SET LOCAL ROLE crm_f340_receiver_a');
            $stmt=$pdo->prepare(<<<'SQL'
WITH nonce_record AS (
  INSERT INTO crm_portal_handoff_ci.request_nonces
    (organization_reference,nonce,submission_reference)
  VALUES (CAST(:org AS uuid),CAST(:nonce AS uuid),CAST(:sub AS uuid))
  ON CONFLICT (organization_reference,nonce) DO NOTHING
  RETURNING organization_reference,submission_reference
), created AS (
  INSERT INTO crm_portal_handoff_ci.metadata_receipts
    (organization_reference,submission_reference,binding_reference,
     site_reference,form_reference,destination_reference)
  SELECT organization_reference,submission_reference,
    CAST(:binding AS uuid),CAST(:site AS uuid),
    CAST(:form AS uuid),CAST(:destination AS uuid)
  FROM nonce_record
  ON CONFLICT (organization_reference,submission_reference) DO NOTHING
  RETURNING submission_reference
)
SELECT (SELECT count(*) FROM nonce_record) AS nonce_rows,
       (SELECT count(*) FROM created) AS created_rows
SQL);
            $stmt->execute([
                'org'=>strtolower($envelope['organization_reference']),
                'nonce'=>$auth['nonce'],
                'sub'=>strtolower($envelope['submission_reference']),
                'binding'=>strtolower($envelope['binding_reference']),
                'site'=>strtolower($envelope['site_reference']),
                'form'=>strtolower($envelope['form_reference']),
                'destination'=>strtolower($envelope['destination_reference']),
            ]);
            $counts=$stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($counts)) {
                $pdo->rollBack();
                return self::result('LAB_DATABASE_UNVERIFIED');
            }
            if ((int)$counts['nonce_rows']===0) {
                $pdo->rollBack();
                return self::result('REQUEST_NONCE_REPLAY');
            }
            if ((int)$counts['nonce_rows']!==1 ||
                !in_array((int)$counts['created_rows'],[0,1],true)) {
                $pdo->rollBack();
                return self::result('LAB_DATABASE_UNVERIFIED');
            }
            if (!$this->storedMatches($pdo,$envelope)) {
                $pdo->rollBack();
                return self::result('PINNED_REPLAY_CONFLICT');
            }
            $new=(int)$counts['created_rows']===1;
            $pdo->commit();
            return self::result(
                $new?'LAB_MOCK_COMMITTED_FIRST':'LAB_MOCK_COMMITTED_LOGICAL_REPLAY',
                $this->signReceipt($envelope,$now)
            );
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return self::result('LAB_DATABASE_UNVERIFIED');
        }
    }

    /** @param array<string,mixed> $envelope @param array<string,mixed> $auth */
    public function lookup(PDO $pdo,array $envelope,array $auth,int $now): array
    {
        $rejection=$this->gate($pdo,$envelope,$auth,$now,self::LOOKUP_DOMAIN);
        if ($rejection!==null) {
            return self::result($rejection);
        }
        try {
            $pdo->beginTransaction();
            $pdo->exec('SET LOCAL ROLE crm_f340_receiver_a');
            $matches=$this->storedMatches($pdo,$envelope);
            $pdo->commit();
            if (!$matches) {
                return self::result('LAB_RECORD_NOT_VERIFIED');
            }
            return self::result('LAB_MOCK_READBACK_SIGNED',$this->signReceipt($envelope,$now));
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return self::result('LAB_DATABASE_UNVERIFIED');
        }
    }

    /** @param array<string,mixed> $envelope @param array<string,mixed> $auth */
    private function gate(PDO $pdo,array $envelope,array $auth,int $now,string $domain): ?string
    {
        if ((new SyntheticLeadEnvelopeV1Inspector())->inspect($envelope)->reasonCode
            !== 'STRUCTURE_ONLY_UNVERIFIED') {
            return 'ENVELOPE_REJECTED';
        }
        if (count($this->serverFixture)!==count(self::BINDING)
            || strlen($this->requestTestKey)<32
            || strlen($this->receiptTestKey)<32
            || hash_equals($this->requestTestKey,$this->receiptTestKey)) {
            return 'LAB_CONFIGURATION_INVALID';
        }
        foreach (self::BINDING as $key) {
            if (!array_key_exists($key,$this->serverFixture)
                || !is_string($this->serverFixture[$key])
                || preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/iD',
                    $this->serverFixture[$key])!==1
                || strtolower($this->serverFixture[$key])===self::NULL_UUID) {
                return 'LAB_CONFIGURATION_INVALID';
            }
            if (strtolower($envelope[$key])!==strtolower($this->serverFixture[$key])) {
                return 'PINNED_CONTEXT_CONFLICT';
            }
        }
        if (count($auth)!==4
            || !array_key_exists('issued_at',$auth)
            || !array_key_exists('expires_at',$auth)
            || !array_key_exists('nonce',$auth)
            || !array_key_exists('mac',$auth)
            || !is_int($auth['issued_at'])
            || !is_int($auth['expires_at'])
            || !is_string($auth['nonce'])
            || !is_string($auth['mac'])
            || preg_match(self::UUID,$auth['nonce'])!==1
            || $auth['nonce']===self::NULL_UUID
            || preg_match('/\A[0-9a-f]{64}\z/D',$auth['mac'])!==1) {
            return 'SIGNED_REQUEST_FORMAT_INVALID';
        }
        $iat=$auth['issued_at'];
        $exp=$auth['expires_at'];
        if ($now<=0 || $iat<=0 || $exp<=$iat || $exp-$iat>300 ||
            $iat>$now+30 || $exp<=$now) {
            return 'SIGNED_REQUEST_WINDOW_INVALID';
        }
        $message=self::canonical($envelope,$iat,$exp,$auth['nonce'],$domain);
        if (!hash_equals(hash_hmac('sha256',$message,$this->requestTestKey),$auth['mac'])) {
            return 'SIGNED_REQUEST_MAC_INVALID';
        }
        if (getenv('GITHUB_ACTIONS')!=='true'
            || getenv('RUNNER_ENVIRONMENT')!=='github-hosted'
            || getenv('CRM_F340_LAB')!=='1'
            || getenv('DB_HOST')!=='127.0.0.1'
            || getenv('DB_DATABASE')!=='imagine_crm_handoff_ci') {
            return 'DISPOSABLE_CI_REQUIRED';
        }
        try {
            if ($pdo->inTransaction()
                || $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)!=='pgsql'
                || $pdo->query('SELECT current_database()')->fetchColumn()
                   !== 'imagine_crm_handoff_ci') {
                return 'DISPOSABLE_DATABASE_REQUIRED';
            }
        } catch (Throwable) {
            return 'DISPOSABLE_DATABASE_REQUIRED';
        }
        return null;
    }

    /** @param array<string,mixed> $envelope */
    private function storedMatches(PDO $pdo,array $envelope): bool
    {
        $statement=$pdo->prepare(<<<'SQL'
SELECT binding_reference,site_reference,form_reference,destination_reference
FROM crm_portal_handoff_ci.metadata_receipts
WHERE organization_reference=CAST(:org AS uuid)
  AND submission_reference=CAST(:sub AS uuid)
SQL);
        $statement->execute([
            'org'=>strtolower($envelope['organization_reference']),
            'sub'=>strtolower($envelope['submission_reference']),
        ]);
        $stored=$statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($stored)) {
            return false;
        }
        foreach (['binding_reference','site_reference','form_reference','destination_reference'] as $field) {
            if (strtolower($stored[$field])!==strtolower($envelope[$field])) {
                return false;
            }
        }
        return true;
    }

    /** @param array<string,mixed> $envelope */
    private function signReceipt(array $envelope,int $now): array
    {
        // The receipt only exists after commit or an independently executed
        // DB read-back. It attests to mock PG, NOT actual CRM persistence.
        $raw=bin2hex(random_bytes(16));
        $raw[12]='4';
        $raw[16]=dechex((hexdec($raw[16])&3)|8);
        $nonce=substr($raw,0,8).'-'.substr($raw,8,4).'-'.substr($raw,12,4)
            .'-'.substr($raw,16,4).'-'.substr($raw,20,12);
        return [
            'issued_at'=>$now,
            'expires_at'=>$now+120,
            'nonce'=>$nonce,
            'mac'=>hash_hmac(
                'sha256',
                self::canonical($envelope,$now,$now+120,$nonce,self::RECEIPT_DOMAIN),
                $this->receiptTestKey
            ),
        ];
    }

    /** @param array<string,mixed> $envelope */
    private static function canonical(array $envelope,int $issued,int $expires,string $nonce,string $domain): string
    {
        $parts=[$domain];
        foreach (self::REFERENCES as $key) {
            $parts[]=strtolower($envelope[$key]);
        }
        $parts[]=(string)$issued;
        $parts[]=(string)$expires;
        $parts[]=$nonce;
        return implode("\n",$parts);
    }

    private static function result(string $code,?array $receipt=null): array
    {
        $result=[
            'schema'=>'imagine.crm.synthetic-portal-handoff.v1',
            'reasonCode'=>$code,
            'evidenceClass'=>'CRM_TEST_ONLY_PG16_NOT_REAL_DELIVERY',
            'mockCommitted'=>$receipt!==null,
            'realCrmPersisted'=>false,
            'tenantAuthenticatedForProduction'=>false,
            'canAcknowledgeVisitor'=>false,
            'canAutoRetryUnknownOutcome'=>false,
            'productionAuthorized'=>false,
        ];
        if ($receipt!==null) {
            $result['receipt']=$receipt;
        }
        return $result;
    }
}
