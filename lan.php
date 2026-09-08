<?php
/**
 * LAN — duas máquinas na mesma rede.
 * A sala vive no banco, com um código curto. Quem abre espera; quem
 * entra digita o código. Nada disso conta conquista: não há como provar
 * quem ganhou de verdade quando os dois lados são gente.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='abrir') {
        $deck=deckAtivo($id,$dif);
        if (!$deck) { recado('Monte um deck antes.','erro'); irPara('/deck.php'); }
        $codigo = strtoupper(bin2hex(random_bytes(2)));
        ex('INSERT INTO salas (codigo,tipo,dono_id,dificuldade,criada_em) VALUES (?,?,?,?,NOW())',[$codigo,'lan',$id,$dif]);
        recado("Sala aberta. O código é $codigo.");
    } elseif ($a==='entrar') {
        $c=strtoupper(trim((string)post('codigo')));
        $s=q1('SELECT * FROM salas WHERE codigo=? AND convidado_id IS NULL AND dono_id<>?',[$c,$id]);
        if (!$s) recado('Código não encontrado, ou a sala já tem dois.','erro');
        else { ex('UPDATE salas SET convidado_id=?, entrou_em=NOW() WHERE id=?',[$id,(int)$s['id']]); recado('Você entrou na sala.'); }
    } elseif ($a==='fechar') { ex('DELETE FROM salas WHERE id=? AND dono_id=?',[(int)post('id'),$id]); }
    irPara('/lan.php');
}
$minhas=q('SELECT s.*, d.nome AS dono, c.nome AS convidado FROM salas s
           JOIN duelistas d ON d.id=s.dono_id LEFT JOIN duelistas c ON c.id=s.convidado_id
           WHERE s.tipo="lan" AND (s.dono_id=? OR s.convidado_id=?) ORDER BY s.id DESC',[$id,$id]);
$titulo='LAN';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0"><h1 style="margin:0">Duelo em rede local</h1>
  <span class="dica">Sem conquista, sem nível, sem <?= YU_MOEDA ?>. É só o jogo.</span></div>
<div class="grade g2">
  <div class="caixa caixa-ouro">
    <h2>Abrir sala</h2>
    <p class="dica">Você recebe um código de 4 caracteres. Passe para quem está na mesma rede.</p>
    <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="abrir">
      <button class="btn btn-principal btn-largo">Abrir sala</button></form>
  </div>
  <div class="caixa">
    <h2>Entrar por código</h2>
    <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="entrar">
      <div class="campo"><label for="codigo">Código da sala</label>
        <input type="text" id="codigo" name="codigo" maxlength="8" required style="text-transform:uppercase;letter-spacing:.2em;font-family:var(--cond);font-size:1.3rem;text-align:center"></div>
      <button class="btn btn-largo">Entrar</button></form>
  </div>
</div>
<?php if ($minhas): ?>
<div class="titulo-secao"><h2>Suas salas</h2></div>
<div class="caixa" style="padding:.4rem">
  <table class="lista"><thead><tr><th>Código</th><th>Dono</th><th>Convidado</th><th>Aberta</th><th></th></tr></thead><tbody>
  <?php foreach ($minhas as $s): ?>
    <tr><td style="font-family:var(--cond);letter-spacing:.2em;font-size:1.1rem;color:var(--ouro)"><?= e($s['codigo']) ?></td>
        <td><?= e($s['dono']) ?></td><td class="dica"><?= e($s['convidado'] ?? 'esperando…') ?></td>
        <td class="dica"><?= e(faz($s['criada_em'])) ?></td>
        <td><?php if ((int)$s['dono_id']===$id): ?>
          <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="fechar"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
            <button class="btn btn-p btn-perigo">Fechar</button></form><?php endif; ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>
<div class="recado aviso" style="margin-top:var(--respiro)">
  O motor de duelo entre duas pessoas ainda está sendo ligado. Por enquanto a sala serve para
  combinar a partida — o tapete contra a máquina já está inteiro, na campanha.
</div>
<?php require YU_APP . '/layout/rodape.php';
