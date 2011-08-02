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
var _ecp1GoogleUpdateFields = new Array();

// Global reference to a Geocoder instance (for efficiency)
var _ecp1GoogleGeocoder = false;
var _geocodeFailedMessage = 'Could not find your location';

// Global map of element id to map instance index
var _ecp1GoogleMapElements = new Array();

// Variables for holding the icon path and image array
var _iconsPath = '';
var _iconsArray = [];

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
	if ( _ecp1GoogleCallback ) {
		_ecp1GoogleCallback();
	}
}

// Called when a map needs to be rendered
// There are two forms of this function one that takes coords
// which is this function, and the other that takes a text 
// address and geocodes it. Once geocoding is complete this
// function is called to render the map.
function _RenderGoogleMap( ElementID, Zoom, Lat, Lng, DisplayMarker ) {
	try {

		var eLat = document.getElementById( Lat );
		var eLng = document.getElementById( Lng );
		if ( eLat == null && eLng == null )
			throw "Invalid Lat ('" + Lat + "') / Lng ('" + Lng + "') elements provided to RenderGoogleMap";
		else if ( eLat == null )
			throw "Invalid Lat ('" + Lat + "') element provided to RenderGoogleMap";
		else if ( eLng == null )
			throw "Invalid Lng ('" + Lng + "') element provided to RenderGoogleMap";
	
		var myElement = document.getElementById( ElementID );
		if ( typeof myElement == 'null' || typeof myElement == 'undefined' )
			throw "Non-existent Element ID provided to RenderGoogleMap";

		var zoomTo = 1;
		if ( Zoom != null ) {
			eZoom = document.getElementById( Zoom );
			if ( eZoom != null && '' != eZoom.value ) {
				zoomTo = parseInt( eZoom.value );
				if ( isNaN( zoomTo ) ) zoomTo = 1;
			}
		}

		var _lat = 0;
		var _lng = 0;
		if ( '' != eLat.value && '' != eLng.value ) {
			_lat = parseFloat( eLat.value );
			if ( isNaN( _lat ) ) { DisplayMarker = false; _lat = 0; }
			_lng = parseFloat( eLng.value );
			if ( isNaN( _lng ) ) { DisplayMarker = false; _lng = 0; }
		} else { DisplayMarker = false; } // prevent mark on default map

		// does the map already exist: if so move dont't create
		var mapIndex = _GoogleElementMapLookup( ElementID );
		if ( mapIndex == null )
			mapIndex = _ecp1GoogleMapCounter;

		var myLatLng = new google.maps.LatLng( _lat, _lng );
		if ( typeof _ecp1GoogleMapInstances[mapIndex] != 'object' ) {
			var myOptions = { mapTypeId: google.maps.MapTypeId.ROADMAP, disableDefaultUI:true, zoomControl:true, zoom:zoomTo, center:myLatLng };
			_ecp1GoogleMapInstances[mapIndex] = new google.maps.Map( document.getElementById( ElementID ), myOptions );
			_ecp1GoogleMapElements[mapIndex] = { index:mapIndex, element:ElementID };
			google.maps.event.addListener( _ecp1GoogleMapInstances[mapIndex], 'dragend', function() {
				_UpdateGoogleMapFields( mapIndex );
			} );
			google.maps.event.addListener( _ecp1GoogleMapInstances[mapIndex], 'zoom_changed', function() {
				_UpdateGoogleMapFields( mapIndex );
			} );
		} else {
			_ecp1GoogleMapInstances[mapIndex].setCenter( myLatLng );
			_ecp1GoogleMapInstances[mapIndex].setZoom( zoomTo );
			if ( typeof _ecp1GoogleMarkers[mapIndex] != 'undefined' && _ecp1GoogleMarkers[mapIndex] != null ) {
				for ( var i=1; i < _ecp1GoogleMarkers[mapIndex][0]; i++ ) {
					_ecp1GoogleMarkers[mapIndex][i].setMap( null );
					_ecp1GoogleMarkers[mapIndex][i] = null;
				}
				_ecp1GoogleMarkers[mapIndex] = null;
			}
		}

		var eDisplayMarkers = null;
		var eMarkerImage = null;
		if ( typeof DisplayMarker == 'object' ) {
			if ( DisplayMarker.length > 0 )
				eDisplayMarkers = document.getElementById( DisplayMarker[0] );
			if ( DisplayMarker.length > 1 )
				eMarkerImage = document.getElementById( DisplayMarker[1] );
		}

		if ( eDisplayMarkers != null && eDisplayMarkers.checked ) {
			_ecp1GoogleMarkers[mapIndex] = new Array();
			_ecp1GoogleMarkers[mapIndex][0] = 1; // marker counter 1 onwards are markers
	 		var markOpts = { map:_ecp1GoogleMapInstances[mapIndex], draggable:true, position:myLatLng };
			_ecp1GoogleMarkers[mapIndex][_ecp1GoogleMarkers[mapIndex][0]] = new google.maps.Marker( markOpts );
			google.maps.event.addListener( _ecp1GoogleMarkers[mapIndex][_ecp1GoogleMarkers[mapIndex][0]], 'dragend', function() {
				_UpdateGoogleMapFields( mapIndex );
			} );

			if ( eMarkerImage != null && '' != eMarkerImage.value ) {
				var image = new google.maps.MarkerImage( _iconsPath + '/' + eMarkerImage.value );
				_ecp1GoogleMarkers[mapIndex][_ecp1GoogleMarkers[mapIndex][0]].setIcon( image );
			}

			_ecp1GoogleMarkers[mapIndex][0] += 1;
		}

		_ecp1GoogleUpdateFields[mapIndex] = { zoom:Zoom, lat:Lat, lng:Lng };
		_ecp1GoogleMapCounter += 1;

	} catch( lm_ex ) { throw "GoogleMapRender Error: " + lm_ex; }
}

// Called when a map needs to be rendered (and first geocoded)
function _GeocodeGoogleMap( ElementID, Zoom, Lat, Lng, DisplayMarker, Location ) {
	if ( ! _ecp1GoogleGeocoder )
		_ecp1GoogleGeocoder = new google.maps.Geocoder();

	var eLat = document.getElementById( Lat );
	var eLng = document.getElementById( Lng );
	if ( eLat == null && eLng == null )
		throw "Invalid Lat ('" + Lat + "') / Lng ('" + Lng + "') elements provided to RenderGoogleMap";
	else if ( eLat == null )
		throw "Invalid Lat ('" + Lat + "') element provided to RenderGoogleMap";
	else if ( eLng == null )
		throw "Invalid Lng ('" + Lng + "') element provided to RenderGoogleMap";
	
	_ecp1GoogleGeocoder.geocode( { 'address': Location }, function( results, status ) {
		if ( google.maps.GeocoderStatus.OK == status ) {
			var _ll = results[0].geometry.location;
			eLat.value = _ll.lat();
			eLng.value = _ll.lng();
			var zElement = document.getElementById( Zoom );
			if ( zElement != null ) {
				zElementV = parseInt( zElement.value );
				if ( isNaN( zElementV ) || zElementV < 6 )
					zElement.value = '6';
			}
			_RenderGoogleMap( ElementID, Zoom, Lat, Lng, DisplayMarker );
		} else { alert( _geocodeFailedMessage ); }
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

	var mark = [];
	if ( typeof options.mark == 'object' )
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

// Helper function that updates the form fields with details from the map
function _UpdateGoogleMapFields( mapIndex ) {
	if ( typeof _ecp1GoogleUpdateFields[mapIndex] == 'object' ) {
		var map = _ecp1GoogleMapInstances[mapIndex];
		var fields = _ecp1GoogleUpdateFields[mapIndex];
		var markers = _ecp1GoogleMarkers[mapIndex];
		var cp = null;
		if ( typeof markers == 'undefined' || markers == null )
			cp = map.getCenter();
		else if ( typeof markers == 'object' )
			cp = markers[1].getPosition();

		var lat = document.getElementById( fields.lat );
		var lng = document.getElementById( fields.lng );
		if ( lat == null || lng == null )
			throw "GoogleMap Error: Unabled to update Lat / Lng fields.";

		lat.value = cp.lat();
		lng.value = cp.lng();

		var zoom = document.getElementById( fields.zoom );
		if ( zoom != null )
			zoom.value = map.getZoom();
	}
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
		_ecp1GoogleUpdateFields[mapIndex] = null;
		for ( var i=0; i < _ecp1GoogleMapCounter; i++ ) {
			var elementMap = _ecp1GoogleMapElements[i];
			if ( elementMap != null && elementMap.element == mapid ) {
				_ecp1GoogleMapElements[i] = null;
				break;
			}
		}
	} catch( del_ex ) {}
}
