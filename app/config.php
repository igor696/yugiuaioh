<?php
/**
 * YU GI UAI OH — configuração do site
 * ---------------------------------------------------------------
 * ONDE ESTE ARQUIVO VAI:  app/config.php
 * (fora da public_html — ninguém acessa ele pela internet)
 *
 * SÓ PRECISA MEXER NOS 3 CAMPOS MARCADOS COM  <<< TROQUE
 * Todo o resto já está pronto, inclusive a chave de segurança,
 * que já foi sorteada só para você.
 */
return [

    // ================================================== BANCO DE DADOS
    // hPanel > Bancos de Dados MySQL. Crie o banco, crie o usuário e
    // copie os três valores para cá, exatamente como aparecem lá.
    'db' => [
        'host'  => 'localhost',                 // na Hostinger é sempre localhost
        'nome'  => 'u686297406_yugiuaioh',      // <<< TROQUE pelo nome do banco
        'user'  => 'u686297406_igoryugi',           // <<< TROQUE pelo usuário
        'senha' => '47480cjMARMELO@',      // <<< TROQUE pela senha
        'porta' => 3306,
    ],

    // =========================================================== SITE
    'site' => [
        'nome'  => 'Yu Gi Uai Oh',
        'url'   => 'https://yugiuaioh.com.br',
        'fuso'  => 'America/Sao_Paulo',

        // 'debug' => true mostra o erro na tela. Serve enquanto você
        // está instalando. DEPOIS QUE FUNCIONAR, volte para false —
        // com true, uma mensagem de erro pode expor caminho de pasta e
        // trecho de consulta para quem estiver olhando.
        'debug' => false,
    ],

    // ====================================================== SEGURANÇA
    'seguranca' => [
        // Já sorteada. Não precisa mexer, e não mostre para ninguém.
        'chave_app'        => '15de8e2169cedcbfb51a649df4a23be98db8b88731e5bc39935abd9e4db58acd',

        'exige_convite'    => true,   // cadastro só com código de convite
        'senha_min'        => 8,
        'tentativas_max'   => 5,      // erros de senha antes de segurar a conta
        'bloqueio_minutos' => 15,
        'sessao_minutos'   => 240,    // 4 horas parado = cai fora
    ],

    // ========================================================= CARTAS
    'cartas' => [
        // 'local' é o único modo honesto: o YGOPRODeck proíbe hotlink
        // de imagem e bloqueia o IP de quem insiste — e num servidor
        // compartilhado o IP bloqueado não é só o seu. As artes ficam
        // na nossa pasta, baixadas uma vez pelo importador:
        //     php tools/importar_ygoprodeck.php --imagens --pt
        'modo_imagem' => 'local',
        'pasta_cache' => __DIR__ . '/../public_html/cache/cartas',
        'url_cache'   => '/cache/cartas',
    ],
];
