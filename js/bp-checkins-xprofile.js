jQuery(document).ready(function($){
	var position, adresse, buttonAction, geocoder, buttonTitle;
	buttonTitle = bp_checkins_dir_vars.addMapViewTitle;
	$("#profile-edit-form .editfield.field_location").append('<a href="#" id="bpci-position-me" title="'+bp_checkins_dir_vars.addCheckinTitle+'"><span>'+bp_checkins_dir_vars.addCheckinTitle+'</span></a><a href="#" id="bpci-polaroid" title="'+bp_checkins_dir_vars.addPolaTitle+'"><span>'+bp_checkins_dir_vars.addPolaTitle+'</span></a>');
	
	if( ( !$.cookie("bp-ci-data-delete") || $.cookie("bp-ci-data-delete").indexOf('delete') == -1 ) && $.cookie("bp-ci-data") && $.cookie("bp-ci-data").length > 8){
		$("#bpci-position-me").addClass('disabled');
		var tempPositionToParse = $.cookie("bp-ci-data").split('|');
		position = new google.maps.LatLng(tempPositionToParse[0], tempPositionToParse[1]);
		adresse = tempPositionToParse[2];
		buttonAction = 'show';
		buttonTitle = bp_checkins_dir_vars.addMapViewTitle;
		$("#profile-edit-form .editfield.field_location").append('<div id="bpci-position-inputs"><input type="hidden" name="bpci-lat" id="bpci-lat" value="'+position.lat()+'"><input type="hidden" name="bpci-lng" id="bpci-lng" value="'+position.lng()+'"><input type="text" readonly value="'+adresse+'" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_dir_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_dir_vars.modCheckinTitle+'"><span>'+bp_checkins_dir_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-refresh-position" class="map-action" title="'+bp_checkins_dir_vars.refreshCheckinTitle+'"><span>'+bp_checkins_dir_vars.refreshCheckinTitle+'</span></a><div id="bpci-map" class="map-hide"></div></div>');
		
	} else {
		$("#bpci-position-me").removeClass('disabled');
	}
	$("#bpci-position-me").click(function(){
		
		if( $.cookie("bp-ci-data") ) {
			$.cookie("bp-ci-data-delete", '', { path: '/' });
			$("#bpci-position-me").addClass('disabled');
			if( !position ){
				var tempPositionToParse = $.cookie("bp-ci-data").split('|');
				position = new google.maps.LatLng(tempPositionToParse[0], tempPositionToParse[1]);
				adresse = tempPositionToParse[2];
				buttonAction = 'show';
				buttonTitle = bp_checkins_dir_vars.addMapViewTitle;
				$("#profile-edit-form .editfield.field_location").append('<div id="bpci-position-inputs"><input type="hidden" name="bpci-lat" id="bpci-lat" value="'+position.lat()+'"><input type="hidden" name="bpci-lng" id="bpci-lng" value="'+position.lng()+'"><input type="text" readonly value="'+adresse+'" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_dir_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_dir_vars.modCheckinTitle+'"><span>'+bp_checkins_dir_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-refresh-position" class="map-action" title="'+bp_checkins_dir_vars.refreshCheckinTitle+'"><span>'+bp_checkins_dir_vars.refreshCheckinTitle+'</span></a><div id="bpci-map" class="map-hide"></div></div>');
			}
			
			return false;
		}
		
		if( $("#bpci-position-me").hasClass('disabled') != true ){
			$(this).parent().append('<div id="bpci-position-inputs"><span class="bpci-loader">loading...</span></div>');
			$("#bpci-position-me").addClass('disabled');
			buttonAction = 'show';

			$('#bpci-position-inputs').gmap3({
				action : 'geoLatLng',
		        callback : function(latLng){
					if(latLng){
						position = latLng;
						$(this).gmap3({
							action:'getAddress',
		                    latLng:latLng,
		                    callback:function(results){
								adresse = results && results[1] ? results && results[1].formatted_address : 'no address';
								$.cookie("bp-ci-data", latLng.lat()+"|"+latLng.lng()+"|"+adresse, { path: '/' });
								$.cookie("bp-ci-data-delete", '', { path: '/' });
								$("#bpci-position-inputs").html('<input type="hidden" name="bpci-lat" id="bpci-lat" value="'+latLng.lat()+'"><input type="hidden" name="bpci-lng" id="bpci-lng" value="'+latLng.lng()+'"><input type="text" readonly value="'+adresse+'" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_dir_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_dir_vars.modCheckinTitle+'"><span>'+bp_checkins_dir_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-refresh-position" class="map-action" title="'+bp_checkins_dir_vars.refreshCheckinTitle+'"><span>'+bp_checkins_dir_vars.refreshCheckinTitle+'</span></a><div id="bpci-map" class="map-hide"></div>');
							}
						});
					} else {
						buttonAction = 'search';
						buttonTitle = bp_checkins_dir_vars.addMapSrcTitle;
						$("#bpci-position-inputs").html('<input type="hidden" name="bpci-lat" id="bpci-lat"><input type="hidden" name="bpci-lng" id="bpci-lng"><input type="text" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_dir_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_dir_vars.modCheckinTitle+'"><span>'+bp_checkins_dir_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-refresh-position" class="map-action" title="'+bp_checkins_dir_vars.refreshCheckinTitle+'"><span>'+bp_checkins_dir_vars.refreshCheckinTitle+'</span></a><div id="bpci-map" class="map-hide"></div>');
						alert(bp_checkins_dir_vars.html5LocalisationError);
						$("#bpci-address").focus();
					}
				}
			});
			
		}
		
		return false;
	});
	
	
	$('#bpci-show-on-map').live( 'click', function(){
		$("#bpci-map").show();
		
		if( buttonAction == 'show' ) {
			$("#bpci-map").gmap3({
	            action: 'addMarker', 
	            latLng:position,
				map:{
					center: position,
					zoom: 16
				}
			},
			{
				action : 'clear',
				name: 'marker'
			},
			{ action:'addOverlay',
	          latLng: position,
	          options:{
	            content: '<div class="bpci-avatar"><s></s><i></i><span>' + $("#whats-new-avatar").html() + '</span></div>',
	            offset:{
	              y:-40,
	              x:10
	            }
	          }
			});
		} else if( buttonAction == 'search' ) {
			address = $('#bpci-address').val();

			bpci_search_position( address, '#bpci-map', '#bpci-address', '#bpci-lat', '#bpci-lng', true, $("#whats-new-avatar").html() );
		}
		
		return false;
	});
	
	$('#bpci-place-show-on-map').click(function(){
		address = $('#bpci-place-address').val();
		
		$("#bpci-place-map").show();
		
		if( $('#new-place-avatar').length )
			avatar = $('#new-place-avatar').html();
		else
			avatar = $("#whats-new-avatar").html();

		bpci_search_position( address, '#bpci-place-map', '#bpci-place-address', '#bpci-place-lat', '#bpci-place-lng', false, avatar );
		
		return false;
	});
	
	function bpci_search_position( address, map, addressField, latField, lngField, cookie, avatar ) {
		geocoder = new google.maps.Geocoder();

		geocoder.geocode( { 'address': address}, function(results, status) {
		    /* Si l'adresse a pu être géolocalisée */
		    if (status == google.maps.GeocoderStatus.OK) {
		     /* Récupération de sa latitude et de sa longitude */
		     var glat = results[0].geometry.location.lat();
		     var glng = results[0].geometry.location.lng();
		     position = new google.maps.LatLng(glat, glng);

			$(map).gmap3({
	            action: 'addMarker', 
	            latLng:position,
				map:{
					center: position,
					zoom: 16
				},
				callback : function(marker){
					$(this).gmap3({
	                    action:'getAddress',
	                    latLng:marker.getPosition(),
	                    callback:function(results){
	                      	adresse = results && results[1] ? results && results[1].formatted_address : 'no address';
	
							$(addressField).val(adresse);
							$(addressField).attr("readonly","readonly");
							
							$(latField).val( position.lat() );
							$(lngField).val( position.lng() );
							
							if( cookie ) {
								$.cookie("bp-ci-data", position.lat()+"|"+position.lng()+"|"+adresse, { path: '/' });
								$.cookie("bp-ci-data-delete", '', { path: '/' });
								buttonAction = 'show';
								$('#bpci-show-on-map').attr('title',bp_checkins_dir_vars.addMapViewTitle);
							}
	                    }
	                  });
				}
			},
			{
				action : 'clear',
				name: 'marker'
			},
			{ action:'addOverlay',
	          latLng: position,
	          options:{
	            content: '<div class="bpci-avatar"><s></s><i></i><span>' + avatar + '</span></div>',
	            offset:{
	              y:-40,
	              x:10
	            }
	          }
			});

		     } else {
		      alert( bp_checkins_dir_vars.addErrorGeocode+": " + status);
		     }
		    });
	}
	
	$('#bpci-mod-position').live( 'click', function(){
		$("#bpci-map").gmap3({
			action : 'clear',
			name: 'overlay'
		});
		$("#bpci-map").hide();
		buttonAction = 'search';
		$('#bpci-show-on-map').attr('title', bp_checkins_dir_vars.addMapSrcTitle);
		$("#bpci-address").val("");
		$("#bpci-address").attr("readonly",false);
		$("#bpci-address").focus();
		/* need to write over this cookie
		$.cookie("bp-ci-data", null);*/
		return false;
	});
	
	$('#bpci-refresh-position').live( 'click', function(){
		$("#bpci-map").gmap3({
			action : 'clear',
			name: 'overlay'
		});
		
		$('#bpci-position-inputs').remove();
		$("#bpci-map").hide();
		$("#bpci-position-me").removeClass('disabled')
		$.cookie("bp-ci-data", '', { path: '/' });		
		$("#bpci-position-me").trigger('click');
		return false;
	});
	
	$("#bpci-position-me").trigger('click');
		
	$('.bp-checkins-whats-new').click(function(){
		if( !$.cookie("bp-ci-data") ){
			alert( bp_checkins_dir_vars.pleaseLocalizeU);
		} else {
			$(this).attr('contenteditable', 'true');
		}
	});
	
	$("input#aw-whats-new-submit").click( function() {
		$("#bpci-map").hide();
	});
	
	
	$("input#bpci-whats-new-submit").click( function() {
		$("#bpci-map").hide();
		var button = $(this);
		var form = button.parent().parent().parent().parent();
		var textareaCheckin;

		form.children().each( function() {
			if( $.nodeName(this, "input") ) {
				$(this).prop( 'disabled', true );
			}
			if($(this).attr('id') == "whats-new-content") {
				textareaCheckin = $(this).find(".bp-checkins-whats-new");
				textareaCheckin.attr('contenteditable', false);
			}
		});
		
		textareaCheckin.find('#bpci-to-remove').remove();

		/* Remove any errors */
		$('div.error').remove();
		button.addClass('loading');
		button.prop('disabled', true);

		/* Default POST values */
		var object = 'checkin';
		var item_id = $("#whats-new-post-in").val();
		var content = textareaCheckin.html();

		/* Set object for non-profile posts */
		if ( item_id > 0 ) {
			object = $("#whats-new-post-object").val();
		}

		$.post( ajaxurl, {
			action: 'post_checkin',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_post_checkin': $("input#_wpnonce_post_checkin").val(),
			'content': content,
			'object': object,
			'item_id': item_id
		},
		function(response) {

			form.children().each( function() {
				if( $.nodeName(this, "input") ) {
					$(this).prop( 'disabled', false );
				}
				if($(this).attr('id') == "whats-new-content") {
					textareaCheckin = $(this).find(".bp-checkins-whats-new");
					textareaCheckin.attr('contenteditable', true);
				}
			});

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				form.prepend( response.substr( 2, response.length ) );
				$( 'form#' + form.attr('id') + ' div.error').hide().fadeIn( 200 );
			} else {
				if ( 0 == $("ul.activity-list").length ) {
					$("div.error").slideUp(100).remove();
					$("div#message").slideUp(100).remove();
					$("div.activity").append( '<ul id="activity-stream" class="activity-list item-list">' );
				}

				$("ul#activity-stream").prepend(response);
				$("ul#activity-stream li:first").addClass('new-update');

				$("li.new-update").hide().slideDown( 300 );
				$("li.new-update").removeClass( 'new-update' );
				$("#bpci-polaroid").removeClass( 'disabled' );
				textareaCheckin.html('');
			}

			$("#whats-new-options").animate({height:'0px'});
			$("form#whats-new-form .bp-checkins-whats-new").animate({height:'40px'});
			$("#bpci-whats-new-submit").prop("disabled", false).removeClass('loading');
		});

		return false;
	});
	
	
	/* Checkins Loop Requesting */
	function bp_checkins_request(scope, filter) {
		
		/* Save the type and filter to a session cookie */
		$.cookie( 'bp-checkins-scope', scope, {path: '/'} );
		$.cookie( 'bp-'+scope+'-filter', filter, {path: '/'} );
		$.cookie( 'bp-activity-oldestpage', 1, {path: '/'} );
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
			});

			/* Update the feed link */
			if ( null != response.feed_url )
				$('.directory div#subnav li.feed a, .home-page div#subnav li.feed a').attr('href', response.feed_url);

			$('div.checkins-type-tabs li.selected').removeClass('loading');

		}, 'json' );
	}
		
	
});