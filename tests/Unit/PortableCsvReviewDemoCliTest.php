<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Portability\PortableCsvReviewBundleV1;
use JsonException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class PortableCsvReviewDemoCliTest extends TestCase
{
    private static function path(): string
    {
        return dirname(__DIR__, 2).'/scripts/ci/emit-portable-csv-review-demo.php';
    }

    public function test_real_cli_produces_only_fixed_synthetic_aggregate_v1(): void
    {
        $run = new Process([PHP_BINARY, self::path()]);
        $run->setTimeout(20);
        $run->run();
        self::assertTrue($run->isSuccessful(), $run->getErrorOutput());
        self::assertSame('', $run->getErrorOutput());
        $output = $run->getOutput();
        self::assertSame(1, substr_count($output, "\n"));
        self::assertLessThanOrEqual(PortableCsvReviewBundleV1::MAX_JSON_BYTES + 1, strlen($output));

        $value = json_decode(trim($output), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imagine.crm.portable_csv_review_bundle', $value['protocol']);
        self::assertSame(1, $value['schema_version']);
        self::assertSame('REVIEW_ONLY_DENIED', $value['status']);
        self::assertSame(2, $value['structural_review']['counts']['data_rows']);
        self::assertSame(1, $value['structural_review']['counts']['projection_collision_extra_rows']);
        self::assertSame(1, $value['control_signals']['counts']['source_bidi_control_cells']);
        self::assertSame(0, $value['control_signals']['counts']['mapped_bidi_control_cells']);
        self::assertSame(1, $value['structural_review']['counts']['unmapped_claimed_required_fields']);
        self::assertCount(6, $value['structural_review']['unfulfilled_gate_codes']);
        self::assertContains('SOURCE_CELLS_HAVE_BIDI_DIRECTIONAL_CONTROLS', $value['control_signals']['warning_codes']);

        foreach (['DemoA', 'fictional', 'other', '"name"', '"email"', '"notes"', 'tenant_id', 'file_path', 'contact_id'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $output);
        }
        foreach (['authorized', 'can_import', 'can_export', 'can_merge', 'can_deduplicate', 'can_persist'] as $flag) {
            self::assertFalse($value[$flag], $flag);
            self::assertFalse($value['structural_review'][$flag], $flag);
        }
    }

    public function test_cli_rejects_arguments_instead_of_reading_untrusted_customer_files(): void
    {
        foreach (['--input=SECRET_FIXTURE.csv', '--help'] as $arg) {
            $run = new Process([PHP_BINARY, self::path(), $arg]);
            $run->setTimeout(20);
            $run->run();
            self::assertSame(2, $run->getExitCode());
            self::assertSame('', $run->getOutput());
            self::assertSame(
                "Demonstracao CSV aceita somente execucao sem argumentos.\n",
                $run->getErrorOutput(),
            );
            self::assertStringNotContainsString('SECRET_FIXTURE', $run->getErrorOutput());
        }
    }

    public function test_demo_source_cannot_access_network_files_databases_or_leak_input(): void
    {
        $php = file_get_contents(self::path());
        self::assertIsString($php);
        self::assertStringContainsString("PHP_SAPI !== 'cli'", $php);
        self::assertStringContainsString('PortableCsvReviewBundleV1', $php);
        foreach ([
            'file_get_contents(', 'file_put_contents(', 'fopen(', 'curl_', 'mysqli_',
            'PDO(', 'DB::', '$_GET', '$_POST', '$_FILES', '$_SERVER',
            'getenv(', 'shell_exec(', 'exec(', 'system(', 'passthru(',
            'http://', 'https://',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $php);
        }
    }
}
