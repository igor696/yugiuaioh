<?php
/**
 * PRESENTES — só o dono.
 * Não é do cargo, é da casa: a página confere o número #YU0001, e é
 * essa conferência que vale, não o item de menu.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirFarao();
if (!ehDono()) { http_response_code(403); exit('Só o dono da casa manda presente.'); }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    csrfConfere(); $a=(string)post('acao');
    if ($a==='criar') {
        $c = strtoupper(trim((string)post('codigo'))) ?: ('YU-' . strtoupper(bin2hex(random_bytes(3))));
        ex('INSERT INTO presentes (codigo,oneub,booster,usos_max,criado_em) VALUES (?,?,?,?,NOW())',
           [$c,(int)post('oneub',0),post('booster')?1:0,(int)post('usos_max',0)]);
        recado("Código criado: $c");
    } elseif ($a==='apagar') ex('DELETE FROM presentes WHERE id=?',[(int)post('id')]);
    irPara('/farao-presentes.php');
}
$lista=q('SELECT * FROM presentes ORDER BY id DESC');
$titulo='Presentes';
require YU_APP . '/layout/topo.php';
?>
<div class="titulo-secao" style="margin-top:0"><?= icoPresente(24) ?><h1 style="margin:0">Presentes</h1>
  <span class="dica">O prêmio cai na carteira da dificuldade em que o duelista estiver ao resgatar.</span></div>
<div class="caixa caixa-ouro">
  <form method="post" style="display:flex;gap:.6rem;flex-wrap:wrap;align-items:end"><?= csrfCampo() ?>
    <input type="hidden" name="acao" value="criar">
    <div class="campo" style="margin:0"><label for="codigo">Código (vazio = sorteia)</label><input type="text" id="codigo" name="codigo" style="text-transform:uppercase"></div>
    <div class="campo" style="margin:0"><label for="oneub"><?= YU_MOEDA ?></label><input type="number" id="oneub" name="oneub" value="500" min="0" style="width:120px"></div>
    <div class="campo" style="margin:0"><label for="usos_max">Usos (0 = sem limite)</label><input type="number" id="usos_max" name="usos_max" value="1" min="0" style="width:120px"></div>
    <div class="campo" style="margin:0"><label><input type="checkbox" name="booster" value="1" style="width:auto"> com pacote</label></div>
    <button class="btn btn-principal">Criar</button>
  </form>
</div>
<div class="caixa" style="padding:.4rem">
  <table class="lista"><thead><tr><th>Código</th><th class="num"><?= YU_MOEDA ?></th><th>Pacote</th><th class="num">Usos</th><th></th></tr></thead><tbody>
  <?php foreach ($lista as $p): ?>
    <tr><td style="font-family:var(--cond);letter-spacing:.12em;color:var(--ouro)"><?= e($p['codigo']) ?></td>
      <td class="num"><?= num((int)$p['oneub']) ?></td><td class="dica"><?= (int)$p['booster']?'sim':'—' ?></td>
      <td class="num"><?= (int)$p['usos'] ?><?= (int)$p['usos_max']?' / '.(int)$p['usos_max']:'' ?></td>
      <td><form method="post" data-confirma="Apagar o código?"><?= csrfCampo() ?><input type="hidden" name="acao" value="apagar">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-p btn-perigo">Apagar</button></form></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php require YU_APP . '/layout/rodape.php';
