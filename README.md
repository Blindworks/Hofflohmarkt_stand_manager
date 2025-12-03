# Hofflohmarkt Stand Manager

A WordPress plugin for managing stands for yard sales (Hofflohmärkte). This plugin allows users to register their stands and displays them on an interactive map. It also provides extensive management features for administrators.

## Features

*   **Stand Registration:** Frontend form allowing participants to easily register their stands.
*   **Interactive Map:** All registered and activated stands are displayed on a map (e.g., Leaflet/OpenStreetMap).
*   **Stand Management:** Administrators can view, edit, activate, or delete stands in the backend.
*   **Yard Sale Nests:** Support for "Nests" (groupings of stands), which can be specially highlighted on the map.
*   **Space Offers & Requests:** Functionality for participants to offer free space or search for space at other stands.
*   **Categories:** Stands can be assigned to various categories (e.g., Clothing, Toys, Furniture).
*   **Email Notifications:** Automatic emails to users upon successful registration and when their stand is activated.
*   **Geocoding:** Automatic conversion of addresses into coordinates for map display.
*   **Admin Dashboard:** Clear overview and filtering of stands in the WordPress admin area.

## Installation

1.  Upload the plugin folder `hofflohmarkt-stand-manager` to the `/wp-content/plugins/` directory of your WordPress installation.
2.  Activate the plugin via the "Plugins" menu in WordPress.
3.  Upon activation, the necessary database tables will be created automatically.

## Usage

### For Administrators
*   Navigate to "Hofflohmarkt" in the WordPress admin menu to view settings and the list of registered stands.
*   Stands must be manually activated before they appear on the public map (unless configured otherwise).
*   Manage categories and space offers via the corresponding submenus.

### For Users
*   Embed the registration form, space offer form, and the map on your pages using shortcodes:
    *   `[hm_registration]` - Displays the stand registration form.
    *   `[hm_offer_space]` - Displays the form to offer available space.
    *   `[hm_map]` - Displays the map with all active stands and space offers.

## Requirements

*   WordPress 5.0 or higher
*   PHP 7.4 or higher

## Development

This plugin was developed to simplify the organization of decentralized flea markets.

### File Structure
*   `hofflohmarkt-stand-manager.php`: Main plugin file.
*   `includes/`: Contains PHP classes for database, admin, forms, and map.
*   `assets/`: Contains CSS and JavaScript files.

## License

GPLv2 or later
