<?php
/**
 * OS DECKS.
 *
 * A validação sai inteira do manual em PDF, pág. 3:
 *
 *   Deck Principal  40 a 60 cartas
 *   Deck Adicional  0 a 15, SÓ Monstros de Fusão
 *   Deck Auxiliar   0 a 15, qualquer carta
 *   Máximo 3 cópias da mesma carta somando os três Decks
 *
 * O montador AVISA e nunca trava — a decisão do que entra é do jogador,
 * mesma regra de casa do Magic the Minas. Só o duelo exige Deck válido.
 */
declare(strict_types=1);

const DECK_MIN = 40, DECK_MAX = 60, EXTRA_MAX = 15, AUX_MAX = 15, COPIAS_MAX = 3;

function meusDecks(int $duelistaId, int $dif): array
{
    return q('SELECT d.*,
              (SELECT COALESCE(SUM(quantidade),0) FROM deck_cartas WHERE deck_id = d.id AND parte = "principal") AS n_principal,
              (SELECT COALESCE(SUM(quantidade),0) FROM deck_cartas WHERE deck_id = d.id AND parte = "adicional") AS n_adicional,
              (SELECT COALESCE(SUM(quantidade),0) FROM deck_cartas WHERE deck_id = d.id AND parte = "auxiliar")  AS n_auxiliar
              FROM decks d WHERE d.duelista_id = ? AND d.dificuldade = ? ORDER BY d.ativo DESC, d.id DESC',
             [$duelistaId, $dif]);
}
function deck(int $id, int $duelistaId): ?array
{
    return q1('SELECT * FROM decks WHERE id = ? AND duelista_id = ?', [$id, $duelistaId]);
}
function cartasDoDeck(int $deckId): array
{
    return q('SELECT dc.parte, dc.quantidade, c.* FROM deck_cartas dc
              JOIN cartas c ON c.num = dc.carta_num
              WHERE dc.deck_id = ? ORDER BY FIELD(dc.parte,"principal","adicional","auxiliar"),
              FIELD(c.tipo,"Monster","Ritual","Magic","Equip","Field","Trap"), c.nivel DESC, c.atk DESC, c.nome',
             [$deckId]);
}
function criarDeck(int $duelistaId, int $dif, string $nome): int
{
    ex('INSERT INTO decks (duelista_id, dificuldade, nome, criado_em) VALUES (?,?,?,NOW())',
       [$duelistaId, $dif, mb_substr(trim($nome) ?: 'Deck novo', 0, 40)]);
    $id = ultimoId();
    // o primeiro deck da dificuldade já nasce sendo o ativo
    if ((int)qv('SELECT COUNT(*) FROM decks WHERE duelista_id = ? AND dificuldade = ?', [$duelistaId, $dif], 0) === 1) {
        ex('UPDATE decks SET ativo = 1 WHERE id = ?', [$id]);
    }
    return $id;
}
function ativarDeck(int $id, int $duelistaId, int $dif): void
{
    ex('UPDATE decks SET ativo = 0 WHERE duelista_id = ? AND dificuldade = ?', [$duelistaId, $dif]);
    ex('UPDATE decks SET ativo = 1 WHERE id = ? AND duelista_id = ?', [$id, $duelistaId]);
}
function deckAtivo(int $duelistaId, int $dif): ?array
{
    return q1('SELECT * FROM decks WHERE duelista_id = ? AND dificuldade = ? AND ativo = 1', [$duelistaId, $dif]);
}

/** Quantas cópias desta carta o deck já tem somando as três partes. */
function copiasNoDeck(int $deckId, int $num): int
{
    return (int)qv('SELECT COALESCE(SUM(quantidade),0) FROM deck_cartas WHERE deck_id = ? AND carta_num = ?', [$deckId, $num], 0);
}

/**
 * Põe uma carta no deck. Devolve ['ok'=>true] ou ['erro'=>'...'].
 * As duas travas aqui são as duas que não se negocia: 3 cópias e
 * "só Fusão no Adicional". O resto é aviso na tela.
 */
function porNoDeck(int $deckId, int $duelistaId, int $dif, int $num, string $parte): array
{
    $d = deck($deckId, $duelistaId);
    if (!$d) return ['erro' => 'Deck não encontrado.'];
    $c = carta($num);
    if (!$c) return ['erro' => 'Carta não encontrada.'];

    if (!in_array($parte, ['principal','adicional','auxiliar'], true)) return ['erro' => 'Parte inválida.'];
    if ($parte === 'adicional' && (int)$c['fusao'] !== 1) {
        return ['erro' => 'No Deck Adicional só entram Monstros de Fusão (manual, pág. 3).'];
    }
    if (copiasNoDeck($deckId, $num) >= COPIAS_MAX) {
        return ['erro' => 'Já são 3 cópias de "' . $c['nome'] . '" somando os três Decks.'];
    }
    $tenho = tenhoQuantas($duelistaId, $dif, $num);
    $usadas = copiasNoDeck($deckId, $num);
    if (!ehFarao() && $usadas >= $tenho) {
        return ['erro' => 'Você só tem ' . $tenho . ' cópia(s) desta carta nesta dificuldade.'];
    }
    ex('INSERT INTO deck_cartas (deck_id, carta_num, parte, quantidade) VALUES (?,?,?,1)
        ON DUPLICATE KEY UPDATE quantidade = quantidade + 1', [$deckId, $num, $parte]);
    return ['ok' => true];
}
function tirarDoDeck(int $deckId, int $num, string $parte): void
{
    ex('UPDATE deck_cartas SET quantidade = quantidade - 1 WHERE deck_id = ? AND carta_num = ? AND parte = ?', [$deckId, $num, $parte]);
    ex('DELETE FROM deck_cartas WHERE deck_id = ? AND carta_num = ? AND parte = ? AND quantidade <= 0', [$deckId, $num, $parte]);
}

/**
 * CONFERÊNCIA DO DECK.
 * Devolve ['valido'=>bool,'erros'=>[],'avisos'=>[],'n'=>[...],'curva'=>[...]]
 * Erro impede duelar. Aviso é conselho — o do Indawora, não trava nada.
 */
function conferirDeck(int $deckId): array
{
    $cartas = cartasDoDeck($deckId);
    $n = ['principal' => 0, 'adicional' => 0, 'auxiliar' => 0];
    $tipos = ['monstro' => 0, 'magica' => 0, 'armadilha' => 0, 'ritual' => 0, 'fusao' => 0];
    $curva = array_fill(1, 8, 0);
    $copias = [];
    $temRitualMagica = []; $temRitualMonstro = [];

    foreach ($cartas as $c) {
        $qtd = (int)$c['quantidade'];
        $n[$c['parte']] += $qtd;
        $copias[$c['num']] = ($copias[$c['num']] ?? 0) + $qtd;
        if ($c['tipo'] === 'Monster') {
            $tipos['monstro'] += $qtd;
            $nv = max(1, min(8, (int)$c['nivel']));
            $curva[$nv] += $qtd;
            if ((int)$c['fusao'] === 1) $tipos['fusao'] += $qtd;
            if ((int)$c['ritual_monstro'] === 1) { $tipos['ritual'] += $qtd; $temRitualMonstro[] = $c['nome']; }
        } elseif ($c['tipo'] === 'Trap') { $tipos['armadilha'] += $qtd; }
        else {
            $tipos['magica'] += $qtd;
            if ($c['tipo'] === 'Ritual') $temRitualMagica[] = $c['nome'];
        }
    }

    $erros = []; $avisos = [];
    if ($n['principal'] < DECK_MIN) $erros[] = 'O Deck Principal tem ' . $n['principal'] . ' cartas. O mínimo é ' . DECK_MIN . '.';
    if ($n['principal'] > DECK_MAX) $erros[] = 'O Deck Principal tem ' . $n['principal'] . ' cartas. O máximo é ' . DECK_MAX . '.';
    if ($n['adicional'] > EXTRA_MAX) $erros[] = 'O Deck Adicional passa de ' . EXTRA_MAX . ' cartas.';
    if ($n['auxiliar']  > AUX_MAX)   $erros[] = 'O Deck Auxiliar passa de ' . AUX_MAX . ' cartas.';
    foreach ($copias as $num => $q) {
        if ($q > COPIAS_MAX) { $c = carta((int)$num); $erros[] = 'São ' . $q . ' cópias de "' . ($c['nome'] ?? $num) . '". O limite é 3.'; }
    }

    if ($n['principal'] > 45 && $n['principal'] <= DECK_MAX) {
        $avisos[] = 'Deck com ' . $n['principal'] . ' cartas. Quanto mais cartas, mais difícil comprar a que você precisa na hora certa — o manual aconselha ficar perto de 40.';
    }
    if ($tipos['monstro'] < 16 && $n['principal'] >= DECK_MIN) {
        $avisos[] = 'Só ' . $tipos['monstro'] . ' monstros. Com essa conta você vai passar turno sem ter o que invocar.';
    }
    $altos = $curva[5] + $curva[6] + $curva[7] + $curva[8];
    if ($altos > 8) {
        $avisos[] = $altos . ' monstros de Nível 5 ou mais. Cada um custa Tributo, e Tributo custa outro monstro no campo.';
    }
    if ($temRitualMonstro && !$temRitualMagica) {
        $avisos[] = 'Tem Monstro de Ritual mas nenhuma Mágica de Ritual. Sem ela, o monstro não sai da mão (manual, pág. 12).';
    }
    if ($tipos['fusao'] > 0 && $n['adicional'] === 0) {
        $avisos[] = 'Você pôs Monstro de Fusão no Principal. O lugar dele é o Deck Adicional.';
    }

    return [
        'valido' => empty($erros), 'erros' => $erros, 'avisos' => $avisos,
        'n' => $n, 'tipos' => $tipos, 'curva' => $curva, 'cartas' => $cartas,
    ];
}

/** A lista embaralhável, uma entrada por cópia — é o que o duelo recebe. */
function listaParaDuelo(int $deckId): array
{
    $lista = [];
    foreach (q('SELECT carta_num, quantidade FROM deck_cartas WHERE deck_id = ? AND parte = "principal"', [$deckId]) as $l) {
        for ($i = 0; $i < (int)$l['quantidade']; $i++) $lista[] = (int)$l['carta_num'];
    }
    return $lista;
}
function listaAdicional(int $deckId): array
{
    $lista = [];
    foreach (q('SELECT carta_num, quantidade FROM deck_cartas WHERE deck_id = ? AND parte = "adicional"', [$deckId]) as $l) {
        for ($i = 0; $i < (int)$l['quantidade']; $i++) $lista[] = (int)$l['carta_num'];
    }
    return $lista;
}

/**
 * "INDAWORA, ME AJUDA" — completa o deck com o que o jogador TEM.
 * Não inventa carta que ele não possui e não passa de 3 cópias. A
 * proporção segue o molde do manual: ~22 monstros, ~12 mágicas, ~6
 * armadilhas, curva pesando o Nível 4.
 */
function completarDeck(int $deckId, int $duelistaId, int $dif): array
{
    $conf = conferirDeck($deckId);
    $faltam = DECK_MIN - $conf['n']['principal'];
    if ($faltam <= 0) return ['ok' => true, 'postas' => 0];

    $alvo = ['monstro' => 22, 'magica' => 12, 'armadilha' => 6];
    $tem  = $conf['tipos'];
    $postas = 0;

    $minhas = q('SELECT c.*, pc.quantidade AS tenho FROM posse_cartas pc
                 JOIN cartas c ON c.num = pc.carta_num
                 WHERE pc.duelista_id = ? AND pc.dificuldade = ? AND pc.quantidade > 0
                 ORDER BY c.atk DESC, c.nivel ASC', [$duelistaId, $dif]);

    $prefere = function (array $c) use (&$tem, $alvo): int {
        if ($c['tipo'] === 'Monster') {
            if ($tem['monstro'] >= $alvo['monstro']) return 0;
            $nv = (int)$c['nivel'];
            return $nv <= 4 ? 3 : ($nv <= 6 ? 2 : 1);
        }
        if ($c['tipo'] === 'Trap') return $tem['armadilha'] < $alvo['armadilha'] ? 2 : 0;
        return $tem['magica'] < $alvo['magica'] ? 2 : 0;
    };

    // duas passadas: primeiro o que combina com o molde, depois o que sobrar
    for ($passada = 0; $passada < 2 && $faltam > 0; $passada++) {
        foreach ($minhas as $c) {
            if ($faltam <= 0) break;
            if ((int)$c['fusao'] === 1) continue;                  // fusão vai no Adicional
            if ($passada === 0 && $prefere($c) === 0) continue;
            $podem = min((int)$c['tenho'], COPIAS_MAX) - copiasNoDeck($deckId, (int)$c['num']);
            for ($i = 0; $i < $podem && $faltam > 0; $i++) {
                $r = porNoDeck($deckId, $duelistaId, $dif, (int)$c['num'], 'principal');
                if (!empty($r['ok'])) {
                    $postas++; $faltam--;
                    if ($c['tipo'] === 'Monster') $tem['monstro']++;
                    elseif ($c['tipo'] === 'Trap') $tem['armadilha']++;
                    else $tem['magica']++;
                } else break;
            }
        }
    }
    return ['ok' => true, 'postas' => $postas, 'faltam' => max(0, $faltam)];
}
