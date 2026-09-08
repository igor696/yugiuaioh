<?php
/**
 * O COFRE — Thoth-Bão, o Contador de Escrivaninha.
 * Cinco carteiras, uma por dificuldade, e o extrato linha por linha.
 * A separação não é enfeite: é a regra que segura a economia inteira.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual();
$verDif = (int)get('d', $dif);
$titulo = 'Cofre';
require YU_APP . '/layout/topo.php';
$rosto = arte('/assets/img/mercadores/rosto/merc-thoth-rosto.png');
?>
<div class="mercador-cabeca">
  <?php if ($rosto): ?><div class="retrato"><img src="<?= e($rosto) ?>" alt=""></div><?php endif; ?>
  <div>
    <h1 style="margin:0 0 .2rem">O Cofre</h1>
    <p class="fala" style="margin:0">“<?= e(mercadores()['thoth']['fala']) ?>”</p>
  </div>
</div>

<div class="grade g3">
  <?php foreach (dificuldades() as $i=>$dd): $c = carteira($id,$i); ?>
    <div class="cartao" style="border-top:3px solid <?= e($dd['cor']) ?>">
      <div class="cartao-topo"><?= icoOneub(20) ?><h3><?= e($dd['nome']) ?></h3></div>
      <div class="valor"><?= num((int)$c['oneub']) ?></div>
      <small class="dica"><?= YU_MOEDA ?><?= (int)$c['selos'] ? ' · ' . (int)$c['selos'] . ' Selo(s) do Faraó' : '' ?></small>
      <div class="rodape-cartao"><a class="btn btn-p btn-largo <?= $i===$verDif?'btn-principal':'' ?>" href="/cofre.php?d=<?= $i ?>">Ver extrato</a></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="titulo-secao">
  <h2>Extrato · <?= e(dificuldade($verDif)['nome']) ?></h2>
  <span class="dica">Tudo o que entrou e o que saiu, na ordem.</span>
</div>
<div class="caixa" style="padding:.4rem">
  <table class="lista">
    <thead><tr><th>Quando</th><th>Motivo</th><th>Detalhe</th><th class="num"><?= YU_MOEDA ?></th></tr></thead>
    <tbody>
    <?php $lin = extrato($id, $verDif, 120); if (!$lin): ?>
      <tr><td colspan="4" class="dica" style="padding:1.2rem">Nada por aqui ainda. Ganhe um duelo.</td></tr>
    <?php endif; foreach ($lin as $l): $v=(int)$l['valor']; ?>
      <tr>
        <td class="dica"><?= e(dataBr($l['criado_em'])) ?></td>
        <td><?= e(rotuloMotivo((string)$l['motivo'])) ?></td>
        <td class="dica"><?= e(corta((string)$l['detalhe'], 60)) ?></td>
        <td class="num <?= $v>0?'positivo':($v<0?'negativo':'') ?>"><?= $v>0?'+':'' ?><?= num($v) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<div class="caixa caixa-ouro" style="margin-top:var(--respiro)">
  <h3>A regra do <?= YU_MOEDA ?></h3>
  <p style="margin:0;max-width:var(--medida)">
    Ele não é vendido, não é comprado e não vale nada fora deste site. Não existe cartão, não
    existe PIX, não existe pacote de moedas. E o saldo de uma dificuldade não gasta em outra —
    é isso que impede alguém de farmar o Muito Fácil e comprar a campanha inteira.
  </p>
</div>
<?php require YU_APP . '/layout/rodape.php';
