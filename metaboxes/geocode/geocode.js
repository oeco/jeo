(function($) {

    $.fn.geolocate = function() {

        var mapCanvas = jQuery(this);
        if(mapCanvas.hasClass('geoready'))
            return false;
        mapCanvas.addClass('geoready');
        if(mapCanvas.length) {
            var myOptions = {
                center: new google.maps.LatLng(-14.235004, -51.92527999999999),
                zoom: 3,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            map = new google.maps.Map(mapCanvas[0], myOptions);
            geocoder = new google.maps.Geocoder();

            codeLatLon();

            if(!markerSet) {
                marker = new google.maps.Marker({
                    map: map,
                    draggable: true,
                    position: new google.maps.LatLng(-14.235004, -51.92527999999999)
                });
                markerSet = true;
            }

            google.maps.event.addListener(marker, 'position_changed', function() {
                jQuery('#geocode_lat').val(marker.position.lat());
                jQuery('#geocode_lon').val(marker.position.lng());
            });

            google.maps.event.addListener(map, 'bounds_changed', function() {
                jQuery('#geocode_viewport').val(map.getBounds());
            })
        }

    };

})(jQuery);

var geocoder;
var maps;
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

function codeAddress() {
    var $geoContainer = jQuery('#geolocate');
    var $results = $geoContainer.find('.results');
    var address = document.getElementById('geocode_address').value;
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
}

function changeMarker(position, viewport) {
    map.setCenter(position);
    map.fitBounds(viewport);
    marker.setPosition(position);
}