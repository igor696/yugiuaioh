/* O TUTORIAL DO INDAWORA.
   Ele conduz na própria tela, na ordem do manual em PDF, e cada passo
   traz a pose do mascote que o documento manda. O botão do rodapé
   ("Tutorial com o Indawora") é interceptado aqui: sem este script, o
   link cai na /manual.php, que é o mesmo conteúdo em página. */
(function () {
  'use strict';
  var PASSOS = [
    ['parado',  'Sobre o jogo',        'Dois duelistas, 8000 Pontos de Vida cada. Ganha quem zerar os do outro — ou quem fizer o outro comprar sem ter carta no Deck. Simples assim, e o resto é detalhe.'],
    ['dir',     'Os três Decks',       'Principal: de 40 a 60 cartas, é de onde você compra. Adicional: até 15, e só Monstro de Fusão. Auxiliar: até 15, para trocar entre Duelos do mesmo Match. E no máximo 3 cópias da mesma carta somando os três.'],
    ['baixo',   'O tapete',            'Seis zonas do seu lado: 5 de Monstro, 5 de Magia e Armadilha, Cemitério, Deck, Zona de Campo e Deck Adicional. A Mágica de Campo não ocupa uma das cinco — ela tem lugar próprio.'],
    ['carta',   'Ler uma carta',       'Nome, Atributo, Nível em estrelas, ilustração, Tipo, texto e — embaixo — ATK e DEF. A regra de ouro: quando o manual briga com o texto da carta, vale o que está escrito na carta.'],
    ['diag-dir','Normais e de Efeito',  'Monstro Normal tem fundo amarelo e nenhum efeito, em troca de ATK e DEF mais altos. Monstro de Efeito tem fundo laranja, e o efeito dele vem numa de cinco categorias: Contínuo, Ignição, Gatilho, Rápido e Virar.'],
    ['cima',    'Fusão e Ritual',      'Fusão sai do Deck Adicional, usando as matérias mais uma carta de invocação. Ritual mora no Principal e só entra em jogo pela Mágica de Ritual dele, com os Tributos que ela pedir.'],
    ['esq',     'Pôr monstro em jogo', 'Uma Invocação-Normal OU um Baixar por turno, nunca as duas. Nível 5 ou 6 pede 1 Tributo; Nível 7 ou mais pede 2. Virar e Especial não têm limite.'],
    ['diag-esq','Mágicas e Armadilhas','Mágica ativa na sua Fase Principal. Armadilha precisa estar baixada e só ativa a partir do turno seguinte — mas pode ativar no turno do adversário. É isso que faz dela uma armadilha.'],
    ['dir',     'O turno',             'Compra, Apoio, Principal 1, Batalha, Principal 2, Final. Quem começa não compra nem ataca no primeiro turno. E se você pular a Batalha, não existe Principal 2.'],
    ['baixo',   'Cálculo de dano',     'Contra Ataque: compara ATK com ATK, maior vence e a diferença sai dos Pontos de Vida. Contra Defesa: seu ATK contra a DEF dele — se passar, destrói sem dano; se não passar, a diferença sai dos SEUS pontos.'],
    ['diag-dir','Cadeia',              'Cada carta ativada vira um elo. A Cadeia resolve de trás para frente: o último elo é o primeiro a acontecer. Guardar um efeito para responder costuma valer mais do que ativar primeiro.'],
    ['louva',   'Bom duelo!',          'É isso. O resto você aprende apanhando do Tuti do Oásis, que é para isso que ele existe. O manual completo fica no rodapé, sempre.']
  ];
  var i = 0, caixa = null;

  function pose(p) { return '/assets/img/mascote/indawora-' + p + '.png'; }

  function desenhar() {
    var passo = PASSOS[i];
    caixa.innerHTML =
      '<img src="' + pose(passo[0]) + '" alt="" width="380" height="380">' +
      '<div class="balao">' +
        '<b>' + passo[1] + '</b><br>' + passo[2] +
        '<div class="linha-btn" style="margin-top:.8rem">' +
          (i > 0 ? '<button class="btn btn-p" data-t="voltar">Voltar</button>' : '') +
          '<button class="btn btn-p btn-principal" data-t="proximo">' + (i === PASSOS.length - 1 ? 'Fechar' : 'Avançar') + '</button>' +
          '<button class="btn btn-p" data-t="sair">Depois eu vejo</button>' +
        '</div>' +
        '<span class="assina">Indawora, o Duelista · ' + (i + 1) + ' de ' + PASSOS.length + '</span>' +
      '</div>';
  }
  function abrir(de) {
    i = de || 0;
    if (!caixa) {
      caixa = document.createElement('div');
      caixa.className = 'mascote-canto surge';
      document.body.appendChild(caixa);
      caixa.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-t]');
        if (!b) return;
        if (b.dataset.t === 'proximo') { i++; if (i >= PASSOS.length) return fechar(); desenhar(); }
        else if (b.dataset.t === 'voltar') { i = Math.max(0, i - 1); desenhar(); }
        else fechar();
      });
    }
    desenhar();
  }
  function fechar() {
    if (caixa) { caixa.remove(); caixa = null; }
    try { localStorage.setItem('yu_tutorial_visto', '1'); } catch (e) {}
    fetch('/api/tutorial.php', { method: 'POST', headers: { 'X-CSRF': (document.querySelector('meta[name=csrf]') || {}).content || '' } });
  }

  document.querySelectorAll('[data-tutorial]').forEach(function (a) {
    a.addEventListener('click', function (ev) { ev.preventDefault(); abrir(0); });
  });
  window.abrirTutorial = abrir;

  var jaViu = false;
  try { jaViu = localStorage.getItem('yu_tutorial_visto') === '1'; } catch (e) {}
  if (window.__yuTutorial && !jaViu && !location.search.includes('duelo=')) {
    setTimeout(function () { abrir(0); }, 900);
  }
  if (location.search.indexOf('tutorial=1') >= 0) { setTimeout(function () { abrir(0); }, 200); }
})();
