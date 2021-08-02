<?php

	namespace Custom\Package\IdPal;

	use Custom\Classes\Handy;

	use App;
	use Custom\User\Comms;

	/* IdPal functions
	*
	* AO app download link: https://qf97.app.link/0gL69PUUZx?clientkey=8833A1F4
	*
	*
	-------------------------------------------------------------------*/

	/*
	*
	* getAuthorization
	*
	* @desc:
	*
	* - this service is used to retrieve new access and refresh tokens to access all other documented ID-Pal API services.
	*
	* @url:
	*
	* - api/getAccessToken
	*
	* @body:
	*
	* - client_key
	*   o get this from your id-pal account section
	* - access_key
	*   o get this from your id-pal account section
	* - client_id
	*   o passing a client ID will return only the details for a specific client. Initially user will get the client id while creating the new client.
	* - refresh_token
	*   o passing a refresh token will return only the details for a specific client. Initially user will get the refresh token while creating the new client
	*
	* @returns:
	*
	* error
	*
	* {"status":12,"message":"client id missing"}
	* {"error":"invalid_request","message":"The refresh token is invalid.","hint":"Token has been revoked"}
	*
	* success
	*
	* {"token_type":"Bearer","expires_in":900,"access_token":"eyJ0eXAiOiJ...........","refresh_token":"def50200a72d43ee97d388.........",client_id":"76"}
	*
	* - token_type
	* - expires_in
	* - access_token
	* - refresh_token
	* - client_id
	*
	*
	*/

	function getAuthorization ($seed=false) {
		$debug = false;
		// error_reporting(E_ALL);
		$return = [
			'access_token' => null,
			'function' => 'getAuthorization',
			'response' => null,
		];
		$admin_email = get_option( 'admin_email' );
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('getAccessToken'); //die($query_url);
		$bearer_info = idPalGetBearerInfo($seed=false); // idPalGetBearerInfo(true) // get from seed/DB as this will be stored repeatly once we have an access token
		// Handy\I_Handy::tip($bearer_info);
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_URL, $query_url);
			curl_setopt($ch, CURLOPT_POST, 1);
			$fields = array(
				'client_key' => $credentials['client_key'],
				'access_key' => $credentials['access_key'],
				'client_id' => $bearer_info['client_id'],
				'refresh_token' => $bearer_info['refresh_token'],
			);
			//if ( $debug && $_SERVER['REMOTE_ADDR'] == '213.79.32.115' ) {
			//Handy\I_Handy::tip($fields);
			//	echo '<pre>Querying ID-Pal with following details - client_id: ' . $bearer_info['client_id'] . ' | refresh_token: ...' . substr($bearer_info['refresh_token'], -4).'</pre>';
			//}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
			$response = curl_exec($ch);
			// Handy\I_Handy::tip($response);die('here');
			if ( $response === FALSE ) {
				if (curl_errno($ch)) {
					error_log('Roots\Sage\Vendor\IdPal | getAuthorization | error no: ' . curl_errno($ch) . ' | error: ' . curl_error($ch));
					Comms\emailUser(['to' => 'hello@hootfish.ie', 'subject' => '[Type: Error | Module: id-pal | Severity: Critical]', 'body' => '[id-pal.php -> getAuthorization()] | error no: ' . curl_errno($ch)]);
					curl_close($ch);
				}
			} else {
				// Handy\I_Handy::tip($response); die();
				$json = json_decode($response, true);
				if ( !empty($json['token_type']) && $json['token_type'] == 'Bearer' ) {
			    	if ( !empty($json['access_token']) ) {
						// too many! Comms\emailUser(['to' => ['hello@hootfish.ie'], 'subject' => '[Type: Success | Module: id-pal]', 'body' => '[id-pal.php -> getAuthorization()] updating refresh token ... ' . $json['client_id'] . ' | ' . substr($json['refresh_token'], -4)]);
				    	// [response] => Array
				        // (
				        //   [token_type] => Bearer
				        //   [expires_in] => 900
				        //   [access_token] => khjdfgsdf ... dfrdsdfsd
				        //   [refresh_token] => sdfsdf ... sdfsdfsdfsd
				        //   [client_id] => 123
				        // )
						//$return['status'] = 'success';
						$return['access_token'] = $json['access_token'];
						idPalSetBearerInfo($json['client_id'], $json['refresh_token']); // store in DB
						if ( $debug && $_SERVER['REMOTE_ADDR'] == '213.79.32.115' ) {
							echo '<pre>Storing ID-Pal response values to DB - client_id : ' . $json['client_id'] . ' |  refresh_token: ...' . substr($json['refresh_token'], -4).'</pre>';
						}
					} else {
						// error_log('id-pal.php -> getAuthorization() | No access token found | ' . print_r($json,1) );
						Comms\emailUser(['to' => 'hello@hootfish.ie', 'subject' => '[Type: Error | Module: id-pal | Severity: Critical]', 'body' => '[id-pal.php -> getAuthorization()] no access token found | error no: ' . print_r($json,1) ]);
					}
				} else {
					// error_log('id-pal.php -> getAuthorization() | error | ' . print_r($json,1) );
					Comms\emailUser(['to' => 'hello@hootfish.ie', 'subject' => '[Type: Error | Module: id-pal | Severity: Critical]', 'body' => print_r($json,1)]);
					if ( !empty($json['status']) ) {
						// Handy\I_Handy::tip($json);die('here');
						//$return['status'] = 'error';
						// Comms\emailUser(['to' => ['hello@hootfish.ie'], 'subject' => 'ID Pal Debugging', 'body' => 'getAuthorization() | status: ' . print_r($json,1)]);
					} elseif ( !empty($json['error']) ) {
						if ( $json['error'] == 'invalid_request' ) {
							// error_log('id-pal.php -> getAuthorization() | invalid_request | ' . print_r($json,1) );
							// Comms\emailUser(['to' => ['hello@hootfish.ie'], 'subject' => 'ID Pal Debugging | invalid_request', 'body' => print_r($json,1)]);
							if ( $json['message'] == 'The refresh token is invalid.' ) {
								//getAuthorization (true);
								if ( $json['hint'] == 'Token has been revoked' ) {}
								if ( $json['hint'] == 'Token is not linked to client' ) {}
							}
						} elseif ( $json['error'] == 'invalid_client' ) {
							if ( $json['message'] == 'Client authentication failed' ) {
								// refresh token doesn't match the client ID - update the client ID to match the latest refresh token in ID-PAL panel
								//getAuthorization (true);
								// error_log('id-pal.php -> getAuthorization() | Client Authentication Failed | ' . print_r($json,1) );
								// Comms\emailUser(['to' => ['hello@hootfish.ie'], 'subject' => 'ID Pal Debugging | Client Authentication Failed', 'body' => print_r($json,1)]);
							}
						}
					}
				}
				$return['response'] = $json;
			}
		} catch (Exception $e) {
			Handy\I_Handy::tip($e);
		}
		// if ( $debug && $_SERVER['REMOTE_ADDR'] == '213.79.32.115' ) { Handy\I_Handy::tip($return); }
		return $return;
	}
	/*
	*
	*
	*
	*
	*/
	function idPalGetCredentials () {
		$client_key = get_field('client_key', 'option');
		$access_key = get_field('access_key', 'option');
		$data = [
			'client_key' => $client_key, // 'FEBC3820',
			'access_key' => $access_key , // 'EC6FBB0F-C6E1-D621-7E19-AFE5022C74C3',
		];
		// Handy\I_Handy::tip($data); die();
		return $data;
	}

	/*
	*
	* gets the refresh token
	*
	* Procedure to generate new access token (when there's a message that the refresh token is invalid (token has been revoked))
	*
	* Procedure
	* ---------
	*
	* 1. https://client.id-pal.com/api - generate a new client - note client_id and refresh_token
	* 2. open DB and find 'aml_idpal_bearer_info'
	* 3. https://serializededitor.com/ - change details to new values, copy back serialised array to 'aml_idpal_bearer_info'
	* 4. https://accountantonline.ie/wp-admin/admin.php?page=debug-idpal-output - test
	* 5. https://accountantonline.ie/test-id-pal/ - real test
	*
	*/
	function idPalGetBearerInfo ($seed = false) {
		$data = []; $bearer_info = [];
		// $bearer_info = get_option( 'aml_idpal_bearer_info' ); // this keeps caching!
		$option = 'aml_idpal_bearer_info';
		global $wpdb; $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );
		if ( is_object( $row ) ) {
			$value = $row->option_value; // Handy\I_Handy::tip($bearer_info); die();
			if ( is_serialized($value) ) {
				$bearer_info = unserialize($value);
			}
		}
		if ( $bearer_info ) {
			$data = [
				'refresh_token' => $bearer_info['refresh_token'],
				'client_id' => $bearer_info['client_id'],
			];
		}
		return $data;
	}
	/*
	*
	* ** not used (use direct insertion into DB instead as couldn't get this to work?) **
	*
	function seedBearer () {
		$data = [
			'refresh_token' => 'def502006016e8132a8010c8929970084f85a66110f8aff5797b702ed744c2b544223a94f3f4a73b6d80123043f37a16cc33ff66f53b1391f1f85e58fcea29296de8b2a879c279f3b9a1bccba4eda006fa5d4935a814601babf0d016cb15cd54fdbd01ff904780d57de74e757881b9c1f90de8c704768d67483f2cc92c2b1c3ffd3c951ecbbfd1f36b268fc4feb084c34334fd8bf366cae181f00a7f3decb1d0ed5a7e00d564c1ec6320e4657897e061bebf038811fac3bfbfe374fd33174838fbafe514acc668db8bb1a91f64e64e3eb0a46041859b92f430e8ea1582a37cd2bc2e36e3a1850df1ac89f6e989bba1fc6a207a84638b717d341764c682327ebdbb45fb0fbe3b6fa17085cc374f67100b1520f76f1a2b098488c34f120e5d87811044420236f0bf6fe862c79dbcead70ed017d8e4e416a592ffe06d80f205d2915a6f8f2e98cd7f3558412d781f3d3cbb3ab2f2cf19659c794f35c4df08430e3deca874aa29',
			'client_id' => '205',
		];
		return $data;
	}
	*/
	/*
	*
	* debug authentication issues
	*
	* called from debug.php
	*
	*/
	function debug_authentication_tokens () {
		if( current_user_can('administrator') ) {
			$auth = getAuthorization(); // get fresh access token
			// Handy\I_Handy::tip($auth);die('here');
			if (!empty($auth)) {
				Handy\I_Handy::tip($auth);
			}
		}
	}
	/*
	*
	* stores the refresh token / client id after the seed query
	*
	*/
	function idPalSetBearerInfo ($client_id, $refresh_token) {
		if (empty($client_id)) return;
		if (empty($refresh_token)) return;
		$data = [
			'client_id' => $client_id,
			'refresh_token' => $refresh_token,
		];
		update_option( 'aml_idpal_bearer_info', $data, false); // don't autoload as this gets cached
	}
	/*
	*
	* helper function for debugging initially
	*
	*/
	function idPalGetTestData () {
		$data = [
			'user_email' => 'hello@hootfish.ie',
		];
		return $data;
	}
	/*
	*
	* helper function that constructs the query string
	*
	*/
	function idPalGetQueryUrl ($endpoint) {
		$api_base = 'https://client.id-pal.com';
		$api_version = '3.0.0'; // 2.4.1 | 2.5.0 | 2.7.0
		return "$api_base/$api_version/api/$endpoint";
	}
	/*
	*
	* get profile to use with (new) 2.5.0 calls
	*
	*/
	function useThisProfile () {
		/*$profile = [
			'id' => null, // 537 is 'Default Profile'
			'name' => '',
		];
		$response = getAppProfiles();
		if ( $response['status'] == 'success' ) {
			if ( $response['response']['success'] ) {
				$profile['id'] = $response['response']['profiles'][0]['profile_id'];
				$profile['name'] = $response['response']['profiles'][0]['profile_name']; // 'Default profile'
			}
		}
		// Handy\I_Handy::tip($profile);die('here');
		return $profile['id'];
		*/
		return 537; // save an unesessary api call
	}
	/*
	*
	*
	* error codes from submissions
	*
	*/
	function get_error_codes() {
		$codes = [
			'No submission received', // 0
			'No submission received and no errors in technical checks, but report not generated', // 1
			'Submission received with errors in technical checks, and report not generated', // 2
			'Report Flagged by user', // 3
			'CDD generated and all technical checks passed', // 4
			'CDD generated but some technical checks failed (e.g. manual over-ride was used)', // 5
		];
		return $codes;
	}
	/*
	*
	*
	* get step (used in 'contacts package') explanations
	*
	*/
	function get_step_meanings() {
		$codes = [
			'AML package created', // 0
			'Link sent', // 1
			'Documents submitted', // 2
		];
		return $codes;
	}
	/*
	*
	* sendAppLink
	*
	* @desc:
	*
	* Allows you to send a customer an invitation to the application via email or SMS message. This will enable the customer to download the ID-Pal app and easily complete a submission to your account.
	* Each app link contains a UUID which can be used to track the lifecycle of customer submissions.
	* As of version 2.5.0, an app link can be generated without requiring an email or mobile number.
	*
	* @url:
	*
	* - api/app-link/send
	*
	* @body:
	*
	* [required]
	*
	* - client_key
	* - access_key
	* - profile_id
	*
	* [optional]
	*
	* - information_type [text|email]
	* - contact
	*   o if information_type is 'email' - this is the email address you want to send this email to - ie user@blah.com
	*   o if information_type is 'text' - this is the phone number you want to send this email to - ie 08712434567
	* - account_id * optional
	*
	* @returns:
	*
	* error
	*
	* success
	*
	* {"status":0,"message":"message sent","appinstance":"4b9ab3e8"}
	*
	* - status:
	*   o [0] - means app url has been sent to this user
	* - message:
	*   o 'message sent'
	* - appinstance:
	*   o unique application instance that this user downloads - ie '4b9ab3e8' (url sent has this appinstance in it - ie https://qf97.app.link/0gL69PUUZx?uuid=4b9ab3e8)
	*
	*/

	function sendAppLink ($args=array()) {
		// error_reporting(E_ALL);
		//$defaults = array(
		//	'information_type' => 'email',
		//);
		//$args = wp_parse_args( $args, $defaults );
		//if (empty($args['contact'])) return;
		$profile_id = useThisProfile(); // Handy\I_Handy::tip($profile_id);die('here');
		$result = [
			'status' => 'error',
			'function' => 'sendAppLink',
		];
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('app-link/send'); // error_log('id-pal.php -> sendAppLink() query_url: '.$query_url);
		// $test_data = idPalGetTestData(); // whilst testing
		$auth = getAuthorization(); // get fresh access token
		// Handy\I_Handy::tip($auth);die('here');
		if (!empty($auth['access_token'])) {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $query_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json; charset=utf-8',
					'Authorization:Bearer '.$auth['access_token'],
					'lan:en'
				));
				curl_setopt($ch, CURLOPT_POST, 1);
				$fields = array(
					'client_key' => $credentials['client_key'],
					'access_key' => $credentials['access_key'],
					'profile_id' => $profile_id,
				);
				// Handy\I_Handy::tip($fields);die('here');
				if ( !empty($args['information_type']) ) $fields['information_type'] = $args['information_type'];
				if ( !empty($args['contact']) ) $fields['contact'] = $args['contact']; // $test_data['user_email']
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$response = curl_exec($ch);
				if ( $response === FALSE ) {
					curl_errno($ch);
					if (curl_errno($ch)) {
						// print "Error: " . curl_error($ch);
						error_log('id-pal.php -> sendAppLink() curl_error: ' . curl_error($ch));
					}
					curl_close($ch);
				} else {
					$json = json_decode($response, true);
					// if ( $json['status'] > 0 ) {$result['status'] = 'error'; error_log('id-pal.php -> sendAppLink() -> error: ' . print_r($json,1)); }
					if ( $json['status'] == 25 ) {
						$result['status'] = 'success';
					} else {
						error_log('id-pal.php -> sendAppLink() -> error: ' . print_r($json,1));
					}
					$result['response'] = $json;
				}
			} catch (Exception $e) {
				error_log('id-pal.php -> sendAppLink() -> exception: ' . print_r($e,1)); // Handy\I_Handy::tip($e);
			}
		} else {
			error_log('id-pal.php -> sendAppLink() -> error: getAuthorization[access_token] is empty ' . print_r($auth,1));
			$result['response'] = $auth;
		}
		// Handy\I_Handy::tip($result); die();
		return $result;
	}
	/*
	*
	*
	* returns all profiles including their individual preference details.
	*
	*
	* new in 2.5.0
	*
	*/
	function getAppProfiles () {
		// error_reporting(E_ALL);
		//$defaults = array(
		//	'information_type' => 'email',
		//);
		//$args = wp_parse_args( $args, $defaults );
		//if (empty($args['contact'])) return;
		$result = [
			'status' => 'error',
			'function' => 'getAppProfiles',
		];
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('app-profile/all'); //error_log('id-pal.php -> sendAppLink() query_url: '.$query_url);
		// $test_data = idPalGetTestData(); // whilst testing
		$auth = getAuthorization(); // get fresh access token
		// Handy\I_Handy::tip($auth);die('here');
		if (!empty($auth['access_token'])) {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $query_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json; charset=utf-8',
					'Authorization:Bearer '.$auth['access_token'],
					'lan:en'
				));
				curl_setopt($ch, CURLOPT_POST, 1);
				$fields = array(
					'client_key' => $credentials['client_key'],
					'access_key' => $credentials['access_key'],
				);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$response = curl_exec($ch);
				if ( $response === FALSE ) {
					curl_errno($ch);
					if (curl_errno($ch)) {
						print "Error: " . curl_error($ch);
					}
					curl_close($ch);
				} else {
					$json = json_decode($response, true);
					if ( $json['status'] > 0 ) {
						$result['status'] = 'error';
					} elseif ( !empty($json['error']) ) {
						$result['status'] = 'error';
					} else {
						$result['status'] = 'success';
					}
					$result['response'] = $json;
				}
			} catch (Exception $e) {
				Handy\I_Handy::tip($e);
			}
		} else {
			$result['response'] = $auth;
		}
		// Handy\I_Handy::tip($result); die();
		return $result;
	}
	/*
	*
	* getSubmissionsStatus
	*
	* @desc:
	*
	* - returns all submissions including their individual details and status.
	*
	* @url:
	*
	* - api/submission/status
	*
	* @body:
	*
	* - client_key
	* - access_key
	*
	* [Optional]
	*
	* - submission_id - passing a submission id will return the details for a specific submission
	* - days
	*   o set the last number of days of submissions you want to be returned
	* - status [0-5]
	*   o 0. No submission received
	*   o 1. No submission received and no errors in technical checks, but report not generated
	*   o 2. Submission received with errors in technical checks, and report not generated
	*   o 3. Report Flagged by user
	*   o 4. CDD generated and all technical checks passed
	*   o 5. CDD generated but some technical checks failed (e.g. manual over-ride was used)
	* - account_id
	* - language
	*
	* @returns:
	*
	* error
	*
	*  {"error": "Unauthenticated."}
	*  {"status": 1, "message": "client key missing"}
	*
	* success
	*
	* {
	*	"submissions": [
	*		{
	*			"appinstance": "462c6d1f",
	*			"submission_id": 26,
	*			"status": 4,
	*			"account_id":,"CUST1003"
	*			"documents": [
	*				{
	*					"document_type": "passport",
	*					"authentication_data": "Pass",
	*					"facial_match": "Pass"
	*				},
	*				{
	*					"document_type": "idcard",
	*					"authentication_data": "Pass",
	*					"facial_match": "Pass"
	*				}
	*			]
	*		}
	*	]
	* }
	*
	*
	* - submissions:
	*   o returns all submissions based on search criteria. Each submission result includes the following details:
	*   - appinstance:
	*   - submission_id:
	*   - status
	*     o 0. No submission received
	*     o 1. Submission received and no errors in technical checks, but report not generated
	*     o 2. Submission received with errors in technical checks, and report not generated
	*     o 3. Report Flagged by user
	*     o 4. CDD generated and all technical checks passed
	*     o 5. CDD generated but some technical checks failed (i.e. manually over-ride was used).
	*   - account_id
	*   - documents
	*   	o document_type
	*    	o authentication_data
	*     	o facial_match
	*
	*
	*/
	function getSubmissionsStatus ($args) {
		if ( empty($args['submission_id']) ) return;
		$result = [
			'status' => 'error',
			'function' => 'getSubmissionsStatus',
		];
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('submission/status'); // die($query_url);
		// $test_data = idPalGetTestData(); // whilst testing
		$auth = getAuthorization(); // get fresh access token
		// Handy\I_Handy::tip($auth);die('here');
		if (!empty($auth['access_token'])) {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $query_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json; charset=utf-8',
					'Authorization:Bearer '.$auth['access_token'],
					'lan:en'
				));
				curl_setopt($ch, CURLOPT_POST, 1);
				$fields = array(
					'client_key' => $credentials['client_key'],
					'access_key' => $credentials['access_key'],
					'submission_id' => $args['submission_id'],
				);
				// Handy\I_Handy::tip($fields); die('here');
				// optional fields
				if (!empty($args['days'])) $fields['days'] = $args['days'];
				if ($args['status']>=0) $fields['status'] = $args['status'];
				// Handy\I_Handy::tip($fields);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$response = curl_exec($ch);
				if ( $response === FALSE ) {
					curl_errno($ch);
					if (curl_errno($ch)) {
						print "Error: " . curl_error($ch);
					}
					curl_close($ch);
				} else {
					$json = json_decode($response, true);
					if ( !empty($json['submissions']) ) {
						$result['status'] = 'success';
					}
					$result['response'] = $json;
				}
			} catch (Exception $e) {
				Handy\I_Handy::tip($e);
			}
		} else {
			$result['response'] = $auth;
		}
		// Handy\I_Handy::tip($result); die();
		return $result;
	}
	/*
	*
	* AppLinkStatus
	*
	* @desc:
	*
	* - returns all submissions made using the app link including their individual details and status.
	*
	* @url:
	*
	* - api/app-link/status
	*
	* @body:
	*
	* - client_key
	* - access_key
	* - uuid (identifies the mobile all submission was made from)
	*
	* [optional]
	*
	* - days
	*   o set the last number of days of submissions you want to be returned
	* - status [0-5]
	*   o 0. No submission received
	*   o 1. No submission received and no errors in technical checks, but report not generated
	*   o 2. Submission received with errors in technical checks, and report not generated
	*   o 3. Report Flagged by user
	*   o 4. CDD generated and all technical checks passed
	*   o 5. CDD generated but some technical checks failed (e.g. manual over-ride was used)
	* - account_id
	* - language
	*
	*
	*/
	function AppLinkStatus ($args) {
		if ( empty($args['uuid']) ) return;
		$result = [
			'status' => 'error',
			'function' => 'AppLinkStatus',
		];
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('app-link/status'); // die($query_url);
		// $test_data = idPalGetTestData(); // whilst testing
		$auth = getAuthorization(); // get fresh access token
		// Handy\I_Handy::tip($auth);die('here');
		if (!empty($auth['access_token'])) {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $query_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json; charset=utf-8',
					'Authorization:Bearer '.$auth['access_token'],
					'lan:en'
				));
				curl_setopt($ch, CURLOPT_POST, 1);
				$fields = array(
					'client_key' => $credentials['client_key'],
					'access_key' => $credentials['access_key'],
					'uuid' => $args['uuid'],
				);
				// optional fields
				if (!empty($args['days'])) $fields['days'] = $args['days'];
				if ($args['status']>=0) $fields['status'] = $args['status'];
				// Handy\I_Handy::tip($fields);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$response = curl_exec($ch);
				if ( $response === FALSE ) {
					curl_errno($ch);
					if (curl_errno($ch)) {
						print "Error: " . curl_error($ch);
					}
					curl_close($ch);
				} else {
					$json = json_decode($response, true);
					if ( !empty($json['submissions']) ) {
						$result['status'] = 'success';
					}
					$result['response'] = $json;
				}
			} catch (Exception $e) {
				Handy\I_Handy::tip($e);
			}
		} else {
			$result['response'] = $auth;
		}
		// Handy\I_Handy::tip($result); die();
		return $result;
	}

	/*
	*
	* getCustomerInformation
	*
	* @desc:
	*
	* - can provide you with a user’s personal details from a submission to the platform.
	*
	* @url:
	*
	* - api/getCustomerInformation
	*
	* @body:
	*
	* - client_key
	* - access_key
	* - submission_id
	*
	* @returns:
	*
	* error
	*
	* success
	*
	*/

	function getCustomerInformation ($args) {
		$result = [
			'status' => 'error',
			'function' => 'getCustomerInformation',
		];
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('getCustomerInformation'); // die($query_url);
		// $test_data = idPalGetTestData(); // whilst testing
		$auth = getAuthorization(); // get fresh access token
		// Handy\I_Handy::tip($auth);die('here');
		if (!empty($auth['access_token'])) {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $query_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json; charset=utf-8',
					'Authorization:Bearer '.$auth['access_token'],
					'lan:en'
				));
				curl_setopt($ch, CURLOPT_POST, 1);
				$fields = array(
					'client_key' => $credentials['client_key'],
					'access_key' => $credentials['access_key'],
					'submission_id' => $args['submission_id'],
				);
				// Handy\I_Handy::tip($fields);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$response = curl_exec($ch);
				if ( $response === FALSE ) {
					curl_errno($ch);
					if (curl_errno($ch)) {
						print "Error: " . curl_error($ch);
					}
					curl_close($ch);
				} else {
					$json = json_decode($response, true);
					/*if ( $json['status'] > 0 ) {
						$result['status'] = 'error';
					} elseif ( !empty($json['error']) ) {
						$result['status'] = 'error';
					} else {
						$result['status'] = 'success';
					}*/
					$result['response'] = $json;
				}
			} catch (Exception $e) {
				Handy\I_Handy::tip($e);
			}
		} else {
			$result['response'] = $auth;
		}
		// Handy\I_Handy::tip($result); die();
		return $result;
	}

	/*
	*
	* getCDDReport
	*
	* @desc:
	*
	* - provides a completed customer due diligence report in PDF format which can be saved and viewed. This service request will
	*   return a complete customer due diligence report inPDF format which may be viewed and saved.
	*
	* @url:
	*
	* - api/getCDDReport
	*
	* @body:
	*
	* - client_key
	* - access_key
	* - submission_id
	*
	* @returns:
	*
	* error
	*
	* success
	*
	*/

	function getCDDReport ($args) {
		$result = [
			'status' => 'error',
			'function' => 'getCDDReport',
		];
		$credentials = idPalGetCredentials();
		$query_url = idPalGetQueryUrl('getCDDReport'); // die($query_url);
		// $test_data = idPalGetTestData(); // whilst testing
		$auth = getAuthorization(); // get fresh access token
		// Handy\I_Handy::tip($auth);die('here');
		if (!empty($auth['access_token'])) {
			try {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_URL, $query_url);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Accept: application/json; charset=utf-8',
					'Authorization:Bearer '.$auth['access_token'],
					'lan:en'
				));
				curl_setopt($ch, CURLOPT_POST, 1);
				$fields = array(
					'client_key' => $credentials['client_key'],
					'access_key' => $credentials['access_key'],
					'submission_id' => $args['submission_id'],
				);
				// Handy\I_Handy::tip($fields); die();
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
				$response = curl_exec($ch);
				if ( $response === FALSE ) {
					curl_errno($ch);
					if (curl_errno($ch)) {
						print "Error: " . curl_error($ch);
					}
					curl_close($ch);
				} else {
					$result['response'] = $response;
				}
			} catch (Exception $e) {
				Handy\I_Handy::tip($e);
			}
		} else {
			$result['response'] = $auth;
		}
		// Handy\I_Handy::tip($result); die();
		return $result;
	}
