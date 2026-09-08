<?php
require __DIR__ . '/../app/bootstrap.php';
$codigo = (int)get('c', 404);
http_response_code(in_array($codigo, [403,404,500], true) ? $codigo : 404);
$titulo = 'Erro ' . $codigo; $paginaEstreita = true;
require YU_APP . '/layout/topo.php';
$textos = [
  403 => ['Esta porta não é sua', 'O Faraó guarda esta sala. Se você acha que deveria entrar, fale com quem te convidou.'],
  404 => ['Não achei essa página', 'O endereço não existe, ou existia e foi embora. Volte pelo menu — ele leva a tudo.'],
  500 => ['Deu ruim do lado de cá', 'O erro foi registrado. Tente de novo em alguns minutos; se insistir, abra um chamado no Suporte.'],
];
[$t, $p] = $textos[$codigo] ?? $textos[404];
?>
<div class="caixa caixa-ouro centro" style="padding:3rem 1.5rem">
  <?php if ($m = mascote('pensando')): ?><img src="<?= e($m) ?>" alt="" width="220" height="220" style="margin:0 auto 1rem"><?php endif; ?>
  <h1><?= e($t) ?></h1>
  <p class="dica" style="max-width:46ch;margin:0 auto 1.2rem"><?= e($p) ?></p>
  <a class="btn btn-principal" href="/">Voltar ao começo</a>
</div>
<?php require YU_APP . '/layout/rodape.php';
