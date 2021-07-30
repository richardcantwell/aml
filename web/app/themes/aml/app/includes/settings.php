<?php
namespace Custom\Settings;
/*
*
* initiate ACF options page
*
* @docs
*
* - https://www.advancedcustomfields.com/resources/options-page/
*
*/
add_action( 'after_setup_theme',  __NAMESPACE__ . '\\acf_options_load' );
function acf_options_load() {
    if ( function_exists('acf_add_options_page') ) {
        acf_add_options_page(array(
            'page_title'    => 'General Settings',
            'menu_title'    => 'Settings',
            'menu_slug'     => 'aml-general-settings',
            'capability'    => 'edit_posts',
            'redirect'      => false
        ));
        acf_add_options_sub_page(array(
            'page_title'    => 'ID Pal Settings',
            'menu_title'    => 'ID Pal',
            'menu_slug'     => 'aml-idpal-settings',
            'parent_slug'   => 'aml-general-settings',
        ));
    }
}
