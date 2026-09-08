<?php
/**
 * O CATÁLOGO DAS 722.
 *
 * O conjunto é fechado: as 722 cartas do Forbidden Memories, com nome,
 * tipo, subtipo, Nível, ATK, DEF e o passcode de 8 dígitos. O passcode é
 * o que casa a carta com a API do YGOPRODeck para trazer o texto e a
 * arte — e a arte fica AQUI, na nossa pasta, porque eles proíbem
 * hotlink e bloqueiam o IP de quem insiste.
 */
declare(strict_types=1);

function carta(int $num): ?array
{
    return q1('SELECT * FROM cartas WHERE num = ?', [$num]);
}
function cartaPorPasscode(string $p): ?array
{
    return q1('SELECT * FROM cartas WHERE passcode = ?', [$p]);
}

/** URL da arte da carta, com queda para o verso quando ainda não baixou. */
function arteCarta(?array $c, string $tam = 'p'): string
{
    global $CFG;
    if ($c && !empty($c['passcode'])) {
        $arq = $CFG['cartas']['url_cache'] . '/' . $c['passcode'] . '.jpg';
        if (temArte($arq)) return $arq;
    }
    return '/assets/img/logo/verso.png';
}

/** O montador precisa saber se a carta é monstro para contar a curva. */
function ehMonstro(array $c): bool { return $c['tipo'] === 'Monster'; }
function ehMagica(array $c): bool  { return in_array($c['tipo'], ['Magic','Equip','Field','Ritual'], true); }
function ehArmadilha(array $c): bool { return $c['tipo'] === 'Trap'; }

/**
 * Quantos Tributos este monstro pede — regra da pág. 13 do manual:
 * Nível 5 ou 6 pede 1, Nível 7 ou mais pede 2.
 */
function tributosDe(array $c): int
{
    $n = (int)($c['nivel'] ?? 0);
    if ($n >= 7) return 2;
    if ($n >= 5) return 1;
    return 0;
}

/**
 * Busca com filtros. Devolve [linhas, total].
 * `posse` traz a quantidade que o duelista tem naquela dificuldade —
 * é o que pinta a silhueta na biblioteca das 722.
 */
function buscarCartas(array $f, int $pagina = 1, int $porPagina = 60, ?int $duelistaId = null, ?int $dif = null): array
{
    $onde = []; $p = [];
    if (!empty($f['busca'])) {
        $onde[] = '(c.nome LIKE ? OR c.nome_pt LIKE ? OR c.passcode LIKE ?)';
        $t = '%' . $f['busca'] . '%'; $p[] = $t; $p[] = $t; $p[] = $t;
    }
    if (!empty($f['tipo'])) {
        $in = implode(',', array_fill(0, count((array)$f['tipo']), '?'));
        $onde[] = "c.tipo IN ($in)";
        foreach ((array)$f['tipo'] as $v) $p[] = $v;
    }
    if (!empty($f['sub']))     { $onde[] = 'c.sub = ?';    $p[] = $f['sub']; }
    if (!empty($f['nivel']))   { $onde[] = 'c.nivel = ?';  $p[] = (int)$f['nivel']; }
    if (isset($f['atk_min']) && $f['atk_min'] !== '') { $onde[] = 'c.atk >= ?'; $p[] = (int)$f['atk_min']; }
    if (isset($f['atk_max']) && $f['atk_max'] !== '') { $onde[] = 'c.atk <= ?'; $p[] = (int)$f['atk_max']; }
    if (!empty($f['colecao'])) { $onde[] = 'c.colecao = ?'; $p[] = $f['colecao']; }

    $w = $onde ? ('WHERE ' . implode(' AND ', $onde)) : '';

    $sel = 'c.*';
    $join = '';
    if ($duelistaId && $dif) {
        $sel .= ', COALESCE(pc.quantidade,0) AS tenho';
        $join = 'LEFT JOIN posse_cartas pc ON pc.carta_num = c.num AND pc.duelista_id = ' . (int)$duelistaId
              . ' AND pc.dificuldade = ' . (int)$dif;
    } else {
        $sel .= ', 0 AS tenho';
    }
    if (!empty($f['so_minhas']) && $duelistaId && $dif) { $onde[] = 'pc.quantidade > 0'; $w = 'WHERE ' . implode(' AND ', $onde); }

    $total = (int)qv("SELECT COUNT(*) FROM cartas c $join $w", $p, 0);
    $ordem = match ($f['ordem'] ?? 'num') {
        'nome' => 'c.nome ASC',
        'atk'  => 'c.atk DESC, c.num ASC',
        'nivel'=> 'c.nivel DESC, c.atk DESC',
        default=> 'c.num ASC',
    };
    $off = max(0, ($pagina - 1) * $porPagina);
    $linhas = q("SELECT $sel FROM cartas c $join $w ORDER BY $ordem LIMIT $porPagina OFFSET $off", $p);
    return [$linhas, $total];
}

/** Quantas cartas distintas o duelista tem naquela dificuldade. */
function quantasTenho(int $duelistaId, int $dif): int
{
    return (int)qv('SELECT COUNT(*) FROM posse_cartas WHERE duelista_id = ? AND dificuldade = ? AND quantidade > 0',
                   [$duelistaId, $dif], 0);
}
function tenhoQuantas(int $duelistaId, int $dif, int $num): int
{
    return (int)qv('SELECT quantidade FROM posse_cartas WHERE duelista_id = ? AND dificuldade = ? AND carta_num = ?',
                   [$duelistaId, $dif, $num], 0);
}

/**
 * Dá cartas ao duelista, respeitando o teto de 3 cópias por nome —
 * a regra da pág. 3 do manual. O que passar de 3 vira ONEUB de volta.
 * Devolve [entregues, devolvido_em_oneub].
 */
function darCartas(int $duelistaId, int $dif, array $nums, string $motivo = 'premio'): array
{
    $entregues = []; $devolvido = 0;
    foreach ($nums as $n) {
        $n = (int)$n;
        $tem = tenhoQuantas($duelistaId, $dif, $n);
        if ($tem >= 3) { $devolvido += 40; continue; }
        ex('INSERT INTO posse_cartas (duelista_id, dificuldade, carta_num, quantidade)
            VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE quantidade = quantidade + 1',
           [$duelistaId, $dif, $n]);
        $entregues[] = $n;
    }
    if ($devolvido > 0) {
        creditar($duelistaId, $dif, $devolvido, $motivo, 'Repetida acima de 3 cópias, devolvida em ' . YU_MOEDA);
    }
    return [$entregues, $devolvido];
}

/** Preço de uma carta na banca do Sefu — sai do ATK e do Nível, não do mercado real. */
function precoDaCarta(array $c): int
{
    if ($c['tipo'] !== 'Monster') return 90;
    $v = 40 + (int)$c['atk'] / 12 + (int)$c['nivel'] * 22;
    return (int)(round($v / 5) * 5);
}

/**
 * A coleção a que a carta pertence, para EXIBIÇÃO.
 *
 * A ordem aqui importa e não é a mesma da lista de boosters: os recortes
 * por subtipo vêm primeiro, senão "Aurora de Tebas" — que é Monstro de
 * Nível até 4 — engoliria dois terços das 722 e as outras coleções
 * ficariam vazias na tela. As pools dos boosters podem se sobrepor à
 * vontade (é de propósito: um pacote de Dragão e um de Fusão dividem
 * cartas); só a etiqueta que aparece na carta precisa ser única.
 */
function colecaoDaCarta(array $c): string
{
    $ordem = ['servos','ritual','escriba','dragao','metal','labirinto','nilo','areia','chama','serra','aurora'];
    foreach ($ordem as $slug) {
        $col = colecoes()[$slug] ?? null;
        if (!$col) continue;
        $r = $col['regra'];
        if (isset($r['tipo']) && !in_array($c['tipo'], $r['tipo'], true)) continue;
        if (isset($r['sub'])  && !in_array((string)$c['sub'], $r['sub'], true)) continue;
        if (isset($r['nivel_max']) && (int)$c['nivel'] > $r['nivel_max']) continue;
        if (isset($r['atk_min'])   && (int)$c['atk']   < $r['atk_min'])   continue;
        return $slug;
    }
    return 'aurora';
}
