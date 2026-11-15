<?php

declare(strict_types=1);

try {
require __DIR__.'/private-crm-toolbox.php';
$key=base64_decode((string)getenv('CRM_PRIVATE_BACKUP_KEY'),true);
if (!is_string($key) || strlen($key)!==32) throw new RuntimeException('Independent backup key required.');
$input=stream_get_contents(STDIN,67108865);
if (!is_string($input) || strlen($input)>67108864) throw new RuntimeException('Backup exceeds private limit.');
$crypto=new Illuminate\Encryption\Encrypter($key,'AES-256-CBC');
if (($argv[1]??'')==='encrypt') echo $crypto->encryptString($input);
elseif (($argv[1]??'')==='decrypt') {
    $decoded=base64_decode($input,true);
    if (!is_string($decoded) || base64_encode($decoded)!==$input) {
        throw new RuntimeException('Noncanonical encrypted archive.');
    }
    echo $crypto->decryptString($input);
}
else throw new RuntimeException('Explicit backup mode required.');
} catch (Throwable $error) {
    fwrite(STDERR, 'Private backup crypto failed: '.get_class($error).' at '.basename($error->getFile()).':'.$error->getLine().PHP_EOL);
    exit(1);
}
