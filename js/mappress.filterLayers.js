(function($) {

	mappress.filterLayers = function(map_id, layers) {

		var filter = mappress.filterLayers;

		var map = mappress.maps[map_id];

		layers.status = [];
		_.each(map.conf.layers, function(layerID) {
			var layer = {
				id: layerID,
				on: true
			};
			layers.status.push(layer);
		});

		var	swapWidget,
			switchWidget;

		mappress.filterLayers.prepare = function() {
			/*
			 * Swapables
			 */
			if(layers.swap && layers.swap.length >= 2) {
				var swap = layers.swap;
				var list = '';
				_.each(swap, function(layer) {
					var attrs = '';
					if(layer.first)
						attrs = 'class="active"';
					else
						filter.disableLayer(layer.id);
					list += '<li data-layer="' + layer.id + '" ' + attrs + '>' + layer.title + '</li>';
				});
				swapWidget = mappress.widget(map_id, '<ul class="swap-layers">' + list + '</ul>');

				swapWidget.find('li').click(function() {
					filter.swap($(this).data('layer'), swap);
				});
			}

			/*
			 * Switchables
			 */
			if(layers.switch && layers.switch.length) {
				var switchable = layers.switch;
				var list = '';
				_.each(switchable, function(layer) {
					var attrs = 'class="active"';
					if(layer.hidden) {
						attrs = '';
						filter.disableLayer(layer.id);
					}
					list += '<li data-layer="' + layer.id + '" ' + attrs + '>' + layer.title + '</li>';
				});
				switchWidget = mappress.widget(map_id, '<ul class="switch-layers">' + list + '</ul>');

				switchWidget.find('li').click(function() {
					filter.switch($(this).data('layer'));
				});
			}

			filter.update();
		}

		mappress.filterLayers.switch = function(layer) {

			var widget = switchWidget;

			if(filter.getStatus(layer).on) {

				filter.disableLayer(layer);

				if(typeof widget != 'undefined')
					widget.find('li[data-layer="' + layer + '"]').removeClass('active');

			} else {

				filter.enableLayer(layer);

				if(typeof widget != 'undefined')
					widget.find('li[data-layer="' + layer + '"]').addClass('active');

			}

			filter.update();
		};

		mappress.filterLayers.swap = function(layer) {

			var widget = swapWidget;

			if(filter.getStatus(layer).on)
				return;

			_.each(map.conf.filteringLayers.swap, function(swapLayer) {

				if(swapLayer.id == layer) {

					filter.enableLayer(layer);

					if(typeof widget != 'undefined')
						widget.find('li[data-layer="' + layer + '"]').addClass('active');

				} else {

					if(filter.getStatus(swapLayer.id).on) {

						filter.disableLayer(swapLayer.id);

						if(typeof widget != 'undefined')
							widget.find('li[data-layer="' + swapLayer.id + '"]').removeClass('active');

					}

				}
			});

			filter.update();
		};

		mappress.filterLayers.disableLayer = function(layer) {

			layers.status[filter.getStatusIndex(layer)] = {
				id: layer,
				on: false
			}

		};

		mappress.filterLayers.enableLayer = function(layer) {

			layers.status[filter.getStatusIndex(layer)] = {
				id: layer,
				on: true
			}

		};

		mappress.filterLayers.update = function() {

			var layers = mappress.setupLayers(filter.getActiveLayers());

			mapbox.load(layers, function(data) {
				map.setLayerAt(0, data.layer);
				map.interaction.refresh();
			});

		};

		mappress.filterLayers.getStatus = function(layer) {
			return _.find(layers.status, function(l) { return layer == l.id; });
		}

		mappress.filterLayers.getStatusIndex = function(layer) {
			var index;
			_.each(layers.status, function(l, i) {
				if(layer == l.id)
					index = i;
			});
			return index;
		}

		mappress.filterLayers.getActiveLayers = function() {
			var activeLayers = [];
			_.each(layers.status, function(layer) {
				if(layer.on)
					activeLayers.push(layer.id);
			});
			return activeLayers;
		}

		$(document).ready(function() {
			filter.prepare();
		});

		return filter;

	};

})(jQuery);