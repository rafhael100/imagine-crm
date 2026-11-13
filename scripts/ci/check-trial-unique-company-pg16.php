<?php

declare(strict_types=1);

// F3-228 — ONLY one exact disposable GitHub Actions PG16 instance.
// Identifiers are synthetic opaque bindings, never CNPJ, leads or people.
if (PHP_SAPI !== 'cli'
    || getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('CI') !== 'true'
    || getenv('TRIAL_LAB_HOST') !== '127.0.0.1'
    || getenv('TRIAL_LAB_PORT') !== '5434'
    || getenv('TRIAL_LAB_DATABASE') !== 'imagine_trial_lab_ci'
    || getenv('TRIAL_LAB_USERNAME') !== 'trial_lab_ci'
    || getenv('TRIAL_LAB_PASSWORD') !== 'fixture-trial-only-disposable-ci'
    || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "F3-228 disabled outside its exact ephemeral CI database.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/app/Core/Plans/TrialWindowAdvisory.php';
require dirname(__DIR__, 2).'/app/Core/Plans/TrialWindowPhase.php';
require dirname(__DIR__, 2).'/app/Core/Identity/OfflineCnpjChecksumInspector.php';
require dirname(__DIR__, 2).'/app/Core/Identity/OfflineCnpjTrialHmacBinding.php';

use App\Core\Plans\TrialWindowAdvisory;
use App\Core\Identity\OfflineCnpjTrialHmacBinding;

const LAB_TABLE = 'public.crm_trial_f3228_ephemeral';

function requireTruth(bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException('A synthetic trial invariant was not met.');
    }
}

function connectDb(): PDO
{
    return new PDO(
        'pgsql:host=127.0.0.1;port=5434;dbname=imagine_trial_lab_ci;sslmode=disable',
        'trial_lab_ci',
        getenv('TRIAL_LAB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC],
    );
}

$one = null;
$two = null;
$success = false;

try {
    $one = connectDb();
    $two = connectDb();
    foreach ([$one, $two] as $pdo) {
        $metadata = $pdo->query("SELECT current_database() db,
            current_user rol, current_setting('server_version_num')::int ver")->fetch();
        requireTruth($metadata['db'] === 'imagine_trial_lab_ci');
        requireTruth($metadata['rol'] === 'trial_lab_ci');
        requireTruth((int) $metadata['ver'] >= 160000 && (int) $metadata['ver'] < 170000);
    }

    $one->exec('CREATE TABLE '.LAB_TABLE.' (
        company_binding char(64) PRIMARY KEY,
        claim_id char(32) NOT NULL UNIQUE,
        claimed_at bigint NOT NULL CHECK (claimed_at >= 0),
        expires_at bigint NOT NULL CHECK (expires_at = claimed_at + 1209600)
    )');
    $columns = $one->query("SELECT column_name FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'crm_trial_f3228_ephemeral'
        ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    requireTruth($columns === ['company_binding','claim_id','claimed_at','expires_at']);

    // Physical unique constraint is now exercised against the real F3-231
    // HMAC binding derived from check-digit-valid synthetic CNPJ examples.
    // Nothing derived from any real company or external registry.
    $bind = new OfflineCnpjTrialHmacBinding();
    $key = 'synthetic-only-cnpj-binding-key-long-enough-32-plus';
    $companyA = $bind->derive('12.ABC.345/01DE-35', $key);
    $companyAEquivalent = $bind->derive('12abc34501de35', $key);
    $companyB = $bind->derive('11.222.333/0001-81', $key);
    $companyC = $bind->derive('45DEF67890GH23', $key);
    requireTruth(hash_equals($companyA, $companyAEquivalent));
    requireTruth(!hash_equals($companyA, $companyB));
    requireTruth(!hash_equals($companyA, $companyC));
    requireTruth(!hash_equals($companyB, $companyC));

    $t = new TrialWindowAdvisory(1800000000, 1800000000);
    requireTruth($t->endsAtEpochSeconds() === 1801209600);
    requireTruth($t->remainingDaysRoundedUp() === 14);

    // Both connections race to claim the SAME opaque, already-normalized
    // synthetic company binding. PostgreSQL primary key arbitrates.
    $insert = 'INSERT INTO '.LAB_TABLE.'
        (company_binding,claim_id,claimed_at,expires_at)
        VALUES (?,?,?,?) ON CONFLICT (company_binding) DO NOTHING';
    $first = $one->prepare($insert);
    $second = $two->prepare($insert);
    $two->exec("SET lock_timeout='200ms'");

    $one->beginTransaction();
    $first->execute([$companyA, bin2hex(random_bytes(16)), 1800000000, $t->endsAtEpochSeconds()]);
    requireTruth($first->rowCount() === 1);

    // The second statement must not silently claim a pending first insert.
    try {
        $second->execute([$companyA, bin2hex(random_bytes(16)), 1800000000, $t->endsAtEpochSeconds()]);
        throw new RuntimeException('Competing trial claim was not blocked.');
    } catch (PDOException $exception) {
        requireTruth($exception->getCode() === '55P03');
    }

    $one->commit();
    $second->execute([$companyAEquivalent, bin2hex(random_bytes(16)), 1800000000, $t->endsAtEpochSeconds()]);
    requireTruth($second->rowCount() === 0);

    // Expiry of the first 14 days does NOT make a second trial eligible.
    $second->execute([$companyA, bin2hex(random_bytes(16)), 1801209601, 1802419201]);
    requireTruth($second->rowCount() === 0);

    // Independent company gets its own single claim.
    $second->execute([$companyB, bin2hex(random_bytes(16)), 1800000000, $t->endsAtEpochSeconds()]);
    requireTruth($second->rowCount() === 1);
    requireTruth((int) $one->query('SELECT count(*) FROM '.LAB_TABLE)->fetchColumn() === 2);

    // Failed transaction rolls back its reservation; the other process can
    // claim the same synthetic binding after rollback.
    $one->beginTransaction();
    $first->execute([$companyC, bin2hex(random_bytes(16)), 1800000000, $t->endsAtEpochSeconds()]);
    requireTruth($first->rowCount() === 1);
    $one->rollBack();
    $second->execute([$companyC, bin2hex(random_bytes(16)), 1800000000, $t->endsAtEpochSeconds()]);
    requireTruth($second->rowCount() === 1);
    requireTruth((int) $one->query('SELECT count(*) FROM '.LAB_TABLE)->fetchColumn() === 3);

    // Only keyed digests of public fictional examples enter the test table.
    // This is not evidence that any real company's fiscal ID is verified.
    requireTruth(
        (int) $one->query('SELECT count(DISTINCT company_binding) FROM '.LAB_TABLE)->fetchColumn() === 3,
    );

    $success = true;
} catch (Throwable) {
    fwrite(STDERR, "F3-228 unique-trial PG16 proof failed (details suppressed).\n");
} finally {
    if ($one instanceof PDO) {
        if ($one->inTransaction()) {
            $one->rollBack();
        }
        try {
            $one->exec('DROP TABLE IF EXISTS '.LAB_TABLE);
        } catch (Throwable) {
            $success = false;
        }
    }
}
if (!$success) {
    exit(1);
}
echo "F3-228 PASS: PG16 one claim per opaque fictional company, race, replay and rollback.\n";
