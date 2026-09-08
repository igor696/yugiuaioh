<?php
/** O hub do DUELO: as três portas — campanha, LAN e online. */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id = (int)$eu['id']; $dif = difAtual();
$titulo = 'Duelo';
require YU_APP . '/layout/topo.php';
?>
<h1>Onde você quer duelar?</h1>
<p class="dica">Conquista e progresso só contam na campanha. LAN e online são para jogar com gente — e ninguém consegue provar quem ganhou, então nada disso vira nível.</p>

<div class="grade g3" style="margin-top:var(--respiro)">
  <div class="cartao caixa-ouro">
    <div class="cartao-topo"><?= icoCampanha(22) ?><h3>Campanha</h3></div>
    <p style="margin:.3rem 0">150 duelistas, 5 cidades, 6 chefões e 7 relíquias. É aqui que a história acontece e é a única porta que conta conquista.</p>
    <p class="dica"><?= quantosVencidos($id,$dif) ?>/30 nesta dificuldade.</p>
    <div class="rodape-cartao"><a class="btn btn-principal btn-largo" href="/campanha.php">Entrar na campanha</a></div>
  </div>
  <div class="cartao">
    <div class="cartao-topo"><?= icoLan(22) ?><h3>LAN</h3></div>
    <p style="margin:.3rem 0">Duas máquinas na mesma rede de casa, sem passar pela internet. Uma abre a sala, a outra entra pelo código.</p>
    <div class="rodape-cartao"><a class="btn btn-largo" href="/lan.php">Abrir sala local</a></div>
  </div>
  <div class="cartao">
    <div class="cartao-topo"><?= icoOnline(22) ?><h3>Online</h3></div>
    <p style="margin:.3rem 0">Contra outro duelista cadastrado, pela internet. A sua mão fica no servidor — o outro lado nunca a recebe.</p>
    <div class="rodape-cartao"><a class="btn btn-largo" href="/online.php">Ver salas abertas</a></div>
  </div>
</div>
<?php require YU_APP . '/layout/rodape.php';
