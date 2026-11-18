<?php

declare(strict_types=1);

use App\Core\Identity\Totp;
use App\Homologation\Environment;
use Illuminate\Support\Str;

if (PHP_SAPI!=='cli' || getenv('CRM_PRIVATE_OPERATOR_MODE')!=='tools-only') {
    throw new RuntimeException('Private operator CLI only.');
}
require dirname(__DIR__,2).'/vendor/autoload.php';
foreach (['storage/framework/cache','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache'] as $path) {
    $directory=dirname(__DIR__,2).'/'.$path;
    if (!is_dir($directory) && !mkdir($directory,0700,true) && !is_dir($directory)) {
        throw new RuntimeException('Private tool cache unavailable.');
    }
}
$privateApp=require dirname(__DIR__,2).'/bootstrap/app.php';
$privateApp->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR,'Private operator failed: '.get_class($error).' at '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
});
if (!Environment::enabled()) throw new RuntimeException('Private operator profile required.');

function privateCrmOwner(): PDO
{
    $db=new PDO('pgsql:host=postgres;port=5432;dbname=imagine_crm_private_homologation',
        'crm_homologation_owner',(string)getenv('CRM_PRIVATE_OWNER_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $row=$db->query('SELECT current_database(),current_user')->fetch(PDO::FETCH_NUM);
    if ($row!==['imagine_crm_private_homologation','crm_homologation_owner']
        || $db->query('SELECT mode FROM public.crm_private_installation WHERE id=1')->fetchColumn()!=='synthetic-v1') {
        throw new RuntimeException('Wrong private operator database.');
    }
    return $db;
}

function privateCrmSql(PDO $db,string $sql,array $values=[]): void
{
    $stmt=$db->prepare($sql); $stmt->execute($values);
}

/** Caller owns the transaction. No route, owner credential or CLI entry in the web app. */
function privateCrmSeedAccount(PDO $db,string $login,#[SensitiveParameter] string $password,
    string $role,string $label,?string $factor=null,?int $lastStep=null): array
{
    if (!in_array($role,['client','admin'],true) || !preg_match('/\Alab-[a-z0-9-]{3,48}\z/D',$login)
        || strlen($password)<12 || strlen($password)>72 || !preg_match('/\AEMPRESA_TESTE_[A-Z0-9_]{1,40}\z/D',$label)) {
        throw new RuntimeException('Invalid fictional account.');
    }
    if ($factor===null) {
        $factor='';$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        for($i=0;$i<32;$i++) $factor.=$alphabet[random_int(0,31)];
    }
    $id=(string)Str::uuid();$org=(string)Str::uuid();$now=time();
    privateCrmSql($db,"SELECT set_config('app.tenant_id',?,true)",[$org]);
    privateCrmSql($db,'INSERT INTO crm_global_identities(id) VALUES (?)',[$id]);
    privateCrmSql($db,'INSERT INTO crm_organizations(id) VALUES (?)',[$org]);
    privateCrmSql($db,'INSERT INTO crm_auth_credentials(identity_id,login_key,password_hash,status,verified_at,version,factor_ciphertext,factor_confirmed_at,last_totp_step) VALUES (?,?,?,\'active\',?,1,?,?,?)',
        [$id,hash('sha256',$login),app('hash')->make($password),$now,app('encrypter')->encryptString($factor),$now,$lastStep]);
    privateCrmSql($db,'INSERT INTO crm_memberships(id,organization_id,global_identity_id,status,version) VALUES (?,?,?,\'active\',1)',[(string)Str::uuid(),$org,$id]);
    privateCrmSql($db,'INSERT INTO crm_private_accounts(identity_id,organization_id,label,role,created_at) VALUES (?,?,?,?,?)',[$id,$org,$label,$role,$now]);
    privateCrmSql($db,'INSERT INTO crm_private_trials(organization_id,started_at,expires_at) VALUES (?,?,?)',[$org,$now,$now+1209600]);
    privateCrmSql($db,'INSERT INTO crm_private_preferences(organization_id) VALUES (?)',[$org]);
    privateCrmSql($db,'INSERT INTO crm_private_funnels(id,organization_id,title,created_at) VALUES (?,?,?,?)',[(string)Str::uuid(),$org,'FUNIL_TESTE_PRINCIPAL',$now]);
    $codes=[];
    for($i=0;$i<5;$i++) {
        $code=bin2hex(random_bytes(12));$codes[]=$code;
        privateCrmSql($db,'INSERT INTO crm_auth_recovery_codes(id,identity_id,code_hash,credential_version) VALUES (?,?,?,1)',
            [(string)Str::uuid(),$id,app('hash')->make($code)]);
    }
    return ['login'=>$login,'password'=>$password,'secret'=>$factor,'recovery'=>$codes,'identity'=>$id,'organization'=>$org,'label'=>$label];
}
