<?php
/**
 * Encadré « Visuel d'ouverture (plein écran) » dans l'éditeur de chaque page :
 * on y choisit l'image ou la vidéo affichée en plein écran en haut de la page,
 * sans toucher au contenu. Plusieurs visuels = diaporama.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Visuels d'ouverture enregistrés pour une page (tableau vide si aucun). */
function lfd_hero_meta( $post_id ) {
	$raw = get_post_meta( $post_id, '_lfd_hero_media', true );
	if ( ! $raw ) {
		return array();
	}
	$list = is_array( $raw ) ? $raw : json_decode( $raw, true );
	$out  = array();
	foreach ( (array) $list as $m ) {
		if ( empty( $m['url'] ) ) {
			continue;
		}
		$out[] = array(
			'url'    => esc_url_raw( $m['url'] ),
			'type'   => ( isset( $m['type'] ) && 'video' === $m['type'] ) ? 'video' : 'image',
			'x'      => isset( $m['x'] ) ? max( 0, min( 100, (int) $m['x'] ) ) : 50,
			'y'      => isset( $m['y'] ) ? max( 0, min( 100, (int) $m['y'] ) ) : 50,
			'name'   => isset( $m['name'] ) ? sanitize_text_field( $m['name'] ) : '',
			'sub'    => isset( $m['sub'] ) ? sanitize_text_field( $m['sub'] ) : '',
			'poster' => isset( $m['poster'] ) ? esc_url_raw( $m['poster'] ) : '',
			'sound'  => ! empty( $m['sound'] ),
		);
	}
	return $out;
}

/** L'encadré a-t-il déjà été enregistré pour cette page ? */
function lfd_hero_is_set( $post_id ) {
	return '' !== (string) get_post_meta( $post_id, '_lfd_hero_media', true );
}

/** Les mêmes visuels, au format utilisé par le rendu de l'ouverture. */
function lfd_hero_meta_items( $post_id ) {
	$items = array();
	foreach ( lfd_hero_meta( $post_id ) as $m ) {
		$items[] = array(
			't'      => 'video' === $m['type'] ? 'video' : 'img',
			'src'    => $m['url'],
			'alt'    => $m['name'],
			'cap'    => $m['sub'],
			'href'   => '',
			'cls'    => $m['sound'] ? 'lfd-sound' : '',
			'poster' => $m['poster'],
			'focus'  => $m['x'] . '% ' . $m['y'] . '%',
		);
	}
	return $items;
}

/** Visuels actuellement placés dans le contenu (groupe « Ouverture »), pour pré-remplir l'encadré. */
function lfd_hero_from_content( $post ) {
	$found = array();
	$walk  = function ( $blocks ) use ( &$walk, &$found ) {
		foreach ( $blocks as $b ) {
			if ( $found ) {
				return;
			}
			$cls = isset( $b['attrs']['className'] ) ? $b['attrs']['className'] : '';
			if ( 'core/group' === $b['blockName'] && false !== strpos( ' ' . $cls . ' ', ' lfd-hero ' ) ) {
				foreach ( lfd_items( $b['innerBlocks'] ) as $it ) {
					if ( 'img' === $it['t'] || 'video' === $it['t'] ) {
						$f       = explode( ' ', str_replace( '%', '', lfd_focus( $it ) ) );
						$found[] = array(
							'url'    => $it['src'],
							'type'   => 'video' === $it['t'] ? 'video' : 'image',
							'x'      => (int) $f[0],
							'y'      => isset( $f[1] ) ? (int) $f[1] : 50,
							'name'   => 'img' === $it['t'] ? $it['alt'] : '',
							'sub'    => wp_strip_all_tags( $it['cap'] ),
							'poster' => isset( $it['poster'] ) ? $it['poster'] : '',
							'sound'  => false !== strpos( ' ' . $it['cls'] . ' ', ' lfd-sound ' ),
						);
					}
				}
				if ( ! $found ) {
					$found = array( false );
				}
				return;
			}
			if ( ! empty( $b['innerBlocks'] ) ) {
				$walk( $b['innerBlocks'] );
			}
		}
	};
	$walk( parse_blocks( $post->post_content ) );
	return array_values( array_filter( $found ) );
}

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'lfd_hero', 'Visuel d’ouverture (plein écran)', 'lfd_hero_box', 'page', 'side', 'high' );
	}
);

add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {
		$screen = get_current_screen();
		if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && $screen && 'page' === $screen->post_type ) {
			wp_enqueue_media();
		}
	}
);

function lfd_hero_box( $post ) {
	wp_nonce_field( 'lfd_hero', 'lfd_hero_nonce' );
	// Rien d'enregistré : on montre les visuels actuellement dans la page. « __none » : tout a été retiré volontairement.
	$list = lfd_hero_is_set( $post->ID ) ? lfd_hero_meta( $post->ID ) : lfd_hero_from_content( $post );
	?>
	<style>
		#lfd-hero-list{display:flex;flex-direction:column;gap:14px;margin:12px 0}
		.lfd-h-row{display:flex;flex-direction:column;gap:10px;padding:10px;border:1px solid #dcdcde;border-radius:4px;background:#fff}
		.lfd-h-vis{position:relative;background:#101a2b;border-radius:3px;overflow:hidden;cursor:crosshair;aspect-ratio:16/10}
		.lfd-h-vis img,.lfd-h-vis video{width:100%;height:100%;object-fit:contain;display:block}
		.lfd-h-dot{position:absolute;width:22px;height:22px;margin:-11px 0 0 -11px;border-radius:50%;border:3px solid #fff;background:#d41b5e;box-shadow:0 0 0 1px rgba(0,0,0,.4);pointer-events:none}
		.lfd-h-tag{position:absolute;left:8px;top:8px;background:#d41b5e;color:#fff;font-size:11px;padding:2px 8px;border-radius:99px;pointer-events:none}
		.lfd-h-fields{display:flex;flex-direction:column;gap:8px;min-width:0}
		.lfd-h-fields label{font-weight:600;display:block;margin-bottom:2px}
		.lfd-h-fields input[type=text]{width:100%}
		.lfd-h-act{display:flex;gap:8px;flex-wrap:wrap;margin-top:4px}
		.lfd-h-help{color:#646970;margin:4px 0}
		.lfd-h-act .button{flex:1 1 auto;text-align:center}
		#lfd-hero-add{width:100%;justify-content:center;text-align:center}
	</style>
	<p class="lfd-h-help">C’est ici que se choisit le grand visuel affiché en plein écran en haut de la page : <strong>une image ou une vidéo</strong>. Avec plusieurs visuels, ils défilent en diaporama. Le texte de la page n’est pas modifié.</p>
	<div id="lfd-hero-list"></div>
	<p>
		<button type="button" class="button button-primary" id="lfd-hero-add">Ajouter une image ou une vidéo</button>
	</p>
	<p class="lfd-h-help" id="lfd-hero-empty"></p>
	<p class="lfd-h-help">Cliquez ensuite sur « Enregistrer » en haut de la page.</p>
	<input type="hidden" name="lfd_hero_media" id="lfd_hero_media" value="<?php echo esc_attr( wp_json_encode( $list ) ); ?>">
	<script>
	(function () {
		var input = document.getElementById('lfd_hero_media'), box = document.getElementById('lfd-hero-list'), empty = document.getElementById('lfd-hero-empty');
		var list = [];
		try { list = JSON.parse(input.value) || []; } catch (e) { list = []; }
		function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML.replace(/"/g, '&quot;'); }
		function save() { input.value = JSON.stringify(list); }
		function pick(opts, cb) {
			var frame = wp.media({ title: opts.title, button: { text: 'Utiliser ce fichier' }, library: { type: opts.type }, multiple: !!opts.multiple });
			frame.on('select', function () { cb(frame.state().get('selection').toJSON()); });
			frame.open();
		}
		function fromAttachment(a) {
			return { url: a.url, type: a.type === 'video' ? 'video' : 'image', x: 50, y: 50, name: '', sub: '', poster: '' };
		}
		function render() {
			box.innerHTML = '';
			empty.textContent = list.length ? '' : 'Aucun visuel : la page s’ouvre sur son titre, sans photo.';
			list.forEach(function (m, i) {
				var row = document.createElement('div'); row.className = 'lfd-h-row';
				var vis = m.type === 'video'
					? '<video src="' + esc(m.url) + '" muted preload="metadata"' + (m.poster ? ' poster="' + esc(m.poster) + '"' : '') + '></video>'
					: '<img src="' + esc(m.url) + '" alt="">';
				row.innerHTML =
					'<div><div class="lfd-h-vis" title="Cliquez sur le sujet principal">' + vis + '<span class="lfd-h-tag">' + (m.type === 'video' ? 'Vidéo' : 'Image') + ' ' + (i + 1) + '</span><span class="lfd-h-dot" style="left:' + m.x + '%;top:' + m.y + '%"></span></div>' +
					'<p class="lfd-h-help">Cliquez sur le sujet principal (le produit) : il restera visible sur tous les écrans.</p></div>' +
					'<div class="lfd-h-fields">' +
					'<div><label>Nom affiché (ex. Cartier)</label><input type="text" data-k="name" value="' + esc(m.name) + '"></div>' +
					'<div><label>Sous-titre affiché</label><input type="text" data-k="sub" value="' + esc(m.sub) + '"></div>' +
					'<p class="lfd-h-help">Le nom et le sous-titre apparaissent seulement quand la page a plusieurs visuels.</p>' +
					(m.type === 'video' ? '<label style="display:flex;gap:6px;align-items:center;font-weight:600"><input type="checkbox" data-k="sound"' + (m.sound ? ' checked' : '') + '> Avec le son</label>' : '') +
					'<div class="lfd-h-act">' +
					'<button type="button" class="button" data-a="replace">Remplacer</button>' +
					(m.type === 'video' ? '<button type="button" class="button" data-a="poster">' + (m.poster ? 'Changer l’image d’attente' : 'Choisir l’image d’attente') + '</button>' : '') +
					'<button type="button" class="button" data-a="up"' + (i === 0 ? ' disabled' : '') + '>Monter</button>' +
					'<button type="button" class="button" data-a="down"' + (i === list.length - 1 ? ' disabled' : '') + '>Descendre</button>' +
					'<button type="button" class="button button-link-delete" data-a="remove">Retirer</button>' +
					'</div></div>';
				row.querySelector('.lfd-h-vis').addEventListener('click', function (e) {
					var r = this.getBoundingClientRect();
					m.x = Math.round(Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)) * 100);
					m.y = Math.round(Math.max(0, Math.min(1, (e.clientY - r.top) / r.height)) * 100);
					save(); render();
				});
				Array.prototype.forEach.call(row.querySelectorAll('input[data-k]'), function (el) {
					el.addEventListener(el.type === 'checkbox' ? 'change' : 'input', function () { m[el.getAttribute('data-k')] = el.type === 'checkbox' ? el.checked : el.value; save(); });
				});
				Array.prototype.forEach.call(row.querySelectorAll('button[data-a]'), function (b) {
					b.addEventListener('click', function () {
						var a = b.getAttribute('data-a');
						if (a === 'remove') { list.splice(i, 1); }
						if (a === 'up' && i > 0) { list.splice(i - 1, 0, list.splice(i, 1)[0]); }
						if (a === 'down' && i < list.length - 1) { list.splice(i + 1, 0, list.splice(i, 1)[0]); }
						if (a === 'replace') {
							pick({ title: 'Remplacer le visuel d’ouverture', type: ['image', 'video'] }, function (sel) {
								if (!sel[0]) { return; }
								var n = fromAttachment(sel[0]); n.name = m.name; n.sub = m.sub; list[i] = n; save(); render();
							});
							return;
						}
						if (a === 'poster') {
							pick({ title: 'Image affichée pendant le chargement de la vidéo', type: ['image'] }, function (sel) {
								if (sel[0]) { m.poster = sel[0].url; save(); render(); }
							});
							return;
						}
						save(); render();
					});
				});
				box.appendChild(row);
			});
		}
		document.getElementById('lfd-hero-add').addEventListener('click', function () {
			pick({ title: 'Visuel d’ouverture : image ou vidéo', type: ['image', 'video'], multiple: true }, function (sel) {
				sel.forEach(function (a) { list.push(fromAttachment(a)); });
				save(); render();
			});
		});
		render();
	})();
	</script>
	<?php
}

add_action(
	'save_post_page',
	function ( $post_id ) {
		if ( ! isset( $_POST['lfd_hero_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lfd_hero_nonce'] ) ), 'lfd_hero' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) || ! isset( $_POST['lfd_hero_media'] ) ) {
			return;
		}
		$list = json_decode( wp_unslash( $_POST['lfd_hero_media'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- nettoyé champ par champ ci-dessous.
		$out  = array();
		foreach ( (array) $list as $m ) {
			if ( empty( $m['url'] ) ) {
				continue;
			}
			$out[] = array(
				'url'    => esc_url_raw( $m['url'] ),
				'type'   => ( isset( $m['type'] ) && 'video' === $m['type'] ) ? 'video' : 'image',
				'x'      => isset( $m['x'] ) ? max( 0, min( 100, (int) $m['x'] ) ) : 50,
				'y'      => isset( $m['y'] ) ? max( 0, min( 100, (int) $m['y'] ) ) : 50,
				'name'   => isset( $m['name'] ) ? sanitize_text_field( $m['name'] ) : '',
				'sub'    => isset( $m['sub'] ) ? sanitize_text_field( $m['sub'] ) : '',
				'poster' => isset( $m['poster'] ) ? esc_url_raw( $m['poster'] ) : '',
				'sound'  => ! empty( $m['sound'] ),
			);
		}
		// « __none » : l'utilisateur a volontairement tout retiré (la page s'ouvre alors sans visuel).
		update_post_meta( $post_id, '_lfd_hero_media', $out ? wp_slash( wp_json_encode( $out ) ) : '__none' );
	}
);


/* ------------------------------------------------------------------ ambiance sonore de la page */

add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'lfd_ambient', 'Ambiance sonore de la page', 'lfd_ambient_box', 'page', 'side', 'default' );
	}
);

function lfd_ambient_box( $post ) {
	wp_nonce_field( 'lfd_ambient', 'lfd_ambient_nonce' );
	$url = (string) get_post_meta( $post->ID, '_lfd_ambient', true );
	$vol = (string) get_post_meta( $post->ID, '_lfd_ambient_vol', true );
	$vol = '' === $vol ? '0.2' : $vol;
	echo '<p style="color:#646970">Un son d’ambiance très léger, en boucle (MP3 conseillé). Il démarre au premier geste du visiteur sur la page (les navigateurs interdisent le son automatique) et un bouton « Son » permet de le couper. Il s’efface quand une vidéo joue avec le son.</p>';
	echo '<p id="lfd-amb-name" style="word-break:break-all">' . ( $url ? esc_html( wp_basename( $url ) ) : '<em>Aucun son</em>' ) . '</p>';
	echo '<input type="hidden" name="lfd_ambient" id="lfd-amb-url" value="' . esc_attr( $url ) . '">';
	echo '<p><button type="button" class="button" id="lfd-amb-pick">' . ( $url ? 'Changer le son' : 'Choisir un son' ) . '</button> <button type="button" class="button-link button-link-delete" id="lfd-amb-del">Retirer</button></p>';
	echo '<p><label for="lfd-amb-vol"><strong>Volume</strong></label><br><select name="lfd_ambient_vol" id="lfd-amb-vol" style="width:100%">';
	foreach ( array( '0.1' => 'Très léger', '0.2' => 'Léger', '0.35' => 'Moyen', '0.55' => 'Présent' ) as $v => $l ) {
		echo '<option value="' . esc_attr( $v ) . '"' . selected( $vol, $v, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select></p>';
	?>
	<script>
	(function () {
		var url = document.getElementById('lfd-amb-url'), name = document.getElementById('lfd-amb-name'), pick = document.getElementById('lfd-amb-pick');
		pick.addEventListener('click', function () {
			var f = wp.media({ title: 'Son d’ambiance', button: { text: 'Utiliser ce son' }, library: { type: 'audio' }, multiple: false });
			f.on('select', function () { var a = f.state().get('selection').first().toJSON(); url.value = a.url; name.textContent = a.filename || a.url; pick.textContent = 'Changer le son'; });
			f.open();
		});
		document.getElementById('lfd-amb-del').addEventListener('click', function () { url.value = ''; name.innerHTML = '<em>Aucun son</em>'; pick.textContent = 'Choisir un son'; });
	})();
	</script>
	<?php
}

add_action(
	'save_post_page',
	function ( $post_id ) {
		if ( ! isset( $_POST['lfd_ambient_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['lfd_ambient_nonce'] ) ), 'lfd_ambient' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
		$url = isset( $_POST['lfd_ambient'] ) ? esc_url_raw( wp_unslash( $_POST['lfd_ambient'] ) ) : '';
		$vol = isset( $_POST['lfd_ambient_vol'] ) ? (float) $_POST['lfd_ambient_vol'] : 0.2;
		update_post_meta( $post_id, '_lfd_ambient', $url );
		update_post_meta( $post_id, '_lfd_ambient_vol', (string) max( 0.05, min( 0.8, $vol ) ) );
	}
);

/** Lecteur et bouton de l'ambiance sonore (pied de page). */
function lfd_ambient_markup( $post_id ) {
	$url = (string) get_post_meta( $post_id, '_lfd_ambient', true );
	if ( ! $url ) {
		return '';
	}
	$vol = (string) get_post_meta( $post_id, '_lfd_ambient_vol', true );
	return '<audio id="lfd-amb" src="' . esc_url( $url ) . '" loop preload="none" data-vol="' . esc_attr( '' === $vol ? '0.2' : $vol ) . '"></audio>'
		. '<button type="button" class="abtn" id="lfd-abtn" aria-pressed="false" aria-label="Activer l’ambiance sonore"><span class="bars" aria-hidden="true"><i></i><i></i><i></i><i></i></span><span class="abtn-l">Son</span></button>';
}
