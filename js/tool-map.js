//<![CDATA[
//google.load("maps", "2.x");

    function initialize() {
      if (GBrowserIsCompatible()) {

        var map = new google.maps.Map2(document.getElementById("map"));
        var center = new GLatLng(49.58362096856756, 11.011413568630815);
        map.setCenter(center, 10);

        map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());
		map.setMapType(G_NORMAL_MAP);
        map.enableDoubleClickZoom();

        var marker = new GMarker(center);
        map.addOverlay(marker);
		map.setMapType(G_NORMAL_MAP);
        var lat = CPTD(center.y.toString(), 'lat');
        var lng = CPTD(center.x.toString(), 'lng');
        var latLngStr = '<br/>Latitude:&nbsp;&nbsp;&nbsp;' + center.y.toString() + '<br/> Longitude: ' + center.x.toString();
		var latStr = '' + center.y.toString();
   		var lngStr = '' + center.x.toString();	
		
        marker.openInfoWindowHtml(latLngStr);

		document.getElementById('geo-lat').value = latStr;
		document.getElementById('geo-long').value = lngStr;
		
		

        marker.openInfoWindowHtml(latLngStr);


        geocoder = new GClientGeocoder();

        GEvent.addListener(map, "click", function(marker, point) {
           map.clearOverlays();
           map.panTo(point);
           var marker = new GMarker(point);
           map.addOverlay(marker);
		   map.setMapType(G_NORMAL_MAP);
           var lat = CPTD(point.y.toString(), 'lat');
           var lng = CPTD(point.x.toString(), 'lng');
           var latLngStr = '<br/>Latitude:&nbsp;&nbsp;&nbsp;' + point.y.toString() + '<br/> Longitude: ' + point.x.toString();
		   var latStr = '' + point.y.toString();
   		   var lngStr = '' + point.x.toString();	
           marker.openInfoWindowHtml(latLngStr);

		   document.getElementById('geo-lat').value = latStr;
		   document.getElementById('geo-long').value = lngStr;
        });

      }
    }

    function showAddress(address) {
      if (geocoder) {
        geocoder.getLatLng(
          address,
          function(point) {
            if (!point) {
              alert(address + " nicht gefunden");
            } else {
              var map = new google.maps.Map2(document.getElementById("map"));
              map.setCenter(point, 16);
              map.addControl(new GSmallMapControl());
              map.addControl(new GMapTypeControl());
              map.setMapType(G_NORMAL_MAP);
              map.enableDoubleClickZoom();
              var marker = new GMarker(point);
              map.addOverlay(marker);
              var lat = CPTD(point.y.toString(), 'lat');
              var lng = CPTD(point.x.toString(), 'lng');
              var latLngStr = '<br/>Latitude:&nbsp;&nbsp;&nbsp;' + point.y.toString() + '<br/> Longitude: ' + point.x.toString();
			  var latStr = '' + point.y.toString();
   		      var lngStr = '' + point.x.toString();	
              marker.openInfoWindowHtml(latLngStr);

		   	  document.getElementById('geo-lat').value = latStr;
		   	  document.getElementById('geo-long').value = lngStr;
        geocoder = new GClientGeocoder();

        GEvent.addListener(map, "click", function(marker, point) {
           map.clearOverlays();
           map.panTo(point);
           var marker = new GMarker(point);
		   map.setMapType(G_NORMAL_MAP);
           map.addOverlay(marker);
           var latLngStr = '<br/>Latitude:&nbsp;&nbsp;&nbsp;' + point.y.toString() + '<br/> Longitude: ' + point.x.toString();
		   var latStr = '' + point.y.toString();
   		   var lngStr = '' + point.x.toString();	
           marker.openInfoWindowHtml(latLngStr);
           var lat = CPTD(point.y.toString(), 'lat');
           var lng = CPTD(point.x.toString(), 'lng');
		   document.getElementById('geo-lat').value = latStr;
		   document.getElementById('geo-long').value = lngStr;
        });

            }
          }
        );
      }
    }
	
	
	function setCenter(lat, lon) {
      if (GBrowserIsCompatible()) {

        var map = new google.maps.Map2(document.getElementById("map"));
        var center = new GLatLng(lat, lon);
        map.setCenter(center, 15);

        map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());
		map.setMapType(G_NORMAL_MAP);
        map.enableDoubleClickZoom();

        var marker = new GMarker(center);
        map.addOverlay(marker);
		map.setMapType(G_NORMAL_MAP);
        var lat = CPTD(center.y.toString(), 'lat');
        var lng = CPTD(center.x.toString(), 'lng');
        var latLngStr = '<br/>Latitude:&nbsp;&nbsp;&nbsp;' + center.y.toString() + '<br/> Longitude: ' + center.x.toString();
		var latStr = '' + center.y.toString();
   		var lngStr = '' + center.x.toString();	
		
        marker.openInfoWindowHtml(latLngStr);

		document.getElementById('geo-lat').value = latStr;
		document.getElementById('geo-long').value = lngStr;
		
		

        marker.openInfoWindowHtml(latLngStr);


        geocoder = new GClientGeocoder();

        GEvent.addListener(map, "click", function(marker, point) {
           map.clearOverlays();
           map.panTo(point);
           var marker = new GMarker(point);
           map.addOverlay(marker);
		   map.setMapType(G_NORMAL_MAP);
           var lat = CPTD(point.y.toString(), 'lat');
           var lng = CPTD(point.x.toString(), 'lng');
           var latLngStr = '<br/>Latitude:&nbsp;&nbsp;&nbsp;' + point.y.toString() + '<br/> Longitude: ' + point.x.toString();
		   var latStr = '' + point.y.toString();
   		   var lngStr = '' + point.x.toString();	
           marker.openInfoWindowHtml(latLngStr);

		   document.getElementById('geo-lat').value = latStr;
		   document.getElementById('geo-long').value = lngStr;
        });

      }
    }

google.setOnLoadCallback(initialize);

function CPTD(p,t) {var r=''; var d=Math.floor(Math.abs(p)); var m=Math.floor((Math.abs(p)-d)*60); var s=(((Math.abs(p)-d)*60-m)*60).toFixed(2); var e; if (t=='lat'){if(p>0){e='N';} else {e='S';} } else {if(p>0) {e='O';} else {e='W';}} return d + '&deg; '+m+'\' '+s+'\'\' '+e;}

//]]>
