/**
 * Every Calendar +1 WordPress Plugin Calendar Popup
 */

// Global variable for i18n of the read more and show map link
var _readMore = 'Read more...';
var _showMap = 'Location Map';
var _loadMap = 'Loading...';
var _showEventDetails = 'Back to Event Details';
var _geocodeAddr = false;
var _mapInitFunction = false;
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

	if ( calEvent.imageurl ) {
		if ( calEvent.url ) {
			popup.find( '.pfloater' ).first()
				.append( jQuery( '<a></a>' )
					.attr( { href: calEvent.url, title: calEvent.title } )
					.addClass( 'ecp1-goto' )
					.html( calEvent.imageurl ) );
		} else {
			popup.find( '.pfloater' ).first()
				.append( jQuery( '<span></span>' )
					.html( calEvent.imageurl ) );
		}

		popup.children( '.ptab' ).first().append( jQuery( '<div></div>' ).addClass( 'pfloater' ) );
	}

	popup.find( '.pfloater' ).last().append( jQuery( '<ul></ul>' ).addClass( 'nodeco' ) );
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
					.text( calEvent.location ) ) );
		if ( calEvent.coords || ( calEvent.location && _geocodeAddr ) ) {
			popup.find( '.mlblock' )
				.append( jQuery( '<br>' ) )
				.append( jQuery( '<a></a>' )
					.text( _showMap )
					.click( function() {
						// Tree is <div><div TAB><div><ul><li><span><a>
						phide = jQuery( this ).parentsUntil( '.ecp1-popup' ).last();
						phide.slideUp( 250, function() {
							pshow = jQuery( this ).siblings( '.ptabhide' ).first();
							if ( ! pshow.hasClass( 'pmapdone' ) ) {
								pshow.addClass( 'pmapdone' ).slideDown( 250 );
								if ( _mapInitFunction )
									_mapInitFunction( '_ecp1ev_' + _ecp1Counter, calEvent.location, true );
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
						.text( _loadMap ) )
					.append( jQuery( '<div><div>' )
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
		alert( ex_pop );
	}

	_ecp1Counter += 1;

}

// Called when an event in FullCalendar is clicked on
// If a dynamic div (made by onrender) exists it will be animated in
// If the div is already display it will be animated out of display
// If the target of the click was the _readMore link sends browser there
function ecp1_onclick( calEvent, jEvent, view ) {
	// If the event target was a link inside popup then go there
	if ( ( jQuery( jEvent.target ).is( 'a' ) || ( jQuery( jEvent.target ).is( 'img' ) && jQuery( jEvent.target ).parent().is( 'a' ) ) ) && jQuery( jEvent.target ).hasClass( 'ecp1-goto' ) )
		return true;

	// If there are no popup children but there is a url return true to go to it
	if ( jQuery( this ).children( '.ecp1-popup' ).length == 0) {
		alert('no popups found');
		if ( calEvent.url )
			return false; // TODO: Change to TRUE
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
		alert('parent error: ' + ex_pnt);
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
		
		// register a click event to close the info popup
		pElement.click( function() {
			jQuery(this).animate( { opacity:0, top:'-25px' }, 250, 'swing', function() { pElement.removeClass( 'ecp1-popup-show' ); } );
		} );
	}

	return false; // don't automatically go to the url parameter
}
