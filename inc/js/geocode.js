(function($) {

	jeo.geocode = L.Control.extend({

		options: {
			position: 'topleft'
		},

		onAdd: function(map) {

			var self = this;

			this._map = map;

			this._map.geocode = this;

			this._container = L.DomUtil.create('div', 'jeo-geocode');

			this._$ = $(this._container);

			var map_id = map.map_id;
			var form = '<form id="' + map_id + '_search" class="map-search"><input type="text" placeholder="' + jeo_labels.search_placeholder + '" /></form><div class="geocode-results" />';

			this._$.append($(form));

			this._resultsContainer = this._$.find('.geocode-results');

			// bind submit event
			this._$.find('form').submit(function() {

				self._get($(this).find('input').val());
				return false;

			});

			return this._container;

		},

		_submit: function() {

			this._get(this._$.find('input[type=text]').val());
			return false;

		},

		_get: function(search) {

			var self = this;

			if(typeof search == 'undefined')
				return;

			// nominatim query
			search = search.replace('%20','+');
			var query = {
				q: search,
				polygon_geojson: 1,
				format: 'json'
			}

			// clear previous search on map
			this._clear();

			// set query viewbox from map
			if(this._map.conf.bounds) {
				var viewbox = this._map.conf.bounds[0][1] + ',' + this._map.conf.bounds[1][0] + ',' + this._map.conf.bounds[1][1] + ',' + this._map.conf.bounds[0][0];
				query.viewbox = viewbox;
				query.bounded = 1;
			}

			$.getJSON('http://nominatim.openstreetmap.org/search.php?json_callback=?', query, function(data) {
				if(data.length)
					self._draw(data);
				else
					this._resultsContainer.append('<span class="widget-title">' + jeo_labels.not_found +  '</span>');
			});

		},

		_clear: function() {

			this._resultsContainer.empty();

			if(this._resultsLayer)
				this._map.removeLayer(this._resultsLayer);

		},

		_draw: function(data) {

			var self = this;

			/*
			 * Map
			 */

			var geojson = { type: 'FeatureCollection', features: [] }

			$.each(data, function(i, r) {
				var item = {
					type: 'Feature',
					geometry: r.geojson,
					properties: {
						display_name: r.display_name
					}
				};
				geojson.features.push(item);
			});

			this._resultsLayer = L.geoJson(geojson, {
				onEachFeature: function(f, l) {
					l.bindPopup(f.properties.display_name);
				}
			});

			this._resultsLayer.addTo(this._map);

			/*
			 * List
			 */

			this._resultsContainer.empty();
			this._resultsContainer.append('<a href="#" class="clear-search">' + jeo_labels.clear_search + '</a><span class="widget-title">' + jeo_labels.results_title + '</span><ul />');
			var list = this._resultsContainer.find('ul');
			var item;

			this._resultsContainer.find('.clear-search').click(function() {
				self._$.find('input').val('');
				self._clear();
				return false;
			});

			_.each(data, function(obj) {
				item = $('<li>' + obj.display_name + '</li>')
				list.append(item);
				item.data({
					bounds: {
						north: obj.boundingbox[0],
						west: obj.boundingbox[2],
						south: obj.boundingbox[1],
						east: obj.boundingbox[3]
					}
				}); 
			});

			list.find('li').click(function() {
				var bounds = $(this).data('bounds');
				self._map.fitBounds([
					[bounds.south, bounds.west],
					[bounds.north, bounds.east]
				]);
			});

		}

	});

})(jQuery);