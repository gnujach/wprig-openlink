<?php
/** Theme customizer for Front Page Area
 * @package wprig-openlink
 * 
 */

 //Add the front-page-featured section
$wp_customize->add_section(
     'front-page-featured',
     array(
         'title'            =>  esc_html__( 'Front Page Featured Area', 'wprig'),
         'description'      =>  esc_html__( 'In this section you can modify Front Page Featured Area', 'wprig'),
         'priority'         =>  120,
        //  'panel'            =>  'theme',
         'active_callback'  =>  'openlink_is_front_page_template',
     )
);

// Featured area title content
$wp_customize->add_setting(
    'featured_area_title',
    array(
        'default'               => esc_html__('Featured Title', 'wprig'),
        'sanitize_callback'     => 'sanitize_text_field'
    )
);

//Featured area title control
$wp_customize->add_control(
    'featured_area_title',
    array(
        'label'             =>  esc_html__('Featured area title', 'wprig'),
        'section'           =>  'front-page-featured',
        'priority'          =>  10,
        'type'              =>  'text',
        'active_callback'   =>  'openlink_show_featured_title'
    )
);
// Add the featured setting where we can select do we use child pages, blog posts or portfolios in front page template.
$wp_customize->add_setting(
    'front_page_featured',
    array(
        'default'           => openlink_get_fp_featured_content(),
        'sanitize_callback' => 'openlink_sanitize_featured'
    )
);
$front_page_featured_choices = apply_filters( 'openlink_front_page_featured', array(
    'nothing'           => esc_html__( 'Nothing', 'wprig' ),
    'jetpack-portfolio' => esc_html__( 'Portfolios', 'wprig' ),
    'portfolio-project' => esc_html__( 'Portfolio Projects', 'wprig' ),
    'download'          => esc_html__( 'Downloads', 'wprig' ),
    'select-pages'      => esc_html__( 'Select Pages', 'wprig' ),
) );

// Unset unused post types.
if( ! post_type_exists( 'jetpack-portfolio' ) ) {
    unset( $front_page_featured_choices['jetpack-portfolio'] );
}
if( ! post_type_exists( 'portfolio_project' ) ) {
    unset( $front_page_featured_choices['portfolio-project'] );
}
if( ! post_type_exists( 'download' ) ) {
    unset( $front_page_featured_choices['download'] );
}
// Add the featured control.
$wp_customize->add_control(
    'front_page_featured',
    array(
        'label'       => esc_html__( 'Featured Content', 'wprig' ),
        'description' => esc_html__( 'Select what you want to show on Front Page featured area.', 'wprig' ),
        'section'     => 'front-page-featured',
        'priority'    => 15,
        'type'        => 'radio',
        'choices'     => $front_page_featured_choices
    )
);
// Loop same setting couple of times.
$k = 1;

// How many pages to show.
$how_many_pages = openlink_how_many_selected_pages();

while( $k <= absint( $how_many_pages ) ) {

    // Add the 'featured_page_*' setting.
    $wp_customize->add_setting(
        'featured_page_' . $k,
        array(
            'default'           => 0,
            'sanitize_callback' => 'absint'
        )
    );
    // Add the 'featured_page_*' control.
		$wp_customize->add_control(
			'featured_page_' . $k,
				array(
					/* Translators: %s stands for number. For example Select page 1. */
					'label'           => sprintf( esc_html__( 'Select page %s', 'wprig' ), $k ),
					'section'         => 'front-page-featured',
					'type'            => 'dropdown-pages',
					'allow_addition'  => true,
					'priority'        => $k+20,
					'active_callback' => 'openlink_show_select_pages',
				)
			);

		$k++; // Add one before loop ends.

	} // End while loop.
/**
 * checks when to show featured area title
 * @since 1.0.0
 * @param WP_Customize_Control
 * @return boolean
 */
function openlink_show_featured_title( $control ) {
    return ( 'nothing' != $control->manager->get_setting( 'front_page_featured' )->value() );
}
/**
 * Checks when to show select pages options.
 *
 * @since  1.0.0
 *
 * @param  WP_Customize_Control
 * @return boolean
 */
function openlink_show_select_pages( $control ) {

	return ( 'select-pages' == $control->manager->get_setting( 'front_page_featured' )->value() );

}
