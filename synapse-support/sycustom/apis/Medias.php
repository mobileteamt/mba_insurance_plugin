<?php

namespace Sycustom\api;

class Medias {

	public function media_routes() {
		register_rest_route(
			SYI_NAMESSPACE,
			'media_upload',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'uploadMedia' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'get_medias',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getAll' ),
				'permission_callback' => function () {
			    	return current_user_can( 'edit_others_posts' );
			  	},
			)
		);
	}

	public function uploadMedia( \WP_REST_Request $request ){
		return uploadFile($_FILES['image']);
	}

	public function getAll(){
		$data = wp_get_current_user();
		return $data;
	}

}
