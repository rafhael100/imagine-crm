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
        $language=PrivateUi::captureLanguage($request);
        if ($error instanceof AuthenticationException) return redirect('/login',303)->withErrors(['session'=>PrivateUi::text('Sessão expirada ou revogada. Entre novamente.',$language)]);
        if ($error instanceof ValidationException) {
            $path=match($request->path()) {
                'cadastro/reenviar','cadastro/verificar'=>'/cadastro/codigo','cadastro/concluir'=>'/cadastro/mfa',
                'cadastro'=>'/cadastro','convite'=>'/convite','recuperar'=>'/recuperar',
                'login'=>'/login','mfa'=>'/mfa',default=>'/painel',
            };
            return redirect($path,303)->withErrors(PrivateUi::validationErrors($error,$language))
                ->withInput($request->only(['login','company','cnpj','email','phone','title','value_cents']));
        }
        $status=$error instanceof HttpExceptionInterface?$error->getStatusCode():503;
        if ($error instanceof \App\Core\Tenancy\MembershipDenied || $error instanceof \Illuminate\Auth\Access\AuthorizationException) $status=403;
        // Independent error HTML remains available even when a compiled view failed.
        $htmlLanguage=$language==='pt'?'pt-BR':$language;
        $text=fn(string $source)=>htmlspecialchars(PrivateUi::text($source,$language),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        return response('<!doctype html><html lang="'.$htmlLanguage.'"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Imagine CRM</title><body style="font:16px system-ui;background:#f7f7f8;color:#272727;padding:8vw"><main style="max-width:640px;background:white;border:1px solid #e6e2dc;border-top:5px solid #f9941d;border-radius:18px;padding:32px"><h1>'.$text('Não foi possível concluir esta operação.').'</h1><p>'.$text('Recarregue, revise os dados ou entre novamente. Seu perfil, o estado do registro e as franquias são verificados em cada operação.').'</p><p>'.$text('Código').' '.$status.'</p><a href="/painel">'.$text('Voltar ao CRM').'</a> · <a href="/login">'.$text('Entrar').'</a></main></body></html>',$status)
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
            || $request->headers->has('X-Role')
            // F250: Human-facing routes must never interpret a service
            // proof as a session, membership, MFA or business authorization.
            || $request->headers->has('X-Aia-Claims')
            || $request->headers->has('X-Aia-Signature')
            || $request->headers->has('X-Aia-Key-Id')
            || $request->headers->has('X-Service-Identity'),400);
        // Remember only the presentation preference, including error redirects
        // that happen before Controller::page(). Do not restore it after $next:
        // the preferences action may have saved a different allowed language.
        $language=PrivateUi::captureLanguage($request);
        try { $response=$next($request); }
        catch(AuthenticationException) { $response=redirect('/login',303)->withErrors(['session'=>PrivateUi::text('Sessão expirada ou revogada. Entre novamente.',$language)]); }
        catch(\App\Core\Tenancy\MembershipDenied|\Illuminate\Auth\Access\AuthorizationException) {
            $response=response()->view('crm-private-journey',['screen'=>'error','language'=>$language,'data'=>['status'=>403]],403);
        }
        catch(ValidationException $error) {
            $path=match($request->path()) {
                'cadastro/reenviar'=>'/cadastro/codigo','cadastro/verificar'=>'/cadastro/codigo','cadastro/concluir'=>'/cadastro/mfa',
                'cadastro'=>'/cadastro','convite'=>'/convite','recuperar'=>'/recuperar',
                'login'=>'/login','mfa'=>'/mfa',default=>'/painel',
            };
            $response=redirect($path,303)->withErrors(PrivateUi::validationErrors($error,$language))
                ->withInput($request->only(['login','company','cnpj','email','phone','title','value_cents']));
        }
        catch(HttpExceptionInterface $error) {
            $status=$error->getStatusCode();
            $response=response()->view('crm-private-journey',['screen'=>'error','language'=>$language,'data'=>['status'=>$status]],$status);
        }
        catch(Throwable $error) {
            // Never log request fields, codes, credentials or raw SQL exceptions.
            logger()->error('CRM_PRIVATE_REQUEST_UNAVAILABLE',['type'=>$error::class,
                'source'=>basename($error->getFile()),'line'=>$error->getLine()]);
            $response=response()->view('crm-private-journey',['screen'=>'error','language'=>$language,'data'=>['status'=>503]],503);
        }
        // Preserve the reviewed kernel's JSON 401 outside this private application.
        if ($response->getStatusCode()===401) {
            $response=redirect('/login',303)->withErrors(['session'=>PrivateUi::text('Sessão expirada ou revogada. Entre novamente.',$language)]);
        }
        foreach (['Cache-Control'=>'private, no-store, max-age=0','X-Robots-Tag'=>'noindex, nofollow, noarchive',
            'X-Content-Type-Options'=>'nosniff','X-Frame-Options'=>'DENY','Referrer-Policy'=>'no-referrer',
            'Content-Security-Policy'=>"default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; form-action 'self'; base-uri 'none'; frame-ancestors 'none'"] as $name=>$value) {
            $response->headers->set($name,$value);
        }
        return $response;
    }
}
