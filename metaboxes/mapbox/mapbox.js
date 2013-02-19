(function($) {

	var mapConf = {};
	var map_id;

	var updateMapConf = function() {

		// layers
		mapConf.layers = getLayers();
		mapConf.filteringLayers = getFilteringLayers();

		// hidden layers
		mapConf.hiddenLayers = [];
		$('#mapbox-metabox input.hidden_layer:checked').each(function() {
			var hidden = $(this).val();
			mapConf.hiddenLayers.push(hidden);
		}).change();

		// server
		if($('input[name="map_data[server]"]:checked').val() === 'custom') {
			mapConf.server = $('input[name="map_data[custom_server]"]').val();
		}

		// center
		if($('.centerzoom.map-setting input.center-lat').val()) {
			var $centerInputs = $('.centerzoom.map-setting');
			mapConf.center = {
				lat: parseFloat($centerInputs.find('input.center-lat').val()),
				lon: parseFloat($centerInputs.find('input.center-lon').val())
			}
		}

		// zoom
		if($('.centerzoom.map-setting input.zoom').val())
			mapConf.zoom = parseInt($('.centerzoom.map-setting input.zoom').val());
		// min zoom
		if($('#min-zoom-input').val())
			mapConf.minZoom = parseInt($('#min-zoom-input').val());
		// max zoom
		if($('#max-zoom-input').val())
			mapConf.maxZoom = parseInt($('#max-zoom-input').val());


		// pan limits
		if($('.pan-limits.map-setting input.east').val()) {
			mapConf.panLimits =	$('.pan-limits.map-setting input.north').val() + ',' +
								$('.pan-limits.map-setting input.west').val() + ',' +
								$('.pan-limits.map-setting input.south').val() + ',' +
								$('.pan-limits.map-setting input.east').val();
		}

		// geocode
	 	if($('#mapbox-metabox .enable-geocode').is(':checked'))
	 		mapConf.geocode = true;
	 	else
	 		mapConf.geocode = false;

	 	if($('#mapbox-metabox .toggle-preview-mode').is(':checked'))
	 		mapConf.preview = true;
	 	else
	 		mapConf.preview = false;

	 	// legend
	 	if($('#mapbox-legend-metabox').length) {
	 		if($('#mapbox-legend-textarea').val())
	 			mapConf.legend = $('#mapbox-legend-textarea').val();
	 		else if(mapConf.legend)
	 			delete mapConf.legend;
	 	}

	 	// don't show full legend
	 	mapConf.legend_full = false;

	 	mapConf.admin = true;

		// save mapConf
		storeConf(mapConf);

		return mapConf;
	}

	function storeConf(conf) {
		var storable = $.extend({}, conf);
		delete storable.callbacks;
		delete storable.preview;
		delete storable.admin;
		$('input[name=map_conf]').val(JSON.stringify(storable));
	}

	function updateMap() {
		updateMapConf();
		if(typeof mappress.maps[map_id] === 'object')
			mapConf.extent = mappress.maps[map_id].getExtent();

		mappress(mapConf);
	}

	function updateMapData() {
		var extent = mappress.maps[map_id].getExtent();
		var center = mappress.maps[map_id].center();
		var zoom = mappress.maps[map_id].zoom();
		$('.current.map-setting .east').text(extent.east);
		$('.current.map-setting .north').text(extent.north);
		$('.current.map-setting .south').text(extent.south);
		$('.current.map-setting .west').text(extent.west);
		$('.current.map-setting .center').text(center);
		$('.current.map-setting .zoom').text(zoom);
	}

	mapConf.callbacks = function() {
		mappress.maps[map_id].addCallback('drawn', function() {
			updateMapData();
		});
		mappress.maps[map_id].addCallback('zoomed', function() {
			updateMapData();
		});
		mappress.maps[map_id].addCallback('panned', function() {
			updateMapData();
		});
	}

	$(document).ready(function() {

		if(!$('#mapbox-metabox').length)
			return;

		var postID = $('input[name=post_ID]').val();

		$('.map-container > .map').attr('id', 'map_' + postID);
		mapConf.containerID = map_id = 'map_' + postID;
		mapConf.postID = postID;

		var layersList = $('#mapbox-metabox .layers-list');
		layersList.sortable();

		mappress(updateMapConf());

		/*
		 * Custom server setup
		 */
		var toggleCustomServer = function() {
			var $mapServerInput = $('input[name="map_data[server]"]:checked');
			var $mapCustomServerInput = $('input[name="map_data[custom_server]"]');
			if($mapServerInput.val() === 'mapbox')
				$mapCustomServerInput.attr('disabled', 'disabled');
			else
				$mapCustomServerInput.attr('disabled', false);
		}
		$('input[name="map_data[server]"]').change(function() {
			toggleCustomServer();
		});
		toggleCustomServer();

		/*
		 * Layer management
		 */
		$('#mapbox-metabox .add-layer').click(function() {
			addLayer();
			return false;
		});
		$('#mapbox-metabox .remove-layer').live('click', function() {
			removeLayer($(this).parents('li'));
			return false;
		});

		// filtering layers opts
		$('#mapbox-metabox .filtering-opts').hide();
		$('#mapbox-metabox .layers-list input.filtering-opt').live('change', function() {
			var optInput = $(this).parent().find(':checked');
			var filteringOpts = optInput.parents('.filter-opts').find('.filtering-opts');
			var opt = optInput.val();
			if(opt != 'fixed') {
				filteringOpts.show();
				if(opt == 'switch') {
					filteringOpts.find('.switch-opts').show();
					filteringOpts.find('.swap-opts').hide();
				} else if(opt == 'swap') {
					filteringOpts.find('.switch-opts').hide();
					filteringOpts.find('.swap-opts').show();
				}
			} else {
				filteringOpts.hide();
			}
		}).change();

		// update swap layer id
		$('#mapbox-metabox .layers-list input.swap_first_layer').live('change', function() {
			$(this).val($(this).parents('li').find('.layer_id').val());
		}).change();

		/*
		 * Map preview button
		 */
		$('#mapbox-metabox .preview-map').click(function() {
			updateMap();
			return false;
		});

		/*
		 * Manage map confs
		 */
		$('#mapbox-metabox .set-map-centerzoom').click(function() {
			updateCenterZoom();
			return false;
		});
		$('#mapbox-metabox .set-map-pan').click(function() {
			updatePanLimits();
			return false;
		});
		$('#mapbox-metabox .set-max-zoom').click(function() {
			updateMaxZoom();
			return false;
		});
		$('#mapbox-metabox .set-min-zoom').click(function() {
			updateMinZoom();
			return false;
		});

		/*
		 * Toggle preview mode
		 */
		 $('#mapbox-metabox .toggle-preview-mode').change(function() {
		 	if($(this).is(':checked'))
		 		mapConf.preview = true;
		 	else
		 		mapConf.preview = false;

		 	updateMap();
		 });

		 $('#mapbox-metabox .enable-geocode').change(function() {
		 	if($(this).is(':checked'))
		 		mapConf.geocode = true;
		 	else
		 		mapConf.geocode = false;

		 	updateMap();
		 });

		function updateCenterZoom() {
			var center = mappress.maps[map_id].center();
			var zoom = mappress.maps[map_id].zoom();
			$('.centerzoom.map-setting span.center').text(center);
			$('.centerzoom.map-setting span.zoom').text(zoom);

			// update inputs
			$('.centerzoom.map-setting input.center-lat').val(center.lat);
			$('.centerzoom.map-setting input.center-lon').val(center.lon);
			$('.centerzoom.map-setting input.zoom').val(zoom);
		}

		function updatePanLimits() {
			var extent = mappress.maps[map_id].getExtent();
			$('.pan-limits.map-setting span.east').text(extent.east);
			$('.pan-limits.map-setting span.north').text(extent.north);
			$('.pan-limits.map-setting span.south').text(extent.south);
			$('.pan-limits.map-setting span.west').text(extent.west);

			// update inputs
			$('.pan-limits.map-setting input.east').val(extent.east);
			$('.pan-limits.map-setting input.north').val(extent.north);
			$('.pan-limits.map-setting input.south').val(extent.south);
			$('.pan-limits.map-setting input.west').val(extent.west);
		}

		function updateMaxZoom() {
			var zoom = mappress.maps[map_id].zoom();
			$('#max-zoom-input').val(zoom);
		}

		function updateMinZoom() {
			var zoom = mappress.maps[map_id].zoom();
			$('#min-zoom-input').val(zoom);
		}

	});

	function addLayer() {
		var layersList = $('#mapbox-metabox .layers-list');
		var layerItem = $(mapbox_metabox_localization.layer_item);
		var layerLength = layersList.find('li').length;

		layerItem.find('.layer_id').attr('name', 'map_data[layers][' + layerLength + '][id]');
		layerItem.find('.fixed_layer, .switch_layer, .swap_layer').attr('name', 'map_data[layers][' + layerLength + '][opts][filtering]');
		layerItem.find('.layer_title').attr('name', 'map_data[layers][' + layerLength + '][title]');
		layerItem.find('.layer_hidden').attr('name', 'map_data[layers][' + layerLength + '][switch_hidden]');

		layerItem.find('.filtering-opts').hide();

		layersList.append(layerItem);
	}

	function removeLayer(layer) {
		layer.remove();
		updateMap();
	}

	function getLayers() {
		var layers = [];
		$('#mapbox-metabox .layers-list li').each(function() {
			layers.push($(this).find('input.layer_id').val());
		});
		return layers;
	}

	function getFilteringLayers() {
		var filtering = {};
		filtering.switch = [];
		filtering.swap = [];
		$('#mapbox-metabox .layers-list li').each(function() {
			var id = $(this).find('input.layer_id').val();
			var filteringOpt = $(this).find('.filtering-opt:checked').val();
			if(filteringOpt == 'switch') {

				var layer = {
					id: id,
					title: $(this).find('input.layer_title').val()
				}
				if($(this).find('input.layer_hidden:checked').length)
					layer.hidden = true;

				filtering.switch.push(layer);

			} else if(filteringOpt == 'swap') {

				var layer = {
					id: id,
					title: $(this).find('input.layer_title').val()
				}
				if($('#mapbox .layers-list .swap_first_layer:checked').val() == id)
					layer.first = true;

				filtering.swap.push(layer);

			}
		});
		return filtering;
	}

})(jQuery);