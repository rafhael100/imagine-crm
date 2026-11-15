<?php

declare(strict_types=1);

namespace App\Homologation;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class PrivateHeaders
{
    public function handle(Request $request,Closure $next)
    {
        abort_unless(Environment::enabled() && in_array($request->getHost(),
            ['crm-dev.imaginecomunica.com.br','127.0.0.1','localhost'],true),404);
        // Tenant/role/identity hints are never accepted as scope selectors.
        abort_if($request->hasAny(['tenant','tenant_id','organization_id','role','identity_id'])
            || $request->headers->has('X-Tenant-Id') || $request->headers->has('X-Identity-Id')
            || $request->headers->has('X-Role'),400);
        try { $response=$next($request); }
        catch(AuthenticationException) { $response=redirect('/login',303)->withErrors(['session'=>'Sessão expirada ou revogada. Entre novamente.']); }
        catch(\App\Core\Tenancy\MembershipDenied|\Illuminate\Auth\Access\AuthorizationException) {
            $response=response()->view('crm-private-journey',['screen'=>'error','language'=>'pt','data'=>['status'=>403]],403);
        }
        catch(ValidationException $error) {
            $path=match($request->path()) {
                'cadastro/verificar'=>'/cadastro/codigo','cadastro/concluir'=>'/cadastro/mfa',
                'cadastro'=>'/cadastro','recuperar'=>'/recuperar',default=>'/painel',
            };
            $response=redirect($path,303)->withErrors($error->errors())
                ->withInput($request->only(['login','company','cnpj','email','phone','title','value_cents']));
        }
        catch(HttpExceptionInterface $error) {
            $status=$error->getStatusCode();
            $response=response()->view('crm-private-journey',['screen'=>'error','language'=>'pt','data'=>['status'=>$status]],$status);
        }
        catch(Throwable $error) {
            // Never log request fields, codes, credentials or raw SQL exceptions.
            logger()->error('CRM_PRIVATE_REQUEST_UNAVAILABLE',['type'=>$error::class,
                'source'=>basename($error->getFile()),'line'=>$error->getLine()]);
            $response=response()->view('crm-private-journey',['screen'=>'error','language'=>'pt','data'=>['status'=>503]],503);
        }
        foreach (['Cache-Control'=>'private, no-store, max-age=0','X-Robots-Tag'=>'noindex, nofollow, noarchive',
            'X-Content-Type-Options'=>'nosniff','X-Frame-Options'=>'DENY','Referrer-Policy'=>'no-referrer',
            'Content-Security-Policy'=>"default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'; base-uri 'none'; frame-ancestors 'none'"] as $name=>$value) {
            $response->headers->set($name,$value);
        }
        return $response;
    }
}
