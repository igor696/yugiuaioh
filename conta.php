<?php
/** MEU PERFIL: rosto, senha, relíquias equipadas, convites e correio. */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu=duelistaAtual(); $id=(int)$eu['id']; $nivel=nivelDe($eu);
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='senha') {
        global $CFG; $min=(int)($CFG['seguranca']['senha_min']??8);
        if (!password_verify((string)post('atual'), (string)$eu['senha_hash'])) recado('A senha atual não confere.','erro');
        elseif (mb_strlen((string)post('nova')) < $min) recado("A nova senha precisa de $min caracteres.",'erro');
        elseif (post('nova') !== post('nova2')) recado('As duas novas não são iguais.','erro');
        else { ex('UPDATE duelistas SET senha_hash=? WHERE id=?',[password_hash((string)post('nova'),PASSWORD_DEFAULT),$id]); recado('Senha trocada.'); }
    } elseif ($a==='reliquia') {
        $r=equiparReliquia($id,(string)post('slug'),$nivel);
        recado($r['erro'] ?? ('Relíquia ' . ($r['estado']==='dentro'?'equipada':'retirada') . '.'), isset($r['erro'])?'erro':'ok');
    } elseif ($a==='convite') { $c=gerarConvite($id); recado("Código novo: $c"); }
    elseif ($a==='ler') { marcarLida((int)post('id'),$id); }
    irPara('/conta.php');
}
$titulo='Meu perfil';
require YU_APP . '/layout/topo.php';
$equip=reliquiasEquipadas($id);
?>
<div class="grade g2" style="align-items:start">
  <div>
    <div class="caixa caixa-ouro centro">
      <div style="width:130px;height:130px;border-radius:50%;overflow:hidden;border:3px solid var(--ouro);margin:0 auto 1rem;background:var(--painel-2)">
        <?php $f=avatarUrl($eu); if ($f): ?><img src="<?= e($f) ?>" alt=""><?php endif; ?>
      </div>
      <h1 style="margin:0"><?= e($eu['nome']) ?></h1>
      <p class="dica" style="margin:.1rem 0 .6rem"><?= e($eu['numero']) ?> · desde <?= e(dataBr($eu['criado_em'],false)) ?></p>
      <?= emblemaNivel($nivel,'gg') ?>
      <p style="font-family:var(--cond);letter-spacing:.1em;text-transform:uppercase;color:var(--ouro);margin:.4rem 0 0"><?= e(tituloNivel($nivel)) ?></p>
      <p class="dica"><a href="/loja.php?aba=avatar">Trocar de rosto na banca da Bastet-Mina</a></p>
    </div>

    <div class="caixa" id="reliquias">
      <h2>Relíquias equipadas</h2>
      <p class="dica">Você tem <b><?= encaixes($nivel) ?></b> encaixe(s). A 2ª abre no nível 12, a 3ª no 24. Uma relíquia por duelo, e só na campanha.</p>
      <div style="display:flex;gap:.8rem;flex-wrap:wrap;margin-top:.6rem">
        <?php foreach (minhasReliquias($id) as $pr): $slug=$pr['reliquia']; $r=reliquiasTodas()[$slug];
          $img='/assets/img/reliquias/'.$r['arquivo'].'.png'; $on=in_array($slug,$equip,true); ?>
          <form method="post" style="text-align:center;width:120px"><?= csrfCampo() ?>
            <input type="hidden" name="acao" value="reliquia"><input type="hidden" name="slug" value="<?= e($slug) ?>">
            <button style="all:unset;cursor:pointer;display:block;width:100%" title="<?= e($r['descricao'].' — '.$r['quando']) ?>">
              <span style="display:grid;place-items:center;width:96px;height:96px;margin:0 auto;border-radius:50%;
                     border:2px solid <?= $on?'var(--ouro)':'var(--borda)' ?>;background:radial-gradient(circle at 50% 35%,#33264F,#150F20);
                     <?= $on?'box-shadow:0 0 20px -4px rgba(212,175,55,.75)':'' ?>">
                <?php if (temArte($img)): ?><img src="<?= $img ?>" alt="" style="width:76%"><?php endif; ?>
              </span>
              <small style="display:block;margin-top:.3rem;line-height:1.2"><?= e($r['nome']) ?></small>
              <small class="dica"><?= $on?'equipada':'clique para equipar' ?></small>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
      <?php if (!tenhoReliquia($id,'enigma')): ?>
        <p class="dica" style="margin-top:.8rem">Peças do Enigma: <b><?= pecasDoEnigma($id) ?> de 6</b> — cada chefão do Muito Difícil entrega uma.</p>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="caixa">
      <h2>Correio</h2>
      <?php $msgs=minhasMensagens($id,25); if (!$msgs): ?><p class="dica">Nenhuma mensagem.</p><?php endif; ?>
      <?php foreach ($msgs as $m): ?>
        <details style="border-bottom:1px solid var(--borda);padding:.55rem 0" <?= $m['lida_em']?'':'open' ?>>
          <summary style="cursor:pointer;display:flex;gap:.5rem;align-items:center">
            <?= $m['tipo']==='mascote'?icoOlho(16):icoMensagem(16) ?>
            <b style="font-family:var(--serif)"><?= e($m['titulo']) ?></b>
            <small class="dica" style="margin-left:auto"><?= e(faz($m['criado_em'])) ?></small>
          </summary>
          <p style="white-space:pre-line;margin:.5rem 0 .3rem"><?= e($m['corpo']) ?></p>
          <?php if (!$m['lida_em']): ?>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="ler"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
              <button class="btn btn-p">Marcar como lida</button></form>
          <?php endif; ?>
        </details>
      <?php endforeach; ?>
    </div>

    <div class="caixa">
      <h2>Convites</h2>
      <p class="dica">O cadastro é fechado. Passe um código para quem você quiser trazer.</p>
      <table class="lista">
        <?php foreach (meusConvites($id) as $c): ?>
          <tr><td style="font-family:var(--cond);letter-spacing:.08em"><?= e($c['codigo']) ?></td>
              <td class="dica"><?= $c['usado_por'] ? 'usado por ' . e($c['nome_usado']) : 'livre' ?></td></tr>
        <?php endforeach; ?>
      </table>
      <form method="post" style="margin-top:.7rem"><?= csrfCampo() ?><input type="hidden" name="acao" value="convite">
        <button class="btn btn-p">Gerar mais um</button></form>
    </div>

    <div class="caixa">
      <h2>Trocar a senha</h2>
      <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="senha">
        <div class="campo"><label for="atual">Senha atual</label><input type="password" id="atual" name="atual" required></div>
        <div class="campo"><label for="nova">Nova</label><input type="password" id="nova" name="nova" required minlength="8"></div>
        <div class="campo"><label for="nova2">Repita</label><input type="password" id="nova2" name="nova2" required minlength="8"></div>
        <button class="btn btn-principal">Trocar</button>
      </form>
    </div>
  </div>
</div>
<?php require YU_APP . '/layout/rodape.php';
