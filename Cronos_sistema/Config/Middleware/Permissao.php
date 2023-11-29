<?php

namespace Cronos_sistema\Config\Middleware;
use PDO;
use \Firebase\JWT\JWT;
use Cronos_sistema\Config\Helpers;
use Cronos_sistema\Config\Connection;


use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;

class Permissao extends BaseController
{

    private function getJwtAuth($request, $params)
    {
        $headers = $request->getHeaders();

        $jwt = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';        

        try {
            $decode = (array) JWT::decode($jwt, 'Ksis-seguros', ['HS256']);
        } catch (\Exception $e) {
            throw new \Exception("Token inválido", 403);            
        }

        $name = $decode['name'];
        $userFound =  DB::table('users')
        ->where('usuario', $name)
        ->get();

       //dd($userFound);
      
        if($this->checa_permissao($userFound[0]->id, $params) == 0){
            throw new \Exception("Você não tem permissão para executar esta ação", 401); 
        }
        return $userFound;
    }

    private function auth($request, $params)
    {
        if ($user = $this->getJwtAuth($request, $params)) {
            $request->user = $user;
            return true;
        }

        throw new \Exception("Token inválido", 403);        
    }

    public function handle($request, $next, $params )
    {
       $this->auth($request, $params);
       return $next($request);
    }

    public function checa_permissao($id, $permissao){

         $user = DB::table('users')
         ->select(DB::raw('id, id_pessoa, usuario')) 
        ->where('id', $id)
        ->where('nivel', '=' , 'A')
        ->orWhere('nivel', '=' , 'C') 
        ->get();

        $grupo = DB::table('permissao_usuario_grupo')
        ->select(DB::raw('permissao_grupo.grupo, permissao_roles.permissao')) 
        ->join('permissao_grupo', 'permissao_grupo.id', '=', 'permissao_usuario_grupo.id_group')
        ->join('permissao_roles', 'permissao_roles.id', '=', 'permissao_grupo.id')
        ->where('id_usuarios', $user[0]->id)
        ->get();

        if($grupo[0]->grupo == 'Admin'){
            return 1; 
            exit;
        }

        $herdada = DB::table('permissao_usuario_herdada')
        ->select(DB::raw('permissao_acesso.permissao')) 
        ->join('permissao_acesso', 'permissao_usuario_herdada.id_permissao_acesso', '=', 'permissao_acesso.id')
        ->where('id_usuario', $id)
        ->where('permissao' , $permissao)
        ->get();

        if($herdada){
            return 1;
            exit;
        };

        return 0;
    }
}