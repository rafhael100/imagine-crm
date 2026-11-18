<?php

declare(strict_types=1);

namespace App\Homologation;

use Illuminate\Support\Str;

/** Private invitation selector; database state, expiry and quota remain authoritative. */
final class InvitationToken
{
    public static function issue(string $organization,string $id): string
    {
        $body=$organization.':'.$id.':'.bin2hex(random_bytes(16));
        return rtrim(strtr(base64_encode($body),'+/','-_'),'=').'.'.self::mac($body);
    }

    public static function inspect(string $token): ?array
    {
        if (strlen($token)>250 || !preg_match('/\A([A-Za-z0-9_-]+)\.([a-f0-9]{64})\z/D',$token,$parts)) return null;
        $body=base64_decode(strtr($parts[1],'-_','+/'),true);
        if (!is_string($body) || !hash_equals(self::mac($body),$parts[2])) return null;
        $values=explode(':',$body);
        if (count($values)!==3 || !Str::isUuid($values[0]) || !Str::isUuid($values[1])
            || !preg_match('/\A[a-f0-9]{32}\z/D',$values[2])) return null;
        return ['organization'=>$values[0],'id'=>$values[1],'hash'=>hash('sha256',$token)];
    }

    private static function mac(string $body): string
    {
        return hash_hmac('sha256','crm-private-invitation-v1|'.$body,(string)config('app.key'));
    }
}
