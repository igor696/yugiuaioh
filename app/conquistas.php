<?php
/**
 * AS 24 CONQUISTAS = OS 24 NÍVEIS.
 *
 * Só valem no modo CAMPANHA — em LAN e ONLINE não há como provar que o
 * duelo aconteceu de verdade, e conquista comprada não é conquista.
 *
 * O ritmo é por mérito, não por relógio: as 6 primeiras vêm abertas, e
 * da 7 à 24 a próxima só aparece depois que a anterior é concluída.
 * Conquista bloqueada continua no quadro, em silhueta, com "???" —
 * do jeito que você aprovou no Magic the Minas.
 */
declare(strict_types=1);

const CONQ_ABERTAS = 6;

function conquistas(): array
{
    return [
     1 =>['nome'=>'O Primeiro Papiro','como'=>'Criar o duelista','regra'=>'sempre','premio'=>50],
     2 =>['nome'=>'Mão de Cinco','como'=>'Comprar a sua primeira mão','regra'=>'duelos:1','premio'=>60],
     3 =>['nome'=>'Ataque Direto','como'=>'Vencer o primeiro duelo','regra'=>'vitorias:1','premio'=>80],
     4 =>['nome'=>'Tributo Pago','como'=>'Fazer a primeira Invocação por Tributo','regra'=>'marca:tributo','premio'=>100],
     5 =>['nome'=>'Cadeia de Três Elos','como'=>'Ativar 3 cartas no mesmo turno','regra'=>'marca:cadeia3','premio'=>120],
     6 =>['nome'=>'O Oásis Atrás','como'=>'Vencer os 30 oponentes do MUITO FÁCIL','regra'=>'limpou:1','premio'=>250],
     7 =>['nome'=>'Ritual Cumprido','como'=>'Fazer a primeira Invocação por Ritual','regra'=>'marca:ritual','premio'=>150],
     8 =>['nome'=>'Fusão Selada','como'=>'Fazer a primeira Invocação-Fusão','regra'=>'marca:fusao','premio'=>150],
     9 =>['nome'=>'A Caravana Vencida','como'=>'Vencer os 30 oponentes do FÁCIL','regra'=>'limpou:2','premio'=>300],
     10=>['nome'=>'Cofre de Mil','como'=>'Ter 1.000 ONEUB numa carteira','regra'=>'saldo:1000','premio'=>200],
     11=>['nome'=>'Coleção de Cem','como'=>'Ter 100 cartas distintas','regra'=>'cartas:100','premio'=>250],
     12=>['nome'=>'Segundo Encaixe','como'=>'Ganhar a segunda relíquia','regra'=>'reliquias:2','premio'=>300],
     13=>['nome'=>'A Guarda Rendida','como'=>'Vencer os 30 oponentes do NORMAL','regra'=>'limpou:3','premio'=>400],
     14=>['nome'=>'Deck de Ouro','como'=>'Ter um Deck válido de 40 cartas próprias','regra'=>'marca:deck40','premio'=>250],
     15=>['nome'=>'Duelo Sem Dano','como'=>'Vencer um duelo sem perder 1 Ponto de Vida','regra'=>'marca:semdano','premio'=>350],
     16=>['nome'=>'A Corte Calada','como'=>'Vencer os 30 oponentes do DIFÍCIL','regra'=>'limpou:4','premio'=>500],
     17=>['nome'=>'Cinco Vitórias Seguidas','como'=>'Cinco vitórias sem derrota no meio','regra'=>'sequencia:5','premio'=>300],
     18=>['nome'=>'O Escriba do Silêncio','como'=>'Derrotar Ankh-Uai nas 5 dificuldades','regra'=>'chefe5:1','premio'=>600],
     19=>['nome'=>'A Sacerdotisa Cega','como'=>'Derrotar Nefer-Trindade nas 5 dificuldades','regra'=>'chefe5:2','premio'=>600],
     20=>['nome'=>'O Rio Domado','como'=>'Derrotar Sobek-Bão nas 5 dificuldades','regra'=>'chefe5:3','premio'=>600],
     21=>['nome'=>'A Forja Fria','como'=>'Derrotar Kefer-Amon nas 5 dificuldades','regra'=>'chefe5:4','premio'=>600],
     22=>['nome'=>'O Vento Parado','como'=>'Derrotar Set-Mineiro nas 5 dificuldades','regra'=>'chefe5:5','premio'=>600],
     23=>['nome'=>'As Sombras Vencidas','como'=>'Vencer os 30 do MUITO DIFÍCIL','regra'=>'limpou:5','premio'=>800],
     24=>['nome'=>'O Enigma Resolvido','como'=>'Montar as 6 Peças e derrotar Amset-Ra','regra'=>'enigma','premio'=>2000],
    ];
}

function minhasConquistas(int $id): array
{
    return array_map('intval', array_column(q('SELECT conquista FROM posse_conquistas WHERE duelista_id = ?', [$id]), 'conquista'));
}
function conquistasNaoVistas(int $id): int
{
    return (int)qv('SELECT COUNT(*) FROM posse_conquistas WHERE duelista_id = ? AND vista_em IS NULL', [$id], 0);
}
function proximaAConcluir(int $id): int
{
    $tenho = minhasConquistas($id);
    for ($i = 1; $i <= 24; $i++) { if (!in_array($i, $tenho, true)) return $i; }
    return 25;
}
/** Está visível no quadro, ou ainda é silhueta com "???" */
function conquistaVisivel(int $n, array $tenho): bool
{
    if ($n <= CONQ_ABERTAS) return true;
    if (in_array($n, $tenho, true)) return true;
    return in_array($n - 1, $tenho, true);
}

/** Uma marca é um evento pontual do duelo — "fez tributo", "venceu sem dano". */
function marcar(int $id, string $marca): void
{
    ex('INSERT IGNORE INTO marcas (duelista_id, marca, criado_em) VALUES (?,?,NOW())', [$id, $marca]);
}
function temMarca(int $id, string $marca): bool
{
    return (bool)qv('SELECT COUNT(*) FROM marcas WHERE duelista_id = ? AND marca = ?', [$id, $marca], 0);
}

/**
 * Confere tudo e entrega o que estiver pronto. Roda depois de cada
 * duelo, de cada compra e ao abrir o painel — é barato e evita o
 * jogador ficar esperando por uma conquista que já mereceu.
 */
function conferirConquistas(int $id): array
{
    $d = q1('SELECT * FROM duelistas WHERE id = ?', [$id]);
    if (!$d) return [];
    $tenho = minhasConquistas($id);
    $novas = [];

    $vitorias  = (int)qv('SELECT COUNT(*) FROM duelos WHERE duelista_id = ? AND modo = "campanha" AND resultado = "vitoria"', [$id], 0);
    $duelos    = (int)qv('SELECT COUNT(*) FROM duelos WHERE duelista_id = ? AND modo = "campanha"', [$id], 0);
    $reliquias = (int)qv('SELECT COUNT(*) FROM posse_reliquias WHERE duelista_id = ?', [$id], 0);
    $maiorSaldo= (int)qv('SELECT COALESCE(MAX(oneub),0) FROM carteiras WHERE duelista_id = ?', [$id], 0);
    $distintas = (int)qv('SELECT COUNT(DISTINCT carta_num) FROM posse_cartas WHERE duelista_id = ? AND quantidade > 0', [$id], 0);
    $sequencia = (int)$d['sequencia_vitorias'];

    foreach (conquistas() as $n => $c) {
        if (in_array($n, $tenho, true)) continue;
        if (!conquistaVisivel($n, $tenho)) continue;
        [$tipo, $valor] = array_pad(explode(':', $c['regra']), 2, '');
        $ok = match ($tipo) {
            'sempre'    => true,
            'duelos'    => $duelos    >= (int)$valor,
            'vitorias'  => $vitorias  >= (int)$valor,
            'reliquias' => $reliquias >= (int)$valor,
            'saldo'     => $maiorSaldo>= (int)$valor,
            'cartas'    => $distintas >= (int)$valor,
            'sequencia' => $sequencia >= (int)$valor,
            'marca'     => temMarca($id, $valor),
            'limpou'    => limpouDificuldade($id, (int)$valor),
            'chefe5'    => (int)qv('SELECT COUNT(DISTINCT dificuldade) FROM chefoes_vencidos WHERE duelista_id = ? AND chefe = ?', [$id, (int)$valor], 0) >= 5,
            'enigma'    => tenhoReliquia($id, 'enigma'),
            default     => false,
        };
        if (!$ok) continue;

        ex('INSERT IGNORE INTO posse_conquistas (duelista_id, conquista, concluida_em) VALUES (?,?,NOW())', [$id, $n]);
        creditar($id, 1, (int)$c['premio'], 'conquista', $c['nome']);
        $novas[] = $n + 0;
        $tenho[] = $n;
        if ((int)$d['nivel'] < $n) { ex('UPDATE duelistas SET nivel = ? WHERE id = ?', [$n, $id]); $d['nivel'] = $n; }
        mandarMensagem($id, 'Conquista: ' . $c['nome'],
            $c['como'] . ".\n\nVocê subiu para o nível $n — " . tituloNivel($n) . ".\n\n"
            . mensagemDoNivel($n), 'mascote');
    }
    return $novas;
}

/** Marca as conquistas como vistas depois que o mascote apareceu. */
function marcarConquistasVistas(int $id): void
{
    ex('UPDATE posse_conquistas SET vista_em = NOW() WHERE duelista_id = ? AND vista_em IS NULL', [$id]);
}
function conquistasNovas(int $id): array
{
    return q('SELECT conquista FROM posse_conquistas WHERE duelista_id = ? AND vista_em IS NULL ORDER BY conquista', [$id]);
}
function arteConquista(int $n): string
{
    return arte('/assets/img/conquistas/conq_' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '.png');
}
