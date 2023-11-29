<?php



namespace Cronos_sistema\route;

use Cronos_sistema\Servicos\Auth;
use Cronos_sistema\Config\Response;
use Cronos_sistema\Servicos\Autentica;

use Cronos_sistema\Servicos\Home\Home;
use Cronos_sistema\Servicos\login\LoginUser;
use Cronos_sistema\Servicos\Imobiliaria\Imobiliaria;
use Cronos_sistema\Servicos\Relatorio\Relatorios;
use Cronos_sistema\Servicos\Corretor\Corretor;




$obRouter->get('/imobiliaria', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Imobiliaria::index($request), 'text/html');
    }
]);

$obRouter->get('/imobiliaria/produtos/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::produto_imobiliaria($request, $id), 'application/json');
    }
]);

$obRouter->get('/imobiliaria/toda-cotacao/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::todas_cotacoe($request, $id), 'application/json');
    }
]);

$obRouter->get('/imobiliaria/cotacao-aprocada/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::cotacoe_aprovada($request, $id), 'application/json');
    }
]);


/** Novo cadastro*/
$obRouter->get('/imobiliaria/novo', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Imobiliaria::novo_cadastro($request), 'text/html');
    }
]);


$obRouter->post('/imobiliaria/salvar-cadastro', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Imobiliaria::salvar_cadastro($request), 'application/json');
    }
]);

/** */


/** Editar cadastro*/
$obRouter->get('/imobiliaria/editar/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::editar_cadastro($request, $id), 'text/html');
    }
]);

$obRouter->post('/imobiliaria/editar-salvar', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Imobiliaria::editar_salvar($request), 'application/json');
    }
]);

/** */



$obRouter->get('/imobiliaria/gerenciar/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::gerenciar_imobiliaria($request, $id), 'text/html');
    }
]);



$obRouter->get('/imobiliaria/excluir-link/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::excluir_link($request, $id), 'text/html');
    }
]);

$obRouter->get('/imobiliaria/cadastrar-link/{id}', [
    'middlewares' => ['Api'],
    function($request, $id){
        return new Response(200, Imobiliaria::cadastrar_link($request, $id), 'text/html');
    }
]);