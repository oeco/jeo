var codeAddress;

(function($) {

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

    var geocoder;
    var map;
    var markerLayer;
    var marker;
    var markerSet = false;

    function codeLatLon() {
        var latitude = parseFloat(jQuery('#geocode_lat').val());
        var longitude = parseFloat(jQuery('#geocode_lon').val());
        var viewport = jQuery('#geocode_viewport').val();

        if(viewport) {

            viewport = viewport.split('), (');
            var viewportSW = viewport[0];
            var viewportNE = viewport[1];
            viewportSW = viewportSW.substr(2);
            viewportNE = viewportNE.substr(0, viewportNE.length - 2);

            viewportSW = viewportSW.split(', ');
            viewportNE = viewportNE.split(', ');

            viewport = new google.maps.LatLngBounds(
                new google.maps.LatLng(viewportSW[0], viewportSW[1]),
                new google.maps.LatLng(viewportNE[0], viewportNE[1])
                );

            map.fitBounds(viewport);

        }

        if(latitude && longitude) {
            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                position: new google.maps.LatLng(latitude, longitude)
            });
            markerSet = true;
        }
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

    codeAddress = function() {
        var $geoContainer = jQuery('#geolocate');
        var $results = $geoContainer.find('.results');
        var address = document.getElementById('geocode_address').value;

        var query = {q: address, polygon_geojson: 1, format: 'json'};

        $.getJSON('http://nominatim.openstreetmap.org/search.php?json_callback=?', query, function(results) {
            if(results.length) {
                $results.empty();
                if(results.length >= 2) {
                    console.log(results);
                    $results.append('<p><strong>' + results.length + ' resultados encontrados</strong></p><ul></ul>');
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
                }
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
            }
        });

        /*
        geocoder.geocode({'address':address}, function(results, status) {

            if(status == google.maps.GeocoderStatus.OK) {

                $results.empty();

                if(results.length >= 2) {
                    $results.append('<p><strong>' + results.length + ' resultados encontrados</strong></p><ul></ul>');
                    var i = 0;
                    jQuery.each(results, function(index, result) {
                        var address = result.formatted_address;
                        $results.find('ul').append('<li class="result-' + i + '">' + address + '</li>');
                        $results.find('ul li.result-' + i)
                            .data('position', result.geometry.location)
                            .data('viewport', result.geometry.viewport);
                        i++;
                    });
                    $results.find('ul li.result-0').addClass('active');
                } else {
                    $results.append('<p><strong>' + results.length + ' resultado encontrado</strong></p><ul></ul>');
                    var i = 0;
                    jQuery.each(results, function(index, result) {
                        var address = result.formatted_address;
                        $results.find('ul').append('<li class="result-' + i + '">' + address + '</li>');
                        $results.find('ul li.result-' + i)
                            .data('position', result.geometry.location)
                            .data('viewport', result.geometry.viewport);
                        i++;
                    });
                    $results.find('ul li.result-0').addClass('active');
                }

                $results.find('ul li').live('click', function() {
                    $results.find('ul li').removeClass('active');
                    jQuery(this).addClass('active');
                    changeMarker(jQuery(this).data('position'), jQuery(this).data('viewport'));
                    jQuery('input#geocode_address').val(jQuery(this).text());
                });

                map.fitBounds(results[0].geometry.viewport);

                if(!markerSet) {
                    marker = new google.maps.Marker({
                        map: map,
                        draggable: true,
                        position: results[0].geometry.location
                    });

                    jQuery('#geocode_lat').val(results[0].geometry.location.lat());
                    jQuery('#geocode_lon').val(results[0].geometry.location.lng());

                    markerSet = true;
                } else {
                    marker.setPosition(results[0].geometry.location);
                }

                console.log(results);

            }

        });
        */
    }

})(jQuery);