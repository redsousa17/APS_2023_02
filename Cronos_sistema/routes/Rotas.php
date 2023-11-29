<?php



namespace Cronos_sistema\route;

use Cronos_sistema\Servicos\Auth;
use Cronos_sistema\Config\Response;
use Cronos_sistema\Servicos\Autentica;

use Cronos_sistema\Servicos\Home\Home;



$obRouter->get('/', [
    'middlewares' => ['Api'],
    function($id){
        return new Response(200, 'Trabalho Escola', 'text/html');
    }
]);


$obRouter->get('/login/validatoken', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(200, Autentica::validatoken($request), 'application/json');
    }
]);

$obRouter->post('/api/Auth/logar', [
    'middlewares' => ['Api'],
    function($request){
        return new Response(201, Auth::login($request), 'application/json');
    }
]);



