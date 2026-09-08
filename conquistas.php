<?php
/**
 * O QUADRO DAS 24.
 * As 6 primeiras vêm abertas; da 7 em diante, a próxima só aparece
 * depois que a anterior é concluída. O que ainda não apareceu fica em
 * silhueta com "???", que é o que dá vontade de descobrir.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id'];
conferirConquistas($id); marcarConquistasVistas($id);
$tenho=minhasConquistas($id);
$titulo='Conquistas';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0">Conquistas</h1>
  <span class="dica"><?= count($tenho) ?> de 24 · cada uma vale um nível · só contam na campanha</span>
</div>
<?= barra(count($tenho), 24) ?>

<div class="grade-conq" style="margin-top:var(--respiro)">
  <?php foreach (conquistas() as $n=>$c):
    $feita=in_array($n,$tenho,true); $visivel=conquistaVisivel($n,$tenho); $img=arteConquista($n); ?>
    <div class="conq <?= $feita?'feita':'' ?> <?= $visivel?'':'silhueta' ?>">
      <div class="medalha">
        <?php if ($img && $visivel): ?><img src="<?= e($img) ?>" alt="" loading="lazy" style="<?= $feita?'':'filter:grayscale(1) brightness(.5)' ?>">
        <?php elseif ($visivel): ?><span class="interrog" style="color:<?= $feita?'var(--ouro)':'var(--borda-viva)' ?>"><?= $n ?></span>
        <?php else: ?><span class="interrog">?</span><?php endif; ?>
      </div>
      <?= emblemaNivel($n,'p') ?>
      <h3 style="margin-top:.35rem"><?= e($visivel?$c['nome']:'???') ?></h3>
      <p class="como"><?= e($visivel?$c['como']:'Conclua a anterior para esta aparecer.') ?></p>
      <?php if ($visivel): ?><small class="dica">+<?= num((int)$c['premio']) ?> <?= YU_MOEDA ?> · vira <?= e(tituloNivel($n)) ?></small><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php require YU_APP . '/layout/rodape.php';
