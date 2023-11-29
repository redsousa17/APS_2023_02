<?php

namespace Cronos_sistema\Servicos;

use Cronos_sistema\Config\Connection;
use Firebase\JWT\JWT as JWT;

use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;
use Cronos_sistema\Config\BaseModelEloquent;

class Auth extends BaseController
{
    private static $box = 'user';

    public static function login($request)
    {
        $dados = $request->getPostVars();
        $usuario =  $dados['usuario'];
        $password =  $dados['password'];


        $userFound =  DB::table('users')
        ->where('usuario', $usuario)
        ->first();

        
        if (!$userFound) {
            throw new \Exception("Usuario ou senha invalida!", 200);
        }

        if (!password_verify($password, $userFound->password)) {
            throw new \Exception("Usuario ou senha invalida!", 200);
        }


        $payload = [
            'sub' => md5(time()),
            'name' => $usuario,
            'iat' => time()
        ];

        $token = JWT::encode($payload, 'Ksis-seguros', 'HS256');

        $user_on = [
            'access_token' => $token
        ];
        
        DB::table('users')
        ->where('id', "=", $userFound->id)
        ->update($user_on);

       
        $result = [
            'id' => $userFound->id, 
            'usuario' => $userFound->usuario, 
            'nivel' => $userFound->nivel, 
            'token' => JWT::encode($payload, 'Ksis-seguros', 'HS256')
        ];

        return $result;
    }


    public static function base64KsiEncode($data)
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
