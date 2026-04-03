<?php

class HM_Form_Handler
{

    public function __construct()
    {
        add_shortcode('hm_registration', array($this, 'render_registration_form'));
        add_shortcode('hm_offer_space', array($this, 'render_space_offer_form'));
        add_action('init', array($this, 'handle_form_submission'));
    }

    public function render_registration_form()
    {
        global $wpdb;
        $table_kategorien = $wpdb->prefix . 'hm_kategorien';
        $categories = $wpdb->get_results("SELECT * FROM $table_kategorien WHERE active = 1 ORDER BY name ASC");

        ob_start();
        ?>
        <div class="hm-registration-form" id="hm-registration-form">
            <h3>Stand registrieren</h3>
            <form method="post" action="">
                <?php wp_nonce_field('hm_register_stand', 'hm_register_nonce'); ?>
                <input type="hidden" name="hm_form_type" value="stand_registration">
                <input type="hidden" name="hm_submit_stand" value="1">

                <div class="hm-form-row">
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_vorname">Vorname</label>
                        <input type="text" name="hm_vorname" id="hm_vorname" required>
                    </div>
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_nachname">Nachname</label>
                        <input type="text" name="hm_nachname" id="hm_nachname" required>
                    </div>
                </div>

                <div class="hm-form-group">
                    <label for="hm_email">E-Mail Adresse</label>
                    <input type="email" name="hm_email" id="hm_email" required>
                </div>

                <div class="hm-form-row">
                    <div class="hm-form-group hm-col-80">
                        <label for="hm_strasse">Straße</label>
                        <input type="text" name="hm_strasse" id="hm_strasse" required>
                    </div>
                    <div class="hm-form-group hm-col-20">
                        <label for="hm_hausnummer">Nr.</label>
                        <input type="text" name="hm_hausnummer" id="hm_hausnummer" required>
                    </div>
                </div>

                <div class="hm-form-row">
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_plz">PLZ</label>
                        <input type="text" name="hm_plz" id="hm_plz" required>
                    </div>
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_ort">Ort</label>
                        <input type="text" name="hm_ort" id="hm_ort" required>
                    </div>
                </div>

                <div class="hm-form-group"
                    style="margin-top: 30px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                    <label>
                        <input type="checkbox" name="hm_hofflohmarkt_nest" value="1">
                        Hofflohmarkt Nest (Zusammenschluss mehrerer Stände)
                    </label>
                </div>

                <div class="hm-form-group">
                    <label>Kategorien (Was verkaufst du?)</label><br>
                    <?php if ($categories): ?>
                        <div class="hm-categories-list" style="display: flex; flex-direction: column; gap: 2px;">
                            <?php foreach ($categories as $cat): ?>
                                <label style="font-weight: normal;">
                                    <input type="checkbox" name="hm_kategorien[]" value="<?php echo esc_attr($cat->id); ?>">
                                    <?php echo esc_html($cat->name); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Keine Kategorien verfügbar.</p>
                    <?php endif; ?>
                </div>

                <button type="submit" id="hm_submit_stand_btn" class="button" style="margin-top: 20px;">Stand Anmelden</button>
                <?php if (isset($_GET['hm_error']) && $_GET['hm_error'] === 'duplicate'): ?>
                    <div class="hm-error-message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                        Diese Person ist an dieser Adresse bereits angemeldet.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['hm_success'])): ?>
                    <div id="hm-success" class="hm-success-message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                        Vielen Dank! Dein Stand wurde zur Überprüfung eingereicht.
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <script>
        document.querySelector('.hm-registration-form form').addEventListener('submit', function () {
            var btn = this.querySelector('#hm_submit_stand_btn');
            btn.disabled = true;
            btn.classList.add('hm-btn-loading');
            btn.innerHTML = '<span class="hm-btn-spinner"></span>Wird gesendet\u2026';
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function render_space_offer_form()
    {
        ob_start();
        ?>
        <div class="hm-space-offer-form">
            <h3>Platz anbieten</h3>
            <p>Du hast keinen eigenen Stand, aber Platz für andere? Biete ihn hier an!</p>
            <form method="post" action="">
                <?php wp_nonce_field('hm_offer_space', 'hm_offer_space_nonce'); ?>
                <input type="hidden" name="hm_form_type" value="space_offer">

                <div class="hm-form-row">
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_vorname">Vorname</label>
                        <input type="text" name="hm_vorname" id="hm_vorname" required>
                    </div>
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_nachname">Nachname</label>
                        <input type="text" name="hm_nachname" id="hm_nachname" required>
                    </div>
                </div>

                <div class="hm-form-group">
                    <label for="hm_email">E-Mail Adresse</label>
                    <input type="email" name="hm_email" id="hm_email" required>
                </div>

                <div class="hm-form-row">
                    <div class="hm-form-group hm-col-80">
                        <label for="hm_strasse">Straße</label>
                        <input type="text" name="hm_strasse" id="hm_strasse" required>
                    </div>
                    <div class="hm-form-group hm-col-20">
                        <label for="hm_hausnummer">Hausnummer</label>
                        <input type="text" name="hm_hausnummer" id="hm_hausnummer" required>
                    </div>
                </div>

                <div class="hm-form-row">
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_plz">PLZ</label>
                        <input type="text" name="hm_plz" id="hm_plz" required>
                    </div>
                    <div class="hm-form-group hm-col-50">
                        <label for="hm_ort">Ort</label>
                        <input type="text" name="hm_ort" id="hm_ort" required>
                    </div>
                </div>

                <div class="hm-form-group" style="margin-top: 20px;">
                    <label for="hm_available_spots">Wie viele Stände haben Platz?</label>
                    <input type="number" name="hm_available_spots" id="hm_available_spots" min="1" value="1" required>
                </div>

                <div class="hm-form-group">
                    <label for="hm_space_description">Beschreibung des Platzangebots (Größe, Besonderheiten, etc.)</label>
                    <textarea name="hm_space_description" id="hm_space_description" rows="3" style="width: 100%;"></textarea>
                </div>

                <button type="submit" name="hm_submit_space_offer" class="button" style="margin-top: 20px;">Platz anbieten</button>
                <?php if (isset($_GET['hm_space_success'])): ?>
                    <div id="hm-success" class="hm-success-message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                        Vielen Dank! Dein Platzangebot wurde eingereicht.
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_form_submission()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (isset($_POST['hm_submit_stand'])) {
            $this->process_stand_registration();
        } elseif (isset($_POST['hm_submit_space_offer'])) {
            $this->process_space_offer();
        }
    }

    private function process_stand_registration()
    {
        if (!isset($_POST['hm_register_nonce']) || !wp_verify_nonce($_POST['hm_register_nonce'], 'hm_register_stand')) {
            return;
        }

        global $wpdb;
        $table_staende = $wpdb->prefix . 'hm_staende';
        $table_stand_kategorien = $wpdb->prefix . 'hm_stand_kategorien';

        $vorname = sanitize_text_field($_POST['hm_vorname']);
        $nachname = sanitize_text_field($_POST['hm_nachname']);
        $email = sanitize_email($_POST['hm_email']);
        $strasse = sanitize_text_field($_POST['hm_strasse']);
        $hausnummer = sanitize_text_field($_POST['hm_hausnummer']);
        $plz = sanitize_text_field($_POST['hm_plz']);
        $ort = sanitize_text_field($_POST['hm_ort']);
        $nest = isset($_POST['hm_hofflohmarkt_nest']) ? 1 : 0;
        $kategorien = isset($_POST['hm_kategorien']) ? $_POST['hm_kategorien'] : array();

        // Duplikat-Check: gleicher Name + gleiche Adresse
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_staende WHERE vorname = %s AND nachname = %s AND strasse = %s AND hausnummer = %s AND plz = %s AND ort = %s",
                $vorname, $nachname, $strasse, $hausnummer, $plz, $ort
            )
        );
        if ($existing > 0) {
            wp_redirect(add_query_arg('hm_error', 'duplicate') . '#hm-registration-form');
            exit;
        }

        // Geocode Address
        $coords = $this->geocode_address($strasse, $hausnummer, $plz, $ort);
        $lat = $coords ? $coords['lat'] : null;
        $lng = $coords ? $coords['lng'] : null;

        $result = $wpdb->insert(
            $table_staende,
            array(
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'strasse' => $strasse,
                'hausnummer' => $hausnummer,
                'plz' => $plz,
                'ort' => $ort,
                'hofflohmarkt_nest' => $nest,
                'active' => 0, // Default inactive
                'lat' => $lat,
                'lng' => $lng
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%f')
        );

        if ($result) {
            $stand_id = $wpdb->insert_id;

            // Save Categories
            if (!empty($kategorien)) {
                foreach ($kategorien as $cat_id) {
                    $wpdb->insert(
                        $table_stand_kategorien,
                        array('stand_id' => $stand_id, 'kategorie_id' => intval($cat_id)),
                        array('%d', '%d')
                    );
                }
            }

            // Send Registration Email
            $this->send_registration_email($email, $vorname . ' ' . $nachname);

            // Redirect to avoid resubmission
            wp_redirect(add_query_arg('hm_success', '1') . '#hm-success');
            exit;
        }
    }

    private function process_space_offer()
    {
        if (!isset($_POST['hm_offer_space_nonce']) || !wp_verify_nonce($_POST['hm_offer_space_nonce'], 'hm_offer_space')) {
            return;
        }

        global $wpdb;
        $table_space_offers = $wpdb->prefix . 'hm_space_offers';

        $vorname = sanitize_text_field($_POST['hm_vorname']);
        $nachname = sanitize_text_field($_POST['hm_nachname']);
        $email = sanitize_email($_POST['hm_email']);
        $strasse = sanitize_text_field($_POST['hm_strasse']);
        $hausnummer = sanitize_text_field($_POST['hm_hausnummer']);
        $plz = sanitize_text_field($_POST['hm_plz']);
        $ort = sanitize_text_field($_POST['hm_ort']);
        $available_spots = intval($_POST['hm_available_spots']);
        $space_description = sanitize_textarea_field($_POST['hm_space_description']);

        // Geocode Address
        $coords = $this->geocode_address($strasse, $hausnummer, $plz, $ort);
        $lat = $coords ? $coords['lat'] : null;
        $lng = $coords ? $coords['lng'] : null;

        $result = $wpdb->insert(
            $table_space_offers,
            array(
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'strasse' => $strasse,
                'hausnummer' => $hausnummer,
                'plz' => $plz,
                'ort' => $ort,
                'available_spots' => $available_spots,
                'space_description' => $space_description,
                'active' => 0, // Default inactive
                'lat' => $lat,
                'lng' => $lng
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%f', '%f')
        );

        if ($result) {
            // Send Registration Email (Maybe a slightly different one?)
            $this->send_registration_email($email, $vorname . ' ' . $nachname);

            // Redirect to avoid resubmission
            wp_redirect(add_query_arg('hm_space_success', '1') . '#hm-success');
            exit;
        }
    }

    private function send_registration_email($to, $name)
    {
        $subject = 'Deine Anmeldung zum Hofflohmarkt';
        $message = "Hallo $name,\n\n";
        $message .= "Vielen Dank für deine Anmeldung zum Hofflohmarkt.\n";
        $message .= "Deine Daten wurden gespeichert und werden nun von uns geprüft.\n";
        $message .= "Du erhältst eine weitere E-Mail, sobald dein Eintrag freigeschaltet wurde.\n\n";
        $message .= "Viele Grüße,\n";
        $message .= "Dein Hofflohmarkt-Team";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail($to, $subject, $message, $headers);
    }

    public static function geocode_address($street, $number, $zip, $city)
    {
        $address = implode(', ', array_filter([$street . ' ' . $number, $zip . ' ' . $city]));

        if (empty($address)) {
            return false;
        }

        // Cache key generation
        $cache_key = 'hm_geo_' . md5($address);
        $cached = get_transient($cache_key);
        if ($cached) {
            return $cached;
        }

        $url = "https://nominatim.openstreetmap.org/search";
        $params = [
            'format' => 'jsonv2',
            'addressdetails' => '0',
            'limit' => '1',
            'q' => $address,
            'countrycodes' => 'de'
        ];

        $url = add_query_arg($params, $url);
        $args = [
            'headers' => [
                'User-Agent' => 'HofflohmarktManager/' . (defined('HM_VERSION') ? HM_VERSION : '1.0') . ' (' . home_url() . ')',
                'Accept' => 'application/json',
            ],
            'timeout' => 10,
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
            return false;
        }

        $result = [
            'lat' => (float) $data[0]['lat'],
            'lng' => (float) $data[0]['lon']
        ];

        set_transient($cache_key, $result, HOUR_IN_SECONDS * 12);

        return $result;
    }
}
