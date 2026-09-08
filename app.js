/* YU GI UAI OH — comportamento comum de todas as páginas.
   Submenus (DUELO e MENU), lupa de carta e confirmação de apagar. */
(function () {
  'use strict';

  // ---- submenus: um aberto por vez, fecha no Esc e no clique fora
  var abertos = [];
  function fecharTodos(menos) {
    document.querySelectorAll('.submenu').forEach(function (s) {
      if (s === menos) return;
      s.classList.remove('aberto');
      setTimeout(function () { if (!s.classList.contains('aberto')) s.hidden = true; }, 140);
      var b = document.querySelector('[data-sub="' + s.id + '"]');
      if (b) b.setAttribute('aria-expanded', 'false');
    });
  }
  document.querySelectorAll('[data-sub]').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      var alvo = document.getElementById(btn.dataset.sub);
      if (!alvo) return;
      var vaiAbrir = alvo.hidden || !alvo.classList.contains('aberto');
      fecharTodos(vaiAbrir ? alvo : null);
      if (vaiAbrir) {
        alvo.hidden = false;
        requestAnimationFrame(function () { alvo.classList.add('aberto'); });
        btn.setAttribute('aria-expanded', 'true');
      } else {
        fecharTodos(null);
      }
    });
  });
  document.addEventListener('click', function (ev) {
    if (!ev.target.closest('.submenu')) fecharTodos(null);
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { fecharTodos(null); fecharLupa(); }
  });

  // ---- lupa: clicar numa carta pequena abre a grande
  var lupa = document.getElementById('lupa');
  function fecharLupa() { if (lupa) { lupa.hidden = true; lupa.querySelector('img').src = ''; } }
  document.addEventListener('click', function (ev) {
    var alvo = ev.target.closest('[data-lupa]');
    if (alvo && lupa) {
      lupa.querySelector('img').src = alvo.dataset.lupa;
      lupa.hidden = false;
      return;
    }
    if (lupa && !lupa.hidden && ev.target.closest('.lupa')) fecharLupa();
  });

  // ---- apagar sempre pergunta antes
  document.querySelectorAll('[data-confirma]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.dataset.confirma)) ev.preventDefault();
    });
  });

  // ---- contagem regressiva da vitrine
  document.querySelectorAll('[data-conta]').forEach(function (el) {
    var s = parseInt(el.dataset.conta, 10) || 0;
    function passo() {
      if (s <= 0) { el.textContent = 'trocando…'; return; }
      var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
      el.textContent = h + 'h ' + String(m).padStart(2, '0') + 'min';
      s -= 60; setTimeout(passo, 60000);
    }
    passo();
  });
})();
