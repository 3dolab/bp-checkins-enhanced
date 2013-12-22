<?php

require_once ( dirname( __FILE__ ) . '/register-post-type-with-dates.php' );

function auto_set_city_taxonomy($place) {
	if ( is_int($place) && get_post_type($place) != 'places' )
		return;
		
	if ( is_int( $place ) ) {
		$post_id = $place;
		//$lat = get_post_meta($post_id,'bpci_places_lat',true);
		//$long = get_post_meta($post_id,'bpci_places_lng',true);
		$address = get_post_meta($post_id,'bpci_places_address',true);
		if(isset( $_REQUEST['bpci_places_address']))
			$address = $_REQUEST['bpci_places_address'];
		if(isset( $_REQUEST['bpci-address']))
			$address = $_REQUEST['bpci-address'];
	} elseif ( is_object( $place ) ) {
		$address = $place->address;
	} 
		$post_id = $place->id;
		$converted_address = bpce_GmwConvertToCoords($address);

		//print_r($address);
		//print_r($converted_address);

		if(!empty($converted_address)):
			$place_cities = wp_get_object_terms($post_id, 'city');
			$city = $converted_address['province'];
			if(!empty($place_cities)&&!is_wp_error( $place_cities )):
				foreach($place_cities as $place_city):
					if( $place_city->slug == $city || $place_city->name == $city )
						return;
				endforeach;
			endif;
			$city_term = get_term_by('name', $city, 'city');
			if(empty($city_term))
				wp_set_object_terms( $post_id, $city, 'city' );
			if(!empty($city_term))
				wp_set_object_terms( $post_id, $city_term->slug, 'city' );
		endif;
}

function bp_checkins_enhanced_register_post_type() {	

	if ( function_exists( 'bp_checkins_init' ) ){
	
	global $bp, $wpdb;
		
		if( empty( $bp->pages->{BP_CHECKINS_SLUG}->slug ) ) {
			
			$directory_ids = bp_core_get_directory_page_ids();
			$page_id = $directory_ids[BP_CHECKINS_SLUG];
			
			$page_slug = $wpdb->get_var( $wpdb->prepare( "SELECT post_name FROM {$wpdb->base_prefix}posts WHERE ID = %d AND post_status = 'publish' ", $page_id ) );
			
		} else {
			$page_slug = $bp->pages->{BP_CHECKINS_SLUG}->slug;
		}
		
		$slug = isset( $page_slug ) ? $page_slug : BP_CHECKINS_SLUG;
	
		$labels = array(
			'name'	             => __( 'Places', 'bp-checkins' ),
			'singular'           => __( 'Place', 'bp-checkins' ),
			//'menu_name'          => __( 'Community Places', 'bp-checkins' ),
			'menu_name'          => __( 'Places', 'bp-checkins' ),
			'all_items'          => __( 'All Places', 'bp-checkins' ),
			'singular_name'      => __( 'Place', 'bp-checkins' ),
			'add_new'            => __( 'Add New Place', 'bp-checkins' ),
			'add_new_item'       => __( 'Add New Place', 'bp-checkins' ),
			'edit_item'          => __( 'Edit Place', 'bp-checkins' ),
			'new_item'           => __( 'New Place', 'bp-checkins' ),
			'view_item'          => __( 'View Place', 'bp-checkins' ),
			'search_items'       => __( 'Search Places', 'bp-checkins' ),
			'not_found'          => __( 'No Places Found', 'bp-checkins' ),
			'not_found_in_trash' => __( 'No Places Found in Trash', 'bp-checkins' )
		);
		
		$args = array(
			'label'	     => __( 'Place', 'bp-checkins' ),
			'labels'     => $labels,
			'public'     => true,
			'rewrite'=>array(
				//'slug'=> $slug . '/'.__( 'places', 'bp-checkins' ),
				'slug'=> __( 'places', 'bp-checkins-enhanced' ),
				'with_front'=>false),
			'show_ui'    => true,
			'supports'   => array( 'title', 'editor', 'author', 'excerpt', 'comments', 'custom-fields', 'thumbnail' ),
			'has_archive' => true,
			'menu_icon'  => BP_CHECKINS_PLUGIN_URL_IMG . '/community-places-post-type-icon.png',
			'taxonomies' => array( 'places_category', 'city')
		);
		if(class_exists('Custom_Post_Type_With_Dates'))
			register_post_type_with_dates( 'places', $args );
		else
			register_post_type( 'places', $args );
		
		$event_labels = array(
			'name'	             => __( 'Events', 'bp-checkins-enhanced' ),
			'singular'           => __( 'Event', 'bp-checkins-enhanced' ),
			'menu_name'          => __( 'Events', 'bp-checkins-enhanced' ),
			'all_items'          => __( 'All Events', 'bp-checkins-enhanced' ),
			'singular_name'      => __( 'Event', 'bp-checkins-enhanced' ),
			'add_new'            => __( 'Add New Event', 'bp-checkins-enhanced' ),
			'add_new_item'       => __( 'Add New Event', 'bp-checkins-enhanced' ),
			'edit_item'          => __( 'Edit Event', 'bp-checkins-enhanced' ),
			'new_item'           => __( 'New Event', 'bp-checkins-enhanced' ),
			'view_item'          => __( 'View Event', 'bp-checkins-enhanced' ),
			'search_items'       => __( 'Search Events', 'bp-checkins-enhanced' ),
			'not_found'          => __( 'No Events Found', 'bp-checkins-enhanced' ),
			'not_found_in_trash' => __( 'No Events Found in Trash', 'bp-checkins-enhanced' )
		);		
		$event_args = array(
			'label'	     => __( 'Event', 'bp-checkins-enhanced' ),
			'labels'     => $event_labels,
			'public'     => true,
			'rewrite'=>array(
				'slug'=> __( 'events', 'bp-checkins-enhanced' ),
				'with_front'=>false),
			'show_ui'    => true,
			'supports'   => array( 'title', 'editor', 'author', 'excerpt', 'comments', 'custom-fields', 'thumbnail' ),
			'has_archive' => true,
			'menu_icon'  => BP_CHECKINS_PLUGIN_URL_IMG . '/community-places-post-type-icon.png',
			'taxonomies' => array( 'places_category', 'city')
		);

		if(class_exists('Custom_Post_Type_With_Dates'))
			register_post_type_with_dates( 'events', $event_args );
		else
			register_post_type( 'events', $event_args );
				
	}
	
}
function bp_checkins_enhanced_register_taxonomy() {	

	if ( function_exists( 'bp_checkins_init' ) ){
	
	global $bp, $wpdb;
		
		register_taxonomy( 'city', 
			array( 'places', 'events' ),
			array( 	'hierarchical' 	=> true, 
					'label' 		=> __('City', 'bp-checkins-enhanced' ),
					'public' 		=> true, 
					'show_ui' 		=> true,
					'query_var' 	=> 'city',
					'rewrite'		=> array(	'slug' => __('city', 'bp-checkins-enhanced'), 'with_front' => false ) ) 
			);
			
		register_taxonomy( 'places_category', 
			array( 'places', 'events' ),
			array( 	'hierarchical' 	=> true, 
					'label' 		=> __('Place Category', 'bp-checkins-enhanced' ),
					'public' 		=> true, 
					'show_ui' 		=> true,
					'query_var' 	=> 'places-category',
					'rewrite'		=> array(	'slug' => __('places_category', 'bp-checkins-enhanced'), 'with_front' => false ) ) 
			);
		
	}
	
}
add_action( 'init', 'bp_checkins_enhanced_register_post_type', 99 );
add_action( 'init', 'bp_checkins_enhanced_register_taxonomy', 99 );

function post_query_places_to_events( $wp_query ) {
	//global $gloss_category;  
	
	// Figure out if we need to exclude glossary - exclude from
	// archives (except category archives), feeds, and home page
	/*
	if( is_home() || is_feed() || ( is_archive() && !is_category() )) {
		set_query_var('cat', '-' . $gloss_category);
		//which is merely the more elegant way to write:
		//$wp_query->set('cat', '-' . $gloss_category);
	}
	*/
	$post_type = get_query_var('post_type');
	if( $wp_query->is_main_query() and $post_type == 'places' ) {
		$meta_var = array( array( 'key' => 'bpci_places_is_live', 'value' => 'live', 'type' => 'string', 'compare' => '!=' ) );
		//set_query_var('meta_key', 'bpci_places_is_live');
		//set_query_var('meta_value', 'live');
		//set_query_var('meta_compare', '!=');
		set_query_var('meta_query', $meta_var);
		//$wp_query->set('meta_query', $meta_var);
		/*
		echo '<pre>';
		print_r($wp_query);
		echo '</pre>';
		*/
		return;
	} elseif( $wp_query->is_main_query() and $post_type == 'events' ) {
		set_query_var('post_type', 'places');
		$meta_var = array( array( 'key' => 'bpci_places_is_live', 'value' => 'live', 'type' => 'string', 'compare' => '=' ) );
		//set_query_var('meta_key', 'bpci_places_is_live');
		//set_query_var('meta_key', 'bpci_places_is_live');
		//set_query_var('meta_value', 'live');
		//set_query_var('meta_compare', '=');
		set_query_var('meta_query', $meta_var);
		//$wp_query->set('meta_query', $meta_var);
		/*
		echo '<pre>';
		print_r($wp_query);
		echo '</pre>';
		*/
		add_filter('posts_join_paged','edit_live_places_join_paged');
		add_filter('posts_orderby', 'edit_live_places_orderby');
		add_filter('posts_where', 'edit_live_places_where');		
		add_filter('wp_title', 'switch_page_title_places', 10, 2);
		return;
	}
}
add_action('pre_get_posts', 'post_query_places_to_events' );

function edit_live_places_join_paged($join_paged_statement) {
	global $wpdb;
	$metatable = $wpdb->postmeta;
	$postable = $wpdb->posts;
	if(get_query_var('year')){
		$join_paged_statement .= " INNER JOIN ".$metatable." meta1 ON ( meta1.post_id = ".$postable.".ID AND meta1.meta_key = 'bpci_places_live_start' )";
		$join_paged_statement .= " INNER JOIN ".$metatable." meta2 ON ( meta2.post_id = ".$postable.".ID AND meta2.meta_key = 'bpci_places_live_end' )";
	} else
		$join_paged_statement .= " LEFT JOIN ".$metatable." meta1 ON ( meta1.post_id = ".$postable.".ID AND meta1.meta_key = 'bpci_places_live_start' )";
	return $join_paged_statement;	
}

function edit_live_places_orderby($orderby_statement) {
	global $wpdb;
	$metatable = $wpdb->postmeta;
	if(get_query_var('year'))
		$orderby_statement = "TIMESTAMP(meta2.meta_value) DESC, TIMESTAMP(meta1.meta_value) DESC";
	else
		$orderby_statement = "TIMESTAMP(meta1.meta_value) DESC";
	return $orderby_statement;
}

function edit_live_places_where( $where ) {
	global $wpdb;
	if(get_query_var('year')){
		$year = get_query_var('year');
		if(get_query_var('monthnum')) {
			$month = $startmonth = $endmonth = zeroise(intval(get_query_var('monthnum')), 2);
		} else {
			$startmonth = zeroise(01, 2);
			$endmonth = zeroise(12, 2);
		} if(get_query_var('day')) {
			$day = $startday = $endday = zeroise(intval(get_query_var('day')), 2);
		} else {
			$startday = zeroise(01, 2);
			$unixmonth = mktime(0, 0 , 0, $endmonth, 1, $year);
			$endday = zeroise(date('t', $unixmonth), 2);
			//$endday = $last_day.' 23:59:59';
		}
		if(get_query_var('day'))
			$where = str_replace(' AND ( ( YEAR( post_date ) = '.$year.' AND MONTH( post_date ) = '.$month.' AND DAYOFMONTH( post_date ) = '.$day.' ) )', '', $where);
		elseif(get_query_var('monthnum'))
			$where = str_replace(' AND ( ( YEAR( post_date ) = '.$year.' AND MONTH( post_date ) = '.$month.' ) )', '', $where);
		else
			$where = str_replace(' AND ( ( YEAR( post_date ) = '.$year.' )', '', $where);
		$where .= " AND CAST(meta1.meta_value AS DATE) <= '".$year."-".$endmonth."-".$endday."'";
		$where .= "	AND CAST(meta2.meta_value AS DATE) >= '".$year."-".$startmonth."-".$startday."'";
	}
	return $where;
}

function switch_permalink_places_post_type( $url, $post ) {
    if ( 'places' == get_post_type( $post ) && get_post_meta( $post->ID, 'bpci_places_is_live',true)=='live' ) {
		$siteurl = get_site_url();
		$place_obj = get_post_type_object( 'places' );
		$place_slug = $place_obj->rewrite['slug'];
		$event_obj = get_post_type_object( 'events' );
		$event_slug = $event_obj->rewrite['slug'];
		$url = str_replace($siteurl.'/'.$place_slug.'/', $siteurl.'/'.$event_slug.'/', $url);
		//$url = str_replace($siteurl.'/'.__( 'places', 'bp-checkins-enhanced' ).'/', $siteurl.'/'.__( 'events', 'bp-checkins-enhanced' ).'/', $url);
        return $url;
    }
    return $url;
}
add_filter('post_type_link', 'switch_permalink_places_post_type', 10, 2);
		
function switch_page_title_places( $title, $sep ) {
	global $post;
    if ( 'places' == get_post_type( $post ) && get_post_meta( $post->ID, 'bpci_places_is_live',true)=='live'  ) {
        return str_replace(__( 'Places', 'bp-checkins' ), __( 'Events', 'bp-checkins-enhanced' ), $title);
    }
    return $title;
}

?>