<?php


namespace Cronos_sistema\Config;
use Cronos_sistema\Config\Connection;

class Read extends Connection {

    static $Select;
    static $Places;
    static $Result;

    /** @var PDOStatement */
    static $Read;

    /** @var PDO */
    static $Conn;

    /**
     * <b>Exe Read:</b> Executa uma leitura simplificada com Prepared Statments. Basta informar o nome da tabela,
     * os termos da seleção e uma analize em cadeia (ParseString) para executar.
     * @param STRING $Tabela = Nome da tabela
     * @param STRING $Termos = WHERE | ORDER | LIMIT :limit | OFFSET :offset
     * @param STRING $ParseString = link={$link}&link2={$link2}
     */
    public static function ExeRead($Tabela, $Termos = null, $ParseString = null) {
        if (!empty($ParseString)):
            parse_str($ParseString, self::$Places);
        endif;

        self::$Select = "SELECT * FROM {$Tabela} {$Termos}";
        self::Execute();

       
    }

    /**
     * <b>Obter resultado:</b> Retorna um array com todos os resultados obtidos. Envelope primário númérico. Para obter
     * um resultado chame o índice getResult()[0]!
     * @return ARRAY $this = Array ResultSet
     */
    public static function getResult() {
        return self::$Result;
    }

    /**
     * <b>Contar Registros: </b> Retorna o número de registros encontrados pelo select!
     * @return INT $Var = Quantidade de registros encontrados
     */
    public static function getRowCount() {
        return self::$Read->rowCount();
    }

    public static function FullRead($Query, $ParseString = null) {
        self::$Select = (string) $Query;
        if (!empty($ParseString)):
            parse_str($ParseString, self::$Places);
        endif;
        self::Execute();
    }

    /**
     * <b>Full Read:</b> Executa leitura de dados via query que deve ser montada manualmente para possibilitar
     * seleção de multiplas tabelas em uma única query!
     * @param STRING $Query = Query Select Syntax
     * @param STRING $ParseString = link={$link}&link2={$link2}
     */
    public static function setPlaces($ParseString) {
        parse_str($ParseString, self::$Places);
        self::Execute();
    }

    /**
     * ****************************************
     * *********** PRIVATE METHODS ************
     * ****************************************
     */
    //Obtém o PDO e Prepara a query
    private static function Connect() {
        
        
       // self::$Conn = Connection::getInstance();
        self::$Read = self::$Conn->prepare(self::$Select);
        self::$Read->setFetchMode(\PDO::FETCH_ASSOC);
    }

    //Cria a sintaxe da query para Prepared Statements
    private static function getSyntax() {
        if (self::$Places):
            foreach (self::$Places as $Vinculo => $Valor):
                if ($Vinculo == 'limit' || $Vinculo == 'offset'):
                    $Valor = (int) $Valor;
                endif;
                self::$Read->bindValue(":{$Vinculo}", $Valor, ( is_int($Valor) ? \PDO::PARAM_INT : \PDO::PARAM_STR));
            endforeach;
        endif;
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private static function Execute() {
        self::Connect();
        try {
            self::getSyntax();
            self::$Read->execute();
            self::$Result = self::$Read->fetchAll();
            
        } catch (\PDOException $e) {
            self::$Result = null;
        }
    }

}
