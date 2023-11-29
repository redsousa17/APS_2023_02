<?php

namespace Cronos_sistema\Services\Teste;

use PDO;
use GuzzleHttp\Psr7\Header;
use Cronos_sistema\Config\Connection;
use Cronos_sistema\Config\Paginacao;
use Cronos_sistema\Config\Helpers;
use Cronos_sistema\Services\Log\Logs;
use Cronos_sistema\Services\Integracao\EssencialAuxiliar;
use Cronos_sistema\Services\CurlSeguradoras\Portoseguro;
use Cronos_sistema\Services\CurlSeguradoras\PortoseguroAuxiliar;
use Cronos_sistema\Config\Ksi_curl\Curl;
use Cronos_sistema\Config\Ksi_curl\MultiCurl;



class IntegracaoMoc extends Connection {

    public static function integracao($request){


        $con = Connection::getInstance();
        $resultado = $request->getPostVars();
        $dados = json_decode(json_encode($resultado, true), true);
        
        set_time_limit(0);

        //return $dados;

        if (!isset($dados['ksi']['token'])) {
            throw new \Exception("Token obrigatorio !", 401);
        } 

        if (!$dados['ksi']['token']) {
            throw new \Exception("Token obrigatorio !", 401);
        } 

        $tokenisvalid =  EssencialAuxiliar::validatoken($dados['ksi']['token']);
        if (!$tokenisvalid) {
            throw new \Exception("Ouve um problema com seu token por favor verifique junto a KSIS !",401);
        } 

        # verfica se e um cliente ou seguradora
        $validacliente = EssencialAuxiliar::validapessoa($tokenisvalid['id_pessoa']); 
        if (!$validacliente) {
            throw new \Exception("Este usuario não tem permissão para solicitar integração!",401);
        }

        $produtoFound = EssencialAuxiliar::consultarProdutoAll($tokenisvalid['id_api_cliente']);

        if (!$produtoFound) {
            throw new \Exception("Nenhum produto cadastrado para este token ou não existe produto cadastrado!",404);
        }

       
        $resultado_consulta = [];
        $resultado_consulta['json_enviado'] = $dados;
        
       
        

        //return $novoArray;

        foreach($produtoFound as $value):  
            
            //return Helpers::get_public().$value['json'];


                        if ($value['flag_inativo'] != '1') {
                            throw new \Exception("Ouve um problema com este produto por favor verifique junto a KSIS !",401);
                        }

                        $apiconfiguracao = EssencialAuxiliar::consultaApi($value['codigo_produto']);
                        if (!$apiconfiguracao) {
                            throw new \Exception("Não existe API disponilvel para este produto! erro 001",404);
                        }

                        if(!$value['json']){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 002",404);
                        }
                        
                        if(!file_exists(Helpers::get_public().$value['json'])){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 003",404);
                        }
                
                        if(!$apiconfiguracao['json']){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 004",404);
                        }
                        if(!file_exists(Helpers::get_public().$apiconfiguracao['json'])){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 005",404);
                        }


                        $json_prosuto_cliente = file_get_contents(Helpers::get_public().$value['json']);
                        $json_produto_seguradora = file_get_contents(Helpers::get_public().$apiconfiguracao['json']);
                        $data = json_decode($json_produto_seguradora, true);
                        $json_cliente = json_decode($json_prosuto_cliente, true);
                        
                        // $resultado['nome_seguradora'][] = $data['seguradora'];
                        
                        
                        #
                        #  Integração Porto Seguro Tradicional
                        #   
                        #
                        #         
                        if(isset($data['seguradora']) &&  $data['seguradora'] == 'porto_seguro_tradicional'){
                             $dados_up = array(
                                            'id_pessoa' => $tokenisvalid['id_pessoa']
                                            , 'id_imovel' =>  $dados['ksi']['codigo_imovel']
                                            , 'id_seguradora' => $value['id_seguradora_produto']
                                            , 'id_produto' => $value['codigo_produto']
                                            , 'tipo' => 'Análise Cadastral'
                                            , 'cpf_cnpj' => $dados['pretendentes'][0]['cpf']
                                            , 'nome' => $dados['pretendentes'][0]['nome']
                                            , 'id_pessoa_ksi' => isset($dados['ksi']['id_pessoa_ksi']) ? $dados['ksi']['id_pessoa_ksi'] : 0
                                        );
                           

                            $file_tradicional = file_get_contents('/home/u601832919/domains/rosemeiregodinho.com.br/public_html/ksiseguros/Cronos_sistema/Services/Teste/jsonTradicional.json');                          

                            $resultado_consulta['porto_seguro_tradicional'] = [
                                'nomseguradora' => 'porto_seguro_tradicional',
                                'result' => json_decode($file_tradicional, true)
                            ]; 
                            //return $data_json_tradiciaonal;
                            
                            $orc =  bin2hex(random_bytes(16));
                            $id_arquivo = Helpers::criaArquivo(
                                                            'proposta'
                                                            , '000000' 
                                                            , json_encode($resultado_consulta, true)
                                                            , $dados_up
                                                            , 'Aprovado'
                                                            , 2
                                                            , $tokenisvalid['id_pessoa']
                                                            , 0
                                                        );

                           

                              
                        
                        }

                    
                        #
                        #  Integração Porto Seguro Essencial
                        #   
                        #
                        #  
                        if(isset($data['seguradora']) &&  $data['seguradora'] == 'porto_seguro_essencial'){
                             $dados_up = array(
                                            'id_pessoa' => $tokenisvalid['id_pessoa']
                                            , 'id_imovel' =>  $dados['ksi']['codigo_imovel']
                                            , 'id_seguradora' => $value['id_seguradora_produto']
                                            , 'id_produto' => $value['codigo_produto']
                                            , 'tipo' => 'Análise Cadastral'
                                            , 'cpf_cnpj' => $dados['pretendentes'][0]['cpf']
                                            , 'nome' => $dados['pretendentes'][0]['nome']
                                            , 'id_pessoa_ksi' => isset($dados['ksi']['id_pessoa_ksi']) ? $dados['ksi']['id_pessoa_ksi'] : 0
                                        );
                                
                            $file_essencial = file_get_contents('/home/u601832919/domains/rosemeiregodinho.com.br/public_html/ksiseguros/Cronos_sistema/Services/Teste/jsonEssencial.json');
                            
                            $resultado_consulta['porto_seguro_essencial'] = [
                                'nomseguradora' => 'porto_seguro_essencial',
                                'result' => json_decode($file_essencial, true)
                            ];

                            $orc =  bin2hex(random_bytes(16));
                            $id_arquivo = Helpers::criaArquivo(
                                                            'proposta'
                                                            , '000000' 
                                                            , json_encode($resultado_consulta, true)
                                                            , $dados_up
                                                            , 'Aprovado'
                                                            , 2
                                                            , $tokenisvalid['id_pessoa']
                                                            , 0
                                                        );
                           // return $data_json_Essencial;

                               
                            //  $curl->close();
                        }

                        #
                        #  Integração Porto Seguro mapfre
                        #   
                        #
                        #
                        if(isset($data['seguradora']) && $data['seguradora'] == 'mapfre'){ 
                            /*
                            $resultado_consulta['mapfre'] = [
                                'nomseguradora' => $data['seguradora'],
                                'result' => null
                            ];

                            $data = array(
                                'id_pessoa' => $tokenisvalid['id_pessoa'],
                                'id_seguradora' => $produtoFound['id_seguradora_produto'],
                                'id_produto' => $produtoFound['codigo_produto'],
                                'tipo' => 'AnÃ¡lise Cadastral',
                                'cpf_cnpj' => $dados['cpf_cnpj'],
                                'nome' => $dados['nome'],
                                'id_pessoa_ksi' => isset($dados['id_pessoa_ksi']) ? $dados['id_pessoa_ksi'] : 0
                            );

                            $orc =  bin2hex(random_bytes(16));
                            $id_arquivo = Helpers::criaArquivo('proposta', $orc, json_encode($resultado_consulta, true), $data, 'AnÃ¡lise recusada '.'erro desconhecido API KSI', 0 , $tokenisvalid['id_pessoa'], 0);

                        
                            return $resultado_consulta;
                            */
                        }

                        #
                        #  Integração Porto Seguro sompo
                        #   
                        #
                        #
                        if(isset($data['seguradora']) && $data['seguradora'] == 'sompo'){ 
                            /*
                            $resultado_consulta['sompo'] = [
                                'nomseguradora' => 'sompo',
                                'result' => null
                            ];

                            $data = array(
                                'id_pessoa' => $tokenisvalid['id_pessoa'],
                                'id_seguradora' => $produtoFound['id_seguradora_produto'],
                                'id_produto' => $produtoFound['codigo_produto'],
                                'tipo' => 'AnÃ¡lise Cadastral',
                                'cpf_cnpj' => $dados['cpf_cnpj'],
                                'nome' => $dados['nome'],
                                'id_pessoa_ksi' => isset($dados['id_pessoa_ksi']) ? $dados['id_pessoa_ksi'] : 0
                            );

                            $orc =  bin2hex(random_bytes(16));
                            $id_arquivo = Helpers::criaArquivo('proposta', $orc, json_encode($resultado_consulta, true), $data, 'AnÃ¡lise recusada '.'erro desconhecido API KSI', 0 , $tokenisvalid['id_pessoa'], 0);

                            
                            return $resultado_consulta;
                            */
                        }

        endforeach;

        //, flag_con_primeira_seguradora
        //, flag_orcamento_aprovado
        //, flag_ordernar_menor_valor
        //, flag_mostra_menor_valor 
        if($tokenisvalid['flag_orcamento_aprovado'] == 1){
            if(isset($resultado_consulta['porto_seguro_tradicional']['Error']) || $tokenisvalid['flag_con_primeira_seguradora']['parecer']['codigoParecer'] == 16 || $tokenisvalid['flag_con_primeira_seguradora']['parecer']['codigoParecer'] == 3 || $tokenisvalid['flag_con_primeira_seguradora']['parecer']['codigoParecer'] == 7){

                unset($resultado_consulta['porto_seguro_tradicional']);
                
            }

            if(isset($resultado_consulta['porto_seguro_essencial']['Error']) || isset($resultado_consulta['porto_seguro_essencial']['errorCode']) || isset($resultado_consulta['porto_seguro_essencial']['code'])){

                unset($resultado_consulta['porto_seguro_essencial']);

            }
           
        }

       
        if($tokenisvalid['flag_con_primeira_seguradora'] == 1){
            $ultimoIndice = array_key_last($resultado_consulta);
          //  return $resultado_consulta[$ultimoIndice];
            unset($resultado_consulta[$ultimoIndice]);
        }

        $novoForma = [];
        if($tokenisvalid['flag_ordernar_menor_valor'] == 1){        

            $novoForma[] = min($resultado_consulta['porto_seguro_essencial'],  $resultado_consulta['porto_seguro_tradicional']);   
            $novoForma[] = max($resultado_consulta['porto_seguro_essencial'],  $resultado_consulta['porto_seguro_tradicional']); // $bloco_30[0]['valor_avista'];
            unset($resultado_consulta['porto_seguro_tradicional']);
            unset($resultado_consulta['porto_seguro_essencial']);
            foreach($novoForma as $array){
               $resultado_consulta[$array['nomseguradora']] = $array;
            }
            
           // $resultado_consulta[$novoForma[1]['nomseguradora']];
           // unset($resultado_consulta['porto_seguro_tradicional']);
           //array_merge_recursive($resultado_consulta, $novoForma );
        }

        $menorvalor = [];
        if($tokenisvalid['flag_mostra_menor_valor'] == 1){
            $menorvalor[] = min($resultado_consulta['porto_seguro_essencial'],  $resultado_consulta['porto_seguro_tradicional']);   
            unset($resultado_consulta['porto_seguro_tradicional']);
            unset($resultado_consulta['porto_seguro_essencial']);

            foreach($menorvalor as $array){
               $resultado_consulta[$array['nomseguradora']] = $array;
            }
        }

        return $resultado_consulta;


    }

    public static function integracao_falha($request){


        $con = Connection::getInstance();
        $resultado = $request->getPostVars();
        $dados = json_decode(json_encode($resultado, true), true);
        
        set_time_limit(0);

        //return $dados;

        if (!isset($dados['ksi']['token'])) {
            throw new \Exception("Token obrigatorio !", 401);
        } 

        if (!$dados['ksi']['token']) {
            throw new \Exception("Token obrigatorio !", 401);
        } 

        $tokenisvalid =  EssencialAuxiliar::validatoken($dados['ksi']['token']);
        if (!$tokenisvalid) {
            throw new \Exception("Ouve um problema com seu token por favor verifique junto a KSIS !",401);
        } 

        # verfica se e um cliente ou seguradora
        $validacliente = EssencialAuxiliar::validapessoa($tokenisvalid['id_pessoa']); 
        if (!$validacliente) {
            throw new \Exception("Este usuario não tem permissão para solicitar integração!",401);
        }

        $produtoFound = EssencialAuxiliar::consultarProdutoAll($tokenisvalid['id_api_cliente']);

        if (!$produtoFound) {
            throw new \Exception("Nenhum produto cadastrado para este token ou não existe produto cadastrado!",404);
        }

       
        $resultado_consulta = [];
        $resultado_consulta['json_enviado'] = $dados;
        
       
        

        //return $novoArray;

        foreach($produtoFound as $value):  
            
            //return Helpers::get_public().$value['json'];


                        if ($value['flag_inativo'] != '1') {
                            throw new \Exception("Ouve um problema com este produto por favor verifique junto a KSIS !",401);
                        }

                        $apiconfiguracao = EssencialAuxiliar::consultaApi($value['codigo_produto']);
                        if (!$apiconfiguracao) {
                            throw new \Exception("Não existe API disponilvel para este produto! erro 001",404);
                        }

                        if(!$value['json']){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 002",404);
                        }
                        
                        if(!file_exists(Helpers::get_public().$value['json'])){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 003",404);
                        }
                
                        if(!$apiconfiguracao['json']){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 004",404);
                        }
                        if(!file_exists(Helpers::get_public().$apiconfiguracao['json'])){
                            throw new \Exception("Arquivo de json não exite por favor configure o api! erro 005",404);
                        }


                        $json_prosuto_cliente = file_get_contents(Helpers::get_public().$value['json']);
                        $json_produto_seguradora = file_get_contents(Helpers::get_public().$apiconfiguracao['json']);
                        $data = json_decode($json_produto_seguradora, true);
                        $json_cliente = json_decode($json_prosuto_cliente, true);
                        
                        // $resultado['nome_seguradora'][] = $data['seguradora'];
                        
                        
                        #
                        #  Integração Porto Seguro Tradicional
                        #   
                        #
                        #         
                        if(isset($data['seguradora']) &&  $data['seguradora'] == 'porto_seguro_tradicional'){

                            $file_tradicional = file_get_contents('/home/ksiseguros/www/ws/Cronos_sistema/Services/Teste/jsonErrodata.json');                          

                            $resultado_consulta['porto_seguro_tradicional'] = [
                                'nomseguradora' => 'porto_seguro_tradicional',
                                "Error" => 5,
                                "orcamento" => "Erro ao buscar orçamento",
                                "result"  => json_decode($file_tradicional, true)
                               
                            ]; 
                            //return $data_json_tradiciaonal;

                           

                              
                        
                        }

                    
                        #
                        #  Integração Porto Seguro Essencial
                        #   
                        #
                        #  
                        if(isset($data['seguradora']) &&  $data['seguradora'] == 'porto_seguro_essencial'){
                                
                            $file_essencial = file_get_contents('/home/ksiseguros/www/ws/Cronos_sistema/Services/Teste/jsonEssencial.json');
                            
                            $resultado_consulta['porto_seguro_essencial'] = [
                                'nomseguradora' => 'porto_seguro_essencial',
                                'result' => json_decode($file_essencial, true)
                            ];

                           // return $data_json_Essencial;

                               
                            //  $curl->close();
                        }

                        #
                        #  Integração Porto Seguro mapfre
                        #   
                        #
                        #
                        if(isset($data['seguradora']) && $data['seguradora'] == 'mapfre'){ 
                            /*
                            $resultado_consulta['mapfre'] = [
                                'nomseguradora' => $data['seguradora'],
                                'result' => null
                            ];

                            $data = array(
                                'id_pessoa' => $tokenisvalid['id_pessoa'],
                                'id_seguradora' => $produtoFound['id_seguradora_produto'],
                                'id_produto' => $produtoFound['codigo_produto'],
                                'tipo' => 'AnÃ¡lise Cadastral',
                                'cpf_cnpj' => $dados['cpf_cnpj'],
                                'nome' => $dados['nome'],
                                'id_pessoa_ksi' => isset($dados['id_pessoa_ksi']) ? $dados['id_pessoa_ksi'] : 0
                            );

                            $orc =  bin2hex(random_bytes(16));
                            $id_arquivo = Helpers::criaArquivo('proposta', $orc, json_encode($resultado_consulta, true), $data, 'AnÃ¡lise recusada '.'erro desconhecido API KSI', 0 , $tokenisvalid['id_pessoa'], 0);

                        
                            return $resultado_consulta;
                            */
                        }

                        #
                        #  Integração Porto Seguro sompo
                        #   
                        #
                        #
                        if(isset($data['seguradora']) && $data['seguradora'] == 'sompo'){ 
                            /*
                            $resultado_consulta['sompo'] = [
                                'nomseguradora' => 'sompo',
                                'result' => null
                            ];

                            $data = array(
                                'id_pessoa' => $tokenisvalid['id_pessoa'],
                                'id_seguradora' => $produtoFound['id_seguradora_produto'],
                                'id_produto' => $produtoFound['codigo_produto'],
                                'tipo' => 'AnÃ¡lise Cadastral',
                                'cpf_cnpj' => $dados['cpf_cnpj'],
                                'nome' => $dados['nome'],
                                'id_pessoa_ksi' => isset($dados['id_pessoa_ksi']) ? $dados['id_pessoa_ksi'] : 0
                            );

                            $orc =  bin2hex(random_bytes(16));
                            $id_arquivo = Helpers::criaArquivo('proposta', $orc, json_encode($resultado_consulta, true), $data, 'AnÃ¡lise recusada '.'erro desconhecido API KSI', 0 , $tokenisvalid['id_pessoa'], 0);

                            
                            return $resultado_consulta;
                            */
                        }

        endforeach;

        //, flag_con_primeira_seguradora
        //, flag_orcamento_aprovado
        //, flag_ordernar_menor_valor
        //, flag_mostra_menor_valor 
        if($tokenisvalid['flag_orcamento_aprovado'] == 1){
            if(isset($resultado_consulta['porto_seguro_tradicional']['Error']) || $tokenisvalid['flag_con_primeira_seguradora']['parecer']['codigoParecer'] == 16 || $tokenisvalid['flag_con_primeira_seguradora']['parecer']['codigoParecer'] == 3 || $tokenisvalid['flag_con_primeira_seguradora']['parecer']['codigoParecer'] == 7){

                unset($resultado_consulta['porto_seguro_tradicional']);
                
            }

            if(isset($resultado_consulta['porto_seguro_essencial']['Error']) || isset($resultado_consulta['porto_seguro_essencial']['errorCode']) || isset($resultado_consulta['porto_seguro_essencial']['code'])){

                unset($resultado_consulta['porto_seguro_essencial']);

            }
           
        }

       
        if($tokenisvalid['flag_con_primeira_seguradora'] == 1){
            $ultimoIndice = array_key_last($resultado_consulta);
          //  return $resultado_consulta[$ultimoIndice];
            unset($resultado_consulta[$ultimoIndice]);
        }

        $novoForma = [];
        if($tokenisvalid['flag_ordernar_menor_valor'] == 1){        

            $novoForma[] = min($resultado_consulta['porto_seguro_essencial'],  $resultado_consulta['porto_seguro_tradicional']);   
            $novoForma[] = max($resultado_consulta['porto_seguro_essencial'],  $resultado_consulta['porto_seguro_tradicional']); // $bloco_30[0]['valor_avista'];
            unset($resultado_consulta['porto_seguro_tradicional']);
            unset($resultado_consulta['porto_seguro_essencial']);
            foreach($novoForma as $array){
               $resultado_consulta[$array['nomseguradora']] = $array;
            }
            
           // $resultado_consulta[$novoForma[1]['nomseguradora']];
           // unset($resultado_consulta['porto_seguro_tradicional']);
           //array_merge_recursive($resultado_consulta, $novoForma );
        }

        $menorvalor = [];
        if($tokenisvalid['flag_mostra_menor_valor'] == 1){
            $menorvalor[] = min($resultado_consulta['porto_seguro_essencial'],  $resultado_consulta['porto_seguro_tradicional']);   
            unset($resultado_consulta['porto_seguro_tradicional']);
            unset($resultado_consulta['porto_seguro_essencial']);

            foreach($menorvalor as $array){
               $resultado_consulta[$array['nomseguradora']] = $array;
            }
        }

        return $resultado_consulta;


    }
}