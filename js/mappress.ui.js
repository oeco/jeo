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

	$(document).ready(function() {
		$('.center-map').click(function() {
			if($(this).data('lat') && $(this).data('lon')) {
				mappress.ui.centermap($(this).data('lat'), $(this).data('lon'), $(this).data('zoom'));
			}
			return false;
		});
	});

})(jQuery);