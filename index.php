<?php
/**
 * A PORTA DE ENTRADA.
 *
 * Mesma arrumação aprovada no Magic the Minas: institucional à esquerda,
 * caixa de login grudada à direita, e o ziguezague de blocos com o
 * mascote descendo a página em diagonal. Sem cabeçalho — o "entrar" já
 * está aqui, maior, e repetir no topo só rouba espaço. O rodapé fica.
 */
require __DIR__ . '/../app/bootstrap.php';
if (estaLogado()) { irPara('/painel.php'); }

$erroLogin = null;
$nomeTentado = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('acao') === 'entrar') {
    csrfConfere();
    $nomeTentado = (string)post('nome');
    $r = entrar($nomeTentado, (string)($_POST['senha'] ?? ''));
    if (!empty($r['ok'])) {
        $destino = $_SESSION['voltar_para'] ?? '/painel.php';
        unset($_SESSION['voltar_para']);
        recado('De volta ao bazar, ' . $r['duelista']['nome'] . '.');
        irPara($destino);
    }
    $erroLogin = $r['erro'] ?? 'Não consegui entrar.';
}

$semCabecalho = true;
$titulo = 'Entrada';
require YU_APP . '/layout/topo.php';

$totalCartas = 722;
try { $totalCartas = (int)qv('SELECT COUNT(*) FROM cartas', [], 722); } catch (Throwable $e) {}

$blocos = [
  ['A campanha, cinco cidades',
   'Cento e cinquenta duelistas esperando, trinta por dificuldade, e um chefão guardando '
 . 'a saída de cada cidade. Cada oponente tem cinco decks, e o deck que ele traz depende '
 . 'de como você vem se saindo contra ele — ganhou seguido, ele sobe; apanhou, ele desce. '
 . 'Ninguém fica preso batendo no mesmo adversário fácil a vida inteira.'],

  ['Cinco jogos dentro de um',
   'Muito Fácil, Fácil, Normal, Difícil e Muito Difícil não são um botão de ajuste: são '
 . 'cinco partidas separadas. Coleção, decks, carteira e progresso de uma dificuldade '
 . 'não passam para a outra. O que você juntou no Muito Fácil não compra nada no Difícil, '
 . 'e é isso que faz cada uma valer a pena começar do zero.'],

  ['As sete relíquias do Milênio',
   'Você entra no jogo com uma delas sorteada — nunca o Enigma. As outras seis estão com '
 . 'os seis guardiões da campanha, uma cada. Ler a mão do oponente, procurar uma carta no '
 . 'Deck, ver o topo antes de comprar: cada relíquia faz uma coisa só, uma vez por duelo, '
 . 'e nenhuma cria elo de Cadeia.'],

  ['As regras do manual, sem atalho',
   'Fase por fase, Tributo por Tributo, Cadeia resolvendo de trás para frente. A conta de '
 . 'dano é a da página 24: ATK contra ATK, e ATK contra DEF do jeito certo, inclusive '
 . 'quando o troco sai dos pontos de quem atacou. O servidor guarda a mão do adversário; '
 . 'ela não é enviada para o seu navegador nem escondida com CSS.'],

  ['O ' . YU_MOEDA . ', a moeda da casa',
   'Ele entra por vitória, por presença diária, por conquista concluída e por repetida '
 . 'devolvida. Sai em carta avulsa, em pacote e em melhoria. Não se compra ' . YU_MOEDA
 . ' com dinheiro de verdade, nunca: não existe cartão, não existe PIX, não existe pacote '
 . 'de moedas. Cada dificuldade tem a sua carteira, e todo movimento fica no extrato.'],

  ['Os oito mercadores de Tebas',
   'Sefu vende carta avulsa numa banca de dez que troca a cada 24 horas — e é a mesma '
 . 'banca para todo mundo no mesmo período. Hapi-Uai vende os pacotes, Merit-Ka os itens '
 . 'de sorte, Djed as melhorias, Bastet-Mina os avatares. Thoth-Bão não vende nada: ele só '
 . 'anota, e é com ele que você confere o extrato linha por linha.'],
];
?>

<div class="entrada">

  <div class="entrada-info">

    <div class="marca-entrada">
      <img src="/assets/img/logo/logo-menu.png" alt="Yu Gi Uai Oh" width="640" height="240" fetchpriority="high">
    </div>

    <h1 class="entrada-titulo">Um duelo de cartas com as regras inteiras, e uma campanha para atravessar</h1>

    <p class="entrada-linha">
      O Yu Gi Uai Oh é um site fechado, feito para quem gosta de duelo de cartas e quer um
      lugar para montar deck, aprender a regra de verdade e jogar contra alguém que não
      pega leve.
    </p>

    <div class="caixa">
      <h2 class="entrada-h2" style="margin-top:0">De onde ele veio</h2>
      <p>
        Ele nasceu de um manual. Não de um menu, não de uma tela: de um caderno de regras em
        português, lido do começo ao fim, com as fases anotadas na margem e as contas de dano
        conferidas uma por uma. Quase todo jogo de carta na internet resolve o duelo por você
        e explica depois — aqui é o contrário. A regra vem primeiro, e a tela é só o tapete.
      </p>
      <p>
        As cartas são as <strong><?= num($totalCartas) ?> do Forbidden Memories</strong>, o
        conjunto fechado onde tudo começou: sem Sincro, sem XYZ, sem Link, sem Pêndulo. Só
        Monstro Normal, de Efeito, de Fusão e de Ritual, mais Mágicas e Armadilhas. É pouco
        na conta e é muito na mesa.
      </p>
      <p class="destaque">
        Em cima disso foram construídos a campanha de <strong>150 duelistas</strong>, uma
        economia própria, sete relíquias, vinte e quatro conquistas e uma mesa que roda a
        partida inteira no servidor.
      </p>
    </div>

    <h2 class="entrada-h2">O que tem aqui dentro</h2>

    <?php /* ================================================================
         O ZIGUEZAGUE.
         Duas fileiras por bloco: o quadro em cima de um lado, o mascote
         embaixo do outro, na diagonal, apontando para o texto que está
         acima dele. O lado troca a cada bloco — é isso que faz a página
         descer em ziguezague de verdade em vez de virar uma tabela de
         duas colunas com um bonequinho na primeira célula.

         A POSE SAI DO ÍNDICE, não é escrita à mão: mascote à esquerda usa
         `diag-dir`, à direita usa `diag-esq`. Se um dia a ordem dos
         blocos mudar, a pose vira junto e nada desalinha.
         ================================================================ */ ?>
    <div class="zig">
      <?php foreach ($blocos as $i => [$tit, $txt]):
        $espelho = ($i % 2) === 1;
        $pose    = $espelho ? 'diag-esq' : 'diag-dir';
        $arte    = mascote($pose);
      ?>
        <div class="zig-passo <?= $espelho ? 'espelho' : '' ?>">
          <div class="zig-quadro">
            <span class="zig-num" aria-hidden="true"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= e($tit) ?></h3>
            <p><?= e($txt) ?></p>
          </div>
          <?php if ($arte): ?>
            <div class="zig-mago">
              <img src="<?= e($arte) ?>" alt="" width="240" height="240" loading="lazy" decoding="async">
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="caixa caixa-ouro">
      <p style="margin:0">
        <strong class="ouro">Não se compra nada com dinheiro de verdade.</strong>
        O <?= YU_MOEDA ?> não é vendido, não é comprado e não vale nada fora daqui. O site é
        gratuito, não comercial e é um projeto de fã — sem vínculo, apoio ou licença oficial
        de ninguém.
      </p>
    </div>
  </div>

  <aside class="entrada-porta">

    <div class="porta-caixa">
      <h2 class="porta-titulo">Entrar</h2>
      <p class="porta-sub">Seu nome de duelista e sua senha.</p>

      <?php if ($erroLogin): ?>
        <div class="recado erro" style="margin-bottom:.8rem"><?= e($erroLogin) ?></div>
      <?php endif; ?>

      <form method="post" autocomplete="on">
        <?= csrfCampo() ?>
        <input type="hidden" name="acao" value="entrar">
        <div class="campo">
          <label for="nome">Seu nome de duelista</label>
          <input type="text" id="nome" name="nome" value="<?= e($nomeTentado) ?>" maxlength="32"
                 required autocomplete="username" <?= $erroLogin ? 'autofocus' : '' ?>>
        </div>
        <div class="campo">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required autocomplete="current-password">
        </div>
        <button class="btn btn-principal btn-largo" type="submit">Entrar no bazar</button>
      </form>
    </div>

    <div class="porta-caixa porta-cadastro">
      <div class="porta-titulo-p">Ainda não é da casa?</div>
      <p class="dica" style="margin:.3rem 0 .9rem">
        O cadastro é fechado: você precisa do código de convite de quem já duela aqui.
      </p>
      <a class="btn btn-largo" href="/cadastrar.php">Criar meu duelista</a>
    </div>

    <?php $louva = mascote('louva'); if ($louva): ?>
      <figure class="porta-mascote">
        <img src="<?= e($louva) ?>" alt="" width="420" height="420" loading="lazy" decoding="async">
        <figcaption>Indawora, o Duelista</figcaption>
      </figure>
    <?php endif; ?>

  </aside>

</div>

<?php require YU_APP . '/layout/rodape.php';
