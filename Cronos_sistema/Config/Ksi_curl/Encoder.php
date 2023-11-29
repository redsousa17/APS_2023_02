<?php 
declare(strict_types=1);

namespace Cronos_sistema\Config\Ksi_curl;

class Encoder
{
    /**
     * Encode JSON
     *
     * Envolva json_encode() para gerar erro quando o valor que está sendo codificado falha.
     *
     * @access public
     * @param  $value
     * @param  $options
     * @param  $depth
     *
     * @return string
     * @throws \ErrorException
     */
    public static function encodeJson()
    {
        $args = func_get_args();
        $value = call_user_func_array('json_encode', $args);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message = 'json_encode error: ' . json_last_error_msg();
            throw new \ErrorException($error_message);
        }
        return $value;
    }
}