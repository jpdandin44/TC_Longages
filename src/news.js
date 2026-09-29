'use strict';
(() => {
  const list = document.querySelector('#news-list');
  if (!list) return;
  const lightbox = document.createElement('dialog');
  lightbox.className = 'news-lightbox'; lightbox.setAttribute('aria-label', 'Affiche en grand format');
  const close = document.createElement('button');
  close.type = 'button'; close.className = 'button button-dark'; close.textContent = 'Fermer l’affiche';
  close.addEventListener('click', () => lightbox.close());
  const largeImage = document.createElement('img'); largeImage.className = 'news-poster';
  lightbox.append(close, largeImage); document.body.append(lightbox);
  function render() {
    list.replaceChildren();
    try {
      const posts = window.TCLCommunication.publicPosts();
      if (!posts.length) {
        const empty = document.createElement('p');
        empty.className = 'news-empty';
        empty.textContent = 'Aucune actualité validée dans ce prototype. Préparez une information dans l’espace communication, puis validez sa démonstration pour la voir ici.';
        list.append(empty); return;
      }
      for (const post of posts) {
        const article = document.createElement('article');
        article.className = 'news-card';
        const label = document.createElement('small');
        label.textContent = `${post.category} · Démonstration locale`;
        const title = document.createElement('h3'); title.textContent = post.title;
        const body = document.createElement('p'); body.textContent = post.body;
        article.append(label, title, body);
        if (post.image) {
          const image = document.createElement('img');
          image.className = 'news-poster'; image.src = post.image.dataUrl; image.alt = post.image.alt;
          image.loading = 'lazy'; article.insertBefore(image, body);
          const enlarge = document.createElement('button');
          enlarge.type = 'button'; enlarge.className = 'news-enlarge'; enlarge.textContent = 'Agrandir l’affiche';
          enlarge.setAttribute('aria-label', 'Agrandir l’affiche : ' + post.title);
          enlarge.addEventListener('click', () => { largeImage.src = post.image.dataUrl; largeImage.alt = post.image.alt; lightbox.showModal(); });
          article.append(enlarge);
        }
        if (post.link) {
          const link = document.createElement('a');
          link.textContent = 'En savoir plus ↗'; link.href = post.link;
          link.target = '_blank'; link.rel = 'noopener noreferrer'; article.append(link);
        }
        list.append(article);
      }
    } catch {
      const error = document.createElement('p');
      error.textContent = 'Les actualités locales ne peuvent pas être lues. Consultez l’espace communication ; aucune donnée n’a été effacée.';
      error.setAttribute('role', 'alert'); list.append(error);
    }
  }
  render();
  window.addEventListener('storage', render);
  window.addEventListener('pageshow', render);
})();
