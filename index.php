<?php

#echo "<pre>";


set_time_limit(0);
require_once 'vendor/autoload.php';

use Cronos_sistema\Config\Router;

define('URL', 'http://trabalho-api.test');
$obRouter = new  Router(URL);

require_once('Cronos_sistema/routes/main.php');
require_once('Cronos_sistema/routes/Middleware.php');

$obRouter->run()
         ->sendResponse();