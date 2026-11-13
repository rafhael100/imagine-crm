<?php

declare(strict_types=1);

// F3-227: ONLY disposable GitHub Actions PostgreSQL 16 service.
// Never run on production, VPS, databases of customers, or local workstations.
use App\Core\Identity\OfflineSignupOtpChallenge;

if (PHP_SAPI !== 'cli'
    || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('CI') !== 'true'
    || getenv('OTP_LAB_HOST') !== '127.0.0.1'
    || getenv('OTP_LAB_PORT') !== '5433'
    || getenv('OTP_LAB_DB') !== 'imagine_otp_lab_ci'
    || getenv('OTP_LAB_USER') !== 'otp_lab_ci'
    || getenv('OTP_LAB_PASSWORD') !== 'fixture-password-only-for-disposable-ci'
    || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "F3-227 disabled outside exact disposable PG16 CI fixture.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/app/Core/Identity/OfflineSignupOtpChallenge.php';

const TABLE = 'public.crm_otp_f3227_ephemeral';
const TEST_KEY = 'never-production-otp-key-fixture-32-bytes-minimum';
const BINDING = 'abababababababababababababababababababababababababababababababab';

function verify(bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException('A disposable OTP invariant failed.');
    }
}

/** @return PDO */
function connect(): PDO
{
    return new PDO(
        'pgsql:host=127.0.0.1;port=5433;dbname=imagine_otp_lab_ci;sslmode=disable',
        'otp_lab_ci',
        getenv('OTP_LAB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
    );
}

$main = null;
$peer = null;
$passed = false;
try {
    $main = connect();
    $peer = connect();

    foreach ([$main, $peer] as $db) {
        $server = $db->query("SELECT current_database() AS db, current_user AS role,
            current_setting('server_version_num')::integer AS version,
            inet_server_addr()::text AS address")->fetch();
        verify($server['db'] === 'imagine_otp_lab_ci');
        verify($server['role'] === 'otp_lab_ci');
        verify((int) $server['version'] >= 160000 && (int) $server['version'] < 170000);
        verify($server['address'] !== null);
    }

    // CI-only isolated database; no production migrations or deployed schema.
    $main->exec("CREATE TABLE ".TABLE." (
        nonce char(32) PRIMARY KEY,
        digest char(64) NOT NULL,
        issued_at bigint NOT NULL CHECK (issued_at >= 0),
        expires_at bigint NOT NULL CHECK (expires_at = issued_at + 900),
        attempts_used integer NOT NULL DEFAULT 0
            CHECK (attempts_used BETWEEN 0 AND 5),
        consumed boolean NOT NULL DEFAULT FALSE
    )");

    $engine = new OfflineSignupOtpChallenge();
    $issued = $engine->issue(TEST_KEY, BINDING, 'email', 100000);
    $challenge = $issued['challenge'];
    $code = $issued['delivery_code'];

    $insert = $main->prepare("INSERT INTO ".TABLE."
        (nonce,digest,issued_at,expires_at) VALUES (?,?,?,?)");
    verify($insert->execute([
        $challenge['nonce'], $challenge['digest'],
        $challenge['issued_at'], $challenge['expires_at'],
    ]));

    $columns = $main->query("SELECT column_name FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'crm_otp_f3227_ephemeral'
        ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    verify($columns === [
        'nonce','digest','issued_at','expires_at','attempts_used','consumed',
    ]);
    // No delivery code, email, phone, CNPJ or identity is persisted.

    $peer->exec("SET lock_timeout='200ms'");
    $main->beginTransaction();
    $read = $main->prepare("SELECT * FROM ".TABLE." WHERE nonce = ? FOR UPDATE");
    $read->execute([$challenge['nonce']]);
    $locked = $read->fetch();
    verify(is_array($locked) && (int) $locked['attempts_used'] === 0
        && $locked['consumed'] === false);

    $firstAssessment = $engine->inspect(
        [
            'nonce' => trim($locked['nonce']),
            'digest' => trim($locked['digest']),
            'issued_at' => (int) $locked['issued_at'],
            'expires_at' => (int) $locked['expires_at'],
        ],
        $code, TEST_KEY, BINDING, 'email', 100100,
        (int) $locked['attempts_used'], (bool) $locked['consumed'],
    );
    verify($firstAssessment['candidate_matches'] === true);
    verify($firstAssessment['can_authenticate'] === false);

    // A second independent DB session cannot claim this uncommitted nonce.
    $peerClaim = $peer->prepare("UPDATE ".TABLE."
        SET consumed=TRUE, attempts_used=attempts_used+1
        WHERE nonce=? AND consumed=FALSE AND attempts_used<5");
    try {
        $peerClaim->execute([$challenge['nonce']]);
        throw new RuntimeException('The second concurrent claim incorrectly passed.');
    } catch (PDOException $e) {
        verify($e->getCode() === '55P03');
    }
    $writer = $main->prepare("UPDATE ".TABLE."
        SET consumed=TRUE, attempts_used=attempts_used+1
        WHERE nonce=? AND consumed=FALSE AND attempts_used=0");
    $writer->execute([$challenge['nonce']]);
    verify($writer->rowCount() === 1);
    $main->commit();

    // Exactly one physical winner; all subsequent attempts have zero effect.
    $peerClaim->execute([$challenge['nonce']]);
    verify($peerClaim->rowCount() === 0);
    $peerRead = $peer->prepare("SELECT consumed,attempts_used FROM ".TABLE." WHERE nonce=?");
    $peerRead->execute([$challenge['nonce']]);
    $current = $peerRead->fetch();
    verify((bool) $current['consumed'] === true);
    verify((int) $current['attempts_used'] === 1);
    $replay = $engine->inspect(
        $challenge, $code, TEST_KEY, BINDING, 'email', 100101, 1, true,
    );
    verify($replay['status_code'] === 'already_consumed_claim');
    verify($replay['candidate_matches'] === false);

    // An incorrect candidate burns the illustrative budget only when the
    // caller commits the independent durable counter inside a transaction.
    $attempt = $engine->issue(TEST_KEY, BINDING, 'phone', 200000);
    $another = $attempt['challenge'];
    $insert->execute([
        $another['nonce'], $another['digest'],
        $another['issued_at'], $another['expires_at'],
    ]);
    $wrong = $attempt['delivery_code'] === '000000' ? '000001' : '000000';

    for ($n = 0; $n < 5; ++$n) {
        $main->beginTransaction();
        $read->execute([$another['nonce']]);
        $state = $read->fetch();
        verify((int) $state['attempts_used'] === $n);
        $assessment = $engine->inspect(
            $another, $wrong, TEST_KEY, BINDING, 'phone', 200100, $n, false,
        );
        verify($assessment['status_code'] === 'candidate_mismatch');
        $increment = $main->prepare("UPDATE ".TABLE."
            SET attempts_used=attempts_used+1
            WHERE nonce=? AND consumed=FALSE AND attempts_used=?");
        $increment->execute([$another['nonce'], $n]);
        verify($increment->rowCount() === 1);
        $main->commit();
    }
    $lateCorrect = $engine->inspect(
        $another, $attempt['delivery_code'], TEST_KEY, BINDING, 'phone',
        200200, 5, false,
    );
    verify($lateCorrect['status_code'] === 'attempt_budget_exhausted_claim');
    verify($lateCorrect['can_register'] === false);
    $expire = $engine->inspect(
        $another, $attempt['delivery_code'], TEST_KEY, BINDING, 'phone',
        $another['expires_at'], 0, false,
    );
    verify($expire['status_code'] === 'expired');
    verify($main->query("SELECT count(*) FROM ".TABLE)->fetchColumn() === 2);

    $passed = true;
} catch (Throwable) {
    // No plaintext code, digest or PDO exception/credential is emitted.
    fwrite(STDERR, "F3-227 PG16 isolated OTP proof failed (details suppressed).\n");
} finally {
    if ($main instanceof PDO) {
        if ($main->inTransaction()) {
            $main->rollBack();
        }
        try {
            $main->exec('DROP TABLE IF EXISTS '.TABLE);
        } catch (Throwable) {
            $passed = false;
        }
    }
}
if (!$passed) {
    exit(1);
}

echo "F3-227 PASS: real PG16 concurrent single-winner, replay and budget refusal, fixture only.\n";
