<?php
/**
 * A CAMPANHA.
 *
 * Cinco cidades — uma por dificuldade — com 30 oponentes cada e um
 * chefão no fim. O chefão libera quando 20 dos 30 já caíram.
 *
 * OS 5 DECKS DE CADA OPONENTE são gerados, não escritos à mão: cada
 * oponente tem um EIXO, e o deck é montado a partir das 722 filtrando
 * por esse eixo, dentro do molde daquele slot. Com semente fixa
 * (oponente + slot), a mesma lista sai igual todas as vezes: o jogador
 * pode estudar o deck do adversário, e isso é bom.
 *
 * O SLOT SOBE quando o jogador ganha seguido — é o que impede farmar o
 * mesmo oponente fácil a vida inteira.
 */
declare(strict_types=1);

function progresso(int $id, int $dif): array
{
    $p = q1('SELECT * FROM progresso WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
    if (!$p) {
        ex('INSERT INTO progresso (duelista_id, dificuldade) VALUES (?,?)', [$id, $dif]);
        $p = q1('SELECT * FROM progresso WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
    }
    return $p ?: [];
}
function vencidos(int $id, int $dif): array
{
    return array_map('intval', array_column(
        q('SELECT oponente FROM oponentes_vencidos WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]), 'oponente'));
}
function quantosVencidos(int $id, int $dif): int { return count(vencidos($id, $dif)); }
function limpouDificuldade(int $id, int $dif): bool { return quantosVencidos($id, $dif) >= 30; }
function chefeLiberado(int $id, int $dif): bool { return quantosVencidos($id, $dif) >= 20; }
function chefeVencido(int $id, int $dif, int $chefe): bool
{
    return (bool)qv('SELECT COUNT(*) FROM chefoes_vencidos WHERE duelista_id = ? AND dificuldade = ? AND chefe = ?', [$id, $dif, $chefe], 0);
}
/** O chefão da cidade é o de número igual à dificuldade; o 6º é o final. */
function chefeDaDificuldade(int $dif): int { return $dif; }

/** Em que slot de deck (1 a 5) este oponente está para este jogador. */
function slotDoOponente(int $id, int $dif, int $n): int
{
    $s = (int)qv('SELECT slot FROM oponente_estado WHERE duelista_id = ? AND dificuldade = ? AND oponente = ?', [$id, $dif, $n], 0);
    return max(1, min(5, $s ?: 1));
}
function moverSlot(int $id, int $dif, int $n, bool $ganhou): int
{
    $s = slotDoOponente($id, $dif, $n);
    $novo = $ganhou ? min(5, $s + 1) : max(1, $s - 1);
    ex('INSERT INTO oponente_estado (duelista_id, dificuldade, oponente, slot, duelos)
        VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE slot = VALUES(slot), duelos = duelos + 1',
       [$id, $dif, $n, $novo]);
    return $novo;
}

/**
 * O MOLDE DE CADA SLOT — a tabela combinada na especificação.
 * 'niveis' é o teto de Nível dos monstros; 'ritual' e 'fusao' abrem as
 * cartas correspondentes.
 */
function moldeDoSlot(int $slot): array
{
    return [
        1 => ['nome'=>'Iniciação','monstros'=>24,'magicas'=>12,'armadilhas'=>4,'nivel_max'=>4,'alto'=>0,'ritual'=>false,'fusao'=>false],
        2 => ['nome'=>'Reforçado','monstros'=>22,'magicas'=>12,'armadilhas'=>6,'nivel_max'=>6,'alto'=>4,'ritual'=>false,'fusao'=>false],
        3 => ['nome'=>'Armado','monstros'=>20,'magicas'=>12,'armadilhas'=>8,'nivel_max'=>7,'alto'=>2,'ritual'=>false,'fusao'=>false],
        4 => ['nome'=>'Ritual','monstros'=>20,'magicas'=>14,'armadilhas'=>6,'nivel_max'=>7,'alto'=>3,'ritual'=>true,'fusao'=>false],
        5 => ['nome'=>'Assinatura','monstros'=>18,'magicas'=>14,'armadilhas'=>8,'nivel_max'=>8,'alto'=>4,'ritual'=>true,'fusao'=>true],
    ][max(1, min(5, $slot))];
}

/**
 * O eixo do oponente vira um filtro sobre as 722. O texto do eixo é
 * livre ("Água / Aqua", "Máquina / Equipamento"), então a leitura é por
 * palavra-chave — o que sobra cai no filtro genérico, e tudo bem.
 */
function filtroDoEixo(string $eixo): array
{
    $e = mb_strtolower($eixo);
    $mapa = [
        'aqua'=>'Aqua','água'=>'Aqua','agua'=>'Aqua','peixe'=>'Fish','serpente marinha'=>'Sea Serpent',
        'planta'=>'Plant','inseto'=>'Insect','rocha'=>'Rock','terra'=>'Rock',
        'besta alada'=>'Winged Beast','besta-guerreira'=>'Beast-Warrior','besta'=>'Beast',
        'máquina'=>'Machine','maquina'=>'Machine','guerreiro'=>'Warrior','zumbi'=>'Zombie',
        'demônio'=>'Fiend','demonio'=>'Fiend','trevas'=>'Fiend','mago'=>'Spellcaster',
        'dragão'=>'Dragon','dragao'=>'Dragon','fada'=>'Fairy','luz'=>'Fairy','piro'=>'Pyro',
        'fogo'=>'Pyro','dinossauro'=>'Dinosaur','réptil'=>'Reptile','reptil'=>'Reptile',
        'trovão'=>'Thunder','vento'=>'Winged Beast',
    ];
    $subs = [];
    foreach ($mapa as $chave => $sub) { if (str_contains($e, $chave)) $subs[] = $sub; }

    $tipos = [];
    if (str_contains($e, 'armadilha')) $tipos[] = 'Trap';
    if (str_contains($e, 'mágica') || str_contains($e, 'magica') || str_contains($e, 'equipamento')) { $tipos[] = 'Magic'; $tipos[] = 'Equip'; }
    if (str_contains($e, 'ritual')) $tipos[] = 'Ritual';

    return ['sub' => array_values(array_unique($subs)), 'tipo' => $tipos,
            'atk_alto' => str_contains($e, 'atk') || str_contains($e, 'força') || str_contains($e, 'forca') || str_contains($e, 'assinatura')];
}

/** Gerador determinístico — mesma semente, mesmo deck. */
function sorteioFixo(int $semente): Closure
{
    $s = $semente;
    return function (int $max) use (&$s): int {
        $s = ($s * 1103515245 + 12345) & 0x7FFFFFFF;
        return $max > 0 ? $s % $max : 0;
    };
}

/**
 * Monta o Deck Principal de um oponente. Devolve [principal, adicional].
 * Respeita 3 cópias por nome e o mínimo de 40 do manual.
 */
function deckDoOponente(int $dif, int $n, int $slot, bool $ehChefe = false): array
{
    $o = $ehChefe ? chefoes()[$n] : oponente($dif, $n);
    if (!$o) return [[], []];
    $molde = moldeDoSlot($slot);
    $f = filtroDoEixo($o['eixo'] ?? '');
    $rnd = sorteioFixo(crc32(($ehChefe ? 'C' : 'O') . $dif . '-' . $n . '-' . $slot));

    $pega = function (array $onde, array $par, int $quantos, int $limiteNivel = 99) use ($rnd): array {
        $linhas = q('SELECT num, nome, atk, nivel FROM cartas WHERE ' . implode(' AND ', $onde) . ' ORDER BY num', $par);
        $linhas = array_values(array_filter($linhas, fn($c) => (int)$c['nivel'] <= $limiteNivel));
        if (!$linhas) return [];
        $r = []; $copias = [];
        $tentativas = 0;
        while (count($r) < $quantos && $tentativas++ < $quantos * 30) {
            $c = $linhas[$rnd(count($linhas))];
            $k = (int)$c['num'];
            if (($copias[$k] ?? 0) >= 3) continue;
            $copias[$k] = ($copias[$k] ?? 0) + 1;
            $r[] = $k;
        }
        return $r;
    };

    // ---- monstros: o eixo primeiro, o resto completa
    $ondeM = ['tipo = ?', 'fusao = 0', 'ritual_monstro = 0']; $parM = ['Monster'];
    if ($f['sub']) {
        $ondeM[] = 'sub IN (' . implode(',', array_fill(0, count($f['sub']), '?')) . ')';
        foreach ($f['sub'] as $s) $parM[] = $s;
    }
    $baixos = max(0, $molde['monstros'] - $molde['alto']);
    $monstros = $pega($ondeM, $parM, $baixos, min(4, $molde['nivel_max']));
    if ($molde['alto'] > 0) {
        $monstros = array_merge($monstros, $pega($ondeM, $parM, $molde['alto'], $molde['nivel_max']));
    }
    if (count($monstros) < $molde['monstros']) {                    // eixo estreito demais: completa geral
        $falta = $molde['monstros'] - count($monstros);
        $monstros = array_merge($monstros, $pega(['tipo = ?','fusao = 0','ritual_monstro = 0'], ['Monster'], $falta, $molde['nivel_max']));
    }

    // ---- mágicas e armadilhas
    $magicas = $pega(['tipo IN ("Magic","Equip","Field")'], [], $molde['magicas']);
    $armadilhas = $pega(['tipo = ?'], ['Trap'], $molde['armadilhas']);
    if ($molde['ritual']) {
        $magicas = array_merge($magicas, $pega(['tipo = ?'], ['Ritual'], 2));
        $monstros = array_merge($monstros, $pega(['ritual_monstro = 1'], [], 2, 8));
    }

    $principal = array_merge($monstros, $magicas, $armadilhas);
    // o manual manda 40 no mínimo; se o filtro deixou faltando, completa
    while (count($principal) < 40) {
        $extra = $pega(['tipo = ?','fusao = 0','ritual_monstro = 0'], ['Monster'], 40 - count($principal), $molde['nivel_max']);
        if (!$extra) break;
        $principal = array_merge($principal, $extra);
    }
    $principal = array_slice($principal, 0, 60);

    $adicional = $molde['fusao'] ? $pega(['fusao = 1'], [], 6, 8) : [];
    return [$principal, $adicional];
}

/**
 * Fecha um duelo de campanha: grava, paga, marca e devolve o resumo
 * para a tela de fim.
 */
function fecharDueloCampanha(int $id, int $dif, array $alvo, bool $venceu, bool $semDano, int $slot): array
{
    $ehChefe = ($alvo['tipo'] ?? '') === 'chefe';
    $n = (int)$alvo['n'];
    $nome = $alvo['nome'];
    $primeiraVez = $ehChefe ? !chefeVencido($id, $dif, $n)
                            : !in_array($n, vencidos($id, $dif), true);
    $temEnigma = in_array('enigma', reliquiasEquipadas($id), true);

    $resumo = ['venceu' => $venceu, 'oneub' => 0, 'cartas' => [], 'booster' => null,
               'reliquia' => null, 'peca' => false, 'enigma' => false, 'conquistas' => []];

    ex('INSERT INTO duelos (duelista_id, modo, dificuldade, alvo_tipo, alvo_n, alvo_nome, resultado, sem_dano, slot, criado_em)
        VALUES (?,?,?,?,?,?,?,?,?,NOW())',
       [$id, 'campanha', $dif, $alvo['tipo'] ?? 'oponente', $n, $nome, $venceu ? 'vitoria' : 'derrota', $semDano ? 1 : 0, $slot]);

    if ($venceu) {
        $ganho = premioDeVitoria($dif, $slot, $semDano, $temEnigma, $ehChefe, $primeiraVez);
        creditar($id, $dif, $ganho, 'vitoria', $nome);
        $resumo['oneub'] = $ganho;

        if ($ehChefe) {
            ex('INSERT IGNORE INTO chefoes_vencidos (duelista_id, dificuldade, chefe, criado_em) VALUES (?,?,?,NOW())', [$id, $dif, $n]);
            $slugReliquia = chefoes()[$n]['reliquia'];
            if (!tenhoReliquia($id, $slugReliquia)) {
                darReliquia($id, $slugReliquia);
                $resumo['reliquia'] = $slugReliquia;
            } elseif ($primeiraVez) {
                creditar($id, $dif, 300, 'vitoria', 'Relíquia repetida, convertida em ' . YU_MOEDA);
                $resumo['oneub'] += 300;
                $resumo['booster'] = sortearBoosterDePremio($id, $dif, true);
            }
            if ($dif === 5) {
                $resumo['peca'] = true;
                $resumo['enigma'] = darPecaDoEnigma($id, $n);
            }
        } else {
            ex('INSERT IGNORE INTO oponentes_vencidos (duelista_id, dificuldade, oponente, criado_em) VALUES (?,?,?,NOW())', [$id, $dif, $n]);
        }

        $d = dificuldade($dif);
        if (random_int(1, 100) <= $d['booster']) { $resumo['booster'] = $resumo['booster'] ?: sortearBoosterDePremio($id, $dif, false); }
        if (random_int(1, 100) <= $d['carta'])  { $resumo['cartas'] = premiarCartas($id, $dif, $ehChefe ? 2 : 1); }

        ex('UPDATE duelistas SET sequencia_vitorias = sequencia_vitorias + 1 WHERE id = ?', [$id]);
        if ($semDano) marcar($id, 'semdano');
    } else {
        $consolo = consoloDeDerrota($dif);
        creditar($id, $dif, $consolo, 'derrota', $nome);
        $resumo['oneub'] = $consolo;
        ex('UPDATE duelistas SET sequencia_vitorias = 0 WHERE id = ?', [$id]);
    }

    if (!$ehChefe) { moverSlot($id, $dif, $n, $venceu); }
    $resumo['conquistas'] = conferirConquistas($id);
    return $resumo;
}

/** Cartas de prêmio: sorteadas dentro da faixa de força da dificuldade. */
function premiarCartas(int $id, int $dif, int $quantas): array
{
    $teto = [1 => 1500, 2 => 1900, 3 => 2200, 4 => 2500, 5 => 3000][$dif] ?? 1500;
    $linhas = q('SELECT num FROM cartas WHERE atk <= ? ORDER BY RAND() LIMIT ' . (int)$quantas, [$teto]);
    $nums = array_map(fn($l) => (int)$l['num'], $linhas);
    [$entregues] = darCartas($id, $dif, $nums, 'vitoria');
    return array_map(fn($n) => carta($n), $entregues);
}
