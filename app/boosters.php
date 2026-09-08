<?php
/**
 * BOOSTERS.
 *
 * 9 cartas por pacote: 7 comuns, 1 rara garantida e 1 slot variável que
 * tem 1 em 12 de virar super-rara. O sorteio acontece NO SERVIDOR e a
 * carta que ainda não foi virada não é mandada para o navegador — não há
 * como espiar o pacote antes de abrir.
 */
declare(strict_types=1);

const CARTAS_POR_BOOSTER = 9;

/** Traduz a regra da coleção em WHERE + parâmetros. */
function filtroDaColecao(string $slug): array
{
    $col = colecoes()[$slug] ?? null;
    if (!$col) return ['1=1', []];
    $r = $col['regra']; $onde = []; $par = [];
    if (!empty($r['tipo'])) { $onde[] = 'tipo IN (' . implode(',', array_fill(0, count($r['tipo']), '?')) . ')'; foreach ($r['tipo'] as $t) $par[] = $t; }
    if (!empty($r['sub']))  { $onde[] = 'sub IN ('  . implode(',', array_fill(0, count($r['sub']),  '?')) . ')'; foreach ($r['sub'] as $t) $par[] = $t; }
    if (!empty($r['nivel_max'])) { $onde[] = '(nivel <= ? OR tipo <> "Monster")'; $par[] = (int)$r['nivel_max']; }
    if (!empty($r['atk_min']))   { $onde[] = 'atk >= ?'; $par[] = (int)$r['atk_min']; }
    return [$onde ? implode(' AND ', $onde) : '1=1', $par];
}

function comprarBooster(int $id, int $dif, string $colecao): array
{
    $col = colecoes()[$colecao] ?? null;
    if (!$col) return ['erro' => 'Essa coleção não existe.'];
    if (!empty($col['so_premio'])) return ['erro' => 'O Relicário do Milênio não se compra. Só se conquista.'];
    if (!debitar($id, $dif, (int)$col['preco'], 'booster', $col['nome'])) {
        return ['erro' => 'Saldo insuficiente nesta dificuldade. Lembre: o ' . YU_MOEDA . ' de uma dificuldade não gasta em outra.'];
    }
    return ['ok' => true, 'booster' => criarBooster($id, $dif, $colecao)];
}

/** Cria o pacote fechado. As cartas já ficam sorteadas, mas guardadas. */
function criarBooster(int $id, int $dif, string $colecao): int
{
    [$onde, $par] = filtroDaColecao($colecao);
    $comuns = q("SELECT num FROM cartas WHERE $onde AND (atk < 1600 OR tipo <> 'Monster') ORDER BY RAND() LIMIT 7", $par);
    $raras  = q("SELECT num FROM cartas WHERE $onde AND atk >= 1600 ORDER BY RAND() LIMIT 1", $par);
    $super  = random_int(1, 12) === 1;
    $ultima = q("SELECT num FROM cartas WHERE $onde " . ($super ? 'AND atk >= 2200' : '') . ' ORDER BY RAND() LIMIT 1', $par);

    $nums = array_merge(
        array_map(fn($l) => (int)$l['num'], $comuns),
        array_map(fn($l) => (int)$l['num'], $raras),
        array_map(fn($l) => (int)$l['num'], $ultima)
    );
    // se a coleção for estreita demais, completa com o que houver nela
    while (count($nums) < CARTAS_POR_BOOSTER) {
        $x = q("SELECT num FROM cartas WHERE $onde ORDER BY RAND() LIMIT 1", $par);
        if (!$x) break;
        $nums[] = (int)$x[0]['num'];
    }
    ex('INSERT INTO boosters (duelista_id, dificuldade, colecao, cartas, super, criado_em) VALUES (?,?,?,?,?,NOW())',
       [$id, $dif, $colecao, json_encode($nums), $super ? 1 : 0]);
    return ultimoId();
}

function sortearBoosterDePremio(int $id, int $dif, bool $bom): ?array
{
    $lista = array_keys(array_filter(colecoes(), fn($c) => empty($c['so_premio'])));
    $slug = $bom ? 'relicario' : $lista[array_rand($lista)];
    if ($slug === 'relicario') {
        // o Relicário existe só como prêmio, e é sorteado do topo das 722
        $nums = array_map(fn($l) => (int)$l['num'], q('SELECT num FROM cartas WHERE atk >= 2400 ORDER BY RAND() LIMIT ' . CARTAS_POR_BOOSTER));
        ex('INSERT INTO boosters (duelista_id, dificuldade, colecao, cartas, super, criado_em) VALUES (?,?,?,?,1,NOW())',
           [$id, $dif, 'relicario', json_encode($nums)]);
        $bid = ultimoId();
    } else {
        $bid = criarBooster($id, $dif, $slug);
    }
    return ['id' => $bid, 'colecao' => $slug, 'nome' => colecoes()[$slug]['nome']];
}

function boostersFechados(int $id, ?int $dif = null): int
{
    return $dif === null
        ? (int)qv('SELECT COUNT(*) FROM boosters WHERE duelista_id = ? AND aberto_em IS NULL', [$id], 0)
        : (int)qv('SELECT COUNT(*) FROM boosters WHERE duelista_id = ? AND dificuldade = ? AND aberto_em IS NULL', [$id, $dif], 0);
}
function meusBoosters(int $id, int $dif): array
{
    return q('SELECT * FROM boosters WHERE duelista_id = ? AND dificuldade = ? AND aberto_em IS NULL ORDER BY id', [$id, $dif]);
}

/** Abre o pacote: entrega as cartas e devolve a lista para a animação. */
function abrirBooster(int $id, int $boosterId): array
{
    $b = q1('SELECT * FROM boosters WHERE id = ? AND duelista_id = ?', [$boosterId, $id]);
    if (!$b) return ['erro' => 'Pacote não encontrado.'];
    if ($b['aberto_em']) return ['erro' => 'Este pacote já foi aberto.'];
    $nums = json_decode((string)$b['cartas'], true) ?: [];
    [$entregues, $devolvido] = darCartas($id, (int)$b['dificuldade'], $nums, 'booster');
    ex('UPDATE boosters SET aberto_em = NOW() WHERE id = ?', [$boosterId]);
    conferirConquistas($id);
    return ['ok' => true, 'cartas' => array_map(fn($n) => carta((int)$n), $nums),
            'novas' => $entregues, 'devolvido' => $devolvido, 'super' => (int)$b['super'] === 1,
            'colecao' => $b['colecao']];
}
