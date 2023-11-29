<?php

namespace Cronos_sistema\Servicos\Exemplo;

use PDO;
use Cronos_sistema\Config\Helpers;


use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;



class Exemplo extends BaseController {

    public static function exemplo_get_id($request, $id){
        
        $dados = $request->getQueryParams(); 

        $result = DB::table('minha_tabela')
        ->get();

        return $result;
    } 

    public static function exemplo_post($request){
        
        $dados = $request->getPostVars();

        $array_aqui[] = $dados;

        DB::table('minha_tabela')
        ->save($array_aqui);
        
        return $dados;
    }
}