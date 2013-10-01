(function($) {

	jeo.fullscreen = L.Control.extend({

		options: {
			position: 'topleft'
		},

		onAdd: function(map) {

			this._container = L.DomUtil.create('div', 'jeo-fullscreen leaflet-bar leaflet-control');
			this._$ = $(this._container);

			this._map = map;

			this._map.fullscreen = this;

			this._$.append('<a class="map-fullscreen" href="#fullscreen"></a>');

			this._bindEvents();

			return this._container;

		},

		_bindEvents: function() {
			var self = this;

			this._$.click(function() {

				self.toggle();
				return false;

			});
		},

		toggle: function() {

			var container;

			if(this._map.$.parents('.content-map').length)
				container = this._map.$.parents('.content-map');
			else
				container = this._map.$.parents('.map-container');

			if(container.hasClass('fullscreen-map')) {

				$('body').removeClass('map-fullscreen');
				container.removeClass('fullscreen-map');

			} else {

				$('body').addClass('map-fullscreen');
				container.addClass('fullscreen-map');

			}

			this._map.invalidateSize(true);

		}
	});

})(jQuery);