jQuery(document).ready(function($){
	var latLong,content,bpciPosition;
	var arrayMarkers = new Array();
	
	if( typeof displayedUserLat !=='undefined' && typeof displayedUserLng !=='undefined' ){
		bpciPosition = new google.maps.LatLng(displayedUserLat,displayedUserLng);
		arrayMarkers.push( {lat:displayedUserLat, lng:displayedUserLng, data:$("#item-header-avatar").html()} );
	}
	
	$("#members-list .action .activity-checkin").each(function(){
		
		var latlongtoparse = $(this).find("a").attr('rel').split(',');
		var avatar = $(this).parent().parent().find('.item-avatar').html();
		
		lat = Number(latlongtoparse[0]);
		lng = Number(latlongtoparse[1]);
		
		if(!bpciPosition){
			bpciPosition = new google.maps.LatLng(lat,lng);
		}
		
		arrayMarkers.push( {lat:lat, lng:lng, data:avatar} );

	});
	
	function bpci_is_on_map(lat, lng, avatar){
		var onmap = -1;
		for(var i=0; i < arrayMarkers.length ; i++){
			if(arrayMarkers[i].lat == lat && arrayMarkers[i].lng == lng && arrayMarkers[i].data == avatar)
				onmap=i;
		}
		return onmap;
	}
	
	$(".link-checkin").live('click', function(){
		
		var latlongtoparse = $(this).attr('rel').split(',');
		
		lat = Number(latlongtoparse[0]);
		lng = Number(latlongtoparse[1]);
		
		var latLong = new google.maps.LatLng(lat, lng);
		
		map = $('#bpci-map').gmap3("get");
		//map = $('#bpci-map').gmap3({ action:'get', name:'map'});
		
		if( -1 == bpci_is_on_map( lat, lng, $(this).parent().parent().parent().find('.item-avatar').html() ) ) {
			add($('#bpci-map'), arrayMarkers.length, lat, lng, $(this).parent().parent().parent().find('.item-avatar').html());
			arrayMarkers.push( {lat:lat, lng:lng, data:$(this).parent().parent().parent().find('.item-avatar').html()} );
		}
		
		//map = $('#bpci-map').gmap3({ action:'get', name:'map'});
		//map.setCenter(latLong);
		map = $('#bpci-map').gmap3("get");
		$('#bpci-map').gmap3(		
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
	
	$("#bpci-map_container").append('<div id="bpci-map"></div>');
	$("#bpci-map").css('width','100%');
	$("#bpci-map").css('height','360px');
	
	var newMarkers = new Array();
	var newOverlays = new Array();
	for (var i=0; i < arrayMarkers.length ; i++ ) {
		//alert(arrayMarkers[i].data);
		newMarkers[i] = {
			latLng:[arrayMarkers[i].lat, arrayMarkers[i].lng], 
			data: arrayMarkers[i].data,
			options:{icon: arrayMarkers[i].pin}
			}
		newOverlays[i] = {
			latLng:[arrayMarkers[i].lat, arrayMarkers[i].lng], 
			data: arrayMarkers[i].data,
			options:{content: '<div class="bpci-avatar"><s></s><i></i><span>' + arrayMarkers[i].data + '</span></div>',
						offset:{
							y:-40,
							x:10
							}
					}
			}
	}
  $('#bpci-map').gmap3(
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
		  values: newOverlays
		}    
	});
  
  function add($this, i, lat, lng, data){
    $this.gmap3({
			overlay: { 
				latLng:[lat, lng], 
				data: data,
				options:{content: '<div class="bpci-avatar"><s></s><i></i><span>' + data + '</span></div>',
							offset:{
								y:-40,
								x:10
							}
				}
			}
	});
  }
	
});