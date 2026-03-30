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

    // Lucide SVG icons (inline, 16x16)
    var ICONS = {
        store: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-2 2a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 16 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 12 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 8 12a2.7 2.7 0 0 1-1.59-.63.7.7 0 0 0-.82 0A2.7 2.7 0 0 1 4 12a2 2 0 0 1-2-2V7"/></svg>',
        home: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>',
        mapPinPlus: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18.364 4.636a9 9 0 0 1 .203 12.519l-.203.21-4.243 4.242a3 3 0 0 1-4.097.135l-.144-.135-4.244-4.243A9 9 0 0 1 18.364 4.636z"/><path d="M12 8v4"/><path d="M10 10h4"/></svg>',
        tag: '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg>',
        users: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
    };

    // Popup builder
    function buildPopup(stand) {
        var typeClass = 'hm-popup--stand';
        if (stand.nest) {
            typeClass = 'hm-popup--nest';
        } else if (stand.provides_space) {
            typeClass = 'hm-popup--space';
        }

        var html = '<div class="hm-popup ' + typeClass + '">';

        html += '<div class="hm-popup-header">';
        if (stand.nest) {
            html += '<span class="hm-popup-badge hm-popup-badge--nest">' + ICONS.home + ' Nest</span>';
        } else if (stand.provides_space) {
            html += '<span class="hm-popup-badge hm-popup-badge--space">' + ICONS.mapPinPlus + ' Platzangebot</span>';
        } else {
            html += '<span class="hm-popup-badge hm-popup-badge--stand">' + ICONS.store + ' Stand</span>';
        }
        html += '</div>';

        html += '<div class="hm-popup-address">' + stand.address + '</div>';


        if (stand.provides_space) {
            var total = stand.available_spots + (stand.accepted_count || 0);
            if (total > 0) {
                html += '<div class="hm-popup-spots">';
                if (stand.available_spots > 0) {
                    html += '<strong>' + stand.available_spots + '</strong> von ' + total + ' Plätzen verfügbar';
                } else {
                    html += '<span style="color: #d63638; font-weight: bold;">Alle ' + total + ' Plätze vergeben</span>';
                }
                html += '</div>';
            }
            if (stand.space_description) {
                html += '<div class="hm-popup-detail">' + stand.space_description + '</div>';
            }

            if (stand.available_spots > 0) {
                var standIdNum = stand.id.replace('stand_', '').replace('space_', '');
                var standType = stand.id.startsWith('stand_') ? 'stand' : 'space';
                html += '<button class="hm-bewerbung-btn" data-stand-id="' + standIdNum +
                        '" data-stand-type="' + standType + '">Jetzt bewerben</button>';
            }
        }

        if (stand.categories && stand.categories.length > 0) {
            html += '<div class="hm-popup-categories">';
            html += '<div class="hm-popup-categories-label">' + ICONS.tag + ' Kategorien</div>';
            html += '<ul class="hm-popup-categories-list">';
            stand.categories.forEach(function (cat) {
                html += '<li class="hm-popup-category">' + cat + '</li>';
            });
            html += '</ul>';
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
