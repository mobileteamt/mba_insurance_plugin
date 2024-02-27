<?php

namespace Sycustom\api;

class Misc {

	public function misc_routes() {

		register_rest_route(
			SYI_NAMESSPACE,
			'get_post_meta',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getPostMeta' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'get_attachment_url_by_id',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getAttachmentURL' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'postdetails',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'getPost'),
				'permission_callback' =>  '__return_true',
			)
		);
		
	}

	public function getPostMeta( \WP_REST_Request $req ){
		$error = new \WP_Error();
		if( !empty( $req['post_id'] ) ){
			if( !empty( $req['key'] ) ){
				return  get_post_meta( $req['post_id'], $req['key'], true );
			}else{
				return  get_post_meta( $req['post_id'] );
			}
		}else{
			$error->add(400, __("Mention the post id for the details", 'wp-rest-user'), array('status' => 400));
		  	return $error;
		}
	}

	public function getAttachmentURL( \WP_REST_Request $req ){
		$error = new \WP_Error();
		if( !empty( $req['id'] ) ){
			return  wp_get_attachment_url( $req['id'] );
		}else{
			$error->add(400, __("Mention the attachment id for the details", 'wp-rest-user'), array('status' => 400));
		  	return $error;
		}
	}
  
	public function getPost( \WP_REST_Request $req ){
		$post_id=$req['id'];
		$r_data['post'] = get_post( $post_id );
		$r_data['meta'] = get_post_meta($post_id);
		return $r_data;
	}

}
