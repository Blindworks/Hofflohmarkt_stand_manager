<?php

class HM_DB
{
    public static function install()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // 1. Hofflohmärkte Table
        $table_hofflohmaerkte = $wpdb->prefix . 'hm_hofflohmaerkte';
        $sql_hofflohmaerkte = "CREATE TABLE $table_hofflohmaerkte (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            date date DEFAULT NULL,
            active boolean DEFAULT 1,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 2. Kategorien Table
        $table_kategorien = $wpdb->prefix . 'hm_kategorien';
        $sql_kategorien = "CREATE TABLE $table_kategorien (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            active boolean DEFAULT 1,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 3. Stände Table
        $table_staende = $wpdb->prefix . 'hm_staende';
        $sql_staende = "CREATE TABLE $table_staende (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vorname varchar(100) NOT NULL,
            nachname varchar(100) NOT NULL,
            strasse varchar(255) NOT NULL,
            hausnummer varchar(20) NOT NULL,
            plz varchar(10) NOT NULL,
            ort varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            hofflohmarkt_nest boolean DEFAULT 0,
            active boolean DEFAULT 0,
            hofflohmarkt_id mediumint(9) DEFAULT NULL,
            special_area_id mediumint(9) DEFAULT NULL,
            lat decimal(10, 8) DEFAULT NULL,
            lng decimal(11, 8) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY special_area_id (special_area_id)
        ) $charset_collate;";

        // Cleanup: Remove old columns from hm_staende if they exist
        $columns_to_remove = ['provides_space', 'space_description', 'available_spots'];
        foreach ($columns_to_remove as $col) {
            $check_col = $wpdb->get_results("SHOW COLUMNS FROM $table_staende LIKE '$col'");
            if (!empty($check_col)) {
                $wpdb->query("ALTER TABLE $table_staende DROP COLUMN $col");
            }
        }

        // 4. Stand-Kategorien Pivot Table
        $table_stand_kategorien = $wpdb->prefix . 'hm_stand_kategorien';
        $sql_stand_kategorien = "CREATE TABLE $table_stand_kategorien (
            stand_id mediumint(9) NOT NULL,
            kategorie_id mediumint(9) NOT NULL,
            PRIMARY KEY  (stand_id, kategorie_id)
        ) $charset_collate;";

        // 5. Space Offers Table (Platzangebote)
        $table_space_offers = $wpdb->prefix . 'hm_space_offers';
        $sql_space_offers = "CREATE TABLE $table_space_offers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vorname varchar(100) NOT NULL,
            nachname varchar(100) NOT NULL,
            strasse varchar(255) NOT NULL,
            hausnummer varchar(20) NOT NULL,
            plz varchar(10) NOT NULL,
            ort varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            available_spots int DEFAULT 0,
            space_description text DEFAULT NULL,
            active boolean DEFAULT 0,
            lat decimal(10, 8) DEFAULT NULL,
            lng decimal(11, 8) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 6. Bewerbungen Table
        $table_bewerbungen = $wpdb->prefix . 'hm_bewerbungen';
        $sql_bewerbungen = "CREATE TABLE $table_bewerbungen (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            stand_id mediumint(9) NOT NULL,
            stand_type varchar(20) NOT NULL DEFAULT 'stand',
            vorname varchar(100) NOT NULL,
            nachname varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            telefon varchar(50) DEFAULT NULL,
            nachricht text DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            action_token varchar(64) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY stand_id (stand_id),
            KEY stand_type (stand_type),
            KEY action_token (action_token)
        ) $charset_collate;";

        // 7. Flohmarkt-Hubs Table
        $table_special_areas = $wpdb->prefix . 'hm_special_areas';
        $sql_special_areas = "CREATE TABLE $table_special_areas (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            strasse varchar(255) DEFAULT NULL,
            hausnummer varchar(20) DEFAULT NULL,
            plz varchar(10) DEFAULT NULL,
            ort varchar(100) DEFAULT NULL,
            capacity int DEFAULT 0,
            description text DEFAULT NULL,
            lat decimal(10, 8) DEFAULT NULL,
            lng decimal(11, 8) DEFAULT NULL,
            active boolean DEFAULT 1,
            hofflohmarkt_id mediumint(9) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_hofflohmaerkte);
        dbDelta($sql_kategorien);
        dbDelta($sql_staende);
        dbDelta($sql_stand_kategorien);
        dbDelta($sql_space_offers);
        dbDelta($sql_bewerbungen);
        dbDelta($sql_special_areas);
    }
}
