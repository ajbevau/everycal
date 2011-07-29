(function ($) {
	$.fn.ecp1eventbubble = function (options) {
		var defaults = {
			'trigger' : '.trigger',
			'popup' : '.popup',
			'distance' : 10,
			'hideDelay' : 500,
			'effectTime' : 250
		};
		
		var settings = $.extend({}, defaults, options);
		
		return this.each(function () {
			var hideDelayTimer = null;

			var trigger = $(settings.trigger, this);
			var popup = $(settings.popup, this);

			$([trigger.get(0), popup.get(0)]).mouseover(function () {
				if (hideDelayTimer) clearTimeout(hideDelayTimer);

				if (popup.is(':animated, :visible')) {
					return;
				} else {
					popup.css({
						display: 'block',
						top: -100,
						left: -32
					}).animate({
						opacity: 1,
						top: '-=' + settings.distance + 'px'
					}, settings.effectTime);				
				}
			}).mouseout(function () {
				if (hideDelayTimer) clearTimeout(hideDelayTimer);

				hideDelayTimer = setTimeout(function () {
					hideDelayTimer = null;
					popup.animate({
						top: '-=' + settings.distance + 'px',
						opacity: 0
					}, settings.effectTime, 'swing', function () {
						popup.css('display', 'none');
					});		
				}, settings.hideDelay);
			});
		});
	}
})(jQuery);

function ecp1_onrender( calEvent, element, view ) {
	
}

function ecp1_onclick( calEvent, jsEvent, view ) {
	alert('calEvent:' + calEvent.title)
	alert('jsEvent:' + jsEvent)
}
