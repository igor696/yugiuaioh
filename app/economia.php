<?php
/**
 * A ECONOMIA.
 *
 * Duas regras que mandam em tudo aqui e estão escritas também no manual,
 * no cofre e no rodapé:
 *
 *   1. NÃO EXISTE dinheiro de verdade. O ONEUB não é vendido, não é
 *      comprado e não vale nada fora daqui.
 *   2. CADA DIFICULDADE TEM A SUA CARTEIRA. O que você juntou no Muito
 *      Fácil não gasta no Difícil. Isso é o que impede o jogador de
 *      farmar o nível bobo e comprar a campanha inteira.
 */
declare(strict_types=1);

function carteira(int $duelistaId, int $dificuldade): array
{
    $c = q1('SELECT * FROM carteiras WHERE duelista_id = ? AND dificuldade = ?', [$duelistaId, $dificuldade]);
    if (!$c) {
        ex('INSERT INTO carteiras (duelista_id, dificuldade, oneub, selos) VALUES (?,?,0,0)', [$duelistaId, $dificuldade]);
        $c = q1('SELECT * FROM carteiras WHERE duelista_id = ? AND dificuldade = ?', [$duelistaId, $dificuldade]);
    }
    return $c ?: ['oneub' => 0, 'selos' => 0];
}
function saldo(int $duelistaId, int $dificuldade): int
{
    return (int)carteira($duelistaId, $dificuldade)['oneub'];
}
function saldoTotal(int $duelistaId): int
{
    return (int)qv('SELECT COALESCE(SUM(oneub),0) FROM carteiras WHERE duelista_id = ?', [$duelistaId], 0);
}

function creditar(int $duelistaId, int $dificuldade, int $valor, string $motivo, string $detalhe = ''): void
{
    if ($valor === 0) return;
    carteira($duelistaId, $dificuldade);
    ex('UPDATE carteiras SET oneub = oneub + ? WHERE duelista_id = ? AND dificuldade = ?', [$valor, $duelistaId, $dificuldade]);
    ex('INSERT INTO extrato (duelista_id, dificuldade, valor, motivo, detalhe, criado_em) VALUES (?,?,?,?,?,NOW())',
       [$duelistaId, $dificuldade, $valor, $motivo, $detalhe]);
}
/** Devolve false quando não há saldo — quem chama decide o que dizer. */
function debitar(int $duelistaId, int $dificuldade, int $valor, string $motivo, string $detalhe = ''): bool
{
    $valor = abs($valor);
    if (saldo($duelistaId, $dificuldade) < $valor) return false;
    ex('UPDATE carteiras SET oneub = oneub - ? WHERE duelista_id = ? AND dificuldade = ?', [$valor, $duelistaId, $dificuldade]);
    ex('INSERT INTO extrato (duelista_id, dificuldade, valor, motivo, detalhe, criado_em) VALUES (?,?,?,?,?,NOW())',
       [$duelistaId, $dificuldade, -$valor, $motivo, $detalhe]);
    return true;
}
function extrato(int $duelistaId, ?int $dificuldade = null, int $limite = 60): array
{
    if ($dificuldade === null) {
        return q('SELECT * FROM extrato WHERE duelista_id = ? ORDER BY id DESC LIMIT ' . (int)$limite, [$duelistaId]);
    }
    return q('SELECT * FROM extrato WHERE duelista_id = ? AND dificuldade = ? ORDER BY id DESC LIMIT ' . (int)$limite,
             [$duelistaId, $dificuldade]);
}

/** Selos do Faraó: só conquista e presente, nunca venda. */
function darSelo(int $duelistaId, int $dificuldade, int $quantos, string $detalhe = ''): void
{
    ex('UPDATE carteiras SET selos = selos + ? WHERE duelista_id = ? AND dificuldade = ?', [$quantos, $duelistaId, $dificuldade]);
    ex('INSERT INTO extrato (duelista_id, dificuldade, valor, motivo, detalhe, criado_em) VALUES (?,?,0,?,?,NOW())',
       [$duelistaId, $dificuldade, 'selo', $quantos . ' Selo(s) do Faraó · ' . $detalhe]);
}

/**
 * O PRÊMIO DE UMA VITÓRIA.
 * Base da dificuldade, mais os modificadores combinados na especificação:
 * deck 5 do oponente +25%, duelo sem perder PV +50%, Enigma equipado +10%.
 */
function premioDeVitoria(int $dificuldade, int $deckDoOponente, bool $semDano, bool $temEnigma, bool $ehChefe, bool $primeiraVez): int
{
    $base = dificuldade($dificuldade)['oneub'];
    $mult = 1.0;
    if ($deckDoOponente >= 5) $mult += 0.25;
    if ($semDano)             $mult += 0.50;
    if ($temEnigma)           $mult += 0.10;
    if ($ehChefe)             $mult *= $primeiraVez ? 3.0 : 1.5;
    return (int)round($base * $mult);
}
/** Derrota devolve 10% — ninguém sai de mãos vazias. */
function consoloDeDerrota(int $dificuldade): int
{
    return (int)round(dificuldade($dificuldade)['oneub'] * 0.10);
}

function rotuloMotivo(string $m): string
{
    return [
        'vitoria'   => 'Vitória em duelo',
        'derrota'   => 'Consolo de derrota',
        'presenca'  => 'Presença diária',
        'conquista' => 'Conquista concluída',
        'booster'   => 'Compra de booster',
        'carta'     => 'Compra de carta',
        'avatar'    => 'Troca de avatar',
        'melhoria'  => 'Melhoria do Djed',
        'sorte'     => 'Item da Merit-Ka',
        'nome'      => 'Troca de nome',
        'presente'  => 'Presente do Faraó',
        'codigo'    => 'Código resgatado',
        'convite'   => 'Convite aceito',
        'venda'     => 'Venda de repetida',
        'selo'      => 'Selo do Faraó',
    ][$m] ?? $m;
}
