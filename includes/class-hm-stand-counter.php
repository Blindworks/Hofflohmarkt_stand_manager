<?php

class HM_Stand_Counter
{
    public function __construct()
    {
        add_shortcode('hm_stand_counter', array($this, 'render'));
    }

    public function render($atts)
    {
        $atts = shortcode_atts(array(
            'label'            => 'Angemeldete Stände',
            'nest_label'       => 'davon Nester',
            'categories_label' => 'Meldungen nach Kategorie',
        ), $atts, 'hm_stand_counter');

        wp_enqueue_style('hm-style-css', HM_PLUGIN_URL . 'assets/css/hm-style.css', array(), time());

        $count      = $this->get_count();
        $nest_count = $this->get_nest_count();
        $categories = $this->get_category_counts();

        $html  = '<div class="hm-counter-wrapper"><div class="hm-counter">';
        $html .= sprintf(
            '<div class="hm-counter-number">%d</div><div class="hm-counter-label">%s</div>',
            $count,
            esc_html($atts['label'])
        );

        $html .= sprintf(
            '<div class="hm-counter-subline"><span class="hm-counter-subline-number">%d</span> <span class="hm-counter-subline-label">%s</span></div>',
            $nest_count,
            esc_html($atts['nest_label'])
        );

        $has_cat_hits = false;
        foreach ($categories as $cat) {
            if ((int) $cat->count > 0) {
                $has_cat_hits = true;
                break;
            }
        }

        if ($has_cat_hits) {
            $html .= '<div class="hm-counter-categories-wrapper">';
            $html .= sprintf(
                '<div class="hm-counter-categories-label">%s</div>',
                esc_html($atts['categories_label'])
            );
            $html .= '<div class="hm-counter-categories">';
            foreach ($categories as $cat) {
                $html .= sprintf(
                    '<span class="hm-counter-category-chip">%s <span class="hm-counter-category-count">%d</span></span>',
                    esc_html($cat->name),
                    (int) $cat->count
                );
            }
            $html .= '</div></div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    private function get_count()
    {
        global $wpdb;
        $staende = $wpdb->prefix . 'hm_staende';
        $events  = $wpdb->prefix . 'hm_hofflohmaerkte';

        $sql = "SELECT COUNT(*) FROM {$staende} s
                INNER JOIN {$events} h ON s.hofflohmarkt_id = h.id
                WHERE s.active = %d AND h.active = %d";

        return (int) $wpdb->get_var($wpdb->prepare($sql, 1, 1));
    }

    private function get_nest_count()
    {
        global $wpdb;
        $staende = $wpdb->prefix . 'hm_staende';
        $events  = $wpdb->prefix . 'hm_hofflohmaerkte';

        $sql = "SELECT COUNT(*) FROM {$staende} s
                INNER JOIN {$events} h ON s.hofflohmarkt_id = h.id
                WHERE s.hofflohmarkt_nest = %d AND s.active = %d AND h.active = %d";

        return (int) $wpdb->get_var($wpdb->prepare($sql, 1, 1, 1));
    }

    private function get_category_counts()
    {
        global $wpdb;
        $kategorien       = $wpdb->prefix . 'hm_kategorien';
        $stand_kategorien = $wpdb->prefix . 'hm_stand_kategorien';
        $staende          = $wpdb->prefix . 'hm_staende';
        $events           = $wpdb->prefix . 'hm_hofflohmaerkte';

        $sql = "SELECT k.id, k.name, COUNT(s.id) AS count
                FROM {$kategorien} k
                LEFT JOIN {$stand_kategorien} sk ON sk.kategorie_id = k.id
                LEFT JOIN {$staende} s ON s.id = sk.stand_id AND s.active = %d
                LEFT JOIN {$events} h ON h.id = s.hofflohmarkt_id AND h.active = %d
                WHERE k.active = %d
                GROUP BY k.id, k.name
                ORDER BY count DESC, k.name ASC";

        return $wpdb->get_results($wpdb->prepare($sql, 1, 1, 1));
    }
}
