<?php



namespace Cronos_sistema\Config;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Connection {
   

  function __construct() {

          if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/../.env')) {
            return false;
          }
    
          $lines = file($_SERVER['DOCUMENT_ROOT'].'/../.env');
          foreach($lines as $line) {
            putenv($line);
          }

          $capsule = new Capsule;
          $capsule->addConnection([
              'driver' =>  preg_replace('/\s\s+/','', getenv('DB_CONNECTION')),
              'host' =>  preg_replace('/\s\s+/','', getenv('DB_HOST')),
              'database' =>  preg_replace('/\s\s+/','', getenv('DB_DATABASE')),
              'strict' => false,
              'username' =>  preg_replace('/\s\s+/','', getenv('DB_USERNAME')),
              'password' =>  preg_replace('/\s\s+/','', getenv('DB_PASSWORD')),
              'charset' => 'utf8',
              'collation' => 'utf8_unicode_ci',
              'prefix' => '',
              'strict'    => false,
              
          ]);


          $capsule->bootEloquent();
          $capsule->setAsGlobal();
  }


}