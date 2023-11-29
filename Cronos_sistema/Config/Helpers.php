<?php

namespace Cronos_sistema\Config;

use PDO;
use Illuminate\Http\Request;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

use Cronos_sistema\Config\BaseController;

use Cronos_sistema\Servicos\Logs\Logs;
use Cronos_sistema\Servicos\Imagens\Imagem;


class Helpers extends BaseController {

    private static $result;
    private $Select;
    private static $Places = [];
    private $Result;
    private $Read;
    private $Conn;

    public static function fromCamelCase($str) {
        $string = str_replace('_', '/', $str);      
        return strtoupper($string);
    }




    public static function sql_trim($sql){
        return str_replace(array('  ', "\t", "\n", "\r"), ' ', $sql);
    }



    public static function atualizaOrcamento($path, $orcamento, $dados_or, $json_dados, $json_orcamento, $id_pessoa){
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');

       

        $destino = '/arquivos/propostas/portoseguro/retorno/'.$path.'/'.$ano.'/'.$mes.'/'.$dia.'/'.$id_pessoa;
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].$destino)){
            mkdir($_SERVER['DOCUMENT_ROOT'].$destino, 0777, true);
        }

        if (file_exists($_SERVER['DOCUMENT_ROOT'].$destino.'/'.$orcamento.'.json')){
           //   unlink($_SERVER['DOCUMENT_ROOT'].$destino.'/'.$orcamento.'.json');
        };

        $arquivo  = fopen($_SERVER['DOCUMENT_ROOT'].$destino.'/'.$orcamento.'.json',  "w");
        fwrite($arquivo, $json_orcamento);
        fclose($arquivo);

        $dthoje = date('Y-m-d H:m:s');

        $array = array(
            ':json' => "{$destino}/{$orcamento}.json",
            ':data_orcamento' => date('Y-m-d H:m:s'),
        );

        DB::table('consultas')
        ->where('orcamento','=',$orcamento)
        ->update($array);

        /*
        $sql = "UPDATE consultas SET json = :json , data_orcamento = :data_orcamento WHERE orcamento= :orcamento";
        $stmt= $con->prepare($sql);
        $stmt->execute($array);
        */
    }

    public static function atualizaEmissaoApoloci($path, $orcamento, $apolice, $json, $id_pessoa){
        $ano = date('Y');
        $mes = date('m');
        $dia = date('d');
        
        $destino = '/arquivos/propostas/portoseguro/retorno/'.$path.'/'.$ano.'/'.$mes.'/'.$dia.'/'.$id_pessoa;
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].$destino)){
            mkdir($_SERVER['DOCUMENT_ROOT'].$destino, 0777, true);
        }

        $destino = '/arquivos/propostas/portoseguro/retorno/'.$path.'/'.$ano.'/'.$mes.'/'.$dia.'/'.$id_pessoa;
        if(!is_dir($_SERVER['DOCUMENT_ROOT'].$destino)){
            mkdir($_SERVER['DOCUMENT_ROOT'].$destino, 0777, true);
        }

        $arquivo  = fopen($_SERVER['DOCUMENT_ROOT'].$destino.'/'.$orcamento.'.json',  "w");
        fwrite($arquivo, $json);
        fclose($arquivo);

        $array = array(
            ':status' => 5,
            ':tipo' => 6,
            ':json_emissao' => "{$destino}/{$orcamento}.json",
            ':apolice' => $apolice
        );

        DB::table('consultas')
        ->where('orcamento','=', $orcamento)
        ->update($array);

        /*
        $sql = "UPDATE consultas SET status = :status , tipo = :tipo, json_emissao = :json_emissao, apolice = :apolice WHERE orcamento= :orcamento";
        $stmt= $con->prepare($sql);
        $stmt->execute($array);
        */
        
       
    }

    public static function criaArquivo($path, $orcamento, $json, $json_dados, array $data, $mensagem, $msg_error_seguradora, $status, $id_pessoa, $tipo){
        
            $ano = date('Y');
            $mes = date('m');
            $dia = date('d');
            
            $destino = '/arquivos/propostas/portoseguro/retorno/'.$path.'/'.$ano.'/'.$mes.'/'.$dia.'/'.$id_pessoa;
            if(!is_dir($_SERVER['DOCUMENT_ROOT'].$destino)){
                mkdir($_SERVER['DOCUMENT_ROOT'].$destino, 0777, true);
            }

            $arquivo  = fopen($_SERVER['DOCUMENT_ROOT'].$destino.'/'.$orcamento.'.json',  "w");
            fwrite($arquivo, $json);
            fclose($arquivo);

            $arquivo  = fopen($_SERVER['DOCUMENT_ROOT'].$destino.'/'.$orcamento.'_dados.json',  "w");
            fwrite($arquivo, $json_dados);
            fclose($arquivo);

            $dthoje = date('Y-m-d H:m:s');
            
            $array = array(
                'id_pessoa' => $data['id_pessoa'],
                'id_pessoa_ksi' => $data['id_pessoa_ksi'],
                'id_seguradora' => $data['id_seguradora'],
                'id_produto' => $data['id_produto'],
                'id_imovel' => $data['id_imovel'],
                'orcamento' => $orcamento,
                'nome' => $data['nome'],
                'nome_integracao' => $data['data']['seguradora'],
                'cpf_cnpj' => $data['cpf_cnpj'],
                'json' => "{$destino}/{$orcamento}.json",
                'json_dados' => "{$destino}/{$orcamento}_dados.json",
                'url_chamada' => $data['base_url'],
                'mensagem' => $mensagem,
                'msg_error_seguradora' => $msg_error_seguradora,
                'tipo' => $tipo,
                'data_orcamento' => date('Y-m-d H:m:s'),
                'data_validade_orcamento' => date('Y-m-d', strtotime('+45 days', strtotime($dthoje))),
                'flag_status' => $status,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'data_now' => date('Y-m-d H:m:s')
            );


        
            DB::table('consultas')
            ->insertGetId($array);


    }

    public static function atualizaStatus($nome, $status ){

        $array = [
            'tipo' => $status,
            'flag_status' => 1
        ];

        DB::table('consultas')
        ->where('orcamento','=', $nome)
        ->update($array);
    }

    public static function get_public(){
        return trim(getenv("PUBLIC_PATH"));
    }

    public static function get_path(){
        return trim(getenv("PATH_APP"));
    }

    public static function get_api(){
        return trim(getenv("API"));
    }

    public static function getUrl($url){
        return trim(getenv($url));
    }




    #
    #
    #
    #
    #CALCULA DIAS
    public static function calculadias($data_inicial, $data_final){
        // Usa a função strtotime() e pega o timestamp das duas datas:
        $time_inicial = strtotime($data_inicial);
        $time_final = strtotime($data_final);
        
        // Calcula a diferença de segundos entre as duas datas:
        $diferenca = $time_final - $time_inicial; // 19522800 segundos
        
        // Calcula a diferença de dias
        $dias = (int)floor( $diferenca / (60 * 60 * 24)); // 225 dias
        
        // Exibe uma mensagem de resultado:
        return self::$result = $dias;

    }

    #FORMATA CPF OU CNPJ
    public static function cnpjcpf($cnpj){
        if (strlen($cnpj) == 15){
            $p1 = substr($cnpj, 0, 3);
            $p2 = substr($cnpj, 3, 3);
            $p3 = substr($cnpj, 6, 3);
            $p4 = substr($cnpj, 9, 4);
            $p5 = substr($cnpj, -2);
            
            return self::$result = $p1.'.'.$p2.'.'.$p3.'/'.$p4.'-'.$p5;
        }
        else if (strlen($cnpj) == 11){
            
            $p1 = substr($cnpj, 0, 3);
            $p2 = substr($cnpj, 3, 3);
            $p3 = substr($cnpj, 6, 3);
            $p4 = substr($cnpj, -2);
            
            return self::$result = $p1.'.'.$p2.'.'.$p3.'-'.$p4;
        }
        

    }

    #MES POR EXTENÇO
    public static function mesextenco($mes){
        switch($mes) {
            case"01": $mes = "Janeiro";	  break;
            case"02": $mes = "Fevereiro"; break;
            case"03": $mes = "Março";	  break;
            case"04": $mes = "Abril";	  break;
            case"05": $mes = "Maio";	  break;
            case"06": $mes = "Junho";	  break;
            case"07": $mes = "Julho";	  break;
            case"08": $mes = "Agosto";	  break;
            case"09": $mes = "Setembro";  break;
            case"10": $mes = "Outubro";	  break;
            case"11": $mes = "Novembro";  break;
            case"12": $mes = "Dezembro";  break;
        }
        return self::$result = $mes;
    }

    #MOEDA
    public static function moeda($valor){
        return self::$result = number_format($valor, 2, ',', '.');
    }

    #MOEDA2
    public static function moeda2($valor){
        return self::$result = number_format($valor, 7, ',', '.');
    }

    #VIRGULA
    public static function virgula($valor){
        return self::$result = str_replace(".",",",$valor);
    }

    #PONTO
    public static function ponto($valor){
        return self::$result = str_replace(",",".",$valor);
    }

    #TIRA SINAL NEGATIVO DE INTEIRO
    public static function negativo($valor){
        return self::$result = str_replace("-","",$valor);
    }

    #GRAVA NO BANCO TIPO FLOAT
    public static function vfloat($valor){
        $array = explode(",",$valor);	
        $um 	= str_replace(".","",$array[0]);	
        $novo = $um.'.'.$array[1];
        
        return self::$result = $novo;
    }

    #ENTER EM AREA DE TEXTO
    public static function enter($string){ 
        $string = str_replace(array("\r\n", "\r", "\n"), "<br>", $string); 
        return self::$result = $string; 
    }

    #ENTER EM AREA DE TEXTO
    public static function enter2($string){ 
        $string = str_replace('<br>', "\n", $string); 
        return self::$result = $string; 
    }

    #SENHA ENCODE
    public static function senha_encode($senha){
        return self::$result = base64_encode(base64_encode(base64_encode(base64_encode($senha))));
        
    }

    #SENHA DECODE
    public static function senha_decode($senha){
        return self::$result = base64_decode(base64_decode(base64_decode(base64_decode($senha))));
        
    }

    #DATA BARRA TRAÇO
    public static function data_barra($data){
        return self::$result = str_replace("/","-",$data);
    }

    #DATA EUA
    public static function data_eua(){
        return self::$result = date("Y-m-d");
    }

    #DATA BRASIL
    public static function data_br(){
        return self::$result = date("d/m/Y");
    }

    #DATA HORA
    public static function data_hora(){
        return self::$result = date("Y-m-d H:i:s");
    }

    #DATA BRASIL EUA
    public static function data_brasil_eua($data){
        $array = explode("/",$data);
        
        return self::$result = $array[2].'-'.$array[1].'-'.$array[0];
    }

    #DATA EUA BRASIL
    public static function data_eua_brasil($data){
        $data =	(isset($data) ?  $data : Date('Y-m-d'));
        
        $array = explode("-",$data);
        
        return self::$result = $array[2].'/'.$array[1].'/'.$array[0];
    }

    #DATA E HORA EUA BRASIL
    public static function data_hora_eua_brasil($data){
        $array = explode(" ",$data);	
        $hora = $array[1];	
        $array2 = explode("-",$array[0]);	
        return self::$result = $array2[2].'/'.$array2[1].'/'.$array2[0].' '.$hora;
    }

    #DATA E HORA EUA BRASIL
    public static function data_hora_brasil_eua($data1){
        $array1 = explode(" ",$data1);	
        $hora1 = $array1[1];	
        $array21 = explode("/",$array1[0]);	
        return self::$result = $array21[2].'-'.$array21[1].'-'.$array21[0].' '.$hora1;
    }

    #REMOVER ACENTOS
    public static function remover_acentos($string){ 
        // Converte todos os caracteres para minusculo 
        $string = strtolower($string); 
        // Remove os acentos 
        $string = preg_replace('[aáàãâä]', 'a', $string); 
        $string = preg_replace('[eéèêë]', 'e', $string); 
        $string = preg_replace('[iíìîï]', 'i', $string); 
        $string = preg_replace('[oóòõôö]', 'o', $string); 
        $string = preg_replace('[uúùûü]', 'u', $string); 
        // Remove o cedilha e o ñ 
        $string = preg_replace('[ç]', 'c', $string); 
        $string = preg_replace('[ñ]', 'n', $string); 
        // Substitui os espaços em brancos por underline 
        //$string = preg_replace('( )', '_', $string); 
        // Remove hifens duplos 
        //$string = preg_replace('--', '_', $string); 
        return self::$result = $string;

    }
    
    #TAMANHO AQUIVO
    public static function tamanho($Url){
        $N = array('Bytes','KB','MB','GB');
        $Tam = filesize($Url);
        for ($Pos=0;$Tam>=1024;$Pos++) { $Tam /= 1024; }
        return self::$result = @round($Tam,2)." ".$N[$Pos];
    }

    #Token
    public static function gerar_token($tamanho, $maiusculas, $minusculas, $numeros, $simbolos){
        $ma = "ABCDEFGHIJKLMNOPQRSTUVYXWZ"; // $ma contem as letras maiúsculas
        $mi = "abcdefghijklmnopqrstuvyxwz"; // $mi contem as letras minusculas
        $nu = "0123456789"; // $nu contem os números
        $si = "!@#$%¨&*()_+="; // $si contem os símbolos
        $token = '';
            if ($maiusculas){
                // se $maiusculas for "true", a variável $ma é embaralhada e adicionada para a variável $token
                $token .= str_shuffle($ma);
            }
            if ($minusculas){
                // se $minusculas for "true", a variável $mi é embaralhada e adicionada para a variável $token
                $token .= str_shuffle($mi);
            }
            if ($numeros){
                // se $numeros for "true", a variável $nu é embaralhada e adicionada para a variável $token
                $token .= str_shuffle($nu);
            }
            if ($simbolos){
                // se $simbolos for "true", a variável $si é embaralhada e adicionada para a variável $token
                $token .= str_shuffle($si);
            }
          // retorna a token embaralhada com "str_shuffle" com o tamanho definido pela variável $tamanho
          return substr(str_shuffle($token),0,$tamanho);
      }
}