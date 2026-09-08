<?php
/**
 * AS RELÍQUIAS DO MILÊNIO.
 *
 * Regras combinadas, e todas valem só na CAMPANHA:
 *   · o jogador começa com 1 sorteada entre 6 — o Enigma nunca entra;
 *   · cada chefão entrega a dele, uma vez;
 *   · se o chefão guarda a que já é sua, a recompensa vira 300 ONEUB + booster;
 *   · vencer chefão no MUITO DIFÍCIL dá 1 Peça do Enigma. 6 peças = Enigma;
 *   · 1 relíquia equipada por duelo. 2º encaixe no nível 12, 3º no 24;
 *   · nenhuma habilidade cria elo de Cadeia. Elas agem fora dela.
 */
declare(strict_types=1);

function minhasReliquias(int $id): array
{
    return q('SELECT * FROM posse_reliquias WHERE duelista_id = ? ORDER BY obtida_em', [$id]);
}
function tenhoReliquia(int $id, string $slug): bool
{
    return (bool)qv('SELECT COUNT(*) FROM posse_reliquias WHERE duelista_id = ? AND reliquia = ?', [$id, $slug], 0);
}
function encaixes(int $nivel): int
{
    if ($nivel >= 24) return 3;
    if ($nivel >= 12) return 2;
    return 1;
}
function reliquiasEquipadas(int $id): array
{
    return array_column(q('SELECT reliquia FROM posse_reliquias WHERE duelista_id = ? AND equipada = 1', [$id]), 'reliquia');
}
function equiparReliquia(int $id, string $slug, int $nivel): array
{
    if (!tenhoReliquia($id, $slug)) return ['erro' => 'Você não tem essa relíquia.'];
    $eq = reliquiasEquipadas($id);
    if (in_array($slug, $eq, true)) {
        ex('UPDATE posse_reliquias SET equipada = 0 WHERE duelista_id = ? AND reliquia = ?', [$id, $slug]);
        return ['ok' => true, 'estado' => 'fora'];
    }
    if (count($eq) >= encaixes($nivel)) {
        return ['erro' => 'Você tem ' . encaixes($nivel) . ' encaixe(s). Tire uma relíquia antes de pôr outra.'];
    }
    ex('UPDATE posse_reliquias SET equipada = 1 WHERE duelista_id = ? AND reliquia = ?', [$id, $slug]);
    return ['ok' => true, 'estado' => 'dentro'];
}
function darReliquia(int $id, string $slug): void
{
    ex('INSERT IGNORE INTO posse_reliquias (duelista_id, reliquia, obtida_em, equipada) VALUES (?,?,NOW(),0)', [$id, $slug]);
}
function pecasDoEnigma(int $id): int
{
    return (int)qv('SELECT COUNT(*) FROM pecas_enigma WHERE duelista_id = ?', [$id], 0);
}
function darPecaDoEnigma(int $id, int $chefe): bool
{
    ex('INSERT IGNORE INTO pecas_enigma (duelista_id, chefe, obtida_em) VALUES (?,?,NOW())', [$id, $chefe]);
    if (pecasDoEnigma($id) >= 6 && !tenhoReliquia($id, 'enigma')) {
        darReliquia($id, 'enigma');
        mandarMensagem($id, 'O Enigma do Milênio',
            "As seis peças se encaixaram sozinhas.\n\nO Enigma está inteiro, e ele cumpre um desejo. "
          . "O seu desejo, duelista, é o de sempre: mais uma partida.\n\n— Indawora", 'mascote');
        return true;
    }
    return false;
}

/**
 * Usar a habilidade dentro de um duelo. Mexe no estado e devolve o que
 * a tela precisa mostrar.
 */
function usarReliquia(array &$e, string $slug, int $lado = 0): array
{
    if (!empty($e['j'][$lado]['reliquia_usada'])) return ['erro' => 'Você já usou uma relíquia neste duelo.'];
    $o = 1 - $lado;
    $r = reliquiasTodas()[$slug] ?? null;
    if (!$r) return ['erro' => 'Relíquia desconhecida.'];

    $resposta = ['ok' => true, 'nome' => $r['nome']];
    switch ($slug) {
        case 'olho':
            $resposta['revelar_mao'] = array_map(fn($n) => carta((int)$n), $e['j'][$o]['mao']);
            diz($e, 'Olho do Milênio: a mão do oponente ficou visível por um instante.', 'reliquia');
            break;
        case 'chave':
            $r2 = [];
            foreach ($e['j'][$o]['mag'] as $i => $z) { if ($z) $r2[$i] = carta((int)$z['num']); }
            $resposta['revelar_baixadas'] = $r2;
            diz($e, 'Chave do Milênio: as cartas baixadas do oponente foram abertas para você.', 'reliquia');
            break;
        case 'colar':
            $resposta['topo'] = array_map(fn($n) => carta((int)$n), array_slice($e['j'][$lado]['deck'], 0, 3));
            diz($e, 'Colar do Milênio: você viu as 3 cartas do topo do seu Deck.', 'reliquia');
            break;
        case 'argola':
            $resposta['pede_busca'] = true;
            diz($e, 'Anel do Milênio: procure 1 carta no Deck e ponha no topo.', 'reliquia');
            break;
        case 'balanca':
            if (somaAtk($e, $lado) < somaAtk($e, $o)) {
                comprarCarta($e, $lado, false);
                diz($e, 'Balança do Milênio: o seu campo pesa menos, e o julgamento te deu 1 carta.', 'reliquia');
                $resposta['comprou'] = true;
            } else {
                diz($e, 'Balança do Milênio: o seu campo não pesa menos que o dele. Nada acontece.', 'reliquia');
            }
            break;
        case 'cetro':
            $e['j'][$o]['pula_batalha'] = max(1, (int)$e['j'][$o]['pula_batalha']);
            diz($e, 'Vara do Milênio: o oponente pula a próxima Fase de Batalha dele.', 'reliquia');
            break;
        case 'enigma':
            $e['j'][$lado]['reliquia_usada'] = false;
            diz($e, 'Enigma do Milênio: o desejo foi concedido — você pode usar outra relíquia neste duelo.', 'reliquia');
            return $resposta;
    }
    $e['j'][$lado]['reliquia_usada'] = true;
    return $resposta;
}

/** O Anel pega uma carta do Deck e põe no topo, embaralhando o resto. */
function anelBuscar(array &$e, int $num, int $lado = 0): array
{
    $i = array_search($num, $e['j'][$lado]['deck'], true);
    if ($i === false) return ['erro' => 'Essa carta não está no seu Deck.'];
    array_splice($e['j'][$lado]['deck'], (int)$i, 1);
    $e['j'][$lado]['deck'] = embaralhar($e['j'][$lado]['deck']);
    array_unshift($e['j'][$lado]['deck'], $num);
    diz($e, 'Anel do Milênio: ' . carta($num)['nome'] . ' foi para o topo do Deck, que foi embaralhado antes.', 'reliquia');
    return ['ok' => true];
}
