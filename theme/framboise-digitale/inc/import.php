<?php
/**
 * Import des contenus : Apparence → Import La Framboise.
 * Remplit chaque page existante (retrouvée par son adresse) avec son contenu
 * mis en forme pour le thème. Aucune adresse, aucun titre, aucun réglage SEO n'est modifié.
 * Le contenu précédent de chaque page est sauvegardé et peut être restauré.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function lfd_import_data() {
	$file = get_template_directory() . '/data/pages.json';
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$data = json_decode( file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	return is_array( $data ) && isset( $data['pages'] ) ? $data : null;
}

/** Retrouve la page correspondant à une adresse du site d'origine. */
function lfd_import_find( $slug ) {
	if ( '' === $slug ) {
		$front = (int) get_option( 'page_on_front' );
		return $front ? get_post( $front ) : null;
	}
	$p = get_page_by_path( $slug, OBJECT, 'page' );
	return $p ? $p : null;
}

function lfd_import_localize( $content, $base ) {
	// Les adresses du site d'origine deviennent celles du site courant (utile sur une copie de test).
	return str_replace( array( $base, str_replace( 'https://', 'http://', $base ) ), untrailingslashit( home_url() ), $content );
}

function lfd_import_one( $slug, $entry, $base ) {
	$post = lfd_import_find( $slug );
	if ( ! $post ) {
		return 'absente';
	}
	if ( '' === get_post_meta( $post->ID, '_lfd_backup_done', true ) ) {
		update_post_meta( $post->ID, '_lfd_backup', wp_slash( $post->post_content ) );
		update_post_meta( $post->ID, '_lfd_backup_tpl', get_post_meta( $post->ID, '_wp_page_template', true ) );
		update_post_meta( $post->ID, '_lfd_backup_done', '1' );
	}
	$content = lfd_import_localize( $entry['content'], $base );
	// Seul le contenu change : ni le titre, ni l'adresse, ni l'état de publication.
	// L'ancien gabarit (créé par le constructeur de pages) n'existe pas dans ce thème : il est mémorisé puis remis à « défaut »,
	// sans quoi WordPress refuse l'enregistrement de la page.
	$GLOBALS['lfd_importing'] = true;
	$r = wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( $content ), 'page_template' => 'default' ), true );
	$GLOBALS['lfd_importing'] = false;
	delete_post_meta( $post->ID, '_lfd_user_edit' );
	if ( is_wp_error( $r ) ) {
		return 'erreur : ' . $r->get_error_message();
	}
	update_post_meta( $post->ID, '_lfd_type', $entry['type'] );
	update_post_meta( $post->ID, '_lfd_imported', gmdate( 'c' ) );
	clean_post_cache( $post->ID );
	update_post_meta( $post->ID, '_lfd_hash', md5( (string) get_post( $post->ID )->post_content ) );
	update_post_meta( $post->ID, '_lfd_src', md5( $content ) );
	foreach ( array( 'client' => '_lfd_client', 'cat' => '_lfd_cat', 'order' => '_lfd_order' ) as $k => $meta ) {
		if ( isset( $entry[ $k ] ) ) {
			update_post_meta( $post->ID, $meta, $entry[ $k ] );
		}
	}
	if ( isset( $entry['thumb'] ) ) {
		update_post_meta( $post->ID, '_lfd_thumb', esc_url_raw( lfd_import_localize( $entry['thumb'], $base ) ) );
	}
	return 'importée';
}

function lfd_import_restore_one( $slug ) {
	$post = lfd_import_find( $slug );
	if ( ! $post || '1' !== get_post_meta( $post->ID, '_lfd_backup_done', true ) ) {
		return 'rien à restaurer';
	}
	$tpl = get_post_meta( $post->ID, '_lfd_backup_tpl', true );
	$GLOBALS['lfd_importing'] = true;
	wp_update_post( array( 'ID' => $post->ID, 'post_content' => wp_slash( get_post_meta( $post->ID, '_lfd_backup', true ) ), 'page_template' => 'default' ) );
	$GLOBALS['lfd_importing'] = false;
	if ( $tpl ) {
		update_post_meta( $post->ID, '_wp_page_template', $tpl );
	}
	foreach ( array( '_lfd_backup', '_lfd_backup_tpl', '_lfd_backup_done', '_lfd_imported', '_lfd_hash', '_lfd_src', '_lfd_user_edit', '_lfd_type', '_lfd_client', '_lfd_cat', '_lfd_order', '_lfd_thumb' ) as $m ) {
		delete_post_meta( $post->ID, $m );
	}
	return 'restaurée';
}

add_action(
	'admin_menu',
	function () {
		add_theme_page( 'Import La Framboise', 'Import La Framboise', 'manage_options', 'lfd-import', 'lfd_import_screen' );
	}
);

/** Importe toutes les pages trouvées. Renvoie le décompte par résultat. */
function lfd_import_all() {
	$data = lfd_import_data();
	$n    = array();
	if ( ! $data ) {
		return $n;
	}
	foreach ( $data['pages'] as $slug => $entry ) {
		$r       = lfd_import_one( $slug, $entry, $data['base'] );
		$n[ $r ] = isset( $n[ $r ] ) ? $n[ $r ] + 1 : 1;
	}
	update_option( 'lfd_import_ran', gmdate( 'c' ) );
	update_option( 'lfd_data_version', LFD_VERSION );
	return $n;
}

/**
 * Nouvelle version du thème : les pages que personne n'a modifiées depuis leur mise en place
 * reçoivent la nouvelle mise en forme ; les pages retouchées à la main ne sont pas touchées.
 */
/**
 * État d'une page par rapport aux contenus du thème :
 * absent (page introuvable), todo (jamais mise en forme), uptodate (mise en forme actuelle),
 * outdated (ancienne mise en forme, jamais retouchée), edited (retouchée à la main depuis).
 */
function lfd_import_status( $slug, $entry, $base ) {
	$post = lfd_import_find( $slug );
	if ( ! $post ) {
		return 'absent';
	}
	$imported = get_post_meta( $post->ID, '_lfd_imported', true );
	if ( ! $imported ) {
		return 'todo';
	}
	$content = (string) $post->post_content;
	$src     = md5( lfd_import_localize( $entry['content'], $base ) );
	if ( get_post_meta( $post->ID, '_lfd_user_edit', true ) ) {
		return 'edited';
	}
	if ( $src === get_post_meta( $post->ID, '_lfd_src', true ) && md5( $content ) === get_post_meta( $post->ID, '_lfd_hash', true ) ) {
		return 'uptodate';
	}
	// Jamais retouchée : contenu identique à celui posé par le thème, ou page non modifiée depuis sa mise en place.
	$hash      = get_post_meta( $post->ID, '_lfd_hash', true );
	$same      = $hash && ( md5( $content ) === $hash || md5( (string) get_post_field( 'post_content', $post->ID ) ) === $hash );
	$t_import  = strtotime( $imported );
	$t_edit    = strtotime( $post->post_modified_gmt . ' UTC' );
	$untouched = $t_import && $t_edit && abs( $t_edit - $t_import ) <= 180;
	if ( $same || $untouched ) {
		return $src === get_post_meta( $post->ID, '_lfd_src', true ) ? 'uptodate' : 'outdated';
	}
	return 'edited';
}

/**
 * Nouvelle version du thème : les pages que personne n'a retouchées depuis leur mise en place
 * reçoivent la nouvelle mise en forme ; les pages retouchées à la main ne sont pas touchées.
 */
function lfd_import_refresh() {
	$data = lfd_import_data();
	$n    = array();
	if ( ! $data ) {
		return $n;
	}
	$labels = array( 'uptodate' => 'déjà à jour', 'edited' => 'conservée (retouchée à la main)' );
	foreach ( $data['pages'] as $slug => $entry ) {
		$st = lfd_import_status( $slug, $entry, $data['base'] );
		if ( 'absent' === $st ) {
			continue;
		}
		$r       = isset( $labels[ $st ] ) ? $labels[ $st ] : lfd_import_one( $slug, $entry, $data['base'] );
		$n[ $r ] = isset( $n[ $r ] ) ? $n[ $r ] + 1 : 1;
	}
	update_option( 'lfd_data_version', LFD_VERSION );
	return $n;
}

/**
 * À l'activation du thème : les pages reçoivent tout de suite leur contenu mis en forme
 * (sinon elles afficheraient l'ancien contenu brut), et le menu existant du site est repris.
 */
add_action(
	'after_switch_theme',
	function () {
		if ( ! get_option( 'lfd_import_ran' ) ) {
			set_transient( 'lfd_import_report', lfd_import_all(), DAY_IN_SECONDS );
		}
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		if ( empty( $locations['primary'] ) ) {
			$best = 0;
			$max  = 0;
			foreach ( wp_get_nav_menus() as $menu ) {
				if ( $menu->count > $max ) {
					$max  = $menu->count;
					$best = $menu->term_id;
				}
			}
			if ( $best ) {
				$locations['primary'] = $best;
				set_theme_mod( 'nav_menu_locations', $locations );
			}
		}
	}
);

/**
 * Filet de sécurité : si le thème est déjà actif et que les contenus n'ont pas encore été mis en place
 * (mise à jour du thème par exemple), l'import se fait à la première visite de l'administration.
 */
add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! get_option( 'lfd_import_ran' ) ) {
			set_transient( 'lfd_import_report', lfd_import_all(), DAY_IN_SECONDS );
		} elseif ( LFD_VERSION !== get_option( 'lfd_data_version' ) ) {
			set_transient( 'lfd_import_report', lfd_import_refresh(), DAY_IN_SECONDS );
		}
	}
);

/**
 * Quand on quitte le thème : les pages non modifiées depuis l'import retrouvent leur contenu d'origine,
 * pour que l'ancien thème réapparaisse exactement comme avant.
 */
add_action(
	'switch_theme',
	function () {
		$data = lfd_import_data();
		if ( ! $data ) {
			return;
		}
		foreach ( array_keys( $data['pages'] ) as $slug ) {
			$post = lfd_import_find( $slug );
			if ( $post && '1' === get_post_meta( $post->ID, '_lfd_backup_done', true ) && md5( $post->post_content ) === get_post_meta( $post->ID, '_lfd_hash', true ) ) {
				lfd_import_restore_one( $slug );
			}
		}
		delete_option( 'lfd_import_ran' );
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$report = get_transient( 'lfd_import_report' );
		if ( is_array( $report ) ) {
			delete_transient( 'lfd_import_report' );
			$parts = array();
			foreach ( $report as $state => $count ) {
				$parts[] = $count . ' ' . $state;
			}
			echo '<div class="notice notice-success is-dismissible"><p><strong>Thème La Framboise Digitale :</strong> contenus mis en place à l’activation (' . esc_html( implode( ', ', $parts ) ) . '). <a href="' . esc_url( admin_url( 'themes.php?page=lfd-import' ) ) . '">Voir le détail</a></p></div>';
		}
		if ( defined( 'KUBIO_VERSION' ) || function_exists( 'kubio_url' ) || class_exists( '\\Kubio\\Flags' ) ) {
			echo '<div class="notice notice-warning"><p><strong>Thème La Framboise Digitale :</strong> l’extension Kubio est encore active. Le thème n’en a pas besoin et neutralise son affichage ; vous pouvez la désactiver une fois le site vérifié.</p></div>';
		}
		$screen = get_current_screen();
		if ( ! get_option( 'lfd_import_ran' ) && ! ( $screen && 'appearance_page_lfd-import' === $screen->id ) ) {
			echo '<div class="notice notice-info"><p><strong>Thème La Framboise Digitale :</strong> les pages n’ont pas encore reçu leur contenu mis en forme. <a href="' . esc_url( admin_url( 'themes.php?page=lfd-import' ) ) . '">Ouvrir l’import</a></p></div>';
		}
	}
);

function lfd_import_screen() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$data = lfd_import_data();
	echo '<div class="wrap"><h1>Import des contenus La Framboise Digitale</h1>';
	if ( ! $data ) {
		echo '<div class="notice notice-error"><p>Fichier de contenus introuvable dans le thème (data/pages.json).</p></div></div>';
		return;
	}
	$results = array();
	if ( isset( $_POST['lfd_action'] ) && check_admin_referer( 'lfd_import' ) ) {
		$action = sanitize_key( wp_unslash( $_POST['lfd_action'] ) );
		$only   = isset( $_POST['lfd_slugs'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['lfd_slugs'] ) ) : array();
		foreach ( $data['pages'] as $slug => $entry ) {
			$key = '' === $slug ? '__home' : $slug;
			if ( ! in_array( $key, $only, true ) ) {
				continue;
			}
			$results[ $slug ] = 'restore' === $action ? lfd_import_restore_one( $slug ) : lfd_import_one( $slug, $entry, $data['base'] );
		}
		if ( 'import' === $action && $results ) {
			update_option( 'lfd_import_ran', gmdate( 'c' ) );
		}
		echo '<div class="notice notice-success"><p>' . esc_html( count( $results ) ) . ' page(s) traitée(s).</p></div>';
	}
	echo '<p>Les contenus sont mis en place automatiquement à l’activation du thème, et mis à jour à chaque nouvelle version pour les pages que vous n’avez pas retouchées. Cet écran sert à vérifier, relancer ou annuler l’opération page par page.</p><p><strong>Une page cochée est remplacée par la mise en forme du thème</strong> (son contenu précédent reste restaurable). Seules les pages à mettre à jour sont cochées d’avance.</p>';
	echo '<p>Chaque page est retrouvée par son adresse actuelle. <strong>Seul le contenu de la page est remplacé</strong> : l’adresse, le titre, la date et les réglages SEO ne changent pas. Le contenu précédent est sauvegardé et le bouton « Restaurer » le remet en place.</p>';
	echo '<p><strong>Avant de lancer l’import sur le site en ligne, faites une sauvegarde complète et testez sur une copie.</strong></p>';
	if ( ! get_option( 'page_on_front' ) ) {
		echo '<div class="notice notice-warning inline"><p>Aucune page d’accueil statique n’est définie (Réglages → Lecture) : la page d’accueil ne peut pas être importée.</p></div>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'lfd_import' );
	echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><td class="check-column"><input type="checkbox" onclick="document.querySelectorAll(\'.lfd-cb\').forEach(function(c){c.checked=this.checked}.bind(this))"></td><th>Adresse</th><th>Type</th><th>Page trouvée</th><th>État</th></tr></thead><tbody>';
	foreach ( $data['pages'] as $slug => $entry ) {
		$post   = lfd_import_find( $slug );
		$st     = lfd_import_status( $slug, $entry, $data['base'] );
		$labels = array(
			'absent'   => 'page absente du site',
			'todo'     => 'à mettre en forme',
			'uptodate' => 'à jour',
			'outdated' => 'ancienne mise en forme : à mettre à jour',
			'edited'   => 'retouchée à la main : conservée telle quelle. Cochez-la pour la remplacer par la mise en forme du thème',
		);
		$state  = $labels[ $st ];
		if ( isset( $results[ $slug ] ) ) {
			$state = $results[ $slug ] . ' · ' . $state;
		}
		$key = '' === $slug ? '__home' : $slug;
		echo '<tr><th class="check-column"><input class="lfd-cb" type="checkbox" name="lfd_slugs[]" value="' . esc_attr( $key ) . '"' . ( $post ? ( in_array( $st, array( 'todo', 'outdated' ), true ) ? ' checked' : '' ) : ' disabled' ) . '></th>';
		echo '<td><code>/' . esc_html( $slug ? $slug . '/' : '' ) . '</code></td><td>' . esc_html( $entry['type'] ) . '</td>';
		echo '<td>' . ( $post ? '<a href="' . esc_url( get_permalink( $post ) ) . '" target="_blank" rel="noopener">' . esc_html( get_the_title( $post ) ) . '</a>' : '—' ) . '</td><td>' . esc_html( $state ) . '</td></tr>';
	}
	echo '</tbody></table><p style="margin-top:16px">';
	echo '<button class="button button-primary" name="lfd_action" value="import">Mettre en forme les pages cochées</button> ';
	echo '<button class="button" name="lfd_action" value="restore" onclick="return confirm(\'Remettre le contenu précédent des pages cochées ?\')">Restaurer le contenu précédent</button>';
	echo '</p></form></div>';
}


/** Toute modification d'une page par une personne (éditeur ou écran des sections) la protège des mises à jour automatiques. */
add_action(
	'save_post_page',
	function ( $post_id, $post, $update ) {
		if ( ! $update || ! empty( $GLOBALS['lfd_importing'] ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( get_post_meta( $post_id, '_lfd_imported', true ) ) {
			update_post_meta( $post_id, '_lfd_user_edit', gmdate( 'c' ) );
		}
	},
	10,
	3
);
