<?php

namespace Cronos_sistema\Config\Middleware;
use Cronos_sistema\Config\Connection;
use \Firebase\JWT\JWT;

use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;

class JwtAuth  extends BaseController
{
    private function getJwtAuth($request)
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
        
        /*
        'SELECT id, id_pessoa, usuario, nivel, status FROM users WHERE usuario = :usuario limit 1';
        $stmt = $con->prepare($sql);
        $stmt->bindValue(':usuario', $name);
        $stmt->execute();

        $userFound = $stmt->fetch(\PDO::FETCH_ASSOC);
        */
       
        
        return $userFound;
    }


    private function auth($request)
    {
        if ($user = $this->getJwtAuth($request)) {
            $request->user = $user;
            return true;
        }

        throw new \Exception("Token inválido", 403);        
    }

    public function handle($request, $next, $parms1)
    {
        $this->auth($request);
        return $next($request);
    }
}