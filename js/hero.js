const cells = document.querySelectorAll('.hero-grid .grid-cell');

cells.forEach((cell, i) => {
  const img = cell.querySelector('img');
  const col = i % 4;
  const row = Math.floor(i / 4);
  const delay = col * 80 + row * 60;

  // --- ENTRANCE ---
  setTimeout(() => {
    img.classList.add('entered');

    img.addEventListener('animationend', function onEntrance(e) {
      if (e.animationName !== 'bottle-entrance') return;
      img.removeEventListener('animationend', onEntrance);
      img.classList.remove('entered');
      img.style.opacity = '1';
      img.style.transform = 'translate(-50%, -50%) scale(1)';
      img.style.animation = 'none';
      img.dataset.ready = 'true';
    });
  }, delay);

  // --- HOVER SHAKE ---
  cell.addEventListener('mouseenter', () => {
    if (img.dataset.ready !== 'true') return;
    img.style.animation = 'none';
    void img.offsetWidth;
    img.style.animation = 'bottle-shake 0.55s ease-in-out forwards';
  });

  img.addEventListener('animationend', (e) => {
    if (e.animationName !== 'bottle-shake') return;
    img.style.animation = 'none';
    img.style.transform = 'translate(-50%, -50%) scale(1)';
  });
});
