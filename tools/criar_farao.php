<?php
/**
 * CRIA O FARAÓ — a conta do dono, #YU0001, nível 25.
 *
 *     php tools/criar_farao.php "Faraó das Trevas, O Chimbungo" senhaforte
 *
 * Roda uma vez. Se a conta já existir, o script diz e não faz nada.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Só pela linha de comando.\n"); }
require __DIR__ . '/../app/bootstrap.php';

$nome  = $argv[1] ?? 'Faraó das Trevas, O Chimbungo';
$senha = $argv[2] ?? '';
if (mb_strlen($senha) < 8) { exit("Uso: php tools/criar_farao.php \"Nome\" senha_com_8_ou_mais\n"); }

if (qv('SELECT COUNT(*) FROM duelistas WHERE numero = "#YU0001"', [], 0)) {
    exit("O #YU0001 já existe. Nada a fazer.\n");
}

db()->beginTransaction();
ex('INSERT INTO duelistas (nome, senha_hash, numero, avatar, nivel, papel, ativo, criado_em, ultimo_acesso, tutorial_visto)
    VALUES (?,?,?,?,?,?,1,NOW(),NOW(),1)',
   [$nome, password_hash($senha, PASSWORD_DEFAULT), '#YU0001', 15, 25, 'farao']);
$id = ultimoId();

foreach (array_keys(dificuldades()) as $d) {
    ex('INSERT INTO carteiras (duelista_id, dificuldade, oneub, selos) VALUES (?,?,?,?)', [$id, $d, 100000, 10]);
    ex('INSERT INTO progresso (duelista_id, dificuldade) VALUES (?,?)', [$id, $d]);
}
// o Faraó tem as sete — inclusive o Enigma
foreach (array_keys(reliquiasTodas()) as $slug) {
    ex('INSERT INTO posse_reliquias (duelista_id, reliquia, obtida_em, equipada) VALUES (?,?,NOW(),?)',
       [$id, $slug, $slug === 'olho' ? 1 : 0]);
}
// e a coleção inteira, 3 cópias de cada, em todas as dificuldades
foreach (array_keys(dificuldades()) as $d) {
    ex('INSERT INTO posse_cartas (duelista_id, dificuldade, carta_num, quantidade)
        SELECT ?, ?, num, 3 FROM cartas ON DUPLICATE KEY UPDATE quantidade = 3', [$id, $d]);
}
for ($i = 0; $i < 20; $i++) { gerarConvite($id); }
db()->commit();

echo "Faraó criado.\n  nome:   $nome\n  número: #YU0001\n  nível:  25\n";
echo "Convites gerados: 20 — veja em /conta.php.\n";
