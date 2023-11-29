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

class LogsInsert extends Connection
{

    public function setLogs($request, $tabela){
        $usuario = $request->user[0];

        $novo = $request->getPostVars();    
        $linha = $this->comparaArray($novo);

        $usuario->id_usuario_now = $novo['id_usuario_now'];
        $usuario->id_registro = $novo['id_registro'];
       
        $this->insertRegistro($usuario, $linha, $tabela);
       
    }

    public function handle($request, $next, $tabela) {
        $this->setLogs($request, $tabela);
        return $next($request);
    }


    public function comparaArray(array $novo){
            unset($novo['token']); 
            unset($novo['id_usuario_now']); 
            
            $result = null;
            foreach($novo as $k=>$n):
                    $result .= ' ( O campo ** '.$k.' ** foi adicionado no registro com valor: "'.$n.'"  )';
            endforeach;    
            return $result;
    }

    public function insertRegistro($dados, $linha, $tabela){
        $array = array(
            'id_registro' => $dados->id_registro
          , 'tabela' => $tabela
          , 'acao' => 'Insert'
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