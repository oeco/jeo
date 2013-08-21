(function($) {

	markers = function(map) {

		if(map.conf.disableMarkers || map.conf.admin)
			return false;

		$.getJSON(mappress_markers.ajaxurl,
		{
			action: 'markers_geojson',
			query: mappress_markers.query
		},
		function(geojson) {
			if(geojson === 0)
				return;
			_build(geojson);
		});

		var _build = function(geojson) {

			var icon = L.Icon.extend({});
			var icons = {};
			var parentLayer;

			if(mappress_markers.enable_clustering)
				parentLayer = new L.MarkerClusterGroup();
			else
				parentLayer = new L.layerGroup();

			map.addLayer(parentLayer);

			var layer = L.geoJson(geojson, {
				onEachFeature: function(f, l) {

					if(icons[f.properties.marker.markerId]) {
						var fIcon = icons[f.properties.marker.markerId];
					} else {
						var fIcon = new icon(f.properties.marker);
						icons[f.properties.marker.markerId] = fIcon;
					}

					l.setIcon(fIcon);

					l.bindPopup(f.properties.bubble);

					l.on('mouseover', function(e) {
						e.target.openPopup();
					});
					l.on('mouseout', function(e) {
						e.target.closePopup();
					});
					l.on('click', function(e) {
						window.location = f.properties.url;
						return false;
					});

				}
			});

			map._markerLayer = layer;

			layer.addTo(parentLayer);

			var bounds = layer.getBounds();
			if(!mappress.fragment().get('loc') && mappress_markers.markerextent && bounds.isValid()) {
				map.fitBounds(layer.getBounds());
			}

			mappress.runCallbacks('markersReady', [map]);

			return layer;

		};
	}
	mappress.mapReady(markers);
	mappress.createCallback('markersReady');

})(jQuery);