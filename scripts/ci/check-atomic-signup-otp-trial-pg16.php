<?php

declare(strict_types=1);

/**
 * F3-233 / NO-GO: combined OTP consumption and trial uniqueness transaction.
 *
 * Only the exact short-lived GitHub Actions PostgreSQL 16 service qualifies.
 * Uses fictional CNPJ values; stores HMAC only, never raw CNPJ, OTP or PII.
 * Not a deployable migration, provider, controller or registration function.
 */

use App\Core\Identity\OfflineCnpjTrialHmacBinding;
use App\Core\Identity\OfflineSignupOtpChallenge;

if (PHP_SAPI !== 'cli'
    || ($argc ?? 0) !== 1
    || getenv('CI') !== 'true'
    || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('TRIAL_OTP_LAB_HOST') !== '127.0.0.1'
    || getenv('TRIAL_OTP_LAB_PORT') !== '5436'
    || getenv('TRIAL_OTP_LAB_DB') !== 'imagine_signup_atomic_ci'
    || getenv('TRIAL_OTP_LAB_USER') !== 'signup_atomic_ci'
    || getenv('TRIAL_OTP_LAB_PASS') !== 'only-disposable-fixture-signup-atomic-2026') {
    fwrite(STDERR, "F3-233 disabled outside exact disposable PostgreSQL CI.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/app/Core/Identity/OfflineCnpjChecksumInspector.php';
require dirname(__DIR__, 2).'/app/Core/Identity/OfflineCnpjTrialHmacBinding.php';
require dirname(__DIR__, 2).'/app/Core/Identity/OfflineSignupOtpChallenge.php';

const OTP_TABLE = 'public.crm_signup_f3233_otp';
const TRIAL_TABLE = 'public.crm_signup_f3233_trial';
const OTP_SECRET = 'fictional-only-otp-secret-more-than-32-bytes';
const CNPJ_SECRET = 'fictional-only-cnpj-secret-more-than-32-bytes';
const OTP_CONTEXT = 'abababababababababababababababababababababababababababababababab';

function claimAssert(bool $yes): void
{
    if (!$yes) {
        throw new RuntimeException('One of the ephemeral signup invariants failed.');
    }
}

function pgBoolean(mixed $value): bool
{
    if ($value === true || $value === 't' || $value === '1') return true;
    if ($value === false || $value === 'f' || $value === '0') return false;
    throw new RuntimeException('Unexpected PostgreSQL boolean value.');
}

function db(): PDO
{
    return new PDO(
        'pgsql:host=127.0.0.1;port=5436;dbname=imagine_signup_atomic_ci;sslmode=disable',
        'signup_atomic_ci',
        getenv('TRIAL_OTP_LAB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    );
}

/** @param array<string,mixed> $issued */
function recordOtp(PDO $pdo, array $issued): void
{
    $ch = $issued['challenge'];
    $stmt = $pdo->prepare('INSERT INTO '.OTP_TABLE.'
        (nonce,digest,issued_at,expires_at) VALUES (?,?,?,?)');
    $stmt->execute([$ch['nonce'],$ch['digest'],$ch['issued_at'],$ch['expires_at']]);
    claimAssert($stmt->rowCount() === 1);
}

/** @return array<string,mixed> */
function lockOtp(PDO $pdo, string $nonce): array
{
    $stmt = $pdo->prepare('SELECT * FROM '.OTP_TABLE.' WHERE nonce=? FOR UPDATE');
    $stmt->execute([$nonce]);
    $row = $stmt->fetch();
    claimAssert(is_array($row));
    return $row;
}

/** @param array<string,mixed> $row */
function assessOtp(OfflineSignupOtpChallenge $engine, array $row, string $code, int $now): array
{
    return $engine->inspect(
        [
            'nonce' => trim($row['nonce']),
            'digest' => trim($row['digest']),
            'issued_at' => (int) $row['issued_at'],
            'expires_at' => (int) $row['expires_at'],
        ],
        $code, OTP_SECRET, OTP_CONTEXT, 'email', $now,
        (int) $row['attempts_used'], pgBoolean($row['consumed']),
    );
}

/** @return int 1=inserted,0=duplicate company */
function insertTrial(PDO $pdo, string $company, string $otpNonce, int $now): int
{
    $stmt = $pdo->prepare('INSERT INTO '.TRIAL_TABLE.'
      (company_binding,otp_nonce,claimed_at) VALUES (?,?,?)
      ON CONFLICT (company_binding) DO NOTHING');
    $stmt->execute([$company,$otpNonce,$now]);
    return $stmt->rowCount();
}

function consumeOtp(PDO $pdo, string $nonce): void
{
    $stmt = $pdo->prepare('UPDATE '.OTP_TABLE.'
      SET consumed=TRUE,attempts_used=attempts_used+1
      WHERE nonce=? AND consumed=FALSE AND attempts_used=0');
    $stmt->execute([$nonce]);
    claimAssert($stmt->rowCount() === 1);
}

$main = null;
$peer = null;
$passed = false;
try {
    $main = db();
    $peer = db();
    foreach ([$main, $peer] as $pdo) {
        $meta = $pdo->query("SELECT current_database() db,current_user rol,
           current_setting('server_version_num')::int v")->fetch();
        claimAssert($meta['db'] === 'imagine_signup_atomic_ci');
        claimAssert($meta['rol'] === 'signup_atomic_ci');
        claimAssert((int) $meta['v'] >= 160000 && (int) $meta['v'] < 170000);
    }

    // These DDL statements run solely within the disposable CI database;
    // they are NOT schema migrations for CRM tenant production.
    $main->exec('CREATE TABLE '.OTP_TABLE.' (
        nonce char(32) PRIMARY KEY,
        digest char(64) NOT NULL,
        issued_at bigint NOT NULL CHECK (issued_at >= 0),
        expires_at bigint NOT NULL CHECK (expires_at = issued_at + 900),
        attempts_used integer NOT NULL DEFAULT 0 CHECK (attempts_used BETWEEN 0 AND 5),
        consumed boolean NOT NULL DEFAULT FALSE
    )');
    $main->exec('CREATE TABLE '.TRIAL_TABLE.' (
        company_binding char(64) PRIMARY KEY,
        otp_nonce char(32) NOT NULL UNIQUE REFERENCES '.OTP_TABLE.'(nonce),
        claimed_at bigint NOT NULL CHECK (claimed_at >= 0)
    )');

    $names = $main->query("SELECT table_name,column_name
      FROM information_schema.columns WHERE table_schema='public'
      AND table_name IN ('crm_signup_f3233_otp','crm_signup_f3233_trial')")->fetchAll();
    claimAssert(count($names) === 9);
    $columnNames = array_column($names, 'column_name');
    foreach (['cnpj','email','phone','delivery_code','password','contact'] as $private) {
        claimAssert(!in_array($private, $columnNames, true));
    }

    $otp = new OfflineSignupOtpChallenge();
    $cnpj = new OfflineCnpjTrialHmacBinding();
    $companyA = $cnpj->derive('12.ABC.345/01DE-35', CNPJ_SECRET);
    $companyAAlt = $cnpj->derive('12abc34501de35', CNPJ_SECRET);
    $companyB = $cnpj->derive('11.222.333/0001-81', CNPJ_SECRET);
    claimAssert(hash_equals($companyA, $companyAAlt));
    claimAssert(!hash_equals($companyA, $companyB));

    // First successful candidate is only a cryptographic observation.
    $issued = $otp->issue(OTP_SECRET, OTP_CONTEXT, 'email', 100000);
    recordOtp($main, $issued);
    $nonce = $issued['challenge']['nonce'];

    $peer->exec("SET lock_timeout='200ms'");
    $main->beginTransaction();
    $row = lockOtp($main, $nonce);
    $result = assessOtp($otp, $row, $issued['delivery_code'], 100100);
    claimAssert($result['status_code'] === 'candidate_matches_only');
    claimAssert($result['can_register'] === false);
    claimAssert(insertTrial($main, $companyA, $nonce, 100100) === 1);
    consumeOtp($main, $nonce);

    // The concurrent process is unable to read and use a locked challenge.
    $peer->beginTransaction();
    try {
        lockOtp($peer, $nonce);
        throw new RuntimeException('Uncommitted OTP lock was ignored.');
    } catch (PDOException $e) {
        claimAssert($e->getCode() === '55P03');
    } finally {
        $peer->rollBack();
    }

    $main->commit();

    $post = lockOtp($peer, $nonce);
    claimAssert(pgBoolean($post['consumed']));
    claimAssert((int) $post['attempts_used'] === 1);
    $replay = assessOtp($otp, $post, $issued['delivery_code'], 100101);
    claimAssert($replay['status_code'] === 'already_consumed_claim');
    claimAssert($replay['candidate_matches'] === false);

    // A fresh OTP is not permission to grant a second trial for the same CNPJ.
    $new = $otp->issue(OTP_SECRET, OTP_CONTEXT, 'email', 110000);
    recordOtp($main, $new);
    $newNonce = $new['challenge']['nonce'];
    $main->beginTransaction();
    $newRow = lockOtp($main, $newNonce);
    claimAssert(assessOtp($otp, $newRow, $new['delivery_code'], 110100)['candidate_matches']);
    claimAssert(insertTrial($main, $companyAAlt, $newNonce, 110100) === 0);
    $main->rollBack(); // Do not consume the OTP after a denied claim.
    $newRow = lockOtp($main, $newNonce);
    claimAssert(!pgBoolean($newRow['consumed']));
    claimAssert((int) $newRow['attempts_used'] === 0);

    // Fault after both writes, before COMMIT: neither claim nor OTP
    // consumption survives rollback; a later attempt can safely retry.
    $rollback = $otp->issue(OTP_SECRET, OTP_CONTEXT, 'email', 120000);
    recordOtp($main, $rollback);
    $rollbackNonce = $rollback['challenge']['nonce'];
    $main->beginTransaction();
    $rollbackRow = lockOtp($main, $rollbackNonce);
    claimAssert(assessOtp($otp, $rollbackRow, $rollback['delivery_code'], 120100)['candidate_matches']);
    claimAssert(insertTrial($main, $companyB, $rollbackNonce, 120100) === 1);
    consumeOtp($main, $rollbackNonce);
    $main->rollBack();

    $query = $main->prepare('SELECT count(*) FROM '.TRIAL_TABLE.' WHERE company_binding=?');
    $query->execute([$companyB]);
    claimAssert((int) $query->fetchColumn() === 0);
    $rollbackRow = lockOtp($main, $rollbackNonce);
    claimAssert(!pgBoolean($rollbackRow['consumed']));

    $main->beginTransaction();
    $rollbackRow = lockOtp($main, $rollbackNonce);
    claimAssert(assessOtp($otp, $rollbackRow, $rollback['delivery_code'], 120101)['candidate_matches']);
    claimAssert(insertTrial($main, $companyB, $rollbackNonce, 120101) === 1);
    consumeOtp($main, $rollbackNonce);
    $main->commit();

    // Strict 900-second boundary must not cause any write.
    $late = $otp->issue(OTP_SECRET, OTP_CONTEXT, 'email', 130000);
    recordOtp($main, $late);
    $lateRow = lockOtp($main, $late['challenge']['nonce']);
    $expired = assessOtp($otp, $lateRow, $late['delivery_code'], 130900);
    claimAssert($expired['status_code'] === 'expired');
    claimAssert($expired['can_register'] === false);

    claimAssert((int) $main->query('SELECT count(*) FROM '.TRIAL_TABLE)->fetchColumn() === 2);
    claimAssert((int) $main->query('SELECT count(*) FROM '.OTP_TABLE.' WHERE consumed')->fetchColumn() === 2);
    $passed = true;
} catch (Throwable) {
    fwrite(STDERR, "F3-233 isolated OTP+trial atomicity proof failed (details suppressed).\n");
} finally {
    if ($main instanceof PDO) {
        if ($main->inTransaction()) {
            $main->rollBack();
        }
        try {
            $main->exec('DROP TABLE IF EXISTS '.TRIAL_TABLE);
            $main->exec('DROP TABLE IF EXISTS '.OTP_TABLE);
        } catch (Throwable) {
            $passed = false;
        }
    }
}
if (!$passed) {
    exit(1);
}

echo "F3-233 PASS: PG16 atomic OTP+trial, replay, single-claim race, rollback, expiry; synthetic only.\n";
