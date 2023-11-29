<?php

namespace Cronos_sistema\Config;

class Request {

    private $router;
    private $httpMethod;
    private $uri;
    private $queryParams = [];
    private $postVars    = [];
    private $putVars     = [];
    private $headers     = [];
    


    public function __construct($router)
    {
        $this->router = $router;
        $this->queryParams = $_GET ?? [];
        $this->headers = getallheaders();
        $this->httpMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        $this->uri = explode('?', $_SERVER['REQUEST_URI'] ?? '')[0];
        $this->setPostVars();
        $this->setPutVars();
        
    }
    public function setPutVars()
    {
        if ($this->httpMethod == 'GET') {
            return false;
        }

        $this->putVars = $_PUT ?? [];
        $inputRaw = file_get_contents('php://input');
        $this->putVars = (strlen($inputRaw) AND empty($_PUT)) ? json_decode($inputRaw, true) : $this->putVars; 
    }

    public function setPostVars()
    {
        if ($this->httpMethod == 'GET') {
            return false;
        }

        $this->postVars = $_POST ?? [];
        $inputRaw = file_get_contents('php://input');
        $this->postVars = (strlen($inputRaw) AND empty($_POST)) ? json_decode($inputRaw, true) : $this->postVars; 
    }


    private function setUri(){
        $this->uri = $_SERVER['REQUEST_URI'] ?? '';
        $xuri = explode('?', $this->uri);
        $this->uri = $xuri[0];
    }

    public function getRouter(){
       return $this->router;
    }

    // retorna o Method da requisicao
    public function getHttpMethod(){
        return $this->httpMethod;
    }

    // retorna o url da requisicao
    public function getUri(){
        return $this->uri;
    }

    // retorna o headers da requisicao
    public function getHeaders(){
        return $this->headers;
    }

    // retorna o parametro da requisicao
    public function getQueryParams(){
        return $this->queryParams;
    }

    // retorna o post da requisição
    public function getPostVars(){
        return $this->postVars;
    }

     // retorna o post da requisição
     public function getPutVars(){
        return $this->putVars;
    }

    

}