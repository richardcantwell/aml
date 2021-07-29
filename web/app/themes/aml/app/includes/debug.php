<?php
namespace Custom\Debug;
use Custom\Config;

use Custom\Student;

/*
*
*/
function tip ($str) {
	echo '<pre>';
	print_r($str);
	echo '</pre>';
}

/* Debug
-------------------------------------------------------------- */
/*
*
* return debug IPs
*
*/
function get_debug_ips () {
	$ips = [
		'176.61.5.209', // rich hollywoodrath
	];
	return $ips;
}

/* Output
-------------------------------------------------------------- */

//add_action('template_redirect', __NAMESPACE__ . '\\output_debug');

function output_debug () {
	$dips = get_debug_ips();
	if ( in_array($_SERVER['REMOTE_ADDR'], $dips) ) {
		//$config = Config\get_config();
		$course_id = 40;
		// get all values and loop
		$fields = get_fields($course_id);
		if( $fields ):
			foreach( $fields as $name => $value ):
				//echo $name . ':';
				//if ( is_array($value) ) { tip($value); } else { echo $value . '<br /><br />'; }
				//'<br /><br />';
			endforeach;
		endif;
		/*// get specific values
		$non_grant = get_field('non-grantholder', $course_id);
		if( $non_grant ) {
			echo 'Instalment 1 = ' . $non_grant['instalment_1'];
		}*/
		/*$fee_options = Course\get_course_fee_options($course_id);
		tip($fee_options);*/
		//echo Course\get_current_user_course_id(); die();
		$student_course_id = Student\c(); echo 'User course ID = ' . $student_course_id;
	}
}
