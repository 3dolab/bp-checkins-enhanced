jQuery(document).ready(function($){
	var latLong,content,bpciPosition;
	var arrayMarkers = new Array();
	
	if( typeof displayedUserLat !=='undefined' && typeof displayedUserLng !=='undefined' ){
		bpciPosition = new google.maps.LatLng(displayedUserLat,displayedUserLng);
		arrayMarkers.push( {lat:displayedUserLat, lng:displayedUserLng, data:$("#item-header-avatar").html(), pin: displayedUserPin, data:$("#item-header-avatar").html()} );
	}
	
	$("#places-stream .places-content .activity-checkin, #content .blog-post .text .activity-checkin").each(function(){
		//alert($(this).find("a").attr('rel'));
		var latlongtoparse = $(this).find("a").attr('rel').split(',');
		//var avatar = $(this).parent().parent().find('.places-avatar').html();
		var postdata = $(this).parent().parent().find('a.places-avatar').attr('rel').split('§');
		//var avatar = $(this).parent().parent().find('.places-avatar').clone().find('a.places-avatar').attr('href', function() { return $(this).attr('rel'); }).removeAttr('rel').parent().html();
		var avatar = $(this).parent().parent().find('.places-avatar').removeAttr('rel').clone().find('a.places-avatar').attr({href: postdata[1], title: postdata[0] }).parent().html();
		var infotitle = $(this).parent().parent().find('.places-avatar').removeAttr('rel').parent().parent().parent().find('.post-title').html();
		var infotext = $(this).parent().parent().find('.places-avatar').removeAttr('rel').parent().parent().parent().find('.text').html();
		var infothumb = '';
		if($(this).parent().parent().find('.places-avatar').removeAttr('rel').parent().parent().parent().find('.thumbnail').length)
			infothumb = $(this).parent().parent().find('.places-avatar').removeAttr('rel').parent().parent().parent().find('.thumbnail').html();
		//var infotext = '';
		var mappin = $(this).parent().parent().find('.places-avatar').removeAttr('rel').clone().find('a.places-avatar img').attr('src');
		lat = Number(latlongtoparse[0]);
		lng = Number(latlongtoparse[1]);
		
		if(!bpciPosition){
			bpciPosition = new google.maps.LatLng(lat,lng);
		}
		
		arrayMarkers.push( {lat:lat, lng:lng, data:infotitle+infothumb+infotext, pin:mappin} );

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
		
		
		map = $('#bpci-map').gmap3({ action:'get', name:'map'});
		
		var infotitle = $(this).parent().parent().parent().find('.post-title').html();
		var infotext = $(this).parent().parent().parent().find('.text').html();
		var infothumb = '';
		if($(this).parent().parent().parent().find('.thumbnail').length)
			infothumb = $(this).parent().parent().parent().find('.thumbnail').html();
		var infotext = '';
		
		if( -1 == bpci_is_on_map( lat, lng, infotitle+infothumb+infotext ) ) {
			add($('#bpci-map'), arrayMarkers.length, lat, lng, infotitle+infothumb+infotext, $(this).parent().parent().parent().find('a.places-avatar img').attr('src'));
			arrayMarkers.push( { lat:lat, lng:lng, data:infotitle+infothumb+infotext, pin:$(this).parent().parent().parent().find('a.places-avatar img').attr('src') } );
		}
		
		map = $('#bpci-map').gmap3({ action:'get', name:'map'});
		map.setCenter(latLong);
		
	});
	
	$("#bpci-map_container").append('<div id="bpci-map"></div>');
	$("#bpci-map").css('width','100%');
	$("#bpci-map").css('height','360px');
	
	//var myJsonString = JSON.stringify(arrayMarkers);
	var newMarkers = new Array();
	for (var i=0; i < arrayMarkers.length ; i++ ) {
				newMarkers[i] = {
					latLng:[arrayMarkers[i].lat, arrayMarkers[i].lng], 
					data: arrayMarkers[i].data,
					options:{icon: arrayMarkers[i].pin}
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
		marker: { 
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
  
  function add($this, i, lat, lng, data, pin){
   $this.gmap3({
			marker: { 
				latLng:[lat, lng], 
				data: data, 
				options: {
					icon: pin
				}
			}
	});
  }
  
});