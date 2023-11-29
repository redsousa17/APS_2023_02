<?php

declare(strict_types=1);

namespace Cronos_sistema\Config\Ksi_curl;

use Cronos_sistema\Config\Ksi_curl\StringUtil;

class Url {

    private $baseUrl = null;
    private $relativeUrl = null;

    public function __construct($base_url, $relative_url = null)
    {
        $this->baseUrl = $base_url;
        $this->relativeUrl = $relative_url;
    }

    public function __toString()
    {
        return $this->absolutizeUrl();
    }

    /**
     * Remove ponto . segments.
     *
     * Interprete e remova o "." e ".." segmentos de caminho de um caminho referenciado.
    */
    public static function removeDotSegments($input)
    {
        // 1. O buffer de entrada é inicializado com o caminho agora anexado
        // componentes e o buffer de saída é inicializado para o vazio
        // string.
        $output = '';

        // 2. Enquanto o buffer de entrada não estiver vazio, faça o seguinte:
        while (!empty($input)) {
            // A. Se o buffer de entrada começar com um prefixo "../" ou "./",
            // então remova esse prefixo do buffer de entrada; por outro lado,
            if (StringUtil::startsWith($input, '../')) {
                $input = substr($input, 3);
            } elseif (StringUtil::startsWith($input, './')) {
                $input = substr($input, 2);

            // B. se o buffer de entrada começar com um prefixo "/./" ou "/.",
            // Onde "." é um segmento de caminho completo, substitua-o
            // prefixo com "/" no buffer de entrada; por outro lado,
            } elseif (StringUtil::startsWith($input, '/./')) {
                $input = substr($input, 2);
            } elseif ($input === '/.') {
                $input = '/';

            // C. se o buffer de entrada começar com um prefixo "/../" ou "/..",
            // onde ".." é um segmento de caminho completo, substitua-o
            // prefixo com "/" no buffer de entrada e remove o último
            // segmento e seu precedente  "/" (if any) from the output
            // buffer; otherwise,
            } elseif (StringUtil::startsWith($input, '/../')) {
                $input = substr($input, 3);
                $output = substr_replace($output, '', StringUtil::reversePosition($output, '/'));
            } elseif ($input === '/..') {
                $input = '/';
                $output = substr_replace($output, '', StringUtil::reversePosition($output, '/'));

            // D. se o buffer de entrada consistir apenas em "." ou "..", então remova
            // isso do buffer de entrada; por outro lado,
            } elseif ($input === '.' || $input === '..') {
                $input = '';

            // E. mover o primeiro segmento de caminho no buffer de entrada para o final de
            // o buffer de saída, incluindo o caractere inicial "/" (se
            // qualquer) e quaisquer caracteres subseqüentes até, mas não incluindo,
            // o próximo caractere "/" ou o fim do buffer de entrada.
            } elseif (!(($pos = StringUtil::position($input, '/', 1)) === false)) {
                $output .= substr($input, 0, $pos);
                $input = substr_replace($input, '', 0, $pos);
            } else {
                $output .= $input;
                $input = '';
            }
        }

        // 3. Finalmente, o buffer de saída é retornado como resultado de
        // remove_dot_segments.
        return $output . $input;
    }

    /**
     * Criar url
     *
     * @acesso público
     * @param $url
     * @param $mixed_data
     *
     * @return string
     */
    public static function buildUrl($url, $mixed_data = '')
    {
        $query_string = '';
        if (!empty($mixed_data)) {
            $query_mark = strpos($url, '?') > 0 ? '&' : '?';
            if (is_string($mixed_data)) {
                $query_string .= $query_mark . $mixed_data;
            } elseif (is_array($mixed_data)) {
                $query_string .= $query_mark . http_build_query($mixed_data, '', '&');
            }
        }
        return $url . $query_string;
    }

    /**
     * URL absoluta.
     *
     * Combine a URL base e relativa em uma URL absoluta.
    */

    private function absolutizeUrl()
    {
        $b = self::parseUrl($this->baseUrl);
        if (!isset($b['path'])) {
            $b['path'] = '/';
        }
        if ($this->relativeUrl === null) {
            return $this->unparseUrl($b);
        }
        $r = self::parseUrl($this->relativeUrl);
        $r['authorized'] = isset($r['scheme']) || isset($r['host']) || isset($r['port'])
            || isset($r['user']) || isset($r['pass']);
        $target = [];
        if (isset($r['scheme'])) {
            $target['scheme'] = $r['scheme'];
            $target['host'] = isset($r['host']) ? $r['host'] : null;
            $target['port'] = isset($r['port']) ? $r['port'] : null;
            $target['user'] = isset($r['user']) ? $r['user'] : null;
            $target['pass'] = isset($r['pass']) ? $r['pass'] : null;
            $target['path'] = isset($r['path']) ? self::removeDotSegments($r['path']) : null;
            $target['query'] = isset($r['query']) ? $r['query'] : null;
        } else {
            $target['scheme'] = isset($b['scheme']) ? $b['scheme'] : null;
            if ($r['authorized']) {
                $target['host'] = isset($r['host']) ? $r['host'] : null;
                $target['port'] = isset($r['port']) ? $r['port'] : null;
                $target['user'] = isset($r['user']) ? $r['user'] : null;
                $target['pass'] = isset($r['pass']) ? $r['pass'] : null;
                $target['path'] = isset($r['path']) ? self::removeDotSegments($r['path']) : null;
                $target['query'] = isset($r['query']) ? $r['query'] : null;
            } else {
                $target['host'] = isset($b['host']) ? $b['host'] : null;
                $target['port'] = isset($b['port']) ? $b['port'] : null;
                $target['user'] = isset($b['user']) ? $b['user'] : null;
                $target['pass'] = isset($b['pass']) ? $b['pass'] : null;
                if (!isset($r['path']) || $r['path'] === '') {
                    $target['path'] = $b['path'];
                    $target['query'] = isset($r['query']) ? $r['query'] : (isset($b['query']) ? $b['query'] : null);
                } else {
                    if (StringUtil::startsWith($r['path'], '/')) {
                        $target['path'] = self::removeDotSegments($r['path']);
                    } else {
                        $base = StringUtil::characterReversePosition($b['path'], '/', true);
                        if ($base === false) {
                            $base = '';
                        }
                        $target['path'] = self::removeDotSegments($base . '/' . $r['path']);
                    }
                    $target['query'] = isset($r['query']) ? $r['query'] : null;
                }
            }
        }
        if ($this->relativeUrl === '') {
            $target['fragment'] = isset($b['fragment']) ? $b['fragment'] : null;
        } else {
            $target['fragment'] = isset($r['fragment']) ? $r['fragment'] : null;
        }
        $absolutized_url = $this->unparseUrl($target);
        return $absolutized_url;
    }

    /**
     * Analisar url.
     *
     * Analisar url em componentes de um URI conforme especificado pela RFC 3986.
     */
    public static function parseUrl($url)
    {
        $parts = parse_url((string) $url);
        if (isset($parts['path'])) {
            $parts['path'] = self::percentEncodeChars($parts['path']);
        }
        return $parts;
    }

    /**
     * Caracteres codificados por porcentagem.
     *
     * Caracteres codificados por porcentagem para representar um octeto de dados em um componente quando
     * o caractere correspondente desse octeto está fora do conjunto permitido.
     */

    private static function percentEncodeChars($chars)
    {
        // ALPHA = AZ / az
        $alpha = 'A-Za-z';

        // DÍGITO = 0-9
        $digit = '0-9';

        // não reservado = ALPHA / DIGIT / "-" / "." / "_" / "~"
        $unreserved = $alpha . $digit . preg_quote('-._~');

        // sub-delims = "!" / "$" / "&" / "'" / "(" / ")"
        // / "*" / "+" / "," / ";" / "=" / "#"
        $sub_delims = preg_quote('!$&\'()*+,;=#');

        // HEXDIG = DÍGITO / "A" / "B" / "C" / "D" / "E" / "F"
        $hexdig = $digit . 'A-F';
        // "Os dígitos hexadecimais maiúsculos 'A' a 'F' são equivalentes a
        // os dígitos minúsculos de 'a' a 'f', respectivamente."
        $hexdig .= 'a-f';

        $pattern = '/(?:[^' . $unreserved . $sub_delims . preg_quote(':@%/?', '/') . ']++|%(?![' . $hexdig . ']{2}))/';
        $percent_encoded_chars = preg_replace_callback(
            $pattern,
            function ($matches) {
                return rawurlencode($matches[0]);
            },
            $chars
        );
        return $percent_encoded_chars;
    } 

    /**
     * Unparse url.
     *
     * Combine componentes de url em um url.
    */
    private function unparseUrl($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme'])   ?       $parsed_url['scheme'] . '://' : '';
        $user     = isset($parsed_url['user'])     ?       $parsed_url['user']           : '';
        $pass     = isset($parsed_url['pass'])     ? ':' . $parsed_url['pass']           : '';
        $pass     = ($user || $pass)               ?       $pass . '@'                   : '';
        $host     = isset($parsed_url['host'])     ?       $parsed_url['host']           : '';
        $port     = isset($parsed_url['port'])     ? ':' . $parsed_url['port']           : '';
        $path     = isset($parsed_url['path'])     ?       $parsed_url['path']           : '';
        $query    = isset($parsed_url['query'])    ? '?' . $parsed_url['query']          : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment']       : '';
        $unparsed_url =  $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
        return $unparsed_url;
    }
}