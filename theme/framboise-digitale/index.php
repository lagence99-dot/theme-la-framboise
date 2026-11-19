<?php
/**
 * Gabarit par défaut : articles, archives, recherche.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="contenu" class="lfd-content">
	<section class="thero wrap"><div class="rv-g"><h1 class="spl">
	<?php
	if ( is_singular() ) {
		single_post_title();
	} elseif ( is_search() ) {
		echo 'Recherche : ' . esc_html( get_search_query() );
	} elseif ( is_archive() ) {
		echo wp_kses_post( get_the_archive_title() );
	} else {
		bloginfo( 'name' );
	}
	?>
	</h1></div></section>
	<?php if ( is_singular() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	<?php elseif ( have_posts() ) : ?>
	<section class="pgrid wrap">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
		<a class="hcard rv" href="<?php the_permalink(); ?>">
			<?php if ( has_post_thumbnail() ) : ?>
			<div class="fr" data-fit><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>
			<b><?php the_title(); ?><i><?php echo esc_html( get_the_date() ); ?></i></b>
		</a>
		<?php endwhile; ?>
	</section>
	<div class="wrap pager"><?php the_posts_pagination( array( 'prev_text' => '←', 'next_text' => '→' ) ); ?></div>
	<?php else : ?>
	<section class="prose wrap"><p>Aucun contenu à afficher.</p></section>
	<?php endif; ?>
</main>
<?php
get_footer();
