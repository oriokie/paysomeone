<?php

use App\Support\InstallGate;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$gate = InstallGate::fromRoot($root);
$result = $gate->handle($_POST);

http_response_code($result['code']);
header('Content-Type: text/html; charset=UTF-8');
header('X-Robots-Tag: noindex');
echo $result['body'];
