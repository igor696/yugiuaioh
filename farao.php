<?php
/** O PAINEL DO FARAÓ — o cargo de administração da casa. */
require __DIR__ . '/../app/bootstrap.php';
exigirFarao();
$eu=duelistaAtual();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='ajuste') { gravarAjuste((string)post('chave'), (string)post('valor')); recado('Ajuste gravado.'); }
    elseif ($a==='responder') { ex('UPDATE suporte SET resposta=?, respondido_em=NOW() WHERE id=?',[(string)post('resposta'),(int)post('id')]);
        $t=q1('SELECT * FROM suporte WHERE id=?',[(int)post('id')]);
        if ($t) mandarMensagem((int)$t['duelista_id'],'Resposta do Faraó: '.$t['titulo'],(string)post('resposta'),'farao');
        recado('Respondido.'); }
    elseif ($a==='desligar') { ex('UPDATE duelistas SET ativo=1-ativo WHERE id=? AND numero<>"#YU0001"',[(int)post('id')]); }
    irPara('/farao.php');
}
$duelistas=q('SELECT d.*, (SELECT COALESCE(SUM(oneub),0) FROM carteiras WHERE duelista_id=d.id) AS total
              FROM duelistas d ORDER BY d.id');
$tickets=q('SELECT s.*, d.nome FROM suporte s JOIN duelistas d ON d.id=s.duelista_id WHERE s.resposta IS NULL ORDER BY s.id DESC');
$titulo='Faraó';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0"><?= icoFarao(26) ?><h1 style="margin:0">Painel do Faraó</h1>
  <span class="dica">Esconder o link não protege nada — a trava está aqui dentro, e é ela que vale.</span></div>

<div class="grade g4">
  <div class="cartao"><h3>Duelistas</h3><div class="valor"><?= count($duelistas) ?></div></div>
  <div class="cartao"><h3>Cartas no catálogo</h3><div class="valor"><?= num((int)qv('SELECT COUNT(*) FROM cartas',[],0)) ?></div>
    <small class="dica"><?= num((int)qv('SELECT COUNT(*) FROM cartas WHERE texto IS NOT NULL AND texto<>""',[],0)) ?> já com texto de regras</small></div>
  <div class="cartao"><h3>Duelos jogados</h3><div class="valor"><?= num((int)qv('SELECT COUNT(*) FROM duelos',[],0)) ?></div></div>
  <div class="cartao"><h3>Chamados abertos</h3><div class="valor"><?= count($tickets) ?></div></div>
</div>

<div class="titulo-secao"><h2>Ajustes do site</h2></div>
<div class="caixa">
  <form method="post" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end"><?= csrfCampo() ?>
    <input type="hidden" name="acao" value="ajuste">
    <div class="campo" style="margin:0"><label for="chave">Chave</label>
      <select id="chave" name="chave">
        <option value="tutorial_ativo">tutorial_ativo — o Indawora aparece sozinho</option>
        <option value="cadastro_aberto">cadastro_aberto</option>
        <option value="aviso_topo">aviso_topo</option>
      </select></div>
    <div class="campo" style="margin:0"><label for="valor">Valor</label><input type="text" id="valor" name="valor" value="1"></div>
    <button class="btn btn-p">Gravar</button>
  </form>
  <table class="lista" style="margin-top:var(--respiro)">
    <?php foreach (ajustes() as $k=>$v): ?><tr><td><?= e($k) ?></td><td class="dica"><?= e($v) ?></td></tr><?php endforeach; ?>
  </table>
</div>

<div class="titulo-secao"><h2>Chamados sem resposta</h2></div>
<?php if (!$tickets): ?><div class="caixa"><p class="dica" style="margin:0">Nenhum.</p></div><?php endif; ?>
<?php foreach ($tickets as $t): ?>
  <div class="caixa"><b><?= e($t['titulo']) ?></b> <small class="dica">— <?= e($t['nome']) ?>, <?= e(faz($t['criado_em'])) ?></small>
    <p style="white-space:pre-line"><?= e($t['corpo']) ?></p>
    <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="responder"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
      <div class="campo"><textarea name="resposta" rows="3" required></textarea></div>
      <button class="btn btn-p btn-principal">Responder</button></form></div>
<?php endforeach; ?>

<div class="titulo-secao"><h2>Duelistas</h2></div>
<div class="caixa" style="padding:.4rem">
  <table class="lista"><thead><tr><th>#</th><th>Nome</th><th>Nível</th><th class="num"><?= YU_MOEDA ?></th><th>Último acesso</th><th></th></tr></thead><tbody>
  <?php foreach ($duelistas as $d): ?>
    <tr><td class="dica"><?= e($d['numero']) ?></td>
      <td><?= e($d['nome']) ?> <?= ($d['papel']==='farao')?'<span class="etiqueta ouro">Faraó</span>':'' ?><?= (int)$d['ativo']?'':' <span class="etiqueta">desligado</span>' ?></td>
      <td><?= emblemaNivel(nivelDe($d),'p') ?></td>
      <td class="num"><?= num((int)$d['total']) ?></td>
      <td class="dica"><?= e(faz($d['ultimo_acesso'])) ?></td>
      <td><?php if ($d['numero']!=='#YU0001'): ?>
        <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="desligar"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
          <button class="btn btn-p"><?= (int)$d['ativo']?'Desligar':'Religar' ?></button></form><?php endif; ?></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php require YU_APP . '/layout/rodape.php';
