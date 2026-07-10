function configurarMenuMobile() {
  const navToggle = document.getElementById('nav-toggle');
  const navMenu = document.getElementById('nav-menu');
  const linksMenu = document.querySelectorAll('.nav__menu a');

  if (!navMenu) return;

  // abre/fecha menu mobile
  if (navToggle) {
    navToggle.addEventListener('click', () => {
      const aberto = navMenu.classList.toggle('aberto');
      navToggle.setAttribute('aria-expanded', aberto ? 'true' : 'false');
    });
  }

  function atualizarLinkAtivo() {
    const paginaAtual = window.location.pathname.split('/').pop();
    const hashAtual = window.location.hash;

    linksMenu.forEach(link => {
      link.removeAttribute('aria-current');

      const href = link.getAttribute('href');
      if (!href || href === '#') return;

      // caso 1: link âncora da própria página
      if (href.startsWith('#')) {
        if (href === hashAtual || (!hashAtual && href === '#conteudo-principal')) {
          link.setAttribute('aria-current', 'page');
        }
      }

      // caso 2: link para outra página
      else {
        const paginaLink = href.split('/').pop();
        if (paginaLink === paginaAtual) {
          link.setAttribute('aria-current', 'page');
        }
      }
    });
  }

  // atualiza ao carregar a página
  atualizarLinkAtivo();

  // atualiza ao clicar nos links
  linksMenu.forEach(link => {
    link.addEventListener('click', () => {
      // fecha menu mobile
      if (navMenu.classList.contains('aberto')) {
        navMenu.classList.remove('aberto');
        if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
      }

      // se for âncora, atualiza o ativo logo após o hash mudar
      const href = link.getAttribute('href');
      if (href && href.startsWith('#')) {
        setTimeout(atualizarLinkAtivo, 0);
      }
    });
  });

  // caso o hash mude por scroll/manual
  window.addEventListener('hashchange', atualizarLinkAtivo);
}

document.addEventListener('DOMContentLoaded', configurarMenuMobile);