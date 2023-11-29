<?php declare(strict_types=1);

namespace Cronos_sistema\Config\Ksi_curl;

class CaseInsensitiveArray implements \ArrayAccess, \Countable, \Iterator
{

    /**
     * @var mixed[] Armazenamento de dados com chaves minúsculas.
     * @veja offsetSet()
     * @veja offsetExists()
     * @veja offsetUnset()
     * @veja offsetGet()
     * @ver contagem()
     * @ver atual()
     * @veja a seguir()
     * @ver chave()
     */
    private $data = [];

    /**
     * @var string[] Chaves que diferenciam maiúsculas de minúsculas.
     * @veja offsetSet()
     * @veja offsetUnset()
     * @ver chave()
     */
    private $keys = [];

    /**
     * Construir
     *
     * Permite criar um array vazio ou converter um array existente em um
     * matriz que não diferencia maiúsculas de minúsculas. Cuidado: os dados podem ser perdidos durante a conversão
     * arrays que diferenciam maiúsculas de minúsculas para arrays que não diferenciam maiúsculas de minúsculas.
     *
     * @param mixed[] $initial (opcional) Matriz existente a ser convertida.
     *
     * @return CaseInsensitiveArray
     *
     * @acesso público
     */
    public function __construct(array $initial = null)
    {
        if ($initial !== null) {
            foreach ($initial as $key => $value) {
                $this->offsetSet($key, $value);
            }
        }
    }

    /**
     * Conjunto de Deslocamento
     *
     * Defina os dados em um deslocamento especificado. Converte o deslocamento para minúsculas e
     * armazena o deslocamento com distinção entre maiúsculas e minúsculas e os dados nos índices de minúsculas em
     * $this->keys e @this->data.
     *
     * @see https://secure.php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param string $offset O deslocamento para armazenar os dados (sem distinção entre maiúsculas e minúsculas).
     * @param mixed $value Os dados a serem armazenados no deslocamento especificado.
     *
     * @return void
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $offsetlower = strtolower($offset);
            $this->data[$offsetlower] = $value;
            $this->keys[$offsetlower] = $offset;
        }
    }

    /**
     * Deslocamento Existe
     *
     * Verifica se o deslocamento existe no armazenamento de dados. O índice é pesquisado com
     * a versão em minúsculas do deslocamento fornecido.
     *
     * @see https://secure.php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param string $offset Offset a verificar
     *
     * @return bool Se o deslocamento existir.
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return (bool) array_key_exists(strtolower($offset), $this->data);
    }

    /**
     * Deslocamento não definido
     *
     * Desativa o deslocamento especificado. Converte o deslocamento fornecido para minúsculas,
     * e desativa a chave que diferencia maiúsculas de minúsculas, bem como os dados armazenados.
     *
     * @see https://secure.php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param string $offset O deslocamento a ser desarmado.
     *
     * @return void
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $offsetlower = strtolower($offset);
        unset($this->data[$offsetlower]);
        unset($this->keys[$offsetlower]);
    }

    /**
     * Obtenção de Deslocamento
     *
     * Retorna os dados armazenados no deslocamento fornecido. O deslocamento é convertido em
     * minúsculo e a pesquisa é feita diretamente no armazenamento de dados.
     *
     * @see https://secure.php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param string $offset Deslocamento a ser pesquisado.
     *
     * @return mixed Os dados armazenados no offset.
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $offsetlower = strtolower($offset);
        return isset($this->data[$offsetlower]) ? $this->data[$offsetlower] : null;
    }

    /**
     * Contar
     *
     * @see https://secure.php.net/manual/en/countable.count.php
     *
     * @param void
     *
     * @return integer O número de elementos armazenados no array.
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return (int) count($this->data);
    }

    /**
     * Atual
     *
     * @see https://secure.php.net/manual/en/iterator.current.php
     *
     * @param void
     *
     * @return Dados mistos na posição atual.
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->data);
    }

     /**
     * Próximo
     *
     * @see https://secure.php.net/manual/en/iterator.next.php
     *
     * @param void
     *
     * @return void
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->data);
    }

    /**
     * Chave
     *
     * @veja https://secure.php.net/manual/en/iterator.key.php
     *
     * @param void
     *
     * @return Tecla mista com diferenciação de maiúsculas e minúsculas na posição atual.
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        $key = key($this->data);
        return isset($this->keys[$key]) ? $this->keys[$key] : $key;
    }

    /**
     * Válido
     *
     * @veja https://secure.php.net/manual/en/iterator.valid.php
     *
     * @return bool Se a posição atual for válida.
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function valid()
    {
        return (bool) (key($this->data) !== null);
    }

    /**
     * Rebobinar
     *
     * @veja https://secure.php.net/manual/en/iterator.rewind.php
     *
     * @param void
     *
     * @return void
     *
     * @acesso público
     */
    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->data);
    }
}