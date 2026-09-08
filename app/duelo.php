<?php
/**
 * O MOTOR DE DUELO.
 *
 * As regras aqui saem TODAS do manual em PDF, e nenhuma foi inventada:
 *
 *   pág. 2   8000 Pontos de Vida; vence quem zerar o do outro, ou quem
 *            fizer o outro comprar sem ter carta no Deck.
 *   pág. 5-6 5 zonas de monstro, 5 de Magia/Armadilha, 1 de Campo à parte.
 *   pág. 13  Uma Invocação-Normal OU um Baixar por turno, nunca as duas.
 *            Nível 5-6 pede 1 Tributo; Nível 7+ pede 2.
 *   pág. 14  Invocação por Virar não tem limite, mas não no turno em que
 *            o monstro foi baixado.
 *   pág. 17  Armadilha só ativa a partir do turno SEGUINTE ao que foi baixada.
 *   pág. 19  Quem começa não compra e não conduz Fase de Batalha no 1º turno.
 *   pág. 20  COMPRA → APOIO → PRINCIPAL 1 → BATALHA → PRINCIPAL 2 → FINAL,
 *            e sem Fase de Batalha não existe Principal 2.
 *   pág. 22  Cada monstro em Posição de Ataque ataca uma vez por turno.
 *   pág. 23  Mais de 6 cartas na mão no fim do turno: descarta até 6.
 *   pág. 24  ATK × ATK: maior vence, a diferença sai dos PV do perdedor;
 *            iguais, os dois morrem e ninguém sofre dano.
 *   pág. 25  ATK × DEF: maior que a DEF destrói e ninguém sofre dano;
 *            igual, nada acontece; menor, a diferença sai dos SEUS PV.
 *            Monstro com 0 de ATK não destrói nada em batalha (pág. 24).
 *
 * O estado inteiro do duelo vive no servidor, em JSON, e o navegador só
 * recebe o que aquele lado pode ver. A mão do oponente não é enviada —
 * essa é a mesma regra de casa do Magic the Minas, e é o que impede
 * trapaça olhando o código da página.
 */
declare(strict_types=1);

const PV_INICIAL = 8000;
const FASES = ['compra','apoio','principal1','batalha','principal2','final'];

function nomeFase(string $f): string
{
    return ['compra'=>'Fase de Compra','apoio'=>'Fase de Apoio','principal1'=>'Fase Principal 1',
            'batalha'=>'Fase de Batalha','principal2'=>'Fase Principal 2','final'=>'Fase Final'][$f] ?? $f;
}

function ladoVazio(array $deck, array $extra): array
{
    return [
        'pv' => PV_INICIAL,
        'deck' => $deck, 'mao' => [], 'extra' => $extra, 'cemiterio' => [],
        'mon' => array_fill(0, 5, null),
        'mag' => array_fill(0, 5, null),
        'campo' => null,
        'invocou' => false,          // Invocação-Normal / Baixar já usados neste turno
        'reliquia_usada' => false,
        'pula_batalha' => false,     // a Vara do Milênio marca aqui
        'dano_sofrido' => 0,
    ];
}

/** Embaralha de verdade — random_int, não rand(). */
function embaralhar(array $a): array
{
    for ($i = count($a) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$a[$i], $a[$j]] = [$a[$j], $a[$i]];
    }
    return $a;
}

function novoDuelo(array $deckJogador, array $extraJogador, array $deckIA, array $extraIA,
                   int $dif, array $oponente, int $primeiro = 0): array
{
    $e = [
        'v' => 1, 'dif' => $dif, 'oponente' => $oponente,
        'turno' => 1, 'quem' => $primeiro, 'primeiro' => $primeiro, 'fase' => 'compra',
        'j' => [ladoVazio(embaralhar($deckJogador), $extraJogador), ladoVazio(embaralhar($deckIA), $extraIA)],
        'log' => [], 'fim' => null, 'ataque_pendente' => null,
        'criado' => time(),
    ];
    for ($k = 0; $k < 2; $k++) {
        for ($i = 0; $i < 5; $i++) { comprarCarta($e, $k, false); }
    }
    diz($e, 'Duelo começou. Cada duelista com ' . num(PV_INICIAL) . ' Pontos de Vida e 5 cartas na mão.');
    diz($e, ($primeiro === 0 ? 'Você' : $oponente['nome']) . ' começa — e quem começa não compra nem ataca no primeiro turno.');
    // o primeiro turno já entra pulando a compra (pág. 19)
    $e['fase'] = 'apoio';
    return $e;
}

function diz(array &$e, string $texto, string $tipo = 'info'): void
{
    $e['log'][] = ['t' => $texto, 'k' => $tipo, 'turno' => $e['turno']];
    if (count($e['log']) > 250) { $e['log'] = array_slice($e['log'], -250); }
}

/** Compra 1. Deck vazio na hora de comprar = derrota (pág. 21). */
function comprarCarta(array &$e, int $lado, bool $registra = true): bool
{
    if (!$e['j'][$lado]['deck']) {
        $e['fim'] = ['vencedor' => 1 - $lado, 'motivo' => 'deck'];
        diz($e, ($lado === 0 ? 'Você' : 'O oponente') . ' precisou comprar e não tinha mais carta no Deck. Duelo encerrado.', 'fim');
        return false;
    }
    $e['j'][$lado]['mao'][] = array_shift($e['j'][$lado]['deck']);
    if ($registra) diz($e, ($lado === 0 ? 'Você comprou' : 'O oponente comprou') . ' 1 carta.');
    return true;
}

function zonaLivreMonstro(array $e, int $lado): ?int
{
    foreach ($e['j'][$lado]['mon'] as $i => $z) { if ($z === null) return $i; }
    return null;
}
function zonaLivreMagia(array $e, int $lado): ?int
{
    foreach ($e['j'][$lado]['mag'] as $i => $z) { if ($z === null) return $i; }
    return null;
}
function monstrosNoCampo(array $e, int $lado): array
{
    $r = [];
    foreach ($e['j'][$lado]['mon'] as $i => $z) { if ($z !== null) $r[$i] = $z; }
    return $r;
}

/** ATK/DEF já com Equipamento e Campo somados. */
function forca(array $e, int $lado, int $zona): array
{
    $z = $e['j'][$lado]['mon'][$zona] ?? null;
    if (!$z) return [0, 0];
    $c = carta((int)$z['num']);
    $atk = (int)$c['atk'] + (int)($z['bonus_atk'] ?? 0);
    $def = (int)$c['def'] + (int)($z['bonus_def'] ?? 0);
    if ($e['j'][$lado]['campo']) { $atk += 200; $def += 200; }
    return [max(0, $atk), max(0, $def)];
}
function somaAtk(array $e, int $lado): int
{
    $s = 0;
    foreach (monstrosNoCampo($e, $lado) as $i => $z) { if ($z['pos'] === 'ataque') { [$a] = forca($e, $lado, $i); $s += $a; } }
    return $s;
}

function tirarDaMao(array &$e, int $lado, int $num): bool
{
    $i = array_search($num, $e['j'][$lado]['mao'], true);
    if ($i === false) return false;
    array_splice($e['j'][$lado]['mao'], (int)$i, 1);
    return true;
}
function aoCemiterio(array &$e, int $lado, int $num): void { $e['j'][$lado]['cemiterio'][] = $num; }

/** Manda o monstro da zona para o Cemitério, junto com o que estava equipado. */
function destruirMonstro(array &$e, int $lado, int $zona): void
{
    $z = $e['j'][$lado]['mon'][$zona] ?? null;
    if (!$z) return;
    aoCemiterio($e, $lado, (int)$z['num']);
    foreach (($z['equip'] ?? []) as $eq) { aoCemiterio($e, $lado, (int)$eq); }
    $e['j'][$lado]['mon'][$zona] = null;
}
function tirarPv(array &$e, int $lado, int $quanto): void
{
    if ($quanto <= 0) return;
    $e['j'][$lado]['pv'] = max(0, (int)$e['j'][$lado]['pv'] - $quanto);
    $e['j'][$lado]['dano_sofrido'] += $quanto;
    if ($e['j'][$lado]['pv'] <= 0 && !$e['fim']) {
        $e['fim'] = ['vencedor' => 1 - $lado, 'motivo' => 'pv'];
    }
}

// ===================================================================== AÇÕES

/** Invocação-Normal, Baixar e Invocação por Tributo (pág. 13). */
function acaoInvocar(array &$e, int $lado, int $num, string $modo, array $tributos = []): array
{
    if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
    if (!in_array($e['fase'], ['principal1','principal2'], true)) return ['erro' => 'Só dá para invocar numa Fase Principal.'];
    if ($e['j'][$lado]['invocou']) return ['erro' => 'Você já fez a Invocação-Normal (ou baixou um monstro) neste turno. É uma coisa OU a outra, uma vez por turno.'];

    $c = carta($num);
    if (!$c || $c['tipo'] !== 'Monster') return ['erro' => 'Isso não é uma carta de monstro.'];
    if ((int)$c['fusao'] === 1) return ['erro' => 'Monstro de Fusão só entra por Invocação-Fusão, do Deck Adicional.'];
    if ((int)$c['ritual_monstro'] === 1) return ['erro' => 'Monstro de Ritual só entra por uma Mágica de Ritual (manual, pág. 12).'];
    if (!in_array($num, $e['j'][$lado]['mao'], true)) return ['erro' => 'Essa carta não está na sua mão.'];

    $precisa = tributosDe($c);
    if (count($tributos) !== $precisa) {
        return ['erro' => $precisa === 0
            ? 'Este monstro não pede Tributo.'
            : 'Nível ' . $c['nivel'] . ' pede ' . $precisa . ' Tributo(s). Escolha ' . $precisa . ' monstro(s) seu(s).'];
    }
    foreach ($tributos as $t) { if (($e['j'][$lado]['mon'][(int)$t] ?? null) === null) return ['erro' => 'Tributo inválido.']; }

    // o Tributo sai ANTES de o novo monstro entrar, então a zona libera
    foreach ($tributos as $t) {
        $z = $e['j'][$lado]['mon'][(int)$t];
        aoCemiterio($e, $lado, (int)$z['num']);
        foreach (($z['equip'] ?? []) as $eq) aoCemiterio($e, $lado, (int)$eq);
        $e['j'][$lado]['mon'][(int)$t] = null;
    }
    $zona = zonaLivreMonstro($e, $lado);
    if ($zona === null) return ['erro' => 'Suas 5 Zonas de Monstro estão cheias.'];

    tirarDaMao($e, $lado, $num);
    $e['j'][$lado]['mon'][$zona] = [
        'num' => $num, 'pos' => $modo === 'baixar' ? 'virada' : 'ataque',
        'atacou' => false, 'mudou' => false, 'turno_entrou' => $e['turno'],
        'equip' => [], 'bonus_atk' => 0, 'bonus_def' => 0,
    ];
    $e['j'][$lado]['invocou'] = true;

    $quem = $lado === 0 ? 'Você' : 'O oponente';
    if ($modo === 'baixar') {
        diz($e, "$quem baixou um monstro com a face para baixo em Posição de Defesa.", 'jogada');
    } else {
        $extra = $precisa ? ' oferecendo ' . $precisa . ' Tributo(s)' : '';
        diz($e, "$quem invocou {$c['nome']}$extra (ATK {$c['atk']} / DEF {$c['def']}).", 'jogada');
    }
    return ['ok' => true];
}

/** Invocação por Virar e mudança de posição (pág. 14 e 21). */
function acaoPosicao(array &$e, int $lado, int $zona, string $para): array
{
    if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
    if (!in_array($e['fase'], ['principal1','principal2'], true)) return ['erro' => 'Só numa Fase Principal.'];
    $z = $e['j'][$lado]['mon'][$zona] ?? null;
    if (!$z) return ['erro' => 'Não há monstro nessa zona.'];
    if ((int)$z['turno_entrou'] === (int)$e['turno']) return ['erro' => 'Este monstro entrou no campo neste turno. Só muda de posição a partir do próximo.'];
    if (!empty($z['mudou'])) return ['erro' => 'Cada monstro muda de posição uma vez por turno.'];
    if (!empty($z['atacou'])) return ['erro' => 'Monstro que já atacou não muda de posição neste turno.'];

    $c = carta((int)$z['num']);
    if ($z['pos'] === 'virada' && $para === 'ataque') {
        $e['j'][$lado]['mon'][$zona]['pos'] = 'ataque';
        $e['j'][$lado]['mon'][$zona]['mudou'] = true;
        diz($e, ($lado === 0 ? 'Você' : 'O oponente') . " fez uma Invocação por Virar: {$c['nome']} apareceu em Posição de Ataque.", 'jogada');
        return ['ok' => true, 'virou' => true];
    }
    if ($para === $z['pos']) return ['erro' => 'Ele já está nessa posição.'];
    $e['j'][$lado]['mon'][$zona]['pos'] = $para === 'defesa' ? 'defesa' : 'ataque';
    $e['j'][$lado]['mon'][$zona]['mudou'] = true;
    diz($e, ($lado === 0 ? 'Você' : 'O oponente') . " mudou {$c['nome']} para Posição de " . ($para === 'defesa' ? 'Defesa' : 'Ataque') . '.', 'jogada');
    return ['ok' => true];
}

/** Baixar Mágica ou Armadilha (pág. 21). */
function acaoBaixarMagia(array &$e, int $lado, int $num): array
{
    if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
    if (!in_array($e['fase'], ['principal1','principal2'], true)) return ['erro' => 'Só numa Fase Principal.'];
    $c = carta($num);
    if (!$c || $c['tipo'] === 'Monster') return ['erro' => 'Só Mágica ou Armadilha se baixa aqui.'];
    if (!in_array($num, $e['j'][$lado]['mao'], true)) return ['erro' => 'Essa carta não está na sua mão.'];
    $zona = zonaLivreMagia($e, $lado);
    if ($zona === null) return ['erro' => 'Suas 5 Zonas de Magia e Armadilha estão cheias — nada mais pode ser ativado nem baixado ali.'];

    tirarDaMao($e, $lado, $num);
    $e['j'][$lado]['mag'][$zona] = ['num' => $num, 'virada' => true, 'turno_baixada' => $e['turno'], 'continua' => false];
    diz($e, ($lado === 0 ? 'Você' : 'O oponente') . ' baixou uma carta na Zona de Magias e Armadilhas.', 'jogada');
    return ['ok' => true];
}

/**
 * Ativa Mágica (da mão ou baixada) ou Armadilha (só baixada, e só a
 * partir do turno seguinte — pág. 17).
 */
function acaoAtivar(array &$e, int $lado, int $num, ?int $zonaMagia = null, array $alvo = []): array
{
    $c = carta($num);
    if (!$c) return ['erro' => 'Carta não encontrada.'];
    $ehArmadilha = $c['tipo'] === 'Trap';

    if ($zonaMagia !== null) {
        $z = $e['j'][$lado]['mag'][$zonaMagia] ?? null;
        if (!$z || (int)$z['num'] !== $num) return ['erro' => 'Carta não está nessa zona.'];
        if ($ehArmadilha && (int)$z['turno_baixada'] >= (int)$e['turno']) {
            return ['erro' => 'Armadilha não pode ser ativada no mesmo turno em que foi baixada (manual, pág. 17).'];
        }
        if (!$ehArmadilha && $e['quem'] !== $lado) {
            return ['erro' => 'Carta Mágica baixada nunca é ativada durante o turno do oponente.'];
        }
    } else {
        if ($ehArmadilha) return ['erro' => 'Armadilha precisa ser baixada antes; nunca ativa direto da mão.'];
        if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
        if (!in_array($e['fase'], ['principal1','principal2'], true)) return ['erro' => 'Mágica normalmente só ativa na sua Fase Principal.'];
        if (!in_array($num, $e['j'][$lado]['mao'], true)) return ['erro' => 'Essa carta não está na sua mão.'];
        if (zonaLivreMagia($e, $lado) === null && $c['tipo'] !== 'Field') {
            return ['erro' => 'Suas 5 Zonas de Magia e Armadilha estão cheias.'];
        }
    }

    $r = aplicarEfeito($e, $lado, $c, $alvo);
    if (!empty($r['erro'])) return $r;

    // tira do lugar de origem
    if ($zonaMagia !== null) { $e['j'][$lado]['mag'][$zonaMagia] = null; }
    else { tirarDaMao($e, $lado, $num); }

    if ($c['tipo'] === 'Field') {
        if ($e['j'][$lado]['campo']) aoCemiterio($e, $lado, (int)$e['j'][$lado]['campo']);
        $e['j'][$lado]['campo'] = $num;                       // fica na Zona de Campo
    } elseif ($c['tipo'] === 'Equip' && !empty($r['equipou'])) {
        // a carta de Equipamento fica no campo, presa ao monstro
        $zl = zonaLivreMagia($e, $lado);
        if ($zl !== null) $e['j'][$lado]['mag'][$zl] = ['num' => $num, 'virada' => false, 'turno_baixada' => $e['turno'], 'continua' => true];
    } else {
        aoCemiterio($e, $lado, $num);
    }
    return ['ok' => true, 'texto' => $r['texto'] ?? ''];
}

/**
 * OS EFEITOS.
 *
 * As 722 vêm com nome, tipo, Nível, ATK e DEF — sem o texto de regras.
 * Então o site trata assim, e diz isso ao jogador em vez de fingir:
 *
 *   · as Mágicas e Armadilhas ICÔNICAS têm efeito escrito à mão aqui;
 *   · Equipamento sem efeito escrito dá +500 de ATK e +300 de DEF;
 *   · Mágica de Campo dá +200 de ATK e DEF aos seus monstros;
 *   · o que não está no registro é declarado como BLEFE — ocupa zona,
 *     assusta o oponente e não muda o jogo. É honesto e é jogável.
 *
 * Quando o importador do YGOPRODeck trouxer o texto oficial, cada uma
 * ganha a sua linha aqui — o registro é uma tabela, não um labirinto.
 */
function registroDeEfeitos(): array
{
    return [
        'Dark Hole'            => 'destroi_todos',
        'Raigeki'              => 'destroi_oponente',
        'Fissure'              => 'destroi_menor_atk',
        'Tremendous Fire'      => 'queima_1000',
        'Sparks'               => 'queima_200',
        'Hinotama'             => 'queima_500',
        'Final Flame'          => 'queima_600',
        'Ookazi'               => 'queima_800',
        'Restructer Revolution'=> 'queima_por_mao',
        'Dian Keto the Cure Master' => 'cura_1000',
        'Soul of the Pure'     => 'cura_800',
        'Red Medicine'         => 'cura_500',
        'Monster Reborn'       => 'reviver',
        'Pot of Greed'         => 'compra_2',
        'The Inexperienced Spy' => 'ver_mao',
        'De-Spell'             => 'destroi_magia',
        'Remove Trap'          => 'destroi_magia',
        'Stop Defense'         => 'forca_ataque',
        'Swords of Revealing Light' => 'espadas',
        'Change of Heart'      => 'roubo',
        'Dark Energy'          => 'equip_800_trevas',
        'Sword of Dark Destruction' => 'equip_400',
        'Book of Secret Arts'  => 'equip_300',
        'Legendary Sword'      => 'equip_300',
        'Beast Fangs'          => 'equip_300',
        'Silver Bow and Arrow' => 'equip_300',
        'Violet Crystal'       => 'equip_300',
        'Raise Body Heat'      => 'equip_300',
        'Machine Conversion Factory' => 'equip_300',
        'Trap Hole'            => 'buraco',
        'Reinforcements'       => 'reforco',
        'Waboku'               => 'waboku',
        'Castle Walls'         => 'muralha',
        'Just Desserts'        => 'just_desserts',
        'Widespread Ruin'      => 'destroi_maior_atk',
        'Invisible Wire'       => 'destroi_atacante',
        'Acid Trap Hole'       => 'buraco',
    ];
}

function aplicarEfeito(array &$e, int $lado, array $c, array $alvo): array
{
    $o = 1 - $lado;
    $quem = $lado === 0 ? 'Você' : 'O oponente';
    $ef = registroDeEfeitos()[$c['nome']] ?? null;

    if ($ef === null) {
        if ($c['tipo'] === 'Equip') $ef = 'equip_500';
        elseif ($c['tipo'] === 'Field') $ef = 'campo';
        else $ef = 'blefe';
    }

    $t = fn(string $s) => diz($e, "$quem ativou {$c['nome']}: $s", 'efeito');

    switch (true) {
        case $ef === 'destroi_todos':
            $n = 0;
            for ($l = 0; $l < 2; $l++) { foreach (monstrosNoCampo($e, $l) as $i => $z) { destruirMonstro($e, $l, $i); $n++; } }
            $t("$n monstro(s) destruído(s) nos dois lados."); break;

        case $ef === 'destroi_oponente':
            $n = 0; foreach (monstrosNoCampo($e, $o) as $i => $z) { destruirMonstro($e, $o, $i); $n++; }
            $t("$n monstro(s) do oponente destruído(s)."); break;

        case $ef === 'destroi_menor_atk':
        case $ef === 'destroi_maior_atk':
            $alvoZ = null; $melhor = $ef === 'destroi_menor_atk' ? PHP_INT_MAX : -1;
            foreach (monstrosNoCampo($e, $o) as $i => $z) {
                [$a] = forca($e, $o, $i);
                if (($ef === 'destroi_menor_atk' && $a < $melhor) || ($ef === 'destroi_maior_atk' && $a > $melhor)) { $melhor = $a; $alvoZ = $i; }
            }
            if ($alvoZ === null) return ['erro' => 'O oponente não controla monstro nenhum.'];
            $nome = carta((int)$e['j'][$o]['mon'][$alvoZ]['num'])['nome'];
            destruirMonstro($e, $o, $alvoZ); $t("$nome foi destruído."); break;

        case str_starts_with($ef, 'queima_') && $ef !== 'queima_por_mao':
            $q = (int)substr($ef, 7); tirarPv($e, $o, $q); $t("$q de dano direto no oponente."); break;

        case $ef === 'queima_por_mao':
            $q = count($e['j'][$o]['mao']) * 200; tirarPv($e, $o, $q); $t("$q de dano (200 por carta na mão dele)."); break;

        case str_starts_with($ef, 'cura_'):
            $q = (int)substr($ef, 5); $e['j'][$lado]['pv'] += $q; $t("+$q Pontos de Vida."); break;

        case $ef === 'compra_2':
            comprarCarta($e, $lado, false); comprarCarta($e, $lado, false); $t('comprou 2 cartas.'); break;

        case $ef === 'reviver':
            $cem = $e['j'][$lado]['cemiterio'];
            $melhorI = null; $melhorA = -1;
            foreach ($cem as $i => $n) { $x = carta((int)$n); if ($x && $x['tipo'] === 'Monster' && (int)$x['atk'] > $melhorA) { $melhorA = (int)$x['atk']; $melhorI = $i; } }
            if ($melhorI === null) return ['erro' => 'Não há monstro no seu Cemitério.'];
            $zona = zonaLivreMonstro($e, $lado);
            if ($zona === null) return ['erro' => 'Suas Zonas de Monstro estão cheias.'];
            $n = (int)$cem[$melhorI]; array_splice($e['j'][$lado]['cemiterio'], $melhorI, 1);
            $e['j'][$lado]['mon'][$zona] = ['num'=>$n,'pos'=>'ataque','atacou'=>false,'mudou'=>false,
                                            'turno_entrou'=>$e['turno'],'equip'=>[],'bonus_atk'=>0,'bonus_def'=>0];
            $t(carta($n)['nome'] . ' voltou do Cemitério por Invocação-Especial.'); break;

        case $ef === 'espadas':
            $e['j'][$o]['pula_batalha'] = 3;   // três turnos dele sem atacar
            $t('o oponente não conduz Fase de Batalha nos próximos turnos.'); break;

        case $ef === 'destroi_magia':
            $achou = false;
            foreach ($e['j'][$o]['mag'] as $i => $z) { if ($z) { aoCemiterio($e, $o, (int)$z['num']); $e['j'][$o]['mag'][$i] = null; $achou = true; break; } }
            if (!$achou && $e['j'][$o]['campo']) { aoCemiterio($e, $o, (int)$e['j'][$o]['campo']); $e['j'][$o]['campo'] = null; $achou = true; }
            if (!$achou) return ['erro' => 'O oponente não tem Mágica nem Armadilha no campo.'];
            $t('uma carta da Zona de Magias e Armadilhas dele foi destruída.'); break;

        case $ef === 'forca_ataque':
            foreach (monstrosNoCampo($e, $o) as $i => $z) { $e['j'][$o]['mon'][$i]['pos'] = 'ataque'; }
            $t('todos os monstros do oponente foram para Posição de Ataque.'); break;

        case $ef === 'ver_mao':
            $t('a mão do oponente foi revelada por um instante.');
            return ['ok' => true, 'revelar' => $e['j'][$o]['mao'], 'texto' => 'Mão do oponente revelada.'];

        case $ef === 'roubo':
            $alvoZ = null; $melhor = -1;
            foreach (monstrosNoCampo($e, $o) as $i => $z) { [$a] = forca($e, $o, $i); if ($a > $melhor) { $melhor = $a; $alvoZ = $i; } }
            if ($alvoZ === null) return ['erro' => 'O oponente não controla monstro nenhum.'];
            $zona = zonaLivreMonstro($e, $lado);
            if ($zona === null) return ['erro' => 'Suas Zonas de Monstro estão cheias.'];
            $e['j'][$lado]['mon'][$zona] = $e['j'][$o]['mon'][$alvoZ];
            $e['j'][$lado]['mon'][$zona]['turno_entrou'] = $e['turno'];
            $e['j'][$o]['mon'][$alvoZ] = null;
            $t('tomou o controle do monstro mais forte do oponente até o fim do duelo.'); break;

        case str_starts_with($ef, 'equip_'):
            $ganho = (int)filter_var($ef, FILTER_SANITIZE_NUMBER_INT) ?: 500;
            $z = $alvo['zona'] ?? null;
            if ($z === null || ($e['j'][$lado]['mon'][(int)$z] ?? null) === null) {
                // sem alvo escolhido, equipa no seu monstro mais forte de ataque
                $melhor = -1;
                foreach (monstrosNoCampo($e, $lado) as $i => $zz) { [$a] = forca($e, $lado, $i); if ($a > $melhor) { $melhor = $a; $z = $i; } }
            }
            if ($z === null) return ['erro' => 'Equipamento precisa de um monstro seu com a face para cima no campo.'];
            $z = (int)$z;
            if (($e['j'][$lado]['mon'][$z]['pos'] ?? '') === 'virada') return ['erro' => 'Não dá para equipar num monstro com a face para baixo.'];
            $e['j'][$lado]['mon'][$z]['bonus_atk'] += $ganho;
            $e['j'][$lado]['mon'][$z]['bonus_def'] += (int)round($ganho * 0.6);
            $e['j'][$lado]['mon'][$z]['equip'][] = (int)$c['num'];
            $t('equipou ' . carta((int)$e['j'][$lado]['mon'][$z]['num'])['nome'] . " com +$ganho de ATK.");
            return ['ok' => true, 'equipou' => true];

        case $ef === 'campo':
            $t('a Zona de Campo foi ocupada: +200 de ATK e DEF nos seus monstros.'); break;

        // ------- armadilhas de reação, resolvidas dentro da Fase de Batalha
        case $ef === 'buraco':
        case $ef === 'destroi_atacante':
            $z = $e['ataque_pendente']['zona'] ?? null;
            if ($z === null) return ['erro' => 'Esta Armadilha responde a um ataque declarado.'];
            $la = $e['ataque_pendente']['lado'];
            $nome = carta((int)$e['j'][$la]['mon'][$z]['num'])['nome'];
            destruirMonstro($e, $la, (int)$z);
            $e['ataque_pendente'] = null;
            $t("$nome foi destruído antes da Etapa de Dano."); break;

        case $ef === 'waboku':
        case $ef === 'muralha':
            $e['ataque_pendente'] = null;
            $t('o ataque foi anulado, sem dano de batalha.'); break;

        case $ef === 'reforco':
            $z = $e['ataque_pendente']['alvo'] ?? null;
            if ($z !== null && ($e['j'][$lado]['mon'][(int)$z] ?? null)) {
                $e['j'][$lado]['mon'][(int)$z]['bonus_atk'] += 500;
                $e['j'][$lado]['mon'][(int)$z]['bonus_def'] += 500;
            }
            $t('+500 no monstro alvo até o fim da batalha.'); break;

        case $ef === 'just_desserts':
            $q = count(monstrosNoCampo($e, $o)) * 500; tirarPv($e, $o, $q);
            $t("$q de dano (500 por monstro no campo dele)."); break;

        default:
            diz($e, "$quem ativou {$c['nome']}. Esta carta ainda não tem efeito de regra neste conjunto — vale como blefe.", 'efeito');
    }
    return ['ok' => true];
}

/**
 * DECLARAR ATAQUE (pág. 22 a 25).
 * $alvoZona = null significa ataque direto, e só pode se o outro lado
 * não controlar monstro nenhum.
 */
function acaoAtacar(array &$e, int $lado, int $zona, ?int $alvoZona): array
{
    if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
    if ($e['fase'] !== 'batalha') return ['erro' => 'Ataque só na Fase de Batalha.'];
    if ($e['turno'] === 1 && $e['primeiro'] === $lado) return ['erro' => 'Quem começa não conduz Fase de Batalha no primeiro turno (manual, pág. 19).'];

    $z = $e['j'][$lado]['mon'][$zona] ?? null;
    if (!$z) return ['erro' => 'Não há monstro nessa zona.'];
    if ($z['pos'] !== 'ataque') return ['erro' => 'Só monstro em Posição de Ataque declara ataque.'];
    if (!empty($z['atacou'])) return ['erro' => 'Cada monstro ataca uma vez por turno.'];

    $o = 1 - $lado;
    $temMonstro = count(monstrosNoCampo($e, $o)) > 0;
    if ($alvoZona === null && $temMonstro) return ['erro' => 'O oponente controla monstro: o ataque não pode ser direto.'];
    if ($alvoZona !== null && ($e['j'][$o]['mon'][$alvoZona] ?? null) === null) return ['erro' => 'Não há monstro nessa zona do oponente.'];

    $e['j'][$lado]['mon'][$zona]['atacou'] = true;
    $ca = carta((int)$z['num']);
    [$atk] = forca($e, $lado, $zona);
    $quem = $lado === 0 ? 'Você' : 'O oponente';

    if ($alvoZona === null) {
        tirarPv($e, $o, $atk);
        diz($e, "$quem atacou diretamente com {$ca['nome']}: $atk de dano.", 'batalha');
        return ['ok' => true, 'direto' => true, 'dano' => $atk];
    }

    $zd = $e['j'][$o]['mon'][$alvoZona];
    $cd = carta((int)$zd['num']);
    [$datk, $ddef] = forca($e, $o, $alvoZona);
    $viradaAntes = $zd['pos'] === 'virada';
    if ($viradaAntes) {
        $e['j'][$o]['mon'][$alvoZona]['pos'] = 'defesa';   // vira na Etapa de Dano (pág. 25)
        diz($e, "O monstro baixado era {$cd['nome']} (DEF $ddef).", 'batalha');
    }
    $emDefesa = $viradaAntes || $zd['pos'] === 'defesa';

    if ($emDefesa) {
        if ($atk === 0) { diz($e, "Monstro com 0 de ATK não destrói nada em batalha.", 'batalha'); }
        elseif ($atk > $ddef) {
            destruirMonstro($e, $o, $alvoZona);
            diz($e, "$quem atacou {$cd['nome']} (DEF $ddef) com {$ca['nome']} (ATK $atk): destruído, sem dano para ninguém.", 'batalha');
        } elseif ($atk === $ddef) {
            diz($e, "ATK $atk contra DEF $ddef: empate, nenhum monstro destruído e ninguém sofre dano.", 'batalha');
        } else {
            $dif = $ddef - $atk; tirarPv($e, $lado, $dif);
            diz($e, "ATK $atk contra DEF $ddef: nenhum monstro destruído, e $dif sai dos PV de " . ($lado === 0 ? 'quem atacou — você' : 'quem atacou') . '.', 'batalha');
        }
    } else {
        if ($atk > $datk) {
            $dif = $atk - $datk; destruirMonstro($e, $o, $alvoZona); tirarPv($e, $o, $dif);
            diz($e, "$quem atacou {$cd['nome']} (ATK $datk) com {$ca['nome']} (ATK $atk): destruído e $dif de dano.", 'batalha');
        } elseif ($atk === $datk) {
            if ($atk === 0) { diz($e, 'Dois monstros com 0 de ATK: nenhum é destruído.', 'batalha'); }
            else {
                destruirMonstro($e, $lado, $zona); destruirMonstro($e, $o, $alvoZona);
                diz($e, "ATK $atk contra ATK $datk: os dois monstros foram destruídos, sem dano.", 'batalha');
            }
        } else {
            $dif = $datk - $atk; destruirMonstro($e, $lado, $zona); tirarPv($e, $lado, $dif);
            diz($e, "ATK $atk contra ATK $datk: {$ca['nome']} foi destruído e $dif sai dos PV de quem atacou.", 'batalha');
        }
    }
    return ['ok' => true];
}

/** Avança uma fase, cumprindo a ordem e as travas da pág. 20. */
function avancarFase(array &$e, int $lado): array
{
    if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
    $f = $e['fase'];

    if ($f === 'compra')      { $e['fase'] = 'apoio'; return ['ok' => true]; }
    if ($f === 'apoio')       { $e['fase'] = 'principal1'; return ['ok' => true]; }
    if ($f === 'principal1') {
        if ($e['turno'] === 1 && $e['primeiro'] === $lado) { diz($e, 'Sem Fase de Batalha no primeiro turno de quem começa — vai direto para a Fase Final.'); return encerrarTurno($e, $lado); }
        if (!empty($e['j'][$lado]['pula_batalha'])) { diz($e, 'A Fase de Batalha foi pulada por um efeito.'); return encerrarTurno($e, $lado); }
        $e['fase'] = 'batalha'; diz($e, 'Fase de Batalha.'); return ['ok' => true];
    }
    if ($f === 'batalha')     { $e['fase'] = 'principal2'; $e['ataque_pendente'] = null; return ['ok' => true]; }
    if ($f === 'principal2')  { return encerrarTurno($e, $lado); }
    return encerrarTurno($e, $lado);
}

/** Pular a Fase de Batalha manda o turno direto para a Final (pág. 20). */
function pularBatalha(array &$e, int $lado): array
{
    if ($e['quem'] !== $lado) return ['erro' => 'Não é o seu turno.'];
    if ($e['fase'] !== 'principal1') return ['erro' => 'Só dá para pular a batalha na Fase Principal 1.'];
    diz($e, ($lado === 0 ? 'Você' : 'O oponente') . ' pulou a Fase de Batalha — e por isso não há Fase Principal 2.');
    return encerrarTurno($e, $lado);
}

/** Fase Final: descarte de mão acima de 6 e passagem de turno. */
function encerrarTurno(array &$e, int $lado, array $descartar = []): array
{
    $e['fase'] = 'final';
    $mao = &$e['j'][$lado]['mao'];
    if (count($mao) > 6) {
        if ($lado === 0 && count($descartar) < count($mao) - 6) {
            return ['pede_descarte' => count($mao) - 6];
        }
        if ($lado === 0) {
            foreach ($descartar as $n) { if (tirarDaMao($e, $lado, (int)$n)) aoCemiterio($e, $lado, (int)$n); }
        } else {
            while (count($mao) > 6) { $n = array_pop($mao); aoCemiterio($e, $lado, (int)$n); }
        }
        diz($e, ($lado === 0 ? 'Você' : 'O oponente') . ' descartou até ficar com 6 cartas na mão.');
    }

    // limpa o que é "por turno"
    foreach ([0, 1] as $l) {
        foreach ($e['j'][$l]['mon'] as $i => $z) {
            if ($z) { $e['j'][$l]['mon'][$i]['atacou'] = false; $e['j'][$l]['mon'][$i]['mudou'] = false; }
        }
        $e['j'][$l]['invocou'] = false;
    }
    if (!empty($e['j'][$lado]['pula_batalha'])) { $e['j'][$lado]['pula_batalha']--; }

    $e['quem'] = 1 - $lado;
    $e['turno']++;
    $e['ataque_pendente'] = null;
    $e['fase'] = 'compra';
    diz($e, 'Turno ' . $e['turno'] . ' — vez ' . ($e['quem'] === 0 ? 'sua' : 'do oponente') . '.', 'turno');

    // a Fase de Compra resolve sozinha
    if (!comprarCarta($e, $e['quem'])) return ['ok' => true];
    $e['fase'] = 'apoio';
    return ['ok' => true];
}

/**
 * O QUE O NAVEGADOR PODE VER.
 * A mão do outro lado NUNCA é enviada — vai só a contagem. E monstro
 * com a face para baixo vai sem o número da carta.
 */
function estadoVisivel(array $e, int $lado): array
{
    $eu = $e['j'][$lado]; $ele = $e['j'][1 - $lado];
    $mapMon = function (array $lado_, bool $meu) {
        $r = [];
        foreach ($lado_['mon'] as $i => $z) {
            if (!$z) { $r[$i] = null; continue; }
            $escondido = $z['pos'] === 'virada' && !$meu;
            $c = $escondido ? null : carta((int)$z['num']);
            $r[$i] = [
                'pos' => $z['pos'], 'atacou' => (bool)$z['atacou'], 'oculto' => $escondido,
                'num' => $escondido ? null : (int)$z['num'],
                'nome' => $c['nome'] ?? null, 'nome_pt' => $c['nome_pt'] ?? null,
                'atk' => $c ? (int)$c['atk'] + (int)$z['bonus_atk'] : null,
                'def' => $c ? (int)$c['def'] + (int)$z['bonus_def'] : null,
                'nivel' => $c['nivel'] ?? null, 'sub' => $c['sub'] ?? null,
                'passcode' => $c['passcode'] ?? null, 'equip' => count($z['equip'] ?? []),
            ];
        }
        return $r;
    };
    $mapMag = function (array $lado_, bool $meu) {
        $r = [];
        foreach ($lado_['mag'] as $i => $z) {
            if (!$z) { $r[$i] = null; continue; }
            $escondido = !empty($z['virada']) && !$meu;
            $c = $escondido ? null : carta((int)$z['num']);
            $r[$i] = ['oculto' => $escondido, 'virada' => !empty($z['virada']),
                      'num' => $escondido ? null : (int)$z['num'], 'nome' => $c['nome'] ?? null,
                      'tipo' => $c['tipo'] ?? null, 'passcode' => $c['passcode'] ?? null];
        }
        return $r;
    };
    $carta1 = fn(?int $n) => $n ? ['num'=>$n] + carta($n) : null;

    return [
        'turno' => $e['turno'], 'fase' => $e['fase'], 'minha_vez' => $e['quem'] === $lado,
        'primeiro' => $e['primeiro'] === $lado, 'fim' => $e['fim'],
        'oponente' => $e['oponente'],
        'eu' => [
            'pv' => (int)$eu['pv'], 'deck' => count($eu['deck']), 'mao' => array_map($carta1, $eu['mao']),
            'cemiterio' => count($eu['cemiterio']), 'extra' => count($eu['extra']),
            'mon' => $mapMon($eu, true), 'mag' => $mapMag($eu, true),
            'campo' => $carta1($eu['campo'] ? (int)$eu['campo'] : null),
            'invocou' => (bool)$eu['invocou'], 'reliquia_usada' => (bool)$eu['reliquia_usada'],
        ],
        'ele' => [
            'pv' => (int)$ele['pv'], 'deck' => count($ele['deck']), 'mao' => count($ele['mao']),
            'cemiterio' => count($ele['cemiterio']), 'extra' => count($ele['extra']),
            'mon' => $mapMon($ele, false), 'mag' => $mapMag($ele, false),
            'campo' => $carta1($ele['campo'] ? (int)$ele['campo'] : null),
        ],
        'log' => array_slice($e['log'], -40),
        'sem_dano' => (int)$eu['dano_sofrido'] === 0,
    ];
}
