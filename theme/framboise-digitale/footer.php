<?php
/**
 * Bloc de contact et pied de page.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$lfd_cta_url = lfd_opt( 'lfd_cta_url' );
if ( 0 === strpos( $lfd_cta_url, '/' ) ) {
	$lfd_cta_url = lfd_url( $lfd_cta_url );
}
?>
<?php if ( ! is_page( 'contact' ) ) : ?>
<section class="cta">
	<div class="wrap">
		<p class="eyebrow rv"><?php echo esc_html( lfd_opt( 'lfd_cta_eyebrow' ) ); ?></p>
		<h2 class="spl"><?php echo wp_kses( lfd_opt( 'lfd_cta_title' ), array( 'em' => array(), 'br' => array() ) ); ?></h2>
		<p class="rv"><?php echo esc_html( lfd_opt( 'lfd_cta_text' ) ); ?></p>
		<a class="btn fill rv" href="<?php echo esc_url( $lfd_cta_url ); ?>"><?php echo esc_html( lfd_opt( 'lfd_cta_label' ) ); ?> <?php echo lfd_arrow(); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
	</div>
</section>
<?php endif; ?>
<footer class="site">
	<div class="wrap fgrid">
		<div>
			<h2 class="fh"><?php bloginfo( 'name' ); ?></h2>
			<p><?php echo esc_html( lfd_opt( 'lfd_footer_text' ) ); ?></p>
		</div>
		<nav aria-label="Menu du pied de page">
			<h2 class="fh">Menu</h2>
			<div class="flinks"><?php lfd_menu( 'footer' ); ?></div>
		</nav>
		<nav aria-label="Services">
			<h2 class="fh">Services</h2>
			<div class="flinks"><?php lfd_menu( 'services' ); ?></div>
		</nav>
		<div>
			<h2 class="fh">Contact</h2>
			<div class="flinks">
				<a href="mailto:<?php echo esc_attr( antispambot( lfd_opt( 'lfd_email' ) ) ); ?>"><?php echo esc_html( antispambot( lfd_opt( 'lfd_email' ) ) ); ?></a>
				<span><?php echo esc_html( lfd_opt( 'lfd_address' ) ); ?></span>
				<?php if ( lfd_opt( 'lfd_instagram' ) ) : ?>
				<a href="<?php echo esc_url( lfd_opt( 'lfd_instagram' ) ); ?>" rel="noopener">Instagram</a>
				<?php endif; ?>
				<a href="<?php echo esc_url( lfd_url( 'conditions-generales-dutilisation/' ) ); ?>">CGU</a>
				<a href="<?php echo esc_url( lfd_url( 'politique-de-confidentialite/' ) ); ?>">Politique de confidentialité</a>
				<a href="<?php echo esc_url( lfd_url( 'politique-de-cookies/' ) ); ?>">Politique de cookies</a>
			</div>
		</div>
	</div>
	<div class="wrap fbase">
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
		<a href="#" id="totop">Retour en haut ↑</a>
	</div>
</footer>
<?php
if ( is_singular( 'page' ) && function_exists( 'lfd_ambient_markup' ) ) {
	echo lfd_ambient_markup( get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput
}
?>
<?php wp_footer(); ?>
</body>
</html>
