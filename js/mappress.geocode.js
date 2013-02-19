(function($) {

	mappress.geocode = function(map_id) {

		var form = '<form id="' + map_id + '_search" class="map-search"><input type="text" placeholder="' + mappress_labels.search_placeholder + '" /></form>';

		var widget = mappress.widget(map_id, form);
		widget.append('<div class="geocode-results"></div>');

		// bind submit event
		widget.submit(function() {
			mappress.geocode.get(widget.find('input').val(), map_id, widget);
			return false;
		});
	}

	mappress.geocode.get = function(search, map_id, widget) {

		if(typeof search == 'undefined')
			return;

		// nominatim query
		search = search.replace('%20','+');
		var query = {
			q: search,
			polygon_geojson: 1,
			format: 'json'
		}

		// map setup
		if(typeof map_id != 'undefined') {
			// clear previous search on map
			mappress.geocode.clear(map, widget);

			var map = mappress.maps[map_id];

			// set query viewbox from map
			if(map.panLimits) {
				var viewbox = map.panLimits.west + ',' + map.panLimits.north + ',' + map.panLimits.east + ',' + map.panLimits.south;
				query.viewbox = viewbox;
				query.bounded = 1;
			}
		}

		$.getJSON('http://nominatim.openstreetmap.org/search.php?json_callback=?', query, function(data) {
				if(data.length && typeof map_id != 'undefined')
					mappress.geocode.draw(map, data, widget);

				return data;
			}
		);
	}

	mappress.geocode.clear = function(map, widget) {
		if(typeof map == 'undefined')
			return;

		// clear results list
		widget.find('.geocode-results').empty();

		// clear markers
		map.removeLayer('search-layer');

		// clear d3
		$searchLayer = $('#' + map.map_id).find('.search-layer');
		if($searchLayer.length)
			$searchLayer.remove();
	}

	mappress.geocode.draw = function(map, data, widget) {

		if(typeof map == 'undefined')
			return;

		/*
		 * Extract and isolate results
		 */
		// points and linestrings
		var markers = _.filter(data, function(d) { if(d.geojson.type == 'Point' || d.geojson.type == 'LineString') return d; });
		// polygons and multipolygons
		var polygons = _.filter(data, function(d) { if(d.geojson.type == 'Polygon' || d.geojson.type == 'MultiPolygon') return d; });

		/*
		 * Results list
		*/
		if(typeof widget != 'undefined') {
			var resultsContainer = widget.find('.geocode-results');
			if(resultsContainer.length) {
				resultsContainer.empty();
				resultsContainer.append('<a href="#"" class="clear-search">' + mappress_labels.clear_search + '</a><span class="widget-title">' + mappress_labels.results_title + '</span><ul />');
				var list = resultsContainer.find('ul');
				var item;

				resultsContainer.find('.clear-search').click(function() {
					widget.find('input').val('');
					mappress.geocode.clear(map, widget);
					return false;
				});

				// list polygons
				if(polygons.length) {
					_.each(polygons, function(polygon) {
						item = $('<li>' + polygon.display_name + '</li>')
						list.append(item);
						item.data({
							extent: {
								north: polygon.boundingbox[0],
								west: polygon.boundingbox[2],
								south: polygon.boundingbox[1],
								east: polygon.boundingbox[3]
							}
						}); 
					});
				}

				// list markers
				if(markers.length) {
					_.each(markers, function(marker) {
						item = $('<li>' + marker.display_name + '</li>');
						list.append(item);
						item.data({
							loc: {
								lon: parseFloat(marker.lon),
								lat: parseFloat(marker.lat)
							}
						}); 
					});
				}

				list.find('li').click(function() {
					var extent = $(this).data('extent');
					var loc = $(this).data('loc');

					if(extent) {
						map.setExtent(new MM.Extent(extent.north, extent.west, extent.south, extent.east));
					} else if(loc) {
						map.ease.location(loc).zoom(map.zoom()).run(700);
					}
				});
			}
		}

		/*
		 * Draw markers layer
		 */
		if(markers.length) {
			var markerLayer = mapbox.markers.layer();
			markerLayer.named('search-layer');
			mapbox.markers.interaction(markerLayer);
			map.addLayer(markerLayer);

			_.each(markers, function(marker) {
				markerLayer.add_feature({
					geometry: {
						coordinates: [parseFloat(marker.lon), parseFloat(marker.lat)]
					},
					properties: {
						'marker-color': '#000',
						'marker-symbol': 'marker-stroked',
						title: marker.display_name
					}
				})
			});
		}

		/*
		 * Draw polygons with d3js
		 */
		if(polygons.length) {
			var data = {
				"type": "FeatureCollection",
				"features": []
			};
			_.each(polygons, function(polygon) {
				var polygonData = {
					"type": "Feature",
					"id": "34",
					"properties": {
						"name": polygon.display_name
					},
					"geometry": polygon.geojson
				}
				data.features.push(polygonData);
			});
			var polygonLayer = d3layer().data(data);
			map.addLayer(polygonLayer);
		}

	}

	function d3layer() {
	    var f = {}, bounds, feature, collection;
	    var div = d3.select(document.body)
	        .append("div")
	        .attr('class', 'd3-vec search-layer'),
	        svg = div.append('svg'),
	        g = svg.append("g");

	    f.parent = div.node();

	    f.project = function(x) {
	      var point = f.map.locationPoint({ lat: x[1], lon: x[0] });
	      return [point.x, point.y];
	    };

	    var first = true;
	    f.draw = function() {
	      first && svg.attr("width", f.map.dimensions.x)
	          .attr("height", f.map.dimensions.y)
	          .style("margin-left", "0px")
	          .style("margin-top", "0px") && (first = false);

	      path = d3.geo.path().projection(f.project);
	      feature.attr("d", path);
	    };

	    f.data = function(x) {
	        collection = x;
	        bounds = d3.geo.bounds(collection);
	        feature = g.selectAll("path")
	            .data(collection.features)
	            .enter().append("path");
	        return f;
	    };

	    f.extent = function() {
	        return new MM.Extent(
	            new MM.Location(bounds[0][1], bounds[0][0]),
	            new MM.Location(bounds[1][1], bounds[1][0]));
	    };
	    return f;
	}

})(jQuery);