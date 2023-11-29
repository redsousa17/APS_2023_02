<?php



namespace Cronos_sistema\route\web;

use Cronos_sistema\Config\Response;

use Cronos_sistema\Servicos\home\Home;




$obRouter->get('/home', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Home::index($request), 'text/html');
    }
]);

$obRouter->get('/home/listar', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Home::listar_home($request), 'text/html');
    }
]);


$obRouter->get('/home/jorgador-id/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Home::jogador_id($request, $id), 'text/html');
    }
]);



$obRouter->post('/carrega/grafico-home', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Home::grafico_home($request), 'text/html');
    }
]);