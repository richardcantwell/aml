<?php
namespace Custom\Student;
use Custom\Debug;
use Custom\Config;
use Custom\Utilities;
use Custom\Course;


/* Admin Columns
-------------------------------------------------------------- */

/*
*
*
*/

add_filter( 'manage_users_columns', __NAMESPACE__ . '\\new_modify_user_table' );
function new_modify_user_table( $column ) {
    $column['course_details'] = 'Course Details';
    return $column;
}

/*
*
*
*/

add_filter( 'manage_users_custom_column', __NAMESPACE__ . '\\new_modify_user_table_row', 10, 3 );
function new_modify_user_table_row( $val, $column_name, $user_id ) {
    switch ($column_name) {
        case 'course_details' :
        	// $course = get_field($user_id, 'course');
        	$student_details = get_student_details($user_id); //Debug\tip($student_details);
			if ( !empty($student_details) ) {
				$o = '';
				$o .= '<ul>';
				foreach( $student_details as $k => $v ) {
					$o .= '<li>';
					$o .= '<strong>' . Utilities\make_key_pretty($k) . '</strong>: ';
					if ( $k == 'course' ) {
						if (!empty($v)) {
							$cobj = get_page_by_title($v, OBJECT, 'course'); // Debug\tip($cobj);
							if (!empty($cobj)) {
								$id = $cobj->ID;
								$code = $cobj->post_title;
								$url = admin_url("post.php?post=$id&action=edit");
								$title = get_post_meta( $id, 'title', true );
								$o .= $code.' | <a href="'.$url.'" target="_blank">'.$title.'</a>';
							}
						}
					} else {
						$o .= (!empty($v)?$v:'-');
					}
					$o .= '</li>';
				}
				$o .= '</ul>';
				return $o;
			}
        default: return $val;
    }
    return $val;
}

/* Helpers
-------------------------------------------------------------- */

function output_payment_form_column_right () {
	if ( !is_user_logged_in() ) return;
	global $current_user;
	$fee_doc_url = 'https://pay.maynoothcollege.ie/wp-content/uploads/2021/07/Fees-Listing-2021-2022-ID-22651.pdf';
	$o = '';
	if ( in_array( 'student', (array) $current_user->roles ) ) {
		$o .= '<p>Record for student <a href="mailto:' . $current_user->user_email . '">' . $current_user->first_name  . ' ' . $current_user->last_name . '</a> ...</p>';
		$student_details = get_student_details($current_user->ID);
		if ( !empty($student_details['course']) ) {
			$show_details = false;
			if ( !empty($student_details) ) {
				$d = '<ul>';
				foreach( $student_details as $k => $v ) {
					$d .= '<li>';
					$d .= '<strong>' . Utilities\make_key_pretty($k) . '</strong>: ';
					if ( $k == 'course' ) {
						if (!empty($v)) {
							$show_details = true;
							$cobj = get_page_by_title($v, OBJECT, 'course'); // Debug\tip($cobj);
							if ( !empty($cobj) ) {
								$id = $cobj->ID;
								$code = $cobj->post_title; $d .= $code;
								$course_details = Course\get_course_details($cobj->ID); // $d .= print_r($course_details, 1);
								$d .=  ( !empty($course_details['title']) ? ' | ' . $course_details['title'] : '' );
								$d .=  ( !empty($course_details['level']) ? ' | ' . $course_details['level'] : '' );
							}
						}
					} else {
						$d .= (!empty($v)?$v:'-');
					}
					$d .= '</li>';
				} // foreach( $student_details as $k => $v )
				$d .= '</ul>';
			} // !empty($student_details)
			if ( $show_details ) {
				$o .= $d;
				$o .= '<div class="info">';
				$o .= '<p>For more information on fees click <a href="'.$fee_doc_url.'">here</a>.</p>';
				$o .= '</div> <!-- info -->';
			} else {
				$o .= '<div class="info">';
				$o .= '<div class="alert-info">';
				$o .= '<p>Having trouble accessing your account?</p>';
				$o .= '<p>Phone the fees office on <a href="tel:+35317084751">(+353 1) 708 4751</a>.</p>';
				$o .= '<p>or email <a href="mailto:accounts@spcm.ie">accounts@spcm.ie</a>.</p>';
				$o .= '</div> <!-- alert -->';
				$o .= '</div> <!-- info -->';
			}
		} else {
			$o .= '<div class="info">';
			$o .= '<div class="alert-warning">';
			$o .= '<p>You do not seem to be associated with any courses.</p>';
			$o .= '</div> <!-- alert -->';
			$o .= '<p>Having trouble accessing your account?</p>';
			$o .= '<p>Phone the fees office on <a href="tel:+35317084751">(+353 1) 708 4751</a>.</p>';
			$o .= '<p>or email <a href="mailto:accounts@spcm.ie">accounts@spcm.ie</a>.</p>';
			$o .= '</div> <!-- info -->';
		} // !empty($student_details['course'])
	} else {
		$o .= '<div class="alert alert-warning">You are not registered as a student</div>';
	}
	return $o;
}

/* Helpers
-------------------------------------------------------------- */

/*
*
* return a student's 'Student Details' (non empty) groups
*
*/

function get_student_details($user_id) {
	if ( empty($user_id) ) return;
	$data = [];
	$config = Config\get_config(); // Debug\tip($config);
	if ( !empty($config['student_details']) ) {
		$fields = get_fields("user_$user_id"); // Debug\tip($fields);
		if( !empty($fields) ):
			foreach( $fields as $name => $v ):
				//echo "$name => $value<br/>";
				if ( in_array($name, $config['student_details']) ) {
					// echo "$name => $value<br/>";
					if ( !empty($v) ) {	
						$data[$name] = $v;
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
* return the current users course ID (if any)
*
*/

function get_current_user_course_id() {
	if ( !is_user_logged_in() ) return;
	global $current_user;
	$code = get_field('course', 'user_' . $current_user->ID);
	if ( !empty($code) ) {
		$cobj = get_page_by_title($code, OBJECT, 'course'); // Debug\tip($cobj);
		if (!empty($cobj)) {
			return $cobj->ID;
		}
	}
	return false;
}

/* XXX
-------------------------------------------------------------- */

/* XXX
-------------------------------------------------------------- */

/* XXX
-------------------------------------------------------------- */

/* XXX
-------------------------------------------------------------- */

/* XXX
-------------------------------------------------------------- */
