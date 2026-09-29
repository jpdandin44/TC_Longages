---
project: TC_Longages
document_type: prototype-guide
title: Visiter et partager la démonstration du site
status: active
version: git
created: 2026-09-16
updated: 2026-09-17
owner: jpdandin
tags:
  - prototype
  - demonstration
  - partage
---

# Visiter le prototype complet

Cette démonstration permet d'examiner le site et le processus proposé avec des personnes, groupes et disponibilités fictifs. Elle ne constitue pas une ouverture des inscriptions. N'y saisir aucune donnée réelle. Les horaires marqués « exemple » ne sont pas les horaires du club.

Le logo JPEG, la photo réelle du court et l'affiche exemple ont été fournis par l'utilisateur pour cette présentation. Le logo est temporaire en attendant une version vectorielle. La photographie d'accueil est celle du court du club ; l'autre photographie demeure une illustration créditée. L'affiche est un support fourni, pas un dossier fictif inventé ni une actualité automatiquement publiée ; elle se charge seulement si vous choisissez de l'essayer.

## Ouvrir sur cet ordinateur

Le serveur de démonstration se lance depuis le projet avec `npm.cmd run demo`. Ouvrir ensuite [la visite guidée](http://127.0.0.1:4174/). Le serveur écoute uniquement sur cet ordinateur ; `Ctrl+C` l'arrête. Il ne remplace pas le serveur habituel du port 4173 ni ses restrictions d'accès au bureau.

## Partager le prototype

L'archive `tc-longages-prototype.zip` contient sept pages autonomes, un petit serveur local, un lanceur Windows, ce guide et les empreintes des pages. Aucun compte, mot de passe, dossier réel, sauvegarde ou export utilisateur n'y est inclus.

1. Transmettre l'archive par le moyen de son choix ; l'agent ne l'envoie à aucun tiers.
2. Extraire complètement l'archive dans un dossier.
3. Avec Node.js 22 ou plus récent installé, lancer `ouvrir-prototype.cmd` sous Windows, ou `node serveur-demo.mjs` depuis le dossier sur un autre système.
4. Ouvrir `http://127.0.0.1:4174/` dans le navigateur et laisser la fenêtre du serveur ouverte pendant la visite.

Le lien `127.0.0.1` fonctionne sur l'ordinateur où le serveur est lancé, pas sur un autre appareil. Chaque destinataire ouvre sa propre copie. La simple ouverture des HTML depuis le disque permet de voir les écrans, mais le partage des essais entre pages dépend alors du navigateur : utiliser le serveur pour le parcours complet.

Le serveur refuse les écritures et ne sert que les sept HTML prévus. La démonstration est librement consultable par la personne disposant de l'archive, sans compte réel. Elle ne doit pas être présentée comme un espace de gestion sécurisé à mettre en production.

## Parcours conseillé

1. **Visite guidée** : vue d'ensemble et accès à toutes les pages.
2. **Site du club** : consulter la vitrine, la photo du court et la FAQ tarifaire. Les neuf formules sont reprises des fiches 2026–2027 ; la condition des cours adultes à 125 ou 150 € et la remise famille restent à confirmer.
3. **Formulaire adhérent** : choisir un exemple adulte ou mineur, parcourir les cinq étapes, modifier les disponibilités puis déposer une demande fictive.
4. **Gestion des inscriptions** : retrouver cette demande, filtrer les dossiers et examiner les détails.
5. **Disponibilités et groupes** : comparer les possibilités, choisir un groupe compatible et confirmer une affectation fictive.
6. **Import/export** : examiner le lot d'import fictif, ses doublons/erreurs, puis confirmer la simulation. L'export CSV télécharge les exemples sélectionnés. L'import de fichiers réels et l'export XLS restent prévus, non livrés.
7. **Communication** : saisir un titre, ajouter un texte ou une affiche avec sa description, enregistrer le brouillon puis relire les quatre aperçus Site, Facebook, WhatsApp et ADOC avant de confirmer la validation. Facebook, WhatsApp, ADOC et la copie sont simulés dans cette visite ; aucun service de publication n'est appelé.
8. **Aperçu des actualités** : consulter les messages validés et leurs affiches entières dans la démonstration.

Les liens de consultation de la vitrine (Ten'Up, Facebook, itinéraire et messagerie) restent des liens ordinaires vers les services existants. Les ouvrir volontairement peut quitter la démonstration ; ils ne créent pas d'inscription ni de publication automatique.

## Essayer une image ou une affiche

Dans **Communication**, utiliser **Utiliser l’affiche exemple** pour charger le visuel fourni, ou choisir un fichier JPEG, PNG ou WebP depuis l'ordinateur. Une seule image accompagne chaque actualité. Le titre et la description de l'image sont obligatoires ; le texte peut rester vide si une affiche est présente.

Le fichier choisi peut atteindre 8 Mo. Le navigateur prépare une copie JPEG de 1 600 pixels maximum sur son grand côté, limitée à 500 ko ; les images enregistrées sont limitées à 2 Mo au total. Les transparences deviennent un fond blanc et les animations une image fixe, sans recadrage. Le quota du navigateur peut être atteint avant cette limite. L'original de votre fichier n'est pas modifié et aucune image n'est téléversée sur le serveur.

Enregistrer, vérifier l'affiche entière dans les aperçus, puis valider l'actualité. Après un remplacement, un retrait ou un changement de description, enregistrer et valider de nouveau. Une modification non enregistrée bloque validation et partage. Si le fichier est refusé, l'image courante est conservée. Si l'enregistrement manque d'espace, la sauvegarde précédente est conservée et la saisie reste à l'écran : réduire ou retirer une image avant de réessayer.

Recharger la page permet de vérifier la conservation dans ce navigateur. Depuis l'aperçu des actualités, **Agrandir l’affiche** permet de la relire en grand. L'export JSON éditorial inclut les images ; son import reste bloqué dans la démonstration. **Ajouter une image locale et importer un fichier JSON sont deux fonctions distinctes.** Les images n'apparaissent pas sur les autres appareils et ne rejoignent pas automatiquement l'archive préparée pour l'hébergement.

Les boutons WhatsApp restent simulés ici, même si l'aperçu contient une affiche. Dans le prototype protégé proposant un vrai lien WhatsApp, seul le texte serait prérempli : il faudrait joindre l'image manuellement dans WhatsApp lors d'un envoi réel.

## Essayer la préparation ADOC

Le quatrième aperçu reprend les champs vus sur la capture fournie : titre, contenu, photo et visibilité sur Ten'Up. Le choix **Non** est sélectionné par défaut. Choisir **Oui** change seulement l'exemple local ; enregistrer puis valider de nouveau pour pouvoir préparer cette révision.

Le compteur additionne le texte et le lien, avec un maximum de 2 000 caractères pour ADOC. Certains émojis comptent pour deux dans ce compteur prudent. Si le contenu est plus long, seul le bouton ADOC est bloqué : aucun texte n'est coupé et les autres aperçus restent utilisables. La photo est préparée localement en JPEG dans les limites décrites plus haut.

Après validation, le bouton de simulation affiche une préparation locale. Il n'ouvre ni ADOC ni Ten'Up, ne copie rien dans le presse-papiers et n'enregistre aucun article dans ces services. Il ne donne pas de statut « envoyé » et ne modifie aucune visibilité réelle.

## Essais et limites

Les essais restent dans ce navigateur, sous des clés distinctes de celles du prototype habituel et sur une autre origine. Ils ne sont pas partagés entre personnes. Le bouton « Réinitialiser les exemples » de la visite guidée remet seulement les données fictives à leur état initial. Un changement d'adresse, de port ou de profil ouvre un autre espace d'essai. Si le stockage est bloqué, la continuité entre pages peut être indisponible et le formulaire le signale.

Cette maquette ne fournit pas une API de données, une base partagée, une authentification de production, un code de reprise sécurisé, une signature numérique ou des contrôles administratifs complets. La finalisation affichée est une simulation, pas une preuve de paiement ou d'inscription. FAQ et formules utilisent le même relevé des deux fiches ; les règles des cours adultes à 125/150 €, la remise famille et les textes d'autorisation restent à valider. Les fiches ne précisent ni tarif de terrain, ni inclusion de licence, ni durée ou horaires des cours : la maquette n'en déduit pas.

Pour régénérer la visite : `npm.cmd run demo:build`. Pour générer la visite et son archive sous Windows : `npm.cmd run demo:package`. L'archivage utilise la bibliothèque ZIP .NET du poste, sans modifier sa politique d'exécution des scripts. Ces commandes écrivent uniquement les livrables locaux ; elles ne transfèrent rien sur Internet. Toute publication ultérieure reste soumise à l'accord explicite de l'utilisateur.
