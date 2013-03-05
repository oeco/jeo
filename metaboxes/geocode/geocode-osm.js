var codeAddress;

(function($) {

    var geocoder;
    var map;
    var markerLayer;

    $.fn.geolocate = function() {

        var mapCanvas = $(this);
        if(mapCanvas.hasClass('geoready'))
            return false;
        mapCanvas.addClass('geoready');
        if(mapCanvas.length) {
            map = mapbox.map(mapCanvas.attr('id'));
            map.addLayer(new MM.TemplatedLayer('http://a.tile.openstreetmap.org/{Z}/{X}/{Y}.png'));
            map.zoom(1);
            markerLayer = mapbox.markers.layer();
            map.addLayer(markerLayer);
        }

        var lat = parseFloat(jQuery('#geocode_lat').val());
        var lon = parseFloat(jQuery('#geocode_lon').val());
        if(lat && lon) {
            updateMap({lat: lat, lon: lon});
        }

    };

    codeAddress = function() {
        var $geoContainer = jQuery('#geolocate');
        var $results = $geoContainer.find('.results');
        var address = document.getElementById('geocode_address').value;

        var query = {q: address, polygon_geojson: 1, format: 'json'};

        $.getJSON('http://nominatim.openstreetmap.org/search.php?json_callback=?', query, function(results) {
            $results.empty();
            if(results.length) {
                $results.append('<p><strong>' + results.length + ' ' + geocode_labels.results_found +'</strong></p><ul></ul>');
                var i = 0;
                jQuery.each(results, function(index, result) {
                    var address = result.display_name;
                    $results.find('ul').append('<li class="result-' + i + '">' + address + '</li>');
                    $results.find('ul li.result-' + i)
                        .data('lat', parseFloat(result.lat))
                        .data('lon', parseFloat(result.lon))
                        .data('boundingbox', result.boundingbox);
                    i++;
                });
                $results.find('ul li').live('click', function() {
                    $results.find('ul li').removeClass('active');
                    jQuery(this).addClass('active');
                    var position = {lat: $(this).data('lat'), lon: $(this).data('lon')};
                    var boundingbox = $(this).data('boundingbox');
                    var extent = new MM.Extent(parseFloat(boundingbox[1]), parseFloat(boundingbox[2]), parseFloat(boundingbox[0]), parseFloat(boundingbox[3]));
                    updateMap(position, extent);
                    updateInputs(position);
                    jQuery('input#geocode_address').val(jQuery(this).text());
                });
            } else {
                $results.append('<p>' + geocode_labels.not_found + '</p>');
            }
        });
    }

    function updateMap(position, extent) {
        map.center(position);
        if(typeof extent !== 'undefined')
            map.setExtent(extent);
        var features = [{geometry: { coordinates: [position.lon, position.lat] }}];
        markerLayer.features(features);
    }

    function updateInputs(position) {
        jQuery('#geocode_lat').val(position.lat);
        jQuery('#geocode_lon').val(position.lon);
    }

})(jQuery);