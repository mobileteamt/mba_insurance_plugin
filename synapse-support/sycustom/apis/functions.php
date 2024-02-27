<?php


function rvapi_auth_token_before_dispatch( $response, $user ){

	$data=[];
    //$user=wp_get_current_user();
    $user_id=$user->ID;

    $profile_image_id=get_user_meta($user_id, 'listeo_core_avatar_id', true);
    $profile_image=wp_get_attachment_url( $profile_image_id );

    if(empty($profile_image)){
        $profile_image=get_avatar_url( $user_id );
    }

    $role='';
    if ( in_array( 'owner', $user->roles, true )){
        $role='owner';
    } 
    if ( in_array( 'guest', $user->roles, true )){
        $role='guest';
    } 

    $data['user_id']= $user_id;
    $data['user_name']= $user->user_login;
    $data['email']= $user->user_email;

    $data['first_name']= get_user_meta($user_id, 'first_name', true);
    $data['last_name']= get_user_meta($user_id, 'last_name', true);
    
    $data['description']= get_user_meta($user_id, 'description', true);
    $data['role']= $role;
    $data['mobile']='';
    $data['location']= '';
    $data['profile_image']= $profile_image;
    $data['banner_image']='';
    return ['token'=>$response['token'],'user'=>$data,'status'=> 'success'];
    //return $data;
}
add_filter('jwt_auth_token_before_dispatch', 'rvapi_auth_token_before_dispatch',10,2);

function uploadFile( $files, $img_name = 'image', $post_id = null, $post_data=[], $image_only = false ){
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    if( $image_only ){
      $file_extension_type = array('jpg', 'jpeg', 'jpe', 'gif', 'png', 'bmp', 'tiff', 'tif', 'ico', 'zip', 'pdf', 'docx');
      $file_extension = strtolower(pathinfo($files['name'], PATHINFO_EXTENSION));
      if (!in_array($file_extension, $file_extension_type)) {
          return wp_send_json(
              array(
                  'success' => false,
                  'data'    => array(
                      'message'  => __('The uploaded file is not a valid file. Please try again.'),
                      'filename' => esc_html($files['name']),
                  ),
              )
          );
      }
    }
    $attachment_id = media_handle_upload($img_name, $post_id, $post_data);
    if (isset($post_data['context']) && isset($post_data['theme'])) {
        if ('custom-background' === $post_data['context']) {
            update_post_meta($attachment_id, '_wp_attachment_is_custom_background', $post_data['theme']);
        }

        if ('custom-header' === $post_data['context']) {
            update_post_meta($attachment_id, '_wp_attachment_is_custom_header', $post_data['theme']);
        }
    }
    if (is_wp_error($attachment_id)) {
        return wp_send_json(
            array(
                'success' => false,
                'data'    => array(
                    'message'  => $attachment_id->get_error_message(),
                    'filename' => esc_html($files['name']),
                ),
            )
        );
    }
    $attachment = wp_prepare_attachment_for_js($attachment_id);
    if (!$attachment) {
        return wp_send_json(
            array(
                'success' => false,
                'data'    => array(
                    'message'  => __('Image cannot be uploaded.'),
                    'filename' => esc_html($_FILES['async-upload']['name']),
                ),
            )
        );
    }
    return wp_send_json(
        array(
            'success' => true,
            'data'    => $attachment,
        )
    );
}

function pr( $data=[], $trigger = true ){
    echo '<pre>';
    print_r( $data );
    echo '</pre>';
    if( $trigger ) die;
}

add_filter( 'listeo_rental_fields', 'add_mba_required_fields', 100, 1);
if( !function_exists( 'add_mba_required_fields' ) ){
    function add_mba_required_fields( $field ){
        $old_fileds = $field['fields'];
        $new_fields = [
            '_vehicle_trim'=>[
                'title'     => "Vehicle Trim",
                'name'      => "Vehicle Trim",
                'id'        => "_vehicle_trim",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            '_vehicle_vin' => [
                'title'     => "Vehicle Vin",
                'name'      => "Vehicle Vin",
                'id'        => "_vehicle_vin",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            '_vehicle_value' => [
                'title'     => "Vehicle Proice",
                'name'      => "Vehicle Proice",
                'id'        => "_vehicle_value",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
        ];
        $result = array_merge( $old_fileds, $new_fields);
        $field['fields'] = $result;
        //pr( $result );
        return $field;
    }
}
add_filter( 'listeo_user_owner_fields', 'add_mba_renter_fields', 100, 1);
add_filter( 'listeo_user_guest_fields', 'add_mba_owner_fields', 100, 1);
if( !function_exists( 'add_mba_owner_fields' ) ){
    function add_mba_renter_fields( $field ){
        $new_fields = [
            'billing_address_1'=>[
                'title'     => "Billing Address 1",
                'name'      => "Billing Address 1",
                'label'      => "Billing Address 1",
                'id'        => "billing_address_1",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_address_2'=>[
                'title'     => "Billing Address 2",
                'name'      => " Billing Address 2",
                'label'     => "Billing Address 2",
                'id'        => "billing_address_2",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_state'=>[
                'title'     => "Billing State",
                'name'      => "Billing State",
                'label'     => "Billing State",
                'id'        => "billing_state",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_postcode'=>[
                'title'     => "Billing Postcode",
                'name'      => "Billing Postcode",
                'label'     => "Billing Postcode",
                'id'        => "billing_postcode",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_country'=>[
                'title'     => "Billing Country",
                'name'      => "Billing Country",
                'label'     => "Billing Country",
                'id'        => "billing_country",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_phone'=>[
                'title'     => "Billing Phone",
                'name'      => "Billing Phone",
                'label'     => "Billing Phone",
                'id'        => "billing_phone",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            '_renter_license_state'=>[
                'title'     => "License State",
                'name'      => "License State",
                'label'     => "License State",
                'id'        => "_renter_license_state",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            '_renter_license_number'=>[
                'title'     => "License Number",
                'name'      => "License Number",
                'label'      => "License Number",
                'id'        => "_renter_license_number",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            '_renter_dob'=>[
                'title'     => "Date Of Birth",
                'name'      => "Date Of Birth",
                'label'      => "Date Of Birth",
                'id'        => "_renter_dob",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
        ];
        $result = array_merge( $field, $new_fields);
        return $result;
    }
}
if( !function_exists( 'add_mba_owner_fields' ) ){
    function add_mba_owner_fields( $field ){
        $new_fields = [
            'billing_address_1'=>[
                'title'     => "Billing Address 1",
                'name'      => "Billing Address 1",
                'label'      => "Billing Address 1",
                'id'        => "billing_address_1",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_address_2'=>[
                'title'     => "Billing Address 2",
                'name'      => " Billing Address 2",
                'label'     => "Billing Address 2",
                'id'        => "billing_address_2",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_state'=>[
                'title'     => "Billing State",
                'name'      => "Billing State",
                'label'     => "Billing State",
                'id'        => "billing_state",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_postcode'=>[
                'title'     => "Billing Postcode",
                'name'      => "Billing Postcode",
                'label'     => "Billing Postcode",
                'id'        => "billing_postcode",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_country'=>[
                'title'     => "Billing Country",
                'name'      => "Billing Country",
                'label'     => "Billing Country",
                'id'        => "billing_country",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
            'billing_phone'=>[
                'title'     => "Billing Phone",
                'name'      => "Billing Phone",
                'label'     => "Billing Phone",
                'id'        => "billing_phone",
                'icon'      => '',
                'type'      => 'text',
                'invert'    => '',
                'desc'      => '',
                'options'   => [],
            ],
        ];
        $result = array_merge( $field, $new_fields);
        return $result;
    }
}


/**
*
* Generate a random 4 digit code
*
* @param void
* @return str a 4 digit code
*
**/

function bdpwr_generate_4_digit_code() {
  
  /**
  *
  * Filter the length of the code
  *
  * @param $length int the number of digits for the code
  *
  **/
  
  $length = apply_filters( 'bdpwr_code_length' , 4 );
  
  /**
  *
  * Filter whether or not to include letters in the code
  *
  * @param $include boolean
  *
  **/
  
  $include_letters = apply_filters( 'bdpwr_include_letters' , false );
  
  $selection_string = ( $include_letters ) ? '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' : '0123456789';
  
  /**
  *
  * Filter the selection string to use any characters you like
  *
  * @param $string str the string to select a code from
  *
  **/
  
  $selection_string = apply_filters( 'bdpwr_selection_string' , $selection_string );
  
  return substr( str_shuffle( $selection_string ) , 0 , $length );
  
}


/**
*
* Get new code expiration time
*
* @param void
* @return int the unix timestamp for a code expiry
*
**/

function bdpwr_get_new_code_expiration_time() {
  
  /**
  *
  * Filter the number of seconds codes should be valid for
  * Set -1 for no expiry
  *
  * @param $seconds int the number of seconds the code will be valid for
  *
  **/
  
  $valid_seconds = apply_filters( 'bdpwr_code_expiration_seconds' , 900 );
  $time_string = '+' . $valid_seconds . ' seconds';
  return strtotime( $time_string );
  
}


/**
*
* Get date from unix timestamp
*
* @param $time str the unix timestamp
* @return str the formatted date
*
**/

function bdpwr_get_formatted_date( $time = false ) {
  
  if( ! $time ) {
    $time = strtotime( 'now' );
  }
  
  /**
  *
  * Filter the date format used in this plugin
  *
  * @param $format str the php date format string
  *
  **/
  
  $format = apply_filters( 'bdpwd_date_format' , 'H:i' );
  
  $date = new DateTime();
  $date->setTimestamp( $time );
  $date->setTimezone( wp_timezone());

  return date_format( $date , $format );
  
}


/**
*
* Get a list of the roles allowed to reset their password with this plugin
*
* @param void
* @return arr an array of role slugs
*
**/

function bdpwr_get_allowed_roles() {
  
  $all_roles = wp_roles()->roles;
  $roles_array = array();
  
  foreach( $all_roles as $slug => $role ) {
    $roles_array[] = $slug;
  }
  
  /**
  *
  * Filter the roles allowed to use this plugin to reset a password
  *
  * @param $roles arr the array of allowed roles
  *
  **/
  
  return apply_filters( 'bdpwr_allowed_roles' , $roles_array );
  
}


/**
*
* Get a user
*
* @param $user_id int the ID of the WP User
* @return obj a BDPWR_User user object
*
**/

function bdpwr_get_user( $user_id = false ) {
  return new BDPWR_User( $user_id );
}


/**
*
* Send a password reset code email
*
* @param $email str the email address to send to
* @param $code the code to send
* @param $expiry int the time that the code will expire
* @return bool true on success false on failure
*
**/

function bdpwr_send_password_reset_code_email( $email = false , $code = false , $expiry = 0 ) {
  
  if( ! $email ) {
    throw new Exception( __( 'An email address is required for the reset code email.' , 'bdvs-password-reset' ));
  }
  
  if( ! $code ) {
    throw new Exception( __( 'No code was provided for the password reset email.' , 'bdvs-password-reset' ));
  }
  
  ob_start(); ?>

  A password reset was requested for your account and your password reset code is <?php echo $code; ?>.

  <?php if( $expiry !== 0 ) { ?>
    Please note that this code will expire at <?php echo bdpwr_get_formatted_date( $expiry ); ?>.
  <?php } ?>

  <?php
  $text = ob_get_contents();
  if( $text ) { ob_end_clean(); }
  
  /**
  *
  * Filter the subject of the email
  *
  * @param $subject str the subject of the email
  *
  **/
  
  $subject = apply_filters( 'bdpwr_code_email_subject' , 'Password Reset' );
  
  /**
  *
  * Filter the body of the email
  *
  * @param $text str the content of the email
  * @param $email str the email address being sent to
  * @param $code the code being sent
  * @param $expiry int the unix timestamp for the code's expiry
  *
  **/
  
  $text = apply_filters( 'bdpwr_code_email_text' , $text , $email , $code , $expiry );
  
  return wp_mail( $email , $subject , $text );
  
}


/**
*
* BACKWARDS COMPATIBILITY FILLS
*
* The following declares new functions available from WP 5.3.0
* in the case that these have not already been declared, i.e. WP is < 5.3.0
*
**/

/**
*
* Retrieves the timezone from site settings as a string.
*
* Uses the `timezone_string` option to get a proper timezone if available,
* otherwise falls back to an offset.
*
* @since 5.3.0
*
* @return string PHP timezone string or a ±HH:MM offset.
*
**/

if( ! function_exists( 'wp_timezone_string' )) {
  function wp_timezone_string() {
    $timezone_string = get_option( 'timezone_string' );

    if ( $timezone_string ) {
      return $timezone_string;
    }

    $offset  = (float) get_option( 'gmt_offset' );
    $hours   = (int) $offset;
    $minutes = ( $offset - $hours );

    $sign      = ( $offset < 0 ) ? '-' : '+';
    $abs_hour  = abs( $hours );
    $abs_mins  = abs( $minutes * 60 );
    $tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

    return $tz_offset;
  }
}

/**
*
* Retrieves the timezone from site settings as a `DateTimeZone` object.
*
* Timezone can be based on a PHP timezone string or a ±HH:MM offset.
*
* @return DateTimeZone Timezone object.
* 
**/

if( ! function_exists( 'wp_timezone' )) {
  function wp_timezone() {
    return new DateTimeZone( wp_timezone_string() );
  }
}