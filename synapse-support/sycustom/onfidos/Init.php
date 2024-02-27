<?php

namespace Sycustom\onfido;
use Onfido;
use Sycustom\onfido\UserControl;

class Init{
	public function __construct(){
		$config = Onfido\Configuration::getDefaultConfiguration();
		new UserControl();
		add_action('user_register', [$this, 'set_default_customer'], 30, 2);
	}

	public function set_default_customer( $user_id, $userdata ){
		if( !empty( $userdata ) && !empty( $userdata['role'] ) ){
			if($userdata['role'] == 'guest' || $userdata['role'] == 'customer' || $userdata['role'] == 'subscriber'){
				wp_update_user( array( 'ID' => $user_id, 'role' => 'customer' ) );
			}				
		}
	}
}
