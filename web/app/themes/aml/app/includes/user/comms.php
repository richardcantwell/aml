<?php

	/*
	*
	* communication functions
	*
	*/

	namespace Custom\User\Comms;

	use Custom\Classes\Handy;

	/*
	*
	* change the FROM email (not sure why we have to do this?) - but this is the key to 
	* this sending email
	*
	*/

	add_filter( 'wp_mail_from', __NAMESPACE__ . '\\change_sender_email' );
	function change_sender_email( $original_email_address ) {
		return 'aml@tynandillon.ie';
	}

	add_filter( 'wp_mail_from_name', __NAMESPACE__ . '\\change_sender_name' );
	function change_sender_name( $original_email_from ) {
		return 'AML @ Tynan Dillon';
	}

	/*
	*
	* helper function to email the user
	*
	* see https://developer.wordpress.org/reference/functions/wp_mail/
	*
	*/

	function emailUser ( $data ) {

		if ( empty($data['to']) ) return;

		// $site_title = get_bloginfo( 'name' );
		// $admin_email = get_option( 'admin_email' );

		$defaults = [
			'headers' => [
				'Content-Type' => 'text/html; charset=UTF-8',
				// 'From' => 'John Q Codex <jqc@wordpress.org>',
				// 'Cc' => 'user@iluvwp@email.test, user2@iluvwp@email.test'; // or note you can just use a simple email address
				// 'Bcc' => "",
			],
		];

		$data = Handy\I_Handy::i_wp_parse_args( $data, $defaults );

		if ( !empty($data['headers']) ) {
			$headers = [];
			foreach ($data['headers']  as $k=>$v) {
				array_push($headers, "$k: $v");
			}
		}

		$args = [
			'to' => $data['to'],
			'subject' => (!empty($data['subject'])?$data['subject']:''),
			'body' => (!empty($data['body'])?$data['body']:''),
			'headers' => $headers,
		];

		// error_log('[user.php -> emailUser() | args: ]' . print_r($args,1));

		$result = wp_mail( $args['to'], $args['subject'], $args['body'], $args['headers'] );

		// Handy\I_Handy::tip($args); echo $result; die('DEBUGGING');

		return $result;

	}
