<?php

declare(strict_types=1);

namespace App\Homologation\S2S;

use InvalidArgumentException;

/** Closed data contract. It does not authenticate a human or grant an operation. */
final class OwnerDraftContract
{
    public const CREATE = 'crm.rule.draft.create';
    public const READ = 'crm.rule.draft.read';
    public const BUSINESS = 'CRM_AIA_OWNER_RULE_DRAFT_V1';
    public const AUTH = 'CRM_AIA_OWNER_DRAFT_AUTH_V1';
    public const RESPONSE = 'CRM_AIA_OWNER_DRAFT_RESPONSE_V1';
    private const JSON = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    public static function uuid(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/D',$value)===1;
    }

    public static function hash(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[0-9a-f]{64}\z/D',$value)===1;
    }

    public static function keys(array $value,array $expected): void
    {
        $actual=array_keys($value);sort($actual,SORT_STRING);sort($expected,SORT_STRING);
        if ($actual!==$expected) throw new InvalidArgumentException('OWNER_DRAFT_FIELDS');
    }

    public static function integer(mixed $value,int $minimum,int $maximum): bool
    {
        return is_int($value) && $value >= $minimum && $value <= $maximum;
    }

    public static function canonical(array $value): string
    {
        $normalize=static function (mixed $item) use (&$normalize): mixed {
            if (is_array($item)) {
                if (array_is_list($item)) throw new InvalidArgumentException('OWNER_DRAFT_OBJECT_REQUIRED');
                foreach ($item as $key=>$child) {
                    if (!is_string($key) || preg_match('/\A[a-z_]+\z/D',$key)!==1) throw new InvalidArgumentException('OWNER_DRAFT_KEY');
                    $item[$key]=$normalize($child);
                }
                ksort($item,SORT_STRING);
                return $item;
            }
            if (!is_string($item) && !is_int($item) && !is_bool($item) && $item!==null) throw new InvalidArgumentException('OWNER_DRAFT_VALUE');
            return $item;
        };
        return json_encode($normalize($value),self::JSON);
    }

    public static function business(array $value): array
    {
        self::keys($value,['schema','company_id','draft_ref','creator_id','source_run_id','rule_id',
            'settings_version','lead_id','lead_version','source_created_at','command_hash','projection']);
        foreach (['company_id','draft_ref','creator_id','source_run_id','lead_id'] as $field) {
            if (!self::uuid($value[$field])) throw new InvalidArgumentException('OWNER_DRAFT_REFERENCE');
        }
        if ($value['schema']!==self::BUSINESS || $value['rule_id']!=='followup_qualified'
            || !self::integer($value['settings_version'],0,20)
            || !self::integer($value['lead_version'],1,2147483647)
            || !self::integer($value['source_created_at'],1,4102444800)
            || !self::hash($value['command_hash']) || !is_array($value['projection'])) {
            throw new InvalidArgumentException('OWNER_DRAFT_BUSINESS');
        }
        $projection=$value['projection'];
        self::keys($projection,['action','stage','delay_seconds','due_at']);
        if ($projection['action']!=='task.followup.create' || $projection['stage']!=='qualified'
            || !self::integer($projection['delay_seconds'],0,86400)
            || !is_int($projection['due_at'])
            || $projection['due_at']!==$value['source_created_at']+$projection['delay_seconds']) {
            throw new InvalidArgumentException('OWNER_DRAFT_PROJECTION');
        }
        return $value;
    }

    public static function digest(array $business): string
    {
        return hash('sha256',self::canonical(self::business($business)));
    }

    public static function mac(array $value,string $key,bool $response=false): string
    {
        if (!self::hash($key)) throw new InvalidArgumentException('OWNER_DRAFT_KEY_MATERIAL');
        unset($value['mac']);
        return hash_hmac('sha256',($response?'IMAGINE_CRM_OWNER_DRAFT_RESPONSE_V1':'IMAGINE_CRM_OWNER_DRAFT_REQUEST_V1')."\n".self::canonical($value),$key);
    }

    /** Verify an authenticated response against this exact attempt, never just a draft id. */
    public static function response(string $raw,array $authorization,array $business,string $key,int $now): array
    {
        $value=StrictJson::decodeObject($raw,8192,8);
        self::keys($value,['schema','issuer','audience','operation','company_id','actor_id','draft_ref',
            'business_hash','nonce','status','business','issued_at','expires_at','key_id','mac','executed','operational']);
        if ($value['schema']!==self::RESPONSE || $value['issuer']!=='imagine-ai-agents' || $value['audience']!=='imagine-crm'
            || $value['executed']!==false || $value['operational']!==false) {
            throw new InvalidArgumentException('OWNER_DRAFT_RESPONSE');
        }
        foreach (['operation','company_id','actor_id','draft_ref','business_hash','nonce','key_id'] as $field) {
            if ($value[$field]!==($authorization[$field]??null)) throw new InvalidArgumentException('OWNER_DRAFT_RESPONSE_BINDING');
        }
        if (!self::integer($value['issued_at'],max(1,$now-30),min(4102444800,$now+5))
            || !self::integer($value['expires_at'],$now+1,min(4102444800,$authorization['expires_at']))
            || $value['expires_at']>$value['issued_at']+15 || $value['expires_at']<=$value['issued_at']
            || !self::hash($value['mac']) || !hash_equals(self::mac($value,$key,true),$value['mac'])) {
            throw new InvalidArgumentException('OWNER_DRAFT_RESPONSE_AUTH');
        }
        if ($value['status']==='ABSENT') {
            if ($authorization['operation']!==self::READ || $value['business']!==null) throw new InvalidArgumentException('OWNER_DRAFT_ABSENCE');
        } elseif ($value['status']==='DRAFT' && is_array($value['business'])) {
            if (!hash_equals(self::digest($business),self::digest($value['business']))
                || self::canonical($business)!==self::canonical($value['business'])) {
                throw new InvalidArgumentException('OWNER_DRAFT_CONTENT');
            }
        } else {
            throw new InvalidArgumentException('OWNER_DRAFT_STATUS');
        }
        return $value;
    }
}
