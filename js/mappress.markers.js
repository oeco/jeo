(function($) {

	mappress.markers = function(map) {

		if(!mappress_markers.query)
			return;

		var markers = mappress.markers;
		var markersLayer = mapbox.markers.layer();
		var features;
		var fragment = false;
		var listPost;

		// setup sidebar
		map.$.parents('.map-container').wrapAll('<div class="content-map" />');
		map.$.parents('.content-map').prepend('<div class="map-sidebar"><div class="sidebar-inner"></div></div>');
		map.$.sidebar = map.$.parents('.content-map').find('.sidebar-inner');
		map.dimensions = new MM.Point(map.parent.offsetWidth, map.parent.offsetHeight);
		map.draw();

		if(typeof mappress.fragment === 'function')
			fragment = mappress.fragment();

		$.getJSON(mappress_markers.ajaxurl,
		{
			action: 'markers_geojson',
			query: mappress_markers.query
		},
		function(geojson) {
			if(geojson === 0)
				return;
			markers.build(geojson);
		});

		mappress.markers.build = function(geojson) {

			map.addLayer(markersLayer);

			features = geojson.features;

			map.features = features;
			map.markersLayer = markersLayer;

			markersLayer
				.features(features)
				.key(function(f) {
					return f.properties.id;
				})
				.factory(function(x) {

					if(!markers.hasLocation(x))
						return;

					var e = document.createElement('div');

					console.log(x.properties.marker_class);

					$(e).addClass('story-points')
						.addClass(x.properties.id)
						.addClass(x.properties.marker_class)
						.attr('data-publisher', x.properties.source);

					$(e).data('feature', x);

					// styles
					$(e).css({
						'background': 'url(' + x.properties.marker.url + ')',
						'width': x.properties.marker.width,
						'height': x.properties.marker.height,
						'margin-top': -x.properties.marker.height,
						'margin-left': -(x.properties.marker.width/2)
					});

					// POPUP

					var o = document.createElement('div');
					o.className = 'popup clearfix';
					$(o).css({
						'bottom': x.properties.marker.height + 11
					});
					e.appendChild(o);
					var content = document.createElement('div');
					content.className = 'story';
					content.innerHTML = x.properties.bubble;
					o.appendChild(content);

					$(e).click(function() {

						markers.open(x, false);

					});

					return e;

				});

		};

		mappress.markers.getMarker = function(id) {
			return _.find(features, function(m) { return m.properties.id === id; });
		}

		mappress.markers.open = function(marker, silent) {

			if(map.conf.sidebar === false) {
				window.location = marker.properties.url;
				return false;
			}

			if(!markers.fromMap(marker))
				return;

			// if marker is string, get object
			if(typeof marker === 'string') {
				marker = _.find(features, function(m) { return m.properties.id === marker; });
			}

			if(fragment) {
				if(!silent)
					fragment.set({story: marker.properties.id});
			}

			if(typeof _gaq !== 'undefined') {
				_gaq.push(['_trackPageView', location.pathname + location.search + '#!/story=' + marker.properties.id]);
			}

			if(!silent) {
				var zoom;
				var center;
				if(markers.hasLocation(marker)) { 
					center = {
						lat: marker.geometry.coordinates[1],
						lon: marker.geometry.coordinates[0]
					}
					zoom = 7;
					if(map.conf.maxZoom < 7)
						zoom = map.conf.maxZoom;
				} else {
					center = map.conf.center;
					zoom = map.conf.zoom;
				}
				map.ease.location(center).zoom(zoom).optimal(0.9, 1.42, function() {
					if(fragment) {
						fragment.rm('loc');
					}
				});				
			}

		};

		mappress.markers.hasLocation = function(marker) {
			if(marker.geometry.coordinates[0] ===  0 || !marker.geometry.coordinates[0])
				return false;
			else
				return true;
		}

		mappress.markers.fromMap = function(x) {
			// if marker is string, get object
			if(typeof x === 'string') {
				x = _.find(features, function(m) { return m.properties.id === x; });
			}

			if(!x)
				return false;

			if(!x.properties.maps)
				return true;

			return _.find(x.properties.maps, function(markerMap) { return 'map_' + markerMap == map.currentMapID; });
		}
	}

})(jQuery);