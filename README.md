# Hofflohmarkt Stand Manager

Ein WordPress-Plugin zur Verwaltung von Ständen für Hofflohmärkte. Dieses Plugin ermöglicht es Benutzern, ihre Stände zu registrieren, und zeigt diese auf einer interaktiven Karte an. Es bietet zudem umfangreiche Verwaltungsfunktionen für Administratoren.

## Funktionen

*   **Stand-Registrierung:** Frontend-Formular, über das Teilnehmer ihre Stände einfach anmelden können.
*   **Interaktive Karte:** Alle registrierten und aktivierten Stände werden auf einer Karte (z.B. Leaflet/OpenStreetMap) dargestellt.
*   **Stand-Verwaltung:** Administratoren können Stände im Backend einsehen, bearbeiten, aktivieren oder löschen.
*   **Hofflohmarkt-Nester:** Unterstützung für "Nester" (Gruppierungen von Ständen), die auf der Karte speziell hervorgehoben werden können.
*   **Platzangebote & Gesuche:** Funktion für Teilnehmer, um freien Platz anzubieten oder nach einem Platz bei anderen Ständen zu suchen.
*   **Kategorien:** Stände können verschiedenen Kategorien zugeordnet werden (z.B. Kleidung, Spielzeug, Möbel).
*   **E-Mail-Benachrichtigungen:** Automatische E-Mails an Benutzer bei erfolgreicher Registrierung und bei Aktivierung des Standes.
*   **Geocoding:** Automatische Umwandlung von Adressen in Koordinaten für die Kartendarstellung.
*   **Admin-Dashboard:** Übersichtliche Darstellung und Filterung der Stände im WordPress-Adminbereich.

## Installation

1.  Lade den Plugin-Ordner `hofflohmarkt-stand-manager` in das Verzeichnis `/wp-content/plugins/` deiner WordPress-Installation hoch.
2.  Aktiviere das Plugin über das Menü "Plugins" in WordPress.
3.  Nach der Aktivierung werden die notwendigen Datenbanktabellen automatisch erstellt.

## Verwendung

### Für Administratoren
*   Navigiere im WordPress-Admin-Menü zu "Hofflohmarkt", um die Einstellungen und die Liste der registrierten Stände zu sehen.
*   Stände müssen manuell aktiviert werden, bevor sie auf der öffentlichen Karte erscheinen (sofern nicht anders konfiguriert).
*   Verwalte Kategorien und Platzangebote über die entsprechenden Untermenüs.

### Für Benutzer
*   Binde das Registrierungsformular, das Platzangebot-Formular und die Karte über Shortcodes auf deinen Seiten ein:
    *   `[hm_registration]` - Zeigt das Formular zur Stand-Registrierung an.
    *   `[hm_offer_space]` - Zeigt das Formular an, um freien Platz anzubieten.
    *   `[hm_map]` - Zeigt die Karte mit allen aktiven Ständen und Platzangeboten.

## Anforderungen

*   WordPress 5.0 oder höher
*   PHP 7.4 oder höher

## Entwicklung

Dieses Plugin wurde entwickelt, um die Organisation von dezentralen Flohmärkten zu vereinfachen.

### Dateistruktur
*   `hofflohmarkt-stand-manager.php`: Hauptdatei des Plugins.
*   `includes/`: Enthält die PHP-Klassen für Datenbank, Admin, Formulare und Karte.
*   `assets/`: Enthält CSS- und JavaScript-Dateien.

## Lizenz

GPLv2 or later
