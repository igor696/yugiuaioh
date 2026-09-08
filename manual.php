<?php
/**
 * O MANUAL.
 *
 * As regras do PDF, em página, na mesma ordem. Ele fica no rodapé de
 * quem já entrou — não é cartão de visita, é consulta. O botão do
 * tutorial chama o Indawora para conduzir isto aqui na própria tela.
 */
require __DIR__ . '/../app/bootstrap.php';
exigirLogin();
$titulo = 'Manual';
require YU_APP . '/layout/topo.php';

$secoes = [
 ['O duelo', [
   'Dois duelistas, <b>8000 Pontos de Vida</b> cada. Vence quem zerar os pontos do outro — ou quem fizer o adversário precisar comprar sem ter mais carta no Deck.',
   'Um <b>Match</b> são três Duelos: leva quem ganhar dois. Entre um Duelo e outro dá para trocar cartas com o Deck Auxiliar.',
   '<b>A regra de ouro:</b> quando este manual e o texto de uma carta discordarem, vale o que está escrito na carta.',
 ]],
 ['Os três Decks', [
   '<b>Deck Principal</b> — de 40 a 60 cartas. É de onde você compra.',
   '<b>Deck Adicional</b> — até 15 cartas, e <b>só Monstros de Fusão</b>.',
   '<b>Deck Auxiliar</b> — até 15 cartas de qualquer tipo, para trocar entre Duelos do mesmo Match.',
   'No máximo <b>3 cópias</b> da mesma carta somando os três Decks.',
 ]],
 ['O tapete', [
   '<b>5 Zonas de Monstro</b> e <b>5 Zonas de Magia e Armadilha</b> de cada lado.',
   'A <b>Zona de Campo</b> é separada: a Mágica de Campo não ocupa uma das cinco.',
   'Ainda existem o <b>Cemitério</b>, o <b>Deck</b> e o <b>Deck Adicional</b>, cada um no seu canto.',
   'Quando as 5 Zonas de Magia e Armadilha estão cheias, nada mais pode ser ativado nem baixado ali.',
 ]],
 ['Ler uma carta', [
   'Nome, Atributo, Nível em estrelas, ilustração, Tipo, texto e — na base — <b>ATK</b> e <b>DEF</b>.',
   '<b>Monstro Normal:</b> fundo amarelo, sem efeito, em troca de números mais altos.',
   '<b>Monstro de Efeito:</b> fundo laranja. O efeito cai numa de cinco categorias: Contínuo, de Ignição, de Gatilho, Rápido e de Virar.',
   '<b>Monstro de Fusão:</b> fundo roxo, mora no Deck Adicional.',
   '<b>Monstro de Ritual:</b> fundo azul, mora no Principal e só entra pela Mágica de Ritual dele.',
 ]],
 ['Pôr monstro em jogo', [
   'Uma <b>Invocação-Normal</b> OU um <b>Baixar</b> por turno — uma coisa ou a outra, nunca as duas.',
   'Monstro de <b>Nível 5 ou 6</b> pede <b>1 Tributo</b>. De <b>Nível 7 ou mais</b>, <b>2 Tributos</b>. Os Tributos saem antes, e por isso liberam a zona.',
   '<b>Invocação por Virar:</b> virar para cima, em ataque, um monstro que estava baixado. Não tem limite por turno — mas não vale no turno em que ele foi baixado.',
   '<b>Invocação-Especial:</b> a que um efeito manda fazer. Também não tem limite.',
   'Cada monstro muda de posição <b>uma vez por turno</b>, e nunca no turno em que entrou no campo.',
 ]],
 ['Mágicas e Armadilhas', [
   '<b>Mágica</b> ativa na sua Fase Principal, direto da mão ou de uma que você tenha baixado antes.',
   '<b>Armadilha</b> precisa estar baixada e só ativa <b>a partir do turno seguinte</b> — mas pode ativar durante o turno do adversário. É isso que faz dela uma armadilha.',
   '<b>Equipamento</b> fica no campo preso ao monstro; se o monstro morre, ela vai junto para o Cemitério.',
   '<b>Campo</b> ocupa a Zona de Campo, e só cabe uma por lado.',
   '<b>Ritual</b> é a Mágica que traz o Monstro de Ritual, pagando os Tributos que ela pedir.',
 ]],
 ['O turno, fase por fase', [
   '<b>1. Fase de Compra</b> — compre 1 carta.',
   '<b>2. Fase de Apoio</b> — o momento dos efeitos que dizem “na Fase de Apoio”.',
   '<b>3. Fase Principal 1</b> — invocar, baixar, ativar, mudar posição.',
   '<b>4. Fase de Batalha</b> — declarar ataques.',
   '<b>5. Fase Principal 2</b> — o que sobrou da Principal 1. <b>Se você pular a Batalha, não existe Principal 2.</b>',
   '<b>6. Fase Final</b> — mais de 6 cartas na mão? Descarte até ficar com 6.',
   '<b>Quem começa não compra e não conduz Fase de Batalha no primeiro turno.</b>',
 ]],
 ['A batalha e a conta do dano', [
   'Cada monstro em <b>Posição de Ataque</b> ataca uma vez por turno. Monstro em Defesa não ataca.',
   'Se o adversário não controla monstro nenhum, o ataque é <b>direto</b>: o ATK inteiro sai dos pontos dele.',
   '<b>Ataque contra Ataque:</b> maior ATK vence, o perdedor é destruído e a diferença sai dos Pontos de Vida de quem perdeu. Se der empate, os dois morrem e ninguém sofre dano.',
   '<b>Ataque contra Defesa:</b> se o seu ATK passa da DEF, o monstro é destruído e ninguém sofre dano. Se for igual, nada acontece. <b>Se for menor, a diferença sai dos SEUS pontos.</b>',
   'Monstro com <b>0 de ATK</b> não destrói nada em batalha.',
   'Monstro baixado vira para cima na Etapa de Dano — e é a DEF dele que conta.',
 ]],
 ['Cadeia', [
   'Cada carta ativada em resposta a outra vira um <b>elo</b> da Cadeia.',
   'A Cadeia <b>resolve de trás para frente</b>: o último elo é o primeiro a acontecer.',
   'Guardar um efeito para responder costuma valer mais do que ativar primeiro.',
   'As <b>relíquias do Milênio</b> deste site agem <b>fora da Cadeia</b> — não criam elo e não podem ser respondidas.',
 ]],
];
?>
<div class="titulo-secao" style="margin-top:0">
  <h1 style="margin:0">Manual de regras</h1>
  <span class="dica">É este manual que o motor do site obedece, e não o contrário.</span>
  <button class="btn btn-p btn-principal" data-tutorial style="margin-left:auto">Chamar o Indawora</button>
</div>

<div class="grade g2" style="align-items:start">
  <?php foreach ($secoes as $i => [$tit, $itens]): ?>
    <div class="caixa <?= $i === 0 ? 'caixa-ouro' : '' ?>">
      <h2><?= e($tit) ?></h2>
      <ul style="line-height:1.7;margin:0;padding-left:1.1rem">
        <?php foreach ($itens as $x): ?><li style="margin-bottom:.35rem"><?= $x ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endforeach; ?>
</div>

<div class="caixa" style="margin-top:var(--respiro)">
  <h2>O que este conjunto de cartas tem — e o que não tem</h2>
  <p style="max-width:var(--medida)">
    As <b>722 cartas</b> são as do Forbidden Memories: o conjunto fechado onde tudo começou.
    Existem <b>Monstro Normal, de Efeito, de Fusão e de Ritual</b>, mais <b>Mágicas</b>
    (comum, de Equipamento, de Campo e de Ritual) e <b>Armadilhas</b>.
  </p>
  <p style="max-width:var(--medida)">
    <b>Não existe</b> Sincro, XYZ, Link nem Pêndulo. Não é limitação de programa: é o recorte
    do conjunto, e é ele que faz o jogo caber na cabeça de quem está aprendendo.
  </p>
  <div class="friso"></div>
  <p class="dica" style="max-width:var(--medida);margin:0">
    <b>Uma honestidade sobre os efeitos.</b> A lista das 722 que alimenta este site traz nome,
    tipo, Nível, ATK, DEF e passcode — mas <b>não traz o texto de regras</b>. Por isso, as
    Mágicas e Armadilhas mais conhecidas têm o efeito escrito à mão no motor e funcionam de
    verdade; as demais entram no jogo como <b>blefe</b>: ocupam zona, assustam o adversário e
    não mudam o estado da mesa. Quando o importador do YGOPRODeck trouxer o texto oficial de
    cada uma, elas vão sendo ligadas uma a uma. Preferimos dizer isso a inventar efeito.
  </p>
</div>
<?php require YU_APP . '/layout/rodape.php';
