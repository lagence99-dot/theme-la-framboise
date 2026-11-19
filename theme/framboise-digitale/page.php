<?php
/**
 * Gabarit de toutes les pages (accueil comprise).
 * Le contenu est préparé avant l'en-tête pour savoir si la page s'ouvre sur un grand visuel.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$lfd_content = '';
$lfd_id      = 0;
$lfd_raw     = '';
if ( have_posts() ) {
	the_post();
	$lfd_id      = get_the_ID();
	$lfd_raw     = get_the_content();
	$lfd_content = apply_filters( 'the_content', $lfd_raw ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
}
$lfd_has_title = false !== strpos( $lfd_raw, 'lfd-hero' ) || false !== strpos( $lfd_raw, 'lfd-prose' );
$lfd_fallback  = '';
if ( ! $lfd_has_title ) {
	// Page sans section « Ouverture » : le titre de la page sert de titre, sur le visuel choisi dans l'encadré s'il y en a un.
	$lfd_fallback = lfd_r_hero( array( array( 't' => 'h', 'l' => 1, 'html' => esc_html( get_the_title( $lfd_id ) ), 'cls' => '' ) ) );
}
get_header();
?>
<main id="contenu" class="lfd-content">
<?php echo $lfd_fallback; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<?php
echo $lfd_content; // phpcs:ignore WordPress.Security.EscapeOutput
if ( $lfd_id && 'projet' === get_post_meta( $lfd_id, '_lfd_type', true ) ) {
	echo lfd_next_project( $lfd_id ); // phpcs:ignore WordPress.Security.EscapeOutput
}
?>
</main>
<?php
get_footer();
