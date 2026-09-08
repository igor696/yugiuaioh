<?php
/** Suporte: um chamado, e o Faraó responde. */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere();
    $t=mb_substr(trim((string)post('titulo')),0,80); $c=trim((string)post('corpo'));
    if ($t==='' || $c==='') recado('Escreva o assunto e o problema.','erro');
    else { ex('INSERT INTO suporte (duelista_id,titulo,corpo,criado_em) VALUES (?,?,?,NOW())',[$id,$t,$c]); recado('Chamado aberto.'); }
    irPara('/suporte.php');
}
$meus=q('SELECT * FROM suporte WHERE duelista_id=? ORDER BY id DESC LIMIT 30',[$id]);
$titulo='Suporte'; $paginaEstreita=true;
require YU_APP . '/layout/topo.php';
?>
<h1>Suporte</h1>
<p class="dica">Achou um erro de regra? Descreva o que aconteceu na mesa, com o turno e a fase — é o que resolve mais rápido.</p>
<div class="caixa caixa-ouro">
  <form method="post"><?= csrfCampo() ?>
    <div class="campo"><label for="titulo">Assunto</label><input type="text" id="titulo" name="titulo" maxlength="80" required></div>
    <div class="campo"><label for="corpo">O que aconteceu</label><textarea id="corpo" name="corpo" rows="6" required></textarea></div>
    <button class="btn btn-principal">Abrir chamado</button></form>
</div>
<?php foreach ($meus as $s): ?>
  <div class="caixa"><div style="display:flex;gap:.6rem;align-items:center">
    <b style="font-family:var(--serif)"><?= e($s['titulo']) ?></b>
    <span class="etiqueta" style="color:<?= $s['resposta']?'var(--turquesa)':'var(--texto-fraco)' ?>"><?= $s['resposta']?'respondido':'aberto' ?></span>
    <small class="dica" style="margin-left:auto"><?= e(faz($s['criado_em'])) ?></small></div>
    <p style="white-space:pre-line;margin:.5rem 0 0"><?= e($s['corpo']) ?></p>
    <?php if ($s['resposta']): ?><div class="recado" style="margin-top:.6rem;white-space:pre-line"><b>Faraó:</b> <?= e($s['resposta']) ?></div><?php endif; ?>
  </div>
<?php endforeach; ?>
<?php require YU_APP . '/layout/rodape.php';
