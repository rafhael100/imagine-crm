<?php

declare(strict_types=1);

namespace App\Homologation;

use Illuminate\Http\Request;

/** Private human session route; this is not a service-identity ingress. */
final class MinimalLeadReadController
{
    public function __invoke(Request $request,string $id)
    {
        // Content negotiation cannot change the closed machine response.
        return response()->json($this->read($request,$id));
    }

    public function summary(Request $request,string $id)
    {
        // One fresh CRM-authorized read; no overview, full model or API fetch.
        $result=$this->read($request,$id,true);
        $language=$request->query('lang',$request->session()->get('crm.private.lang','pt'));
        if (!in_array($language,['pt','en','es'],true)) $language='pt';
        $request->session()->put('crm.private.lang',$language);

        return response()->view('crm-private-journey',[
            'screen'=>'minimal-lead','language'=>$language,
            'data'=>['projection'=>$result['projection']],
        ]);
    }

    private function read(Request $request,string $id,bool $presentation=false): array
    {
        abort_unless(Environment::minimalLeadReadEnabled(),404);
        // Laravel GET routes also match HEAD; it must not perform the audited read.
        abort_unless($request->getRealMethod()==='GET' && $request->isMethod('GET'),405);
        // Only the HTML page accepts one literal locale parameter. The JSON
        // contract keeps its empty-query rule, including ignored/duplicate syntax.
        $query=(string)$request->server('QUERY_STRING','');
        abort_if(($presentation
            ? ($query!=='' && preg_match('/\Alang=(?:pt|en|es)\z/D',$query)!==1)
            : $query!=='') || $request->getContent()!=='',422);
        // This route accepts the existing CRM session only. Service credentials
        // or caller-supplied authority hints cannot be combined with that session.
        foreach (['Authorization','X-Tenant-Ref','X-Principal-Ref','X-Principal-Id',
            'X-Organization-Id','X-Organization-Ref','X-Company-Id','X-Company-Ref',
            'X-Aia-Tenant','X-Aia-Principal','X-Aia-Company'] as $header) {
            abort_if($request->headers->has($header),400);
        }

        return app(Workspace::class)->minimalLead($id);
    }
}
