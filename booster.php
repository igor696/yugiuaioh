<?php
/** A abertura do pacote, carta por carta. */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$r = $_SESSION['booster_aberto'] ?? null;
if (!$r) irPara('/loja.php?aba=booster');
unset($_SESSION['booster_aberto']);
$titulo='Pacote aberto'; $paginaEstreita=true;
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0"><?= e(colecoes()[$r['colecao']]['nome'] ?? $r['colecao']) ?></h1>
  <?php if (!empty($r['super'])): ?><span class="etiqueta ouro">saiu super-rara</span><?php endif; ?>
</div>
<div class="caixa">
  <div class="grade-cartas" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
    <?php foreach ($r['cartas'] as $i=>$c): $nova = in_array((int)$c['num'], $r['novas'], true); ?>
      <div style="text-align:center">
        <div class="mini surge" style="animation-delay:<?= $i*90 ?>ms" data-lupa="<?= e(arteCarta($c)) ?>">
          <img src="<?= e(arteCarta($c)) ?>" alt="<?= e($c['nome']) ?>">
          <span class="tipo" style="background:<?= e(corDoTipo($c['tipo'])) ?>"></span>
        </div>
        <div style="font-size:.8rem;margin-top:.3rem;line-height:1.2"><?= e(corta($c['nome'],26)) ?></div>
        <?php if (!$nova): ?><small class="dica">acima de 3 cópias</small><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($r['devolvido'])): ?>
    <p class="dica" style="margin-top:var(--respiro)">Você já tinha 3 cópias de alguma delas.
       O que passou do limite voltou como <b class="ouro"><?= num((int)$r['devolvido']) ?> <?= YU_MOEDA ?></b>.</p>
  <?php endif; ?>
  <div class="linha-btn" style="margin-top:var(--respiro)">
    <a class="btn btn-principal" href="/loja.php?aba=booster">Abrir outro</a>
    <a class="btn" href="/deck.php">Ir montar deck</a>
  </div>
</div>
<?php require YU_APP . '/layout/rodape.php';
