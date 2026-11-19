<?php
/**
 * Rendu des sections : chaque groupe de blocs portant une classe « lfd-… »
 * est affiché avec la mise en page et les animations du thème.
 * Le contenu reste modifiable dans l'éditeur de blocs de WordPress.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Classes de groupes rendues par leur parent (elles ne produisent rien seules). */
function lfd_child_classes() {
	return array( 'lfd-panel', 'lfd-col', 'lfd-xrow', 'lfd-stat' );
}

/** Transforme une liste de blocs analysés en éléments simples. */
function lfd_items( $blocks ) {
	$out = array();
	foreach ( (array) $blocks as $b ) {
		$name = isset( $b['blockName'] ) ? $b['blockName'] : null;
		if ( ! $name ) {
			continue;
		}
		$html = isset( $b['innerHTML'] ) ? $b['innerHTML'] : '';
		$cls  = isset( $b['attrs']['className'] ) ? $b['attrs']['className'] : '';
		switch ( $name ) {
			case 'core/heading':
				if ( preg_match( '~<h([1-6])[^>]*>(.*)</h\1>~s', $html, $m ) ) {
					$out[] = array( 't' => 'h', 'l' => (int) $m[1], 'html' => trim( $m[2] ), 'cls' => $cls );
				}
				break;
			case 'core/paragraph':
				if ( preg_match( '~<p[^>]*>(.*)</p>~s', $html, $m ) && '' !== trim( wp_strip_all_tags( $m[1] ) ) ) {
					$out[] = array( 't' => 'p', 'html' => trim( $m[1] ), 'cls' => $cls );
				}
				break;
			case 'core/image':
				if ( preg_match( '~<img[^>]*\ssrc="([^"]*)"~', $html, $m ) ) {
					$it = array( 't' => 'img', 'src' => html_entity_decode( $m[1] ), 'alt' => '', 'cap' => '', 'href' => '', 'cls' => $cls, 'id' => isset( $b['attrs']['id'] ) ? (int) $b['attrs']['id'] : 0 );
					if ( preg_match( '~<img[^>]*\salt="([^"]*)"~', $html, $a ) ) {
						$it['alt'] = html_entity_decode( $a[1] );
					}
					if ( preg_match( '~<a[^>]*\shref="([^"]*)"~', $html, $a ) ) {
						$it['href'] = html_entity_decode( $a[1] );
					}
					if ( preg_match( '~<figcaption[^>]*>(.*)</figcaption>~s', $html, $a ) ) {
						$it['cap'] = trim( $a[1] );
					}
					$out[] = $it;
				}
				break;
			case 'core/video':
				if ( preg_match( '~<video[^>]*\ssrc="([^"]*)"~', $html, $m ) ) {
					$poster = preg_match( '~\sposter="([^"]*)"~', $html, $p ) ? html_entity_decode( $p[1] ) : '';
					$vcap   = preg_match( '~<figcaption[^>]*>(.*)</figcaption>~s', $html, $c ) ? trim( $c[1] ) : '';
					$vlink  = isset( $b['attrs']['metadata']['lfdLink'] ) ? (string) $b['attrs']['metadata']['lfdLink'] : '';
					$out[]  = array( 't' => 'video', 'src' => html_entity_decode( $m[1] ), 'poster' => $poster, 'cls' => $cls, 'alt' => '', 'cap' => $vcap, 'href' => $vlink, 'id' => 0 );
				}
				break;
			case 'core/buttons':
				foreach ( (array) $b['innerBlocks'] as $bt ) {
					if ( preg_match( '~<a[^>]*\shref="([^"]*)"[^>]*>(.*)</a>~s', $bt['innerHTML'], $m ) ) {
						$out[] = array( 't' => 'btn', 'href' => html_entity_decode( $m[1] ), 'label' => trim( wp_strip_all_tags( $m[2] ) ) );
					}
				}
				break;
			case 'core/list':
				foreach ( (array) $b['innerBlocks'] as $li ) {
					if ( preg_match( '~<li[^>]*>(.*)</li>~s', $li['innerHTML'], $m ) ) {
						$out[] = array( 't' => 'li', 'html' => trim( $m[1] ) );
					}
				}
				break;
			case 'core/details':
				$title = preg_match( '~<summary[^>]*>(.*)</summary>~s', $html, $m ) ? trim( $m[1] ) : '';
				$out[] = array( 't' => 'acc', 'title' => $title, 'items' => lfd_items( $b['innerBlocks'] ) );
				break;
			case 'core/html':
				if ( preg_match( '~^\s*<div class="lfd-rich">(.*)</div>\s*$~s', $html, $m ) ) {
					$out[] = array( 't' => 'rich', 'html' => trim( $m[1] ), 'cls' => '' );
				} else {
					$out[] = array( 't' => 'raw', 'html' => render_block( $b ) );
				}
				break;
			case 'core/group':
				$out[] = array( 't' => 'group', 'cls' => $cls, 'items' => lfd_items( $b['innerBlocks'] ) );
				break;
			default:
				$out[] = array( 't' => 'raw', 'html' => render_block( $b ) );
		}
	}
	return $out;
}

function lfd_first( $items, $type, $cls = null ) {
	foreach ( $items as $k => $it ) {
		if ( $it['t'] === $type && ( null === $cls || ( isset( $it['cls'] ) && false !== strpos( ' ' . $it['cls'] . ' ', ' ' . $cls . ' ' ) ) ) ) {
			return $k;
		}
	}
	return -1;
}

function lfd_has_cls( $it, $cls ) {
	return isset( $it['cls'] ) && false !== strpos( ' ' . $it['cls'] . ' ', ' ' . $cls . ' ' );
}

/** Texte sans balises ; les sauts de ligne deviennent des espaces. */
function lfd_plain( $html ) {
	return trim( preg_replace( '~\s+~u', ' ', wp_strip_all_tags( preg_replace( '~<br\s*/?>~i', ' ', $html ) ) ) );
}

/** Point focal : classe « lfd-focus-79-50 » sur l'image = produit placé à 79 % / 50 %. */
function lfd_focus( $it ) {
	if ( ! empty( $it['focus'] ) ) {
		return $it['focus'];
	}
	if ( isset( $it['cls'] ) && preg_match( '~lfd-focus-(\d{1,3})-(\d{1,3})~', $it['cls'], $m ) ) {
		return min( 100, (int) $m[1] ) . '% ' . min( 100, (int) $m[2] ) . '%';
	}
	return '50% 50%';
}

function lfd_arrow() {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 12h16M14 6l6 6-6 6"/></svg>';
}

/** Vidéo lue automatiquement, sans son et en boucle, seulement quand elle est à l'écran. */
function lfd_video( $it, $style = '' ) {
	return '<video' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . ' src="' . esc_url( $it['src'] ) . '"'
		. ( ! empty( $it['poster'] ) ? ' poster="' . esc_url( $it['poster'] ) . '"' : '' ) . ' muted loop playsinline preload="metadata" data-auto'
		. ( lfd_has_sound( $it ) ? ' data-sound' : '' ) . '></video>';
}

/** La vidéo doit-elle faire entendre son son ? (case « Avec le son ») */
function lfd_has_sound( $it ) {
	return 'video' === $it['t'] && isset( $it['cls'] ) && false !== strpos( ' ' . $it['cls'] . ' ', ' lfd-sound ' );
}

/** Bouton son (haut-parleur). */
function lfd_sbtn( $extra = '' ) {
	return '<button type="button" class="sbtn ' . esc_attr( $extra ) . '" aria-pressed="false" aria-label="Activer le son"><svg class="i-off" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h4l5-4v14l-5-4H4z"/><path d="M17 9.5l4 5M21 9.5l-4 5"/></svg><svg class="i-on" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9h4l5-4v14l-5-4H4z"/><path d="M16.5 9a4.5 4.5 0 0 1 0 6M19 6.5a8 8 0 0 1 0 11"/></svg><span class="sbtn-l">Activer le son</span></button>';
}

/** Boutons posés sur une vidéo : son (si activé) et pause. */
function lfd_vbtns( $it ) {
	return ( lfd_has_sound( $it ) ? lfd_sbtn() : '' ) . lfd_vbtn();
}

/** Bouton pause / lecture posé sur une vidéo. */
function lfd_vbtn() {
	return '<button type="button" class="vbtn" aria-label="Mettre la vidéo en pause"><svg class="i-pause" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14M16 5v14"/></svg><svg class="i-play" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5l11 7-11 7z"/></svg></button>';
}

/** Image ou vidéo qui remplit son cadre (point focal respecté). */
function lfd_media_fill( $it, $with_btn = true ) {
	$style = 'object-position:' . lfd_focus( $it );
	if ( 'video' === $it['t'] ) {
		return lfd_video( $it, $style ) . ( $with_btn ? lfd_vbtns( $it ) : '' );
	}
	return '<img style="' . esc_attr( $style ) . '" src="' . esc_url( $it['src'] ) . '" alt="' . esc_attr( $it['alt'] ) . '" loading="lazy" decoding="async">';
}

/** Premier visuel (image ou vidéo) d'une liste d'éléments. */
function lfd_first_media( $items ) {
	foreach ( $items as $k => $it ) {
		if ( 'img' === $it['t'] || 'video' === $it['t'] ) {
			return $k;
		}
	}
	return -1;
}

/** Cadre image au format exact du visuel (aucun recadrage). */
function lfd_frame( $it, $extra = '', $lazy = true ) {
	if ( 'video' === $it['t'] ) {
		return '<div class="fr fr-video ' . esc_attr( $extra ) . '" data-fit>' . lfd_video( $it ) . lfd_vbtns( $it ) . '</div>';
	}
	$img = '<img src="' . esc_url( $it['src'] ) . '" alt="' . esc_attr( $it['alt'] ) . '"' . ( $lazy ? ' loading="lazy" decoding="async"' : '' ) . '>';
	if ( ! empty( $it['href'] ) ) {
		return '<a class="fr ' . esc_attr( $extra ) . '" data-fit href="' . esc_url( $it['href'] ) . '">' . $img . '</a>';
	}
	return '<div class="fr ' . esc_attr( $extra ) . '" data-fit>' . $img . '</div>';
}

/** Figure « référence » : visuel + légende « Nom · catégorie ». */
function lfd_ref( $it, $extra = '' ) {
	$cap = '';
	if ( ! empty( $it['cap'] ) ) {
		$parts = array_map( 'trim', explode( '·', wp_strip_all_tags( $it['cap'] ), 2 ) );
		$cap   = '<figcaption><b>' . esc_html( $parts[0] ) . '</b>' . ( isset( $parts[1] ) ? '<span>' . esc_html( $parts[1] ) . '</span>' : '' ) . '</figcaption>';
	}
	return '<figure class="ref">' . lfd_frame( $it, $extra ) . $cap . '</figure>';
}

/**
 * Rend une suite d'éléments dans l'ordre.
 * $o : rv (classe d'apparition), btn ('lk' ou 'btn'), split (titres découpés en lignes).
 */
function lfd_flow( $items, $o = array() ) {
	$o    = wp_parse_args( $o, array( 'rv' => 'rv', 'btn' => 'lk', 'split' => false ) );
	$rv   = $o['rv'] ? ' class="' . esc_attr( $o['rv'] ) . '"' : '';
	$html = '';
	$n    = count( $items );
	$bi   = 0;
	for ( $i = 0; $i < $n; $i++ ) {
		$it = $items[ $i ];
		switch ( $it['t'] ) {
			case 'h':
				$c = array();
				if ( $o['split'] ) {
					$c[] = 'spl';
				} elseif ( $o['rv'] ) {
					$c[] = $o['rv'];
				}
				if ( lfd_has_cls( $it, 'lfd-eyebrow' ) ) {
					$c[] = 'eyebrow';
				}
				$html .= '<h' . $it['l'] . ( $c ? ' class="' . esc_attr( implode( ' ', $c ) ) . '"' : '' ) . '>' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
				break;
			case 'p':
				if ( lfd_has_cls( $it, 'lfd-eyebrow' ) ) {
					$html .= '<p class="eyebrow ' . esc_attr( $o['rv'] ) . '">' . wp_kses_post( $it['html'] ) . '</p>';
				} else {
					$html .= '<p' . $rv . '>' . wp_kses_post( $it['html'] ) . '</p>';
				}
				break;
			case 'li':
				$html .= '<ul class="lfd-list ' . esc_attr( $o['rv'] ) . '">';
				while ( $i < $n && 'li' === $items[ $i ]['t'] ) {
					$html .= '<li>' . wp_kses_post( $items[ $i ]['html'] ) . '</li>';
					$i++;
				}
				$i--;
				$html .= '</ul>';
				break;
			case 'btn':
				$html .= '<div class="row ' . esc_attr( $o['rv'] ) . '">';
				while ( $i < $n && 'btn' === $items[ $i ]['t'] ) {
					$b = $items[ $i ];
					if ( 'btn' === $o['btn'] ) {
						$html .= '<a class="btn' . ( 0 === $bi ? ' fill' : '' ) . '" href="' . esc_url( $b['href'] ) . '">' . esc_html( $b['label'] ) . ( 0 === $bi ? ' ' . lfd_arrow() : '' ) . '</a>';
					} else {
						$html .= '<a class="lk" href="' . esc_url( $b['href'] ) . '">' . esc_html( $b['label'] ) . ' →</a>';
					}
					$bi++;
					$i++;
				}
				$i--;
				$html .= '</div>';
				break;
			case 'acc':
				$html .= '<details class="lfd-acc ' . esc_attr( $o['rv'] ) . '"><summary>' . wp_kses_post( $it['title'] ) . '</summary><div class="lfd-acc-b">' . lfd_flow( $it['items'], array( 'rv' => '' ) ) . '</div></details>';
				break;
			case 'img':
			case 'video':
				$html .= lfd_frame( $it, 'wu' );
				break;
			case 'group':
				$html .= '<div class="lfd-sub-group">' . lfd_flow( $it['items'], $o ) . '</div>';
				break;
			case 'raw':
				$html .= '<div class="lfd-raw ' . esc_attr( $o['rv'] ) . '">' . $it['html'] . '</div>';
				break;
			case 'rich':
				$html .= '<div class="rich ' . esc_attr( $o['rv'] ) . '">' . wp_kses_post( $it['html'] ) . '</div>';
				break;
		}
	}
	return $html;
}

/* ---------------------------------------------------------------- sections */

function lfd_r_hero( $items ) {
	$media = array();
	$copy  = array();
	foreach ( $items as $it ) {
		if ( 'img' === $it['t'] || 'video' === $it['t'] ) {
			$media[] = $it;
		} else {
			$copy[] = $it;
		}
	}
	// L'encadré « Visuel d'ouverture » de la page, s'il a été rempli, remplace les visuels placés dans le contenu.
	$pid = get_the_ID();
	if ( $pid && function_exists( 'lfd_hero_is_set' ) && lfd_hero_is_set( $pid ) ) {
		$media = lfd_hero_meta_items( $pid );
	}
	$text = '';
	foreach ( $copy as $it ) {
		if ( 'h' === $it['t'] && lfd_has_cls( $it, 'lfd-sub' ) ) {
			$text .= '<h' . $it['l'] . ' class="hero-sub">' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
		} elseif ( 'h' === $it['t'] ) {
			$text .= '<h' . $it['l'] . ' class="spl">' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
		} elseif ( 'p' === $it['t'] ) {
			$text .= '<p class="' . ( lfd_has_cls( $it, 'lfd-eyebrow' ) ? 'eyebrow' : 'hero-sub' ) . '">' . wp_kses_post( $it['html'] ) . '</p>';
		} elseif ( 'btn' === $it['t'] ) {
			$text .= '<a class="btn" href="' . esc_url( $it['href'] ) . '">' . esc_html( $it['label'] ) . '</a>';
		}
	}
	if ( ! $media ) {
		return '<section class="thero wrap"><div class="rv-g">' . $text . '</div></section>';
	}
	$GLOBALS['lfd_photo_hero'] = true;
	$slides                    = '';
	$bars                      = '';
	foreach ( $media as $k => $m ) {
		$pos     = lfd_focus( $m );
		$style   = 'object-position:' . $pos . ';transform-origin:' . $pos;
		$name    = 'img' === $m['t'] ? $m['alt'] : '';
		$sub     = wp_strip_all_tags( $m['cap'] );
		$slides .= '<div class="hs' . ( 0 === $k ? ' on' : '' ) . '" data-name="' . esc_attr( $name ) . '" data-sub="' . esc_attr( $sub ) . '">';
		if ( 'video' === $m['t'] ) {
			$slides .= '<video style="' . esc_attr( $style ) . '" src="' . esc_url( $m['src'] ) . '"' . ( $m['poster'] ? ' poster="' . esc_url( $m['poster'] ) . '"' : '' ) . ' muted loop playsinline autoplay preload="' . ( 0 === $k ? 'auto' : 'none' ) . '"' . ( lfd_has_sound( $m ) ? ' data-sound' : '' ) . '></video>';
			$has_snd = ! empty( $has_snd ) || lfd_has_sound( $m );
		} else {
			$slides .= '<img style="' . esc_attr( $style ) . '" src="' . esc_url( $m['src'] ) . '" alt="' . esc_attr( $m['alt'] ) . '"' . ( 0 === $k ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"' ) . '>';
		}
		$slides .= '</div>';
		$bars   .= '<button type="button"' . ( 0 === $k ? ' class="on"' : '' ) . ' aria-label="' . esc_attr( sprintf( 'Visuel %d', $k + 1 ) ) . '"></button>';
	}
	$info = '';
	if ( count( $media ) > 1 ) {
		$first = $media[0];
		$info  = '<div class="hs-info"><div class="hs-name"><span>' . esc_html( 'img' === $first['t'] ? $first['alt'] : '' ) . '</span></div><p class="hs-sub">' . esc_html( wp_strip_all_tags( $first['cap'] ) ) . '</p><div class="hs-bars">' . $bars . '</div></div>';
	}
	return '<div class="pin" data-pin><section class="hero"><div class="hero-frame"><div class="hero-move">' . $slides . '</div></div>'
		. '<div class="hero-copy"><div class="rv-g hero-text">' . $text . '</div>' . $info . '</div>'
		. '<div class="scrolltip" aria-hidden="true">Scroll</div>' . ( ! empty( $has_snd ) ? lfd_sbtn( 'hero-sbtn' ) : '' ) . '</section></div>';
}

function lfd_r_intro( $items ) {
	$big  = '';
	$ref  = '';
	$rest = array();
	foreach ( $items as $it ) {
		if ( '' === $big && 'p' === $it['t'] && lfd_has_cls( $it, 'lfd-big' ) ) {
			$big = '<p class="big" data-fill>' . esc_html( lfd_plain( $it['html'] ) ) . '</p>';
		} elseif ( '' === $ref && ( 'img' === $it['t'] || 'video' === $it['t'] ) ) {
			$ref = lfd_ref( $it, 'wl' );
		} else {
			$rest[] = $it;
		}
	}
	$side = $rest ? '<div class="side">' . lfd_flow( $rest, array( 'btn' => 'btn' ) ) . '</div>' : '';
	return '<section class="intro wrap' . ( $ref ? '' : ' no-ref' ) . '">' . $big . $ref . $side . '</section>';
}

function lfd_r_feat( $items, $cls ) {
	$media = array();
	$rest  = array();
	foreach ( $items as $it ) {
		if ( 'img' === $it['t'] || 'video' === $it['t'] ) {
			$media[] = $it;
		} else {
			$rest[] = $it;
		}
	}
	$items = $rest;
	$ref   = '';
	if ( count( $media ) > 1 ) {
		$ref = lfd_cflow( $media );
	} elseif ( $media ) {
		$ref = lfd_ref( $media[0] );
	}
	$alt = false !== strpos( $cls, 'lfd-alt' ) ? ' alt' : '';
	// Taille du visuel : petit (par défaut), moitié de la page, ou bord à bord (le visuel touche le bord de l'écran).
	$size = false !== strpos( ' ' . $cls . ' ', ' lfd-size-full ' ) ? 'full' : ( false !== strpos( ' ' . $cls . ' ', ' lfd-size-half ' ) ? 'half' : '' );
	$txt  = '<div class="txt">' . lfd_flow( array_values( $items ), array( 'rv' => '' ) ) . '</div>';
	if ( 'full' === $size && $ref ) {
		return '<section class="feat size-full' . $alt . '">' . $ref . $txt . '</section>';
	}
	return '<section class="wrap"><div class="feat' . $alt . ( 'half' === $size ? ' size-half' : '' ) . ( $ref ? '' : ' no-ref' ) . '">' . $ref . $txt . '</div></section>';
}

/** Carrousel de visuels : l'image active est en avant, les voisines réduites, estompées et floutées, avec reflet. */
function lfd_cflow( $media ) {
	$items = '';
	$n     = count( $media );
	foreach ( $media as $k => $m ) {
		$inner  = 'video' === $m['t'] ? lfd_video( $m ) : '<img src="' . esc_url( $m['src'] ) . '" alt="' . esc_attr( $m['alt'] ) . '" draggable="false"' . ( $k > 2 ? ' loading="lazy"' : '' ) . ' decoding="async">';
		$items .= '<div class="cf-item' . ( 0 === $k ? ' is-on' : '' ) . '" data-cap="' . esc_attr( trim( wp_strip_all_tags( $m['cap'] ) ) ) . '" data-href="' . esc_attr( $m['href'] ) . '">' . $inner . '</div>';
	}
	$l = '<svg viewBox="0 0 20 10" aria-hidden="true"><path d="M20 5H1M5 1 1 5l4 4"/></svg>';
	$r = '<svg viewBox="0 0 20 10" aria-hidden="true"><path d="M0 5h19M15 1l4 4-4 4"/></svg>';
	return '<figure class="ref cflow" data-cflow tabindex="0" aria-roledescription="carrousel" aria-label="Visuels"><div class="cf-stage">' . $items . '</div>'
		. '<figcaption class="cf-cap"><b class="cf-name"></b><span class="cf-ctrl"><button type="button" class="cf-prev" aria-label="Visuel précédent">' . $l . '</button>'
		. '<span class="cf-count" aria-live="polite">1/' . $n . '</span><button type="button" class="cf-next" aria-label="Visuel suivant">' . $r . '</button></span></figcaption></figure>';
}

/** Chiffres clés : grands chiffres, intitulé, petit texte. */
function lfd_r_stats( $items ) {
	$head  = '';
	$cells = '';
	$n     = 0;
	foreach ( $items as $it ) {
		if ( 'h' === $it['t'] && '' === $head ) {
			$head = '<h' . $it['l'] . ' class="stats-h spl">' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
		} elseif ( 'group' === $it['t'] ) {
			$num = '';
			$lab = '';
			$txt = '';
			foreach ( $it['items'] as $x ) {
				if ( 'p' !== $x['t'] ) {
					continue;
				}
				if ( lfd_has_cls( $x, 'lfd-stat-n' ) ) {
					$num = lfd_plain( $x['html'] );
				} elseif ( lfd_has_cls( $x, 'lfd-stat-l' ) ) {
					$lab = lfd_plain( $x['html'] );
				} else {
					$txt .= ( $txt ? '<br>' : '' ) . wp_kses( $x['html'], array( 'strong' => array(), 'em' => array(), 'br' => array(), 'a' => array( 'href' => array() ) ) );
				}
			}
			if ( '' === $num ) {
				continue;
			}
			// « 356 » défile jusqu'à sa valeur ; un « + », « % » ou « k » après le chiffre s'affiche en italique framboise.
			if ( preg_match( '~^(\D*?)(\d[\d\s\x{00A0}\x{202F}.,]*)(.*)$~u', $num, $m ) ) {
				$val  = (float) str_replace( array( ' ', "\xC2\xA0", "\xE2\x80\xAF", ',' ), array( '', '', '', '.' ), $m[2] );
				$numh = ( '' !== $m[1] ? '<span class="pfx">' . esc_html( $m[1] ) . '</span>' : '' ) . '<span class="num" data-to="' . esc_attr( $val ) . '" data-raw="' . esc_attr( trim( $m[2] ) ) . '">' . esc_html( trim( $m[2] ) ) . '</span>' . ( '' !== trim( $m[3] ) ? '<span class="sfx">' . esc_html( trim( $m[3] ) ) . '</span>' : '' );
			} else {
				$numh = esc_html( $num );
			}
			$cells .= '<div class="stat rv" style="--dl:' . ( 0.12 * $n ) . 's"><p class="stat-n">' . $numh . '</p>' . ( '' !== $lab ? '<p class="stat-l">' . esc_html( $lab ) . '</p>' : '' ) . ( '' !== $txt ? '<p class="stat-t">' . $txt . '</p>' : '' ) . '</div>';
			$n++;
		}
	}
	return '<section class="stats wrap">' . $head . '<div class="stats-g" style="--n:' . max( 1, min( 4, $n ) ) . '">' . $cells . '</div></section>';
}

function lfd_r_manifesto( $items ) {
	$out = '';
	foreach ( $items as $it ) {
		if ( 'h' === $it['t'] ) {
			$out .= '<h' . $it['l'] . ' class="mf" data-fill>' . esc_html( lfd_plain( $it['html'] ) ) . '</h' . $it['l'] . '>';
		} elseif ( 'p' === $it['t'] ) {
			$out .= '<p class="rv">' . wp_kses_post( $it['html'] ) . '</p>';
		} elseif ( 'rich' === $it['t'] ) {
			$out .= '<div class="rich rv">' . wp_kses_post( $it['html'] ) . '</div>';
		}
	}
	return '<section class="manifesto"><div class="wrap">' . $out . '</div></section>';
}

function lfd_r_stack( $items ) {
	$out = '';
	$n   = 0;
	foreach ( $items as $g ) {
		if ( 'group' !== $g['t'] ) {
			continue;
		}
		$n++;
		$k   = lfd_first_media( $g['items'] );
		$pic = '';
		$sub = $g['items'];
		if ( $k >= 0 ) {
			$im = $sub[ $k ];
			if ( 'video' === $im['t'] ) {
				$pic = '<div class="pic">' . lfd_video( $im ) . lfd_vbtns( $im ) . '</div>';
			} else {
				$tag = $im['href'] ? 'a' : 'div';
				$pic = '<' . $tag . ' class="pic"' . ( $im['href'] ? ' href="' . esc_url( $im['href'] ) . '"' : '' ) . '><img class="fg" src="' . esc_url( $im['src'] ) . '" alt="' . esc_attr( $im['alt'] ) . '" loading="lazy" decoding="async"></' . $tag . '>';
			}
			unset( $sub[ $k ] );
		}
		$out .= '<article class="panel' . ( 0 === $n % 2 ? ' alt' : '' ) . '"><div class="panel-in"><div class="txt rv-g"><span class="num">' . sprintf( '%02d', $n ) . '</span>' . lfd_flow( array_values( $sub ) ) . '</div>' . $pic . '</div></article>';
	}
	return '<section class="stack">' . $out . '</section>';
}

function lfd_r_statement( $items ) {
	return '<section class="state wrap">' . lfd_flow( $items, array( 'split' => true ) ) . '</section>';
}

function lfd_r_exp( $items ) {
	$head = '';
	$eye  = '';
	$side = array();
	$rows = '';
	foreach ( $items as $it ) {
		if ( 'group' === $it['t'] ) {
			$k    = '';
			$t    = '';
			$href = '';
			foreach ( $it['items'] as $s ) {
				if ( 'p' === $s['t'] ) {
					$k = wp_strip_all_tags( $s['html'] );
				} elseif ( 'h' === $s['t'] ) {
					$t = wp_strip_all_tags( $s['html'] );
					if ( preg_match( '~<a[^>]*\shref="([^"]*)"~', $s['html'], $m ) ) {
						$href = html_entity_decode( $m[1] );
					}
					$lvl = $s['l'];
				} elseif ( 'btn' === $s['t'] && ! $href ) {
					$href = $s['href'];
				}
			}
			$lvl   = isset( $lvl ) ? $lvl : 3;
			$inner = '<span class="k">' . esc_html( $k ) . '</span><h' . $lvl . '>' . esc_html( $t ) . '</h' . $lvl . '>' . lfd_arrow();
			$rows .= ( $href ? '<a class="xrow rv" href="' . esc_url( $href ) . '">' . $inner . '</a>' : '<div class="xrow rv">' . $inner . '</div>' ) . '<span class="rule"></span>';
		} elseif ( 'h' === $it['t'] && lfd_has_cls( $it, 'lfd-eyebrow' ) ) {
			$eye = '<h' . $it['l'] . ' class="eyebrow rv">' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
		} elseif ( 'h' === $it['t'] && '' === $head ) {
			$head = '<h' . $it['l'] . ' class="spl">' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
		} else {
			$side[] = $it;
		}
	}
	return '<section class="exp wrap">' . $eye . '<div class="exp-head">' . $head . '<div class="txt">' . lfd_flow( $side ) . '</div></div>'
		. ( $rows ? '<div class="xlist"><span class="rule"></span>' . $rows . '</div>' : '' ) . '</section>';
}

function lfd_r_cols( $items, $cls ) {
	$lead = array();
	$cols = '';
	$n    = 0;
	foreach ( $items as $it ) {
		if ( 'group' === $it['t'] ) {
			$n++;
			$cols .= '<div class="col rv" style="--dl:' . ( 0.08 * ( $n - 1 ) ) . 's">' . lfd_flow( $it['items'], array( 'rv' => '', 'btn' => 'btn' ) ) . '</div>';
		} else {
			$lead[] = $it;
		}
	}
	$cards = false !== strpos( $cls, 'lfd-cards' ) ? ' cards' : '';
	return '<section class="colsec wrap">' . ( $lead ? '<div class="cols-lead">' . lfd_flow( $lead, array( 'split' => true ) ) . '</div>' : '' )
		. '<div class="cols' . $cards . '" style="--n:' . max( 1, min( 4, $n ) ) . '">' . $cols . '</div></section>';
}

function lfd_r_gallery( $items ) {
	$media = array();
	foreach ( $items as $it ) {
		if ( 'img' === $it['t'] || 'video' === $it['t'] ) {
			$media[] = $it;
		}
	}
	$out = '';
	$n   = count( $media );
	$fx  = array( 'wu', 'wl', 'wr' );
	$col = 0; // 0 = prochaine image à gauche, 1 = à droite.
	foreach ( $media as $k => $m ) {
		// 1er visuel et vidéos en pleine largeur, puis paires décalées ; un visuel sans voisin repasse en pleine largeur.
		$next_pairs = isset( $media[ $k + 1 ] ) && 'img' === $media[ $k + 1 ]['t'];
		if ( 0 === $k || 'video' === $m['t'] || ( 0 === $col && ! $next_pairs ) ) {
			$pos = 'g-full';
			$col = 0;
		} elseif ( 0 === $col ) {
			$pos = 'g-a';
			$col = 1;
		} else {
			$pos = 'g-b';
			$col = 0;
		}
		$out .= lfd_frame( $m, $pos . ' ' . $fx[ $k % 3 ] );
	}
	return '<section class="wrap"><div class="gal">' . $out . '</div></section>';
}

/** Texte posé sur une image de fond plein cadre. */
function lfd_r_cover( $items ) {
	$k  = lfd_first_media( $items );
	$bg = '';
	$bt = '';
	if ( $k >= 0 ) {
		$bg = '<div class="bgimg">' . lfd_media_fill( $items[ $k ], false ) . '</div>';
		$bt = 'video' === $items[ $k ]['t'] ? lfd_vbtns( $items[ $k ] ) : '';
		unset( $items[ $k ] );
	}
	return '<section class="cover" data-par>' . $bg . $bt . '<div class="wrap">' . lfd_flow( array_values( $items ), array( 'split' => true, 'btn' => 'btn' ) ) . '</div></section>';
}

/** Vidéo (ou image) sur toute la largeur, 700 px de haut minimum : une césure dans la page. */
function lfd_r_videoband( $items ) {
	$k = lfd_first_media( $items );
	if ( $k < 0 ) {
		return '';
	}
	$cap = '';
	foreach ( $items as $it ) {
		if ( '' === $cap && 'h' === $it['t'] ) {
			$cap = '<h' . $it['l'] . ' class="vband-cap">' . wp_kses_post( $it['html'] ) . '</h' . $it['l'] . '>';
		} elseif ( '' === $cap && 'p' === $it['t'] ) {
			$cap = '<p class="vband-cap">' . wp_kses_post( $it['html'] ) . '</p>';
		}
	}
	return '<section class="vband" data-vband><div class="vband-in">' . lfd_media_fill( $items[ $k ] ) . $cap . '</div></section>';
}

/** Vidéos côte à côte, trois par ligne ; flèches et compteur dès qu'il y en a plus. */
function lfd_r_videos3( $items ) {
	$cells = '';
	$n     = 0;
	foreach ( $items as $it ) {
		if ( 'img' !== $it['t'] && 'video' !== $it['t'] ) {
			continue;
		}
		$cap   = trim( wp_strip_all_tags( $it['cap'] ) );
		$media = lfd_media_fill( $it );
		$box   = ( 'img' === $it['t'] && $it['href'] ) ? '<a class="vmedia" href="' . esc_url( $it['href'] ) . '">' . $media . '</a>' : '<div class="vmedia">' . $media . '</div>';
		$capl   = ( 'video' === $it['t'] && $it['href'] && '' !== $cap ) ? '<a href="' . esc_url( $it['href'] ) . '">' . esc_html( $cap ) . ' →</a>' : esc_html( $cap );
		$cells .= '<figure class="vcell rv" style="--dl:' . ( 0.08 * ( $n % 3 ) ) . 's">' . $box . ( '' !== $cap ? '<figcaption>' . $capl . '</figcaption>' : '' ) . '</figure>';
		$n++;
	}
	if ( ! $n ) {
		return '';
	}
	$l = '<svg viewBox="0 0 56 24" aria-hidden="true"><path d="M56 12H2M13 1 2 12l11 11"/></svg>';
	$r = '<svg viewBox="0 0 56 24" aria-hidden="true"><path d="M0 12h54M43 1l11 11-11 11"/></svg>';
	return '<section class="vrow" data-vrow><div class="vrow-nav"><button type="button" class="vprev" aria-label="Précédent">' . $l . '</button>'
		. '<span class="vcount" aria-live="polite"><b>1</b>/' . $n . '</span><button type="button" class="vnext" aria-label="Suivant">' . $r . '</button></div>'
		. '<div class="vrow-track" tabindex="0" aria-label="Vidéos">' . $cells . '</div></section>';
}

/** Liste de mots-clés en grands caractères. */
function lfd_r_words( $items ) {
	$out = '';
	$n   = 0;
	foreach ( $items as $it ) {
		if ( 'li' === $it['t'] || 'p' === $it['t'] ) {
			$out .= '<li class="rv" style="--dl:' . ( 0.05 * $n ) . 's">' . esc_html( lfd_plain( $it['html'] ) ) . '</li>';
			$n++;
		}
	}
	return '<section class="words wrap"><ul>' . $out . '</ul></section>';
}

/** Mosaïque de projets : chaque image (avec son lien et sa légende « Nom · catégorie ») devient une tuile. */
function lfd_r_mosaic( $items ) {
	$out = '';
	foreach ( $items as $it ) {
		if ( 'img' !== $it['t'] && 'video' !== $it['t'] ) {
			continue;
		}
		$parts = array_map( 'trim', explode( '·', wp_strip_all_tags( $it['cap'] ), 2 ) );
		$label = '' !== $parts[0] ? '<span class="tl"><b>' . esc_html( $parts[0] ) . '</b>' . ( isset( $parts[1] ) ? '<i>' . esc_html( $parts[1] ) . '</i>' : '' ) . '</span>' : '';
		$img   = lfd_media_fill( $it, false );
		$name  = '' !== $parts[0] ? $parts[0] : $it['alt'];
		$link  = $it['href'] ? '<a class="tile-a" href="' . esc_url( $it['href'] ) . '" aria-label="' . esc_attr( 'Voir le projet ' . $name ) . '">' . $label . '</a>' : $label;
		$out  .= '<div class="tile rv">' . $img . $link . ( 'video' === $it['t'] ? lfd_vbtns( $it ) : '' ) . '</div>';
	}
	return '<section class="mosaic">' . $out . '</section>';
}

function lfd_r_logos( $items ) {
	$out = '';
	foreach ( $items as $it ) {
		if ( 'img' === $it['t'] ) {
			$out .= '<img src="' . esc_url( $it['src'] ) . '" alt="' . esc_attr( $it['alt'] ) . '" loading="lazy" decoding="async">';
		} elseif ( 'p' === $it['t'] || 'li' === $it['t'] ) {
			// Sans fichier de logo, le nom s'affiche en lettres ; une image ajoutée dans le groupe le remplace.
			$out .= '<span class="mark">' . esc_html( wp_strip_all_tags( $it['html'] ) ) . '</span>';
		}
	}
	return '<section class="logos wrap rv">' . $out . '</section>';
}

function lfd_r_text( $items, $cls = '' ) {
	$head = '';
	if ( $items && 'h' === $items[0]['t'] ) {
		$h    = array_shift( $items );
		$head = '<h' . $h['l'] . ' class="spl">' . wp_kses_post( $h['html'] ) . '</h' . $h['l'] . '>';
		while ( $items && 'h' === $items[0]['t'] && $items[0]['l'] > $h['l'] && count( $items ) > 1 && 'h' !== $items[1]['t'] && 'p' !== $items[1]['t'] ) {
			break;
		}
	}
	$alt = false !== strpos( ' ' . $cls . ' ', ' lfd-alt ' ) ? ' alt' : '';
	return '<section class="duo wrap' . $alt . '"><div class="duo-h">' . $head . '</div><div class="duo-b">' . lfd_flow( $items, array( 'btn' => 'btn' ) ) . '</div></section>';
}

function lfd_r_meta( $items ) {
	$label = '';
	$out   = '';
	foreach ( $items as $it ) {
		if ( 'h' === $it['t'] ) {
			$label = wp_strip_all_tags( $it['html'] );
		} elseif ( 'p' === $it['t'] ) {
			$out  .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . wp_kses_post( $it['html'] ) . '</dd></div>';
			$label = '';
		}
	}
	return '<section class="wrap"><dl class="meta rv">' . $out . '</dl><span class="rule"></span></section>';
}

function lfd_r_case( $items ) {
	$lead = '';
	if ( $items && 'p' === $items[0]['t'] ) {
		$p    = array_shift( $items );
		$lead = '<p class="lead" data-fill>' . esc_html( lfd_plain( $p['html'] ) ) . '</p>';
	}
	return '<section class="wrap"><div class="case-body' . ( $items ? '' : ' solo' ) . '">' . $lead . ( $items ? '<div class="txt">' . lfd_flow( $items ) . '</div>' : '' ) . '</div></section>';
}

function lfd_r_prose( $items ) {
	return '<section class="prose wrap">' . lfd_flow( $items, array( 'rv' => '' ) ) . '</section>';
}

/**
 * Enrobage d'une section selon les classes de son groupe :
 * fond « lfd-band-dark | accent | soft », couleur libre « lfd-bg-RRGGBB »,
 * taille des titres « lfd-ts-s | l | xl », taille du texte « lfd-bs-s | l »,
 * interlettrage « lfd-ls-tight | wide », interlignage « lfd-lh-tight | loose ».
 */
function lfd_wrap_section( $out, $classes ) {
	$wrap  = array();
	$style = '';
	foreach ( array( 'dark', 'accent', 'soft' ) as $band ) {
		if ( in_array( 'lfd-band-' . $band, $classes, true ) ) {
			$wrap[] = 'band band-' . $band;
		}
	}
	foreach ( $classes as $c ) {
		if ( ! $wrap && preg_match( '~^lfd-bg-([0-9a-f]{6})$~i', $c, $m ) ) {
			$hex    = strtolower( $m[1] );
			$lum    = ( 0.2126 * hexdec( substr( $hex, 0, 2 ) ) + 0.7152 * hexdec( substr( $hex, 2, 2 ) ) + 0.0722 * hexdec( substr( $hex, 4, 2 ) ) ) / 255;
			$wrap[] = 'band band-custom' . ( $lum < 0.55 ? ' on-dark' : '' );
			$style  = ' style="--band:#' . $hex . '"';
		}
		if ( preg_match( '~^lfd-(ts-(?:s|l|xl)|bs-(?:s|l)|ls-(?:tight|wide)|lh-(?:tight|loose)|ta-(?:center|right))$~', $c, $m ) ) {
			$wrap[] = $m[1];
		}
	}
	return $wrap ? '<div class="lfdw ' . esc_attr( implode( ' ', $wrap ) ) . '"' . $style . '>' . $out . '</div>' : $out;
}

/* ------------------------------------------------------------- aiguillage */

add_filter(
	'render_block',
	function ( $html, $block ) {
		if ( is_admin() || empty( $block['blockName'] ) || 'core/group' !== $block['blockName'] ) {
			return $html;
		}
		$cls = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
		if ( false === strpos( $cls, 'lfd-' ) ) {
			return $html;
		}
		$classes = preg_split( '/\s+/', trim( $cls ) );
		if ( array_intersect( $classes, lfd_child_classes() ) ) {
			return $html; // rendu par la section parente.
		}
		$map = array(
			'lfd-hero'      => 'lfd_r_hero',
			'lfd-intro'     => 'lfd_r_intro',
			'lfd-feat'      => 'lfd_r_feat',
			'lfd-manifesto' => 'lfd_r_manifesto',
			'lfd-stack'     => 'lfd_r_stack',
			'lfd-statement' => 'lfd_r_statement',
			'lfd-exp'       => 'lfd_r_exp',
			'lfd-cols'      => 'lfd_r_cols',
			'lfd-gallery'   => 'lfd_r_gallery',
			'lfd-logos'     => 'lfd_r_logos',
			'lfd-mosaic'    => 'lfd_r_mosaic',
			'lfd-cover'     => 'lfd_r_cover',
			'lfd-videoband' => 'lfd_r_videoband',
			'lfd-videos3'   => 'lfd_r_videos3',
			'lfd-stats'     => 'lfd_r_stats',
			'lfd-words'     => 'lfd_r_words',
			'lfd-text'      => 'lfd_r_text',
			'lfd-meta'      => 'lfd_r_meta',
			'lfd-case'      => 'lfd_r_case',
			'lfd-prose'     => 'lfd_r_prose',
		);
		foreach ( $classes as $c ) {
			if ( isset( $map[ $c ] ) ) {
				$out = call_user_func( $map[ $c ], lfd_items( $block['innerBlocks'] ), $cls );
				return lfd_wrap_section( $out, $classes );
			}
		}
		return $html;
	},
	10,
	2
);

/* -------------------------------------------------------------- projets */

/** Pages « projet », dans l'ordre choisi. */
function lfd_projects() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$posts = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'meta_key'       => '_lfd_type',
			'meta_value'     => 'projet',
			'no_found_rows'  => true,
		)
	);
	$cache = array();
	foreach ( $posts as $p ) {
		$thumb = get_post_meta( $p->ID, '_lfd_thumb', true );
		if ( ! $thumb && has_post_thumbnail( $p ) ) {
			$thumb = get_the_post_thumbnail_url( $p, 'large' );
		}
		$client  = get_post_meta( $p->ID, '_lfd_client', true );
		$cache[] = array(
			'id'    => $p->ID,
			'name'  => $client ? $client : get_the_title( $p ),
			'cat'   => get_post_meta( $p->ID, '_lfd_cat', true ),
			'thumb' => $thumb,
			'url'   => get_permalink( $p ),
			'order' => (int) get_post_meta( $p->ID, '_lfd_order', true ),
		);
	}
	usort(
		$cache,
		function ( $a, $b ) {
			return $a['order'] === $b['order'] ? strcmp( $a['name'], $b['name'] ) : $a['order'] - $b['order'];
		}
	);
	return $cache;
}

function lfd_card( $p ) {
	$img = $p['thumb'] ? '<div class="fr" data-fit><img src="' . esc_url( $p['thumb'] ) . '" alt="' . esc_attr( 'Projet ' . $p['name'] ) . '" loading="lazy" decoding="async"></div>' : '<div class="fr fr-empty"></div>';
	return '<a class="hcard" href="' . esc_url( $p['url'] ) . '">' . $img . '<b>' . esc_html( $p['name'] ) . '<i>' . esc_html( $p['cat'] ) . '</i></b></a>';
}

/** [lfd_projets] : galerie de tous les projets, qui défile à l'horizontale pendant le scroll. */
add_shortcode(
	'lfd_projets',
	function ( $atts ) {
		$atts  = shortcode_atts( array( 'titre' => 'Nos projets' ), $atts );
		$cards = implode( '', array_map( 'lfd_card', lfd_projects() ) );
		if ( ! $cards ) {
			return '';
		}
		$words = explode( ' ', $atts['titre'] );
		$last  = array_pop( $words );
		$title = esc_html( implode( ' ', $words ) ) . ' <em>' . esc_html( $last ) . '</em>';
		return '<section class="hpin" data-hpin aria-label="' . esc_attr( $atts['titre'] ) . '"><div class="hstick"><div class="hhead"><h2>' . $title . '</h2><div class="hprog" aria-hidden="true"></div></div><div class="htrack">' . $cards . '</div></div></section>';
	}
);

/** [lfd_portfolio] : grille de tous les projets. */
add_shortcode(
	'lfd_portfolio',
	function () {
		$out = '';
		foreach ( lfd_projects() as $k => $p ) {
			$out .= '<div class="rv" style="--dl:' . ( 0.06 * ( $k % 3 ) ) . 's">' . lfd_card( $p ) . '</div>';
		}
		return '<section class="pgrid wrap">' . $out . '</section>';
	}
);

/** Bloc « projet suivant » en bas des pages projet. */
function lfd_next_project( $post_id ) {
	$all = lfd_projects();
	$n   = count( $all );
	if ( $n < 2 ) {
		return '';
	}
	foreach ( $all as $k => $p ) {
		if ( $p['id'] === (int) $post_id ) {
			$nx = $all[ ( $k + 1 ) % $n ];
			return '<a class="next" href="' . esc_url( $nx['url'] ) . '">' . ( $nx['thumb'] ? '<div class="bgimg"><img src="' . esc_url( $nx['thumb'] ) . '" alt="" loading="lazy" decoding="async"></div>' : '' )
				. '<div class="wrap"><p class="eyebrow">Projet suivant</p><h2>' . esc_html( $nx['name'] ) . ' →</h2></div></a>';
		}
	}
	return '';
}
