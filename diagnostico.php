<?php
/** Conferência de saúde: banco, tabelas, artes e catálogo. */
require __DIR__ . '/../app/bootstrap.php';
exigirFarao();
$titulo='Diagnóstico';
require YU_APP . '/layout/topo.php';
$tabelas=['duelistas','convites','mensagens','ajustes','carteiras','extrato','progresso','posse_cartas','cartas',
  'decks','deck_cartas','oponentes_vencidos','chefoes_vencidos','oponente_estado','posse_reliquias','pecas_enigma',
  'posse_conquistas','marcas','duelos','boosters','itens','jogos_salvos','salas','presentes','presentes_usados',
  'suporte','colecao_livre','decks_reais'];
$pastas=['mascote'=>14,'chefoes'=>6,'mercadores'=>8,'avatares'=>15,'reliquias'=>28,'selos'=>5,'molduras'=>4,'logo'=>5,
  'oponentes/n1'=>30,'oponentes/n2'=>30,'oponentes/n3'=>30,'oponentes/n4'=>30,'oponentes/n5'=>30,
  'boosters'=>14,'conquistas'=>24];
?>
<h1>Diagnóstico</h1>
<div class="grade g2" style="align-items:start">
  <div class="caixa"><h2>Tabelas</h2><table class="lista">
    <?php foreach ($tabelas as $t): $ok=temTabela($t); ?>
      <tr><td><?= e($t) ?></td><td class="num"><?= $ok ? num((int)qv("SELECT COUNT(*) FROM `$t`",[],0)) : '—' ?></td>
      <td style="color:<?= $ok?'#8FD18A':'#F0857E' ?>"><?= $ok?'ok':'FALTA' ?></td></tr>
    <?php endforeach; ?></table></div>
  <div class="caixa"><h2>Artes</h2><table class="lista">
    <?php foreach ($pastas as $p=>$esperado):
      $dir = YU_RAIZ.'/public_html/assets/img/'.$p;
      $n = is_dir($dir) ? count(glob($dir.'/*.png')) : 0; ?>
      <tr><td><?= e($p) ?></td><td class="num"><?= $n ?> / <?= $esperado ?></td>
      <td style="color:<?= $n>=$esperado?'#8FD18A':($n?'var(--ouro)':'#F0857E') ?>"><?= $n>=$esperado?'completo':($n?'parcial':'vazio') ?></td></tr>
    <?php endforeach; ?>
    <tr><td>cache/cartas (artes das 722)</td>
      <td class="num"><?= count(glob(YU_RAIZ.'/public_html/cache/cartas/*.jpg')) ?> / 722</td><td class="dica">importador</td></tr>
  </table></div>
</div>
<div class="caixa"><h2>Ambiente</h2><table class="lista">
  <tr><td>PHP</td><td><?= e(PHP_VERSION) ?></td></tr>
  <tr><td>Versão do site</td><td><?= YU_VERSAO ?></td></tr>
  <tr><td>Pasta de log</td><td class="dica"><?= e(YU_RAIZ.'/logs') ?> — <?= is_writable(YU_RAIZ.'/logs')?'gravável':'SEM PERMISSÃO' ?></td></tr>
  <tr><td>Cache de cartas</td><td class="dica"><?= is_writable(YU_RAIZ.'/public_html/cache/cartas')?'gravável':'SEM PERMISSÃO' ?></td></tr>
</table></div>
<?php require YU_APP . '/layout/rodape.php';
