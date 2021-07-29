<?php
namespace Custom\Course;
use Custom\Debug;
use Custom\Config;
use Custom\Utilities;

/* Admin Columns
-------------------------------------------------------------- */

/*
*
* return a course's 'Fee Option' (non empty) groups
*
*/
function get_course_fee_options($course_id) {
	if ( empty($course_id) ) return;
	$data = [];
	$config = Config\get_config(); // Debug\tip($choices);
	if ( !empty($config['fee_options']) ) {
		$fields = get_fields($course_id); // Debug\tip($fields);
		if( $fields ):
			foreach( $fields as $name => $value ):
				if ( in_array($name, $config['fee_options']) ) {
					if ( count(array_filter($value)) > 0 ) {
						$data[$name] = $value;
					}
				}
			endforeach;
		endif;
	}
	return $data;
}

/*
*
* return a course's 'Fee Composition' (non empty) groups
*
*/
function get_course_fee_comp($course_id) {
	if ( empty($course_id) ) return;
	$data = [];
	$config = Config\get_config(); // Debug\tip($choices);
	if ( !empty($config['fee_comp']) ) {
		$fields = get_fields($course_id); //Debug\tip($fields);
		if( !empty($fields) ):
			foreach( $fields as $name => $value ):
				//echo "$name => $value<br/>";
				if ( in_array($name, $config['fee_comp']) ) {
					// echo "$name => $value<br/>";
					if ( !empty($value) ) {	
						$data[$name] = $value;
					}
				}
			endforeach;
		endif;
	}
	// Debug\tip($data);
	return $data;
}

/*
*
* return a course's 'Course Details' (non empty) groups
*
*/
function get_course_details($course_id) {
	if ( empty($course_id) ) return;
	$data = [];
	$config = Config\get_config(); // Debug\tip($choices);
	if ( !empty($config['course_details']) ) {
		$fields = get_fields($course_id); //Debug\tip($fields);
		if( !empty($fields) ):
			foreach( $fields as $name => $value ):
				//echo "$name => $value<br/>";
				if ( in_array($name, $config['course_details']) ) {
					// echo "$name => $value<br/>";
					if ( !empty($value) ) {	
						$data[$name] = $value;
					}
				}
			endforeach;
		endif;
	}
	// Debug\tip($data);
	return $data;
}


/* Admin Columns
-------------------------------------------------------------- */

/*
*
* 
*/

add_filter( 'manage_edit-course_columns', __NAMESPACE__ . '\\course_admin_columns_headings');

function course_admin_columns_headings($columns){
	$columns['course_details'] = 'Details';
	$columns['fee_comp'] = 'Fee Composition';
	$columns['fee_options'] = 'Fee Options';
	unset($columns['date']);
	unset($columns['categories']);
	return $columns;
}

/*
*
* 
*/

add_action( 'manage_course_posts_custom_column', __NAMESPACE__ . '\\course_admin_columns', 10, 2);

function course_admin_columns ($column, $object_id){


    switch ($column) {
 		case 'course_details':
 			$course_details = get_course_details($object_id); //Debug\tip($fee_comp);
			if ( !empty($course_details) ) {
				//Debug\tip($course_details);
				echo '<ul>';
				foreach( $course_details as $k => $v ) {
					echo '<li>';
					echo '<strong>' . Utilities\make_key_pretty($k) . '</strong>: ';
					echo (!empty($v)?$v:'-');
					echo '</li>';
				}
				echo '</ul>';
			}
		break;
 		case 'fee_comp':
 			$fee_comp = get_course_fee_comp($object_id); //Debug\tip($fee_comp);
			if ( !empty($fee_comp) ) {
				//Debug\tip($fee_comp);
				echo '<ul>';
				foreach( $fee_comp as $k => $v ) {
					echo '<li>';
					echo '<strong>' . Utilities\make_key_pretty($k) . '</strong>: ';
					echo (!empty($v)?'€'.$v:'-');
					echo '</li>';
				}
				echo '</ul>';
			}
		break;
		case 'fee_options':
			$fee_options = get_course_fee_options($object_id);
			if ( !empty($fee_options) ) {
				//Debug\tip($fee_options);
				echo '<ul>';
				foreach( $fee_options as $k => $v ) {
					echo '<li>';
					echo '<strong>' . Utilities\make_key_pretty($k) . '</strong>: ';
					if ( is_array($v) ) { 
						// Debug\tip($value);
						foreach( $v as $item ) {
							echo (!empty($item)?'€'.$item.' | ':'-');
						}
					} else { 
						echo (!empty($item)?'€'.$v:'-');
					}
					echo '</li>';
				}
				echo '</ul>';
			}
		break;
        default: break;
    }

}

