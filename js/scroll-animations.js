function initScrollAnimations() {
  const isMobile = window.innerWidth <= 768;

  // ---- DEFINÍCIE ANIMÁCIÍ ----
  // Každá skupina má: selektor, typ animácie a stagger delay
  const groups = [
    // O NÁS — bloky zľava
    { selector: '.o-nas-heading',   anim: 'fade-up',   stagger: 0 },
    { selector: '.o-nas-block',     anim: 'fade-left',  stagger: 0.12 },

    // PRODUKTY — striedavo zľava/sprava (desktop), fade-up (mobile)
    { selector: '.produkty-heading', anim: 'fade-up',   stagger: 0 },
    { selector: '.produkt-row',      anim: 'fade-alt',  stagger: 0.1 },

    // SPOLUPRÁCA — karty zdola po riadkoch
    { selector: '.spolupraca-heading',   anim: 'fade-up', stagger: 0 },
    { selector: '.spolupraca-logo-row',  anim: 'fade-up', stagger: 0.08 },
    { selector: '.spolupraca-intro',     anim: 'fade-up', stagger: 0.12 },
    { selector: '.spolupraca-card',      anim: 'fade-up', stagger: 0.08 },

    // KDE NÁS KÚPIŠ
    { selector: '.kde-heading',  anim: 'fade-up',    stagger: 0 },
    { selector: '.kde-intro',    anim: 'fade-up',    stagger: 0.08 },
    { selector: '.kde-mapa',     anim: 'fade-scale', stagger: 0.12 },

    // KONTAKT — polia zhora nadol
    { selector: '.kontakt-heading',        anim: 'fade-up', stagger: 0 },
    { selector: '.kontakt-intro',          anim: 'fade-up', stagger: 0.06 },
    { selector: '.kontakt-form .form-field', anim: 'fade-up', stagger: 0.08 },
    { selector: '.kontakt-submit',         anim: 'fade-up', stagger: 0.06 },

    // FOOTER — stĺpce zľava doprava
    { selector: '.footer-logo-col',     anim: 'fade-left', stagger: 0 },
    { selector: '.footer-nav-col',      anim: 'fade-up',   stagger: 0.1 },
    { selector: '.footer-contacts-col', anim: 'fade-right', stagger: 0.15 },
    { selector: '.footer-firma-col',    anim: 'fade-right', stagger: 0.2 },
  ];

  // ---- POČIATOČNÉ STAVY ----
  groups.forEach(({ selector, anim }) => {
    document.querySelectorAll(selector).forEach((el, i) => {
      // fade-alt — párne zľava, nepárne sprava (len desktop)
      let actualAnim = anim;
      if (anim === 'fade-alt') {
        actualAnim = isMobile ? 'fade-up' : (i % 2 === 0 ? 'fade-left' : 'fade-right');
      }
      el.dataset.anim = actualAnim;
      el.classList.add('anim-hidden', `anim-${actualAnim}`);
    });
  });

  // ---- INTERSECTION OBSERVER ----
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('anim-visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.1,
    rootMargin: '0px 0px -48px 0px'
  });

  // ---- PRIRADENIE STAGGER DELAYS ----
  groups.forEach(({ selector, stagger }) => {
    document.querySelectorAll(selector).forEach((el, i) => {
      el.style.transitionDelay = `${i * stagger}s`;
      observer.observe(el);
    });
  });
}

document.addEventListener('DOMContentLoaded', initScrollAnimations);
