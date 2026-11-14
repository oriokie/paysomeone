<?php

require __DIR__.'/../../vendor/autoload.php';

echo App\Services\Office\Totp::at($argv[1] ?? 'JBSWY3DPEHPK3PXP');
