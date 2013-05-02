var mappress = {};

(function($) {

	var map;

	mappress = function(conf) {

		var _init = function() {
			if(conf.mainMap)
				$('body').addClass('loading-map');

			if(conf.admin) { // is admin panel
				return mappress.build(conf);
			}

			if(!conf.postID && typeof conf === 'object') { // conf ready
				return mappress.build(conf);
			}

			return $.getJSON(mappress_localization.ajaxurl,
				{
					action: 'map_data',
					map_id: conf.postID
				},
				function(map_data) {
					mapConf = mappress.convertMapConf(map_data);
					mapConf = _.extend(mapConf, conf);
					return mappress.build(mapConf);
				});
		}

		if($.isReady) {
			return _init();
		} else {
			return $(document).ready(_init);
		}
		
	};

	mappress.maps = {};

	mappress.build = function(conf) {

		if(!conf.containerID)
			conf.containerID = 'map_' + conf.postID + '_' + conf.count;

		var map_id = conf.containerID;

		var handlers = null;
		if(conf.disableInteraction)
			handlers = [];

		mappress.maps[map_id] = mapbox.map(map_id, null, null, handlers);

		map = mappress.maps[map_id];

		// store main map
		if(conf.mainMap || !mappress.map)
			mappress.map = map;

		// store conf
		map.conf = conf;

		// store map id
		map.map_id = map_id;
		if(conf.postID)
			map.postID = conf.postID;
		
		/*
		 * Map settings
		 */

		if((conf.maxZoom && isNaN(conf.maxZoom)) || !conf.maxZoom)
			conf.maxZoom = 17;

		if((conf.minZoom && isNaN(conf.minZoom)) || !conf.minZoom)
			conf.minZoom = 0;

		if((conf.zoom && isNaN(conf.zoom)) || !conf.zoom)
			conf.zoom = 2;

		if(conf.extent) {
			if(typeof conf.extent === 'string')
				conf.extent = new MM.Extent.fromString(conf.extent);
			else if(typeof conf.extent === 'array')
				conf.extent = new MM.Extent.fromArray(conf.extent);

		}
		if(conf.panLimits) {
			if(typeof conf.panLimits === 'string')
				conf.panLimits = new MM.Extent.fromString(conf.panLimits);
			else if(typeof conf.panLimits === 'array')
				conf.panLimits = new MM.Extent.fromArray(conf.panLimits);
		}

		/*
		 * DOM settings
		 */
		// store jquery node
		map.$ = $('#' + map_id);
		/*
		 * Widgets (reset and add)
		 */
		map.$.empty().parent().find('.map-widgets').remove();
		map.$.parent().prepend('<div class="map-widgets"></div>');

		map.$.widgets = map.$.parent().find('.map-widgets');

		if(conf.mainMap) {
			$('body').removeClass('loading-map');
			if(!$('body').hasClass('displaying-map'))
				$('body').addClass('displaying-map');
		}

		/*
		 * MapBox API
		 */

	    // Enable zoom-level dependent design.
	    map.$.addClass('zoom-' + map.getZoom());

		map.$.find('.map-fullscreen').click(function() {
			map.draw();
		});

		// disable handlers
		if((conf.disableHandlers && conf.disableHandlers.mousewheel))
			map.eventHandlers[3].remove();

		// layers
		var layers = mappress.setupLayers(conf.layers);
		map.addLayer(mapbox.layer().id(layers, function() {

			if(!conf.disableInteraction)
				map.interaction.auto();

			mappress.runCallbacks('layersReady', [map]);

		}));

		if(!conf.disableInteraction) {
			map.ui.zoomer.add();
			map.ui.fullscreen.add();
		}

		if(!conf.preview)
			map.setZoomRange(conf.minZoom, conf.maxZoom);

		if(typeof conf.center === 'object' && conf.center.lat && !isNaN(conf.center.lat) && conf.center.lon && !isNaN(conf.center.lon))
			map.centerzoom(conf.center, conf.zoom, false);
		else
			map.centerzoom({lat: 0, lon: 0}, conf.zoom, false);

		if(conf.panLimits instanceof MM.Extent) {
			map.panLimits = conf.panLimits;
			if(!conf.preview)
				map.setPanLimits(conf.panLimits);
		}
		if(conf.extent instanceof MM.Extent)
			map.setExtent(conf.extent);

		if(conf.legend && !conf.disableInteraction)
			map.ui.legend.add().content(conf.legend);

		if(conf.legend_full && !conf.disableInteraction)
			mappress.enableDetails(map, conf.legend, conf.legend_full);

		// Enable zoom-level dependent design.
	    map.addCallback('drawn', _.throttle(function(map) {
	    	if(!map.ease.running()) {
	    		var classes = map.$.attr('class');
	    		classes = classes.split(' ');
	    		$.each(classes, function(i, cl) {
	    			if(cl.indexOf('zoom') === 0)
			            map.$.removeClass(cl);
	    		});
	            map.$.addClass('zoom-' + parseInt(map.getZoom()));
	        }
	    }, 200));

		// fullscreen widgets callback
		map.addCallback('drawn', function(map) {
			if(map.$.hasClass('map-fullscreen-map')) {
				if(map.$.parents('.content-map').length)
					map.$.parents('.content-map').addClass('fullscreen');
				map.$.widgets.addClass('fullscreen');
				// temporary fix scrollTop
				document.body.scrollTop = 0;
				map.dimensions = new MM.Point(map.parent.offsetWidth, map.parent.offsetHeight);
			} else {
				map.$.parents('.content-map').removeClass('fullscreen');
				map.$.widgets.removeClass('fullscreen');
			}
		});

		// conf passed callbacks
		if(typeof conf.callbacks === 'function')
			conf.callbacks();

		// map is ready, do callbacks
		mappress.runCallbacks('mapReady', [map]);

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
	 * Legend page (map details)
	 */
	mappress.enableDetails = function(map, legend, full) {
		if(typeof legend === 'undefined')
			legend = '';

		map.ui.legend.add().content(legend + '<span class="map-details-link">' + mappress_localization.more_label + '</span>');

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
	 * Utils
	 */

	mappress.setupLayers = function(layers) {

		// separate layers
		var tileLayers = [];
		var mapboxLayers = [];
		var customServerLayers = [];

		$.each(layers, function(i, layer) {
			if(layer.indexOf('http') !== -1) {
				tileLayers.push(layer);
			} else {
				mapboxLayers.push(layer);
			}
		});

		/*
		 * Currently only working with mapbox layers
		 */

		mapboxLayers = mapboxLayers.join();
		return mapboxLayers;
	};

	mappress.convertMapConf = function(conf) {

		var newConf = {};

		if(conf.server != 'mapbox')
			newConf.server = conf.server;

		newConf.layers = [];
		newConf.filteringLayers = {};
		newConf.filteringLayers.switch = [];
		newConf.filteringLayers.swap = [];

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
					newConf.filteringLayers.switch.push(switchLayer);
				}
				if(layer.opts.filtering == 'swap') {
					var swapLayer = {
						id: layer.id,
						title: layer.title
					};
					if(conf.swap_first_layer == layer.id)
						swapLayer.first = true;
					newConf.filteringLayers.swap.push(swapLayer);
				}
			}
		});

		newConf.center = {lat: parseFloat(conf.center.lat), lon: parseFloat(conf.center.lon)};
		newConf.panLimits = conf.pan_limits.north + ',' + conf.pan_limits.west + ',' + conf.pan_limits.south + ',' + conf.pan_limits.east;
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