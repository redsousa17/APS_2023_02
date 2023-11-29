<?php

namespace Cronos_sistema\Config\Middleware;
use Cronos_sistema\Config\Connection;
use Cronos_sistema\Services\Auth;
use \Firebase\JWT\JWT;

use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class LogsDelete extends Connection
{

    public function setLogs($request, $tabela){
        $usuario = $request->user[0];
        $parte = explode("/" ,$request->getUri());
        $revert = array_reverse($parte);

        $novo = $request->getPostVars();       
        $linha = ' ( O registro ** '.$revert[0].' ** foi excluido da tabela "'.$tabela.' pelo usuario "'.$usuario->usuario.'" ") ';

        unset($usuario->id_usuario_now);

        $usuario->id_usuario_now = isset($novo['id_usuario_now']) && ($novo['id_usuario_now'] != '') ? $usuario->id_usuario_now = $novo['id_usuario_now'] :  $usuario->id_usuario_now = $usuario->id_pessoa;
        $usuario->id_registro = $revert[0];

        $this->insertRegistro($usuario, $linha, $tabela);
       
    }

    public function handle($request, $next, $tabela) {
        $this->setLogs($request, $tabela);
        //  $request->getRouter()->setContentType('application/json; charset=utf-8');
        return $next($request);
    }


    public function insertRegistro($dados, $linha, $tabela){

        $array = array(
            'id_registro' => $dados->id_registro
          , 'tabela' => $tabela
          , 'acao' => 'Delete'
          , 'alteracao' => $linha
          , 'error' => ''
          , 'data_now' => date('Y-m-d H:m:s')
          , 'id_usuario_now' => $dados->id_usuario_now
          , 'ip' => $_SERVER["REMOTE_ADDR"]
          ,);


        DB::table('logs_sistemas')
        ->insert($array);
    }
}