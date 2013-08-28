(function($) {

	jeo.ui = {};

	jeo.ui.centermap = function(lat, lon, zoom, map) {

		if(typeof map == 'undefined')
			map = jeo.map;

		if(typeof zoom == 'undefined' || !zoom)
			zoom = map.getZoom();

		if(lat && lon && zoom) {
			$('html,body').stop().animate({
				scrollTop: $('.map-container').offset().top
			}, 400, function() {
				map.setView([lat, lon], zoom);
				map.invalidateSize(true);
			});
		}
	}

	jeo.ui.highlightCenter = function() {
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

	jeo.ui.featuredSlider = function(elementID, mapID) {

		var	$container,
			$items,
			$controllers,
			$activeItem,
			$nextItem;

		jeo.mapReady(function(map) {

			if(map.map_id != mapID)
				return false;

			$container = $('#' + elementID);
			$items = $container.find('.slider-item');
			$controllers = $container.find('.slider-controllers');
			$activeItem = $container.find('.slider-item:first-child');

			var _lockMap = function() {
				map.boxZoom.disable();
				map.touchZoom.disable();
				map.scrollWheelZoom.disable();
				map.dragging.disable();
				map.doubleClickZoom.disable();
				if(map.filterLayers)
					map.filterLayers.removeFrom(map);
				if(map.zoomControl)
					map.zoomControl.removeFrom(map);
				if(map.geocode)
					map.geocode.removeFrom(map);
				map.invalidateSize(true);
			}
			_lockMap();

			var _openItem = function($item, animate) {
				if(!$item || !$item.length)
					return false;

				$items.removeClass('active');
				$item.addClass('active');

				$controllers.find('li').removeClass('active');
				$controllers.find('[data-postid="' + $item.attr('id') + '"]').addClass('active');

				$next = $item.next();

				if(!$next.length)
					$next = $container.find('.slider-item:nth-child(1)');

				map.setView([parseFloat($item.data('lat')), parseFloat($item.data('lon'))], parseInt($item.data('zoom')));
			}
			_openItem($activeItem, false);

			var _update = setInterval(function() {
				_openItem($next, true);
			}, 6000);

			$controllers.find('li').click(function() {
				_openItem($container.find('#' + $(this).data('postid')), true);
				clearInterval(_update);
				_update = setInterval(function() {
					_openItem($next, true);
				}, 6000);
			});
		});

	}

	$(document).ready(function() {
		$('.center-map').click(function() {
			if($(this).data('lat') && $(this).data('lon')) {
				jeo.ui.centermap($(this).data('lat'), $(this).data('lon'), $(this).data('zoom'));
			}
			return false;
		});

		jeo.ui.highlightCenter();
	});

})(jQuery);