# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Plugin Overview

**Hofflohmarkt Stand Manager** ist ein WordPress-Plugin zur Verwaltung von Flohmarktständen. Es erlaubt Teilnehmern, sich über ein Frontend-Formular anzumelden, und zeigt aktive Stände auf einer interaktiven Leaflet/OpenStreetMap-Karte an.

## Development Setup

Dieses Plugin hat kein eigenes Build-System. Entwicklung erfolgt direkt in PHP/JS/CSS.

**Deployment:**
1. Plugin-Ordner in `/wp-content/plugins/hofflohmarkt-stand-manager/` einer WordPress-Installation kopieren
2. Plugin im WP-Admin aktivieren → `HM_DB::install()` erstellt alle Datenbanktabellen automatisch via `dbDelta()`

**Testumgebung:** Eine lokale WordPress-Instanz (z.B. mit LocalWP, XAMPP oder Docker) ist erforderlich.

## Architecture

Der Einstiegspunkt `hofflohmarkt-stand-manager.php` lädt alle Klassen und instanziiert sie im `Hofflohmarkt_Stand_Manager`-Konstruktor.

### PHP-Klassen (`includes/`)

| Klasse | Datei | Verantwortlichkeit |
|--------|-------|-------------------|
| `HM_DB` | `class-hm-db.php` | Schema-Installation via `dbDelta()`. Statische `install()`-Methode wird beim Plugin-Aktivieren aufgerufen. |
| `HM_Admin` | `class-hm-admin.php` | WordPress-Admin-Menü (Stände, Platzangebote, Hofflohmärkte, Kategorien). CRUD-Operationen direkt in Render-Methoden. |
| `HM_Form_Handler` | `class-hm-form-handler.php` | Shortcodes `[hm_registration]` und `[hm_offer_space]`. POST-Verarbeitung, Adress-Geocodierung via Nominatim-API, E-Mail-Versand. |
| `HM_Map` | `class-hm-map.php` | Shortcode `[hm_map]`. Lädt Leaflet, gibt aktive Stände + Platzangebote als JSON an `hm-map.js` weiter. |
| `HM_Bewerbungen` | `class-hm-bewerbungen.php` | AJAX-Handler für Standplatzbewerbungen. Token-basierte Accept/Reject-Links per E-Mail an Stand-Inhaber. |

### Datenbanktabellen (Prefix: `wp_hm_`)

- `hm_hofflohmaerkte` – Veranstaltungen
- `hm_kategorien` – Produktkategorien
- `hm_staende` – Standanmeldungen (enthält lat/lng nach Geocodierung, `active=0` bis zur Admin-Freigabe)
- `hm_stand_kategorien` – Pivot-Tabelle Stand ↔ Kategorie
- `hm_space_offers` – Platzangebote von Teilnehmern ohne eigenen Stand
- `hm_bewerbungen` – Bewerbungen auf Stände/Platzangebote mit Status (`pending`/`accepted`/`rejected`) und Einweg-`action_token`

### Geocodierung

`HM_Form_Handler::geocode_address()` ist eine statische Methode, die Nominatim (OpenStreetMap) nutzt. Ergebnisse werden 12 Stunden als WordPress Transient gecacht (`hm_geo_` + MD5 der Adresse).

### Bewerbungs-Workflow

1. Besucher klickt auf Karten-Marker → AJAX-Request an `wp_ajax_hm_submit_bewerbung`
2. Bewerbung wird in `hm_bewerbungen` mit `status=pending` und zufälligem `action_token` gespeichert
3. Stand-Inhaber erhält E-Mail mit Accept/Reject-URLs (`?hm_action=accept&token=...&bid=...`)
4. `HM_Bewerbungen::handle_bewerbung_action()` verarbeitet den Link, invalidiert den Token und benachrichtigt den Bewerber

### Frontend-Assets

- `assets/js/hm-map.js` + `assets/css/hm-style.css` – Karten-Darstellung (Leaflet-Marker, Popups mit Bewerbungsformular)
- `assets/js/hm-bewerbung.js` + `assets/css/hm-bewerbung.css` – Modal-Dialog und AJAX für Bewerbungen

Leaflet wird von `unpkg.com` geladen (Version 1.9.4).

## Wichtige Konventionen

- Alle DB-Zugriffe über `$wpdb->prepare()`, `$wpdb->insert()`, `$wpdb->update()` – kein direktes SQL mit User-Input
- Neue Stände und Platzangebote starten immer mit `active = 0` und müssen vom Admin freigeschaltet werden
- `stand_type` in `hm_bewerbungen` kann sowohl `'space'` als auch `'space_offer'` enthalten (historische Inkonsistenz) – `HM_Bewerbungen` behandelt beide Werte
