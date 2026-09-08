<?php
/** Resgatar um código de presente do Faraó. */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere();
    $c=strtoupper(trim((string)post('codigo')));
    $p=q1('SELECT * FROM presentes WHERE codigo=? AND (usos_max=0 OR usos < usos_max)',[$c]);
    if (!$p) recado('Código inválido, esgotado ou já usado.','erro');
    elseif (qv('SELECT COUNT(*) FROM presentes_usados WHERE presente_id=? AND duelista_id=?',[(int)$p['id'],$id],0))
        recado('Você já resgatou esse código.','erro');
    else {
        creditar($id,$dif,(int)$p['oneub'],'codigo',$p['codigo']);
        if ((int)$p['booster']) { sortearBoosterDePremio($id,$dif,false); }
        ex('INSERT INTO presentes_usados (presente_id,duelista_id,criado_em) VALUES (?,?,NOW())',[(int)$p['id'],$id]);
        ex('UPDATE presentes SET usos=usos+1 WHERE id=?',[(int)$p['id']]);
        recado('Resgatado: +' . num((int)$p['oneub']) . ' ' . YU_MOEDA . ((int)$p['booster'] ? ' e um pacote.' : '.'));
    }
    irPara('/resgatar.php');
}
$titulo='Resgatar código'; $paginaEstreita=true;
require YU_APP . '/layout/topo.php';
?>
<div class="caixa caixa-ouro centro" style="padding:2.2rem">
  <?php if ($m=mascote('presente') ?: mascote('reliquia')): ?><img src="<?= e($m) ?>" alt="" width="180" height="180" style="margin:0 auto 1rem"><?php endif; ?>
  <h1>Tem um código na mão?</h1>
  <p class="dica" style="max-width:44ch;margin:0 auto 1.2rem">O prêmio cai na carteira da dificuldade em que você está agora — <b><?= e(dificuldade($dif)['nome']) ?></b>.</p>
  <form method="post" style="max-width:330px;margin:0 auto"><?= csrfCampo() ?>
    <div class="campo"><input type="text" name="codigo" maxlength="24" required
      style="text-transform:uppercase;letter-spacing:.18em;text-align:center;font-family:var(--cond);font-size:1.3rem"></div>
    <button class="btn btn-principal btn-largo">Resgatar</button></form>
</div>
<?php require YU_APP . '/layout/rodape.php';
