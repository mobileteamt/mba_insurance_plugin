<?php

namespace Sycustom\api;
use Sycustom\api\User;
use Sycustom\api\Listing;
use Sycustom\api\Booking;
use Sycustom\api\Medias;
use Sycustom\api\Payment;
use Sycustom\api\OrderAndProduct;
use Sycustom\api\Misc;

final class Base{

	protected static $instance = null;

	public function __construct(){
		$this->registerRouts();
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function init(){
		$this->registerRouts();
	}

	public function registerRouts(){
		add_action( 'rest_api_init', array( new User(), 'user_routes' ) );
		add_action( 'rest_api_init', array( new Listing(), 'listing_routes' ) );
		add_action( 'rest_api_init', array( new Booking(), 'booking_routes' ) );
		add_action( 'rest_api_init', array( new Medias(), 'media_routes' ) );
		add_action( 'rest_api_init', array( new Payment(), 'payment_routes' ) );
		add_action( 'rest_api_init', array( new OrderAndProduct(), 'order_routes' ) );
		add_action( 'rest_api_init', array( new Misc(), 'misc_routes' ) );
	}

}
