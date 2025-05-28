<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'wetter.php');

require_once "./global.php";

// $plugins->run_hooks('wetter_start'); // Falls du Hooks hier brauchst

global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $plugins;

// Plugin-Hauptdatei für Helferfunktionen laden
if (!function_exists('wetter_info')) {
    if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT . "inc/plugins/wetter.php";
    } else {
        error("Wetter-Plugin Hauptdatei nicht gefunden."); // Beachte: error() beendet das Script
    }
}

$lang->load("wetter"); // Lädt inc/languages/DEINE_SPRACHE/wetter.lang.php

if (isset($mybb->settings['wetter_plugin_active']) && $mybb->settings['wetter_plugin_active'] == 0) {
    error($lang->wetter_plugin_disabled_frontend); // Beachte: error() beendet das Script
}

$plugin_info_debug = wetter_info();
$wetter_version = $plugin_info_debug['version'] ?? time(); // Für Cache-Busting der CSS-Datei

// --- Parameter auslesen ---
$stadt_param = $mybb->get_input('stadt', MyBB::INPUT_STRING);
$filter_datum_param = $mybb->get_input('filter_datum', MyBB::INPUT_STRING);
// Format des Datums validieren (YYYY-MM-DD), um ungültige Eingaben zu verhindern
if (!empty($filter_datum_param) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $filter_datum_param)) {
    $filter_datum_param = ""; // Ungültiges Datum ignorieren
}
$filter_datum_html = htmlspecialchars_uni($filter_datum_param); // Für die Vorbelegung des Formularfelds

$view_param = $mybb->get_input('view', MyBB::INPUT_STRING);
$archive_active = ($view_param == 'archive');
// $current_view_type_for_form = $archive_active ? 'archive' : 'current'; // Für das Hidden Field im Formular, falls verwendet

// --- Stadt für Query und Überschrift bestimmen ---
$stadt_fuer_query = '';
$headline_stadt_text = '';
$page_title_for_template = $lang->wetter_page_title_default; // Standard-Seitentitel

$configured_cities_for_check = wetter_helper_get_cities_array_from_string();

if (!empty($stadt_param) && in_array($stadt_param, $configured_cities_for_check)) {
    $stadt_fuer_query = $stadt_param;
    $headline_stadt_text = sprintf($lang->wetter_headline_city_specific, htmlspecialchars_uni(ucfirst($stadt_param)));
    $page_title_for_template = sprintf($lang->wetter_headline_city_specific, htmlspecialchars_uni(ucfirst($stadt_param)));
} else {
    $stadt_fuer_query = '__ALL__'; // Konstante für "Alle Städte"
    $headline_stadt_text = $lang->wetter_headline_all_cities;
    $page_title_for_template = $lang->wetter_headline_all_cities;
}

$headline_text_for_page = $headline_stadt_text;
if (!empty($filter_datum_param)) {
    // Formatiere das Datum für die Anzeige mit dem MyBB Datumsformat
    $formatted_filter_date = htmlspecialchars_uni(my_date($mybb->settings['dateformat'], strtotime($filter_datum_param)) ?: $filter_datum_param);
    $headline_text_for_page .= sprintf($lang->wetter_headline_filtered_by_date, $formatted_filter_date);
    $page_title_for_template .= sprintf($lang->wetter_headline_filtered_by_date, $formatted_filter_date);
}
$view_type_text = $archive_active ? $lang->wetter_view_archive : $lang->wetter_view_current;
$headline_text_for_page .= " ({$view_type_text})";
$page_title_for_template .= " ({$view_type_text})";


// --- Navigationselemente generieren ---
$city_options_html = "";
if (!empty($configured_cities_for_check)) {
    foreach ($configured_cities_for_check as $city_name_loop) {
        $selected_attr = ($stadt_fuer_query == $city_name_loop && $stadt_fuer_query != '__ALL__') ? " selected=\"selected\"" : "";
        $city_options_html .= '<option value="'.htmlspecialchars_uni($city_name_loop).'"'.$selected_attr.'>'.htmlspecialchars_uni(ucfirst($city_name_loop)).'</option>';
    }
}

$archive_toggle_link_text = $archive_active ? $lang->wetter_show_current_link : $lang->wetter_show_archive_link;
$archive_toggle_view_target = $archive_active ? '' : 'archive';

$archive_link_params = [];
if (!empty($stadt_param) && $stadt_fuer_query != '__ALL__') { // Behalte Stadt bei, wenn eine spezifische ausgewählt war
    $archive_link_params['stadt'] = urlencode($stadt_param);
}
if (!empty($filter_datum_param)) {
    $archive_link_params['filter_datum'] = urlencode($filter_datum_param);
}
if (!empty($archive_toggle_view_target)) {
    $archive_link_params['view'] = $archive_toggle_view_target;
}
$archive_toggle_link_url = "wetter.php";
if (!empty($archive_link_params)) {
    $archive_toggle_link_url .= "?" . http_build_query($archive_link_params, '', '&amp;');
}
$archive_toggle_link_html = '<a href="'.$archive_toggle_link_url.'">'.$archive_toggle_link_text.'</a>';

// --- Wetterdaten abrufen ---
$limit_frontend = 100; // Bei "Alle Städte" und vielen Daten könnte Paginierung nötig werden
$wetterdaten = wetter_helper_get_weather_data($stadt_fuer_query, $archive_active, $limit_frontend, $filter_datum_param);

// --- Wetterdaten für das Template vorbereiten ---
$wetter_table_rows_content = "";
$alt_row_class_name = "trow1"; // Für abwechselnde Zeilenfarben

if (!empty($wetterdaten)) {
    foreach ($wetterdaten as $row) {
        $wetter_entry_data = array();
        $wetter_entry_data['city_name_html'] = htmlspecialchars_uni($row['city_name'] ?? 'N/A'); // city_name wird von der Helper-Funktion geliefert
        $wetter_entry_data['datum_formatiert'] = htmlspecialchars_uni(my_date($mybb->settings['wetter_plugin_date_format'], strtotime($row['datum'])));
        $wetter_entry_data['zeitspanne_html'] = htmlspecialchars_uni($row['zeitspanne']);
        $wetter_entry_data['icon_html'] = !empty($row['icon']) && $row['icon'] != 'wi-na' ? '<i class="wi '.htmlspecialchars_uni($row['icon']).'" title="'.htmlspecialchars_uni($row['icon']).'"></i>' : '-';
        $wetter_entry_data['temperatur_html'] = htmlspecialchars_uni($row['temperatur']).'&deg;C';
        $wetter_entry_data['wetterlage_html'] = htmlspecialchars_uni($row['wetterlage']);
        $wetter_entry_data['sonnenaufgang_html'] = !empty($row['sonnenaufgang']) ? htmlspecialchars_uni($row['sonnenaufgang']) : '-';
        $wetter_entry_data['sonnenuntergang_html'] = !empty($row['sonnenuntergang']) ? htmlspecialchars_uni($row['sonnenuntergang']) : '-';
        $wetter_entry_data['mondphase_html'] = !empty($row['mondphase']) ? htmlspecialchars_uni($row['mondphase']) : '-';
        $wetter_entry_data['windrichtung_html'] = !empty($row['windrichtung']) ? htmlspecialchars_uni($row['windrichtung']) : '-';
        $wetter_entry_data['windstaerke_html'] = !empty($row['windstaerke']) ? htmlspecialchars_uni($row['windstaerke']).' km/h' : '-';

        eval("\$wetter_table_rows_content .= \"".$templates->get("wetter_data_row")."\";");
        $alt_row_class_name = ($alt_row_class_name == 'trow1') ? 'trow2' : 'trow1';
    }
}

// Meldung für keine Daten
$no_data_message = "";
if (empty($wetter_table_rows_content)) {
    $no_data_message = $lang->wetter_no_data_message; // z.B. "Keine Wetterdaten für die aktuelle Auswahl gefunden."
    eval("\$wetter_table_rows_content = \"".$templates->get("wetter_no_data")."\";");
}

// Navigation rendern
eval("\$wetter_navigation_output = \"".$templates->get("wetter_nav")."\";");

// Haupttemplate ausgeben
eval("\$page_output = \"".$templates->get("wetter_main")."\";");
output_page($page_output);

// $plugins->run_hooks('wetter_end'); // Falls du Hooks hier brauchst
?>