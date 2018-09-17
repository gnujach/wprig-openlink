<?php 
/**
 * 
 */
$featured_area = esc_attr( openlink_get_fp_featured_content() );
if ( 'select-pages' === $featured_area ) {
    //Selected pages query
    $featured_content_args = apply_filters( 'openlink_front_page_selected_pages', array(
		'post_type'      => 'page',
		'post__in'       => openlink_featured_pages(),
		'posts_per_page' => openlink_how_many_selected_pages(),
		'no_found_rows'  => true,
		'orderby'        => 'post__in',
	) );
}
// Featured Content Query.
$featured_content = new WP_Query( $featured_content_args );
if ( 'nothing' !== $featured_area && $featured_content->have_posts() ) : ?>
	<div id="front-page-featured-area" class="front-page-featured-area front-page-area">
<?php echo openlink_get_featured_area_title_html(); ?>
		<div class="grid-wrapper grid-wrapper-2">
			<?php
				while ( $featured_content->have_posts() ) : $featured_content->the_post();
					if ( 'select-pages' === $featured_area ) :
						get_template_part( 'template-parts/content', 'product' );
					endif;
				endwhile;
			?>
		</div>
	</div>
<?php 
endif;
wp_reset_postdata(); // Reset post data.
