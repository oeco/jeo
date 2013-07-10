var mappress = {};

(function($) {

	mappress = function(conf, callback) {

		var _init = function() {
			if(conf.mainMap)
				$('body').addClass('loading-map');

			if(conf.admin) { // is admin panel
				return mappress.build(conf, callback);
			}

			if(conf.dataReady || !conf.postID) { // data ready
				return mappress.build(conf, callback);
			}

			return $.getJSON(mappress_localization.ajaxurl,
				{
					action: 'map_data',
					map_id: conf.postID
				},
				function(map_data) {
					mapConf = mappress.parseConf(map_data);
					mapConf = _.extend(mapConf, conf);
					return mappress.build(mapConf, callback);
				});
		}

		if($.isReady) {
			return _init();
		} else {
			return $(document).ready(_init);
		}
		
	};

	mappress.maps = {};

	mappress.build = function(conf, callback) {
		
		/*
		 * Map settings
		 */

		var options = {
			maxZoom: 17,
			minZoom: 0,
			zoom: 2,
			center: [0,0],
			attributionControl: false
		};

		if(conf.center && !isNaN(conf.center[0]))
			options.center = conf.center;

		if(conf.zoom && !isNaN(conf.zoom))
			options.zoom = conf.zoom;

		if(conf.bounds)
			options.maxBounds = conf.bounds;

		if(conf.maxZoom && !isNaN(conf.maxZoom) && !conf.preview)
			options.maxZoom = conf.maxZoom;

		if(conf.minZoom && !isNaN(conf.minZoom) && !conf.preview)
			options.minZoom = conf.minZoom;

		var map;

		if(!conf.containerID)
			conf.containerID = 'map_' + conf.postID + '_' + conf.count;

		var map_id = conf.containerID;

		if(conf.server == 'mapbox')
			map = L.mapbox.map(map_id, null, options);
		else
			map = L.map(map_id, options);

		// store conf
		map.conf = conf;

		// store map id
		map.map_id = map_id;
		if(conf.postID)
			map.postID = conf.postID;

		// layers
		mappress.loadLayers(map, mappress.parseLayers(conf.layers));

		// set bounds
		if(conf.fitBounds instanceof L.LatLngBounds)
			map.fitBounds(conf.fitBounds);


		/*
		 * DOM settings
		 */
		// store jquery node
		map.$ = $('#' + map_id);

		if(conf.mainMap) {
			$('body').removeClass('loading-map');
			if(!$('body').hasClass('displaying-map'))
				$('body').addClass('displaying-map');
		}

		/*
		 * Widgets (reset and add)
		 */
		map.$.parent().find('.map-widgets').remove();
		map.$.parent().prepend('<div class="map-widgets"></div>');

		map.$.widgets = map.$.parent().find('.map-widgets');

		map.$.addClass('zoom-' + map.getZoom());

		/* 
		 * Legends
		 */
		if(conf.legend) {
			map.legendControl.addLegend(conf.legend);
		}
		if(conf.legend_full)
			mappress.enableDetails(map, conf.legend, conf.legend_full);

		/*
		 * Geocode
		 */
		if(map.conf.geocode)
			map.addControl(new mappress.geocode());

		/*
		 * Filter layers
		 */
		if(map.conf.filteringLayers)
			map.addControl(new mappress.filterLayers());

		/*
		 * CALLBACKS
		 */

		// conf passed callbacks
		if(typeof conf.callbacks === 'function')
			conf.callbacks(map);

		// map is ready, do callbacks
		mappress.runCallbacks('mapReady', [map]);

		if(typeof callback === 'function')
			callback(map);

		return map;
	}


	/*
	 * Map widgets
	 */

	mappress.widget = function(map_id, content, widgetClass, group) {
		var $map = $('#' + map_id);
		var $widgets = $map.parent().find('.map-widgets');
		// add widget
		var widget = $('<div class="map-widget" />');
		if(typeof group !== 'undefined') {
			widget.append('<div class="' + group + '" />');
			widget.find('.' + group).append($(content));
		} else {
			widget.append($(content));
		}
		if(typeof widgetClass !== 'undefined') widget.addClass(widgetClass);
		$widgets.append(widget);
		return widget;
	};

	/*
	 * Utils
	 */

	mappress.parseLayers = function(layers) {

		var parsedLayers = [];

		var composite = 0;
		var composited = 0;

		$.each(layers, function(i, layer) {

			if(layer.indexOf('http') !== -1) {

				parsedLayers.push(layer);
				composite++;

			} else {

				composited++;

				if(!parsedLayers[composite] || parsedLayers[composite].indexOf('http') !== -1)
					parsedLayers.push(layer);
				else
					parsedLayers[composite] += ',' + layer;

				if(composited >= 16) {
					composited = 0;
					composite++;
				}

			}

		});

		// create tileLayers

		var layers = [];
		$.each(parsedLayers, function(i, layer) {

			if(layer.indexOf('http') !== -1) {
				layers.push(L.tileLayer(layer));
			} else {
				layers.push(L.mapbox.tileLayer(layer));
				layers.push(L.mapbox.gridLayer(layer));
			}

		});

		return layers;
	};

	mappress.loadLayers = function(map, parsedLayers) {

		if(map.coreLayers)
			map.coreLayers.clearLayers();
		else {
			map.coreLayers = new L.layerGroup();
			map.addLayer(map.coreLayers);
		}

		$.each(parsedLayers, function(i, layer) {
			map.coreLayers.addLayer(layer);
			if(layer._tilejson) {
				map.addControl(L.mapbox.gridControl(layer));
			}
		});


		return map.coreLayers;
	}

	mappress.parseConf = function(conf) {

		var newConf = {};

		newConf.server = conf.server;

		if(conf.dataReady)
			newConf.dataReady = true;

		if(conf.conf)
			newConf = _.extend(newConf, conf.conf);

		newConf.layers = [];
		newConf.filteringLayers = {};
		newConf.filteringLayers.switchLayers = [];
		newConf.filteringLayers.swapLayers = [];

		$.each(conf.layers, function(i, layer) {
			newConf.layers.push(layer.id);
			if(layer.opts) {
				if(layer.opts.filtering == 'switch') {
					var switchLayer = {
						id: layer.id,
						title: layer.title
					};
					if(layer.switch_hidden)
						switchLayer.hidden = true;
					newConf.filteringLayers.switchLayers.push(switchLayer);
				}
				if(layer.opts.filtering == 'swap') {
					var swapLayer = {
						id: layer.id,
						title: layer.title
					};
					if(conf.swap_first_layer == layer.id)
						swapLayer.first = true;
					newConf.filteringLayers.swapLayers.push(swapLayer);
				}
			}
		});

		newConf.center = [parseFloat(conf.center.lat), parseFloat(conf.center.lon)];

		if(conf.pan_limits.south && conf.pan_limits.north) {
			newConf.bounds = [
				[conf.pan_limits.south, conf.pan_limits.west],
				[conf.pan_limits.north, conf.pan_limits.east]
			];
		}

		newConf.zoom = parseInt(conf.zoom);
		newConf.minZoom = parseInt(conf.min_zoom);
		newConf.maxZoom = parseInt(conf.max_zoom);

		if(conf.geocode)
			newConf.geocode = true;

		newConf.disableHandlers = {};
		if(conf.disable_mousewheel)
			newConf.disableHandlers.mousewheel = true;

		if(conf.legend)
			newConf.legend = conf.legend;

		if(conf.legend_full)
			newConf.legend_full = conf.legend_full;
		
		return newConf;
	}

	/*
	 * Legend page (map details)
	 */
	mappress.enableDetails = function(map, legend, full) {
		if(typeof legend === 'undefined')
			legend = '';

		map.legendControl.removeLegend(legend);
		map.conf.legend_full_content = legend + '<span class="map-details-link">' + mappress_localization.more_label + '</span>';
		map.legendControl.addLegend(map.conf.legend_full_content);

		var isContentMap = map.$.parents('.content-map').length;
		var $detailsContainer = map.$.parents('.map-container');
		if(isContentMap)
			$detailsContainer = map.$.parents('.content-map');

		if(!$detailsContainer.hasClass('clearfix'))
			$detailsContainer.addClass('clearfix');

		map.$.find('.map-details-link').unbind().click(function() {
			$detailsContainer.append($('<div class="map-details-page"><div class="inner"><a href="#" class="close">×</a>' + full + '</div></div>'));
			$detailsContainer.find('.map-details-page .close, .map-nav a').click(function() {
				$detailsContainer.find('.map-details-page').remove();
				return false;
			});

		});
	}

	/*
	 * Custom fullscreen
	 */
	mappress.fullscreen = function(map) {

		if(map.$.parents('.content-map').length)
			var container = map.$.parents('.content-map');
		else
			var container = map.$.parents('.map-container');

		map.$.find('.map-fullscreen').click(function() {

			if(container.hasClass('fullsreen-map'))
				container.removeClass('fullscreen-map');
			else
				container.addClass('fullscreen-map');

			return false;

		});
	}

	/*
	 * Callback manager
	 */

	mappress.callbacks = {};

	mappress.createCallback = function(name) {
		mappress.callbacks[name] = [];
		mappress[name] = function(callback) {
			mappress.callbacks[name].push(callback);
		}
	}

	mappress.runCallbacks = function(name, args) {
		if(!mappress.callbacks[name]) {
			console.log('A MapPress callback tried to run, but wasn\'t initialized');
			return false;
		}
		if(!mappress.callbacks[name].length)
			return false;

		var _run = function(callbacks) {
			if(callbacks) {
				_.each(callbacks, function(c, i) {
					if(c instanceof Function)
						c.apply(this, args);
				});
			}
		}
		_run(mappress.callbacks[name]);
	}

	mappress.createCallback('mapReady');
	mappress.createCallback('layersReady');

})(jQuery);