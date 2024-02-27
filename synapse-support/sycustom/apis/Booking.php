<?php
namespace Sycustom\api;
use Listeo_Core_Bookings_Calendar;
use DateTime;
///service price is not appearing

class Booking {
	public function booking_routes() {
		register_rest_route(
			SYI_NAMESSPACE,
			'booking',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getMyBookings' ),
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
			'allbooking',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getAllBookings' ),
				'permission_callback' => function () {
			    	$user = wp_get_current_user();
					if ( in_array( 'owner', (array)$user->roles ) ) {
					  return true;
					}else{
						return false;
					}
                },
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'booking',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'newBooking' ),
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
            'manage-booking',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'manageBookings' ),
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
            'delete-booking',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'deleteBooking' ),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            )
        );

		register_rest_route(
			SYI_NAMESSPACE,
			'check_avaliabity',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'checkAvaliabity' ),
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

	public function getMyBookings( \WP_REST_Request $req ){
		$user_id      = get_current_user_id();
        $limit        = !empty( $req['limit'] ) ? $req['limit'] :5;
        $offset       = !empty( $req['offset'] ) ? $req['offset'] :'0';
        $args         = [
            'bookings_author' => $user_id,
            'type' => 'reservation'
        ];
        if( !empty( $req['status'] ) ){
            $args['status'] = $req['status'];
        }
		$bookings = new Listeo_Core_Bookings_Calendar();
		/*$records = $bookings->get_bookings(
            date('Y-m-d H:i:s', strtotime('-3 years')),
            date('Y-m-d H:i:s', strtotime('+3 years')),
            $args,
            'booking_date',
            $limit,
            $offset,
            '',
            'rental'
		);*/
        $records = $bookings->get_newest_bookings($args, $limit, $offset);
        $records = array_map(function( $arr = [] ){
            if( !empty( $arr['bookings_author'] ) ){
                $arr['g_avatar'] = get_avatar_url($arr['bookings_author']);
            }
            if( !empty( $arr['listing_id'] ) ){
                $arr['rv_details'] = get_post($arr['listing_id']);
            }
            if( !empty($arr['status']) ){
                $tag = [];
                $show_approve = $show_reject = $show_renew = $show_delete = false;
                switch ( $arr['status'] ) {
                    case 'waiting' :
                        $tag[] = 'Pending';
                        $show_approve = true;
                        $show_reject = true;
                        $show_renew = false;
                    break;

                    case 'pay_to_confirm' :      
                        if($arr['price']>0){
                            $tag[] = 'Waiting for user payment';    
                        }
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = false;
                        $show_cancel = true;
                    break;

                    case 'confirmed' :
                        $class[] = 'approved-booking';
                        $tag[] = 'Approved';
                        if( $arr['price'] > 0 ){
                            if($_payment_option == "pay_cash"){
                                $tag[] = 'Cash payment';    
                            } else {
                                $tag[] = 'Unpaid'; 
                            }
                        }
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = false;
                        $show_cancel = true;
                    break;

                    case 'paid' :
                        $tag[] = 'Approved';
                        if( $arr['price'] > 0 ){
                            $tag[] = 'Paid';
                        }
                        $show_approve = false;
                        $show_renew = false;
                        $show_reject = false;
                        $show_cancel = true;
                    break;

                    case 'cancelled' :
                        $tag[] = 'Canceled';
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = false;
                        $show_delete = true;
                    break;
                    case 'expired' :
                        $tag[] = 'Expired';
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = true;
                        $show_delete = true;
                    break;
                    default:
                    break;
                }
                $arr['tags'] = $tag;
                $arr['show_approve'] = $show_approve;
                $arr['show_reject'] = $show_reject;
                $arr['show_renew'] = $show_renew;
                $arr['show_delete'] = $show_delete;
            }
            return $arr;
        }, 
        $records);
		return $records;
	}

	public function getAllBookings( \WP_REST_Request $req ){
		$user_id      = get_current_user_id();
		$bookings     = new Listeo_Core_Bookings_Calendar();
        $limit        = !empty( $req['limit'] ) ? $req['limit'] :5;
        $offset       = !empty( $req['offset'] ) ? $req['offset'] :'0';
        $args = [
          'owner_id' => $user_id,
          'type' => 'reservation'
        ];
        if( !empty( $req['status'] ) ){
          $args['status'] = $req['status'];
        }
		$records = $bookings->get_newest_bookings($args,$limit, $offset);
        /*$records = $bookings->get_bookings(
            date('Y-m-d H:i:s', strtotime('-3 years')),
            date('Y-m-d H:i:s', strtotime('+3 years')),
            $args,
            $by = 'booking_date',
            $limit,
            $offset,
            '',
            $listing_type = 'rental'
        );*/
        $records = array_map(function( $arr = [] ){
            if( !empty( $arr['bookings_author'] ) ){
                $arr['g_avatar'] = get_avatar_url($arr['bookings_author']);
            }
            if( !empty( $arr['listing_id'] ) ){
                $arr['rv_details'] = get_post($arr['listing_id']);
            }
            if( !empty($arr['status']) ){
                $tag = [];
                $show_approve = $show_reject = $show_renew = $show_delete = false;
                switch ( $arr['status'] ) {
                    case 'waiting' :
                        $tag[] = 'Pending';
                        $show_approve = true;
                        $show_reject = true;
                        $show_renew = false;
                    break;

                    case 'pay_to_confirm' :      
                        if($arr['price']>0){
                            $tag[] = 'Waiting for user payment';    
                        }
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = false;
                        $show_cancel = true;
                    break;

                    case 'confirmed' :
                        $class[] = 'approved-booking';
                        $tag[] = 'Approved';
                        if( $arr['price'] > 0 ){
                            if($_payment_option == "pay_cash"){
                                $tag[] = 'Cash payment';    
                            } else {
                                $tag[] = 'Unpaid'; 
                            }
                        }
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = false;
                        $show_cancel = true;
                    break;

                    case 'paid' :
                        $tag[] = 'Approved';
                        if( $arr['price'] > 0 ){
                            $tag[] = 'Paid';
                        }
                        $show_approve = false;
                        $show_renew = false;
                        $show_reject = false;
                        $show_cancel = true;
                    break;

                    case 'cancelled' :
                        $tag[] = 'Canceled';
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = false;
                        $show_delete = true;
                    break;
                    case 'expired' :
                        $tag[] = 'Expired';
                        $show_approve = false;
                        $show_reject = false;
                        $show_renew = true;
                        $show_delete = true;
                    break;
                    default:
                    break;
                }
                $arr['tags'] = $tag;
                $arr['show_approve'] = $show_approve;
                $arr['show_reject'] = $show_reject;
                $arr['show_renew'] = $show_renew;
                $arr['show_delete'] = $show_delete;
            }
            return $arr;
        }, 
        $records);
		return $records;
	}
	
	public function checkAvaliabity( \WP_REST_Request $req ) {
        $error = new \WP_Error();
        if(!isset($req['slot'])){
            $slot = false;
        } else {
            $slot = sanitize_text_field($req['slot']);
        }
        $multiply = 1;
        if(isset($req['adults'])) $multiply = $req['adults']; 
        if(isset($req['tickets'])) $multiply = $req['tickets'];
        $coupon         = (isset($req['coupon'])) ? $req['coupon'] : false ;
        $services       = (isset($req['services'])) ? $req['services'] : false ;
        $decimals       = get_option('listeo_number_decimals',2);
        $listing_id     = $req['listing_id'];
        $date_start     = $req['date_start'];
        $date_end       = $req['date_end'];
        $booking_status = get_post_meta( $listing_id, '_booking_status', true);
        $instant_booking = get_post_meta( $listing_id, '_instant_booking', true);
        if( !empty( $instant_booking ) && $instant_booking =='on' && !empty( $booking_status ) && $booking_status =='on' ) {
            $special_prices_results = \Listeo_Core_Bookings_Calendar :: get_bookings( $req['date_start'], $req['date_end'], array( 'listing_id' => $req['listing_id'], 'type' => 'special_price' ) );
            $listing_type           = get_post_meta( $listing_id, '_listing_type', true );
            if( $listing_type == 'rental'){
                foreach ($special_prices_results as $result){
                    $special_prices[ $result['date_start'] ] = $result['comment'];
                }
                $normal_price       = (float) get_post_meta ( $listing_id, '_normal_price', true);
                $weekend_price      = (float)  get_post_meta ( $listing_id, '_weekday_price', true);
                if( empty( $weekend_price ) ){
                    $weekend_price      = $normal_price;
                }
                $reservation_price      = (float) get_post_meta ( $listing_id, '_reservation_price', true);
                $_count_per_guest       = get_post_meta ( $listing_id, '_count_per_guest', true);
                $services_price         = 0;
                $firstDay               = new DateTime( $date_start );
                $lastDay                = new DateTime( $date_end );
                if(get_option('listeo_count_last_day_booking')){
                    $lastDay            = $lastDay->modify('+1 day');     
                }
                $days_between           = $lastDay->diff($firstDay)->format("%a");
                $days_count             = ($days_between == 0) ? 1 : $days_between;
                $interval               = \DateInterval::createFromDateString('1 day');
                $period                 = new \DatePeriod( $firstDay, $interval, $lastDay );
                $price                  = 0;
                $special_price_count    = [];
                $week_end_count         = 0;
                $week_end_price         = 0;
                $normal_price_count     = 0;
                foreach ( $period as $current_day ) {
                    $date   = $current_day->format("Y-m-d 00:00:00");
                    $day    = $current_day->format("N");
                    if ( isset( $special_prices[$date] ) ){
                        $price += $special_prices[$date];
                        array_push($special_price_count, $special_prices[$date]);
                    }else {
                        $start_of_week = intval( get_option( 'start_of_week' ) );
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
                            $services_price +=  listeo_calculate_service_price($service, $multiply, $days_count, $countable[$i] );
                           $i++;
                        }
                    } 
                }
                $ajax_out['reservation_price']  = $reservation_price;
                $ajax_out['services_price']     = $services_price;
                $ajax_out['services_day']       = $days_count;
                $price                          += $services_price;
                $ajax_out['owner_fees']         = $price*.1;
                $ajax_out['rv_fees']            = $price*.15;
                $renters_comission              = $price*.1;
                $owners_comission               = $price*.15;
                $owner_price                    = $price*.1;
                $price                          += $owner_price;
                if( isset($coupon) && !empty($coupon) ) {
                    $wc_coupon  = new \WC_Coupon($coupon);
                    $coupons    = explode(',',$coupon);
                    foreach ($coupons as $key => $new_coupon) {
                        $price = \Listeo_Core_Bookings_Calendar::apply_coupon_to_price($price,$new_coupon);
                    }
                }
                $ajax_out['special_price_count']    = $special_price_count;
                $ajax_out['week_end_count']         = $week_end_count;
                $ajax_out['week_end_price']         = $week_end_price;
                $ajax_out['normal_price_count']     = $normal_price_count;
                $ajax_out['normal_price']           = $normal_price;
                $ajax_out['the_price']              = $price;
            } else {
                $ajax_out['error']                  = true;
                $ajax_out['message']                = 'Not rental';
            }
            return $ajax_out;
        }else{
            $error->add( 400, __( "You cannot book this RV", 'wp-rest-user' ), array( 'status' => 400 ) );
            return $error;
        }
    }

	public function newBooking( \WP_REST_Request $req ){
		$error = new \WP_Error();
        if(!isset($req['value'])){
            $error->add( 400, __( "Bad request", 'wp-rest-user' ), array( 'status' => 400 ) );
    	    return $error;
        }
        $data = $req['value'];
        $listing_id = $data['listing_id'];
        $booking_status = get_post_meta( $listing_id, '_booking_status', true);
        $instant_booking = get_post_meta( $listing_id, '_instant_booking', true);
        if( empty( $instant_booking ) || (!empty( $instant_booking ) && $instant_booking !='on' ) ) {
            $error->add( 400, __( "You cannot book this RV", 'wp-rest-user' ), array( 'status' => 400 ) );
            return $error;
        }
        if ( isset($req['confirmed']) ){
            $_user_id 				= get_current_user_id();
            $data 					= $req['value'];
            $listing_type 		    = get_post_meta( $data['listing_id'], '_listing_type', true );
            $services 				= (isset($data['services'])) ? $data['services'] : false ;
            $comment_services       = false;
            if(!empty($services)){
                $currency_abbr 			 = get_option( 'listeo_currency' );
                $currencyreqion 		 = get_option( 'listeo_currencyreqion' );
                $currency_symbol 		 = \Listeo_Core_Listing::get_currency_symbol($currency_abbr);
                $comment_services 	     = array();
                $bookable_services 	     = listeo_get_bookable_services( $data['listing_id'] );
                if( $listing_type == 'rental' ) {
                    $firstDay 			= new DateTime( $data['date_start'] );
                    $lastDay 		    = new DateTime( $data['date_end'] . '23:59:59') ;
                    $days_between 	    = $lastDay->diff($firstDay)->format("%a");
                    $days_count 		= ($days_between == 0) ? 1 : $days_between ;
                } else {
                    $days_count 		= 1;
                }
                $countable  = array_column($services,'value');
                if( isset( $data['adults'] ) ) {
                    $guests = $data['adults'];
                } else if( isset( $data['tickets'] ) ) {
                    $guests = $data['tickets'];
                } else {
                    $guests = 1;
                }
                $i = 0;
                foreach ($bookable_services as $key => $service) {
                    if(in_array(sanitize_title($service['name']),array_column($services,'service'))) { 
                        $comment_services[] =  array(
                            'service' 	    => $service, 
                            'guests' 		=> $guests, 
                            'days' 			=> $days_count, 
                            'countable'     => $countable[$i],
                            'price' 		=> listeo_calculate_service_price($service, $guests, $days_count, $countable[$i] ) 
                        );
                        $i++;
                    }
                }                  
            }
            $listing_meta       = get_post_meta ( $data['listing_id'], '', true );
            $instant_booking    = get_post_meta(  $data['listing_id'], '_instant_booking', true );
            if ( get_transient( 'listeo_last_booking'.$_user_id ) == $data['listing_id'] . ' ' . $data['date_start']. ' ' . $data['date_end'] ){
                $error->add( 400, __( "This slot already have reserved", 'wp-rest-user' ), array( 'status' => 400 ) );
            	return $error;
            }
            $error 						   = false;
			set_transient( 'listeo_last_booking'.$_user_id, $data['listing_id'] . ' ' . $data['date_start'].' ' . $data['date_end'], 60 * 15 );
            $listing_meta 			       = get_post_meta ( $data['listing_id'], '', true );
            $listing_owner 			       = get_post_field( 'post_author', $data['listing_id'] );
            $billing_address_1 	           = (isset($req['billing_address_1'])) ? sanitize_text_field($req['billing_address_1']) : false ;
            $billingreqcode 		       = (isset($req['billingreqcode'])) ? sanitize_text_field($req['billingreqcode']) : false ;
            $billing_city 			       = (isset($req['billing_city'])) ? sanitize_text_field($req['billing_city']) : false ;
            $billing_country 		       = (isset($req['billing_country'])) ? sanitize_text_field($req['billing_country']) : false ;
            $coupon 					   = (isset($req['coupon_code'])) ? sanitize_text_field($req['coupon_code']) : false ;
            switch ( $listing_meta['_listing_type'][0] ) {
                case 'event' :
                    $comment= array( 
                        'first_name'    			=> sanitize_text_field($req['firstname']),
                        'last_name'     			=> sanitize_text_field($req['lastname']),
                        'email'         			=> sanitize_email($req['email']),
                        'phone'         			=> sanitize_text_field($req['phone']),
                        'message'       			=> sanitize_textarea_field($req['message']),
                        'tickets'       			=> sanitize_text_field($data['tickets']),
                        'service'       			=> $comment_services,
                        'billing_address_1' 	    => $billing_address_1,
                        'billingreqcode'  		    => $billingreqcode,
                        'billing_city'      	    => $billing_city,
                        'billing_country'   	    => $billing_country,
                        'coupon'        			=> $coupon,
                        'price'         			=> \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'],$data['tickets'], $services, '' )
                    );
                    $booking_id = \Listeo_Core_Bookings_Calendar :: insert_booking ( array (
                        'owner_id'      => $listing_owner,
                        'listing_id'    => $data['listing_id'],
                        'date_start'    => $data['date_start'],
                        'date_end'      => $data['date_start'],
                        'comment'       =>  json_encode ( $comment ),
                        'type'          =>  'reservation',
                        'price'         => \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'],$data['tickets'], $services, $coupon ),
                    ));
                    $already_sold_tickets 	            = (int) get_post_meta($data['listing_id'],'_event_tickets_sold',true);
                    $sold_now 							= $already_sold_tickets + $data['tickets'];
                    updatereq_meta($data['listing_id'],'_event_tickets_sold',$sold_now);
                    $status 							= apply_filters( 'listeo_event_default_status', 'waiting');
                    if($instant_booking == 'check_on' || $instant_booking == 'on' ) {
                        $status = 'confirmed'; 
                        if(get_option('listeo_instant_booking_require_payment')){
                            $status = "pay_to_confirm";
                        }
                    }
                    $changed_status = \Listeo_Core_Bookings_Calendar :: set_booking_status ( $booking_id, $status );
                break;
                case 'rental' :
                    $status 			= apply_filters( 'listeo_rental_default_status', 'waiting');
                    $booking_hours 	    = \Listeo_Core_Bookings_Calendar::wpk_change_booking_hours(  $data['date_start'], $data['date_end'] );
                    $date_start 		= $booking_hours[ 'date_start' ];
                    $date_end 		    = $booking_hours[ 'date_end' ];
                    $free_places 		= \Listeo_Core_Bookings_Calendar :: count_free_places( $data['listing_id'], $data['date_start'], $data['date_end'] );
                    if ( $free_places > 0 ) {
                        $count_per_guest= get_post_meta($data['listing_id'], "_count_per_guest" , true ); 
                        $multiply 		= 1;
                        if(isset($data['adults'])) $multiply = $data['adults'];
                        $price 		    = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'],  $data['date_start'], $data['date_end'], $multiply, $services, $coupon   );
                        $price_before_coupons 	= \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, ''   );
                        $booking_id = \Listeo_Core_Bookings_Calendar :: insert_booking ( 
                            array (
                                'owner_id' 		        => $listing_owner,
                                'listing_id' 	        => $data['listing_id'],
                                'date_start' 	        => $data['date_start'],
                                'date_end' 		        => $data['date_end'],
                                'comment' 		        => json_encode ( array( 
                                    'first_name'    		=> sanitize_text_field($req['firstname']),
                                    'last_name'     		=> sanitize_text_field($req['lastname']),
                                    'email'         		=> sanitize_email($req['email']),
                                    'phone'         		=> sanitize_text_field($req['phone']),
                                    'message'       		=> sanitize_textarea_field($req['message']),
                                    'adults'                => sanitize_text_field($data['adults']),
                                    'service'               => $comment_services,
                                    'billing_address_1'     => $billing_address_1,
                                    'billingreqcode'  	    => $billingreqcode,
                                    'billing_city'          => $billing_city,
                                    'billing_country'       => $billing_country,
                                    'coupon'                => $coupon,
                                    'price'                 => $price_before_coupons,
                                )),
                            'type' 			=>  'reservation',
                            'price' 		=> $price,
                        ));
                        $status = apply_filters( 'listeo_event_default_status', 'waiting');
                        if($instant_booking == 'check_on' || $instant_booking == 'on') {
                            $status = 'confirmed'; 
                            if(get_option('listeo_instant_booking_require_payment') && $price > 0 ){
                                $status = "pay_to_confirm";
                            }
                        }
                        $changed_status = \Listeo_Core_Bookings_Calendar :: set_booking_status ( $booking_id, $status );
                    } else{
                        $error 		= true;
                        $message 	= __('Unfortunately those dates are not available anymore.', 'listeo_core');
                    }
                break;
                case 'service' :
                    $status = apply_filters( 'listeo_service_default_status', 'waiting');
                    if($instant_booking == 'check_on' || $instant_booking == 'on') {
                        $status = 'confirmed'; 
                        if(get_option('listeo_instant_booking_require_payment') ){
                            $status = "pay_to_confirm";
                        }
                    }
                    if ( ! isset( $data['slot'] ) ) {
                        $count_per_guest = get_post_meta($data['listing_id'], "_count_per_guest" , true ); 
                        if($count_per_guest){
                            $multiply = 1;
                            if(isset($data['adults'])) $multiply = $data['adults'];
                            $price = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply , $services, $coupon  );
                            $price_before_coupons = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'],  $data['date_start'], $data['date_end'], $multiply, $services, ''   );
                        } else {
                            $price 	= \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'] ,1, $services, $coupon );
                            $price_before_coupons = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'],  $data['date_start'], $data['date_end'], 1, $services, ''   );
                        }
                        $hour_end = ( isset($data['_hour_end']) && !empty($data['_hour_end']) ) ? $data['_hour_end'] : $data['_hour'] ;
                        $booking_id = \Listeo_Core_Bookings_Calendar :: insert_booking ( 
                            array (
                                'owner_id' 		=> $listing_owner,
                                'listing_id' 	=> $data['listing_id'],
                                'date_start' 	=> $data['date_start'] . ' ' . $data['_hour'] . ':00',
                                'date_end' 		=> $data['date_end'] . ' ' . $hour_end . ':00',
                                'comment' 		=>  json_encode ( 
                                    array( 
                                        'first_name'        => sanitize_text_field($req['firstname']),
                                        'last_name'     	=> sanitize_text_field($req['lastname']),
                                        'email'         	=> sanitize_email($req['email']),
                                        'phone'         	=> sanitize_text_field($req['phone']),
                                        'message'       	=> sanitize_text_field($req['message']),
                                        'adults'        	=> sanitize_text_field($data['adults']),
                                        'message'       	=> sanitize_textarea_field($req['message']),
                                        'service'       	=> $comment_services,
                                        'billing_address_1' => $billing_address_1,
                                        'billingreqcode'  	=> $billingreqcode,
                                        'billing_city'      => $billing_city,
                                        'billing_country'   => $billing_country,
                                        'coupon'   			=> $coupon,
                                        'price'     		=> $price_before_coupons
                                        )
                                    ),
                                'type' 				=>  'reservation',
                                'price' 			=> $price,
                                )
                            );
                        $changed_status = \Listeo_Core_Bookings_Calendar :: set_booking_status ( $booking_id, $status );
                    } else {
                        $free_places = \Listeo_Core_Bookings_Calendar :: count_free_places( $data['listing_id'], $data['date_start'], $data['date_end'], $data['slot'] );
                        if ( $free_places > 0 ) {
                            $slot 				= json_decode( wp_unslash($data['slot']) );
                            $hours 				= explode( ' - ', $slot[0] );
                            $hour_start 		= date( "H:i:s", strtotime( $hours[0] ) );
                            $hour_end 			= date( "H:i:s", strtotime( $hours[1] ) );
                            $count_per_guest 	= get_post_meta($data['listing_id'], "_count_per_guest" , true );
                            $services 			= (isset($data['services'])) ? $data['services'] : false ;
                            if($count_per_guest){
                                $multiply = 1;
                                if(isset($data['adults'])) $multiply = $data['adults'];
                                $price = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, $coupon  );
                                $price_before_coupons = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, ''  );
                            } else {
                                $price = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], 1, $services,  $coupon );
                                $price_before_coupons = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], 1, $services, ''  );
                            }
                            $booking_id = \Listeo_Core_Bookings_Calendar :: insert_booking ( array (
                                'owner_id' 			=> $listing_owner,
                                'listing_id' 		=> $data['listing_id'],
                                'date_start' 		=> $data['date_start'] . ' ' . $hour_start,
                                'date_end' 			=> $data['date_end'] . ' ' . $hour_end,
                                'comment' 			=>  json_encode ( array( 
                                    'first_name'        => $req['firstname'],
                                    'last_name'     	=> sanitize_text_field($req['lastname']),
                                    'email'         	=> sanitize_email($req['email']),
                                    'phone'         	=> sanitize_text_field($req['phone']),
                                    'adults'        	=> sanitize_text_field($data['adults']),
                                    'message'       	=> sanitize_textarea_field($req['message']),
                                    'service'       	=> $comment_services,
                                    'billing_address_1' => $billing_address_1,
                                    'billingreqcode'  	=> $billingreqcode,
                                    'billing_city'      => $billing_city,
                                    'billing_country'   => $billing_country,
                                    'coupon'   			=> $coupon,
                                    'price'         	=> $price_before_coupons
                                    )),
                                'type' 				=>  'reservation',
                                'price' 			=> $price,
                            ));
                            $status = apply_filters( 'listeo_service_slots_default_status', 'waiting');
                            if($instant_booking == 'check_on' || $instant_booking == 'on') {
                                $status = 'confirmed'; 
                            	if(get_option('listeo_instant_booking_require_payment') && $price > 0 ){
                                    $status = "pay_to_confirm";
                                }
                            }
                            $changed_status = \Listeo_Core_Bookings_Calendar :: set_booking_status ( $booking_id, $status );
                            } else{
                            $error = true;
                            $message = __('Those dates are not available.', 'listeo_core');
                        }
                        }
                    break;
            }
            if( !isset( $changed_status ) ) {
                $message = __( 'We have some technical problem, please try again later or contact administrator.', 'listeo_core' );
                $error = true;
            }
            switch ( $status ) {
                case 'waiting' :
                    $message = esc_html__( 'Your booking is waiting for confirmation.', 'listeo_core' );
                break;
                case 'confirmed' :
                    if($price > 0){
                        $message = esc_html__( 'We are waiting for your payment.', 'listeo_core' );
                    } else {
                        $message = '';
                    }
                break;
                case 'pay_to_confirm':
                  $message = '';
                break;
                case 'cancelled' :
                  $message = esc_html__( 'Your booking was cancelled', 'listeo_core' );
                break;
            }
            if( !empty( $booking_id ) ){
                $data['booking_data'] =  \Listeo_Core_Bookings_Calendar :: get_booking($booking_id);
            }
        }
        if( !empty( $data['services'] ) ){
            $services =  $data['services'];    
        } else {
            $services = false;
        }
        if ( !empty( $data['slot']) ){
            $slot = json_decode( wp_unslash( $data['slot'] ) );
            $hour = $slot[0];
        } else if ( !empty( $data['_hour'] ) ) {
            $hour = $data['_hour'];
            if( !empty( $data['_hour_end'] ) ) {
                $hour_end = $data['_hour_end'];
            }
        }
        if( isset($data['coupon']) && !empty($data['coupon'])){
            $coupon = $data['coupon'];
        } else {
            $coupon = false;
        }
        $count_per_guest = get_post_meta($data['listing_id'],"_count_per_guest",true); 
        $multiply = 1;
        if(isset($data['adults'])) $multiply = $data['adults'];
        if(isset($data['tickets'])) $multiply = $data['tickets'];

        $data['price'] = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, '' );  
        if(!empty($coupon)){
            $data['price_sale'] = \Listeo_Core_Bookings_Calendar :: calculate_price( $data['listing_id'], $data['date_start'], $data['date_end'], $multiply, $services, $coupon );    
        }
        if(isset($hour)){   
            $data['_hour'] = $hour;
        }
        if(isset($hour_end)){
            $data['_hour_end'] = $hour_end;
        }
        if ( isset( $data['slot'] ) ) {
        	$hours = explode( ' - ', $slot[0] );
        	$hour_start = date( "H:i:s", strtotime( $hours[0] ) );
        	$hour_end = date( "H:i:s", strtotime( $hours[1] ) );
        	$data['date_start'] .= ' ' . $hour_start;
        	$data['date_end'] .= ' ' . $hour_end;
        } else if ( isset( $data['_hour'] ) ) {
            $hour_start = date( "H:i:s", strtotime( $hour ) );
            $data['date_start'] .= ' ' . $hour . ':00';
            $data['date_end'] .= ' ' . $hour . ':00';
        }
        return $data;
	}

    public function deleteBooking( \WP_REST_Request $req ){
        $current_user_id = get_current_user_id();
        if ( !empty( $req['booking_id']) ) {
            wp_send_json_success( Listeo_Core_Bookings_Calendar :: set_booking_status( sanitize_text_field($req['booking_id']), sanitize_text_field('deleted')) );
        }else{
            $error = new \WP_Error();
            $error->add(400, __("Please provide Booking Id to delete", 'wp-rest-user'), array('status' => 400));
            return $error;
        }
    }

    public static function manageBookings( \WP_REST_Request $req ) {
        $current_user_id = get_current_user_id();
        if ( empty( $req['booking_id']) ) {
            $error = new \WP_Error();
            $error->add(400, __("Please provide Booking Id ", 'wp-rest-user'), array('status' => 400));
            return $error;
        }
        if ( !empty( $req['status']) ) {
            try{
                wp_send_json_success( Listeo_Core_Bookings_Calendar :: set_booking_status( sanitize_text_field($req['booking_id']), $req['status']) );
            }catch( \Exception $e ){
                $error = new \WP_Error();
                $error->add(400, __("Please provide Booking Id to delete", 'wp-rest-user'), array('status' => 400));
                return $error;
            }
            
        }else{
            $error = new \WP_Error();
            $error->add(400, __("Please provide Status", 'wp-rest-user'), array('status' => 400));
            return $error;
        }
        

    }
}
