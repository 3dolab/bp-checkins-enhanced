<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

//$checkins_enhanced_activated = (int)bp_get_option( 'bp-checkins-enhanced-activate-component' );

function bp_checkins_enhanced_admin_tabs( $tabs, $admin_page ) {

	if(!empty($tabs) && is_array($tabs)){
		$href = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-checkins-admin' , 'tab' => 'enhanced'), $admin_page ) );
		$tabs[] = array( 'href' => $href, 'name' => __( 'BP Checkins Enhanced', 'bp-checkins-enhanced' ) );
	}
	return $tabs;
}
add_filter('bp_checkins_admin_tabs','bp_checkins_enhanced_admin_tabs', 10, 2);

function bp_checkins_enhanced_set_active_tab($active, $tab){
	if( !empty( $tab ) && $tab == 'enhanced' )
		$active = __( 'BP Checkins Enhanced', 'bp-checkins-enhanced' );
	return $active;
}
add_filter('bp_checkins_admin_tabs', 'bp_checkins_enhanced_set_active_tab', 10, 2);

function bp_checkins_enhanced_settings_admin( $tab, $admin_page ){
	global $bp_checkins_logs_slug;
		
	$buddy_settings_page = bp_core_do_network_admin() ? 'settings.php' : 'options-general.php';
	?>
	
			<?php if( $tab == 'enhanced' ):?>
				<?php if( !(int)bp_get_option( 'bp-checkins-activate-component' ) || '' == bp_get_option( 'bp-checkins-activate-component' ) ):?>	
					<p>
						<?php printf( __('If you want to use this feature, you need to activate the Checkins and Places component, to do so use <a href="%s">the appropriate tab</a>', 'bp-checkins'), bp_get_admin_url( add_query_arg( array( 'page' => 'bp-checkins-admin', 'tab' => 'component'   ), $admin_page ) ) );?>
					</p>
				<?php elseif( !(int)bp_get_option( 'bp-checkins-enhanced-activate-component' ) || '' == bp_get_option( 'bp-checkins-enhanced-activate-component' ) ):?>
					
					<input type="hidden" name="bpci-admin[bp-checkins-enhanced-activate-component]" value="1">
					
					<p>
						<?php _e('If you want to activate this component, simply click on the &#39;Save Settings&#39; button, then you will be able to edit its behavior', 'bp-checkins');?>
					</p>
					
				<?php else:?>	
					<h3><?php _e('BP Checkins Enhanced Component', 'bp-checkins');?></h3>
					<table class="form-table">
						<tbody>
								<tr>
									<th scope="row"><?php _e( 'Places and Events post type and rewrite enabled', 'bp-checkins-enhanced' ) ?></th>
									<td>
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-places-events]"<?php if ( (int)bp_get_option( 'bp-checkins-enhanced-places-events' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-places-events-yes" value="1" /> <?php _e( 'Yes', 'bp-checkins' ) ?> &nbsp;
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-places-events]"<?php if ( !(int)bp_get_option( 'bp-checkins-enhanced-places-events' ) || '' == bp_get_option( 'bp-checkins-enhanced-places-events' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-places-events-no" value="0" /> <?php _e( 'No', 'bp-checkins' ) ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Event Calendar Widget', 'bp-checkins-enhanced' ) ?></th>
									<td>
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-event-calendar]"<?php if ( (int)bp_get_option( 'bp-checkins-enhanced-event-calendar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-event-calendar-yes" value="1" /> <?php _e( 'Yes', 'bp-checkins' ) ?> &nbsp;
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-event-calendar]"<?php if ( !(int)bp_get_option( 'bp-checkins-enhanced-event-calendar' ) || '' == bp_get_option( 'bp-checkins-enhanced-event-calendar' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-event-calendar-no" value="0" /> <?php _e( 'No', 'bp-checkins' ) ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Ajax Registration Form', 'bp-checkins-enhanced' ) ?></th>
									<td>
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-ajax-registration]"<?php if ( (int)bp_get_option( 'bp-checkins-enhanced-ajax-registration' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-ajax-registration-yes" value="1" /> <?php _e( 'Yes', 'bp-checkins' ) ?> &nbsp;
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-ajax-registration]"<?php if ( !(int)bp_get_option( 'bp-checkins-enhanced-ajax-registration' ) || '' == bp_get_option( 'bp-checkins-enhanced-ajax-registration' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-ajax-registration-no" value="0" /> <?php _e( 'No', 'bp-checkins' ) ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Map Type', 'bp-checkins-enhanced' ) ?> (n/a)</th>
									<td>
										<select name="bpci-admin[bp-checkins-enhanced-maptype]" id="bp-checkins-enhanced-maptype">
											<option value="<?php _e('ROADMAP', 'bp-checkins-enhanced');?>" <?php selected( bp_get_option( 'bp-checkins-enhanced-maptype' ), 'ROADMAP' );?>><?php _e('ROADMAP', 'bp-checkins-enhanced');?></option>
											<option value="<?php _e('SATELLITE', 'bp-checkins-enhanced');?>" <?php selected( bp_get_option( 'bp-checkins-enhanced-maptype' ), 'SATELLITE' );?>><?php _e('SATELLITE', 'bp-checkins-enhanced');?></option>
											<option value="<?php _e('TERRAIN', 'bp-checkins-enhanced');?>" <?php selected( bp_get_option( 'bp-checkins-enhanced-maptype' ), 'TERRAIN' );?>><?php _e('TERRAIN', 'bp-checkins-enhanced');?></option>
											<option value="<?php _e('HYBRID', 'bp-checkins-enhanced');?>" <?php selected( bp_get_option( 'bp-checkins-enhanced-maptype' ), 'HYBRID' );?>><?php _e('HYBRID', 'bp-checkins-enhanced');?></option>
										</select>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Clustering', 'bp-checkins' ) ?> (n/a)</th>
									<td>
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-clustering]"<?php if ( (int)bp_get_option( 'bp-checkins-enhanced-clustering' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-clustering-yes" value="1" /> <?php _e( 'Yes', 'bp-checkins' ) ?> &nbsp;
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-clustering]"<?php if ( !(int)bp_get_option( 'bp-checkins-enhanced-clustering' ) || '' == bp_get_option( 'bp-checkins-enhanced-clustering' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-clustering-no" value="0" /> <?php _e( 'No', 'bp-checkins' ) ?>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php _e( 'Markers', 'bp-checkins' ) ?> (n/a)</th>
									<td>
										<input type="text" name="bpci-admin[bp-checkins-enhanced-default-pin]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-place-default-pin' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-place-default-pin' ) .'"'; 
										} else {
											echo 'value="'.dirname( __FILE__ ) . '/images/pin.png"';
										}
										?>	 
										 id="bp-checkins-enhanced-default-pin"/> &nbsp;<?php _e( 'Default Pin', 'bp-checkins-enhanced' );?><br />
										 <input type="text" name="bpci-admin[bp-checkins-enhanced-black-pin]" 
										 <?php if ( bp_get_option( 'bp-checkins-enhanced-place-black-pin' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-place-black-pin' ) .'"'; 
										} else {
											echo 'value="'.dirname( __FILE__ ) . '/images/blackpin.png"';
										}
										?>	 
										 id="bp-checkins-enhanced-black-pin"/> &nbsp;<?php _e( 'Current Pin', 'bp-checkins-enhanced' );?><br />
										<?php if( (int)bp_get_option( 'bp-checkins-enhanced-clustering' ) && '' != bp_get_option( 'bp-checkins-enhanced-clustering' ) ):?>	
										<input type="text" size="5" name="bpci-admin[bp-checkins-enhanced-cluster-radius]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-radius' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-radius' ) .'"'; 
										} else {
											echo 'value="50"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-cluster-radius"/> &nbsp;<?php _e( 'Cluster Radius', 'bp-checkins-enhanced' );?><br />
										 <input type="text" name="bpci-admin[bp-checkins-enhanced-cluster-1-pin]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-1-pin' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-1-pin' ) .'"'; 
										} else {
											echo 'value="'.dirname( __FILE__ ) . '/images/cluster-1.png"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-cluster-1-pin"/> &nbsp;<?php _e( 'Cluster 1', 'bp-checkins-enhanced' );?>&nbsp;
										 <input type="text" size="4" name="bpci-admin[bp-checkins-enhanced-cluster-1-size]" id="bp-checkins-enhanced-place-cluster-1-size"
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-1-size' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-1-size' ) .'"'; 
										} else {
											echo 'value="0"';
										}
										?>/>&nbsp;<?php _e( 'size', 'bp-checkins-enhanced' );?><br />
										<input type="text" name="bpci-admin[bp-checkins-enhanced-cluster-2-pin]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-2-pin' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-2-pin' ) .'"'; 
										} else {
											echo 'value="'.dirname( __FILE__ ) . '/images/cluster-2.png"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-cluster-2-pin"/> &nbsp;<?php _e( 'Cluster 2', 'bp-checkins-enhanced' );?>&nbsp;
										 <input type="text" size="4" name="bpci-admin[bp-checkins-enhanced-cluster-2-size]" id="bp-checkins-enhanced-place-cluster-2-size"
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-2-size' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-2-size' ) .'"'; 
										} else {
											echo 'value="5"';
										}
										?>/>&nbsp;<?php _e( 'size', 'bp-checkins-enhanced' );?><br />
										<input type="text" name="bpci-admin[bp-checkins-enhanced-cluster-3-pin]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-3-pin' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-3-pin' ) .'"'; 
										} else {
											echo 'value="'.dirname( __FILE__ ) . '/images/cluster-3.png"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-cluster-3-pin"/> &nbsp;<?php _e( 'Cluster 3', 'bp-checkins-enhanced' );?>&nbsp;
										 <input type="text" size="4" name="bpci-admin[bp-checkins-enhanced-cluster-3-size]" id="bp-checkins-enhanced-place-cluster-3-size"
										<?php if ( bp_get_option( 'bp-checkins-enhanced-cluster-3-size' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-cluster-3-size' ) .'"'; 
										} else {
											echo 'value="10"';
										}
										?>/>&nbsp;<?php _e( 'size', 'bp-checkins-enhanced' );?><br />
										 <?php endif; ?>
									</td>
								</tr>								
								<tr>
									<th scope="row"><?php _e( 'Infowindow', 'bp-checkins-enhanced' ) ?> (n/a)</th>
									<td>
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-infowindow]"<?php if ( (int)bp_get_option( 'bp-checkins-enhanced-infowindow' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-infowindow-yes" value="1" /> <?php _e( 'Yes', 'bp-checkins' ) ?> &nbsp;
										<input type="radio" name="bpci-admin[bp-checkins-enhanced-infowindow]"<?php if ( !(int)bp_get_option( 'bp-checkins-enhanced-infowindow' ) || '' == bp_get_option( 'bp-checkins-enhanced-infowindow' ) ) : ?> checked="checked"<?php endif; ?> id="bp-checkins-enhanced-infowindow-no" value="0" /> <?php _e( 'No', 'bp-checkins' ) ?>
									</td>
								</tr>
								<?php if( (int)bp_get_option( 'bp-checkins-enhanced-places-events' ) && '' != bp_get_option( 'bp-checkins-enhanced-places-events' ) ):?>	
								<tr>
									<th scope="row"><?php _e( 'DOM Selectors', 'bp-checkins-enhanced' );?></th>
									<td>
										<input type="text" name="bpci-admin[bp-checkins-enhanced-place-post-selector]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-place-post-selector' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-place-post-selector' ) .'"'; 
										} else {
											echo 'value=".blog-post"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-post-selector"/> &nbsp;<?php _e( 'Place post container', 'bp-checkins-enhanced' );?><br />
										<input type="text" name="bpci-admin[bp-checkins-enhanced-place-title-selector]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-place-title-selector' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-place-title-selector' ) .'"'; 
										} else {
											echo 'value=".post-title"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-title-selector"/> &nbsp;<?php _e( 'Place title container', 'bp-checkins-enhanced' );?><br />
										<input type="text" name="bpci-admin[bp-checkins-enhanced-place-thumb-selector]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-place-thumb-selector' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-place-thumb-selector' ) .'"'; 
										} else {
											echo 'value=".thumbnail"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-thumb-selector"/> &nbsp;<?php _e( 'Place thumbnail container', 'bp-checkins-enhanced' );?><br />
										 <input type="text" name="bpci-admin[bp-checkins-enhanced-place-text-selector]" 
										<?php if ( bp_get_option( 'bp-checkins-enhanced-place-text-selector' ) ){
											echo 'value="'. bp_get_option( 'bp-checkins-enhanced-place-text-selector' ) .'"'; 
										} else {
											echo 'value=".text"';
										}
										?>	 
										 id="bp-checkins-enhanced-place-text-selector"/> &nbsp;<?php _e( 'Place text container', 'bp-checkins-enhanced' );?><br />
									</td>
								</tr>
								<?php endif; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php endif; ?>
	<?php
}
add_action('bp_checkins_admin_screen_tab', 'bp_checkins_enhanced_settings_admin', 10, 2);