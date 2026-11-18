<?php

declare(strict_types=1);

namespace App\Homologation;

use Illuminate\Http\Request;

/** Existing CRM human session only. The browser cannot supply the commercial payload. */
final class OwnerRuleDraftController
{
    private function admit(Request $request,bool $write): void
    {
        abort_unless(Environment::ownerDraftsEnabled(),404);
        $method=$write?'POST':'GET';
        abort_unless($request->getRealMethod()===$method && $request->getMethod()===$method,405);
        $query=(string)$request->server('QUERY_STRING','');
        abort_if($write?$query!=='':($query!=='' && preg_match('/\Alang=(?:pt|en|es)\z/D',$query)!==1),422);
        foreach (['Authorization','X-Tenant-Ref','X-Principal-Ref','X-Principal-Id','X-Organization-Id',
            'X-Organization-Ref','X-Company-Id','X-Company-Ref','X-Aia-Tenant','X-Aia-Principal','X-Aia-Company',
            'X-HTTP-Method-Override'] as $header) abort_if($request->headers->has($header),400);
        if ($write) {
            // The existing web middleware verifies this CSRF token. Exact raw
            // syntax also rejects duplicate keys, method overrides and selectors.
            abort_unless(preg_match('/\Aapplication\/x-www-form-urlencoded(?:;\s*charset=UTF-8)?\z/iD',(string)$request->header('Content-Type'))===1
                && preg_match('/\A_token=[A-Za-z0-9]{40}\z/D',$request->getContent())===1
                && array_keys($request->request->all())===['_token'],422);
        } else {
            abort_if($request->getContent()!=='',422);
        }
    }

    public function create(Request $request,string $id)
    {
        $this->admit($request,true);
        $result=app(Workspace::class)->createOwnerRuleDraft($id);
        return redirect('/agentes/rascunhos/'.$result['draft_ref'],303);
    }

    public function retry(Request $request,string $id)
    {
        $this->admit($request,true);
        $result=app(Workspace::class)->retryOwnerRuleDraft($id);
        return redirect('/agentes/rascunhos/'.$result['draft_ref'],303);
    }

    public function show(Request $request,string $id)
    {
        $this->admit($request,false);
        $result=app(Workspace::class)->readOwnerRuleDraft($id);
        $language=$request->query('lang',$request->session()->get('crm.private.lang','pt'));
        if (!in_array($language,['pt','en','es'],true)) $language='pt';
        $request->session()->put('crm.private.lang',$language);
        return response()->view('crm-private-journey',[
            'screen'=>'owner-draft','language'=>$language,'data'=>['draft'=>$result],
        ],$result['available']?200:503);
    }
}
