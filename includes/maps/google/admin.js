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
var _ecp1GoogleMarkers = new Array();

// Global reference to a Geocoder instance (for efficiency)
var _ecp1GoogleGeocoder = false;

// Global map of element id to map instance index
var _ecp1GoogleMapElements = new Array();

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
	alert( 'callback running' );
	if ( _ecp1GoogleCallback ) {
		alert( 'custom callback call' );
		_ecp1GoogleCallback();
	}
}

// Called when a map needs to be rendered
// There are two forms of this function one that takes coords
// which is this function, and the other that takes a text 
// address and geocodes it. Once geocoding is complete this
// function is called to render the map.
function _RenderGoogleMap( ElementID, Zoom, Lat, Lng, DisplayMarker ) {
alert( '_RENDER: ' + Lat + ' ' + Lng + ' @ ' + Zoom ); return;
	var myLatLng = new google.maps.LatLng(Lat, Lng);
	var myOptions = { zoom: Zoom, center: myLatLng, mapTypeId: google.maps.MapTypeId.ROADMAP, disableDefaultUI:true, zoomControl:true };
	var myElement = document.getElementById( ElementID );
	if ( typeof myElement == 'null' || typeof myElement == 'undefined' )
		throw "Non-existent Element ID provided to RenderGoogleMap";

	_ecp1GoogleMapInstances[_ecp1GoogleMapCounter] = new google.maps.Map( document.getElementById( ElementID ), myOptions );
	_ecp1GoogleMapElements[_ecp1GoogleMapCounter] = { index:_ecp1GoogleMapCounter, element:ElementID };
	if ( typeof DisplayMarker != 'undefined' && DisplayMarker ) {
		_ecp1GoogleMarkers[_ecp1GoogleMapCounter] = new Array();
		_ecp1GoogleMarkers[_ecp1GoogleMapCounter][0] = 1; // marker counter 1 onwards are markers
 		var markOpts = { map:_ecp1GoogleMapInstances[_ecp1GoogleMapCounter], draggable:false, position:myLatLng };
		_ecp1GoogleMarkers[_ecp1GoogleMapCounter][_ecp1GoogleMarkers[_ecp1GoogleMapCounter][0]] = new google.maps.Marker( markOpts );
		if ( typeof DisplayMarker == 'string' ) {
			var image = new google.maps.MarkerImage( DisplayMarker );
			_ecp1GoogleMarkers[_ecp1GoogleMapCounter][_ecp1GoogleMarkers[_ecp1GoogleMapCounter][0]].setIcon( image );
		}
		_ecp1GoogleMarkers[_ecp1GoogleMapCounter][0] += 1;
	}
	_ecp1GoogleMapCounter += 1;
}

// Called when a map needs to be rendered (and first geocoded)
function _GeocodeGoogleMap( ElementID, Zoom, Lat, Lng, DisplayMarker, Location ) {
alert( '_GEOCODE: ' + Location + ' to ' + Lat + ' ' + Lng + ' @ ' + Zoom ); return;
	if ( ! _ecp1GoogleGeocoder )
		_ecp1GoogleGeocoder = new google.maps.Geocoder();
	
	_ecp1GoogleGeocoder.geocode( { 'address': Location }, function( results, status ) {
		if ( google.maps.GeocoderStatus.OK == status ) {
			var _ll = results[0].geometry.location;
			_RenderGoogleMap( ElementID, Zoom, _ll.lat(), _ll.lng(), DisplayMarker );
		}
	} );
}

// Function that decides between Render and Geocode functions above
// This is the actual function the PHP class tells Every Calendar to use
function RenderGoogleMap( options ) {
	// Check that we're ready
	if ( ! _ecp1GoogleGoodToGo ) 
		throw 'Google Maps library is not yet loaded - please try again soon.';

	// Validate we have a element id and marker value
	if ( typeof options.element == 'undefined' )
		throw 'No ElementID passed to RenderGoogleMap';
	var eID = options.element;

	var mark = false;
	if ( typeof options.mark == 'boolean' || typeof options.mark == 'string' )
		mark = options.mark;

	var zoom = null; // zoom we can live with defaults
	if ( typeof options.zoom == 'string' )
		zoom = options.zoom;

	// Check that we have fields for lat / lng
	if ( typeof options.lat != 'string' || typeof options.lng != 'string' )
		throw 'No Lat/Lng element passed to RenderGoogleMap';

	// Check what style of options provided
	if ( typeof options.location == 'string' )
		_GeocodeGoogleMap( eID, zoom, options.lat, options.lng, mark, options.location );
	else
		_RenderGoogleMap( eID, zoom, options.lat, options.lng, mark );
}

// Helper function that turns a map element id into an index
function _GoogleElementMapLookup( mapid ) {
	var mapIndex = null;
	for ( var i=0; i < _ecp1GoogleMapCounter; i++ ) {
		var elementMap = _ecp1GoogleMapElements[i];
		if ( elementMap != null && elementMap.element == mapid ) {
			mapIndex = elementMap.index;
			break;
		}
	}
	return mapIndex;
}

// Function that returns the center lat / lng as {'lat':X,'lng':Y}
function GetGoogleMapCenterPoint( mapid ) {
	try {
		var mapIndex = _GoogleElementMapLookup( mapid );
		if ( typeof _ecp1GoogleMapInstances[mapIndex] == 'object' ) {
			var map = _ecp1GoogleMapInstances[mapIndex];
			var center = map.getCenter();
			var myResult = { lat:center.lat(), lng:center.lng() };
			return myResult;
		}
	} catch( cp_ex ) {}
	return { lat:0, lng:0 };
}

// Function that returns an array of { 'lat':X, 'lng':Y, 'src':MarkerURL } for
// all markers that are on this map; API specifies return empty array if none
function GetGoogleMapMarkers( mapid ) {
	try {
		var mapIndex = _GoogleElementMapLookup( mapid );
		if ( typeof _ecp1GoogleMarkers[mapIndex][0] == 'number' && _ecp1GoogleMarkers[mapIndex][0] > 0 ){
			var myResult = new Array();
			for ( var i=1; i < _ecp1GoogleMarkers[mapIndex][0]; i++ ) {
				var myPos = _ecp1GoogleMarkers[mapIndex][i].getPosition();
				var myImg = _ecp1GoogleMarkers[mapIndex][i].getIcon();
				if ( typeof myImg == 'object' )
					myImg = myImg.url;
				myResult[i-1] = { lat:myPos.lat(), lng:myPos.lng(), src:myImg }
			}
			return myResult;
		}
	} catch( mark_ex ) {}
	return new Array();
}

// Function that will add a marker to the given map id { 'lat':X, 'lng':Y, 'src':url }
function AddGoogleMapMarker( mapid, args ) {
	try {
		var mapIndex = _GoogleElementMapLookup( mapid );
		if ( typeof _ecp1GoogleMapInstances[mapIndex] == 'object' ) {
			var map = _ecp1GoogleMapInstances[mapIndex];
			if ( typeof _ecp1GoogleMarkers[mapIndex] == 'undefined' ) {
				_ecp1GoogleMarkers[mapIndex] = new Array();
				_ecp1GoogleMarkers[mapIndex][0] = 1; // counter 1 onwards store markers
			}
			var markerIndex = _ecp1GoogleMarkers[mapIndex][0];
			var pos = new google.maps.LatLng( args.lat, args.lng );
			var markerOpts = { map:map, draggable:false, position:pos, icon:args.src };
			_ecp1GoogleMarkers[mapIndex][_ecp1GoogleMarkers[mapIndex][0]] = new google.maps.Marker( markerOpts );
			_ecp1GoogleMarkers[mapIndex][0] += 1;
		}
	} catch( am_ex ) {}
}

// Function will return the zoom level of the given map
function GetGoogleMapZoom( mapid ) {
	try {
		var mapIndex = _GoogleElementMapLookup( mapid );
		if ( typeof _ecp1GoogleMapInstances[mapIndex] == 'object' )
			return _ecp1GoogleMapInstances[mapIndex].getZoom();
	} catch( zm_ex ) { }
	return 10;
}

// Function Unloads a Google Map from the given element
function DeleteGoogleMap( mapid ) {
	try {
		var mapIndex = _GoogleElementMapLookup( mapid );
		_ecp1GoogleMapInstances[mapIndex] = null;
		_ecp1GoogleMarkers[mapIndex] = null;
		for ( var i=0; i < _ecp1GoogleMapCounter; i++ ) {
			var elementMap = _ecp1GoogleMapElements[i];
			if ( elementMap != null && elementMap.element == mapid ) {
				_ecp1GoogleMapElements[i] = null;
				break;
			}
		}
	} catch( del_ex ) {}
}
