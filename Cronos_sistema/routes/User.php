<?php


namespace Cronos_sistema\route;

use Cronos_sistema\Config\Response;
use Cronos_sistema\Servicos\Usuarios\Users;


$obRouter->get('/user/listar', [
    'middlewares' => ['Api', 'jwt-auth', 'Permissao:listar'],
    function($request){
        return new Response(200, Users::listar($request), 'application/json');
    }
]);

$obRouter->get('/user/listar-usuario-e-pessoas', [
    'middlewares' => ['Api', 'jwt-auth', 'Permissao:listar'],
    function($request){
        return new Response(200, Users::listar_usuario_e_pessoas($request), 'application/json');
    }
]);

$obRouter->get('/user/listar-usuarioId/{id}', [
    'middlewares' => ['Api', 'jwt-auth', 'Permissao:listar'],
    function($id){
        return new Response(200, Users::listar_usuarioId($id), 'application/json');
    }
]);

$obRouter->get('/user/deletar-usuario/{id}', [
    'middlewares' => ['Api', 'jwt-auth', 'Permissao:deletar'],
    function($id){
        return new Response(200, Users::deletar_usuario($id), 'application/json');
    }
]);

$obRouter->post('/user/cadastrar-usuario', [
    'middlewares' => ['Api', 'jwt-auth', 'Permissao:adicionar'],
    function($request){
        return new Response(200, Users::cadastrar_usuario($request), 'application/json');
    }
]);

$obRouter->post('/user/atualizar-usuario', [
    'middlewares' => ['Api', 'jwt-auth', 'Permissao:editar'],
    function($request){
        return new Response(200, Users::atualizar_usuario($request), 'application/json');
    }
]);
