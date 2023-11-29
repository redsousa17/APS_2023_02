<?php


namespace Cronos_sistema\Config;


class Str
{
     private $string;

     public function __construct($string){
         $this->string = $string;
     }

     public function trim() {
        $this->string = trim($this->string);
        return $this;
     }

     public function replace($search, $replace){
         $this->string = str_replace($search, $replace, $this->string);
         return $this;
     }

    public function lower(){
        $this->string = strtolower($this->string);
        return $this;
    }

    public function upper(){
         $this->string = strtoupper($this->string);
         return $this;
    }

    public function isoToUtf(){
        $this->string = mb_convert_encoding($this->string, 'UTF-8', 'ISO-8859-1');
        return $this;
    }

    public function utfToIso(){
        $this->string = mb_convert_encoding($this->string, 'ISO-8859-1', 'UTF-8');
        return $this;
    }

    public function  removeAcentos(){
        $comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú');
        $semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', '0', 'U', 'U', 'U');

        $this->string =  str_replace($comAcentos, $semAcentos, $this->string);

        return $this;
    }



}