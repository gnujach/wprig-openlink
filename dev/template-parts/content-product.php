<?php
/**
 * Template part for displaying page content in page.php.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Checathlon
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php if ( is_singular() && ! is_front_page() ) : // If single. ?>

		<div class="entry-inner-singular">

			<header class="entry-header page-header text-center">
				<?php the_title( '<h1 class="entry-title title-font no-margin-bottom text-italic">', '</h1>' ); ?>
			</header><!-- .entry-header -->

			<?php openlink_post_thumbnail( $post_thumbnail = 'medium' ); ?>

			<div class="entry-inner-singular-wrapper">

				<div class="entry-inner-content">
					<div class="entry-content">
					<?php
						the_content();

						wp_link_pages( array(
							'before'      => '<div class="page-links">' . esc_html__( 'Pages:', 'checathlon' ),
							'after'       => '</div>',
							'link_before' => '<span>',
							'link_after'  => '</span>',
							'pagelink'    => '<span class="screen-reader-text">' . esc_html__( 'Page', 'checathlon' ) . ' </span>%',
							'separator'   => '<span class="screen-reader-text">,</span> ',
						) );
					?>
					</div><!-- .entry-content -->
				</div><!-- .entry-inner-content -->

				<?php get_sidebar(); ?>

			</div><!-- .entry-inner-singular-wrapper -->

	</div><!-- .entry-inner-singular -->

<?php else : ?>

		<div class="entry-inner-wrapper">

			<?php
				// Get featured image as post background image.
                // echo openlink_get_bg_header( array( 'size' => 'medium', 'icon' => 'info' ) );
                echo openlink_get_bg_header( array( ) );
			?>

		<div class="entry-inner">

			<header class="entry-header">
				<?php
					the_title( '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
				?>
			</header><!-- .entry-header -->

			<div class="entry-summary">
				<?php the_excerpt(); ?>
			</div><!-- .entry-summary -->

		</div><!-- .entry-inner -->

	</div><!-- .entry-inner-wrapper -->

	<?php endif; // End check single. ?>

</article><!-- #post-## -->
