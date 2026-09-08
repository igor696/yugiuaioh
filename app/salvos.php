<?php
/**
 * JOGOS SALVOS — 5 lugares por dificuldade.
 * O que se guarda é o retrato do progresso: onde parou, o que tinha, o
 * que já venceu. Voltar para um lugar salvo devolve tudo daquele momento
 * NAQUELA dificuldade, sem tocar nas outras.
 */
declare(strict_types=1);

const SALVOS_POR_DIFICULDADE = 5;

function retrato(int $id, int $dif): array
{
    return [
        'quando'    => date('c'),
        'carteira'  => carteira($id, $dif),
        'progresso' => progresso($id, $dif),
        'vencidos'  => vencidos($id, $dif),
        'chefoes'   => array_map('intval', array_column(q('SELECT chefe FROM chefoes_vencidos WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]), 'chefe')),
        'slots'     => q('SELECT oponente, slot FROM oponente_estado WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]),
        'cartas'    => q('SELECT carta_num, quantidade FROM posse_cartas WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]),
        'decks'     => array_map(function ($d) {
                          $d['cartas'] = q('SELECT carta_num, parte, quantidade FROM deck_cartas WHERE deck_id = ?', [$d['id']]);
                          return $d;
                       }, q('SELECT * FROM decks WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif])),
    ];
}

function salvar(int $id, int $dif, int $slot, string $nome, bool $automatico = false): array
{
    $slot = max(1, min(SALVOS_POR_DIFICULDADE, $slot));
    $r = retrato($id, $dif);
    ex('INSERT INTO jogos_salvos (duelista_id, dificuldade, slot, nome, retrato, automatico, salvo_em)
        VALUES (?,?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE nome = VALUES(nome), retrato = VALUES(retrato),
                                automatico = VALUES(automatico), salvo_em = NOW()',
       [$id, $dif, $slot, mb_substr($nome ?: ('Ponto ' . $slot), 0, 40), json_encode($r), $automatico ? 1 : 0]);
    return ['ok' => true];
}
function autoSalvar(int $id, int $dif): void
{
    salvar($id, $dif, SALVOS_POR_DIFICULDADE, 'Automático', true);
}
function listarSalvos(int $id, ?int $dif = null): array
{
    return $dif === null
        ? q('SELECT id,duelista_id,dificuldade,slot,nome,automatico,salvo_em FROM jogos_salvos WHERE duelista_id = ? ORDER BY dificuldade, slot', [$id])
        : q('SELECT id,duelista_id,dificuldade,slot,nome,automatico,salvo_em FROM jogos_salvos WHERE duelista_id = ? AND dificuldade = ? ORDER BY slot', [$id, $dif]);
}

function carregar(int $id, int $salvoId): array
{
    $s = q1('SELECT * FROM jogos_salvos WHERE id = ? AND duelista_id = ?', [$salvoId, $id]);
    if (!$s) return ['erro' => 'Ponto salvo não encontrado.'];
    $r = json_decode((string)$s['retrato'], true);
    if (!$r) return ['erro' => 'O ponto salvo está ilegível.'];
    $dif = (int)$s['dificuldade'];

    db()->beginTransaction();
    try {
        ex('UPDATE carteiras SET oneub = ?, selos = ? WHERE duelista_id = ? AND dificuldade = ?',
           [(int)$r['carteira']['oneub'], (int)$r['carteira']['selos'], $id, $dif]);
        ex('DELETE FROM oponentes_vencidos WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
        foreach ($r['vencidos'] as $n) {
            ex('INSERT IGNORE INTO oponentes_vencidos (duelista_id, dificuldade, oponente, criado_em) VALUES (?,?,?,NOW())', [$id, $dif, (int)$n]);
        }
        ex('DELETE FROM chefoes_vencidos WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
        foreach ($r['chefoes'] as $n) {
            ex('INSERT IGNORE INTO chefoes_vencidos (duelista_id, dificuldade, chefe, criado_em) VALUES (?,?,?,NOW())', [$id, $dif, (int)$n]);
        }
        ex('DELETE FROM oponente_estado WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
        foreach ($r['slots'] as $l) {
            ex('INSERT INTO oponente_estado (duelista_id,dificuldade,oponente,slot,duelos) VALUES (?,?,?,?,0)',
               [$id, $dif, (int)$l['oponente'], (int)$l['slot']]);
        }
        ex('DELETE FROM posse_cartas WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
        foreach ($r['cartas'] as $l) {
            ex('INSERT INTO posse_cartas (duelista_id,dificuldade,carta_num,quantidade) VALUES (?,?,?,?)',
               [$id, $dif, (int)$l['carta_num'], (int)$l['quantidade']]);
        }
        foreach (q('SELECT id FROM decks WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]) as $d) {
            ex('DELETE FROM deck_cartas WHERE deck_id = ?', [(int)$d['id']]);
        }
        ex('DELETE FROM decks WHERE duelista_id = ? AND dificuldade = ?', [$id, $dif]);
        foreach ($r['decks'] as $d) {
            ex('INSERT INTO decks (duelista_id,dificuldade,nome,ativo,criado_em) VALUES (?,?,?,?,NOW())',
               [$id, $dif, $d['nome'], (int)$d['ativo']]);
            $novo = ultimoId();
            foreach ($d['cartas'] as $c) {
                ex('INSERT INTO deck_cartas (deck_id,carta_num,parte,quantidade) VALUES (?,?,?,?)',
                   [$novo, (int)$c['carta_num'], $c['parte'], (int)$c['quantidade']]);
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        error_log('carregar salvo: ' . $e->getMessage());
        return ['erro' => 'Não consegui carregar esse ponto.'];
    }
    return ['ok' => true, 'dificuldade' => $dif];
}
function apagarSalvo(int $id, int $salvoId): void
{
    ex('DELETE FROM jogos_salvos WHERE id = ? AND duelista_id = ?', [$salvoId, $id]);
}
