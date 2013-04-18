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

	mappress.ui.featuredSlider = function(elementID, mapID) {

		var	$container,
			$items,
			$controllers,
			$activeItem,
			$nextItem,
			map;

		mappress.mapReady(mapID, function() {
			$container = $('#' + elementID);
			map = mappress.maps[mapID];
			$items = $container.find('.slider-item');
			$controllers = $container.find('.slider-controllers');
			$activeItem = $container.find('.active');

			lockMap();

			openItem($activeItem, false);

			$controllers.find('li').click(function() {
				openItem($container.find('#' + $(this).data('postid')), true);
				clearInterval(update);
				update = setInterval(function() {
					openItem($next, true);
				}, 6000);
			});

			var update = setInterval(function() {
				openItem($next, true);
			}, 6000);
		});

		var openItem = function($item, animate) {
			if(!$item || !$item.length)
				return false;

			$items.removeClass('active');
			$item.addClass('active');

			$controllers.find('li').removeClass('active');
			$controllers.find('[data-postid="' + $item.attr('id') + '"]').addClass('active');

			$next = $item.next();

			if(!$next.length)
				$next = $container.find('.slider-item:nth-child(1)');

			map.centerzoom({lat: $item.data('lat'), lon: $item.data('lon')}, $item.data('zoom'), animate);
		}

		var lockMap = function() {
			map.ui.zoomer.remove();
			map.ui.fullscreen.remove();
			map.eventHandlers[1].remove();
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