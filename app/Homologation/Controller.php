<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Identity\ExperimentalAuthenticator;
use App\Core\Identity\GuardPrincipalSource;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class Controller
{
    private function page(Request $request,string $screen,array $data=[])
    {
        $language=$request->query('lang',$request->session()->get('crm.private.lang','pt'));
        if (!in_array($language,['pt','en','es'],true)) $language='pt';
        $request->session()->put('crm.private.lang',$language);
        return response()->view('crm-private-journey',compact('screen','language','data'));
    }

    private function enrollment(): Enrollment { return app(Enrollment::class); }
    private function auth(): ExperimentalAuthenticator { return app(ExperimentalAuthenticator::class); }

    public function home(Request $r) {
        return $this->page($r,'home',[
            'opportunity_scenario'=>OpportunityScenario::fromQuery($r->query()),
        ]);
    }
    public function signup(Request $r) {
        $plan=$r->query('plan',$r->session()->get('crm.private.plan','basic'));
        if (!in_array($plan,['basic','essential','professional','enterprise'],true)) $plan='basic';
        $r->session()->put('crm.private.plan',$plan);
        $r->session()->put('crm.private.suggested_document',$this->enrollment()->suggestedDocument());
        return $this->page($r,'signup',['documents'=>Enrollment::documents()]);
    }
    public function issue(Request $r)
    {
        $r->validate(['login'=>'required|string|max:52','password'=>'required|string|min:12|max:72',
            'company'=>'required|string|max:64','cnpj'=>'required|string|max:14',
            'email'=>'required|string|max:80','phone'=>'required|string|max:20','terms'=>'required|string','plan'=>'nullable|in:basic,essential,professional,enterprise']);
        $this->enrollment()->issue($r->only(['login','password','company','cnpj','email','phone','terms','plan']));
        return redirect('/cadastro/codigo',303);
    }
    public function otp(Request $r) { return $this->page($r,'otp',$this->enrollment()->delivery()); }
    public function resend(Request $r) { $this->enrollment()->resend(); return redirect('/cadastro/codigo',303); }
    public function verify(Request $r)
    {
        $r->validate(['code'=>'required|string|size:6']);
        $this->enrollment()->verify($r->string('code')->toString());
        return redirect('/cadastro/mfa',303);
    }
    public function setup(Request $r) { return $this->page($r,'setup',$this->enrollment()->factorSetup()); }
    public function complete(Request $r)
    {
        $r->validate(['code'=>'required|string|size:6']);
        $result=$this->enrollment()->complete($r->string('code')->toString());
        return $this->page($r,'registered',$result);
    }
    public function login(Request $r) { return $this->page($r,'login'); }
    public function password(Request $r)
    {
        $r->validate(['login'=>'required|string|max:160','password'=>'required|string|max:72']);
        try { $this->auth()->begin($r->string('login')->toString(),$r->string('password')->toString()); }
        catch(AuthenticationException) { return redirect('/login',303)->withErrors(['login'=>'Acesso negado. Verifique os dados ou aguarde antes de tentar novamente.']); }
        return redirect('/mfa',303);
    }
    public function mfa(Request $r) { return $this->page($r,'mfa'); }
    public function factor(Request $r)
    {
        $r->validate(['code'=>'required|string|max:32','recovery'=>'nullable|in:1']);
        try {
            $this->auth()->completeMfa($r->string('code')->toString(),$r->input('recovery')==='1');
            $role=app(GuardPrincipalSource::class)->withinCurrent(function ($principal) {
                $account=DB::connection('crm_iam')->table('public.crm_private_accounts')
                    ->where('identity_id',$principal->principalId)->first();
                abort_unless($account && $account->status==='active',403);
                return $account->role;
            });
        } catch(AuthenticationException) { return redirect('/mfa',303)->withErrors(['code'=>'Fator inválido, já usado ou sessão expirada.']); }
        return redirect($role==='admin'?'/admin':'/painel',303);
    }
    public function logout(Request $r)
    {
        $this->auth()->logout();
        return redirect('/login',303)->with('notice','Sessão encerrada.');
    }
    public function recovery(Request $r) { return $this->page($r,'recovery'); }
    public function recover(Request $r)
    {
        $r->validate(['login'=>'required|string|max:52','code'=>'required|string|max:32','password'=>'required|string|min:12|max:72']);
        $codes=$this->enrollment()->recover($r->string('login')->toString(),$r->string('code')->toString(),$r->string('password')->toString());
        return $this->page($r,'recovered',['recovery_codes'=>$codes]);
    }
    public function dashboard(Request $r) { return $this->page($r,'workspace',app(Workspace::class)->overview()); }
    public function leads(Request $r)
    {
        $r->validate(['title'=>'required|string|max:64','value_cents'=>'required|integer|min:0|max:100000000','funnel'=>'nullable|uuid']);
        app(Workspace::class)->createLeads([$r->only(['title','value_cents','funnel'])]);
        return redirect('/painel?section=leads',303)->with('notice','Lead fictício persistido.');
    }
    public function lead(Request $r,string $id)
    {
        $r->validate(['version'=>'required|integer|min:1','stage'=>'nullable|string|max:16','title'=>'nullable|string|max:64']);
        app(Workspace::class)->updateLead($id,$r->integer('version'),array_filter($r->only(['stage','title']),fn($v)=>$v!==null&&$v!==''));
        return redirect('/painel?section=kanban',303)->with('notice','Alteração persistida.');
    }
    public function tasks(Request $r)
    {
        $r->validate(['lead'=>'required|uuid','title'=>'required|string|max:64','due'=>'required|date_format:Y-m-d']);
        app(Workspace::class)->createTask($r->string('lead')->toString(),$r->string('title')->toString(),$r->string('due')->toString());
        return redirect('/painel?section=tasks',303);
    }
    public function task(Request $r,string $id)
    {
        $r->validate(['version'=>'required|integer|min:1']);
        app(Workspace::class)->resolveTask($id,$r->integer('version'));
        return redirect('/painel?section=tasks',303);
    }
    public function preferences(Request $r)
    {
        $r->validate(['automation_version'=>'required|integer|min:0|max:19','language'=>'required|in:pt,en,es','rules'=>'nullable|array|max:2','rules.*'=>'string|in:followup_qualified,followup_proposal']);
        app(Workspace::class)->preferences($r->string('language')->toString(),$r->input('rules',[]),$r->integer('automation_version'));
        $r->session()->put('crm.private.lang',$r->input('language'));
        return redirect('/painel?section=settings',303);
    }
    public function order(Request $r)
    {
        $r->validate(['plan'=>'required|string','method'=>'required|string','key'=>'required|uuid']);
        app(Workspace::class)->order($r->string('plan')->toString(),$r->string('method')->toString(),$r->string('key')->toString());
        return redirect('/painel?section=billing',303);
    }
    public function payment(Request $r,string $id)
    {
        $r->validate(['state'=>'required|string|max:16','version'=>'required|integer|min:1']);
        app(Workspace::class)->simulatePayment($id,$r->string('state')->toString(),$r->integer('version'));
        return redirect('/painel?section=billing',303);
    }
    public function export(Request $r)
    {
        $model=app(Workspace::class)->overview();
        return response()->streamDownload(function () use ($model) {
            $out=fopen('php://output','w'); fputcsv($out,['title','stage','value_cents'],',','"','');
            foreach($model['leads'] as $lead) fputcsv($out,[$lead->title,$lead->stage,$lead->value_cents],',','"','');
            fclose($out);
        },'leads-ficticios.csv',['Content-Type'=>'text/csv; charset=UTF-8']);
    }
    public function import(Request $r)
    {
        $r->validate(['csv'=>'required|file|max:20']);
        $file=fopen($r->file('csv')->getRealPath(),'r');
        try {
            $header=fgetcsv($file,1024,',','"','');
            abort_unless(in_array($header,[['title','value_cents'],['title','stage','value_cents']],true),422,'Use o CSV exportado ou o cabeçalho title,value_cents.');
            $rows=[];
            while(($row=fgetcsv($file,1024,',','"',''))!==false) {
                abort_unless(count($row)===count($header) && count($rows)<20,422);
                $rows[]=['title'=>$row[0],'value_cents'=>$row[count($header)-1],
                    'stage'=>count($header)===3?$row[1]:'new'];
            }
        } finally { fclose($file); }
        app(Workspace::class)->createLeads($rows,true);
        return redirect('/painel?section=leads',303);
    }
    public function admin(Request $r) { return $this->page($r,'admin',app(Administration::class)->overview()); }
    public function status(Request $r,string $id)
    {
        $r->validate(['status'=>'required|in:active,suspended']);
        app(Administration::class)->status($id,$r->string('status')->toString());
        return redirect('/admin',303);
    }
    public function funnel(Request $r)
    {
        $r->validate(['title'=>'required|string|max:64']);
        app(Workspace::class)->createFunnel($r->input('title'));
        return redirect('/painel?section=funnels',303);
    }
    public function invite(Request $r)
    {
        $token=app(Workspace::class)->invite();
        return $this->page($r,'invitation',['invitation'=>$token]);
    }
    public function invitationForm(Request $r) { return $this->page($r,'invitation-signup'); }
    public function invitationIssue(Request $r)
    {
        $r->validate(['invitation'=>'required|string|max:250','login'=>'required|string|max:52',
            'password'=>'required|string|min:12|max:72','email'=>'required|string|max:80','terms'=>'required|in:simulated']);
        $this->enrollment()->issueInvitation($r->only(['invitation','login','password','email','terms']));
        return redirect('/cadastro/codigo',303);
    }
}
