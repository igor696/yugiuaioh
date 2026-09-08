/* =====================================================================
   A MESA, do lado do navegador.
   =====================================================================
   Esta camada NÃO decide regra nenhuma. Ela desenha o que o servidor
   mandou e devolve o que o jogador clicou. Toda validação — se pode
   invocar, se o ataque vale, quanto de dano sai — acontece no PHP. Se
   alguém abrir o console e chamar a API na mão, cai nas mesmas travas.
   ===================================================================== */
(function () {
  'use strict';
  var estado = null, escolhido = null, esperandoAlvo = null, ocupado = false;

  function arte(passcode) {
    return passcode ? '/cache/cartas/' + passcode + '.jpg' : window.YU.verso;
  }
  function el(tag, cls, txt) {
    var d = document.createElement(tag);
    if (cls) d.className = cls;
    if (txt != null) d.textContent = txt;
    return d;
  }

  // ------------------------------------------------------- conversa
  function chamar(acao, dados) {
    if (ocupado) return Promise.resolve(null);
    ocupado = true;
    var corpo = new URLSearchParams(Object.assign({ acao: acao, _csrf: window.YU.csrf }, dados || {}));
    return fetch('/api/duelo.php', { method: 'POST', body: corpo })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
        ocupado = false;
        if (!res.ok) { avisar(res.j.erro || 'Não deu.'); return null; }
        if (res.j.estado) { estado = res.j.estado; desenhar(); }
        if (res.j.pede_descarte) pedirDescarte(res.j.pede_descarte);
        if (res.j.reliquia) mostrarReliquia(res.j.reliquia);
        if (res.j.revelar) mostrarCartas('Mão do oponente', res.j.revelar);
        if (estado && estado.fim) fimDeDuelo(res.j.resumo);
        return res.j;
      })
      .catch(function () { ocupado = false; avisar('Falha de conexão com a mesa.'); return null; });
  }

  function avisar(texto) {
    var d = document.getElementById('dica-fase');
    d.textContent = texto;
    d.style.color = 'var(--carmim)';
    setTimeout(function () { d.style.color = ''; atualizarDica(); }, 3200);
  }

  // -------------------------------------------------------- desenho
  function zonaMonstro(z, meu, indice) {
    var d = el('div', 'zona' + (z ? ' cheia' : '') + (z && z.pos === 'defesa' ? ' defesa' : '') + (z && z.pos === 'virada' ? ' defesa' : ''));
    d.dataset.zona = indice;
    d.dataset.lado = meu ? 'eu' : 'ele';
    if (!z) { d.textContent = meu ? 'monstro' : ''; return d; }
    if (z.oculto || z.pos === 'virada') {
      d.classList.add('virada');
      if (meu && z.nome) { var s = el('div', 'stat', z.nome); d.appendChild(s); }
      return d;
    }
    var img = new Image(); img.src = arte(z.passcode); img.alt = z.nome || '';
    d.appendChild(img);
    d.appendChild(el('div', 'stat', (z.pos === 'defesa' ? 'DEF ' + z.def : 'ATK ' + z.atk)));
    if (z.atacou) d.style.opacity = '.62';
    d.dataset.lupa = arte(z.passcode);
    return d;
  }
  function zonaMagia(z, meu, indice) {
    var d = el('div', 'zona' + (z ? ' cheia' : ''));
    d.dataset.mag = indice; d.dataset.lado = meu ? 'eu' : 'ele';
    if (!z) { d.textContent = meu ? 'magia' : ''; return d; }
    if (z.oculto) { d.classList.add('virada'); return d; }
    var img = new Image(); img.src = arte(z.passcode); img.alt = z.nome || '';
    d.appendChild(img);
    if (z.virada) d.style.filter = 'brightness(.8)';
    d.dataset.lupa = arte(z.passcode);
    return d;
  }

  function desenhar() {
    if (!estado) return;
    var eu = estado.eu, ele = estado.ele;

    document.querySelector('#pv-eu .n').textContent = eu.pv;
    document.querySelector('#pv-ele .n').textContent = ele.pv;
    document.getElementById('pv-eu').classList.toggle('baixo', eu.pv <= 2000);
    document.getElementById('pv-ele').classList.toggle('baixo', ele.pv <= 2000);
    document.getElementById('mao-ele').textContent = ele.mao;
    document.getElementById('deck-ele').textContent = ele.deck;
    document.getElementById('cem-ele').textContent = ele.cemiterio;
    document.getElementById('deck-eu').textContent = eu.deck;
    document.getElementById('cem-eu').textContent = eu.cemiterio;
    document.getElementById('extra-eu').textContent = eu.extra;

    var monEle = document.getElementById('mon-ele'); monEle.innerHTML = '';
    var magEle = document.getElementById('mag-ele'); magEle.innerHTML = '';
    var monEu = document.getElementById('mon-eu'); monEu.innerHTML = '';
    var magEu = document.getElementById('mag-eu'); magEu.innerHTML = '';

    // as fileiras do adversário aparecem espelhadas, como numa mesa de verdade
    magEle.appendChild(campoZona(ele.campo, 'campo'));
    for (var i = 4; i >= 0; i--) magEle.appendChild(zonaMagia(ele.mag[i], false, i));
    magEle.appendChild(deckZona(ele.deck, 'deck'));
    for (i = 4; i >= 0; i--) monEle.appendChild(zonaMonstro(ele.mon[i], false, i));
    monEle.insertBefore(deckZona(ele.cemiterio, 'cemit.'), monEle.firstChild);
    monEle.appendChild(deckZona(ele.extra, 'adic.'));

    monEu.appendChild(deckZona(eu.extra, 'adic.'));
    for (i = 0; i < 5; i++) monEu.appendChild(zonaMonstro(eu.mon[i], true, i));
    monEu.appendChild(deckZona(eu.cemiterio, 'cemit.'));
    magEu.appendChild(campoZona(eu.campo, 'campo'));
    for (i = 0; i < 5; i++) magEu.appendChild(zonaMagia(eu.mag[i], true, i));
    magEu.appendChild(deckZona(eu.deck, 'deck'));

    var mao = document.getElementById('mao-eu'); mao.innerHTML = '';
    eu.mao.forEach(function (c, idx) {
      var d = el('div', 'carta-mao');
      d.dataset.num = c.num; d.dataset.idx = idx;
      var img = new Image(); img.src = arte(c.passcode); img.alt = c.nome;
      d.appendChild(img);
      d.title = c.nome + (c.tipo === 'Monster' ? ' · Nv ' + c.nivel + ' · ATK ' + c.atk + ' / DEF ' + c.def : ' · ' + c.tipo);
      if (escolhido && escolhido.tipo === 'mao' && escolhido.idx === idx) d.classList.add('escolhida');
      mao.appendChild(d);
    });

    document.querySelectorAll('#fases span').forEach(function (s) {
      s.classList.toggle('agora', s.dataset.f === estado.fase);
    });
    var log = document.getElementById('log');
    log.innerHTML = '';
    estado.log.forEach(function (l) { log.appendChild(el('p', l.k, l.t)); });
    log.scrollTop = log.scrollHeight;

    document.getElementById('btn-fase').disabled = !estado.minha_vez;
    document.getElementById('btn-pular').hidden = estado.fase !== 'principal1';
    atualizarDica();
  }

  function deckZona(n, rot) {
    var d = el('div', 'zona zona-especial' + (n ? ' cheia' : ''));
    d.innerHTML = '<div style="text-align:center;font-family:var(--cond)"><b style="font-size:1.1rem;color:var(--ouro)">' + n + '</b><br>' + rot + '</div>';
    return d;
  }
  function campoZona(c, rot) {
    var d = el('div', 'zona zona-especial' + (c ? ' cheia' : ''));
    if (c) { var img = new Image(); img.src = arte(c.passcode); d.appendChild(img); d.title = c.nome; }
    else d.textContent = rot;
    return d;
  }

  function atualizarDica() {
    if (!estado) return;
    var d = document.getElementById('dica-fase');
    if (estado.fim) { d.textContent = 'Duelo encerrado.'; return; }
    if (!estado.minha_vez) { d.textContent = 'Vez do oponente.'; return; }
    if (esperandoAlvo) { d.textContent = esperandoAlvo.dica; return; }
    var t = {
      compra: 'Fase de Compra.',
      apoio: 'Fase de Apoio — avance quando quiser.',
      principal1: 'Fase Principal 1. Clique numa carta da mão para jogá-la.',
      batalha: 'Fase de Batalha. Clique num monstro seu em ataque e depois no alvo.',
      principal2: 'Fase Principal 2. Ainda dá para invocar e baixar.',
      final: 'Fase Final.'
    }[estado.fase] || '';
    d.textContent = t;
  }

  // -------------------------------------------------------- janelinha
  function janela(titulo, corpoHtml, botoes) {
    var j = document.getElementById('janela');
    j.innerHTML =
      '<div style="position:fixed;inset:0;z-index:140;background:rgba(8,5,14,.8);display:grid;place-items:center;padding:1.2rem">' +
        '<div class="caixa caixa-ouro surge" style="max-width:min(620px,94vw);max-height:88vh;overflow:auto">' +
          '<h2>' + titulo + '</h2>' + corpoHtml +
          '<div class="linha-btn" style="margin-top:1rem">' + (botoes || '<button class="btn" data-fechar>Fechar</button>') + '</div>' +
        '</div>' +
      '</div>';
    j.querySelectorAll('[data-fechar]').forEach(function (b) {
      b.addEventListener('click', function () { j.innerHTML = ''; });
    });
    return j;
  }
  function mostrarCartas(titulo, cartas) {
    var html = '<div class="grade-cartas">';
    (cartas || []).forEach(function (c) {
      html += '<div class="mini"><img src="' + arte(c.passcode) + '" alt="' + (c.nome || '') + '" title="' + (c.nome || '') + '"></div>';
    });
    janela(titulo, html + '</div>');
  }
  function mostrarReliquia(r) {
    if (r.revelar_mao) return mostrarCartas(r.nome + ' — mão do oponente', r.revelar_mao);
    if (r.topo) return mostrarCartas(r.nome + ' — topo do seu Deck', r.topo);
    if (r.revelar_baixadas) return mostrarCartas(r.nome + ' — as baixadas dele', Object.values(r.revelar_baixadas));
    if (r.pede_busca) return avisar(r.nome + ': escolha a carta na lista do seu Deck.');
  }

  function pedirDescarte(quantos) {
    var html = '<p>Sua mão passou de 6 cartas. Escolha <b>' + quantos + '</b> para descartar (manual, pág. 23).</p><div class="grade-cartas" id="desc">';
    estado.eu.mao.forEach(function (c) {
      html += '<div class="mini" data-n="' + c.num + '" title="' + c.nome + '"><img src="' + arte(c.passcode) + '" alt=""></div>';
    });
    var j = janela('Descartar', html + '</div>', '<button class="btn btn-principal" id="ok-descarte" disabled>Descartar</button>');
    var pick = [];
    j.querySelectorAll('#desc .mini').forEach(function (m) {
      m.addEventListener('click', function () {
        var n = parseInt(m.dataset.n, 10);
        var i = pick.indexOf(n);
        if (i >= 0) { pick.splice(i, 1); m.style.outline = ''; }
        else if (pick.length < quantos) { pick.push(n); m.style.outline = '2px solid var(--turquesa)'; }
        j.querySelector('#ok-descarte').disabled = pick.length !== quantos;
      });
    });
    j.querySelector('#ok-descarte').addEventListener('click', function () {
      document.getElementById('janela').innerHTML = '';
      chamar('descartar', { cartas: JSON.stringify(pick) });
    });
  }

  function fimDeDuelo(resumo) {
    var venceu = estado.fim.vencedor === 0;
    var motivo = { pv: 'Pontos de Vida zerados', deck: 'Deck vazio na hora de comprar', desistencia: 'Desistência' }[estado.fim.motivo] || '';
    var html = '<p style="font-family:var(--serif);font-size:1.2rem">' +
      (venceu ? 'Você venceu.' : 'Você perdeu.') + ' <span class="dica">(' + motivo + ')</span></p>';
    if (resumo) {
      html += '<ul style="line-height:1.8">';
      if (resumo.oneub) html += '<li><b>+' + resumo.oneub + '</b> ONEUB</li>';
      if (resumo.reliquia) html += '<li>Relíquia conquistada: <b>' + resumo.reliquia + '</b></li>';
      if (resumo.peca) html += '<li>Uma <b>Peça do Enigma</b> caiu no seu cofre.</li>';
      if (resumo.enigma) html += '<li><b>O Enigma do Milênio está inteiro.</b></li>';
      if (resumo.booster) html += '<li>Pacote de prêmio: <b>' + resumo.booster.nome + '</b></li>';
      (resumo.cartas || []).forEach(function (c) { html += '<li>Carta: <b>' + c.nome + '</b></li>'; });
      (resumo.conquistas || []).forEach(function (n) { html += '<li>Conquista nova: <b>nº ' + n + '</b></li>'; });
      html += '</ul>';
    }
    janela(venceu ? 'Duelo vencido' : 'Duelo perdido', html,
      '<a class="btn btn-principal" href="/campanha.php">Voltar à campanha</a>' +
      '<a class="btn" href="/painel.php">Painel</a>');
  }

  // -------------------------------------------------------- cliques
  document.getElementById('mao-eu').addEventListener('click', function (ev) {
    var c = ev.target.closest('.carta-mao');
    if (!c || !estado || !estado.minha_vez) return;
    var carta = estado.eu.mao[parseInt(c.dataset.idx, 10)];
    abrirMenuDaMao(carta, parseInt(c.dataset.idx, 10));
  });

  function abrirMenuDaMao(c, idx) {
    var ehMonstro = c.tipo === 'Monster';
    var precisa = ehMonstro ? (c.nivel >= 7 ? 2 : (c.nivel >= 5 ? 1 : 0)) : 0;
    var botoes = '';
    if (ehMonstro) {
      botoes += '<button class="btn btn-principal" data-a="invocar">Invocar' + (precisa ? ' (' + precisa + ' tributo)' : '') + '</button>';
      botoes += '<button class="btn" data-a="baixar">Baixar virado</button>';
    } else {
      if (c.tipo !== 'Trap') botoes += '<button class="btn btn-principal" data-a="ativar">Ativar</button>';
      botoes += '<button class="btn" data-a="baixar_magia">Baixar</button>';
    }
    botoes += '<button class="btn" data-fechar>Cancelar</button>';

    var info = '<div style="display:flex;gap:1rem;align-items:flex-start">' +
      '<img src="' + arte(c.passcode) + '" alt="" style="width:150px;border-radius:8px">' +
      '<div><p style="margin:0 0 .4rem"><b>' + c.nome + '</b></p>' +
      '<p class="dica" style="margin:0">' + c.tipo + (c.sub ? ' · ' + c.sub : '') +
      (ehMonstro ? '<br>Nível ' + c.nivel + ' · ATK ' + c.atk + ' / DEF ' + c.def : '') + '</p>' +
      (precisa ? '<p class="dica" style="margin-top:.4rem">Pede ' + precisa + ' tributo(s): clique nos seus monstros depois.</p>' : '') +
      '</div></div>';

    var j = janela('Jogar carta', info, botoes);
    j.querySelectorAll('[data-a]').forEach(function (b) {
      b.addEventListener('click', function () {
        document.getElementById('janela').innerHTML = '';
        var a = b.dataset.a;
        if (a === 'invocar' && precisa > 0) return pedirTributos(c, precisa);
        if (a === 'invocar') return chamar('invocar', { num: c.num, modo: 'invocar', tributos: '[]' });
        if (a === 'baixar') return chamar('invocar', { num: c.num, modo: 'baixar', tributos: '[]' });
        if (a === 'baixar_magia') return chamar('baixar_magia', { num: c.num });
        if (a === 'ativar') return chamar('ativar', { num: c.num });
      });
    });
  }

  function pedirTributos(c, quantos) {
    var pick = [];
    esperandoAlvo = { dica: 'Escolha ' + quantos + ' monstro(s) seu(s) para oferecer como Tributo.' };
    atualizarDica();
    function limpar() {
      esperandoAlvo = null;
      document.querySelectorAll('#mon-eu .zona').forEach(function (z) { z.classList.remove('escolhida'); });
      document.removeEventListener('click', ouvir, true);
      atualizarDica();
    }
    function ouvir(ev) {
      var z = ev.target.closest('#mon-eu .zona.cheia');
      if (!z) return;
      ev.preventDefault(); ev.stopPropagation();
      var i = parseInt(z.dataset.zona, 10);
      var p = pick.indexOf(i);
      if (p >= 0) { pick.splice(p, 1); z.classList.remove('escolhida'); }
      else { pick.push(i); z.classList.add('escolhida'); }
      if (pick.length === quantos) {
        limpar();
        chamar('invocar', { num: c.num, modo: 'invocar', tributos: JSON.stringify(pick) });
      }
    }
    document.addEventListener('click', ouvir, true);
  }

  // clique nas zonas: ataque, mudança de posição e ativação de baixada
  document.querySelector('.tapete').addEventListener('click', function (ev) {
    if (!estado || !estado.minha_vez || esperandoAlvo) return;
    var z = ev.target.closest('.zona');
    if (!z) return;

    // ---- ataque: primeiro o meu monstro, depois o alvo
    if (estado.fase === 'batalha') {
      if (z.dataset.lado === 'eu' && z.dataset.zona !== undefined && z.classList.contains('cheia')) {
        var i = parseInt(z.dataset.zona, 10);
        var m = estado.eu.mon[i];
        if (!m || m.pos !== 'ataque' || m.atacou) return avisar('Só monstro em Posição de Ataque que ainda não atacou.');
        escolhido = { tipo: 'atacante', zona: i };
        document.querySelectorAll('.zona').forEach(function (x) { x.classList.remove('escolhida'); });
        z.classList.add('escolhida');
        var temAlvo = estado.ele.mon.some(function (x) { return x; });
        if (!temAlvo) { escolhido = null; return chamar('atacar', { zona: i, alvo: '' }); }
        avisar('Agora clique no monstro que vai receber o ataque.');
        return;
      }
      if (escolhido && escolhido.tipo === 'atacante' && z.dataset.lado === 'ele' && z.dataset.zona !== undefined && z.classList.contains('cheia')) {
        var alvo = parseInt(z.dataset.zona, 10), at = escolhido.zona;
        escolhido = null;
        document.querySelectorAll('.zona').forEach(function (x) { x.classList.remove('escolhida'); });
        return chamar('atacar', { zona: at, alvo: alvo });
      }
      return;
    }

    // ---- fase principal: posição e ativação
    if (estado.fase === 'principal1' || estado.fase === 'principal2') {
      if (z.dataset.lado === 'eu' && z.dataset.zona !== undefined && z.classList.contains('cheia')) {
        var idx = parseInt(z.dataset.zona, 10), mon = estado.eu.mon[idx];
        if (!mon) return;
        var b = mon.pos === 'virada'
          ? '<button class="btn btn-principal" data-p="ataque">Invocar por Virar</button>'
          : '<button class="btn btn-principal" data-p="' + (mon.pos === 'ataque' ? 'defesa' : 'ataque') + '">Mudar para ' + (mon.pos === 'ataque' ? 'Defesa' : 'Ataque') + '</button>';
        var j = janela(mon.nome || 'Monstro baixado',
          '<p class="dica">Cada monstro muda de posição uma vez por turno, e nunca no turno em que entrou.</p>',
          b + '<button class="btn" data-fechar>Fechar</button>');
        j.querySelectorAll('[data-p]').forEach(function (btn) {
          btn.addEventListener('click', function () {
            document.getElementById('janela').innerHTML = '';
            chamar('posicao', { zona: idx, para: btn.dataset.p });
          });
        });
        return;
      }
      if (z.dataset.lado === 'eu' && z.dataset.mag !== undefined && z.classList.contains('cheia')) {
        var mi = parseInt(z.dataset.mag, 10), mg = estado.eu.mag[mi];
        if (!mg) return;
        var j2 = janela(mg.nome || 'Carta baixada', '<p class="dica">Armadilha só ativa a partir do turno seguinte ao que foi baixada.</p>',
          '<button class="btn btn-principal" data-ativar>Ativar</button><button class="btn" data-fechar>Fechar</button>');
        j2.querySelector('[data-ativar]').addEventListener('click', function () {
          document.getElementById('janela').innerHTML = '';
          chamar('ativar', { num: mg.num, zona_magia: mi });
        });
      }
    }
  });

  document.getElementById('btn-fase').addEventListener('click', function () { chamar('fase'); });
  document.getElementById('btn-pular').addEventListener('click', function () { chamar('pular_batalha'); });
  document.getElementById('btn-desistir').addEventListener('click', function () {
    if (window.confirm('Desistir conta como derrota. Tem certeza?')) chamar('desistir');
  });
  document.querySelectorAll('[data-reliquia]').forEach(function (b) {
    b.addEventListener('click', function () { chamar('reliquia', { slug: b.dataset.reliquia }).then(function () { b.disabled = true; }); });
  });

  chamar('estado');
})();
