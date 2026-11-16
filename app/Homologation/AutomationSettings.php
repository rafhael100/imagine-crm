<?php

declare(strict_types=1);
namespace App\Homologation;
use InvalidArgumentException;

/** Bounded settings for the existing two native templates; never an n8n workflow definition. */
final readonly class AutomationSettings
{
    private function __construct(public int $version,public array $rules,public array $history) {}

    public static function read(string $json): self
    {
        $data=json_decode($json,true,32,JSON_THROW_ON_ERROR);
        if(is_array($data) && array_is_list($data)) { self::rules($data); return new self(0,$data,[]); }
        if(!is_array($data) || ($data['schema']??null)!=='CRM_PRIVATE_AUTOMATION_V1'
            || !is_int($data['version']??null) || $data['version']<1 || $data['version']>20
            || !is_array($data['history']??null) || !array_is_list($data['history'])
            || count($data['history'])!==$data['version']) throw new InvalidArgumentException('INVALID_AUTOMATION_SETTINGS');
        self::rules($data['rules']??null);
        foreach($data['history'] as $index=>$revision) {
            if(!is_array($revision) || ($revision['version']??null)!==$index+1
                || !is_int($revision['at']??null) || !is_string($revision['actor']??null)) throw new InvalidArgumentException('INVALID_AUTOMATION_HISTORY');
            self::rules($revision['rules']??null);
        }
        if($data['history'][count($data['history'])-1]['rules']!==$data['rules']) throw new InvalidArgumentException('AUTOMATION_HISTORY_MISMATCH');
        return new self($data['version'],$data['rules'],$data['history']);
    }

    private static function rules(mixed $rules): void
    {
        if(!is_array($rules) || !array_is_list($rules) || count($rules)>2) throw new InvalidArgumentException('INVALID_NATIVE_RULES');
        foreach($rules as $rule) if(!is_string($rule) || !in_array($rule,['followup_qualified','followup_proposal'],true)) throw new InvalidArgumentException('UNSUPPORTED_NATIVE_RULE');
        if(count(array_unique($rules))!==count($rules)) throw new InvalidArgumentException('DUPLICATE_RULE');
    }

    public function revise(int $expected,array $rules,string $actor,int $now): self
    {
        self::rules($rules);
        if($expected!==$this->version) throw new \LogicException('AUTOMATION_VERSION_CONFLICT');
        if($this->version>=20) throw new \LogicException('LAB_REVISION_LIMIT');
        if($actor==='' || strlen($actor)>64 || $now<0) throw new InvalidArgumentException('INVALID_SERVER_AUDIT');
        $rules=array_values($rules);sort($rules);
        $version=$this->version+1;
        return new self($version,$rules,[...$this->history,['version'=>$version,'rules'=>$rules,'actor'=>$actor,'at'=>$now]]);
    }

    public function json(): string
    {
        return json_encode(['schema'=>'CRM_PRIVATE_AUTOMATION_V1','version'=>$this->version,'rules'=>$this->rules,'history'=>$this->history],JSON_THROW_ON_ERROR);
    }
}
