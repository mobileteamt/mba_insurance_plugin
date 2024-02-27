<?php
namespace Sycustom\api;

class User{

	public function __construct(){
		add_action('init', [$this, 'newFilterAdd'], 10);
	}

	public function user_routes(){
		register_rest_route(
			SYI_NAMESSPACE,
			'create_user',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'createUser'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'dashboard',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'dashBoard'),
				'permission_callback' =>  function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'update_user',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'updateProfile'),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'change_password',
			array(
				'methods' => 'PATCH',
				'callback' => array($this, 'changePassword'),
				'permission_callback' => function () {
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'profile',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getProfile'),
				'permission_callback' => function () {
					//return current_user_can( 'edit_others_posts' );
					return is_user_logged_in();
				},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'reset_password',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'resetPassLink'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'valid_the_code',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'validAPICode'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'login',
			array(
				'methods' => 'POST',
				'callback' => array($this, 'generate_token'),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'reset_password',
			array(
				'methods' => 'PATCH',
				'callback' => array($this, 'resetPass'),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function generate_token( \WP_REST_Request $request ) {
		$username   = $request->get_param( 'username' );
		$password   = $request->get_param( 'password' );
		//pr( has_filter('authenticate') );
		$_SESSION['from_api'] = true;
		try{
			$user = wp_authenticate( $username, $password );
		}catch(\Exception $e){
			$error = new \WP_Error;
			$error->add(404, __("Passwordwa field 'password' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();
			$error 		= new \WP_Error;
			$error->add(404, __("Passworddd field 'password' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		$ch 		= curl_init();
		$url 		= 'http://104.211.25.86:6085/wp-json/jwt-auth/v1/token';
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request->get_params());
		$result  	= curl_exec($ch);
		curl_close($ch);
		unset($_SESSION['from_api']);
		//pr( $result );
		//echo 'ppp';//json_encode($result);
		die;
	}

	public function newFilterAdd(){
		add_filter( 'authenticate', array( $this, 'new_direction' ), 20, 3 );
	}

	public function new_direction( $user, $username, $password ) {
		if( isset( $_SESSION['from_api'] ) ){
		    if ( is_wp_error( $user ) ) {
		      	$error = new \WP_Error;
		      	$error_code = $user->get_error_code();
		      	$error_message = $user->get_error_message();
				$error->add(400, __($error_message, 'wp-rest-user'), array('status' => 400));
				echo json_encode( $error );
		      	die;
		    }
	    }
	  	return $user;
	}

	public function createUser( \WP_REST_Request $request )	{
		global $wpdb;
		$response 				= array();
		$parameters 			= $request;
		$username 				= sanitize_user($parameters['username']);
		$email 					= sanitize_email($parameters['email']);
		$password 				= sanitize_text_field($parameters['password']);
		$first_name 			= sanitize_text_field($parameters['first_name']);
		$last_name 				= sanitize_text_field($parameters['last_name']);
		$dob 					= sanitize_text_field($parameters['dob']);
		$flat_number 			= sanitize_text_field($parameters['flat_number']);
		$building_number 		= sanitize_text_field($parameters['building_number']);
		$building_name 			= sanitize_text_field($parameters['building_name']);
		$street 				= sanitize_text_field($parameters['street']);
		$town 					= sanitize_text_field($parameters['town']);
		$state 					= sanitize_text_field($parameters['state']);
		$postcode 				= sanitize_text_field($parameters['postcode']);
		$country 				= sanitize_text_field($parameters['country']);
		$phone 					= sanitize_text_field($parameters['phone']);
		$license_state 			= !empty( $parameters['license_state']) ? sanitize_text_field($parameters['license_state']) : '';
		$license_number 		= !empty( $parameters['license_number'] ) ? sanitize_text_field($parameters['license_number']) : '';
		$role 					= sanitize_text_field($parameters['role']);
		$profile_pic 			= !empty($parameters['profile_pic']) ? sanitize_text_field($parameters['profile_pic']) : '';
		$driving_license 		= !empty($parameters['driving_license']) ? sanitize_text_field($parameters['driving_license']) : '';
		$error = new \WP_Error();
		if (empty($username)) {
			$error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($email)) {
			$error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($password)) {
			$error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($first_name) || empty($last_name)) {
			$error->add(400, __("Firstname and Last name is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($country) || empty($postcode) || empty($state) || empty($street)) {
			$error->add(400, __("Please enter proper address.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($dob)) {
			$error->add(400, __("Please enter date of birth.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if ($role != 'owner') {
			$role = 'guest';
			/*if (empty($license_number)) {
				$error->add(400, __("Please enter Driving license number.", 'wp-rest-user'), array('status' => 400));
				return $error;
			}
			if (empty($license_state)) {
				$error->add(400, __("Please enter Driving license Issued State.", 'wp-rest-user'), array('status' => 400));
				return $error;
			}*/
		}
		$user_id = username_exists($username);
		if (!$user_id && email_exists($email) == false) {
			$user_id = wp_create_user($username, $password, $email);
			if (!is_wp_error($user_id)) {
				$metas = [
					'first_name' 				=> $first_name,
					'last_name' 				=> $last_name,
					'dob' 						=> $dob,
					'_renter_dob' 				=> $dob,
					'flat_number' 				=> $flat_number,
					'building_number' 			=> $building_number,
					'billing_address_1' 		=> $building_name,
					'billing_city' 				=> $town,
					'billing_address_2' 		=> $street,
					'billing_state' 			=> $state,
					'billing_postcode' 			=> $postcode,
					'billing_country' 			=> $country,
					'billing_phone' 			=> $phone,
					'listeo_core_avatar_id' 	=> $profile_pic,
					'_driving_license' 			=> $driving_license,
				];
				if ($role == 'guest') {
					$metas['_renter_license_state'] 	= $license_state;
					$metas['_renter_license_number'] 	= $license_number;
				}
				foreach ($metas as $key => $val) {
					if ($val) {
						update_user_meta($user_id, $key, $val);
					}
				}
				$meta_data = get_user_meta($user_id);
				$user = get_user_by('id', $user_id);
				$user->set_role($role);
				$response['code'] 			= 200;
				$response['meta_data'] 		= $meta_data;
				$response['message'] 		= $user;
			} else {
				return $user_id;
			}
		} else {
			$error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		return new \WP_REST_Response($response, 123);
	}

	public function updateProfile( \WP_REST_Request $request ){
		$user_id 				= get_current_user_id();
		$user 					= get_user_by('id', $user_id);
		$role 					= $user->get_role();
		$response 				= array();
		$parameters 			= $request;
		$first_name 			= sanitize_text_field($parameters['first_name']);
		$last_name 				= sanitize_text_field($parameters['last_name']);
		$dob 					= sanitize_text_field($parameters['dob']);
		$flat_number 			= sanitize_text_field($parameters['flat_number']);
		$building_number 		= sanitize_text_field($parameters['building_number']);
		$building_name 			= sanitize_text_field($parameters['building_name']);
		$street 				= sanitize_text_field($parameters['street']);
		$town 					= sanitize_text_field($parameters['town']);
		$state 					= sanitize_text_field($parameters['state']);
		$postcode 				= sanitize_text_field($parameters['postcode']);
		$country 				= sanitize_text_field($parameters['country']);
		$phone 					= sanitize_text_field($parameters['phone']);
		$license_state 			= sanitize_text_field($parameters['license_state']);
		$license_number 		= sanitize_text_field($parameters['license_number']);
		$profile_pic 			= !empty($parameters['profile_pic']) ? sanitize_text_field($parameters['profile_pic']) : '';
		$driving_license 		= !empty($parameters['driving_license']) ? sanitize_text_field($parameters['driving_license']) : '';
		if (empty($first_name) || empty($last_name)) {
			$error->add(400, __("Firstname and Last name is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($country) || empty($postcode) || empty($state) || empty($street)) {
			$error->add(400, __("Please enter proper address.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if (empty($dob)) {
			$error->add(400, __("Please enter date of birth.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		if ($role != 'owner') {
			$role = 'guest';
			if (empty($license_number)) {
				$error->add(400, __("Please enter Driving license number.", 'wp-rest-user'), array('status' => 400));
				return $error;
			}
			if (empty($license_state)) {
				$error->add(400, __("Please enter Driving license Issued State.", 'wp-rest-user'), array('status' => 400));
				return $error;
			}
		}
		if (!empty($user_id)) {
			$metas = [
				'first_name' 			=> $first_name,
				'last_name' 			=> $last_name,
				'dob' 					=> $dob,
				'_renter_dob' 			=> $dob,
				'flat_number' 			=> $flat_number,
				'building_number' 		=> $building_number,
				'billing_address_1' 	=> $building_name,
				'billing_city' 			=> $town,
				'billing_address_2' 	=> $street,
				'billing_state' 		=> $state,
				'billing_postcode' 		=> $postcode,
				'billing_country' 		=> $country,
				'billing_phone' 		=> $phone,
				'listeo_core_avatar_id' => $profile_pic,
				'_driving_license' 		=> $driving_license,
			];
			if ($role == 'guest') {
				$metas['_renter_license_state'] 	= $license_state;
				$metas['_renter_license_number'] 	= $license_number;
			}
			foreach ($metas as $key => $val) {
				if ($val) {
					update_user_meta($user_id, $key, $val);
				}
			}
			$meta_data 						= get_user_meta($user_id);
			$response['code'] 				= 200;
			$response['meta_data'] 			= $meta_data;
			$response['message'] 			= $user;
		} else {
			$error->add(406, __("No User Found", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		return new \WP_REST_Response($response, 123);
	}

	public function getProfile()	{
		$data = [];
		$user = wp_get_current_user();
		$user_id = $user->ID;
		$profile_image_id = get_user_meta($user_id, 'listeo_core_avatar_id', true);
		$profile_image = wp_get_attachment_url($profile_image_id);
		
		$avatar_image = get_avatar_url($user_id);
		$role = '';
		if (in_array('owner', $user->roles, true)) {
			$role = 'owner';
		}
		if (in_array('guest', $user->roles, true)) {
			$role = 'guest';
		}
		$data['user_id'] 		= $user_id;
		$data['user_name'] 		= $user->user_login;
		$data['email'] 			= $user->user_email;
		$data['first_name'] 	= get_user_meta($user_id, 'first_name', true);
		$data['last_name'] 		= get_user_meta($user_id, 'last_name', true);
		$data['description'] 	= get_user_meta($user_id, 'description', true);
		$data['role'] 			= $role;
		$data['mobile'] 		= '';
		$data['location'] 		= '';
		$data['profile_image'] 	= $profile_image;
		$data['avatar_image'] 	= $avatar_image;
		$data['banner_image'] 	= '';
		//$data = wp_get_current_user();
		//$data->avatar = get_avatar(get_current_user_id());
		//$data->meta = get_user_meta(get_current_user_id());
		return ['data' => $data, 'status' => 'success'];
	}

	public function changePassword( \WP_REST_Request $req )	{
		$user_id 		= get_current_user_id();
		$user 			= get_user_by('id', $user_id);
		$request 		= $req->get_params();
		$password 		= $request['old_password'];
		$new_password 	= $request['new_password'];
		if (empty($user_id)) {
			$json = array('code' => '0', 'msg' => 'Please enter user id');
			echo json_encode($json);
			exit;
		}
		if (empty($password)) {
			$json = array('code' => '0', 'msg' => 'Please enter old password');
			echo json_encode($json);
			exit;
		}
		if (empty($new_password)) {
			$json = array('code' => '0', 'msg' => 'Please enter new password');
			echo json_encode($json);
			exit;
		}
		$hash 	= $user->data->user_pass;
		$code 	= 500;
		$status = false;
		if (wp_check_password($password, $hash)) {
			$code 					= 200;
			$status 				= true;
			wp_set_password($new_password, $user_id);
			$response['code'] 		= 200;
			$response['message'] 	= 'Password updated successfully';
		} else {
			$response['message'] 	= 'Current password does not match.';
		}
		return new \WP_REST_Response($response, 123);
	}

	public function resetPassLink( \WP_REST_Request $request )	{
		if (empty($request['email']) || $request['email'] === '') {
			return new \WP_Error('no_email', __('You must provide an email address.', 'bdvs-password-reset'), array('status' => 400));
		}
		$exists = email_exists($request['email']);
		if (!$exists) {
			return new \WP_Error('bad_email', __('No user found with this email address.', 'bdvs-password-reset'), array('status' => 500));
		}
		try {
			$user = bdpwr_get_user($exists);
			$user->send_reset_code();
		} catch (\Exception $e) {
			return new \WP_Error('bad_request', $e->getMessage(), array('status' => 500));
		}
		$response['status'] = 200;
		$response['message'] = __('A password reset email has been sent to your email address.', 'bdvs-password-reset');
		return new \WP_REST_Response($response, 123);
	}

	public function validAPICode( \WP_REST_Request $request ){
		if (empty($request['email']) || $request['email'] === '') {
			return new \WP_Error('no_email', __('You must provide an email address.', 'bdvs-password-reset'), array('status' => 400));
		}
		if (empty($request['token_code']) || $request['token_code'] === '') {
			return new \WP_Error('no_code', __('You must provide a code.', 'bdvs-password-reset'), array('status' => 400));
		}
		$exists = email_exists($request['email']);
		if (!$exists) {
			return new \WP_Error('bad_email', __('No user found with this email address.', 'bdvs-password-reset'), array('status' => 500));
		}

		try {
			$user = bdpwr_get_user($exists);
			$user->validate_code($request['token_code']);
		} catch (\Exception $e) {
			return new \WP_Error('bad_request', $e->getMessage(), array('status' => 500));
		}
		$response['status'] = 200;
		$response['message'] = __('The code supplied is valid.', 'bdvs-password-reset');
		return new \WP_REST_Response($response, 123);
	}

	public function resetPass( \WP_REST_Request $req ){
		$request = $req->get_params();
		if (empty($request['email']) || $request['email'] === '') {
			return new \WP_Error('no_email', __('You must provide an email address.', 'bdvs-password-reset'), array('status' => 400));
		}
		if (empty($request['code']) || $request['code'] === '') {
			return new \WP_Error('no_code', __('You must provide a code.', 'bdvs-password-reset'), array('status' => 400));
		}
		if (empty($request['password']) || $request['password'] === '') {
			return new \WP_Error('no_code', __('You must provide a new password.', 'bdvs-password-reset'), array('status' => 400));
		}
		$exists = email_exists($request['email']);
		if (!$exists) {
			return new \WP_Error('bad_email', __('No user found with this email address.', 'bdvs-password-reset'), array('status' => 500));
		}
		try {
			$user = bdpwr_get_user($exists);
			$user->validate_code($request['code']);
			//pr($user);
		} catch (\Exception $e) {
			return new \WP_Error('Code has been expired', $e->getMessage(), array('status' => 500));
		}

		try {
			$user = bdpwr_get_user($exists);
			$user->set_new_password($request['code'], $request['password']);
		} catch (\Exception $e) {
			return new \WP_Error('bad_request', $e->getMessage(), array('status' => 500));
		}
		$response['status'] = 200;
		$response['message'] = __('Password updated successfully.', 'bdvs-password-reset');
		return new \WP_REST_Response($response, 123);
	}

	public function dashBoard( \WP_REST_Request $req ){
		$user = wp_get_current_user();
		$user_id = $user->ID;
		$r_data = [];
		$r_data['active_listing'] = count_user_posts( $user->ID , 'listing' ); 
		$r_data['total_views'] = get_user_meta( $user->ID, 'listeo_total_listing_views', true );
		$r_data['total_reviews'] = listeo_count_user_comments(
	    array(
	        'author_id' => $user->ID , // Author ID
	        'author_email' => $user->user_email, // Author ID
	        'approved' => 1, // Approved or not Approved
	    	)
			);
		$r_data['total_bookmarks'] = get_user_meta( $user->ID, 'listeo_total_listing_bookmarks', true );
		$users = new \Listeo_Core_Users;
	    $listings = $users->get_agent_listings('',0,-1);
	    $args = array (
	        'owner_id' => get_current_user_id(),
	        'type' => 'reservation',
	    );
	    $r_data['bookings'] = \Listeo_Core_Bookings_Calendar :: get_newest_bookings( $args );
	    $listireo_core_message = new \Listeo_Core_Messages();
	    $r_data['messages'] = $listireo_core_message->get_conversations( $user_id );
	    $listireo_core_bookmarks = new \Listeo_Core_Bookmarks();
	    $r_data['bookmarks'] = $listireo_core_bookmarks->get_bookmarked_posts();
	    $visitor_reviews_args = array(
				'post_author' 	=> $user_id,
				'parent'      	=> 0,
				'status' 				=> 'approve',
				'post_type' 		=> 'listing',
			);
		$r_data['visitor_reviews'] = get_comments( $visitor_reviews_args );
		$your_reviews_args = array(
			'author__in' 	=> array($user_id),
			'post_type' 	=> 'listing',
			'status' 			=> 'all',
			'parent'      => 0,
		);
		$r_data['your_reviews'] = get_comments( $your_reviews_args ); 
		return $r_data;
	}

}