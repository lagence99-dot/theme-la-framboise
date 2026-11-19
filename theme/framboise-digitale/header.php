<?php
/**
 * En-tête du site.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#contenu">Aller au contenu</a>
<?php if ( is_front_page() && '1' === (string) lfd_opt( 'lfd_curtain' ) ) : ?>
<div class="curtain" aria-hidden="true"><b>La Framboise</b></div>
<?php endif; ?>
<header class="site" id="hdr">
	<div class="in">
		<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?> : accueil">La Framboise<small>Digitale</small></a>
		<button type="button" class="burger" id="burger" aria-expanded="false" aria-controls="nav" aria-label="Ouvrir le menu"><span></span><span></span></button>
		<nav class="main" id="nav" aria-label="Menu principal">
			<?php lfd_menu( 'primary' ); ?>
		</nav>
	</div>
</header>
