<?php
namespace Custom\Form;
use Custom\Debug;
use Custom\Config;
use Custom\Classes\Handy;
use Custom\Utilities;

use App;

use Custom\Course;
use Custom\Student;

/*
*
* form
*
*/


/* XXX
-------------------------------------------------------------- */

add_action( 'init', __NAMESPACE__ . '\\initate_form_hooks', 11);
function initate_form_hooks () {
	$fid = 1;
	$chained_fee_options_field = 5;

	// add read only functionality
	add_filter( "gform_pre_render_{$fid}", __NAMESPACE__ . '\\apply_readonly' );

	// populate courses
	add_filter( "gform_pre_render_{$fid}", __NAMESPACE__ . '\\populate_courses' );
	add_filter( "gform_pre_validation_{$fid}", __NAMESPACE__ . '\\populate_courses' );
	add_filter( "gform_pre_submission_filter_{$fid}", __NAMESPACE__ . '\\populate_courses' );
	add_filter( "gform_admin_pre_render_{$fid}", __NAMESPACE__ . '\\populate_courses' );

	// populate course fee options
	add_filter( "gform_pre_render_{$fid}", __NAMESPACE__ . '\\populate_course_fee_options' );
	add_filter( "gform_pre_validation_{$fid}", __NAMESPACE__ . '\\populate_course_fee_options' );
	add_filter( "gform_pre_submission_filter_{$fid}", __NAMESPACE__ . '\\populate_course_fee_options' );
	add_filter( "gform_admin_pre_render_{$fid}", __NAMESPACE__ . '\\populate_course_fee_options' );

	// course fee options (chained select field) (** have to hard code the field ID **)
	// add_filter( "gform_chained_selects_input_choices_{$fid}_{$chained_fee_options_field}_1", __NAMESPACE__ . '\\populate_course_fee_options', 10, 7 ); // fee category
	// add_filter( "gform_chained_selects_input_choices_{$fid}_{$chained_fee_options_field}_2", __NAMESPACE__ . '\\populate_course_fee_installment_options', 10, 7 ); // installment amount
	//// add_filter( "gform_chained_selects_input_choices_{$fid}_{$chained_fee_options_field}_3",  __NAMESPACE__ . '\\populate_something_else', 10, 7 );

	// misc
	add_filter("gform_pre_render_{$fid}", __NAMESPACE__ . '\\listen_to_fee_options_dropdown'); // listen to 'client' field selection then pre-populate fees HTML field

    //add_filter( "gform_notification_{$fid}", __NAMESPACE__ . '\\your_function_name', 10, 3 ); 
    add_action( "gform_after_submission_{$fid}", __NAMESPACE__ . '\\handle_form_submission', 10, 2 );
    add_action( "gform_confirmation_{$fid}", __NAMESPACE__ . '\\handle_confirmation', 10, 4 );
}
/*
*
* populate the course dropdown
*/
function populate_courses ( $form ) {
	foreach ( $form['fields'] as &$field ) {
		// look for 'populate-products' css class and 'select' field
		if(strpos($field['cssClass'], 'populate-courses') === false) continue;
		if ( !is_user_logged_in() ) continue;
		$user_course_id = Student\get_current_user_course_id();
		if ( empty($user_course_id ) ) continue;
		$courses = get_posts(['post_type' => 'course','orderby' => 'title','order' => 'ASC','posts_per_page' => -1]);
		$choices = [];
		if ( !empty($courses) ) {
			foreach( $courses as $course ) {
				$title = get_field( 'title', $course->ID );
				$params = [
					'text' => $course->post_title . ' / ' . $title, 
					'value' => $course->ID,
				];
				if ( $course->ID == $user_course_id ) $params['isSelected'] = true;
				$choices[] = $params;
			}
		}
        // update 'Select a Post' to whatever you'd like the instructive option to be
        $field->placeholder = 'Select Course';
        $field->choices = $choices;
	}
	return $form;
}
/*
*
* populate the fee options dropdown
*/
function populate_course_fee_options ( $form ) {
	foreach ( $form['fields'] as &$field ) {
		// look for 'populate-products' css class and 'select' field
		if(strpos($field['cssClass'], 'course-fee-options') === false) continue;
		if ( !is_user_logged_in() ) continue;
		$user_course_id = Student\get_current_user_course_id();
		if ( empty($user_course_id ) ) continue;
		$fee_options = Course\get_course_fee_options($user_course_id); if ( !$fee_options ) continue; // Debug\tip($fee_options);
		$keys = array_keys($fee_options); if ( !$keys ) return $input_choices;
		$choices = [];
		if ( !empty($keys) ) {
			// $choices[] = [ 'text' => 'Select Option',  'value' => '', ];
			foreach( $keys as $key ) {
				$params = [
					'text' => Utilities\make_key_pretty($key), 
					'value' => $key,
				];
				$choices[] = $params;
			}
		}
        // update 'Select a Post' to whatever you'd like the instructive option to be
        $field->placeholder = 'Select Option';
        $field->choices = $choices;
	}
	return $form;
}
/*
*
* monitor the client selection dropdown and populate XX field upon user selecting a client
*
* need to add css class 'fieldClientExisting' to existing client field & 'fieldClientServices' css class to HTML output field
*
* see snippet at https://legacy.forums.gravityhelp.com/topic/dynamically-populate-fields-based-on-drop-down-selection
* see ajax example at https://wptheming.com/2013/07/simple-ajax-example/
* see User\Gforms\renderClientServices() for output
*
*/
function listen_to_fee_options_dropdown ($form) {
	$outputJqueryListener = false; // determines whether the form is shown or not
	$feeOptionsField = null;
	$instalmentInfoField = null;
	$user_course_id = Student\get_current_user_course_id();
	// exit if there aren't 2 key classes in this form
	// search for key elemement IDs
	$search_fields = [
		'fieldCourseOptions' => 'fieldCourseOptions',
		'fieldInstalmentInfo' => 'fieldInstalmentInfo',
	];
	$matches = getGfValuesBy($search_fields, $form, 'css'); //Handy\I_Handy::tip($matches); die();
	// if we have a client field - construct the input - ie '#input_[FORMID]_[FIELDID]' and output the jQuery listener
	if ( !empty($matches['fieldCourseOptions']) && !empty($matches['fieldInstalmentInfo']) ) {
		$feeOptionsField = '#input_'.$form['id'].'_'.$matches['fieldCourseOptions'];
		$instalmentInfoField = '#field_'.$form['id'].'_'.$matches['fieldInstalmentInfo']; // this is a HTML field
		$outputJqueryListener = true;
	}
	?>
	<? if ( !empty($outputJqueryListener) ) { ?>
		<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery('<?php echo $feeOptionsField; ?>').bind('change', function() {
					var option = jQuery(this).val();
					// console.log('Client field ID (<?php echo $feeOptionsField; ?>) changed | value selected is "' + clientId + '" | HTML output field ID is <?php echo $instalmentInfoField; ?>' );
					jQuery('<?php echo $instalmentInfoField; ?>').html('<div class="instalment_info mx-auto"><p class="loading"><img src="<?php echo App\asset_path('images/loader.gif'); ?>" alt="Loading" style="width:50px;display:inline-block;margin:0 auto;text-align:center;"/></p></div>');
					jQuery.ajax({
						type: 'POST',
						url: '<?php echo admin_url( 'admin-ajax.php'); ?>',
						data: {
							'action': 'render_instalment_options',
							'option' : option,
							'user_course_id' : <?php echo ( !empty($user_course_id) ? $user_course_id : 'null' ); ?>,
						},
						success:function(response) {
							// console.log(response);
							jQuery('<?php echo $instalmentInfoField; ?>').html( response );
						},
						error: function(errorThrown){
							console.log(errorThrown);
						}
					});
				});
			});
		</script>
	<? } ?>
	<?php
	return $form;
}
/*
*
* AJAX populate user services
*
* @called
*
* - blocks/profile/onboard.php -  - called from monitor_client_dropdown()
*
* - https://docs.gravityforms.com/category/add-ons-gravity-forms/chained-selects/?_ga=2.168824993.1630690546.1524669147-269875677.1492530510
* - https://docs.gravityforms.com/gform_chained_selects_input_choices/
*
*/
add_action( 'wp_ajax_render_instalment_options', __NAMESPACE__ . '\\render_instalment_options' );
add_action( 'wp_ajax_nopriv_render_instalment_options', __NAMESPACE__ . '\\render_instalment_options' );
function render_instalment_options () {
	$output = ''; // $output = print_r($_REQUEST,1);
	if ( isset($_REQUEST) ) {
		if ( empty($_REQUEST['option']) ) return;
		if ( empty($_REQUEST['user_course_id']) ) return; 
		$option = $_REQUEST['option'];
		$user_course_id = $_REQUEST['user_course_id'];
		if ( !empty($option) ) {
			$user_course_fee_details = Course\get_course_fee_options($user_course_id); // Debug\tip($user_course_fee_details);
			$output .= '<div class="instalment_info">';
			if ( !empty($user_course_fee_details[$option]) ) {
				//$output .= '<p>'.$option.'</p>'; // [free_fee_ineligible]
				//$output .= '<p>'.print_r($user_course_fee_details[$option],1).'</p>';
				$output .= '<p>It is now ' . date('F Y') . '. Amounts due are as follows:</p>';
				$output .= '<ul>';
				foreach( $user_course_fee_details[$option] as $instalment => $cost ) {
					if ( $instalment == 'full' ) continue;
					if ( $instalment == 'instalment_1' ) $text = 'Prior to Registration'; // 'September ' . date('Y');
					if ( $instalment == 'instalment_2' ) $text = 'By January 31st'; // 'January ' . date('Y', strtotime('+1 year'));
					$output .= '<li>' . $text . ': €<span data-cost="' . $cost . '" id="' . $instalment . '">' . number_format($cost) . '</span></li>';
				} // $user_course_fee_details[$option] as $installment => $cost 
				$output .= '</ul>';
				$output .= '<script type="text/javascript">';
					$output .= 'jQuery(document).ready(function() {';
					$output .= '	jQuery("li.deposit-payment-question input").on("click", function() {';
					$output .= '    	var in_1 = jQuery("#instalment_1").data("cost");';
					$output .= '    	var in_2 = jQuery("#instalment_2").data("cost");';	
					$output .= '    	var calc = 0;'; 
		            $output .= '		if ( jQuery(this).prop("checked") == true ) {';
		            $output .= '    		console.log("Student has paid deposit.");';
		            $output .= '    		calc = (parseInt(in_1) - 150);';
		            $output .= '    		jQuery("#instalment_1").text(calc);';
		            $output .= '		} else if ( jQuery(this).prop("checked") == false ) {';
            		$output .= '    		console.log("Student has not paid deposit");';
            		$output .= '    		calc = parseInt(in_1);';
            		$output .= '    		jQuery("#instalment_1").text(calc);';
            		$output .= '		}';
					$output .= '	});';
					$output .= '});';
				$output .= '</script>';
			} else {
				$output .= '<p>No details have been found for this fee option.</p>';
			} // !empty($client_history)
			$output .= '</div>';

		} // !empty($option)
    }
    die($output);
}
/*
*
* 1/3. populate all this users course fee options
*
*/
function V1_populate_course_fee_options ( $input_choices, $form_id, $field, $input_id, $chain_value, $value, $index ) {
	if(strpos($field['cssClass'], 'course-fee-options') === false) return $input_choices; // only proceed if chained select has this class
	if ( !is_user_logged_in() ) return $input_choices;
	$user_course_id = Student\get_current_user_course_id();
	if ( empty($user_course_id ) ) return $input_choices;
	$fee_options = Course\get_course_fee_options($user_course_id); if ( !$fee_options ) return $input_choices; // Debug\tip($fee_options);
	$keys = array_keys($fee_options); if ( !$keys ) return $input_choices;
	$choices = [];
    foreach( $keys as $key ) {
        $choices[] = array(
            'text'       => Utilities\make_key_pretty($key),
            'value'      => $key,
            'isSelected' => false
        );
    }
	// Debug\tip($choices);
	return $choices;
}
/*
*
* 2/3. xxxx
*
*/
function V1_populate_course_fee_installment_options ( $input_choices, $form_id, $field, $input_id, $chain_value, $value, $index ) {
	if(strpos($field['cssClass'], 'course-fee-options') === false) return $input_choices;
	if ( !is_user_logged_in() ) return $input_choices;
	$user_course_id = Student\get_current_user_course_id(); // echo 'user_course_id'.$user_course_id;
	if ( empty($user_course_id ) ) return $input_choices;
	$selected_option = $chain_value[ "{$field->id}.1" ]; // echo 'selected_option'.$selected_option;
	if( !$selected_option ) return $input_choices;
	$fee_options = Course\get_course_fee_options($user_course_id); // Debug\tip($fee_options);
	$instalments = $fee_options[$selected_option];
	if( !$instalments ) return $input_choices; // Debug\tip($instalments);
	$choices = [];
	foreach( $instalments as $k=>$v ) {
		if (empty($v)) continue;
	    $choices[] = array(
	        'text'       => Utilities\make_key_pretty($k) . ' - ' . '€' . $v,
	        'value'      => $k,
	        'isSelected' => false
	    );
	}
	return $choices;
}
/*
*
* handle the form submission
*/
function handle_form_submission ($entry, $form) {
	//Debug\tip($form); die('here1');
    $search_fields = [
        'course_code' => 'fieldCourseCode',
        'user_name' => 'fieldUserName',
        'user_email' => 'fieldUserEmail',
    ];
    $matches = getGfValuesBy($search_fields, $form, 'css');
    $data = [
        'course_code' => (!empty($matches['course_code'])?rgar($entry, $matches['course_code']):''),
        'name' => [
        	'first' => (!empty($matches['user_name'].'.3')?rgar($entry, $matches['user_name'].'.3'):''),
        	'last' => (!empty($matches['user_name'].'.6')?rgar($entry, $matches['user_name'].'.6'):''),
        ],
        'user_email' => (!empty($matches['user_email'])?rgar($entry, $matches['user_email']):''),
    ];
	// Debug\tip($data); die('here2');
}
/*
*
* handle the form confirmation
*/
function handle_confirmation ( $confirmation, $form, $entry, $ajax ) {
	$data = [
		'entry_id' => $entry['id'],
	];
	$confirmation = '<div id="gform_confirmation_wrapper_1" class="gform_confirmation_wrapper">';
	$confirmation .= '<div id="gform_confirmation_message_1" class="gform_confirmation_message_1 gform_confirmation_message alert alert-success">';
	$confirmation .= '<p>Thank you for your payment. You will soon be sent a payment receipt via email. Please keep safe for your records. Please <a href="' . wp_logout_url( home_url() ) . '">Sign out</a>.</p>';
	$confirmation .= '</div>';
	$confirmation .= '</div>';
	return $confirmation;
}
/* XXX
-------------------------------------------------------------- */
/*
*
* 
*/
add_filter( 'gform_currencies', __NAMESPACE__ . '\\moveEuroSymbolToLeft' );
function moveEuroSymbolToLeft ( $currencies ) {
	$currencies['EUR'] = array(
		'name' => esc_html__( 'Euro', 'gravityforms' ),
		'symbol_left' => '&#8364;',
		'symbol_right' => '',
		'symbol_padding' => ' ',
		'thousand_separator' => ',',
		'decimal_separator' => '.',
		'decimals' => 2
	);
	return $currencies;
}
/*
* make fields read only (https://endurtech.com/create-read-only-field-in-gravityforms) - note you have to add
* a class 'gf_readonly' to the field in the editor first
*/

function apply_readonly( $form ) {
?><script type="text/javascript">
	jQuery(document).ready(function() { 
		jQuery("li.gf_readonly input").prop( "disabled", true );
		jQuery("li.gf_readonly select").prop( "disabled", true );
	});
</script><?php
return $form;
}
/* Helpers
-------------------------------------------------------------- */
/*
*
* helper function to get submitted form values by label
*
* @search_fields
*
* - array of key=>value
*
*  o key: string you want filled with field ID
*  o value: the css class to search
*
* @form
*
* - the gravity form object
*
* @criteria
*
* - label
* - css - css fields must start with 'field' - ie 'fieldClientName'
*
* @criteria
*
* - field
* - trigger ( ** new- added 09/2019)
*
*/

function getGfValuesBy( $search_fields, $form, $criteria='label', $lookup='field' ) {
	$matches = [];
	$fields = $form['fields']; // get all the form fields handed to this hook
	// search for label
	if ( $criteria == 'label' ) {
		foreach($fields as $field) {
			$key = array_search($field->label, $search_fields); // search for the required fields in the $form object
			if ( $key ) {
				$matches[$key] = $field->id; // if there's a match populate the $field_ids array // echo 'key = '.$key.'<br />';
			}
		}
	}
	// search for css class
	if ( $criteria == 'css' ) {	
		foreach ( $fields as $field ) {
			$classes = (!empty($field->cssClass)?explode(' ', $field->cssClass):[]);
			if ( $lookup == 'field' ) {
				// css looks like 'fieldXXXX'
				/*if ( !empty($classes[0]) ) {
					if ( substr( $classes[0], 0, 5 ) === 'field' ) {
					}
				} // !empty($classes[0])*/
				foreach ( $classes as $class ) {
					if (strpos($class, 'field') !== false) {
						if ( substr( $class, 0, 5 ) === 'field' ) {
							$key = array_search($class, $search_fields); // search for the required fields in the $form object
							if ( $key ) {
								$matches[$key] = $field->id; // if there's a match populate the $field_ids array // echo 'key = '.$key.'<br />';
							}
						}
					}
				}
			} elseif ( $lookup == 'trigger' ) {
				// css looks like 'fieldXXXX triggerYYYY'
				if ( !empty($classes[1]) ) {
					if ( substr( $classes[1], 0, 7 ) === 'trigger' ) {
						$key = array_search($classes[1], $search_fields); // search for the required fields in the $form object
						if ( $key ) {
							$matches[$key] = $field->id; // if there's a match populate the $field_ids array // echo 'key = '.$key.'<br />';
						}
					}
				}
			} elseif ( $lookup == 'senta' ) {
				// css can look like anything - will find the senta name
				foreach ( $classes as $class ) {
					if (strpos($class, 'senta_') !== false) {
					   $key = array_search($class, $search_fields); // returns key for a value find
						if ( $key ) {
							$matches[$key] = [
								'id' => $field->id,
								'type' => $field->type,
							]; // if there's a match populate the $field_ids array // echo 'key = '.$key.'<br />';
						}
					}
				}
			} // $lookup == 'instruct'
		} // ($fields as $field)
	} // $criteria == 'css'
	return $matches;
}
