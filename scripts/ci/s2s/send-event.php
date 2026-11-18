<?php
require '/var/www/html/vendor/autoload.php';$app=require '/var/www/html/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try{(new App\Homologation\S2S\AgentsEventAdapter)->send($argv[1],$argv[2]);echo "CRM_AGENTS_ACK_VERIFIED_NO_DELIVERY\n";}catch(Throwable){fwrite(STDERR,"CRM_AGENTS_EVENT_DENIED\n");exit(2);}
