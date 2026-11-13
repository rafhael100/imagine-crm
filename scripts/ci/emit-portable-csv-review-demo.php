<?php

declare(strict_types=1);

/**
 * F3-224 / IN-118 — offline executable integration of the actual Core V1
 * report with fixed synthetic CSV only. NO external input or file arguments.
 *
 * Command: php scripts/ci/emit-portable-csv-review-demo.php
 * Outputs one bounded denial-only JSON line, never the fictional cell values.
 */
use App\Core\Portability\PortableCsvReviewBundleV1;

if (PHP_SAPI !== 'cli' || ($argc ?? 0) !== 1) {
    fwrite(STDERR, "Demonstracao CSV aceita somente execucao sem argumentos.\n");
    exit(2);
}

require dirname(__DIR__, 2).'/vendor/autoload.php';

$csv = "name;notes;ignored\r\n"
    ."DemoA;ok;\u{202E}fictional\r\n"
    ."DemoA;ok;other\r\n";

try {
    $json = (new PortableCsvReviewBundleV1())->encode(
        $csv,
        'semicolon',
        [
            ['source_position' => 0, 'target_field' => 'name'],
            ['source_position' => 1, 'target_field' => 'notes'],
        ],
        true,
        ['name', 'email'],
    );
    fwrite(STDOUT, $json."\n");
} catch (\InvalidArgumentException) {
    fwrite(STDERR, "Demonstracao CSV inativa: relatorio invalido.\n");
    exit(1);
}
