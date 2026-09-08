<?php
/**
 * JOGOS SALVOS — 5 lugares por dificuldade.
 * O lugar 5 é o automático: o site grava sozinho depois de cada duelo.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='salvar')  { salvar($id,(int)post('d'),(int)post('slot'),(string)post('nome','')); recado('Ponto gravado.'); }
    elseif ($a==='carregar'){ $r=carregar($id,(int)post('id')); recado($r['erro'] ?? 'Ponto carregado — o resto das dificuldades não foi tocado.', isset($r['erro'])?'erro':'ok'); }
    elseif ($a==='apagar'){ apagarSalvo($id,(int)post('id')); recado('Ponto apagado.'); }
    irPara('/salvos.php');
}
$titulo='Jogos salvos';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0">Jogos salvos</h1>
  <span class="dica">Carregar um ponto devolve tudo daquela dificuldade — e só dela.</span>
</div>

<?php foreach (dificuldades() as $i=>$dd): $lista=listarSalvos($id,$i); $porSlot=[];
  foreach ($lista as $s) $porSlot[(int)$s['slot']]=$s; ?>
  <div class="caixa" style="margin-bottom:var(--respiro);border-top:3px solid <?= e($dd['cor']) ?>">
    <div class="titulo-secao" style="margin:0 0 var(--respiro)">
      <h2 style="margin:0"><?= e($dd['nome']) ?></h2>
      <span class="dica"><?= quantosVencidos($id,$i) ?>/30 vencidos · <?= num(saldo($id,$i)) ?> <?= YU_MOEDA ?> · <?= num(quantasTenho($id,$i)) ?> cartas</span>
    </div>
    <div class="grade" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr))">
      <?php for ($slot=1;$slot<=SALVOS_POR_DIFICULDADE;$slot++): $s=$porSlot[$slot]??null; $auto=$slot===SALVOS_POR_DIFICULDADE; ?>
        <div class="cartao" style="gap:.4rem">
          <h3><?= $auto?'Automático':'Lugar '.$slot ?></h3>
          <?php if ($s): ?>
            <div style="font-family:var(--serif);font-size:1.05rem"><?= e($s['nome']) ?></div>
            <small class="dica"><?= e(dataBr($s['salvo_em'])) ?></small>
            <div class="rodape-cartao linha-btn">
              <form method="post" data-confirma="Carregar apaga o progresso ATUAL desta dificuldade. Continuar?">
                <?= csrfCampo() ?><input type="hidden" name="acao" value="carregar"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                <button class="btn btn-p btn-principal">Carregar</button></form>
              <?php if (!$auto): ?>
                <form method="post" data-confirma="Apagar este ponto?"><?= csrfCampo() ?>
                  <input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn-p btn-perigo">Apagar</button></form>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p class="dica" style="margin:0">Vazio.</p>
          <?php endif; ?>
          <?php if (!$auto): ?>
            <form method="post" class="rodape-cartao" style="display:flex;gap:.35rem">
              <?= csrfCampo() ?><input type="hidden" name="acao" value="salvar">
              <input type="hidden" name="d" value="<?= $i ?>"><input type="hidden" name="slot" value="<?= $slot ?>">
              <input type="text" name="nome" placeholder="nome do ponto" maxlength="40">
              <button class="btn btn-p">Gravar</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php require YU_APP . '/layout/rodape.php';
