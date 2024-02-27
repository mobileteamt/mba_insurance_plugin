<?php
namespace Sycustom\api;
use \Listeo_Core_Reviews;

class Listing {

	public function _construct(){
		$user = wp_get_current_user();
		if ( !in_array( 'guest', (array) $user->roles ) || !in_array( 'owner', (array) $user->roles ) ) {
		    return false;
		}else{
			return true;
		}
	}

	public function listing_routes() {
		register_rest_route(
			SYI_NAMESSPACE,
			'getlisting',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getListing' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'get-allreviews',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getAllReviews' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'getratingcategories',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRatingCategories' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'getrentalcategory',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getRentalCategory' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'getfeatures',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getFeatersFromLising' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);

		register_rest_route(
			SYI_NAMESSPACE,
			'list-action',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'performAction' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'list-details',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'listingDetails' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'list-create',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'addList' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'list-update',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'updateList' ),
				'permission_callback' => function () {
			    return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'list-trash',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'trashList' ),
				'permission_callback' => function () {
			    return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'add-bookmark',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'createBookmark' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'remove-bookmark',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'removeBookmark' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'get-bookmarks',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getMyBookmarks' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'get-mylisting',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getMyListing' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'get-listing_categories',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'getListingCategories' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'add-comment',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'createComment' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);
		register_rest_route(
			SYI_NAMESSPACE,
			'check-for-review',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'checkIfCanUserReview' ),
				'permission_callback' => function () {
			    	return is_user_logged_in();
			  },
			)
		);

	}

	public function getListing( \WP_REST_Request $req ) {
		global $listeo_core;
		global $wp_post_types;
		$location  	= (isset($req['location_search'])) ? sanitize_text_field( stripslashes( $req['location_search'] ) ) : '';
		$keyword   	= (isset($req['keyword_search'])) ? sanitize_text_field( stripslashes( $req['keyword_search'] ) ) : '';
		$radius   	= (isset($req['search_radius'])) ?  sanitize_text_field( stripslashes( $req['search_radius'] ) ) : '';
		$orderby   	= (isset($req['orderby'])) ?  sanitize_text_field( stripslashes( $req['orderby'] ) ) : '';
		$order   	= (isset($req['order'])) ?  sanitize_text_field( stripslashes( $req['order'] ) ) : '';
		$style   	= sanitize_text_field( stripslashes( $req['style'] ) );
		$grid_columns  = sanitize_text_field( stripslashes( $req['grid_columns'] ) );
		$per_page   = sanitize_text_field( stripslashes( $req['per_page'] ) );
		$date_range =  (isset($req['date_range'])) ? sanitize_text_field(  $req['date_range']  ) : '';
		$region   	= (isset($req['tax-region'])) ?  sanitize_text_field(  $req['tax-region']  ) : '';
		$category   = (isset($req['tax-listing_category'])) ?  sanitize_text_field(  $req['tax-listing_category']  ) : '';
		$feature   	= (isset($req['tax-listing_feature'])) ?  sanitize_text_field(  $req['tax-listing_feature']  ) : '';
		$date_start = '';
		$date_end 	= '';
		if($date_range){
			$dates = explode(' - ',$date_range);	
			$date_start = $dates[0];
			$date_end = $dates[1]; 
		}
		if(empty($per_page)) { $per_page = get_option('listeo_listings_per_page',10); }
		$query_args = array(
			'ignore_sticky_posts'   => 1,
			'post_type'         	=> 'listing',
			'orderby'           	=> $orderby,
			'order'             	=>  $order,
			'offset'            	=> ( absint( $req['page'] ) - 1 ) * absint( $per_page ),
			'location'   			=> $location,
			'keyword'   			=> $keyword,
			'search_radius'   		=> $radius,
			'posts_per_page'    	=> $per_page,
			'date_start'    		=> $date_start,
			'date_end'    			=> $date_end,
			'tax-region'    		=> $region,
			'tax-listing_feature'   => $feature,
			'tax-listing_category'  => $category,
		);
		$query_args['listeo_orderby'] = (isset($req['listeo_core_order'])) ? sanitize_text_field( $req['listeo_core_order'] ) : false;
		$taxonomy_objects = get_object_taxonomies( 'listing', 'objects' );
		foreach ($taxonomy_objects as $tax) {
			if(isset($req[ 'tax-'.$tax->name ] )) {
				$query_args[ 'tax-'.$tax->name ] = $req[ 'tax-'.$tax->name ];
			}
    	}
		$orderby = isset($req['listeo_core_order']) ? $req['listeo_core_order'] : 'date';
		$listings = \Listeo_Core_Listing::get_real_listings( $query_args );
		$posts=[];
		foreach($listings->posts as $post){
			$post_id=$post->ID;
			$categories=$this->getListingTypeTags($post_id);
			$friendly_address = get_post_meta($post->ID, '_friendly_address', true);
			$address = get_post_meta($post->ID, '_address', true);
			$address =  (!empty($friendly_address)) ? $friendly_address : $address;
			$currency_abbr = get_option('listeo_currency');
			$currency_symbol = \Listeo_Core_Listing::get_currency_symbol($currency_abbr);
			$posts[]=[
				'id'							=> $post_id,
				'name'						=> $post->post_name,
				'title'						=> $post->post_title,
				'content'					=> $post->post_content,
				'image_thumbnail'	=>get_the_post_thumbnail_url($post_id, 'thumbnail'),
				'image_full'			=>get_the_post_thumbnail_url($post_id, 'full'),
				'categories'			=>$categories,
				'verified'				=>get_post_meta($post_id,'_verified',true ),
				'address'					=> $address,
				'currency_Symbol'	=> $currency_symbol,
				'price_range'			=> get_the_listing_price_range($post),
				'rating'					=>get_post_meta($post_id, 'listeo-avg-rating', true),
				'bookmark'				=> listeo_core_check_if_bookmarked($post_id),
				'listing_type'		=>get_post_meta($post_id, '_listing_type', true),
			];
		}
		$result = array(
			'posts'							=>$posts,
			'found_listings'    => $listings->have_posts(),
			'max_num_pages' 		=> $listings->max_num_pages,
		);
		return $result;// [$listings, $result];
	}

	public function getMyListing( \WP_REST_Request $req ) {
		$page = (isset($req['listings_paged'])) ? $req['listings_paged'] : 1;
		if(isset($req['status']) && !empty($req['status'])) {
			$status = $req['status'];
		} else {
			$status = '';
		}
		$status = isset($req['status']) ? $req['status'] : '' ;
		$search = isset($req['search']) ? $req['search'] : '' ;
		$listings = $this->get_agent_listings($status,$page,10,$search);
		//pr( $listings );
		$posts=[];
		foreach($listings as $k=>$post){
			$post_id=$post->ID;
			$categories=$this->getListingTypeTags($post_id);
			$friendly_address = get_post_meta($post->ID, '_friendly_address', true);
			$address = get_post_meta($post->ID, '_address', true);
			$address =  (!empty($friendly_address)) ? $friendly_address : $address;
			$currency_abbr = get_option('listeo_currency');
			$currency_symbol = \Listeo_Core_Listing::get_currency_symbol($currency_abbr);
			$posts[]=[
				'id'						=> $post_id,
				'name'						=> $post->post_name,
				'title'						=> $post->post_title,
				'content'					=> $post->post_content,
				'image_thumbnail'			=>get_the_post_thumbnail_url($post_id, 'thumbnail'),
				'image_full'				=>get_the_post_thumbnail_url($post_id, 'full'),
				'categories'				=>$categories,
				'verified'					=>get_post_meta($post_id,'_verified',true ),
				'address'					=> $address,
				'currency_Symbol'			=> $currency_symbol,
				'price_range'				=> get_the_listing_price_range($post),
				'rating'					=>get_post_meta($post_id, 'listeo-avg-rating', true),
				'bookmark'					=> listeo_core_check_if_bookmarked($post_id),
				'listing_type'				=>get_post_meta($post_id, '_listing_type', true),
			];
		}
		$result = array(
			'posts'							=>$posts,
			'found_listings'    => count($listings),
			'max_num_pages' 		=> 10,
		);
		return $result;
	}

	public function get_agent_listings($status,$page,$per_page,$search = false){
		$current_user = wp_get_current_user();
		switch ($status) {
			case 'pending':
				$post_status = array('pending_payment','draft','pending');
			break;
			case 'active':
				$post_status = array('publish');
			break;
			case 'expired':
				$post_status = array('expired');
			break;
			default:
				$post_status = array('publish','pending_payment','expired','draft','pending');
			break;
		}
		$q = new \WP_Query(
			array(
				'author'        	=>  $current_user->ID,
		    'posts_per_page'  	=> $per_page,
		    'post_type'		  	=> 'listing',
		    'paged'				=> $page,
		    's'					=> $search,
		    'post_status'	  	=> $post_status,
			)
		);
		return $q->posts;
	}

	private  function getListingTypeTags($post_id){
		$terms=get_the_terms( $post_id, 'listing_category' );
		$categories=[];
		if ( !empty( $terms ) ) {
			foreach( $terms as $term ){
				$categories[]=[
					'term_id'			=> $term-> term_id,
					'name'				=> $term->name,
					'slug'				=> $term->slug,
					'taxonomy'			=>'listing_category',
				];
			}
		}
		$listingType=get_post_meta($post_id, '_listing_type', true);
		switch ($listingType) {
			case 'service':
				$type_terms 		= get_the_terms($post_id, 'service_category');
				$taxonomy_name 	= 'service_category';
				break;
			case 'rental':
				$type_terms 		= get_the_terms($post_id, 'rental_category');
				$taxonomy_name 	= 'rental_category';
				break;
			case 'event':
				$type_terms 		= get_the_terms($post_id, 'event_category');
				$taxonomy_name 	= 'event_category';
				break;
			case 'classifieds':
				$type_terms 		= get_the_terms($post_id, 'classifieds_category');
				$taxonomy_name 	= 'classifieds_category';
				break;
			case 'region':
				$type_terms 		= get_the_terms($post_id, 'region');
				$taxonomy_name 	= 'region';
				break;
		}
		if ( !empty( $type_terms) ) {
			foreach ($type_terms as $term) {
				$categories[]=[
					'term_id'			=> $term-> term_id,
					'name'				=> $term->name,
					'slug'				=> $term->slug,
					'taxonomy'		=>$taxonomy_name,
				];
			}
		}	
		return $categories;
	}

	public function listingDetails( \WP_REST_Request $req ){
		$post_id 						= $req['id'];
		$post 							= get_post( $post_id );
		$categories 					= $this->getListingTypeTags($post_id);
		$friendly_address 				= get_post_meta($post->ID, '_friendly_address', true);
		$address 						= get_post_meta($post->ID, '_address', true);
		$address 						=  (!empty($friendly_address)) ? $friendly_address : $address;
		$currency_abbr 					= get_option('listeo_currency');
		$currency_symbol 				= \Listeo_Core_Listing::get_currency_symbol($currency_abbr);
		$data 							= [
			'id'						=> $post_id,
			'name'						=> $post->post_name,
			'title'						=> $post->post_title,
			'content'					=> $post->post_content,
			'added_by'					=> get_user_by('id',$post->post_author),
			'image_thumbnail'			=>get_the_post_thumbnail_url($post_id, 'thumbnail'),
			'image_full'				=>get_the_post_thumbnail_url($post_id, 'full'),
			'categories'				=>$categories,
			'verified'					=>get_post_meta($post_id,'_verified',true ),
			'address'					=> $address,
			'currency_Symbol'			=> $currency_symbol,
			'price_range'				=> get_the_listing_price_range($post),
			'rating'					=>get_post_meta($post_id, 'listeo-avg-rating', true),
			'bookmark'					=> listeo_core_check_if_bookmarked($post_id),
			'listing_type'				=>get_post_meta($post_id, '_listing_type', true),
			'gallery'					=>get_post_meta( $post_id, '_gallery', true ),
			'features'					=> $this->getListingFeatures($post_id),
			'overview'					=> $this-> getListingOverview($post_id),
		];
		//return ['data'=> $data, 'status'=> 'success'];
		$list_id 			= $req['id'];
		$data['post'] 		= get_post( $list_id );
		$post_meta 			= get_post_meta( $list_id );
		$type 				= get_post_meta( $list_id, '_listing_type',true );
		$details_list 		= [];
		switch ( $type ) {
			case 'service':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_service(); 
				break;	
			case 'rental':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_rental(); 
				break;
			case 'event':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_event(); 
				break;
			case 'classifieds':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_classifieds(); 
				if( !empty( $details_list ) ){
					unset( $details_list['fields']['_classifieds_price'] );	
				}
				break;
			default:
				break;
		}
		$taxonomies 	= get_option( 'listeo_single_taxonomies_checkbox_list', array('listing_feature') );
		$list_taxonomy 	= [];
		if( !empty( $taxonomies ) ){			
			foreach( $taxonomies as $tax ){
				//echo '<pre>';
				//print_r( $tax );die;
				$list_taxonomy[$tax] 				= [];
				$list_taxonomy[$tax]['term_list'] 	= get_the_term_list( $list_id, $tax );
				$list_taxonomy[$tax]['tax_obj'] 	= get_taxonomy( $tax );
				$list_taxonomy[$tax]['taxonomy'] 	= get_taxonomy_labels( $list_taxonomy[$tax]['tax_obj'] );
			};
		}
		$data['details_list'] 	= $details_list;
		$data['list_taxonomy'] 	= $list_taxonomy;
		$bookings = new \Listeo_Core_Bookings_Calendar;
		if (isset($post_meta['_slots_status'][0]) && !empty($post_meta['_slots_status'][0])) {
			if (isset($post_meta['_slots'][0])) {
				$slots = json_decode($post_meta['_slots'][0]);
				if (strpos($post_meta['_slots'][0], '-') == false) $slots = false;
			} else {
				$slots = false;
			}
		} else {
			$slots = false;
		}
		if (isset($post_meta['_opening_hours'][0])) {
			$opening_hours = json_decode($post_meta['_opening_hours'][0], true);
		}
		if ($post_meta['_listing_type'][0] == 'rental' || $post_meta['_listing_type'][0] == 'service') {
			if ($post_meta['_listing_type'][0] == 'rental') {
				$records = $bookings->get_bookings(
					date('Y-m-d H:i:s'),
					date('Y-m-d H:i:s', strtotime('+3 years')),
					array('listing_id' => $list_id, 'type' => 'reservation'),
					$by 						= 'booking_date',
					$limit 						= '',
					$offset 					= '',
					$all 						= '',
					$listing_type 				= 'rental'
				);
			} else {
				$records = $bookings->get_bookings(
					date('Y-m-d H:i:s'),
					date('Y-m-d H:i:s', strtotime('+3 years')),
					array('listing_id' => $list_id, 'type' => 'reservation'),
					'booking_date',
					$limit = '',
					$offset = '',
					'owner'
				);
			}
			$wpk_start_dates 	= [];
			$wpk_end_dates 		= [];
			$disabled_dates 	= [];
			if (!empty($records)) {
				foreach ($records as $record) {
					if ($post_meta['_listing_type'][0] == 'rental') {
						// when we have one day reservation
						if ($record['date_start'] == $record['date_end']) {
							$wpk_start_dates[] = date('Y-m-d', strtotime($record['date_start']));
							$wpk_end_dates[] = date('Y-m-d', strtotime($record['date_start'] . ' + 1 day'));
						} else {
							$wpk_start_dates[] = date('Y-m-d', strtotime($record['date_start']));
							$wpk_end_dates[] = date('Y-m-d', strtotime($record['date_end']));
							$period = new \DatePeriod(
								new \DateTime(date('Y-m-d', strtotime($record['date_start'] . ' + 1 day'))),
								new \DateInterval('P1D'),
								new \DateTime(date('Y-m-d', strtotime($record['date_end'])))
							);
							foreach ($period as $day_number => $value) {
								$disabled_dates[] = $value->format('Y-m-d');
							}
						}
					} else {
						if ($record['date_start'] == $record['date_end']) {
							$disabled_dates[] = date('Y-m-d', strtotime($record['date_start']));
						} else {
							$period = new \DatePeriod(
								new \DateTime(date('Y-m-d', strtotime($record['date_start']))),
								new \DateInterval('P1D'),
								new \DateTime(date('Y-m-d', strtotime($record['date_end'] . ' +1 day')))
							);
							foreach ($period as $day_number => $value) {
								$disabled_dates[] = $value->format('Y-m-d');
							}
						}
					}
				}
			}
			$reviewss 	= get_comments([
						    'post_id' => $list_id,
						    'status' => 'approve',
						    'hierarchical' => 'threaded'
						]);
			$criteria_fields = listeo_get_reviews_criteria();
			$rev_structure = $criteria = [];
			foreach ($criteria_fields as $key => $value) {
				$value['rating'] = get_post_meta($post->ID, $key.'-avg', true);
				$criteria[$key] = $value;
			}
			if( !empty( $reviewss ) ){
				foreach( $reviewss as $revs ){
					$key 						= $revs->comment_ID;
					$rev 						= [];
					$rev['comment_ID'] 			= $revs->comment_ID;
					$rev['comment_post_ID'] 	= $revs->comment_post_ID;
					$rev['comment_author'] 		= $revs->comment_author;
					$rev['comment_author_email'] = $revs->comment_author_email;
					$rev['comment_date'] 		= $revs->comment_date;
					$rev['user_id'] 			= $revs->user_id;
					$rev['avatar'] 				= get_avatar_url($revs);
					$rev['comment_content'] 	= $revs->comment_content;
					$rev['star_rating'] 		= get_comment_meta( $key, 'listeo-rating', true );
					array_push($rev_structure, $rev );
				}
			}
			$data['list_menu'] 			= get_post_meta( $list_id, '_menu', 1 );
			$data['rating'] 			= get_post_meta( $list_id, 'listeo-avg-rating', true );
			$data['wpk_start_dates'] 	= $wpk_start_dates;
			$data['wpk_end_dates'] 		= $wpk_end_dates;
			$data['disabled_dates'] 	= $disabled_dates;
			$data['reviews'] 			= $rev_structure;
			$data['criteria_fields'] 	= $criteria;
			$data['meta'] 				= array_map(function($arr){ return $arr[0];}, $post_meta);
			//$data['reviews'] 			= json_decode(json_encode ( $reviewss ) , true);
		}
		//pr(gettype($data['reviews']));
		return $data ;
	}

	private function getListingOverview($post_id){
		$type = get_post_meta( $post_id, '_listing_type',true );
		$details_list ='';
		switch ( $type ) {
			case 'service':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_service(); 
			break;	
			case 'rental':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_rental(); 
			break;
			case 'event':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_event(); 
			break;
			case 'classifieds':
				$details_list = \Listeo_Core_Meta_Boxes::meta_boxes_classifieds(); 
				if( !empty( $details_list ) ){
					unset( $details_list['fields']['_classifieds_price'] );	
				}
			break;
		}
		$overviews=[];
		foreach($details_list['fields'] as $field){
			$meta=get_post_meta($post_id,$field['id'], true);
			if(!empty($meta)){
				$overviews[]=[
					'name'=>$field['name'],
					'id'=>$field['id'],
					'icon'=>$field['icon'],
					'value'=>$meta,
				];	
			}
		}
		return $overviews;
	}

	private function getListingFeatures($post_id){
		//listing_feature
		$terms = get_the_terms($post_id, 'listing_feature');
		$features=[];
		if(!empty( $terms ) ){
			foreach($terms as $term){
				$svg_id=get_term_meta($term-> term_id,'_icon_svg',true);
				$icon_svg=(!empty($svg_id)) ? wp_get_attachment_url($svg_id) : '';
				$features[]=[
					'term_id'	=> $term-> term_id,
					'name'		=> $term->name,
					'slug'		=> $term->slug,
					'icon'		=> get_term_meta($term-> term_id, 'icon', true),
					'icon_svg'	=> $icon_svg,
					'taxonomy'	=>'listing_feature',
				];
			}
		}
		return $features;
	}

	public function addList( \WP_REST_Request $req ){
		//pr($req);
		$rental_categories 	= !empty( $req['rental_categories'] ) ? $req['rental_categories'] : [];
		$listing_categories = !empty( $req['listing_categories'] ) ? $req['listing_categories'] : [];
		$listing_feature 	= !empty( $req['listing_feature'] ) ? $req['listing_feature'] : [];
		if(empty($req['title'])){
		    $json = array('code'=>'0','msg'=>'Rental Title is required field');
		    echo json_encode($json);
		    exit;    
		}
		if(empty($req['description'])){
		    $json = array('code'=>'0','msg'=>'Rental Description is required field');
		    echo json_encode($json);
		    exit;    
		}
		if(empty($req['_friendly_address'])){
		    $json = array('code'=>'0','msg'=>'Please enter Address');
		    echo json_encode($json);
		    exit;    
		}
		$post = [
			'post_title'				=> wp_strip_all_tags( $req['title'] ),
			'post_type' 				=> 'listing',
			'post_content' 				=> sanitize_text_field( $req['description'] ),
			'post_status'				=> 'publish',
			'tax_input' 				=>  array(
						"rental_category" => $rental_categories,
						'listing_category' => $listing_categories,
						"listing_feature" => $listing_feature
					),
			'meta_input'				=> [
					//'_listing_expires' => !empty( $req['_listing_expires'] ) ? $req['_listing_expires'] : time(),
					'_listing_type' 		=> !empty( $req['_listing_type'] ) ? $req['_listing_type'] : 'rental',
					'_friendly_address' 	=> !empty( $req['_friendly_address'] ) ? $req['_friendly_address'] : '',
					'_address' 				=> !empty( $req['_address'] ) ? $req['_address'] : '',
					'_geolocation_lat' 		=> !empty( $req['_geolocation_lat'] ) ? $req['_geolocation_lat'] : '',
					'_geolocation_long' 	=> !empty( $req['_geolocation_long'] ) ? $req['_geolocation_long'] : '',
					'_place_id' 			=> !empty( $req['_place_id'] ) ? $req['_place_id'] : '',
					'_price_night' 			=> !empty( $req['_price_night'] ) ? $req['_price_night'] : '',
					'_booking' 				=> !empty( $req['_booking'] ) ? $req['_booking'] : '',
					'_check_in' 			=> !empty( $req['_check_in'] ) ? $req['_check_in'] : '',
					'_phone' 				=> !empty( $req['_phone'] ) ? $req['_phone'] : '',
					'_email' 				=> !empty( $req['_email'] ) ? $req['_email'] : '',
					'_email_contact_widget' => !empty( $req['_email_contact_widget'] ) ? $req['_email_contact_widget'] : '',
					'_facebook' 			=> !empty( $req['_facebook'] ) ? $req['_facebook'] : '',
					'_twitter' 				=> !empty( $req['_twitter'] ) ? $req['_twitter'] : '',
					'_youtube' 				=> !empty( $req['_youtube'] ) ? $req['_youtube'] : '',
					'_instagram' 			=> !empty( $req['_instagram'] ) ? $req['_instagram'] : '',
					'_whatsapp' 			=> !empty( $req['_whatsapp'] ) ? $req['_whatsapp'] : '',
					'_skype' 				=> !empty( $req['_skype'] ) ? $req['_skype'] : '',
					'_year' 				=> !empty( $req['_year'] ) ? $req['_year'] : '',
					'_class' 				=> !empty( $req['_class'] ) ? $req['_class'] : '',
					'_slides' 				=> !empty( $req['_slides'] ) ? $req['_slides'] : '',
					'_manufacturer' 		=> !empty( $req['_manufacturer'] ) ? $req['_manufacturer'] : '',
					'_sleeps' 				=> !empty( $req['_sleeps'] ) ? $req['_sleeps'] : '',
					'_length' 				=> !empty( $req['_length'] ) ? $req['_length'] : '',
					'_booking_status' 		=> !empty( $req['_booking_status'] ) ? $req['_booking_status'] : 'on',
					'_model' 				=> !empty( $req['_model'] ) ? $req['_model'] : '',
					'_number_of_bunk_beds' 	=> !empty( $req['_number_of_bunk_beds'] ) ? $req['_number_of_bunk_beds'] : '',
					'_vehicle_value' 		=> !empty( $req['_vehicle_value'] ) ? $req['_vehicle_value'] : '',
					'_vehicle_vin' 			=> !empty( $req['_vehicle_vin'] ) ? $req['_vehicle_vin'] : '',
					'_vehicle_trim' 		=> !empty( $req['_vehicle_trim'] ) ? $req['_vehicle_trim'] : '',
					'_height' 				=> !empty( $req['_height'] ) ? $req['_height'] : '',
					'_menu_status' 			=> !empty( $req['_menu_status'] ) ? $req['_menu_status'] : 'on',
					'_store_products' 		=> !empty( $req['_store_products'] ) ? $req['_store_products'] : [],
					'_normal_price' 		=> !empty( $req['_normal_price'] ) ? $req['_normal_price'] : '',
					'_weekday_price' 		=> !empty( $req['_weekday_price'] ) ? $req['_weekday_price'] : '',
					'_reservation_price' 	=> !empty( $req['_reservation_price'] ) ? $req['_reservation_price'] : 0,
					'_expired_after' 		=> !empty( $req['_expired_after'] ) ? $req['_expired_after'] :'',
					'_count_per_guest' 		=> !empty( $req['_count_per_guest'] ) ? $req['_count_per_guest'] :'on',
					'keywords' 				=> !empty( $req['keywords'] ) ? $req['keywords'] :'',
					'_availability' 		=> [
						'dates'				=> !empty( $req['unavailable_dates'] ) ? implode('|', $req['unavailable_dates']) :'',
						'price'				=> !empty( $req['available_price'] ) ? json_encode( $req['available_price']) :'',
					],
			],
		];
		$post_id = wp_insert_post( $post );
		if( !empty( $post_id ) ){
			if( !empty( $req['thumbnail'] ) ){
				set_post_thumbnail( $post_id, $req['thumbnail']);
				update_post_meta( $post_id, '_featured', $req['thumbnail']);
			}
			if( !empty( $req['gallery'] ) ){
				update_post_meta( $post_id, '_gallery', $req['gallery']);
			}
			if( !empty( $_FILES['thumbnail'] ) ){
				$attachment = uploadFile( $_FILES['thumbnail'], 'thumbnail', $post_id );
				
				if( $attachment['success'] ){
					update_post_meta( $post_id, '_featured', $attachment['data']['id']);
					set_post_thumbnail( $post_id, $attachment['data']['id'] );
				}
			}
			$post_data = get_post( $post_id );
			$this->save_as_product( $post_id, $post_data, true );
			$this->save_availibilty_calendar( $post_id, $post_data, true );
			$this->save_event_timestamp( $post_id, $post_data, true );
			return $post_data;
		}else{
			$error = new \WP_Error();
			$error->add(400, __("couldn't update listing.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		
	}

	public function updateList( \WP_REST_Request $req ){
		$error = new \WP_Error();
		$post_id = $req['id'];
		$rental_categories = !empty( $req['rental_categories'] ) ? $req['rental_categories'] : [];
		$listing_categories = !empty( $req['listing_categories'] ) ? $req['listing_categories'] : [];
		$listing_feature 	= !empty( $req['listing_feature'] ) ? $req['listing_feature'] : [];
		$post = [
			'ID' 						=> $post_id,
			'post_title'				=> wp_strip_all_tags( $req['title'] ),
			'post_type' 				=> 'listing',
			'post_content' 				=> sanitize_text_field( $req['description'] ),
			'post_status'				=> 'publish',
			'tax_input' 				=>  array(
					"rental_category" 	=> $rental_categories,
					'listing_category' 	=> $listing_categories,
					'listing_feature' 	=> $listing_feature
				),
			'meta_input'				=> [
				//'_listing_expires' => !empty( $req['_listing_expires'] ) ? $req['_listing_expires'] : time(),
				'_listing_type' => !empty( $req['_listing_type'] ) ? $req['_listing_type'] : 'rental',
				'_address' => !empty( $req['_address'] ) ? $req['_address'] : '',
				'_place_id' => !empty( $req['_place_id'] ) ? $req['_place_id'] : '',
				'_booking' => !empty( $req['_booking'] ) ? $req['_booking'] : '',
				'_availability' => [
					'dates'=> !empty( $req['unavailable_dates'] ) ? implode('|', $req['unavailable_dates']) :'',
					'price'=> !empty( $req['available_price'] ) ? json_encode( $req['available_price']) :'',
				],
			],
		];
		try{
			wp_update_post( $post );
			if( !empty( $post_id ) ){
				if( !empty( $req['_friendly_address'] ) ){
					update_post_meta( $post_id, '_friendly_address', $req['_friendly_address']);
				}
				if( !empty( $req['_geolocation_lat'] ) ){
					update_post_meta( $post_id, '_geolocation_lat', $req['_geolocation_lat']);
				}
				if( !empty( $req['_geolocation_long'] ) ){
					update_post_meta( $post_id, '_geolocation_long', $req['_geolocation_long']);
				}
				if( !empty( $req['_year'] ) ){
					update_post_meta( $post_id, '_year', $req['_year']);
				}
				if( !empty( $req['_phone'] ) ){
					update_post_meta( $post_id, '_phone', $req['_phone']);
				}
				if( !empty( $req['_email'] ) ){
					update_post_meta( $post_id, '_email', $req['_email']);
				}
				if( !empty( $req['_price_night'] ) ){
					update_post_meta( $post_id, '_price_night', $req['_price_night']);
				}
				if( !empty( $req['_check_in'] ) ){
					update_post_meta( $post_id, '_check_in', $req['_check_in']);
				}
				if( !empty( $req['_skype'] ) ){
					update_post_meta( $post_id, '_skype', $req['_skype']);
				}
				if( !empty( $req['_facebook'] ) ){
					update_post_meta( $post_id, '_facebook', $req['_facebook']);
				}
				if( !empty( $req['_twitter'] ) ){
					update_post_meta( $post_id, '_twitter', $req['_twitter']);
				}
				if( !empty( $req['_youtube'] ) ){
					update_post_meta( $post_id, '_youtube', $req['_youtube']);
				}
				if( !empty( $req['_instagram'] ) ){
					update_post_meta( $post_id, '_instagram', $req['_instagram']);
				}
				if( !empty( $req['_whatsapp'] ) ){
					update_post_meta( $post_id, '_whatsapp', $req['_whatsapp']);
				}
				if( !empty( $req['_skype'] ) ){
					update_post_meta( $post_id, '_skype', $req['_skype']);
				}
				if( !empty( $req['_class'] ) ){
					update_post_meta( $post_id, '_class', $req['_class']);
				}
				if( !empty( $req['_slides'] ) ){
					update_post_meta( $post_id, '_slides', $req['_slides']);
				}
				if( !empty( $req['_manufacturer'] ) ){
					update_post_meta( $post_id, '_manufacturer', $req['_manufacturer']);
				}
				if( !empty( $req['_sleeps'] ) ){
					update_post_meta( $post_id, '_sleeps', $req['_sleeps']);
				}
				if( !empty( $req['_length'] ) ){
					update_post_meta( $post_id, '_length', $req['_length']);
				}
				if( !empty( $req['_model'] ) ){
					update_post_meta( $post_id, '_model', $req['_model']);
				}
				if( !empty( $req['_number_of_bunk_beds'] ) ){
					update_post_meta( $post_id, '_number_of_bunk_beds', $req['_number_of_bunk_beds']);
				}
				if( !empty( $req['_vehicle_trim'] ) ){
					update_post_meta( $post_id, '_vehicle_trim', $req['_vehicle_trim']);
				}
				if( !empty( $req['_vehicle_vin'] ) ){
					update_post_meta( $post_id, '_vehicle_vin', $req['_vehicle_vin']);
				}
				if( !empty( $req['_height'] ) ){
					update_post_meta( $post_id, '_height', $req['_height']);
				}
				if( !empty( $req['_menu_status'] ) ){
					update_post_meta( $post_id, '_menu_status', $req['_menu_status']);
				}
				if( !empty( $req['_booking_status'] ) ){
					if($req['_booking_status'] == 'on'){
						update_post_meta( $post_id, '_booking_status', 'on');
					}else{
						update_post_meta( $post_id, '_booking_status', "0");
					}
				}
				if( !empty( $req['_store_products'] ) ){
					update_post_meta( $post_id, '_store_products', $req['_store_products']);
				}
				if( !empty( $req['_normal_price'] ) ){
					update_post_meta( $post_id, '_normal_price', $req['_normal_price']);
				}
				if( !empty( $req['_weekday_price'] ) ){
					update_post_meta( $post_id, '_weekday_price', $req['_weekday_price']);
				}
				if( !empty( $req['_count_per_guest'] ) ){
					update_post_meta( $post_id, '_count_per_guest', $req['_count_per_guest']);
				}
				if( !empty( $req['keywords'] ) ){
					update_post_meta( $post_id, 'keywords', $req['keywords']);
				}
				if( !empty( $req['_expired_after'] ) ){
					update_post_meta( $post_id, '_expired_after', $req['_expired_after']);
				}
				if( !empty( $req['_reservation_price'] ) ){
					update_post_meta( $post_id, '_reservation_price', $req['_reservation_price']);
				}
				if( !empty( $req['thumbnail'] ) ){
					set_post_thumbnail( $post_id, $req['thumbnail']);
					update_post_meta( $post_id, '_featured', $req['thumbnail']);
				}
				if( !empty( $req['gallery'] ) ){
					update_post_meta( $post_id, '_gallery', $req['gallery']);
				}
				if( !empty( $_FILES['thumbnail'] ) ){
					$attachment = uploadFile( $_FILES['thumbnail'], 'thumbnail', $post_id );
					if( $attachment['success'] ){
						update_post_meta( $post_id, '_featured', $attachment['data']['id']);
						set_post_thumbnail( $post_id, $attachment['data']['id']);
					}
				}
				$post_data = get_post( $post_id );
				$this->save_as_product( $post_id, $post_data, true );
				$this->save_availibilty_calendar( $post_id, $post_data, true );
				$this->save_event_timestamp( $post_id, $post_data, true );
				return $post_data;
			}else{
				$error->add(400, __("couldn't update listing.", 'wp-rest-user'), array('status' => 400));
				return $error;
			}
		}catch( \Exception $e ){
			$error->add(400, __($e->getMessage(), 'wp-rest-user'), array('status' => 400));
		}
		
	}

	private function save_as_product( $post_ID, $post, $update ){
		if(!is_woocommerce_activated()){
			return;
		}
		if ( $post->post_type == 'listing' ) {
			$product_id = get_post_meta( $post_ID, 'product_id', true );
			$product = array (
				'post_author' 	=> get_current_user_id(),
				'post_content' 	=> $post->post_content,
				'post_status' 	=> 'publish',
				'post_title' 	=> $post->post_title,
				'post_parent' 	=> '',
				'post_type' 	=> 'product',
			);
			if ( !$product_id ||  get_post_type( $product_id ) != 'product' ) {
				$product_id = wp_insert_post( $product );
				wp_set_object_terms( $product_id, 'listing_booking', 'product_type' );
			} else {
				$product['ID'] = $product_id;
				wp_update_post ( $product );
			}
			$term = get_term_by( 'name', apply_filters( 'listeo_default_product_category', 'Listeo booking'), 'product_cat', ARRAY_A );
			if ( ! $term ) $term = wp_insert_term(
				apply_filters( 'listeo_default_product_category', 'Listeo booking' ),
				'product_cat',
				array(
				  'description'=> __( 'Listings category', 'listeo-core' ),
				  'slug' => str_replace( ' ', '-', apply_filters( 'listeo_default_product_category', 'Listeo booking' ) )
				)
			);
			wp_set_object_terms( $product_id, $term['term_id'], 'product_cat' );
			update_post_meta( $post_ID, 'product_id', $product_id );
		}
	}

	private function save_availibilty_calendar( $post_ID, $post, $update ) {
		$bookings = new \Listeo_Core_Bookings_Calendar;
		$avaliabity = get_post_meta($post_ID, '_availability', true);
		if($avaliabity) {
			$dates = array_filter( explode( "|", $avaliabity['dates'] ) );
			if ( ! empty( $dates ) ) $bookings :: update_reservations( $post_ID, $dates );
			$special_prices = json_decode( $avaliabity['price'], true );
			if ( ! empty( $special_prices ) ) $bookings :: update_special_prices( $post_ID, $special_prices );
		}
	}

	private function save_event_timestamp( $post_ID, $post, $update ) {
		$post_type = get_post_meta( $post_ID, '_listing_type', true );
		if($post_type == 'event'){
			$event_date = get_post_meta( $post_ID, '_event_date', true );
      if($event_date){
        $meta_value_date = explode( ' ', $event_date,2 ); 
        $meta_value_stamp_obj = \DateTime::createFromFormat( listeo_date_time_wp_format_php(), $meta_value_date[0] );
        if( $meta_value_stamp_obj ){
        	$meta_value_stamp = $meta_value_stamp_obj->getTimestamp();
        	update_post_meta( $post_ID, '_event_date_timestamp', $meta_value_stamp );    
        }
      }
      $event_date_end = get_post_meta($post_ID, '_event_date_end', true);
      if( $event_date_end ){
        $meta_value_date_end = explode(' ', $event_date_end, 2); 
        $meta_value_stamp_end_obj = \DateTime::createFromFormat( listeo_date_time_wp_format_php(), $meta_value_date_end[0] );
        if( $meta_value_stamp_end_obj ){
        	$meta_value_stamp_end = $meta_value_stamp_end_obj->getTimestamp();
        	update_post_meta( $post_ID, '_event_date_end_timestamp', $meta_value_stamp_end );    
        }
      } 
		}
	}

	public function trashList( \WP_REST_Request $request ){
	}

	public function getRentalCategory( \WP_REST_Request $request ){
		$terms = get_terms( array(
		    'taxonomy'   => 'rental_category',
		    'hide_empty' => false,
		) );
		return $terms;
	}

	public function getFeatersFromLising( \WP_REST_Request $req ){
		$categories  	=  isset(	$req['cat_ids']	) ? $req['cat_ids'] : [32];
		$panel  		=  $req['panel'];
		$selected  		=  isset( $req['selected'] ) ? $req['selected'] : false;
		$listing_id  	=  !empty( $req['listing_id'] ) ? $req['listing_id'] : 0;
		$success 		= true;
		if(!$selected){
			if($listing_id){
				$selected_check = wp_get_object_terms( $listing_id, 'listing_feature', array( 'fields' => 'ids' ) ) ;
				if ( ! empty( $selected_check ) ) {
					if ( ! is_wp_error( $selected_check ) ) {
						$selected = $selected_check;
					}
				}
			}
		};
		
		if($categories){
			$features = array();
			foreach ($categories as $category) {
				if(is_numeric($category)) {
					$cat_object = get_term_by('id', $category, 'listing_category');	
				} else {
					$cat_object = get_term_by('slug', $category, 'listing_category');	
				}

					//pr( $cat_object );
				if($cat_object){
					$features_temp = get_term_meta( $cat_object->term_id, 'listeo_taxonomy_multicheck', true );
					if($features_temp){
						foreach ($features_temp as $key => $value) {
							$feature_obj = get_term_by('slug', $value, 'listing_feature');
							if( !$feature_obj ){
								continue;
							}
							array_push($features, $feature_obj);
						}
					}
				}
			}
			//$features = array_unique($features);
		}
		return $features;
	}

	public function createBookmark( \WP_REST_Request $req ){
		$post_id = $req['listing_id'];
    	if(is_user_logged_in()){
	   	$userID = $this->get_user_id();
	   	if($this->check_if_added($post_id)) {
			$result['type'] = 'error';
			$result['message'] = __( 'You\'ve already added that post' , 'listeo_core' );
	   	} else {
	   		$bookmarked_posts =  (array) $this->get_bookmarked_posts();
	   		$bookmarked_posts[] = $post_id;
				$action = update_user_meta( $userID, 'listeo_core-bookmarked-posts', $bookmarked_posts );
				if($action === false) {
				$result['type'] = 'error';
				$result['message'] = __( 'Oops, something went wrong, please try again' , 'listeo_core' );
				} else {
					$bookmarks_counter = get_post_meta( $post_id, 'bookmarks_counter', true );
		   		$bookmarks_counter++;			   
		   		update_post_meta( $post_id, 'bookmarks_counter', $bookmarks_counter );
		   		$author_id 		= get_post_field( 'post_author', $post_id );
					$total_bookmarks = get_user_meta($author_id,'listeo_total_listing_bookmarks',true);
					$total_bookmarks = (int) $total_bookmarks + 1;
					update_user_meta($author_id, 'listeo_total_listing_bookmarks', $total_bookmarks);
		  		$bookmarked_posts[] = $post_id;
		  		do_action("listeo_listing_bookmarked", $post_id, $userID );
					$result['type'] = 'success';
					$result['message'] = __( 'Listing was bookmarked' , 'listeo_core' );
				}
			}
		}
		wp_send_json($result);
		die();
	}

	public function removeBookmark( \WP_REST_Request $req ){
		$post_id = $req['listing_id'];
	   if(is_user_logged_in()){
		   	$userID = $this->get_user_id();		
	   		$bookmarked_posts = $this->get_bookmarked_posts();
	   		$bookmarked_posts = array_diff($bookmarked_posts, array($post_id));
	      $bookmarked_posts = array_values($bookmarked_posts);

				$action = update_user_meta( $userID, 'listeo_core-bookmarked-posts', $bookmarked_posts, false );
			if($action === false) {
				$result['type'] = 'error';
				$result['message'] = __('Oops, something went wrong, please try again','listeo_core');
			} else {
				$bookmarks_counter = get_post_meta( $post_id, 'bookmarks_counter', true );
				$bookmarks_counter--;
				update_post_meta( $post_id, 'bookmarks_counter', $bookmarks_counter );
				$author_id 		= get_post_field( 'post_author', $post_id );
				$total_bookmarks = get_user_meta($author_id,'listeo_total_listing_bookmarks',true);
				$total_bookmarks = (int) $total_bookmarks - 1;
				update_user_meta($author_id, 'listeo_total_listing_bookmarks', $total_bookmarks);
				do_action("listeo_listing_unbookmarked", $post_id, $userID );
				$result['type'] = 'success';
				$result['message'] = esc_html__('Listing was removed from the list','listeo_core');
			}
		}
		wp_send_json($result);
		die();
	}

	function get_user_id() {
	    global $current_user;
	    wp_get_current_user();
	    return $current_user->ID;
	}

	function get_bookmarked_posts() {
		return get_user_meta($this->get_user_id(), 'listeo_core-bookmarked-posts', true);
	}

	function check_if_added($id) {
		$bookmarked_post_ids = $this->get_bookmarked_posts();
		if ($bookmarked_post_ids) {
			foreach ($bookmarked_post_ids as $bookmarked_id) {
				if ($bookmarked_id == $id) { 
					return true; 
				}
			}
	    } 
	    return false;
	}

	public function performAction( \WP_REST_Request $req ){
		$error 		= new \WP_Error();
		$action 	= sanitize_title( $req['action'] );
		$listing_id = absint( $req['listing_id'] );
		$out_data 	= [];
		try {
			$current_user = wp_get_current_user();
    		$listing      = get_post( $listing_id );
    		$listing_data = get_post( $listing );
			if ( ! $listing_data || 'listing' !== $listing_data->post_type ) {
				$title = false;
			} else {
				$title = esc_html( get_the_title( $listing_data ) );	
			}
			switch ( $action ) {
				case 'delete' :
					if($current_user->ID == $listing->post_author && 'listing' == $listing_data->post_type ){
						wp_trash_post( $listing_id );
						$out_data['status'] =  'success';
						$out_data['message'] =  sprintf( __( '%s has been deleted', 'listeo_core' ), $title );
					} else {
						$error->add(400, __('You are trying to remove not your listing', 'wp-rest-user'), array('status' => 400));
    				return $error;
					}
				break;
				case 'unpublish':
					if($current_user->ID == $listing->post_author && 'listing' == $listing_data->post_type ){
						wp_update_post(array(
			        'ID'    =>  $listing_id,
			        'post_status'   =>  'draft'
		        ));
						$out_data['status'] =  'success';
						$out_data['message'] =  sprintf( __( '%s has been unpublished', 'listeo_core' ), $title );
					} else {
						$error->add(400, __('You are trying to change not your listing', 'wp-rest-user'), array('status' => 400));
    				return $error;
					}
				break;
				case 'renew':
					if(!get_option('listeo_new_listing_requires_purchase')){
						if($current_user->ID == $listing->post_author && 'listing' == $listing_data->post_type ){
							wp_update_post(array(
				        'ID'    =>  $listing_id,
				        'post_status'   =>  'publish'
			        ));
							delete_post_meta($listing_id, "_listing_expires");   
				      $post_types_expiry = new \Listeo_Core_Post_Types;
							$post_types_expiry->set_expiry(get_post($listing_id));
							$out_data['status'] =  'success';
							$out_data['message'] =  sprintf( __( '%s has been renewed', 'listeo_core' ), $title );
						} else {
							$error->add(400, __('You are trying to renew not your listing', 'wp-rest-user'), array('status' => 400));
	    				return $error;
						}
					} else {
						$error->add(400, __('This listing is not purchasable', 'wp-rest-user'), array('status' => 400));
    				return $error;
					}
				break;
				default :
					do_action( 'listeo_core_dashboard_do_action_' . $action );
				break;
			}
			do_action( 'listeo_core_my_listing_do_action', $action, $listing_id );
		} catch ( Exception $e ) {
			$error->add(400, __($e->getMessage(), 'wp-rest-user'), array('status' => 400));
    	return $error;
		}
	}

	public function getMyBookmarks( \WP_REST_Request $req ){
	  $bookmarks = $this->get_bookmarked_posts();
	  $posts=[];
	  if( !empty( $bookmarks ) ){
		  foreach($bookmarks as $post_id){
				$post 					= get_post($post_id);
				if( !empty( $post ) ){
					$categories_list	=$this->getListingTypeTags($post_id);
					$friendly_address 	= get_post_meta($post->ID, '_friendly_address', true);
					$address 			= get_post_meta($post->ID, '_address', true);
					$address 			=  (!empty($friendly_address)) ? $friendly_address : $address;
					$currency_abbr 		= get_option('listeo_currency');
					$currency_symbol 	= \Listeo_Core_Listing::get_currency_symbol($currency_abbr);
					$posts[]=[
						'id'					=> $post_id,
						'name'					=> $post->post_name,
						'title'					=> $post->post_title,
						'content'				=> $post->post_content,
						'image_thumbnail'		=>get_the_post_thumbnail_url($post_id, 'thumbnail'),
						'image_full'			=>get_the_post_thumbnail_url($post_id, 'full'),
						'categories'			=>$categories,
						'verified'				=>get_post_meta($post_id,'_verified',true ),
						'address'				=> $address,
						'currency_Symbol'		=> $currency_symbol,
						'price_range'			=> get_the_listing_price_range($post),
						'rating'				=>get_post_meta($post_id, 'listeo-avg-rating', true),
						'bookmark'				=> listeo_core_check_if_bookmarked($post_id),
						'listing_type'			=>get_post_meta($post_id, '_listing_type', true),
					];
				}
			}
		}
	 	$result = array(
			'posts'				=>$posts,
			'found_listings'    => count($posts)
		);
		return $result;
	}

	public function createComment( \WP_REST_Request $req ){

		if (empty($req['comment_post_ID']) || $req['comment_post_ID'] === '') {
			return new \WP_Error('no_code', __('Listing Id is required.', 'bdvs-password-reset'), array('status' => 400));
		}
		if (empty($req['comment']) || $req['comment'] === '') {
			return new \WP_Error('no_code', __('Please add some comment.', 'bdvs-password-reset'), array('status' => 400));
		}
		$current_user = wp_get_current_user();
		if (empty($current_user) ) {
			return new \WP_Error('no_code', __('No User associated.', 'bdvs-password-reset'), array('status' => 400));
		}
		$data = [
            'comment_post_ID'      => $req['comment_post_ID'],
            'comment_content'      => $req['comment'],
            'comment_parent'       => $req['comment_parent'],
            'user_id'              => $current_user->ID,
            'comment_author'       => $current_user->user_login,
            'comment_author_email' => $current_user->user_email,
            'comment_author_url'   => $current_user->user_url,
        ];
		$comment_id = wp_insert_comment( $data );
		if( $comment_id ){
			$list_comment_obj = new Listeo_Core_Reviews();
			$list_comment_obj->save_comment_meta_data( $comment_id );
			$list_comment_obj->add_comment_rating( $comment_id, 1);
			$list_comment_obj->save_comment_attachment( $comment_id );
		}

	}

	public function getAllReviews( \WP_REST_Request $req ){
		$r_data = [];
		$limit = 10;
		try{
			$current_user = wp_get_current_user();
			$visitor_reviews_page = (isset($req['visitor-reviews-page'])) ? $req['visitor-reviews-page'] : 1;
			add_filter( 'comments_clauses', 'listeo_top_comments_only' );
			$visitor_reviews_offset = ($visitor_reviews_page * $limit) - $limit;
			$total_visitor_reviews = get_comments(
				array(
					'orderby' 		=> 'post_date' ,
	        		'order' 		=> 'DESC',
	       			'status' 		=> 'approve',
	        		'post_author' 	=> $current_user->ID,
					'parent'    	=> 0,
					'post_type' 	=> 'listing',
	        	)
			);
			$visitor_reviews_args = array(
				'post_author' 	=> $current_user->ID,
				'parent'      	=> 0,
				'status' 		=> 'approve',
				'post_type' 	=> 'listing',
				'number' 		=> $limit,
				'offset' 		=> $visitor_reviews_offset,
			);
			$r_data['visitor_reviews_pages'] = ceil(count($total_visitor_reviews)/$limit);
			$r_data['visitor_reviews'] = get_comments( $visitor_reviews_args );
			$your_reviews_page = (isset($req['your-reviews-page'])) ? $req['your-reviews-page'] : 1;
			$your_reviews_offset = ($your_reviews_page * $limit) - $limit;
			$total_your_reviews = get_comments(
				array(
					'orderby' 	=> 'post_date' ,
	        		'order' 	=> 'DESC',
	       			'status' 	=> 'all',
	        		'author__in' => array($current_user->ID),
					'post_type' => 'listing',
					'parent'      => 0,
	        	)
			);
			$your_reviews_args = array(
				'author__in' 	=> array($current_user->ID),
				'post_type' 	=> 'listing',
				'status' 		=> 'all',
				'parent'      	=> 0,
				'number' 		=> $limit,
			 	'offset' 		=> $your_reviews_offset,
			);
			$r_data['your_reviews_pages'] = ceil(count($total_your_reviews)/$limit);
			$r_data['your_reviews'] = get_comments( $your_reviews_args );
		}catch( \Exception $e ){
			return new \WP_Error('no_code', __($e->getMessage(), 'bdvs-password-reset'), array('status' => 400));
		}
		return $r_data;
	}

	public function getRatingCategories(){
		return listeo_get_reviews_criteria();
	}

	public function getListingCategories(){
		$terms = get_terms( array(
		    'taxonomy'   => 'listing_category',
		    'hide_empty' => false,
		) );
		return $terms;
	}

	public function checkIfCanUserReview( \WP_REST_Request $req ){
		if (empty($req['listing_id']) || $req['listing_id'] === '') {
			return new \WP_Error('no_code', __('Listing Id is required.', 'bdvs-password-reset'), array('status' => 400));
		}
		try{
			global $wpdb;
			$table_name = $wpdb->prefix . 'bookings_calendar';
			$has_booked = $wpdb->get_results( $wpdb->prepare( "
		            SELECT * FROM {$table_name}
		            WHERE bookings_author = %d
		            AND listing_id = %d
		            
			", get_current_user_id(),$req['listing_id'] ) );
			//pr( $has_booked );
			return !empty($has_booked);
		}catch( \Exception $e ){
			return new \WP_Error('no_code', __($e->getMessage(), 'bdvs-password-reset'), array('status' => 400));
		}
	}

}