<?php


namespace Cronos_sistema\Servicos\Estatisticas;

use PDO;
use Cronos_sistema\Config\Helpers;


use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;


class Estatisticas extends BaseController {


    public static function index($request){

        $trabalho = DB::table('estatisticas')
        ->paginate(25, ['*'], 'page', $pagina ?? 1);

        return $trabalho;

    }

    public static function novo($request){
        $dados = $request->getPostVars(); 

        $data = [
            "pais" => $dados['pais'],
            "nome_liga" => $dados['nome_liga'],
            "clube" => $dados['clube'],
            "nome_jogador" => $dados['nome_jogador'],
            "numero_partidas" => $dados['numero_partidas'],
            "numero_substituicao" => $dados['numero_substituicao'],
            "tempo_jogado" => $dados['tempo_jogado'],
            "gols_marcados" => $dados['gols_marcados'],
            "meta_esperada" => $dados['meta_esperada'],
            "gols_esperado" => $dados['gols_esperado']
        ];

        $trabalho = DB::table('estatisticas')
       ->insertGetId($data);

        return $trabalho;

    }


    public static function buscar_jogador($request, $id){

        $trabalho = DB::table('estatisticas')
        ->where('id','=', $id)
        ->first();


        return $trabalho;

    }


    public static function update($request, $id){
        $dados = $request->getPostVars(); 


        $data = [
            "pais" => $dados['pais'],
            "nome_liga" => $dados['nome_liga'],
            "clube" => $dados['clube'],
            "nome_jogador" => $dados['nome_jogador'],
            "numero_partidas" => $dados['numero_partidas'],
            "numero_substituicao" => $dados['numero_substituicao'],
            "tempo_jogado" => $dados['tempo_jogado'],
            "gols_marcados" => $dados['gols_marcados'],
            "meta_esperada" => $dados['meta_esperada'],
            "gols_esperado" => $dados['gols_esperado']
        ];


        $trabalho = DB::table('estatisticas')
        ->where('id','=', $id)
        ->update($data);


        return "Registro atualizado com sucesso";

    }


    public static function delete($request, $id){

        DB::table('estatisticas')
        ->where('id','=', $id)
        ->delete();


        return "Registro deletado com sucesso";

    }


 }