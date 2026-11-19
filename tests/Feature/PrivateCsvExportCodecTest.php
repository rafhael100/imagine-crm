<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Portability\PortableCsvTableCodec;
use App\Homologation\Controller;
use App\Homologation\Workspace;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Isolated private CSV portability contract using fictional in-memory leads.
 * No authentication, database, host services or customer export is exercised.
 */
final class PrivateCsvExportCodecTest extends TestCase
{
    private function download(array $leads): string
    {
        $recorder=new class($leads) {
            public function __construct(private array $leads) {}
            public function overview(): array { return ['leads'=>$this->leads]; }
        };
        $this->app->instance(Workspace::class,$recorder);
        $response=(new Controller())->export(Request::create('/exportar','GET'));
        self::assertSame(200,$response->getStatusCode());
        self::assertStringContainsString('leads-ficticios.csv',(string)$response->headers->get('content-disposition'));
        self::assertSame('text/csv; charset=UTF-8',$response->headers->get('content-type'));
        ob_start();
        try {
            ($response->getCallback())();
            return (string)ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function test_empty_export_preserves_header_and_roundtrips(): void
    {
        $csv=$this->download([]);
        self::assertSame("title,stage,value_cents\r\n",$csv);
        self::assertSame([['title','stage','value_cents']],(new PortableCsvTableCodec())->decode($csv,'comma'));
    }

    public function test_fictional_leads_roundtrip_without_numeric_mutation(): void
    {
        $leads=[
            (object)['title'=>'LEAD_TESTE_A','stage'=>'new','value_cents'=>0],
            (object)['title'=>'LEAD_TESTE_B','stage'=>'qualified','value_cents'=>100000000],
        ];
        $decoded=(new PortableCsvTableCodec())->decode($this->download($leads),'comma');
        self::assertSame([
            ['title','stage','value_cents'],
            ['LEAD_TESTE_A','new','0'],
            ['LEAD_TESTE_B','qualified','100000000'],
        ],$decoded);
    }

    public function test_rejects_spreadsheet_formula_before_response_stream(): void
    {
        $this->app->instance(Workspace::class,new class {
            public function overview(): array {
                return ['leads'=>[(object)[
                    'title'=>'=HYPERLINK("https://invalid.example")',
                    'stage'=>'new',
                    'value_cents'=>1,
                ]]];
            }
        });
        $this->expectException(\InvalidArgumentException::class);
        (new Controller())->export(Request::create('/exportar','GET'));
    }
}
