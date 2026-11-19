<?php
/**
 * La Framboise Digitale — thème sur mesure.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LFD_VERSION', '1.8.2' );

require get_template_directory() . '/inc/render.php';
require get_template_directory() . '/inc/import.php';
require get_template_directory() . '/inc/patterns.php';
require get_template_directory() . '/inc/hero-field.php';
require get_template_directory() . '/inc/sections.php';

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 320, 'flex-height' => true, 'flex-width' => true ) );
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		register_nav_menus(
			array(
				'primary'  => 'Menu principal',
				'footer'   => 'Pied de page : menu',
				'services' => 'Pied de page : services',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_template_directory();
		$uri = get_template_directory_uri();
		wp_enqueue_style( 'lfd-main', $uri . '/assets/css/main.css', array(), (string) filemtime( $dir . '/assets/css/main.css' ) );
		wp_enqueue_script( 'lfd-main', $uri . '/assets/js/main.js', array(), (string) filemtime( $dir . '/assets/js/main.js' ), true );
	}
);

// Polices embarquées dans le thème (aucun appel à un serveur tiers) : préchargement des deux principales.
add_action(
	'wp_head',
	function () {
		$f = get_template_directory_uri() . '/assets/fonts/';
		echo '<link rel="preload" href="' . esc_url( $f . 'bricolage-grotesque-latin-wght-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		echo '<link rel="preload" href="' . esc_url( $f . 'hanken-grotesk-latin-400-normal.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		echo "<script>document.documentElement.classList.add('js');</script>\n";
	},
	1
);

/**
 * Les pages utilisent toujours le gabarit du thème, même si un ancien gabarit
 * (créé par un constructeur de pages) leur est encore attribué.
 */
add_filter(
	'template_include',
	function ( $template ) {
		if ( is_page() ) {
			$t = get_template_directory() . '/page.php';
			if ( file_exists( $t ) ) {
				return $t;
			}
		}
		return $template;
	},
	PHP_INT_MAX
);

/**
 * Un constructeur de pages encore actif (Kubio) ne doit plus intervenir dans l'affichage :
 * ses feuilles de style et scripts sont retirés des pages publiques.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		foreach ( array( wp_styles(), wp_scripts() ) as $deps ) {
			foreach ( (array) $deps->queue as $handle ) {
				if ( 0 === strpos( $handle, 'kubio' ) ) {
					$deps->dequeue( $handle );
				}
			}
		}
	},
	PHP_INT_MAX
);
add_action(
	'wp_print_styles',
	function () {
		foreach ( (array) wp_styles()->queue as $handle ) {
			if ( 0 === strpos( $handle, 'kubio' ) ) {
				wp_dequeue_style( $handle );
			}
		}
	},
	PHP_INT_MAX
);

add_filter(
	'body_class',
	function ( $classes ) {
		if ( is_singular() ) {
			$type = get_post_meta( get_queried_object_id(), '_lfd_type', true );
			if ( $type ) {
				$classes[] = 'lfd-type-' . sanitize_html_class( $type );
			}
		}
		if ( ! empty( $GLOBALS['lfd_photo_hero'] ) ) {
			$classes[] = 'has-photo-hero';
		}
		return $classes;
	}
);

/* ----------------------------------------------------------- réglages */

function lfd_opt( $key ) {
	$defaults = array(
		'lfd_email'       => 'com75@laframboisedigitale.fr',
		'lfd_address'     => '103 rue de Grenelle, Paris',
		'lfd_instagram'   => 'https://www.instagram.com/laframboisedigitale',
		'lfd_footer_text' => 'La Framboise Digitale est une agence digitale spécialisée en UX/UI design, création de sites internet sur mesure, e-commerce et stratégie digitale. Nous concevons des expériences digitales premium qui allient design, émotion et performance pour les marques ambitieuses, du luxe aux entreprises innovantes, à Paris et sur la Côte d’Azur.',
		'lfd_cta_eyebrow' => 'Paris, Nice, Cannes',
		'lfd_cta_title'   => 'Vous avez un projet qui mérite de <em>se distinguer</em> ?',
		'lfd_cta_text'    => 'Échangeons autour de vos enjeux et construisons une expérience digitale à la hauteur de votre ambition.',
		'lfd_cta_label'   => 'Discuter de votre projet',
		'lfd_cta_url'     => '/contact/',
		'lfd_curtain'     => '1',
	);
	$v        = get_theme_mod( $key, null );
	return ( null === $v || '' === $v ) && isset( $defaults[ $key ] ) ? $defaults[ $key ] : $v;
}

add_action(
	'customize_register',
	function ( $wp_customize ) {
		$wp_customize->add_section( 'lfd', array( 'title' => 'La Framboise : textes et contacts', 'priority' => 30 ) );
		$fields = array(
			'lfd_email'       => array( 'Adresse e-mail', 'text' ),
			'lfd_address'     => array( 'Adresse postale', 'text' ),
			'lfd_instagram'   => array( 'Lien Instagram', 'url' ),
			'lfd_footer_text' => array( 'Texte du pied de page', 'textarea' ),
			'lfd_cta_eyebrow' => array( 'Bloc contact : sur-titre', 'text' ),
			'lfd_cta_title'   => array( 'Bloc contact : titre (<em> pour l’italique framboise)', 'text' ),
			'lfd_cta_text'    => array( 'Bloc contact : texte', 'textarea' ),
			'lfd_cta_label'   => array( 'Bloc contact : libellé du bouton', 'text' ),
			'lfd_cta_url'     => array( 'Bloc contact : lien du bouton', 'text' ),
		);
		foreach ( $fields as $id => $f ) {
			$wp_customize->add_setting( $id, array( 'default' => '', 'sanitize_callback' => 'wp_kses_post' ) );
			$wp_customize->add_control( $id, array( 'label' => $f[0], 'section' => 'lfd', 'type' => $f[1] ) );
		}
		$wp_customize->add_setting( 'lfd_curtain', array( 'default' => '1', 'sanitize_callback' => 'sanitize_text_field' ) );
		$wp_customize->add_control( 'lfd_curtain', array( 'label' => 'Rideau d’ouverture sur la page d’accueil', 'section' => 'lfd', 'type' => 'select', 'choices' => array( '1' => 'Oui', '0' => 'Non' ) ) );
	}
);

/* -------------------------------------------------------------- menus */

function lfd_url( $path ) {
	return home_url( '/' . ltrim( $path, '/' ) );
}

/** Menu principal de secours : reprend le menu actuel du site, avec le déroulant « Expertises ». */
function lfd_primary_fallback() {
	$menu = array(
		array( 'L’agence', 'agence-design-digital-luxe/' ),
		array(
			'Expertises',
			'#',
			array(
				array( 'Branding', 'agence-branding-luxe/' ),
				array( 'CX / PX / UX', 'agence-ux-cx-design-luxe/' ),
				array( 'Design & émotion', 'direction-artistique-digitale-luxe/' ),
				array( 'Design émotionnel', 'design-emotionnel-luxe/' ),
				array( 'Technologie & IA', 'developpement-web-mobile-ia-luxe-paris/' ),
				array( 'Conseil communication', 'agence-conseil-en-communication-luxe/' ),
				array( 'Formations', 'formation-ux-designer-ia-augmentee/' ),
			),
		),
		array( 'Nos projets', 'portfolio-agence-digitale-luxe/' ),
		array( 'Contact', 'contact/' ),
	);
	echo '<ul class="menu">';
	foreach ( $menu as $m ) {
		$has = ! empty( $m[2] );
		$url = '#' === $m[1] ? '#' : lfd_url( $m[1] );
		echo '<li class="menu-item' . ( $has ? ' menu-item-has-children' : '' ) . '"><a href="' . esc_url( $url ) . '">' . esc_html( $m[0] ) . '</a>';
		if ( $has ) {
			echo '<ul class="sub-menu">';
			foreach ( $m[2] as $s ) {
				echo '<li class="menu-item"><a href="' . esc_url( lfd_url( $s[1] ) ) . '">' . esc_html( $s[0] ) . '</a></li>';
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

function lfd_menu( $location ) {
	if ( 'primary' === $location ) {
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'depth' => 3, 'menu_class' => 'menu', 'fallback_cb' => 'lfd_primary_fallback' ) );
		} else {
			lfd_primary_fallback();
		}
		return;
	}
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu( array( 'theme_location' => $location, 'container' => false, 'depth' => 1, 'items_wrap' => '%3$s', 'fallback_cb' => false, 'walker' => new LFD_Flat_Walker() ) );
		return;
	}
	$defaults = array(
		'footer'   => array(
			'Homepage' => '',
			'L’agence' => 'agence-design-digital-luxe/',
			'Projets'  => 'portfolio-agence-digitale-luxe/',
			'Contact'  => 'contact/',
		),
		'services' => array(
			'Branding'              => 'agence-branding-luxe/',
			'CX / PX / UX'          => 'agence-ux-cx-design-luxe/',
			'Design & émotion'      => 'direction-artistique-digitale-luxe/',
			'Technologie & IA'      => 'developpement-web-mobile-ia-luxe-paris/',
			'Conseil communication' => 'agence-conseil-en-communication-luxe/',
			'Formations'            => 'formation-ux-designer-ia-augmentee/',
		),
	);
	foreach ( $defaults[ $location ] as $label => $path ) {
		echo '<a href="' . esc_url( lfd_url( $path ) ) . '">' . esc_html( $label ) . '</a>';
	}
}

/** Menu à plat : une suite de liens, sans liste. */
class LFD_Flat_Walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$cur     = in_array( 'current-menu-item', (array) $item->classes, true ) ? ' aria-current="page"' : '';
		$output .= '<a href="' . esc_url( $item->url ) . '"' . $cur . '>' . esc_html( $item->title ) . '</a>';
	}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

/* ------------------------------------------- réglages d'une page projet */

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'lfd_page', 'La Framboise : réglages de la page', 'lfd_meta_box', 'page', 'side', 'default' );
	}
);

function lfd_meta_box( $post ) {
	wp_nonce_field( 'lfd_meta', 'lfd_meta_nonce' );
	echo '<p><a class="button button-primary" style="width:100%;text-align:center" href="' . esc_url( lfd_sec_url( $post->ID ) ) . '">Gérer les sections de la page</a></p><p style="color:#646970">Images de chaque section, visuel à gauche ou à droite, fonds de couleur, ordre, ajout de section.</p><hr>';
	$type = get_post_meta( $post->ID, '_lfd_type', true );
	echo '<p><label for="lfd_type"><strong>Type de page</strong></label><br><select name="lfd_type" id="lfd_type" style="width:100%">';
	foreach ( array( '' => 'Page standard', 'accueil' => 'Accueil', 'projet' => 'Projet (référence client)', 'portfolio' => 'Portfolio', 'expertise' => 'Expertise', 'formation' => 'Formation', 'contact' => 'Contact', 'legal' => 'Page légale' ) as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $type, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></p><p style="color:#646970">Les champs ci-dessous servent uniquement aux pages « Projet » (galeries de projets, projet suivant).</p>';
	$fields = array(
		'_lfd_client' => 'Nom du client',
		'_lfd_cat'    => 'Catégorie (ex. Parfum)',
		'_lfd_thumb'  => 'Adresse de la vignette (sinon image mise en avant)',
		'_lfd_order'  => 'Ordre d’affichage (1, 2, 3…)',
	);
	foreach ( $fields as $k => $l ) {
		echo '<p><label for="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</label><br><input type="text" style="width:100%" id="' . esc_attr( $k ) . '" name="' . esc_attr( $k ) . '" value="' . esc_attr( get_post_meta( $post->ID, $k, true ) ) . '"></p>';
	}
}

add_action(
	'save_post_page',
	function ( $post_id ) {
		if ( ! isset( $_POST['lfd_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lfd_meta_nonce'] ) ), 'lfd_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
		if ( isset( $_POST['lfd_type'] ) ) {
			update_post_meta( $post_id, '_lfd_type', sanitize_key( wp_unslash( $_POST['lfd_type'] ) ) );
		}
		foreach ( array( '_lfd_client', '_lfd_cat', '_lfd_order' ) as $k ) {
			if ( isset( $_POST[ $k ] ) ) {
				update_post_meta( $post_id, $k, sanitize_text_field( wp_unslash( $_POST[ $k ] ) ) );
			}
		}
		if ( isset( $_POST['_lfd_thumb'] ) ) {
			update_post_meta( $post_id, '_lfd_thumb', esc_url_raw( wp_unslash( $_POST['_lfd_thumb'] ) ) );
		}
	}
);
