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
		var pin = $(this).parent().parent().find('.places-avatar').removeAttr('rel').clone().find('a.places-avatar img').attr('src');
		
		lat = Number(latlongtoparse[0]);
		lng = Number(latlongtoparse[1]);
		
		if(!bpciPosition){
			bpciPosition = new google.maps.LatLng(lat,lng);
		}
		
		arrayMarkers.push( {lat:lat, lng:lng, data:infotitle+infotext, pin:pin} );

	});
	
	function bpci_is_on_map(lat, lng, avatar){
		var onmap = -1;
		for(var i=0; i < arrayMarkers.length ; i++){
			if(arrayMarkers[i].lat == lat && arrayMarkers[i].lng == lng && arrayMarkers[i].data == infotitle+infotext)
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
		
		if( -1 == bpci_is_on_map( lat, lng, $(this).parent().parent().parent().find('.item-avatar').html() ) ) {
			add($('#bpci-map'), arrayMarkers.length, lat, lng, $(this).parent().parent().parent().find('.item-avatar').html());
			arrayMarkers.push( {lat:lat, lng:lng, data:$(this).parent().parent().parent().find('.item-avatar').html()} );
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
			  radius: 10,
			  events: { // events trigged by clusters
				mouseover: function(cluster){
				  $(cluster.main.getDOMElement()).css("border", "1px solid red");
				},
				mouseout: function(cluster){
				  $(cluster.main.getDOMElement()).css("border", "0px");
				}
			  },
			  // This style will be used for clusters with more than 0 markers
			  0: {
				content: "<div class='cluster cluster-1'>CLUSTER_COUNT</div>",
				width: 53,
				height: 52
			  },
			  5: {
				content: "<div class='cluster cluster-2'>CLUSTER_COUNT</div>",
				width: 56,
				height: 55
			  },
			  10: {
				content: "<div class='cluster cluster-3'>CLUSTER_COUNT</div>",
				width: 66,
				height: 65
			  },
			  50: {
				content: "<div class='cluster cluster-3'>CLUSTER_COUNT</div>",
				width: 66,
				height: 65
			  }
			},
			options: {
			  icon: '../images/pin.png'
			},
			events:{ // events trigged by markers
				click: function(marker){
					alert("Here is the default click event");
					if (marker.getAnimation() != null) {
						marker.setAnimation(null);
					} else {
						marker.setAnimation(google.maps.Animation.BOUNCE);
					}
				},
				mouseover: function(marker, event, context){
					$(this).gmap3(
					  {clear:"overlay"},
					  {
					  overlay:{
						latLng: marker.getPosition(),
						options:{
							content: '<div class="bpci-avatar"><s></s><i></i><span>' + context.data + '</span></div>',
							offset:{
								y:-40,
								x:10
								}
						}
					  }
					});
				},
				mouseout: function(){
					$(this).gmap3({clear:"overlay"});
				}
			}
		}
	});
  
  function add($this, i, lat, lng, data){
    $this.gmap3(
    { marker: {
		latLng: [lat, lng],
		options:{
			icon: data
			}
		}
    },
    { overlay: {
		  latLng: [lat, lng],
		  options:{
			content: '<div class="bpci-avatar"><s></s><i></i><span>' + data + '</span></div>',
			offset:{
				y:-40,
				x:10
				}
			}
		}
    },
	{ action:'clear', name:'marker'});
  }
  
});