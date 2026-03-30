<?php

class HM_Bewerbungen
{
    public function __construct()
    {
        // AJAX handler for logged-in and non-logged-in users
        add_action('wp_ajax_hm_submit_bewerbung', array($this, 'submit_bewerbung'));
        add_action('wp_ajax_nopriv_hm_submit_bewerbung', array($this, 'submit_bewerbung'));

        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_bewerbung_assets'));

        // Handler for accept/reject actions
        add_action('init', array($this, 'handle_bewerbung_action'));
    }

    public function enqueue_bewerbung_assets()
    {
        wp_enqueue_script('hm-bewerbung-js', HM_PLUGIN_URL . 'assets/js/hm-bewerbung.js', array('jquery'), time(), true);

        wp_localize_script('hm-bewerbung-js', 'hmBewerbungData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hm_bewerbung_nonce')
        ));

        // Enqueue styles for modal
        wp_enqueue_style('hm-bewerbung-css', HM_PLUGIN_URL . 'assets/css/hm-bewerbung.css', array(), time());
    }

    public function submit_bewerbung()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hm_bewerbung_nonce')) {
            wp_send_json_error(array('message' => 'Sicherheitsüberprüfung fehlgeschlagen.'));
            return;
        }

        // Validate and sanitize input
        $stand_id = isset($_POST['stand_id']) ? intval($_POST['stand_id']) : 0;
        $stand_type = isset($_POST['stand_type']) ? sanitize_text_field($_POST['stand_type']) : 'stand';
        $vorname = isset($_POST['vorname']) ? sanitize_text_field($_POST['vorname']) : '';
        $nachname = isset($_POST['nachname']) ? sanitize_text_field($_POST['nachname']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $telefon = isset($_POST['telefon']) ? sanitize_text_field($_POST['telefon']) : '';
        $nachricht = isset($_POST['nachricht']) ? sanitize_textarea_field($_POST['nachricht']) : '';

        // Validation
        if (empty($stand_id) || empty($vorname) || empty($nachname) || empty($email)) {
            wp_send_json_error(array('message' => 'Bitte füllen Sie alle Pflichtfelder aus.'));
            return;
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'));
            return;
        }

        // Generate unique action token
        $action_token = bin2hex(random_bytes(32));

        // Insert into database
        global $wpdb;
        $table_bewerbungen = $wpdb->prefix . 'hm_bewerbungen';

        $result = $wpdb->insert(
            $table_bewerbungen,
            array(
                'stand_id' => $stand_id,
                'stand_type' => $stand_type,
                'vorname' => $vorname,
                'nachname' => $nachname,
                'email' => $email,
                'telefon' => $telefon,
                'nachricht' => $nachricht,
                'status' => 'pending',
                'action_token' => $action_token,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Fehler beim Speichern der Bewerbung.'));
            return;
        }

        $bewerbung_id = $wpdb->insert_id;

        // Send confirmation email to applicant
        $this->send_confirmation_email($email, $vorname, $nachname);

        // Send notification email to stand owner
        $this->send_owner_notification($stand_id, $stand_type, $bewerbung_id, $action_token, $vorname, $nachname, $email, $telefon, $nachricht);

        wp_send_json_success(array('message' => 'Ihre Bewerbung wurde erfolgreich eingereicht!'));
    }

    private function send_confirmation_email($to, $vorname, $nachname)
    {
        $subject = 'Bewerbung eingereicht - Hofflohmarkt';
        $message = "Hallo {$vorname} {$nachname},\n\n";
        $message .= "Ihre Bewerbung für einen Standplatz wurde erfolgreich eingereicht.\n\n";
        $message .= "Wir werden uns in Kürze bei Ihnen melden.\n\n";
        $message .= "Mit freundlichen Grüßen\n";
        $message .= "Ihr Hofflohmarkt-Team";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);
    }

    private function send_owner_notification($stand_id, $stand_type, $bewerbung_id, $action_token, $bewerber_vorname, $bewerber_nachname, $bewerber_email, $bewerber_telefon, $bewerber_nachricht)
    {
        global $wpdb;

        // Debug logging
        error_log("HM_Bewerbungen: Sending owner notification for stand_id=$stand_id, stand_type=$stand_type");

        // Get stand owner information
        if ($stand_type === 'space_offer' || $stand_type === 'space') {
            $table = $wpdb->prefix . 'hm_space_offers';
        } else {
            $table = $wpdb->prefix . 'hm_staende';
        }

        $stand = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $stand_id
        ));

        if (!$stand) {
            error_log("HM_Bewerbungen: Stand not found in table $table with id $stand_id");
            return;
        }

        $owner_email = $stand->email;
        $owner_name = $stand->vorname . ' ' . $stand->nachname;

        error_log("HM_Bewerbungen: Found owner $owner_name ($owner_email)");

        // Create accept and reject URLs
        $accept_url = add_query_arg(array(
            'hm_action' => 'accept',
            'token' => $action_token,
            'bid' => $bewerbung_id
        ), home_url());

        $reject_url = add_query_arg(array(
            'hm_action' => 'reject',
            'token' => $action_token,
            'bid' => $bewerbung_id
        ), home_url());

        // Prepare email
        $subject = 'Neue Bewerbung für Ihren Standplatz - Hofflohmarkt';

        $message = "Hallo {$owner_name},\n\n";
        $message .= "Sie haben eine neue Bewerbung für Ihren Standplatz erhalten!\n\n";
        $message .= "--- Bewerberdaten ---\n";
        $message .= "Name: {$bewerber_vorname} {$bewerber_nachname}\n";
        $message .= "E-Mail: {$bewerber_email}\n";

        if (!empty($bewerber_telefon)) {
            $message .= "Telefon: {$bewerber_telefon}\n";
        }

        if (!empty($bewerber_nachricht)) {
            $message .= "\nNachricht:\n{$bewerber_nachricht}\n";
        }

        $message .= "\n--- Ihre Entscheidung ---\n\n";
        $message .= "Bitte klicken Sie auf einen der folgenden Links, um die Bewerbung anzunehmen oder abzulehnen:\n\n";
        $message .= "✓ BEWERBUNG ANNEHMEN:\n{$accept_url}\n\n";
        $message .= "✗ BEWERBUNG ABLEHNEN:\n{$reject_url}\n\n";
        $message .= "Wenn Sie die Bewerbung annehmen, können Sie direkt mit dem Bewerber in Kontakt treten.\n\n";
        $message .= "Mit freundlichen Grüßen\n";
        $message .= "Ihr Hofflohmarkt-Team";

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $mail_result = wp_mail($owner_email, $subject, $message, $headers);

        if (!$mail_result) {
            error_log("HM_Bewerbungen: wp_mail failed to send email to $owner_email");
        } else {
            error_log("HM_Bewerbungen: Email sent successfully to $owner_email");
        }
    }

    public function handle_bewerbung_action()
    {
        // Check if this is an action request
        if (!isset($_GET['hm_action']) || !isset($_GET['token']) || !isset($_GET['bid'])) {
            return;
        }

        $action = sanitize_text_field($_GET['hm_action']);
        $token = sanitize_text_field($_GET['token']);
        $bewerbung_id = intval($_GET['bid']);

        // Validate action
        if (!in_array($action, array('accept', 'reject'))) {
            wp_die('Ungültige Aktion.');
        }

        global $wpdb;
        $table_bewerbungen = $wpdb->prefix . 'hm_bewerbungen';

        // Get bewerbung by ID first to check if it exists
        $bewerbung = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_bewerbungen WHERE id = %d",
            $bewerbung_id
        ));

        if (!$bewerbung) {
            wp_die('Bewerbung nicht gefunden.');
        }

        // Check if already processed
        if ($bewerbung->status !== 'pending') {
            wp_die('Diese Bewerbung wurde bereits bearbeitet.');
        }

        // Check token
        if ($bewerbung->action_token !== $token) {
            wp_die('Ungültiger Link.');
        }

        // Update status
        $new_status = ($action === 'accept') ? 'accepted' : 'rejected';

        $updated = $wpdb->update(
            $table_bewerbungen,
            array(
                'status' => $new_status,
                'action_token' => null // Invalidate token after use
            ),
            array('id' => $bewerbung_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            wp_die('Fehler beim Aktualisieren der Bewerbung.');
        }

        // Decrement available_spots for accepted space offer bewerbungen
        if ($new_status === 'accepted' && ($bewerbung->stand_type === 'space' || $bewerbung->stand_type === 'space_offer')) {
            $table_space_offers = $wpdb->prefix . 'hm_space_offers';
            $space_offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_space_offers WHERE id = %d", $bewerbung->stand_id));
            if ($space_offer && $space_offer->available_spots > 0) {
                $wpdb->update(
                    $table_space_offers,
                    array('available_spots' => $space_offer->available_spots - 1),
                    array('id' => $space_offer->id),
                    array('%d'),
                    array('%d')
                );
            }
        }

        // Send notification to applicant
        $this->send_decision_notification($bewerbung, $new_status);

        // Show success message
        $message = ($action === 'accept')
            ? 'Die Bewerbung wurde erfolgreich angenommen. Der Bewerber wurde per E-Mail benachrichtigt.'
            : 'Die Bewerbung wurde abgelehnt. Der Bewerber wurde per E-Mail benachrichtigt.';

        wp_die($message, 'Bewerbung bearbeitet', array('response' => 200));
    }

    private function send_decision_notification($bewerbung, $status)
    {
        global $wpdb;

        // Get stand owner information
        if ($bewerbung->stand_type === 'space_offer' || $bewerbung->stand_type === 'space') {
            $table = $wpdb->prefix . 'hm_space_offers';
        } else {
            $table = $wpdb->prefix . 'hm_staende';
        }

        $stand = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $bewerbung->stand_id
        ));

        if (!$stand) {
            return;
        }

        $owner_name = $stand->vorname . ' ' . $stand->nachname;
        $owner_email = $stand->email;
        $owner_telefon = isset($stand->telefon) ? $stand->telefon : '';

        $bewerber_email = $bewerbung->email;
        $bewerber_name = $bewerbung->vorname . ' ' . $bewerbung->nachname;

        if ($status === 'accepted') {
            $subject = 'Ihre Bewerbung wurde angenommen - Hofflohmarkt';
            $message = "Hallo {$bewerber_name},\n\n";
            $message .= "Gute Nachrichten! Ihre Bewerbung für einen Standplatz wurde angenommen.\n\n";
            $message .= "--- Kontaktdaten des Stand-Betreibers ---\n";
            $message .= "Name: {$owner_name}\n";
            $message .= "E-Mail: {$owner_email}\n";

            if (!empty($owner_telefon)) {
                $message .= "Telefon: {$owner_telefon}\n";
            }

            $message .= "\nBitte treten Sie direkt mit dem Stand-Betreiber in Kontakt, um die Details zu besprechen.\n\n";
            $message .= "Mit freundlichen Grüßen\n";
            $message .= "Ihr Hofflohmarkt-Team";
        } else {
            $subject = 'Ihre Bewerbung - Hofflohmarkt';
            $message = "Hallo {$bewerber_name},\n\n";
            $message .= "Vielen Dank für Ihre Bewerbung für einen Standplatz.\n\n";
            $message .= "Leider können wir Ihnen dieses Mal keinen Platz anbieten.\n\n";
            $message .= "Wir wünschen Ihnen viel Erfolg bei der weiteren Suche.\n\n";
            $message .= "Mit freundlichen Grüßen\n";
            $message .= "Ihr Hofflohmarkt-Team";
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        wp_mail($bewerber_email, $subject, $message, $headers);
    }

    public static function get_bewerbungen_for_stand($stand_id, $stand_type = 'stand')
    {
        global $wpdb;
        $table_bewerbungen = $wpdb->prefix . 'hm_bewerbungen';

        // Normalize stand_type
        if ($stand_type === 'space_offer') {
            $stand_type = 'space'; // Or handle both if needed, but DB likely has 'space' or 'space_offer' depending on what was saved
        }

        // Since we save whatever the frontend sends, we might have 'space' or 'space_offer' in the DB.
        // Let's check for both if it's a space offer
        if ($stand_type === 'space_offer' || $stand_type === 'space') {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_bewerbungen 
                 WHERE stand_id = %d AND (stand_type = 'space' OR stand_type = 'space_offer')
                 ORDER BY created_at DESC",
                $stand_id
            ));
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_bewerbungen 
             WHERE stand_id = %d AND stand_type = %s 
             ORDER BY created_at DESC",
            $stand_id,
            $stand_type
        ));
    }

    public static function get_accepted_count($stand_id, $stand_type = 'stand')
    {
        global $wpdb;
        $table_bewerbungen = $wpdb->prefix . 'hm_bewerbungen';

        if ($stand_type === 'space_offer' || $stand_type === 'space') {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_bewerbungen
                 WHERE stand_id = %d AND (stand_type = 'space' OR stand_type = 'space_offer') AND status = 'accepted'",
                $stand_id
            ));
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_bewerbungen
             WHERE stand_id = %d AND stand_type = %s AND status = 'accepted'",
            $stand_id,
            $stand_type
        ));
    }

    public static function get_bewerbung_count($stand_id, $stand_type = 'stand')
    {
        global $wpdb;
        $table_bewerbungen = $wpdb->prefix . 'hm_bewerbungen';

        if ($stand_type === 'space_offer' || $stand_type === 'space') {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_bewerbungen 
                 WHERE stand_id = %d AND (stand_type = 'space' OR stand_type = 'space_offer')",
                $stand_id
            ));
        }

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_bewerbungen 
             WHERE stand_id = %d AND stand_type = %s",
            $stand_id,
            $stand_type
        ));
    }
}
