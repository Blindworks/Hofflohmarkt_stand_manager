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

        // Enqueue our custom map script
        wp_enqueue_script('hm-map-js', HM_PLUGIN_URL . 'assets/js/hm-map.js', array('leaflet-js', 'jquery'), time(), true);

        // Localize script with stand data
        $stands = $this->get_active_stands();
        wp_localize_script('hm-map-js', 'hmMapData', array(
            'stands' => $stands
        ));
    }

    public function render_map()
    {
        return '<div id="hm-map" style="height: 500px; width: 100%;"></div>';
    }

    private function get_active_stands()
    {
        global $wpdb;
        $table_staende = $wpdb->prefix . 'hm_staende';
        $table_space_offers = $wpdb->prefix . 'hm_space_offers';
        $table_stand_kategorien = $wpdb->prefix . 'hm_stand_kategorien';
        $table_kategorien = $wpdb->prefix . 'hm_kategorien';

        $stands = array();

        // 1. Fetch active regular stands
        $results_stands = $wpdb->get_results("
            SELECT id, vorname, nachname, strasse, hausnummer, plz, ort, lat, lng, hofflohmarkt_nest
            FROM $table_staende 
            WHERE active = 1 AND lat IS NOT NULL AND lng IS NOT NULL
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
                'space_description' => $row->space_description,
                'categories' => array() // Space offers don't have categories usually
            );
        }

        return $stands;
    }
}
