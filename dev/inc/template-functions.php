<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package wprig
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function wprig_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	if ( is_active_sidebar( 'sidebar-1' ) ) {
		global $template;
		if ( 'front-page.php' !== basename( $template ) ) {
			$classes[] = 'has-sidebar';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'wprig_body_classes' );

/**
 * Add a pingback url auto-discovery header for singularly identifiable articles.
 */
function wprig_pingback_header() {
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
	}
}
add_action( 'wp_head', 'wprig_pingback_header' );

/**
 * Adds async/defer attributes to enqueued / registered scripts.
 *
 * If #12009 lands in WordPress, this function can no-op since it would be handled in core.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return array
 */
function wprig_filter_script_loader_tag( $tag, $handle ) {

	foreach ( array( 'async', 'defer' ) as $attr ) {
		if ( ! wp_scripts()->get_data( $handle, $attr ) ) {
			continue;
		}

		// Prevent adding attribute when already added in #12009.
		if ( ! preg_match( ":\s$attr(=|>|\s):", $tag ) ) {
			$tag = preg_replace( ':(?=></script>):', " $attr", $tag, 1 );
		}

		// Only allow async or defer, not both.
		break;
	}

	return $tag;
}

add_filter( 'script_loader_tag', 'wprig_filter_script_loader_tag', 10, 2 );

/**
 * Generate preload markup for stylesheets.
 *
 * @param object $wp_styles Registered styles.
 * @param string $handle The style handle.
 */
function wprig_get_preload_stylesheet_uri( $wp_styles, $handle ) {
	$preload_uri = $wp_styles->registered[ $handle ]->src . '?ver=' . $wp_styles->registered[ $handle ]->ver;
	return $preload_uri;
}

/**
 * Adds preload for in-body stylesheets depending on what templates are being used.
 * Disabled when AMP is active as AMP injects the stylesheets inline.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
 */
function wprig_add_body_style() {

	// If AMP is active, do nothing.
	if ( wprig_is_amp() ) {
		return;
	}

	// Get registered styles.
	$wp_styles = wp_styles();

	$preloads = array();

	// Preload content.css.
	$preloads['wprig-content'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-content' );

	// Preload sidebar.css and widget.css.
	if ( is_active_sidebar( 'sidebar-1' ) ) {
		$preloads['wprig-sidebar'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-sidebar' );
		$preloads['wprig-widgets'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-widgets' );
	}

	// Preload comments.css.
	if ( ! post_password_required() && is_singular() && ( comments_open() || get_comments_number() ) ) {
		$preloads['wprig-comments'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-comments' );
	}

	// Preload front-page.css.
	global $template;
	if ( 'front-page.php' === basename( $template ) ) {
		$preloads['wprig-front-page'] = wprig_get_preload_stylesheet_uri( $wp_styles, 'wprig-front-page' );
	}

	// Output the preload markup in <head>.
	foreach ( $preloads as $handle => $src ) {
		echo '<link rel="preload" id="' . esc_attr( $handle ) . '-preload" href="' . esc_url( $src ) . '" as="style" />';
		echo "\n";
	}

}
add_action( 'wp_head', 'wprig_add_body_style' );

/**
 * Add dropdown symbol to nav menu items with children.
 *
 * Adds the dropdown markup after the menu link element,
 * before the submenu.
 *
 * Javascript converts the symbol to a toggle button.
 *
 * @TODO:
 * - This doesn't work for the page menu because it
 *   doesn't have a similar filter. So the dropdown symbol
 *   is only being added for page menus if JS is enabled.
 *   Create a ticket to add to core?
 *
 * @param string   $item_output The menu item's starting HTML output.
 * @param WP_Post  $item        Menu item data object.
 * @param int      $depth       Depth of menu item. Used for padding.
 * @param stdClass $args        An object of wp_nav_menu() arguments.
 * @return string Modified nav menu HTML.
 */
function wprig_add_primary_menu_dropdown_symbol( $item_output, $item, $depth, $args ) {

	// Only for our primary menu location.
	if ( empty( $args->theme_location ) || 'primary' != $args->theme_location ) {
		return $item_output;
	}

	// Add the dropdown for items that have children.
	if ( ! empty( $item->classes ) && in_array( 'menu-item-has-children', $item->classes ) ) {
		return $item_output . '<span class="dropdown"><i class="dropdown-symbol"></i></span>';
	}

	return $item_output;
}
add_filter( 'walker_nav_menu_start_el', 'wprig_add_primary_menu_dropdown_symbol', 10, 4 );

/**
 * Filters the HTML attributes applied to a menu item's anchor element.
 *
 * Checks if the menu item is the current menu
 * item and adds the aria "current" attribute.
 *
 * @param array   $atts   The HTML attributes applied to the menu item's `<a>` element.
 * @param WP_Post $item  The current menu item.
 * @return array Modified HTML attributes
 */
function wprig_add_nav_menu_aria_current( $atts, $item ) {
	/*
	 * First, check if "current" is set,
	 * which means the item is a nav menu item.
	 *
	 * Otherwise, it's a post item so check
	 * if the item is the current post.
	 */
	if ( isset( $item->current ) ) {
		if ( $item->current ) {
			$atts['aria-current'] = 'page';
		}
	} else if ( ! empty( $item->ID ) ) {
		global $post;
		if ( ! empty( $post->ID ) && $post->ID == $item->ID ) {
			$atts['aria-current'] = 'page';
		}
	}

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'wprig_add_nav_menu_aria_current', 10, 2 );
add_filter( 'page_menu_link_attributes', 'wprig_add_nav_menu_aria_current', 10, 2 );

/**
 * Returns featured area title.
 *
 * @since  1.0.0
 *
 * @return string
 */
function openlink_get_featured_area_title() {
	return openlink_get_theme_mod( 'featured_area_title', esc_html__( 'Products', 'checathlon' ) );
}

/**
 * This is a wrapper function for core WP's `get_theme_mod()` function. Core doesn't
 * provide a filter hook for the default value (useful for child themes). The purpose
 * of this function is to provide that additional filter hook. To filter the final
 * theme mod, use the core `theme_mod_{$name}` filter hook.
 *
 * @since  1.0.0
 *
 * @author    Justin Tadlock <justin@justintadlock.com>
 * @copyright Copyright (c) 2013 - 2016, Justin Tadlock
 * @link      http://themehybrid.com/themes/extant
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * @param     string $name    Theme mod ID.
 * @param     mixed  $default Theme mod default.
 * @return    mixed
 */
function openlink_get_theme_mod( $name, $default = false ) {
	return get_theme_mod( $name, apply_filters( "checathlon_theme_mod_{$name}_default", $default ) );
}
/**
 * Returns the default featured content theme mod in Front Page.
 *
 * @since  1.0.0
 *
 * @return string
 */
function openlink_get_fp_featured_content() {
	// wp_die
	return openlink_get_theme_mod( 'front_page_featured', 'select-pages' );
}
/**
 * Returns featured pages selected from the Customizer.
 *
 * @since  1.0.0
 *
 * @return array
 */
function openlink_featured_pages() {

	$k = 1;

	// Set empty array of featured pages.
	$openlink_featured_pages = array();

	// How many pages to show.
	$how_many_pages = openlink_how_many_selected_pages();

	// Loop all the featured pages.
	while ( $k <= absint ( $how_many_pages ) ) { // Begins the loop through found pages from customize settings.

		$openlink_page_id = absint( get_theme_mod( 'featured_page_' . $k ) );

			// Add selected featured pages in array.
			if ( 0 !== $openlink_page_id || ! empty( $openlink_page_id ) ) { // Check if page is selected.
				$openlink_featured_pages[] = $openlink_page_id;
			}

		$k++;

	}

	// Return featured pages.
	return $openlink_featured_pages;
}
/**
 * Returns the Front Page selected pages count.
 *
 * @since  1.0.0
 *
 * @return integer
 */
function openlink_how_many_selected_pages() {
	return apply_filters( 'openlink_how_many_selected_pages', 8 );
	// return 8;
}
/**
 * Returns featured area title html.
 *
 * @since  1.0.0
 *
 * @return string
 */
function openlink_get_featured_area_title_html() {
	if ( openlink_get_featured_area_title() ) {
		return '<h2 class="front-page-featured-title front-page-title widget-title">' . esc_html( openlink_get_featured_area_title() ) . '</h2>';
	}
}
/**
 * Check if we're on Front Page template.
 *
 * @since  1.0.0
 *
 * @return boolean.
 */
function openlink_is_front_page_template() {
	// wp_die ( "errors" );
	// wp_die( is_page_template( 'templates/featured-page.php' ) || openlink_is_front_page() );
	// return is_page_template( 'templates/featured-page.php' ) || openlink_is_front_page();
	return is_front_page();
}
/**
 * Check if we are on front page.
 *
 * @return bool
 */
function openlink_is_front_page() {
	wp_die("error");
	return ( ! is_home() && is_front_page() );
}
/**
 * Get post thubmnail as background image.
 *
 * Uses openlink_post_background function.
 */
function openlink_get_bg_header( $args = array() ) {
	$defaults = array(
		'post_id' => get_the_ID(),
		'size'    => 'medium',
		'icon'    => 'pencil',
	);
	$args = wp_parse_args( $args, $defaults );

	// Get featured image as post background image.
	$openlink_bg = openlink_post_background( $args['size'] );

	// Start markup.
	$markup = '';

	if ( has_post_thumbnail( $args['post_id'] ) ) :
		$markup .= '<div class="entry-header-bg" style="background-image:url(' . esc_url( $openlink_bg ) . ');">';
			$markup .= '<a class="entry-header-bg-link" href="' . esc_url( get_permalink() ) . '" rel="bookmark"><span class="screen-reader-text">' . esc_html__( 'Continue reading', 'wprig' ) . ' ' . get_the_title() . '</span></a>';
	else :
		$markup .= '<div class="entry-header-bg">';
		$markup .= '<a class="entry-header-bg-link" href="' . esc_url( get_permalink() ) . '" rel="bookmark">' . '&nbps;' . '<span class="screen-reader-text">' . esc_html__( 'Continue reading', 'checathlon' ) . ' ' . get_the_title() . '</span></a>';
	endif;

	$markup .= '</div>';

	return $markup;
}
/**
 * Display an optional post background.
 *
 * @since 1.0.0
 */
function openlink_post_background( $post_thumbnail = null, $id = null ) {

	// Set default size.
	if ( null === $post_thumbnail ) {
		$post_thumbnail = 'post-thumbnail';
	}

	// Set default ID.
	if ( null === $id ) {
		$id = get_the_ID();
	}

	// Return post thumbnail url if it's set, else return false.
	if ( has_post_thumbnail( $id ) ) {
		$thumb_url_array = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), esc_attr( $post_thumbnail ), true );
		$bg              = $thumb_url_array[0];
	} else {
		$bg = false;
	}

	return $bg;

}