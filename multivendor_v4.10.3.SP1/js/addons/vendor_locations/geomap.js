(function (_, $) {
    var methods = {
        init: function () {
            var $elems = $(this);

            if (!window.google) {
                $.ceGeolocate('loadMapApi')
                    .done(function () {
                        methods._init($elems);
                    });
            } else {
                methods._init($elems);
            }

            return $elems;
        },

        _init: function ($elems) {
            return $elems.each(function () {
                var $elem = $(this),
                    marker_selector = $elem.data('caGeomapMarkerSelector'),
                    max_zoom = parseInt($elem.data('caGeomapMaxZoom'), 10),
                    map = new google.maps.Map(this, {
                        maxZoom: max_zoom
                    }),
                    markers_bounds = new google.maps.LatLngBounds(),
                    markers = [];

                $(marker_selector).each(function () {
                    var $marker = $(this),
                        lat = parseFloat($marker.data('caGeomapMarkerLat')),
                        lng = parseFloat($marker.data('caGeomapMarkerLng')),
                        url = $marker.data('caGeomapMarkerUrl'),
                        label = $marker.data('caGeomapMarkerLabel');

                    if (lat && lng) {
                        var marker = new google.maps.Marker({
                            position: {lat: lat, lng: lng},
                            map: map,
                            label: label
                        });

                        if (url) {
                            marker.addListener('click', function() {
                                $.redirect(url, false);
                            });
                        }

                        markers.push(marker);
                        markers_bounds.extend({lat: lat, lng: lng});
                    }
                });


                $.getScript('js/addons/vendor_locations/lib/markerclusterer/markerclusterer.js', function () {
                    var markerCluster = new MarkerClusterer(map, markers, {
                        imagePath: 'js/addons/vendor_locations/lib/markerclusterer/m'
                    });

                    map.setCenter(markers_bounds.getCenter());
                    map.fitBounds(markers_bounds);
                })
            });
        }
    };

    $.fn.ceGeomap = function(method) {
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('ty.geomap: method ' +  method + ' does not exist');
        }
    };
})(Tygh, Tygh.$);
