<?php

	/*
	*
	* id pal functions
	*
	*/

	namespace Custom\User\IdPal;

	use Custom\Classes\Handy;
	use Custom\Debug;

    use Custom\Package\IdPal;
	use Custom\User\Comms;

	/*
	*
	* output all director packages in dashboard
	*
	*/
	function user_idpal_summary() {
		$url_base_idpal = 'https://qf97.app.link/0gL69PUUZx';
		$error_codes = [
			'No submission received', // 0
			'No submission received and no errors in technical checks, but report not generated', // 1
			'Submission received with errors in technical checks, and report not generated', // 2
			'Report Flagged by user', // 3
			'CDD generated and all technical checks passed', // 4
			'CDD generated but some technical checks failed (e.g. manual over-ride was used)', // 5
		];
		$args = array(
		    'role'    => 'client',
		    'orderby' => 'user_nicename',
		    'order'   => 'ASC',
		    // 'meta_key' => 'aml_company_directors'
		);
		$clients = get_users( $args );
		// Handy\I_Handy::tip($clients);
		?>
		<div class="user_actions">
			<? if ( !empty($clients) ): ?>
				<div class="accordion" id="accordion-clients">
					<? $i=1; $j=1; foreach ($clients as $client): ?>
						<?
						$business_name = get_user_meta($client->ID, 'aml_business_name', true);
						$client_name = $client->display_name . ( !empty($business_name) ? ' ('.$business_name.')':''); 
						$directors_package = get_user_meta($client->ID, 'aml_company_directors', true); // Handy\I_Handy::tip($directors_package);
						/*
						   [status] => 1|0
						   [entry_id] =>
						   [members] =>
						   			[0] =>
						   				[email] => XXX@YYY.com
						   				[status] => 1
						   				[step] => 1
						   				[started] => 1560172462
						   				[updated] => 1560172462
						   				[finished] => // [added when user is queried as being complete ]
						   				[overwritten] => // [added when there's a manual overwrite via dashboard]
						   				[uuid] => ca08f1c1
						   				[submissions] =>
						   					[0] =>
						   						[uuid] => ca08f1c1
						   						[submission_id] => 21896
						   						[status] => 5
						   						[document_type] => idcard
						   						[authentication_data] => Fail|Pass
						   						[facial_match] => Fail|Pass
						   					[1]
						   					[2]
						   			[1]
						   			[2]
						   			..
						 [flow_unlocked] => 1560186422
						*/
						// $client->user_email
						?>
						<div class="card">
							<div class="card-header" id="heading-client-<?=$client->ID?>">
								<h2 class="mb-0"><button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse-client-<?=$client->ID?>" aria-expanded="<?=($i===1?'true':'false')?>" aria-controls="collapse-client-<?=$client->ID?>"><?=$client_name?></button></h2>
								<span class="indicators"><span class="status-<?=$directors_package['status']?>"></span></span>
							</div> <!-- card-header -->
							<div id="collapse-client-<?=$client->ID?>" class="collapse<?=($i===1?' show':'')?>" aria-labelledby="heading-client-<?=$client->ID?>" data-parent="#accordion-clients">
								<div class="card-body">
									<? if ( empty($directors_package['status']) ): ?>
										<p>Displaying incomplete ID Pal tasks:</p>
										<? if ( !empty($directors_package['members']) ): ?>
											<div class="transaction">
												<ul>
												<? foreach ( $directors_package['members'] as $member ) { ?>
													<span class="indicators"><span class="status-<?=$member['status']?>"></span><span class="step-<?$member['step']?>"></span></span>
													<? if ( empty($member['status']) ) { ?>
														<? $url_unique_idpal = add_query_arg( 'uuid', $member['uuid'], $url_base_idpal ); // new $member['uuid'] ?>
														<li>
															<?
															echo sprintf("User <a href='mailto:%1s' title='%2s' target='_blank'>%3s</a>, started %4s%5s (AML package step <a href='#' title='%6s'>%7s</a>) has currently ID-Pal status %8s.",
																$member['email'].'?subject='.urlencode('ID Verification Outstanding').'&body=Please complete your ID Verification using your unique ID-Pal URL unique ID Pal URL '.$url_unique_idpal.'.',
																print_r($member,1),
																$member['email'],
																date('Y-m-d H:i:s', $member['started']),
																(!empty($member['updated'])?', updated '.date('Y-m-d H:i:s', $member['updated']):''),
																'0: AML package created, 1: link sent, 2: docs submitted',
																$member['step'],
																(!empty($member['submissions'])?'<a href="#" title="'.$error_codes[$member['submissions'][0]['status']].'">'.$member['submissions'][0]['status'].'</a>':'<a href="#" title="'.$error_codes[0].'">0</a>')
																// (!empty($member['submissions'])?'<a href="#" title="'.$error_codes[$member['submissions'][0]['status']].'">'.$member['submissions'][0]['status'].'</a>':'<a href="#" title="'.$error_codes[0].'">0</a>')
															);
															?>
														</li>
													<? } else { ?>
														<? // these users should be complete ?>
														<li><?=$member['email']?> is status <?=$member['status']?>.</li>
													<? } // empty($member['status']) ?>
												<? } // $directors_package['members'] as $member ?>
												</ul>
											</div>
										<? endif; // !empty($directors_package['members']) ?>
										<? $j++; ?>
									<? else: ?>
										<p>Displaying complete ID Pal tasks:</p>
									<? endif; // empty($directors_package['status']) ) ?>
									<a href="#" class="btn btn-secondary idpal_btn_submit_user" data-id="<?=$client->ID?>" title="Send this user to ID Pal">Send</a>
								</div> <!-- card-body -->
							</div> <!-- collapse -->
						</div> <!-- card -->
					<? $i++; endforeach; // foreach ($clients as $client) ?>
				</div> <!-- .accordion -->
			<? else: ?>
				<p>There are currently no outstanding ÌD-Pal tasks.</p>
			<? endif; ?>
		</div> <!-- user_mandates -->
		<?
	}

	/*
	*
	* idpal_submit - submit this user into the IDPal process (called by Ajax)
	*
	*/
	add_action('wp_ajax_idpal_btn_submit_user', __NAMESPACE__ . '\\idpal_btn_submit_user');
	add_action('wp_ajax_nopriv_idpal_btn_submit_user', __NAMESPACE__ . '\\idpal_btn_submit_user');
	function idpal_btn_submit_user() {
		$user_id = $_POST['user_id'] ;
		if ( !empty($user_id) ) {
			// enter this user into the funnel
			$user_obj = get_user_by('id', $user_id);
			submit_to_idpal($user_id, [$user_obj->user_email]);
			$directors = get_user_meta( $user_id, 'aml_company_directors', true);
			Handy\I_Handy::tip($directors);
		}
		die();
	}
	/*
	*
	* Director form hooks
	*
	add_action( 'init', __NAMESPACE__ . '\\director_form_hooks', 11);
	function director_form_hooks () {
		$fid = App\themeSettings('theme_idpal_limited_fid');
		if ( !empty($fid) ):
			// after submission
			add_filter( "gform_after_submission_{$fid}", __NAMESPACE__ . '\\create_director_package', 10, 2 );
			// add_filter( "gform_confirmation_{$ofid}", __NAMESPACE__ . '\\confirmation_manual_onboarding', 10, 4 ); // perhaps instruct client service agent about something - ie what's been done
		endif;
		// add_filter( "gform_after_submission_81", __NAMESPACE__ . '\\create_director_package', 10, 2 ); // test IDPal form
	}
	*/
	/*
	*
	* this hook listens to 'all' (change so that all forms can access - 09/2019) form submissions and checks for the presence of 'fieldXXXX' css selectors - then if
	* present creates a directors package 'aml_company_directors' which is queried every our by a CRON ('aml_cron_idpal_get_progress_uuids') to be used with ID Pal
	*
	* array [
	* 	status
	*  	entry_id
	*   members => [
	*   	[0] => [
	*   		email
	*   	 	status
	*   	 	step
	*   	 	started
	*   	 	updated
	*   	  	finished
	*   	 	appinstance // depreciate eventually
	*   	 	uuid // new 4.5.0
	*           [submissions] => Array
	*                 (
	*                    [0] => Array
	*                        (
	*                           [appinstance] => 10b28563
	*                           [uuid] => 10b28563
	*                           [submission_id] => 31269
	*                           [profile_id] => 87
	*                           [profile_name] => Default Profile
	*                           [status] => 5
	*                           [account_id] =>
	*                       )
	*                    [1]
	*                    [2]
	*                )
	*            [finished] => 1568730684
	*   	],
	*  	]
	* ]
	*/
	//add_filter( "gform_after_submission", __NAMESPACE__ . '\\create_director_package', 10, 2 );
	function create_director_package ($entry, $form) {
		// die('here');
		$debug_ips = Debug\get_debug_ips();
		if ( is_user_logged_in() ) {
    		$current_user = wp_get_current_user();  // Handy\I_Handy::tip($current_user); die();
    		$user_roles = (array) $current_user->roles;
    		if ( in_array( 'client', $user_roles ) || in_array( 'administrator', $user_roles ) ) {
    			// 1. store 'aml_company_directors' meta (if there is submitted meta)
    			$emails = [];
				$search_fields = [
					'fieldClientDirector1' => 'fieldClientDirector1',
					'fieldClientDirector2' => 'fieldClientDirector2',
					'fieldClientDirector3' => 'fieldClientDirector3',
					'fieldClientDirector4' => 'fieldClientDirector4',
					'fieldClientDirector5' => 'fieldClientDirector5',
				];
				$matches = GravityForms\getGfValuesBy($search_fields, $form, 'css'); 
				// if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { Handy\I_Handy::tip($matches); die('DEBUGGING --- create_director_package() --- DEBUGGING'); }
				if ( !empty($matches['fieldClientDirector1']) ) array_push($emails, rgar($entry, $matches['fieldClientDirector1']));
				if ( !empty($matches['fieldClientDirector2']) ) array_push($emails, rgar($entry, $matches['fieldClientDirector2']));
				if ( !empty($matches['fieldClientDirector3']) ) array_push($emails, rgar($entry, $matches['fieldClientDirector3']));
				if ( !empty($matches['fieldClientDirector4']) ) array_push($emails, rgar($entry, $matches['fieldClientDirector4']));
				if ( !empty($matches['fieldClientDirector5']) ) array_push($emails, rgar($entry, $matches['fieldClientDirector5']));
				// if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { Handy\I_Handy::tip($emails); die('DEBUGGING --- create_director_package() --- DEBUGGING'); }
				if ( !empty($emails) ) {
					// error_log('[user.php -> create_director_package()] emails: ' . print_r($emails,1));
					// Handy\I_Handy::tip($emails); die();
					submit_to_idpal($current_user->ID, $emails, [ 'entry_id' => $entry['id'] ]);
				} // !empty($emails)
    		} // in_array( 'client', $user_roles ) || in_array( 'administrator', $user_roles )
		} // ( is_user_logged_in() )
	}
	/*
	*
	*
	* submit the emails to ID Pal
	*
	*
	*/
	add_action('template_redirect', __NAMESPACE__ . '\\output_debug');
	function output_debug () {
		submit_to_idpal (4, ['liverpoolrc@gmail.com']);
		//$profiles = IdPal\getAppProfiles();
		//Handy\I_Handy::tip($profiles); die();
	}
	/*
	*
	*
	*/
	function submit_to_idpal ($user_id, $emails, $args = []) {
		if ( empty($user_id) ) return;
		// error_log('[user.php -> create_director_package()] emails: ' . print_r($emails,1));
		// Handy\I_Handy::tip($emails); die();
		$admin_email = get_option( 'admin_email' );
		$site_title = get_bloginfo( 'name' );
		$site_url = get_bloginfo( 'url' );
		$directors = [
			'status' => 0,
			'entry_id' => (!empty($args['entry_id'])?$entry['id']:null), // if this is form submission record the entry
			'members' => [],
		];
		foreach ($emails as $email) {
			if (!empty($email)) {
				$director = [
					'email' => $email,
					'status' => 0,
					'step' => 0, // 0: package created, 1: link sent, 2: docs submitted
					'started' => time(),
					'uuid' => '',
					//'appinstance' => '', // depreciate eventually - but need to keep for now (API 2.4.1 -> 2.5)
					'submissions' => [], // none initially
				];
				array_push($directors['members'], $director);
			}
		}
		// Handy\I_Handy::tip($directors); die();
		error_log('create_director_package() called | directors created: ' . print_r($directors, 1));
		update_user_meta( $user_id, 'aml_company_directors', $directors );
		// 2. get 'aml_company_directors' - loop and send IDPal URL
		$directors = get_user_meta( $user_id, 'aml_company_directors', true);
		$business_name = get_user_meta($user_id, 'aml_business_name', true);
		if ( !empty($directors) ) {
			if ( empty($directors['status']) ) {
				$updated_directors = [
					'status' => $directors['status'],
					'entry_id' => $directors['entry_id'],
					'members' => [],
				];
				foreach ($directors['members'] as $member) {
					$updated_director = [];
					$updated_director = $member;
					if ( empty($member['status']) ) {
						$result = IdPal\sendAppLink(); // Handy\I_Handy::tip($result); die();
						// error_log('user.php -> create_director_package() | IdPal\sendAppLink() -> result: ' . print_r($result,1) );
						/*print_r($result,1) Array
						(
						    [status] => error
						    [function] => sendAppLink
						    [response] => Array
						        (
						            [status] => 25
						            [message] => UUID generated
						            [uuid] => 6306e32c
						        )

						)*/
						// if ( $result['status'] == 'success' ) {} // this only works your getting IDP to send email/SMS
						// $result['response']['uuid'] now contains app instance // ie '004d1a0c';
						// Handy\I_Handy::tip($result); die();
						if ( !empty($result['response']['uuid']) ) {
							$mail_args = [
								'to' => [$member['email']],
								'headers' => [
									'Content-Type' => 'text/html; charset=UTF-8',
									'Cc: Accountant Online <' . $admin_email . '>',
									'Cc: Website <hello@hootfish.ie>', // testing
								],
								'subject' => 'ID/POA Verification required',
								'body' => sprintf(
									"Hi,
									<br /><br />You are receiving this email because your business%1s will be availing of services from %2s.
									<br /><br />Please %3s to download the ID Pal app for ID verification.
									<br /><br />This link is valid for 48 hours. We cannot provide services to you until you have completed this process.
									<br /><br /><strong>Why do we need this from you?</strong>
									<br /><br />Accountant Online is supervised by %4s and therefore we are required to review and keep ID and proof of address for our clients before providing professional services.
									<br /><br /><strong>Keeping your data safe</strong>
									<br /><br />By submitting your ID and proof of address you are agreeing to Accountant Online storing this personal data for as long as you remain a client of Accountant Online and for up to 5 years after in line with guidelines from Chartered Accountants Ireland. We will keep this data in line with our %5s and our %6s.
									<br /><br />Please let us know if you have any questions.",
									(!empty($business_name)?' (' . $business_name . ')':''),
									$site_title, // '<a href="'.$site_url.'">'.$site_title.'</a>',
									'<a href="https://qf97.app.link/0gL69PUUZx?uuid='.$result['response']['uuid'].'">click this link</a>',
									'<a href="https://www.charteredaccountants.ie/find-a-Firm/firm-details?firm=lizdan-business-services-ltd-45648">Chartered Accountants Ireland</a>',
									'<a href="'.$site_url.'/about/privacy/">privacy policy</a>',
									'<a href="'.$site_url.'/about/gdpr-policies-and-procedures/">GDPR policies and procedures</a>'
								),
							];
							$email_sent = Comms\emailUser($mail_args);
							if ( $email_sent ) {
								// error_log('Ican\Theme\User\IdPal -> create_director_package() | IdPal\sendAppLink() -> email_sent -> result('.$email_sent.') -> mail args: ' . print_r($mail_args,1) );
								// email has been sent to this director with unique 'uuid'
								$updated_director['step'] = 1; // 1: link sent
								$updated_director['updated'] = time();
								//$updated_director['appinstance'] = $result['response']['uuid']; // depreciate eventually
								$updated_director['uuid'] = $result['response']['uuid'];
							} else {
								//error_log('Ican\Theme\User | create_director_package(): call to IdPal\sendAppLink() returned error: ' . print_r($result,1));
							}
						} else {
							//error_log('Ican\Theme\User | create_director_package(): call to IdPal\sendAppLink() with no parameters returned error: ' . print_r($result,1));
							// try again but this time allow ID-Pal to send the email
							$result = IdPal\sendAppLink(['information_type' => 'email', 'contact' => $member['email']]); // Handy\I_Handy::tip($result); die();
							if ($result['status'] == 'success') {
								// email has been sent to this director with unique 'uuid'
								$updated_director['step'] = 1; // 1: link sent
								$updated_director['updated'] = time();
								//$updated_director['appinstance'] = $result['response']['uuid'];
								$updated_director['uuid'] = $result['response']['uuid']; // depreciate eventually
							} else {
								//error_log('Ican\Theme\User | create_director_package(): call to IdPal\sendAppLink() with parameters returned error: ' . print_r($result,1));
							}
						} // !empty($result['response']['uuid'])
					}
					array_push($updated_directors['members'], $updated_director);
				}
				// Handy\I_Handy::tip($updated_directors); die();
				update_user_meta( $user_id, 'aml_company_directors', $updated_directors );
			} // empty($directors['status'])
		} // !empty($directors)
	}
	/*
	*
	* CRON script which runs every 1 hour (** TO BE DEPRECIATED with webhook method process_idpal_webhook_response() **)
	*
	* - loops all clients with 'aml_company_directors' meta
	* - pulls out aml_company_directors
	* - loops aml_company_directors and call IdPal\AppLinkStatus($uuid) for each director
	* - if complete - update director status in 'aml_company_directors' and updpate overall status
	* - if overall status is 1 - trigger aml_company_directors webhook to complete flow, email admin
	*
	* @triggered by
	*
	* - WP-crontrol every 1 dat (was 1 hour) (see WP Crontrol for specifics)
	*
	* @status
	*
	* - turned OFF [rc] 30/09/2020 - to try and address expiring access tokens
	*
	*/
	add_action( 'aml_cron_idpal_get_progress_uuids', __NAMESPACE__ . '\\idpal_get_progress_uuids' );
	function idpal_get_progress_uuids () {
		$debug = false;
		/*$gflow_webhook = [
			'key' => App\themeSettings('theme_idpal_limited_webhook_key'),
			'secret' => App\themeSettings('theme_idpal_limited_webhook_secret'),
		]; // replaced with $response = GravityFlow\getWorkflowWebhookKeySecret(ENTRY_ID);*/
		$admin_email = get_option( 'admin_email' );
		$site_url = get_option( 'siteurl' );
		$clients = get_users([
		    'meta_key' => 'aml_company_directors'
		]);
		if ( !empty($clients) ) {
			foreach ($clients as $client) {
				/*
				*
				* Step 1
				* ------
				*
				* Has this client got directors packet and if so is it 'incomplete'? If 'incomplete' - loop through the directors inside, make a
				* call to IdPal\AppLinkStatus(uuid) to see what the status of their submissions (ie passport check, address check, likeless check etc) are. If
				* all their submissions are >4 ('CDD generated and all technical checks passed') then upload this user's status as 'complete' (1) and if all directors
				* in this packet are complete - update the directors packet status as 'complete' (1)
				*
				*/
				$directors = get_user_meta($client->ID, 'aml_company_directors', true);
				if ( !empty($directors) ) {
					if ( empty($directors['status']) ) {
						 // directors are incomplete
						$updated_directors = [
							'status' => 0, // default until proven
							'entry_id' => $directors['entry_id'],
							'members' => [],
						];
						$os=0;$i=0;foreach ($directors['members'] as $member) { // $os overall status
							$updated_director = [];
							$updated_director = $member;
							if ( !isset($member['overwritten']) ) {
								if ( empty($member['status']) ) {
									// director is incomplete
									// echo $member['appinstance'];
									// $args = [ 'submission_id' => '9112' ]; $result = IdPal\getCustomerInformation($args); Handy\I_Handy::tip($result);
									/*$args = [ 'submission_id' => '9500' ]; $result = IdPal\getCDDReport($args); // 4505
									if ( !empty($result['response']) ) {
										header('Content-Type: application/pdf'); die($result['response']);
									}*/
									// check ID-Pal see if director is now complete?
									// statuses passed into AppLinkStatus()
									// 0. No submission received
									// 1. Submission received and no errors in technical checks, but report not generated
									// 2. Submission received with errors in technical checks, and report not generated
									// 3. Report Flagged by user
									// 4. CDD generated and all technical checks passed
									// 5. CDD generated but some technical checks failed (i.e. manually over-ride was used).
									$args = [
										'days' => 25, // last X days - leaving blank shows 'all' submissions ever (too much)
										// 'status' => 0,
										//'uuid' => $member['appinstance'], // old 4.2.1 - depreciate eventually // 'ca08f1c1', // $member['appinstance']
										'uuid' => $member['uuid'], // new 4.5.0
									];
									$result = IdPal\AppLinkStatus($args); // Handy\I_Handy::tip($result); die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
									//error_log('user.php -> idpal_get_progress_uuids() | AppLinkStatus() | ' . print_r($args,1) . ' | result: ' . print_r($result,1) );
									//if( current_user_can('administrator') ) {
										// Handy\I_Handy::tip($result); // die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
									//}
									if ($result['status'] == 'success') { // we have results (there is a submission entry from $member['uuid'])
										$updated_director['submissions'] = ( !empty($updated_director['submissions'])?$updated_director['submissions']:[]); // remove older submissions and replace with latest
										$updated_director['status'] = 0; // status is 0 until proved
										if ( !empty($result['response']['submissions']) && is_array($result['response']['submissions']) ) { // to negate 'No submissions recieved' response
											//$submission_data = [];
											foreach ($result['response']['submissions'] as $submission) {
												/*if ($member['uuid'] == $submission['uuid']) { // new - $member['uuid'] == $submission['uuid']
													$tmp = [
														//'appinstance' => $submission['uuid'], // evntually depreciate for 'uuid'
														'uuid' => $submission['uuid'], // new 4.5.0
														'submission_id' => $submission['submission_id'],
														//'profile_id' => $submission['profile_id'], // new 4.5.0
														//'profile_name' => $submission['profile_name'], // new 4.5.0
														'status' => $submission['status'],
														//'account_id' => $submission['account_id'],
														//'document_type' => $submission['documents'][0]['document_type'],
														//'authentication_data' => $submission['documents'][0]['authentication_data'],
														//'facial_match' => $submission['documents'][0]['facial_match'],
														//'document_id' => $submission['documents'][0]['document_id'],
													];
													array_push($submission_data, $tmp);
													// store the $submission['submission_id']
												}*/
												if ($member['uuid'] == $submission['uuid']) { // make sure it's a submission into this app instance
													$updated_director['submissions'][$submission['submission_id']]['status'] = $submission['status'];
													//if ( isset($updated_director['submissions'][$submission['submission_id']]['submission_complete']) ) { 
														if ( $submission['status'] >=4 ) { // means the submission has been verified by an AO account manager - this is the real completion
															$updated_director['status'] = 1;  // set this members status to 1
															$updated_director['step'] = 2; // 2: docs submitted
															$updated_director['finished'] = time(); // not 100% accurate - within the time between CRON calls
															$os++; // add 1 to overall status dd
														}
													//}
												}
											}
											// Handy\I_Handy::tip($submission_data); die();
											/*if ( !empty($submission_data) ) {
												if ( count($submission_data) > 1 ) {
													// choose latest submission - occours in edge case where a user has submitted more than once with the same uuids
													$submission_data = [end($submission_data)]; // Handy\I_Handy::tip($submission_data);
												}
												$updated_director['submissions'] = $submission_data; // Handy\I_Handy::tip($submission_data);
												// die($latest_submission['status']);
												if ( $submission_data[0]['status'] >=4 ) {
													$updated_director['status'] = 1;  // set this members status to 1
													$updated_director['step'] = 2; // 2: docs submitted
													$updated_director['finished'] = time(); // not 100% accurate - within the time between CRON calls
													$os++; // add 1 to overall status dd
												}
												// consider overall director status (as can't seem to get a straight result) for their submissions (passport, address, likeness etc)
												//$ms=0;$j=0;foreach ($submission_data as $submission) {
												// 	if ( $submission['status'] >=4 ) $ms++; // member status
												//$j++;} echo "<p>$ms|$j</p>";
												//if ($ms==$j) {
												//	// director has passed all submissions
												//	$updated_director['status'] = 1; $os++; // add 1 to overall status
												//}
											}*/
										}
									}
									// Handy\I_Handy::tip($updated_director); die();
								} else {
									$os++; // uncomment for debugging // overall status // empty($member['status']) // this individual director is 'incomplete'
								}
							} else {
								 $os++; // member has been overwritten so just pass
							}
							array_push($updated_directors['members'], $updated_director);
						$i++;} // $directors['members'] as $member // for each director in this directors package
						// echo "$os|$i"; // are all directors complete? if the same - then yes
						// if ( $debug ) { if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { echo "$os|$i"; Handy\I_Handy::tip($updated_directors); }}
						if ($os==$i) { 
							$updated_directors['status'] = 1; 
						}
						update_user_meta( $client->ID, 'aml_company_directors', $updated_directors ); // store progress (for dashboard)
						// check overall status of 'aml_company_directors' and if complete - do follow up tasks
						if ( $updated_directors['status'] == 1 ) {
							// error_log('continue_workflow() called');
							continue_workflow( $client->ID );
						}
						/*if ( $updated_directors['status'] == 1 ) {
							$directors = get_user_meta($client->ID, 'aml_company_directors', true);
							if ( !empty($directors) ) {
								$updated_directors = $directors; // if we need to update anything
								// directors are all complete - so complete the flow
								if ( !empty($directors['status']) ) {
									//$info = [
									//	'entry_id' => $directors['entry_id'],
									//	'key' => $gflow_webhook['key'],
									//	'secret' => $gflow_webhook['secret'],
									//];
									$response = GravityFlow\incoming_webhook_endpoint_process($directors['entry_id']); // looking for 'complete'
									if ( $response == 'complete' ) { // debugging add 'true' | otherwise $response == 'complete'
										// Email administrator
										$business_name = get_user_meta($client->ID, 'aml_business_name', true);
										$fid = null; // App\themeSettings('theme_idpal_limited_fid');
										$mail_args = [
											'to' => $admin_email,
											'subject' => 'Directors ID-Pal ID/POA submission and verification complete',
											'body' => sprintf(
														"Administrator,\n\n All directors in directors package <a href='%1s' target='_blank'>entry</a> initiated by client <a href='mailto:%2s'>%3s %4s</a>%5s have now completed their ID-Pal ID/POA submission and verification. This client has been notified and can now continue with subsequent workflow checklists.",
														(!empty($fid)?admin_url('admin.php?page=gf_entries&view=entry&id='.$fid.'&lid='.$directors['entry_id']):''),
														$client->user_email,
														$client->first_name,
														$client->last_name,
														(!empty($business_name)?' ('.$business_name.')':'')
													),
										];
										$result = Comms\emailUser($mail_args);
										// Email user (first user in bundle) and tell them that flow can continue
										if ( !empty( $client->user_email ) ) {
											// could also use $updated_directors['members'][0]['email'] - this is assuming that the 'responsible' user is number 1 on the list which is probably not the case - possibly last
											$mail_args = [
												'to' => $client->user_email,
												'subject' => 'ID & POA Verification complete',
												'body' => sprintf(
													"Hi %1s,\n\nID verification is now complete for your business. Please proceed to complete your %2s.",
													$client->first_name,
													"<a href='$site_url/profile/checklists'>checklist</a>."
												),
											];
											$result = Comms\emailUser($mail_args);
										}
										// store
										$updated_directors['flow_unlocked'] = time(); update_user_meta( $client->ID, 'aml_company_directors', $updated_directors ); // Update the user so that procedure only happens once
									}
									// Handy\I_Handy::tip($response);
								} // !empty($updated_directors['status'])
							} // !empty($directors) // this client has a directors package
						}*/
					}  // empty($directors['status']) // directors package is incomplete
				} // !empty($directors) // this client has a directors package
			} // foreach ($clients as $client) {
		} // !empty($clients)
		// Handy\I_Handy::tip($site_master_history, '213.79.32.115');
		/*if ( !empty($gflow_webhook['key']) && !empty($gflow_webhook['secret']) ) {
			// get all clients with 'meta_key' => 'aml_company_directors'
		} else {
			error_log('Ican\Theme\User | queryIdPalStatus: webhook_key & webhook_secret empty.');
			Comms\emailUser(['to' => ['hello@hootfish.ie'], 'subject' => 'AO Debug', 'body' => 'Ican\Theme\User | queryIdPalStatus() : '.print_r($gflow_webhook,1)]);
		}*/
	}
	/*
	*
	* debugging CRON action 'aml_cron_idpal_get_progress_uuids' (below) - manually call for testing
	*
	*/
	// add_action('template_redirect', __NAMESPACE__ . '\\run_aml_cron_idpal_get_progress_uuids');
	function run_aml_cron_idpal_get_progress_uuids () {
		if( current_user_can('administrator') ) {
			do_action( 'aml_cron_idpal_get_progress_uuids' );
		}
	}
	/*
	* user related dashboard widgets
	*/
	add_action('wp_dashboard_setup', __NAMESPACE__ . '\\dashboard_widgets');
	function dashboard_widgets() {
		if ( current_user_can('administrator') || current_user_can('client_service_manager') || current_user_can('client_service_agent') ) { // if ( current_user_can('administrator') ) {
			global $wp_meta_boxes;
			wp_add_dashboard_widget('user_idpal_widget', 'ID Pal Activity', __NAMESPACE__ . '\\user_idpal_dashboard', __NAMESPACE__ . '\\user_idpal_configure');
		}
	}
	/*
	*
	* output incomplete director packages in dashboard
	*
	*/
	function user_idpal_dashboard() {
		$url_base_idpal = 'https://qf97.app.link/0gL69PUUZx';
		$error_codes = [
			'No submission received', // 0
			'No submission received and no errors in technical checks, but report not generated', // 1
			'Submission received with errors in technical checks, and report not generated', // 2
			'Report Flagged by user', // 3
			'CDD generated and all technical checks passed', // 4
			'CDD generated but some technical checks failed (e.g. manual over-ride was used)', // 5
		];
		$clients = get_users([
		    'meta_key' => 'aml_company_directors'
		]);
		// Handy\I_Handy::tip($clients);
		?>
		<div class="user_actions">
			<? if ( !empty($clients) ) { ?>
				<? $i=1; foreach ($clients as $client) { ?>
					<?
					$directors_package = get_user_meta($client->ID, 'aml_company_directors', true); // Handy\I_Handy::tip($directors_package);
					# (
					#    [status] => 1|0
					#    [entry_id] =>
					#    [members] =>
					#    			[0] =>
					#    				[email] => XXX@YYY.com
					#    				[status] => 1
					#    				[step] => 1
					#    				[started] => 1560172462
					#    				[updated] => 1560172462
					#    				[finished] => // [added when user is queried as being complete ]
					#    				[overwritten] => // [added when there's a manual overwrite via dashboard]
					#    				[uuid] => ca08f1c1
					#    				[submissions] =>
					#    					[0] =>
					#    						[uuid] => ca08f1c1
					#    						[submission_id] => 21896
					#    						[status] => 5
					#    						[document_type] => idcard
					#    						[authentication_data] => Fail|Pass
					#    						[facial_match] => Fail|Pass
					#    					[1]
					#    					[2]
					#    			[1]
					#    			[2]
					#    			..
					#  [flow_unlocked] => 1560186422
					#
					?>
					<? if ( empty($directors_package['status']) ) { ?>
						<? $business_name = get_user_meta($client->ID, 'aml_business_name', true); ?>
						<p><strong><?=$i?></strong>) Displaying incomplete ID Pal tasks for client <a href="<?=admin_url('user-edit.php?user_id='.$client->ID.'&wp_http_referer=%2Fwp%2Fwp-admin%2Fusers.php')?>" title="User ID: <?=$client->ID?> | Package: <?=print_r($directors_package,1)?>" target="_blank"><?=$client->user_email?></a><?=(!empty($business_name)?' ('.$business_name.')':'')?>:</p>
						<? if ( !empty($directors_package['members']) ) { ?>
							<div class="transaction" style="border:1px solid gray; padding: 10px; margin-bottom: 10px;">
								<ul>
								<? foreach ( $directors_package['members'] as $member ) { ?>
									<? if ( empty($member['status']) ) { ?>
										<? $url_unique_idpal = add_query_arg( 'uuid', $member['uuid'], $url_base_idpal ); // new $member['uuid'] ?>
										<li>
											<?
											echo sprintf("User <a href='mailto:%1s' title='%2s' target='_blank'>%3s</a>, started %4s%5s (AML package step <a href='#' title='%6s'>%7s</a>) has currently ID-Pal status %8s.",
												$member['email'].'?subject='.urlencode('ID Verification Outstanding').'&body=Please complete your ID Verification using your unique ID-Pal URL unique ID Pal URL '.$url_unique_idpal.'.',
												print_r($member,1),
												$member['email'],
												date('Y-m-d H:i:s', $member['started']),
												(!empty($member['updated'])?', updated '.date('Y-m-d H:i:s', $member['updated']):''),
												'0: AML package created, 1: link sent, 2: docs submitted',
												$member['step'],
												(!empty($member['submissions'])?'<a href="#" title="'.$error_codes[$member['submissions'][0]['status']].'">'.$member['submissions'][0]['status'].'</a>':'<a href="#" title="'.$error_codes[0].'">0</a>')
												// (!empty($member['submissions'])?'<a href="#" title="'.$error_codes[$member['submissions'][0]['status']].'">'.$member['submissions'][0]['status'].'</a>':'<a href="#" title="'.$error_codes[0].'">0</a>')
											);
											?>
										</li>
									<? } else { ?>
										<? // these users should be complete ?>
										<li><?=$member['email']?> is status <?=$member['status']?>.</li>
									<? } // empty($member['status']) ?>
								<? } // $directors_package['members'] as $member ?>
								</ul>
							</div>
						<? } // !empty($directors_package['members']) ?>
						<? $i++; ?>
					<? } // empty($directors_package['status']) ) ?>
				<? } // foreach ($clients as $client) ?>
			<? } else { ?>
				<p>There are currently no outstanding ÌD-Pal tasks.</p>
			<? } ?>
		</div> <!-- user_mandates -->
		<?
	}
	/*
	*
	* the 'Configure' view of user_idpal_dashboard()
	*
	* @docs:
	*
	* - https://www.wpexplorer.com/configurable-dashboard-widgets/
	*
	*
	* @php widget options
	*
	* - if ( !$user_reminders_widget_options = get_option( 'user_reminders_dashboard_widget_options' ) ) { $user_reminders_widget_options = array(); }
	* - $url_1 = $user_reminders_widget_options['url_1']; $url_val=(isset($url_1)?$url_1:'');
	* - <input class="widefat" id="user_reminders_url_1" name="user_reminders_widget[url_1]" type="text" value="$url_val" />
	*
	*/
	function user_idpal_configure ( $widget_id ) {
	    // handle submission
	    if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['user_idpal_configure_posted']) ) {
	    	// remove this users actions manually (** there's a big question whether we should be doing things like this**)
	    	if (empty($_POST['user_idpal_configure_client_id'])) die('No client ID passed');
	    	if (empty($_POST['user_idpal_configure_user_email'])) die('No user email passed');
	    	$client_id = $_POST['user_idpal_configure_client_id'];
	    	$user_email = $_POST['user_idpal_configure_user_email'];
	    	$submission_id = $_POST['user_idpal_configure_submission_id'];
    		// 1. mark user_email as complete in this clients directors package
    		$result = user_idpal_manually_complete($client_id, $user_email, $submission_id);
	    	if ( $result ) {
		    	// do other stuff
		    	$message = 'You have manually set client ' . $client_id . 's directors package user <strong>' . $user_email . '</strong> to status <strong>1</strong> (complete).';
		    	add_action( 'admin_notices', __NAMESPACE__ . '\\output_dashboard_widget_message', $message );
	    	}
	    }
		$clients = get_users([
		    'meta_key' => 'aml_company_directors'
		]);
	    ?>
	    <p>Please <a href="#" title="This action should only be taken if theres a valid reason why this user couldn't complete their ID & POA Verification through ID-Pal themselves. If you understand the risks, choose the client and the checklist you want to manually complete.">hover here</a> so that you understand the
	    implications of manually overriding the ID-Pal process. Open a new <a href="<?=admin_url()?>">dashboard</a> tab to copy correct client <-> user emails.</p>
	    <p>
	        <label for="user_idpal_configure_client_id"><? _e('Client / Business (Package Owner):', 'sage'); ?></label>
	        <? if ( !empty($clients) ) { ?>
	        	<select class="widefat" id="user_idpal_configure_client_id" name="user_idpal_configure_client_id">
	        		<option value="">Select</option>
		        	<? $i=1; foreach ( $clients as $client ) { ?>
		        		<? $directors_package = get_user_meta($client->ID, 'aml_company_directors', true); // Handy\I_Handy::tip($directors_package); ?>
		        		<? $business_name = get_user_meta($client->ID, 'aml_business_name', true); ?>
		        		<? if ( empty($directors_package['status']) ) { ?>
		        			<option value="<?=$client->ID?>"><?=$client->user_email?><?=(!empty($business_name)?' ('.$business_name.')':'')?></option>
		        		<? } ?>
		        	<? $i++; } // $clients as $client ?>
	        	</select>
	        <? } ?>
	        <input name="user_idpal_configure_posted" type="hidden" value="1" />
	    </p>
	    <p>
	    	<label for="user_idpal_configure_user_email"><? _e('User Email:', 'sage'); ?></label>
	    	<input class="widefat" type="text" name="user_idpal_configure_user_email" placeholder="Email address" value="" />
	    </p>
	    <p>
	    	<label for="user_idpal_configure_submission_id"><? _e('Submission ID:', 'sage'); ?></label>
	    	<input class="widefat" type="text" name="user_idpal_configure_submission_id" placeholder="" value="" />
	    	<small>Add the submission ID for this user if they did not use the UUID link provided to them by AO. You'll find
	    	the submission ID in the URL bar in <a href="https://client.id-pal.com" target="_blank">ID-Pal</a> when you click on the user.</small>
	    </p>
	    <p>Upon submission, the <strong>ID-Pal status</strong> for this user of this package will be manually hard set to '1' (complete). The CRON 'aml_cron_idpal_get_progress_uuids' will
	    then be automatically run to clear this user off this incomplete list. If you've added a submission ID - this is a softer override in that the new submission ID will be queried first and
	    this user updated with the new submission ID's UUID - therefore when the CRON runs - this user will be updated with the new submission IDs details. </p>
	    <?
	}
	/*
	*
	* manually complete a user in an ID-Pal directors package
	*
	* @called:
	*
	*  - from dasboard widget - user_idpal_configure()
	*
	*/
	function user_idpal_manually_complete ( $client_id, $user_email, $submission_id ) {
		if (empty($client_id)) return false;
		if (empty($user_email)) return false;
		if ( is_user_logged_in() ) {
			$admin_email = get_option( 'admin_email' );
    		$current_user = wp_get_current_user();  // Handy\I_Handy::tip($current_user); die();
    		$user_roles = (array) $current_user->roles;
    		if ( in_array( 'administrator', $user_roles ) ) {
				$directors_package = get_user_meta($client_id, 'aml_company_directors', true);
				$updated_directors_package = $directors_package; // copy initally
				if (!empty($directors_package)) {
					$debugging_message = '<b>Before</b>' . print_r($directors_package, 1); // Handy\I_Handy::tip($directors_package); die();
					$i=0;foreach ( $directors_package['members'] as $member ) {
						if ( $member['email'] == $user_email ) {
							if ( empty($member['status']) ) { // just check - but always should be 0
								if ( !empty($submission_id ) ) {
									// softer overide
									$updated_directors_package['members'][$i]['note'] = 'Submission ID ' . $submission_id . ' manually added ' . date('d-m-Y H:i:s') . ' by ' . $current_user->ID;
									$result = IdPal\getSubmissionsStatus(['submission_id' => $submission_id]); // Handy\I_Handy::tip($result); die(); // example - http://prntscr.com/nzzo82
									if ( $result['status'] == 'success' ) {
										if ( !empty($result['response']['submissions']) ) {
											// update the users UUID from result (and allow hourly CRON to query)
											if ( !empty($result['response']['submissions'][0]['uuid']) ) { // assuming that we're taken the FIRST submission - but their could be more?
												$updated_directors_package['members'][$i]['note'] .= ' which replaced uuid ' . (!empty($member['uuid'])?$member['uuid']:'N/A') . '.';
												//$updated_directors_package['members'][$i]['appinstance'] = $result['response']['submissions'][0]['uuid']; // need to depreciate this for 'uuid' below
												$updated_directors_package['members'][$i]['uuid'] = $result['response']['submissions'][0]['uuid'];
											} else {
												$updated_directors_package['members'][$i]['note'] .= ' but IDPal/getSubmissionsStatus() - [uuid] not found.';
											}
										} else {
											$updated_directors_package['members'][$i]['note'] .= ' but IDPal/getSubmissionsStatus() returned an error of status ' . $result['response']['status'];
										}
									}
								} else {
									// just manually override
									$updated_directors_package['members'][$i]['status'] = 1; // set this users status to 1
									$updated_directors_package['members'][$i]['overwritten'] = time(); // add a timestamp for when this was manually overwritten
								}
							}
						}
					$i++;}
					update_user_meta($client_id, 'aml_company_directors', $updated_directors_package); $debugging_message .= '<b>Middle</b>' . print_r($updated_directors_package, 1);
					do_action( 'aml_cron_idpal_get_progress_uuids' ); // run the cron
					// post cron - just do a debug
					/*$directors_package = get_user_meta($client_id, 'aml_company_directors', true);
					$debugging_message .= '<b>After</b>' . print_r($directors_package, 1);
					Comms\emailUser(['to' => 'hello@hootfish.ie', 'subject' => '[Type: Info] ID Pal', 'body' => '[user.php -> user_idpal_manually_complete()] | trace: ' . $debugging_message]);*/
					// finally return
					return true;
				}
			}
		}
		return false;
	}
	/*
	*
	* populate_proof - is this used anywhere? Form 43 is 'Verified ID & Proof Of Address' (old as now use ID Pal)
	*
	*/
	//add_filter( "gform_pre_render_43", __NAMESPACE__ . '\\populate_proof' );
	//add_filter( "gform_pre_validation_43", __NAMESPACE__ . '\\populate_proof' );
	//add_filter( "gform_pre_submission_filter_43", __NAMESPACE__ . '\\populate_proof' );
	//add_filter( "gform_admin_pre_render_43", __NAMESPACE__ . '\\populate_proof' );
	function populate_proof ( $form ) {
		$directors = [];
    	if ( is_user_logged_in() ) {
    		$current_user = wp_get_current_user();
    		if ( in_array( 'client', (array) $current_user->roles ) ) {
    			$directors =  get_user_meta( $current_user->ID, 'aml_company_directors', true );
    		}
    	}
    	// Handy\I_Handy::tip($$directors);
		foreach ( $form['fields'] as &$field ) {
			// look for 'populate-proof' css class and 'select' field
			if (strpos($field['cssClass'], 'populate-proof') === false) continue;
			$choices = [];
			if ( !empty($directors) ) {
				foreach( $directors as $director ) {
					$choices[] = array('text' => $director, 'value' => $director);
				}
			}
	        // update 'Select a Post' to whatever you'd like the instructive option to be
	        $field->placeholder = 'Select Director';
	        $field->choices = $choices;
		}
		return $form;
	}
	/*
	*
	* @triggered by
	*
	* - response from ID Pal (webhook feature - see https://client.id-pal.com/push-api) to a complete submission
	*
	* @webhook
	*
	* - Production: https://accountantonline.ie/wp-admin/admin-post.php?action=idpal_webhook_response
	* - Staging: https://acntonline.staging.wpengine.com/wp-admin/admin-post.php?action=idpal_webhook_response
	* - Local: http://accountantonline.local:8080/wp/wp-admin/admin-post.php?action=idpal_webhook_response
	*
	* @response
	*
	* sign Key: p%s77OL%oc6zjuRFNZ4U7#XVRJ4%@R5*%ZoB4E5syW*tvv7d1oRMlNfCRbYyPr
	*
	* @docs
	*
	* - https://codex.wordpress.org/Plugin_API/Action_Reference/admin_post_(action)
	* - see https://www.sitepoint.com/handling-post-requests-the-wordpress-way/ for admin-post.php anatomy
	*
	* @response
	*
	* see https://prnt.sc/jtplh5
	*
	* {
	* "event_id": 123,
	* "event_type": “submission_complete”, "uuid ": "abcd1234",
	* "submission_id": 3456
	* }
	*
	*/
	add_action( 'admin_post_idpal_webhook_response', __NAMESPACE__ . '\\process_idpal_webhook_response' );
	add_action( 'admin_post_nopriv_idpal_webhook_response', __NAMESPACE__ . '\\process_idpal_webhook_response' );
	function process_idpal_webhook_response () {
		error_log( 'process_idpal_webhook_response() called' );
	    status_header(200); // return back to referrer
	    $response = file_get_contents('php://input');
	    // mailPostData(); // debugging
	    //error_log( 'process_idpal_webhook_response() | response: ' . print_r($response, 1));
	    if ( !empty($response) ) {
	    	$data = json_decode( $response, true ); // Handy\I_Handy::tip($data);die();
			error_log( 'process_idpal_webhook_response() | data: ' . print_r($data,1)  );
			//wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> process_idpal_webhook_response() ] | data: ' . print_r($data,1) );
		    //$data['event_id']; // 678425
		    //$data['event_type']; // [new_submission_start|submission_complete]
		    //$data['uuid']; // 1ad67848
		    //$data['submission_id']; // 169119
		    //$data['source']; // mobileapp
			if ( !empty($data['event_type']) ) { // !empty($data['resourceReference'])
		    	/*if ( $data['event_type'] == 'new_submission_start' ) {
		    		error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type']); 
		    	} elseif ( $data['event_type'] == 'submission_expired' ) { 
		    		error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type']); 
		    	} elseif ( $data['event_type'] == 'submission_complete' ) { 
		    		error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type']); 
		    	} elseif ( $data['event_type'] == 'ping' ) { 
		    		error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type']); 
		    	}*/
		    	//wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> process_idpal_webhook_response() ] | event_type: ' . $data['event_type']);
	    		//mailPostData(); // die('sdf'); // debugging
	    		//error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type']); 
				if (!empty($data['uuid'])) {
					$client_id = get_client_by_director_uuid($data['uuid']);
					if (!empty($client_id)) {
	    				error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type'] . ', client ID ( ' . $client_id . ') found for UUID ' . $data['uuid']); 
						process_directors_package ($client_id, $data);
						// Handy\I_Handy::tip($package);
					} else {
	    				error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type'] . ', client ID not found for UUID ' . $data['uuid']); 
					}// !empty($client_id)
				} // !empty($data['uuid'])
			} // !empty($data['resourceReference'])
	    } // !empty($response)
	    die('<p>Nothing else to do here!</p>');  // request handlers should die() when they complete their task
	}
	/*
	*
	* get a director in a directors package ('aml_company_directors') given some identifier
	*
	* @params
	*
	* - $identifier [uuid | submission_id]
	* - $value
	*
	*/
	function get_client_by_director_uuid ( $uuid ) {
		if (empty($uuid)) return;
		$clients = get_users([
			'meta_key' => 'aml_company_directors'
		]);
		if ( !empty($clients) ) {
			foreach ($clients as $client) {
				$directors = get_user_meta($client->ID, 'aml_company_directors', true);
				if ( !empty($directors) ) {
					if ( empty($directors['status']) ) {
						// directors package is incomplete 
						foreach ($directors['members'] as $member) {
							$updated_director = [];
							$updated_director = $member;
							if ( !isset($member['overwritten']) ) {
								if ( empty($member['status']) ) {
									// director is incomplete
									if ( $member['uuid'] == $uuid ) {
										//Handy\I_Handy::tip($member);
										return $client->ID;
									}
								}
							} // !isset($member['overwritten'])
						} // $directors['members'] as $member
					} // empty($directors['status'])
				} // !empty($directors)
			} // $clients as $client
		} // !empty($clients)
	}
	/*
	*
	*
	* @params
	*
	* - $client_id [user_id]
	* - $uuid [director submission]
	* - $data [
	* - event_type [new_submission_start|submission_complete]
	* - uuid
	* - submission_id
	* - source
	* - ]
	*/
	function process_directors_package ( $client_id, $data ) {
		error_log('process_directors_package() called | client ID: ' . $client_id . ', data: ' . print_r($data,1) );
		if (empty($client_id)) return;
		if (empty($data['uuid'])) return;
		if (empty($data['event_type'])) return;
		$debug = true;
		$debug_ips = Debug\get_debug_ips();
		$directors = get_user_meta($client_id, 'aml_company_directors', true);
		if ( !empty($directors) ) {
			if ( empty($directors['status']) ) {
				// directors package is incomplete
				$updated_directors = [
					'status' => 0, // default until proven
					'entry_id' => $directors['entry_id'],
					'members' => [],
				];
				$os=0;$i=0;foreach ($directors['members'] as $member) { // $os overall status
					$updated_director = [];
					$updated_director = $member;
					if ( !isset($member['overwritten']) ) {
						if ( empty($member['status']) ) {
							// member is NOT complete
							error_log('compare ' . $member['uuid'] . ' | ' . $data['uuid']);
							// here's the difference (between CRON and webhook versions) - we're not checking ALL clients - we're just checking this one
							if ( $data['uuid'] == $member['uuid'] ) {
								$updated_director['submissions'][$data['submission_id']][$data['event_type']] = time();
								// wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> process_directors_package()] | client ' . $client_id . ' - member uuid ' . $data['uuid'] . ' ' . $data['event_type']);
								if ( $data['event_type'] == 'submission_complete' ) {
									$updated_director['status'] = 1;  // set this members status to 1
									$updated_director['step'] = 2; // 2: docs submitted
									$updated_director['finished'] = time(); // not 100% accurate - within the time between CRON calls
									$os++; // add 1 to overall status dd
								}
							} // $member['uuid'] == $data['uuid']
						} else {
							// member IS complete
							$os++; // uncomment for debugging // overall status // empty($member['status']
						}
					} else {
						// member has been overwritten so just pass
						$os++;
					}
					array_push($updated_directors['members'], $updated_director);
				$i++;} // $directors['members'] as $member // for each director in this directors package
				// echo "$os|$i"; // are all directors complete? if the same - then yes
				if ( $debug ) { if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { echo "<p>$os|$i</p>"; Handy\I_Handy::tip($updated_directors); }}
				if ( $os==$i ) { 
					$updated_directors['status'] = 1; 
				}
				// wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> process_directors_package()] | client ' . $client_id . ' <code>' . print_r($updated_directors, 1) . '</code>' );
				$result = update_user_meta( $client_id, 'aml_company_directors', $updated_directors );
				// check overall status of 'aml_company_directors' and if complete - do follow up tasks
				if ( $updated_directors['status'] == 1 ) {
					// error_log('continue_workflow() called');
					continue_workflow( $client_id );
				}
			} // empty($directors['status'])
		} // !empty($directors)
	}
	/*
	*
	* Is this clients package now 'complete'? If complete - make a call to the gravity flow webhook to complete this step then
	* email the primary contact of this packet (assumed to be the first) to notify them that they can now continue their workflow. Email AO admin to notify them
	* that a packet has been completed.
	*
	*/
	function continue_workflow ( $client_id ) {
		if (empty($client_id)) return;
		$debug = false;
		// wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> continue_workflow()] | client ' . $client_id . ' client package member overall completed.');
		$debug_ips = Debug\get_debug_ips();
		$directors = get_user_meta($client_id, 'aml_company_directors', true);
		if ( $debug ) { if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { Handy\I_Handy::tip($directors); die('DEBUGGING -- continue_workflow() -- DEBUGGING');}}
		if ( !empty($directors) ) {
			$updated_directors = $directors; // if we need to update anything
			// directors are all complete - so complete the flow
			if ( !empty($directors['status']) ) {
				/*$info = [
					'entry_id' => $directors['entry_id'],
					'key' => $gflow_webhook['key'],
					'secret' => $gflow_webhook['secret'],
				];*/
				$response = GravityFlow\incoming_webhook_endpoint_process($directors['entry_id']); // looking for 'complete'
				if ( $response == 'complete' ) { // debugging add 'true' | otherwise $response == 'complete'
					// Email administrator
					$business_name = get_user_meta($client->ID, 'aml_business_name', true);
					$fid = null; // App\themeSettings('theme_idpal_limited_fid');
					$mail_args = [
						'to' => $admin_email,
						'headers' => [
							'Content-Type' => 'text/html; charset=UTF-8',
							'Cc: Richard Cantwell <hello@hootfish.ie>', // debugging
						],
						'subject' => 'Directors ID-Pal ID/POA submission and verification complete',
						'body' => sprintf(
									"Administrator,<br><br> All directors in directors package <a href='%1s' target='_blank'>entry</a> initiated by client <a href='mailto:%2s'>%3s %4s</a>%5s have now completed their ID-Pal ID/POA submission and verification. This client has been notified and can now continue with subsequent workflow checklists.",
									(!empty($fid)?admin_url('admin.php?page=gf_entries&view=entry&id='.$fid.'&lid='.$directors['entry_id']):''),
									$client->user_email,
									$client->first_name,
									$client->last_name,
									(!empty($business_name)?' ('.$business_name.')':'')
								),
					];
					$result = Comms\emailUser($mail_args);
					// Email user (first user in bundle) and tell them that flow can continue
					if ( !empty( $client->user_email ) ) {
						// could also use $updated_directors['members'][0]['email'] - this is assuming that the 'responsible' user is number 1 on the list which is probably not the case - possibly last
						$mail_args = [
							'to' => $client->user_email,
							'headers' => [
								'Content-Type' => 'text/html; charset=UTF-8',
								'Cc: Richard Cantwell <hello@hootfish.ie>', // debugging
							],
							'subject' => 'ID & POA Verification complete',
							'body' => sprintf(
								"Hi %1s,<br><br>ID verification is now complete for your business. Please proceed to complete your %2s.",
								$client->first_name,
								"<a href='$site_url/profile/checklists'>checklist</a>."
							),
						];
						$result = Comms\emailUser($mail_args);
					}
					// store
					$updated_directors['flow_unlocked'] = time(); update_user_meta( $client->ID, 'aml_company_directors', $updated_directors ); // Update the user so that procedure only happens once
				}
				// Handy\I_Handy::tip($response);
			} // !empty($updated_directors['status'])
		} // !empty($directors) // this client has a directors package
	}
	/*
	*
	* debugging
	*
	*/
	// add_action( 'template_redirect', __NAMESPACE__ . '\\output_debugging', 11, 1 );
	function output_debugging () {
		$debug_ips = Debug\get_debug_ips();
		$uuid = '40096ef8';
		if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) {
			$client_id = get_client_by_director_uuid($uuid);
			if (!empty($client_id)) {
				echo 'Client is ' . $client_id;
				// [new_submission_start|submission_complete]
				$data = [
					'uuid' => $uuid,
					'event_type' => 'submission_complete',
				];
				process_directors_package ($client_id, $data);
				// Handy\I_Handy::tip($package);
			}
			die('DEBUGGING');
		}
	}
	/*
	*
	* testing script to post payload to https://acntonline.staging.wpengine.com/wp-admin/admin-post.php?action=idpal_webhook_response
	* to test if POST data is being received or if theres some block on it being received (perhaps WP Engine?)?
	*
	* @useage
	*
	* - load any page (** careful with load - make sure to remove)
	*
	*  @webhooks
	*
	* - https://accountantonline.ie/wp-admin/admin-post.php?action=idpal_webhook_response
	* - https://acntonline.staging.wpengine.com/wp-admin/admin-post.php?action=idpal_webhook_response
	* - http://accountantonline.local:8080/wp/wp-admin/admin-post.php?action=idpal_webhook_response
	*
	*/
	// add_action( 'init', __NAMESPACE__ . '\\sendDummyPostData' );  // ** careful - should only be on on LOCAL
	function sendDummyPostData () {
		$ch = curl_init();
		$webhooks = [
			'production' => 'https://accountantonline.ie/wp-admin/admin-post.php?action=idpal_webhook_response',
			'staging' => 'https://acntonline.staging.wpengine.com/wp-admin/admin-post.php?action=idpal_webhook_response',
			'local' => 'http://accountantonline.local:8080/wp/wp-admin/admin-post.php?action=idpal_webhook_response',
		];
		$url = $webhooks['production']; // which environement to send POST to
		// dummy data to POST
		$data = [
			'event_id' => 123,
			'event_type' => 'submission_complete',
			'uuid' => 'abcd1234',
			'submission_id' => 3456,
		];
		// url-ify the data for the POST
		$data_string = '';
		foreach($data as $key=>$value) { $data_string .= $key.'='.$value.'&'; }
		$data_string = rtrim($data_string, '&');
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, count($data));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // receive server response ...
		//if ( $url == $webhooks['staging'] ) {
		//	curl_setopt($ch, CURLOPT_USERPWD, "acntonline:0d8bf4e3"); // for staging testing - by pass password protection
		//}
		$response = curl_exec($ch); // Handy\I_Handy::tip($response);
		if ( curl_error($ch) ) {
		    $error_msg = curl_error($ch);
		    error_log( 'sendDummyPostData() | response | error:  ' . $error_msg );
			//Handy\I_Handy::tip($response);die('asd');
		} else {
			// $info = curl_getinfo($ch);
			error_log( 'sendDummyPostData() | response | success ' . print_r($response, 1) );
		    //Handy\I_Handy::tip($response);die('qads');
		}
		curl_close ($ch);
	}
	// helper function to get origin of request
	function get_referer_origin () {
		$origin = '';
		if (array_key_exists('HTTP_ORIGIN', $_SERVER)) {
		    $origin = $_SERVER['HTTP_ORIGIN'];
		} else if (array_key_exists('HTTP_REFERER', $_SERVER)) {
		    $origin = $_SERVER['HTTP_REFERER'];
		} else {
		    $origin = $_SERVER['REMOTE_ADDR'];
		}
		return $origin;
	}
	/*
	*
	* debug POST data and email hello@
	*
	* see https://perishablepress.com/protect-post-requests/
	*
	*/
	function mailPostData () {
		$email = 'hello@hootfish.ie'; // 'hello@hootfish.ie'
		$subject = 'Testing getPostData requests';
		$remote_ip = $_SERVER['REMOTE_ADDR'];
		$remote_host = $_SERVER["REMOTE_HOST"];
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$method = $_SERVER['REQUEST_METHOD'];
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		$origin = get_referer_origin();
		$post_vars = file_get_contents('php://input'); // actual post data
		// start output
		$message = 'IP: ' . $remote_ip . "<br>";
		$message .= 'Origin: ' . $origin . "<br>";
		$message .= 'Host: ' . $remote_host . "<br>";
		$message .= 'User Agent: ' . $user_agent . "<br>";
		$message .= 'Method: ' . $method . "<br>";
		$message .= 'Protocol: ' . $protocol . "<br>";
		$message .= 'POST Vars: ' . $post_vars . "<br>";
		if ( $method == 'POST' ) {
			mail($email, $subject, $message);
		}
		error_log('mailPostData|'.$message);
	}
