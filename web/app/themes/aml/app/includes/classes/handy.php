<?php

namespace Custom\Classes\Handy;

/**
 * Ican Handy
 *
*/

class I_Handy {

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

	}

	/**
	 * XX
	 *
	 * @since     1.0.0
	 * @return    string    nice output of array
	 */
	public static function tip ($array, $ip='') {

		if ( empty($array) ) return;

		if ( !empty($ip) ) {

			if ( $_SERVER['REMOTE_ADDR'] == $ip ) {

				print "<pre>";
				print_r ($array);
				print "</pre>";

			}

		} else {

			print "<pre>";
			print_r($array);
			print "</pre>";

		}

	}

	/**
	 * unserializes - titan framework seems to double serialize things?
	 *
	 * s:54:"a:3:{i:0;s:5:"17353";i:1;s:5:"17352";i:2;s:5:"17354";}";
	 *
	 * @since     1.0.0
	 * @return    string    nice output of array
	 */

	public static function unserialize ($string) {

		if ( empty($string) ) return;

		while ( is_serialized($string) ) {

			$string = unserialize($string);

		}

		return $string;

	}

	/**
	 * XX
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public static function filter ( $val, $check, $options=array() ) {

		switch ($check):

			case 'string':
			$val = filter_var($val, FILTER_SANITIZE_STRING);
			break;

			case 'int':
			$val = filter_var($val, FILTER_SANITIZE_NUMBER_INT);
			$val = filter_var($val, FILTER_VALIDATE_INT, $options);	// False if fail
			break;

			case 'float':
			$val = filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT);
			$val = filter_var($val, FILTER_VALIDATE_FLOAT);	// False if fail
			break;

			case 'number':
			$val = filter_var($val, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
			break;

			case 'email':
			$val = filter_var($val, FILTER_SANITIZE_EMAIL);
			$val = filter_var($val, FILTER_VALIDATE_EMAIL);	// False if fail
			break;

			case 'url':
			$val = filter_var($val, FILTER_SANITIZE_URL);
			$val = filter_var($val, FILTER_VALIDATE_URL);	// False if fail
			break;

		endswitch;

		return $val;

	}

	/**
	 *
	 */
	public static function forms ( $args = array(), $empty_option=true ) {

		$options = array ();

		$defaults  = array();

		$args = wp_parse_args( $args, $defaults );

		if ( @class_exists ( 'RGFormsModel' ) ) {

			$forms = \RGFormsModel::get_forms( null, 'title' );

			if ( is_array ( $forms ) ) {

				foreach ( $forms as $form ) {

					$options[$form->id] = $form->title;

				}

			}

		}

		if ( !empty($empty_option) ) $options = array(''=>'Select') + $options;

		return $options;

	}

	/**
	* get form fields
	* API https://www.gravityhelp.com/documentation/article/getting-started-with-the-gravity-forms-api-gfapi/#getting-a-single-form
	*/

	public static function form_fields ( $args = array(), $empty_option=true ) {

		$options = array ();

		$defaults  = array();

		$args = wp_parse_args( $args, $defaults );

		if ( @class_exists ( 'GFAPI' ) ) {

			$form = \GFAPI::get_form( $args['id'] );

			if ( is_array ( $form ) ) {

				if ( is_array ( $form['fields'] ) ) {

					foreach ( $form['fields'] as $field ) {

						$options[$field->id] = $field->label;

					}

				}

			}

		}

		if ( !empty($empty_option) ) $options = array(''=>'Select') + $options;

		return $options;

	}

	/**
	* generate an objects dropdown
	* Author: ICAN
	 */

	public static function objects ( $args = array(), $empty_option=true ) {

		$options = array();

		$defaults  = array(
			'orderby' => 'title',
			'order' => 'ASC',
			'posts_per_page' => -1
		);

		$args = wp_parse_args( $args, $defaults );

		$objs = get_posts ($args);

		if (is_array ( $objs )) {

			foreach ( $objs as $obj ) {

				$options[$obj->ID] = $obj->post_title.' (ID:'.$obj->ID.')';

			}

		}

		if ( !empty($empty_option) ) $options = array(''=>'Select') + $options;

		return $options;

	}


	/**
	* generate an users dropdown
	* Author: ICAN
	 */

	public static function users ( $args = array(), $empty_option=true ) {

		$options = array();

		$defaults  = array(
			'role' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$objs = get_users ($args);

		if (is_array ( $objs )) {

			foreach ( $objs as $obj ) {

				$options[$obj->ID] = $obj->user_login.' (ID:'.$obj->ID.')';

			}

		}

		if ( !empty($empty_option) ) $options = array(''=>'Select') + $options;

		return $options;

	}

	/*
	*
	* See http://codex.wordpress.org/Function_Reference/get_pages
	*/

	public static function pages ( $args = array(), $empty_option=true ) {

		$options = array();

		$defaults  = array(
			'sort_column' => 'menu_order',
			'posts_per_page' => -1
		);

		$args = wp_parse_args( $args, $defaults );

		$pages = get_pages ( $args );

		if (is_array ( $pages )) {

			foreach ( $pages as $page ) {

				$indent = ( !empty($page->post_parent) ? '--- ' : '' );
				$options[$page->ID] = $indent.$page->post_title.' (ID:'.$page->ID.')';

			}

		}

		if ( !empty($empty_option) ) $options = array(''=>'Select') + $options;

		return $options;

	}

	/**
	 *
	 */

	public static function filler_latin ( $chars = NULL ) {

		$latin = 'No sumo fabulas necessitatibus vis, nec legimus gloriatur no. Ne duo integre mediocrem, ius ut tota novum nihil. Cetero regione perfecto eum ea, eos sint laoreet an, tollit iisque neglegentur per cu. Elit nobis sanctus quo ne, eum affert animal id. Pri insolens comprehensam ut. Tritani volumus est an. Nulla fastidii epicurei id qui, vel ne bonorum ancillae intellegat. Ne fastidii copiosae epicurei sed. Ei vitae assentior appellantur sea, tractatos consulatu conclusionemque pro ad. Te convenire salutatus ius, eos ad graecis moderatius inciderint. Cum lucilius temporibus at, ea putent eruditi reformidans eam, usu ex aliquip vivendo adolescens. Facete alterum aliquam ius no, cum autem iisque sanctus ad, saperet dolorum ex nec.';

		return (!empty($chars)?substr($latin,0,$chars):$latin);

	}

	/**
	 * get related objects based on tags.
	 *
	 * @since     1.0.0
	 * @return    string    nice output of array
	 */
	public static function get_related_objects ( $args = array() ) {

		global $post;

		$defaults  = array(
			'type' => 'post',
			'taxonomies' => array(),
			'count' => -1,
			'relation' => 'OR'
		);

		$args = wp_parse_args( $args, $defaults );

		$type = $args['type'];
		$count = $args['count'];
		$taxonomies = $args['taxonomies'];
		$relation = $args['relation'];

		$related_posts = NULL;

		$args = array(
			'post_type' => $type,
			'numberposts' => $count,
			'post__not_in' => array($post->ID)
		);

		$tax_query = array();

		$cat_array = array();
		$tag_array = array();
		$tax_array = array();

		if ( in_array('category', $taxonomies) ) {

			$cat_array =  wp_get_post_categories($post->ID);
			$tax_query[] = array('taxonomy' => 'category', "field" => "term_id", 'terms' => $cat_array, 'include_children' => false );

		}

		if ( in_array('post_tag', $taxonomies) ) {

			$tags = wp_get_post_tags($post->ID);
			foreach($tags as $tag) $tag_array[] = $tag->slug;
			$tax_query[] = array('taxonomy' => 'post_tag', "field" => "slug", 'terms' => $tag_array);

		}

		if ( in_array('campaign', $taxonomies) ) {

			$terms = wp_get_post_terms( $post->ID , 'campaign');
			foreach($terms as $term) $tax_array[] = $term->slug;
			$tax_query[] = array('taxonomy' => 'campaign', "field" => "slug", 'terms' => $tax_array);

		}

		if ( !empty($tax_query) ) $args['tax_query'] = $tax_query;

		if ( count($tax_query) > 1 ) $args['tax_query']['relation'] = $relation;

		// $this->tip($args);

		$related_posts = get_posts($args);

		if ( !empty ( $related_posts ) ) {

			return $related_posts;

		}

		return $related_posts;

	}

	/*
	* get_top_parent_page_id ()
	* Returns the top parent of an object - recursive function
	*/

	public static function get_top_parent_page_id() {

		global $post;

		$ancestors = $post->ancestors;

		// Check if page is a child page (any level)
		if ($ancestors) {

			//  Grab the ID of top-level page from the tree
			return end($ancestors);

		} else {

			// Page is the top level, so use  it's own id
			return $post->ID;

		}

	}

	/*
	* whats_this_template()
	* what template is this page using?
	* useage - whats_this_template ( 'section.php' )
	*/

	public static function whats_this_template ( $template ) {

		global $post;

		// If this top parent is a section, get its custom details
		$template_file = get_post_meta($post->ID,'_wp_page_template', TRUE);

		if( $template_file == $template ) return true;

		return false;

	}

	/*
	*
	*/

	public static function get_segments ($url) {

		return explode("/", $url);

	}

	/*
	*
	* url_contains()
	*
	* is a segement in a url
	*
	*/

	public static function url_contains ( $str ) {

		$url = self::get_current_url(true);

		$segments = self::get_segments($url);

		if (!empty($segments)){

			foreach ($segments as $segment){
				if ($str == $segment) return true;
			}

		}

		return false;

	}

	/*
	* simply returns the current page URL
	*/

	public static function get_current_url ( $removequerystring=false ) {
		global $wp;
		$url = home_url(add_query_arg(array(),$wp->request));
		/*$url = 'http';
		if ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $url .= "s";
		$url .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$url .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$url .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		if ($removequerystring) $url = (strpos($url,'?')===false?$url:substr($url,0,strpos($url,'?')));
		*/
		return $url;
	}

	/*
	* simply returns the the refering URl
	*/

	public static function get_refering_url ( $remove_querystring=false ) {

		$url = (!empty($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');

		if ($remove_querystring) $url = substr($url,0,strpos($url,'?'));

		return $url;

	}

	/*
	* simply returns the current query string
	*/

	public static function get_querystring() {

		return $_SERVER['QUERY_STRING'];

	}

	/*
	* returns the referrer object - used with yoast SEO breadcrumbs
	*/

	public static function get_referrer_obj() {

		$pobj = NULL;

		$base = get_bloginfo('url');
		$rpage = (!empty($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'');
		$rpage = (stristr($rpage,'?')?substr($rpage,0,strpos($rpage,'?')):$rpage);	// remove query string

		/*if ( $_SERVER['REMOTE_ADDR']=='185.32.152.232' ):
			echo $base;
			echo '<br />----<br />';
			echo $rpage;
			if ( stristr($rpage, $base) ) {echo 'yes';}else{echo 'no';}
		endif;*/

		if ( stristr($rpage, $base) ):

			$slug = str_replace($base,'',$rpage);
			$slug = rtrim($slug,'/');
			$slug = ltrim($slug,'/');

			if ( !empty($slug) ) $pobj = get_page_by_path($slug);

		endif;

		return $pobj;

	}

	/**
	 *
	 * Get the secgments of the URL
	 */

	public static function get_url_segments () {

		$url = self::get_current_url();
		$url = str_replace('http://','',$url);
		$url = rtrim($url,"/");
		return explode("/", $url);

	}

	/*
	*
	* just sanitizes a url - mainly for W3C validation
	*/

	public static function sanitize_url($url) {

		$url = str_replace('&','&amp;',$url);

		return $url;

	}

	/*
	*
	* searcharray('searchvalue', searchkey, $array)
	*/

	public static function search_array($value, $key, $array) {

	   foreach ($array as $k => $val) {
		   if ($val[$key] == $value) {
			   return $k;
		   }
	   }

	   return null;

	}

	/*
	*
	* Find position of Nth $occurrence of $needle in $haystack
	* Starts from the beginning of the string
	*/

	public static function strpos_offset($needle, $haystack, $occurrence) {

		// explode the haystack
		$arr = explode($needle, $haystack);

		// check the needle is not out of bounds

		switch( $occurrence ) {
		case $occurrence == 0:
			return false;
		case $occurrence > max(array_keys($arr)):
			return false;
		default:
			return strlen(implode($needle, array_slice($arr, 0, $occurrence)));
		}

	}

	/*
	*
	* is there $_GET empty?
	*/

	public static function is_get_empty () {

		if ( !empty($_GET) ):

			foreach ($_GET as $key=>$val):

				// echo "$key=>$val<br />";
				if ( !empty($val) ) return false;

			endforeach;

		endif;

		return true;

	}

	public static function add_intro_paragraph($content) {

		$new_content = [];

		$paragraphs = explode('<p>', $content);

		foreach ($paragraphs as $key => $paragraph) {
			$class = ($key==1?'intro':'');
			$paragraph = '<p class="'.$class.'">' . $paragraph;
			if ( $key > 0 ) {
				$new_content[] = $paragraph;
			}
		}

		$content = (!empty($new_content)?implode('', $new_content):$new_content);

		return $content;

	}

	/*
	*
	* check the current post for the existence of a short code
	* http://wp.tutsplus.com/articles/quick-tip-improving-shortcodes-with-the-has_shortcode-function/
	* works with the likes of [cat="445"] - ie has_this_shortcode('article_view type="notice"')
	* or tiboot_has_this_shortcode('listing_jobs')
	*/

	public static function has_this_shortcode ($shortcode = '') {

		$post_to_check = get_post(get_the_ID());

		// false because we have to search through the post content first
		$found = false;

		// if no short code was provided, return false
		if (!$shortcode) {
			return $found;
		}
		// check the post content for the short code
		if ( !empty($post_to_check) && (stripos($post_to_check->post_content, '[' . $shortcode) !== false) ) {
			// we have found the short code
			$found = true;
		}

		// return our final results
		return $found;

	}

	/*
	*
	*
	*
	* @useage: Handy\I_Handy::is_gutenberg_block_being_used('acf/koru')
	*
	*/

	public static function is_gutenberg_block_being_used ( $post, $find ) {
	    if (empty($post)) return false;
	    if (empty($find)) return false;
        if ( has_blocks( $post->post_content ) ) {
            $blocks = parse_blocks( $post->post_content );
            if (!empty($blocks) ) {
                foreach ( $blocks as $block ) {
	                if ( $block['blockName'] === $find ) { 
	                    return true;
	                }
            	}
            }
        }
	    return false;
	}
	
	/*
	* echo_first_image
	* Might need this to take out first image of an artilce
	* to re position the article MPU
	*/

	public static function echo_first_image ( $postID ) {

		$args = array(
			'numberposts' => 1,
			'order' => 'ASC',
			'post_mime_type' => 'image',
			'post_parent' => $postID,
			'post_status' => null,
			'post_type' => 'attachment',
		);

		$attachments = get_children( $args );

		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$image_attributes = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' )  ? wp_get_attachment_image_src( $attachment->ID, 'thumbnail' ) : wp_get_attachment_image_src( $attachment->ID, 'full' );

				echo '<img src="' . wp_get_attachment_thumb_url( $attachment->ID ) . '" class="current">';
			}
		}

	}

	/*
	*
	* i_wp_parse_args - wp_parse_args but used with multi dimensional arrays
	*
	* http://mekshq.com/recursive-wp-parse-args-wordpress-function/
	*
	*/

	public static function i_wp_parse_args( &$a, $b ) {

		$a = (array) $a;
		$b = (array) $b;
		$result = $b;
		foreach ( $a as $k => &$v ) {
			if ( is_array( $v ) && isset( $result[ $k ] ) ) {
				$result[ $k ] = I_Handy::i_wp_parse_args( $v, $result[ $k ] );
			} else {
				$result[ $k ] = $v;
			}
		}
		return $result;
	}

	/*
	* get_top_parent()
	* returns the top parent of a page - recursive function
	*/

	public static function get_top_parent ($pid){

		$mypage = get_page($pid);

		if ( $mypage->post_parent == 0 ){
			return $mypage->ID;
		} else{
			return get_top_parent($mypage->post_parent);
		}

	}


	/*
	* has_children( $post_id )
	* Has an object children?
	*/

	public static function has_children( $post_type='page', $post_id ) {

		$children = get_pages( "post_type=$post_type&child_of=$post_id" );

		if( count( $children ) != 0 ) { return true; } // Has Children
		else { return false; } // No children

	}

	/*
	*
	* get uppermost ancestor
	*
	*/

	public static function get_post_top_ancestor_id() {

		global $post;

		if($post->post_parent){

			$ancestors = array_reverse(get_post_ancestors($post->ID));

			return $ancestors[0];

		}

		return $post->ID;

	}

	/*
	*
	* is there $_GET empty?
	*
	*/

	public static function is_get_tax_empty () {

		$taxs = array('loc','mrt','aud','pro');

		if ( !empty($_GET) ):

			foreach ($_GET as $key=>$val):

				// echo "$key=>$val<br />";
				if ( !empty($key) && in_array($key,$taxs) ) return false;

			endforeach;

		endif;

		return true;

	}

	/*
	*
	* is there $_GET empty?
	*
	*/

	public static function get_widgets ( $sidebar_identity, $query='name' ) {

		global $wp_registered_sidebars, $wp_registered_widgets;

		// $output = '';
		$our_widgets = array(); // holds the final data to return
		$sibebar_id = false;	// Loop over all of the registered sidebars looking for the one with the same name as $sidebar_identity

		foreach( $wp_registered_sidebars as $sidebar ) {

			// echo $sidebar[$query] . ' | ' . $sidebar_identity . '<br /><br />';

			if ( $sidebar[$query] == $sidebar_identity ) {
				// we now have the sidebar ID, we can stop our loop and continue.
				$sidebar_id = $sidebar['id'];
				break;
			}

		}

		// echo $sidebar_id . '<br /><br />';

		if( empty($sidebar_id) ) {
			// There is no sidebar registered with the name provided.
			return $output;
		}

		$sidebars_widgets = wp_get_sidebars_widgets();	// A nested array in the format $sidebar_id => array( 'widget_id-1', 'widget_id-2' ... );

		$widget_ids = $sidebars_widgets[$sidebar_id];

		// echo $sidebar_id . '<br /><br />';

		if( empty($widget_ids) ) {
			// Without proper widget_ids we can't continue.
			return array();
		}

		// Loop over each widget_id so we can create our container widgets array for output
		// using the_widget( $widget, $instance, $args );
		foreach( $widget_ids as $id ) {

			$tmp = array();

			// Add all arguments
			//$tmp['classname'] = $wp_registered_widgets[$id]['classname'];
			$obj = $wp_registered_widgets[$id]['callback'][0];
			$class_name = get_class($obj);
			$tmp['classname'] = $class_name;


			// Add all arguments
			// $tmp['args'] = array ('before_widget' => 'li class="widget">','after_widget' => '</li>','before_title' => '<h2>','after_title' => '</h2>');
			$tmp['args'] = array ('before_widget' => '','after_widget' => '','before_title' => '','after_title' => '');

			// The name of the option in the database is the name of the widget class.
			$option_name = $wp_registered_widgets[$id]['callback'][0]->option_name;

			// Widget data is stored as an associative array. To get the right data we need to get the
			// right key which is stored in $wp_registered_widgets
			$key = $wp_registered_widgets[$id]['params'][0]['number'];

			$widget_data = get_option($option_name);

			// Add the widget data on to the end of the output array.
			$tmp['instance'] = $widget_data[$key];

			$our_widgets[] = $tmp;

		}

		return $our_widgets;

	}

	/*
	* see http://stackoverflow.com/questions/18669256/how-to-update-wordpress-taxonomiescategories-tags-count-field-after-bulk-impo
	*/

	public static function update_term_count () {

		global $wpdb;

		$sql = "UPDATE wp_term_taxonomy SET count = (
				SELECT COUNT(*) FROM wp_term_relationships rel
				    LEFT JOIN wp_posts po ON (po.ID = rel.object_id)
				    WHERE
				        rel.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id
				        AND
				        wp_term_taxonomy.taxonomy NOT IN ('link_category')
				        AND
				        po.post_status IN ('publish', 'future')
				)
		";

		$result = $wpdb->query($sql);

		return $result;

	}

	/*
	* dummy function for outputting bootstrap columns and rows
	*/

	public static function BootstrapContentArrange($i) {

	    $items = $i;                // qnt of items
	    $rows = ceil($items/3);     // rows to fill
	    $lr = $items%3;             // last row items
	    $lrc = $lr;                 // counter to last row

	    while ($items > 0) {        // while still have items
	        $cell = 0;
	        if ($rows > 1) {        // if not last row...
	            echo '<div class="row">'.PHP_EOL;
	            while ($cell < 3) {     // iterate with 3x4 cols
	                echo '<div class="col-md-4">Content</div>'.PHP_EOL;
	                $cell++;
	            }
	            echo "</div>".PHP_EOL;
	        $rows--;        // end a row
	        } elseif ($rows == 1 && $lr > 0) {      // if last row and still has items
	            echo '<div class="row">'.PHP_EOL;
	            while ($lrc > 0) {      // iterate over qnt of remaining items
	                $lr == 2 ?      // is it two?
	                    print('<div class="col-md-6">Content</div>'.PHP_EOL) :  // makes 2x6 row
	                    print('<div class="col-md-12">Content</div>'.PHP_EOL); // makes 1x12 row
	                $lrc--;
	            }
	            echo "</div>".PHP_EOL;
	            break;
	        } else {        // if round qnt of items (exact multiple of 3)
	            echo '<div class="row">'.PHP_EOL;
	            while ($cell < 3) {     // iterate as usual
	                echo '<div class="col-md-4">Content</div>'.PHP_EOL;
	                $cell++;
	            }
	            echo "</div>".PHP_EOL;
	            break;
	        }
	        $items--;       // decrement items until it's over or it breaks
	    }
	}

	/*
	*
	* gets the top parent ID of a particlar page
	*
	* useage Handy\I_Handy::getTopParentID($post)
	*
	*/

	public static function getTopParentID ( $post ) {
		if (empty($post)) return;
	    // check if page is a child page (any level)
	    $ancestors = $post->ancestors;
	    if ( !empty( $ancestors ) ) {
	        //  Grab the ID of top-level page from the tree
	        $ancestors = end($ancestors);
	        return $ancestors;
	    } else {
	        //Page is the top level, so use  it’s own id
	        //return $post->ID;
	        return 1;
	    }
	}

	/*
	*
	* Creates a google calander link.
	*
	* @Description:
	*
	* Call this with the shown parameters (make sure $time and $end are integers and in Unix timestamp format!). Get a link that will open
	* a new event in Google Calendar with those details pre-filled. Sample link, navigate to it while logged into your Google account. If
	* you aren't logged in, it should redirect properly upon login
	*
	* @useage:
	*
	* - Handy\I_Handy::make_google_calendar_link("A Special Event", 1429518000, 1429561200, "612 Wharf Ave. Hoboken, New Jersey", "Descriptions require imagination juice");
	*
	* - https://jennamolby.com/tools/google-calendar-link-generator/
	*
	*/

	public static function make_google_calendar_link($name, $begin, $end, $location, $details) {
	    $params = array('&dates=', '/', '&details=', '&location=', '&sf=true&output=xml');
	    $url = 'https://www.google.com/calendar/render?action=TEMPLATE&text=';
	    $arg_list = func_get_args();
	    for ($i = 0; $i < count($arg_list); $i++) {
	        $current = $arg_list[$i];
	        if(is_int($current)) {
	            $t = new \DateTime('@' . $current, new \DateTimeZone('GMT'));
	            $current = $t->format('Ymd\THis\Z');
	            unset($t);
	        }
	        else {
	            $current = urlencode($current);
	        }
	        $url .= (string) $current . $params[$i];
	    }
	    return $url;
	}

	/*
	*
	*/

	public static function telUnformat($tel) {
		$tel = str_replace('(0)','',$tel); // remove the optional numbers - numbers in brackets - ie generally (0)
		$tel = preg_replace("/[^0-9]/", '', $tel); // remove all non numberic characters
		$tel = '+' . $tel; // add the internationl '+'
	    return $tel;
	}
}
