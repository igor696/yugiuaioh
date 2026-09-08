<?php
/** ONLINE — salas públicas entre duelistas cadastrados. */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='abrir') {
        if (!deckAtivo($id,$dif)) { recado('Monte um deck antes.','erro'); irPara('/deck.php'); }
        ex('INSERT INTO salas (codigo,tipo,dono_id,dificuldade,criada_em) VALUES (?,?,?,?,NOW())',
           [strtoupper(bin2hex(random_bytes(3))),'online',$id,$dif]);
        recado('Sala publicada no bazar.');
    } elseif ($a==='entrar') {
        $s=q1('SELECT * FROM salas WHERE id=? AND tipo="online" AND convidado_id IS NULL AND dono_id<>?',[(int)post('id'),$id]);
        if (!$s) recado('Essa sala não está mais livre.','erro');
        else { ex('UPDATE salas SET convidado_id=?, entrou_em=NOW() WHERE id=?',[$id,(int)$s['id']]); recado('Você entrou.'); }
    } elseif ($a==='fechar') { ex('DELETE FROM salas WHERE id=? AND dono_id=?',[(int)post('id'),$id]); }
    irPara('/online.php');
}
$salas=q('SELECT s.*, d.nome AS dono, d.avatar, d.nivel, c.nome AS convidado FROM salas s
          JOIN duelistas d ON d.id=s.dono_id LEFT JOIN duelistas c ON c.id=s.convidado_id
          WHERE s.tipo="online" ORDER BY s.id DESC LIMIT 40');
$titulo='Online';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0"><h1 style="margin:0">Salas abertas</h1>
  <span class="dica">Sua mão fica no servidor. O outro lado recebe só a contagem — nunca as cartas.</span>
  <form method="post" style="margin-left:auto"><?= csrfCampo() ?><input type="hidden" name="acao" value="abrir">
    <button class="btn btn-p btn-principal">Abrir sala</button></form>
</div>
<?php if (!$salas): ?>
  <div class="caixa centro" style="padding:2.5rem">
    <?php if ($m=mascote('pensando')): ?><img src="<?= e($m) ?>" alt="" width="190" height="190" style="margin:0 auto 1rem"><?php endif; ?>
    <h2>Nenhuma sala no bazar agora</h2><p class="dica">Abra a primeira.</p>
  </div>
<?php else: ?>
<div class="grade g3">
  <?php foreach ($salas as $s): $av='/assets/img/avatares/128/avatar-'.str_pad((string)max(1,(int)$s['avatar']),2,'0',STR_PAD_LEFT).'.png'; ?>
    <div class="cartao">
      <div style="display:flex;gap:.7rem;align-items:center">
        <div style="width:54px;height:54px;border-radius:50%;overflow:hidden;border:2px solid var(--ouro);flex:none">
          <?php if (temArte($av)): ?><img src="<?= $av ?>" alt=""><?php endif; ?></div>
        <div><b style="font-family:var(--serif)"><?= e($s['dono']) ?></b><?= emblemaNivel((int)$s['nivel'],'p') ?>
          <div class="dica"><?= e(dificuldade((int)$s['dificuldade'])['nome']) ?> · <?= e(faz($s['criada_em'])) ?></div></div>
      </div>
      <div class="rodape-cartao">
        <?php if ($s['convidado']): ?><span class="etiqueta">cheia — <?= e($s['convidado']) ?></span>
        <?php elseif ((int)$s['dono_id']===$id): ?>
          <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="fechar"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn btn-p btn-perigo btn-largo">Fechar minha sala</button></form>
        <?php else: ?>
          <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="entrar"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn btn-p btn-largo btn-principal">Entrar</button></form>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<div class="recado aviso" style="margin-top:var(--respiro)">
  O motor de duelo entre duas pessoas ainda está sendo ligado. A sala já funciona para
  combinar a partida; o tapete completo, hoje, é o da campanha.
</div>
<?php require YU_APP . '/layout/rodape.php';
