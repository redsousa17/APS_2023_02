<?php

namespace Cronos_sistema\Config;

use Cronos_sistema\Config\Canvas;

/// Classe d'upload de uploaded_file
class Upload {

private static $arquivo;
private static $altura;
private static $largura;
private static $pasta;

public static function move(string $directory, string $name = null, string $image_tmp){
        if(!is_dir($directory)){
            mkdir($directory, 0777, true);
        }
        //mkdir($directory, 0777, true);
        try {
            $moved = move_uploaded_file($image_tmp, $directory.$name);
        } finally {
            restore_error_handler();
        }
        if (!$moved) {
            throw new \Exception (sprintf('Could not move the file "%s" to "%s" (%s).', $directory, $name));
        }
        @chmod($name, 0666 & ~umask());
}



public static function redimensionar($width , $height ,  $destination, $img_localizacao, $nomeimgem, $_path){
    if(!is_dir($img_localizacao)){
        mkdir($img_localizacao, 0777, true);
    }
   
    copy($destination.$nomeimgem, $img_localizacao.$nomeimgem);

    try {

        $filename = $img_localizacao.$nomeimgem;

        

        list($width_orig, $height_orig, $type) = getimagesize($filename);

        $ratio_orig = $width_orig/$height_orig; 

        if ($width_orig > $height_orig) {
            $height = ($width/$width_orig) * $height_orig;
        }
        if ($width_orig < $height_orig) {
            $width  = ($height/$height_orig) * $width_orig;
        }

        
        preg_match("/\.(gif|bmp|png|jpg|jpeg){1}$/i", $filename, $ext);
        $image_p = imagecreatetruecolor($width, $height);

        switch ($type){
            case 1:	// gif
                $origem = imagecreatefromgif($filename);
                imagecopyresampled($image_p, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                imagegif($image_p, $origem);
                return true;
            break;
            case 1:	// jpg
               
                $origem = imagecreatefromjpeg($filename);
                imagecopyresampled($image_p, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                imagejpeg($image_p, 'teste.jpg');
                return true;
            break;
            case 3:	// png
                $origem = imagecreatefrompng($filename);
                imagecopyresampled($image_p, $origem, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
                imagepng($image_p, $origem);
                return true;
           break;
        }

        return true;

       // imagedestroy($image_p);
       // imagedestroy($origem);
    } catch (\Exception $e) {
        
    }
    
}



}