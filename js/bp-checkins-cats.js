jQuery(document).ready(function($){

	var latLong,content,bpciPosition;
	var arrayMarkers = new Array();
	
	bp_init_checkins();
	
	if( typeof displayedUserLat !=='undefined' && typeof displayedUserLng !=='undefined' ){
		bpciPosition = new google.maps.LatLng(displayedUserLat,displayedUserLng);
		arrayMarkers.push( {lat:displayedUserLat, lng:displayedUserLng, data:$("#whats-new-avatar").html()} );
	}
	
	$('.bp-ci-zoompic').live('click', function(){
		
		if( $(this).find('.thumbnail').attr('width') != "100%" ){
			var thumb = $(this).find('.thumbnail').attr('src');
			var full = $(this).attr('href');
			$(this).find('.thumbnail').attr('src', full);
			$(this).attr('href', thumb);
			$(this).find('.thumbnail').attr('width', '100%');
			$(this).find('.thumbnail').attr('height', '100%');
			$(this).find('.thumbnail').css('max-width', '100%');
			return false;
		} else {
			var full = $(this).find('.thumbnail').attr('src');
			var thumb = $(this).attr('href');
			$(this).find('.thumbnail').attr('src', thumb);
			$(this).attr('href', full);
			$(this).find('.thumbnail').attr('width', '100px');
			return false;
		}
		return false;
	});
	
	$('#places-filter-select select').live('change', function() {
		var selected_tab = $( 'div.checkins-type-tabs li.selected a' );

		if ( !selected_tab.length )
			var scope = null;
		else
			var scope = selected_tab.attr('id').replace( '-area', '' );

		var filter = $(this).val();

		bp_checkins_request(scope, filter);

		return false;
	});
	
	function bp_checkins_hide_comments() {
		if ( typeof( bp_dtheme_hide_comments ) != "undefined" )
			bp_dtheme_hide_comments();
		else
			bp_legacy_theme_hide_comments();
	}
	
	/* Checkins Loop Requesting */
	function bp_checkins_request(scope, filter) {
		
		/* Save the type and filter to a session cookie */
		$.cookie( 'bp-checkins-scope', scope, {path: '/'} );
		$.cookie( 'bp-'+scope+'-filter', filter, {path: '/'} );
		$.cookie( 'bp-checkins-oldestpage', 1, {path: '/'} );
		$.cookie( 'bp-checkins-places-oldestpage', 1, {path: '/'} );

		/* Remove selected and loading classes from tabs */
		$('div.item-list-tabs li').each( function() {
			$(this).removeClass('selected loading');
		});

		/* Set the correct selected nav and filter */
		$('a#'+scope+'-area').parent('li').addClass('selected');
		$('div.checkins-type-tabs li.selected').addClass('loading');
		$('#'+scope+'-filter-select select option[value="' + filter + '"]').prop( 'selected', true );

		if ( bp_ajax_request )
			bp_ajax_request.abort();

		bp_ajax_request = $.post( ajaxurl, {
			action: scope+'_apply_filter',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_activity_filter': jq("input#_wpnonce_activity_filter").val(),
			'scope': scope,
			'filter': filter
		},
		function(response)
		{
			$('div.activity').fadeOut( 100, function() {
				jq(this).html(response.contents);
				jq(this).fadeIn(100);
				/* Selectively hide comments */
				bp_checkins_hide_comments();
				bpce_init_places_map(scope);
			});

			/* Update the feed link */
			if ( null != response.feed_url )
				$('.directory div#subnav li.feed a, .home-page div#subnav li.feed a').attr('href', response.feed_url);

			$('div.checkins-type-tabs li.selected').removeClass('loading');

		}, 'json' );
	}
	
	$('.bpci-place-load-more a').live('click', function(){
		$(this).addClass('loading');
		var liElement = $(this).parent();

		if ( null == $.cookie('bp-checkins-places-oldestpage') )
			$.cookie('bp-checkins-places-oldestpage', 1, {path: '/'} );

		var bpci_places_oldest_page = ( $.cookie('bp-checkins-places-oldestpage') * 1 ) + 1;

		$.post( ajaxurl, {
			action: 'places_get_older_updates',
			'cookie': encodeURIComponent(document.cookie),
			'page': bpci_places_oldest_page
		},
		function(response)
		{
			liElement.removeClass('loading');
			$.cookie( 'bp-checkins-places-oldestpage', bpci_places_oldest_page, {path: '/'} );
			$("#content ul.places-list").append(response.contents);
			liElement.hide();
			bpce_add_places_to_map('places');
		}, 'json' );

		return false;
	});
	
	function bp_init_checkins() {
		/* Reset the page */
		$.cookie( 'bp-checkins-places-oldestpage', 1, {path: '/'} );

		if ( null != $.cookie('bp-places-filter') && $('#places-filter-select').length )
		 	$('#places-filter-select select option[value="' + $.cookie( 'bp-places-filter') + '"]').prop( 'selected', true );
	}
	function bpce_init_places_map(scope){
		
		if ( scope == 'places' ) {
			var map_selector = "bpci-places-map";		
		} else {
			var map_selector = "bpci-map";
		}	
		$('#bpci-map_container').html('<div id="'+map_selector+'"></div>');
		$('#'+map_selector).css('width','100%');
		$('#'+map_selector).css('height','360px');

		$('#'+map_selector).gmap3(		
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
			}
		});
		bpce_add_places_to_map(scope);
	}
	
	function bpce_add_places_to_map(scope){
		var arrayMarkers = new Array();
		var newMarkers = new Array();
		var newOverlays = new Array();
		if ( scope == 'places' ) {
			var selector = "#places-stream .places-content .activity-checkin";
			var map_selector = "bpci-places-map";
			var avatar_selector = "places-avatar";			
		} else {
			var selector = "#activity-stream .activity-content .activity-checkin";
			var map_selector = "bpci-map";
			var avatar_selector = "activity-avatar";
		}
		//alert($(selector).html());
		$(selector).each(function(){
			//alert($(this).find("a").attr('rel'));
			var georel = $(this).find("a").attr('rel');
			if(georel)
				var latlongtoparse = georel.split(',');
			//var avatar = $(this).parent().parent().find('.'+avatar_selector).html();			
			//var avatar = $(this).parent().parent().find('.places-avatar').clone().find('a.places-avatar').attr('href', function() { return $(this).attr('rel'); }).removeAttr('rel').parent().html();
			if ( scope == 'places' ){
				var reldata = $(this).parent().parent().find('a.places-avatar').attr('rel');
				if (reldata)
					var postdata = reldata.split('§');
				if (postdata)
					var avatar = $(this).parent().parent().find('a.places-avatar').removeAttr('rel').parent().clone().find('a.places-avatar').attr({href: postdata[1], title: postdata[0] }).parent().html();
				else
					var avatar = $(this).parent().parent().find('.places-avatar').html();
				var infotitle = $(this).parent().parent().find('.places-avatar').parent().find('.places-inner h4').html();
				var infotext = $(this).parent().parent().find('.places-avatar').parent().find('.places-inner .place-excerpt').html();
				var mappin = $(this).parent().parent().find('.places-avatar').removeAttr('rel').clone().find('a.places-avatar img').attr('src');
			}else{
				var hrefdata = $(this).find('a.link-checkin').attr('href');
				var titledata = $(this).find('a.link-checkin').attr('title');
				if (hrefdata)
					var avatar = $(this).parent().parent().find('.activity-avatar').clone().find('a').attr({href: hrefdata, title: titledata }).parent().html();
				else
					var avatar = $(this).parent().parent().find('.activity-avatar').html();
			}
			if(latlongtoparse){
				lat = Number(latlongtoparse[0]);
				lng = Number(latlongtoparse[1]);				
				if(!bpciPosition){
					bpciPosition = new google.maps.LatLng(lat,lng);
				}
				if( scope == 'places')
					arrayMarkers.push( {lat:lat, lng:lng, data:infotitle+infotext, pin:mappin} );
				else
					arrayMarkers.push( {lat:lat, lng:lng, data:avatar} );
			}
		});
	
		for (var i=0; i < arrayMarkers.length ; i++ ) {
			//alert(arrayMarkers[i].data);
			if ( scope == 'places' ){
				newMarkers[i] = {
					latLng:[arrayMarkers[i].lat, arrayMarkers[i].lng], 
					data: arrayMarkers[i].data,
					options:{	icon: arrayMarkers[i].pin}
					}
			}else{
				newOverlays[i] = {
					latLng:[arrayMarkers[i].lat, arrayMarkers[i].lng], 
					data: arrayMarkers[i].data,
					options:{	content: '<div class="bpci-avatar"><s></s><i></i><span>' + arrayMarkers[i].data + '</span></div>',
								offset:{
									y:-40,
									x:10
								}
					}
				}
			}
		}

		if ( scope == 'places' ) { 
			$('#'+map_selector).gmap3(		
			  { marker: { 
					 values: newMarkers,
					 cluster:{
					  radius: 50,
					  events: { // events trigged by clusters
						 click:function(cluster, event, context) {
							var map = $(this).gmap3("get");
							map.panTo(context.data.latLng);
							map.setZoom(map.getZoom()+2);
						  }
					  },
					  // This style will be used for clusters with more than 0 markers
					  0: {
						content: "<div class='cluster cluster-1'>CLUSTER_COUNT</div>",
						width: 96,
						height: 72
					  },
					  5: {
						content: "<div class='cluster cluster-2'>CLUSTER_COUNT</div>",
						width: 96,
						height: 80
					  },
					  10: {
						content: "<div class='cluster cluster-3'>CLUSTER_COUNT</div>",
						width: 96,
						height: 80
					  }
					},
					options: {
					  icon: defaultPlacePin
					},
					events:{ // events trigged by markers
						click: function(marker, event, context){
							var map = $(this).gmap3("get"),
							  infowindow = $(this).gmap3({get:{name:"infowindow"}});
							if (infowindow){
							  infowindow.open(map, marker);
							  infowindow.setContent(context.data);
							} else {
							  $(this).gmap3({
								infowindow:{
								  anchor:marker,
								  options:{content: context.data}
								}
							  });
							}
						}
					}
				}
			});
		} else { 
				//alert('nO'+newOverlays.length);
			$('#'+map_selector).gmap3(
			
			  { overlay: {
				  values: newOverlays
				}    
			});
		}	
	}
			$(".link-checkin").live('click', function(){		
			var latlongtoparse = $(this).attr('rel').split(',');			
			lat = Number(latlongtoparse[0]);
			lng = Number(latlongtoparse[1]);			
			var latLong = new google.maps.LatLng(lat, lng);			
			//map = $('#'+map_selector).gmap3({ action:'get', name:'map'});
			if ( scope == 'places' ) { 
				var infotitle = $(this).parent().parent().find('.places-avatar').parent().find('.places-inner h4').html();
				var infotext = $(this).parent().parent().find('.places-avatar').parent().find('.places-inner .place-excerpt').html();			
				if( -1 == bpci_is_on_map( lat, lng, infotitle+infotext ) ) {
					add($('#'+map_selector), arrayMarkers.length, lat, lng, $(this).parent().parent().parent().find('.item-avatar img').attr('src'), 'places');
					arrayMarkers.push( {lat:lat, lng:lng, data:infotitle+infotext, pin:$(this).parent().parent().parent().find('.item-avatar img').attr('src')} );
				}
			} else {
				if( -1 == bpci_is_on_map( lat, lng, $(this).parent().parent().parent().find('.item-avatar').html() ) ) {
					add($('#'+map_selector), arrayMarkers.length, lat, lng, $(this).parent().parent().parent().find('.item-avatar').html());
					arrayMarkers.push( {lat:lat, lng:lng, data:$(this).parent().parent().parent().find('.item-avatar').html()} );
				}
			} 			
			//map = $('#'+map_selector).gmap3({ action:'get', name:'map'});
			//map.setCenter(latLong);
			$('#'+map_selector).gmap3(		
			  { map: { 
					options: { 
						center:latLong,
						zoom: 11,
						mapTypeId: google.maps.MapTypeId.TERRAIN,
						callback:function(map){
							for (var i=0; i < arrayMarkers.length ; i++ ) {
							  add($(this), i, arrayMarkers[i].lat, arrayMarkers[i].lng, arrayMarkers[i].data);
							  map.setCenter(latLong);
							  map.setZoom(11);
							}
						}
					},
				}
			});
		});
	bpce_init_places_map('places');
});