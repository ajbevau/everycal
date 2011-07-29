/**
 * Every Calendar +1 WordPress Plugin Calendar Popup
 */
function ecp1_onrender( calEvent, element, view ) {
	element.append('<div class="ecp1-popup">Some really long and useful content<br/>about the event<br/><strong>YAY</strong></div>');
}

function ecp1_onclick( calEvent, jEvent, view ) {
	pElement = jQuery( this ).children( '.ecp1-popup' ).first();
	if ( ! pElement ) return false;
	if ( pElement.is( ':animated' ) ) // let it finish
		return false;

	// Need to set max z-index on parent to ensure element is on top
	var maxZ = Math.max.apply( null, jQuery.map( jQuery( this ).siblings(), function( e, n ) {
		if ( jQuery( e ).css( 'position' ) == 'absolute' )
			return parseInt( jQuery( e ).css( 'z-index' ) ) || 15; // Full Calendar has 8 so being safe
	} ) );


	if ( pElement.is( ':visible' ) ) { // hide it
		pElement.animate( { opacity:0, top:'-25px' }, 150, 'swing', function() { pElement.removeClass( 'ecp1-popup-show' ); } );
	} else { // show it
		jQuery( this ).css( 'z-index', maxZ + 1 );
		mVer = '-20';
		mHor = '-45';
		if ( 'month' != view.name ) {
			mVer = '50';
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
				else if ( bottom_balance < 0 ) jQuery(this).animate( { top: bottom_balance + 'px' }, 100 );
			} );
		pElement.click( function() {
			jQuery(this).animate( { opacity:0, top:'-25px' }, 250, 'swing', function() { pElement.removeClass( 'ecp1-popup-show' ); } );
		} );
	}
}
