<?php
// admin/modules/wetter/wetter_archive_view.php

if(!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

global $mybb, $db, $lang, $page;

if(!isset($lang->wetter_admin_view_archive_title)) {
    $lang->load("wetter", false, true);
}

// Lade die Haupt-Plugin-Datei für Helferfunktionen
if (!function_exists('wetter_sanitize_city_name_for_table') || !function_exists('wetter_helper_get_cities_array_from_string')) {
    if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT . "inc/plugins/wetter.php";
    } else {
        $page->output_header($lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten");
        $page->output_inline_error("Fehler: Haupt-Plugin-Datei (wetter.php) nicht gefunden.");
        $page->output_footer();
        exit;
    }
}
// ACP CSS für Icons laden
if (is_object($page) && property_exists($page, 'extra_header')) {
    $plugin_version = time(); // Fallback
    if (function_exists('wetter_info')) {
        $plugin_info_css = wetter_info();
        if (isset($plugin_info_css['version'])) {
            $plugin_version = $plugin_info_css['version'];
        }
    }
    $version_param_css = '?v=' . $plugin_version;

    // Original Wetter-Icon CSS Dateien
    $css_file_main_name = 'weather-icons.min.css';
    $css_path_main = $mybb->settings['bburl'] . '/images/wetter/css/' . $css_file_main_name . $version_param_css;
    $page->extra_header .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_path_main) . '" />';

    $css_file_wind_name = 'weather-icons-wind.min.css';
    $css_path_wind = $mybb->settings['bburl'] . '/images/wetter/css/' . $css_file_wind_name . $version_param_css;
    $page->extra_header .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_path_wind) . '" />';

    // Deine benutzerdefinierte ACP CSS-Datei
    $custom_acp_css_path = $mybb->settings['bburl'] . '/images/wetter/css/wetter_acp_styles.css' . $version_param_css; // Pfad anpassen, falls nötig
    $page->extra_header .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($custom_acp_css_path) . '" />';
}

// Paginierungsparameter ACP
$page_num_acp = $mybb->get_input('page', MyBB::INPUT_INT);
if($page_num_acp <= 0) {
    $page_num_acp = 1;
}
$items_per_page_acp = (int)($mybb->settings['wetter_plugin_items_per_page_acp'] ?? 20);


$page->output_header($lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten");

$sub_menu = array();
$sub_menu['overview']     = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry']        = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry');
// $sub_menu['archive']      = array('title' => $lang->wetter_admin_manage_archive_title, 'link' => 'index.php?module=wetter-archive'); // Falls separate Verwaltungsseite
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view', 'active' => true);
$sub_menu['cities']       = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$sub_menu['settings']     = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$page->output_nav_tabs($sub_menu, 'archive_view');


echo "<h2>" . ($lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten") . "</h2>";
$archive_desc = $lang->wetter_admin_view_archive_desc ?: "Hier kannst du die archivierten Wetterdaten für die ausgewählte Stadt einsehen. Archivdaten können hier nicht direkt bearbeitet oder gelöscht werden. Nutze dafür ggf. Datenbanktools oder spezifische Archivverwaltungsfunktionen (falls implementiert).";
echo "<p>" . $archive_desc . "</p>";

$prefix = TABLE_PREFIX;

$configured_cities_str = $mybb->settings['wetter_plugin_cities'] ?? '';
$cities_array = wetter_helper_get_cities_array_from_string($configured_cities_str);

if (empty($cities_array)) {
    $page->output_inline_error($lang->wetter_admin_no_cities_configured_overview);
    $page->output_footer();
    exit;
}
sort($cities_array);

// Parametername für Stadt hier ist 'arch_stadt' oder vereinheitlichen auf 'stadt'
$selected_city_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING)); // Vereinheitlicht zu 'stadt'
if (empty($selected_city_original) || !in_array($selected_city_original, $cities_array)) {
    $selected_city_original = $cities_array[0];
}

echo '<form method="get" action="index.php" style="margin-bottom:10px;">
      <input type="hidden" name="module" value="wetter-archive_view">
      <label for="stadt_select_archive">'.($lang->wetter_admin_select_city_label ?: "Stadt wählen:").'</label>
      <select name="stadt" id="stadt_select_archive" onchange="this.form.submit()">'; // Bei Wechsel auf Seite 1 zurücksetzen
foreach ($cities_array as $city_name_loop) {
    $selected_attr = ($city_name_loop === $selected_city_original) ? " selected=\"selected\"" : "";
    echo '<option value="' . htmlspecialchars($city_name_loop) . '"' . $selected_attr . '>' . htmlspecialchars(ucfirst($city_name_loop)) . '</option>';
}
echo '</select>
      <noscript><input type="submit" value="' . ($lang->wetter_admin_show_button ?: "Anzeigen") . '" class="button" /></noscript>
      </form>';


$table_name_to_query_no_prefix = "";
$table_exists = false;
$tabelle_name_full = "";

if (!empty($selected_city_original)) {
    $city_suffix_sanitized = wetter_sanitize_city_name_for_table($selected_city_original);
    if (!empty($city_suffix_sanitized)) {
        $table_name_to_query_no_prefix = "wetter_" . $db->escape_string($city_suffix_sanitized) . "_archiv";
        $tabelle_name_full = $prefix . $table_name_to_query_no_prefix;
        $table_exists = $db->table_exists($table_name_to_query_no_prefix);
    }
}

$table_output = new Table;
$table_output->construct_header($lang->wetter_admin_date ?: "Datum");
$table_output->construct_header($lang->wetter_admin_timeslot ?: "Zeitspanne");
$table_output->construct_header($lang->wetter_admin_icon ?: "Icon", array("class" => "align_center"));
$table_output->construct_header($lang->wetter_admin_temperature ?: "Temp.");
$table_output->construct_header($lang->wetter_admin_weathercondition ?: "Wetterlage");
$table_output->construct_header($lang->wetter_admin_sunrise ?: "Sonnenaufgang");
$table_output->construct_header($lang->wetter_admin_sunset ?: "Sonnenuntergang");
$table_output->construct_header($lang->wetter_admin_moonphase ?: "Mondphase");
$table_output->construct_header($lang->wetter_admin_winddirection ?: "Windr.");
$table_output->construct_header($lang->wetter_admin_windspeed ?: "Windst.");
$num_columns_for_colspan = 10;

$total_rows = 0;
$query = false;

if ($table_exists && !empty($table_name_to_query_no_prefix)) {
    $count_query = $db->simple_select($table_name_to_query_no_prefix, "COUNT(*) as total");
    $total_rows = (int)$db->fetch_field($count_query, "total");

    if ($total_rows > 0) {
        $offset = ($page_num_acp - 1) * $items_per_page_acp;
        $query = $db->query("
            SELECT * FROM `{$tabelle_name_full}`
            ORDER BY datum ASC, zeitspanne ASC
            LIMIT {$items_per_page_acp} OFFSET {$offset}
        ");
    }
}

if ($query && $db->num_rows($query) > 0) {
    while ($row = $db->fetch_array($query)) {
        $formatiertes_datum = htmlspecialchars_uni(my_date($mybb->settings['dateformat'], strtotime($row['datum']))); // ACP Datumsformat
        $table_output->construct_cell($formatiertes_datum);
        $table_output->construct_cell(htmlspecialchars($row['zeitspanne']));
        $icon_klasse_aus_db = htmlspecialchars($row['icon']);
        if (!empty($icon_klasse_aus_db) && $icon_klasse_aus_db !== 'wi-na') {
            $table_output->construct_cell('<span class="wetter-icon-display" title="' . $icon_klasse_aus_db . '"><i class="wi wetter-icon-acp-gross ' . $icon_klasse_aus_db . '"></i></span>', array("class" => "align_center"));
        } else {
            $table_output->construct_cell('-', array("class" => "align_center"));
        }
        $table_output->construct_cell(htmlspecialchars($row['temperatur']) . "°C");
        $table_output->construct_cell(htmlspecialchars($row['wetterlage']));
        $table_output->construct_cell(!empty($row['sonnenaufgang']) ? htmlspecialchars($row['sonnenaufgang']) : '-'); // Gut
$table_output->construct_cell(!empty($row['sonnenuntergang']) ? htmlspecialchars($row['sonnenuntergang']) : '-'); // Gut

// --- ANPASSUNG VORSCHLAG FÜR MONSPHASE ---
// Alt: $table_output->construct_cell(!empty($row['mondphase']) ? htmlspecialchars($row['mondphase']) : '-');
$mondphase_icon_db = htmlspecialchars($row['mondphase']);
        if (!empty($mondphase_icon_db) && $mondphase_icon_db !== 'wi-na') {
    $table_output->construct_cell('<span class="wetter-icon-display" title="' . $mondphase_icon_db . '"><i class="wi wetter-icon-acp-gross ' . $mondphase_icon_db . '"></i></span>', array("class" => "align_center"));
} else {
    $table_output->construct_cell('-', array("class" => "align_center"));
}
// --- ENDE ANPASSUNG MONSPHASE ---

$windrichtung_db_val = htmlspecialchars_uni($row['windrichtung']);
if (!empty($windrichtung_db_val) && $windrichtung_db_val !== 'wi-na') {
    // KORREKTUR: $table_output statt $table verwenden
    $table_output->construct_cell('<i class="wi wetter-icon-acp-gross wi-wind ' . htmlspecialchars_uni($windrichtung_db_val) . '" title="' . htmlspecialchars_uni($windrichtung_db_val) . '"></i>', array("class" => "align_center"));
} else {
    // KORREKTUR: $table_output statt $table verwenden
    $table_output->construct_cell('-', array("class" => "align_center"));
}
$table_output->construct_cell(!empty($row['windstaerke']) ? htmlspecialchars($row['windstaerke']) . " km/h" : '-');
        $table_output->construct_row();
    }
} else {
    if ($table_exists) {
        $table_output->construct_cell(sprintf(($lang->wetter_admin_no_archived_data_for_city ?: "Keine archivierten Wetterdaten für %s gefunden."), htmlspecialchars($selected_city_original)), array("colspan" => $num_columns_for_colspan, "class" => "align_center"));
    } else {
        if (!empty($selected_city_original)) {
            $table_output->construct_cell(sprintf(($lang->wetter_admin_archive_table_not_exist ?: "Archivtabelle für %s existiert nicht."), htmlspecialchars($selected_city_original)), array("colspan" => $num_columns_for_colspan, "class" => "align_center"));
        } else {
            $table_output->construct_cell($lang->wetter_admin_select_city_to_view_archive ?: "Bitte wähle eine Stadt, um das Archiv anzuzeigen.", array("colspan" => $num_columns_for_colspan, "class" => "align_center"));
        }
    }
    $table_output->construct_row();
}

$table_title_str = $lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten";
if(!empty($selected_city_original)) {
    $table_title_str .= ": " . htmlspecialchars(ucfirst($selected_city_original));
}
$table_output->output($table_title_str);

// Paginierung ausgeben
if ($total_rows > $items_per_page_acp) {
    $pagination_url = "index.php?module=wetter-archive_view&amp;stadt=" . urlencode($selected_city_original);
    echo draw_admin_pagination($page_num_acp, $items_per_page_acp, $total_rows, $pagination_url);
}

$page->output_footer();
?>
