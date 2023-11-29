<?php

namespace Config;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;


  if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/../.env')) {
    return false;
  }

  $lines = file($_SERVER['DOCUMENT_ROOT'].'/../.env');
  foreach($lines as $line) {
    putenv($line);
  }

  dd(getenv('DB_CONNECTION'));

$capsule = new Capsule;
if (getenv('DB_CONNECTION') == 'mysql') {
    $capsule->addConnection([
        'driver' => trim(getenv('DB_CONNECTION')),
        'host' => trim(getenv('DB_HOST')),
        'database' => trim(getenv('DB_DATABASE')),
        'username' => trim(getenv('DB_USERNAME')),
        'password' => trim(getenv('DB_PASSWORD')),
        'port' => trim(getenv('DB_PORT')),
        'prefix' => '',
        'strict'    => false,
        'modes'       => [
            'ONLY_FULL_GROUP_BY',
            'STRICT_TRANS_TABLES',
            'NO_ZERO_IN_DATE',
            'NO_ZERO_DATE',
            'ERROR_FOR_DIVISION_BY_ZERO',
            'NO_AUTO_CREATE_USER',
            'NO_ENGINE_SUBSTITUTION',
        ],
        'engine' => null,
    ]);
} elseif (getenv('DB_CONNECTION') == 'sqlite') {
    $capsule->addConnection([
        'driver' => 'sqlite',
        'database' => __DIR__ . "/../storage/database/" . $conf['sqlite']['database']
    ]);
}

$capsule->setEventDispatcher(new Dispatcher(new Container));

$capsule->setAsGlobal();
$capsule->bootEloquent();