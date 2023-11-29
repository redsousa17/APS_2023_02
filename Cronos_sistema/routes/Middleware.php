<?php 



namespace Cronos_sistema\route;



\Cronos_sistema\Config\Middleware\Queue::setMap([
    'maintenence'    => \Cronos_sistema\Config\Middleware\Maintenance::class,
    'Api'            => \Cronos_sistema\Config\Middleware\Api::class,
    'jwt-auth'       => \Cronos_sistema\Config\Middleware\JwtAuth::class,
    'Permissao'      => \Cronos_sistema\Config\Middleware\Permissao::class,
    'LogsUpdate'     => \Cronos_sistema\Config\Middleware\LogsUpdate::class,
    'LogsInsert'     => \Cronos_sistema\Config\Middleware\LogsInsert::class,
    'LogsDelete'     => \Cronos_sistema\Config\Middleware\LogsDelete::class,
]);

\Cronos_sistema\Config\Middleware\Queue::setDefault([
    'maintenence'
]);