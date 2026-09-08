<?php
require __DIR__ . '/../app/bootstrap.php';
if (estaLogado()) irPara('/painel.php');

$erro = null; $nome = ''; $codigo = (string)get('codigo', ''); $avatar = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfConfere();
    $nome   = (string)post('nome', '');
    $codigo = (string)post('codigo', '');
    $avatar = (int)post('avatar', 1);
    $senha  = (string)post('senha', '');
    if ($senha !== (string)post('senha2', '')) {
        $erro = 'As duas senhas não são iguais.';
    } else {
        $r = criarDuelista($nome, $senha, $codigo, $avatar);
        if (!empty($r['ok'])) {
            entrar($nome, $senha);
            conferirConquistas((int)$r['id']);
            $rel = reliquiasTodas()[$r['reliquia']];
            recado('Duelista criado. Você começou com o ' . $rel['nome'] . ' — o sorteio escolheu por você.');
            irPara('/painel.php');
        }
        $erro = $r['erro'];
    }
}
$titulo = 'Criar duelista'; $paginaEstreita = true;
require YU_APP . '/layout/topo.php';
?>
<h1>Criar meu duelista</h1>
<p class="dica">Sem e-mail. O que você precisa é de um nome, uma senha e o código de convite de quem já está aqui.</p>

<?php if ($erro): ?><div class="recado erro"><?= e($erro) ?></div><?php endif; ?>

<div class="grade g2" style="align-items:start">
  <form method="post" class="caixa caixa-ouro">
    <?= csrfCampo() ?>
    <div class="campo">
      <label for="nome">Nome de duelista</label>
      <input type="text" id="nome" name="nome" value="<?= e($nome) ?>" maxlength="32" required autofocus autocomplete="username">
      <small class="dica">De 3 a 24 letras. É com ele que você entra.</small>
    </div>
    <div class="campo">
      <label for="codigo">Código de convite</label>
      <input type="text" id="codigo" name="codigo" value="<?= e($codigo) ?>" placeholder="UAI-XXXXXX" required style="text-transform:uppercase">
    </div>
    <div class="campo">
      <label for="senha">Senha</label>
      <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password">
    </div>
    <div class="campo">
      <label for="senha2">Repita a senha</label>
      <input type="password" id="senha2" name="senha2" required minlength="8" autocomplete="new-password">
    </div>
    <input type="hidden" name="avatar" id="avatar-escolhido" value="<?= (int)$avatar ?>">
    <button class="btn btn-principal btn-largo" type="submit">Criar duelista</button>
    <p class="dica centro" style="margin:.8rem 0 0"><a href="/index.php">Já tenho conta</a></p>
  </form>

  <div class="caixa">
    <h2>Escolha um rosto</h2>
    <p class="dica">Cinco vêm abertos. Os outros dez você libera jogando — por nível, por conquista e por chefão derrotado.</p>
    <div class="grade" style="grid-template-columns:repeat(auto-fill,minmax(84px,1fr));gap:.6rem">
      <?php foreach (avatares() as $n => $a):
        $livre = $a['trava'] === '';
        $img = '/assets/img/avatares/avatar-' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . '.png'; ?>
        <button type="button" class="escolha-avatar" data-n="<?= $n ?>" <?= $livre ? '' : 'disabled' ?>
                title="<?= e($a['nome']) . ($livre ? '' : ' — libera jogando') ?>"
                style="all:unset;cursor:<?= $livre ? 'pointer' : 'default' ?>;border-radius:50%;overflow:hidden;
                       border:2px solid <?= $livre ? 'var(--borda-viva)' : 'var(--borda)' ?>;aspect-ratio:1;display:block;
                       opacity:<?= $livre ? '1' : '.32' ?>;filter:<?= $livre ? 'none' : 'grayscale(1)' ?>">
          <?php if (temArte($img)): ?><img src="<?= $img ?>" alt="<?= e($a['nome']) ?>" loading="lazy">
          <?php else: ?><span style="display:grid;place-items:center;height:100%"><?= $n ?></span><?php endif; ?>
        </button>
      <?php endforeach; ?>
    </div>
    <p class="dica" style="margin-top:.8rem">
      Assim que a conta existir, uma das seis relíquias do Milênio é sorteada para você.
      O Enigma nunca sai no sorteio — ele só se junta no fim da campanha.
    </p>
  </div>
</div>

<script>
document.querySelectorAll('.escolha-avatar').forEach(function(b){
  b.addEventListener('click', function(){
    document.querySelectorAll('.escolha-avatar').forEach(function(x){ x.style.borderColor='var(--borda-viva)'; x.style.boxShadow='none'; });
    b.style.borderColor='var(--ouro)'; b.style.boxShadow='0 0 0 3px rgba(212,175,55,.28)';
    document.getElementById('avatar-escolhido').value = b.dataset.n;
  });
});
document.querySelector('.escolha-avatar')?.click();
</script>
<?php require YU_APP . '/layout/rodape.php';
