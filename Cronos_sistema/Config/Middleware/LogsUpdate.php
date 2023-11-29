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

class LogsUpdate extends Connection
{

    public function setLogs($request, $tabela){
        $usuario = $request->user[0] ?? null;

        $antigo = $this->buscarDadosAntigos($request->getPostVars(), $tabela);
        $novo = $request->getPostVars();       
        $linha = $this->comparaArray($antigo, $novo);

        unset($usuario->id_usuario_now);

        $usuario->id_usuario_now = isset($novo['id_usuario_now']) && ($novo['id_usuario_now'] != '') ? $usuario->id_usuario_now = $novo['id_usuario_now'] :  $usuario->id_usuario_now = $usuario->id_pessoa;
        $usuario->id_registro = $novo['registro_id'];

        $this->insertRegistro($usuario, $linha, $tabela);
       
    }

    public function handle($request, $next, $tabela) {
        $this->setLogs($request, $tabela);
        //  $request->getRouter()->setContentType('application/json; charset=utf-8');
        return $next($request);
    }

    public function buscarDadosAntigos(array $request, $tabela){
        $result = DB::table($tabela)
       ->where('id', $request['registro_id'])
       ->get();

        return json_decode(json_encode($result[0]), true);
    }

    public function comparaArray(array $antigo, array $novo){

        $de = array_diff($antigo, $novo);
        $insert = array_diff($novo, $antigo);

        unset($novo['token']);
        unset($novo['id_usuario_now']); 

        $result = null;
        if(count($de) > 0){
            foreach($de as $k=>$n):
                if(array_key_exists($k, $novo)){
                    $result .= ' ( O campo ** '.$k.' ** foi alterado de "'.$n.'"  para  "'.$novo[$k].'" ) ';
                    unset($insert[$k]);
                }
            endforeach;    
            
        }

        return $result;
    }

    public function insertRegistro($dados, $linha, $tabela){

        $array = array(
            'id_registro' => $dados->registro_id
          , 'tabela' => $tabela
          , 'acao' => 'Update'
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