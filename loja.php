<?php
/**
 * O BAZAR DE TEBAS — as oito bancas.
 * A aba escolhe o mercador; cada um vende uma coisa só, e nenhum deles
 * aceita dinheiro de verdade.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id=(int)$eu['id']; $dif=difAtual(); $d=dificuldade($dif);
$aba = (string)get('aba','cartas');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere();
    $acao = (string)post('acao');
    if ($acao==='carta')   { $r = comprarCarta_($id,$dif,(int)post('num'));   recado($r['erro'] ?? ('Comprada: ' . $r['carta']['nome'] . '.'), isset($r['erro'])?'erro':'ok'); }
    elseif ($acao==='booster'){ $r = comprarBooster($id,$dif,(string)post('colecao')); recado($r['erro'] ?? 'Pacote comprado. Abra no Cofre ou aqui mesmo.', isset($r['erro'])?'erro':'ok'); }
    elseif ($acao==='item') { $r = comprarItem($id,$dif,(string)post('aba'),(string)post('slug')); recado($r['erro'] ?? ('Comprado: ' . $r['item']['nome'] . '.'), isset($r['erro'])?'erro':'ok'); }
    elseif ($acao==='avatar'){
        $n=(int)post('n');
        if (avatarLiberado($n,$eu)) { ex('UPDATE duelistas SET avatar=? WHERE id=?',[$n,$id]); recado('Rosto trocado.'); }
        else recado('Esse avatar ainda não está liberado.','erro');
    }
    elseif ($acao==='abrir') { $r = abrirBooster($id,(int)post('id'));
        if (!empty($r['erro'])) recado($r['erro'],'erro');
        else { $_SESSION['booster_aberto']=$r; irPara('/booster.php'); } }
    irPara('/loja.php?aba='.urlencode($aba));
}

$titulo='Loja'; $jsExtra=[];
require YU_APP . '/layout/topo.php';
$mercs = mercadores();
$atual = null; foreach ($mercs as $slug=>$m) { if ($m['aba']===$aba) { $atual=[$slug,$m]; break; } }
if (!$atual) { $atual=['sefu',$mercs['sefu']]; $aba='cartas'; }
[$slugM,$merc]=$atual;
$rosto = arte('/assets/img/mercadores/rosto/merc-'.$slugM.'-rosto.png');
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0">Bazar de Tebas</h1>
  <span class="etiqueta" style="color:<?= e($d['cor']) ?>"><?= e($d['nome']) ?></span>
  <span class="dica">Saldo: <b class="ouro"><?= num(saldo($id,$dif)) ?></b> <?= YU_MOEDA ?> — só desta dificuldade.</span>
</div>

<div class="abas">
  <?php foreach ($mercs as $slug=>$m): $r = arte('/assets/img/mercadores/rosto/merc-'.$slug.'-rosto.png'); ?>
    <a class="aba <?= $m['aba']===$aba?'ativa':'' ?>" href="<?= $m['aba']==='cofre'?'/cofre.php':'/loja.php?aba='.$m['aba'] ?>">
      <?php if ($r): ?><img src="<?= e($r) ?>" alt=""><?php endif; ?><span><?= e($m['vende']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="mercador-cabeca">
  <?php if ($rosto): ?><div class="retrato"><img src="<?= e($rosto) ?>" alt=""></div><?php endif; ?>
  <div>
    <h2 style="margin:0 0 .2rem"><?= e($merc['nome']) ?></h2>
    <p class="fala" style="margin:0">“<?= e($merc['fala']) ?>”</p>
  </div>
</div>

<?php if ($aba==='cartas'): $vitrine=vitrineDoSefu($dif); ?>
  <p class="dica">A banca tem 10 cartas e é a mesma para todo mundo até a virada do dia. Troca em
     <b data-conta="<?= segundosParaTrocarVitrine() ?>">…</b>. Atualizar a página não sorteia outra.</p>
  <div class="grade-cartas" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr))">
    <?php foreach ($vitrine as $c): $tenho=tenhoQuantas($id,$dif,(int)$c['num']); ?>
      <div style="text-align:center">
        <div class="mini" data-lupa="<?= e(arteCarta($c)) ?>" title="<?= e($c['nome']) ?>">
          <img src="<?= e(arteCarta($c)) ?>" alt="<?= e($c['nome']) ?>" loading="lazy">
          <span class="tipo" style="background:<?= e(corDoTipo($c['tipo'])) ?>"></span>
          <?php if ($tenho): ?><span class="qtd">×<?= $tenho ?></span><?php endif; ?>
        </div>
        <div style="font-size:.8rem;margin:.3rem 0 .2rem;line-height:1.2"><?= e(corta($c['nome'],26)) ?></div>
        <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="carta"><input type="hidden" name="num" value="<?= (int)$c['num'] ?>">
          <button class="btn btn-p btn-largo" <?= $tenho>=3?'disabled':'' ?>><?= $tenho>=3 ? 'no limite' : num((int)$c['preco']).' '.YU_MOEDA ?></button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($aba==='booster'): $meus = meusBoosters($id,$dif); ?>
  <?php if ($meus): ?>
    <div class="caixa caixa-ouro" style="margin-bottom:var(--respiro)">
      <h3>Você tem <?= count($meus) ?> pacote(s) fechado(s)</h3>
      <div class="linha-btn">
        <?php foreach ($meus as $b): ?>
          <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="abrir"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
            <button class="btn btn-principal"><?= e(colecoes()[$b['colecao']]['nome'] ?? $b['colecao']) ?> — abrir</button></form>
        <?php endforeach; ?>
      </div>
      <small class="dica">O sorteio já aconteceu no servidor. A carta que você ainda não virou não está no seu navegador.</small>
    </div>
  <?php endif; ?>
  <div class="grade-boosters">
    <?php foreach (colecoes() as $slug=>$col): if (!empty($col['so_premio'])) continue; ?>
      <div class="booster">
        <div class="capa" style="--cor:<?= e($col['cor']) ?>">
          <?php if (temArte('/assets/img/logo/logo-mini.png')): ?><img class="marca-b" src="/assets/img/logo/logo-mini.png" alt=""><?php endif; ?>
        </div>
        <div class="corpo">
          <h3><?= e($col['nome']) ?></h3>
          <small class="dica">9 cartas · 7 comuns, 1 rara e 1 slot que tem 1 em 12 de virar super.</small>
          <div class="rodape-cartao">
            <div class="preco"><?= num((int)$col['preco']) ?> <?= YU_MOEDA ?></div>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="booster"><input type="hidden" name="colecao" value="<?= e($slug) ?>">
              <button class="btn btn-p btn-largo btn-principal">Comprar</button></form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($aba==='avatar'): ?>
  <div class="grade" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
    <?php foreach (avatares() as $n=>$a): $img='/assets/img/avatares/avatar-'.str_pad((string)$n,2,'0',STR_PAD_LEFT).'.png';
      $livre = avatarLiberado($n,$eu); $usando = (int)$eu['avatar']===$n; ?>
      <div style="text-align:center">
        <div style="border-radius:50%;overflow:hidden;border:2px solid <?= $usando?'var(--ouro)':'var(--borda-viva)' ?>;aspect-ratio:1;<?= $livre?'':'filter:grayscale(1);opacity:.35' ?>">
          <?php if (temArte($img)): ?><img src="<?= $img ?>" alt="<?= e($a['nome']) ?>" loading="lazy"><?php endif; ?>
        </div>
        <div style="font-size:.82rem;margin:.35rem 0 .3rem;line-height:1.2"><?= e($livre?$a['nome']:'???') ?></div>
        <?php if ($livre && !$usando): ?>
          <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="avatar"><input type="hidden" name="n" value="<?= $n ?>">
            <button class="btn btn-p btn-largo">Usar</button></form>
        <?php elseif ($usando): ?><span class="etiqueta ouro">em uso</span>
        <?php else: ?><small class="dica"><?= e(str_replace(['nivel:','conquista:','chefoes:','campanha'],['nível ','conquista ','vencer  chefões','zerar a campanha'],$a['trava'])) ?></small><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif (in_array($aba,['sorte','melhoria','cosmetico'],true)): ?>
  <div class="grade g3">
    <?php foreach (itensDaLoja()[$aba] as $it): $tem = tenhoItem($id,$it['slug']); ?>
      <div class="cartao">
        <h3 style="font-family:var(--serif);text-transform:none;letter-spacing:0;font-size:1.1rem;color:var(--texto)"><?= e($it['nome']) ?></h3>
        <p class="dica" style="margin:0"><?= e($it['texto']) ?></p>
        <div class="rodape-cartao">
          <?php if ($tem): ?><span class="etiqueta ouro">já é seu</span>
          <?php else: ?>
            <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="item">
              <input type="hidden" name="aba" value="<?= e($aba) ?>"><input type="hidden" name="slug" value="<?= e($it['slug']) ?>">
              <button class="btn btn-p btn-largo btn-principal"><?= num((int)$it['preco']) ?> <?= YU_MOEDA ?></button></form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php elseif ($aba==='sombrio'): ?>
  <p class="dica">A Wadjet só abre a cesta para quem já derrubou um chefão nesta dificuldade.</p>
  <?php $podeSombrio = (int)qv('SELECT COUNT(*) FROM chefoes_vencidos WHERE duelista_id=? AND dificuldade=?',[$id,$dif],0) > 0; ?>
  <?php if (!$podeSombrio): ?>
    <div class="caixa centro" style="padding:2.5rem"><h2>Cesta fechada</h2>
      <p class="dica">Derrote o chefão desta cidade e ela abre.</p></div>
  <?php else: ?>
    <div class="grade-cartas" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr))">
      <?php foreach (q('SELECT * FROM cartas WHERE atk >= 2500 ORDER BY RAND() LIMIT 6') as $c):
        $preco = precoDaCarta($c)*3; $tenho=tenhoQuantas($id,$dif,(int)$c['num']); ?>
        <div style="text-align:center">
          <div class="mini" data-lupa="<?= e(arteCarta($c)) ?>"><img src="<?= e(arteCarta($c)) ?>" alt=""></div>
          <div style="font-size:.8rem;margin:.3rem 0"><?= e(corta($c['nome'],24)) ?></div>
          <form method="post"><?= csrfCampo() ?><input type="hidden" name="acao" value="carta"><input type="hidden" name="num" value="<?= (int)$c['num'] ?>">
            <button class="btn btn-p btn-largo" <?= $tenho>=3?'disabled':'' ?>><?= num($preco) ?> <?= YU_MOEDA ?></button></form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require YU_APP . '/layout/rodape.php';
