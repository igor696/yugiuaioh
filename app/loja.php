<?php
/**
 * A LOJA — as oito bancas do Bazar de Tebas.
 * A vitrine do Sefu é a mesma para todo mundo dentro do mesmo período de
 * 24 horas: atualizar a página não sorteia outra. A semente é a data.
 */
declare(strict_types=1);

/** 10 cartas por dia, iguais para todos, preço convertido do ATK/Nível. */
function vitrineDoSefu(int $dif): array
{
    $semente = crc32(date('Y-m-d') . '-' . $dif);
    $teto = [1=>1600, 2=>2000, 3=>2300, 4=>2600, 5=>3000][$dif] ?? 1600;
    $todas = q('SELECT * FROM cartas WHERE atk <= ? ORDER BY num', [$teto]);
    if (!$todas) return [];
    $rnd = sorteioFixo($semente);
    $r = []; $vistos = [];
    for ($i = 0; $i < 200 && count($r) < 10; $i++) {
        $c = $todas[$rnd(count($todas))];
        if (isset($vistos[$c['num']])) continue;
        $vistos[$c['num']] = true;
        $c['preco'] = precoDaCarta($c);
        $r[] = $c;
    }
    return $r;
}
function segundosParaTrocarVitrine(): int { return strtotime('tomorrow') - time(); }

function comprarCarta_(int $id, int $dif, int $num): array
{
    $c = carta($num);
    if (!$c) return ['erro' => 'Carta não encontrada.'];
    $preco = precoDaCarta($c);
    if (tenhoQuantas($id, $dif, $num) >= 3) return ['erro' => 'Você já tem 3 cópias — o limite do manual.'];
    if (!debitar($id, $dif, $preco, 'carta', $c['nome'])) return ['erro' => 'Saldo insuficiente nesta dificuldade.'];
    darCartas($id, $dif, [$num], 'carta');
    conferirConquistas($id);
    return ['ok' => true, 'carta' => $c, 'preco' => $preco];
}

/** Os itens de sorte da Merit-Ka e as melhorias do Djed. */
function itensDaLoja(): array
{
    return [
      'sorte' => [
        ['slug'=>'maonova','nome'=>'Frasco de Recomeço','preco'=>180,'texto'=>'Uma vez por duelo, devolve a mão ao Deck, embaralha e compra a mesma quantidade.'],
        ['slug'=>'olhada','nome'=>'Óleo de Vidência','preco'=>140,'texto'=>'Uma vez por duelo, olha as 2 cartas do topo do seu Deck.'],
        ['slug'=>'folego','nome'=>'Incenso de Fôlego','preco'=>220,'texto'=>'Começa o duelo com 500 Pontos de Vida a mais. Só na CAMPANHA.'],
      ],
      'melhoria' => [
        ['slug'=>'deck_slot','nome'=>'Mais um lugar de Deck','preco'=>300,'texto'=>'Guarda um Deck a mais nesta dificuldade.'],
        ['slug'=>'encaixe','nome'=>'Encaixe de relíquia','preco'=>2000,'texto'=>'Abre um encaixe extra antes do nível que o liberaria.'],
      ],
      'cosmetico' => [
        ['slug'=>'moldura-prata','nome'=>'Moldura de prata','preco'=>400,'texto'=>'Anel de prata com hieróglifos, em volta do seu avatar.'],
        ['slug'=>'moldura-ouro','nome'=>'Moldura de ouro','preco'=>900,'texto'=>'Anel de ouro martelado com cabochões de lápis.'],
        ['slug'=>'moldura-farao','nome'=>'Moldura do Faraó','preco'=>2500,'texto'=>'Duas uraeus e o disco solar. Só para quem já venceu um chefão.'],
      ],
    ];
}
function comprarItem(int $id, int $dif, string $aba, string $slug): array
{
    foreach (itensDaLoja()[$aba] ?? [] as $it) {
        if ($it['slug'] !== $slug) continue;
        if (!debitar($id, $dif, (int)$it['preco'], $aba === 'melhoria' ? 'melhoria' : ($aba === 'sorte' ? 'sorte' : 'avatar'), $it['nome'])) {
            return ['erro' => 'Saldo insuficiente nesta dificuldade.'];
        }
        ex('INSERT INTO itens (duelista_id, dificuldade, slug, criado_em) VALUES (?,?,?,NOW())', [$id, $dif, $slug]);
        return ['ok' => true, 'item' => $it];
    }
    return ['erro' => 'Item não encontrado.'];
}
function tenhoItem(int $id, string $slug): bool
{
    return (bool)qv('SELECT COUNT(*) FROM itens WHERE duelista_id = ? AND slug = ?', [$id, $slug], 0);
}
