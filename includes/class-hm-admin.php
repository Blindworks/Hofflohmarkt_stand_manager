<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class HM_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'hm_dashboard') === false) {
            return;
        }

        // Enqueue Leaflet CSS and JS
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Hofflohmarkt Manager',
            'Hofflohmarkt',
            'manage_options',
            'hm_dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-store',
            20
        );

        add_submenu_page(
            'hm_dashboard',
            'Alle Stände',
            'Alle Stände',
            'manage_options',
            'hm_dashboard',
            array($this, 'render_dashboard_page')
        );

        add_submenu_page(
            'hm_dashboard',
            'Platzangebote',
            'Platzangebote',
            'manage_options',
            'hm_space_offers',
            array($this, 'render_space_offers_page')
        );

        add_submenu_page(
            'hm_dashboard',
            'Hofflohmärkte',
            'Hofflohmärkte',
            'manage_options',
            'hm_hofflohmaerkte',
            array($this, 'render_hofflohmaerkte_page')
        );

        add_submenu_page(
            'hm_dashboard',
            'Kategorien',
            'Kategorien',
            'manage_options',
            'hm_kategorien',
            array($this, 'render_kategorien_page')
        );
    }

    public function render_dashboard_page() {
        global $wpdb;
        $table_staende = $wpdb->prefix . 'hm_staende';
        $table_hofflohmaerkte = $wpdb->prefix . 'hm_hofflohmaerkte';

        // Handle Actions
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($action == 'edit' && $id > 0) {
            $this->render_stand_edit_page($id);
        } elseif ($action == 'delete' && $id > 0 && check_admin_referer('hm_delete_stand_' . $id)) {
            $wpdb->delete($table_staende, array('id' => $id));
            echo '<div class="updated"><p>Stand gelöscht.</p></div>';
            $this->render_stand_list_page();
        } elseif ($action == 'geocode' && $id > 0 && check_admin_referer('hm_geocode_stand_' . $id)) {
            $stand = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_staende WHERE id = %d", $id));
            if ($stand) {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-hm-form-handler.php';
                $coords = HM_Form_Handler::geocode_address($stand->strasse, $stand->hausnummer, $stand->plz, $stand->ort);
                
                if ($coords) {
                    $wpdb->update(
                        $table_staende,
                        array('lat' => $coords['lat'], 'lng' => $coords['lng']),
                        array('id' => $id),
                        array('%f', '%f'),
                        array('%d')
                    );
                    echo '<div class="updated"><p>Geocoding erfolgreich! Koordinaten aktualisiert.</p></div>';
                } else {
                    // Reset coordinates on failure to indicate error
                    $wpdb->update(
                        $table_staende,
                        array('lat' => null, 'lng' => null),
                        array('id' => $id),
                        array('%f', '%f'),
                        array('%d')
                    );
                    echo '<div class="error"><p>Geocoding fehlgeschlagen. Adresse konnte nicht gefunden werden.</p></div>';
                }
            }
            $this->render_stand_list_page();
        } else {
            $this->render_stand_list_page();
        }
    }

    private function render_stand_list_page() {
        global $wpdb;
        $table_staende = $wpdb->prefix . 'hm_staende';
        $table_hofflohmaerkte = $wpdb->prefix . 'hm_hofflohmaerkte';

        $items = $wpdb->get_results("
            SELECT s.*, h.name as hofflohmarkt_name 
            FROM $table_staende s 
            LEFT JOIN $table_hofflohmaerkte h ON s.hofflohmarkt_id = h.id 
            ORDER BY s.created_at DESC
        ");

        ?>
        <div class="wrap">
            <h1>Alle Stände</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Titel (Name)</th>
                        <th>Adresse</th>
                        <th>Hofflohmarkt</th>
                        <th>Geocoding</th>
                        <th>Nest</th>

                        <th>Aktiv</th>
                        <th>Erstellt am</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($item->vorname . ' ' . $item->nachname); ?></strong><br>
                                    <small><?php echo esc_html($item->email); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($item->strasse . ' ' . $item->hausnummer); ?><br>
                                    <?php echo esc_html($item->plz . ' ' . $item->ort); ?>
                                </td>
                                <td><?php echo $item->hofflohmarkt_name ? esc_html($item->hofflohmarkt_name) : '<span style="color:red;">Nicht zugewiesen</span>'; ?></td>
                                <td>
                                    <?php if ($item->lat && $item->lng): ?>
                                        <span style="color: green;" title="<?php echo esc_attr($item->lat . ', ' . $item->lng); ?>">
                                            <span class="dashicons dashicons-location"></span> OK
                                        </span>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">
                                            <span class="dashicons dashicons-warning"></span> Fehler
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item->hofflohmarkt_nest): ?>
                                        <span class="dashicons dashicons-yes" style="color: green;"></span>
                                    <?php endif; ?>
                                </td>

                                </td>
                                <td>
                                    <?php if ($item->active): ?>
                                        <span style="color: green; font-weight: bold;">Aktiv</span>
                                    <?php else: ?>
                                        <span style="color: orange;">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item->created_at); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=hm_dashboard&action=edit&id=' . $item->id); ?>" class="button button-small">Bearbeiten</a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hm_dashboard&action=geocode&id=' . $item->id), 'hm_geocode_stand_' . $item->id); ?>" class="button button-small" title="Geocoding erneut ausführen"><span class="dashicons dashicons-location" style="margin-top: 3px;"></span></a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hm_dashboard&action=delete&id=' . $item->id), 'hm_delete_stand_' . $item->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Wirklich löschen?');">Löschen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Keine Stände gefunden.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_stand_edit_page($id) {
        global $wpdb;
        $table_staende = $wpdb->prefix . 'hm_staende';
        $table_hofflohmaerkte = $wpdb->prefix . 'hm_hofflohmaerkte';
        $table_kategorien = $wpdb->prefix . 'hm_kategorien';
        $table_stand_kategorien = $wpdb->prefix . 'hm_stand_kategorien';

        // Handle Save
        if (isset($_POST['hm_save_stand']) && check_admin_referer('hm_edit_stand_' . $id)) {
            $active = isset($_POST['hm_active']) ? 1 : 0;
            $hofflohmarkt_nest = isset($_POST['hm_hofflohmarkt_nest']) ? 1 : 0;
            $hofflohmarkt_id = intval($_POST['hm_hofflohmarkt_id']);
            
            $vorname = sanitize_text_field($_POST['hm_vorname']);
            $nachname = sanitize_text_field($_POST['hm_nachname']);
            $email = sanitize_email($_POST['hm_email']);
            $strasse = sanitize_text_field($_POST['hm_strasse']);
            $hausnummer = sanitize_text_field($_POST['hm_hausnummer']);
            $plz = sanitize_text_field($_POST['hm_plz']);
            $ort = sanitize_text_field($_POST['hm_ort']);
            $lat = sanitize_text_field($_POST['hm_lat']);
            $lat = sanitize_text_field($_POST['hm_lat']);
            $lng = sanitize_text_field($_POST['hm_lng']);
            


            // Fetch current stand data to check status change
            $current_stand = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_staende WHERE id = %d", $id));
            $was_active = $current_stand ? $current_stand->active : 0;

            $wpdb->update(
                $table_staende,
                array(
                    'active' => $active, 
                    'hofflohmarkt_nest' => $hofflohmarkt_nest,
                    'hofflohmarkt_id' => $hofflohmarkt_id,
                    'vorname' => $vorname,
                    'nachname' => $nachname,
                    'email' => $email,
                    'strasse' => $strasse,
                    'hausnummer' => $hausnummer,
                    'plz' => $plz,
                    'ort' => $ort,
                    'ort' => $ort,
                    'lat' => $lat,
                    'lng' => $lng,

                ),
                array('id' => $id),
                array('%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f'),
                array('%d')
            );

            // Send Activation Email if status changed from inactive to active
            if (!$was_active && $active) {
                $this->send_activation_email($email, $vorname . ' ' . $nachname);
            }

            // Update Categories
            $wpdb->delete($table_stand_kategorien, array('stand_id' => $id));
            if (isset($_POST['hm_kategorien']) && is_array($_POST['hm_kategorien'])) {
                foreach ($_POST['hm_kategorien'] as $cat_id) {
                    $wpdb->insert(
                        $table_stand_kategorien,
                        array('stand_id' => $id, 'kategorie_id' => intval($cat_id)),
                        array('%d', '%d')
                    );
                }
            }

            echo '<div class="updated"><p>Stand aktualisiert.</p></div>';
        }

        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_staende WHERE id = %d", $id));
        $hofflohmaerkte = $wpdb->get_results("SELECT * FROM $table_hofflohmaerkte ORDER BY date DESC");
        
        // Get Categories
        $stand_cats = $wpdb->get_col($wpdb->prepare("SELECT kategorie_id FROM $table_stand_kategorien WHERE stand_id = %d", $id));
        $all_cats = $wpdb->get_results("SELECT * FROM $table_kategorien");
        $cat_names = array();
        foreach($all_cats as $cat) {
            if(in_array($cat->id, $stand_cats)) {
                $cat_names[] = $cat->name;
            }
        }

        if (!$item) {
            echo '<div class="error"><p>Stand nicht gefunden.</p></div>';
            return;
        }
    ?>
        <div class="wrap">
            <h1>Stand bearbeiten: <?php echo esc_html($item->vorname . ' ' . $item->nachname); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=hm_dashboard'); ?>" class="button">Zurück zur Übersicht</a>
            
            <form method="post" action="">
                <?php wp_nonce_field('hm_edit_stand_' . $id); ?>
                
                <div style="display: flex; gap: 20px; margin-top: 20px;">
                    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                        <h3>Verwaltung</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="hm_active">Status</label></th>
                                <td>
                                    <label>
                                        <input name="hm_active" type="checkbox" id="hm_active" value="1" <?php checked($item->active, 1); ?>>
                                        Aktiv (Auf Karte anzeigen)
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hm_hofflohmarkt_nest">Hofflohmarkt Nest</label></th>
                                <td>
                                    <label>
                                        <input name="hm_hofflohmarkt_nest" type="checkbox" id="hm_hofflohmarkt_nest" value="1" <?php checked($item->hofflohmarkt_nest, 1); ?>>
                                        Ist ein Nest (Platz für weitere Stände)
                                    </label>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><label for="hm_hofflohmarkt_id">Hofflohmarkt zuweisen</label></th>
                                <td>
                                    <select name="hm_hofflohmarkt_id" id="hm_hofflohmarkt_id">
                                        <option value="0">-- Keiner --</option>
                                        <?php foreach ($hofflohmaerkte as $hm): ?>
                                            <option value="<?php echo $hm->id; ?>" <?php selected($item->hofflohmarkt_id, $hm->id); ?>>
                                                <?php echo esc_html($hm->name . ' (' . $hm->date . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button('Änderungen speichern', 'primary', 'hm_save_stand'); ?>
                    </div>

                    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                        <h3>Stand Daten bearbeiten</h3>
                        <table class="form-table">
                            <tr>
                                <th><label for="hm_vorname">Vorname</label></th>
                                <td><input type="text" name="hm_vorname" id="hm_vorname" value="<?php echo esc_attr($item->vorname); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_nachname">Nachname</label></th>
                                <td><input type="text" name="hm_nachname" id="hm_nachname" value="<?php echo esc_attr($item->nachname); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_email">E-Mail</label></th>
                                <td><input type="email" name="hm_email" id="hm_email" value="<?php echo esc_attr($item->email); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_strasse">Straße</label></th>
                                <td><input type="text" name="hm_strasse" id="hm_strasse" value="<?php echo esc_attr($item->strasse); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_hausnummer">Hausnummer</label></th>
                                <td><input type="text" name="hm_hausnummer" id="hm_hausnummer" value="<?php echo esc_attr($item->hausnummer); ?>" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_plz">PLZ</label></th>
                                <td><input type="text" name="hm_plz" id="hm_plz" value="<?php echo esc_attr($item->plz); ?>" class="small-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_ort">Ort</label></th>
                                <td><input type="text" name="hm_ort" id="hm_ort" value="<?php echo esc_attr($item->ort); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label>Kategorien</label></th>
                                <td>
                                    <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                        <?php foreach ($all_cats as $cat): ?>
                                            <label style="display: block; margin-bottom: 5px;">
                                                <input type="checkbox" name="hm_kategorien[]" value="<?php echo esc_attr($cat->id); ?>" <?php checked(in_array($cat->id, $stand_cats)); ?>>
                                                <?php echo esc_html($cat->name); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="hm_lat">Lat</label></th>
                                <td><input type="text" name="hm_lat" id="hm_lat" value="<?php echo esc_attr($item->lat); ?>" class="regular-text"></td>
                            </tr>
                            <tr>
                                <th><label for="hm_lng">Lng</label></th>
                                <td><input type="text" name="hm_lng" id="hm_lng" value="<?php echo esc_attr($item->lng); ?>" class="regular-text"></td>
                            </tr>
                        </table>

                        <?php if ($item->lat && $item->lng): ?>
                            <div id="hm-admin-map" style="height: 300px; width: 100%; margin-top: 20px; border: 1px solid #ccc;"></div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    var map = L.map('hm-admin-map').setView([<?php echo $item->lat; ?>, <?php echo $item->lng; ?>], 16);
                                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    }).addTo(map);
                                    L.marker([<?php echo $item->lat; ?>, <?php echo $item->lng; ?>]).addTo(map)
                                        .bindPopup('<?php echo esc_js($item->strasse . ' ' . $item->hausnummer); ?>')
                                        .openPopup();
                                });
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    public function render_space_offers_page() {
        global $wpdb;
        $table_space_offers = $wpdb->prefix . 'hm_space_offers';

        // Handle Actions
        $action = isset($_GET['action']) ? $_GET['action'] : 'list';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Show detail view with applications
        if ($action == 'view' && $id > 0) {
            $this->render_space_offer_detail($id);
            return;
        }

        if ($action == 'delete' && $id > 0 && check_admin_referer('hm_delete_space_offer_' . $id)) {
            $wpdb->delete($table_space_offers, array('id' => $id));
            echo '<div class="updated"><p>Platzangebot gelöscht.</p></div>';
        } elseif ($action == 'toggle_active' && $id > 0 && check_admin_referer('hm_toggle_active_space_' . $id)) {
            $current_status = $wpdb->get_var($wpdb->prepare("SELECT active FROM $table_space_offers WHERE id = %d", $id));
            $new_status = $current_status ? 0 : 1;
            $wpdb->update($table_space_offers, array('active' => $new_status), array('id' => $id));
            
            // Send email if activated
            if ($new_status) {
                $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_space_offers WHERE id = %d", $id));
                $this->send_activation_email($offer->email, $offer->vorname . ' ' . $offer->nachname);
            }
            
            echo '<div class="updated"><p>Status aktualisiert.</p></div>';
        }

        $items = $wpdb->get_results("SELECT * FROM $table_space_offers ORDER BY created_at DESC");

        ?>
        <div class="wrap">
            <h1>Platzangebote</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Adresse</th>
                        <th>Plätze</th>
                        <th>Beschreibung</th>
                        <th>Bewerbungen</th>
                        <th>Status</th>
                        <th>Erstellt am</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items): ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($item->vorname . ' ' . $item->nachname); ?></strong><br>
                                    <small><?php echo esc_html($item->email); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($item->strasse . ' ' . $item->hausnummer); ?><br>
                                    <?php echo esc_html($item->plz . ' ' . $item->ort); ?>
                                </td>
                                <td><?php echo esc_html($item->available_spots); ?></td>
                                <td><?php echo esc_html($item->space_description); ?></td>
                                <td>
                                    <?php
                                    $bewerbung_count = HM_Bewerbungen::get_bewerbung_count($item->id, 'space');
                                    echo '<strong>' . $bewerbung_count . '</strong>';
                                    ?>
                                </td>
                                <td>
                                    <?php if ($item->active): ?>
                                        <span style="color: green; font-weight: bold;">Aktiv</span>
                                    <?php else: ?>
                                        <span style="color: orange;">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($item->created_at); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=hm_space_offers&action=view&id=' . $item->id); ?>" class="button button-small button-primary">Details</a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hm_space_offers&action=toggle_active&id=' . $item->id), 'hm_toggle_active_space_' . $item->id); ?>" class="button button-small">
                                        <?php echo $item->active ? 'Deaktivieren' : 'Aktivieren'; ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hm_space_offers&action=delete&id=' . $item->id), 'hm_delete_space_offer_' . $item->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Wirklich löschen?');">Löschen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9">Keine Platzangebote gefunden.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_hofflohmaerkte_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hm_hofflohmaerkte';

        // Handle Delete Action
        if (isset($_GET['action']) && $_GET['action'] == 'delete_hofflohmarkt' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if (check_admin_referer('hm_delete_hofflohmarkt_' . $id)) {
                $wpdb->delete($table_name, array('id' => $id));
                echo '<div class="updated"><p>Hofflohmarkt gelöscht.</p></div>';
            }
        }

        // Handle Form Submission (Create or Update)
        if (isset($_POST['hm_submit_hofflohmarkt']) && check_admin_referer('hm_save_hofflohmarkt')) {
            $name = sanitize_text_field($_POST['hm_name']);
            $date = sanitize_text_field($_POST['hm_date']);
            $active = isset($_POST['hm_active']) ? 1 : 0;
            $id = isset($_POST['hm_hofflohmarkt_id']) ? intval($_POST['hm_hofflohmarkt_id']) : 0;

            if (!empty($name)) {
                if ($id > 0) {
                    // Update
                    $wpdb->update(
                        $table_name,
                        array('name' => $name, 'date' => $date, 'active' => $active),
                        array('id' => $id),
                        array('%s', '%s', '%d'),
                        array('%d')
                    );
                    echo '<div class="updated"><p>Hofflohmarkt aktualisiert.</p></div>';
                } else {
                    // Insert
                    $wpdb->insert(
                        $table_name,
                        array('name' => $name, 'date' => $date, 'active' => $active),
                        array('%s', '%s', '%d')
                    );
                    echo '<div class="updated"><p>Hofflohmarkt gespeichert.</p></div>';
                }
            }
        }

        // Prepare Data for Edit Mode
        $edit_mode = false;
        $current_item = null;
        if (isset($_GET['action']) && $_GET['action'] == 'edit_hofflohmarkt' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $current_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
            if ($current_item) {
                $edit_mode = true;
            }
        }

        // Default values
        $name_val = $edit_mode ? $current_item->name : '';
        $date_val = $edit_mode ? $current_item->date : '';
        $active_val = $edit_mode ? $current_item->active : 1;
        $id_val = $edit_mode ? $current_item->id : 0;

        // Fetch Items
        $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY date DESC");

        ?>
        <div class="wrap">
            <h1>Hofflohmärkte verwalten</h1>
            
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <h2><?php echo $edit_mode ? 'Hofflohmarkt bearbeiten' : 'Neuen Hofflohmarkt anlegen'; ?></h2>
                    <form method="post" action="<?php echo remove_query_arg(array('action', 'id')); ?>">
                        <?php wp_nonce_field('hm_save_hofflohmarkt'); ?>
                        <input type="hidden" name="hm_hofflohmarkt_id" value="<?php echo esc_attr($id_val); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="hm_name">Name</label></th>
                                <td><input name="hm_name" type="text" id="hm_name" class="regular-text" value="<?php echo esc_attr($name_val); ?>" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hm_date">Datum</label></th>
                                <td><input name="hm_date" type="date" id="hm_date" class="regular-text" value="<?php echo esc_attr($date_val); ?>"></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hm_active">Aktiv</label></th>
                                <td><input name="hm_active" type="checkbox" id="hm_active" value="1" <?php checked($active_val, 1); ?>></td>
                            </tr>
                        </table>
                        <?php submit_button($edit_mode ? 'Aktualisieren' : 'Speichern', 'primary', 'hm_submit_hofflohmarkt'); ?>
                        <?php if ($edit_mode): ?>
                            <a href="<?php echo admin_url('admin.php?page=hm_hofflohmaerkte'); ?>" class="button">Abbrechen</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="flex: 2;">
                    <h2>Vorhandene Hofflohmärkte</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Datum</th>
                                <th>Aktiv</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo esc_html($item->id); ?></td>
                                        <td><?php echo esc_html($item->name); ?></td>
                                        <td><?php echo esc_html($item->date); ?></td>
                                        <td><?php echo $item->active ? 'Ja' : 'Nein'; ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=hm_hofflohmaerkte&action=edit_hofflohmarkt&id=' . $item->id); ?>" class="button button-small">Bearbeiten</a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hm_hofflohmaerkte&action=delete_hofflohmarkt&id=' . $item->id), 'hm_delete_hofflohmarkt_' . $item->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Wirklich löschen?');">Löschen</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">Keine Hofflohmärkte gefunden.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_kategorien_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hm_kategorien';

        // Handle Delete Action
        if (isset($_GET['action']) && $_GET['action'] == 'delete_kategorie' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if (check_admin_referer('hm_delete_kategorie_' . $id)) {
                $wpdb->delete($table_name, array('id' => $id));
                echo '<div class="updated"><p>Kategorie gelöscht.</p></div>';
            }
        }

        // Handle Form Submission (Create or Update)
        if (isset($_POST['hm_submit_kategorie']) && check_admin_referer('hm_save_kategorie')) {
            $name = sanitize_text_field($_POST['hm_name']);
            $active = isset($_POST['hm_active']) ? 1 : 0;
            $id = isset($_POST['hm_kategorie_id']) ? intval($_POST['hm_kategorie_id']) : 0;

            if (!empty($name)) {
                if ($id > 0) {
                    // Update
                    $wpdb->update(
                        $table_name,
                        array('name' => $name, 'active' => $active),
                        array('id' => $id),
                        array('%s', '%d'),
                        array('%d')
                    );
                    echo '<div class="updated"><p>Kategorie aktualisiert.</p></div>';
                } else {
                    // Insert
                    $wpdb->insert(
                        $table_name,
                        array('name' => $name, 'active' => $active),
                        array('%s', '%d')
                    );
                    echo '<div class="updated"><p>Kategorie gespeichert.</p></div>';
                }
            }
        }

        // Prepare Data for Edit Mode
        $edit_mode = false;
        $current_item = null;
        if (isset($_GET['action']) && $_GET['action'] == 'edit_kategorie' && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $current_item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
            if ($current_item) {
                $edit_mode = true;
            }
        }

        // Default values
        $name_val = $edit_mode ? $current_item->name : '';
        $active_val = $edit_mode ? $current_item->active : 1;
        $id_val = $edit_mode ? $current_item->id : 0;

        // Fetch Items
        $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY name ASC");

        ?>
        <div class="wrap">
            <h1>Kategorien verwalten</h1>
            
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <h2><?php echo $edit_mode ? 'Kategorie bearbeiten' : 'Neue Kategorie anlegen'; ?></h2>
                    <form method="post" action="<?php echo remove_query_arg(array('action', 'id')); ?>">
                        <?php wp_nonce_field('hm_save_kategorie'); ?>
                        <input type="hidden" name="hm_kategorie_id" value="<?php echo esc_attr($id_val); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="hm_name">Name</label></th>
                                <td><input name="hm_name" type="text" id="hm_name" class="regular-text" value="<?php echo esc_attr($name_val); ?>" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="hm_active">Aktiv</label></th>
                                <td><input name="hm_active" type="checkbox" id="hm_active" value="1" <?php checked($active_val, 1); ?>></td>
                            </tr>
                        </table>
                        <?php submit_button($edit_mode ? 'Aktualisieren' : 'Speichern', 'primary', 'hm_submit_kategorie'); ?>
                        <?php if ($edit_mode): ?>
                            <a href="<?php echo admin_url('admin.php?page=hm_kategorien'); ?>" class="button">Abbrechen</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div style="flex: 2;">
                    <h2>Vorhandene Kategorien</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Aktiv</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($items): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo esc_html($item->id); ?></td>
                                        <td><?php echo esc_html($item->name); ?></td>
                                        <td><?php echo $item->active ? 'Ja' : 'Nein'; ?></td>
                                        <td>
                                            <a href="<?php echo admin_url('admin.php?page=hm_kategorien&action=edit_kategorie&id=' . $item->id); ?>" class="button button-small">Bearbeiten</a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=hm_kategorien&action=delete_kategorie&id=' . $item->id), 'hm_delete_kategorie_' . $item->id); ?>" class="button button-small button-link-delete" onclick="return confirm('Wirklich löschen?');">Löschen</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4">Keine Kategorien gefunden.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    private function send_activation_email($to, $name) {
        $subject = 'Dein Stand wurde freigeschaltet!';
        $message = "Hallo $name,\n\n";
        $message .= "Gute Neuigkeiten! Dein Stand für den Hofflohmarkt wurde geprüft und freigeschaltet.\n";
        $message .= "Er ist nun auf der Karte sichtbar.\n\n";
        $message .= "Wir wünschen dir viel Erfolg und Spaß beim Hofflohmarkt!\n\n";
        $message .= "Viele Grüße,\n";
        $message .= "Dein Hofflohmarkt-Team";

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail($to, $subject, $message, $headers);
    }

    private function render_space_offer_detail($id) {
        global $wpdb;
        $table_space_offers = $wpdb->prefix . 'hm_space_offers';
        
        $offer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_space_offers WHERE id = %d", $id));
        
        if (!$offer) {
            echo '<div class="error"><p>Platzangebot nicht gefunden.</p></div>';
            return;
        }
        
        // Get bewerbungen for this space offer
        $bewerbungen = HM_Bewerbungen::get_bewerbungen_for_stand($id, 'space');
        
        ?>
        <div class="wrap">
            <h1>Platzangebot: <?php echo esc_html($offer->vorname . ' ' . $offer->nachname); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=hm_space_offers'); ?>" class="button">← Zurück zur Übersicht</a>
            
            <div style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2>Angebots-Details</h2>
                <table class="form-table">
                    <tr>
                        <th>Name:</th>
                        <td><?php echo esc_html($offer->vorname . ' ' . $offer->nachname); ?></td>
                    </tr>
                    <tr>
                        <th>E-Mail:</th>
                        <td><a href="mailto:<?php echo esc_attr($offer->email); ?>"><?php echo esc_html($offer->email); ?></a></td>
                    </tr>
                    <tr>
                        <th>Adresse:</th>
                        <td><?php echo esc_html($offer->strasse . ' ' . $offer->hausnummer . ', ' . $offer->plz . ' ' . $offer->ort); ?></td>
                    </tr>
                    <tr>
                        <th>Verfügbare Plätze:</th>
                        <td><?php echo esc_html($offer->available_spots); ?></td>
                    </tr>
                    <tr>
                        <th>Beschreibung:</th>
                        <td><?php echo esc_html($offer->space_description); ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <?php if ($offer->active): ?>
                                <span style="color: green; font-weight: bold;">Aktiv</span>
                            <?php else: ?>
                                <span style="color: orange; font-weight: bold;">Inaktiv</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2>Bewerbungen (<?php echo count($bewerbungen); ?>)</h2>
                <?php if ($bewerbungen && count($bewerbungen) > 0): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Datum</th>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Telefon</th>
                                <th>Nachricht</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bewerbungen as $bewerbung): ?>
                                <tr>
                                    <td><?php echo esc_html(date('d.m.Y H:i', strtotime($bewerbung->created_at))); ?></td>
                                    <td><strong><?php echo esc_html($bewerbung->vorname . ' ' . $bewerbung->nachname); ?></strong></td>
                                    <td><a href="mailto:<?php echo esc_attr($bewerbung->email); ?>"><?php echo esc_html($bewerbung->email); ?></a></td>
                                    <td><?php echo esc_html($bewerbung->telefon ?: '-'); ?></td>
                                    <td><?php echo esc_html($bewerbung->nachricht ?: '-'); ?></td>
                                    <td>
                                        <?php if ($bewerbung->status === 'pending'): ?>
                                            <span style="color: orange;">Ausstehend</span>
                                        <?php elseif ($bewerbung->status === 'approved'): ?>
                                            <span style="color: green;">Genehmigt</span>
                                        <?php elseif ($bewerbung->status === 'rejected'): ?>
                                            <span style="color: red;">Abgelehnt</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Noch keine Bewerbungen für dieses Platzangebot.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
