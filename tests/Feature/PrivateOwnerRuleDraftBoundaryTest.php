<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Identity\GuardPrincipalSource;
use App\Homologation\Environment;
use App\Homologation\OwnerRuleDraftController;
use App\Homologation\Workspace;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

/** Request admission only. Actual owner/session/grant/SQL/mTLS authority is exercised in the CI harness. */
final class PrivateOwnerRuleDraftBoundaryTest extends TestCase
{
    private const REF='11111111-1111-4111-8111-111111111111';
    private array $saved=[];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['CRM_PRIVATE_OWNER_DRAFTS','CRM_PRIVATE_JOURNEY'] as $name) {
            $this->saved[$name]=getenv($name);putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $name=>$value) putenv($value===false?$name:$name.'='.$value);
        parent::tearDown();
    }

    private function profile(): void
    {
        $connection=['driver'=>'pgsql','database'=>'imagine_crm_private_homologation','host'=>'postgres','port'=>'5432','url'=>null];
        $this->app['env']='staging';
        config(['identity.experimental'=>true,'identity.private_staging_profile'=>'private-homologation-synthetic-v1',
            'app.debug'=>false,'app.url'=>'https://crm-dev.imaginecomunica.com.br','session.driver'=>'redis',
            'identity.abuse_store'=>'redis','database.default'=>'pgsql',
            'database.connections.pgsql'=>$connection+['username'=>'crm_homologation_app','password'=>str_repeat('A',32)],
            'database.connections.crm_iam'=>$connection+['username'=>'crm_homologation_iam','password'=>str_repeat('B',32)]]);
        putenv('CRM_PRIVATE_JOURNEY=synthetic-v1');putenv('CRM_PRIVATE_OWNER_DRAFTS=owner-rule-draft-v1');
    }

    private function denied(int $status,callable $operation): void
    {
        try {$operation();self::fail('Unexpected admission.');}
        catch (HttpExceptionInterface $error) {self::assertSame($status,$error->getStatusCode());}
    }

    public function test_flag_cannot_open_default_application_and_exact_private_profile_is_required(): void
    {
        putenv('CRM_PRIVATE_OWNER_DRAFTS=owner-rule-draft-v1');
        self::assertFalse(Environment::ownerDraftsEnabled());
        $this->get('/agentes/rascunhos/'.self::REF)->assertNotFound();
        $this->profile();self::assertTrue(Environment::ownerDraftsEnabled());
        foreach ([null,'','true','1','owner-rule-draft-v2','owner-rule-draft-v1 '] as $flag) {
            putenv($flag===null?'CRM_PRIVATE_OWNER_DRAFTS':'CRM_PRIVATE_OWNER_DRAFTS='.$flag);
            self::assertFalse(Environment::ownerDraftsEnabled());
        }
        putenv('CRM_PRIVATE_OWNER_DRAFTS=owner-rule-draft-v1');
        foreach (['production','local','testing'] as $env) {$this->app['env']=$env;self::assertFalse(Environment::ownerDraftsEnabled());}
    }

    public function test_hostile_read_input_never_resolves_the_workspace(): void
    {
        $this->profile();$resolved=0;
        $this->app->bind(Workspace::class,static function () use (&$resolved):never {++$resolved;throw new \LogicException('Not an authority.');});
        foreach ([['HEAD','','',405],['POST','','',405],['GET','lang=en&lang=es','',422],
            ['GET','lang=%65n','',422],['GET','company_id=foreign','',422],['GET','lang=en&','',422],['GET','','{}',422]] as [$method,$query,$body,$status]) {
            $request=Request::create('/agentes/rascunhos/'.self::REF,$method,[],[],[],[],$body);
            $request->server->set('QUERY_STRING',$query);
            $this->denied($status,fn()=>(new OwnerRuleDraftController())->show($request,self::REF));
        }
        foreach (['Authorization','X-Company-Ref','X-Principal-Id','X-HTTP-Method-Override'] as $header) {
            $request=Request::create('/agentes/rascunhos/'.self::REF);$request->headers->set($header,'UNTRUSTED');
            $this->denied(400,fn()=>(new OwnerRuleDraftController())->show($request,self::REF));
        }
        self::assertSame(0,$resolved);
    }

    public function test_browser_cannot_supply_rule_author_company_or_duplicate_form_fields(): void
    {
        $this->profile();$resolved=0;
        $this->app->bind(Workspace::class,static function () use (&$resolved):never {++$resolved;throw new \LogicException('Not an authority.');});
        $token=str_repeat('A',40);
        foreach (['_token='.$token.'&creator_id='.self::REF,'_token='.$token.'&company_id='.self::REF,
            '_token='.$token.'&rule_id=followup_qualified','_token='.$token.'&_token='.$token,
            '_token='.$token.'&_method=GET','_token%5B%5D='.$token,'{}','', '_token='.$token.'&'] as $raw) {
            parse_str($raw,$parameters);
            $request=Request::create('/agentes/planejamentos/'.self::REF.'/rascunho','POST',$parameters,[],[],
                ['CONTENT_TYPE'=>'application/x-www-form-urlencoded'],$raw);
            $this->denied(422,fn()=>(new OwnerRuleDraftController())->create($request,self::REF));
            $this->denied(422,fn()=>(new OwnerRuleDraftController())->retry($request,self::REF));
        }
        $request=Request::create('/agentes/planejamentos/'.self::REF.'/rascunho','POST',['_token'=>$token],[],[],
            ['CONTENT_TYPE'=>'application/x-www-form-urlencoded'],'_token='.$token);
        $request->server->set('QUERY_STRING','lang=en');
        $this->denied(422,fn()=>(new OwnerRuleDraftController())->create($request,self::REF));
        self::assertSame(0,$resolved);
    }

    public function test_runtime_flag_is_rechecked_before_any_connection_or_identity(): void
    {
        $this->profile();putenv('CRM_PRIVATE_OWNER_DRAFTS');
        $db=$this->createMock(Connection::class);
        $db->expects(self::never())->method('selectOne');$db->expects(self::never())->method('table');
        $workspace=new Workspace(new GuardPrincipalSource(),$db,$db);
        $this->denied(404,fn()=>$workspace->createOwnerRuleDraft(self::REF));
        $this->denied(404,fn()=>$workspace->retryOwnerRuleDraft(self::REF));
        $this->denied(404,fn()=>$workspace->readOwnerRuleDraft(self::REF));
        $this->denied(404,fn()=>$workspace->ownerDraftReferences());
    }

    public function test_pending_view_does_not_disclose_business_or_unrelated_flash(): void
    {
        $this->app['session.store']->flash('notice','UNRELATED_NOTICE');
        $errors=new \Illuminate\Support\ViewErrorBag();
        $errors->put('default',new \Illuminate\Support\MessageBag(['field'=>'UNRELATED_ERROR']));
        $this->app['view']->share('errors',$errors);
        foreach (['pt'=>'Confirmação pendente','en'=>'Confirmation pending','es'=>'Confirmación pendiente'] as $language=>$label) {
            $html=view('crm-private-journey',['screen'=>'owner-draft','language'=>$language,'data'=>['draft'=>[
                'draft_ref'=>self::REF,'state'=>'pending','business'=>null,'available'=>false]]])->render();
            self::assertStringContainsString($label,$html);
            self::assertStringContainsString('data-owner-draft-ref="'.self::REF.'"',$html);
            self::assertStringNotContainsString('data-owner-draft-creator=',$html);
            self::assertStringNotContainsString('data-owner-draft-lead=',$html);
            self::assertStringNotContainsString('UNRELATED_NOTICE',$html);
            self::assertStringNotContainsString('UNRELATED_ERROR',$html);
        }
    }
}
