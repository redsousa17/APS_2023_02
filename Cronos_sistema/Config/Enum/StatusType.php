<?php

namespace Cronos_sistema\Config\Enum;

enum StatusType : string {

    case HABILITADO     = "1";
    case APROVADO       = "2";
    case RECUSADO       = "0";
    case ANALISE        = "3";
    case PENDENTESEG    = "7";
    case ERRO1001       = "Erro na seguradora. (já estamos atuando junto a seguradora para que possamos devolver o status dessa consulta o mais rápido possível)";
    case ERRO1000       = "Erro na seguradora. (já estamos atuando junto a seguradora para que possamos devolver o status dessa consulta o mais rapido possivel)";
    case ERRO3          = 'Essa cotação está em processo de análise. O status dessa consulta será atualizado dentro de até 48hs.';
    case MSGAPROVADO    = 'Parabéns, sua cotação está aprovada! Selecione o produto desejado para interagir e avançar.';
    case FALHASERVE     = 'Falha na conexão com a seguradora';
    case FALHAAUTH      = 'Falha na autenticação da seguradora.';

}