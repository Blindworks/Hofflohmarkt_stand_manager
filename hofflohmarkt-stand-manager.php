<?php
/**
 * Plugin Name: Hofflohmarkt Stand Manager
 * Plugin URI:  https://blindworks.org/projects/hofflohmarkt.html
 * Description: Verwaltet Stände für Hofflohmärkte: Frontend-Anmeldung, interaktive Leaflet-Karte, Platzangebote und Bewerbungs-Workflow.
 * Version:     1.0.0
 * Author:      Blindworks
 * Author URI:  https://blindworks.org/projects/hofflohmarkt.html
 * Text Domain: hofflohmarkt-stand-manager
 */

// Exit if accessed directly - ensures WordPress is loaded
if (!defined('ABSPATH')) {
	exit;
}

// Define Constants
define('HM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('HM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once HM_PLUGIN_PATH . 'includes/class-hm-db.php';
require_once HM_PLUGIN_PATH . 'includes/class-hm-admin.php';
require_once HM_PLUGIN_PATH . 'includes/class-hm-form-handler.php';
require_once HM_PLUGIN_PATH . 'includes/class-hm-map.php';
require_once HM_PLUGIN_PATH . 'includes/class-hm-bewerbungen.php';
require_once HM_PLUGIN_PATH . 'includes/class-hm-stand-counter.php';

// Register activation hook
register_activation_hook(__FILE__, array('HM_DB', 'install'));

/**
 * Main Plugin Class
 */
class Hofflohmarkt_Stand_Manager
{

	public function __construct()
	{
		// Initialize components
		new HM_Admin();
		new HM_Form_Handler();
		new HM_Map();
		new HM_Bewerbungen();
		new HM_Stand_Counter();

		// Enqueue scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
	}

	public function enqueue_assets()
	{
		// Enqueue scripts and styles here
	}
}

// Initialize the plugin
new Hofflohmarkt_Stand_Manager();