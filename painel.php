<?php
/**
 * O PAINEL.
 *
 * Três faixas, e nada mais: quem você é (com o Indawora falando o que
 * cabe no seu nível), onde você parou na campanha, e os quatro atalhos
 * que valem — cofre, loja, conquistas e correio.
 *
 * A troca de dificuldade fica aqui em cima, visível, porque ela troca o
 * site inteiro de contexto e não pode ficar escondida num menu.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual();
$id = (int)$eu['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('acao') === 'dificuldade') {
    csrfConfere();
    trocarDificuldade((int)post('d', 1));
    irPara('/painel.php');
}

$dif   = difAtual();
$d     = dificuldade($dif);
$nivel = nivelDe($eu);
conferirConquistas($id);
$novas = conquistasNovas($id);

$vencidosN = quantosVencidos($id, $dif);
$chefe     = chefeDaDificuldade($dif);
$liberado  = chefeLiberado($id, $dif);
$deck      = deckAtivo($id, $dif);
$conf      = $deck ? conferirDeck((int)$deck['id']) : null;
$minhas    = minhasReliquias($id);
$equipadas = reliquiasEquipadas($id);
$prox      = null;
foreach (oponentesDaDificuldade($dif) as $o) {
    if (!in_array((int)$o['n'], vencidos($id, $dif), true)) { $prox = $o; break; }
}

$titulo = 'Painel';
require YU_APP . '/layout/topo.php';
?>

<!-- ============================================= FAIXA 1 — QUEM VOCÊ É -->
<section class="painel-saudacao">
  <?php if ($m = mascote($novas ? 'reliquia' : 'parado')): ?>
    <div class="saud-mascote"><img src="<?= e($m) ?>" alt="" width="320" height="320"></div>
  <?php endif; ?>

  <div class="balao">
    <?php if ($novas): ?>
      <b>Conquista nova.</b> <?= e(conquistas()[(int)$novas[0]['conquista']]['nome']) ?> —
      você subiu para o nível <?= (int)$novas[0]['conquista'] ?>, <?= e(tituloNivel((int)$novas[0]['conquista'])) ?>.
      <?php marcarConquistasVistas($id); ?>
    <?php else: ?>
      <?= e(mensagemDoNivel($nivel)) ?>
    <?php endif; ?>
    <span class="assina">Indawora, o Duelista</span>
  </div>

  <div class="saud-eu">
    <div class="foto">
      <?php $f = avatarUrl($eu); ?>
      <?php if ($f): ?><img src="<?= e($f) ?>" alt=""><?php else: ?><span style="font-family:var(--cond);font-size:1.6rem;color:var(--ouro)"><?= e(iniciais($eu)) ?></span><?php endif; ?>
    </div>
    <div class="nome"><?= e($eu['nome']) ?></div>
    <div class="num"><?= e($eu['numero']) ?></div>
    <?= emblemaNivel($nivel, 'g') ?>
    <div class="titulo-nivel"><?= e(tituloNivel($nivel)) ?></div>
    <?php $p = proximaAConcluir($id); ?>
    <?= barra(min(24, $nivel), 24, 'turquesa') ?>
    <small class="dica"><?= $p <= 24 ? 'Próxima: ' . e(conquistas()[$p]['nome']) : 'As 24 concluídas.' ?></small>
  </div>
</section>

<!-- =========================================== A TROCA DE DIFICULDADE -->
<form method="post" class="caixa" style="margin-top:var(--respiro)">
  <?= csrfCampo() ?><input type="hidden" name="acao" value="dificuldade">
  <div class="titulo-secao" style="margin-top:0">
    <h2>Você está jogando o <span style="color:<?= e($d['cor']) ?>"><?= e(mb_strtoupper($d['nome'])) ?></span></h2>
    <span class="dica">Coleção, decks, carteira e progresso são separados por dificuldade. Nada atravessa.</span>
  </div>
  <div class="trilha">
    <?php foreach (dificuldades() as $i => $dd):
      $selo = '/assets/img/selos/dif-' . $i . '.png';
      $vc = quantosVencidos($id, $i); ?>
      <button type="submit" name="d" value="<?= $i ?>" class="cidade <?= $i === $dif ? 'ativa' : '' ?>"
              style="color:<?= e($dd['cor']) ?>;cursor:pointer">
        <?php if (temArte($selo)): ?><img src="<?= $selo ?>" alt="" width="30" height="30"><?php endif; ?>
        <span>
          <?= e($dd['nome']) ?>
          <span class="n" style="display:block;color:var(--texto-fraco)"><?= $vc ?>/30 vencidos</span>
        </span>
      </button>
    <?php endforeach; ?>
  </div>
</form>

<!-- =========================================== FAIXA 2 — CONTINUAR -->
<div class="grade g2" style="margin-top:var(--respiro)">
  <div class="cartao cartao-campanha">
    <?php
      $arteProx = $prox ? arteOponente($dif, $prox, true) : '';
      if ($liberado && $vencidosN >= 30) { $arteProx = arte('/assets/img/chefoes/rosto/' . chefoes()[$chefe]['arquivo'] . '.png'); }
    ?>
    <div class="retrato">
      <?php if ($arteProx): ?><img src="<?= e($arteProx) ?>" alt="">
      <?php else: ?><div class="sem-arte" style="height:100%"><span style="font-family:var(--serif);font-size:2rem;color:var(--borda-viva);display:grid;place-items:center;height:100%">?</span></div><?php endif; ?>
    </div>
    <div>
      <div class="cartao-topo"><?= icoCampanha(20) ?><h3>Continuar a campanha</h3></div>
      <p style="margin:.2rem 0 .4rem;font-family:var(--serif);font-size:1.25rem">
        <?= e($d['cidade']) ?>
      </p>
      <p class="dica" style="margin-bottom:.5rem">
        <?php if ($prox): ?>
          Próximo da fila: <strong><?= e($prox['nome']) ?></strong> — <?= e($prox['eixo']) ?>.
        <?php elseif ($liberado): ?>
          Os trinta caíram. Só falta <strong><?= e(chefoes()[$chefe]['nome']) ?></strong>.
        <?php else: ?>
          Ainda faltam <?= 20 - $vencidosN ?> vitórias para o chefão aparecer.
        <?php endif; ?>
      </p>
      <?= barra($vencidosN, 30) ?>
      <small class="dica"><?= $vencidosN ?> de 30 duelistas vencidos nesta cidade<?= $liberado ? ' · chefão liberado' : '' ?></small>
    </div>
    <div style="display:flex;flex-direction:column;gap:.5rem;min-width:190px">
      <a class="btn btn-principal" href="/campanha.php">Ir para a campanha</a>
      <?php if ($conf && !$conf['valido']): ?>
        <a class="btn btn-perigo" href="/deck.php">Seu deck não está válido</a>
      <?php elseif (!$deck): ?>
        <a class="btn btn-perigo" href="/deck.php">Monte um deck primeiro</a>
      <?php else: ?>
        <a class="btn" href="/deck.php"><?= e($deck['nome']) ?> · <?= (int)$conf['n']['principal'] ?> cartas</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ================================= FAIXA 3 — RELÍQUIAS E OS ATALHOS -->
<div class="titulo-secao">
  <h2>Suas relíquias</h2>
  <span class="dica"><?= count($equipadas) ?> de <?= encaixes($nivel) ?> encaixe(s) em uso · a 2ª abre no nível 12, a 3ª no 24</span>
</div>
<div class="caixa">
  <div style="display:flex;gap:.9rem;flex-wrap:wrap">
    <?php foreach (reliquiasTodas() as $slug => $r):
      $tem = tenhoReliquia($id, $slug);
      $eq  = in_array($slug, $equipadas, true);
      $img = '/assets/img/reliquias/' . $r['arquivo'] . ($tem ? '' : '-bloqueada') . '.png'; ?>
      <div style="text-align:center;width:110px" title="<?= e($r['nome'] . ' — ' . $r['descricao']) ?>">
        <div style="width:88px;height:88px;margin:0 auto;border-radius:50%;display:grid;place-items:center;
                    border:2px solid <?= $eq ? 'var(--ouro)' : ($tem ? 'var(--borda-viva)' : 'var(--borda)') ?>;
                    background:radial-gradient(circle at 50% 35%,#33264F,#150F20);
                    <?= $eq ? 'box-shadow:0 0 18px -4px rgba(212,175,55,.7)' : '' ?>">
          <?php if (temArte($img)): ?><img src="<?= $img ?>" alt="" style="width:76%;<?= $tem ? '' : 'opacity:.45' ?>">
          <?php else: ?><span style="color:var(--borda-viva)"><?= icoReliquia(30) ?></span><?php endif; ?>
        </div>
        <small style="display:block;margin-top:.3rem;line-height:1.2;<?= $tem ? '' : 'color:var(--texto-fraco)' ?>">
          <?= e($tem ? $r['nome'] : '???') ?>
        </small>
      </div>
    <?php endforeach; ?>
  </div>
  <p class="dica" style="margin:.9rem 0 0">
    Uma relíquia por duelo, e todas valem só na campanha.
    <?php $pecas = pecasDoEnigma($id); if (!tenhoReliquia($id, 'enigma')): ?>
      Peças do Enigma: <strong><?= $pecas ?> de 6</strong> — cada chefão do Muito Difícil entrega uma.
    <?php endif; ?>
    <a href="/conta.php#reliquias">Trocar o que está equipado</a>.
  </p>
</div>

<div class="grade g4" style="margin-top:var(--respiro)">
  <div class="cartao">
    <div class="cartao-topo"><?= icoCofre(20) ?><h3>Cofre</h3></div>
    <div class="valor"><?= num(saldo($id, $dif)) ?></div>
    <small class="dica"><?= YU_MOEDA ?> no <?= e($d['nome']) ?> · <?= num(saldoTotal($id)) ?> somando tudo</small>
    <div class="rodape-cartao"><a class="btn btn-p btn-largo" href="/cofre.php">Abrir o cofre</a></div>
  </div>

  <div class="cartao">
    <div class="cartao-topo"><?= icoLoja(20) ?><h3>Loja</h3></div>
    <div class="valor"><?= num(boostersFechados($id, $dif)) ?></div>
    <small class="dica">pacote(s) fechado(s) nesta dificuldade · a banca do Sefu troca a cada 24 h</small>
    <div class="rodape-cartao"><a class="btn btn-p btn-largo" href="/loja.php">Ir ao bazar</a></div>
  </div>

  <div class="cartao">
    <div class="cartao-topo"><?= icoConquista(20) ?><h3>Conquistas</h3></div>
    <div class="valor"><?= count(minhasConquistas($id)) ?><span style="font-size:1rem;color:var(--texto-fraco)">/24</span></div>
    <small class="dica">Só valem na campanha</small>
    <div class="rodape-cartao"><a class="btn btn-p btn-largo" href="/conquistas.php">Ver o quadro</a></div>
  </div>

  <div class="cartao">
    <div class="cartao-topo"><?= icoCarta(20) ?><h3>Coleção</h3></div>
    <div class="valor"><?= num(quantasTenho($id, $dif)) ?><span style="font-size:1rem;color:var(--texto-fraco)">/722</span></div>
    <small class="dica">cartas distintas no <?= e($d['nome']) ?></small>
    <div class="rodape-cartao"><a class="btn btn-p btn-largo" href="/cartas.php">Ver a biblioteca</a></div>
  </div>
</div>

<?php require YU_APP . '/layout/rodape.php';
