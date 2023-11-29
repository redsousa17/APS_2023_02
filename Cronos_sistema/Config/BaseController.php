<?php



namespace Cronos_sistema\Config;

use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Cronos_sistema\Config\Helpers;

use Jenssegers\Blade\Blade;




class BaseController {
    private static $vars = [];
    private static $files = '';

     public static function init($vars = []) {
        self::$vars = $vars;
    }

    private static function content($view){
        $file = "../resources/view/{$view}.php";
        return file_exists($file) ? file_get_contents($file) : '';
    }

    protected static function renderView($viewPath, $layoutPath = null){   
        if (file_exists("../resources/views/{$viewPath}.blade.php")) {
            $blade = new Blade('../resources/views', '../storage/cache/');
            echo $blade->make($viewPath, $layoutPath)->render();
        } else {
            return require_once "../resources/views/404.php";
        }
        
    }

    protected static function renderHtml($view, $vars = []){
        $vars = array_merge(self::$vars, $vars);
        $keys = array_map(function($item){
            return '{{' . $item . '}}'; 
        },array_keys($vars));

        return str_replace($keys, array_values($vars),self::content($view));
    }


  
}