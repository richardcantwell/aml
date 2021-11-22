<?php
namespace Custom\Config;

use Custom\Debug;
use Custom\Package\IdPal;

/*
*
*
*
*
*/


function get_config() {
	$data = [
		'admin_email' => get_option( 'admin_email' ),
		'site_title' => get_bloginfo( 'name' ),
		'site_url' => get_bloginfo( 'url' ),
		'url_base_idpal' => get_field('base_url', 'option'),
		'manager' => get_field('manager', 'option'), // ID Pal Companion 'manager'
		'status_codes' => IdPal\get_status_codes(), // get status code meanings
		'step_meanings' => IdPal\get_step_meanings(), // get step meanings // Handy\I_Handy::tip($step_meanings);
		'debug_ips' => Debug\get_debug_ips(),
	];

	//$debug_ips = Debug\get_debug_ips();
	return $data;
}



/*
*
*
*
*
*/

/*
*
*
*
*
*/

/*
*
*
*
*
*/

