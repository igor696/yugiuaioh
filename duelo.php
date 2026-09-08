<?php
/**
 * A PORTA DO MOTOR DE DUELO.
 *
 * O estado inteiro vive na sessão do servidor. O navegador manda uma
 * ação e recebe de volta apenas o que aquele lado pode ver — a mão do
 * oponente nunca sai daqui. Depois de cada ação do jogador, se a vez
 * passou, a IA joga o turno dela antes da resposta voltar.
 */
require __DIR__ . '/../../app/bootstrap.php';
exigirLogin();
csrfConfere();

$eu  = duelistaAtual();
$id  = (int)$eu['id'];
$dif = difAtual();

$e = $_SESSION['duelo'] ?? null;
if (!is_array($e)) json(['erro' => 'Não há duelo aberto. Volte para a campanha.'], 409);

$acao = (string)post('acao', 'estado');
$resp = ['ok' => true];

/* Quando o duelo já acabou, a única coisa que ainda responde é o estado —
   qualquer outra ação seria uma jogada depois do apito. */
if ($e['fim'] && $acao !== 'estado' && $acao !== 'fechar') {
    json(['ok' => true, 'estado' => estadoVisivel($e, 0), 'fim' => $e['fim']]);
}

switch ($acao) {
    case 'estado':
        break;

    case 'invocar':
        $r = acaoInvocar($e, 0, (int)post('num'), (string)post('modo', 'invocar'),
                         array_map('intval', (array)json_decode((string)post('tributos', '[]'), true)));
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        if ((string)post('modo') !== 'baixar' && tributosDe(carta((int)post('num'))) > 0) { marcar($id, 'tributo'); }
        break;

    case 'posicao':
        $r = acaoPosicao($e, 0, (int)post('zona'), (string)post('para', 'ataque'));
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        break;

    case 'baixar_magia':
        $r = acaoBaixarMagia($e, 0, (int)post('num'));
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        break;

    case 'ativar':
        $zona = post('zona_magia');
        $r = acaoAtivar($e, 0, (int)post('num'), $zona === null || $zona === '' ? null : (int)$zona,
                        ['zona' => post('alvo') === null || post('alvo') === '' ? null : (int)post('alvo')]);
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        $_SESSION['duelo_ativacoes'] = (int)($_SESSION['duelo_ativacoes'] ?? 0) + 1;
        if ((int)$_SESSION['duelo_ativacoes'] >= 3) { marcar($id, 'cadeia3'); }
        $resp['revelar'] = $r['revelar'] ?? null;
        break;

    case 'atacar':
        $alvo = post('alvo');
        $r = acaoAtacar($e, 0, (int)post('zona'), $alvo === null || $alvo === '' ? null : (int)$alvo);
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        break;

    case 'reliquia':
        $slug = (string)post('slug');
        if (!in_array($slug, reliquiasEquipadas($id), true)) json(['erro' => 'Essa relíquia não está equipada.'], 400);
        $r = usarReliquia($e, $slug, 0);
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        $resp['reliquia'] = $r;
        break;

    case 'anel_buscar':
        $r = anelBuscar($e, (int)post('num'), 0);
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        break;

    case 'fase':
        $r = avancarFase($e, 0);
        if (!empty($r['pede_descarte'])) { $_SESSION['duelo'] = $e; json(['ok' => true, 'pede_descarte' => $r['pede_descarte'], 'estado' => estadoVisivel($e, 0)]); }
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        break;

    case 'pular_batalha':
        $r = pularBatalha($e, 0);
        if (!empty($r['pede_descarte'])) { $_SESSION['duelo'] = $e; json(['ok' => true, 'pede_descarte' => $r['pede_descarte'], 'estado' => estadoVisivel($e, 0)]); }
        if (!empty($r['erro'])) json(['erro' => $r['erro']], 400);
        break;

    case 'descartar':
        $r = encerrarTurno($e, 0, array_map('intval', (array)json_decode((string)post('cartas', '[]'), true)));
        if (!empty($r['pede_descarte'])) json(['erro' => 'Escolha ' . $r['pede_descarte'] . ' carta(s) para descartar.'], 400);
        break;

    case 'desistir':
        $e['fim'] = ['vencedor' => 1, 'motivo' => 'desistencia'];
        diz($e, 'Você desistiu do duelo.', 'fim');
        break;

    default:
        json(['erro' => 'Ação desconhecida.'], 400);
}

/* ---- a vez da máquina ---------------------------------------------- */
if (!$e['fim'] && $e['quem'] === 1) {
    $ehChefe = ($e['oponente']['tipo'] ?? '') === 'chefe';
    jogarIa($e, cabecaDaDificuldade((int)$e['dif'], $ehChefe));
}

$_SESSION['duelo'] = $e;
$resp['estado'] = estadoVisivel($e, 0);

/* ---- e o fechamento, quando acabou --------------------------------- */
if ($e['fim'] && empty($_SESSION['duelo_fechado'])) {
    $_SESSION['duelo_fechado'] = true;
    $venceu  = (int)$e['fim']['vencedor'] === 0;
    $semDano = (int)$e['j'][0]['dano_sofrido'] === 0 && $venceu;
    if (($e['modo'] ?? 'campanha') === 'campanha') {
        $resp['resumo'] = fecharDueloCampanha($id, (int)$e['dif'], $e['oponente'], $venceu, $semDano, (int)($e['slot'] ?? 1));
        autoSalvar($id, (int)$e['dif']);
    }
}
json($resp);
