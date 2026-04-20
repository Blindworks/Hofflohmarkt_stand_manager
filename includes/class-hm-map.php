<?php

class HM_Map
{

    public function __construct()
    {
        add_shortcode('hm_map', array($this, 'render_map'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_map_assets'));
    }

    public function enqueue_map_assets()
    {
        // Enqueue Leaflet CSS and JS
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

        // Enqueue our custom styles and map script
        wp_enqueue_style('hm-style-css', HM_PLUGIN_URL . 'assets/css/hm-style.css', array('leaflet-css'), time());
        wp_enqueue_script('hm-map-js', HM_PLUGIN_URL . 'assets/js/hm-map.js', array('leaflet-js', 'jquery'), time(), true);

        // Localize script with stand data
        $stands = $this->get_active_stands();
        wp_localize_script('hm-map-js', 'hmMapData', array(
            'stands' => $stands
        ));
    }

    public function render_map()
    {
        $html  = '<div class="hm-map-wrapper">';
        $html .= '<div id="hm-map"></div>';
        $html .= '<div class="hm-map-legend">';
        $html .= '<div class="hm-legend-title">Legende</div>';
        $html .= '<div class="hm-legend-item"><span class="hm-legend-marker hm-legend-marker--blue"></span><span>Stand</span></div>';
        $html .= '<div class="hm-legend-item"><span class="hm-legend-marker hm-legend-marker--yellow"></span><span>Hofflohmarkt Nest</span></div>';
        $html .= '<div class="hm-legend-item"><span class="hm-legend-marker hm-legend-marker--green"></span><span>Flohmarkt-Hub</span></div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function get_active_stands()
    {
        global $wpdb;
        $table_staende = $wpdb->prefix . 'hm_staende';
        $table_space_offers = $wpdb->prefix . 'hm_space_offers';
        $table_stand_kategorien = $wpdb->prefix . 'hm_stand_kategorien';
        $table_kategorien = $wpdb->prefix . 'hm_kategorien';
        $table_areas = $wpdb->prefix . 'hm_special_areas';

        $stands = array();

        // 1. Fetch active regular stands (excluding those assigned to a Flohmarkt-Hub)
        $results_stands = $wpdb->get_results("
            SELECT id, vorname, nachname, strasse, hausnummer, plz, ort, lat, lng, hofflohmarkt_nest
            FROM $table_staende
            WHERE active = 1 AND lat IS NOT NULL AND lng IS NOT NULL
              AND (special_area_id IS NULL OR special_area_id = 0)
        ");

        foreach ($results_stands as $row) {
            // Fetch categories for this stand
            $cats = $wpdb->get_col($wpdb->prepare("
                SELECT k.name 
                FROM $table_kategorien k 
                JOIN $table_stand_kategorien sk ON k.id = sk.kategorie_id 
                WHERE sk.stand_id = %d
            ", $row->id));

            $stands[] = array(
                'id' => 'stand_' . $row->id,
                'title' => $row->vorname . ' ' . $row->nachname,
                'address' => $row->strasse . ' ' . $row->hausnummer . ', ' . $row->plz . ' ' . $row->ort,
                'lat' => $row->lat,
                'lng' => $row->lng,
                'nest' => (int) $row->hofflohmarkt_nest === 1,
                'provides_space' => false,
                'available_spots' => 0,
                'space_description' => '',
                'categories' => $cats
            );
        }

        // 2. Fetch active space offers
        $results_spaces = $wpdb->get_results("
            SELECT id, vorname, nachname, strasse, hausnummer, plz, ort, lat, lng, available_spots, space_description
            FROM $table_space_offers 
            WHERE active = 1 AND lat IS NOT NULL AND lng IS NOT NULL
        ");

        foreach ($results_spaces as $row) {
            $stands[] = array(
                'id' => 'space_' . $row->id,
                'title' => $row->vorname . ' ' . $row->nachname,
                'address' => $row->strasse . ' ' . $row->hausnummer . ', ' . $row->plz . ' ' . $row->ort,
                'lat' => $row->lat,
                'lng' => $row->lng,
                'nest' => false,
                'provides_space' => true,
                'available_spots' => (int) $row->available_spots,
                'accepted_count' => (int) HM_Bewerbungen::get_accepted_count($row->id, 'space'),
                'space_description' => $row->space_description,
                'categories' => array() // Space offers don't have categories usually
            );
        }

        // 3. Fetch active special areas with category counters
        $results_areas = $wpdb->get_results("
            SELECT id, name, strasse, hausnummer, plz, ort, lat, lng, capacity, description
            FROM $table_areas
            WHERE active = 1 AND lat IS NOT NULL AND lng IS NOT NULL
        ");

        foreach ($results_areas as $row) {
            $total_stands = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_staende WHERE special_area_id = %d AND active = 1",
                $row->id
            ));

            $category_counts = $wpdb->get_results($wpdb->prepare(
                "SELECT k.name, COUNT(DISTINCT s.id) AS cnt
                 FROM $table_staende s
                 JOIN $table_stand_kategorien sk ON sk.stand_id = s.id
                 JOIN $table_kategorien k ON k.id = sk.kategorie_id
                 WHERE s.special_area_id = %d AND s.active = 1
                 GROUP BY k.id, k.name
                 ORDER BY cnt DESC, k.name ASC",
                $row->id
            ));

            $cat_list = array();
            foreach ($category_counts as $c) {
                $cat_list[] = array('name' => $c->name, 'count' => (int) $c->cnt);
            }

            $stands[] = array(
                'id' => 'area_' . $row->id,
                'type' => 'area',
                'title' => $row->name,
                'address' => trim($row->strasse . ' ' . $row->hausnummer . ', ' . $row->plz . ' ' . $row->ort),
                'lat' => $row->lat,
                'lng' => $row->lng,
                'nest' => false,
                'provides_space' => false,
                'capacity' => (int) $row->capacity,
                'total_stands' => $total_stands,
                'description' => $row->description,
                'category_counts' => $cat_list,
                'categories' => array(),
            );
        }

        return $stands;
    }
}
