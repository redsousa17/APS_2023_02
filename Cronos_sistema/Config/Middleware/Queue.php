<?php


namespace Cronos_sistema\Config\Middleware;

use Cronos_sistema\Config\Middleware\Maintenance;
use Cronos_sistema\Config\Middleware\Api;

class Queue {

    private static $map     = [];
    private static $default = [];
    private $middlewares = [];
    
    // funcão de execução dos serviços
    private $services;
    private $serviceArgs = [];


    public function __construct($middlewares, $services, $serviceArgs){
        $this->middlewares    = array_merge(self::$default, $middlewares);
        $this->services = $services;
        $this->serviceArgs = $serviceArgs;
    }

    public static function setMap($map){
        self::$map = $map;
    } 

    //defini middleware padrao
    public static function setDefault($default){
        self::$default = $default;
    }

    public function next($request){      
        
        if(empty($this->middlewares)){
            return call_user_func_array($this->services, $this->serviceArgs);
        }

        $middleware = array_shift($this->middlewares);
        $parms = explode(":", $middleware);
        $parms1 = isset($parms[1]) ? $parms[1] : null;

        if (!isset(self::$map[$parms[0]])) {
           throw new \Exception("Problemas ao processar o middleware", 500);
        };

        //next
        $queue = $this;
        $next = function($request) use ($queue){
            return $queue->next($request);
        };
        return (new self::$map[$parms[0]])->handle($request, $next, $parms1);
    }

}