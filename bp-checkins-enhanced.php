<?php
/*
Plugin Name: BP Checkins Enhanced
Plugin URI: http://www.3dolab.net/blog/dev/
Description: BP Checkins Enhancements
Author: 3dolab
Author URI:http://www.3dolab.net/
Version: 0.1
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
*/

// Make sure that no info is exposed if file is called directly -- Idea taken from Akismet plugin
if ( !function_exists( 'add_action' ) ) {
	echo "This page cannot be called directly.";
	exit;
}

// Define some useful constants that can be used by functions
/*
if ( ! defined( 'WP_CONTENT_URL' ) ) {	
	if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
	define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
}
if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if ( basename(dirname(__FILE__)) == 'plugins' )
	define("BLANK_DIR",'');
else define("BLANK_DIR" , basename(dirname(__FILE__)) . '/');
define("BLANK_PATH", WP_PLUGIN_URL . "/" . BLANK_DIR);
*/
//require_once ( dirname( __FILE__ ) . '/bp-checkins-enhanced-admin.php' );
require_once ( dirname( __FILE__ ) . '/data-sync.php' );
require_once ( dirname( __FILE__ ) . '/place-query-filter.php' );
require_once ( dirname( __FILE__ ) . '/event-calendar-widget.php' );
require_once ( dirname( __FILE__ ) . '/ajax-registration.php' );

/**
 * GMW function - Geocode address
 * @version 1.0
 * @author Eyal Fitoussi
 */
function bpce_GmwConvertToCoords($org_address) {
	
 	$returned_address = array();
	$ch = curl_init();	
    $rip_it = array( " " => "+", "," => "", "?" => "", "&" => "", "=" => "" , "#" => "");
	$locale = get_locale();
    $lang = substr($locale,0,2);
    // MAKE SURE ADDRES DOENST HAVE ANY CHARACTERS THAT GOOGLE CANNOT READ 
    $address = str_replace(array_keys($rip_it), array_values($rip_it), $org_address);
    
    // GET THE XML FILE WITH RESULTS
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/2.0 (compatible; MSIE 3.02; Update a; AK; Windows 95)");
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_URL, "http://maps.googleapis.com/maps/api/geocode/xml?address=". $address."&language=".$lang."&sensor=false"  );
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
	$got_xml = curl_exec($ch);
  
    // PARSE THE XML FILE 
    $xml = false;
	$xml = new SimpleXMLElement($got_xml);

	if ( $xml->status == 'OVER_QUERY_LIMIT' ) :
			$returned_address = false;
	elseif ( $xml->status == 'ZERO_RESULTS' ) :
		$returned_address = false;
	elseif ( $xml->status == 'OK' ) :
		//GET THE LATITUDE/LONGITUDE FROM THE XML FILE 
		$returned_address['lat']  = esc_attr( $xml->result->geometry->location->lat );
		$returned_address['long'] = esc_attr( $xml->result->geometry->location->lng );
		
		$returned_address['formatted_address'] = esc_attr($xml->result->formatted_address);
		$address_array = $xml->result->address_component;
		
		if ( isset($address_array) && !empty($address_array) ) :
			$returned_address['street'] = false;
			$returned_address['apt'] = false;
			$returned_address['city'] = false;
			$returned_address['state_short'] = false;
			$returned_address['state_long'] = false;
			$returned_address['zipcode'] = false;
			$returned_address['country_short'] = false;
			$returned_address['country_long'] = false;
			
			foreach ($address_array as $ac) :
				if ( $ac->type == 'street_number' ) :
					$street_number = esc_attr($ac->long_name); 
				endif;
				
				if ($ac->type == 'route') :
					$street_f = esc_attr($ac->long_name); 
					if ( isset( $street_number )  && !empty( $street_number ) )	
						$returned_address['street'] = $street_number . ' ' . $street_f;
					else
						$returned_address['street'] = $street_f;
				endif;
				
				if ($ac->type == 'subpremise') 
					$returned_address['apt'] = esc_attr($ac->long_name); 
				
				if ($ac->type == 'city') 
					$returned_address['city'] = esc_attr($ac->long_name); 
					
				if ($ac->type == 'administrative_area_level_2'):
					$returned_address['province'] = esc_attr($ac->long_name);
					$returned_address['prv'] = esc_attr($ac->short_name);
				endif;
					
				if ($ac->type == 'administrative_area_level_1') :
					$returned_address['state_short'] = esc_attr($ac->short_name); 
					$returned_address['state_long'] = esc_attr($ac->long_name);
					$returned_address['rgn'] = esc_attr($ac->short_name); 
					$returned_address['region'] = esc_attr($ac->long_name);
				endif;
				
				if ($ac->type == 'postal_code') 
					$returned_address['zipcode'] = esc_attr($ac->long_name); 
				
				if ($ac->type == 'country') :
					$returned_address['country_short'] = esc_attr($ac->short_name); 
					$returned_address['country_long'] = esc_attr($ac->long_name);
				endif;	
			endforeach;
		endif;
		return $returned_address;
	endif;
}

function cross_update_user_location_public($field_id, $value) {
	//we would need a new "xprofile field" option in the admin settings
	//if( $field_id == __('Location','bp-checkins-enhanced') )
	if( $field_id == 'Indirizzo' ){
		$address = bpce_GmwConvertToCoords($value);
		bp_update_user_meta( $user_id, 'bpci_public_lat', $address['lat'] );
		bp_update_user_meta( $user_id, 'bpci_public_lng', $address['lng'] );
	}
}
add_action('xprofile_profile_field_data_updated', 'cross_update_user_location_public', 10, 2);

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
//add_action( 'save_post', 'auto_set_city_taxonomy' );
//add_action( 'bp_checkins_places_after_address_postmeta_update', 'auto_set_city_taxonomy' );
//add_action( 'bp_checkins_places_after_address_postmeta_insert', 'auto_set_city_taxonomy' );
add_action( 'bp_checkins_places_after_save', 'auto_set_city_taxonomy' );

function bp_checkins_places_permalink_filter($permalink){
	//return str_replace(bp_get_checkins_root_slug().'/place/', '/'.__( 'places', 'bp-checkins' ).'/',$permalink);
	$permalink = str_replace('/place/', '/'.__( 'places', 'bp-checkins-enhanced' ).'/',$permalink);
	$permalink = str_replace('/category/', '/'.__( 'places_category', 'bp-checkins-enhanced' ).'/',$permalink);
	return $permalink;
}
// there's too much to edit into the original source ( bp component and action variable conditionals )
//add_filter('bp_get_checkins_places_the_permalink', 'bp_checkins_places_permalink_filter');
//add_filter('bp_get_checkins_places_category_link', 'bp_checkins_places_permalink_filter');
//add_filter('bp_get_checkins_places_home', 'bp_checkins_places_permalink_filter');

function bp_checkins_enhanced_display_place_checkin($content=false){
	if($content) {	
		$place_id = get_the_ID();
		$place_permalink = get_permalink($place_id);
	} else {	
		$place_id = bp_get_checkins_places_id();
		$place_permalink = bp_get_checkins_places_the_permalink();	
	}
	$address = get_post_meta( $place_id, 'bpci_places_address', true );	
	$lat = get_post_meta( $place_id, 'bpci_places_lat', true );
	$lng = get_post_meta( $place_id, 'bpci_places_lng', true );
	if(get_post_meta( $place_id, 'bpci_places_is_live', true )){
			$eventstart = get_post_meta( $place_id, 'bpci_places_live_start', true );
			if($eventstart)
				$start = date_i18n(get_option('date_format'),strtotime($eventstart));
			$eventend = get_post_meta( $place_id, 'bpci_places_live_end', true );
			if($eventend)
				$end = date_i18n(get_option('date_format'),strtotime($eventend));
			if($start && $end) {
				if($start == $end)
					$live_event = $start;
				else
					$live_event = ' '.__('from','bp-checkins-enhanced').' '.$start.' '.__('to','bp-checkins-enhanced').' '.$end;
			}
			elseif($start)
				$live_event = ' '.__('from','bp-checkins-enhanced').' '.$start;
			elseif($end)
				$live_event = ' '.__('until','bp-checkins-enhanced').' '.$end;
		}
	if( $address ){
			$div = '
				<div class="activity-checkin">
					<a href="'.$place_permalink.'" title="'.__('Open the map for this update', 'bp-checkins').'" id="place-'.$place_id.'" rel="'.$lat.','.$lng.'" class="link-checkin"><span class="update-checkin">'.stripslashes( $address ).'</span></a>'.$live_event.'
				</div>
				';
	}
	if($content)
		return $content.$div;
	else
		echo $div;
}
//remove_action( 'bp_places_entry_content', 'bp_checkins_display_place_checkin');
//add_action( 'bp_places_entry_content', 'bp_checkins_enhanced_display_place_checkin');

function bp_checkins_enhanced_display_user_checkin($content=false){

	if( ( (int)bp_get_option( 'bp-checkins-disable-activity-checkins' ) && !bp_is_current_component('checkins') && !bp_is_current_action( 'checkins' ) && !bp_is_single_activity() ) || ( (int)bp_get_option( 'bp-checkins-disable-activity-checkins' ) && bp_is_single_activity() && ( !(int)bp_get_option( 'bp-checkins-activate-component' ) || '' == bp_get_option( 'bp-checkins-activate-component' ) ) )  )
		return false;
	
	if($content) {	
		$activity_id = get_the_ID();
		$activity_permalink = get_permalink($activity_id);
		$address = get_post_meta( $activity_id, 'bpci_places_address', true );	
		$lat = get_post_meta( $activity_id, 'bpci_places_lat', true );
		$lng = get_post_meta( $activity_id, 'bpci_places_lng', true );
		if(get_post_meta( $activity_id, 'bpci_places_is_live', true )){
			$eventstart = get_post_meta( $activity_id, 'bpci_places_live_start', true );
			if($eventstart)
				$start = date_i18n(get_option('date_format'),strtotime($eventstart));
			$eventend = get_post_meta( $activity_id, 'bpci_places_live_end', true );
			if($eventend)
				$end = date_i18n(get_option('date_format'),strtotime($eventend));
			if($start && $end) {
				if($start == $end)
					$live_event = $start;
				else
					$live_event = ' '.__('from','bp-checkins-enhanced').' '.$start.' '.__('to','bp-checkins-enhanced').' '.$end;
			}
			elseif($start)
				$live_event = ' '.__('from','bp-checkins-enhanced').' '.$start;
			elseif($end)
				$live_event = ' '.__('until','bp-checkins-enhanced').' '.$end;
		}
	} else {	
		$activity_id = bp_get_activity_id();
		$activity_permalink = bp_activity_get_permalink( $activity_id ) . '?map=1';
		$address = bp_activity_get_meta( $activity_id, 'bpci_activity_address' );
		$lat = bp_activity_get_meta( $activity_id, 'bpci_activity_lat' );
		$lng = bp_activity_get_meta( $activity_id, 'bpci_activity_lng' );
	}
	if( $address ){
			$div = '
				<div class="activity-checkin">
					<a href="'.$activity_permalink.'" title="'.__('Open the map for this update', 'bp-checkins').'" id="activity-'.$activity_id.'" rel="'.$lat.','.$lng.'" class="link-checkin"><span class="update-checkin">'.stripslashes( $address ).'</span></a>'.$live_event.'
				</div>
				';
	}
	if($content)
		return $content.$div;
	else
		echo $div;
}
//remove_action( 'bp_activity_entry_content', 'bp_checkins_display_user_checkin');
//add_action( 'bp_activity_entry_content', 'bp_checkins_enhanced_display_user_checkin');
function bp_checkins_enhanced_load_core_actions(){
	remove_action( 'bp_activity_entry_content', 'bp_checkins_display_user_checkin');
	add_action( 'bp_activity_entry_content', 'bp_checkins_enhanced_display_user_checkin');
	remove_action( 'bp_places_entry_content', 'bp_checkins_display_place_checkin');
	add_action( 'bp_places_entry_content', 'bp_checkins_enhanced_display_place_checkin');
}
add_action( 'bp_loaded', 'bp_checkins_enhanced_load_core_actions' );

function bp_checkins_add_members_position(){
	global $wpdb;
	
	//if( bp_is_user_friends() ) {
		$user_id = bp_get_member_user_id();
		//$lat = bp_get_user_meta( $user_id, 'bpci_latest_lat', true );
		//$lng = bp_get_user_meta( $user_id, 'bpci_latest_lng', true );
		//$address = bp_get_user_meta( $user_id, 'bpci_latest_address', true );
		
		// public location data taken from geomywp, but it could also be a custom BP xprofile field, chosen in bp-checkins settings (need option)

		$member_loc_tab = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT * FROM wppl_friends_locator
					WHERE member_id = %s",
					array($user_id)
			), ARRAY_A
		);
		if ( isset( $member_loc_tab ) && !empty( $member_loc_tab ) ) :
			$mem_loc = array(
					'savedLat' 	=> $member_loc_tab[0]['lat'],
					'savedLong' => $member_loc_tab[0]['long']
			);
			$lat = $member_loc_tab[0]['lat'];
			$lng = $member_loc_tab[0]['long'];
			$address = $member_loc_tab[0]['address'];
		else:
			$lat = bp_get_user_meta( $user_id, 'bpci_public_lat', true );
			$lng = bp_get_user_meta( $user_id, 'bpci_public_lng', true );
			$fieldname = __('Location','bp-checkins-enhanced');
			//$locdata = xprofile_get_field_data( $fieldname, $user->ID );		
			$address = xprofile_get_field_data( 'Indirizzo', $user_id );
		endif;
		
		//print_r($member_loc_tab);
		
		if($lat && $lng && $address){
			?>
			<div class="member-location">
				<a href="#bpci-map" title="<?php _e('Center the map on this member', 'bp-checkins');?>" id="member-<?php echo $user_id;?>" rel="<?php echo $lat.','.$lng;?>" class="link-checkin"><span class="update-checkin"><?php echo stripslashes( $address );?></span></a>
			</div>
			<?php
		}
	//}
}

add_action('bp_directory_members_actions', 'bp_checkins_add_members_position', 99);
//add_action('bp_directory_members_item', 'bp_checkins_add_members_position', 99);

function bp_checkins_load_members_map(){

	//if( (int)bp_get_option( 'bp-checkins-disable-geo-friends' ) )
		//return false;
		
	if( (int)bp_get_option( 'bp-checkins-disable-activity-checkins' ) && ( !(int)bp_get_option( 'bp-checkins-activate-component' ) || '' == bp_get_option( 'bp-checkins-activate-component' ) ) )
		return false;
		
	$user_id = bp_displayed_user_id();
	
	if(!$user_id) {
		 $user_id = get_current_user_id();
		 $avatar = '<div id="item-header-avatar" style="display:none">'.get_avatar( $user_id, 150 ).'</div>';
		//return false;
		echo $avatar;
	}
	$lat = bp_get_user_meta( $user_id, 'bpci_latest_lat', true );
	$lng = bp_get_user_meta( $user_id, 'bpci_latest_lng', true );
	$address = bp_get_user_meta( $user_id, 'bpci_latest_address', true );
	?>
	<div id="bpci-map_container"></div>
	
	<?php if( !empty( $lat ) ):?>
	
		<script type="text/javascript">
			var displayedUserLat = "<?php echo $lat;?>";
			var displayedUserLng = "<?php echo $lng;?>";
			var displayedUserAddress = "<?php echo $address;?>";
			var displayedUserPin = "<?php echo plugin_dir_url( __FILE__ ) . 'images/blackpin.png'; ?>";
			var defaultPlacePin = "<?php echo plugin_dir_url( __FILE__ ) . 'images/pin.png'; ?>";
		</script>
		
	<?php else :?>
	
		<script type="text/javascript">
			var displayedUserPin = "<?php echo plugin_dir_url( __FILE__ ) . 'images/blackpin.png'; ?>";
			var defaultPlacePin = "<?php echo plugin_dir_url( __FILE__ ) . 'images/pin.png'; ?>";
		</script>
		
	<?php endif;?>
	
	<?php
}
//add_action('bp_before_members_friends_content', 'bp_checkins_load_members_map');
//add_action('bp_members_screen_index', 'bp_checkins_load_members_map');
add_action('bp_before_directory_members', 'bp_checkins_load_members_map');
add_action('bp_before_directory_checkins', 'bp_checkins_load_members_map');
//add_action('bp_before_directory_activity', 'bp_checkins_load_members_map');
add_action('activity_loop_start', 'bp_checkins_load_members_map');
add_action('bp_before_member_friends_content', 'bp_checkins_load_members_map');

//bp_checkins_place_geolocate
//bp_checkins_place_display_cats

function bp_checkins_enhanced_load_gmap3() {
	if( bp_checkins_is_activity_or_friends() || bp_checkins_is_directory() || bp_checkins_is_group_checkins_area() ) {
	
		wp_enqueue_script( 'google-maps', 'http://maps.google.com/maps/api/js?sensor=false' );
		wp_dequeue_script( 'gmap3' );
		wp_deregister_script( 'gmap3' );
		wp_enqueue_script( 'gmap3', plugin_dir_url( __FILE__ ) . 'js/gmap3.js', array('jquery'), '5.1' );
		wp_dequeue_style( 'bpcistyle' );
		wp_deregister_style( 'bpcistyle' );
		wp_enqueue_style( 'bpcistyle',  plugin_dir_url( __FILE__ ) . 'css/bpcinstyle.css', array(), '5.1' );
		
		if( !empty( $_GET['map'] ) && $_GET['map'] == 1 ) {
			global $bpci_lat, $bpci_lng;
			$bpci_lat = bp_activity_get_meta( bp_current_action(), 'bpci_activity_lat' );
			$bpci_lng = bp_activity_get_meta( bp_current_action(), 'bpci_activity_lng' );

			if( !empty( $bpci_lat ) && !empty( $bpci_lng ) ) {
				remove_action('wp_head', 'bp_checkins_item_map');
				add_action( 'wp_head', 'bp_checkins_item_map_enhanced');
			}
			
		} elseif( bp_checkins_show_friends_checkins() ){
			wp_dequeue_script( 'bp-ckeckins-friends' );
			wp_deregister_script( 'bp-ckeckins-friends' );
			wp_register_script( 'bp-ckeckins-friends', plugin_dir_url( __FILE__ ) . 'js/bp-checkins-friends.js' );
			wp_enqueue_script( 'bp-ckeckins-friends');
			remove_action('bp_before_member_friends_content', 'bp_checkins_load_friends_map');
			add_action('bp_before_member_friends_content', 'bp_checkins_load_members_map');
		} else {
			
			if( bp_checkins_is_directory() || bp_checkins_is_group_checkins_area() ) {
				wp_dequeue_script( 'bp-ckeckins-dir' );
				wp_deregister_script( 'bp-ckeckins-dir' );
				wp_register_script( 'bp-ckeckins-dir', plugin_dir_url( __FILE__ ) . 'js/bp-checkins-dir.js' );
				wp_enqueue_script( 'bp-ckeckins-dir' );
				bp_checkins_localize_script('dir');
				remove_action( 'bp_activity_entry_content', 'bp_checkins_display_user_checkin');
				add_action( 'bp_activity_entry_content', 'bp_checkins_enhanced_display_user_checkin');
				remove_action( 'bp_places_entry_content', 'bp_checkins_display_place_checkin');
				add_action( 'bp_places_entry_content', 'bp_checkins_enhanced_display_place_checkin');
			} else {
				wp_dequeue_script( 'bp-ckeckins' );
				wp_deregister_script( 'bp-ckeckins' );
				wp_register_script( 'bp-ckeckins', plugin_dir_url( __FILE__ ) . 'js/bp-checkins.js' );
				wp_enqueue_script( 'bp-ckeckins');
				bp_checkins_localize_script('activity');
				add_action('activity_loop_start', 'bp_checkins_load_members_map');
			}
			
		}
		
		if( bp_is_single_activity() ){
			add_action('wp_footer', 'bp_checkins_img_trick');
		}
		
	} elseif( bp_is_members_component() || bp_checkins_is_place_home() || bp_checkins_is_category_place() || bp_checkins_is_single_place() || ( is_single() && get_post_type()=='places' ) || is_post_type_archive( 'places' ) || ( ( is_tax('places_category') || is_tax('city') ) && get_post_type()=='places' ) ) {
	
		wp_enqueue_script( 'google-maps', 'http://maps.google.com/maps/api/js?sensor=false' );
		wp_dequeue_script( 'gmap3' );
		wp_deregister_script( 'gmap3' );
		wp_enqueue_script( 'gmap3', plugin_dir_url( __FILE__ ) . 'js/gmap3.js', array('jquery'), '5.1' );	
		wp_dequeue_style( 'bpcistyle' );
		wp_deregister_style( 'bpcistyle' );		
		wp_enqueue_style( 'bpcistyle',  plugin_dir_url( __FILE__ ) . 'css/bpcinstyle.css', array(), '5.1' );
	}
	
	if( bp_displayed_user_id() && bp_is_settings_component() && bp_is_current_action( 'checkins-settings') ){
		wp_enqueue_style( 'bpcistyle', plugin_dir_url( __FILE__ ) . 'css/bpcinstyle.css', array(), '5.1' );
	}
	

	if( bp_checkins_is_place_home() && !bp_checkins_is_single_place() ){
		if(bp_checkins_is_category_place()){
			wp_dequeue_script( 'bp-ckeckins-cats');
			wp_deregister_script( 'bp-ckeckins-cats');
			wp_register_script( 'bp-ckeckins-cats', plugin_dir_url( __FILE__ ) . 'js/bp-checkins-cats.js' );
			wp_enqueue_script( 'bp-ckeckins-cats' );
			bp_checkins_localize_script('cats');
			//add_action( 'bp_before_directory_bp_checkins_page', 'bp_checkins_load_members_map');
			add_action( 'template_notices', 'bp_checkins_load_members_map');
		} elseif(!is_singular()&&!is_home()&&!is_front_page()&&!bp_checkins_is_directory()&&!bp_checkins_is_group_checkins_area()&&!bp_is_members_component()&&!bp_checkins_if_single_place()) {
			wp_dequeue_style( 'bpcistyle' );
			wp_deregister_style( 'bpcistyle' );
			wp_enqueue_style( 'bpcistyle',  plugin_dir_url( __FILE__ ) . 'css/bpcinstyle.css', array(), '5.1' );
			wp_enqueue_script( 'bp-ckeckins-places', plugin_dir_url( __FILE__ ) . 'js/bp-checkins-places.js' );
			//remove_action( 'bp_activity_entry_content', 'bp_checkins_display_user_checkin');
			//add_action( 'bp_activity_entry_content', 'bp_checkins_enhanced_display_user_checkin');
			//remove_action( 'bp_places_entry_content', 'bp_checkins_display_place_checkin');
			//add_action( 'bp_places_entry_content', 'bp_checkins_enhanced_display_place_checkin');
			add_action( 'template_notices', 'bp_checkins_load_members_map');
			//add_action( 'bp_before_home_bp_checkins_page', 'bp_checkins_load_members_map');
		}
	} elseif(bp_checkins_is_single_place()){
			wp_dequeue_script( 'bp-ckeckins-single' );
			wp_deregister_script( 'bp-ckeckins-single' );
			wp_enqueue_script( 'bp-ckeckins-single', BP_CHECKINS_PLUGIN_URL_JS . '/bp-checkins-single.js' );
			bp_checkins_localize_script('single');
			
			remove_action( 'bpci_map_single', 'bp_checkins_place_map' );
			add_action( 'bpci_map_single', 'bp_checkins_place_map_filter');

	} elseif( bp_is_members_component() ) {
	
		wp_enqueue_script( 'bp-ckeckins-members', plugin_dir_url( __FILE__ ) . 'js/bp-checkins-members.js' );
					
	} elseif ( is_singular() && get_post_type()=='places' ){
		
			add_action('comment_form_after_fields', 'bp_checkins_places_geo_fields');
			wp_enqueue_script( 'bp-ckeckins-single', BP_CHECKINS_PLUGIN_URL_JS . '/bp-checkins-single.js' );
			bp_checkins_localize_script('single');			
			//add_action('bpci_map_single', 'bp_checkins_place_map');
			//add_filter('the_content', 'bp_checkins_place_map_filter');
			add_filter('the_excerpt', 'bp_checkins_place_map_filter');
			add_action('comment_form_top', 'bp_checkins_places_geo_fields');
			
	} elseif ( !is_404() && !is_search() && ( is_post_type_archive( 'places' ) || ( ( is_tax('places_category') || is_tax('city') ) && get_post_type()=='places' ) ) ) {
			global $wp_query;
			if($wp_query->is_main_query() && $wp_query->found_posts){
			//add_filter('the_content','bp_checkins_place_map_filter');
			wp_enqueue_script( 'bp-ckeckins-places', plugin_dir_url( __FILE__ ) . 'js/bp-checkins-places.js' );
			//add_filter( 'the_content', 'bp_checkins_enhanced_display_place_checkin');
			add_filter( 'the_excerpt', 'bp_checkins_enhanced_display_place_checkin');
			//add_action( 'the_post', 'bp_checkins_enhanced_display_place_checkin');
			add_action( 'loop_start', 'bp_checkins_load_members_map');
			}
	}

}

add_action('bp_actions', 'bp_checkins_enhanced_load_gmap3', 99);

function bp_checkins_place_map_filter($content=false) {
	global $bpci_lat, $bpci_lng;
	$place_id = get_the_ID();
	$bpci_lat = get_post_meta( $place_id, 'bpci_places_lat', true );
	$bpci_lng = get_post_meta( $place_id, 'bpci_places_lng', true );
	$address = get_post_meta( $place_id, 'bpci_places_address', true );
	if(get_post_meta( $place_id, 'bpci_places_is_live', true )){
			$eventstart = get_post_meta( $place_id, 'bpci_places_live_start', true );
			if($eventstart)
				$start = date_i18n(get_option('date_format'),strtotime($eventstart));
			$eventend = get_post_meta( $place_id, 'bpci_places_live_end', true );
			if($eventend)
				$end = date_i18n(get_option('date_format'),strtotime($eventend));
			if($start && $end) {
				if($start == $end)
					$live_event = $start;
				else
					$live_event = ' '.__('from','bp-checkins-enhanced').' '.$start.' '.__('to','bp-checkins-enhanced').' '.$end;
			}
			elseif($start)
				$live_event = ' '.__('from','bp-checkins-enhanced').' '.$start;
			elseif($end)
				$live_event = ' '.__('until','bp-checkins-enhanced').' '.$end;
		}
	if((empty($bpci_lat) || empty($bpci_lng)) && !empty($address )){	
		$returned_address = bpce_GmwConvertToCoords($address);
		$bpci_lat = $returned_address['lat'];
		$bpci_lng = $returned_address['long'];
	}

	if(!empty($bpci_lat) && !empty($bpci_lng)){
		$overlaycontent = "<div class='bpci-avatar'><s></s><i></i><span>";
		$avatar = bp_get_checkins_places_avatar();
		$mapwrapper = '<div id="bpci-map" style="width:100%"></div><div class="places-avatar">'.$avatar.'</div><div class="activity-checkin">'.stripslashes( $address ).$live_event.'</div>';
		//if ( is_singular() && ( $content || get_post_type()=='places' ) )
			$script = '<script type="text/javascript">
				jQuery(document).ready(function($){
					var bpciPosition = new google.maps.LatLng('.$bpci_lat.','.$bpci_lng.');

					$("#bpci-map").gmap3(
						{ map: { 
							options: { 
								center:bpciPosition,
								zoom: 6,
								mapTypeId: google.maps.MapTypeId.TERRAIN,
								callback:function(map){
									for (var i=0; i < arrayMarkers.length ; i++ ) {
									  add($(this), i, arrayMarkers[i].lat, arrayMarkers[i].lng, arrayMarkers[i].data);
									  map.setCenter(bpciPosition);
									  map.setZoom(6);
									}
								}
							},
						},
						marker: { 
							latLng: bpciPosition,
							data: $(".places-avatar").html(),
							options: {
							  icon: $(".places-avatar img").attr("src")
							},
							events:{ // events trigged by markers
								click: function(marker, event, context){
									window.location.href = $("a.places-avatar").attr("href");
								}
							}
						}
					});
				});
			</script>';
		//elseif ( is_singular() && !$content && !bp_checkins_is_single_place() )
	}
		if($content)
			return $mapwrapper.$script.$content;
		else
			echo $script;
}

function bp_checkins_item_map_enhanced() {

	global $bpci_lat, $bpci_lng;
	$overlaycontent = "<div class='bpci-avatar'><s></s><i></i><span>";
	if(!empty($bpci_lat) && !empty($bpci_lng)){
			$script = '<script type="text/javascript">
				jQuery(document).ready(function($){
					var bpciPosition = new google.maps.LatLng('.$bpci_lat.','.$bpci_lng.');
					adresse = $(".update-checkin").html();
					//var pin = $("a.item-avatar img").attr("src");
					//var link = $("a.item-avatar").attr("href");
					//var infotext = $("a.item-avatar").parent().find(".activity-inner p").html();
					$(".activity-checkin").append("<div id=\"bpci-map\"></div>");
					$(".activity-checkin").css("width","100%");
					$("#bpci-map").gmap3(
						{ map: { 
							options: { 
								center:bpciPosition,
								zoom: 6,
								mapTypeId: google.maps.MapTypeId.TERRAIN,
								callback:function(map){
									for (var i=0; i < arrayMarkers.length ; i++ ) {
									  add($(this), i, arrayMarkers[i].lat, arrayMarkers[i].lng, arrayMarkers[i].data);
									  map.setCenter(bpciPosition);
									  map.setZoom(6);
									}
								}
							},
						},
						overlay: {
							latLng: bpciPosition,
							data: $(".activity-avatar").html(),
							options:{	content: "'.$overlaycontent.'" + $(".activity-avatar").html() + "</span></div>",
										offset:{
											y:-40,
											x:10
										}
							}
						}
						/*
						marker: { 
							latLng: bpciPosition,
							data: infotext,
							options: {
							  icon: pin
							},
							events:{ // events trigged by markers
								click: function(marker, event, context){
									window.location.href = link;
								}
							}							
						}
						*/
					});
				});
			</script>';		
	}
			echo $script;
}

function bpce_places_avatar_filter($output) {

	$term_id = bp_get_checkins_places_term_id();
	$place_id = get_the_ID();
	$permalink = get_permalink();
	$title = get_the_title();	
	
	if( !empty($term_id) ) {
		$term_name = bp_get_checkins_places_category_title();
		$term_link = bp_get_checkins_places_category_link();
	} else {
			
		$place_id = bp_get_checkins_places_id();
		$place_category = get_the_terms( $place_id, 'places_category' );
		
		if(!empty($place_category)&&!is_wp_error( $place_category )) {
			foreach( $place_category as $index => $cat ) {
				if (!$term_id)
					$term_id = $cat->term_id;
					$term_name = $cat->name;
					$term_link = bp_get_checkins_places_category_link($cat->slug);
			}
		}
		
	}
	if(!is_single())
		$rel = ' rel="'.$title.'§'.$permalink.'"';
	$avatar = bp_checkins_get_place_parent_category_avatar( $term_id );
	$output = '<a href="'.$term_link.'" class="places-avatar" title="'.$term_name.'"'.$rel.'>'.$avatar.'</a>';
	return $output;
	
}
add_filter( 'bp_get_checkins_places_avatar', 'bpce_places_avatar_filter');

function bp_checkins_get_place_parent_category_avatar( $term_id ) {
		
		$avatar = false;
		
		$avatar_id = get_metadata( 'places_category', $term_id, 'places_category_thumbnail_id', true);
			
		if( !empty( $avatar_id ) ) {
				$avatar_array = wp_get_attachment_image_src( $avatar_id, $size='thumbnail' );
				$avatar = '<img src="'.$avatar_array[0].'" width="'.$avatar_array[1].'" height="'.$avatar_array[2].'">';
		} else  {
				$term = get_term($term_id, 'places_category');
				//print_r($term);
				$parent_id = $term->parent;
				if($parent_id){
					$avatar_id = get_metadata( 'places_category', $parent_id, 'places_category_thumbnail_id', true);
					$avatar_array = wp_get_attachment_image_src( $avatar_id, $size='thumbnail' );
					$avatar = '<img src="'.$avatar_array[0].'" width="'.$avatar_array[1].'" height="'.$avatar_array[2].'">';
			}
		} 
		
		if( !$avatar ) {
			$customdefaultimg = bp_get_option( 'bp-checkins-custom-default-img', plugin_dir_url( __FILE__ ) . 'images/pin.png' );
			$avatar = '<img src="'.$customdefaultimg.'" width="150px" height="150px">';
		}
		return $avatar;
}

function bp_checkins_display_user_checkin_category(){
	
	if( ( (int)bp_get_option( 'bp-checkins-disable-activity-checkins' ) && !bp_is_current_component('checkins') && !bp_is_current_action( 'checkins' ) && !bp_is_single_activity() ) || ( (int)bp_get_option( 'bp-checkins-disable-activity-checkins' ) && bp_is_single_activity() && ( !(int)bp_get_option( 'bp-checkins-activate-component' ) || '' == bp_get_option( 'bp-checkins-activate-component' ) ) )  )
		return false;
		
	$activity_id = bp_get_activity_id();
	$activity_permalink = bp_activity_get_permalink( $activity_id ) . '?map=1';
	
	$address = bp_activity_get_meta( $activity_id, 'bpci_activity_address' );
	
	$place_category = get_the_terms( $activity_id, 'places_category' );
	if(!empty($place_category)&&!is_wp_error( $place_category )) {
		foreach( $place_category as $index => $cat ) {
			if (!$term_id)
				$term_id = $cat->term_id;
				$term_name = $cat->name;
				$term_link = bp_get_checkins_places_category_link($cat->slug);
		}
	}
	$avatar = bp_checkins_get_place_parent_category_avatar( $term_id );
	$output = '<a href="'.$term_link.'" class="item-avatar" title="'.$term_name.'">'.$avatar.'</a>';
	echo $output;
}
// add_action( 'bp_activity_entry_content', 'bp_checkins_display_user_checkin_category',9);
function bp_checkins_post_comment_template( $comment_template ) {
	global $post;

	if ( is_single() && get_post_type()=='places' )
		return BP_CHECKINS_PLUGIN_DIR . '/templates/bp-checkins-place-comments.php';
	else
		return $comment_template;
}
add_filter('comments_template', 'bp_checkins_post_comment_template', 9, 1);
function place_add_template_class($classes) {
	global $post;
	if ( ( is_single() && get_post_type()=='places' ) || is_post_type_archive( 'places' ) || ( is_tax('places_category') || is_tax('city') && get_post_type()=='places' ) )
		$classes[] = 'place';
	return $classes;
}
add_filter('post_class', 'place_add_template_class');
add_filter('body_class', 'place_add_template_class');

function post_author_link_buddypress($link, $author_id, $author_nicename) {
	return $link = bp_core_get_user_domain( $author_id );
}
add_filter( 'author_link', 'post_author_link_buddypress', 10, 3 );
/* THESE ARE THEME SPECIFIC FUNCTIONS => NEED TO HOOK AT HIGHER LEVEL AND THEN UPDATE JS JQUERY SELECTORS */
function place_category_avatar_filter($list) {
	if ( !is_admin() && ( ( is_single() && get_post_type()=='places' ) || is_post_type_archive( 'places' ) || ( is_tax('places_category') || is_tax('city') && get_post_type()=='places' ) ) ) {
		$city = get_the_term_list( get_the_ID(), 'city' );
		if($city)
			$city = '</div><div class="post-details-spacer"></div><div class="post-details-city post-details-category">'.$city;
		$list = '<div class="places-avatar">'.bp_get_checkins_places_avatar().'</div>'.get_the_term_list( get_the_ID(), 'places_category' ).$city;
	}
	return $list;
}
add_filter( 'the_category', 'place_category_avatar_filter' );
function term_links_only_one($term_links) {
	if ( !is_admin() && ( ( is_single() && get_post_type()=='places' ) || is_post_type_archive( 'places' ) || ( is_tax('places_category') || is_tax('city') && get_post_type()=='places' ) ) ) {
		return array(reset($term_links));
	}
	return $term_links;
}
add_filter( 'term_links-places_category', 'term_links_only_one' );
add_filter( 'term_links-city', 'term_links_only_one' );
?>