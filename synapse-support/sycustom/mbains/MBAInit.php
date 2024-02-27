<?php

namespace Sycustom\mbains;
use Sycustom\mbains\WooReplacement;

class MBAInit{
	public function __construct(){
		add_action('wp_ajax_get_mba_quote', array($this, 'mbaGetQuote'));
    add_action('wp_ajax_nopriv_get_mba_quote', array($this, 'ajax_check_avaliabity'));
	}

	public function mbaGetQuote(){
		//if ( isset($_POST['confirmed']) ){
			$value = json_decode( wp_unslash(htmlspecialchars_decode(wp_unslash($_POST['list_val']))), true );
			$list_id = $value['listing_id'];
			$list_meta = get_post_meta( $list_id );
			$user_id 						= get_current_user_id();
			$user 							= get_user_by('id', $user_id);
			$req_body = [
				"booking_id" 					=> "ABC-123",
				"package_id" 					=> "1",
				"liability_only" 			=> "no",
				"start_date" 					=> $value['date_start'],
				"end_date" 						=> $value['date_end'],
				"named_event" 				=> "",
				"owner_first_name" 		=> "George",
				"owner_last_name" 		=> "Washington",
				"owner_street" 				=> "7600 N 16th St",
				"owner_street_two" 		=> "Suite 145",
				"owner_city" 					=> "Phoenix",
				"owner_state" 				=> "AZ",
				"owner_zip" 					=> "85020",
				"owner_country" 			=> "US",
				"owner_email" 				=> "owner@mbainsurance.net",
				"owner_phone" 				=> "8006222201",
				"vehicle_type" 				=> "Class C",
				"vehicle_year" 				=> "2016",
				"vehicle_make" 				=> "Forest River",
				"vehicle_model" 			=> "Forester",
				"vehicle_trim" 				=> "2251SC",
				"vehicle_vin" 				=> "123456789ABCDEFGH",
				"vehicle_value" 			=> "76999",
				"vehicle_length" 			=> "20",
				"salvage_title" 			=> "no",
				"renter_first_name" 	=> "John",
				"renter_last_name" 		=> "Adams",
				"renter_street" 			=> "8383 E Evans Rd",
				"renter_street_two" 	=> "",
				"renter_city" 				=> "Scottsdale",
				"renter_state" 				=> "AZ",
				"renter_zip" 					=> "85260",
				"renter_country" 			=> "US",
				"renter_email" 				=> "renter@mbainsurance.net",
				"renter_phone" 				=> "4809461066",
				"renter_license_state" 						=> "AZ",
				"international_drivers_license" 	=> "no",
				"renter_license_number" 					=> "AZ123456789",
				"renter_dob" 											=> "1990-01-01",
				"renter_bg_check_status" 					=> "approved",
			];
			$ch = curl_init();
			$url = 'https://mbapartnerconnect.biz/v1/quote/create/';
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Mba-Partner-Connect-Key: 8D1D746B5D22BED42312FBD914CAF'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $req_body);
			$result  = curl_exec($ch);
			curl_close($ch);
			echo json_encode($result);
			die;
		}
	//}

}
