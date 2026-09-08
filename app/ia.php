<?php
/**
 * A IA DO OPONENTE.
 *
 * Ela não trapaceia: joga com o mesmo estado, as mesmas regras e a mesma
 * função de ação que o jogador. A diferença entre um Tuti do Oásis e o
 * Faraó Sem Rosto é o CUIDADO com que ela pensa, e isso é o parâmetro
 * `cabeca` (1 a 5), que sai da dificuldade:
 *
 *   1  invoca o primeiro monstro que couber e ataca sempre
 *   2  escolhe o monstro de maior ATK e não ataca em desvantagem óbvia
 *   3  usa Tributo, defende quando está atrás e baixa armadilha
 *   4  guarda Mágica para a hora certa e escolhe o alvo mais valioso
 *   5  calcula a troca antes de atacar e joga para o dano exato
 */
declare(strict_types=1);

function cabecaDaDificuldade(int $dif, bool $ehChefe = false): int
{
    return $ehChefe ? min(5, $dif + 1) : $dif;
}

/** Toca o turno inteiro da IA. Devolve o número de jogadas feitas. */
function jogarIa(array &$e, int $cabeca): int
{
    $lado = 1;
    if ($e['quem'] !== $lado || $e['fim']) return 0;
    $jogadas = 0;
    $limite = 40;                            // trava de segurança contra laço infinito

    while ($e['quem'] === $lado && !$e['fim'] && $limite-- > 0) {
        switch ($e['fase']) {
            case 'compra':
            case 'apoio':
                avancarFase($e, $lado); break;

            case 'principal1':
            case 'principal2':
                if (!iaFasePrincipal($e, $cabeca)) { avancarFase($e, $lado); }
                else { $jogadas++; }
                break;

            case 'batalha':
                if (!iaBatalha($e, $cabeca)) { avancarFase($e, $lado); }
                else { $jogadas++; }
                break;

            default:
                avancarFase($e, $lado);
        }
    }
    return $jogadas;
}

/** Uma jogada da Fase Principal. Devolve false quando não há mais nada a fazer. */
function iaFasePrincipal(array &$e, int $cabeca): bool
{
    $lado = 1; $o = 0;
    $mao = $e['j'][$lado]['mao'];
    if (!$mao) return false;

    $meus = monstrosNoCampo($e, $lado);
    $dele = monstrosNoCampo($e, $o);

    // 1) Mágica que resolve o campo, quando a IA já pensa (cabeça 3+)
    if ($cabeca >= 3) {
        foreach ($mao as $n) {
            $c = carta((int)$n);
            if (!$c || $c['tipo'] === 'Monster' || $c['tipo'] === 'Trap') continue;
            $ef = registroDeEfeitos()[$c['nome']] ?? ($c['tipo'] === 'Equip' ? 'equip_500' : ($c['tipo'] === 'Field' ? 'campo' : 'blefe'));
            $vale = match (true) {
                $ef === 'destroi_oponente', $ef === 'destroi_maior_atk' => count($dele) > 0,
                $ef === 'destroi_todos'   => count($dele) > count($meus),
                $ef === 'destroi_menor_atk' => count($dele) > 0,
                $ef === 'reviver'         => count($e['j'][$lado]['cemiterio']) > 0 && zonaLivreMonstro($e, $lado) !== null,
                $ef === 'compra_2'        => true,
                str_starts_with($ef, 'queima_') => (int)$e['j'][$o]['pv'] <= 1200 || $cabeca >= 4,
                str_starts_with($ef, 'cura_')   => (int)$e['j'][$lado]['pv'] <= 3000,
                str_starts_with($ef, 'equip_')  => count($meus) > 0,
                $ef === 'campo'           => $e['j'][$lado]['campo'] === null && count($meus) > 0,
                $ef === 'roubo'           => count($dele) > 0 && zonaLivreMonstro($e, $lado) !== null,
                default                   => false,
            };
            if ($vale) { $r = acaoAtivar($e, $lado, (int)$n); if (!empty($r['ok'])) return true; }
        }
    }

    // 2) Invocação — uma por turno
    if (!$e['j'][$lado]['invocou'] && zonaLivreMonstro($e, $lado) !== null) {
        $cands = [];
        foreach ($mao as $n) {
            $c = carta((int)$n);
            if (!$c || $c['tipo'] !== 'Monster' || (int)$c['fusao'] === 1 || (int)$c['ritual_monstro'] === 1) continue;
            $cands[] = $c;
        }
        if ($cands) {
            usort($cands, fn($a, $b) => (int)$b['atk'] <=> (int)$a['atk']);
            $maiorDele = 0;
            foreach ($dele as $i => $z) { [$a] = forca($e, $o, $i); $maiorDele = max($maiorDele, $a); }

            foreach ($cands as $c) {
                $precisa = tributosDe($c);
                if ($precisa > 0 && ($cabeca < 3 || count($meus) < $precisa + ($cabeca >= 4 ? 0 : 1))) continue;
                $tributos = [];
                if ($precisa > 0) {
                    $ordenados = [];
                    foreach ($meus as $i => $z) { [$a] = forca($e, $lado, $i); $ordenados[$i] = $a; }
                    asort($ordenados);
                    $tributos = array_slice(array_keys($ordenados), 0, $precisa);
                }
                // cabeça 3+: se não vence o campo dele, baixa em defesa em vez de dar de bandeja
                $modo = 'invocar';
                if ($cabeca >= 3 && $maiorDele > 0 && (int)$c['atk'] <= $maiorDele && (int)$c['def'] > (int)$c['atk']) {
                    $modo = 'baixar';
                }
                $r = acaoInvocar($e, $lado, (int)$c['num'], $modo, $tributos);
                if (!empty($r['ok'])) return true;
            }
        }
    }

    // 3) Virar monstro baixado quando ele já ganha a troca (cabeça 2+)
    if ($cabeca >= 2) {
        $maiorDele = 0;
        foreach ($dele as $i => $z) { [$a] = forca($e, $o, $i); $maiorDele = max($maiorDele, $a); }
        foreach ($meus as $i => $z) {
            if ($z['pos'] !== 'virada' || (int)$z['turno_entrou'] >= (int)$e['turno'] || !empty($z['mudou'])) continue;
            [$a] = forca($e, $lado, $i);
            if ($a > $maiorDele) { $r = acaoPosicao($e, $lado, $i, 'ataque'); if (!empty($r['ok'])) return true; }
        }
    }

    // 4) Baixar Armadilha, que só serve a partir do turno seguinte (cabeça 3+)
    if ($cabeca >= 3 && zonaLivreMagia($e, $lado) !== null) {
        foreach ($mao as $n) {
            $c = carta((int)$n);
            if ($c && $c['tipo'] === 'Trap') { $r = acaoBaixarMagia($e, $lado, (int)$n); if (!empty($r['ok'])) return true; }
        }
    }
    return false;
}

/** Uma declaração de ataque. */
function iaBatalha(array &$e, int $cabeca): bool
{
    $lado = 1; $o = 0;
    if ($e['turno'] === 1 && $e['primeiro'] === $lado) return false;

    $meus = [];
    foreach (monstrosNoCampo($e, $lado) as $i => $z) {
        if ($z['pos'] === 'ataque' && empty($z['atacou'])) { [$a] = forca($e, $lado, $i); $meus[$i] = $a; }
    }
    if (!$meus) return false;
    arsort($meus);

    $dele = monstrosNoCampo($e, $o);
    foreach ($meus as $zona => $atk) {
        if (!$dele) {                                   // campo vazio: ataque direto
            $r = acaoAtacar($e, $lado, $zona, null);
            if (!empty($r['ok'])) return true;
            continue;
        }
        $melhorAlvo = null; $melhorNota = -PHP_INT_MAX;
        foreach ($dele as $i => $z) {
            [$da, $dd] = forca($e, $o, $i);
            $emDefesa = $z['pos'] !== 'ataque';
            if ($z['pos'] === 'virada') {
                // não dá para saber o que é; cabeça baixa arrisca, cabeça alta só ataca com folga
                $nota = $cabeca >= 4 ? ($atk - 1500) : 300;
            } elseif ($emDefesa) {
                $nota = $atk > $dd ? 400 : ($atk === $dd ? -50 : -($dd - $atk));
            } else {
                $nota = $atk > $da ? ($atk - $da) + 600 : ($atk === $da ? ($cabeca >= 3 ? -200 : 100) : -($da - $atk) * 2);
            }
            if ($nota > $melhorNota) { $melhorNota = $nota; $melhorAlvo = $i; }
        }
        if ($melhorAlvo === null) continue;
        if ($cabeca >= 3 && $melhorNota < 0) continue;   // não se joga fora de graça
        if ($cabeca <= 1) { $melhorAlvo = array_key_first($dele); }
        $r = acaoAtacar($e, $lado, $zona, (int)$melhorAlvo);
        if (!empty($r['ok'])) return true;
    }
    return false;
}
