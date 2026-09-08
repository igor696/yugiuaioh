<?php
/**
 * DECK REAL — os baralhos de papel que você tem em casa.
 * Serve para lembrar o que está montado na caixa, e é só isso. Não
 * duela, não conta nada. Muita gente esquece o que já montou.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='criar') { ex('INSERT INTO decks_reais (duelista_id,nome,nota,criado_em) VALUES (?,?,?,NOW())',
        [$id, mb_substr(trim((string)post('nome')) ?: 'Deck de papel',0,40), (string)post('nota','')]); recado('Anotado.'); }
    if ($a==='apagar') ex('DELETE FROM decks_reais WHERE id=? AND duelista_id=?',[(int)post('id'),$id]);
    irPara('/deck-real.php');
}
$lista=q('SELECT * FROM decks_reais WHERE duelista_id=? ORDER BY id DESC',[$id]);
$titulo='Deck real';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0"><h1 style="margin:0">Deck real</h1>
  <span class="dica">O que está montado na caixa, em casa. Nada aqui entra na mesa do site.</span></div>
<div class="grade g2" style="align-items:start">
  <div class="caixa caixa-ouro"><h2>Anotar um deck de papel</h2>
    <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="criar">
      <div class="campo"><label for="nome">Nome</label><input type="text" id="nome" name="nome" maxlength="40" required></div>
      <div class="campo"><label for="nota">O que tem nele</label><textarea id="nota" name="nota" rows="8" placeholder="Cole a lista, ou escreva do jeito que você lembra."></textarea></div>
      <button class="btn btn-principal btn-largo">Guardar</button></form>
  </div>
  <div>
    <?php if (!$lista): ?><div class="caixa"><p class="dica" style="margin:0">Nenhum deck de papel anotado ainda.</p></div><?php endif; ?>
    <?php foreach ($lista as $d): ?>
      <div class="caixa"><div style="display:flex;align-items:center;gap:.6rem">
        <h3 style="margin:0;font-family:var(--serif);font-size:1.15rem"><?= e($d['nome']) ?></h3>
        <small class="dica"><?= e(dataBr($d['criado_em'],false)) ?></small>
        <form method="post" data-confirma="Apagar?" style="margin-left:auto"><?= csrfCampo() ?>
          <input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
          <button class="btn btn-p btn-perigo">Apagar</button></form></div>
        <p style="white-space:pre-line;margin:.6rem 0 0"><?= e($d['nota']) ?></p></div>
    <?php endforeach; ?>
  </div>
</div>
<?php require YU_APP . '/layout/rodape.php';
