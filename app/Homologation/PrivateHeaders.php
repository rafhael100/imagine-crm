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
    public static function renderFailure(Throwable $error,Request $request)
    {
        if ($error instanceof AuthenticationException) return redirect('/login',303)->withErrors(['session'=>'Sessão expirada ou revogada. Entre novamente.']);
        if ($error instanceof ValidationException) {
            $path=match($request->path()) {
                'cadastro/reenviar','cadastro/verificar'=>'/cadastro/codigo','cadastro/concluir'=>'/cadastro/mfa',
                'cadastro'=>'/cadastro','convite'=>'/convite','recuperar'=>'/recuperar',default=>'/painel',
            };
            return redirect($path,303)->withErrors($error->errors())
                ->withInput($request->only(['login','company','cnpj','email','phone','title','value_cents']));
        }
        $status=$error instanceof HttpExceptionInterface?$error->getStatusCode():503;
        if ($error instanceof \App\Core\Tenancy\MembershipDenied || $error instanceof \Illuminate\Auth\Access\AuthorizationException) $status=403;
        // Independent error HTML remains available even when a compiled view failed.
        return response('<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Imagine CRM</title><body style="font:16px system-ui;background:#f7f7f8;color:#272727;padding:8vw"><main style="max-width:640px;background:white;border:1px solid #e6e2dc;border-top:5px solid #f9941d;border-radius:18px;padding:32px"><h1>Não foi possível concluir esta operação.</h1><p>Recarregue, revise os dados ou entre novamente. Seu perfil, o estado do registro e as franquias são verificados em cada operação.</p><p>Código '.$status.'</p><a href="/painel">Voltar ao CRM</a> · <a href="/login">Entrar</a></main></body></html>',$status)
            ->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex, nofollow');
    }

    public static function reportFailure(Throwable $error): void
    {
        $causes=[];
        for($i=0;$error && $i<4;$i++,$error=$error->getPrevious()) {
            $item=['type'=>$error::class,'source'=>basename($error->getFile()),'line'=>$error->getLine()];
            if (preg_match('/\A[A-Z0-9]{5}\z/D',(string)$error->getCode())) $item['code']=(string)$error->getCode();
            foreach($error->getTrace() as $frame) {
                if (isset($frame['file']) && str_contains($frame['file'],'/app/Homologation/')) {
                    $item['origin']=basename($frame['file']).':'.($frame['line']??0);break;
                }
            }
            if (preg_match('/Undefined variable \$([A-Za-z0-9_]+)/',$error->getMessage(),$match)) $item['undefined']=$match[1];
            $causes[]=$item;
        }
        logger()->error('CRM_PRIVATE_REQUEST_UNAVAILABLE',['causes'=>$causes]);
    }

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
                'cadastro/reenviar'=>'/cadastro/codigo','cadastro/verificar'=>'/cadastro/codigo','cadastro/concluir'=>'/cadastro/mfa',
                'cadastro'=>'/cadastro','convite'=>'/convite','recuperar'=>'/recuperar',default=>'/painel',
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
