<?php 
add_action( 'wp_enqueue_scripts', 'listeo_enqueue_styles' );
function listeo_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css',array('bootstrap','font-awesome-5','font-awesome-5-shims','simple-line-icons','listeo-woocommerce') );

}


 
function remove_parent_theme_features() {
   	
}
add_action( 'after_setup_theme', 'remove_parent_theme_features', 10 );

/*** Custom Codes ***/
function syind_without_service_price($price, $listing_id, $date_start, $date_end, $multiply , $services){
	/*$services_price = 0;
    if(isset($services) && !empty($services)){
        $bookable_services = listeo_get_bookable_services($listing_id);
        $countable = array_column($services,'value');
      
        $i = 0;
        foreach ($bookable_services as $key => $service) {
            
            if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                //$services_price += (float) preg_replace("/[^0-9\.]/", '', $service['price']);
                $services_price +=  listeo_calculate_service_price($service, $multiply, $days_count, $countable[$i] );
                
               $i++;
            }
           
        
        } 
    }*/
    //return $price + ($price*.1);
    $damage_deposit  = get_post_meta ( $listing_id, '_reservation_price', true);
    $price_without_damage = ($price-$damage_deposit);
    
    $renter_comission_rvmil = get_option('listeo_renter_commission_rate');
    $newprice = ($price_without_damage*$renter_comission_rvmil)/100;
    
    return $damage_deposit+$newprice+$price_without_damage;
    //return $price;
}
add_filter('listeo_booking_price_calc', 'syind_without_service_price', 10, 6);

add_action('wp_ajax_calculate_breakdown', 'ajax_calculate_breakdown');
add_action('wp_ajax_nopriv_calculate_breakdown', 'ajax_calculate_breakdown');

function ajax_calculate_breakdown(){
	
	
    $multiply = 1;
    session_start();
    if(isset($_POST['insurance_plans'])) { 
    	$_SESSION['insurance_plans'] = $_POST['insurance_plans']; 
    }
    if(isset($_POST['insurance_roadside'])) { 
    	$_SESSION['insurance_roadside'] = $_POST['insurance_roadside']; 
    }
    if(isset($_POST['adults'])) $multiply = $_POST['adults']; 
    if(isset($_POST['tickets'])) $multiply = $_POST['tickets'];
    $coupon = (isset($_POST['coupon'])) ? $_POST['coupon'] : false ;
    $services = (isset($_POST['services'])) ? $_POST['services'] : false ;
    $decimals = get_option('listeo_number_decimals',2);
    $listing_id = $_POST['listing_id'];
    //$ajax_out['price'] = number_format_i18n($price,$decimals);
    $date_start = $_POST['date_start'];
    $date_end = $_POST['date_end'];
    //$mba_insurance_plan = $_SESSION['insurance_plan'];
    $special_prices_results = Listeo_Core_Bookings_Calendar :: get_bookings( $_POST['date_start'], $_POST['date_end'], array( 'listing_id' => $_POST['listing_id'], 'type' => 'special_price' ) );

    $listing_type = get_post_meta( $listing_id, '_listing_type', true);
    if($listing_type == 'rental') {
        foreach ($special_prices_results as $result){
            $special_prices[ $result['date_start'] ] = $result['comment'];
        }
    	$normal_price = (float) get_post_meta ( $listing_id, '_normal_price', true);
    	$weekend_price = (float)  get_post_meta ( $listing_id, '_weekday_price', true);
    	if(empty($weekend_price)){
        	$weekend_price = $normal_price;
    	}
    	$reservation_price  =  (float) get_post_meta ( $listing_id, '_reservation_price', true);
    	$_count_per_guest  = get_post_meta ( $listing_id, '_count_per_guest', true);
    	$services_price = 0;
    	$firstDay = new DateTime( $date_start );
    	$lastDay = new DateTime( $date_end );
    	if(get_option('listeo_count_last_day_booking')){
        	$lastDay = $lastDay->modify('+1 day');     
    	}
        $days_between = $lastDay->diff($firstDay)->format("%a");
        $days_count = ($days_between == 0) ? 1 : $days_between ;
        //fix for not calculating last day of leaving
        //if ( $date_start != $date_end ) $lastDay -> modify('-1 day');
        
        $interval = DateInterval::createFromDateString('1 day');
    
    	$period = new DatePeriod( $firstDay, $interval, $lastDay );
    	//echo '<pre>';
    	//print_r($firstDay);die;
        $price = 0;
      	$special_price_count = [];
      	$week_end_count = 0;
      	$week_end_price = 0;
      	$normal_price_count = 0;
        foreach ( $period as $current_day ) {

            // get current date in sql format
            $date = $current_day->format("Y-m-d 00:00:00");
            $day = $current_day->format("N");

            if ( isset( $special_prices[$date] ) ){
                $price += $special_prices[$date];
                array_push($special_price_count, $special_prices[$date]);
            }else {
                $start_of_week = intval( get_option( 'start_of_week' ) ); // 0 - sunday, 1- monday
                // when we have weekends
                if($start_of_week == 0 ) {
                    if ( isset( $weekend_price ) && $day == 5 || $day == 6) {
                    	$week_end_count++;
                        $price += $weekend_price;
                        $week_end_price = $weekend_price;
                    } else {
                     	$price += $normal_price;
                     	$normal_price_count++;
                 	}
                } else {
                    if ( isset( $weekend_price ) && $day == 6 || $day == 7) {
                        $price += $weekend_price;
                     	$week_end_count++; 
                        $week_end_price = $weekend_price;
                    } else { 
                     	$price += $normal_price;
                     	$normal_price_count++;
                    }
                }
            }
        }
        if($_count_per_guest){
        	$ajax_out['total_guest'] = (int) $multiply - 1;
        	$ajax_out['guest_price'] = $price * ((int) $multiply -1);
            $price = $price * (int) $multiply;
        }
        $services_price = 0;
        if(isset($services) && !empty($services)){
            $bookable_services = listeo_get_bookable_services($listing_id);
            $countable = array_column($services,'value');
            $i = 0;
            foreach ($bookable_services as $key => $service) {
                if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                    //$services_price += (float) preg_replace("/[^0-9\.]/", '', $service['price']);
                    $services_price +=  listeo_calculate_service_price($service, $multiply, $days_count, $countable[$i] );
                    
                   $i++;
                }
            } 
        }
        $ajax_out['reservation_price'] = $reservation_price;
        $ajax_out['services_price'] = $services_price;
        $ajax_out['services_day'] = $days_count;
        //$price += $reservation_price + $services_price;
        $price += $services_price;
        $owner_comission_rvmil = get_option('listeo_owner_commission_rate')/100;
        $renter_comission_rvmil = get_option('listeo_renter_commission_rate')/100;
        //$ajax_out['abcd'] = $owner_comission_rvmil;
        //$ajax_out['efgh'] = $renter_comission_rvmil;
        $ajax_out['owner_fees'] = $price*$renter_comission_rvmil;
        $ajax_out['rv_fees'] = $price*$owner_comission_rvmil;
        
        $renters_comission = $price*$owner_comission_rvmil;
        $owners_comission = $price*$renter_comission_rvmil;
        
        $owner_price = $price*$renter_comission_rvmil;
        $price += $owner_price;// + $price*.15;
        
        $full_price = $price + $reservation_price;
        if(isset($coupon) && !empty($coupon)){
            $wc_coupon = new WC_Coupon($coupon);
            $coupons = explode(',',$coupon);
            foreach ($coupons as $key => $new_coupon) {
                $discounted_price = Listeo_Core_Bookings_Calendar::apply_coupon_to_price($full_price,$new_coupon);
            }
            $ajax_out['discount_price'] = round($discounted_price,2);
            
            $_SESSION['discount_price'] = round($discounted_price,2);
        }
        $ajax_out['special_price_count'] = $special_price_count;
      	$ajax_out['week_end_count'] = $week_end_count;
      	$ajax_out['week_end_price'] = $week_end_price;
      	$ajax_out['normal_price_count'] = $normal_price_count;
      	$ajax_out['normal_price'] = $normal_price;
        $ajax_out['the_price'] = $price;
        
        $_SESSION['the_price'] = $price;
        
        /*Insurance Create Quote API Start*/
        		$mba_insurance_plan = $_SESSION['insurance_plans'];
        		$mba_insurance_roadside = $_SESSION['insurance_roadside'];
        
        		$booking_id = "123";
				$package_id = $mba_insurance_plan;
				$liability_only = "no";
				$start_date = date('Y-m-d H:i:s', strtotime($date_start));
				$end_date = date('Y-m-d H:i:s', strtotime($date_end));
				
				//$listing_id = $listing_id;
				$owner_first_name = get_post_meta( $listing_id, 'owner_first_name', true );
				$owner_last_name = get_post_meta( $listing_id, 'owner_last_name', true );
				$owner_street = get_post_meta( $listing_id, '_friendly_address', true );
				$owner_city = get_post_meta( $listing_id, 'owner_city', true );
				$owner_state = get_post_meta( $listing_id, 'owner_state', true );
				$owner_zip = get_post_meta( $listing_id, 'owner_zip', true );
				//$owner_country = get_post_meta( $listing_id, 'owner_country', true );
				$owner_country = "US";
				$owner_email = get_post_meta( $listing_id, 'owner_email', true );
				$owner_phone = get_post_meta( $listing_id, 'owner_phone', true );
				
				$rental_category = get_the_terms( $listing_id, 'rental_category' );
				$rental_cat = $rental_category[0]->name;
				$vehicle_type = $rental_cat;
				$vehicle_year = get_post_meta( $listing_id, '_year', true );
				$vehicle_make = get_post_meta( $listing_id, '_vehicle_make', true );
				$vehicle_model = get_post_meta( $listing_id, '_model', true );
				//$vehicle_trim_data = get_post_meta( $listing_id, 'vehicle_trim', true );
				//if(!empty($vehicle_trim_data)){
				$vehicle_trim = "2251SC";
				//}else {
					//$vehicle_trim = $vehicle_trim_data;
				//}
				//$vehicle_vin = get_post_meta( $listing_id, '_class', true );
				$vehicle_vin = get_post_meta( $listing_id, '_vehicle_vin', true );
				$vehicle_value = get_post_meta( $listing_id, '_vehicle_value', true );
				$vehicle_length = get_post_meta( $listing_id, '_length', true );
				$salvage_title = "no";
				
				$renter_first_name = "John";
				$renter_last_name = "Adams";
				$renter_street = "8383 E Evans Rd";
				$renter_city = "Scottsdale";
				$renter_state = "AZ";
				$renter_zip = "85260";
				$renter_country = "US";
				$renter_email = "renter@mbainsurance.net";
				$renter_phone = "4809461066";
				
				$renter_license_state = "AZ";
				$international_drivers_license = "no";
				$renter_license_number = "78979879";
				$renter_dob = "1990-01-01";
				$renter_bg_check_status = "approved";
				$roadside = $mba_insurance_roadside;
				
				
				//$abcd =  "<br>".$booking_id."<br>".$package_id."<br>".$liability_only."<br>".$start_date."<br>".$end_date."<br><br>"; 
				//$abcd .= $owner_first_name."<br>".$owner_last_name."<br>".$owner_street."<br>".$owner_city."<br>".$owner_state."<br>".$owner_zip."<br>".$owner_country."<br>".$owner_email."<br>".$owner_phone."<br><br>";
				//$abcd .= $vehicle_type."<br>".$vehicle_year."<br>".$vehicle_make."<br>".$vehicle_model."<br>".$vehicle_trim."<br>".$vehicle_vin."<br>".$vehicle_value."<br>".$vehicle_length."<br>".$salvage_title."<br><br>";
				//$abcd .= $renter_first_name."<br>".$renter_last_name."<br>".$renter_street."<br>".$renter_city."<br>".$renter_state."<br>".$renter_zip."<br>".$renter_country."<br>".$renter_email."<br>".$renter_phone."<br><br>"; 
				//$abcd .= $renter_license_state."<br>".$international_drivers_license."<br>".$renter_license_number."<br>".$renter_dob."<br>".$renter_bg_check_status."<br><br>".$roadside."<br><br>"; 
	
			 	$curl = curl_init();
					curl_setopt_array($curl, array(
  					CURLOPT_URL => 'https://mbapartnerconnect.net/v1/quote/create/',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => array(
						"booking_id" 	   				=> $booking_id,
						"package_id" 	   				=> $package_id,
						"liability_only"   				=> $liability_only,
						"start_date" 	   				=> $start_date,
						"end_date" 		   				=> $end_date,
						"named_event" 	   				=> "",
						"owner_first_name" 				=> $owner_first_name,
						"owner_last_name"  				=> $owner_last_name,
						"owner_street" 	   				=> $owner_street,
						"owner_street_two" 				=> "",
						"owner_city" 	   				=> $owner_city,
						"owner_state" 	   				=> $owner_state,
						"owner_zip" 	   				=> $owner_zip,
						"owner_country"    				=> $owner_country,
						"owner_email" 	   				=> $owner_email,
						"owner_phone" 					=> $owner_phone,
						"vehicle_type" 					=> $vehicle_type,
						"vehicle_year" 					=> $vehicle_year,
						"vehicle_make" 					=> $vehicle_make,
						"vehicle_model" 				=> $vehicle_model,
						"vehicle_trim" 					=> $vehicle_trim,
						"vehicle_vin" 					=> $vehicle_vin,
						"vehicle_value" 				=> $vehicle_value,
						"vehicle_length" 				=> $vehicle_length,
						"salvage_title" 				=> $salvage_title,
						"renter_first_name" 			=> $renter_first_name,
						"renter_last_name" 				=> $renter_last_name,
						"renter_street" 				=> $renter_street,
						"renter_street_two" 			=> "",
						"renter_city" 					=> $renter_city,
						"renter_state" 					=> $renter_state,
						"renter_zip" 					=> $renter_zip,
						"renter_country" 				=> $renter_country,
						"renter_email" 					=> $renter_email,
						"renter_phone" 					=> $renter_phone,
						"renter_license_state" 			=> $renter_license_state,
						"international_drivers_license" => $international_drivers_license,
						"renter_license_number" 		=> $renter_license_number,
						"renter_dob" 					=> $renter_dob,
						"renter_bg_check_status" 		=> $renter_bg_check_status,
						"roadside" 						=> $roadside,
					),
					CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response = array();
			$response = curl_exec($curl);
			if(curl_exec($curl) === false){
                    echo 'Curl error: ' . curl_error($curl);
            }else{
            	//print_r($response);
                $insurance_price = json_decode($response, true);
                if($insurance_price['response_code'] == '200') {
	                $mba_insurance_fee = $insurance_price['response_data'];
	                /*echo "<pre>";
	                print_r($mba_insurance_fee);
	                echo "</pre>";*/
	                $mba_insurance_price = $mba_insurance_fee['insurance'];
	                if(!empty($mba_insurance_fee['roadside']) && $roadside=="on"){
		                $mba_insurance_roadside_price = $mba_insurance_fee['roadside'];
					} else{
						$mba_insurance_roadside_price = '0';
					}
	                $mba_insurance_total_price = $mba_insurance_fee['total'];
	                
				} /*elseif($insurance_price['response_code'] == '400'){
					$mba_create_quote_issue = $insurance_price['response_data']['errors'][0];
					echo $mba_create_quote_issue;
				}*/
            }
            curl_close($curl);
		/*Insurance Create Quote API End*/
        
        //$ajax_out['insurance_fee'] = $mba_insurance_fee." ABC".$mba_insurance_plan." RF".$mba_insurance_roadside;
        //$ajax_out['insurance_fee'] = $response;
        $ajax_out['insurance_fee'] = $mba_insurance_price;
        $ajax_out['insurance_roadside_price'] = $mba_insurance_roadside_price;
        $ajax_out['insurance_total_price'] = $mba_insurance_total_price;
        
        $_SESSION['mba_insurance_fee'] = $mba_insurance_price;
        $_SESSION['mba_insurance_roadside_price'] = $mba_insurance_roadside_price;
        $_SESSION['mba_insurance_total_price'] = $mba_insurance_total_price;
    }else{
    	$ajax_out['error'] = true;
    	$ajax_out['message'] = 'Not rental';
    }

    wp_send_json_success( $ajax_out );
}


// Add header
function action_woocommerce_admin_order_item_headers( $order ) {
    // Set the column name
    $column_name = __( 'Damage Deposit', 'woocommerce' );
    $column_name_a = __( 'Veteran Investment', 'woocommerce' );
    
    // Display the column name
    echo '<th>' . $column_name . '</th>';
    echo '<th>' . $column_name_a . '</th>';
}
add_action( 'woocommerce_admin_order_item_headers', 'action_woocommerce_admin_order_item_headers', 10, 1 );

// Add content
function action_woocommerce_admin_order_item_values( $product, $item, $item_id ) {
    if ( method_exists( $item, 'is_type' ) ) {
        // Only for "line_item" items type, to avoid errors
        if ( ! $item->is_type( 'line_item' ) ) return;
		 $listing_id = get_field( 'number', $item->get_product_id() );
         $damage_deposit_price = get_post_meta ( $listing_id, '_reservation_price', true);
         
         global $woocommerce, $post;
         
         $order = new WC_Order($post->ID);
         $order_id = trim(str_replace('#', '', $order->get_order_number()));
         //print_r($order);
        
         $subtotal_price = $order->get_subtotal();
         $admin_final_price = $subtotal_price-$damage_deposit_price;
         
         $renter_comission_rvmil = get_option('listeo_renter_commission_rate');
    	 $newrate = 1+($renter_comission_rvmil)/100;
    	 $administrator_price_without_tax = $admin_final_price/$newrate;
         $administrator_price = $admin_final_price - $administrator_price_without_tax;
         
        echo '<td>$' . number_format($damage_deposit_price, 2, '.', ',') . '</td>';
        echo '<td>$' . $administrator_price . '</td>';
    }     
}
add_action( 'woocommerce_admin_order_item_values', 'action_woocommerce_admin_order_item_values', 10, 3 );

/* Checkbox On Checkout */
add_action( 'woocommerce_pay_order_before_submit', 'bt_add_checkout_checkbox', 10 );
/**
 * Add WooCommerce additional Checkbox checkout field
 */
function bt_add_checkout_checkbox() {
   
    woocommerce_form_field( 'checkout_checkbox', array( // CSS ID
       'type'          => 'checkbox',
       'class'         => array('form-row mycheckbox'), // CSS Class
       'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
       'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
       'required'      => true, // Mandatory or Optional
       'label'         => 'I agree with <a href="https://www.staging.rvbymilitary.com/terms-and-conditions/" target="_blank" rel="noopener">&nbsp;Terms & Conditions&nbsp;</a>and<a href="https://www.staging.rvbymilitary.com/privacy-policy/" target="_blank" rel="noopener">&nbsp;Privacy Policy&nbsp;</a>', // Label and Link
    ));  
	
	woocommerce_form_field( 'checkout_checkbox_two', array( // CSS ID
       'type'          => 'checkbox',
       'class'         => array('form-row mycheckbox'), // CSS Class
       'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
       'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
       'required'      => true, // Mandatory or Optional
       'label'         => 'I have downloaded the PDF <a href="https://www.staging.rvbymilitary.com/wp-content/uploads/2023/06/RVbyMilitary.com-Return-form-v2.pdf" target="_blank" rel="noopener">&nbsp;Return Form&nbsp;</a>and<a href="https://www.staging.rvbymilitary.com/wp-content/uploads/2023/06/RVbyMilitary.com-Pre-Arrival-Checklist-v2.pdf" target="_blank" rel="noopener">&nbsp;Pre Arrival Checklist&nbsp;</a>', // Label and Link
    ));    
}

add_action( 'woocommerce_checkout_process', 'bt_add_checkout_checkbox_warning' );
/**
 * Alert if checkbox not checked
 */ 
function bt_add_checkout_checkbox_warning() {
    if ( ! (int) isset( $_POST['checkout_checkbox'] ) ) {
        wc_add_notice( __( 'Please acknowledge the Checkbox' ), 'error' );
    }
	if ( ! (int) isset( $_POST['checkout_checkbox_two'] ) ) {
        wc_add_notice( __( 'Please acknowledge the Checkbox' ), 'error' );
    }
}

add_action( 'woocommerce_pay_order_before_submit', 'bbloomer_conditionally_hide_show_new_field', 9999 );

function bbloomer_conditionally_hide_show_new_field() {
    
  wc_enqueue_js( "
      jQuery('input#payment_method_ppcp-gateway').on('click', function(){
         if (this.checked) {
            // HIDE IF CHECKED
            //alert('Hi');
            jQuery('li.wc_payment_method.payment_method_ppcp-gateway').css('display', 'none'); 
			jQuery('input#payment_method_ppcp-credit-card-gateway').trigger('click');
         }  
      });
  ");
       
}

add_action('wp_footer', 'custom_footer_code');
function custom_footer_code() {
 echo "<script>
    jQuery(document).ready(function() {
      // Function to remove text
      function removeText() {
        var targetElement = jQuery('div#unpaid_listing_in_cart'); // Replace 'target' with the ID or class of the element you want to target

        // Check if the text exists within the target element
        if (targetElement.html().includes('Please add your PayPal email address. This is required to get your payments for booking using PayPal Payout service.')) {
          targetElement.html(targetElement.html().replace('Please add your PayPal email address. This is required to get your payments for booking using PayPal Payout service.', ''));
        }
        
        var targetElement_two = jQuery('div#unpaid_listing_in_cart span'); // Replace 'target' with the ID or class of the element you want to target

        // Check if the text exists within the target element
        if (targetElement_two.text().includes('PayPal email missing!')) {
          targetElement_two.text(targetElement_two.text().replace('PayPal email missing!', ''));
        }
      }

      // Call the removeText function
      removeText();
    });
  </script>";
}

/* Woocommerce Order */
add_action('woocommerce_pay_order_before_submit', 'get_current_booking_id_from_order_id_from_url');
//add_action('woocommerce_pay_order_after_submit', 'get_current_booking_id_from_order_id_from_url');

function get_current_booking_id_from_order_id_from_url(){
	if ( is_wc_endpoint_url( 'order-pay' ) ) {
	global $wp;
	//Get Order ID
	$current_order_id =  intval( str_replace( 'checkout/order-pay/', '', $wp->request ) );
		$current_order_id;
		$order = wc_get_order($current_order_id);
		
		session_start();
		$mba_insurance_plan = $_SESSION['insurance_plans'];
        $mba_insurance_roadside = $_SESSION['insurance_roadside'];
		
		global $wpdb;
		$result = $wpdb->get_results("select ID, date_start, date_end, bookings_author, listing_id, comment from wp_w8vjtt_bookings_calendar where order_id = '".$current_order_id. "'");

		if(!empty($result)){
			//print_r($result);
			foreach($result as $row) {
				
				$booking_id = $row->ID;
				$package_id = $mba_insurance_plan;
				$liability_only = "no";
				$start_date = $row->date_start;
				$end_date = $row->date_end;
				
				$listing_id = $row->listing_id;
				$owner_first_name = get_post_meta( $listing_id, 'owner_first_name', true );
				$owner_last_name = get_post_meta( $listing_id, 'owner_last_name', true );
				$owner_street = get_post_meta( $listing_id, '_friendly_address', true );
				$owner_city = get_post_meta( $listing_id, 'owner_city', true );
				$owner_state = get_post_meta( $listing_id, 'owner_state', true );
				$owner_zip = get_post_meta( $listing_id, 'owner_zip', true );
				//$owner_country = get_post_meta( $listing_id, 'owner_country', true );
				$owner_country = "US";
				$owner_email = get_post_meta( $listing_id, 'owner_email', true );
				$owner_phone = get_post_meta( $listing_id, 'owner_phone', true );
				
				$rental_category = get_the_terms( $listing_id, 'rental_category' );
				$rental_cat = $rental_category[0]->name;
				$vehicle_type = $rental_cat;
				$vehicle_year = get_post_meta( $listing_id, '_year', true );
				$vehicle_make = get_post_meta( $listing_id, '_vehicle_make', true );
				$vehicle_model = get_post_meta( $listing_id, '_model', true );
				//$vehicle_trim_data = get_post_meta( $listing_id, 'vehicle_trim', true );
				//if(!empty($vehicle_trim_data)){
				$vehicle_trim = "2251SC";
				//}else {
					//$vehicle_trim = $vehicle_trim_data;
				//}
				//$vehicle_vin = get_post_meta( $listing_id, '_class', true );
				$vehicle_vin = get_post_meta( $listing_id, '_vehicle_vin', true );
				$vehicle_value = get_post_meta( $listing_id, '_vehicle_value', true );
				$vehicle_length = get_post_meta( $listing_id, '_length', true );
				$salvage_title = "no";
				
				$renter_id = $row->bookings_author;
				$renter_info = json_decode($row->comment);
				//echo "<pre>";
				//print_r( $renter_info );
				//echo "</pre>";
				$renter_first_name = $renter_info->first_name;
				$renter_last_name = $renter_info->last_name;
				$renter_street = $renter_info->billing_address_1;
				$renter_city = $renter_info->billing_city;
				$renter_state = get_user_meta( $renter_id, '_renter_license_state', true );
				$renter_zip = $renter_info->billing_postcode;
				$renter_country = $renter_info->billing_country;
				$renter_email = $renter_info->email;
				$renter_phone = $renter_info->phone;
				
				$renter_license_state = get_user_meta( $renter_id, '_renter_license_state', true );
				$international_drivers_license = get_user_meta( $renter_id, '_international_drivers_license', true );
				$renter_license_number = get_user_meta( $renter_id, '_renter_license_number', true );
				$renter_dob = get_user_meta( $renter_id, '_renter_dob', true );
				$renter_bg_check_status = "approved";
				
				$credit_card_number = get_user_meta( $renter_id, '_credit_card_number', true );
				$credit_card_csc    = get_user_meta( $renter_id, '_credit_card_csc', true );
				$credit_card_exp_month = get_user_meta( $renter_id, '_credit_card_expire_month', true );
				$credit_card_exp_year = get_user_meta( $renter_id, '_credit_card_expire_year', true );
				$roadside = $mba_insurance_roadside;
				
				
				
				//echo $listing_id."<br><br>";
				
				$abcd = $booking_id."<br>".$package_id."<br>".$liability_only."<br>".$start_date."<br>".$end_date."<br><br>"; 
				$abcd .= $owner_first_name."<br>".$owner_last_name."<br>".$owner_street."<br>".$owner_city."<br>".$owner_state."<br>".$owner_zip."<br>".$owner_country."<br>".$owner_email."<br>".$owner_phone."<br><br>";
				$abcd .= $vehicle_type."<br>".$vehicle_year."<br>".$vehicle_make."<br>".$vehicle_model."<br>".$vehicle_trim."<br>".$vehicle_vin."<br>".$vehicle_value."<br>".$vehicle_length."<br>".$salvage_title."<br><br>";
				$abcd .= $renter_first_name."<br>".$renter_last_name."<br>".$renter_street."<br>".$renter_city."<br>".$renter_state."<br>".$renter_zip."<br>".$renter_country."<br>".$renter_email."<br>".$renter_phone."<br><br>"; 
				$abcd .= $renter_license_state."<br>".$international_drivers_license."<br>".$renter_license_number."<br>".$renter_dob."<br>".$renter_bg_check_status."<br><br>";
				$abcd .= $credit_card_number."<br>".$credit_card_csc."<br>".$credit_card_exp_month."<br>".$credit_card_exp_year."<br>".$roadside."<br><br>"; 
			
			/* Create Quote Start */
			$curl = curl_init();
					curl_setopt_array($curl, array(
  					CURLOPT_URL => 'https://mbapartnerconnect.net/v1/quote/create/',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => array(
						"booking_id" 	   				=> $booking_id,
						"package_id" 	   				=> $package_id,
						"liability_only"   				=> $liability_only,
						"start_date" 	   				=> $start_date,
						"end_date" 		   				=> $end_date,
						"named_event" 	   				=> "",
						"owner_first_name" 				=> $owner_first_name,
						"owner_last_name"  				=> $owner_last_name,
						"owner_street" 	   				=> $owner_street,
						"owner_street_two" 				=> "",
						"owner_city" 	   				=> $owner_city,
						"owner_state" 	   				=> $owner_state,
						"owner_zip" 	   				=> $owner_zip,
						"owner_country"    				=> $owner_country,
						"owner_email" 	   				=> $owner_email,
						"owner_phone" 					=> $owner_phone,
						"vehicle_type" 					=> $vehicle_type,
						"vehicle_year" 					=> $vehicle_year,
						"vehicle_make" 					=> $vehicle_make,
						"vehicle_model" 				=> $vehicle_model,
						"vehicle_trim" 					=> $vehicle_trim,
						"vehicle_vin" 					=> $vehicle_vin,
						"vehicle_value" 				=> $vehicle_value,
						"vehicle_length" 				=> $vehicle_length,
						"salvage_title" 				=> $salvage_title,
						"renter_first_name" 			=> $renter_first_name,
						"renter_last_name" 				=> $renter_last_name,
						"renter_street" 				=> $renter_street,
						"renter_street_two" 			=> "",
						"renter_city" 					=> $renter_city,
						"renter_state" 					=> $renter_state,
						"renter_zip" 					=> $renter_zip,
						"renter_country" 				=> $renter_country,
						"renter_email" 					=> $renter_email,
						"renter_phone" 					=> $renter_phone,
						"renter_license_state" 			=> $renter_license_state,
						"international_drivers_license" => $international_drivers_license,
						"renter_license_number" 		=> $renter_license_number,
						"renter_dob" 					=> $renter_dob,
						"renter_bg_check_status" 		=> $renter_bg_check_status,
						//"roadside" 						=> $roadside,
						"roadside" 						=> "on",
					),
					CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response = array();
			$response = curl_exec($curl);
			if(curl_exec($curl) === false){
                    echo 'Curl error: ' . curl_error($curl);
            }else{
            	echo $abcd;
            	//print_r($response);
                $insurance_price = json_decode($response, true);
                if($insurance_price['response_code'] == '200') {
	                $mba_insurance_fee = $insurance_price['response_data'];
	                //echo "<pre>";
	                //print_r($mba_insurance_fee);
	                //echo "</pre>";
	                $mba_insurance_price = $mba_insurance_fee['insurance'];
	                if(!empty($mba_insurance_fee['roadside']) && $roadside=="on"){
		                $mba_insurance_roadside_price = $mba_insurance_fee['roadside'];
					} else{
						$mba_insurance_roadside_price = '0';
					}
	                $mba_insurance_total_price = $mba_insurance_fee['total'];
	                
				} elseif($insurance_price['response_code'] == '400'){
					$mba_create_quote_issue = $insurance_price['response_data']['errors'][0];
					echo $mba_create_quote_issue;
				}
            }
            curl_close($curl);
            /* Create Quote End */
            
            /* Create Addendum Start */
			$curl = curl_init();
					curl_setopt_array($curl, array(
  					CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/create/',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => array(
						"booking_id" 	   				=> $booking_id,
						"package_id" 	   				=> $package_id,
						"liability_only"   				=> $liability_only,
						"start_date" 	   				=> $start_date,
						"end_date" 		   				=> $end_date,
						"named_event" 	   				=> "",
						"owner_first_name" 				=> $owner_first_name,
						"owner_last_name"  				=> $owner_last_name,
						"owner_street" 	   				=> $owner_street,
						"owner_street_two" 				=> "",
						"owner_city" 	   				=> $owner_city,
						"owner_state" 	   				=> $owner_state,
						"owner_zip" 	   				=> $owner_zip,
						"owner_country"    				=> $owner_country,
						"owner_email" 	   				=> $owner_email,
						"owner_phone" 					=> $owner_phone,
						"vehicle_type" 					=> $vehicle_type,
						"vehicle_year" 					=> $vehicle_year,
						"vehicle_make" 					=> $vehicle_make,
						"vehicle_model" 				=> $vehicle_model,
						"vehicle_trim" 					=> $vehicle_trim,
						"vehicle_vin" 					=> $vehicle_vin,
						"vehicle_value" 				=> $vehicle_value,
						"vehicle_length" 				=> $vehicle_length,
						"salvage_title" 				=> $salvage_title,
						"renter_first_name" 			=> $renter_first_name,
						"renter_last_name" 				=> $renter_last_name,
						"renter_street" 				=> $renter_street,
						"renter_street_two" 			=> "",
						"renter_city" 					=> $renter_city,
						"renter_state" 					=> $renter_state,
						"renter_zip" 					=> $renter_zip,
						"renter_country" 				=> $renter_country,
						"renter_email" 					=> $renter_email,
						"renter_phone" 					=> $renter_phone,
						"renter_license_state" 			=> $renter_license_state,
						"international_drivers_license" => $international_drivers_license,
						"renter_license_number" 		=> $renter_license_number,
						"renter_dob" 					=> $renter_dob,
						"renter_bg_check_status" 		=> $renter_bg_check_status,
						
						"credit_card_name" 				=> $renter_first_name." ".$renter_last_name,
					   	"credit_card_number" 			=> $credit_card_number,
					   	"credit_card_csc" 				=> $credit_card_csc,
					    "credit_card_exp_month" 		=> $credit_card_exp_month,
					    "credit_card_exp_year" 			=> $credit_card_exp_year,
					    "credit_card_address" 			=> $renter_street,
					    "credit_card_city" 				=> $renter_city,
					    "credit_card_state" 			=> $renter_state,
					    "credit_card_zip" 				=> $renter_zip,
					    "roadside" 						=> $roadside,
					),
					CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response = curl_exec($curl);
			if(curl_exec($curl) === false){
                    echo 'Curl error: ' . curl_error($curl);
            }else{
                //print_r($response);
                //echo $abcd;
                
                $mba_insurance_fee_output = $_SESSION['mba_insurance_fee'];
        		$mba_insurance_roadside_price_output = $_SESSION['mba_insurance_roadside_price'];
        		$mba_insurance_total_price_output = $_SESSION['mba_insurance_total_price'];
                
                $mba_insurance_plan = $_SESSION['insurance_plans'];
        		$mba_insurance_roadside = $_SESSION['insurance_roadside'];
        		if($mba_insurance_plan == '1'){
        			$plan_name = "Alpha";
        		} elseif($mba_insurance_plan == '2'){
        			$plan_name = "Bravo";
        		}else {
        			$plan_name = "Charlie";
        		}
        		
        		if($mba_insurance_roadside == 'off'){
        			$roadside_name = "Roadside Not Opted";
        		}else {
        			$roadside_name = "Roadside Opted";
        		}
        		
        		
                
                $insurance_price = json_decode($response, true);
                if($insurance_price['response_code'] == '200') {
	                $responseData =  $insurance_price['response_data'];
	                
	                $additional_text_m = "User '".$renter_first_name." ".$renter_last_name."' is approved by Real ID";
	                $order_note_m = $additional_text_m ;
	                $order->add_order_note($order_note_m);
	                $order->save();
	                
	                $additional_text_f = "MBA Insurance Plan: ";
	                $order_note_d = $additional_text_f . ' ' .$plan_name ;
	                $order->add_order_note($order_note_d);
	                $order->save();
	                
	                $additional_text_p = "Roadside: ";
	                $order_note_p = $additional_text_p . ' ' . $roadside_name ;
	                $order->add_order_note($order_note_p);
	                $order->save();
	                
	                $additional_text_c = "MBA Insurance Fee: ";
	                $additional_text_d = "MBA Roadside Fee: ";
	                $additional_text_e = "MBA Total Fee: ";
	                $order_note_d = $additional_text_c . ' ' .$mba_insurance_fee_output.' ' .$additional_text_d.' ' .$mba_insurance_roadside_price_output.' ' .$additional_text_e.' ' .$mba_insurance_total_price_output ;
	                $order->add_order_note($order_note_d);
	                $order->save();
	                
                	$additional_text = "Policy Number:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
	                $order->save();
	                
                } else {
                	$responseData = $insurance_price['response_data']['errors'];
                	
                	$additional_text = "Error:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
                	$order->save();
                	
                }
            }
            curl_close($curl);
            /* Create Addendum End */
            
            /* Insurance Documents Start */
            $curl_retrive = curl_init();
			curl_setopt_array($curl_retrive, array(
  			CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/retrieve/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
						"booking_id" 	   		=> $booking_id,
						"type" 	   				=> "addendum",
					),
			CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response_retrive = curl_exec($curl_retrive);
			if(curl_exec($curl_retrive) === false){
                    echo 'Curl error: ' . curl_error($curl_retrive);
            }else{               
                //echo $response_retrive;
                $filename_mba_insurance = "Booking_ID_".$booking_id."_Order_ID_".$current_order_id;
                $insurance_pdf_file_path = dirname(__FILE__).'/mbapdf/'.$filename_mba_insurance.'_insurance.pdf';
                $insurance_pdf_file = file_put_contents($insurance_pdf_file_path, $response_retrive);
                
                $insurance_pdf_file_path_url =  get_stylesheet_directory_uri().'/mbapdf/'.$filename_mba_insurance.'_insurance.pdf';

                $insurance_pdf = json_decode($insurance_pdf_file_path, true);
                
                if($insurance_pdf['response_code'] == '400') {
	                $responseData =  $insurance_pdf['response_data'];
	                //echo $responseData;
	                	                
	                $additional_text = "Error:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
                	$order->save();
	                
                } else {
                	$responseData = $insurance_pdf_file_path_url;
                	//echo $responseData;
                	
                	$additional_text = "PDF:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
	                $order->save();
                	
                }
            }
            curl_close($curl_retrive);
            /* Insurance Documents End */
            
            /* RoadSide Documents Start */
            $doc_roadside = $_SESSION['insurance_roadside'];
            //echo $doc_roadside;
            if($doc_roadside == "on"){
            	
            $curl_retrive = curl_init();
			curl_setopt_array($curl_retrive, array(
  			CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/retrieve/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
						"booking_id" 	   		=> $booking_id,
						"type" 	   				=> "roadside",
					),
			CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response_retrive = curl_exec($curl_retrive);
			if(curl_exec($curl_retrive) === false){
                    echo 'Curl error: ' . curl_error($curl_retrive);
            }else{               
                //echo $response_retrive;
                $filename_mba_insurance = "Booking_ID_".$booking_id."_Order_ID_".$current_order_id;
                $insurance_pdf_file_path = dirname(__FILE__).'/mbapdf/'.$filename_mba_insurance.'_roadside.pdf';
                $insurance_pdf_file = file_put_contents($insurance_pdf_file_path, $response_retrive);
                
                $insurance_pdf_file_path_url =  get_stylesheet_directory_uri().'/mbapdf/'.$filename_mba_insurance.'_roadside.pdf';

                $insurance_pdf = json_decode($insurance_pdf_file_path, true);
                
                if($insurance_pdf['response_code'] == '400') {
	                $responseData =  $insurance_pdf['response_data'];
	                //echo $responseData;
	                	                
	                $additional_text = "Error:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
                	$order->save();
	                
                } else {
                	$responseData = $insurance_pdf_file_path_url;
                	//echo $responseData;
                	
                	$additional_text = "PDF:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
	                $order->save();
                	
                }
            }
            curl_close($curl_retrive);
			} //End if
			/* RoadSide Documents End */
            
            /* Cancel Documents Start */
            /*$mbadate = "2023-12-30";
            $mbabooking = $booking_id;
            $mbaorder = $current_order_id;
            
            $curl_cancel = curl_init();
			curl_setopt_array($curl_cancel, array(
  			CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/cancel/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
						"booking_id" 	   		=> $mbabooking,
						"cancel_effective_date" => $mbadate,
						"renter_possession" 	=> "0",
					),
			CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response_cancel = curl_exec($curl_cancel);
			if(curl_exec($curl_cancel) === false){
                    echo 'Curl error: ' . curl_error($curl_cancel);
            }else{               
                $mba_cancel = json_decode($response_cancel, true);
                if($mba_cancel['response_code'] == '200') {
	                $responseData =  $mba_cancel['response_data'];
	                
	                $additional_text_d = "Insurance Cancelled: ";
	                $order_note_d = $additional_text_d . ' ' . json_encode($responseData);
	                $order->add_order_note($order_note_d);
	                $order->save();
	                
                } else {
                	$responseData = $mba_cancel['response_data'];
                	
                	$additional_text = "Error:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
                	$order->save();
                	
                }
            }
            curl_close($curl_cancel);

	        $curl_retrive = curl_init();
			curl_setopt_array($curl_retrive, array(
	  		CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/retrieve/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
						"booking_id" 	   		=> $mbabooking,
						"type" 	   				=> "cancel",
					),
			CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response_retrive = curl_exec($curl_retrive);
			if(curl_exec($curl_retrive) === false){
	            echo 'Curl error: ' . curl_error($curl_retrive);
	        }else{               
	            //echo $response_retrive;
	                
	            $filename_mba_insurance = "Booking_ID_".$mbabooking."_Order_ID_".$mbaorder;
	            $insurance_pdf_file_path = dirname(__FILE__).'/mbapdf/'.$filename_mba_insurance.'_cancel.pdf';
	            $insurance_pdf_file = file_put_contents($insurance_pdf_file_path, $response_retrive);
	                
	            $insurance_pdf_file_path_url =  get_stylesheet_directory_uri().'/mbapdf/'.$filename_mba_insurance.'_cancel.pdf';

	            $insurance_pdf = json_decode($insurance_pdf_file_path, true);
	                
	            $mba_cancel_send = json_decode($response_retrive, true);
	            //echo $mba_cancel_send['response_code'];
	            //echo $mba_cancel_send['response_data'];
	                
	            if($mba_cancel_send['response_code'] == '400') {
		            $responseData =  $mba_cancel_send['response_data'];
		            //echo $responseData;
		                	                
		            $additional_text = "Error:";
		            $order_note = $additional_text . ' ' . json_encode($responseData);
		                
		            $order->add_order_note($order_note);
	                $order->save();
		                
	            } else {
	                $responseData = $insurance_pdf_file_path_url;
	                //echo $responseData;
	                	
	                $additional_text = "PDF:";
		            $order_note = $additional_text . ' ' . json_encode($responseData);
		                
		            $order->add_order_note($order_note);
		            $order->save();
	                	
	            }
	        }
	        curl_close($curl_retrive);*/
            /* Cancel Documents End */
			}
		}
	}
}
add_action('wp_ajax_cancelmbainsurance', 'my_filters');
add_action('wp_ajax_nopriv_cancelmbainsurance', 'my_filters');

function my_filters(){
	if( isset( $_POST['mbadate'] ) && isset( $_POST['mbabooking'] ) && isset( $_POST['mbaorder'] ) ) {
            $mbadate = ($_POST['mbadate']);
            $mbabooking = ($_POST['mbabooking']);
            $mbaorder = ($_POST['mbaorder']);
            
            $curl_cancel = curl_init();
			curl_setopt_array($curl_cancel, array(
  			CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/cancel/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
						"booking_id" 	   		=> $mbabooking,
						"cancel_effective_date" => $mbadate,
						"renter_possession" 	=> "0",
					),
			CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response_cancel = curl_exec($curl_cancel);
			if(curl_exec($curl_cancel) === false){
                    echo 'Curl error: ' . curl_error($curl_cancel);
            }else{               
                $mba_cancel = json_decode($response_cancel, true);
                if($mba_cancel['response_code'] == '200') {
	                $responseData =  $mba_cancel['response_data'];
	                
	                $additional_text_d = "Insurance Cancelled: ";
	                $order_note_d = $additional_text_d . ' ' . json_encode($responseData);
	                $order->add_order_note($order_note_d);
	                $order->save();
	                
                } else {
                	$responseData = $mba_cancel['response_data'];
                	
                	$additional_text = "Error:";
	                $order_note = $additional_text . ' ' . json_encode($responseData);
	                
	                $order->add_order_note($order_note);
                	$order->save();
                	
                }
            }
            curl_close($curl_cancel);

	        $curl_retrive = curl_init();
			curl_setopt_array($curl_retrive, array(
	  		CURLOPT_URL => 'https://mbapartnerconnect.net/v1/addendum/retrieve/',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => array(
						"booking_id" 	   		=> $mbabooking,
						"type" 	   				=> "cancel",
					),
			CURLOPT_HTTPHEADER => array('Mba-Partner-Connect-Key: FA8D8D7C455A85288DBFAE3561A1E', 'Cookie: PHPSESSID=a153a31c27d1ed22a20d6b055627fa10'),
			));
			$response_retrive = curl_exec($curl_retrive);
			if(curl_exec($curl_retrive) === false){
	            echo 'Curl error: ' . curl_error($curl_retrive);
	        }else{               
	            //echo $response_retrive;
	                
	            $filename_mba_insurance = "Booking_ID_".$mbabooking."_Order_ID_".$mbaorder;
	            $insurance_pdf_file_path = dirname(__FILE__).'/mbapdf/'.$filename_mba_insurance.'_cancel.pdf';
	            $insurance_pdf_file = file_put_contents($insurance_pdf_file_path, $response_retrive);
	                
	            $insurance_pdf_file_path_url =  get_stylesheet_directory_uri().'/mbapdf/'.$filename_mba_insurance.'_cancel.pdf';

	            $insurance_pdf = json_decode($insurance_pdf_file_path, true);
	                
	            $mba_cancel_send = json_decode($response_retrive, true);
	            //echo $mba_cancel_send['response_code'];
	            //echo $mba_cancel_send['response_data'];
	                
	            if($mba_cancel_send['response_code'] == '400') {
		            $responseData =  $mba_cancel_send['response_data'];
		            //echo $responseData;
		                	                
		            $additional_text = "Error:";
		            $order_note = $additional_text . ' ' . json_encode($responseData);
		                
		            $order->add_order_note($order_note);
	                $order->save();
		                
	            } else {
	                $responseData = $insurance_pdf_file_path_url;
	                //echo $responseData;
	                	
	                $additional_text = "PDF:";
		            $order_note = $additional_text . ' ' . json_encode($responseData);
		                
		            $order->add_order_note($order_note);
		            $order->save();
	                	
	            }
	        }
	        curl_close($curl_retrive);
	}
}
?>