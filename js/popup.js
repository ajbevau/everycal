/**
 * Every Calendar +1 WordPress Plugin Calendar Popup
 */

// Global variable for i18n of the read more and show map link
var _readMore = 'Read more...';
var _showMapStr = 'Location Map';
var _loadMapStr = 'Loading...';
var _showEventDetails = 'Back to Event Details';
var _showLargeMap = 'Large Map';
var _geocodeAddr = false;
var _showMap = false;
var _mapLoadFunction = false;
var _mapDeleteFunction = false;
var _mapCenterFunction = false;
var _mapMarkersFunction = false;
var _mapAddMarkerFunction = false;
var _ecp1Counter = 0;

// Called when FullCalendar renders an event
// Adds a dynamic div with the event details
function ecp1_onrender( calEvent, element, view ) {

	try {

		var popup = jQuery( '<div></div>' )
				.addClass( 'ecp1-popup' )
				.append( jQuery( '<div></div>' )
					.addClass( 'ptab' )
					.append( jQuery( '<div></div>' )
						.addClass( 'pfloater' ) ) );

		var listClasses = 'nodeco';
		if ( calEvent.imageelem ) {
			if ( calEvent.url ) {
				popup.find( '.pfloater' ).first()
					.append( jQuery( '<a></a>' )
						.attr( { href: calEvent.url, title: calEvent.title } )
						.addClass( 'ecp1-goto' )
						.html( calEvent.imageelem ) );
				popup.find( '.pfloater a img' ).first().addClass( 'ecp1-goto' );
			} else {
				popup.find( '.pfloater' ).first()
					.append( jQuery( '<span></span>' )
						.html( calEvent.imageelem ) );
			}

			popup.children( '.ptab' ).first().append( jQuery( '<div></div>' ).addClass( 'pfloater' ) );
			listClasses += ' hasimg';
		}

		popup.find( '.pfloater' ).last().append( jQuery( '<ul></ul>' ).addClass( listClasses ) );
		popup.find( '.nodeco' ).append( jQuery( '<li><strong>' + calEvent.title + '</strong></li>' ) );

		var ds = jQuery.fullCalendar.formatDates(
				jQuery.fullCalendar.parseDate( calEvent.start ),
				jQuery.fullCalendar.parseDate( calEvent.end ),
				'h:mmtt( - h:mmtt )' );
		popup.find( '.nodeco' ).append( jQuery( '<li></li>' ).text( ds ) );

		if ( calEvent.location ) {
			popup.find( '.nodeco' )
				.append( jQuery( '<li></li>' )
					.append( jQuery( '<span><strong>@</strong></span>' )
						.addClass( 'h' ) )
					.append( jQuery( '<span></span>' )
						.addClass( 'mlblock' )
						.text( calEvent.location )
						.append( jQuery( '<br>' ) ) ) );
		} else if ( calEvent.lat && calEvent.lng ) {
			popup.find( '.nodeco' )
				.append( jQuery( '<li></li>' )
					.append( jQuery( '<span></span>' ).addClass( 'h' ) )
					.append( jQuery( '<span></span>' ).addClass( 'mlblock' ) ) );
		}

		if ( _showMap && calEvent.showmap && ( ( calEvent.lat && calEvent.lng ) || ( calEvent.location && _geocodeAddr ) ) ) {
			popup.find( '.mlblock' )
				.append( jQuery( '<a></a>' )
					.text( _showMapStr )
					.click( function() {
						// Tree is <div><div TAB><div><ul><li><span><a>
						phide = jQuery( this ).parentsUntil( '.ecp1-popup' ).last();
						var pWidth = phide.width();
						var pHeight = phide.height();
						phide.slideUp( 250, function() {
							pshow = jQuery( this ).siblings( '.ptabhide' ).first();
							if ( ! pshow.hasClass( 'pmapdone' ) ) {
								pshow.css( { width:pWidth, height:pHeight } ).addClass( 'pmapdone' ).slideDown( 250, function() {
									jQuery( this ).children( 'div' ).each( function() { jQuery( this ).css( { width:(pWidth-1) } ); } );
									var mapElem = jQuery( this ).children().first( 'div' );
									if ( mapElem.length > 0 && typeof _mapLoadFunction == 'function' ) {
										mapElem.css( { height:(pHeight-20) } );
										var lZoom = 10;
										if ( typeof calEvent.zoom == 'number' ) lZoom = calEvent.zoom;
										var lMark = true;
										if ( typeof calEvent.mark == 'string' || typeof calEvent.mark == 'boolean' ) lMark = calEvent.mark;
										var lID = mapElem.attr( 'id' );
										var lOpts = { element:lID, zoom:lZoom, mark:lMark };
										if ( typeof calEvent.lat == 'number' && typeof calEvent.lng == 'number' ) {
											lOpts.lat = calEvent.lat;
											lOpts.lng = calEvent.lng;
										} else {
											lOpts.location = calEvent.location;
										}

										_mapLoadFunction( lOpts );
									}
								} );
							} else {
								pshow.slideDown( 250 );
							}
						 } );
						return false;
					} )
					.css( { cursor:'pointer' } ) );

			popup.append( jQuery( '<div></div>' )
					.addClass( 'ptab ptabhide' )
					.append( jQuery( '<div></div>' )
						.attr( { id: '_ecp1ev_' + _ecp1Counter } )
						.addClass( 'donotclose ecp1-map-container' )
						.text( _loadMapStr ) )
					.append( jQuery( '<div></div>' )
						.addClass( 'donotclose ecp1-map-linker' )
						.css( { padding:'5px 0 0 0' } )
						.append( jQuery( '<a></a>' )
							.text( _showLargeMap )
							.click( function() {
								var lm = jQuery( '#_ecp1-large-map' );
								if ( lm.length == 0 ) {
									jQuery( 'body' ).append( jQuery( '<div></div>' )
										.attr( { id:'_ecp1-large-map' } ).css( { display:'none', 'z-index':99999 } ) );
									lm = jQuery( '#_ecp1-large-map' );
								}

								// Get the the center point and markers off the map
								var id = jQuery( this ).parent().siblings( '.ecp1-map-container' ).first().attr( 'id' );
								var cp = _mapCenterFunction( id );
								var mk = _mapMarkersFunction( id );
								var zm = _mapGetZoomFunction( id );

								var pw = jQuery( window ).width();
								var ph = jQuery( document ).height();
								var ps = jQuery( document ).scrollTop(); ps = ( ps+20 ) + 'px auto 0 auto';
								lm.css( { width:pw, height:ph, position:'absolute', top:0, left:0, 
										display:'block', textAlign:'center', background:'rgba(0,0,0,0.7)' } )
									.append( jQuery( '<div></div>' )
										.css( { background:'#ffffff', opacity:1, padding:'1em', width:800, height:600, margin:ps } )
										.append( jQuery( '<div></div>' )
											.css( { textAlign:'right' } )
											.append( jQuery( '<a></a>' )
												.css( { cursor:'pointer' } )
												.text( _showEventDetails )
												.click( function() {
													_mapDeleteFunction( '_ecp1-lmcontainer' );
													jQuery( '#_ecp1-large-map' ).remove();
												} ) ) )
										.append( jQuery( '<div></div>' )
											.attr( { id:'_ecp1-lmcontainer' } )
											.css( { textAlign:'left', width:800, height:575 } )
											.text( _loadMapStr ) ) );

								if ( typeof _mapLoadFunction == 'function' ) {
									var lOpts = { element:'_ecp1-lmcontainer', lat:cp.lat, lng:cp.lng, zoom:zm, mark:false };
									_mapLoadFunction( lOpts );
									for ( var i=0; i < mk.length; i++ ) {
										_mapAddMarkerFunction( '_ecp1-lmcontainer', mk[i] );
									}
								}
							} )
							.css( { cursor:'pointer', float:'right' } ) )
						.append( jQuery( '<a></a>' )
							.text( _showEventDetails )
							.click( function() {
								phide = jQuery( this ).parentsUntil( '.ecp1-popup' ).last();
								phide.slideUp( 250, function() {
									pshow = jQuery( this ).siblings( '.ptab' ).first();
									pshow.slideDown( 250 );
								} );
								return false;
							} )
							.css( { cursor:'pointer' } ) ) ) );
		}

		if ( calEvent.description )
			popup.find( '.nodeco' ).append( jQuery( '<li></li>' ).text( calEvent.description ) );

		if ( calEvent.url )
			popup.find( '.nodeco' ).append( jQuery( '<li></li>' )
						.append( jQuery( '<a></a>' )
							.text( _readMore )
							.addClass( 'ecp1-goto' )
							.attr( { href: calEvent.url, title: calEvent.title } ) ) );

		popup.append( jQuery( '<span></span>' ).addClass( 'clear' ) );

		element.append( popup );

	} catch(ex_pop) {
		alert( 'Unexpected calendar error: ' + ex_pop );
	}

	_ecp1Counter += 1;

}

// Called when an event in FullCalendar is clicked on
// If a dynamic div (made by onrender) exists it will be animated in
// If the div is already display it will be animated out of display
// If the target of the click was the _readMore link sends browser there
function ecp1_onclick( calEvent, jEvent, view ) {
	// If the event target was a link inside popup then go there
	if ( ( jQuery( jEvent.target ).is( 'a' ) || ( jQuery( jEvent.target ).is( 'img' ) && jQuery( jEvent.target ).parent().is( 'a' ) ) )
	 		&& jQuery( jEvent.target ).hasClass( 'ecp1-goto' ) )
		return true;

	// If this is an element specificied with class donotclose then keep popup open
	if ( jQuery( jEvent.target ).parents( '.donotclose' ).length > 0 )
		return false;

	// If there are no popup children but there is a url return true to go to it
	if ( jQuery( this ).children( '.ecp1-popup' ).length == 0) {
		if ( calEvent.url )
			return true;
		return false; // no popup or url so do nothing
	}

	// Get the first popup
	pElement = jQuery( this ).children( '.ecp1-popup' ).first();
	if ( pElement.is( ':animated' ) ) // let it finish
		return false;

	// Need to set max z-index on parent to ensure element is on top
	var maxZ = 15;
	try {
		sibs = jQuery( this ).parent().children();
		maxZ = Math.max.apply( null, jQuery.map( sibs, function( e, n ) {
			if ( jQuery( e ).css( 'position' ) == 'absolute' )
				return parseInt( jQuery( e ).css( 'z-index' ) ) || 15; // Full Calendar has 8 so being safe
		} ) );
	} catch (ex_pnt) {
		alert( 'Unexpected parent z-index error: ' + ex_pnt );
	}


	if ( pElement.is( ':visible' ) ) { // hide it
		pElement.animate( { opacity:0, top:'-25px' }, 150, 'swing', function() { pElement.removeClass( 'ecp1-popup-show' ); } );
	} else { // show it
		jQuery( this ).css( 'z-index', maxZ + 1 );
		mVer = '-20'; // where to animate to
		mHor = '-45';
		if ( 'month' != view.name ) {
			mVer = '50'; // in week/day view move down a little further
		}

		pElement.addClass( 'ecp1-popup-show' ).css( { top: -180, left: -45 } ).animate( { opacity:1, top:mVer, left:mHor }, 250, function() {
				// Make sure fully visible otherwise animate to there
				me = jQuery( this );
				pc = jQuery( '#ecp1_calendar' );
				left_balance = me.offset().left - pc.offset().left - 20;
				right_balance = pc.offset().left + pc.width() - (me.offset().left + me.width() + 150);
				top_balance = me.offset().top - pc.offset().top - 20;
				bottom_balance = pc.offset().top + pc.height() - (me.offset().top + me.height() + 40);
				if ( left_balance < 0 ) jQuery(this).animate( { left: (-1*left_balance) + 'px' }, 100 );
				else if ( right_balance < 0 ) jQuery(this).animate( { left: right_balance + 'px' }, 100 );
				if ( top_balance < 0 ) jQuery(this).animate( { top: (-1*top_balance) + 'px' }, 100 );
				else if ( bottom_balance < 1 ) jQuery(this).animate( { top: bottom_balance + 'px' }, 100 );
		} );
	}

	return false; // don't automatically go to the url parameter
}
