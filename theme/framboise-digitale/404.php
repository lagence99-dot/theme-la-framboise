<?php
/**
 * Page introuvable.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="contenu" class="lfd-content">
	<section class="thero wrap"><div class="rv-g">
		<p class="eyebrow">Erreur 404</p>
		<h1 class="spl">Cette page est <em>introuvable</em></h1>
		<p class="hero-sub">L’adresse a peut-être changé. Vous pouvez revenir à l’accueil ou découvrir nos projets.</p>
		<div class="row" style="margin-top:28px">
			<a class="btn fill" href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l’accueil</a>
			<a class="btn" href="<?php echo esc_url( lfd_url( 'portfolio-agence-digitale-luxe/' ) ); ?>">Nos projets</a>
		</div>
	</div></section>
</main>
<?php
get_footer();
