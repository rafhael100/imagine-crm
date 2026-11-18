<?php

require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    (new App\Homologation\S2S\AgentsReceiptReconciler)->reconcile($argv[1], $argv[2]);
    echo "CRM_AGENTS_RECEIPT_VERIFIED_NO_DELIVERY\n";
} catch (Throwable) {
    fwrite(STDERR, "CRM_AGENTS_RECEIPT_UNRESOLVED\n");
    exit(2);
}
