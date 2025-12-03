jQuery(document).ready(function ($) {
    if ($('#hm-map').length > 0) {
        var map = L.map('hm-map').setView([51.1657, 10.4515], 6); // Default center Germany

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        var markers = L.layerGroup().addTo(map);
        var bounds = L.latLngBounds();

        var blueIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var redIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        var greenIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });

        if (hmMapData.stands && hmMapData.stands.length > 0) {
            hmMapData.stands.forEach(function (stand) {
                var lat = parseFloat(stand.lat);
                var lng = parseFloat(stand.lng);

                if (!isNaN(lat) && !isNaN(lng)) {
                    var icon = blueIcon;
                    if (stand.nest) {
                        icon = redIcon;
                    } else if (stand.provides_space) {
                        icon = greenIcon;
                    }

                    var marker = L.marker([lat, lng], { icon: icon });

                    var popupContent = stand.address + '<br>';

                    if (stand.nest) {
                        popupContent += '<br><strong>Hofflohmarkt Nest!</strong><br>';
                    }

                    if (stand.provides_space) {
                        popupContent += '<br><strong style="color: green;">Bietet Platz an!</strong><br>';
                        if (stand.available_spots) {
                            popupContent += 'Freie Plätze: ' + stand.available_spots + '<br>';
                        }
                        if (stand.space_description) {
                            popupContent += '<em>' + stand.space_description + '</em><br>';
                        }
                        // Add Bewerbung button
                        var standIdNum = stand.id.replace('stand_', '').replace('space_', '');
                        var standType = stand.id.startsWith('stand_') ? 'stand' : 'space';
                        popupContent += '<br><button class="hm-bewerbung-btn" data-stand-id="' + standIdNum + '" data-stand-type="' + standType + '">Bewerbung</button>';
                    }

                    if (stand.categories && stand.categories.length > 0) {
                        popupContent += '<br><em>' + stand.categories.join(', ') + '</em>';
                    }

                    marker.bindPopup(popupContent);
                    markers.addLayer(marker);
                    bounds.extend([lat, lng]);
                }
            });

            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }
    }
});
