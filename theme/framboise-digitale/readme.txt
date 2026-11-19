=== La Framboise Digitale ===
Thème WordPress sur mesure — version 1.8.2
Testé avec WordPress 7.1 et PHP 8.3 (minimum : WordPress 6.4, PHP 7.4).

== Installation ==

1. Faites une sauvegarde complète du site (fichiers + base de données).
2. Apparence → Thèmes → Ajouter → Téléverser un thème : choisissez framboise-digitale.zip.
   Si une version du thème est déjà installée, WordPress propose « Remplacer l'actif par la version téléversée » : acceptez.
3. Activez le thème (s'il ne l'est pas déjà). C'est tout :
   - les contenus mis en forme sont placés automatiquement dans les pages (à l'activation, ou à la première
     visite de l'administration si le thème était déjà actif) ;
   - le menu existant du site est repris, avec son déroulant « Expertises ».
4. Videz le cache du site s'il y en a un, puis vérifiez les pages.
5. Une fois le site vérifié, vous pouvez désactiver l'ancien constructeur de pages (Kubio).

Ce que fait la mise en place des contenus
- Chaque page est retrouvée par son adresse actuelle.
- Seul le contenu de la page est remplacé. L'adresse, le titre, la date et les réglages SEO ne changent pas.
- Le contenu précédent est sauvegardé. Apparence → Import La Framboise permet de vérifier, relancer ou annuler page par page.
- Si vous réactivez l'ancien thème, les pages non modifiées retrouvent automatiquement leur contenu d'origine.

Les pages sample-page, specs, info, page-projets et expertises ne sont pas concernées (pages vides ou de test) :
elles restent en ligne à la même adresse, avec leur titre.

== Gérer les sections d'une page (sans passer par l'éditeur) ==

Pages → Sections La Framboise, ou le lien « Sections » sous le nom d'une page dans la liste des pages,
ou le bouton « Gérer les sections de la page » dans l'éditeur, ou « Sections de la page » dans la barre noire
en haut du site quand vous regardez une page en étant connecté.

Chaque section de la page y apparaît comme une carte :
- Changer une image : bouton « Changer l'image » sous le visuel, puis choix dans la médiathèque (ou téléversement).
- Disposition : visuel à gauche et texte à droite, ou l'inverse.
- Fond de la section : blanc, aplat bleu nuit, aplat framboise ou aplat gris clair.
- Monter / Descendre pour changer l'ordre, Supprimer pour retirer la section.
- Ajouter une section : choisissez un type en bas de l'écran (texte + visuel, texte sur image de fond, texte,
  introduction, grande phrase, manifeste, mots-clés, galerie, frise de logos, mosaïque), remplissez les champs.
- Les sections simples proposent aussi leurs champs Titre, Texte et Boutons. Les sections riches (colonnes, listes,
  questions fréquentes, projets à la une) gardent leurs textes dans l'éditeur ; leurs images, leur fond et leur ordre
  se règlent ici.
Cliquez sur « Enregistrer la page » en bas de l'écran. Le grand visuel plein écran se règle dans l'éditeur de la page.

== Nouveautés de la version 1.8.1 ==

Taille du visuel dans « Texte + visuel » (Sections de la page)
- « Petit » : le visuel avec ses marges, comme jusqu'ici.
- « Moitié de la page » : le visuel occupe une moitié, le texte l'autre.
- « Bord à bord » : le visuel touche le bord de l'écran et occupe un peu plus de la moitié de la largeur ;
  le texte est posé de l'autre côté. L'image n'est jamais recadrée (elle garde ses proportions).
- « Disposition » choisit toujours le côté : visuel à gauche ou à droite.

== Nouveautés de la version 1.8.0 ==

Éditeur de texte (Sections de la page)
- Le champ « Texte » de chaque section est un éditeur comme un traitement de texte : gras, italique, souligné,
  barré, couleur, alignement (gauche, centré, droite, justifié), taille (Petit à Énorme), listes, citation,
  lien hypertexte, et dans « Formats » : interlignage serré / normal / aéré, interlettrage serré / large.
- L'onglet « Texte » de l'éditeur montre le code HTML si besoin.
- Pour centrer ou aligner à droite les titres d'une section : rubrique « Typographie », « Alignement des titres et textes ».

Chiffres clés
- Nouvelle section « Chiffres clés » : 3 ou 4 grands chiffres, chacun avec un intitulé et un petit texte.
  Les chiffres défilent jusqu'à leur valeur à l'apparition. « 356+ » ou « 98 % » : le signe s'affiche en italique framboise.

Carrousel dans « Texte + visuel »
- Ajoutez plusieurs visuels à une section « Texte + visuel » : ils deviennent un carrousel. L'image active est en avant,
  avec un reflet ; les voisines sont réduites, estompées et floutées. Défilement automatique, flèches, compteur,
  glisser au doigt ou à la souris, clic sur une image voisine pour l'amener au centre.
- Chaque visuel a sa légende (Nom · catégorie) et son lien ; l'ordre se règle en glissant les vignettes.

== Nouveautés de la version 1.7.0 ==

Mosaïque de projets (Sections de la page)
- Un lien peut maintenant être posé sur une vignette vidéo (il était perdu à l'enregistrement auparavant).
- Changer l'ordre des vignettes : attrapez l'image d'une vignette et faites-la glisser à sa nouvelle place,
  ou utilisez les boutons « ← Avant » et « Après → ». Le numéro de position s'affiche sur chaque vignette.

Typographie d'une section (Sections de la page, rubrique « Typographie »)
- Taille des titres (plus petite, plus grande, très grande), taille du texte (plus petite, plus grande),
  interlettrage (serré, large), interlignage (serré, aéré). Réglages propres à chaque section.

Fond de section
- « Couleur personnalisée » dans « Fond de la section » : un nuancier permet de choisir n'importe quelle couleur.
  Le texte passe automatiquement en blanc sur une couleur sombre.
- Pour une couleur à la place d'une photo dans « Texte sur image ou couleur de fond » : retirez le visuel,
  puis choisissez la couleur.

Son des vidéos
- Case « Avec le son » sous chaque vidéo (Sections de la page) et dans l'encadré du visuel d'ouverture.
- Une seule vidéo fait entendre son son à la fois : celle qui est la plus visible à l'écran. En scrollant, le son
  passe à la vidéo suivante et s'arrête quand la vidéo sort de l'écran, comme sur Instagram.
- Au chargement de la page, le son démarre immédiatement quand le navigateur l'autorise (en général dès que
  le visiteur a déjà cliqué sur une page du site, par exemple en arrivant depuis le menu). Sinon, la vidéo joue
  sans son, un bouton framboise « Activer le son » clignote doucement, et le son démarre au premier clic ou
  toucher, n'importe où sur la page. Le bouton haut-parleur permet ensuite de couper ou de remettre le son.
- Une même vidéo placée deux fois : cochez « Avec le son » sur une seule des deux.

Ambiance sonore de page
- Encadré « Ambiance sonore de la page » dans l'éditeur (colonne de droite) : choisissez un son (MP3 conseillé)
  et un volume (très léger, léger, moyen, présent).
- L'ambiance démarre au premier geste du visiteur, en fondu, et s'efface quand une vidéo joue avec le son.
  Un bouton « Son » en bas à gauche permet de la couper ; ce choix est retenu pendant la visite.

Pages retouchées
- Toute page modifiée par une personne (éditeur ou Sections de la page) n'est plus jamais remplacée
  par une mise à jour du thème.

== Vidéos ==

- Vidéo pleine largeur (césure) : section « Vidéo pleine largeur », 700 px de haut minimum sur ordinateur
  (sur téléphone, elle est limitée à la hauteur de l'écran). Elle s'ouvre au scroll et se lit automatiquement,
  sans son et en boucle, avec un bouton pause en bas à droite. Un titre facultatif s'affiche en bas à gauche.
  Les pages agence et expertise en comportent deux : la première avec la vidéo Cartier, la seconde avec une image
  à remplacer par une vidéo.
- Vidéos côte à côte : section « Vidéos côte à côte », trois par ligne, avec une légende sous chacune.
  Au-delà de trois, des flèches et un compteur (1/8) apparaissent ; sur téléphone, on les fait glisser au doigt.
  Un exemple figure sur la page L'agence.
- Remplacer une image par une vidéo : dans « Sections de la page », bouton « Changer le visuel » sous l'image,
  puis choisissez une vidéo. L'ancienne image devient automatiquement l'image affichée pendant le chargement.
  Cela fonctionne partout : texte + visuel, introduction, texte sur image de fond, galerie, projets à la une, mosaïque.
- Format conseillé : MP4 (H.264), sans piste son, 1920 px de large, 10 à 15 Mo maximum par vidéo
  (au-delà, le chargement devient lent, surtout sur téléphone). Une vidéo n'est lue que lorsqu'elle est à l'écran.

== Modifier une page ==

Les contenus sont des blocs WordPress standards (titres, paragraphes, images, vidéos, boutons, listes),
rangés dans des groupes nommés : Ouverture, Introduction, Texte + visuel, Galerie, Colonnes, etc.
Ouvrez la « Vue d'ensemble du document » de l'éditeur pour les voir par leur nom.
Pour ajouter une section : bouton « + » → Compositions → catégorie « La Framboise ».

Visuel d'ouverture (plein écran)
Dans la colonne de droite de l'éditeur de chaque page (onglet « Page »), l'encadré « Visuel d'ouverture (plein écran) » sert à choisir le grand visuel du haut de page,
sans toucher au texte :
- « Ajouter une image ou une vidéo » ouvre la médiathèque. Une vidéo se lit automatiquement, sans son, en boucle.
- Plusieurs visuels = diaporama ; les boutons Monter / Descendre règlent l'ordre.
- Cliquez sur le sujet principal de l'image (le produit) : il restera visible sur tous les écrans, mobile compris.
- Pour une vidéo, « Choisir l'image d'attente » définit l'image affichée pendant le chargement.
- « Remplacer » change le fichier, « Retirer » l'enlève. Sans aucun visuel, la page s'ouvre sur son titre seul.
- Nom et sous-titre s'affichent à droite du titre quand la page a plusieurs visuels.
Pensez à cliquer sur « Mettre à jour » pour enregistrer la page.

Titres : un mot en italique dans un titre s'affiche en italique framboise.

Pages projet (réglages dans la colonne de droite de l'éditeur, encadré « La Framboise »)
- Type de page : Projet.
- Nom du client, catégorie, adresse de la vignette, ordre d'affichage : utilisés par la galerie horizontale,
  la page portfolio et le lien « Projet suivant ».

Donner du rythme à une page (pages expertise)
- Texte + visuel : le visuel se place à gauche ; ajoutez la classe lfd-alt au groupe pour le passer à droite.
- Texte sur image de fond : groupe « Texte sur image de fond » (classe lfd-cover), l'image du groupe devient l'arrière-plan.
- Aplat de couleur : ajoutez au groupe la classe lfd-band-dark (bleu nuit), lfd-band-accent (framboise) ou lfd-band-soft (gris clair).
- Mots-clés en grand : groupe « Mots-clés » (classe lfd-words) contenant une liste.
Ces sections sont proposées dans Compositions → « La Framboise ».
Pour changer un visuel : cliquez sur l'image dans l'éditeur, puis « Remplacer ».

Mosaïque de projets (page portfolio) : dans le groupe « Mosaïque de projets », chaque image est une tuile ;
son lien mène à la page du projet et sa légende « Nom · catégorie » s'affiche dessus. L'ordre des images est l'ordre des tuiles.

Frise de logos : dans le groupe « Frise de logos », un paragraphe = un nom affiché en lettres ;
une image = un logo. Remplacez les noms par les fichiers de logos officiels quand vous les avez.

== Réglages ==

Apparence → Personnaliser → « La Framboise : textes et contacts » : e-mail, adresse, Instagram,
texte du pied de page, bloc de contact de fin de page, rideau d'ouverture.
Apparence → Menus : trois emplacements (menu principal, pied de page : menu, pied de page : services).
Sans menu attribué, le thème affiche les liens actuels du site.

== Codes courts ==

[lfd_projets titre="Nos projets"]  galerie de tous les projets qui défile à l'horizontale
[lfd_portfolio]                    grille de tous les projets

== Polices ==

Bricolage Grotesque, Hanken Grotesk, Instrument Serif et Jost (licence SIL Open Font License),
embarquées dans le thème : aucun appel à un serveur tiers.
