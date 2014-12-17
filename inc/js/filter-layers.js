(function($) {

	jeo.filterLayers = L.Control.extend({

		options: {
			position: 'bottomleft'
		},

		onAdd: function(map) {

			if(!this._map.conf.filteringLayers || this._map.conf.disableInteraction)
				return false;

			var self = this;

			this._map = map;

			this._map.filterLayers = this;

			this._container = L.DomUtil.create('div', 'jeo-filter-layers');

			this._$ = $(this._container);

			this._layers = map.conf.filteringLayers;

			this._swapWidget;
			this._switchWidget;

			this._layers.status = [];
			_.each(this._map.conf.layers, function(layer) {
				self._layers.status.push({
					ID: layer.ID,
					on: true
				});
			});

			this._build();

			return this._container;

		},

		_build: function() {

			var self = this;

			/*
			 * Swapables
			 */
			if(this._layers.swapLayers && this._layers.swapLayers.length >= 2) {
				var swap = this._layers.swapLayers;
				var list = '';
				_.each(swap, function(layer) {
					var attrs = '';
					if(layer.first)
						attrs = 'class="active"';
					else
						self._disableLayer(layer.ID);
					list += '<li data-layer="' + layer.ID + '" ' + attrs + '>' + layer.title + '</li>';
				});

				this._swapWidget = '<ul class="swap-layers">' + list + '</ul>';
				this._$.append(this._swapWidget);

				this._$.on('click', '.swap-layers li', function() {
					self._swapLayer($(this).data('layer'));
				});
			}

			/*
			 * Switchables
			 */
			if(this._layers.switchLayers && this._layers.switchLayers.length) {
				var switchable = this._layers.switchLayers;
				var list = '';
				_.each(switchable, function(layer) {
					var attrs = 'class="active"';
					if(layer.hidden) {
						attrs = '';
						self._disableLayer(layer.ID);
					}
					list += '<li data-layer="' + layer.ID + '" ' + attrs + '>' + layer.title + '</li>';
				});

				this._switchWidget = '<ul class="switch-layers">' + list + '</ul>';
				this._$.append(this._switchWidget);

				this._$.on('click', '.switch-layers li', function() {
					self._switchLayer($(this).data('layer'));
				});

			}

			this._update();

			return this._container;

		},

		_switchLayer: function(layer) {

			if(this._getStatus(layer).on) {

				this._disableLayer(layer);
				this._$.find('li[data-layer="' + layer + '"]').removeClass('active');

			} else {

				this._enableLayer(layer);
				this._$.find('li[data-layer="' + layer + '"]').addClass('active');

			}

			this._update();

		},

		_swapLayer: function(layer) {

			var self = this;

			if(this._getStatus(layer).on)
				return;

			_.each(this._layers.swapLayers, function(swapLayer) {

				if(swapLayer.ID == layer) {

					self._enableLayer(layer);

					self._$.find('li[data-layer="' + layer + '"]').addClass('active');

				} else {

					if(self._getStatus(swapLayer.ID).on) {

						self._disableLayer(swapLayer.ID);

						self._$.find('li[data-layer="' + swapLayer.ID + '"]').removeClass('active');

					}

				}
			});
			this._update();

		},

		_disableLayer: function(layer) {

			this._layers.status[this._getStatusIndex(layer)] = {
				ID: layer,
				on: false
			};

		},

		_enableLayer: function(layer) {

			this._layers.status[this._getStatusIndex(layer)] = {
				ID: layer,
				on: true
			};

		},

		_update: function() {

			this._map.$.find('.map-tooltip').hide();
			jeo.loadLayers(this._map, jeo.parseLayers(this._map, this._getActiveLayers()));

		},

		_getStatus: function(layer) {
			return _.find(this._layers.status, function(l) { return layer == l.ID; });
		},

		_getStatusIndex: function(layer) {

			var index;
			_.each(this._layers.status, function(l, i) {
				if(layer == l.ID)
					index = i;
			});
			return index;

		},

		_getActiveLayers: function() {
			var self = this;
			var activeLayers = [];
			_.each(this._layers.status, function(layer) {
				if(layer.on) {
					var actualLayer = _.find(self._map.conf.layers, function(l) { return l.ID == layer.ID; });
					activeLayers.push(actualLayer);
				}
			});
			return activeLayers;

		}

	});

})(jQuery);
