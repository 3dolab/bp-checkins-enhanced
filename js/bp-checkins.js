jQuery(document).ready(function($){
	var position, adresse, buttonAction, geocoder, buttonTitle;
	var latLong,content,bpciPosition;
	var arrayMarkers = new Array();
	
	if( typeof displayedUserLat !=='undefined' && typeof displayedUserLng !=='undefined' ){
		bpciPosition = new google.maps.LatLng(displayedUserLat,displayedUserLng);
		arrayMarkers.push( {lat:displayedUserLat, lng:displayedUserLng, data:$("#item-header-avatar").html()} );
	}
	
	buttonTitle = bp_checkins_vars.addMapViewTitle;
	$("#whats-new-textarea").append('<a href="#" id="bpci-position-me" title="'+bp_checkins_vars.addCheckinTitle+'"><span>'+bp_checkins_vars.addCheckinTitle+'</span></a>');
	
	if( ( !$.cookie("bp-ci-data-delete") || $.cookie("bp-ci-data-delete").indexOf('delete') == -1 ) && $.cookie("bp-ci-data") && $.cookie("bp-ci-data").length > 8){
		$("#bpci-position-me").addClass('disabled');
		var tempPositionToParse = $.cookie("bp-ci-data").split('|');
		position = new google.maps.LatLng(tempPositionToParse[0], tempPositionToParse[1]);
		adresse = tempPositionToParse[2];
		buttonAction = 'show';
		buttonTitle = bp_checkins_vars.addMapViewTitle;
		$("#whats-new-textarea").append('<div id="bpci-position-inputs"><input type="hidden" name="bpci-lat" id="bpci-lat" value="'+position.lat()+'"><input type="hidden" name="bpci-lng" id="bpci-lng" value="'+position.lng()+'"><input type="text" readonly value="'+adresse+'" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_vars.modCheckinTitle+'"><span>'+bp_checkins_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-reset-position" class="map-action" title="'+bp_checkins_vars.resetCheckinTitle+'"><span>'+bp_checkins_vars.resetCheckinTitle+'</span></a></div>');
		
	} else {
		$("#bpci-position-me").removeClass('disabled');
	}
	$("#bpci-position-me").click(function(){
		if( $("#bpci-position-me").hasClass('disabled') != true ){
			$(this).parent().append('<div id="bpci-position-inputs"><span class="bpci-loader">loading...</span></div>');
			$("#bpci-position-me").addClass('disabled');
			buttonAction = 'show';

			$('#bpci-position-inputs').gmap3({
				getgeoloc:{
					callback : function(latLng){
						if(latLng){
							position = latLng;
							$(this).gmap3({
								getaddress:{
									latLng:latLng,
									callback:function(results){
										adresse = results && results[1] ? results && results[1].formatted_address : 'no address';
										$.cookie("bp-ci-data", latLng.lat()+"|"+latLng.lng()+"|"+adresse, { path: '/' });
										$.cookie("bp-ci-data-delete", '', { path: '/' });
										$("#bpci-position-inputs").html('<input type="hidden" name="bpci-lat" id="bpci-lat" value="'+latLng.lat()+'"><input type="hidden" name="bpci-lng" id="bpci-lng" value="'+latLng.lng()+'"><input type="text" readonly value="'+adresse+'" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_vars.modCheckinTitle+'"><span>'+bp_checkins_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-reset-position" class="map-action" title="'+bp_checkins_vars.resetCheckinTitle+'"><span>'+bp_checkins_vars.resetCheckinTitle+'</span></a>');
									}
								}
							});
						} else {
							buttonAction = 'search';
							buttonTitle = bp_checkins_vars.addMapSrcTitle;
							$("#bpci-position-inputs").html('<input type="hidden" name="bpci-lat" id="bpci-lat"><input type="hidden" name="bpci-lng" id="bpci-lng"><input type="text" id="bpci-address" name="bpci-address" placeholder="'+bp_checkins_vars.addressPlaceholder+'"><a href="#" id="bpci-show-on-map" class="map-action" title="'+buttonTitle+'"><span>'+buttonTitle+'</span></a><a href="#" id="bpci-mod-position" class="map-action" title="'+bp_checkins_vars.modCheckinTitle+'"><span>'+bp_checkins_vars.modCheckinTitle+'</span></a><a href="#" id="bpci-reset-position" class="map-action" title="'+bp_checkins_vars.resetCheckinTitle+'"><span>'+bp_checkins_vars.resetCheckinTitle+'</span></a>');
							alert(bp_checkins_vars.html5LocalisationError);
							$("#bpci-address").focus();
						}
					}
				}
			});
			
		}
		
		return false;
	});
	
	
	$('#bpci-show-on-map').live( 'click', function(){
		$("#bpci-map").show();
		
		if( buttonAction == 'show' ) {
			$("#bpci-map").gmap3(
			{ map: { 
				options: { 
					center:position,
					zoom: 6,
					mapTypeId: google.maps.MapTypeId.TERRAIN
				},
			},
			overlay: {
			  latLng: position,
				  options:{
					content: '<div class="bpci-avatar"><s></s><i></i><span>' + $("#whats-new-avatar").html() + '</span></div>',
					offset:{
					  y:-40,
					  x:10
					}
				}
			}			
		});
			
		} else if( buttonAction == 'search' ) {
			address = $('#bpci-address').val();

			geocoder = new google.maps.Geocoder();

			geocoder.geocode( { 'address': address}, function(results, status) {
			    /* Si l'adresse a pu être géolocalisée */
			    if (status == google.maps.GeocoderStatus.OK) {
			     /* Récupération de sa latitude et de sa longitude */
			     var glat = results[0].geometry.location.lat();
			     var glng = results[0].geometry.location.lng();
			     position = new google.maps.LatLng(glat, glng);

				$('#bpci-map').gmap3(
					{ map: { 
						options: { 
							center:position,
							zoom: 6,
							mapTypeId: google.maps.MapTypeId.TERRAIN,
							callback:function(map){
								$(this).gmap3({
									getaddress:{
									latLng:map.getPosition(),
										callback:function(results){
											adresse = results && results[1] ? results && results[1].formatted_address : 'no address';
											$('#bpci-address').val(adresse);
											$("#bpci-address").attr("readonly","readonly");
											$("#bpci-lat").val( position.lat() );
											$("#bpci-lng").val( position.lng() );
											$.cookie("bp-ci-data", position.lat()+"|"+position.lng()+"|"+adresse, { path: '/' });
											$.cookie("bp-ci-data-delete", '', { path: '/' });
											buttonAction = 'show';
											$('#bpci-show-on-map').attr('title',bp_checkins_vars.addMapViewTitle);
										}
									}
								  });
							}
						}
					},
					overlay: {
					  latLng: position,
						  options:{
							content: '<div class="bpci-avatar"><s></s><i></i><span>' + $("#whats-new-avatar").html() + '</span></div>',
							offset:{
							  y:-40,
							  x:10
							}
						} 
					}
				});

			     } else {
			      alert( bp_checkins_vars.addErrorGeocode+": " + status);
			     }
			    });
		}
		
		return false;
	});
	
	$('#bpci-mod-position').live( 'click', function(){
		$("#bpci-map").gmap3({
				clear: {
					//last: true,
					name: overlay
				}
		});
		$("#bpci-map").hide();
		buttonAction = 'search';
		$('#bpci-show-on-map').attr('title',bp_checkins_vars.addMapSrcTitle);
		$("#bpci-address").val("");
		$("#bpci-address").attr("readonly",false);
		$("#bpci-address").focus();
		/* need to write over this cookie
		$.cookie("bp-ci-data", null);*/
		return false;
	});
	
	$('#bpci-reset-position').live( 'click', function(){
		$("#bpci-map").gmap3({
				clear: {
					//last: true,
					name: overlay
				}
		});
		$("#bpci-position-me").removeClass('disabled');
		$('#bpci-position-inputs').remove();
		buttonAction = 'show';
		buttonTitle = bp_checkins_vars.addMapViewTitle;
		$.cookie("bp-ci-data-delete", 'delete', { path: '/' });
		return false;
	});
	
	$("input#aw-whats-new-submit").click( function() {
		$("#bpci-map").hide();
	});
	
	$('.bp-ci-zoompic').live('click', function(){
		
		if( $(this).find('.thumbnail').attr('width') != "100%" ){
			var thumb = $(this).find('.thumbnail').attr('src');
			var full = $(this).attr('href');
			$(this).find('.thumbnail').attr('src', full);
			$(this).attr('href', thumb);
			$('#footer').append('<div id="bpci-full" style="visibility:hidden"><img  src="'+full+'"></div>');
			var reverseh = $('#bpci-full img').height();
			var reversew = $('#bpci-full img').width();
			var ratio = Number( reverseh / reversew );
			$(this).find('.thumbnail').attr('width', '100%');
			//$(this).find('.thumbnail').attr('height', '100%');
			$(this).find('.thumbnail').css('max-width', '100%');
			$(this).find('.thumbnail').attr('height', Number(ratio * $(this).find('.thumbnail').width() ) +'px');
			$('#footer #bpci-full').remove();
			return false;
		} else {
			var full = $(this).find('.thumbnail').attr('src');
			var thumb = $(this).attr('href');
			$(this).find('.thumbnail').attr('src', thumb);
			$(this).attr('href', full);
			$('#footer').append('<div id="bpci-thumb" style="visibility:hidden"><img  src="'+thumb+'"></div>');
			var reverseh = $('#bpci-thumb img').height();
			var reversew = $('#bpci-thumb img').width();
			var ratio = Number( reverseh / reversew );
			$(this).find('.thumbnail').attr('width', '100px');
			$(this).find('.thumbnail').attr('height', Number(ratio * 100) +'px');
			$('#footer #bpci-thumb').remove();
			return false;
		}
		return false;
	});
	
	function bpce_init_places_map(){
		
		var map_selector = "bpci-map";
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
		bpce_add_places_to_map();
	}
	function bpce_add_places_to_map(scope){
		var arrayMarkers = new Array();
		var arrayOvers = new Array();
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
			if($(this).parent().parent().hasClass('places'))
				scope = 'places';
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
					arrayOvers.push( {lat:lat, lng:lng, data:avatar} );
			}
		});
	
		for (var i=0; i < arrayMarkers.length ; i++ ) {
			newMarkers[i] = {
					latLng:[arrayMarkers[i].lat, arrayMarkers[i].lng], 
					data: arrayMarkers[i].data,
					options:{	icon: arrayMarkers[i].pin}
			}
		}
		for (var i=0; i < arrayOvers.length ; i++ ) {
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

		if ( scope == 'places' ) { 
				//alert('nM'+newMarkers.length);
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
		bpce_init_places_map();
});