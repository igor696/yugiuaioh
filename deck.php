<?php
/**
 * O MONTADOR DE DECKS.
 *
 * Ele AVISA e não trava, igual ao Magic the Minas — quem decide o que
 * entra é o jogador. Só duas coisas são recusadas de verdade, e as duas
 * saem do manual (pág. 3): mais de 3 cópias da mesma carta somando os
 * três Decks, e carta que não é Fusão dentro do Deck Adicional.
 *
 * O duelo é que exige Deck válido. Aqui você pode deixar o deck pela
 * metade e voltar amanhã.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id = (int)$eu['id']; $dif = difAtual(); $d = dificuldade($dif);

$deckId = (int)get('id', 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfConfere();
    $acao = (string)post('acao');
    if ($acao === 'criar') {
        $deckId = criarDeck($id, $dif, (string)post('nome', ''));
        recado('Deck criado. Agora é só encher.');
        irPara('/deck.php?id=' . $deckId);
    }
    $deckId = (int)post('id', $deckId);
    $dk = deck($deckId, $id);
    if ($dk) {
        if ($acao === 'por') {
            $r = porNoDeck($deckId, $id, $dif, (int)post('num'), (string)post('parte', 'principal'));
            if (!empty($r['erro'])) recado($r['erro'], 'erro');
        } elseif ($acao === 'tirar') {
            tirarDoDeck($deckId, (int)post('num'), (string)post('parte', 'principal'));
        } elseif ($acao === 'ativar') {
            ativarDeck($deckId, $id, $dif);
            recado('Este é o deck que vai para a mesa.');
        } elseif ($acao === 'renomear') {
            ex('UPDATE decks SET nome = ? WHERE id = ? AND duelista_id = ?', [mb_substr(trim((string)post('nome')), 0, 40), $deckId, $id]);
        } elseif ($acao === 'apagar') {
            ex('DELETE FROM deck_cartas WHERE deck_id = ?', [$deckId]);
            ex('DELETE FROM decks WHERE id = ? AND duelista_id = ?', [$deckId, $id]);
            recado('Deck apagado.');
            irPara('/deck.php');
        } elseif ($acao === 'completar') {
            $r = completarDeck($deckId, $id, $dif);
            recado($r['postas'] > 0
                ? 'Indawora pôs ' . $r['postas'] . ' carta(s) que você já tinha.' . (!empty($r['faltam']) ? ' Ainda faltam ' . $r['faltam'] . ' — sua coleção não dá para mais.' : '')
                : 'Não sobrou carta sua para pôr. Passa na loja.', $r['postas'] > 0 ? 'ok' : 'aviso');
        }
    }
    irPara('/deck.php?id=' . $deckId . (get('b') ? '&b=' . urlencode((string)get('b')) : ''));
}

$decks = meusDecks($id, $dif);
if (!$deckId && $decks) { $deckId = (int)($decks[0]['id']); }
$dk = $deckId ? deck($deckId, $id) : null;
$conf = $dk ? conferirDeck((int)$dk['id']) : null;

$busca = trim((string)get('b', ''));
$filtroTipo = (string)get('t', '');
$pagina = max(1, (int)get('p', 1));
[$achadas, $total] = buscarCartas(
    ['busca' => $busca, 'tipo' => $filtroTipo ? [$filtroTipo] : null, 'so_minhas' => 1, 'ordem' => 'nome'],
    $pagina, 36, $id, $dif);

$titulo = 'Deck';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0">Seus decks</h1>
  <span class="etiqueta" style="color:<?= e($d['cor']) ?>"><?= e($d['nome']) ?></span>
  <span class="dica">Cada dificuldade tem os seus. Nada atravessa.</span>
</div>

<div class="linha-btn" style="margin-bottom:var(--respiro)">
  <?php foreach ($decks as $x): ?>
    <a class="btn btn-p <?= (int)$x['id'] === $deckId ? 'btn-principal' : '' ?>" href="/deck.php?id=<?= (int)$x['id'] ?>">
      <?= e($x['nome']) ?> · <?= (int)$x['n_principal'] ?><?= (int)$x['ativo'] === 1 ? ' ★' : '' ?>
    </a>
  <?php endforeach; ?>
  <form method="post" style="display:flex;gap:.4rem">
    <?= csrfCampo() ?><input type="hidden" name="acao" value="criar">
    <input type="text" name="nome" placeholder="Nome do deck novo" maxlength="40" style="width:190px">
    <button class="btn btn-p">Criar</button>
  </form>
</div>

<?php if (!$dk): ?>
  <div class="caixa centro" style="padding:2.5rem 1.5rem">
    <?php if ($m = mascote('pensando')): ?><img src="<?= e($m) ?>" alt="" width="200" height="200" style="margin:0 auto 1rem"><?php endif; ?>
    <h2>Nenhum deck nesta dificuldade ainda</h2>
    <p class="dica" style="max-width:52ch;margin:0 auto">Crie um acima. Depois clique em “Indawora, me ajuda” e eu completo com as cartas que você já tem — sem inventar nenhuma que não seja sua.</p>
  </div>
<?php else: ?>

<div class="grade" style="grid-template-columns:1fr;gap:var(--respiro)">
  <div class="caixa" style="display:grid;gap:var(--respiro);grid-template-columns:1fr">
    <div style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:center">
      <form method="post" style="display:flex;gap:.4rem;align-items:center">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="renomear"><input type="hidden" name="id" value="<?= $deckId ?>">
        <input type="text" name="nome" value="<?= e($dk['nome']) ?>" maxlength="40" style="width:220px;font-family:var(--serif);font-size:1.1rem">
        <button class="btn btn-p">Renomear</button>
      </form>
      <?php if ((int)$dk['ativo'] !== 1): ?>
        <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="ativar"><input type="hidden" name="id" value="<?= $deckId ?>">
          <button class="btn btn-p">Usar este na mesa</button></form>
      <?php else: ?><span class="etiqueta ouro">Deck da mesa</span><?php endif; ?>
      <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="completar"><input type="hidden" name="id" value="<?= $deckId ?>">
        <button class="btn btn-p btn-principal">Indawora, me ajuda</button></form>
      <form method="post" data-confirma="Apagar o deck «<?= e($dk['nome']) ?>»? Não tem volta." style="margin-left:auto">
        <?= csrfCampo() ?><input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= $deckId ?>">
        <button class="btn btn-p btn-perigo">Apagar</button></form>
    </div>

    <div class="grade g4" style="gap:.8rem">
      <div><small class="dica">Principal</small><div style="font-family:var(--serif);font-size:1.7rem;color:<?= $conf['n']['principal'] >= DECK_MIN && $conf['n']['principal'] <= DECK_MAX ? 'var(--ouro-claro)' : '#F0857E' ?>"><?= (int)$conf['n']['principal'] ?><span style="font-size:.9rem;color:var(--texto-fraco)">/40–60</span></div><?= barra(min(60,(int)$conf['n']['principal']), 60) ?></div>
      <div><small class="dica">Adicional (só Fusão)</small><div style="font-family:var(--serif);font-size:1.7rem"><?= (int)$conf['n']['adicional'] ?><span style="font-size:.9rem;color:var(--texto-fraco)">/15</span></div></div>
      <div><small class="dica">Auxiliar</small><div style="font-family:var(--serif);font-size:1.7rem"><?= (int)$conf['n']['auxiliar'] ?><span style="font-size:.9rem;color:var(--texto-fraco)">/15</span></div></div>
      <div><small class="dica">Monstros · Mágicas · Armadilhas</small>
        <div style="font-family:var(--serif);font-size:1.35rem"><?= (int)$conf['tipos']['monstro'] ?> · <?= (int)$conf['tipos']['magica'] ?> · <?= (int)$conf['tipos']['armadilha'] ?></div></div>
    </div>

    <?php /* A CURVA DE NÍVEL. É a informação que mais decide deck neste
         jogo, porque Nível é Tributo e Tributo é monstro a menos no
         campo. Desenhada em CSS, sem biblioteca de gráfico. */ ?>
    <div>
      <small class="dica">Curva de Nível dos monstros</small>
      <div style="display:flex;gap:.35rem;align-items:flex-end;height:78px;margin-top:.35rem">
        <?php $maior = max(1, max($conf['curva'])); foreach ($conf['curva'] as $nv => $q): ?>
          <div style="flex:1;text-align:center" title="Nível <?= $nv ?>: <?= $q ?> monstro(s)">
            <div style="height:<?= (int)round($q * 58 / $maior) ?>px;background:linear-gradient(180deg,var(--ouro),#8A6B1E);border-radius:3px 3px 0 0"></div>
            <small style="font-size:.68rem;color:<?= $nv >= 5 ? 'var(--carmim)' : 'var(--texto-fraco)' ?>"><?= $nv ?></small>
          </div>
        <?php endforeach; ?>
      </div>
      <small class="dica">Do 5 em diante custa Tributo (vermelho).</small>
    </div>

    <?php if ($conf['erros']): ?>
      <div class="recado erro"><b>O deck ainda não pode ir para a mesa:</b><ul style="margin:.4rem 0 0"><?php foreach ($conf['erros'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($conf['avisos']): ?>
      <div class="recado aviso"><b>O Indawora acha o seguinte — e é só palpite:</b><ul style="margin:.4rem 0 0"><?php foreach ($conf['avisos'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
  </div>
</div>

<div class="grade" style="grid-template-columns:1fr;gap:var(--respiro);margin-top:var(--respiro)">
  <!-- ------------------------------------------------- o deck montado -->
  <div class="caixa">
    <h2>No deck</h2>
    <?php foreach (['principal' => 'Deck Principal', 'adicional' => 'Deck Adicional', 'auxiliar' => 'Deck Auxiliar'] as $parte => $rot):
      $desta = array_values(array_filter($conf['cartas'], fn($c) => $c['parte'] === $parte)); ?>
      <div class="rodape-titulo mt"><?= e($rot) ?> · <?= array_sum(array_column($desta, 'quantidade')) ?></div>
      <?php if (!$desta): ?><p class="dica">Vazio.</p><?php else: ?>
        <div class="grade-cartas">
          <?php foreach ($desta as $c): ?>
            <form method="post" style="margin:0">
              <?= csrfCampo() ?><input type="hidden" name="acao" value="tirar"><input type="hidden" name="id" value="<?= $deckId ?>">
              <input type="hidden" name="num" value="<?= (int)$c['num'] ?>"><input type="hidden" name="parte" value="<?= e($parte) ?>">
              <button type="submit" class="mini" style="all:unset;cursor:pointer;display:block" title="Tirar 1 de «<?= e($c['nome']) ?>»">
                <span class="mini" style="display:block">
                  <img src="<?= e(arteCarta($c)) ?>" alt="<?= e($c['nome']) ?>" loading="lazy">
                  <span class="tipo" style="background:<?= e(corDoTipo($c['tipo'])) ?>"></span>
                  <span class="qtd">×<?= (int)$c['quantidade'] ?></span>
                </span>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <p class="dica" style="margin-top:.8rem">Clique numa carta para tirar uma cópia.</p>
  </div>

  <!-- ------------------------------------------------- a sua coleção -->
  <div class="caixa">
    <h2>Suas cartas nesta dificuldade</h2>
    <form method="get" class="filtros">
      <input type="hidden" name="id" value="<?= $deckId ?>">
      <div class="campo cresce"><label for="b">Procurar</label>
        <input type="search" id="b" name="b" value="<?= e($busca) ?>" placeholder="nome ou passcode"></div>
      <div class="campo"><label for="t">Tipo</label>
        <select id="t" name="t">
          <option value="">todos</option>
          <?php foreach (['Monster'=>'Monstro','Magic'=>'Mágica','Equip'=>'Equipamento','Field'=>'Campo','Ritual'=>'Ritual','Trap'=>'Armadilha'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $filtroTipo === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select></div>
      <button class="btn btn-p"><?= icoBusca(16) ?> Filtrar</button>
    </form>

    <?php if (!$achadas): ?>
      <p class="dica">Nenhuma carta sua bate com esse filtro. As cartas chegam por vitória, por pacote e pela banca do Sefu.</p>
    <?php else: ?>
      <div class="grade-cartas">
        <?php foreach ($achadas as $c):
          $usadas = copiasNoDeck($deckId, (int)$c['num']);
          $livres = (int)$c['tenho'] - $usadas;
          $parte = (int)$c['fusao'] === 1 ? 'adicional' : 'principal'; ?>
          <form method="post" style="margin:0">
            <?= csrfCampo() ?><input type="hidden" name="acao" value="por"><input type="hidden" name="id" value="<?= $deckId ?>">
            <input type="hidden" name="num" value="<?= (int)$c['num'] ?>"><input type="hidden" name="parte" value="<?= $parte ?>">
            <button type="submit" style="all:unset;cursor:pointer;display:block" <?= $livres <= 0 ? 'disabled' : '' ?>
                    title="<?= e($c['nome']) ?> — <?= $livres ?> livre(s) de <?= (int)$c['tenho'] ?>">
              <span class="mini <?= $livres <= 0 ? 'nao-tenho' : '' ?>" style="display:block">
                <img src="<?= e(arteCarta($c)) ?>" alt="<?= e($c['nome']) ?>" loading="lazy">
                <span class="tipo" style="background:<?= e(corDoTipo($c['tipo'])) ?>"></span>
                <span class="qtd"><?= $livres ?>/<?= (int)$c['tenho'] ?></span>
              </span>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
      <?php $paginas = (int)ceil($total / 36); if ($paginas > 1): ?>
        <div class="linha-btn centro" style="margin-top:var(--respiro);justify-content:center">
          <?php for ($i = 1; $i <= min(12, $paginas); $i++): ?>
            <a class="btn btn-p <?= $i === $pagina ? 'btn-principal' : '' ?>"
               href="/deck.php?id=<?= $deckId ?>&amp;b=<?= urlencode($busca) ?>&amp;t=<?= e($filtroTipo) ?>&amp;p=<?= $i ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require YU_APP . '/layout/rodape.php';
