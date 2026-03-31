document.addEventListener('DOMContentLoaded', () => {

  const temaBtns = document.querySelectorAll('.tema-btn');
  let aktivnaTema = null;

  temaBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const tema = btn.dataset.tema;
      if (aktivnaTema === tema) {
        btn.classList.remove('active');
        aktivnaTema = null;
      } else {
        temaBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        aktivnaTema = tema;
      }
    });
  });

  const textarea = document.getElementById('sprava');
  const charCount = document.getElementById('char-count');
  const maxChars = 500;

  textarea.addEventListener('input', () => {
    const remaining = maxChars - textarea.value.length;
    charCount.textContent = `${textarea.value.length} / ${maxChars}`;
    charCount.style.color = remaining < 50 ? '#e05555' : '#aaa';
  });

  const form = document.getElementById('kontakt-form');
  const successMsg = document.getElementById('kontakt-success');
  const submitBtn = document.getElementById('kontakt-submit');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const temaInput = document.getElementById('tema-hidden');
    temaInput.value = aktivnaTema || 'Všeobecný dotaz';

    submitBtn.disabled = true;
    const povodnyText = submitBtn.textContent;
    submitBtn.textContent = 'Odosielam...';
    successMsg.classList.remove('visible');

    try {
      const formData = new FormData(form);

      const response = await fetch('send.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        form.reset();
        temaBtns.forEach(b => b.classList.remove('active'));
        aktivnaTema = null;
        charCount.textContent = `0 / ${maxChars}`;

        successMsg.textContent = result.message;
        successMsg.style.color = '#28a745';
        successMsg.classList.add('visible');

        submitBtn.textContent = 'Odoslané!';
        setTimeout(() => {
          submitBtn.textContent = povodnyText;
          submitBtn.disabled = false;
        }, 3000);

      } else {
        throw new Error(result.message || 'Nastala chyba');
      }

    } catch (err) {
      alert(err.message);
      submitBtn.textContent = 'Skúste znova';
      submitBtn.disabled = false;
    }
  });
});