<?php
/**
 * A BIBLIOTECA DAS 722.
 * Tudo aparece — o que você não tem vem em silhueta com "?", como no
 * quadro de conquistas. Ver o que falta é metade da graça de colecionar.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$eu = duelistaAtual(); $id = (int)$eu['id']; $dif = difAtual(); $d = dificuldade($dif);

$f = [
  'busca'   => trim((string)get('b','')),
  'tipo'    => get('t') ? [(string)get('t')] : null,
  'sub'     => (string)get('s',''),
  'nivel'   => (string)get('n',''),
  'ordem'   => (string)get('o','num'),
  'so_minhas' => get('minhas') ? 1 : 0,
];
$pagina = max(1,(int)get('p',1));
[$linhas,$total] = buscarCartas($f, $pagina, 96, $id, $dif);
$subs = array_column(q('SELECT DISTINCT sub FROM cartas WHERE sub <> "" ORDER BY sub'), 'sub');
$titulo = 'As 722';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0">A biblioteca</h1>
  <span class="etiqueta" style="color:<?= e($d['cor']) ?>"><?= e($d['nome']) ?></span>
  <span class="dica"><?= num(quantasTenho($id,$dif)) ?> de 722 liberadas nesta dificuldade · <?= num($total) ?> no filtro</span>
</div>

<form method="get" class="filtros">
  <div class="campo cresce"><label for="b">Procurar</label><input type="search" id="b" name="b" value="<?= e($f['busca']) ?>" placeholder="nome ou passcode"></div>
  <div class="campo"><label for="t">Tipo</label><select id="t" name="t"><option value="">todos</option>
    <?php foreach (['Monster'=>'Monstro','Magic'=>'Mágica','Equip'=>'Equipamento','Field'=>'Campo','Ritual'=>'Ritual','Trap'=>'Armadilha'] as $k=>$v): ?>
      <option value="<?= $k ?>" <?= get('t')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div class="campo"><label for="s">Subtipo</label><select id="s" name="s"><option value="">todos</option>
    <?php foreach ($subs as $s): ?><option value="<?= e($s) ?>" <?= $f['sub']===$s?'selected':'' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <div class="campo"><label for="n">Nível</label><select id="n" name="n"><option value="">qualquer</option>
    <?php for ($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= (string)$f['nivel']===(string)$i?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></div>
  <div class="campo"><label for="o">Ordem</label><select id="o" name="o">
    <option value="num" <?= $f['ordem']==='num'?'selected':'' ?>>número</option>
    <option value="nome" <?= $f['ordem']==='nome'?'selected':'' ?>>nome</option>
    <option value="atk" <?= $f['ordem']==='atk'?'selected':'' ?>>ATK</option>
    <option value="nivel" <?= $f['ordem']==='nivel'?'selected':'' ?>>nível</option></select></div>
  <div class="campo"><label><input type="checkbox" name="minhas" value="1" <?= $f['so_minhas']?'checked':'' ?> style="width:auto"> só as minhas</label></div>
  <button class="btn btn-p"><?= icoBusca(16) ?> Filtrar</button>
</form>

<div class="grade-cartas">
  <?php foreach ($linhas as $c): $tenho=(int)$c['tenho']; ?>
    <div class="mini <?= $tenho?'':'nao-tenho' ?>" data-lupa="<?= e(arteCarta($c)) ?>"
         title="<?= e($c['nome']) ?> — <?= e(rotuloDoTipo($c['tipo'])) ?><?= $c['tipo']==='Monster'?' · Nv '.(int)$c['nivel'].' · ATK '.(int)$c['atk'].' / DEF '.(int)$c['def']:'' ?>">
      <img src="<?= e(arteCarta($c)) ?>" alt="<?= e($c['nome']) ?>" loading="lazy">
      <span class="tipo" style="background:<?= e(corDoTipo($c['tipo'])) ?>"></span>
      <?php if ($tenho): ?><span class="qtd">×<?= $tenho ?></span><?php else: ?><span class="qtd">#<?= (int)$c['num'] ?></span><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php $paginas=(int)ceil($total/96); if ($paginas>1):
  $qs = function(int $p){ $q=$_GET; $q['p']=$p; return '/cartas.php?'.http_build_query($q); }; ?>
  <div class="linha-btn" style="justify-content:center;margin-top:var(--respiro-g)">
    <?php for ($i=1;$i<=$paginas;$i++): ?>
      <a class="btn btn-p <?= $i===$pagina?'btn-principal':'' ?>" href="<?= e($qs($i)) ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>
<?php require YU_APP . '/layout/rodape.php';
