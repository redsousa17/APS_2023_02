<?php declare(strict_types=1);

namespace Cronos_sistema\Config\Ksi_curl;

use Cronos_sistema\Config\Ksi_curl\ArrayUtil;
use Cronos_sistema\Config\Ksi_curl\Decoder;
use Cronos_sistema\Config\Ksi_curl\Url;

class Curl
{
    const VERSION = '9.10.0';
    const DEFAULT_TIMEOUT = 30;

    public $curl = null;
    public $id = null;

    public $error = false;
    public $errorCode = 0;
    public $errorMessage = null;

    public $curlError = false;
    public $curlErrorCode = 0;
    public $curlErrorMessage = null;

    public $httpError = false;
    public $httpStatusCode = 0;
    public $httpErrorMessage = null;

    public $url = null;
    public $requestHeaders = [];

    public $responseHeaders = null;
    public $rawResponseHeaders = '';
    public $responseCookies = [];
    public $response = null;
    public $rawResponse = null;

    public $beforeSendCallback = null;
    public $downloadCompleteCallback = null;
    public $successCallback = null;
    public $errorCallback = null;
    public $completeCallback = null;
    public $fileHandle = null;
    public $downloadFileName = null;

    public $attempts = 0;
    public $retries = 0;
    public $childOfMultiCurl = false;
    public $remainingRetries = 0;
    public $retryDecider = null;

    public $jsonDecoder = null;
    public $xmlDecoder = null;

    private $headerCallbackData;
    private $cookies = [];
    private $headers = [];
    private $options = [];

    private $jsonDecoderArgs = [];
    private $jsonPattern = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';
    private $xmlDecoderArgs = [];
    private $xmlPattern = '~^(?:text/|application/(?:atom\+|rss\+|soap\+)?)xml~i';
    private $defaultDecoder = null;

    public static $RFC2616 = [
        // RFC 2616: "qualquer CHAR exceto CTLs ou separadores".
        // CHAR = <qualquer caractere US-ASCII (octetos 0 - 127)>
        // CTL = <qualquer caractere de controle US-ASCII
        // (octetos 0 - 31) e DEL (127)>
        // separadores = "(" | ")" | "<" | ">" | "@"
        // | "," | ";" | ":" | "\" | <">
        // | "/" | "[" | "]" | "?" | "="
        // | "{" | "}" | SP | HT
        // SP = <US-ASCII SP, espaço (32)>
        // HT = <US-ASCII HT, guia horizontal (9)>
        // <"> = <aspas duplas US-ASCII (34)>
        '!', '#', '$', '%', '&', "'", '*', '+', '-', '.', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B',
        'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '^', '_', '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q',
        'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '|', '~',
    ];
    public static $RFC6265 = [
        // RFC 6265: "Caracteres US-ASCII excluindo CTLs, espaço em branco DQUOTE, vírgula, ponto-e-vírgula e barra invertida".
        // %x21
        '!',
        // %x23-2B
        '#', '$', '%', '&', "'", '(', ')', '*', '+',
        // %x2D-3A
        '-', '.', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':',
        // %x3C-5B
        '<', '=', '>', '?', '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q',
        'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '[',
        // %x5D-7E
        ']', '^', '_', '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r',
        's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~',
    ];

    private static $deferredProperties = [
        'curlErrorCodeConstant',
        'curlErrorCodeConstants',
        'curlOptionCodeConstants',
        'effectiveUrl',
        'rfc2616',
        'rfc6265',
        'totalTime',
    ];

    /**
     * Construct
     *
     * @access public
     * @param  $base_url
     * @throws \ErrorException
     */
    public function __construct($base_url = null, $options = [])
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

        $this->curl = curl_init();
        $this->initialize($base_url, $options);
    }

    /**
     * Antes de enviar
     *
     * @access public
     * @param  $callback callable|null
     */
    public function beforeSend($callback)
    {
        $this->beforeSendCallback = $callback;
    }

    /**
     * Criar dados de postagem
     *
     * @access public
     * @param  $data
     *
     * @return array|string
     * @throws \ErrorException
     */
    public function buildPostData($data)
    {
        $binary_data = false;

        // Retorna string codificada em JSON quando o tipo de conteúdo da solicitação é JSON e os dados são serializáveis.
        if (isset($this->headers['Content-Type']) &&
            preg_match($this->jsonPattern, $this->headers['Content-Type']) &&
            (
                is_array($data) ||
                (
                    is_object($data) &&
                    interface_exists('JsonSerializable', false) &&
                    $data instanceof \JsonSerializable
                )
            )) {
            $data = Encoder::encodeJson($data);
        } elseif (is_array($data)) {
            // Constrói manualmente um array unidimensional a partir de um array multidimensional usando curl_setopt($ch,
            // CURLOPT_POSTFIELDS, $data) não lida corretamente com arrays multidimensionais quando os arquivos são
            // referenciado.
            if (ArrayUtil::isArrayMultidim($data)) {
                $data = ArrayUtil::arrayFlattenMultidim($data);
            }

            // Modifique os valores do array para garantir que quaisquer arquivos referenciados sejam tratados adequadamente, dependendo do suporte de
            // a API @filename ou uso de CURLFile. Isso também corrige o aviso "curl_setopt(): O uso do
            // A API @filename para upload de arquivo está obsoleta. Em vez disso, use a classe CURLFile". Ignore
            // valores que não são de arquivo prefixados com o caractere @.
            foreach ($data as $key => $value) {
                if (is_string($value) && strpos($value, '@') === 0 && is_file(substr($value, 1))) {
                    $binary_data = true;
                    if (class_exists('CURLFile')) {
                        $data[$key] = new \CURLFile(substr($value, 1));
                    }
                } elseif ($value instanceof \CURLFile) {
                    $binary_data = true;
                }
            }
        }

        if (!$binary_data &&
            (is_array($data) || is_object($data)) &&
            (
                !isset($this->headers['Content-Type']) ||
                !preg_match('/^multipart\/form-data/', $this->headers['Content-Type'])
            )) {
            // Evite usar http_build_query() pois as chaves com valores nulos são
            // excluído inesperadamente da string resultante.
            //
            // $ php -a
            // php > echo http_build_query(['a' => '1', 'b' => null, 'c' => '3']);
            // a=1&c=3
            // php > echo http_build_query(['a' => '1', 'b' => '', 'c' => '3']);
            // a=1&b=&c=3
            //
            // $data = http_build_query($data, '', '&');
            $data = implode('&', array_map(function ($k, $v) {
                // Codifica chaves e valores usando urlencode() para corresponder ao padrão
                // comportamento http_build_query() onde $encoding_type é
                // PHP_QUERY_RFC1738.
                //
                // Use strval() pois urlencode() espera um parâmetro de string:
                // TypeError: urlencode() espera que o parâmetro 1 seja string, inteiro dado
                // TypeError: urlencode() espera que o parâmetro 1 seja string, dado nulo
                //
                // php_raw_url_encode()
                // php_url_encode()
                // https://github.com/php/php-src/blob/master/ext/standard/http.c
                return urlencode(strval($k)) . '=' . urlencode(strval($v));
            }, array_keys((array)$data), array_values((array)$data)));
        }

        return $data;
    }

    /**
     * Call
     *
     * @access public
     */
    public function call()
    {
        $args = func_get_args();
        $function = array_shift($args);
        if (is_callable($function)) {
            array_unshift($args, $this);
            call_user_func_array($function, $args);
        }
    }

    /**
     * Close
     *
     * @access public
     */
    public function close()
    {
        if (is_resource($this->curl) || $this->curl instanceof \CurlHandle) {
            curl_close($this->curl);
        }
        $this->curl = null;
        $this->options = null;
        $this->jsonDecoder = null;
        $this->jsonDecoderArgs = null;
        $this->xmlDecoder = null;
        $this->xmlDecoderArgs = null;
        $this->headerCallbackData = null;
        $this->defaultDecoder = null;
    }

    /**
     * Completo
     *
     * @access public
     * @param  $callback callable|null
     */
    public function complete($callback)
    {
        $this->completeCallback = $callback;
    }

    /**
     * Progresso
     *
     * @access public
     * @param  $callback callable|null
     */
    public function progress($callback)
    {
        $this->setOpt(CURLOPT_PROGRESSFUNCTION, $callback);
        $this->setOpt(CURLOPT_NOPROGRESS, false);
    }

    /**
     * Excluir
     *
     * @access public
     * @param  $url
     * @param  $query_parameters
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function delete($url, $query_parameters = [], $data = [])
    {
        if (is_array($url)) {
            $data = $query_parameters;
            $query_parameters = $url;
            $url = (string)$this->url;
        }

        $this->setUrl($url, $query_parameters);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');

        // Evite incluir um cabeçalho de comprimento de conteúdo em solicitações DELETE, a menos que haja um corpo de mensagem. A seguir
        // incluiria "Content-Length: 0" no cabeçalho da solicitação:
        // curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        // RFC 2616 4.3 Corpo da mensagem:
        // A presença de um corpo de mensagem em uma solicitação é sinalizada pelo
        // inclusão de um campo de cabeçalho Content-Length ou Transfer-Encoding em
        // os cabeçalhos de mensagem da solicitação.
        if (!empty($data)) {
            $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        }
        return $this->exec();
    }

    /**
     * Download
     *
     * @access public
     * @param  $url
     * @param  $mixed_filename
     *
     * @return boolean
     */
    public function download($url, $mixed_filename)
    {
        // Use tmpfile() ou php://temp para evitar o erro "Muitos arquivos abertos".
        if (is_callable($mixed_filename)) {
            $this->downloadCompleteCallback = $mixed_filename;
            $this->downloadFileName = null;
            $this->fileHandle = tmpfile();
        } else {
            $filename = $mixed_filename;

            // Use um arquivo temporário ao fazer o download. Não usar um arquivo temporário pode causar um erro quando um arquivo existente
            // o download do arquivo já foi totalmente concluído e um novo download é iniciado com o mesmo destino salvar
            // caminho. A solicitação de download incluirá o cabeçalho "Range: bytes=$filesize-" que é sintaticamente válido,
            // mas insatisfatório.
            $download_filename = $filename . '.pccdownload';
            $this->downloadFileName = $download_filename;

            // Tenta retomar o download somente quando um arquivo de download temporário existir e não estiver vazio.
            if (is_file($download_filename) && $filesize = filesize($download_filename)) {
                $first_byte_position = $filesize;
                $range = $first_byte_position . '-';
                $this->setRange($range);
                $this->fileHandle = fopen($download_filename, 'ab');
            } else {
                $this->fileHandle = fopen($download_filename, 'wb');
            }

            // Mova o arquivo temporário baixado para o caminho de salvamento de destino.
            $this->downloadCompleteCallback = function ($instance, $fh) use ($download_filename, $filename) {
                // Feche o identificador de arquivo aberto antes de renomear o arquivo.
                if (is_resource($fh)) {
                    fclose($fh);
                }

                rename($download_filename, $filename);
            };
        }

        $this->setFile($this->fileHandle);
        $this->get($url);

        return ! $this->error;
    }

    /**
     * Download rápido
     *
     * @access private
     * @param  $url
     * @param  $filename
     * @param  $connections
     *
     * @return boolean
     */
    public function _fastDownload($url, $filename, $connections = 4) {
        // Primeiro precisamos recuperar o cabeçalho 'Content-Length'.
        // Use GET porque nem todos os hosts suportam solicitações HEAD.
        $this->setOpts([
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_NOBODY        => true,
            CURLOPT_HEADER        => true,
            CURLOPT_ENCODING      => '',
        ]);
        $this->setUrl($url);
        $this->exec();

        $content_length = isset($this->responseHeaders['Content-Length']) ?
            $this->responseHeaders['Content-Length'] : null;

        // Se o cabeçalho do tamanho do conteúdo estiver faltando, use o download normal.
        if (!$content_length) {
            return $this->download($url, $filename);
        }

        // Tente dividir chunk_size igualmente.
        $chunkSize = ceil($content_length / $connections);

       // Primeiros bytes.
        $offset = 0;
        $nextChunk = $chunkSize;

        // Precisamos disso mais tarde.
        $file_parts = [];

        $multi_curl = new MultiCurl();
        $multi_curl->setConcurrency($connections);

        $multi_curl->error(function ($instance) {
            return false;
        });

        for ($i = 1; $i <= $connections; $i++) {
            // Se for o último pedaço, não há necessidade de fornecê-lo.
            // O intervalo começa com 0, então subtraia 1.
            $nextChunk = $i === $connections ? '' : $nextChunk - 1;

            // Cria partes do arquivo.
            $fpath = "$filename.part$i";
            if (is_file($fpath)) {
                unlink($fpath);
            }
            $fp = fopen($fpath, 'w');

            // Rastreia todos os nomes de partes de arquivo; precisamos disso mais tarde.
            $file_parts[] = $fpath;

            $curl = new Curl();
            $curl->setOpt(CURLOPT_ENCODING, '');
            $curl->setRange("$offset-$nextChunk");
            $curl->setFile($fp);
            $curl->disableTimeout(); // caso contrário, o download pode falhar.
            $curl->setUrl($url);

            $curl->complete(function () use ($fp) {
                fclose($fp);
            });

            $multi_curl->addCurl($curl);

            if ($i !== $connections) {
                $offset = $nextChunk + 1;  // Adicione 1 para corresponder ao deslocamento.
                $nextChunk = $nextChunk + $chunkSize;
            }
        }

        // deixe a mágica começar.
        $multi_curl->start();

        // Concatena pedaços para single.
        if (is_file($filename)) {
            unlink($filename);
        }
        $mainfp = fopen($filename, 'w');
        foreach ($file_parts as $part) {
            $fp = fopen($part, 'r');
            stream_copy_to_stream($fp, $mainfp);
            fclose($fp);
            unlink($part);
        }
        fclose($mainfp);

        return true;
    }

    /**
     * Error
     *
     * @access public
     * @param  $callback callable|null
     */
    public function error($callback)
    {
        $this->errorCallback = $callback;
    }

    /**
     * Exec
     *
     * @access public
     * @param  $ch
     *
     * @return mixed Retorna o valor fornecido por parseResponse.
     */
    public function exec($ch = null)
    {
        $this->attempts += 1;

        if ($this->jsonDecoder === null) {
            $this->setDefaultJsonDecoder();
        }
        if ($this->xmlDecoder === null) {
            $this->setDefaultXmlDecoder();
        }

        if ($ch === null) {
            $this->responseCookies = [];
            $this->call($this->beforeSendCallback);
            $this->rawResponse = curl_exec($this->curl);
            $this->curlErrorCode = curl_errno($this->curl);
            $this->curlErrorMessage = curl_error($this->curl);
        } else {
            $this->rawResponse = curl_multi_getcontent($ch);
            $this->curlErrorMessage = curl_error($ch);
        }
        $this->curlError = $this->curlErrorCode !== 0;

        // Certifique-se de que Curl::rawResponse seja uma string, pois curl_exec() pode retornar false.
        // Sem isso, chamar strlen($curl->rawResponse) irá gerar erro quando o
        // configuração de tipos estritos está habilitada.
        if (!is_string($this->rawResponse)) {
            $this->rawResponse = '';
        }

        // Transfira os dados de retorno de chamada do cabeçalho e libere o armazenamento temporário para evitar vazamento de memória.
        $this->rawResponseHeaders = $this->headerCallbackData->rawResponseHeaders;
        $this->responseCookies = $this->headerCallbackData->responseCookies;
        $this->headerCallbackData->rawResponseHeaders = '';
        $this->headerCallbackData->responseCookies = [];
        $this->headerCallbackData->stopRequestDecider = null;
        $this->headerCallbackData->stopRequest = false;

        // Inclui informações adicionais de código de erro na mensagem de erro quando possível.
        if ($this->curlError) {
            $curl_error_message = curl_strerror($this->curlErrorCode);

            if ($this->curlErrorCodeConstant !== '') {
                $curl_error_message .= ' (' . $this->curlErrorCodeConstant . ')';
            }

            if (!empty($this->curlErrorMessage)) {
                $curl_error_message .= ': ' . $this->curlErrorMessage;
            }

            $this->curlErrorMessage = $curl_error_message;
        }

        $this->httpStatusCode = $this->getInfo(CURLINFO_HTTP_CODE);
        $this->httpError = in_array((int) floor($this->httpStatusCode / 100), [4, 5], true);
        $this->error = $this->curlError || $this->httpError;
        $this->errorCode = $this->error ? ($this->curlError ? $this->curlErrorCode : $this->httpStatusCode) : 0;

        // NOTA: CURLINFO_HEADER_OUT definido como verdadeiro é necessário para requestHeaders
        // para não ficar vazio (por exemplo $curl->setOpt(CURLINFO_HEADER_OUT, true);).
        if ($this->getOpt(CURLINFO_HEADER_OUT) === true) {
            $this->requestHeaders = $this->parseRequestHeaders($this->getInfo(CURLINFO_HEADER_OUT));
        }
        $this->responseHeaders = $this->parseResponseHeaders($this->rawResponseHeaders);
        $this->response = $this->parseResponse($this->responseHeaders, $this->rawResponse);

        $this->httpErrorMessage = '';
        if ($this->error) {
            if (isset($this->responseHeaders['Status-Line'])) {
                $this->httpErrorMessage = $this->responseHeaders['Status-Line'];
            }
        }
        $this->errorMessage = $this->curlError ? $this->curlErrorMessage : $this->httpErrorMessage;

        // Redefina as propriedades adiadas selecionadas para que possam ser recalculadas.
        unset($this->curlErrorCodeConstant);
        unset($this->effectiveUrl);
        unset($this->totalTime);

        // Redefine o cabeçalho de comprimento do conteúdo possivelmente definido a partir de uma solicitação PUT ou SEARCH.
        $this->unsetHeader('Content-Length');

        // Redefine a configuração de ninguém possivelmente definida a partir de uma solicitação HEAD.
        $this->setOpt(CURLOPT_NOBODY, false);

        // Permitir que o multicurl tente novamente conforme necessário.
        if ($this->isChildOfMultiCurl()) {
            return;
        }

        if ($this->attemptRetry()) {
            return $this->exec($ch);
        }

        $this->execDone();

        return $this->response;
    }

    public function execDone()
    {
        if ($this->error) {
            $this->call($this->errorCallback);
        } else {
            $this->call($this->successCallback);
        }

        $this->call($this->completeCallback);

        // Feche os manipuladores de arquivos abertos e redefina a instância do curl.
        if ($this->fileHandle !== null) {
            $this->downloadComplete($this->fileHandle);
        }
    }

    /**
     * Get
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function get($url, $data = [])
    {
        if (is_array($url)) {
            $data = $url;
            $url = (string)$this->url;
        }
        $this->setUrl($url, $data);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $this->setOpt(CURLOPT_HTTPGET, true);
        return $this->exec();
    }

    /**
     * Get Info
     *
     * @access public
     * @param  $opt
     *
     * @return mixed
     */
    public function getInfo($opt = null)
    {
        $args = [];
        $args[] = $this->curl;

        if (func_num_args()) {
            $args[] = $opt;
        }

        return call_user_func_array('curl_getinfo', $args);
    }

    /**
     * Get Opt
     *
     * @access public
     * @param  $option
     *
     * @return mixed
     */
    public function getOpt($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Head
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function head($url, $data = [])
    {
        if (is_array($url)) {
            $data = $url;
            $url = (string)$this->url;
        }
        $this->setUrl($url, $data);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'HEAD');
        $this->setOpt(CURLOPT_NOBODY, true);
        return $this->exec();
    }

    /**
     * Options
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function options($url, $data = [])
    {
        if (is_array($url)) {
            $data = $url;
            $url = (string)$this->url;
        }
        $this->setUrl($url, $data);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        return $this->exec();
    }

    /**
     * Patch
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function patch($url, $data = [])
    {
        if (is_array($url)) {
            $data = $url;
            $url = (string)$this->url;
        }

        if (is_array($data) && empty($data)) {
            $this->removeHeader('Content-Length');
        }

        $this->setUrl($url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->exec();
    }

    /**
     * Post
     *
     * @access public
     * @param  $url
     * @param  $data
     * @param  $follow_303_with_post
     * Se true, fará com que 303 redirecionamentos sejam seguidos usando uma solicitação POST (padrão: false).
     * Notas:
     * - Os redirecionamentos são seguidos apenas se a opção CURLOPT_FOLLOWLOCATION estiver definida como true.
     * - De acordo com as especificações do HTTP (ver [1]), um redirecionamento 303 deve ser seguido usando
     * o método GET. 301 e 302 não devem.
     * - Para forçar um redirecionamento 303 a ser executado usando o mesmo método, o
     * o objeto cURL subjacente deve ser definido em um estado especial (o CURLOPT_CUSTOMREQUEST
     * deve ser definida como o método a ser usado após o redirecionamento). Devido a uma limitação
     * da extensão cURL do PHP < 5.5.11 ([2], [3]), não é possível redefinir isso
     * opção. Usando esses mecanismos PHP, é impossível restaurar esse comportamento
     * em um objeto Curl de classe php-curl existente.
     *
     * @return mixed Retorna o valor fornecido por exec.
     *
     * [1] https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.2
     * [2] https://github.com/php/php-src/pull/531
     * [3] http://php.net/ChangeLog-5.php#5.5.11
     */
    public function post($url, $data = '', $follow_303_with_post = false)
    {
        if (is_array($url)) {
            $follow_303_with_post = (bool)$data;
            $data = $url;
            $url = (string)$this->url;
        }

        $this->setUrl($url);

        // Defina o método de solicitação como "POST" ao seguir um redirecionamento 303 com
        // uma solicitação POST adicional é desejada. Isso é equivalente a definir
        // a opção de linha de comando -X, --request onde o curl não mudará o
        // método de solicitação de acordo com o código de resposta HTTP 30x.
        if ($follow_303_with_post) {
            $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        } elseif (isset($this->options[CURLOPT_CUSTOMREQUEST])) {
            // Desmarque a opção CURLOPT_CUSTOMREQUEST para que o curl não use
            // uma solicitação POST após um redirecionamento post/redirect/get. Sem
            // isso, curl usará a string de método especificada para todas as solicitações.
            $this->setOpt(CURLOPT_CUSTOMREQUEST, null);
        }

        $this->setOpt(CURLOPT_POST, true);
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->exec();
    }

    /**
     * Put
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function put($url, $data = [])
    {
        if (is_array($url)) {
            $data = $url;
            $url = (string)$this->url;
        }
        $this->setUrl($url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $put_data = $this->buildPostData($data);
        if (empty($this->options[CURLOPT_INFILE]) && empty($this->options[CURLOPT_INFILESIZE])) {
            if (is_string($put_data)) {
                $this->setHeader('Content-Length', strlen($put_data));
            }
        }
        if (!empty($put_data)) {
            $this->setOpt(CURLOPT_POSTFIELDS, $put_data);
        }
        return $this->exec();
    }

    /**
     * Search
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Retorna o valor fornecido por exec.
     */
    public function search($url, $data = [])
    {
        if (is_array($url)) {
            $data = $url;
            $url = (string)$this->url;
        }
        $this->setUrl($url);
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'SEARCH');
        $put_data = $this->buildPostData($data);
        if (empty($this->options[CURLOPT_INFILE]) && empty($this->options[CURLOPT_INFILESIZE])) {
            if (is_string($put_data)) {
                $this->setHeader('Content-Length', strlen($put_data));
            }
        }
        if (!empty($put_data)) {
            $this->setOpt(CURLOPT_POSTFIELDS, $put_data);
        }
        return $this->exec();
    }

    /**
     * Set Basic Authentication
     *
     * @access public
     * @param  $username
     * @param  $password
     */
    public function setBasicAuthentication($username, $password = '')
    {
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set Digest Authentication
     *
     * @access public
     * @param  $username
     * @param  $password
     */
    public function setDigestAuthentication($username, $password = '')
    {
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set Cookie
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    public function setCookie($key, $value)
    {
        $this->setEncodedCookie($key, $value);
        $this->buildCookies();
    }

    /**
     * Set Cookies
     *
     * @access public
     * @param  $cookies
     */
    public function setCookies($cookies)
    {
        foreach ($cookies as $key => $value) {
            $this->setEncodedCookie($key, $value);
        }
        $this->buildCookies();
    }

    /**
     * Get Cookie
     *
     * @access public
     * @param  $key
     *
     * @return mixed
     */
    public function getCookie($key)
    {
        return $this->getResponseCookie($key);
    }

    /**
     * Get Response Cookie
     *
     * @access public
     * @param  $key
     *
     * @return mixed
     */
    public function getResponseCookie($key)
    {
        return isset($this->responseCookies[$key]) ? $this->responseCookies[$key] : null;
    }

    /**
     * Set Max Filesize
     *
     * @access public
     * @param  $bytes
     */
    public function setMaxFilesize($bytes)
    {
        $callback = function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($bytes) {
            // Aborta a transferência quando $bytes baixados excede o máximo de $bytes retornando um valor diferente de zero.
            return $downloaded > $bytes ? 1 : 0;
        };
        $this->progress($callback);
    }

    /**
     * Set Port
     *
     * @access public
     * @param  $port
     */
    public function setPort($port)
    {
        $this->setOpt(CURLOPT_PORT, (int) $port);
    }

    /**
     * Set Connect Timeout
     *
     * @access public
     * @param  $seconds
     */
    public function setConnectTimeout($seconds)
    {
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $seconds);
    }

    /**
     * Set Cookie String
     *
     * @access public
     * @param  $string
     *
     * @return bool
     */
    public function setCookieString($string)
    {
        return $this->setOpt(CURLOPT_COOKIE, $string);
    }

    /**
     * Set Cookie File
     *
     * @access public
     * @param  $cookie_file
     *
     * @return boolean
     */
    public function setCookieFile($cookie_file)
    {
        return $this->setOpt(CURLOPT_COOKIEFILE, $cookie_file);
    }

    /**
     * Set Cookie Jar
     *
     * @access public
     * @param  $cookie_jar
     *
     * @return boolean
     */
    public function setCookieJar($cookie_jar)
    {
        return $this->setOpt(CURLOPT_COOKIEJAR, $cookie_jar);
    }

    /**
     * Set Default JSON Decoder
     *
     * @access public
     * @param  $assoc
     * @param  $depth
     * @param  $options
     */
    public function setDefaultJsonDecoder()
    {
        $this->jsonDecoder = 'Cronos_sistema\Config\Ksi_curl\Decoder::decodeJson';
        $this->jsonDecoderArgs = func_get_args();
    }

    /**
     * Set Default XML Decoder
     *
     * @access public
     * @param  $class_name
     * @param  $options
     * @param  $ns
     * @param  $is_prefix
     */
    public function setDefaultXmlDecoder()
    {
        $this->xmlDecoder = 'Cronos_sistema\Config\Ksi_curl\Decoder::decodeXml';
        $this->xmlDecoderArgs = func_get_args();
    }

    /**
     * Set Default Decoder
     *
     * @access public
     * @param  $mixed boolean|callable|string
     */
    public function setDefaultDecoder($mixed = 'json')
    {
        if ($mixed === false) {
            $this->defaultDecoder = false;
        } elseif (is_callable($mixed)) {
            $this->defaultDecoder = $mixed;
        } else {
            if ($mixed === 'json') {
                $this->defaultDecoder = 'Cronos_sistema\Config\Ksi_curl\Decoder::decodeJson';
            } elseif ($mixed === 'xml') {
                $this->defaultDecoder = 'Cronos_sistema\Config\Ksi_curl\Decoder::decodeXml';
            }
        }
    }

    /**
     * Set Default Header Out
     *
     * @access public
     */
    public function setDefaultHeaderOut()
    {
        $this->setOpt(CURLINFO_HEADER_OUT, true);
    }

    /**
     * Set Default Timeout
     *
     * @access public
     */
    public function setDefaultTimeout()
    {
        $this->setTimeout(self::DEFAULT_TIMEOUT);
    }

    /**
     * Set Default User Agent
     *
     * @access public
     */
    public function setDefaultUserAgent()
    {
        $user_agent = 'PHP-Curl-Class/' . self::VERSION . ' (+https://ws.ksiseguros.com.br/api/paginas/curl.html)';
        $user_agent .= ' PHP/' . PHP_VERSION;
        $curl_version = curl_version();
        $user_agent .= ' curl/' . $curl_version['version'];
        $this->setUserAgent($user_agent);
    }

    /**
     * Set File
     *
     * @access public
     * @param  $file
     */
    public function setFile($file)
    {
        $this->setOpt(CURLOPT_FILE, $file);
    }

    /**
     * Set Header
     *
     * Add extra header to include in the request.
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set Headers
     *
     * Add extra headers to include in the request.
     *
     * @access public
     * @param  $headers
     */
    public function setHeaders($headers)
    {
        if (ArrayUtil::isArrayAssoc($headers)) {
            foreach ($headers as $key => $value) {
                $key = trim($key);
                $value = trim($value);
                $this->headers[$key] = $value;
            }
        } else {
            foreach ($headers as $header) {
                list($key, $value) = explode(':', $header, 2);
                $key = trim($key);
                $value = trim($value);
                $this->headers[$key] = $value;
            }
        }

        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set JSON Decoder
     *
     * @access public
     * @param  $mixed boolean|callable
     */
    public function setJsonDecoder($mixed)
    {
        if ($mixed === false || is_callable($mixed)) {
            $this->jsonDecoder = $mixed;
            $this->jsonDecoderArgs = [];
        }
    }

    /**
     * Set XML Decoder
     *
     * @access public
     * @param  $mixed boolean|callable
     */
    public function setXmlDecoder($mixed)
    {
        if ($mixed === false || is_callable($mixed)) {
            $this->xmlDecoder = $mixed;
            $this->xmlDecoderArgs = [];
        }
    }

    /**
     * Set Opt
     *
     * @access public
     * @param  $option
     * @param  $value
     *
     * @return boolean
     */
    public function setOpt($option, $value)
    {
        $required_options = [
            CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER',
        ];

        if (in_array($option, array_keys($required_options), true) && $value !== true) {
            trigger_error($required_options[$option] . ' is a required option', E_USER_WARNING);
        }

        $success = curl_setopt($this->curl, $option, $value);
        if ($success) {
            $this->options[$option] = $value;
        }
        return $success;
    }

    /**
     * Set Opts
     *
     * @access public
     * @param  $options
     *
     * @return boolean
     * Retorna true se todas as opções foram definidas com sucesso. Se uma opção não pôde ser definida com sucesso, false é
     * retornado imediatamente, ignorando quaisquer opções futuras na matriz de opções. Semelhante a curl_setopt_array().
     */
    public function setOpts($options)
    {
        foreach ($options as $option => $value) {
            if (!$this->setOpt($option, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set Proxy
     *
     * Set an HTTP proxy to tunnel requests through.
     *
     * @access public
    * @param $proxy - O proxy HTTP para o túnel de solicitações. Pode incluir o número da porta.
     * @param $port - O número da porta do proxy ao qual se conectar. Este número de porta também pode ser definido em $proxy.
     * @param $username - O nome de usuário a ser usado para a conexão com o proxy.
     * @param $password - A senha a ser usada para a conexão com o proxy.
     */
    public function setProxy($proxy, $port = null, $username = null, $password = null)
    {
        $this->setOpt(CURLOPT_PROXY, $proxy);
        if ($port !== null) {
            $this->setOpt(CURLOPT_PROXYPORT, $port);
        }
        if ($username !== null && $password !== null) {
            $this->setOpt(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        }
    }

    /**
     * Set Proxy Auth
     *
     * Defina o(s) método(s) de autenticação HTTP a serem usados ​​para a conexão proxy.
     *
     * @access public
     * @param  $auth
     */
    public function setProxyAuth($auth)
    {
        $this->setOpt(CURLOPT_PROXYAUTH, $auth);
    }

    /**
     * Set Proxy Type
     *
     * Set the proxy protocol type.
     *
     * @access public
     * @param  $type
     */
    public function setProxyType($type)
    {
        $this->setOpt(CURLOPT_PROXYTYPE, $type);
    }

    /**
     * Set Proxy Tunnel
     *
     * Set the proxy to tunnel through HTTP proxy.
     *
     * @access public
     * @param  $tunnel boolean
     */
    public function setProxyTunnel($tunnel = true)
    {
        $this->setOpt(CURLOPT_HTTPPROXYTUNNEL, $tunnel);
    }

    /**
     * Unset Proxy
     *
     * Disable use of the proxy.
     *
     * @access public
     */
    public function unsetProxy()
    {
        $this->setOpt(CURLOPT_PROXY, null);
    }

    /**
     * Set Range
     *
     * @access public
     * @param  $range
     */
    public function setRange($range)
    {
        $this->setOpt(CURLOPT_RANGE, $range);
    }

    /**
     * Set Referer
     *
     * @access public
     * @param  $referer
     */
    public function setReferer($referer)
    {
        $this->setReferrer($referer);
    }

    /**
     * Set Referrer
     *
     * @access public
     * @param  $referrer
     */
    public function setReferrer($referrer)
    {
        $this->setOpt(CURLOPT_REFERER, $referrer);
    }

    /**
     * Set Retry
     *
      * Número de novas tentativas para tentar ou callable de decisão.
     *
     * Ao usar um número de tentativas para tentar, o número máximo de tentativas
     * para a solicitação é $maximum_number_of_retries + 1.
     *
     * Ao usar um decisor callable, a solicitação será repetida até que o
     * A função retorna um valor que é avaliado como falso.
     *
     * @access public
     * @param  $mixed
     */
    public function setRetry($mixed)
    {
        if (is_callable($mixed)) {
            $this->retryDecider = $mixed;
        } elseif (is_int($mixed)) {
            $maximum_number_of_retries = $mixed;
            $this->remainingRetries = $maximum_number_of_retries;
        }
    }

    /**
     * Set Timeout
     *
     * @access public
     * @param  $seconds
     */
    public function setTimeout($seconds)
    {
        $this->setOpt(CURLOPT_TIMEOUT, $seconds);
    }

    /**
     * Disable Timeout
     *
     * @access public
     */
    public function disableTimeout()
    {
        $this->setTimeout(null);
    }

    /**
     * Set Url
     *
     * @access public
     * @param  $url
     * @param  $mixed_data
     */
    public function setUrl($url, $mixed_data = '')
    {
        $built_url = Url::buildUrl($url, $mixed_data);

        if ($this->url === null) {
            $this->url = (string)new Url($built_url);
        } else {
            $this->url = (string)new Url($this->url, $built_url);
        }

        $this->setOpt(CURLOPT_URL, $this->url);
    }

    /**
     * Set User Agent
     *
     * @access public
     * @param  $user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    /**
     * Set Interface
     *
     * O nome da interface de rede de saída a ser usada.
     * Pode ser um nome de interface, um endereço IP ou um nome de host.
     *
     * @access public
     * @param  $interface
     */
    public function setInterface($interface)
    {
        $this->setOpt(CURLOPT_INTERFACE, $interface);
    }

    /**
     * Attempt Retry
     *
     * @access public
     */
    public function attemptRetry()
    {
        $attempt_retry = false;
        if ($this->error) {
            if ($this->retryDecider === null) {
                $attempt_retry = $this->remainingRetries >= 1;
            } else {
                $attempt_retry = call_user_func($this->retryDecider, $this);
            }
            if ($attempt_retry) {
                $this->retries += 1;
                if ($this->remainingRetries) {
                    $this->remainingRetries -= 1;
                }
            }
        }
        return $attempt_retry;
    }

    /**
     * Success
     *
     * @access public
     * @param  $callback callable|null
     */
    public function success($callback)
    {
        $this->successCallback = $callback;
    }

    /**
     * Unset Header
     *
     * Remova o cabeçalho extra definido anteriormente usando Curl::setHeader().
     *
     * @access public
     * @param  $key
     */
    public function unsetHeader($key)
    {
        unset($this->headers[$key]);
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Remove Header
     *
     * Remova um cabeçalho interno da solicitação.
     * Usar `curl -H "Host:" ...' é equivalente a $curl->removeHeader('Host');.
     *
     * @access public
     * @param  $key
     */
    public function removeHeader($key)
    {
        $this->setHeader($key, '');
    }

    /**
     * Verbose
     *
     * @access public
     * @param  bool $on
     * @param  resource|string $output
     */
    public function verbose($on = true, $output = 'STDERR')
    {
        if ($output === 'STDERR') {
            if (!defined('STDERR')) {
                define('STDERR', fopen('php://stderr', 'wb'));
            }
            $output = STDERR;
        }

        // Desligue CURLINFO_HEADER_OUT para verbose funcionar. isso tem o lado
        // efeito de fazer com que Curl::requestHeaders fique vazio.
        if ($on) {
            $this->setOpt(CURLINFO_HEADER_OUT, false);
        }
        $this->setOpt(CURLOPT_VERBOSE, $on);
        $this->setOpt(CURLOPT_STDERR, $output);
    }

    /**
     * Diagnose
     *
     * @access public
     * @param  bool $return
     */
    public function diagnose($return = false)
    {
        if ($return) {
            ob_start();
        }

        echo "\n";
        echo '--- Begin PHP Curl Class diagnostic output ---' . "\n";
        echo 'PHP Curl Class version: ' . self::VERSION . "\n";
        echo 'PHP version: ' . PHP_VERSION . "\n";

        $curl_version = curl_version();
        echo 'Curl version: ' . $curl_version['version'] . "\n";

        if ($this->attempts === 0) {
            echo 'No HTTP requests have been made.' . "\n";
        } else {
            $request_method = $this->getOpt(CURLOPT_CUSTOMREQUEST);
            $request_url = $this->getOpt(CURLOPT_URL);
            $request_options_count = count($this->options);
            $request_headers_count = count($this->requestHeaders);
            $request_body_empty = empty($this->getOpt(CURLOPT_POSTFIELDS));
            $response_header_length = isset($this->responseHeaders['Content-Length']) ?
                $this->responseHeaders['Content-Length'] : '(not specified in response header)';
            $response_calculated_length = is_string($this->rawResponse) ?
                strlen($this->rawResponse) : '(' . var_export($this->rawResponse, true) . ')';
            $response_headers_count = count($this->responseHeaders);

            echo
                'Request contained ' . $request_options_count . ' ' . (
                    $request_options_count === 1 ? 'option:' : 'options:'
                ) . "\n";
            if ($request_options_count) {
                $i = 1;
                foreach ($this->options as $option => $value) {
                    echo '    ' . $i . ' ';
                    if (isset($this->curlOptionCodeConstants[$option])) {
                        echo $this->curlOptionCodeConstants[$option] . ':';
                    } else {
                        echo $option . ':';
                    }

                    if (is_string($value)) {
                        echo ' ' . $value . "\n";
                    } elseif (is_int($value)) {
                        echo ' ' . $value . "\n";
                    } elseif (is_bool($value)) {
                        echo ' ' . ($value ? 'true' : 'false') . "\n";
                    } elseif (is_callable($value)) {
                        echo ' (callable)' . "\n";
                    } else {
                        echo ' ' . gettype($value) . ':' . "\n";
                        var_dump($value);
                    }
                    $i += 1;
                }
            }

            echo
                'Sent an HTTP '   . $request_method . ' request to "' . $request_url . '".' . "\n" .
                'Request contained ' . $request_headers_count . ' ' . (
                    $request_headers_count === 1 ? 'header:' : 'headers:'
                ) . "\n";
            if ($request_headers_count) {
                $i = 1;
                foreach ($this->requestHeaders as $key => $value) {
                    echo '    ' . $i . ' ' . $key . ': ' . $value . "\n";
                    $i += 1;
                }
            }

            echo 'Request contained ' . ($request_body_empty ? 'no body' : 'a body') . '.' . "\n";

            if ($request_headers_count === 0 && (
                $this->getOpt(CURLOPT_VERBOSE) ||
                $this->getOpt(CURLINFO_HEADER_OUT) !== true
            )) {
                echo
                    'Warning: Request headers (Curl::requestHeaders) are expected be empty ' .
                    '(CURLOPT_VERBOSE was enabled or CURLINFO_HEADER_OUT was disabled).' . "\n";
            }

            echo
                'Response contains ' . $response_headers_count . ' ' . (
                    $response_headers_count === 1 ? 'header:' : 'headers:'
                ) . "\n";
            if ($this->responseHeaders !== null) {
                $i = 1;
                foreach ($this->responseHeaders as $key => $value) {
                    echo '    ' . $i . ' ' . $key . ': ' . $value . "\n";
                    $i += 1;
                }
            }

            if (!isset($this->responseHeaders['Content-Type'])) {
                echo 'Response did not set a content type' . "\n";
            } elseif (preg_match($this->jsonPattern, $this->responseHeaders['Content-Type'])) {
                echo 'Response appears to be JSON' . "\n";
            } elseif (preg_match($this->xmlPattern, $this->responseHeaders['Content-Type'])) {
                echo 'Response appears to be XML' . "\n";
            }

            if ($this->curlError) {
                echo
                    'A curl error (' . $this->curlErrorCode . ') occurred ' .
                    'with message "' . $this->curlErrorMessage . '".' . "\n";
            }
            if (!empty($this->httpStatusCode)) {
                echo 'Received an HTTP status code of ' . $this->httpStatusCode . '.' . "\n";
            }
            if ($this->httpError) {
                echo
                    'Received an HTTP ' . $this->httpStatusCode . ' error response ' .
                    'with message "' . $this->httpErrorMessage . '".' . "\n";
            }

            if ($this->rawResponse === null) {
                echo 'Received no response body (response=null).' . "\n";
            } elseif ($this->rawResponse === '') {
                echo 'Received an empty response body (response="").' . "\n";
            } else {
                echo 'Received a non-empty response body.' . "\n";
                if (isset($this->responseHeaders['Content-Length'])) {
                    echo 'Response content length (from content-length header): ' . $response_header_length . "\n";
                } else {
                    echo 'Response content length (calculated): ' . $response_calculated_length . "\n";
                }
            }
        }

        echo '--- End PHP Curl Class diagnostic output ---' . "\n";
        echo "\n";

        if ($return) {
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
    }

    /**
     * Reset
     *
     * @access public
     */
    public function reset()
    {
        if (is_resource($this->curl) || $this->curl instanceof \CurlHandle) {
            curl_reset($this->curl);
        } else {
            $this->curl = curl_init();
        }

        $this->setDefaultUserAgent();
        $this->setDefaultTimeout();
        $this->setDefaultHeaderOut();

        $this->initialize();
    }

    /**
     * Set auto referer
     *
     * @access public
     */
    public function setAutoReferer($auto_referer = true)
    {
        $this->setAutoReferrer($auto_referer);
    }

    /**
     * Set auto referrer
     *
     * @access public
     */
    public function setAutoReferrer($auto_referrer = true)
    {
        $this->setOpt(CURLOPT_AUTOREFERER, $auto_referrer);
    }

    /**
     * Set follow location
     *
     * @access public
     */
    public function setFollowLocation($follow_location = true)
    {
        $this->setOpt(CURLOPT_FOLLOWLOCATION, $follow_location);
    }

    /**
     * Set forbid reuse
     *
     * @access public
     */
    public function setForbidReuse($forbid_reuse = true)
    {
        $this->setOpt(CURLOPT_FORBID_REUSE, $forbid_reuse);
    }

    /**
     * Set maximum redirects
     *
     * @access public
     */
    public function setMaximumRedirects($maximum_redirects)
    {
        $this->setOpt(CURLOPT_MAXREDIRS, $maximum_redirects);
    }

    public function getCurl()
    {
        return $this->curl;
    }

    public function getId()
    {
        return $this->id;
    }

    public function isError()
    {
        return $this->error;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function isCurlError()
    {
        return $this->curlError;
    }

    public function getCurlErrorCode()
    {
        return $this->curlErrorCode;
    }

    public function getCurlErrorMessage()
    {
        return $this->curlErrorMessage;
    }

    public function isHttpError()
    {
        return $this->httpError;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    public function getHttpErrorMessage()
    {
        return $this->httpErrorMessage;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    public function getRawResponseHeaders()
    {
        return $this->rawResponseHeaders;
    }

    public function getResponseCookies()
    {
        return $this->responseCookies;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function getBeforeSendCallback()
    {
        return $this->beforeSendCallback;
    }

    public function getDownloadCompleteCallback()
    {
        return $this->downloadCompleteCallback;
    }

    public function getDownloadFileName()
    {
        return $this->downloadFileName;
    }

    public function getSuccessCallback()
    {
        return $this->successCallback;
    }

    public function getErrorCallback()
    {
        return $this->errorCallback;
    }

    public function getCompleteCallback()
    {
        return $this->completeCallback;
    }

    public function getFileHandle()
    {
        return $this->fileHandle;
    }

    public function getAttempts()
    {
        return $this->attempts;
    }

    public function getRetries()
    {
        return $this->retries;
    }

    public function isChildOfMultiCurl()
    {
        return $this->childOfMultiCurl;
    }

    public function getRemainingRetries()
    {
        return $this->remainingRetries;
    }

    public function getRetryDecider()
    {
        return $this->retryDecider;
    }

    public function getJsonDecoder()
    {
        return $this->jsonDecoder;
    }

    public function getXmlDecoder()
    {
        return $this->xmlDecoder;
    }

    /**
     * Destruct
     *
     * @access public
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __get($name)
    {
        $return = null;
        if (in_array($name, self::$deferredProperties, true) && is_callable([$this, $getter = '_get_' . $name])) {
            $return = $this->$name = $this->$getter();
        }
        return $return;
    }

    /**
     * Get Curl Error Code Constants
     *
     * @access private
     */
    private function _get_curlErrorCodeConstants()
    {
        $constants = get_defined_constants(true);
        $filtered_array = array_filter(
            $constants['curl'],
            function ($key) {
                return strpos($key, 'CURLE_') !== false;
            },
            ARRAY_FILTER_USE_KEY
        );
        $curl_const_by_code = array_flip($filtered_array);
        return $curl_const_by_code;
    }

    /**
     * Get Curl Error Code Constant
     *
     * @access private
     */
    private function _get_curlErrorCodeConstant()
    {
        $curl_const_by_code = $this->curlErrorCodeConstants;
        if (isset($curl_const_by_code[$this->curlErrorCode])) {
            return $curl_const_by_code[$this->curlErrorCode];
        }
        return '';
    }

    /**
     * Get Curl Option Code Constants
     *
     * @access private
     */
    private function _get_curlOptionCodeConstants()
    {
        $constants = get_defined_constants(true);
        $filtered_array = array_filter(
            $constants['curl'],
            function ($key) {
                return strpos($key, 'CURLOPT_') !== false;
            },
            ARRAY_FILTER_USE_KEY
        );
        $curl_const_by_code = array_flip($filtered_array);
        return $curl_const_by_code;
    }

    /**
     * Get Effective Url
     *
     * @access private
     */
    private function _get_effectiveUrl()
    {
        return $this->getInfo(CURLINFO_EFFECTIVE_URL);
    }

    /**
     * Get RFC 2616
     *
     * @access private
     */
    private function _get_rfc2616()
    {
        return array_fill_keys(self::$RFC2616, true);
    }

    /**
     * Get RFC 6265
     *
     * @access private
     */
    private function _get_rfc6265()
    {
        return array_fill_keys(self::$RFC6265, true);
    }

    /**
     * Get Total Time
     *
     * @access private
     */
    private function _get_totalTime()
    {
        return $this->getInfo(CURLINFO_TOTAL_TIME);
    }

    /**
     * Build Cookies
     *
     * @access private
     */
    private function buildCookies()
    {
        // Evite alterar CURLOPT_COOKIE se não houver cookies definidos.
        if (count($this->cookies)) {
            // Evite usar http_build_query() porque a codificação desnecessária é executada.
            // http_build_query($this->cookies, '', '; ');
            $this->setOpt(CURLOPT_COOKIE, implode('; ', array_map(function ($k, $v) {
                return $k . '=' . $v;
            }, array_keys($this->cookies), array_values($this->cookies))));
        }
    }

    /**
     * Download Complete
     *
     * @access private
     * @param  $fh
     */
    private function downloadComplete($fh)
    {
        if ($this->error && is_file((string) $this->downloadFileName)) {
            @unlink($this->downloadFileName);
        } elseif (!$this->error && $this->downloadCompleteCallback) {
            rewind($fh);
            $this->call($this->downloadCompleteCallback, $fh);
            $this->downloadCompleteCallback = null;
        }

        if (is_resource($fh)) {
            fclose($fh);
        }

        // Corrige "Aviso do PHP: Uso de constante indefinida STDOUT" ao ler o
        // script PHP de stdin. Usar nulo causa "Aviso: curl_setopt():
        // argumento fornecido não é um recurso File-Handle válido".
        if (!defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'w'));
        }

        // Redefina CURLOPT_FILE com STDOUT para evitar: "curl_exec(): CURLOPT_FILE
        // o recurso desapareceu, redefinindo para o padrão".
        $this->setFile(STDOUT);

        // Redefina CURLOPT_RETURNTRANSFER para dizer ao cURL para retornar
        // respostas como o valor de retorno de curl_exec(). Sem isso,
        // curl_exec() reverterá para retornar valores booleanos.
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Parse Headers
     *
     * @access private
     * @param  $raw_headers
     *
     * @return array
     */
    private function parseHeaders($raw_headers)
    {
        $raw_headers = preg_split('/\r\n/', (string) $raw_headers, -1, PREG_SPLIT_NO_EMPTY);
        $http_headers = new CaseInsensitiveArray();

        $raw_headers_count = count($raw_headers);
        for ($i = 1; $i < $raw_headers_count; $i++) {
            if (strpos($raw_headers[$i], ':') !== false) {
                list($key, $value) = explode(':', $raw_headers[$i], 2);
                $key = trim($key);
                $value = trim($value);
                // Use isset() como array_key_exists() e ArrayAccess não são compatíveis.
                if (isset($http_headers[$key])) {
                    $http_headers[$key] .= ',' . $value;
                } else {
                    $http_headers[$key] = $value;
                }
            }
        }

        return [isset($raw_headers['0']) ? $raw_headers['0'] : '', $http_headers];
    }

    /**
     * Parse Request Headers
     *
     * @access private
     * @param  $raw_headers
     *
     * @return Cronos_sistema\Config\Ksi_curl\CaseInsensitiveArray
     */
    private function parseRequestHeaders($raw_headers)
    {
        $request_headers = new CaseInsensitiveArray();
        list($first_line, $headers) = $this->parseHeaders($raw_headers);
        $request_headers['Request-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $request_headers[$key] = $value;
        }
        return $request_headers;
    }

    /**
     * Parse Response
     *
     * @access private
     * @param  $response_headers
     * @param  $raw_response
     *
     * @return mixed
     * Se o tipo de conteúdo da resposta for json:
     * Retorna o valor de retorno do decodificador json: Um objeto stdClass quando o decodificador json padrão é usado.
     * Se o tipo de conteúdo da resposta for xml:
     * Retorna o valor de retorno do decodificador xml: Um objeto SimpleXMLElement quando o decodificador xml padrão é usado.
     * Se o tipo de conteúdo da resposta for outro:
     * Retorna a resposta bruta original, a menos que um decodificador padrão tenha sido definido.
     * Se o tipo de conteúdo da resposta não puder ser determinado:
     * Retorna a resposta bruta original.
     */
    private function parseResponse($response_headers, $raw_response)
    {
        $response = $raw_response;
        if (isset($response_headers['Content-Type'])) {
            if (preg_match($this->jsonPattern, $response_headers['Content-Type'])) {
                if ($this->jsonDecoder) {
                    $args = $this->jsonDecoderArgs;
                    array_unshift($args, $response);
                    $response = call_user_func_array($this->jsonDecoder, $args);
                }
            } elseif (preg_match($this->xmlPattern, $response_headers['Content-Type'])) {
                if ($this->xmlDecoder) {
                    $args = $this->xmlDecoderArgs;
                    array_unshift($args, $response);
                    $response = call_user_func_array($this->xmlDecoder, $args);
                }
            } else {
                if ($this->defaultDecoder) {
                    $response = call_user_func($this->defaultDecoder, $response);
                }
            }
        }

        return $response;
    }

    /**
     * Parse Response Headers
     *
     * @access private
     * @param  $raw_response_headers
     *
     * @return Cronos_sistema\Config\Ksi_curl\CaseInsensitiveArray
     */
    private function parseResponseHeaders($raw_response_headers)
    {
        $response_header_array = explode("\r\n\r\n", $raw_response_headers);
        $response_header  = '';
        for ($i = count($response_header_array) - 1; $i >= 0; $i--) {
            if (stripos($response_header_array[$i], 'HTTP/') === 0) {
                $response_header = $response_header_array[$i];
                break;
            }
        }

        $response_headers = new CaseInsensitiveArray();
        list($first_line, $headers) = $this->parseHeaders($response_header);
        $response_headers['Status-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $response_headers[$key] = $value;
        }
        return $response_headers;
    }

    /**
     * Set Encoded Cookie
     *
     * @access private
     * @param  $key
     * @param  $value
     */
    private function setEncodedCookie($key, $value)
    {
        $name_chars = [];
        foreach (str_split($key) as $name_char) {
            if (isset($this->rfc2616[$name_char])) {
                $name_chars[] = $name_char;
            } else {
                $name_chars[] = rawurlencode($name_char);
            }
        }

        $value_chars = [];
        foreach (str_split($value) as $value_char) {
            if (isset($this->rfc6265[$value_char])) {
                $value_chars[] = $value_char;
            } else {
                $value_chars[] = rawurlencode($value_char);
            }
        }

        $this->cookies[implode('', $name_chars)] = implode('', $value_chars);
    }

    /**
     * Initialize
     *
     * @access private
     * @param  $base_url
     */
    private function initialize($base_url = null, $options = [])
    {
        if (isset($options)) {
            $this->setOpts($options);
        }

        $this->id = uniqid('', true);

        // Defina apenas o agente de usuário padrão se ainda não estiver definido.
        if (!array_key_exists(CURLOPT_USERAGENT, $this->options)) {
            $this->setDefaultUserAgent();
        }

       // Defina o tempo limite padrão apenas se ainda não estiver definido.
        if (!array_key_exists(CURLOPT_TIMEOUT, $this->options)) {
            $this->setDefaultTimeout();
        }

        if (!array_key_exists(CURLINFO_HEADER_OUT, $this->options)) {
            $this->setDefaultHeaderOut();
        }

        // Crie um espaço reservado para armazenar temporariamente os dados de retorno de chamada do cabeçalho.
        $header_callback_data = new \stdClass();
        $header_callback_data->rawResponseHeaders = '';
        $header_callback_data->responseCookies = [];
        $header_callback_data->stopRequestDecider = null;
        $header_callback_data->stopRequest = false;
        $this->headerCallbackData = $header_callback_data;
        $this->setStop();
        $this->setOpt(CURLOPT_HEADERFUNCTION, createHeaderCallback($header_callback_data));

        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->headers = new CaseInsensitiveArray();

        if ($base_url !== null) {
            $this->setUrl($base_url);
        }
    }

    /**
     * Set Stop
     *
     * Especifique um tomador de decisão para interromper a solicitação antecipadamente sem esperar
     * a resposta completa a ser recebida.
     *
     * O callable recebe dois parâmetros. O primeiro é o recurso cURL,
     * o segundo é uma string com dados de cabeçalho. Ambos os parâmetros correspondem ao
     * parâmetros no retorno de chamada CURLOPT_HEADERFUNCTION.
     *
     * O callable deve retornar um valor verdadeiro para que a requisição seja interrompida
     * cedo.
     *
     * O callable pode ser definido como nulo para evitar chamar o decisor de solicitação de parada
     * retorno de chamada e, em vez disso, apenas verifique o valor de stopRequest para tentar
     * para interromper a solicitação usada por Curl::stop().
     *
     * @access public
     * @param  $callback callable|null
     */
    public function setStop($callback = null)
    {
        $this->headerCallbackData->stopRequestDecider = $callback;
        $this->headerCallbackData->stopRequest = false;

        $header_callback_data = $this->headerCallbackData;
        $this->progress(createStopRequestFunction($header_callback_data));
    }

    /**
     * Stop
     *
      * Tentativa de interromper a solicitação.
     *
     * Usado por MultiCurl::stop() ao fazer várias solicitações paralelas.
     *
     * @access public
     */
    public function stop()
    {
        $this->headerCallbackData->stopRequest = true;
    }
}

/**
 * Create Header Callback
 *
 * Reunir cabeçalhos e analisar cookies à medida que os cabeçalhos de resposta são recebidos. Mantenha esta função separada da classe para que
* unset($curl) chama __destruct() automaticamente como esperado. Caso contrário, chamar manualmente $curl->close() será
* necessário para evitar um vazamento de memória.
 *
 * @param  $header_callback_data
 *
 * @return callable
 */
function createHeaderCallback($header_callback_data) {
    return function ($ch, $header) use ($header_callback_data) {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) {
            $header_callback_data->responseCookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
        }

        if ($header_callback_data->stopRequestDecider !== null) {
            $stop_request_decider = $header_callback_data->stopRequestDecider;
            if ($stop_request_decider($ch, $header)) {
                $header_callback_data->stopRequest = true;
            }
        }

        $header_callback_data->rawResponseHeaders .= $header;
        return strlen($header);
    };
}

/**
 * Create Stop Request Function
 *
 * Crie uma função para Curl::progress() que interrompa uma solicitação antecipadamente quando o
 * o sinalizador stopRequest está ativado. Mantenha esta função separada da classe para evitar
 * um vazamento de memória. 
 *
 * @param  $header_callback_data
 *
 * @return callable
 */
function createStopRequestFunction($header_callback_data) {
    return function (
        $resource,
        $download_size,
        $downloaded,
        $upload_size,
        $uploaded
    ) use (
        $header_callback_data
    ) {
        // Aborta a transferência quando o sinalizador de solicitação de parada foi definido retornando um valor diferente de zero.
        return $header_callback_data->stopRequest ? 1 : 0;
    };
}