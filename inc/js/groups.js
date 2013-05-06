var groups = {};

(function($) {

	mappress.group = function(conf) {

		var group = {};

		var fragment = mappress.fragment();

		var _init = function() {

			if(conf.mainMap)
				$('body').addClass('loading-map');

			if(!conf.postID && typeof conf === 'object') { // conf ready
				return group.build(conf);
			}

			$.getJSON(mappress_groups.ajaxurl,
				{
					action: 'mapgroup_data',
					group_id: conf.postID
				},
				function(mapConf) {
					mapConf = _.extend(mapConf, conf);
					group.build(mapConf);
				});

		}

		if($.isReady) {
			_init();
		} else {
			$(document).ready(_init);
		}

		group.build = function(data) {

			group.$ = $('#mapgroup_' + data.postID);

			// store maps data
			group.mapsData = data.maps;

			// nodes
			group.$.nav = group.$.find('.map-nav');
			group.$.map = group.$.find('.map');

			group.containerID = group.$.map.attr('id');

			// prepare first map and group conf
			var firstMapID = group.$.nav.find('li:first-child a').data('map');
			if(fragment.get('map'))
				firstMapID = fragment.get('map');

			group.conf = _.extend(data, mappress.convertMapConf(group.mapsData[firstMapID]));
			delete group.conf.postID;

			// set mappress conf containerID to group id
			group.conf.containerID = group.containerID;

			// force main map
			group.conf.mainMap = true;

			// store current map id
			group.currentMapID = firstMapID;

			// build group
			group.map = mappress(group.conf);
			group.map.isGroup = true;
			group.map.currentMapID = firstMapID;

			if(mappress.fragmentEnabled)
				fragment.set({'map': firstMapID});

			group.updateUI();

			group.$.nav.find('li a').click(function() {

				// disable "more" tab click
				if($(this).hasClass('toggle-more'))
					return false;

				if($(this).hasClass('active'))
					return false;

				var mapID = $(this).data('map');

				// update layers
				group.update(mapID);

				// update ui
				group.updateUI();

				return false;
			});

			mappress.runCallbacks('groupReady', [group]);

		}

		group.updateUI = function() {

			var mapID = group.currentMapID;
			var $navEl = group.$.nav.find('[data-map="' + mapID + '"]');
			var moreLabel = group.$.nav.find('.more-tab > a').text(); // store more label

			group.$.nav.find('li a').removeClass('active');
			$navEl.addClass('active');

			// ui behaviour for more tab
			if($navEl.parent().hasClass('more-item')) {
				group.$.nav.find('.more-tab > a').addClass('active').text($navEl.text());
			} else {
				group.$.nav.find('.more-tab > a').removeClass('active').text(moreLabel);
			}

		}

		group.update = function(mapID) {

			group.map = mappress.maps[group.containerID];

			// prepare new conf and layers
			var conf = mappress.convertMapConf(group.mapsData[mapID]);
			var layers = mappress.setupLayers(conf.layers);

			// store new conf
			group.map.conf = conf;

			// update current map id
			group.currentMapID = mapID;
			group.map.currentMapID = mapID;

			var fragmentEnabled = mappress.fragmentEnabled;
			if(fragmentEnabled)
				fragment.set({'map': mapID});

			mapbox.load(layers, function(data) {

				group.map.setLayerAt(0, data.layer);
				group.map.interaction.refresh();

				// clear widgets

				group.map.$.widgets.empty();

				if(conf.geocode)
					mappress.geocode(group.map);

				if(conf.filteringLayers)
					mappress.filterLayers(group.map);

				group.map.ui.legend.remove();

				if(conf.legend)
					group.map.ui.legend.add().content(conf.legend);

				if(conf.legend_full)
					mappress.enableDetails(group.map, conf.legend, conf.legend_full);

				mappress.runCallbacks('groupChanged', [mapID, group]);

			});
		}

		groups[group.id] = group;
		return group;
	}

	mappress.createCallback('groupReady');
	mappress.createCallback('groupChanged');

})(jQuery);