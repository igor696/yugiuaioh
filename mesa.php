<?php
/**
 * A MESA.
 *
 * A página monta o duelo e desenha o tapete vazio; daí para a frente
 * quem manda é o duelo.js falando com a /api/duelo.php. Nada do estado
 * do adversário vem escrito no HTML — se viesse, bastaria abrir o código
 * da página para ver a mão dele.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id = (int)$eu['id']; $dif = difAtual();

$tipo = (string)get('tipo', 'oponente');
$n    = (int)get('n', 1);
$novo = get('novo') !== null || !isset($_SESSION['duelo']);

if ($novo) {
    $deck = deckAtivo($id, $dif);
    if (!$deck) { recado('Monte um deck nesta dificuldade antes de duelar.', 'erro'); irPara('/deck.php'); }
    $conf = conferirDeck((int)$deck['id']);
    if (!$conf['valido']) { recado('O deck não está válido: ' . $conf['erros'][0], 'erro'); irPara('/deck.php?id=' . (int)$deck['id']); }

    if ($tipo === 'chefe') {
        $c = chefoes()[$n] ?? null;
        if (!$c) { irPara('/campanha.php'); }
        if (!chefeLiberado($id, $dif)) { recado('O chefão ainda não apareceu nesta cidade.', 'aviso'); irPara('/campanha.php'); }
        $alvo = ['tipo' => 'chefe', 'n' => $n, 'nome' => $c['nome'], 'eixo' => $c['eixo'],
                 'arte' => arte('/assets/img/chefoes/' . $c['arquivo'] . '.png'),
                 'rosto' => arte('/assets/img/chefoes/rosto/' . $c['arquivo'] . '.png'),
                 'fala' => $c['deboche'], 'cor' => $c['cor']];
        $slot = 5;
        [$dp, $da] = deckDoOponente($dif, $n, 5, true);
    } else {
        $o = oponente($dif, $n);
        if (!$o) { irPara('/campanha.php'); }
        $slot = slotDoOponente($id, $dif, $n);
        $alvo = ['tipo' => 'oponente', 'n' => $n, 'nome' => $o['nome'], 'eixo' => $o['eixo'],
                 'arte' => arteOponente($dif, $o), 'rosto' => arteOponente($dif, $o, true),
                 'fala' => '', 'cor' => dificuldade($dif)['cor']];
        [$dp, $da] = deckDoOponente($dif, $n, $slot, false);
    }

    $meuDeck  = listaParaDuelo((int)$deck['id']);
    $meuExtra = listaAdicional((int)$deck['id']);
    $primeiro = random_int(0, 1);

    $e = novoDuelo($meuDeck, $meuExtra, $dp, $da, $dif, $alvo, $primeiro);
    $e['modo'] = 'campanha';
    $e['slot'] = $slot;
    if (tenhoItem($id, 'folego')) { $e['j'][0]['pv'] += 500; diz($e, 'Incenso de Fôlego: você começa com 8500 Pontos de Vida.'); }
    if ($primeiro === 1) {                       // a máquina começa: ela joga já
        jogarIa($e, cabecaDaDificuldade($dif, $tipo === 'chefe'));
    }
    $_SESSION['duelo'] = $e;
    $_SESSION['duelo_fechado'] = false;
    $_SESSION['duelo_ativacoes'] = 0;
    irPara('/mesa.php');
}

$e = $_SESSION['duelo'];
$alvo = $e['oponente'];
$minhasRel = reliquiasEquipadas($id);
$titulo = 'Duelo · ' . $alvo['nome'];
$jsExtra = ['duelo.js'];
require YU_APP . '/layout/topo.php';
?>

<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0;font-size:1.4rem"><?= e($alvo['nome']) ?></h1>
  <span class="etiqueta" style="color:<?= e($alvo['cor'] ?? '#D4AF37') ?>">
    <?= e($alvo['tipo'] === 'chefe' ? 'Chefão' : 'Deck ' . (int)$e['slot'] . '/5') ?>
  </span>
  <span class="dica"><?= e($alvo['eixo']) ?></span>
  <form method="post" action="/api/duelo.php" style="margin-left:auto" id="form-desistir">
    <button type="button" class="btn btn-p btn-perigo" id="btn-desistir">Desistir</button>
  </form>
</div>

<?php if (!empty($alvo['fala'])): ?>
  <div class="mercador-cabeca" style="margin-bottom:.8rem">
    <?php if (!empty($alvo['rosto'])): ?><div class="retrato" style="width:78px;height:78px"><img src="<?= e($alvo['rosto']) ?>" alt=""></div><?php endif; ?>
    <p class="fala" style="margin:0">“<?= e($alvo['fala']) ?>”</p>
  </div>
<?php endif; ?>

<div class="mesa">
  <div>
    <!-- ---------------------------------------------- lado do oponente -->
    <div class="mesa-meio">
      <div class="pv" id="pv-ele">
        <?php if (!empty($alvo['rosto'])): ?>
          <img src="<?= e($alvo['rosto']) ?>" alt="" width="44" height="44" style="border-radius:50%;border:2px solid var(--borda-viva)">
        <?php endif; ?>
        <span class="rot"><?= e($alvo['nome']) ?></span><span class="n">8000</span>
      </div>
      <div class="dica">mão <b id="mao-ele">0</b> · deck <b id="deck-ele">0</b> · cemitério <b id="cem-ele">0</b></div>
    </div>

    <div class="tapete">
      <div class="lado">
        <div class="fileira" id="mag-ele"></div>
        <div class="fileira" id="mon-ele"></div>
      </div>
      <div class="friso" style="margin:.6rem 0"></div>
      <div class="lado">
        <div class="fileira" id="mon-eu"></div>
        <div class="fileira" id="mag-eu"></div>
      </div>
    </div>

    <div class="mesa-meio">
      <div class="pv" id="pv-eu">
        <?php $f = avatarUrl($eu); if ($f): ?>
          <img src="<?= e($f) ?>" alt="" width="44" height="44" style="border-radius:50%;border:2px solid var(--ouro)">
        <?php endif; ?>
        <span class="rot"><?= e($eu['nome']) ?></span><span class="n">8000</span>
      </div>
      <div class="dica">deck <b id="deck-eu">0</b> · cemitério <b id="cem-eu">0</b> · adicional <b id="extra-eu">0</b></div>
    </div>

    <div class="mao" id="mao-eu"></div>
  </div>

  <!-- ------------------------------------------------------- o painel -->
  <aside class="painel-duelo">
    <div class="caixa" style="padding:.8rem">
      <div class="fase-trilho" id="fases">
        <?php foreach (FASES as $f): ?><span data-f="<?= $f ?>"><?= e(str_replace('Fase de ', '', str_replace('Fase ', '', nomeFase($f)))) ?></span><?php endforeach; ?>
      </div>
      <p class="dica" id="dica-fase" style="margin:.6rem 0 .4rem">—</p>
      <div class="linha-btn">
        <button class="btn btn-p btn-principal" id="btn-fase">Avançar fase</button>
        <button class="btn btn-p" id="btn-pular">Pular batalha</button>
      </div>
    </div>

    <?php if ($minhasRel): ?>
    <div class="caixa" style="padding:.8rem">
      <div class="rodape-titulo" style="margin-bottom:.5rem">Relíquia</div>
      <div class="reliquia-encaixe" id="reliquias">
        <?php foreach ($minhasRel as $slug): $r = reliquiasTodas()[$slug];
          $img = '/assets/img/reliquias/' . $r['arquivo'] . '-icone.png';
          if (!temArte($img)) $img = '/assets/img/reliquias/' . $r['arquivo'] . '.png'; ?>
          <button type="button" data-reliquia="<?= e($slug) ?>" title="<?= e($r['nome'] . ' — ' . $r['descricao']) ?>">
            <?php if (temArte($img)): ?><img src="<?= $img ?>" alt=""><?php else: ?><?= icoReliquia(24) ?><?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
      <small class="dica" style="display:block;margin-top:.45rem">Uma vez por duelo. Fora da Cadeia.</small>
    </div>
    <?php endif; ?>

    <div class="caixa" style="padding:.8rem">
      <div class="rodape-titulo" style="margin-bottom:.5rem">O que aconteceu</div>
      <div class="log" id="log"></div>
    </div>
  </aside>
</div>

<div id="janela"></div>

<script>window.YU = { csrf: document.querySelector('meta[name=csrf]').content, verso: '/assets/img/logo/verso.png' };</script>
<?php require YU_APP . '/layout/rodape.php';
