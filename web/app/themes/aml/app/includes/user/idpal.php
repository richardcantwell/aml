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
	* submit a user to ID Pal
	*
	* @called:
	*
	* - when 'begin' button is clicked
	*
	*/
	function submit_to_idpal ($user_id, $emails, $args = []) {
		// 
		if ( empty($user_id) ) return;
		$debug_ips = Debug\get_debug_ips(); // if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) {}
		// error_log('[user.php -> create_contact_package()] emails: ' . print_r($emails,1));
		// Handy\I_Handy::tip($emails); die();
		$admin_email = get_option( 'admin_email' );
		$site_title = get_bloginfo( 'name' );
		$site_url = get_bloginfo( 'url' );
		$url_base_idpal = get_field('base_url', 'option');
		$manager = get_field('manager', 'option'); // ID Pal Companion 'manager'
		$contacts = [
			'status' => 0,
			'entry_id' => (!empty($args['entry_id'])?$entry['id']:null), // if this is form submission record the entry
			'members' => [],
		];
		foreach ($emails as $email) {
			if (!empty($email)) {
				$contact = [
					'email' => $email,
					'status' => 0,
					'step' => 0, // 0: package created, 1: link sent, 2: docs submitted
					'started' => time(),
					'uuid' => '',
					//'appinstance' => '', // depreciate eventually - but need to keep for now (API 2.4.1 -> 2.5)
					'submissions' => [], // none initially
				];
				array_push($contacts['members'], $contact);
			}
		}
		// Handy\I_Handy::tip($contacts); die();
		error_log('create_contact_package() called | contacts created: ' . print_r($contacts, 1));
		update_user_meta( $user_id, 'aml_company_contacts', $contacts );
		// 2. get 'aml_company_contacts' - loop and send IDPal URL
		$contacts = get_user_meta( $user_id, 'aml_company_contacts', true);
		$business_name = get_field('business_name', 'user_' . $client->ID);
		if ( !empty($contacts) ) {
			if ( empty($contacts['status']) ) {
				$updated_contacts = [
					'status' => $contacts['status'],
					'entry_id' => $contacts['entry_id'],
					'members' => [],
				];
				foreach ($contacts['members'] as $member) {
					$updated_contact = [];
					$updated_contact = $member;
					if ( empty($member['status']) ) {
						$result = IdPal\sendAppLink(); // get the app UUID // Handy\I_Handy::tip($result); die();
						// error_log('user.php -> create_contact_package() | IdPal\sendAppLink() -> result: ' . print_r($result,1) );
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
									'Cc: ' . $site_title . ' <' . $admin_email . '>',
									'Cc: Website <hello@hootfish.ie>', // testing
								],
								'subject' => 'ID/POA Verification required',
								'body' => sprintf(
									"Hi,
									<br /><br />You are receiving this email because your business%1s will be availing of services from %2s.
									<br /><br />Please %3s to download the ID Pal app for ID verification.
									<br /><br />This link is valid for 48 hours. We cannot provide services to you until you have completed this process.
									<br /><br /><strong>Why do we need this from you?</strong>
									<br /><br />We are supervised by %4s and therefore we are required to review and keep ID and proof of address for our clients before providing professional services.
									<br /><br /><strong>Keeping your data safe</strong>
									<br /><br />By submitting your ID and proof of address you are agreeing to us storing this personal data for as long as you remain a client of ours for up to 5 years after in line with guidelines from Chartered Accountants Ireland. We will keep this data in line with our %5s and our %6s.
									<br /><br />Please let us know if you have any questions.",
									(!empty($business_name)?' (' . $business_name . ')':''),
									(!empty($manager)?$manager:$site_title),
									'<a href="'.$url_base_idpal.'?uuid='.$result['response']['uuid'].'">click this link</a>',
									'<a href="https://www.charteredaccountants.ie/find-a-Firm/firm-details?firm=lizdan-business-services-ltd-45648">Chartered Accountants Ireland</a>',
									'<a href="'.$site_url.'/about/privacy/">privacy policy</a>',
									'<a href="'.$site_url.'/about/gdpr-policies-and-procedures/">GDPR policies and procedures</a>'
								),
							];
							// Handy\I_Handy::tip($mail_args); die();
							$email_sent = Comms\emailUser($mail_args); // Handy\I_Handy::tip($email_sent); die();
							if ( $email_sent ) {
								// error_log('Ican\Theme\User\IdPal -> create_contact_package() | IdPal\sendAppLink() -> email_sent -> result('.$email_sent.') -> mail args: ' . print_r($mail_args,1) );
								// email has been sent to this contact with unique 'uuid'
								$updated_contact['step'] = 1; // 1: link sent
								$updated_contact['updated'] = time();
								//$updated_contact['appinstance'] = $result['response']['uuid']; // depreciate eventually
								$updated_contact['uuid'] = $result['response']['uuid'];
							} else {
								//error_log('Ican\Theme\User | create_contact_package(): call to IdPal\sendAppLink() returned error: ' . print_r($result,1));
							}
						} else {
							//error_log('Ican\Theme\User | create_contact_package(): call to IdPal\sendAppLink() with no parameters returned error: ' . print_r($result,1));
							// try again but this time allow ID-Pal to send the email
							$result = IdPal\sendAppLink(['information_type' => 'email', 'contact' => $member['email']]); // Handy\I_Handy::tip($result); die();
							if ($result['status'] == 'success') {
								// email has been sent to this contact with unique 'uuid'
								$updated_contact['step'] = 1; // 1: link sent
								$updated_contact['updated'] = time();
								//$updated_contact['appinstance'] = $result['response']['uuid'];
								$updated_contact['uuid'] = $result['response']['uuid']; // depreciate eventually
							} else {
								//error_log('Ican\Theme\User | create_contact_package(): call to IdPal\sendAppLink() with parameters returned error: ' . print_r($result,1));
							}
						} // !empty($result['response']['uuid'])
					}
					array_push($updated_contacts['members'], $updated_contact);
				}
				// Handy\I_Handy::tip($updated_contacts); die();
				update_user_meta( $user_id, 'aml_company_contacts', $updated_contacts );
			} // empty($contacts['status'])
		} // !empty($contacts)
	}
	/* UI
	-------------------------------------------------------------- */
		/*
		*
		* outputs a table of all clients and their progress
		* 
		* @called
		*
		* - the 'clients' block
		*
		*
		*/
		function user_idpal_summary() {
			$url_base_idpal = get_field('base_url', 'option');
			$error_codes = IdPal\get_error_codes(); // get code meanings
			$step_meanings = IdPal\get_step_meanings(); // get step meanings // Handy\I_Handy::tip($step_meanings);
			$args = array(
			    'role'    => 'client',
			    'orderby' => 'user_nicename',
			    'order'   => 'ASC',
			    // 'meta_key' => 'aml_company_contacts'
			);
			$clients = get_users( $args );
			// Handy\I_Handy::tip($clients);
			?>
			<? if ( current_user_can('manage_aml_clients') ): ?>
				<div class="clients" style="margin-top:3rem;">
					<? if ( !empty($clients) ): ?>
						<div class="table-responsive">
		               		<table class="table table-striped table-sm">
								<thead>
									<tr>
										<th></th>
										<th></th>
										<th>Code</th>
										<th>ID</th>
										<th>Email</th>
										<th>Name</th>
										<th>Status</th>
										<th>Added</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<? $i=1; $j=1; foreach ($clients as $client): ?>
										<?
										$fields = get_fields('user_' . $client->ID); // Handy\I_Handy::tip($fields);
										$client_data = get_userdata( $client->ID ); // Handy\I_Handy::tip($client_data);
										$client_name = $client->display_name; 
										$client_avatar = 'https://secure.gravatar.com/avatar/?s=20&d=mm&r=g'; // get_avatar( get_the_author_meta($client->ID), 20 );
										$contacts_package = get_user_meta($client->ID, 'aml_company_contacts', true); // Handy\I_Handy::tip($contacts_package);
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
										?>
		                                <tr class="accordion-toggle collapsed" id="accordion-client-<?=$client->ID?>" data-toggle="collapse" data-parent="#accordion-client-<?=$client->ID?>" href="#collapse-client-<?=$client->ID?>">
			                                <? if ( !empty($contacts_package) ): ?><td class="expand-button"></td><? else: ?><td></td><? endif; ?>
			                                <td><img src="<?=$client_avatar?>" alt="<?=$client_name?>"/></td>
			                                <td><?=$fields['code']?></td>
			                                <td><?=$client->ID?></td>
			                                <td><?=$client_name?><?=(!empty($fields['business_name'])?' ('.$fields['business_name'].')':'')?></td>
			                                <td><?=$client->user_email?></td>
			                                <td> <? if ( !empty($contacts_package) ): ?><span class="indicator status-<?=$contacts_package['status']?>"></span><? endif;?></td>
			                                <td><?=date( 'd-m-Y', strtotime( $client_data->user_registered ) )?></td>
			                                <td><? if ( empty($contacts_package) ): ?><a href="#" class="idpal_btn_submit_user" data-id="<?=$client->ID?>" title="Send this user to ID Pal">Begin</a><? endif; ?></td>
		                                </tr>
		                                <? if ( !empty($contacts_package) ): ?>
											<tr class="hide-table-padding">
												<td colspan="9">
													<div id="collapse-client-<?=$client->ID?>" class="collapse in p-3">
														<? if ( !empty($contacts_package['members']) ): ?>
															<div class="details">
																<? foreach ( $contacts_package['members'] as $member ): ?>
																	<? // Handy\I_Handy::tip($member); ?>
																	<div class="row">
																		<div class="col-12 col-md-8">
																			<div class="card">
																				<div class="card-body">
																					<ul class="list-unstyled">
																						<li><strong>Step</strong>: 
																							<ul class="mt-3">
																								<? foreach ($step_meanings as $k=>$v): ?>
																									<? $class = ($member['step']==$k?' class="highlight"':''); ?>
																									<li><?=$k?>. <span<?=$class?>><?=$v?></span></li>
																								<? endforeach; ?>
																							</ul>
																						</li>
																						<li><strong>Start:</strong> <?=date('d-m-Y H:i:s', $member['started'])?></li>
																						<li><strong>Updated:</strong> <?=(!empty($member['updated'])?date('d-m-Y H:i:s', $member['updated']):'')?></li>
																						<? if ( empty($member['status']) ): ?>
																							<? $url_unique_idpal = add_query_arg( 'uuid', $member['uuid'], $url_base_idpal ); // new $member['uuid'] ?>
																							<li><a href='mailto:<?=$member['email']?>?subject=<?=urlencode('ID Verification Outstanding')?>&body=Please complete your ID Verification using your unique ID-Pal URL unique ID Pal URL <?=$url_unique_idpal?>.' title='Email this user' target='_blank' class='btn btn-secondary'>Email this user</a></li>
																						<? endif; ?>
																						<? if ( !empty($member['finished']) ): ?><li><strong>Finished</strong>: <?=date('d-m-Y H:i:s', $member['finished'])?></li><? endif; ?>
																						<? if ( !empty($member['overwritten']) ): ?><li><strong>Overwritten</strong>: Yes</li><? endif; ?>
																						<? if ( !empty($member['submissions']) ): ?>
																							<li><strong>Submissions</strong>:
																								<ul class="mt-3">
																								<? foreach ( $member['submissions'] as $id=>$details ): ?>
																									<li><a href="https://client.id-pal.com/submission/<?=$id?>" target="_blank"><?=$id?></a>:
																										<? if ( is_array($details) ): ?>
																											<ul class="mt-3">
																												<? foreach ( $details as $k=>$v ): ?>
																													<li><?=code_to_string($k)?>: <?=date('d-m-Y H:i:s', $v)?></li>
																												<? endforeach; ?>
																											</ul>
																										<? endif; ?>
																									</li>
																								<? endforeach; ?>
																								<? /*if ( empty($member['status']) ): ?>
																									<li><?=$member['submissions'][0]['status']?>, <?=$error_codes[$member['submissions'][0]['status']]?></li>
																								<? endif;*/ ?>
																								</ul>
																							</li>
																						<? endif; ?>
																					</ul>
																				</div> <!-- card-body -->
																			</div> <!-- card -->
																		</div>
																		<div class="col-6 col-md-4">
																			<div class="card">
																				<div class="card-body">
																					<div class="indicators"><a href="#" title="Status: <?=$member['status']?>"><span class="indicator status-<?=$member['status']?>"></span></a><a href="#" title="Step: <?=$member['step']?>"><span class="indicator step-<?=$member['step']?>"></span></a></div>
																					<ul class="list-unstyled">
																						<li><strong>Client</strong>: <?=$client_name?></li>
																						<? if ( !empty($fields['business_name']) ): ?><li><strong>Business</strong>: <?=$fields['business_name']?></li><? endif; ?>
																						<li><strong>Added</strong>: <?=date( 'd-m-Y H:i:s', strtotime( $client_data->user_registered ) )?></li>
																						<li><strong>Email:</strong> <a href='mailto:<?=$member['email']?>' title='' target='_blank'><?=$member['email']?></a></li>
																						<? if ( !empty($fields['code']) ): ?><li><strong>Code</strong>: <?=$fields['code']?></li><? endif; ?>
																						<? if ( !empty($fields['entity_type']) ): ?><li><strong>Entity</strong>: <?=ucfirst($fields['entity_type'])?></li><? endif; ?>
																						<? if ( !empty($fields['partner']) ): ?><li><strong>Partner</strong>: <?=$fields['partner']?></li><? endif; ?>
																						<? if ( !empty($fields['manager']) ): ?><li><strong>Manager</strong>: <?=$fields['manager']?></li><? endif; ?>
																						<? if ( !empty($fields['lead_staff']) ): ?><li><strong>Lead Staff</strong>: <?=$fields['lead_staff']?></li><? endif; ?>
																					</ul>
																				</div> <!-- card-body -->
																			</div> <!-- card -->
																		</div>
																	</div>
																<? endforeach; // $contacts_package['members'] as $member ?>
															</div>
														<? endif; // !empty($contacts_package['members']) ?>
														<? $j++; ?>
													</div>
												</td>
											</tr>
										<? endif; ?>
									<? $i++; endforeach; // foreach ($clients as $client) ?>
								</tbody>
							</table>
						</div> <!-- .table-responsive -->
					<? else: ?>
						<p>There are currently no outstanding ÌD-Pal tasks.</p>
					<? endif; ?>
				</div> <!-- clients -->
			<? else: ?>
				<div class="alert alert-warning">You do not have permission to view this content.</div>
			<? endif; ?>
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
				$contacts = get_user_meta( $user_id, 'aml_company_contacts', true);
				Handy\I_Handy::tip($contacts);
			}
			die();
		}
		/*
		*
		* output some stats (called from sidebar)
		*
		*/
		function user_idpal_stats() {
			if ( !current_user_can('manage_aml_clients') ) return false;
			$stats = [
				'total' => 0,
				'complete' => 0,
			];
			$args = array(
			    'role'    => 'client',
			    'orderby' => 'user_nicename',
			    'order'   => 'ASC',
			    // 'meta_key' => 'aml_company_contacts'
			);
			$clients = get_users( $args );
			if ( !empty($clients) ) {
		 		$i=0; $j=0; foreach ($clients as $client) {
		 			$contacts_package = get_user_meta($client->ID, 'aml_company_contacts', true);
		 			if ( !empty($contacts_package) ) {
		 				if ( !empty($contacts_package['status']) ) $j++;
		 				/*if ( !empty($contacts_package['members']) ) {
		 					foreach ( $contacts_package['members'] as $member ) {
		 						// $member['status']
		 					}
		 				}*/
		 			}
		 		$i++; }
	 		}
			$stats = [
				'total' => $i,
				'complete' => $j,
			];
			return $stats;
		}
	/* CRON
	-------------------------------------------------------------- */
		/*
		*
		* nudge clients who haven't completed their ID Pal procedure
		*
		* @triggered by
		*
		* - WP-crontrol every XX hours (see WP Crontrol for specifics)
		*
		*/
		add_action( 'aml_nudge_clients', __NAMESPACE__ . '\\nudge_clients' );
		function nudge_clients () {
			$now = time();
			$admin_email = get_option( 'admin_email' );
			$manager = get_field('manager', 'option'); // ID Pal Companion 'manager'
			$site_url = get_option( 'siteurl' );
			$url_base_idpal = get_field('base_url', 'option');
			$mail_args = [
				'to' => '',
				'headers' => [
					'Content-Type' => 'text/html; charset=UTF-8',
					'Cc: Richard Cantwell <hello@hootfish.ie>', // debugging
				],
				'subject' => 'Nearly there!',
				'body' => '',
			];
			$args = array(
			    'role'    => 'client',
			    // 'meta_key' => 'aml_company_contacts'
			);
			$clients = get_users( $args );
			if ( !empty($clients) ) {
		 		$i=0; $j=0; foreach ($clients as $client) {
		 			$contacts_package = get_user_meta($client->ID, 'aml_company_contacts', true);
		 			$business_name = get_field('business_name', 'user_' . $client->ID);
		 			if ( !empty($contacts_package) ) {
		 				if ( !empty($contacts_package['members']) ) {
		 					foreach ( $contacts_package['members'] as $member ) {
		 						if ( empty($member['status']) ) {
		 							$url_unique_idpal = add_query_arg( 'uuid', $member['uuid'], $url_base_idpal );
									$mail_args['body'] = sprintf(
										"Hi there, 
										<br><br>We recently requested ID verification and Proof of Address from you via the ID Pal service but have noticed that you haven't completed the process as of yet.
										<br><br>If you are having any issues, please <a href='mailto:%1s'>contact us</a> as we'd be delighted to help.",
										$admin_email
									);
									$result = Comms\emailUser($mail_args);
									if ( $result ) $j++;
		 						} // empty($member['status'])
		 					} // $contacts_package['members'] as $member 
		 				} // !empty($contacts_package['members'])
		 			} // !empty($contacts_package)
		 		$i++; } // foreach ($clients as $client)
				$mail_args = [
					'to' => 'hello@hootfish.ie',
					'subject' => '' . $j . ' ' . $manager . ' clients were nudged!',
					'body' => sprintf(
						"[user.php -> nudge_clients()] total of %1d clients were nudged (%2s).",
						$j,
						date('Y-m-d H:i:s')
					),
				];
				$result = Comms\emailUser($mail_args);
	 		} // !empty($clients)
		}
		/*
		*
		* pull all clients and see how they're doing in the ID Pal funnel
		*
		* - loops all clients with 'aml_company_contacts' meta
		* - pulls out aml_company_contacts
		* - loops aml_company_contacts and call IdPal\AppLinkStatus($uuid) for each contact
		* - if complete - update contact status in 'aml_company_contacts' and updpate overall status
		* - if overall status is 1 - trigger aml_company_contacts webhook to complete flow, email admin
		*
		* @triggered by
		*
		* - dashboard widget - manually complete
		* - CRON (but depreciated as too many calls)
		*
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
			    'meta_key' => 'aml_company_contacts'
			]);
			if ( !empty($clients) ) {
				foreach ($clients as $client) {
					/*
					*
					* Step 1
					* ------
					*
					* Has this client got contacts packet and if so is it 'incomplete'? If 'incomplete' - loop through the contacts inside, make a
					* call to IdPal\AppLinkStatus(uuid) to see what the status of their submissions (ie passport check, address check, likeless check etc) are. If
					* all their submissions are >4 ('CDD generated and all technical checks passed') then upload this user's status as 'complete' (1) and if all contacts
					* in this packet are complete - update the contacts packet status as 'complete' (1)
					*
					*/
					$contacts = get_user_meta($client->ID, 'aml_company_contacts', true);
					if ( !empty($contacts) ) {
						if ( empty($contacts['status']) ) {
							 // contacts are incomplete
							$updated_contacts = [
								'status' => 0, // default until proven
								'entry_id' => $contacts['entry_id'],
								'members' => [],
							];
							$os=0;$i=0;foreach ($contacts['members'] as $member) { // $os overall status
								$updated_contact = [];
								$updated_contact = $member;
								if ( !isset($member['overwritten']) ) {
									if ( empty($member['status']) ) {
										// contact is incomplete
										// echo $member['appinstance'];
										// $args = [ 'submission_id' => '9112' ]; $result = IdPal\getCustomerInformation($args); Handy\I_Handy::tip($result);
										/*$args = [ 'submission_id' => '9500' ]; $result = IdPal\getCDDReport($args); // 4505
										if ( !empty($result['response']) ) {
											header('Content-Type: application/pdf'); die($result['response']);
										}*/
										// check ID-Pal see if contact is now complete?
										// statuses passed into AppLinkStatus()
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
											$updated_contact['submissions'] = ( !empty($updated_contact['submissions'])?$updated_contact['submissions']:[]); // remove older submissions and replace with latest
											$updated_contact['status'] = 0; // status is 0 until proved
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
														$updated_contact['submissions'][$submission['submission_id']]['status'] = $submission['status'];
														//if ( isset($updated_contact['submissions'][$submission['submission_id']]['submission_complete']) ) { 
															if ( $submission['status'] >=4 ) { // means the submission has been verified by an AO account manager - this is the real completion
																$updated_contact['status'] = 1;  // set this members status to 1
																$updated_contact['step'] = 2; // 2: docs submitted
																$updated_contact['finished'] = time(); // not 100% accurate - within the time between CRON calls
																$os++; // add 1 to overall status dd
															}
														//}
													}
												}
												// Handy\I_Handy::tip($submission_data); die();
											}
										}
										// Handy\I_Handy::tip($updated_contact); die();
									} else {
										$os++; // uncomment for debugging // overall status // empty($member['status']) // this individual contact is 'incomplete'
									}
								} else {
									 $os++; // member has been overwritten so just pass
								}
								array_push($updated_contacts['members'], $updated_contact);
							$i++;} // $contacts['members'] as $member // for each contact in this contacts package
							// echo "$os|$i"; // are all contacts complete? if the same - then yes
							// if ( $debug ) { if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { echo "$os|$i"; Handy\I_Handy::tip($updated_contacts); }}
							if ($os==$i) { 
								$updated_contacts['status'] = 1; 
							}
							update_user_meta( $client->ID, 'aml_company_contacts', $updated_contacts ); // store progress (for dashboard)
							// check overall status of 'aml_company_contacts' and if complete - do follow up tasks
							if ( $updated_contacts['status'] == 1 ) {
								// error_log('continue_workflow() called');
								continue_workflow( $client->ID );
							}
						}  // empty($contacts['status']) // contacts package is incomplete
					} // !empty($contacts) // this client has a contacts package
				} // foreach ($clients as $client) {
			} // !empty($clients)
			// Handy\I_Handy::tip($site_master_history, '213.79.32.115');
			/*if ( !empty($gflow_webhook['key']) && !empty($gflow_webhook['secret']) ) {
				// get all clients with 'meta_key' => 'aml_company_contacts'
			} else {
				error_log('Ican\Theme\User | queryIdPalStatus: webhook_key & webhook_secret empty.');
				Comms\emailUser(['to' => ['hello@hootfish.ie'], 'subject' => 'AO Debug', 'body' => 'Ican\Theme\User | queryIdPalStatus() : '.print_r($gflow_webhook,1)]);
			}*/
		}
	/* Form
	-------------------------------------------------------------- */
		/*
		*
		* Contact form hooks
		*
		add_action( 'init', __NAMESPACE__ . '\\contact_form_hooks', 11);
		function contact_form_hooks () {
			$fid = App\themeSettings('theme_idpal_limited_fid');
			if ( !empty($fid) ):
				// after submission
				add_filter( "gform_after_submission_{$fid}", __NAMESPACE__ . '\\create_contact_package', 10, 2 );
				// add_filter( "gform_confirmation_{$ofid}", __NAMESPACE__ . '\\confirmation_manual_onboarding', 10, 4 ); // perhaps instruct client service agent about something - ie what's been done
			endif;
			// add_filter( "gform_after_submission_81", __NAMESPACE__ . '\\create_contact_package', 10, 2 ); // test IDPal form
		}
		*/
		/*
		*
		* this hook listens to 'all' (change so that all forms can access - 09/2019) form submissions and checks for the presence of 'fieldXXXX' css selectors - then if
		* present creates a contacts package 'aml_company_contacts' which is queried every our by a CRON ('aml_cron_idpal_get_progress_uuids') to be used with ID Pal
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
		//add_filter( "gform_after_submission", __NAMESPACE__ . '\\create_contact_package', 10, 2 );
		function create_contact_package ($entry, $form) {
			// die('here');
			$debug_ips = Debug\get_debug_ips();
			if ( is_user_logged_in() ) {
	    		$current_user = wp_get_current_user();  // Handy\I_Handy::tip($current_user); die();
	    		$user_roles = (array) $current_user->roles;
	    		if ( in_array( 'client', $user_roles ) || in_array( 'administrator', $user_roles ) ) {
	    			// 1. store 'aml_company_contacts' meta (if there is submitted meta)
	    			$emails = [];
					$search_fields = [
						'fieldClientContact1' => 'fieldClientContact1',
						'fieldClientContact2' => 'fieldClientContact2',
						'fieldClientContact3' => 'fieldClientContact3',
						'fieldClientContact4' => 'fieldClientContact4',
						'fieldClientContact5' => 'fieldClientContact5',
					];
					$matches = GravityForms\getGfValuesBy($search_fields, $form, 'css'); 
					// if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { Handy\I_Handy::tip($matches); die('DEBUGGING --- create_contact_package() --- DEBUGGING'); }
					if ( !empty($matches['fieldClientContact1']) ) array_push($emails, rgar($entry, $matches['fieldClientContact1']));
					if ( !empty($matches['fieldClientContact2']) ) array_push($emails, rgar($entry, $matches['fieldClientContact2']));
					if ( !empty($matches['fieldClientContact3']) ) array_push($emails, rgar($entry, $matches['fieldClientContact3']));
					if ( !empty($matches['fieldClientContact4']) ) array_push($emails, rgar($entry, $matches['fieldClientContact4']));
					if ( !empty($matches['fieldClientContact5']) ) array_push($emails, rgar($entry, $matches['fieldClientContact5']));
					// if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { Handy\I_Handy::tip($emails); die('DEBUGGING --- create_contact_package() --- DEBUGGING'); }
					if ( !empty($emails) ) {
						// error_log('[user.php -> create_contact_package()] emails: ' . print_r($emails,1));
						// Handy\I_Handy::tip($emails); die();
						submit_to_idpal($current_user->ID, $emails, [ 'entry_id' => $entry['id'] ]);
					} // !empty($emails)
	    		} // in_array( 'client', $user_roles ) || in_array( 'administrator', $user_roles )
			} // ( is_user_logged_in() )
		}
	/* Admin - dashboard
	-------------------------------------------------------------- */
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
		* output incomplete contact packages in dashboard
		*
		*/
		function user_idpal_dashboard() {
			$url_base_idpal = get_field('base_url', 'option');
			$error_codes = IdPal\get_error_codes(); // get code meanings
			// $step_meanings = IdPal\get_step_meanings(); // get step meanings // Handy\I_Handy::tip($step_meanings);
			$clients = get_users([
			    'meta_key' => 'aml_company_contacts'
			]);
			// Handy\I_Handy::tip($clients);
			?>
			<div class="user_actions">
				<? if ( !empty($clients) ) { ?>
					<? $i=1; foreach ($clients as $client) { ?>
						<?
						$contacts_package = get_user_meta($client->ID, 'aml_company_contacts', true); // Handy\I_Handy::tip($contacts_package);
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
						<? if ( empty($contacts_package['status']) ) { ?>
							<? $business_name = get_field('business_name', 'user_' . $client->ID); ?>
							<p><strong><?=$i?></strong>) Displaying incomplete ID Pal tasks for client <a href="<?=admin_url('user-edit.php?user_id='.$client->ID.'&wp_http_referer=%2Fwp%2Fwp-admin%2Fusers.php')?>" title="User ID: <?=$client->ID?> | Package: <?=print_r($contacts_package,1)?>" target="_blank"><?=$client->user_email?></a><?=(!empty($business_name)?' ('.$business_name.')':'')?>:</p>
							<? if ( !empty($contacts_package['members']) ) { ?>
								<div class="transaction" style="border:1px solid gray; padding: 10px; margin-bottom: 10px;">
									<ul>
									<? foreach ( $contacts_package['members'] as $member ) { ?>
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
									<? } // $contacts_package['members'] as $member ?>
									</ul>
								</div>
							<? } // !empty($contacts_package['members']) ?>
							<? $i++; ?>
						<? } // empty($contacts_package['status']) ) ?>
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
	    		// 1. mark user_email as complete in this clients contacts package
	    		$result = user_idpal_manually_complete($client_id, $user_email, $submission_id);
		    	if ( $result ) {
			    	// do other stuff
			    	$message = 'You have manually set client ' . $client_id . 's contacts package user <strong>' . $user_email . '</strong> to status <strong>1</strong> (complete).';
			    	add_action( 'admin_notices', __NAMESPACE__ . '\\output_dashboard_widget_message', $message );
		    	}
		    }
			$clients = get_users([
			    'meta_key' => 'aml_company_contacts'
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
			        		<? $contacts_package = get_user_meta($client->ID, 'aml_company_contacts', true); // Handy\I_Handy::tip($contacts_package); ?>
			        		<? $business_name = get_field('business_name', 'user_' . $client->ID); ?>
			        		<? if ( empty($contacts_package['status']) ) { ?>
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
		* manually complete a user in an ID-Pal contacts package
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
					$contacts_package = get_user_meta($client_id, 'aml_company_contacts', true);
					$updated_contacts_package = $contacts_package; // copy initally
					if (!empty($contacts_package)) {
						$debugging_message = '<b>Before</b>' . print_r($contacts_package, 1); // Handy\I_Handy::tip($contacts_package); die();
						$i=0;foreach ( $contacts_package['members'] as $member ) {
							if ( $member['email'] == $user_email ) {
								if ( empty($member['status']) ) { // just check - but always should be 0
									if ( !empty($submission_id ) ) {
										// softer overide
										$updated_contacts_package['members'][$i]['note'] = 'Submission ID ' . $submission_id . ' manually added ' . date('d-m-Y H:i:s') . ' by ' . $current_user->ID;
										$result = IdPal\getSubmissionsStatus(['submission_id' => $submission_id]); // Handy\I_Handy::tip($result); die(); // example - http://prntscr.com/nzzo82
										if ( $result['status'] == 'success' ) {
											if ( !empty($result['response']['submissions']) ) {
												// update the users UUID from result (and allow hourly CRON to query)
												if ( !empty($result['response']['submissions'][0]['uuid']) ) { // assuming that we're taken the FIRST submission - but their could be more?
													$updated_contacts_package['members'][$i]['note'] .= ' which replaced uuid ' . (!empty($member['uuid'])?$member['uuid']:'N/A') . '.';
													//$updated_contacts_package['members'][$i]['appinstance'] = $result['response']['submissions'][0]['uuid']; // need to depreciate this for 'uuid' below
													$updated_contacts_package['members'][$i]['uuid'] = $result['response']['submissions'][0]['uuid'];
												} else {
													$updated_contacts_package['members'][$i]['note'] .= ' but IDPal/getSubmissionsStatus() - [uuid] not found.';
												}
											} else {
												$updated_contacts_package['members'][$i]['note'] .= ' but IDPal/getSubmissionsStatus() returned an error of status ' . $result['response']['status'];
											}
										}
									} else {
										// just manually override
										$updated_contacts_package['members'][$i]['status'] = 1; // set this users status to 1
										$updated_contacts_package['members'][$i]['overwritten'] = time(); // add a timestamp for when this was manually overwritten
									}
								}
							}
						$i++;}
						update_user_meta($client_id, 'aml_company_contacts', $updated_contacts_package); $debugging_message .= '<b>Middle</b>' . print_r($updated_contacts_package, 1);
						do_action( 'aml_cron_idpal_get_progress_uuids' ); // run the cron
						// post cron - just do a debug
						/*$contacts_package = get_user_meta($client_id, 'aml_company_contacts', true);
						$debugging_message .= '<b>After</b>' . print_r($contacts_package, 1);
						Comms\emailUser(['to' => 'hello@hootfish.ie', 'subject' => '[Type: Info] ID Pal', 'body' => '[user.php -> user_idpal_manually_complete()] | trace: ' . $debugging_message]);*/
						// finally return
						return true;
					}
				}
			}
			return false;
		}
	/* Webhook
	-------------------------------------------------------------- */
		/*
		*
		* @triggered by
		*
		* - response from ID Pal (webhook feature - see https://client.id-pal.com/push-api) to a complete submission
		*
		* @webhook
		*
		* - Production: https://aml.tynandillon.ie/wp-admin/admin-post.php?action=idpal_webhook_response
		* - Staging: https://xxxx/wp-admin/admin-post.php?action=idpal_webhook_response
		* - Local: http://aml.loc/wp/wp-admin/admin-post.php?action=idpal_webhook_response
		*
		* @response
		*
		* sign Key: hRawN$o%9u6@BtT^mQ
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
						$client_id = get_client_by_contact_uuid($data['uuid']);
						if (!empty($client_id)) {
		    				error_log('process_idpal_webhook_response() | event_type = ' . $data['event_type'] . ', client ID ( ' . $client_id . ') found for UUID ' . $data['uuid']); 
							process_contacts_package ($client_id, $data);
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
		* get a contact in a contacts package ('aml_company_contacts') given some identifier
		*
		* @params
		*
		* - $identifier [uuid | submission_id]
		* - $value
		*
		*/
		function get_client_by_contact_uuid ( $uuid ) {
			if (empty($uuid)) return;
			$clients = get_users([
				'meta_key' => 'aml_company_contacts'
			]);
			if ( !empty($clients) ) {
				foreach ($clients as $client) {
					$contacts = get_user_meta($client->ID, 'aml_company_contacts', true);
					if ( !empty($contacts) ) {
						if ( empty($contacts['status']) ) {
							// contacts package is incomplete 
							foreach ($contacts['members'] as $member) {
								$updated_contact = [];
								$updated_contact = $member;
								if ( !isset($member['overwritten']) ) {
									if ( empty($member['status']) ) {
										// contact is incomplete
										if ( $member['uuid'] == $uuid ) {
											//Handy\I_Handy::tip($member);
											return $client->ID;
										}
									}
								} // !isset($member['overwritten'])
							} // $contacts['members'] as $member
						} // empty($contacts['status'])
					} // !empty($contacts)
				} // $clients as $client
			} // !empty($clients)
		}
		/*
		*
		*
		* @params
		*
		* - $client_id [user_id]
		* - $uuid [contact submission]
		* - $data [
		* - event_type [new_submission_start|submission_complete]
		* - uuid
		* - submission_id
		* - source
		* - ]
		*/
		function process_contacts_package ( $client_id, $data ) {
			error_log('process_contacts_package() called | client ID: ' . $client_id . ', data: ' . print_r($data,1) );
			if (empty($client_id)) return;
			if (empty($data['uuid'])) return;
			if (empty($data['event_type'])) return;
			$debug = true;
			$debug_ips = Debug\get_debug_ips();
			$contacts = get_user_meta($client_id, 'aml_company_contacts', true);
			if ( !empty($contacts) ) {
				if ( empty($contacts['status']) ) {
					// contacts package is incomplete
					$updated_contacts = [
						'status' => 0, // default until proven
						'entry_id' => $contacts['entry_id'],
						'members' => [],
					];
					$os=0;$i=0;foreach ($contacts['members'] as $member) { // $os overall status
						$updated_contact = [];
						$updated_contact = $member;
						if ( !isset($member['overwritten']) ) {
							if ( empty($member['status']) ) {
								// member is NOT complete
								error_log('compare ' . $member['uuid'] . ' | ' . $data['uuid']);
								// here's the difference (between CRON and webhook versions) - we're not checking ALL clients - we're just checking this one
								if ( $data['uuid'] == $member['uuid'] ) {
									$updated_contact['submissions'][$data['submission_id']][$data['event_type']] = time();
									// wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> process_contacts_package()] | client ' . $client_id . ' - member uuid ' . $data['uuid'] . ' ' . $data['event_type']);
									if ( $data['event_type'] == 'submission_complete' ) {
										$updated_contact['status'] = 1;  // set this members status to 1
										$updated_contact['step'] = 2; // 2: docs submitted
										$updated_contact['finished'] = time(); // not 100% accurate - within the time between CRON calls
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
						array_push($updated_contacts['members'], $updated_contact);
					$i++;} // $contacts['members'] as $member // for each contact in this contacts package
					// echo "$os|$i"; // are all contacts complete? if the same - then yes
					if ( $debug ) { if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { echo "<p>$os|$i</p>"; Handy\I_Handy::tip($updated_contacts); }}
					if ( $os==$i ) { 
						$updated_contacts['status'] = 1; 
					}
					// wp_mail( 'hello@hootfish.ie', '[Type: Debug | Module: IDPal', '[user/idpal.php -> process_contacts_package()] | client ' . $client_id . ' <code>' . print_r($updated_contacts, 1) . '</code>' );
					$result = update_user_meta( $client_id, 'aml_company_contacts', $updated_contacts );
					// check overall status of 'aml_company_contacts' and if complete - do follow up tasks
					if ( $updated_contacts['status'] == 1 ) {
						// error_log('continue_workflow() called');
						continue_workflow( $client_id ); // xx
					}
				} // empty($contacts['status'])
			} // !empty($contacts)
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
			$contacts = get_user_meta($client_id, 'aml_company_contacts', true);
			if ( $debug ) { if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) { Handy\I_Handy::tip($contacts); die('DEBUGGING -- continue_workflow() -- DEBUGGING');}}
			if ( !empty($contacts) ) {
				$updated_contacts = $contacts; // if we need to update anything
				// contacts are all complete - so complete the flow
				if ( !empty($contacts['status']) ) {
					/*$info = [
						'entry_id' => $contacts['entry_id'],
						'key' => $gflow_webhook['key'],
						'secret' => $gflow_webhook['secret'],
					];*/
					$response = 'complete'; // GravityFlow\incoming_webhook_endpoint_process($contacts['entry_id']); // looking for 'complete'
					if ( $response == 'complete' ) { // debugging add 'true' | otherwise $response == 'complete'
						// Email administrator
						$business_name = get_field('business_name', 'user_' . $client->ID);
						$fid = null; // App\themeSettings('theme_idpal_limited_fid');
						$mail_args = [
							'to' => $admin_email,
							'headers' => [
								'Content-Type' => 'text/html; charset=UTF-8',
								'Cc: Richard Cantwell <hello@hootfish.ie>', // debugging
							],
							'subject' => 'Contacts ID-Pal ID/POA submission and verification complete',
							'body' => sprintf(
										"Administrator,<br><br> All contacts in contacts package <a href='%1s' target='_blank'>entry</a> initiated by client <a href='mailto:%2s'>%3s %4s</a>%5s have now completed their ID-Pal ID/POA submission and verification. This client has been notified.",
										(!empty($fid)?admin_url('admin.php?page=gf_entries&view=entry&id='.$fid.'&lid='.$contacts['entry_id']):''),
										$client->user_email,
										$client->first_name,
										$client->last_name,
										(!empty($business_name)?' ('.$business_name.')':'')
									),
						];
						$result = Comms\emailUser($mail_args);
						// Email user (first user in bundle) and tell them that flow can continue
						if ( !empty( $client->user_email ) ) {
							// could also use $updated_contacts['members'][0]['email'] - this is assuming that the 'responsible' user is number 1 on the list which is probably not the case - possibly last
							$mail_args = [
								'to' => $client->user_email,
								'headers' => [
									'Content-Type' => 'text/html; charset=UTF-8',
									'Cc: Richard Cantwell <hello@hootfish.ie>', // debugging
								],
								'subject' => 'ID & POA Verification complete',
								'body' => sprintf(
									"Hi %1s,<br><br>ID verification is now complete for your business.",
									$client->first_name
								),
							];
							$result = Comms\emailUser($mail_args);
						}
						// store
						$updated_contacts['flow_unlocked'] = time(); update_user_meta( $client->ID, 'aml_company_contacts', $updated_contacts ); // Update the user so that procedure only happens once
					}
					// Handy\I_Handy::tip($response);
				} // !empty($updated_contacts['status'])
			} // !empty($contacts) // this client has a contacts package
		}
	/* Admin - user (client) columns
	-------------------------------------------------------------- */
		/*
		*
		*
		*/
		add_filter( 'manage_users_columns', __NAMESPACE__ . '\\manage_users_columns_client' );
		function manage_users_columns_client( $column ) {
		    $column['client_details'] = 'Client Details';
		    $column['idpal_progress'] = 'ID Pal Progress';
		    return $column;
		}
		/*
		*
		*
		*/
		add_filter( 'manage_users_custom_column', __NAMESPACE__ . '\\manage_users_custom_column_client', 10, 3 );
		function manage_users_custom_column_client( $val, $column_name, $user_id ) {
		    switch ($column_name) {
		        case 'client_details' :
		        	$client_details = get_fields('user_' . $user_id); //Debug\tip($student_details);
					$o = '';
					if ( !empty($client_details) ) {
						$o .= '<ul>';
						foreach( $client_details as $k => $v ) {
							$o .= '<li>';
							$o .= '<strong>' . code_to_string($k) . '</strong>: ';
							$o .= (!empty($v)?$v:'-');
							$o .= '</li>';
						}
						$o .= '</ul>';
					}
					return $o; break;
				case 'idpal_progress' :
					return 'x'; break;
		        default: return $val; break;
		    }
		    return $val;
		}
	/* Debugging
	-------------------------------------------------------------- */
		// add_action( 'template_redirect', __NAMESPACE__ . '\\output_debugging', 11, 1 );
		function output_debugging () {
			$debug_ips = Debug\get_debug_ips();
			if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) {
				/*
				$uuid = '40096ef8';
				$client_id = get_client_by_contact_uuid($uuid);
				if (!empty($client_id)) {
					echo 'Client is ' . $client_id;
					// [new_submission_start|submission_complete]
					$data = [
						'uuid' => $uuid,
						'event_type' => 'submission_complete',
					];
					process_contacts_package ($client_id, $data);
					// Handy\I_Handy::tip($package);
				}
				*/
				if( current_user_can('administrator') ) {
					// do_action( 'aml_cron_idpal_get_progress_uuids' );
					// do_action( 'aml_nudge_clients' );
				}
				die('DEBUGGING');
			}
		}
	/* Testing
	-------------------------------------------------------------- */
		/*
		*
		* testing script to post payload to https://xxxx/wp-admin/admin-post.php?action=idpal_webhook_response
		* to test if POST data is being received or if theres some block on it being received (perhaps WP Engine?)?
		*
		* @useage
		*
		* - load any page (** careful with load - make sure to remove)
		*
		*  @webhooks
		*
		* - https://aml.tynandillon.ie/wp-admin/admin-post.php?action=idpal_webhook_response
		* - https://xxxx/wp-admin/admin-post.php?action=idpal_webhook_response
		* - http://aml.loc/wp/wp-admin/admin-post.php?action=idpal_webhook_response
		*
		*/
		// add_action( 'init', __NAMESPACE__ . '\\sendDummyPostData' );  // ** careful - should only be on on LOCAL
		function sendDummyPostData () {
			$ch = curl_init();
			$webhooks = [
				'production' => 'https://aml.tynandillon.ie/wp-admin/admin-post.php?action=idpal_webhook_response',
				'staging' => 'https://xxxx/wp-admin/admin-post.php?action=idpal_webhook_response',
				'local' => 'http://aml.loc/wp/wp-admin/admin-post.php?action=idpal_webhook_response',
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
		/*
		*
		*
		* submit the emails to ID Pal
		*
		*
		*/
		//add_action('template_redirect', __NAMESPACE__ . '\\output_debug');
		function output_debug () {
			$debug_ips = Debug\get_debug_ips();
			if ( in_array($_SERVER['REMOTE_ADDR'], $debug_ips) ) {
				//submit_to_idpal (4, ['liverpoolrc@gmail.com']);
				//$profiles = IdPal\getAppProfiles();
				//Handy\I_Handy::tip($profiles); die();
			}
		}
		/*
		*
		*
		*
		*/
	/* Helpers
	-------------------------------------------------------------- */
		function code_to_string ($str) {
			if ( empty($str) ) return;
			$str = str_replace('_', ' ', $str);
			$str = ucwords($str);
			return $str;
		}
	/* Misc
	-------------------------------------------------------------- */
	/* Misc
	-------------------------------------------------------------- */
	/* Misc
	-------------------------------------------------------------- */
