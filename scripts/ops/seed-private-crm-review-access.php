<?php

declare(strict_types=1);

require __DIR__.'/private-crm-toolbox.php';
umask(0077);
$file='/private/review-access.json';
if (is_file($file)) throw new RuntimeException('Reviewer access already exists; no implicit credential replacement.');
$db=privateCrmOwner();$db->beginTransaction();$records=[];
try {
    foreach(['admin'=>'admin','client'=>'client'] as $key=>$role) {
        $records[$key]=privateCrmSeedAccount($db,'lab-review-'.$key,bin2hex(random_bytes(16)),$role,
            'EMPRESA_TESTE_IMAGINE_'.strtoupper($key));
    }
    $db->commit();
} catch(Throwable $error) { $db->rollBack();throw new RuntimeException('Private reviewer enrollment failed.'); }
if (file_put_contents($file,json_encode($records,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT),LOCK_EX)===false) {
    throw new RuntimeException('Protected reviewer access directory unavailable.');
}
chmod($file,0600);
echo "Client and administrator reviewer access prepared in the protected operator directory; no credentials displayed.\n";
