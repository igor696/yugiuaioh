<?php
/**
 * O CABEÇALHO.
 *
 * A porta de entrada (index.php) marca $semCabecalho = true e pula tudo
 * isto: lá o "entrar / criar" está na coluna da direita, maior, e um
 * cabeçalho repetindo a mesma coisa só rouba espaço. Igual ao MTM.
 *
 * Depois do login o cabeçalho tem exatamente o que foi combinado:
 *
 *   [logo]  DUELO ▾  DECK  LOJA  COFRE  [☰ MENU] ..... [ONEUB] [duelista]
 *
 * DUELO abre um submenu por baixo, como o MENU — CAMPANHA, LAN, ONLINE.
 * Não existe "testar o Deck".
 */
$eu    = duelistaAtual();
$atual = basename($_SERVER['SCRIPT_NAME'] ?? '');
$dif   = $eu ? difAtual() : 1;

$abas = [
    'duelo.php' => ['DUELO', 'icoDuelo'],
    'deck.php'  => ['DECK',  'icoDeck'],
    'loja.php'  => ['LOJA',  'icoLoja'],
    'cofre.php' => ['COFRE', 'icoCofre'],
];
$submenuDuelo = [
    ['campanha.php', 'Campanha', 'icoCampanha', 'Duelos contra a máquina, com história'],
    ['lan.php',      'LAN',      'icoLan',      'Máquinas na mesma rede'],
    ['online.php',   'Online',   'icoOnline',   'Pela internet, no Bazar de Tebas'],
];
$menu = [
    'Suas cartas' => [
        ['deck-real.php', 'Deck real',     'icoDeck',   'Os decks de papel que você tem em casa'],
        ['livre.php',     'Coleção livre', 'icoPasta',  'Cadastro livre, fora da campanha'],
        ['cartas.php',    'As 722',        'icoCarta',  'A biblioteca inteira, liberada e por liberar'],
    ],
    'Sua jornada' => [
        ['conquistas.php', 'Conquistas',  'icoConquista', ''],
        ['salvos.php',     'Jogos salvos','icoSalvos',    'Onde você parou, em 5 lugares por dificuldade'],
        ['conta.php',      'Meu perfil',  'icoPerfil',    ''],
    ],
    'Ajuda' => [
        ['manual.php',    'Manual',     'icoManual',    'As regras, do jeito que estão no PDF'],
        ['resgatar.php',  'Resgatar código', 'icoResgatar', ''],
        ['suporte.php',   'Suporte',    'icoSuporte',   ''],
        ['quem-somos.php','Quem somos?','icoQuemSomos', ''],
    ],
];
if (ehFarao()) {
    $menu['Faraó'] = [['farao.php', 'Painel do Faraó', 'icoFarao', '']];
    if (ehDono()) { $menu['Faraó'][] = ['farao-presentes.php', 'Presentes', 'icoPresente', '']; }
}

$novasMensagens  = $eu ? mensagensNaoLidas((int)$eu['id']) : 0;
$novasConquistas = $eu ? conquistasNaoVistas((int)$eu['id']) : 0;
$pacotes         = $eu ? boostersFechados((int)$eu['id']) : 0;
$avisosMenu      = $novasMensagens + $novasConquistas;
$nivel           = nivelDe($eu);
$meuSaldo        = $eu ? saldo((int)$eu['id'], $dif) : 0;
$foto            = $eu ? avatarUrl($eu) : '';
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e(($titulo ?? '') !== '' ? $titulo . ' · Yu Gi Uai Oh' : 'Yu Gi Uai Oh') ?></title>
<meta name="description" content="Duelo de cartas com as regras do manual, campanha contra a máquina, coleção e decks. Projeto de fã, sem dinheiro de verdade.">
<meta name="theme-color" content="#150F20">
<meta name="csrf" content="<?= e(csrfToken()) ?>">
<link rel="icon" href="/assets/img/logo/icone.png" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/logo/logo-mini.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&family=Spectral:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/yugi.css?v=<?= YU_VERSAO ?>">
</head>
<body class="<?= !empty($corpoClasse) ? e($corpoClasse) : '' ?>">

<a class="pular" href="#conteudo">Pular para o conteúdo</a>

<?php if (empty($semCabecalho)): ?>
<header class="topo">
  <div class="topo-linha">

    <a class="marca" href="<?= $eu ? '/painel.php' : '/' ?>" title="<?= $eu ? 'Voltar ao painel' : 'Página inicial' ?>">
      <img src="/assets/img/logo/logo-menu.png" alt="Yu Gi Uai Oh" width="220" height="82" fetchpriority="high">
    </a>

    <?php if ($eu): ?>
    <nav class="nav" aria-label="Menu principal">
      <?php foreach ($abas as $arq => [$rotulo, $ico]):
        /* NOTA: este arquivo é incluído DENTRO do escopo global de cada
           página. Toda variável de laço aqui precisa de nome próprio —
           um `$d` solto vazava para fora do foreach e atropelava o
           `$d = dificuldade(...)` das páginas. Daí o prefixo `$_`. */
        $ehDuelo = $arq === 'duelo.php';
        $ativo = $atual === $arq || ($ehDuelo && in_array($atual, ['campanha.php','lan.php','online.php','mesa.php'], true)); ?>
        <div class="nav-item <?= $ehDuelo ? 'tem-sub' : '' ?>">
          <?php if ($ehDuelo): ?>
            <button type="button" class="nav-btn <?= $ativo ? 'ativo' : '' ?>" data-sub="sub-duelo"
                    aria-expanded="false" aria-controls="sub-duelo">
              <?= $ico(20) ?><span><?= e($rotulo) ?></span><i class="seta" aria-hidden="true"></i>
            </button>
            <div class="submenu" id="sub-duelo" hidden>
              <?php foreach ($submenuDuelo as [$_arq, $_rot, $_ico, $_desc]): ?>
                <a href="/<?= $_arq ?>" class="sub-item <?= $atual === $_arq ? 'ativo' : '' ?>">
                  <span class="sub-ico"><?= $_ico(20) ?></span>
                  <span class="sub-txt"><b><?= e($_rot) ?></b><?php if ($_desc): ?><em><?= e($_desc) ?></em><?php endif; ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <a href="/<?= $arq ?>" class="nav-btn <?= $ativo ? 'ativo' : '' ?>">
              <?= $ico(20) ?><span><?= e($rotulo) ?></span>
              <?php if ($arq === 'loja.php' && $pacotes): ?><i class="pino ouro"><?= min(99, $pacotes) ?></i><?php endif; ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

      <div class="nav-item tem-sub">
        <button type="button" class="nav-btn nav-menu" data-sub="sub-menu" aria-expanded="false" aria-controls="sub-menu">
          <span class="tracinhos" aria-hidden="true"><i></i><i></i><i></i></span><span>MENU</span>
          <?php if ($avisosMenu): ?><i class="pino"><?= min(99, $avisosMenu) ?></i><?php endif; ?>
        </button>
        <div class="submenu submenu-largo" id="sub-menu" hidden>
          <?php foreach ($menu as $grupo => $itens): ?>
            <div class="sub-grupo<?= $grupo === 'Faraó' ? ' sub-farao' : '' ?>">
              <div class="sub-titulo"><?= e($grupo) ?></div>
              <?php foreach ($itens as [$_arq, $_rot, $_ico, $_desc]): ?>
                <a href="/<?= $_arq ?>" class="sub-item <?= $atual === $_arq ? 'ativo' : '' ?>">
                  <span class="sub-ico"><?= $_ico(20) ?></span>
                  <span class="sub-txt"><b><?= e($_rot) ?></b><?php if ($_desc): ?><em><?= e($_desc) ?></em><?php endif; ?></span>
                  <?php if ($_arq === 'conquistas.php' && $novasConquistas): ?><i class="pino"><?= (int)$novasConquistas ?></i><?php endif; ?>
                  <?php if ($_arq === 'conta.php' && $novasMensagens): ?><i class="pino"><?= (int)$novasMensagens ?></i><?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
          <div class="sub-grupo sub-sair">
            <a href="/sair.php" class="sub-item item-sair">
              <span class="sub-ico"><?= icoSair(20) ?></span>
              <span class="sub-txt"><b>Sair</b></span>
            </a>
          </div>
        </div>
      </div>
    </nav>

    <div class="chip-area">
      <a class="chip-oneub" href="/cofre.php" title="Seu Cofre — saldo do <?= e(dificuldade($dif)['nome']) ?>">
        <?= icoOneub(18) ?><b><?= num($meuSaldo) ?></b>
        <?php if ($pacotes): ?><i class="chip-pacote" title="<?= (int)$pacotes ?> pacote(s) fechado(s)"><?= (int)$pacotes ?></i><?php endif; ?>
      </a>
      <a class="chip-foto" href="/conta.php" title="Meu perfil">
        <?php if ($foto): ?><img src="<?= e($foto) ?>" alt=""><?php else: ?><span><?= e(iniciais($eu)) ?></span><?php endif; ?>
        <?php if ($novasMensagens): ?><i class="badge"><?= min(99, $novasMensagens) ?></i><?php endif; ?>
      </a>
      <div class="chip-id">
        <div class="chip-nome"><?= e($eu['nome']) ?><?= emblemaNivel($nivel, 'p') ?></div>
        <div class="chip-num"><?= e($eu['numero']) ?></div>
      </div>
    </div>
    <?php else: ?>
      <div class="chip-area">
        <a class="btn btn-p" href="/index.php">Entrar</a>
        <a class="btn btn-p btn-principal" href="/cadastrar.php">Criar duelista</a>
      </div>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>

<?php unset($_arq, $_rot, $_ico, $_desc, $arq, $rotulo, $ico, $ehDuelo, $ativo); ?>
<main class="pagina <?= !empty($paginaEstreita) ? 'pagina-estreita' : '' ?>" id="conteudo">
<?php foreach (pegarRecados() as $r): ?>
  <div class="recado <?= e($r['tipo']) ?>"><?= e($r['texto']) ?></div>
<?php endforeach; ?>
