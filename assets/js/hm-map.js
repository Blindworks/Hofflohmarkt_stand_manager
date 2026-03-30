jQuery(document).ready(function ($) {
    if ($('#hm-map').length === 0) return;

    // Brand colors
    var COLORS = {
        blue:   '#00A3D9',
        yellow: '#F5D000',
        green:  '#7AB648'
    };

    // SVG marker factory – inline SVG pin, no external image requests
    function createSvgIcon(fill) {
        var svg =
            '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="40" viewBox="0 0 28 40">' +
            '<path d="M14 0C6.27 0 0 6.27 0 14c0 10.5 14 26 14 26s14-15.5 14-26C28 6.27 21.73 0 14 0z" ' +
            'fill="' + fill + '" stroke="#fff" stroke-width="1.5"/>' +
            '<circle cx="14" cy="14" r="6" fill="#fff" opacity="0.9"/>' +
            '</svg>';
        return L.divIcon({
            html: svg,
            className: 'hm-svg-marker',
            iconSize: [28, 40],
            iconAnchor: [14, 40],
            popupAnchor: [0, -36]
        });
    }

    var icons = {
        stand: createSvgIcon(COLORS.blue),
        nest:  createSvgIcon(COLORS.yellow),
        space: createSvgIcon(COLORS.green)
    };

    // Map init
    var map = L.map('hm-map', {
        zoomControl: false
    }).setView([50.178, 8.742], 13);

    L.control.zoom({ position: 'topright' }).addTo(map);

    // CartoDB Voyager tiles – cleaner, modern look
    L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19
    }).addTo(map);

    // Popup builder
    function buildPopup(stand) {
        var html = '<div class="hm-popup">';

        html += '<div class="hm-popup-header">';
        if (stand.nest) {
            html += '<span class="hm-popup-badge hm-popup-badge--nest">Nest</span>';
        } else if (stand.provides_space) {
            html += '<span class="hm-popup-badge hm-popup-badge--space">Platzangebot</span>';
        } else {
            html += '<span class="hm-popup-badge hm-popup-badge--stand">Stand</span>';
        }
        html += '</div>';

        html += '<div class="hm-popup-address">' + stand.address + '</div>';

        if (stand.provides_space) {
            if (stand.available_spots) {
                html += '<div class="hm-popup-spots">';
                html += '<strong>' + stand.available_spots + '</strong> freie Plätze';
                html += '</div>';
            }
            if (stand.space_description) {
                html += '<div class="hm-popup-detail">' + stand.space_description + '</div>';
            }

            var standIdNum = stand.id.replace('stand_', '').replace('space_', '');
            var standType = stand.id.startsWith('stand_') ? 'stand' : 'space';
            html += '<button class="hm-bewerbung-btn" data-stand-id="' + standIdNum +
                    '" data-stand-type="' + standType + '">Jetzt bewerben</button>';
        }

        if (stand.categories && stand.categories.length > 0) {
            html += '<div class="hm-popup-categories">';
            stand.categories.forEach(function (cat) {
                html += '<span class="hm-popup-category">' + cat + '</span>';
            });
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    // Render markers
    var markers = L.layerGroup().addTo(map);
    var bounds = L.latLngBounds();

    if (hmMapData.stands && hmMapData.stands.length > 0) {
        hmMapData.stands.forEach(function (stand) {
            var lat = parseFloat(stand.lat);
            var lng = parseFloat(stand.lng);
            if (isNaN(lat) || isNaN(lng)) return;

            var icon = icons.stand;
            if (stand.nest) {
                icon = icons.nest;
            } else if (stand.provides_space) {
                icon = icons.space;
            }

            var marker = L.marker([lat, lng], { icon: icon });
            marker.bindPopup(buildPopup(stand), { maxWidth: 280 });
            markers.addLayer(marker);
            bounds.extend([lat, lng]);
        });

        if (bounds.isValid()) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }
});
