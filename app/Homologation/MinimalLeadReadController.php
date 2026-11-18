<?php

declare(strict_types=1);

namespace App\Homologation;

use Illuminate\Http\Request;

/** Private human session route; this is not a service-identity ingress. */
final class MinimalLeadReadController
{
    public function __invoke(Request $request,string $id)
    {
        abort_unless(Environment::minimalLeadReadEnabled(),404);
        // Laravel GET routes also match HEAD; it must not perform the audited read.
        abort_unless($request->getRealMethod()==='GET' && $request->isMethod('GET'),405);
        // The only client selector is the resource in the path. Check raw input
        // so even ignored/empty/duplicate query syntax cannot become a selector.
        abort_if((string)$request->server('QUERY_STRING','')!==''
            || $request->getContent()!=='',422);
        // This route accepts the existing CRM session only. Service credentials
        // or caller-supplied authority hints cannot be combined with that session.
        foreach (['Authorization','X-Tenant-Ref','X-Principal-Ref','X-Principal-Id',
            'X-Organization-Id','X-Organization-Ref','X-Company-Id','X-Company-Ref',
            'X-Aia-Tenant','X-Aia-Principal','X-Aia-Company'] as $header) {
            abort_if($request->headers->has($header),400);
        }

        return response()->json(app(Workspace::class)->minimalLead($id));
    }
}
