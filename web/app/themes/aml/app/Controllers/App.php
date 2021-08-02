<?php

namespace App\Controllers;

use Sober\Controller\Controller;
use Custom\User\IdPal;

class App extends Controller
{

    /*
    *
    * Exposes $data (and optional hacky $view global data to ALL templates )
    *
    *
    */

    public function data () {
        global $post, $view;
        $settings = [];
        $data = [];
        $data = [
            'site' => [
                'name' => get_bloginfo('name'),
                'url' => get_bloginfo('url'),
                'email' => get_option('admin_email'),
            ],
            'theme' => [
                'logo' => [
                    'default'=>\App\asset_path('images/logo-idpal-90.jpg'),
                ],
                'settings' => $settings,
            ],
            'plugins' => [
            ],
            'template' => [
            ],
            'user' => [
                'id' => null,
                'core' => [],
                'obj' => null,
                'roles' => [],
                'meta' => [],
                'history' => [],
            ],
            'stats' => IdPal\user_idpal_stats(),
        ];
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $data['user'] = [
                'id' => $current_user->ID,
                'core' => [
                        'user_login' => $current_user->user_login,
                        'user_email' => $current_user->user_email,
                        'user_firstname' => $current_user->user_firstname,
                        'user_lastname' => $current_user->user_lastname,
                        'display_name' => $current_user->display_name,
                    ],
                'obj' => $current_user,
            ];
            $data['user']['roles'] = (array) $current_user->roles;
        }
        if ( is_home() ) {
        } elseif ( is_page() ) {
        } elseif ( is_404() ) {
        } elseif ( is_single() ) {
            if ( $post->post_type == 'post' ) {
            }
        } elseif ( is_archive() ) {
            if ( is_category() ) {
            } elseif ( is_tax() ) {}
            if ( is_post_type_archive('course') ) {}
        } elseif ( is_search() ) {
        }
        $view = $data; // hack to get $data variable into $view
        return $data; // available to all tempaltes now
    }

    public function siteName()
    {
        return get_bloginfo('name');
    }

    public static function title()
    {
        if (is_home()) {
            if ($home = get_option('page_for_posts', true)) {
                return get_the_title($home);
            }
            return __('Latest Posts', 'sage');
        }
        if (is_archive()) {
            return get_the_archive_title();
        }
        if (is_search()) {
            return sprintf(__('Search Results for %s', 'sage'), get_search_query());
        }
        if (is_404()) {
            return __('Not Found', 'sage');
        }
        return get_the_title();
    }
}
