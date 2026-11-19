# La Framboise Digitale — thème WordPress sur mesure

Fiche de synthèse du projet, pour reprendre le travail plus tard (dans un Projet Claude ou avec un développeur).
État au 19 novembre 2026 : **thème version 1.8.2**, installé sur https://laframboisedigitale.fr/.

---

## 1. Le contexte

- **Site** : laframboisedigitale.fr, agence digitale luxe (Paris, Cannes). WordPress, auparavant thème Astra + constructeur Kubio.
- **Objectif** : un thème sur mesure, épuré, très animé au scroll (effet « waouh »), qui met en valeur les visuels des références clients.
- **Contraintes absolues** :
  - conserver **toutes les URL** du sitemap (`/page-sitemap.xml`, 37 pages) pour le SEO ;
  - conserver les **textes** existants ;
  - les pages restent des **pages** WordPress (pas des articles).
- **Sites de référence fournis** : omediaparis.com, cipher.tv, wearecollins.com, hoodzpahdesign.com.

## 2. Direction artistique validée

- **Fond blanc pur** (#FFFFFF), textes **noir bleuté** (#101A2B, pas de noir pur), gris bleuté pour les textes secondaires (#5A6577).
- **Un seul accent : framboise** (#D41B5E), pour les mots en italique des titres, les survols, les boutons actifs.
- **Typographies** (embarquées dans le thème, aucun appel à Google) :
  - Bricolage Grotesque (titres), Hanken Grotesk (texte), Instrument Serif italique (mots mis en avant), Jost (logo, menus, sur-titres).
- **Visuels** : jamais recadrés de façon disgracieuse. Le cadre prend le format réel de l'image ; en plein écran, un point focal garde le produit dans le cadre.
- Ce qui a été **refusé** en cours de route : fond noir (trop sombre), bandeaux de noms de clients qui défilent, texte en filet, point rose qui suit la souris.

## 3. Ce que fait le thème (fonctionnalités, version 1.8.2)

**Mise en page et animations**
- Ouverture plein écran (image, diaporama ou vidéo) qui se referme en cadre arrondi au scroll ; rideau d'ouverture sur l'accueil.
- Titres qui montent mot à mot, textes qui s'allument mot à mot, blocs texte + visuel qui se construisent en arrivant de part et d'autre avec un flou qui se dissipe.
- Panneaux projets empilés (accueil), galerie horizontale de tous les projets, mosaïque de projets (portfolio).
- Menu avec déroulant « Expertises » (repris du menu du site), menu plein écran sur téléphone.

**Sections disponibles** (écran Pages → Sections La Framboise)
- Texte + visuel (petit / moitié de page / bord à bord, à gauche ou à droite ; plusieurs visuels = carrousel avec reflet et voisines floutées).
- Texte sur image ou couleur de fond, Texte, Introduction en grand texte, Grande phrase, Manifeste, Mots-clés en grand.
- Galerie, Frise de logos, Mosaïque de projets (ordre par glisser-déposer, lien sur chaque vignette, y compris vidéo).
- Vidéo pleine largeur (700 px de haut minimum), Vidéos côte à côte (3 par ligne, flèches et compteur).
- Chiffres clés (3 ou 4 grands chiffres qui défilent jusqu'à leur valeur).

**Réglages par section** : fond (blanc, bleu nuit, framboise, gris clair, couleur personnalisée), taille des titres et du texte, interlettrage, interlignage, alignement.

**Édition**
- Champ « Texte » en éditeur de texte riche (gras, italique, souligné, alignement, taille, interlignage, lien…).
- Encadré « Visuel d'ouverture (plein écran) » dans l'éditeur de chaque page : image(s) ou vidéo, point focal au clic.
- Encadré « Ambiance sonore de la page ».

**Son**
- Case « Avec le son » par vidéo ; une seule vidéo sonore à la fois (la plus visible), comme Instagram.
- Le son démarre à l'ouverture quand le navigateur l'autorise ; sinon invitation « Activer le son » et démarrage au premier clic (règle imposée par tous les navigateurs).

**Sécurité des contenus**
- À l'activation, les pages reçoivent leur contenu mis en forme ; l'ancien contenu est sauvegardé et restaurable (Apparence → Import La Framboise).
- Une page retouchée à la main n'est jamais écrasée par une mise à jour du thème.
- Les styles de Kubio sont neutralisés tant que le thème est actif.

## 4. Organisation des fichiers de la sauvegarde

```
theme/framboise-digitale/        le thème (à zipper pour l'installer)
  style.css                      en-tête du thème (numéro de version)
  functions.php                  réglages, menus, polices, encadré « réglages de la page »
  header.php / footer.php / page.php / index.php / 404.php
  inc/render.php                 affichage de chaque type de section
  inc/sections.php               écran « Sections de la page » (formulaires, enregistrement)
  inc/hero-field.php             visuel d'ouverture + ambiance sonore
  inc/import.php                 mise en place des contenus, sauvegarde, restauration
  inc/patterns.php               modèles de sections dans l'éditeur
  assets/css/main.css            toute la mise en forme
  assets/js/main.js              toutes les animations, le son, les carrousels
  assets/fonts/                  polices embarquées
  data/pages.json                contenus mis en forme des 32 pages (générés)
framboise-digitale-1.8.2.zip     le thème prêt à téléverser dans WordPress
outils/                          scripts qui ont produit data/pages.json
  extract.py                     extrait les textes et images des pages du site d'origine
  build_data.py                  transforme ces contenus en blocs WordPress mis en forme
  content.json                   contenus extraits du site d'origine (avant le thème)
maquette/                        la pré-maquette HTML validée au départ (framboise.html + images)
LISEZ-MOI.txt                    notice d'utilisation du thème (installation, réglages)
PROJET.md                        ce document
```

## 5. Installer ou mettre à jour

1. Sauvegarde complète du site.
2. Apparence → Thèmes → Ajouter → Téléverser `framboise-digitale-1.8.2.zip` → « Remplacer l'actif par la version téléversée ».
3. Ouvrir n'importe quelle page de l'administration : un bandeau confirme la mise à jour des pages.
4. Vider le cache s'il y en a un.

Pour faire une nouvelle version : modifier les fichiers de `theme/framboise-digitale/`, augmenter le numéro dans `style.css` **et** dans `functions.php` (`LFD_VERSION`), puis zipper le dossier `framboise-digitale`.

## 6. Points ouverts

- **Kubio** : désactivé possible ; ne le supprimer qu'après quelques semaines et une sauvegarde. Seule la page `/specs/` (page de test en faux texte) en dépend encore.
- **Pages de test** `sample-page`, `specs`, `info`, `page-projets`, `expertises` : non mises en forme, à garder ou passer en brouillon.
- **Vidéos** : remplacer les visuels provisoires des césures vidéo par les vraies vidéos (MP4, sans piste son si muette, 10 à 15 Mo max).
- **Logos** de la frise Technologie & IA (WordPress, PrestaShop, Magento) : remplacer les noms en lettres par les fichiers officiels.
- **Images** : les PNG du site pèsent 1 à 2 Mo ; une extension de compression accélérerait le site.
- **Limites connues** : le reflet du carrousel n'apparaît pas dans Firefox ; le son ne peut jamais démarrer avant un premier geste sur iPhone.

## 7. Historique des versions

| Version | Apports |
|---|---|
| 1.0 | Premier thème : gabarits, animations, import des 32 pages |
| 1.1 | Mise en place automatique des contenus, menu Expertises, mosaïque, neutralisation de Kubio |
| 1.2 | Pages expertise : visuels, aplats de couleur, texte sur image de fond, mots-clés |
| 1.3 | Encadré « Visuel d'ouverture (plein écran) » |
| 1.4 | Écran « Sections de la page » |
| 1.5 | Correction de la mise à jour des pages |
| 1.6 | Vidéo pleine largeur, vidéos côte à côte, vidéos partout à la place des images |
| 1.7 | Lien sur vignette vidéo, glisser-déposer, typographie par section, couleur libre, son des vidéos, ambiance sonore |
| 1.7.1 | Son au chargement quand le navigateur l'autorise, invitation « Activer le son » |
| 1.8 | Éditeur de texte riche, chiffres clés, carrousel avec reflet |
| 1.8.1 | Taille du visuel : petit, moitié de page, bord à bord |
| 1.8.2 | Correction : la section ne se contracte plus quand on change la typographie |
