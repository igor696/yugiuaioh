<?php
/**
 * COLEÇÃO LIVRE — fora da campanha.
 * Um caderno para registrar cartas que você quer acompanhar sem que
 * isso mexa em nenhuma das cinco dificuldades. Não vale para duelo.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='por')  ex('INSERT INTO colecao_livre (duelista_id,carta_num,quantidade) VALUES (?,?,1)
                         ON DUPLICATE KEY UPDATE quantidade=quantidade+1',[$id,(int)post('num')]);
    if ($a==='tirar'){ ex('UPDATE colecao_livre SET quantidade=quantidade-1 WHERE duelista_id=? AND carta_num=?',[$id,(int)post('num')]);
                       ex('DELETE FROM colecao_livre WHERE duelista_id=? AND carta_num=? AND quantidade<=0',[$id,(int)post('num')]); }
    irPara('/livre.php?b='.urlencode((string)get('b','')));
}
$busca=trim((string)get('b',''));
$minhas=q('SELECT c.*, l.quantidade FROM colecao_livre l JOIN cartas c ON c.num=l.carta_num
           WHERE l.duelista_id=? ORDER BY c.nome',[$id]);
$achadas = $busca ? q('SELECT * FROM cartas WHERE nome LIKE ? OR passcode LIKE ? ORDER BY num LIMIT 36',['%'.$busca.'%','%'.$busca.'%']) : [];
$titulo='Coleção livre';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0"><h1 style="margin:0">Coleção livre</h1>
  <span class="dica">Um caderno à parte. Não conta para duelo, nível nem conquista — e por isso não trava nada.</span></div>
<div class="grade g2" style="align-items:start">
  <div class="caixa"><h2>O que você registrou · <?= count($minhas) ?></h2>
    <?php if (!$minhas): ?><p class="dica">Nada ainda. Procure ao lado e vá somando.</p><?php else: ?>
      <div class="grade-cartas">
        <?php foreach ($minhas as $c): ?>
          <form method="post" style="margin:0"><?= csrfCampo() ?><input type="hidden" name="acao" value="tirar"><input type="hidden" name="num" value="<?= (int)$c['num'] ?>">
            <button style="all:unset;cursor:pointer;display:block" title="Tirar 1 de «<?= e($c['nome']) ?>»">
              <span class="mini" style="display:block"><img src="<?= e(arteCarta($c)) ?>" alt="">
                <span class="qtd">×<?= (int)$c['quantidade'] ?></span></span></button></form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="caixa"><h2>Procurar nas 722</h2>
    <form method="get" class="filtros"><div class="campo cresce"><label for="b">Nome ou passcode</label>
      <input type="search" id="b" name="b" value="<?= e($busca) ?>"></div><button class="btn btn-p">Procurar</button></form>
    <div class="grade-cartas">
      <?php foreach ($achadas as $c): ?>
        <form method="post" style="margin:0"><?= csrfCampo() ?><input type="hidden" name="acao" value="por"><input type="hidden" name="num" value="<?= (int)$c['num'] ?>">
          <button style="all:unset;cursor:pointer;display:block" title="<?= e($c['nome']) ?>">
            <span class="mini" style="display:block"><img src="<?= e(arteCarta($c)) ?>" alt=""></span></button></form>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php require YU_APP . '/layout/rodape.php';
