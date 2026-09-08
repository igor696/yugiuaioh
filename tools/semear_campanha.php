<?php
/**
 * SEMEIA UM DUELISTA DE TESTE — para conferir a campanha sem jogar 30
 * duelos à mão.
 *
 *     php tools/semear_campanha.php NomeDoDuelista 3 18
 *
 * (dificuldade 3, com 18 dos 30 já vencidos)
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit("Só pela linha de comando.\n"); }
require __DIR__ . '/../app/bootstrap.php';

$nome = $argv[1] ?? '';
$dif  = max(1, min(5, (int)($argv[2] ?? 1)));
$qtd  = max(0, min(30, (int)($argv[3] ?? 10)));
$d = q1('SELECT * FROM duelistas WHERE nome = ?', [$nome]);
if (!$d) { exit("Duelista «$nome» não existe.\n"); }
$id = (int)$d['id'];

for ($n = 1; $n <= $qtd; $n++) {
    ex('INSERT IGNORE INTO oponentes_vencidos (duelista_id, dificuldade, oponente, criado_em) VALUES (?,?,?,NOW())', [$id, $dif, $n]);
    ex('INSERT INTO oponente_estado (duelista_id, dificuldade, oponente, slot, duelos) VALUES (?,?,?,?,1)
        ON DUPLICATE KEY UPDATE slot = VALUES(slot)', [$id, $dif, $n, random_int(2, 4)]);
}
creditar($id, $dif, 3000, 'presente', 'Semeadura de teste');
// uma coleção suficiente para montar deck: 3 cópias de 60 cartas sorteadas
foreach (q('SELECT num FROM cartas ORDER BY RAND() LIMIT 60') as $c) {
    ex('INSERT INTO posse_cartas (duelista_id, dificuldade, carta_num, quantidade) VALUES (?,?,?,3)
        ON DUPLICATE KEY UPDATE quantidade = 3', [$id, $dif, (int)$c['num']]);
}
conferirConquistas($id);
echo "Pronto: $nome com $qtd vencidos na dificuldade $dif, 3000 " . YU_MOEDA . " e 60 cartas.\n";
