<?php
/**
 * A CAMPANHA — a cidade da dificuldade escolhida.
 * Trinta retratos, o chefão no fim, e o slot de deck de cada oponente
 * escrito no canto: é a informação que decide contra quem vale duelar.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id = (int)$eu['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('acao') === 'dificuldade') {
    csrfConfere(); trocarDificuldade((int)post('d', 1)); irPara('/campanha.php');
}
$dif = difAtual(); $d = dificuldade($dif);
$venc = vencidos($id, $dif);
$chefeN = chefeDaDificuldade($dif);
$chefe  = chefoes()[$chefeN];
$liberado = chefeLiberado($id, $dif);
$jaVenceuChefe = chefeVencido($id, $dif, $chefeN);
$deck = deckAtivo($id, $dif);
$conf = $deck ? conferirDeck((int)$deck['id']) : null;
$prontoParaDuelar = $conf && $conf['valido'];

$titulo = 'Campanha · ' . $d['nome'];
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0"><?= e($d['cidade']) ?></h1>
  <span class="etiqueta" style="color:<?= e($d['cor']) ?>"><?= e($d['nome']) ?></span>
  <span class="dica"><?= count($venc) ?> de 30 vencidos<?= $liberado ? ' · chefão liberado' : ' · faltam ' . (20 - count($venc)) . ' para o chefão' ?></span>
</div>

<form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="dificuldade">
  <div class="trilha">
    <?php foreach (dificuldades() as $i => $dd): $selo = '/assets/img/selos/dif-' . $i . '.png'; ?>
      <button type="submit" name="d" value="<?= $i ?>" class="cidade <?= $i === $dif ? 'ativa' : '' ?>" style="color:<?= e($dd['cor']) ?>;cursor:pointer">
        <?php if (temArte($selo)): ?><img src="<?= $selo ?>" alt=""><?php endif; ?>
        <span><?= e($dd['nome']) ?><span class="n" style="display:block"><?= quantosVencidos($id,$i) ?>/30</span></span>
      </button>
    <?php endforeach; ?>
  </div>
</form>

<?php if (!$prontoParaDuelar): ?>
  <div class="recado aviso">
    <?php if (!$deck): ?>
      Você ainda não tem um deck nesta dificuldade. <a href="/deck.php">Monte um</a> — o Indawora ajuda a completar com o que você já tem.
    <?php else: ?>
      O deck <strong><?= e($deck['nome']) ?></strong> ainda não está válido: <?= e($conf['erros'][0] ?? '') ?>
      <a href="/deck.php">Abrir o montador</a>.
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="grade-op">
  <?php foreach (oponentesDaDificuldade($dif) as $o):
    $n = (int)$o['n'];
    $ja = in_array($n, $venc, true);
    $slot = slotDoOponente($id, $dif, $n);
    $img = arteOponente($dif, $o, true); ?>
    <a class="op" href="/mesa.php?tipo=oponente&amp;n=<?= $n ?>" title="<?= e($o['nome']) ?>">
      <div class="op-arte">
        <?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy">
        <?php else: ?><div class="sem-arte"><span><?= $n ?></span></div><?php endif; ?>
        <span class="op-slot">deck <?= $slot ?>/5</span>
        <?php if ($ja): ?><span class="op-venceu" title="Já vencido">✓</span><?php endif; ?>
      </div>
      <div class="op-corpo">
        <div class="op-nome"><?= e($o['nome']) ?></div>
        <div class="op-eixo"><?= e($o['eixo']) ?></div>
      </div>
    </a>
  <?php endforeach; ?>

  <div class="chefe-faixa" style="color:<?= e($chefe['cor']) ?>">
    <?php $ac = arte('/assets/img/chefoes/' . $chefe['arquivo'] . '.png'); ?>
    <div class="arte">
      <?php if ($ac): ?><img src="<?= e($ac) ?>" alt="" loading="lazy" style="<?= $liberado ? '' : 'filter:grayscale(1) brightness(.4)' ?>">
      <?php else: ?><div class="sem-arte" style="height:100%"></div><?php endif; ?>
    </div>
    <div>
      <span class="etiqueta">Chefão da cidade</span>
      <h3 style="margin:.4rem 0 .2rem"><?= e($liberado ? $chefe['nome'] : '???') ?></h3>
      <?php if ($liberado): ?>
        <p class="dica" style="margin:0"><?= e($chefe['eixo']) ?> · guarda o <strong><?= e(reliquiasTodas()[$chefe['reliquia']]['nome']) ?></strong></p>
        <p class="deboche">“<?= e($chefe['deboche']) ?>”</p>
      <?php else: ?>
        <p class="dica" style="margin:0">Vença 20 dos 30 duelistas desta cidade para ele aparecer. Faltam <?= max(0, 20 - count($venc)) ?>.</p>
      <?php endif; ?>
    </div>
    <div style="min-width:180px">
      <?php if ($liberado): ?>
        <a class="btn btn-principal btn-largo" href="/mesa.php?tipo=chefe&amp;n=<?= $chefeN ?>">Desafiar</a>
        <?php if ($jaVenceuChefe): ?><small class="dica" style="display:block;margin-top:.4rem;text-align:center">Já derrotado aqui</small><?php endif; ?>
      <?php else: ?>
        <button class="btn btn-largo" disabled>Trancado</button>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($liberado): ?>
<div class="caixa" style="margin-top:var(--respiro)">
  <h2><?= e($chefe['nome']) ?></h2>
  <p style="max-width:var(--medida)"><?= e($chefe['lore']) ?></p>
</div>
<?php endif; ?>

<?php require YU_APP . '/layout/rodape.php';
