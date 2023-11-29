<?php


namespace Cronos_sistema\Config;

use Cronos_sistema\Servicos\Autentica;

class Response {

    private $httpCode = 200;
    private $headers  = [];
    private $contentType = 'text/html';
    private $content;


    // inicia os valores da classe 
    public function __construct($httpCode, $content, $contentType = 'text/html'){
        $this->httpCode = $httpCode;
        $this->content  = $content;
        $this->setContentType($contentType);
    }

    // responsavel por alterar o header da requisição
    public function setContentType($contentType){
        
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set("display_errors", 0);

        $this->contentType  = $contentType;
        $this->addHeader('Access-Control-Allow-Origin', '*');
        $this->addHeader('Access-Control-Allow-Headers', '*');
        $this->addHeader('Access-Control-Max-Age', 86400);
        $this->addHeader('Access-Control-Allow-Methods', 'PUT, GET, POST, DELETE, OPTIONS');
        $this->addHeader('Content-Type', $contentType);
       
    }

    // responsavel por adicionar registro no cabeçalho de response
    public function addHeader($key, $value){
       
        $this->headers[$key] = $value; 
    }

    private function sendHeaders(){
       // print_r($this->headers); 
       // $cod =  $this->httpCode;
       // http_response_code((int)$cod);

        foreach($this->headers as $key=>$value){
            header($key.': '.$value);
        }
       
    }

    public function sendResponse(){
        $this->sendHeaders();
       
        switch ($this->contentType){
            case 'text/html':
                echo $this->content;
            break;
            case 'application/json':
                echo json_encode(
                    array(
                             'status'=> Autentica::codehttp(http_response_code())
                            ,'data'=> $this->content
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            break;    
            case 'application/json; charset=utf-8':
                echo json_encode(
                    array(
                             'status'=> Autentica::codehttp(http_response_code())
                            ,'data'=> $this->content
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            exit;    
        }
       
    }










}