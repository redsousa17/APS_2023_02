<?php


namespace Cronos_sistema\Config\Middleware;

use Cronos_sistema\Config\Connection;
use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;

class Maintenance  extends BaseController {

    public function handle($request, $next, $parms1){
       
        if(getenv('MANUTECAO') == 'true'){
            throw new \Exception("API em manuteção", 200);
        }
        return $next($request);
    }
}