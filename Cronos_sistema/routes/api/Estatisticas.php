<?php



namespace Cronos_sistema\route\api;

use Cronos_sistema\Servicos\Auth;
use Cronos_sistema\Config\Response;
use Cronos_sistema\Servicos\Autentica;

use Cronos_sistema\Servicos\Home\Home;
use Cronos_sistema\Servicos\Estatisticas\Estatisticas;


$obRouter->get('/estatisticas/listar', [
    'middlewares' => ['Api', 'jwt-auth'],
    function($request){
        return new Response(200, Estatisticas::index($request), 'application/json');
    }
]);

$obRouter->post('/estatisticas/adicionar', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Estatisticas::novo($request), 'application/json');
    }
]);


$obRouter->get('/estatisticas/listar/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Estatisticas::buscar_jogador($request, $id), 'application/json');
    }
]);

$obRouter->post('/estatisticas/atualizar/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Estatisticas::update($request, $id), 'application/json');
    }
]);