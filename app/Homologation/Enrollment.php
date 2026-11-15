<?php

declare(strict_types=1);

namespace App\Homologation;

use App\Core\Identity\OfflineCnpjChecksumInspector;
use App\Core\Identity\OfflineSignupOtpChallenge;
use App\Core\Identity\Totp;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Fictional enrollment; private in-browser OTP delivery, never SMTP/SMS. */
final readonly class Enrollment
{
    public function __construct(private Connection $db, private Encrypter $crypto,
        private Hasher $hasher, private Session $session) {}

    public static function documents(): array
    {
        $out=[];
        for ($i=1;$i<=50;$i++) {
            $base='99999999'.str_pad((string)$i,4,'0',STR_PAD_LEFT);
            foreach ([[5,4,3,2,9,8,7,6,5,4,3,2],[6,5,4,3,2,9,8,7,6,5,4,3,2]] as $weights) {
                $sum=0; foreach ($weights as $j=>$weight) $sum+=(int)$base[$j]*$weight;
                $remainder=$sum%11; $base.=(string)($remainder<2?0:11-$remainder);
            }
            $out[]=$base;
        }
        return $out;
    }

    private function key(string $domain): string
    {
        return hash_hmac('sha256','crm-private|'.$domain,(string)config('app.key'));
    }

    private function binding(): string { return hash_hmac('sha256',$this->session->getId(),$this->key('session')); }

    public function issue(#[\SensitiveParameter] array $input): void
    {
        Environment::assertConnection($this->db,'enrollment');
        $login=$input['login']??''; $password=$input['password']??'';
        $cnpj=$input['cnpj']??''; $label=$input['company']??'';
        if (!is_string($login) || preg_match('/\Alab-[a-z0-9-]{3,48}\z/D',$login)!==1
            || !is_string($password) || strlen($password)<12 || strlen($password)>72
            || !is_string($cnpj) || !in_array($cnpj,self::documents(),true)
            || !(new OfflineCnpjChecksumInspector())->inspect($cnpj)['check_digits_valid']
            || !is_string($label) || preg_match('/\AEMPRESA_TESTE_[A-Z0-9_]{1,40}\z/D',$label)!==1
            || ($input['email']??null)!==$login.'@example.test'
            || ($input['phone']??null)!=='+5500000000000'
            || ($input['terms']??null)!=='simulated') {
            self::invalid('Use exclusivamente os dados fictícios indicados e aceite os termos simulados.');
        }
        $contact=hash_hmac('sha256',$login.'@example.test',$this->key('contact'));
        $secret=''; $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        for($i=0;$i<32;$i++) $secret.=$alphabet[random_int(0,31)];
        $payload=['login'=>$login,'password_hash'=>$this->hasher->make($password),
            'cnpj'=>$cnpj,'label'=>$label,'factor'=>$secret,'plan'=>in_array($input['plan']??'basic',['basic','essential','professional','enterprise'],true)?($input['plan']??'basic'):'basic'];
        $this->issuePayload($payload,$contact);
    }

    private function issuePayload(array $payload,string $contact): void
    {
        $issued=(new OfflineSignupOtpChallenge())->issue($this->key('otp'),$contact,'email',time());
        $nonce=$this->db->transaction(function () use ($contact,$payload,$issued) {
            $this->db->selectOne('SELECT pg_advisory_xact_lock(hashtextextended(?::text,0))',['crm_private_signup|'.$contact]);
            if ($this->db->table('public.crm_private_signup_requests')->where('contact_binding',$contact)
                ->where('issued_at','>',time()-900)->count()>=3) return null;
            // A resend supersedes earlier challenges, including partially verified setup.
            $this->db->table('public.crm_private_signup_requests')->where('contact_binding',$contact)
                ->whereNull('completed_at')->update(['completed_at'=>time()]);
            $c=$issued['challenge'];
            $this->db->table('public.crm_private_signup_requests')->insert($c+[
                'contact_binding'=>$contact,'session_binding'=>$this->binding(),
                'payload_ciphertext'=>$this->crypto->encryptString(json_encode($payload)),
                'delivery_ciphertext'=>$this->crypto->encryptString($issued['delivery_code'])]);
            return $c['nonce'];
        });
        if (!$nonce) self::invalid('Limite de três emissões em 15 minutos. Aguarde para tentar novamente.');
        $this->session->put('crm.private.signup',$nonce);
    }

    public function resend(): void
    {
        Environment::assertConnection($this->db,'enrollment');
        $row=$this->request();
        abort_unless($row,404);
        $payload=json_decode($this->crypto->decryptString($row->payload_ciphertext),true,512,JSON_THROW_ON_ERROR);
        $this->issuePayload($payload,$row->contact_binding);
    }

    public function suggestedDocument(): string
    {
        Environment::assertConnection($this->db,'enrollment');
        $keys=json_decode((string)getenv('CRM_PRIVATE_TRIAL_KEYS'),true,512,JSON_THROW_ON_ERROR);
        abort_unless(is_array($keys) && count($keys)>0,503);
        foreach(self::documents() as $document) {
            $used=false;
            foreach($keys as $version=>$key) {
                if (!is_string($key) || strlen($key)<32) abort(503);
                $used=$used || $this->db->table('public.crm_private_trial_claims')->where('key_version',(int)$version)
                    ->where('cnpj_hmac',hash_hmac('sha256','private-trial|'.$document,$key))->exists();
            }
            if (!$used) return $document;
        }
        return self::documents()[0];
    }

    private function request(bool $lock=false): ?object
    {
        $nonce=$this->session->get('crm.private.signup');
        if (!is_string($nonce) || preg_match('/\A[a-f0-9]{32}\z/D',$nonce)!==1) return null;
        $q=$this->db->table('public.crm_private_signup_requests')->where('nonce',$nonce)
            ->where('session_binding',$this->binding())->whereNull('completed_at');
        return ($lock?$q->lockForUpdate():$q)->first();
    }

    public function delivery(): array
    {
        Environment::assertConnection($this->db,'enrollment');
        $row=$this->request();
        abort_unless($row && (int)$row->expires_at>time(),404);
        return ['code'=>$this->crypto->decryptString($row->delivery_ciphertext),
            'verified'=>$row->consumed_at!==null,'expires_at'=>(int)$row->expires_at];
    }

    public function verify(string $candidate): void
    {
        Environment::assertConnection($this->db,'enrollment');
        if (!preg_match('/\A[0-9]{6}\z/D',$candidate)) self::invalid('Informe seis dígitos.');
        $ok=$this->db->transaction(function () use ($candidate) {
            $row=$this->request(true);
            if (!$row) return false;
            $answer=(new OfflineSignupOtpChallenge())->inspect([
                'nonce'=>$row->nonce,'digest'=>$row->digest,'issued_at'=>(int)$row->issued_at,
                'expires_at'=>(int)$row->expires_at],$candidate,$this->key('otp'),$row->contact_binding,
                'email',time(),(int)$row->attempts_used,$row->consumed_at!==null);
            if ($row->consumed_at!==null || (int)$row->attempts_used>=5) return false;
            $values=['attempts_used'=>(int)$row->attempts_used+1];
            if ($answer['candidate_matches']) $values['consumed_at']=time();
            $this->db->table('public.crm_private_signup_requests')->where('nonce',$row->nonce)->update($values);
            return $answer['candidate_matches'];
        });
        if (!$ok) self::invalid('Código inválido, expirado ou já utilizado.');
    }

    public function factorSetup(): array
    {
        Environment::assertConnection($this->db,'enrollment');
        $row=$this->request();
        abort_unless($row && $row->consumed_at!==null && (int)$row->expires_at>time(),403);
        $payload=json_decode($this->crypto->decryptString($row->payload_ciphertext),true,512,JSON_THROW_ON_ERROR);
        return ['secret'=>$payload['factor'],'login'=>$payload['login']];
    }

    /** Complete key history is mandatory; missing/changed historical keys deny enrollment. */
    private function trialKeys(): array
    {
        $keys=json_decode((string)getenv('CRM_PRIVATE_TRIAL_KEYS'),true,512,JSON_THROW_ON_ERROR);
        if (!is_array($keys) || count($keys)<1 || count($keys)>10) self::invalid('Configuração do trial indisponível.');
        $normalized=[];
        foreach($keys as $version=>$secret) {
            if (!ctype_digit((string)$version) || (int)$version<1 || !is_string($secret) || strlen($secret)<32) self::invalid('Configuração do trial indisponível.');
            $normalized[(int)$version]=$secret;
        }
        ksort($normalized);
        foreach ($this->db->table('public.crm_private_key_versions')->get() as $known) {
            if (!isset($normalized[(int)$known->version])
                || !hash_equals($known->fingerprint,hash('sha256',$normalized[(int)$known->version]))) {
                self::invalid('Histórico de chaves do trial incompleto.');
            }
        }
        foreach($normalized as $version=>$secret) {
            $this->db->table('public.crm_private_key_versions')->insertOrIgnore(['version'=>$version,'fingerprint'=>hash('sha256',$secret)]);
        }
        return $normalized;
    }

    public function complete(string $candidate): array
    {
        Environment::assertConnection($this->db,'enrollment');
        if (!preg_match('/\A[0-9]{6}\z/D',$candidate)) self::invalid('Informe o código do autenticador.');
        $result=$this->db->transaction(function () use ($candidate) {
            $row=$this->request(true);
            if (!$row || $row->consumed_at===null || (int)$row->expires_at<=time() || (int)$row->mfa_attempts>=5) return null;
            $p=json_decode($this->crypto->decryptString($row->payload_ciphertext),true,512,JSON_THROW_ON_ERROR);
            $this->db->table('public.crm_private_signup_requests')->where('nonce',$row->nonce)->update(['mfa_attempts'=>(int)$row->mfa_attempts+1]);
            $step=(new Totp())->matchStep($p['factor'],$candidate,time());
            if ($step===null) return null;
            $this->db->selectOne('SELECT pg_advisory_xact_lock(hashtextextended(?::text,0))',['crm_private_trial_keys']);
            $keys=$this->trialKeys();
            $aliases=[];
            foreach ($keys as $version=>$key) {
                $hash=hash_hmac('sha256','private-trial|'.$p['cnpj'],$key);
                if ($this->db->table('public.crm_private_trial_claims')->where('key_version',$version)->where('cnpj_hmac',$hash)->exists()) {
                    return ['duplicate'=>true];
                }
                $aliases[]=[$version,$hash];
            }
            if ($this->db->table('public.crm_auth_credentials')->where('login_key',hash('sha256',$p['login']))->exists()) return ['duplicate'=>true];
            $id=(string)Str::uuid(); $org=(string)Str::uuid(); $now=time();
            $this->db->selectOne("SELECT set_config('app.tenant_id',?,true)",[$org]);
            $this->db->table('public.crm_global_identities')->insert(['id'=>$id]);
            $this->db->table('public.crm_organizations')->insert(['id'=>$org]);
            $this->db->table('public.crm_auth_credentials')->insert(['identity_id'=>$id,'login_key'=>hash('sha256',$p['login']),
                'password_hash'=>$p['password_hash'],'status'=>'active','verified_at'=>$now,'version'=>1,
                'factor_ciphertext'=>$this->crypto->encryptString($p['factor']),'factor_confirmed_at'=>$now,'last_totp_step'=>$step]);
            $this->db->table('public.crm_memberships')->insert(['id'=>(string)Str::uuid(),'organization_id'=>$org,
                'global_identity_id'=>$id,'status'=>'active','version'=>1]);
            $this->db->table('public.crm_private_accounts')->insert(['identity_id'=>$id,'organization_id'=>$org,'label'=>$p['label'],'created_at'=>$now,'desired_plan'=>$p['plan']??'basic']);
            $this->db->table('public.crm_private_trials')->insert(['organization_id'=>$org,'started_at'=>$now,'expires_at'=>$now+1209600]);
            $this->db->table('public.crm_private_preferences')->insert(['organization_id'=>$org]);
            foreach($aliases as [$version,$hash]) $this->db->table('public.crm_private_trial_claims')->insert(['key_version'=>$version,'cnpj_hmac'=>$hash,'organization_id'=>$org]);
            $codes=[];
            for($i=0;$i<5;$i++) {
                $code=bin2hex(random_bytes(12)); $codes[]=$code;
                $this->db->table('public.crm_auth_recovery_codes')->insert(['id'=>(string)Str::uuid(),'identity_id'=>$id,
                    'code_hash'=>$this->hasher->make($code),'credential_version'=>1]);
            }
            $this->db->table('public.crm_private_signup_requests')->where('nonce',$row->nonce)->update(['completed_at'=>$now]);
            return ['login'=>$p['login'],'recovery_codes'=>$codes];
        });
        if (!$result) self::invalid('Código MFA inválido, expirado ou limite de tentativas atingido.');
        if (isset($result['duplicate'])) self::invalid('Empresa ou identificador já utilizado. Um trial por CNPJ.');
        $this->session->forget('crm.private.signup');
        return $result;
    }

    private static function invalid(string $message): never
    {
        throw ValidationException::withMessages(['signup'=>$message]);
    }

    public function recover(string $login,string $code,string $password): array
    {
        Environment::assertConnection($this->db,'enrollment');
        if (!preg_match('/\Alab-[a-z0-9-]{3,48}\z/D',$login)
            || !preg_match('/\A[a-f0-9]{24}\z/D',$code) || strlen($password)<12 || strlen($password)>72) {
            self::invalid('Dados de recuperação inválidos.');
        }
        $result=$this->db->transaction(function () use ($login,$code,$password) {
            $key=hash('sha256',$login); $window=intdiv(time(),900);
            $this->db->selectOne('SELECT pg_advisory_xact_lock(hashtextextended(?::text,0))',['crm_private_recovery|'.$key]);
            $this->db->table('public.crm_private_recovery_attempts')->insertOrIgnore(['login_key'=>$key,'window'=>$window]);
            $q=$this->db->table('public.crm_private_recovery_attempts')->where('login_key',$key)->where('window',$window);
            $attempt=(clone $q)->lockForUpdate()->first();
            if ((int)$attempt->attempts>=5) return null;
            $q->update(['attempts'=>(int)$attempt->attempts+1]);
            $credential=$this->db->table('public.crm_auth_credentials')->where('login_key',$key)->lockForUpdate()->first();
            if (!$credential || $credential->status!=='active') {
                $this->hasher->check($code,'$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
                return null;
            }
            $match=null;
            foreach($this->db->table('public.crm_auth_recovery_codes')->where('identity_id',$credential->identity_id)
                ->where('credential_version',$credential->version)->whereNull('consumed_at')->lockForUpdate()->limit(5)->get() as $row) {
                if ($this->hasher->check($code,$row->code_hash)) $match=$row;
            }
            if (!$match) return null;
            $this->db->table('public.crm_auth_recovery_codes')->where('id',$match->id)->update(['consumed_at'=>time()]);
            $version=(int)$credential->version+1;
            $this->db->table('public.crm_auth_credentials')->where('identity_id',$credential->identity_id)
                ->update(['password_hash'=>$this->hasher->make($password),'version'=>$version]);
            $this->db->table('public.crm_auth_sessions')->where('identity_id',$credential->identity_id)->update(['state'=>'revoked']);
            $codes=[];
            for($i=0;$i<5;$i++) {
                $fresh=bin2hex(random_bytes(12)); $codes[]=$fresh;
                $this->db->table('public.crm_auth_recovery_codes')->insert(['id'=>(string)Str::uuid(),
                    'identity_id'=>$credential->identity_id,'code_hash'=>$this->hasher->make($fresh),'credential_version'=>$version]);
            }
            return $codes;
        });
        if (!$result) self::invalid('Recuperação negada ou limite de tentativas atingido.');
        return $result;
    }
}
