<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\GuardPrincipalSource;
use App\Homologation\Environment;
use App\Homologation\MinimalLeadReadController;
use App\Homologation\Workspace;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/** Admission and hostile input only; live session/SQL authority is tested in HTTP CI. */
final class PrivateMinimalLeadReadBoundaryTest extends TestCase
{
    private const REF = '11111111-1111-4111-8111-111111111111';
    private array $previousEnvironment = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['CRM_PRIVATE_OP01','CRM_PRIVATE_JOURNEY'] as $name) {
            $this->previousEnvironment[$name] = getenv($name);
        }
        putenv('CRM_PRIVATE_OP01');
        putenv('CRM_PRIVATE_JOURNEY');
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnvironment as $name=>$value) {
            putenv($value===false?$name:$name.'='.$value);
        }
        parent::tearDown();
    }

    /** Configuration admission is not a session, membership or read grant. */
    private function configurePrivateProfile(): void
    {
        $connection = ['driver'=>'pgsql','database'=>'imagine_crm_private_homologation',
            'host'=>'postgres','port'=>'5432','url'=>null];
        $this->app['env'] = 'staging';
        config([
            'identity.experimental'=>true,
            'identity.private_staging_profile'=>'private-homologation-synthetic-v1',
            'app.debug'=>false,
            'app.url'=>'https://crm-dev.imaginecomunica.com.br',
            'session.driver'=>'redis',
            'identity.abuse_store'=>'redis',
            'database.default'=>'pgsql',
            'database.connections.pgsql'=>$connection+['username'=>'crm_homologation_app','password'=>str_repeat('A',32)],
            'database.connections.crm_iam'=>$connection+['username'=>'crm_homologation_iam','password'=>str_repeat('B',32)],
        ]);
        putenv('CRM_PRIVATE_JOURNEY=synthetic-v1');
        putenv('CRM_PRIVATE_OP01=minimal-lead-v1');
    }

    private function assertHttpFailure(int $status,callable $operation): void
    {
        try {
            $operation();
            self::fail('The private read unexpectedly admitted this request.');
        } catch (HttpExceptionInterface $error) {
            self::assertSame($status,$error->getStatusCode());
        }
    }

    public function test_flag_alone_cannot_add_a_route_or_admit_the_default_application(): void
    {
        putenv('CRM_PRIVATE_OP01=minimal-lead-v1');
        putenv('CRM_PRIVATE_JOURNEY=synthetic-v1');
        self::assertFalse(Environment::minimalLeadReadEnabled());
        $this->get('/agentes/leads/'.self::REF.'/minimo')->assertNotFound();
        $this->get('/agentes/leads/'.self::REF.'/resumo')->assertNotFound();
    }

    public function test_private_profile_still_requires_the_exact_explicit_flag(): void
    {
        $this->configurePrivateProfile();
        self::assertTrue(Environment::minimalLeadReadEnabled());
        foreach ([null,'','true','1','minimal-lead-v2','MINIMAL-LEAD-V1','minimal-lead-v1 '] as $flag) {
            putenv($flag===null?'CRM_PRIVATE_OP01':'CRM_PRIVATE_OP01='.$flag);
            self::assertFalse(Environment::minimalLeadReadEnabled());
        }
        putenv('CRM_PRIVATE_OP01=minimal-lead-v1');
        foreach (['production','testing','local'] as $environment) {
            $this->app['env'] = $environment;
            self::assertFalse(Environment::minimalLeadReadEnabled());
        }
    }

    public function test_hostile_query_body_and_head_never_resolve_the_workspace(): void
    {
        $this->configurePrivateProfile();
        $resolved = 0;
        $this->app->bind(Workspace::class,function () use (&$resolved): never {
            ++$resolved;
            throw new \LogicException('Workspace must not be reached.');
        });
        $cases = [
            ['GET','fields=ref','',422],
            ['GET','lang=en','',422],
            ['GET','tenant_ref=foreign','',422],
            ['GET','field=ref&field=stage','',422],
            ['GET','&&','',422],
            ['GET','%00=','',422],
            ['GET','','{}',422],
            ['GET','',' ',422],
            ['HEAD','','',405],
            ['POST','','',405],
        ];
        foreach ($cases as [$method,$query,$body,$status]) {
            $request = Request::create('/agentes/leads/'.self::REF.'/minimo',$method,[],[],[],[],$body);
            $request->server->set('QUERY_STRING',$query);
            $this->assertHttpFailure($status,fn ()=>(new MinimalLeadReadController())($request,self::REF));
        }
        self::assertSame(0,$resolved);
    }

    public function test_runtime_method_rechecks_the_flag_before_any_connection_or_identity(): void
    {
        $this->configurePrivateProfile();
        putenv('CRM_PRIVATE_OP01');
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('selectOne');
        $db->expects(self::never())->method('table');
        $db->expects(self::never())->method('transaction');
        $workspace = new Workspace(new GuardPrincipalSource(),$db,$db);
        $this->assertHttpFailure(404,fn ()=>$workspace->minimalLead(self::REF));
    }

    public function test_service_hints_and_method_override_never_resolve_the_workspace(): void
    {
        $this->configurePrivateProfile();
        $resolved = 0;
        $this->app->bind(Workspace::class,function () use (&$resolved): never {
            ++$resolved;
            throw new \LogicException('Workspace must not be reached.');
        });
        foreach (['Authorization','X-Tenant-Ref','X-Principal-Ref','X-Principal-Id',
            'X-Organization-Id','X-Organization-Ref','X-Company-Id','X-Company-Ref',
            'X-Aia-Tenant','X-Aia-Principal','X-Aia-Company'] as $header) {
            $request = Request::create('/agentes/leads/'.self::REF.'/minimo');
            $request->headers->set($header,'UNTRUSTED');
            $this->assertHttpFailure(400,fn ()=>(new MinimalLeadReadController())($request,self::REF));
        }
        $request = Request::create('/agentes/leads/'.self::REF.'/minimo','POST');
        $request->headers->set('X-HTTP-Method-Override','GET');
        // The locked Symfony 8.1 Request refuses GET overrides. Preserve that
        // protection and verify the attempted override never reaches a read.
        self::assertSame('POST',$request->getMethod());
        self::assertSame('POST',$request->getRealMethod());
        $this->assertHttpFailure(405,fn ()=>(new MinimalLeadReadController())($request,self::REF));
        self::assertSame(0,$resolved);
    }

    public function test_noncanonical_resources_are_rejected_before_any_database_access(): void
    {
        $this->configurePrivateProfile();
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('selectOne');
        $db->expects(self::never())->method('table');
        $db->expects(self::never())->method('transaction');
        $workspace = new Workspace(new GuardPrincipalSource(),$db,$db);
        foreach (['','SYNTHETIC_LEAD','AAAAAAAA-AAAA-4AAA-8AAA-AAAAAAAAAAAA',
            self::REF.' ',str_replace('-','',self::REF),self::REF.'/other'] as $ref) {
            $this->assertHttpFailure(404,fn ()=>$workspace->minimalLead($ref));
        }
    }

    public function test_summary_rejects_query_ambiguity_body_methods_and_service_hints_before_read(): void
    {
        $this->configurePrivateProfile();
        $resolved = 0;
        $this->app->bind(Workspace::class,function () use (&$resolved): never {
            ++$resolved;
            throw new \LogicException('Workspace must not be reached.');
        });
        foreach (['lang=en&lang=es','lang[]=en','lang=%65n','%6cang=en','lang=EN',
            'lang=','lang=pt&','&lang=pt','lang=pt&fields=ref','tenant_ref=foreign','&&'] as $query) {
            $request = Request::create('/agentes/leads/'.self::REF.'/resumo');
            $request->server->set('QUERY_STRING',$query);
            $this->assertHttpFailure(422,fn ()=>(new MinimalLeadReadController())->summary($request,self::REF));
        }
        foreach (['HEAD','POST'] as $method) {
            $request = Request::create('/agentes/leads/'.self::REF.'/resumo',$method);
            $this->assertHttpFailure(405,fn ()=>(new MinimalLeadReadController())->summary($request,self::REF));
        }
        $request = Request::create('/agentes/leads/'.self::REF.'/resumo','GET',[],[],[],[],'{}');
        $this->assertHttpFailure(422,fn ()=>(new MinimalLeadReadController())->summary($request,self::REF));
        $request = Request::create('/agentes/leads/'.self::REF.'/resumo');
        $request->headers->set('Authorization','Bearer UNTRUSTED');
        $this->assertHttpFailure(400,fn ()=>(new MinimalLeadReadController())->summary($request,self::REF));
        putenv('CRM_PRIVATE_OP01');
        $request = Request::create('/agentes/leads/'.self::REF.'/resumo');
        $this->assertHttpFailure(404,fn ()=>(new MinimalLeadReadController())->summary($request,self::REF));
        self::assertSame(0,$resolved);
    }

    public function test_summary_renders_from_only_the_projection_without_unrelated_flash_content(): void
    {
        $this->app['session.store']->flash('notice','UNRELATED_PRIVATE_NOTICE');
        $errors = new \Illuminate\Support\ViewErrorBag();
        $errors->put('default',new \Illuminate\Support\MessageBag(['field'=>'UNRELATED_PRIVATE_ERROR']));
        $this->app['view']->share('errors',$errors);

        foreach (['pt'=>['pt-BR','Ganho'],'en'=>['en','Won'],'es'=>['es','Ganado']] as $language=>[$htmlLanguage,$stage]) {
            $html = view('crm-private-journey',[
                'screen'=>'minimal-lead','language'=>$language,
                'data'=>['projection'=>['ref'=>self::REF,'stage'=>'won']],
            ])->render();
            self::assertStringContainsString('<html lang="'.$htmlLanguage.'">',$html);
            self::assertStringContainsString('data-minimal-lead-ref="'.self::REF.'">'.self::REF,$html);
            self::assertStringContainsString('data-minimal-lead-stage="won">'.$stage,$html);
            self::assertStringContainsString('/painel?section=leads&amp;lang='.$language,$html);
            self::assertStringContainsString('action="/logout"',$html);
            foreach (['UNRELATED_PRIVATE_NOTICE','UNRELATED_PRIVATE_ERROR','data-lead-id=',
                'LEAD_TESTE_','EMPRESA_TESTE_','/tarefas','/leads/'.self::REF.'"'] as $unrelated) {
                self::assertStringNotContainsString($unrelated,$html);
            }
        }
    }
}
