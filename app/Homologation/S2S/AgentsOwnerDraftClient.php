<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use App\Homologation\Environment;
use Illuminate\Support\Str;
use RuntimeException;

/** Fixed private mTLS destination. No human cookies, sessions or request headers are forwarded. */
final class AgentsOwnerDraftClient
{
    public function exchange(array $business,string $actor,string $operation,string $keyId,int $deadline): array
    {
        if (!Environment::ownerDraftsEnabled() || !in_array($operation,[OwnerDraftContract::CREATE,OwnerDraftContract::READ],true)
            || !OwnerDraftContract::uuid($actor) || preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D',$keyId)!==1) {
            throw new RuntimeException('OWNER_DRAFT_TRANSPORT_DENIED');
        }
        $business=OwnerDraftContract::business($business);
        if ($operation===OwnerDraftContract::CREATE && $actor!==$business['creator_id']) throw new RuntimeException('OWNER_DRAFT_AUTHOR_DENIED');
        $kind=$operation===OwnerDraftContract::CREATE?'create':'read';
        $key=@file_get_contents('/run/secrets/crm_owner_draft_'.$kind.'_key');
        if (!OwnerDraftContract::hash($key)) throw new RuntimeException('OWNER_DRAFT_TRANSPORT_UNAVAILABLE');
        $now=time();$expires=min($now+30,$deadline,4102444800);
        if ($expires<=$now || $business['source_created_at']>$now) throw new RuntimeException('OWNER_DRAFT_WINDOW_EXPIRED');
        $authorization=['schema'=>OwnerDraftContract::AUTH,'issuer'=>'imagine-crm','audience'=>'imagine-ai-agents',
            'operation'=>$operation,'company_id'=>$business['company_id'],'actor_role'=>'owner','actor_id'=>$actor,
            'draft_ref'=>$business['draft_ref'],'business_hash'=>OwnerDraftContract::digest($business),
            'nonce'=>(string)Str::uuid(),'issued_at'=>$now,'expires_at'=>$expires,'key_id'=>$keyId];
        $authorization['mac']=OwnerDraftContract::mac($authorization,$key);
        $body=['authorization'=>$authorization];
        if ($kind==='create') $body['business']=$business;
        $context=stream_context_create([
            'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false,'peer_name'=>'agents-s2s',
                'cafile'=>'/certs/ca.crt','local_cert'=>'/certs/crm.crt','local_pk'=>'/certs/crm.key'],
            'http'=>['method'=>'POST','header'=>"Content-Type: application/json\r\nAccept: application/json\r\nConnection: close\r\n",
                'content'=>OwnerDraftContract::canonical($body),'timeout'=>3,'follow_location'=>0,'max_redirects'=>0,'ignore_errors'=>true],
        ]);
        // The length cap also bounds memory on an untrusted remote response.
        $raw=@file_get_contents('https://agents-s2s:8443/private/owner-drafts/'.$kind,false,$context,0,8193);
        if (!is_string($raw) || strlen($raw)>8192 || !preg_match('/\AHTTP\/\S+ 200(?: |$)/',$http_response_header[0]??'')) {
            throw new RuntimeException('OWNER_DRAFT_TRANSPORT_UNAVAILABLE');
        }
        try { return OwnerDraftContract::response($raw,$authorization,$business,$key,time()); }
        catch (\Throwable $error) { throw new RuntimeException('OWNER_DRAFT_RESPONSE_DENIED',0,$error); }
    }
}
