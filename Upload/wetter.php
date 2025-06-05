<?php
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'wetter.php'); 

require_once "./global.php";

// $plugins->run_hooks('wetter_start'); // Falls du Hooks hier brauchst

// frontend_pagination global machen, damit es im Template verfügbar ist
global $mybb, $db, $lang, $templates, $theme, $headerinclude, $header, $footer, $plugins, $frontend_pagination; 

// Plugin-Hauptdatei für Helferfunktionen laden
if (!function_exists('wetter_info')) {
    if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT . "inc/plugins/wetter.php";
    } else {
        error("Wetter-Plugin Hauptdatei nicht gefunden."); 
    }
}

$lang->load("wetter"); 

if (isset($mybb->settings['wetter_plugin_active']) && $mybb->settings['wetter_plugin_active'] == 0) {
    error($lang->wetter_plugin_disabled_frontend); 
}

$plugin_info_debug = wetter_info();
$wetter_version_for_css = $mybb->settings['wetter_version'] ?? $plugin_info_debug['version'] ?? time(); // Für Cache-Busting der CSS-Datei, nimm die installierte Version

// --- Parameter auslesen ---
$stadt_param = $mybb->get_input('stadt', MyBB::INPUT_STRING);
$filter_datum_param = $mybb->get_input('filter_datum', MyBB::INPUT_STRING);
if (!empty($filter_datum_param) && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $filter_datum_param)) {
    $filter_datum_param = ""; 
}
$filter_datum_html = htmlspecialchars_uni($filter_datum_param); 

$view_param = $mybb->get_input('view', MyBB::INPUT_STRING);
$archive_active = ($view_param == 'archive');

// --- Paginierungsparameter ---
$items_per_page = (int)$mybb->settings['wetter_plugin_items_per_page_frontend'];
$page_num = $mybb->get_input('page', MyBB::INPUT_INT);
if($page_num <= 0) {
    $page_num = 1;
}
$start = 0;


// --- Stadt für Query und Überschrift bestimmen ---
$stadt_fuer_query = '';
$headline_stadt_text = '';
$page_title_for_template = $lang->wetter_page_title_default; 

$configured_cities_for_check = wetter_helper_get_cities_array_from_string();

if (!empty($stadt_param) && in_array($stadt_param, $configured_cities_for_check)) {
    $stadt_fuer_query = $stadt_param;
    $headline_stadt_text = sprintf($lang->wetter_headline_city_specific, htmlspecialchars_uni(ucfirst($stadt_param)));
    $page_title_for_template = sprintf($lang->wetter_headline_city_specific, htmlspecialchars_uni(ucfirst($stadt_param)));
} else {
    $stadt_fuer_query = '__ALL__'; 
    $headline_stadt_text = $lang->wetter_headline_all_cities;
    $page_title_for_template = $lang->wetter_headline_all_cities;
}

$headline_text_for_page = $headline_stadt_text;
if (!empty($filter_datum_param)) {
    $formatted_filter_date = htmlspecialchars_uni(my_date($mybb->settings['dateformat'], strtotime($filter_datum_param)) ?: $filter_datum_param);
    $headline_text_for_page .= sprintf($lang->wetter_headline_filtered_by_date, $formatted_filter_date);
    $page_title_for_template .= sprintf($lang->wetter_headline_filtered_by_date, $formatted_filter_date);
}
$view_type_text = $archive_active ? ($lang->wetter_view_archive ?: "Archiv") : ($lang->wetter_view_current ?: "Aktuell");
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
if (!empty($stadt_param) && $stadt_fuer_query != '__ALL__') { 
    $archive_link_params['stadt'] = $stadt_param; // urlencode nicht nötig für http_build_query
}
if (!empty($filter_datum_param)) {
    $archive_link_params['filter_datum'] = $filter_datum_param;
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
// Zuerst ALLE relevanten Daten holen, um die Gesamtzahl für die Paginierung zu bekommen.
// Setze den Limit-Parameter der Helper-Funktion auf 0, um alle zu bekommen (falls die Helper-Funktion das so interpretiert)
// oder stelle sicher, dass die Helper-Funktion alle passenden Daten liefert, wenn Limit = 0.
$all_relevant_wetterdaten = wetter_helper_get_weather_data($stadt_fuer_query, $archive_active, 0, $filter_datum_param);

$total_rows = count($all_relevant_wetterdaten);
$wetterdaten_fuer_diese_seite = array();

if ($items_per_page > 0 && $total_rows > 0) {
    $start = ($page_num - 1) * $items_per_page;
    $wetterdaten_fuer_diese_seite = array_slice($all_relevant_wetterdaten, $start, $items_per_page);
} else {
    // Keine Paginierung oder keine Daten -> alle (gefilterten) Daten anzeigen
    $wetterdaten_fuer_diese_seite = $all_relevant_wetterdaten;
}

// --- Paginierungslinks generieren ---
$frontend_pagination = ""; // Sicherstellen, dass es initialisiert ist
if ($items_per_page > 0 && $total_rows > $items_per_page) {
    $pagination_url = "wetter.php?";
    $url_params_for_pagination = [];
    if (!empty($stadt_param) && $stadt_fuer_query != '__ALL__') {
        $url_params_for_pagination['stadt'] = $stadt_param;
    }
    if (!empty($filter_datum_param)) {
        $url_params_for_pagination['filter_datum'] = $filter_datum_param;
    }
    if ($archive_active) {
        $url_params_for_pagination['view'] = 'archive';
    }
    if(!empty($url_params_for_pagination)) {
        $pagination_url .= http_build_query($url_params_for_pagination, '', '&amp;') . '&amp;';
    }
    // Das {page} wird von multipage() ersetzt.
    $frontend_pagination = multipage($total_rows, $items_per_page, $page_num, $pagination_url."page={page}");
}


// --- Wetterdaten für das Template vorbereiten ---
$wetter_table_rows_content = "";
$alt_row_class_name = "trow1"; 

if (!empty($wetterdaten_fuer_diese_seite)) { // Verwende die paginierten Daten
    foreach ($wetterdaten_fuer_diese_seite as $row) { // Verwende die paginierten Daten
        $wetter_entry_data = array();
        $wetter_entry_data['city_name_html'] = htmlspecialchars_uni($row['city_name'] ?? 'N/A'); 
        $wetter_entry_data['datum_formatiert'] = htmlspecialchars_uni(my_date($mybb->settings['wetter_plugin_date_format'], strtotime($row['datum'])));
        $wetter_entry_data['zeitspanne_html'] = htmlspecialchars_uni($row['zeitspanne']);
        $icon_klasse_aus_db = $row['icon'];
		$wetter_entry_data['icon_html'] = !empty($icon_klasse_aus_db) && $icon_klasse_aus_db != 'wi-na' ? '<i class="wi wetter-icon-frontend-gross ' . htmlspecialchars_uni($icon_klasse_aus_db) . '" title="' . htmlspecialchars_uni($icon_klasse_aus_db) . '"></i>' : '-';

        $wetter_entry_data['temperatur_html'] = htmlspecialchars_uni($row['temperatur']).'&deg;C';
        $wetter_entry_data['wetterlage_html'] = htmlspecialchars_uni($row['wetterlage']);
        $wetter_entry_data['sonnenaufgang_html'] = !empty($row['sonnenaufgang']) ? htmlspecialchars_uni($row['sonnenaufgang']) : '-';
        $wetter_entry_data['sonnenuntergang_html'] = !empty($row['sonnenuntergang']) ? htmlspecialchars_uni($row['sonnenuntergang']) : '-';
        
        $mondphase_icon_class = $row['mondphase'];
		if (!empty($mondphase_icon_class) && $mondphase_icon_class !== 'wi-na') {
		$wetter_entry_data['mondphase_html'] = '<i class="wi wetter-icon-frontend-gross ' . htmlspecialchars_uni($mondphase_icon_class) . '" title="' . htmlspecialchars_uni($mondphase_icon_class) . '"></i>';
		} else {
		$wetter_entry_data['mondphase_html'] = '-';
		}

        
        // Windrichtung vorbereiten
        $windrichtung_db_val = $row['windrichtung']; // Enthält z.B. "wi-from-n"
		$windrichtung_html_output = '-'; 

		if (!empty($windrichtung_db_val) && $windrichtung_db_val !== 'wi-na') {
		// Nur spezifische Wind-Richtung-Klassen bekommen das 'wi-wind' Präfix
		if (strpos($windrichtung_db_val, 'wi-from-') === 0) { // Du hattest 'wi-towards-' ja entfernt
        $windrichtung_html_output = '<i class="wi wetter-icon-frontend-gross wi-wind ' . htmlspecialchars_uni($windrichtung_db_val) . '" title="' . htmlspecialchars_uni($windrichtung_db_val) . '"></i>';
		}
            // Optional: Fallback für andere 'wi-' Klassen, falls die hier unerwartet landen (sollte für Windrichtung nicht passieren, wenn Daten konsistent sind)
            // else if (strpos($windrichtung_db_val, 'wi-') === 0) { // Z.B. wenn 'wi-na' doch als Icon behandelt werden soll oder ein anderes generisches wi-Icon
            //     $windrichtung_html_output = '<i class="wi ' . htmlspecialchars_uni($windrichtung_db_val) . '" title="' . htmlspecialchars_uni($windrichtung_db_val) . '"></i>';
            // }
        }
        $wetter_entry_data['windrichtung_html'] = $windrichtung_html_output; // **KORREKTUR HIER**

        $wetter_entry_data['windstaerke_html'] = !empty($row['windstaerke']) ? htmlspecialchars_uni($row['windstaerke']).' km/h' : '-';

        eval("\$wetter_table_rows_content .= \"".$templates->get("wetter_data_row")."\";");
        $alt_row_class_name = ($alt_row_class_name == 'trow1') ? 'trow2' : 'trow1';
    }
}

// Meldung für keine Daten
$no_data_message = "";
if (empty($wetter_table_rows_content)) {
    // Unterscheide die Meldung, ob Filter aktiv sind oder nicht
    if (!empty($stadt_param) || !empty($filter_datum_param)) {
        $no_data_message = $lang->wetter_no_data_message_filter; // z.B. "Keine Wetterdaten für die aktuelle Auswahl/Filter gefunden."
    } else if ($stadt_fuer_query == '__ALL__') {
        $no_data_message = $lang->wetter_no_data_message_all_cities; // z.B. "Keine Wetterdaten für alle Städte vorhanden."
    } else {
        $no_data_message = $lang->wetter_no_data_message; // Allgemeine Meldung
    }
    eval("\$wetter_table_rows_content = \"".$templates->get("wetter_no_data")."\";");
}

// Navigation rendern
eval("\$wetter_navigation_output = \"".$templates->get("wetter_nav")."\";");

// Haupttemplate ausgeben
eval("\$page_output = \"".$templates->get("wetter_main")."\";");
output_page($page_output);

// $plugins->run_hooks('wetter_end'); // Falls du Hooks hier brauchst
?>