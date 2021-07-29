<?php
namespace Custom\Block\Clients;
use Custom\Debug;
use Custom\Config;
use Custom\Utilities;
/*
*
*
*
*/
add_action('acf/init', __NAMESPACE__ . '\\init_block_types');
function init_block_types() {
    if( function_exists('acf_register_block_type') ) {
        acf_register_block_type(array(
            'name'              => 'clients',
            'title'             => __('Clients'),
            'description'       => __('Outputs all clients.'),
            'render_template'   => \App\template_path(locate_template("views/blocks/clients.blade.php")),
            'category'          => 'formatting',
            'icon'              => 'admin-comments',
            'keywords'          => array( 'clients' ),
        ));
    }
}

/*
*
* include the form script
*
*/
//add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\wpdocs_theme_name_scripts' );
function wpdocs_theme_name_scripts() {
    // wp_enqueue_style( 'form', get_stylesheet_uri() );
    wp_enqueue_script( 'form', get_template_directory_uri() . '/assets/scripts/form/form-1.7.3.js', array(), '1.7.3', true );
}
/*
*
* add css class 'form' to body class if a form block is present on this page
*
*/

//add_filter( 'body_class', __NAMESPACE__ . '\\add_form_to_body_classes' );
function add_form_to_body_classes( $classes ) {
    if ( is_single() ) {
        global $post;
        if ( !empty($post) ) {
            $has_form = Handy\I_Handy::is_gutenberg_block_being_used($post, 'acf/form');
            if ( !empty($has_form) ) {
                $classes[] = 'payment-form';
            }   
        }
    }
    return $classes;
}