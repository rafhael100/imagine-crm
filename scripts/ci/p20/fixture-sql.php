<?php
declare(strict_types=1);
/** Only called after target verification, on the same PDO object. */
function p20ExecuteFixture(object $db, string $mode, string $sql): void
{
    if ($mode === 'prepare') {
        // PostgreSQL fixture scripts contain multiple statements and dollar-quoted bodies.
        // exec uses the simple protocol without splitting SQL or weakening ERRMODE_EXCEPTION.
        $db->exec($sql);
        return;
    }
    if ($mode !== 'read' || !str_starts_with(strtoupper(ltrim($sql)), 'SELECT ')) {
        throw new RuntimeException('P20_FIXTURE_MODE_DENIED');
    }
    $statement = $db->query($sql);
    foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $value) {
        echo (is_bool($value) ? ($value ? 't' : 'f') : $value)."\n";
    }
}
