(function($) {

	jeo.rangeSliderFilter = L.Control.extend({

		options: {
			position: 'bottomright',
			sliderProperty: 'range_slider_property'
		},

		onAdd: function(map) {

			var self = this;

			this._map = map;

			this._map.rangeSliderFilter = this;

			this._container = L.DomUtil.create('div', 'jeo-date-range-slider');
			this._$ = $(this._container);

			L.DomEvent.disableClickPropagation(this._container);

			this._build();
			return this._container;

		},

		onRemove: function(map) {

			this._filterMarkers(map._markers);

			this._$.slider.dateRangeSlider('destroy');
			this._$.slider.remove();

		},

		_build: function() {

			if(this._map._markers.length <= 1) {
				this._$.css({'display': 'none'});
				return false;
			}

			var self = this;

			var min = _.min(this._map._markers, function(m) { return m.toGeoJSON().properties[self.options.sliderProperty]; });
			var max = _.max(this._map._markers, function(m) { return m.toGeoJSON().properties[self.options.sliderProperty]; });

			min = min.toGeoJSON().properties[this.options.sliderProperty];
			max = max.toGeoJSON().properties[this.options.sliderProperty];

			this._rangeType = jeo_range_slider_options.rangeType;

			if(this._rangeType == 'dateRangeSlider') {

				if(_.isNumber(min) && _.isNumber(max)) {
					min = new Date(min*1000);
					max = new Date(max*1000);
				} else {
					min = new Date(min);
					max = new Date(max);
				}

			}

			this._rangeOptions = _.extend({
				bounds: {
					min: min,
					max: max
				},
				defaultValues: {
					min: min,
					max: max
				},
				formatter: function(val) {

					if(jeo_range_slider_options.options.dateFormat && _.isDate(val)) {

						val = moment(val).format(jeo_range_slider_options.options.dateFormat);

					}

					return val;
				}
			}, jeo_range_slider_options.options);

			this._$.slider = $('<div class="jeo-date-range-slider-container" />');

			this._$.append(this._$.slider);

			this._$.slider[this._rangeType](this._rangeOptions);

			var self = this;

			this._$.slider.bind('valuesChanging', function(e, data) {

				if(_.isDate(data.values.min)) {
					var min = data.values.min.getTime() / 1000;
					var max = data.values.max.getTime() / 1000;
				} else {
					var min = data.values.min;
					var max = data.values.max;
				}

				var markers = _.filter(self._map._markers, function(m) {
					return (m.toGeoJSON().properties[self.options.sliderProperty] >= min && m.toGeoJSON().properties[self.options.sliderProperty] <= max)
				});

				self._filterMarkers(markers);

				jeo.runCallbacks('rangeSliderFiltered', [markers, this._map]);

			});

			return this._container;

		},

		_filterMarkers: function(markers) {

			var self = this;

			_.each(this._map._markers, function(m, i) {
				//m.setOpacity(0);
				//L.DomUtil.addClass(m._icon, 'leaflet-hidden');
				self._map._markerLayer.removeLayer(m);
			});

			_.each(markers, function(m, i) {
				//m.setOpacity(1);
				//L.DomUtil.removeClass(m._icon, 'leaflet-hidden');
				self._map._markerLayer.addLayer(m);
			});

		}

	});

	jeo.markersReady(function(map) {
		if(map.conf.rangeSliderFilter)
			map.addControl(new jeo.rangeSliderFilter());
	});

	jeo.createCallback('rangeSliderFiltered');

})(jQuery);