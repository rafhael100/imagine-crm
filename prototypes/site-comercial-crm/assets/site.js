(() => {
  'use strict';
  const veil = document.querySelector('#veil');
  const message = document.querySelector('#dialogmsg');
  const close = document.querySelector('#close');
  const understood = document.querySelector('#understood');
  const menu = document.querySelector('#menu');
  const mobileLinks = document.querySelector('#mobilelinks');
  let priorFocus = null;

  function hideDialog() {
    veil.classList.remove('show');
    document.body.style.overflow = '';
    if (priorFocus) priorFocus.focus();
    priorFocus = null;
  }

  function showDialog(button) {
    priorFocus = button;
    message.textContent = `Você selecionou “${button.dataset.action}”. Esta página está disponível apenas para avaliação visual. O cadastro, acesso ao CRM, contratos e pagamentos reais ainda não estão liberados.`;
    veil.classList.add('show');
    document.body.style.overflow = 'hidden';
    close.focus();
  }

  document.querySelectorAll('[data-action]').forEach(button => {
    button.addEventListener('click', () => showDialog(button));
  });
  close.addEventListener('click', hideDialog);
  understood.addEventListener('click', hideDialog);
  veil.addEventListener('click', event => {
    if (event.target === veil) hideDialog();
  });
  document.addEventListener('keydown', event => {
    if (veil.classList.contains('show')) {
      if (event.key === 'Escape') {
        event.preventDefault();
        hideDialog();
      } else if (event.key === 'Tab') {
        if (event.shiftKey && document.activeElement === close) {
          event.preventDefault();
          understood.focus();
        } else if (!event.shiftKey && document.activeElement === understood) {
          event.preventDefault();
          close.focus();
        }
      }
      return;
    }
    if (event.key === 'Escape' && mobileLinks.classList.contains('open')) {
      mobileLinks.classList.remove('open');
      menu.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-label', 'Abrir menu');
      menu.focus();
    }
  });
  menu.addEventListener('click', () => {
    const open = !mobileLinks.classList.contains('open');
    mobileLinks.classList.toggle('open', open);
    menu.setAttribute('aria-expanded', String(open));
    menu.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
  });
  mobileLinks.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      mobileLinks.classList.remove('open');
      menu.setAttribute('aria-expanded', 'false');
      menu.setAttribute('aria-label', 'Abrir menu');
    });
  });
})();
