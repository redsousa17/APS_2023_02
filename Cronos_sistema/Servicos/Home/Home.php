<?php

namespace Cronos_sistema\Servicos\Home;

use PDO;

use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;



class Home extends BaseController {


    public static function index($request){        
        $dados = $request->getQueryParams(); 

        $result = DB::table('estatisticas')
        ->paginate(10, ['*'], 'page', $dados['page'] ?? 1);

        $itens = collect($result);

        BaseController::renderView('home', compact('itens'));
    } 


    public static function listar_home($request){
        
        $dados = $request->getQueryParams(); 

        $result = DB::table('estatisticas')
        ->get();

        return $result;
    } 


    public static function jogador_id($request, $id){
        
        $dados = $request->getQueryParams(); 

        $result = DB::table('estatisticas')
        ->where('id','=', $id)
        ->first();

        BaseController::renderView('jogador', compact('result'));
    } 


    public static function grafico_home($request){
        
        $dados = $request->getQueryParams(); 

        $result = DB::table('estatisticas')
        ->get();

        return $result;
    } 

}