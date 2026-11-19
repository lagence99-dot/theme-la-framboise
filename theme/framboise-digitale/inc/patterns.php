<?php
/**
 * Modèles de sections proposés dans l'éditeur (bouton « + », onglet Compositions, catégorie « La Framboise »).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}
		register_block_pattern_category( 'lfd', array( 'label' => 'La Framboise' ) );
		$img = esc_url( get_template_directory_uri() . '/screenshot.png' );
		$g   = function ( $cls, $name, $inner ) {
			return '<!-- wp:group {"metadata":{"name":"' . $name . '"},"className":"' . $cls . '"} -->' . "\n" . '<div class="wp-block-group ' . $cls . '">' . $inner . '</div>' . "\n" . '<!-- /wp:group -->';
		};
		$h   = function ( $level, $text ) {
			$a = 2 === $level ? '' : ' {"level":' . $level . '}';
			return '<!-- wp:heading' . $a . ' -->' . "\n" . '<h' . $level . ' class="wp-block-heading">' . $text . '</h' . $level . '>' . "\n" . '<!-- /wp:heading -->';
		};
		$p   = function ( $text, $cls = '' ) {
			return $cls
				? '<!-- wp:paragraph {"className":"' . $cls . '"} -->' . "\n" . '<p class="' . $cls . '">' . $text . '</p>' . "\n" . '<!-- /wp:paragraph -->'
				: '<!-- wp:paragraph -->' . "\n" . '<p>' . $text . '</p>' . "\n" . '<!-- /wp:paragraph -->';
		};
		$i   = '<!-- wp:image {"sizeSlug":"full","linkDestination":"none"} -->' . "\n" . '<figure class="wp-block-image size-full"><img src="' . $img . '" alt=""/></figure>' . "\n" . '<!-- /wp:image -->';
		$b   = '<!-- wp:buttons -->' . "\n" . '<div class="wp-block-buttons"><!-- wp:button -->' . "\n" . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">En savoir plus</a></div>' . "\n" . '<!-- /wp:button --></div>' . "\n" . '<!-- /wp:buttons -->';

		$patterns = array(
			'ouverture' => array( 'Ouverture plein écran (images ou vidéos)', $g( 'lfd-hero', 'Ouverture', $i . $p( 'Sur-titre', 'lfd-eyebrow' ) . $h( 1, 'Titre de la page' ) ) ),
			'intro'     => array( 'Introduction en grand texte', $g( 'lfd-intro', 'Introduction', $p( 'Phrase d’introduction affichée en grand, qui s’allume mot à mot au scroll.', 'lfd-big' ) . $p( 'Texte complémentaire.' ) . $b ) ),
			'feat'      => array( 'Texte + visuel (visuel à gauche)', $g( 'lfd-feat', 'Texte + visuel', $i . $h( 2, 'Titre du bloc' ) . $p( 'Texte du bloc.' ) . $b ) ),
			'feat-alt'  => array( 'Texte + visuel (visuel à droite)', $g( 'lfd-feat lfd-alt', 'Texte + visuel', $i . $h( 2, 'Titre du bloc' ) . $p( 'Texte du bloc.' ) . $b ) ),
			'texte'     => array( 'Texte : titre à gauche, contenu à droite', $g( 'lfd-text', 'Texte', $h( 2, 'Titre' ) . $p( 'Contenu : paragraphes, listes, boutons, questions fréquentes (bloc Détails).' ) ) ),
			'colonnes'  => array( 'Colonnes', $g( 'lfd-cols', 'Colonnes', $h( 2, 'Titre de la section' ) . $g( 'lfd-col', 'Colonne', $h( 3, 'Colonne 1' ) . $p( 'Texte.' ) ) . $g( 'lfd-col', 'Colonne', $h( 3, 'Colonne 2' ) . $p( 'Texte.' ) ) . $g( 'lfd-col', 'Colonne', $h( 3, 'Colonne 3' ) . $p( 'Texte.' ) ) ) ),
			'galerie'   => array( 'Galerie de visuels (images ou vidéos)', $g( 'lfd-gallery', 'Galerie', $i . $i . $i ) ),
			'videoband' => array( 'Vidéo pleine largeur (césure, 700 px de haut)', $g( 'lfd-videoband', 'Vidéo pleine largeur', $i ) ),
			'videos3'   => array( 'Vidéos côte à côte (3 par ligne)', $g( 'lfd-videos3', 'Vidéos côte à côte', $i . $i . $i ) ),
			'cover'     => array( 'Texte sur image de fond', $g( 'lfd-cover', 'Texte sur image de fond', $i . $h( 2, 'Titre posé sur l’image' ) . $p( 'Texte court.' ) ) ),
			'aplat'     => array( 'Texte sur aplat sombre', $g( 'lfd-text lfd-band-dark', 'Texte', $h( 2, 'Titre' ) . $p( 'Contenu sur fond sombre. Remplacez « lfd-band-dark » par « lfd-band-accent » (framboise) ou « lfd-band-soft » (gris clair).' ) ) ),
			'mots'      => array( 'Mots-clés sur aplat framboise', '<!-- wp:group {"metadata":{"name":"Mots-clés"},"className":"lfd-words lfd-band-accent"} -->' . "\n" . '<div class="wp-block-group lfd-words lfd-band-accent"><!-- wp:list -->' . "\n" . '<ul class="wp-block-list"><!-- wp:list-item -->' . "\n" . '<li>Mot-clé 1</li>' . "\n" . '<!-- /wp:list-item --><!-- wp:list-item -->' . "\n" . '<li>Mot-clé 2</li>' . "\n" . '<!-- /wp:list-item --></ul>' . "\n" . '<!-- /wp:list --></div>' . "\n" . '<!-- /wp:group -->' ),
			'logos'     => array( 'Frise de logos', $g( 'lfd-logos', 'Frise de logos', $p( 'WordPress' ) . $p( 'PrestaShop' ) . $p( 'Magento' ) ) ),
			'phrase'    => array( 'Grande phrase', $g( 'lfd-statement', 'Grande phrase', $h( 2, 'Une phrase forte, avec un mot en <em>italique</em>.' ) ) ),
			'manifeste' => array( 'Manifeste (texte géant qui s’allume)', $g( 'lfd-manifesto', 'Manifeste', $h( 2, 'Une phrase manifeste.' ) . $p( 'Un court texte d’accompagnement.' ) ) ),
			'projets'   => array( 'Galerie horizontale de tous les projets', '<!-- wp:shortcode -->' . "\n" . '[lfd_projets titre="Nos projets"]' . "\n" . '<!-- /wp:shortcode -->' ),
		);
		foreach ( $patterns as $slug => $def ) {
			register_block_pattern( 'lfd/' . $slug, array( 'title' => $def[0], 'categories' => array( 'lfd' ), 'content' => $def[1] ) );
		}
	}
);
