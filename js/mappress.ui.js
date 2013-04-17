(function($) {

	mappress.ui = {};

	mappress.ui.centermap = function(lat, lon, zoom, map) {

		if(typeof map == 'undefined')
			map = mappress.map;

		if(typeof zoom == 'undefined' || !zoom)
			zoom = map.zoom();

		if(lat && lon && zoom) {
			$('html,body').stop().animate({
				scrollTop: $('.map-container').offset().top
			}, 400, function() {
				map.centerzoom({lat: lat, lon: lon}, zoom, true);
			});
		}
	}

	mappress.ui.highlightCenter = function() {
		var $endEl = $('.transition.has-end');
		if($endEl.length) {
			setTimeout(function() {
				$endEl.each(function() {
					$(this).hover(function() {
						var $el = $(this);
						var end = parseInt($el.data('end'));
						$el.addClass('animate');
						setTimeout(function() { $el.hide(); }, end);
					});
				});
			}, 3000);
		}
	}

	$(document).ready(function() {
		$('.center-map').click(function() {
			if($(this).data('lat') && $(this).data('lon')) {
				mappress.ui.centermap($(this).data('lat'), $(this).data('lon'), $(this).data('zoom'));
			}
			return false;
		});

		mappress.ui.highlightCenter();
	});

})(jQuery);