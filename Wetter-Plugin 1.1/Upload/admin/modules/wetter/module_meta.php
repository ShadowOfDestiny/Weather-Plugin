<?php
if (!defined("IN_MYBB")) {
    die("Direkter Zugriff nicht erlaubt.");
}

if (!function_exists('wetter_admin_get_weather_icon_classes')) {
function wetter_get_all_icon_classes() { // Neuer, eindeutiger Name
    return array(
        'wi-day-sunny','wi-day-cloudy','wi-day-cloudy-gusts','wi-day-cloudy-windy','wi-day-fog','wi-day-hail','wi-day-haze',
        'wi-day-lightning','wi-day-rain','wi-day-rain-mix','wi-day-rain-wind','wi-day-showers','wi-day-sleet','wi-day-sleet-storm',
        'wi-day-snow','wi-day-snow-thunderstorm','wi-day-snow-wind','wi-day-sprinkle','wi-day-storm-showers','wi-day-sunny-overcast',
        'wi-day-thunderstorm','wi-day-windy','wi-solar-eclipse','wi-hot','wi-day-cloudy-high','wi-day-light-wind','wi-night-clear',
        'wi-night-alt-cloudy','wi-night-alt-cloudy-gusts','wi-night-alt-cloudy-windy','wi-night-alt-hail','wi-night-alt-lightning',
        'wi-night-alt-rain','wi-night-alt-rain-mix','wi-night-alt-rain-wind','wi-night-alt-showers','wi-night-alt-sleet',
        'wi-night-alt-sleet-storm','wi-night-alt-snow','wi-night-alt-snow-thunderstorm','wi-night-alt-snow-wind','wi-night-alt-sprinkle',
        'wi-night-alt-storm-showers','wi-night-alt-thunderstorm','wi-night-cloudy','wi-night-cloudy-gusts','wi-night-cloudy-windy',
        'wi-night-fog','wi-night-hail','wi-night-lightning','wi-night-partly-cloudy','wi-night-rain','wi-night-rain-mix',
        'wi-night-rain-wind','wi-night-showers','wi-night-sleet','wi-night-sleet-storm','wi-night-snow','wi-night-snow-thunderstorm',
        'wi-night-snow-wind','wi-night-sprinkle','wi-night-storm-showers','wi-night-thunderstorm','wi-lunar-eclipse','wi-stars',
        'wi-storm-showers','wi-thunderstorm','wi-night-alt-cloudy-high','wi-night-cloudy-high','wi-night-alt-partly-cloudy',
        'wi-moon-new','wi-moon-waxing-crescent-1','wi-moon-waxing-crescent-2','wi-moon-waxing-crescent-3','wi-moon-waxing-crescent-4',
        'wi-moon-waxing-crescent-5','wi-moon-waxing-crescent-6','wi-moon-first-quarter','wi-moon-waxing-gibbous-1','wi-moon-waxing-gibbous-2',
        'wi-moon-waxing-gibbous-3','wi-moon-waxing-gibbous-4','wi-moon-waxing-gibbous-5','wi-moon-waxing-gibbous-6','wi-moon-full',
        'wi-moon-waning-gibbous-1','wi-moon-waning-gibbous-2','wi-moon-waning-gibbous-3','wi-moon-waning-gibbous-4','wi-moon-waning-gibbous-5',
        'wi-moon-waning-gibbous-6','wi-moon-third-quarter','wi-moon-waning-crescent-1','wi-moon-waning-crescent-2','wi-moon-waning-crescent-3',
        'wi-moon-waning-crescent-4','wi-moon-waning-crescent-5','wi-moon-waning-crescent-6','wi-moon-alt-new','wi-moon-alt-waxing-crescent-1',
        'wi-moon-alt-waxing-crescent-2','wi-moon-alt-waxing-crescent-3','wi-moon-alt-waxing-crescent-4','wi-moon-alt-waxing-crescent-5',
        'wi-moon-alt-waxing-crescent-6','wi-moon-alt-first-quarter','wi-moon-alt-waxing-gibbous-1','wi-moon-alt-waxing-gibbous-2',
        'wi-moon-alt-waxing-gibbous-3','wi-moon-alt-waxing-gibbous-4','wi-moon-alt-waxing-gibbous-5','wi-moon-alt-waxing-gibbous-6',
        'wi-moon-alt-full','wi-moon-alt-waning-gibbous-1','wi-moon-alt-waning-gibbous-2','wi-moon-alt-waning-gibbous-3',
        'wi-moon-alt-waning-gibbous-4','wi-moon-alt-waning-gibbous-5','wi-moon-alt-waning-gibbous-6','wi-moon-alt-third-quarter',
        'wi-moon-alt-waning-crescent-1','wi-moon-alt-waning-crescent-2','wi-moon-alt-waning-crescent-3','wi-moon-alt-waning-crescent-4',
        'wi-moon-alt-waning-crescent-5','wi-moon-alt-waning-crescent-6','wi-na'
		);
	}
}

// Windrichtungen (aus deiner module_meta.php)
function wetter_get_wind_directions() {
    // Hier könntest du später Sprachvariablen verwenden, falls die Bezeichnungen übersetzt werden sollen.
    return array(
        "" => "--- Bitte wählen ---", "N" => "Nord (N)", "NNO" => "Nord-Nordost (NNO)", "NO" => "Nordost (NO)",
        "ONO" => "Ost-Nordost (ONO)", "O" => "Ost (O)", "OSO" => "Ost-Südost (OSO)", "SO" => "Südost (SO)",
        "SSO" => "Süd-Südost (SSO)", "S" => "Süd (S)", "SSW" => "Süd-Südwest (SSW)", "SW" => "Südwest (SW)",
        "WSW" => "West-Südwest (WSW)", "W" => "West (W)", "WNW" => "West-Nordwest (WNW)", "NW" => "Nordwest (NW)",
        "NNW" => "Nord-Nordwest (NNW)", "Windstill" => "Windstill", "Variabel" => "Variabel"
    );
}

global $lang;
// Lade die ACP-Sprachdatei für das Wetter-Modul
// MyBB sollte die Datei admin/DEINE_SPRACHE/wetter.lang.php finden, wenn sie existiert.
if(!isset($lang->wetter_admin_title)) {
    $lang->load("wetter", false, true);
}

function wetter_meta() {
    global $page, $lang, $plugins;

    $sub_menu = array();
    $sub_menu['10'] = array( "id" => "overview", "title" => $lang->wetter_admin_overview_title, "link"  => "index.php?module=wetter-overview" );
    $sub_menu['20'] = array( "id" => "cities", "title" => $lang->wetter_admin_manage_cities_title, "link" => "index.php?module=wetter-cities" );
    $sub_menu['30'] = array( "id" => "entry", "title" => $lang->wetter_admin_entry_title, "link" => "index.php?module=wetter-entry" );
    $sub_menu['50'] = array( "id" => "archive", "title" => $lang->wetter_admin_manage_archive_title, "link" => "index.php?module=wetter-archive" );
    $sub_menu['60'] = array( "id" => "archive_view", "title" => $lang->wetter_admin_view_archive_title, "link" => "index.php?module=wetter-archive_view" );
    $sub_menu['70'] = array( "id" => "settings", "title" => $lang->wetter_admin_settings_title, "link" => "index.php?module=wetter-settings" );

    // Füge das Hauptmenü-Item hinzu. "wetter" ist hier der interne Name des Moduls.
    // $lang->wetter_admin_title ist die Anzeige im Menü.
    $page->add_menu_item($lang->wetter_admin_title, "wetter", "index.php?module=wetter-overview", 60, $sub_menu);
    return true;
}

function wetter_action_handler($action) {
    global $page, $lang, $mybb; // $mybb hinzugefügt für $mybb->admin_dir

    $page->active_module = "wetter";

    $actions = array(
        "overview"     => array("active" => "overview",     "file" => "overview.php"),
        "entry"        => array("active" => "entry",        "file" => "wetter_entry.php"),
	"edit"         => array("active" => "edit",         "file" => "wetter_edit.php"), // Neue Aktion
        "archive"      => array("active" => "archive",      "file" => "wetter_archive.php"),
        "archive_view" => array("active" => "archive_view", "file" => "wetter_archive_view.php"),
        "settings"     => array("active" => "settings",     "file" => "settings.php"),
        "cities"       => array("active" => "cities",       "file" => "cities.php")
    );

    // Standardaktion, wenn keine oder eine ungültige Aktion angegeben wurde
    if (!isset($actions[$action])) {
        $action = "overview";
    }
    $page->active_action = $actions[$action]['active'];

    // Pfad zur Datei im Modulverzeichnis zurückgeben
    return $actions[$action]['file'];;
}

// ACP Berechtigungen (optional, aber empfohlen)
function wetter_admin_permissions() {
    global $lang;
    $lang->load("wetter", false, true); // Sicherstellen, dass die Sprachdatei für Berechtigungen geladen ist

    $admin_permissions = array(
        "overview"     => $lang->wetter_perm_can_manage_overview ?: "Kann Wetterübersicht verwalten?",
        "cities"       => $lang->wetter_perm_can_manage_cities ?: "Kann Städte verwalten?",
        "entry"        => $lang->wetter_perm_can_manage_entries ?: "Kann Wettereinträge verwalten?",
        "archive"      => $lang->wetter_perm_can_manage_archive ?: "Kann Wetterarchiv verwalten?",
        "settings"     => $lang->wetter_perm_can_manage_settings ?: "Kann Wetter-Plugin Einstellungen verwalten?"
    );
    return array("name" => $lang->wetter_admin_title, "permissions" => $admin_permissions, "disporder" => 60);
}
?>
