<?php
/**
 * YU GI UAI OH — arranque.
 * Todo arquivo PHP do site começa incluindo este, e mais nada.
 */
declare(strict_types=1);

define('YU_RAIZ',   dirname(__DIR__));
define('YU_APP',    __DIR__);
define('YU_VERSAO', '1.0.0');
define('YU_MOEDA',  'ONEUB');

$arquivoConfig = YU_APP . '/config.php';
if (!is_file($arquivoConfig)) {
    http_response_code(500);
    exit('Falta o arquivo app/config.php. Copie o app/config.exemplo.php e preencha.');
}
$CFG = require $arquivoConfig;

date_default_timezone_set($CFG['site']['fuso'] ?? 'America/Sao_Paulo');
mb_internal_encoding('UTF-8');

if (PHP_SAPI === 'cli' || !empty($CFG['site']['debug'])) {
    ini_set('display_errors', '1'); error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0'); error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}
ini_set('log_errors', '1');
if (!is_dir(YU_RAIZ . '/logs')) { @mkdir(YU_RAIZ . '/logs', 0755, true); }
ini_set('error_log', YU_RAIZ . '/logs/php-erros.log');

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => $https,
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_name('DUELOSESSAO');
    session_start();
}

require YU_APP . '/helpers.php';
require YU_APP . '/db.php';
require YU_APP . '/dados.php';       // tabelas fixas do jogo (relíquias, níveis, coleções…)
require YU_APP . '/simbolos.php';
require YU_APP . '/ajustes.php';
require YU_APP . '/auth.php';
require YU_APP . '/contas.php';
require YU_APP . '/mensagens.php';
require YU_APP . '/economia.php';
require YU_APP . '/cartas.php';
require YU_APP . '/decks.php';
require YU_APP . '/reliquias.php';
require YU_APP . '/conquistas.php';
require YU_APP . '/campanha.php';
require YU_APP . '/duelo.php';
require YU_APP . '/ia.php';
require YU_APP . '/loja.php';
require YU_APP . '/boosters.php';
require YU_APP . '/salvos.php';
