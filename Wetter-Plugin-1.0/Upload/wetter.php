<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'wetter.php');

require_once "./global.php";

global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $plugins;

// Plugin-Hauptdatei für Helferfunktionen laden
if (!function_exists('wetter_info')) { // Prüfe auf eine Funktion aus deinem Plugin
    if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT . "inc/plugins/wetter.php";
    } else {
        error("Wetter-Plugin Hauptdatei nicht gefunden.");
        exit;
    }
}

$lang->load("wetter"); // Lädt inc/languages/DEINE_SPRACHE/wetter.lang.php

if (isset($mybb->settings['wetter_plugin_active']) && $mybb->settings['wetter_plugin_active'] == 0) {
    error($lang->wetter_plugin_disabled_frontend);
    exit;
}

$plugin_info_debug = wetter_info(); // Sicherstellen, dass wetter_info existiert
$wetter_version = isset($plugin_info_debug['version']) ? $plugin_info_debug['version'] : time();

// --- Seitenlogik und Datenbeschaffung ---
$page_title_for_template = $lang->wetter_page_title_default;
$headline_text_for_page = $lang->wetter_page_title_default;
add_breadcrumb($lang->wetter_page_title_default, "wetter.php");

// Stadt und Ansicht bestimmen
$configured_cities = wetter_helper_get_cities_array_from_string(); // Deine umbenannte Helferfunktion
$selected_city_name_input = $mybb->get_input('stadt', MyBB::INPUT_STRING);
$view_mode_input = $mybb->get_input('view', MyBB::INPUT_STRING);
$is_archived_view_active = ($view_mode_input === 'archive');
$city_to_display_original = "";

if (!empty($configured_cities)) {
    if (!empty($selected_city_name_input) && in_array($selected_city_name_input, $configured_cities)) {
        $city_to_display_original = $selected_city_name_input;
    } else {
        $city_to_display_original = $configured_cities[0];
    }
    $city_for_breadcrumb_escaped = htmlspecialchars($city_to_display_original);
    $view_type_for_breadcrumb = $is_archived_view_active ? ($lang->wetter_archive_short ?: "Archiv") : ($lang->wetter_current_short ?: "Aktuell");
    $breadcrumb_city_text = sprintf(($lang->wetter_breadcrumb_city_view ?: "%s (%s)"), $city_for_breadcrumb_escaped, $view_type_for_breadcrumb);
    $breadcrumb_url = "wetter.php?stadt=". urlencode($city_to_display_original). ($is_archived_view_active ? "&amp;view=archive" : "");
    add_breadcrumb($breadcrumb_city_text, $breadcrumb_url);
    $headline_text_for_page = $breadcrumb_city_text;
    $page_title_for_template = $breadcrumb_city_text;
}

// Navigation vorbereiten
$city_options_html = "";
$archive_toggle_link_html = "";
if (!empty($configured_cities)) {
    foreach ($configured_cities as $city_nav) {
        $selected_attr_current = ($city_nav == $city_to_display_original && !$is_archived_view_active) ? ' selected="selected"' : '';
        $city_options_html .= '<option value="wetter.php?stadt='.urlencode($city_nav).'"'.$selected_attr_current.'>'.htmlspecialchars($city_nav).' ('.$lang->wetter_current_short.')</option>';
        $selected_attr_archive = ($city_nav == $city_to_display_original && $is_archived_view_active) ? ' selected="selected"' : '';
        $city_options_html .= '<option value="wetter.php?stadt='.urlencode($city_nav).'&view=archive"'.$selected_attr_archive.'>'.htmlspecialchars($city_nav).' ('.$lang->wetter_archive_short.')</option>';
    }
    if($city_to_display_original) {
        if ($is_archived_view_active) {
            $archive_toggle_link_html = ' | <a href="wetter.php?stadt='.urlencode($city_to_display_original).'">'.$lang->wetter_show_current_link.'</a>';
        } else {
            $archive_toggle_link_html = ' | <a href="wetter.php?stadt='.urlencode($city_to_display_original).'&view=archive">'.$lang->wetter_show_archive_link.'</a>';
        }
    }
} else {
     $city_options_html = "<option value=''>".$lang->wetter_message_no_cities_configured_frontend."</option>";
}

$wetter_navigation_output_content = $templates->get("wetter_nav"); // Direkt holen
if (!empty($wetter_navigation_output_content)) {
    eval("\$wetter_navigation_output = \"".$wetter_navigation_output_content."\";");
} else {
    $wetter_navigation_output = "";
    error_log("Wetter Frontend FEHLER: Navigations-Template 'wetter_nav' NICHT gefunden oder leer! Cache: ".(is_object($templates) && property_exists($templates, 'cache') ? implode(',',array_keys($templates->cache)) : 'nicht verfügbar'));
}

// Tabelleninhalt vorbereiten
$wetter_table_rows_content = "";
$no_data_message = ""; 

if ($city_to_display_original) {
    $weather_entries_for_display = wetter_helper_get_weather_data($city_to_display_original, $is_archived_view_active, 10); // Deine umbenannte Helferfunktion
    if (!empty($weather_entries_for_display)) {
        $alt_row_class_name_toggle = "trow1";
        $template_data_row_content = $templates->get("wetter_data_row"); // Direkt holen

        if(!empty($template_data_row_content)) {
            foreach ($weather_entries_for_display as $entry_data) {
                $wetter_entry_data = array();
                // ... (Daten für $wetter_entry_data füllen, wie in Deinem Code)
                $db_datum_value = isset($entry_data['datum']) ? $entry_data['datum'] : null;
                $timestamp_value = $db_datum_value ? strtotime($db_datum_value) : false;

                if ($timestamp_value === false || $timestamp_value < 0) {
                    $wetter_entry_data['datum_formatiert'] = $lang->wetter_invalid_date_format_frontend;
                } else {
                    $date_format_setting = isset($mybb->settings['wetter_plugin_date_format']) && !empty($mybb->settings['wetter_plugin_date_format']) ? $mybb->settings['wetter_plugin_date_format'] : 'd.m.Y';
                    $wetter_entry_data['datum_formatiert'] = date($date_format_setting, $timestamp_value);
                }
                $wetter_entry_data['zeitspanne']      = isset($entry_data['zeitspanne']) ? htmlspecialchars((string)$entry_data['zeitspanne']) : '-';
                $wetter_entry_data['temperatur']      = isset($entry_data['temperatur']) ? htmlspecialchars((string)$entry_data['temperatur']) : '-';
                $wetter_entry_data['wetterlage']      = isset($entry_data['wetterlage']) ? htmlspecialchars((string)$entry_data['wetterlage']) : '-';
                $wetter_entry_data['sonnenaufgang']   = isset($entry_data['sonnenaufgang']) ? htmlspecialchars((string)$entry_data['sonnenaufgang']) : '-';
                $wetter_entry_data['sonnenuntergang'] = isset($entry_data['sonnenuntergang']) ? htmlspecialchars((string)$entry_data['sonnenuntergang']) : '-';
                $wetter_entry_data['mondphase']       = isset($entry_data['mondphase']) ? htmlspecialchars((string)$entry_data['mondphase']) : '-';
                $wetter_entry_data['windrichtung']    = isset($entry_data['windrichtung']) ? htmlspecialchars((string)$entry_data['windrichtung']) : '-';
                $wetter_entry_data['windstaerke']     = isset($entry_data['windstaerke']) ? htmlspecialchars((string)$entry_data['windstaerke']) : '-';
                $wetter_entry_data['icon_aus_db']     = isset($entry_data['icon']) ? htmlspecialchars((string)$entry_data['icon']) : 'wi-na';
                $alt_row_class_name = $alt_row_class_name_toggle;
                $alt_row_class_name_toggle = ($alt_row_class_name_toggle == 'trow1') ? 'trow2' : 'trow1';
                eval("\$wetter_table_rows_content .= \"".$template_data_row_content."\";");
            }
        } else {
            $wetter_table_rows_content = "<tr><td colspan='10' style='text-align:center;'>FEHLER: Template 'wetter_data_row' konnte nicht geladen werden oder ist leer.</td></tr>";
            error_log("Wetter Frontend FEHLER: Template 'wetter_data_row' ist leer oder nicht im Cache! Cache: ".(is_object($templates) && property_exists($templates, 'cache') ? implode(',',array_keys($templates->cache)) : 'nicht verfügbar'));
        }
    } else { // Keine Wetterdaten für die ausgewählte Stadt
        $view_type_text = $is_archived_view_active ? ($lang->wetter_archived_data_term ?: "archivierte") : ($lang->wetter_current_data_term ?: "aktuelle");
        $city_name_escaped = htmlspecialchars($city_to_display_original);
        $no_data_message = sprintf(($lang->wetter_message_no_data_for_city_view ?: "Keine %s Wetterdaten für %s gefunden."), $view_type_text, $city_name_escaped);
    }
} else { // Keine Städte konfiguriert
     $no_data_message = $lang->wetter_message_no_cities_configured_frontend;
}

// Wenn $wetter_table_rows_content noch leer ist (weil keine Daten ODER keine Stadt), lade wetter_no_data
if (empty($wetter_table_rows_content) && !empty($no_data_message)) {
    $template_no_data_content = $templates->get("wetter_no_data"); // Direkt holen
    if (!empty($template_no_data_content)) {
        eval("\$wetter_table_rows_content = \"".$template_no_data_content."\";");
    } else {
        $wetter_table_rows_content = "<tr><td colspan='10' style='text-align:center;'>FEHLER: Template 'wetter_no_data' konnte nicht geladen werden. Nachricht: {$no_data_message}</td></tr>";
        error_log("Wetter Frontend FEHLER: Template 'wetter_no_data' ist leer oder nicht im Cache! Cache: ".(is_object($templates) && property_exists($templates, 'cache') ? implode(',',array_keys($templates->cache)) : 'nicht verfügbar'));
    }
}

// Haupttemplate ausgeben
$page_template_content = $templates->get("wetter_main");
if (!empty($page_template_content)) {
    eval("\$page_output = \"".$page_template_content."\";");
    output_page($page_output);
} else {
    $cache_keys_string = "Cache nicht verfügbar";
    if(is_object($templates) && property_exists($templates, 'cache')) {
        $cache_keys_string = implode(", ", array_keys($templates->cache));
    }
    error_log("Wetter Frontend FEHLER ENDGÜLTIG: Haupttemplate 'wetter_main' gab leeren Inhalt zurück oder wurde nicht gefunden. Verfügbare Cache-Keys: " . $cache_keys_string);
    error("Das Haupttemplate für die Wetterseite ('wetter_main') konnte NICHT geladen werden oder ist leer (nach get).");
}
exit;
?>