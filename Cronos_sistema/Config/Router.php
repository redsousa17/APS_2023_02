<?php 

namespace Cronos_sistema\Config;
use \Closure;
use \ReflectionFunction;
use Cronos_sistema\Config\Middleware\Queue;

class Router {

    private $url = '';
    private $prefix = '';
    private $routes = [];
    private $request;
    private $contentType = 'text/html';

    public function __construct($url){
        $this->request = new Request($this);
        $this->url     = $url;
        $this->setPrefix();
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    private function setPrefix(){
        $parseUrl = parse_url($this->url);
        $this->prefix = $parseUrl['path'] ?? '';
    }

    private function addRoute($method, $route, $params = []){
       
        foreach($params as $key => $value){
            if($value instanceof Closure){
               $params['Servicos'] = $value;
                unset($params[$key]);
                continue;
            }
        }
      
        $params['middlewares'] = $params['middlewares'] ?? [];
       
        // passando dados pela url
        $params['variables'] = [];

        $pattrernVariable = '/{(.*?)}/';
        if(preg_match_all($pattrernVariable, $route, $matches)){
            
            $route = preg_replace($pattrernVariable, '(.*?)', $route);
            $params['variables'] = $matches[1] ?? '';

        }

       
        $route = rtrim($route, '/');
       
        // padrao validacao url
        $patternRoute = '/^'.str_replace('/','\/', $route).'$/';

        $this->routes[$patternRoute][$method] = $params;

       
    }

    // responsavel por retornar a uri desconsiderando o prefix
    private function getUri(){
        $uri = $this->request->getUri();
        //divide a uri com prefix
        $xUri = strlen($this->prefix) ? explode($this->prefix, $uri) : [$uri];

        //retorno sem prefix 
        return rtrim(end($xUri), '/');

    }

    //retorna os dados da rota atual
    private function getRoute(){
        $uri = $this->getUri();
        $httpMethod = $this->request->getHttpMethod();

        //valida rota
        foreach($this->routes as $patternRoute => $methods){
           
            if(preg_match($patternRoute, $uri, $matches)){
                if(isset($methods[$httpMethod])){
                    //print_r($matches);
                    unset($matches[0]);

                    //variaveis processadas da url
                    $keys  = $methods[$httpMethod]['variables'];
                    $methods[$httpMethod]['variables'] = array_combine($keys, $matches);
                    $methods[$httpMethod]['variables']['request'] = $this->request;

                    return $methods[$httpMethod];
                }

                throw new \Exception("Metodo não permitido", 405);

            }  
        }

        throw new \Exception("Rota não encontrada", 404);

    }


    

    public function get($route, $params = []){

        return $this->addRoute('GET', $route, $params);
    }

    public function post($route, $params = []){

       // print_r($route."\n");
        return $this->addRoute('POST', $route, $params);
    }

    public function put($route, $params = []){
        return $this->addRoute('PUT', $route, $params);
    }

    public function delete($route, $params = []){
        return $this->addRoute('DELETE', $route, $params);
    }

    public function run(){
        try {
            $route = $this->getRoute();

            if(!isset($route['Servicos'])){
                throw new \Exception("Serviço não encontrado", 500);
            }
            $args = [];
            $reflection = new ReflectionFunction($route['Servicos']);
            foreach($reflection->getParameters() as $parameters){
               $name = $parameters->getName();                
               $args[$name] = $route['variables'][$name] ?? '';
            }
            return (new Queue($route['middlewares'], $route['Servicos'], $args))->next($this->request);

        }catch(\Exception $e){
            return new Response($e->getCode(), $this->getErrorMessage($e->getMessage()), $this->contentType);
        }
    }

    private function getErrorMessage($message)
    {
       
        switch ($this->contentType) {
            case 'application/json':
                return ['error' => $message];
                break;            
            case 'application/json; charset=utf-8':
                return ['error' => $message];
                break;            
            default:
                return $message;
                break;
        }
    }
}