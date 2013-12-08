<?php
function cross_update_checkin_meta($args) {
	if(function_exists('gmw_pt_update_location')) {
		global $wpdb;
		//echo '<pre>';
		foreach($args as $location){
			//print_r($location);			
			if ( isset($location->address) && $location->address != false ) {
				$address = $address_apt = $location->address;
				$returned_address = bpce_GmwConvertToCoords( $address );		
				$street  = $returned_address['street'];
				$apt 	 = $returned_address['apt'];
				$city 	 = $returned_address['city'];
				$state 	 = $returned_address['state_short'];
				$zipcode = $returned_address['zipcode'];
				$country = $returned_address['country_short'];
				//$map_icon = '_default.png';
			}

			$gmwLocation = array(
				'post_id' 	  		=> $location->id,
				'post_type'			=> 'places',
				'post_title'		=> $location->title,
				'post_status'		=> 'publish',
				'address' 	  		=> $address,
				'address_apt' 		=> $address_apt,
				'returned_address'  => $returned_address,
				'street'			=> $street,
				'apt'				=> $apt,
				'city'				=> $city,
				'state_short'		=> $state,
				'state_long'		=> $returned_address['state_long'],
				'zipcode'			=> $zipcode,
				'country_short'		=> $country,
				'country_long'		=> $returned_address['country_long'],
				'formatted_address' => $returned_address['formatted_address'],
				//'phone' 			=> $phone,
				//'fax' 			=> $fax,
				//'email' 			=> $email,
				//'website' 		=> $website,
				'lat' 				=> $returned_address['lat'],
				'long' 				=> $returned_address['long'],
				//'map_icon'  		=> $map_icon
			);
			
			$postID = $location->id;
			update_post_meta($postID, '_wppl_street', $gmwLocation['street']);
			update_post_meta($postID, '_wppl_apt', $gmwLocation['apt']);
			update_post_meta($postID, '_wppl_city', $gmwLocation['city']);
			update_post_meta($postID, '_wppl_state', $gmwLocation['state_short']);
			update_post_meta($postID, '_wppl_state_long', $gmwLocation['state_long']);
			update_post_meta($postID, '_wppl_zipcode', $gmwLocation['zipcode'] );
			update_post_meta($postID, '_wppl_country', $gmwLocation['country_short']);
			update_post_meta($postID, '_wppl_country_long', $gmwLocation['country_long']);
			update_post_meta($postID, '_wppl_address', $gmwLocation['address_apt']);
			update_post_meta($postID, '_wppl_formatted_address', $gmwLocation['formatted_address']);
			update_post_meta($postID, '_wppl_lat', $returned_address['lat']);
			update_post_meta($postID, '_wppl_long', $returned_address['long']);
			//update_post_meta($postID, '_wppl_map_icon' , $gmwLocation['map_icon']);
			
			$wpdb->replace( $wpdb->prefix . 'places_locator',
					array(
							'post_id'			=> $gmwLocation['post_id'],
							'feature'  			=> 0,
							'post_type' 		=> $gmwLocation['post_type'],
							'post_title' 		=> $gmwLocation['post_title'],
							'post_status'		=> $gmwLocation['post_status'],
							'street' 			=> $gmwLocation['street'],
							'apt' 				=> $gmwLocation['apt'],
							'city' 				=> $gmwLocation['city'],
							'state' 			=> $gmwLocation['state_short'],
							'state_long' 		=> $gmwLocation['state_long'],
							'zipcode' 			=> $gmwLocation['zipcode'],
							'country' 			=> $gmwLocation['country_short'],
							'country_long' 		=> $gmwLocation['country_long'],
							'address' 			=> $gmwLocation['address_apt'],
							'formatted_address' => $gmwLocation['formatted_address'],
							//'phone' 			=> $gmwLocation['phone'],
							//'fax' 			=> $gmwLocation['fax'],
							//'email' 			=> $gmwLocation['email'],
							//'website' 		=> $gmwLocation['website'],
							'lat' 				=> $gmwLocation['lat'],
							'long' 				=> $gmwLocation['long'],
							//'map_icon'  		=> $gmwLocation['map_icon'],
					)
			);
		}
		//echo '</pre>';
	}
}
//add_action('bp_checkins_places_after_save', 'cross_update_checkin_meta');

function cross_update_geo_meta($gmwLocation) {
	if(class_exists('BP_Checkins_Place') && $gmwLocation['post_type'] == 'places') {
		if( !empty( $gmwLocation['post_id'] ) )
			$postID = $gmwLocation['post_id'];
		else
			continue;
		if( !empty( $gmwLocation['address'] ) ) {
			update_post_meta( $postID, 'bpci_places_address', $gmwLocation['address'] );	
			//do_action_ref_array( 'bp_checkins_places_after_address_postmeta_update', array( &$this ) );
		}				
		if( !empty( $this->lat ) ) {
			update_post_meta( $postID, 'bpci_places_lat', $gmwLocation['lat'] );	
			//do_action_ref_array( 'bp_checkins_places_after_lat_postmeta_update', array( &$this ) );
		}				
		if( !empty( $this->lng ) ) {
			update_post_meta( $postID, 'bpci_places_long', $gmwLocation['long'] );
			//do_action_ref_array( 'bp_checkins_places_after_lng_postmeta_update', array( &$this ) );
		}
	}
}
//add_action('gmw_pt_after_location_updated', 'cross_update_geo_meta');

function cross_update_user_location_gmw($mem_id, $returned_address, $address_apt, $street, $apt, $city) {
	//bp_update_user_meta( $mem_id, 'location', $returned_address );
	xprofile_set_field_data( 'location', $mem_id, $returned_address );
}
//add_action('gmw_fl_after_save_location', 'cross_update_user_location_gmw');


function one_time_sync_all_geo_meta() {
	global $wpdb;
	echo '<pre>';
	$bcplaces = get_posts(array('post_type' => array( 'places' ),'posts_per_page' => -1, 'post_status' => 'any'));
	if($bcplaces) {	
		$gmwlocations = array();
		foreach($bcplaces as $bcplace){
			//update_post_meta( $bcplace->ID, '_bpci_place_hide_sitewide', "0" );
			if(get_post_meta($bcplace->ID,"bpci_places_is_live",true)=='live'&&get_post_meta($bcplace->ID,"st_date",true)){
			/*
				$st_date = get_post_meta($bcplace->ID,"st_date",true);
				$st_time = get_post_meta($bcplace->ID,"st_time",true);
				$end_date = get_post_meta($bcplace->ID,"end_date",true);
				$end_time = get_post_meta($bcplace->ID,"end_time",true);
				// now bp checkins
				$bpstartime = date('Y-m-d', strtotime($st_date)).' '.date('H:i:s', strtotime($st_time));
				$bpendtime = date('Y-m-d', strtotime($end_date)).' '.date('H:i:s', strtotime($end_time));
				echo 'BC'.$bcplace->ID.':<br />'.$bpstartime.'<br />'.$bpendtime.'<br />';
				//update_post_meta( $bcplace->ID, 'bpci_places_is_live', 'live' );
				update_post_meta( $bcplace->ID, 'bpci_places_live_start', $bpstartime );
				update_post_meta( $bcplace->ID, 'bpci_places_live_end', $bpendtime );
				$address = get_post_meta($bcplace->ID,"address",true);
				if(empty($address)) update_post_meta( $bcplace->ID, 'bpci_places_address', $address );
				
				$bcplace->address = $address;
				$bcplace->id = $bcplace->ID;
				$bcplace->title = $bcplace->post_title;
				$gmwlocations[] = $bcplace;
			*/
			}else
				update_post_meta( $bcplace->ID, "bpci_places_is_live", "no" );
			update_post_meta( $bcplace->ID, "_bpci_place_hide_sitewide", 0 );
			update_post_meta( $bcplace->ID, "_bpci_group_id", 0 );
			$address = get_post_meta($bcplace->ID,"bpci_places_address",true);
			$gtaddress = get_post_meta($bcplace->ID,"address",true);
			echo '<pre>'.$bcplace->ID.': '.$address.' <br />'.$gtaddress.'</pre>';
			if(empty($address) && !empty($gtaddress)){	
				$address = $gtaddress;
				update_post_meta( $bcplace->ID, 'bpci_places_address', $gtaddress );
			}
			$bpci_lat = get_post_meta( $bcplace->ID, 'bpci_places_lat', true );
			$bpci_lng = get_post_meta( $bcplace->ID, 'bpci_places_long', true );
			if((empty($bpci_lat) || empty($bpci_lng)) && !empty( $address )){	
				$gt_lat = get_post_meta( $bcplace->ID, 'geo_latitude', true );
				$gt_lng = get_post_meta( $bcplace->ID, 'geo_longitude', true );
				if( empty($gt_lat) || empty($gt_lng) ){	
					$returned_address = bpce_GmwConvertToCoords($address);
					$bpci_lat = $returned_address['lat'];
					$bpci_lng = $returned_address['long'];
				} else {	
					$bpci_lat = $gt_lat;
					$bpci_lng = $gt_lng;
				}
				
			}
			if(!empty($bpci_lat))
				update_post_meta( $bcplace->ID, 'bpci_places_lat', $bpci_lat );	
			if(!empty($bpci_lng))
				update_post_meta( $bcplace->ID, 'bpci_places_long', $bpci_lng );
		}
		//cross_update_checkin_meta($gmwlocations);
	}
	
	//$gtplaces = get_posts(array('post_type' => array( 'place', 'event' ),'posts_per_page' => -1, 'post_status' => 'any'));
	if($gtplaces) {	
		$gmwlocations = array();
		foreach($gtplaces as $gtplace){
			$lat = get_post_meta($gtplace->ID,"geo_latitude",true);
			$long = get_post_meta($gtplace->ID,"geo_longitude",true);
			$address = get_post_meta($gtplace->ID,"address",true);
			$timing = get_post_meta($gtplace->ID,"timing",true);
			$contact = get_post_meta($gtplace->ID,"contact",true);
			$email = get_post_meta($gtplace->ID,"email",true);
			$website = get_post_meta($gtplace->ID,"website",true);
			$twitter = get_post_meta($gtplace->ID,"twitter",true);
			$facebook = get_post_meta($gtplace->ID,"facebook",true);
			if($gtplace->post_type == 'event'){
				$st_date = get_post_meta($gtplace->ID,"st_date",true);
				$st_time = get_post_meta($gtplace->ID,"st_time",true);
				$end_date = get_post_meta($gtplace->ID,"end_date",true);
				$end_time = get_post_meta($gtplace->ID,"end_time",true);
				// now bp checkins
				$bpstartime = date('Y-m-d', strtotime($st_date)).' '.date('H:i:s', strtotime($st_time));
				$bpendtime = date('Y-m-d', strtotime($end_date)).' '.date('H:i:s', strtotime($end_time));
				update_post_meta( $gtplace->ID, 'bpci_places_is_live', 'live' );
				update_post_meta( $gtplace->ID, 'bpci_places_live_start', $bpstartime );
				update_post_meta( $gtplace->ID, 'bpci_places_live_end', $bpendtime );
			}
			// + package_pid, post_city_id, reg_fees, tl_dummy_content

			update_post_meta( $gtplace->ID, 'bpci_places_address', $address );
			update_post_meta( $gtplace->ID, 'bpci_places_lat', $lat );	
			update_post_meta( $gtplace->ID, 'bpci_places_long', $long );
			$postupdates = array( 'ID' => $gtplace->ID, 'post_type' => 'places' );			
			wp_update_post( $postupdates );
			echo 'GT'.$gtplace->ID.'<br />';
			// and then geomywp
			$location->address = $address;
			$location->id = $gtplace->ID;
			$location->title = $gtplace->post_title;
			$gmwlocations[] = $location;
		}
		cross_update_checkin_meta($gmwlocations);
	}
	//$users = get_users(array('meta_key' => 'wpec-customer-coupons'));
	//$users = get_users();
    if($users) {		
		foreach($users as $user){
			echo 'U'.$user->ID.'<br />';
			$fieldname = __('Location','bp-checkins-enhanced');
			//$locdata = xprofile_get_field_data( $fieldname, $user->ID );		
			$locdata = xprofile_get_field_data( 'Indirizzo', $user->ID );			
			if ( isset($locdata) ) {
				if(is_array($locdata))
					$address = implode(' ', $locdata);
				else
					$address = $locdata;
			}else
				continue;
			
			$returned_address = bpce_GmwConvertToCoords($address);
			print_r($returned_address);
			//$orgAddress = $address;
			$orgAddress = $returned_address;
			$street 	   = ( isset($orgAddress['street']) ) ? $orgAddress['street'] : false;
			$apt           = ( isset($orgAddress['apt']) ) ? $orgAddress['apt'] : false;
			$city          = ( isset($orgAddress['city']) ) ? $orgAddress['city'] : false;
			/*$state  	   = ( isset($orgAddress['state']) ) ? $returned_address['state_short'] : false;
			$state_long    = ( isset($orgAddress['state']) ) ? $returned_address['state_long'] : false;
			$zipcode  	   = ( isset($orgAddress['zipcode']) ) ? $orgAddress['zipcode'] : false;
			$country       = ( isset($orgAddress['country']) ) ? $returned_address['country_short'] : false;
			$country_long  = ( isset($orgAddress['country']) ) ? $returned_address['country_long'] : false; */

			$wpdb->replace( 'wppl_friends_locator', array( 
				'member_id'			=> $user->ID,	
				'street' 			=> $street,
				'apt' 				=> $apt,
				'city' 				=> $city,
				'state' 			=> $returned_address['state_short'], 
				'state_long' 		=> $returned_address['state_long'], 
				'zipcode'			=> $orgAddress['zipcode'],
				'country' 			=> $returned_address['country_short'],
				'country_long' 		=> $returned_address['country_long'],
				'address'			=> $address,
				'formatted_address' => $returned_address['formatted_address'],
				'lat'				=> $returned_address['lat'],
				'long'				=> $returned_address['long'],
				'map_icon'			=> $map_icon	
			));				
				//$activity_address = $returned_address['formatted_address'];
				//$activity_id = gmw_location_record_activity( $args = array('location' => apply_filters('gmw_fl_activity_address_fields', $activity_address, $returned_address, $gmw_options) ) );
		}
	}
	echo '</pre>';
}
//add_action('admin_footer', 'one_time_sync_all_geo_meta');
?>