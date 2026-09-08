<?php
/**
 * TROCAR A SENHA DE UM DUELISTA PELA LINHA DE COMANDO.
 *
 * Serve para quando a senha do Faraó se perdeu — senha guardada com
 * bcrypt não tem como ser lida de volta, então o jeito é gravar uma
 * nova por cima.
 *
 *     php tools/trocar_senha.php --listar
 *     php tools/trocar_senha.php "#YU0001" NovaSenhaForte123
 *
 * O primeiro argumento aceita o número (#YU0001) ou o nome exato.
 *
 * A senha fica no histórico do shell. Depois de rodar, limpe com
 * `history -c` — ou, melhor, entre no site e troque de novo por
 * /conta.php, que não deixa rastro nenhum.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Este script roda só pela linha de comando.\n"); }
require __DIR__ . '/../app/bootstrap.php';

if (in_array('--listar', $argv, true)) {
    echo "Duelistas cadastrados:\n\n";
    foreach (q('SELECT numero, nome, papel, nivel, ativo FROM duelistas ORDER BY id') as $d) {
        printf("  %-8s  %-32s  %-8s  nível %2d  %s\n",
            $d['numero'], $d['nome'], $d['papel'], (int)$d['nivel'],
            (int)$d['ativo'] ? '' : '(desligado)');
    }
    exit(PHP_EOL);
}

$quem  = $argv[1] ?? '';
$senha = $argv[2] ?? '';

if ($quem === '' || mb_strlen($senha) < 8) {
    exit("Uso:\n  php tools/trocar_senha.php --listar\n"
       . "  php tools/trocar_senha.php \"#YU0001\" NovaSenhaComOitoOuMais\n");
}

$d = q1('SELECT id, nome, numero FROM duelistas WHERE numero = ? OR nome = ?', [$quem, $quem]);
if (!$d) { exit("Não achei duelista com número ou nome «$quem». Rode com --listar para ver os que existem.\n"); }

ex('UPDATE duelistas SET senha_hash = ?, erros_senha = 0, travado_ate = NULL WHERE id = ?',
   [password_hash($senha, PASSWORD_DEFAULT), (int)$d['id']]);

echo "Senha trocada.\n";
echo "  duelista: {$d['nome']}  ({$d['numero']})\n";
echo "  A trava por erro de senha também foi solta, caso houvesse.\n\n";
echo "Entre no site e troque de novo por /conta.php — assim ela não fica no histórico do shell.\n";
echo "Para limpar o rastro agora:  history -c\n";
