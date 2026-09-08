<?php
/** Rodapé. Muda conforme o duelista está dentro ou fora. */
$euR = duelistaAtual();
?>
</main>

<div class="lupa" id="lupa" hidden><img src="" alt=""></div>

<?php if ($euR): ?>
<footer class="rodape rodape-dentro">
  <div class="rodape-caixa">

    <nav class="rodape-menu" aria-label="Menu do rodapé">
      <div class="rodape-titulo">Ir para</div>
      <ul>
        <li><a href="/painel.php">Painel</a></li>
        <li><a href="/campanha.php">Campanha</a></li>
        <li><a href="/deck.php">Deck</a></li>
        <li><a href="/cartas.php">As 722</a></li>
        <li><a href="/loja.php">Loja</a></li>
        <li><a href="/cofre.php">Cofre</a></li>
        <li><a href="/conquistas.php">Conquistas</a></li>
        <li><a href="/salvos.php">Jogos salvos</a></li>
        <li><a href="/conta.php">Meu perfil</a></li>
      </ul>

      <div class="rodape-titulo mt">Ajuda</div>
      <ul>
        <?php /* O MANUAL fica no rodapé de quem entrou, e só dele — como
             foi combinado. O botão está preparado para chamar o mascote:
             o tutorial.js intercepta o clique e o Indawora conduz na
             própria tela. O endereço é a rede de segurança quando o
             script não sobe. */ ?>
        <li><a href="/manual.php"><b>Manual</b></a></li>
        <li><a href="/manual.php?tutorial=1" data-tutorial>Tutorial com o Indawora</a></li>
        <li><a href="/resgatar.php">Resgatar código</a></li>
        <li><a href="/suporte.php">Suporte</a></li>
      </ul>
    </nav>

    <div class="rodape-texto">
      <p><strong>Yu Gi Uai Oh</strong> · versão <?= YU_VERSAO ?></p>
      <p>Regras conforme o manual em português produzido para este projeto.
         Dados e artes das cartas por <a href="https://ygoprodeck.com" target="_blank" rel="noopener">YGOPRODeck</a>.</p>
      <p class="fraco">Yu-Gi-Oh! é marca dos respectivos titulares. Este é um projeto de fã,
         sem vínculo, apoio ou licença oficial.</p>
      <p class="fraco">
        <strong><?= YU_MOEDA ?></strong> não é vendido, não é comprado e não vale nada fora deste site.
        Aqui não se paga nada com dinheiro de verdade.
      </p>
      <div class="rodape-estudio">
        <a class="botao-quem-somos" href="/quem-somos.php">Quem somos?</a>
        <span class="rodape-estudio-dica">Feito por With Six Are Studios</span>
      </div>
    </div>

  </div>
</footer>

<?php else: ?>
<footer class="rodape rodape-fora">
  <div class="rodape-fora-caixa">
    <img class="rodape-logo" src="/assets/img/logo/logo-mini.png" alt="" width="64" height="64">
    <div>
      <p><strong>Yu Gi Uai Oh</strong> · versão <?= YU_VERSAO ?></p>
      <p class="fraco">Regras conforme o manual em português produzido para este projeto.
         Dados e artes das cartas por <a href="https://ygoprodeck.com" target="_blank" rel="noopener">YGOPRODeck</a>.
         Yu-Gi-Oh! é marca dos respectivos titulares — projeto de fã, sem vínculo oficial.</p>
      <p class="fraco">Projeto gratuito: não se compra nada com dinheiro de verdade.</p>
    </div>
    <div class="rodape-estudio">
      <a class="botao-quem-somos" href="/quem-somos.php">Quem somos?</a>
      <span class="rodape-estudio-dica">Feito por With Six Are Studios</span>
    </div>
  </div>
</footer>
<?php endif; ?>

<script src="/assets/js/app.js?v=<?= YU_VERSAO ?>"></script>
<?php if ($euR): ?>
<script>window.__yuTutorial = <?= ajusteLigado('tutorial_ativo', true) ? 1 : 0 ?>;</script>
<script src="/assets/js/tutorial.js?v=<?= YU_VERSAO ?>" defer></script>
<?php endif; ?>
<?php if (!empty($jsExtra)): foreach ((array)$jsExtra as $j): ?>
<script src="/assets/js/<?= e($j) ?>?v=<?= YU_VERSAO ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>
