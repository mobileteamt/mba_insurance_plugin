<?php
/**
 * Plugin Name: Synapse India Support
 * Plugin URI:  https://www.synapseindia.com/
 * Description: WordPress API functions.
 * Version:     1.1.1
 * Author:      SynapseIndia Outsourcing Pvt. Ltd.
 * Author URI:  https://www.synapseindia.com/
 * License:     GPL-3.0
 * Text Domain: sy-support
 *
 */

defined( 'ABSPATH' ) || die( "Can't access directly" );

// Helper constants.
define( 'SYI_PLUGIN_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'SYI_PLUGIN_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
define( 'SYI_PLUGIN_VERSION', '1.1.1' );
define( 'SYI_NAMESSPACE', 'rvapi/v1' );
define( 'REALTEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/sycustom/apis/functions.php');
require_once(__DIR__ . '/sycustom/CoreUser.php');

use Sycustom\api\Base;
use Sycustom\onfido\Init;
use Sycustom\mbains\MBAInit;

new Base();
new Init();
new MBAInit();


//add_action( 'admin_enqueue_scripts', 'si_add_admin_style' );
add_action( 'wp_enqueue_scripts', 'si_add_frontEnd_assets' );

function si_add_frontEnd_assets() {
  wp_enqueue_style( 'si_frontent_style', plugins_url( '/assets/css/sysupport.css', __FILE__ ), false );
  wp_register_script( 'si_frontent_asset', plugins_url( '/assets/js/sysupport.js', __FILE__ ), array( 'jquery' ), '1.0.0', true);
  $wnm_custom  =  array( 
            'ajax_uri'         => admin_url( 'admin-ajax.php' )
            );
  wp_localize_script( 'si_frontent_asset', 'siscript', $wnm_custom );
  wp_enqueue_script( 'si_frontent_asset' );
}

function si_add_admin_style() {
  wp_enqueue_style( 'si_frontent_style', plugins_url( '/assets/css/sysupport.css',  __FILE__ ), false );
  wp_register_script( 'si_frontent_asset', plugins_url( '/assets/js/sysupport.js',  __FILE__ ), array( 'jquery' ), '1.0.0', true);
  $wnm_custom  =  array( 
            'ajax_uri'         => admin_url( 'admin-ajax.php' )
            );
  wp_localize_script( 'si_frontent_asset', 'siscript', $wnm_custom );
  wp_enqueue_script( 'si_frontent_asset' );
}


function check_before_booking(){
    if ( is_page( 'booking-confirmation' ) && is_user_logged_in() ) {
        $user = wp_get_current_user();
        global $wp;
       // pr($user->roles);
        if ( in_array( 'customer', (array) $user->roles ) || in_array( 'renter', (array) $user->roles ) || in_array( 'guest', (array) $user->roles ) ) {
            $renter_data = array_map( function( $a ){ return $a[0]; }, get_user_meta( $user->ID ) );
            //pr( $renter_data );
            if ( ! \WC()->session->has_session() ) {
              \WC()->session->set_customer_session_cookie(true);
            }
            session_start();
            $mba_insurance_plan = $_SESSION['insurance_plans'];
            $mba_insurance_roadside = $_SESSION['insurance_roadside'];

            if( empty( $mba_insurance_plan ) ){
                //$_POST['value']= '';
                wc_add_notice('Please Set Insurance Plan First', 'notice');
                ?>
                <script type="text/javascript">window.location.assign(history.back(-1))</script>
                <?php
                exit;
            }
            if( empty( $mba_insurance_roadside ) ){
                wc_add_notice('Please Opt Roadside', 'notice');
                ?>
                <script type="text/javascript">window.location.assign(history.back(-1))</script>
                <?php
                exit;
            }if( empty( $renter_data['_credit_card_number'] ) ){
                wc_add_notice('Please enter Credit Card Number', 'notice');
                wp_safe_redirect('/my-profile/');
            }
            if( empty( $renter_data['_credit_card_csc'] ) ){
                wc_add_notice('Please enter Credit Card CVC', 'notice');
                wp_safe_redirect('/my-profile/');
            }
            if( empty( $renter_data['_credit_card_expire_month'] ) ){
                wc_add_notice('Please enter Credit Card Expiary month', 'notice');
                wp_safe_redirect('/my-profile/');
            }
            if( empty( $renter_data['_credit_card_expire_year'] ) ){
                wc_add_notice('Please enter Credit Expiary Year', 'notice');
                wp_safe_redirect('/my-profile/');
            }
            if( empty( $renter_data['_renter_license_state'] ) ){
                wc_add_notice('Please enter license State', 'notice');
                wp_safe_redirect('/my-profile/');
            }
            if( empty( $renter_data['_renter_license_number'] ) ){
                wc_add_notice('Please enter license number', 'notice');
                wp_safe_redirect('/my-profile/');
                exit;
            }
            if( empty( $renter_data['_renter_dob'] ) ){
                wc_add_notice('Please enter your Date of Birth', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
        }
    }else if ( is_page( 'add-listing' ) && is_user_logged_in() ) {
        $user = wp_get_current_user();
       // pr($user->roles);
        if ( in_array( 'owner', (array) $user->roles ) ) {
            $renter_data = array_map( function( $a ){ return $a[0]; }, get_user_meta( $user->ID ) );
            //pr( $renter_data );
            if ( ! \WC()->session->has_session() ) {
              \WC()->session->set_customer_session_cookie(true);

            }
            if( empty( $renter_data['first_name'] ) ){
                wc_add_notice('Please enter First Name', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['last_name'] ) ){
                wc_add_notice('Please enter Last Name', 'notice');
                wp_safe_redirect('/my-profile/');
                exit;
            }
            if( empty( $renter_data['billing_address_1'] ) ){
                wc_add_notice('Please enter Address 1', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['billing_address_2'] ) ){
                wc_add_notice('Please enter Address 2', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['billing_city'] ) ){
                wc_add_notice('Please enter City', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['billing_state'] ) ){
                wc_add_notice('Please enter State', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['billing_postcode'] ) ){
                wc_add_notice('Please enter Post Code', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['billing_phone'] ) ){
                wc_add_notice('Please enter Phone Number', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
            if( empty( $renter_data['billing_country'] ) ){
                wc_add_notice('Please enter Country Code', 'notice');
                wp_safe_redirect('/my-profile/');exit;
            }
        }
    }
}
add_action( 'template_redirect', 'check_before_booking' );