var mappress = {};

(function($) {

	/*
	 * MAP BUILD
	 * conf:
	 * - containerID (string)
	 * - server (string)
	 * - layers (array (layer))
	 * - filterLayers (array (layer))
	 * - center (array (lat,lon))
	 * - zoom (int)
	 * - extent (MM.Extent)
	 * - panLimits (MM.Extent)
	 * - minZoom (int)
	 * - maxZoom (int)
	 * - geocode (bool)
	 * - disableHash (bool)
	 * - disableMarkers (bool)
	 */

	var map;

	mappress = function(conf) {

		if(!conf.postID && typeof conf === 'object') { // conf ready

			return mappress.build(conf);

		}

		if(conf.admin) { // is admin panel
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

	};

	mappress.maps = {};

	mappress.build = function(conf) {

		var map_id = conf.containerID;

		var handlers = null;
		if(conf.disableInteraction)
			handlers = [];

		mappress.maps[map_id] = mapbox.map(map_id, null, null, handlers);

		map = mappress.maps[map_id];

		// store jquery node
		map.$ = $('#' + map_id);

		/*
		 * Widgets (reset and add)
		 */
		map.$.empty().parent().find('.map-widgets').remove();
		map.$.parent().prepend('<div class="map-widgets"></div>');

		map.$.widgets = map.$.parent().find('.map-widgets');

		if(typeof conf.callbacks === 'function')
			conf.callbacks();

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

		map.$.parents('.content-map').resize(function() {
			map.dimensions = new MM.Point(map.parent.offsetWidth, map.parent.offsetHeight);
			map.draw();
		});

        // Enable zoom-level dependent design.
        map.$.addClass('zoom-' + map.getZoom());
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

		// store conf
		map.conf = conf;
		map.conf.formattedLayers = layers;

		// store map id
		map.map_id = map_id;

		// layers
		var layers = mappress.setupLayers(conf.layers);
		map.addLayer(mapbox.layer().id(layers, function() {

			map.interaction.auto();

			if(conf.geocode && !conf.disableInteraction)
				mappress.geocode(map_id);

			if(conf.filteringLayers && !conf.disableInteraction)
				mappress.filterLayers(map_id, conf.filteringLayers);

		}));
		
		/*
		 * CONFS
		 */
		if(!conf.disableInteraction) {
			map.ui.zoomer.add();
			map.ui.fullscreen.add();
		}

		if(conf.extent) {
			if(typeof conf.extent === 'string')
				conf.extent = new MM.Extent.fromString(conf.extent);
			else if(typeof conf.extent === 'array')
				conf.extent = new MM.Extent.fromArray(conf.extent);

			if(conf.extent instanceof MM.Extent)
				map.setExtent(conf.extent);
		}

		if(conf.panLimits) {
			if(typeof conf.panLimits === 'string')
				conf.panLimits = new MM.Extent.fromString(conf.panLimits);
			else if(typeof conf.panLimits === 'array')
				conf.panLimits = new MM.Extent.fromArray(conf.panLimits);

			if(conf.panLimits instanceof MM.Extent) {
				map.panLimits = conf.panLimits;
				if(!conf.preview)
					map.setPanLimits(conf.panLimits);
			}
		}

		if(((conf.minZoom && !isNaN(conf.minZoom)) || (conf.maxZoom && !isNaN(conf.maxZoom))) && !conf.preview)
			map.setZoomRange(conf.minZoom, conf.maxZoom);

		if(conf.center && conf.center.lat && !isNaN(conf.center.lat) && conf.center.lon && !isNaN(conf.center.lon))
			map.center(conf.center);
		else
			map.center({lat: 0, lon: 0});

		if(conf.zoom && !isNaN(conf.zoom))
			map.zoom(conf.zoom);
		else
			map.zoom(2);

		if(!conf.disableHash && !conf.admin)
			mappress.setupHash();

		if(!conf.disableMarkers && !conf.admin)
			mappress.markers(map);

		if(conf.legend && !conf.disableInteraction)
			map.ui.legend.add().content(conf.legend);

		if(conf.legend_full && !conf.disableInteraction)
			mappress.enableDetails(map, conf.legend, conf.legend_full);

		return map;
	}

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

	/*
	 * Map widgets
	 */

	mappress.widget = function(map_id, content) {
		var $map = $('#' + map_id);
		var $widgets = $map.parent().find('.map-widgets');
		// add widget
		var widget = $('<div class="map-widget"></div>').append($(content));
		$widgets.append(widget);
		return widget;
	};

	/*
	 * Legend page (map details)
	 */
	mappress.enableDetails = function(map, legend, full) {
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

	mappress.convertMapConf = function(conf) {

		var newConf = {};

		if(conf.server != 'mapbox')
			newConf.server = conf.server;

		newConf.containerID = 'map_' + conf.postID;
		newConf.layers = [];
		newConf.filteringLayers = [];
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

		if(conf.legend)
			newConf.legend = conf.legend;

		if(conf.legend_full)
			newConf.legend_full = conf.legend_full;

		return newConf;
	}

})(jQuery);