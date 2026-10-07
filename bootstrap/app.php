<?php

use App\Core\App;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$app = new App(BASE_PATH);
$app->boot();

return $app;
