<?php

declare(strict_types=1);

namespace Tests\Isolated;

use App\Homologation\Controller;
use App\Homologation\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

/**
 * Explicit CI witness outside the default suites. The workflow checks the
 * historical checkout's commit/tree/Git mode before this exact-byte check.
 * This proves parsing and forwarding by the complete historical Controller;
 * Workspace is a recorder, so it does not prove historical HTTP/IAM or a DB write.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PrivateCsvHistoricalParserTest extends TestCase
{
    private const SOURCE_BLOB = '380e8437749f050ee24b9be77b0eacb3241626d8';
    private const SOURCE_SHA256 = 'f59471983a992f4b50c2461a2dcc77f033a1148bac32505acde116a1c7380803';
    private string $sourceFile;

    protected function setUp(): void
    {
        self::assertGreaterThanOrEqual(80400,PHP_VERSION_ID,'The historical witness requires PHP 8.4.');
        self::assertLessThan(80500,PHP_VERSION_ID,'The historical witness requires PHP 8.4.');
        $source=getenv('CRM_PRIVATE_CSV_HISTORICAL_SOURCE');
        self::assertIsString($source,'The separately checked historical source is required.');
        self::assertNotSame('',$source);
        self::assertSame($source,realpath($source),'Historical source must have an exact resolved path.');
        $this->sourceFile=$source.'/app/Homologation/Controller.php';
        self::assertTrue(is_file($this->sourceFile) && !is_link($this->sourceFile));
        self::assertSame($this->sourceFile,realpath($this->sourceFile));
        self::assertSame(0,fileperms($this->sourceFile)&0111,'Historical Controller must not be executable.');
        $bytes=file_get_contents($this->sourceFile);
        self::assertIsString($bytes);
        self::assertSame(12147,strlen($bytes));
        self::assertSame(self::SOURCE_BLOB,sha1('blob '.strlen($bytes)."\0".$bytes));
        self::assertSame(self::SOURCE_SHA256,hash('sha256',$bytes));
        self::assertFalse(class_exists(Controller::class,false),'The current Controller must not be loaded.');
        require $this->sourceFile;
        self::assertSame($this->sourceFile,(new \ReflectionClass(Controller::class))->getFileName());
        parent::setUp();
    }

    public function test_php84_historical_controller_forwards_an_unclosed_quoted_numeric_field(): void
    {
        $recorder=new class {
            public array $calls=[];
            public function createLeads(array $rows,bool $historicalImport=false): void
            {
                $this->calls[]=['rows'=>$rows,'historical_import'=>$historicalImport];
            }
        };
        $this->app->instance(Workspace::class,$recorder);
        $file=tempnam(sys_get_temp_dir(),'crm-csv-history-');
        self::assertIsString($file);
        try {
            $csv="title,value_cents\nLEAD_TESTE_CSV_PREFIX,1\nLEAD_TESTE_CSV_UNCLOSED,\"100";
            self::assertSame(strlen($csv),file_put_contents($file,$csv));
            $upload=new UploadedFile($file,'fictional.csv','text/csv',UPLOAD_ERR_OK,true);
            $request=Request::create('/importar','POST',[],[],['csv'=>$upload]);
            $request->setLaravelSession($this->app['session.store']);
            $response=(new Controller())->import($request);
            self::assertSame(303,$response->getStatusCode());
            self::assertSame('/painel',parse_url($response->getTargetUrl(),PHP_URL_PATH));
            self::assertSame('section=leads',parse_url($response->getTargetUrl(),PHP_URL_QUERY));
            self::assertSame([['rows'=>[
                ['title'=>'LEAD_TESTE_CSV_PREFIX','value_cents'=>'1','stage'=>'new'],
                ['title'=>'LEAD_TESTE_CSV_UNCLOSED','value_cents'=>'100','stage'=>'new'],
            ],'historical_import'=>true]],$recorder->calls);
            self::assertSame(self::SOURCE_SHA256,hash_file('sha256',$this->sourceFile));
        } finally { unlink($file); }
    }
}
