<?php

namespace Cronos_sistema\Servicos\Usuarios;

use Cronos_sistema\Config\Helpers;

//require_once "../config.php";

use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;

class Users extends BaseController
{

    public static function listar_usuarioId($id)
    {

        $sql =  DB::table('users')
            ->select(DB::raw('id, id_pessoa, usuario, nivel, status'))
            ->where('id', $id)
            ->get();

        return $sql;
    }

    public static function listar_usuario_e_pessoas($request)
    {

        $queryParams = $request->getQueryParams();
        unset($queryParams['url']);

        $pagina    = 25;
        $npagina   = $queryParams['pagina'] ?? 1;

        $query = '';
        $input = ' pessoas.nome
                 , pessoas.id
                 , pessoas.email
                 , users.usuario
                 , users.nivel
                 , users.`status`
                 , users.id
                 , users.data_now';

        $result = DB::table('users')
            ->join('pessoas', 'users.id_pessoa', '=', 'pessoas.id')
            ->where(function ($q) use ($queryParams) {
                if (count($queryParams) > 0) {
                    foreach ($queryParams as $key => $value) {
                        if ($key != 'pag' &&  $value != "" && $key != 'nome') {
                            $q->where($key, '=', $value);
                        }
                        if ($key == 'nome' &&  $value != '') {
                            $q->where($key, 'LIKE', "%" . $value . "%");
                        }
                    }
                }
            })
            ->paginate($pagina, ['*'], 'page', $npagina);

        return $result;
    }

    public static function listar($request)
    {

        $queryParams = $request->getQueryParams();
        unset($queryParams['url']);


        $pagina    = 25;
        $npagina   = $queryParams['pagina'] ?? 1;

        $result =  DB::table('users')
            ->where(function ($q) use ($queryParams) {
                if (count($queryParams) > 0) {
                    foreach ($queryParams as $key => $value) {
                        if ($value != '') {
                            $q->where($key, '=', $value);
                        }
                    }
                }
            })
            ->paginate($pagina, ['*'], 'page', $npagina);


        return $result;
    }

    public static function cadastrar_usuario($request)
    {
        $dados = $request->getPostVars();

        $item = ['usuario', 'nivel', 'status', 'id_pessoa', 'senha', 'confirmar_senha'];
        foreach ($item as $input) {
            if (!isset($dados[$input])) {
                throw new \Exception("O campo '" . $input . "' e obrigatorio !", 400);
            }
        }

        if ($dados['senha'] != $dados['confirmar_senha']) {
            throw new \Exception("O campo senha não são iguais!", 400);
        }

        if ($dados['senha'] == '' && $dados['confirmar_senha'] == '') {
            throw new \Exception("O campo senha e confirmação são obrigatorio!", 400);
        }

        $senha = password_hash($dados['senha'], PASSWORD_DEFAULT);

        $data = [
              'usuario' => trim($dados['usuario'])
            , 'password' => $senha
            , 'nivel' => trim($dados['nivel'])
            , 'status' => trim($dados['status'])
            , 'ip' => $_SERVER["REMOTE_ADDR"]
            , 'id_usuario_now' => trim($dados['id_usuario_now'])
            , 'data_now' => date('Y-m-d H:m:s')
        ];

        $ultimo_id_inserido = date('Y-m-d H:m:s');
        $sql = DB::table('users')
            ->insertGetId($data);


        return [
              "mensagem" => "Usuario(a) inserida com successo!"
            , "registro" =>  $ultimo_id_inserido,
        ];
    }

    public static function atualizar_usuario($request)
    {

        $dados = $request->getPostVars();

        $antigo = DB::table('users')
            ->where('id', $dados['id_registro'])
            ->get();

        if ($dados['password'] != '' && $dados['confirmar_senha'] != '') {
            $senha = password_hash($dados['password'], PASSWORD_DEFAULT);
        }

        $array = [
            'usuario' => $dados['usuario'],
            'nivel' => $dados['nivel'],
            'status' => $dados['status'],
            'ip' => $_SERVER["REMOTE_ADDR"],
            'id_usuario_now' => $dados["id_usuario_now"]
        ];


        if ($dados['password'] != '' && $dados['confirmar_senha'] != '') {
            $array[]['password'] = $senha;
        }

        DB::table('users')
            ->where('id', $dados['id_registro'])
            ->update($array);

        return [
            "mensagem" => "Usuario(a) inserida com successo!",
            "registro" => $dados,
        ];

    }

    public static function deletar_usuario($id)
    {

        $sql = DB::table('')
            ->where('id', $id)
            ->delete();

        return "Usuario eliminado com sucesso!";
    }

   

}
