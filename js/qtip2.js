/**
 * Every Calendar +1 WordPress Plugin qTip2 Init Script
 */
function ecp1OnDisplay( view ) {
	jQuery('#ecp1_calendar .fc-event').qtip({
		content: 'An example tooltip',
		position: { my: 'top left', at: 'bottom right' },
		show: 'click',
		hide: 'click',
		style: { tip: true, classes: 'ui-tooltip-red' }
	});
}