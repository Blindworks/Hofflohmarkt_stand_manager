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
            'label' => 'Angemeldete Stände',
        ), $atts, 'hm_stand_counter');

        wp_enqueue_style('hm-style-css', HM_PLUGIN_URL . 'assets/css/hm-style.css', array(), time());

        $count = $this->get_count();

        return sprintf(
            '<div class="hm-counter-wrapper"><div class="hm-counter"><div class="hm-counter-number">%d</div><div class="hm-counter-label">%s</div></div></div>',
            $count,
            esc_html($atts['label'])
        );
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
}
