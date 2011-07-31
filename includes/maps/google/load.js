/**
 * Every Calendar +1 WordPress Plugin
 *
 * Google Maps plugin for the maps interface.
 */

// Global state holder that indicates the script loaded successfully
var _ecp1GoogleGoodToGo = false;
var _ecp1GoogleCallback = false;

// Global array of map instances for controlling when events occur
var _ecp1GoogleMapCounter = 0;
var _ecp1GoogleMapInstances = new Array();

// Global reference to a Geocoder instance (for efficiency)
var _ecp1GoogleGeocoder = false;

// Called on page load
function InitGoogleMaps( _finishCallback ) {
	// Store the callback if one is given
	if ( typeof _finishCallback != "undefined" && _finishCallback ) 
		_ecp1GoogleCallback = _finishCallback;
	
	// Adapted from Googles Async load example
	var script = document.createElement("script");
	script.type = "text/javascript";
	script.src = "http://maps.googleapis.com/maps/api/js?v=3.4&sensor=false&callback=_ecp1GoogleMapsReady";
	document.body.appendChild( script )
}

// Helper to track library load
function _ecp1GoogleMapsReady() {
	_ecp1GoogleGoodToGo = true;
	if ( _ecp1GoogleCallback )
		_ecp1GoogleCallback();
}

// Called when a map needs to be rendered
// There are two forms of this function one that takes coords
// which is this function, and the other that takes a text 
// address and geocodes it. Once geocoding is complete this
// function is called to render the map.
function RenderGoogleMap( ElementID, Lat, Lng, DisplayMarker ) {
	var myLatlng = new google.maps.LatLng(Lat, Lng);
	var myOptions = { zoom: 7, center: myLatlng, mapTypeId: google.maps.MapTypeId.ROADMAP };
	_ecp1GoogleMapInstances[_ecp1GoogleMapCounter] = new google.maps.Map( document.getElementById( ElementID ), myOptions );
	_ecp1GoogleMapCounter += 1;
}

// Called when a map needs to be rendered (and first geocoded)
function RenderGoogleMap( ElementID, Location, DisplayMarker ) {
	if ( ! _ecp1GoogleGeocoder )
		_ecp1GoogleGeocoder = new google.maps.Geocoder();
	
	_ecp1GoogleGeocoder.geocode( { 'address': Location }, function( results, status ) {
		if ( google.maps.GeocoderStatus.OK == status ) {
			var _ll = results[0].geometry.location;
			try {
				// TODO: are the variables scoped like this???
				RenderGoogleMap( ElementID, _ll.lat(), _ll.lng(), DisplayMarker );
			} catch( rnd_ex ) {
				alert( rnd_ex );
			}
		}
	} );
}