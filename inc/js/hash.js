(function($) {

	jeo.fragment = function() {

        var f = {};
        var _set = function(query) {
            var hash = [];
            _.each(query, function(v, k) {
                hash.push(k + '=' + v);
            });
            document.location.hash = '!/' + hash.join('&');
        };
        f.set = function(options) {
            _set(_.extend(f.get(), options));
        };
        f.get = function(key, defaultVal) {
        	var vars = document.location.hash.substring(3).split('&');
            var hash = {};
            _.each(vars, function(v) {
                var pair = v.split("=");
                if (!pair[0] || !pair[1]) return;
                hash[pair[0]] = unescape(pair[1]);
                if (key && key == pair[0]) {
                    defaultVal = hash[pair[0]];
                }
            });
            return key ? defaultVal : hash;
        };
        f.rm = function(key) {
            var hash = f.get();
            hash[key] && delete hash[key];
            _set(hash);
        };
        return f;

	}

	var fragment = jeo.fragment();

	jeo.fragmentEnabled = false;

	var setupHash = function(map) {

		if(map.conf.disableHash || map.conf.admin || !map.conf.mainMap)
			return false;

		jeo.fragmentEnabled = true;

		var track = _.debounce(function() {
			var c = map.getCenter();
			(isNumber(c.lat) && isNumber(c.lng) && isNumber(map.getZoom())) &&
			fragment.set({loc: [c.lat, c.lng, parseInt(map.getZoom())].join(',')});
		}, 400);
		map.on('zoomend', track);
		map.on('dragend', track);
		fragment.get('full') && _.delay(function() { map.fullscreen.toggle() }, 100);
		fragment.get('iframe') && $('body').addClass('iframe');

		var loc = fragment.get('loc');
		if(loc) {
			loc = loc.split(',');
			if(loc.length = 3) {
				var center = [parseFloat(loc[0]), parseFloat(loc[1])];
				var zoom = parseInt(loc[2]);
				map.setView(center, zoom, {reset: true});
			}
		}

		// fullscreen hash
		map.on('resize', function() {
			if($('body').hasClass('map-fullscreen')) {
				fragment.set({full: true});
			} else {
				fragment.rm('full');
			}
		});
	}

	function isNumber(n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	}

	jeo.mapReady(setupHash);

})(jQuery);