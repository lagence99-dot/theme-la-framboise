<?php
/**
 * Écran « Sections de la page » (Pages → Sections La Framboise).
 * Pour chaque page : la liste de ses sections, avec des champs simples pour changer une image,
 * passer le visuel à gauche ou à droite, choisir un fond de couleur, réordonner, supprimer ou ajouter une section.
 * Tout est enregistré dans le contenu de la page, sous forme de blocs WordPress standards.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------- fabrication des blocs */

function lfd_b_names() {
	return array(
		'lfd-hero' => 'Ouverture', 'lfd-intro' => 'Introduction', 'lfd-feat' => 'Texte + visuel', 'lfd-manifesto' => 'Manifeste',
		'lfd-stack' => 'Projets à la une', 'lfd-panel' => 'Projet', 'lfd-statement' => 'Grande phrase', 'lfd-exp' => 'Expertises',
		'lfd-xrow' => 'Expertise', 'lfd-cols' => 'Colonnes', 'lfd-col' => 'Colonne', 'lfd-gallery' => 'Galerie', 'lfd-logos' => 'Frise de logos',
		'lfd-mosaic' => 'Mosaïque de projets', 'lfd-cover' => 'Texte sur image de fond', 'lfd-words' => 'Mots-clés', 'lfd-text' => 'Texte',
		'lfd-meta' => 'Fiche projet', 'lfd-case' => 'Présentation du projet', 'lfd-prose' => 'Texte légal',
		'lfd-videoband' => 'Vidéo pleine largeur', 'lfd-videos3' => 'Vidéos côte à côte', 'lfd-stats' => 'Chiffres clés', 'lfd-stat' => 'Chiffre',
	);
}

function lfd_b_kses( $html ) {
	return trim( wp_kses( (string) $html, array( 'em' => array(), 'strong' => array(), 'br' => array(), 'a' => array( 'href' => array() ) ) ) );
}

function lfd_b_group( $cls, $inner ) {
	$names = lfd_b_names();
	$first = strtok( $cls, ' ' );
	$attrs = array( 'metadata' => array( 'name' => isset( $names[ $first ] ) ? $names[ $first ] : 'Section' ), 'className' => $cls );
	return '<!-- wp:group ' . serialize_block_attributes( $attrs ) . " -->\n" . '<div class="wp-block-group ' . esc_attr( $cls ) . '">' . implode( "\n", array_filter( (array) $inner ) ) . "</div>\n<!-- /wp:group -->";
}

function lfd_b_h( $level, $html ) {
	$level = max( 1, min( 6, (int) $level ) );
	$a     = 2 === $level ? '' : ' ' . serialize_block_attributes( array( 'level' => $level ) );
	return '<!-- wp:heading' . $a . " -->\n<h" . $level . ' class="wp-block-heading">' . lfd_b_kses( $html ) . '</h' . $level . ">\n<!-- /wp:heading -->";
}

function lfd_b_p( $html, $cls = '' ) {
	if ( $cls ) {
		return '<!-- wp:paragraph ' . serialize_block_attributes( array( 'className' => $cls ) ) . " -->\n" . '<p class="' . esc_attr( $cls ) . '">' . lfd_b_kses( $html ) . "</p>\n<!-- /wp:paragraph -->";
	}
	return "<!-- wp:paragraph -->\n<p>" . lfd_b_kses( $html ) . "</p>\n<!-- /wp:paragraph -->";
}

function lfd_b_img( $src, $alt = '', $cap = '', $href = '', $cls = '' ) {
	$a = array( 'sizeSlug' => 'full', 'linkDestination' => $href ? 'custom' : 'none' );
	if ( $cls ) {
		$a['className'] = $cls;
	}
	$img = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '"/>';
	if ( $href ) {
		$img = '<a href="' . esc_url( $href ) . '">' . $img . '</a>';
	}
	$fig = 'wp-block-image size-full' . ( $cls ? ' ' . $cls : '' );
	$c   = '' !== trim( (string) $cap ) ? '<figcaption class="wp-element-caption">' . lfd_b_kses( $cap ) . '</figcaption>' : '';
	return '<!-- wp:image ' . serialize_block_attributes( $a ) . " -->\n" . '<figure class="' . esc_attr( $fig ) . '">' . $img . $c . "</figure>\n<!-- /wp:image -->";
}

function lfd_b_is_video( $src ) {
	return (bool) preg_match( '~\.(mp4|webm|mov|m4v|ogv)(\?|#|$)~i', (string) $src );
}

/**
 * Bloc vidéo. Le lien (une vidéo n'en a pas dans WordPress) est rangé dans les métadonnées du bloc ;
 * « Avec le son » ajoute la classe lfd-sound.
 */
function lfd_b_video( $src, $cap = '', $poster = '', $href = '', $sound = false ) {
	$attrs = array();
	if ( $sound ) {
		$attrs['className'] = 'lfd-sound';
	}
	$href = trim( (string) $href );
	if ( '' !== $href ) {
		$attrs['metadata'] = array( 'lfdLink' => esc_url_raw( $href ) );
	}
	$v = '<video controls' . ( $poster ? ' poster="' . esc_url( $poster ) . '"' : '' ) . ' src="' . esc_url( $src ) . '"></video>';
	$c = '' !== trim( (string) $cap ) ? '<figcaption class="wp-element-caption">' . lfd_b_kses( $cap ) . '</figcaption>' : '';
	return '<!-- wp:video' . ( $attrs ? ' ' . serialize_block_attributes( $attrs ) : '' ) . " -->\n" . '<figure class="wp-block-video' . ( $sound ? ' lfd-sound' : '' ) . '">' . $v . $c . "</figure>\n<!-- /wp:video -->";
}

/** Bloc image ou bloc vidéo selon le fichier choisi. */
function lfd_b_media( $src, $alt = '', $cap = '', $href = '', $cls = '', $poster = '', $sound = false ) {
	if ( lfd_b_is_video( $src ) ) {
		return lfd_b_video( $src, $cap, $poster, $href, $sound );
	}
	$cls = trim( str_replace( 'lfd-sound', '', (string) $cls ) );
	return lfd_b_img( $src, $alt, $cap, $href, $cls );
}

/** Classes de réglage d'une section : couleur libre et typographie. */
function lfd_sec_extra_cls( $s ) {
	$out = '';
	if ( isset( $s['band'] ) && 'custom' === $s['band'] && isset( $s['bgcolor'] ) && preg_match( '~^#?([0-9a-f]{6})$~i', trim( $s['bgcolor'] ), $m ) ) {
		$out .= ' lfd-bg-' . strtolower( $m[1] );
	}
	$allowed = array( 'ts' => array( 's', 'l', 'xl' ), 'bs' => array( 's', 'l' ), 'ls' => array( 'tight', 'wide' ), 'lh' => array( 'tight', 'loose' ), 'ta' => array( 'center', 'right' ), 'size' => array( 'half', 'full' ) );
	foreach ( $allowed as $k => $vals ) {
		if ( isset( $s[ $k ] ) && in_array( $s[ $k ], $vals, true ) ) {
			$out .= ' lfd-' . $k . '-' . $s[ $k ];
		}
	}
	return $out;
}

/** Classes de réglage retirées avant d'en appliquer de nouvelles. */
function lfd_sec_is_setting_cls( $c ) {
	return (bool) preg_match( '~^lfd-(alt|band-(dark|accent|soft)|bg-[0-9a-f]{6}|ts-\w+|bs-\w+|ls-\w+|lh-\w+|ta-\w+|size-\w+)$~i', $c );
}

/**
 * Texte venant de l'éditeur de texte riche.
 * Paragraphes simples (gras, italique, liens) : blocs paragraphe classiques.
 * Mise en forme avancée (alignement, taille, interlignage, souligné, listes…) : un bloc HTML « lfd-rich ».
 */
function lfd_b_richtext( $html ) {
	$html = trim( (string) $html );
	if ( '' === $html ) {
		return array();
	}
	if ( false === strpos( $html, '<' ) ) {
		return lfd_b_paras( $html );
	}
	$html = trim( wp_kses_post( $html ) );
	if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
		return array();
	}
	$complex = preg_match( '~style=|<span|<u>|<del|<s>|<ul|<ol|<h[1-6]|<blockquote|<div|<table|<hr|<pre|class=~i', $html );
	if ( ! $complex ) {
		if ( ! preg_match( '~<p[\s>]~i', $html ) ) {
			return lfd_b_paras( $html );
		}
		$rest = trim( preg_replace( '~<p[^>]*>.*?</p>~s', '', $html ) );
		if ( '' === trim( wp_strip_all_tags( $rest ) ) && preg_match_all( '~<p[^>]*>(.*?)</p>~s', $html, $m ) ) {
			$out = array();
			foreach ( $m[1] as $p ) {
				if ( '' !== trim( wp_strip_all_tags( str_replace( '&nbsp;', ' ', $p ) ) ) ) {
					$out[] = lfd_b_p( $p );
				}
			}
			return $out;
		}
	}
	return array( "<!-- wp:html -->\n<div class=\"lfd-rich\">" . $html . "</div>\n<!-- /wp:html -->" );
}

function lfd_b_btns( $pairs ) {
	$inner = '';
	foreach ( (array) $pairs as $b ) {
		if ( '' === trim( (string) $b['label'] ) ) {
			continue;
		}
		$inner .= "<!-- wp:button -->\n" . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $b['url'] ) . '">' . esc_html( $b['label'] ) . "</a></div>\n<!-- /wp:button -->";
	}
	return $inner ? "<!-- wp:buttons -->\n" . '<div class="wp-block-buttons">' . $inner . "</div>\n<!-- /wp:buttons -->" : '';
}

function lfd_b_list( $lines ) {
	$inner = '';
	foreach ( (array) $lines as $l ) {
		if ( '' !== trim( (string) $l ) ) {
			$inner .= "<!-- wp:list-item -->\n<li>" . lfd_b_kses( $l ) . "</li>\n<!-- /wp:list-item -->";
		}
	}
	return $inner ? "<!-- wp:list -->\n" . '<ul class="wp-block-list">' . $inner . "</ul>\n<!-- /wp:list -->" : '';
}

/** Texte saisi (paragraphes séparés par une ligne vide) → blocs paragraphe. */
function lfd_b_paras( $text ) {
	$out = array();
	foreach ( preg_split( '~\R\s*\R~u', trim( (string) $text ) ) as $p ) {
		$p = trim( $p );
		if ( '' !== $p ) {
			$out[] = lfd_b_p( preg_replace( '~\R~u', '<br>', $p ) );
		}
	}
	return $out;
}

/* ------------------------------------------------------- lecture des sections */

/** Types de sections : libellé, champs proposés. */
function lfd_sec_kinds() {
	return array(
		'lfd-feat'      => array( 'Texte + visuel (plusieurs visuels = carrousel)', array( 'imgs', 'imgmeta', 'side', 'band', 'title', 'text', 'btn' ) ),
		'lfd-stats'     => array( 'Chiffres clés (3 ou 4 grands chiffres)', array( 'band', 'title', 'stats' ) ),
		'lfd-cover'     => array( 'Texte sur image ou couleur de fond', array( 'img1', 'band', 'title', 'text', 'btn' ) ),
		'lfd-text'      => array( 'Texte (titre d’un côté, contenu de l’autre)', array( 'side', 'band', 'title', 'text', 'btn' ) ),
		'lfd-intro'     => array( 'Introduction en grand texte', array( 'img1', 'band', 'big', 'text', 'btn' ) ),
		'lfd-statement' => array( 'Grande phrase', array( 'band', 'title' ) ),
		'lfd-manifesto' => array( 'Manifeste', array( 'band', 'title', 'text' ) ),
		'lfd-words'     => array( 'Mots-clés en grand', array( 'band', 'words' ) ),
		'lfd-gallery'   => array( 'Galerie de visuels', array( 'imgs' ) ),
		'lfd-logos'     => array( 'Frise de logos', array( 'band', 'imgs', 'words' ) ),
		'lfd-mosaic'    => array( 'Mosaïque de projets', array( 'imgs', 'imgmeta' ) ),
		'lfd-videoband' => array( 'Vidéo pleine largeur (césure, 700 px de haut)', array( 'img1', 'title' ) ),
		'lfd-videos3'   => array( 'Vidéos côte à côte (3 par ligne)', array( 'imgs', 'imgcap' ) ),
	);
}

function lfd_sec_classes( $block ) {
	$cls = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	return array_values( array_filter( preg_split( '/\s+/', trim( $cls ) ) ) );
}

function lfd_sec_band( $classes ) {
	foreach ( array( 'dark', 'accent', 'soft' ) as $b ) {
		if ( in_array( 'lfd-band-' . $b, $classes, true ) ) {
			return $b;
		}
	}
	return '';
}

function lfd_sec_html_to_text( $html ) {
	return html_entity_decode( preg_replace( '~<br\s*/?>~i', "\n", $html ), ENT_QUOTES, 'UTF-8' );
}

/** Toutes les images d'une section, dans l'ordre (y compris dans les sous-groupes). */
function lfd_sec_images( $block, &$out ) {
	foreach ( (array) $block['innerBlocks'] as $b ) {
		if ( 'core/image' === $b['blockName'] || 'core/video' === $b['blockName'] ) {
			$it = lfd_items( array( $b ) );
			if ( $it ) {
				$out[] = $it[0];
			}
		} elseif ( ! empty( $b['innerBlocks'] ) ) {
			lfd_sec_images( $b, $out );
		}
	}
}

/** Décrit une section pour l'écran d'édition. */
function lfd_sec_describe( $block, $index ) {
	$classes = lfd_sec_classes( $block );
	$names   = lfd_b_names();
	$kinds   = lfd_sec_kinds();
	$kind    = '';
	foreach ( $classes as $c ) {
		if ( isset( $names[ $c ] ) ) {
			$kind = $c;
			break;
		}
	}
	$d = array(
		'orig'   => $index,
		'kind'   => $kind,
		'label'  => $kind ? $names[ $kind ] : 'Autre contenu',
		'simple' => false,
		'side'   => in_array( 'lfd-alt', $classes, true ) ? 'right' : 'left',
		'band'   => lfd_sec_band( $classes ),
		'title'  => '',
		'level'  => 2,
		'text'   => '',
		'big'    => '',
		'words'  => '',
		'btns'   => array(),
		'imgs'   => array(),
		'peek'   => '',
		'bgcolor' => '',
		'ts'     => '',
		'bs'     => '',
		'ls'     => '',
		'lh'     => '',
		'ta'     => '',
		'size'   => '',
		'stats'  => array(),
		'text_html' => '',
	);
	foreach ( $classes as $c ) {
		if ( preg_match( '~^lfd-bg-([0-9a-f]{6})$~i', $c, $m ) ) {
			$d['band']    = 'custom';
			$d['bgcolor'] = '#' . strtolower( $m[1] );
		} elseif ( preg_match( '~^lfd-(ts|bs|ls|lh|ta|size)-(\w+)$~', $c, $m ) ) {
			$d[ $m[1] ] = $m[2];
		}
	}
	if ( 'core/group' !== $block['blockName'] || ! $kind ) {
		$d['label'] = 'core/shortcode' === $block['blockName'] ? 'Élément automatique (galerie de projets)' : 'Autre contenu';
		$d['peek']  = wp_html_excerpt( wp_strip_all_tags( render_block( $block ) ), 140, '…' );
		return $d;
	}
	lfd_sec_images( $block, $d['imgs'] );
	$items = lfd_items( $block['innerBlocks'] );
	$heads = 0;
	$ps    = array();
	$other = 0;
	$words = array();
	foreach ( $items as $it ) {
		if ( 'h' === $it['t'] ) {
			$heads++;
			if ( 1 === $heads ) {
				$d['title'] = lfd_sec_html_to_text( $it['html'] );
				$d['level'] = $it['l'];
				if ( ! empty( $it['cls'] ) ) {
					$other++;
				}
			}
		} elseif ( 'p' === $it['t'] ) {
			if ( lfd_has_cls( $it, 'lfd-big' ) && '' === $d['big'] ) {
				$d['big'] = lfd_sec_html_to_text( $it['html'] );
			} elseif ( ! empty( $it['cls'] ) ) {
				$other++;
			} else {
				$ps[]              = lfd_sec_html_to_text( $it['html'] );
				$d['text_html']   .= '<p>' . $it['html'] . '</p>';
			}
		} elseif ( 'rich' === $it['t'] ) {
			$ps[]            = wp_strip_all_tags( $it['html'] );
			$d['text_html'] .= $it['html'];
		} elseif ( 'btn' === $it['t'] ) {
			$d['btns'][] = array( 'label' => $it['label'], 'url' => $it['href'] );
		} elseif ( 'li' === $it['t'] ) {
			$words[] = lfd_sec_html_to_text( $it['html'] );
		} elseif ( 'img' !== $it['t'] && 'video' !== $it['t'] ) {
			$other++;
		}
	}
	$d['text']  = implode( "\n\n", $ps );
	$d['words'] = implode( "\n", $words );
	$d['peek']  = wp_html_excerpt( $d['title'] ? $d['title'] : ( $d['big'] ? $d['big'] : $d['text'] ), 120, '…' );
	if ( isset( $kinds[ $kind ] ) ) {
		$fields = $kinds[ $kind ][1];
		$ok     = 0 === $other && $heads <= 1 && count( $d['btns'] ) <= 2;
		if ( ! in_array( 'words', $fields, true ) && $words ) {
			$ok = false;
		}
		if ( 'lfd-logos' === $kind ) {
			// dans une frise, les paragraphes sont des noms affichés en lettres.
			$d['words'] = implode( "\n", array_merge( $ps, $words ) );
			$d['text']  = '';
		} elseif ( ! in_array( 'text', $fields, true ) && $ps ) {
			$ok = false;
		}
		if ( ! in_array( 'title', $fields, true ) && $heads ) {
			$ok = false;
		}
		if ( ! in_array( 'imgs', $fields, true ) && count( $d['imgs'] ) > 1 ) {
			$ok = false;
		}
		$d['simple'] = $ok;
	}
	if ( 'lfd-stats' === $kind ) {
		foreach ( $items as $it ) {
			if ( 'group' !== $it['t'] ) {
				continue;
			}
			$row = array( 'n' => '', 'l' => '', 't' => '' );
			foreach ( $it['items'] as $x ) {
				if ( 'p' !== $x['t'] ) {
					continue;
				}
				if ( lfd_has_cls( $x, 'lfd-stat-n' ) ) {
					$row['n'] = lfd_sec_html_to_text( $x['html'] );
				} elseif ( lfd_has_cls( $x, 'lfd-stat-l' ) ) {
					$row['l'] = lfd_sec_html_to_text( $x['html'] );
				} else {
					$row['t'] = lfd_sec_html_to_text( $x['html'] );
				}
			}
			$d['stats'][] = $row;
		}
		$d['simple'] = $heads <= 1;
	}
	return $d;
}

/* ------------------------------------------------------- enregistrement */

function lfd_sec_cls( $kind, $side, $band ) {
	$cls = $kind;
	if ( 'right' === $side && in_array( $kind, array( 'lfd-feat', 'lfd-text' ), true ) ) {
		$cls .= ' lfd-alt';
	}
	if ( in_array( $band, array( 'dark', 'accent', 'soft' ), true ) ) {
		$cls .= ' lfd-band-' . $band;
	}
	return $cls;
}

/** Construit une section « simple » à partir des champs du formulaire. */
function lfd_sec_build( $kind, $s ) {
	$kinds  = lfd_sec_kinds();
	$fields = $kinds[ $kind ][1];
	$has    = function ( $f ) use ( $fields ) {
		return in_array( $f, $fields, true );
	};
	$inner  = array();
	$imgs   = array();
	foreach ( isset( $s['img'] ) ? (array) $s['img'] : array() as $im ) {
		if ( ! empty( $im['src'] ) ) {
			$imgs[] = lfd_b_media( $im['src'], isset( $im['alt'] ) ? $im['alt'] : '', isset( $im['cap'] ) ? $im['cap'] : '', isset( $im['href'] ) ? $im['href'] : '', isset( $im['cls'] ) ? sanitize_text_field( $im['cls'] ) : '', isset( $im['poster'] ) ? $im['poster'] : '', ! empty( $im['sound'] ) );
		}
	}
	if ( $has( 'big' ) && '' !== trim( (string) $s['big'] ) ) {
		$inner[] = lfd_b_p( preg_replace( '~\R~u', ' ', trim( $s['big'] ) ), 'lfd-big' );
	}
	if ( $has( 'img1' ) && $imgs ) {
		$inner[] = $imgs[0];
	}
	if ( $has( 'imgs' ) ) {
		$inner = array_merge( $inner, $imgs );
	}
	if ( $has( 'title' ) && '' !== trim( (string) $s['title'] ) ) {
		$inner[] = lfd_b_h( isset( $s['level'] ) ? $s['level'] : 2, preg_replace( '~\R~u', '<br>', trim( $s['title'] ) ) );
	}
	if ( $has( 'text' ) ) {
		$inner = array_merge( $inner, lfd_b_richtext( isset( $s['text'] ) ? $s['text'] : '' ) );
	}
	if ( $has( 'words' ) ) {
		$lines = preg_split( '~\R~u', trim( isset( $s['words'] ) ? (string) $s['words'] : '' ) );
		if ( 'lfd-logos' === $kind ) {
			foreach ( $lines as $l ) {
				if ( '' !== trim( $l ) ) {
					$inner[] = lfd_b_p( $l );
				}
			}
		} else {
			$inner[] = lfd_b_list( $lines );
		}
	}
	if ( $has( 'stats' ) ) {
		foreach ( isset( $s['stat'] ) ? (array) $s['stat'] : array() as $st ) {
			$num = isset( $st['n'] ) ? trim( (string) $st['n'] ) : '';
			if ( '' === $num ) {
				continue;
			}
			$cell = array( lfd_b_p( $num, 'lfd-stat-n' ) );
			if ( isset( $st['l'] ) && '' !== trim( $st['l'] ) ) {
				$cell[] = lfd_b_p( trim( $st['l'] ), 'lfd-stat-l' );
			}
			if ( isset( $st['t'] ) && '' !== trim( $st['t'] ) ) {
				$cell[] = lfd_b_p( preg_replace( '~\R~u', '<br>', trim( $st['t'] ) ) );
			}
			$inner[] = lfd_b_group( 'lfd-stat', $cell );
		}
	}
	if ( $has( 'btn' ) ) {
		$inner[] = lfd_b_btns( isset( $s['btn'] ) ? (array) $s['btn'] : array() );
	}
	$side = $has( 'side' ) && isset( $s['side'] ) ? $s['side'] : 'left';
	$band = $has( 'band' ) && isset( $s['band'] ) ? $s['band'] : '';
	return lfd_b_group( lfd_sec_cls( $kind, $side, $band ) . lfd_sec_extra_cls( $s ), $inner );
}

/** Remplace les classes d'un groupe existant (attribut + balise) sans toucher à son contenu. */
function lfd_sec_set_classes( $block, $cls ) {
	$block['attrs']['className'] = $cls;
	$fix                         = function ( $html ) use ( $cls ) {
		return preg_replace( '~<div class="wp-block-group[^"]*"~', '<div class="wp-block-group ' . esc_attr( $cls ) . '"', $html, 1 );
	};
	$block['innerHTML']          = $fix( $block['innerHTML'] );
	foreach ( $block['innerContent'] as $k => $chunk ) {
		if ( is_string( $chunk ) && false !== strpos( $chunk, 'wp-block-group' ) ) {
			$block['innerContent'][ $k ] = $fix( $chunk );
			break;
		}
	}
	return $block;
}

/** Remplace, dans l'ordre, les images d'une section existante par celles du formulaire. */
function lfd_sec_swap_images( $block, $imgs, &$n ) {
	foreach ( $block['innerBlocks'] as $k => $b ) {
		if ( 'core/image' === $b['blockName'] || 'core/video' === $b['blockName'] ) {
			if ( isset( $imgs[ $n ] ) && ! empty( $imgs[ $n ]['src'] ) ) {
				$cur  = lfd_items( array( $b ) );
				$new  = $imgs[ $n ];
				$snd  = ! empty( $new['sound'] );
				$was  = $cur && false !== strpos( ' ' . $cur[0]['cls'] . ' ', ' lfd-sound ' );
				$isv  = lfd_b_is_video( $new['src'] );
				if ( ! $cur || $cur[0]['src'] !== $new['src'] || ( $isv && $was !== $snd ) ) {
					$old    = $cur ? $cur[0] : array( 't' => 'img', 'src' => '', 'alt' => '', 'cap' => '', 'href' => '', 'cls' => '' );
					// Une image remplacée par une vidéo devient l'image affichée pendant le chargement de la vidéo.
					$poster = 'video' === $old['t'] ? ( isset( $old['poster'] ) ? $old['poster'] : '' ) : $old['src'];
					$parsed = parse_blocks( lfd_b_media( $new['src'], $old['alt'], $old['cap'], $old['href'], $old['cls'], $poster, $snd ) );
					$block['innerBlocks'][ $k ] = $parsed[0];
				}
			}
			$n++;
		} elseif ( ! empty( $b['innerBlocks'] ) ) {
			$block['innerBlocks'][ $k ] = lfd_sec_swap_images( $b, $imgs, $n );
		}
	}
	return $block;
}

function lfd_sec_save( $post, $posted ) {
	$blocks = array_values(
		array_filter(
			parse_blocks( $post->post_content ),
			function ( $b ) {
				return ! empty( $b['blockName'] );
			}
		)
	);
	$kinds  = lfd_sec_kinds();
	$names  = lfd_b_names();
	$out    = array();
	foreach ( (array) $posted as $s ) {
		if ( ! empty( $s['delete'] ) ) {
			continue;
		}
		$kind = isset( $s['kind'] ) ? sanitize_text_field( $s['kind'] ) : '';
		$orig = isset( $s['orig'] ) ? $s['orig'] : 'new';
		if ( 'new' === $orig ) {
			if ( isset( $kinds[ $kind ] ) ) {
				$out[] = lfd_sec_build( $kind, $s );
			}
			continue;
		}
		$orig = (int) $orig;
		if ( ! isset( $blocks[ $orig ] ) ) {
			continue;
		}
		$block = $blocks[ $orig ];
		if ( ! empty( $s['simple'] ) && isset( $kinds[ $kind ] ) ) {
			$out[] = lfd_sec_build( $kind, $s );
			continue;
		}
		// Section riche : son texte reste tel quel ; seuls le côté, le fond et les images changent.
		if ( 'core/group' === $block['blockName'] && isset( $names[ $kind ] ) ) {
			$classes = lfd_sec_classes( $block );
			$keep    = array_values( array_filter( $classes, function ( $c ) { return ! lfd_sec_is_setting_cls( $c ); } ) );
			$cls     = implode( ' ', $keep );
			if ( isset( $s['side'] ) && 'right' === $s['side'] ) {
				$cls .= ' lfd-alt';
			}
			if ( isset( $s['band'] ) && in_array( $s['band'], array( 'dark', 'accent', 'soft' ), true ) ) {
				$cls .= ' lfd-band-' . $s['band'];
			}
			$cls .= lfd_sec_extra_cls( $s );
			if ( $cls !== implode( ' ', $classes ) ) {
				$block = lfd_sec_set_classes( $block, $cls );
			}
			if ( ! empty( $s['img'] ) ) {
				$n     = 0;
				$block = lfd_sec_swap_images( $block, array_values( (array) $s['img'] ), $n );
			}
		}
		$out[] = serialize_block( $block );
	}
	return wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( implode( "\n\n", $out ) ), 'page_template' => 'default' ), true );
}

/* ------------------------------------------------------- accès à l'écran */

function lfd_sec_url( $post_id ) {
	return admin_url( 'edit.php?post_type=page&page=lfd-sections&post=' . (int) $post_id );
}

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=page', 'Sections de la page', 'Sections La Framboise', 'edit_pages', 'lfd-sections', 'lfd_sec_screen' );
	}
);

add_filter(
	'page_row_actions',
	function ( $actions, $post ) {
		if ( current_user_can( 'edit_page', $post->ID ) ) {
			$actions['lfd_sections'] = '<a href="' . esc_url( lfd_sec_url( $post->ID ) ) . '">Sections</a>';
		}
		return $actions;
	},
	10,
	2
);

add_action(
	'admin_bar_menu',
	function ( $bar ) {
		if ( ! is_admin() && is_page() && current_user_can( 'edit_page', get_queried_object_id() ) ) {
			$bar->add_node( array( 'id' => 'lfd-sections', 'title' => 'Sections de la page', 'href' => lfd_sec_url( get_queried_object_id() ) ) );
		}
	},
	81
);

add_action(
	'admin_enqueue_scripts',
	function () {
		if ( isset( $_GET['page'] ) && 'lfd-sections' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_enqueue_media();
			wp_enqueue_editor();
		}
	}
);

/* ------------------------------------------------------- écran */

function lfd_sec_field_img( $name, $im, $meta ) {
	$src = isset( $im['src'] ) ? $im['src'] : '';
	$prev = '<span>Aucun visuel</span>';
	if ( $src ) {
		$prev = lfd_b_is_video( $src ) ? '<video src="' . esc_url( $src ) . '" muted preload="metadata"></video><b class="lfd-vtag">Vidéo</b>' : '<img src="' . esc_url( $src ) . '" alt="">';
	}
	echo '<div class="lfd-img"><div class="lfd-img-prev">' . $prev . '</div>';
	echo '<input type="hidden" class="lfd-img-src" name="' . esc_attr( $name ) . '[src]" value="' . esc_attr( $src ) . '">';
	foreach ( array( 'alt', 'cls', 'poster' ) as $k ) {
		echo '<input type="hidden" class="lfd-img-' . esc_attr( $k ) . '" name="' . esc_attr( $name ) . '[' . esc_attr( $k ) . ']" value="' . esc_attr( isset( $im[ $k ] ) ? $im[ $k ] : '' ) . '">';
	}
	$isv = $src && lfd_b_is_video( $src );
	$snd = ! empty( $im['sound'] ) || ( isset( $im['cls'] ) && false !== strpos( ' ' . $im['cls'] . ' ', ' lfd-sound ' ) );
	echo '<label class="lfd-snd"' . ( $isv ? '' : ' hidden' ) . '><span><input type="checkbox" name="' . esc_attr( $name ) . '[sound]" value="1"' . checked( $snd, true, false ) . '> Avec le son</span></label>';
	if ( $meta ) {
		echo '<label>' . ( 'cap' === $meta ? 'Légende' : 'Nom · catégorie' ) . '<input type="text" name="' . esc_attr( $name ) . '[cap]" value="' . esc_attr( isset( $im['cap'] ) ? wp_strip_all_tags( $im['cap'] ) : '' ) . '"></label>';
		echo '<label>Lien<input type="text" name="' . esc_attr( $name ) . '[href]" value="' . esc_attr( isset( $im['href'] ) ? $im['href'] : '' ) . '"></label>';
	} else {
		foreach ( array( 'cap', 'href' ) as $k ) {
			echo '<input type="hidden" name="' . esc_attr( $name ) . '[' . esc_attr( $k ) . ']" value="' . esc_attr( isset( $im[ $k ] ) ? wp_strip_all_tags( $im[ $k ] ) : '' ) . '">';
		}
	}
	echo '<div class="lfd-img-act"><button type="button" class="button lfd-img-pick">' . ( $src ? 'Changer le visuel' : 'Choisir une image ou une vidéo' ) . '</button><button type="button" class="button-link lfd-img-del">Retirer</button></div>';
	echo '<div class="lfd-img-move"><span class="lfd-drag" title="Attrapez l’image de la vignette et faites-la glisser">⠿ Glisser l’image</span><button type="button" class="button-link lfd-left" aria-label="Placer avant">← Avant</button><button type="button" class="button-link lfd-right" aria-label="Placer après">Après →</button></div></div>';
}

/** Affiche la carte d'une section. $d : description ; $uid : identifiant de formulaire. */
function lfd_sec_card( $d, $uid, $new = false ) {
	$kinds  = lfd_sec_kinds();
	$fields = isset( $kinds[ $d['kind'] ] ) ? $kinds[ $d['kind'] ][1] : array();
	$n      = 'sec[' . $uid . ']';
	$simple = $new || $d['simple'];
	$has    = function ( $f ) use ( $fields ) {
		return in_array( $f, $fields, true );
	};
	$fixed  = 'lfd-hero' === $d['kind'];
	$meta   = $has( 'imgmeta' ) ? 'meta' : ( $has( 'imgcap' ) ? 'cap' : '' );
	echo '<div class="lfd-sec' . ( $fixed ? ' is-fixed' : '' ) . '" data-uid="' . esc_attr( $uid ) . '">';
	echo '<input type="hidden" name="' . esc_attr( $n ) . '[orig]" value="' . esc_attr( $new ? 'new' : $d['orig'] ) . '">';
	echo '<input type="hidden" name="' . esc_attr( $n ) . '[kind]" value="' . esc_attr( $d['kind'] ) . '">';
	echo '<input type="hidden" name="' . esc_attr( $n ) . '[simple]" value="' . ( $simple ? '1' : '' ) . '">';
	echo '<input type="hidden" class="lfd-del" name="' . esc_attr( $n ) . '[delete]" value="">';
	echo '<div class="lfd-sec-head"><strong>' . esc_html( $d['label'] ) . '</strong><span class="lfd-peek">' . esc_html( $d['peek'] ) . '</span><span class="lfd-sec-act">';
	if ( ! $fixed ) {
		echo '<button type="button" class="button lfd-up">Monter</button><button type="button" class="button lfd-down">Descendre</button><button type="button" class="button-link button-link-delete lfd-rm">Supprimer</button>';
	}
	echo '</span></div><div class="lfd-sec-body">';
	if ( $fixed ) {
		echo '<p class="description">Le grand visuel plein écran se choisit dans l’éditeur de la page, encadré « Visuel d’ouverture (plein écran) ». Le titre se modifie dans l’éditeur.</p>';
	}
	// colonne visuels
	$imgs = $d['imgs'];
	if ( $simple && $has( 'img1' ) ) {
		echo '<div class="lfd-col-img"><span class="lfd-lab">Visuel</span>';
		lfd_sec_field_img( $n . '[img][0]', isset( $imgs[0] ) ? $imgs[0] : array(), false );
		echo '</div>';
	} elseif ( $simple && $has( 'imgs' ) ) {
		echo '<div class="lfd-col-imgs"><span class="lfd-lab">Visuels (dans l’ordre d’affichage)</span><div class="lfd-imgs" data-name="' . esc_attr( $n ) . '[img]" data-meta="' . esc_attr( $meta ) . '">';
		foreach ( $imgs as $k => $im ) {
			lfd_sec_field_img( $n . '[img][' . $k . ']', $im, $meta );
		}
		echo '</div><button type="button" class="button lfd-imgs-add">Ajouter des images ou des vidéos</button></div>';
	} elseif ( ! $simple && ! $fixed && $imgs ) {
		echo '<div class="lfd-col-imgs"><span class="lfd-lab">Visuels de cette section</span><div class="lfd-imgs is-locked">';
		foreach ( $imgs as $k => $im ) {
			lfd_sec_field_img( $n . '[img][' . $k . ']', $im, false );
		}
		echo '</div></div>';
	}
	echo '<div class="lfd-col-fields">';
	foreach ( array( 'bgcolor', 'ts', 'bs', 'ls', 'lh', 'ta', 'size' ) as $dk ) {
		if ( ! isset( $d[ $dk ] ) ) {
			$d[ $dk ] = '';
		}
	}
	$sideable = $has( 'side' ) || ( ! $simple && in_array( $d['kind'], array( 'lfd-feat', 'lfd-text' ), true ) );
	if ( $sideable ) {
		$lab = 'lfd-text' === $d['kind'] ? array( 'Titre à gauche, contenu à droite', 'Contenu à gauche, titre à droite' ) : array( 'Visuel à gauche, texte à droite', 'Texte à gauche, visuel à droite' );
		echo '<label>Disposition<select name="' . esc_attr( $n ) . '[side]"><option value="left"' . selected( $d['side'], 'left', false ) . '>' . esc_html( $lab[0] ) . '</option><option value="right"' . selected( $d['side'], 'right', false ) . '>' . esc_html( $lab[1] ) . '</option></select></label>';
	}
	if ( 'lfd-feat' === $d['kind'] ) {
		echo '<label>Taille du visuel<select name="' . esc_attr( $n ) . '[size]">';
		foreach ( array( '' => 'Petit (avec marges, comme aujourd’hui)', 'half' => 'Moitié de la page', 'full' => 'Bord à bord : le visuel touche le bord de l’écran, sur toute la hauteur' ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $d['size'], $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label>';
	}
	$bandable = $has( 'band' ) || ( ! $simple && ! in_array( $d['kind'], array( 'lfd-hero', 'lfd-stack', 'lfd-mosaic', 'lfd-videoband', 'lfd-videos3', 'lfd-prose', '' ), true ) );
	if ( $bandable ) {
		$bgc = ! empty( $d['bgcolor'] ) ? $d['bgcolor'] : '#f2e9e4';
		echo '<div class="lfd-band-row"><label>Fond de la section<select class="lfd-band" name="' . esc_attr( $n ) . '[band]">';
		foreach ( array( '' => 'Blanc', 'dark' => 'Aplat bleu nuit', 'accent' => 'Aplat framboise', 'soft' => 'Aplat gris clair', 'custom' => 'Couleur personnalisée' ) as $v => $l ) {
			echo '<option value="' . esc_attr( $v ) . '"' . selected( $d['band'], $v, false ) . '>' . esc_html( $l ) . '</option>';
		}
		echo '</select></label><label class="lfd-bgc"' . ( 'custom' === $d['band'] ? '' : ' hidden' ) . '>Couleur<input type="color" name="' . esc_attr( $n ) . '[bgcolor]" value="' . esc_attr( $bgc ) . '"></label></div>';
		if ( 'lfd-cover' === $d['kind'] ) {
			echo '<p class="description">Pour une couleur à la place de la photo : retirez le visuel et choisissez la couleur ci-dessus.</p>';
		}
	}
	if ( ! in_array( $d['kind'], array( 'lfd-mosaic', 'lfd-videos3', 'lfd-gallery', '' ), true ) ) {
		$opt = function ( $key, $label, $choices ) use ( $d, $n ) {
			$cur = isset( $d[ $key ] ) ? $d[ $key ] : '';
			$h   = '<label>' . esc_html( $label ) . '<select name="' . esc_attr( $n ) . '[' . $key . ']">';
			foreach ( $choices as $v => $l ) {
				$h .= '<option value="' . esc_attr( $v ) . '"' . selected( $cur, $v, false ) . '>' . esc_html( $l ) . '</option>';
			}
			return $h . '</select></label>';
		};
		$open = '' !== $d['ts'] . $d['bs'] . $d['ls'] . $d['lh'] . $d['ta'];
		echo '<details class="lfd-typo"' . ( $open ? ' open' : '' ) . '><summary>Typographie : taille, interlettrage, interlignage</summary><div class="lfd-typo-grid">';
		echo $opt( 'ts', 'Taille des titres', array( '' => 'Normale', 's' => 'Plus petite', 'l' => 'Plus grande', 'xl' => 'Très grande' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $opt( 'bs', 'Taille du texte', array( '' => 'Normale', 's' => 'Plus petite', 'l' => 'Plus grande' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $opt( 'ls', 'Interlettrage (espace entre les lettres)', array( '' => 'Normal', 'tight' => 'Serré', 'wide' => 'Large' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $opt( 'lh', 'Interlignage (espace entre les lignes)', array( '' => 'Normal', 'tight' => 'Serré', 'loose' => 'Aéré' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo $opt( 'ta', 'Alignement des titres et textes', array( '' => 'À gauche', 'center' => 'Centré', 'right' => 'À droite' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</div></details>';
	}
	if ( $simple ) {
		if ( $has( 'big' ) ) {
			echo '<label>Texte d’introduction (affiché en grand)<textarea rows="3" name="' . esc_attr( $n ) . '[big]">' . esc_textarea( $d['big'] ) . '</textarea></label>';
		}
		if ( $has( 'title' ) ) {
			echo '<input type="hidden" name="' . esc_attr( $n ) . '[level]" value="' . esc_attr( $d['level'] ) . '">';
			echo '<label>Titre <span class="description">(un mot entre &lt;em&gt; et &lt;/em&gt; s’affiche en italique framboise)</span><textarea rows="2" name="' . esc_attr( $n ) . '[title]">' . esc_textarea( $d['title'] ) . '</textarea></label>';
		}
		if ( $has( 'text' ) ) {
			$val = isset( $d['text_html'] ) && '' !== $d['text_html'] ? $d['text_html'] : $d['text'];
			echo '<div class="lfd-wys-wrap"><span class="lfd-lab">Texte</span><textarea class="lfd-wys" id="lfd-wys-' . esc_attr( $uid ) . '" rows="8" name="' . esc_attr( $n ) . '[text]">' . esc_textarea( $val ) . '</textarea></div>';
		}
		if ( $has( 'stats' ) ) {
			$rows = isset( $d['stats'] ) ? $d['stats'] : array();
			echo '<div class="lfd-stats-f"><span class="lfd-lab">Chiffres (3 ou 4 ; laissez une ligne vide pour n’en afficher que 3)</span>';
			for ( $i = 0; $i < 4; $i++ ) {
				$r = isset( $rows[ $i ] ) ? $rows[ $i ] : array( 'n' => '', 'l' => '', 't' => '' );
				echo '<div class="lfd-stat-row"><label>Chiffre<input type="text" name="' . esc_attr( $n ) . '[stat][' . $i . '][n]" value="' . esc_attr( $r['n'] ) . '" placeholder="24"></label>'
					. '<label>Intitulé<input type="text" name="' . esc_attr( $n ) . '[stat][' . $i . '][l]" value="' . esc_attr( $r['l'] ) . '" placeholder="Collaborateurs"></label>'
					. '<label>Petit texte<input type="text" name="' . esc_attr( $n ) . '[stat][' . $i . '][t]" value="' . esc_attr( $r['t'] ) . '" placeholder="Créatifs, experts de l’image…"></label></div>';
			}
			echo '<p class="description">Les chiffres défilent jusqu’à leur valeur quand la section apparaît. Un « + », « % » ou « k » placé après le chiffre (ex. 356+) s’affiche en italique framboise.</p></div>';
		}
		if ( $has( 'words' ) ) {
			echo '<label>' . ( 'lfd-logos' === $d['kind'] ? 'Noms affichés en lettres' : 'Mots-clés' ) . ' <span class="description">(un par ligne)</span><textarea rows="5" name="' . esc_attr( $n ) . '[words]">' . esc_textarea( $d['words'] ) . '</textarea></label>';
		}
		if ( $has( 'btn' ) ) {
			for ( $i = 0; $i < 2; $i++ ) {
				$b = isset( $d['btns'][ $i ] ) ? $d['btns'][ $i ] : array( 'label' => '', 'url' => '' );
				echo '<div class="lfd-btn"><label>Bouton ' . ( $i + 1 ) . ' : libellé<input type="text" name="' . esc_attr( $n ) . '[btn][' . $i . '][label]" value="' . esc_attr( $b['label'] ) . '"></label><label>Lien<input type="text" name="' . esc_attr( $n ) . '[btn][' . $i . '][url]" value="' . esc_attr( $b['url'] ) . '"></label></div>';
			}
		}
	} elseif ( ! $fixed ) {
		echo '<p class="description">Section riche (plusieurs titres, listes ou colonnes) : ses textes se modifient dans l’éditeur de la page. Ici : images' . ( $sideable ? ', disposition' : '' ) . ( $bandable ? ', fond' : '' ) . ', ordre.</p>';
	}
	echo '</div></div></div>';
}

function lfd_sec_screen() {
	$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0; // phpcs:ignore WordPress.Security.NonceVerification
	echo '<div class="wrap lfd-sections"><h1>Sections de la page</h1>';
	if ( ! $post_id || ! get_post( $post_id ) || 'page' !== get_post_type( $post_id ) ) {
		echo '<p>Choisissez la page à modifier :</p><ul class="lfd-pages">';
		foreach ( get_pages( array( 'sort_column' => 'post_title' ) ) as $p ) {
			echo '<li><a href="' . esc_url( lfd_sec_url( $p->ID ) ) . '">' . esc_html( $p->post_title ) . '</a> <code>/' . esc_html( get_page_uri( $p ) ) . '/</code></li>';
		}
		echo '</ul></div>';
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		echo '<p>Vous n’avez pas le droit de modifier cette page.</p></div>';
		return;
	}
	if ( isset( $_POST['lfd_sec_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lfd_sec_nonce'] ) ), 'lfd_sec_' . $post_id ) ) {
		$posted = isset( $_POST['sec'] ) ? wp_unslash( $_POST['sec'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- chaque champ est nettoyé lors de la construction des blocs.
		$r      = lfd_sec_save( get_post( $post_id ), $posted );
		if ( is_wp_error( $r ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $r->get_error_message() ) . '</p></div>';
		} else {
			echo '<div class="notice notice-success is-dismissible"><p>Page enregistrée. <a href="' . esc_url( get_permalink( $post_id ) ) . '" target="_blank" rel="noopener">Voir la page</a></p></div>';
		}
		clean_post_cache( $post_id );
	}
	$post   = get_post( $post_id );
	$blocks = array_values(
		array_filter(
			parse_blocks( $post->post_content ),
			function ( $b ) {
				return ! empty( $b['blockName'] );
			}
		)
	);
	echo '<p class="lfd-top"><strong>' . esc_html( $post->post_title ) . '</strong> · <a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">Voir la page</a> · <a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">Ouvrir l’éditeur (textes, visuel d’ouverture)</a> · <a href="' . esc_url( admin_url( 'edit.php?post_type=page&page=lfd-sections' ) ) . '">Autre page</a></p>';
	echo '<form method="post" id="lfd-sec-form">';
	wp_nonce_field( 'lfd_sec_' . $post_id, 'lfd_sec_nonce' );
	echo '<div id="lfd-sec-list">';
	foreach ( $blocks as $i => $b ) {
		lfd_sec_card( lfd_sec_describe( $b, $i ), 's' . $i );
	}
	echo '</div>';
	echo '<div class="lfd-add"><label for="lfd-add-kind"><strong>Ajouter une section</strong></label> <select id="lfd-add-kind">';
	foreach ( lfd_sec_kinds() as $k => $def ) {
		echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $def[0] ) . '</option>';
	}
	echo '</select> <button type="button" class="button" id="lfd-add-btn">Ajouter en bas de page</button></div>';
	echo '<p class="lfd-save"><button class="button button-primary button-hero">Enregistrer la page</button></p></form>';
	// gabarits des nouvelles sections
	foreach ( lfd_sec_kinds() as $k => $def ) {
		echo '<template id="lfd-tpl-' . esc_attr( $k ) . '">';
		lfd_sec_card( array( 'orig' => 'new', 'kind' => $k, 'label' => $def[0] . ' (nouvelle)', 'simple' => true, 'side' => 'left', 'band' => '', 'title' => '', 'level' => 2, 'text' => '', 'big' => '', 'words' => '', 'btns' => array(), 'imgs' => array(), 'peek' => '' ), '__UID__', true );
		echo '</template>';
	}
	echo '<template id="lfd-tpl-img">';
	lfd_sec_field_img( '__NAME__', array(), false );
	echo '</template><template id="lfd-tpl-imgmeta">';
	lfd_sec_field_img( '__NAME__', array(), 'meta' );
	echo '</template><template id="lfd-tpl-imgcap">';
	lfd_sec_field_img( '__NAME__', array(), 'cap' );
	echo '</template>';
	?>
	<style>
		.lfd-sections .lfd-top{font-size:14px;margin:6px 0 18px}
		.lfd-sec{background:#fff;border:1px solid #c3c4c7;border-radius:4px;margin:0 0 14px;max-width:1180px}
		.lfd-sec.is-gone{opacity:.45}
		.lfd-sec.is-gone .lfd-sec-body{display:none}
		.lfd-sec-head{display:flex;align-items:center;gap:12px;padding:10px 14px;border-bottom:1px solid #dcdcde;background:#f6f7f7}
		.lfd-sec-head strong{font-size:14px;white-space:nowrap}
		.lfd-peek{color:#646970;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
		.lfd-sec-act{display:flex;gap:6px;align-items:center;white-space:nowrap}
		.lfd-sec-body{display:flex;gap:22px;padding:14px;flex-wrap:wrap}
		.lfd-col-img{flex:0 0 240px}
		.lfd-col-imgs{flex:1 1 100%}
		.lfd-col-fields{flex:1 1 380px;display:flex;flex-direction:column;gap:10px;min-width:0}
		.lfd-col-fields label,.lfd-img label{display:flex;flex-direction:column;gap:3px;font-weight:600}
		.lfd-col-fields label .description{font-weight:400}
		.lfd-col-fields textarea,.lfd-col-fields input[type=text],.lfd-col-fields select{width:100%;max-width:none;font-weight:400}
		.lfd-btn{display:grid;grid-template-columns:1fr 1fr;gap:10px}
		.lfd-lab{display:block;font-weight:600;margin-bottom:6px}
		.lfd-imgs{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px;margin-bottom:10px}
		.lfd-img{display:flex;flex-direction:column;gap:6px}
		.lfd-img-prev{background:#101a2b;border-radius:3px;aspect-ratio:3/2;display:grid;place-items:center;overflow:hidden;color:#fff}
		.lfd-img-prev{position:relative}
		.lfd-img-prev img,.lfd-img-prev video{width:100%;height:100%;object-fit:contain;display:block}
		.lfd-vtag{position:absolute;left:8px;top:8px;background:#d41b5e;color:#fff;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px}
		.lfd-img-act{display:flex;gap:10px;align-items:center}
		.lfd-imgs.is-locked .lfd-img-del{display:none}
		.lfd-img label[hidden],.lfd-bgc[hidden]{display:none!important}
		.lfd-snd span{display:flex;gap:6px;align-items:center;font-weight:600}
		.lfd-img-move{display:flex;gap:12px;align-items:center;font-size:12px}
		.lfd-col-img .lfd-img-move,.lfd-imgs.is-locked .lfd-img-move{display:none}
		.lfd-imgs:not(.is-locked) .lfd-img-prev{cursor:grab}
		.lfd-drag{cursor:default;user-select:none;color:#50575e;padding:4px 6px;border:1px dashed #c3c4c7;border-radius:3px}
		.lfd-imgs:not(.is-locked){counter-reset:pos}
		.lfd-imgs:not(.is-locked) .lfd-img{counter-increment:pos;padding:8px;border:1px solid transparent;border-radius:4px;background:#fff}
		.lfd-imgs:not(.is-locked) .lfd-img-prev::after{content:counter(pos);position:absolute;right:8px;top:8px;min-width:22px;height:22px;border-radius:99px;background:#fff;color:#1d2327;font:600 12px/22px sans-serif;text-align:center}
		.lfd-img.is-drag{opacity:.4}
		.lfd-img.drop-before{border-left:3px solid #d41b5e}
		.lfd-img.drop-after{border-right:3px solid #d41b5e}
		.lfd-band-row{display:flex;gap:12px;align-items:flex-end}
		.lfd-band-row label:first-child{flex:1}
		.lfd-bgc input[type=color]{width:64px;height:36px;padding:2px}
		.lfd-typo{border:1px solid #dcdcde;border-radius:4px;padding:8px 12px}
		.lfd-typo summary{cursor:pointer;font-weight:600}
		.lfd-typo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px}
		.lfd-wys-wrap .wp-editor-wrap{margin-top:2px}
		.lfd-wys-wrap .mce-edit-area iframe{min-height:180px}
		.lfd-stat-row{display:grid;grid-template-columns:110px 1fr 2fr;gap:10px;margin-bottom:8px}
		.lfd-stat-row label{font-weight:600}
		.lfd-add{background:#fff;border:1px dashed #8c8f94;border-radius:4px;padding:14px;max-width:1180px;margin-top:6px}
		.lfd-save{position:sticky;bottom:0;background:#f0f0f1;padding:12px 0;margin:0;max-width:1180px}
		.lfd-pages{columns:2;max-width:1000px}
	</style>
	<script>
	(function () {
		var list = document.getElementById('lfd-sec-list'), seq = 0;
		function pick(multiple, cb) {
			var frame = wp.media({ title: 'Choisir une image ou une vidéo', button: { text: 'Utiliser' }, library: { type: ['image', 'video'] }, multiple: multiple });
			frame.on('select', function () { cb(frame.state().get('selection').toJSON()); });
			frame.open();
		}
		// éditeur de texte riche (barre d'outils type traitement de texte)
		var wysCfg = {
			tinymce: {
				wpautop: false,
				plugins: 'charmap,colorpicker,hr,lists,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wptextpattern',
				toolbar1: 'formatselect,fontsizeselect,styleselect,bold,italic,underline,strikethrough,forecolor,alignleft,aligncenter,alignright,alignjustify,bullist,numlist,blockquote,link,unlink,removeformat,undo,redo',
				toolbar2: '',
				block_formats: 'Paragraphe=p;Sous-titre=h4;Petit titre=h5',
				fontsize_formats: 'Petit=0.85em Normal=1em Grand=1.2em Très\u00a0grand=1.5em Énorme=2em',
				style_formats: [
					{ title: 'Interlignage serré', selector: 'p,h4,h5,li', styles: { lineHeight: '1.3' } },
					{ title: 'Interlignage normal', selector: 'p,h4,h5,li', styles: { lineHeight: '1.6' } },
					{ title: 'Interlignage aéré', selector: 'p,h4,h5,li', styles: { lineHeight: '2' } },
					{ title: 'Interlettrage large', inline: 'span', styles: { letterSpacing: '0.06em' } },
					{ title: 'Interlettrage serré', inline: 'span', styles: { letterSpacing: '-0.02em' } }
				],
				content_style: 'body{font-family:-apple-system,Segoe UI,Roboto,sans-serif;font-size:15px;line-height:1.6;color:#1d2327;margin:10px} p{margin:0 0 1em}',
				height: 220
			},
			quicktags: true,
			mediaButtons: false
		};
		function wysInit(root) {
			if (!window.wp || !wp.editor || !wp.editor.initialize) { return; }
			Array.prototype.forEach.call((root || document).querySelectorAll('textarea.lfd-wys'), function (ta) {
				if (ta.getAttribute('data-wys') || ta.closest('template')) { return; }
				ta.setAttribute('data-wys', '1');
				wp.editor.initialize(ta.id, wysCfg);
			});
		}
		window.addEventListener('load', function () { wysInit(document); });
		document.getElementById('lfd-sec-form').addEventListener('submit', function () { if (window.tinymce) { window.tinymce.triggerSave(); } });
		function isVid(u) { return /\.(mp4|webm|mov|m4v|ogv)(\?|#|$)/i.test(u || ''); }
		// glisser-déposer des visuels (poignée « Glisser ») et boutons Avant / Après
		var dragEl = null;
		document.addEventListener('dragstart', function (e) {
			var h = e.target.closest && e.target.closest('.lfd-img-prev');
			var el = h && h.closest('.lfd-imgs:not(.is-locked) > .lfd-img');
			if (!el) { return; }
			dragEl = el; el.classList.add('is-drag');
			try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', ''); e.dataTransfer.setDragImage(el, 20, 20); } catch (x) {}
		});
		function clearMarks() { Array.prototype.forEach.call(document.querySelectorAll('.drop-before,.drop-after'), function (x) { x.classList.remove('drop-before', 'drop-after'); }); }
		document.addEventListener('dragover', function (e) {
			if (!dragEl) { return; }
			var over = e.target.closest && e.target.closest('.lfd-img');
			if (!over || over === dragEl || over.parentNode !== dragEl.parentNode) { return; }
			e.preventDefault();
			var r = over.getBoundingClientRect(), before = e.clientX < r.left + r.width / 2;
			clearMarks(); over.classList.add(before ? 'drop-before' : 'drop-after');
		});
		document.addEventListener('drop', function (e) {
			if (!dragEl) { return; }
			var over = e.target.closest && e.target.closest('.lfd-img');
			if (over && over !== dragEl && over.parentNode === dragEl.parentNode) {
				e.preventDefault();
				var r = over.getBoundingClientRect();
				over.parentNode.insertBefore(dragEl, e.clientX < r.left + r.width / 2 ? over : over.nextSibling);
			}
		});
		document.addEventListener('dragend', function () {
			if (dragEl) { dragEl.classList.remove('is-drag'); }
			dragEl = null; clearMarks();
		});
		document.addEventListener('change', function (e) {
			if (e.target.classList && e.target.classList.contains('lfd-band')) {
				var c = e.target.closest('.lfd-band-row').querySelector('.lfd-bgc');
				if (c) { c.hidden = e.target.value !== 'custom'; }
			}
		});
		function setImg(box, url) {
			var src = box.querySelector('.lfd-img-src'), poster = box.querySelector('.lfd-img-poster'), before = src.value;
			// une image remplacée par une vidéo devient l'image affichée pendant son chargement
			if (poster) { poster.value = isVid(url) ? (before && !isVid(before) ? before : poster.value) : ''; }
			src.value = url || '';
			var snd = box.querySelector('.lfd-snd'); if (snd) { snd.hidden = !isVid(url); }
			var q = (url || '').replace(/"/g, '&quot;');
			box.querySelector('.lfd-img-prev').innerHTML = !url ? '<span>Aucun visuel</span>' : (isVid(url) ? '<video src="' + q + '" muted preload="metadata"></video><b class="lfd-vtag">Vidéo</b>' : '<img src="' + q + '" alt="">');
			box.querySelector('.lfd-img-pick').textContent = url ? 'Changer le visuel' : 'Choisir une image ou une vidéo';
		}
		document.addEventListener('click', function (e) {
			var t = e.target, sec = t.closest ? t.closest('.lfd-sec') : null;
			if (!t.classList) { return; }
			if (t.classList.contains('lfd-img-pick')) {
				var box = t.closest('.lfd-img');
				pick(false, function (sel) { if (sel[0]) { setImg(box, sel[0].url); } });
			} else if (t.classList.contains('lfd-img-del')) {
				var b = t.closest('.lfd-img');
				if (b.parentNode.classList.contains('lfd-imgs')) { b.parentNode.removeChild(b); } else { setImg(b, ''); }
			} else if (t.classList.contains('lfd-imgs-add')) {
				var wrap = t.parentNode.querySelector('.lfd-imgs');
				pick(true, function (sel) {
					sel.forEach(function (a) {
						var m = wrap.getAttribute('data-meta'), tpl = document.getElementById(m === 'cap' ? 'lfd-tpl-imgcap' : (m ? 'lfd-tpl-imgmeta' : 'lfd-tpl-img'));
						var d = document.createElement('div');
						d.innerHTML = tpl.innerHTML.replace(/__NAME__/g, wrap.getAttribute('data-name') + '[n' + (seq++) + ']');
						var node = d.firstElementChild; wrap.appendChild(node); setImg(node, a.url);
					});
				});
			} else if (t.classList.contains('lfd-left') || t.classList.contains('lfd-right')) {
				var me = t.closest('.lfd-img');
				if (t.classList.contains('lfd-left') && me.previousElementSibling) { me.parentNode.insertBefore(me, me.previousElementSibling); }
				if (t.classList.contains('lfd-right') && me.nextElementSibling) { me.parentNode.insertBefore(me.nextElementSibling, me); }
			} else if (t.classList.contains('lfd-up') && sec) {
				var prev = sec.previousElementSibling;
				if (prev && !prev.classList.contains('is-fixed')) { list.insertBefore(sec, prev); }
			} else if (t.classList.contains('lfd-down') && sec) {
				var next = sec.nextElementSibling;
				if (next) { list.insertBefore(next, sec); }
			} else if (t.classList.contains('lfd-rm') && sec) {
				var del = sec.querySelector('.lfd-del'), gone = del.value !== '1';
				del.value = gone ? '1' : '';
				sec.classList.toggle('is-gone', gone);
				t.textContent = gone ? 'Annuler la suppression' : 'Supprimer';
			} else if (t.id === 'lfd-add-btn') {
				var kind = document.getElementById('lfd-add-kind').value, tp = document.getElementById('lfd-tpl-' + kind);
				var h = document.createElement('div');
				h.innerHTML = tp.innerHTML.replace(/__UID__/g, 'n' + (seq++));
				var card = h.firstElementChild; list.appendChild(card); wysInit(card);
				card.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		});
	})();
	</script>
	<?php
	echo '</div>';
}
