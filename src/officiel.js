(function () {
  'use strict';
  const form = document.getElementById('contact-form');
  if (!form) return;
  const name = document.getElementById('contact-name');
  const contact = document.getElementById('contact-reply');
  const message = document.getElementById('contact-message');
  const button = document.getElementById('contact-prepare');
  const preview = document.getElementById('contact-preview');
  const feedback = document.getElementById('contact-feedback');
  button.disabled = false;
  form.addEventListener('submit', event => event.preventDefault());
  function validity() {
    name.setCustomValidity(name.value.trim() ? '' : 'Indiquez votre nom.');
    message.setCustomValidity(message.value.trim() ? '' : 'Ajoutez votre message.');
    const value = contact.value.trim();
    const email = /^[^\s@<>]+@[^\s@<>]+\.[^\s@<>]+$/.test(value);
    const phone = /^[+()\d .-]+$/.test(value) && value.replace(/\D/g,'').length >= 6 && value.replace(/\D/g,'').length <= 15;
    contact.setCustomValidity(email || phone ? '' : 'Indiquez un e-mail valide ou un numéro de téléphone.');
  }
  form.addEventListener('input', () => { preview.hidden = true; feedback.hidden = true; validity(); });
  button.addEventListener('click', () => {
    validity(); if (!form.reportValidity()) return;
    document.getElementById('contact-preview-text').textContent = 'Nom : ' + name.value.trim() + '\nRéponse à : ' + contact.value.trim() + '\n\n' + message.value.trim();
    preview.hidden = false;
    feedback.textContent = 'Aperçu préparé. Aucun message envoyé, aucune donnée enregistrée. Pour joindre le club, utilisez son adresse e-mail.';
    feedback.hidden = false; feedback.focus();
  });
  form.addEventListener('reset', () => { preview.hidden = true; feedback.hidden = true; name.setCustomValidity(''); contact.setCustomValidity(''); message.setCustomValidity(''); });
})();
