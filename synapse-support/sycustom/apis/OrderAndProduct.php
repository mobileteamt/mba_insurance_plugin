<?php

namespace Sycustom\api;

class OrderAndProduct {

	public function order_routes() {

		register_rest_route(
			SYI_NAMESSPACE,
			'set_insurance',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'setInsurance' ),
				'permission_callback' => function () {
			    	$user = wp_get_current_user();
					if ( in_array( 'guest', (array) $user->roles ) || in_array( 'owner', (array) $user->roles ) ) {
					    return true;
					}else{
						return false;
					}
			  	},
			)
		);
	
		register_rest_route(
			SYI_NAMESSPACE,
			'get_insurance_quote',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'getQuote' ),
				'permission_callback' => function () {
			    	$user = wp_get_current_user();
					if ( in_array( 'guest', (array) $user->roles ) || in_array( 'owner', (array) $user->roles ) ) {
					    return true;
					}else{
						return false;
					}
			  	},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'insurance_documents',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'retrieveDocument' ),
				'permission_callback' => function () {
			    	$user = wp_get_current_user();
					if ( in_array( 'guest', (array) $user->roles ) || in_array( 'owner', (array) $user->roles ) ) {
					    return true;
					}else{
						return false;
					}
			  	},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'create_insurance',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'createAddendum' ),
				'permission_callback' => function () {
			    	$user = wp_get_current_user();
					if ( in_array( 'guest', (array) $user->roles ) || in_array( 'owner', (array) $user->roles ) ) {
					    return true;
					}else{
						return false;
					}
			  	},
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'cancel_insurance',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'cancelRedendum' ),
				'permission_callback' => function () {
			    	$user = wp_get_current_user();
					if ( in_array( 'guest', (array) $user->roles ) || in_array( 'owner', (array) $user->roles ) ) {
					    return true;
					}else{
						return false;
					}
			  	},
			)
		);
		
	}

	public function setInsurance( \WP_REST_Request $req ){
		if( !empty( $req['ins_type'] ) ){
			$_SESSION['ins_type'] =  $req['ins_type'];
		}else{
			unset($_SESSION['ins_type']);
		}
		if( !empty( $req['roadside'] ) && $req['roadside'] == 'yes' ){
			$_SESSION['roadside'] =  'yes';
		}else{
			unset($_SESSION['ins_type']);
		}
		return ['success'=> true, 'msg'=> 'Insurance has been updated'];
	}

	public function getQuote( \WP_REST_Request $req ){
  		$error = new \WP_Error();
		if( !empty($req['list_id']) ){
			$list_id 				= $req[ 'list_id' ];
			$rental_category 		= get_the_terms( $list_id, 'rental_category' );
			$rental_cat 			= !empty( $rental_category[0] )? $rental_category[0]->name:'';
			$list_meta 				= array_map( function( $a ){ return $a[0]; }, get_post_meta( $list_id ) );
			$list 					= get_post( $list_id );
			$owner_details 			= array_map( function( $a ){ return $a[0]; }, get_user_meta( $list->post_author ) );
			$user_id 				= get_current_user_id();
			$user 					= get_user_by('id', $user_id);
			$renter_data 			= array_map( function( $a ){ return $a[0]; }, get_user_meta( $user_id ) );
			if( isset( $_SESSION['roadside'] ) ){
		    	$roadside = 'yes';
		    }else{
		    	$roadside = 'no';
		    }
		    if( isset( $_SESSION['ins_type'] ) ){
		    	$ins_type = $_SESSION['ins_type'];
		    }else{
		    	$error->add(400, __("Please choose insurance Plan first", 'wp-rest-user'), array('status' => 400));
	      		return $error;
		    }
			if( !empty( $req['start_date'] ) ){
				$start_date = date('Y-m-d H:i:s', strtotime( $req['start_date'] ) );
			}else{
				$error->add(400, __("Please provide Start Date", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			if( !empty( $req['end_date'] ) ){
				$end_date = date('Y-m-d H:i:s', strtotime( $req['end_date'] ) );
			}else{
				$error->add(400, __("Please provide End Date", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			$req_body = [
				"booking_id" 					=> "ABC-123",
				"package_id" 					=> $ins_type,
				"liability_only" 				=> $roadside,
				"start_date" 					=> $start_date,
				"end_date" 						=> $end_date,
				"owner_first_name" 				=> !empty( $owner_details['first_name'] ) ? $owner_details['first_name']:'',
				"owner_last_name" 				=> !empty( $owner_details['last_name'] ) ? $owner_details['last_name']:'',
				"owner_street" 					=> !empty( $owner_details['billing_address_1'] ) ? $owner_details['billing_address_1']:'',
				"owner_street_two" 				=> !empty( $owner_details['billing_address_2'] ) ? $owner_details['billing_address_2']:'',
				"owner_city" 					=> !empty( $owner_details['billing_city'] ) ? $owner_details['billing_city']:'',
				"owner_state" 					=> !empty( $owner_details['billing_state'] ) ? $owner_details['billing_state']:'',
				"owner_zip" 					=> !empty( $owner_details['billing_postcode'] ) ? $owner_details['billing_postcode']:'',
				"owner_country" 				=> !empty( $owner_details['billing_country'] ) ? $owner_details['billing_country']:'',
				"owner_email" 					=> !empty( $owner_details['billing_email'] ) ? $owner_details['billing_email']:'',
				"owner_phone" 					=> !empty( $owner_details['billing_phone'] ) ? $owner_details['billing_phone']:'',
				"vehicle_type" 					=> 'Toy Hauler Trailer',//!empty( $rental_cat ) ? $rental_cat : '',
				"vehicle_year" 					=> !empty( $list_meta['_year'] ) ? $list_meta['_year'] : '',
				"vehicle_make" 					=> !empty( $list_meta['_manufacturer'] ) ? $list_meta['_manufacturer'] : '',
				"vehicle_model" 				=> !empty( $list_meta['_model'] ) ? $list_meta['_model'] : '',
				"vehicle_trim" 					=> !empty( $list_meta['_vehicle_trim'] ) ? $list_meta['_vehicle_trim'] : '',
				"vehicle_vin" 					=> !empty( $list_meta['_vehicle_vin'] ) ?  $list_meta['_vehicle_vin'] : '',
				"vehicle_value" 				=> !empty( $list_meta['_vehicle_value'] ) ? $list_meta['_vehicle_value'] : '',
				"vehicle_length" 				=> !empty( $list_meta['_length'] ) ? $list_meta['_length'] : '',
				"salvage_title" 				=> "no",
				"renter_first_name" 			=> !empty( $renter_data['first_name'] ) ? $renter_data['first_name'] : '',
				"renter_last_name" 				=> !empty( $renter_data['last_name'] ) ? $renter_data['last_name'] : '',
				"renter_street" 				=> !empty( $renter_data['billing_address_1'] ) ? $renter_data['billing_address_1'] : '',
				"renter_street_two" 			=> !empty( $renter_data['billing_address_2'] ) ? $renter_data['billing_address_2'] : '',
				"renter_city" 					=> !empty( $renter_data['billing_city'] ) ? $renter_data['billing_city'] : '',
				"renter_state" 					=> !empty( $renter_data['billing_state'] ) ? $renter_data['billing_state'] : '',
				"renter_zip" 					=> !empty( $renter_data['billing_postcode'] ) ? $renter_data['billing_postcode'] : '',
				"renter_country" 				=> !empty( $renter_data['billing_country'] ) ? $renter_data['billing_country'] : '',
				"renter_email" 					=> !empty( $renter_data['billing_email'] ) ? $renter_data['billing_email'] : '',
				"renter_phone" 					=> !empty( $renter_data['billing_phone'] ) ? $renter_data['billing_phone'] : '',
				"renter_license_state" 			=> !empty( $renter_data['_renter_license_state'] ) ? $renter_data['_renter_license_state'] : '',
				"international_drivers_license" => "no",
				"renter_license_number" 		=> !empty( $renter_data['_renter_license_number'] ) ? $renter_data['_renter_license_number'] : '',
				"renter_dob" 					=> !empty($renter_data['dob']) ? date('Y-m-d', strtotime($renter_data['dob'])):'',
				"renter_bg_check_status" 		=> "approved",
			];
			$check_empty = array_keys($req_body, null, true);
			if( !empty( $check_empty ) ){
				$error->add(400, __("some details are yet to update", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			try{
				$ch = curl_init();
				$url = 'https://mbapartnerconnect.biz/v1/quote/create/';
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Mba-Partner-Connect-Key: 8D1D746B5D22BED42312FBD914CAF'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req_body);
				$result  = curl_exec($ch);
				curl_close($ch);
				//echo json_encode($result);
			}catch( \Exception $e ){
				$error->add(400, __($e->getMessage(), 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			die;
		}
	}

	public function createAddendum( \WP_REST_Request $req ){
  		$error = new \WP_Error();
		if ( !empty($req['list_id']) && !empty($req['booking_id']) ){
			$list_id 			= $req['list_id'];
			$rental_category 	= get_the_terms( $list_id, 'rental_category' );
			$rental_cat 		= !empty( $rental_category[0] )? $rental_category[0]->name:'';
			$list_meta 			= array_map( function( $a ){ return $a[0]; }, get_post_meta( $list_id ) );
			$list 				= get_post( $list_id );
			$owner_details 		= array_map( function( $a ){ return $a[0]; }, get_user_meta( $list->post_author ) );
			$user_id 			= get_current_user_id();
			$user 				= get_user_by('id', $user_id);
			$renter_data 		= array_map( function( $a ){ return $a[0]; }, get_user_meta( $user_id ) );
			if( isset( $_SESSION['roadside'] ) ){
		    	$roadside = 'on';
		    }else{
		    	$roadside = 'off';
		    }
		    if( isset( $_SESSION['ins_type'] ) ){
		    	$ins_type = $_SESSION['ins_type'];
		    }else{
		    	$error->add(400, __("Please choose insurance Plan first", 'wp-rest-user'), array('status' => 400));
	      		return $error;
		    }
			if( !empty( $req['start_date'] ) ){
				$start_date = date('Y-m-d H:i:s', strtotime( $req['start_date'] ) );
			}else{
				$error->add(400, __("Please provide Start Date", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			if( !empty( $req['end_date'] ) ){
				$end_date = date('Y-m-d H:i:s', strtotime( $req['end_date'] ) );
			}else{
				$error->add(400, __("Please provide End Date", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			$req_body = [
				"booking_id" 				=> $req['booking_id'],
				"package_id" 				=> $ins_type,
				"liability_only" 			=> 'no',
				"start_date" 				=> $start_date,
				"end_date" 					=> $end_date,
				"owner_first_name" 			=> !empty( $owner_details['first_name'] ) ? $owner_details['first_name']:'',
				"owner_last_name" 			=> !empty( $owner_details['last_name'] ) ? $owner_details['last_name']:'',
				"owner_street" 				=> !empty( $owner_details['billing_address_1'] ) ? $owner_details['billing_address_1']:'',
				"owner_street_two" 			=> !empty( $owner_details['billing_address_2'] ) ? $owner_details['billing_address_2']:'',
				"owner_city" 				=> !empty( $owner_details['billing_city'] ) ? $owner_details['billing_city']:'',
				"owner_state" 				=> !empty( $owner_details['billing_state'] ) ? $owner_details['billing_state']:'',
				"owner_zip" 				=> !empty( $owner_details['billing_postcode'] ) ? $owner_details['billing_postcode']:'',
				"owner_country" 			=> !empty( $owner_details['billing_country'] ) ? $owner_details['billing_country']:'',
				"owner_email" 				=> !empty( $owner_details['billing_email'] ) ? $owner_details['billing_email']:'',
				"owner_phone" 				=> !empty( $owner_details['billing_phone'] ) ? $owner_details['billing_phone']:'',
				"vehicle_type" 				=> !empty( $rental_cat ) ? $rental_cat : '',
				"vehicle_year" 				=> !empty( $list_meta['_year'] ) ? $list_meta['_year'] : '',
				"vehicle_make" 				=> !empty( $list_meta['_manufacturer'] ) ? $list_meta['_manufacturer'] : '',
				"vehicle_model" 			=> !empty( $list_meta['_model'] ) ? $list_meta['_model'] : '',
				"vehicle_trim" 				=> !empty( $list_meta['_vehicle_trim'] ) ? $list_meta['_vehicle_trim'] : '',
				"vehicle_vin" 				=> !empty( $list_meta['_vehicle_vin'] ) ?  $list_meta['_vehicle_vin'] : '',
				"vehicle_value" 			=> !empty( $list_meta['_vehicle_value'] ) ? $list_meta['_vehicle_value'] : '',
				"vehicle_length" 			=> !empty( $list_meta['_length'] ) ? $list_meta['_length'] : '',
				"salvage_title" 			=> "no",
				"renter_first_name" 		=> !empty( $renter_data['first_name'] ) ? $renter_data['first_name'] : '',
				"renter_last_name" 			=> !empty( $renter_data['last_name'] ) ? $renter_data['last_name'] : '',
				"renter_street" 			=> !empty( $renter_data['billing_address_1'] ) ? $renter_data['billing_address_1'] : '',
				"renter_street_two" 		=> !empty( $renter_data['billing_address_2'] ) ? $renter_data['billing_address_2'] : '',
				"renter_city" 				=> !empty( $renter_data['billing_city'] ) ? $renter_data['billing_city'] : '',
				"renter_state" 				=> !empty( $renter_data['billing_state'] ) ? $renter_data['billing_state'] : '',
				"renter_zip" 				=> !empty( $renter_data['billing_postcode'] ) ? $renter_data['billing_postcode'] : '',
				"renter_country" 			=> !empty( $renter_data['billing_country'] ) ? $renter_data['billing_country'] : '',
				"renter_email" 				=> !empty( $renter_data['billing_email'] ) ? $renter_data['billing_email'] : '',
				"renter_phone" 				=> !empty( $renter_data['billing_phone'] ) ? $renter_data['billing_phone'] : '',
				"renter_license_state" 		=> !empty( $renter_data['_renter_license_state'] ) ? $renter_data['_renter_license_state'] : '',
				"international_drivers_license" 	=> "no",
				"renter_license_number" 			=> !empty( $renter_data['_renter_license_number'] ) ? $renter_data['_renter_license_number'] : '',
				"renter_dob" 						=> !empty($renter_data['dob']) ? date('Y-m-d', strtotime($renter_data['dob'])):'',
				"renter_bg_check_status" 			=> "approved",
				"credit_card_name" 					=> "John Adams",
				"credit_card_number" 				=> $_SESSION['card_number'],
				"credit_card_csc" 					=> $_SESSION['cvc'],
				"credit_card_exp_month" 			=> $_SESSION['exp_month'],
				"credit_card_exp_year" 				=> $_SESSION['exp_year'],
				"credit_card_address" 				=> !empty( $_SESSION['billing_details']['address'] ) ? $_SESSION['billing_details']['address'] : $owner_details['billing_address_1'],
				"credit_card_city" 					=> !empty( $_SESSION['billing_details']['city'] ) ? $_SESSION['billing_details']['city'] : $owner_details['billing_city'],
				"credit_card_state" 				=> !empty( $_SESSION['billing_details']['state'] ) ? $_SESSION['billing_details']['state'] : $owner_details['billing_state'],
				"credit_card_zip" 					=> !empty( $_SESSION['billing_details']['zip'] ) ? $_SESSION['billing_details']['zip'] : $owner_details['billing_postcode'],
				"roadside" 							=> $roadside,
			];
			$check_empty = array_keys($req_body, null, true);
			if( !empty( $check_empty ) ){
				$error->add(400, __("some details are yet to update", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			//pr($req_body);
			try{
				$ch = curl_init();
				$url = 'https://mbapartnerconnect.biz/v1/addendum/create/';
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Mba-Partner-Connect-Key: 8D1D746B5D22BED42312FBD914CAF'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req_body);
				$result  = curl_exec($ch);
				curl_close($ch);
				//echo json_encode($result);
			}catch( \Exception $e ){
				$error->add(400, __($e->getMessage(), 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			die;
		}
	}

	public function retrieveDocument( \WP_REST_Request $req ){
  		$error = new \WP_Error();
		if ( !empty($req['booking_id']) ){
			$req_body = [
				"booking_id" 			=> $req['booking_id'],
				"type" 					=> "addendum",
			];
			$check_empty = array_keys($req_body, null, true);
			if( !empty( $check_empty ) ){
				$error->add(400, __("some details are yet to update", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			try{
				$ch = curl_init();
				$url = 'https://mbapartnerconnect.biz/v1/addendum/retrieve/';
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Mba-Partner-Connect-Key: 8D1D746B5D22BED42312FBD914CAF'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req_body);
				$result  = curl_exec($ch);
				curl_close($ch);
				//echo json_encode($result);
			}catch( \Exception $e ){
				$error->add(400, __($e->getMessage(), 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			die;
		}
	}

	public function cancelRedendum( \WP_REST_Request $req ){
  		$error = new \WP_Error();
		if ( !empty($req['booking_id']) ){
			$req_body = [
				"booking_id" 					=> $req['booking_id'],
				"cancel_effective_date" 		=> date('Y-m-d', strtotime($req['cancel_date']) ),
				"renter_possession" 			=> "0",
			];
			$check_empty = array_keys($req_body, null, true);
			if( !empty( $check_empty ) ){
				$error->add(400, __("some details are yet to update", 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			try{
				$ch = curl_init();
				$url = 'https://mbapartnerconnect.biz/v1/addendum/cancel/';
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Mba-Partner-Connect-Key: 8D1D746B5D22BED42312FBD914CAF'));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $req_body);
				$result  = curl_exec($ch);
				curl_close($ch);
				//echo json_encode($result);
			}catch( \Exception $e ){
				$error->add(400, __($e->getMessage(), 'wp-rest-user'), array('status' => 400));
      			return $error;
			}
			die;
		}
	}

}
