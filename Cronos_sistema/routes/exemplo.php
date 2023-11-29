<?php


namespace Cronos_sistema\route;

use Cronos_sistema\Config\Response;
use Cronos_sistema\Services\Exemplo\Exemplo; 


## Este arquio e somente de exemplo caso queira cria seu proprio arquivo duplique este arquivo 

$obRouter->get('/exemplo-rota/listar-apiId/{id}', [
    'middlewares' => ['Api', 'jwt-auth'],
    function($request, $id){
        return new Response(200, Exemplo::exemplo_get_id($request, $id),'application/json');
    }
]);

$obRouter->post('/exemplo-rota/cadastro-api', [
    'middlewares' => ['Api', 'jwt-auth'],
    function($request){
        return new Response(200, Exemplo::exemplo_post($request),'application/json');
    }
]);