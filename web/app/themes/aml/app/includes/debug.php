<?php

	namespace Custom\Debug;

	use Custom\Classes\Handy;
	use Custom\Debug;

	// use Custom\Config;

    use Custom\Package\IdPal;
    use Custom\User\IdPal as IdPalUser;
	use Custom\User\Comms;

	/*
	*
	* return debug IPs
	*
	*/
	function get_debug_ips () {
		$ips = [
			'176.61.5.209', // rich hollywoodrath
			'195.70.88.106', // dragon co working
		];
		return $ips;
	}
	/*
	*
	*
	*
	* @docs
	*
	* - https://premium.wpmudev.org/blog/creating-wordpress-admin-pages/
	*
	*/
	add_action( 'admin_menu', __NAMESPACE__ . '\\my_admin_menu' );
	function my_admin_menu() {
		add_menu_page(
			'AO Debugger', // page title
			'Debug', // menu title
			'manage_options', // permissions required to access
			'theme-options', // slug
			__NAMESPACE__ . '\\debug_output_admin_page', // function for page output
			'dashicons-admin-tools', // menu icon
			55, // menu position
		);
		// ID-Pal
		add_submenu_page(
			'theme-options', // parent slug
			'ID-Pal Debugging', // page title
			'ID-Pal', // menu label
			'manage_options', // permissions required to access
			'debug-idpal-output', // child slug
			__NAMESPACE__ . '\\debug_output_admin_page_idpal' // function for page output
		);
		/*// XXX
		add_submenu_page(
			'theme-options', // parent slug
			'XXX Debugging', // page title
			'XXX', // menu label
			'manage_options', // permissions required to access
			'debug-xxx-output', // child slug
			__NAMESPACE__ . '\\debug_output_admin_page_gravityflow' // function for page output
		);*/
	}
	/*
	*
	* Parent output
	*
	*
	*/
	function debug_output_admin_page(){
		?>
		<div class="wrap">
			<h2>Accountant Online Debugger</h2>
		</div>
		<?php
	}
	/*
	*
	* ID-Pal output
	*
	*
	*/
	function debug_output_admin_page_idpal(){
		$api_doc_latest = 'https://client.id-pal.com/Api_WebServicesProgrammersGuide.pdf';
		$api_version_latest = '3.0.0';
		?>
		<div class="wrap">
			<h2>ID Pal debugger</h2>
			<p>Offical API socumentation - view <a href="<?=$api_doc_latest?>" target="_blank"><?=$api_version_latest?></a></p>
			<div class="output">
				<h3>debug_authentication_tokens()</h3>
				<?
				$result = IdPal\debug_authentication_tokens(); // Handy\I_Handy::tip($result); die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
				if ( !empty($result) ) {
					Handy\I_Handy::tip($result);
				}
				?>
				<?
				/*// statuses passed into getSubmissionsStatus()
				// 0. No submission received
				// 1. Submission received and no errors in technical checks, but report not generated
				// 2. Submission received with errors in technical checks, and report not generated
				// 3. Report Flagged by user
				// 4. CDD generated and all technical checks passed
				// 5. CDD generated but some technical checks failed (i.e. manually over-ride was used).
				$args = [
					'days' => 15, // last X days - leaving blank shows 'all' submissions ever (too much)
					//'status' => 1,
					'uuid' => 'c6149843', // 'ca08f1c1', // $member['appinstance']
				];
				?>
				<h3>AppLinkStatus()</h3>
				<p>Query ID-Pal API function<?=($args['days']?' set to query all data in the past ' . $args['days'] . ' days ':'')?><?=($args['status']?' with user status set to ' . $args['status']:'.')?>.</p>
				<?
				$result = IdPal\AppLinkStatus($args); // Handy\I_Handy::tip($result); die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
				if ( !empty($result) ) {
					Handy\I_Handy::tip($result);
				}
				*/
				?>
				<h3>getSubmissionsStatus()</h3>
				<?
				// statuses passed into getSubmissionsStatus()
				// 0. No submission received
				// 1. Submission received and no errors in technical checks, but report not generated
				// 2. Submission received with errors in technical checks, and report not generated
				// 3. Report Flagged by user
				// 4. CDD generated and all technical checks passed
				// 5. CDD generated but some technical checks failed (i.e. manually over-ride was used).
				$args = [
					// 'days' => 5, // last X days - leaving blank shows 'all' submissions ever (too much)
					//'status' => 1,
					'submission_id' => 432907,
				];
				?>
				<p>Query ID-Pal API function for submission ID <strong><?=$args['submission_id']?></strong> <?=($args['days']?' days set to query all data in the past ' . $args['days'] . ' days ':'')?><?=($args['status']?' with user status set to ' . $args['status']:'.')?>.</p>
				<?
				$result = IdPal\getSubmissionsStatus($args); // Handy\I_Handy::tip($result); die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
				if ( !empty($result) ) {
					Handy\I_Handy::tip($result);
				}
				?>
				<h3>AppLinkStatus()</h3>
				<?
				$args = [
					// 'days' => 25, // last X days - leaving blank shows 'all' submissions ever (too much)
					// 'status' => 0,
					'uuid' => 'dba36020',
				];
				?>
				<p>Query ID-Pal API function<?=($args['days']?' set to query all data in the past ' . $args['days'] . ' days ':'')?><?=($args['uuid']?' with user uuid set to ' . $args['uuid']:'.')?><?=($args['status']?' with user status set to ' . $args['status']:'.')?>.</p>
				<?
				$result = IdPal\AppLinkStatus($args);
				if ( !empty($result) ) {
					Handy\I_Handy::tip($result);
				}
				?>
				<h3>Test</h3>
				<?
				/*$clients = get_users([
					'meta_key' => 'ao_company_directors'
				]);
				if ( !empty($clients) ) {
					$uuid = '6ccd0d5a'; // change this
					foreach ($clients as $client) {
						$directors = get_user_meta($client->ID, 'ao_company_directors', true);
						if ( !empty($directors) ) {
							if ( empty($directors['status']) ) {
								// Handy\I_Handy::tip($directors);
								// directors package is incomplete 
								foreach ($directors['members'] as $member) {
									Handy\I_Handy::tip($member);
									$updated_director = [];
									$updated_director = $member;
									if ( !isset($member['overwritten']) ) {
										if ( empty($member['status']) ) {
											// director is incomplete
											echo '<p>uuid=|' . $member['uuid'].'|</p>';
											if ( $member['uuid'] == $uuid ) {
												//Handy\I_Handy::tip($member);
												echo '<p>Client is |' . $client->ID . '|</p>';
											}
										}
									} // !isset($member['overwritten'])
								} // $directors['members'] as $member
							} // empty($directors['status'])
						} // !empty($directors)
					} // $clients as $client
				} // !empty($clients)*/
				?>
			</div> <!-- output -->
			<? /*<div class="output">
				<h3>Clients</h3>
				<?
				$clients = get_users([
				    'meta_key' => 'ao_company_directors'
				]);
				// Handy\I_Handy::tip($clients);
				if ( !empty($clients) ) {
					$i=1; foreach ($clients as $client) {
						$directors_package = get_user_meta($client->ID, 'ao_company_directors', true); // Handy\I_Handy::tip($directors_package);
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
						#    				[appinstance] => ca08f1c1
						#    				[submissions] =>
						#    								[0] =>
						#    									[appinstance] => ca08f1c1
						#    									[submission_id] => 21896
						#    									[status] => 5
						#    									[document_type] => idcard
						#    									[authentication_data] => Fail|Pass
						#    									[facial_match] => Fail|Pass
						#    			[1]
						#    			[2]
						#    			..
						#  [flow_unlocked] => 1560186422
						#
						Handy\I_Handy::tip($directors_package);
					} // foreach ($clients as $client)
				}
				?>
			</div> <!-- output --> */ ?>
		</div> <!-- wrap -->
		<?
	}
	/*
	*
	* Gravity Flow
	*
	*
	*/
	function debug_output_admin_page_gravityflow(){
		?>
		<div class="wrap">
			<h2>Gravity Flow debugger</h2>
			<p></p>
			<div class="output">
				<h3>GravityFlow\getWorkflowWebhookKeySecret()</h3>
				<?
				// $test_entry = 25554; $response = GravityFlow\getWorkflowWebhookKeySecret($test_entry); Handy\I_Handy::tip($response); die();
				?>
				<h3>GravityFlow\incoming_webhook_endpoint_process</h3>
				<?
				// $test_entry = 25612; $response = GravityFlow\incoming_webhook_endpoint_process($test_entry); Handy\I_Handy::tip($response); // if ( $response == 'complete') {}
				?>
			</div> <!-- output -->
		</div> <!-- wrap -->
		<?
	}
	/*
	*
	*
	*
	*
	*/
	/*
	*
	* test anything
	*
	*/
	add_action('template_redirect', __NAMESPACE__ . '\\test_anything');
	function test_anything () {		
		//	IdPalUser\translate_idpal_status(1);
		/*$mail_args = [
			'to' => 'hello@hootfish.ie',
			'headers' => [
				'Content-Type' => 'text/html; charset=UTF-8',
				'Cc' => 'liverpoolrc@yahoo.com, liverpoolrc@gmail.com',
			],
			'subject' => 'Test email from AML',
			'body' => sprintf(
				"Hi,
				<br /><br />This is a %1s",
				'test'
			),
		];
		//Handy\I_Handy::tip($mail_args); die();
		$email_sent = Comms\emailUser($mail_args); //Handy\I_Handy::tip($email_sent); die();*/

		/*$uuid = 'c8297d9';
		$args = [
			'days' => 25, // last X days - leaving blank shows 'all' submissions ever (too much)
			// 'status' => 0,
			//'uuid' => $member['appinstance'], // old 4.2.1 - depreciate eventually // 'ca08f1c1', // $member['appinstance']
			'uuid' => $uuid, // new 4.5.0
		];
		$result = IdPal\AppLinkStatus($args); // Handy\I_Handy::tip($result); die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
		//error_log('user.php -> idpal_get_progress_uuids() | AppLinkStatus() | ' . print_r($args,1) . ' | result: ' . print_r($result,1) );
		//if( current_user_can('administrator') ) {
		// Handy\I_Handy::tip($result); // die(); // see http://prntscr.com/nzzo82 // 9505 | 064a7bc4 |
		//}
		if ($result['status'] == 'success') { 
			Handy\I_Handy::tip($result); die();
		}*/
		//$uuid = 'cd3e20b1';
		//$client = IdPalUser\get_client_by_uuid($uuid);
		//Handy\I_Handy::tip($client); die('DEBUG');
		$client_id = 49;
		//$client = get_user_by( 'id', $client_id );
		//Handy\I_Handy::tip($client); die('DEBUG');
		//IdPalUser\tidy_up ( $client_id ); die('DEBUG');
	}
	/*
	*
	*
	*
	*
	*/
