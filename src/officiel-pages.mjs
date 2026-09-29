import { escapeHTML as e } from '../scripts/official-config.mjs';

const link = (href, label, external = false) => `<a class="button button-dark" href="${e(href)}"${external ? ' target="_blank" rel="noopener noreferrer"' : ''}>${e(label)} <span aria-hidden="true">↗</span></a>`;
const card = (title, text, action = '') => `<article class="action-card"><h2>${title}</h2><p>${text}</p>${action}</article>`;
const intro = (kicker, title, description) => `<header class="action-heading"><p class="eyebrow">${kicker}</p><h1>${title}</h1><p>${description}</p></header>`;
const empty = (title, text, action = '') => `<section class="empty-state"><h2>${title}</h2><p>${text}</p>${action}</section>`;

export function actionPages(config) {
  const calendar = config.google.calendarUrl;
  const teamCards = config.teams.map(team => card(e(team.name), `Catégorie : ${e(team.category)}${team.tags.length ? '<br>' + team.tags.map(e).join(' · ') : ''}`, link('./disponibilites.html#' + team.id, 'Disponibilités'))).join('');
  const formCards = config.teams.map(team => {
    const forms = config.google.forms.filter(form => form.teamId === team.id);
    return `<section class="action-card" id="${e(team.id)}"><h2>${e(team.name)}</h2>${forms.length ? forms.map(form => `<p>${e(form.label)}</p>${link(form.url,'Ouvrir le formulaire',true)}`).join('') : '<p>Le formulaire de cette équipe n’est pas encore disponible. Votre capitaine vous transmettra le lien.</p>'}</section>`;
  }).join('');
  return {
    'competitions.html': {
      title:'Compétitions', body:intro('JOUER EN ÉQUIPE','Le plaisir du match,<br>l’esprit du club.','Retrouvez les accès utiles pour préparer les rencontres et échanger avec votre capitaine.') +
      `<div class="action-grid">${card('Les équipes','Les équipes et leurs catégories seront présentées après confirmation par le club.',link('./equipes.html','Voir les équipes'))}${card('Les rendez-vous','Le calendrier du club sera la référence des dates et des lieux de rencontre.',link('./calendrier.html','Consulter le calendrier'))}${card('Votre disponibilité','Pour chaque demande, indiquez « Oui », « Non » ou « Je ne sais pas » dans le formulaire de votre équipe.',link('./disponibilites.html','Mes disponibilités'))}</div>` +
      empty('Envie de participer ?','Le club vous renseigne sur les possibilités selon votre expérience et votre niveau.',link('./contact.html','Contacter le club'))
    },
    'calendrier.html': {
      title:'Calendrier', body:intro('LES RENDEZ-VOUS','À vos agendas.','Les rencontres et les rendez-vous du club réunis dans un même calendrier.') +
      (calendar ? empty('Le calendrier du club','Consultez les événements, les lieux et les horaires sur Google Calendar. Le service s’ouvre dans un nouvel onglet.',link(calendar,'Ouvrir le calendrier',true)) : empty('Le calendrier arrive bientôt','Le calendrier du club n’a pas encore été relié à cette version. Pour connaître un prochain rendez-vous, contactez le club.',link('./contact.html','Se renseigner'))) +
      `<div class="action-links">${link('./competitions.html','Retour aux compétitions')}${link('./disponibilites.html','Mes disponibilités')}</div>`
    },
    'disponibilites.html': {
      title:'Mes disponibilités', body:intro('PRÉPARER LES RENCONTRES','Votre réponse compte.','Le capitaine vous transmet un lien pour votre équipe et la rencontre concernée. Aucun compte adhérent n’est nécessaire sur le site.') +
      `<div class="action-grid">${card('1. Ouvrir le bon formulaire','Vérifiez le nom de l’équipe et la rencontre indiqués dans le message de votre capitaine.')}${card('2. Choisir votre réponse','Sélectionnez votre nom dans la liste de votre équipe, puis « Oui », « Non » ou « Je ne sais pas ».')}${card('3. Confirmer dans le formulaire','Une réponse « Je ne sais pas » est différente d’une absence de réponse. Votre capitaine consulte ensuite la synthèse.')}</div>` +
      (formCards ? `<div class="action-grid">${formCards}</div>` : empty('Les formulaires arrivent bientôt','Aucun formulaire d’équipe n’est encore relié à cette version. Si vous avez déjà reçu un lien de votre capitaine, utilisez ce lien.')) +
      `<p class="notice">Vous n’utilisez pas WhatsApp ? Prévenez votre capitaine : il pourra vous contacter directement et vous indiquer comment répondre.</p>`
    },
    'equipes.html': {
      title:'Équipes', body:intro('ENSEMBLE SUR LE COURT','Les équipes du club.','Retrouvez ici les équipes et leurs catégories, après confirmation par le club.') +
      (teamCards ? `<div class="action-grid">${teamCards}</div>` : empty('Les équipes seront présentées ici','Leur composition n’est pas encore renseignée dans cette version. Les effectifs et les coordonnées des joueurs resteront réservés aux responsables autorisés.',link('./contact.html','Se renseigner sur les équipes'))) +
      `<div class="action-links">${link('./calendrier.html','Calendrier')}${link('./espace.html','Bureau / Capitaine')}</div>`
    },
    'espace.html': {
      title:'Bureau / Capitaine', body:intro('LES RESPONSABLES DU CLUB','Un espace pour<br>faire vivre les équipes.','L’espace réservé au bureau et aux capitaines est en préparation. La connexion n’est pas encore ouverte.') +
      `<div class="action-grid">${card('Administrateur','Gérer les comptes autorisés, leur activation et leurs périmètres d’accès.')}${card('Bureau','Consulter les informations de gestion autorisées et le suivi des équipes.')}${card('Capitaine','Retrouver uniquement ses équipes, leurs effectifs et la synthèse des disponibilités.')}</div>` +
      `<p class="notice">Aucun identifiant n’est demandé dans cet aperçu. Les données privées et les outils de gestion seront accessibles après activation de la connexion sécurisée.</p>` +
      `<div class="action-links">${link('./contact.html','Contacter le club')}${link('./index.html','Retour au club')}</div>`
    },
    'contact.html': {
      title:'Contact', body:intro('PARLONS TENNIS','Écrivez au club.','Une question sur les cours, les rencontres ou la vie du club ? Quelques mots suffisent.') +
      `<div class="action-grid"><section class="action-card"><h2>Votre message</h2><p class="notice">Aperçu du futur formulaire : la préparation ci-dessous ne transmet et ne conserve aucune donnée. Pour joindre le club dès maintenant, écrivez à <a href="mailto:${e(config.club.email)}">${e(config.club.email)}</a>.</p><form id="contact-form" class="contact-form"><div class="field"><label for="contact-name">Nom</label><input id="contact-name" autocomplete="off" required maxlength="80"></div><div class="field"><label for="contact-reply">E-mail ou téléphone</label><input id="contact-reply" type="text" autocomplete="off" required maxlength="150" aria-describedby="reply-help"><small id="reply-help">Un seul moyen de vous répondre suffit.</small></div><div class="field"><label for="contact-message">Message</label><textarea id="contact-message" rows="6" required maxlength="2000"></textarea></div><p class="muted">Votre saisie reste dans la page, le temps de cet aperçu. Elle n’est ni envoyée au club ni enregistrée dans le navigateur.</p><div class="form-actions"><button type="button" class="button button-dark" id="contact-prepare" disabled>Préparer l’aperçu</button><button type="reset" class="button">Effacer</button></div><noscript><p>Activez JavaScript pour essayer l’aperçu, ou utilisez l’adresse e-mail du club.</p></noscript></form><p id="contact-feedback" class="form-feedback" role="status" tabindex="-1" hidden></p><section id="contact-preview" class="contact-preview" hidden><h3>Relire le message</h3><p id="contact-preview-text" class="preview-text"></p></section></section>${card('Un contact direct',`<a href="mailto:${e(config.club.email)}">${e(config.club.email)}</a><br>${e(config.club.address)}<br>${e(config.club.postalCity)}<br><br><strong>Assistance pendant les tests</strong><br><span id="test-support-email">${e(config.contact.testSupport.email)}</span><br><small>Adresse prévue, activation à confirmer. Pour le moment, utilisez l’adresse du club ci-dessus.</small>`,link(config.links.tenup,'Le club sur Ten’Up',true))}</div>`
    }
  };
}
