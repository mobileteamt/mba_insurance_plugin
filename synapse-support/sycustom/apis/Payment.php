<?php

namespace Sycustom\api;

class Payment {

	private $stripe;
	public function __construct(){
		$this->stripe = new \Stripe\StripeClient('sk_test_51N6h37HKIp78eYVMR6HA4pXF5iBeb9ECrVgpVZZoqmcso4O1b8aLjo0N8Y94wulG7MakE8yx2ujRvSlOieAfB9nh00fICn6oNa');
		//$this->stripe = new \Stripe\StripeClient('pk_test_51N6h37HKIp78eYVMDuolFln5SoPN6hnIDO7UunzyF3A7szblspHcLgpMmFTxugjEHA8AdhSYBv4T8ZfUlS8UetWg00qA8naFJg');
	}

	public function payment_routes() {
		register_rest_route( SYI_NAMESSPACE, 'stripe-payment', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'wc_rest_payment_endpoint_handler' ),
			'permission_callback' => function () {
			  return is_user_logged_in();
			},
		));

		register_rest_route( SYI_NAMESSPACE, 'stripe-create-token', array(
			'methods'  => 'POST',
			'callback' => array( $this, 'createPaymentMethod' ),
			'permission_callback' => function () {
			  return is_user_logged_in();
			},
		));

	}

	public function wc_rest_create_stripe_token( \WP_REST_Request $req ) {
		$card_number 	= sanitize_text_field($req['card_number']);
		$exp_month 		= sanitize_text_field($req['exp_month']);
		$exp_year 		= sanitize_text_field($req['exp_year']);
		$cvc 			= sanitize_text_field($req['cvc']);
		if (empty($card_number)) {
			$json = array('code' => '0', 'msg' => 'Incorrect Card Number');
			echo json_encode($json);
			exit;
		}
		if (empty($exp_year)) {
			$json = array('code' => '0', 'msg' => 'Incorrect Year');
			echo json_encode($json);
			exit;
		}
		if (empty($exp_month)) {
			$json = array('code' => '0', 'msg' => 'Incorrect Expiray Month');
			echo json_encode($json);
			exit;
		}
		if (empty($cvc)) {
			$json = array('code' => '0', 'msg' => 'Incorrect cvc Number');
			echo json_encode($json);
			exit;
		}
		try{

		}catch (\Exception $e) {
			return new \WP_Error('bad_request', $e->getMessage(), array('status' => 500));
		}
		$resp = $this->stripe->tokens->create([
		  	'card' => [
				'number' 			=> $card_number,
				'exp_month' 		=> $exp_month,
				'exp_year' 			=> $exp_year,
				'cvc' 				=> $cvc,
				'name'				=>'John Doe',
				'address_line1'		=>'B6, NSEZ',
				'address_city'		=>'Noida',
				'address_state'		=>'UP',
				'address_zip'		=>'703205',
				'address_country'	=>'IN',
			],
		]);
		$pm = $this->stripe->paymentMethods->create([
			'type' => 'card',
			'card' => [
				'number' 		=> $card_number,
				'exp_month' 	=> $exp_month,
				'exp_year' 		=> $exp_year,
				'cvc' 			=> $cvc,
			],
		]);
		return new \WP_REST_Response([$resp,$pm], 123);
	}

	public function createPaymentMethod( \WP_REST_Request $req ) {
		$card_number 	= sanitize_text_field($req['card_number']);
		$exp_month 		= sanitize_text_field($req['exp_month']);
		$exp_year 		= sanitize_text_field($req['exp_year']);
		$cvc 			= sanitize_text_field($req['cvc']);
		if (empty($card_number)) {
			$json = array('code' => '0', 'msg' => 'Incorrect Card Number');
			echo json_encode($json);
			exit;
		}
		if (empty($exp_year)) {
			$json = array('code' => '0', 'msg' => 'Incorrect Year');
			echo json_encode($json);
			exit;
		}
		if (empty($exp_month)) {
			$json = array('code' => '0', 'msg' => 'Incorrect Expiray Month');
			echo json_encode($json);
			exit;
		}
		if (empty($cvc)) {
			$json = array('code' => '0', 'msg' => 'Incorrect cvc Number');
			echo json_encode($json);
			exit;
		}
		try{
			$pm = $this->stripe->paymentMethods->create([
				'type' => 'card',
				'card' => [
					'number' 		=> $card_number,
					'exp_month' 	=> $exp_month,
					'exp_year' 		=> $exp_year,
					'cvc' 			=> $cvc,
				],
			]);
			$_SESSION['card_number'] 	=  $card_number;
			$_SESSION['exp_month'] 		=  $exp_month;
			$_SESSION['exp_year'] 		=  $exp_year;
			$_SESSION['cvc'] 			=  $cvc;
			if( !empty( $req['billing_details'])) $_SESSION['billing_details'] = [];
			if( !empty( $req['billing_details']['name'])) $_SESSION['billing_details']['name'] = $req['billing_details']['name'];
			if( !empty( $req['billing_details']['email'])) $_SESSION['billing_details']['email'] = $req['billing_details']['email'];
			if( !empty( $req['billing_details']['phone'])) $_SESSION['billing_details']['phone'] = $req['billing_details']['phone'];
			if( !empty( $req['billing_details']['address'])) $_SESSION['billing_details']['address'] = $req['billing_details']['address'];
			if( !empty( $req['billing_details']['city'])) $_SESSION['billing_details']['city'] = $req['billing_details']['city'];
			if( !empty( $req['billing_details']['state'])) $_SESSION['billing_details']['state'] = $req['billing_details']['state'];
			if( !empty( $req['billing_details']['zip'])) $_SESSION['billing_details']['zip'] = $req['billing_details']['zip'];
			
			return new \WP_REST_Response($pm, 123);
		}catch (\Exception $e) {
			return new \WP_Error('bad_request', $e->getMessage(), array('status' => 500));
		}		
	}

	public function wc_rest_payment_endpoint_handler( $request = null ) {
		$response       = array();
		$parameters 	= $request->get_params();
		$payment_method = sanitize_text_field( $parameters['payment_method'] );
		$order_id       = sanitize_text_field( $parameters['order_id'] );
		//$payment_token  = sanitize_text_field( $parameters['payment_token'] );
		$stripe_source  = sanitize_text_field( $parameters['stripe_source'] );
		$error          = new \WP_Error();

		if ( empty( $payment_method ) ) {
			$error->add( 400, __( "Payment Method 'payment_method' is required.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		}
		if ( empty( $order_id ) ) {
			$error->add( 401, __( "Order ID 'order_id' is required.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		} else {
			$order = wc_get_order($order_id);
			if( $order == false ){
				$error->add( 402, __( "Order ID 'order_id' is invalid. Order does not exist.", 'wc-rest-payment' ), array( 'status' => 400 ) );
				return $error;
			} else if ( $order->get_status() !== 'pending' ) {
				$error->add( 403, __( "Order status is NOT 'pending', meaning order had already received payment. Multiple payment to the same order is not allowed. ", 'wc-rest-payment' ), array( 'status' => 400 ) );
				return $error;
			
			}else{
				$payment_gateways = WC()->payment_gateways->payment_gateways();
				$order->set_payment_method($payment_gateways['stripe']);
				$order->save();
			}
		}
		if ( empty( $stripe_source ) ) {
			$error->add( 404, __( "Payment Source 'stripe_source' is required.", 'wc-rest-payment' ), array( 'status' => 400 ) );
			return $error;
		}
		//$_POST['stripe_token']            = $payment_token;
		$_POST['stripe_source']            = $stripe_source;
		if ( $payment_method === "stripe" ) {
			try{
				$wc_gateway_stripe      = new \WC_Gateway_Stripe();
				$payment_result         = $wc_gateway_stripe->process_payment( $order_id );
			}catch (\Exception $e) {
				return new \WP_Error('bad_request', $e->getMessage(), array('status' => 500));
			}				
			if ( $payment_result['result'] === "success" ) {
				$response['code']    = 200;
				$response['message'] = __( "Your Payment was Successful", "wc-rest-payment" );
				$order = wc_get_order( $order_id );
			    if( $order->get_status() == 'processing' ) {
			        $order->update_status( 'completed' );
			    }
			} else {
				return new \WP_REST_Response( array("c"), 123 );
				$response['code']    = 401;
				$response['message'] = __( "Please enter valid card details", "wc-rest-payment" );
			}
		}  else {
			$response['code'] = 405;
			$response['message'] = __( "Please select an available payment method. Supported payment method can be found at https://wordpress.org/plugins/wc-rest-payment/#description", "wc-rest-payment" );
		}
		return new \WP_REST_Response( $response, 123 );
	}

	public function makePayment(){

	}
}
