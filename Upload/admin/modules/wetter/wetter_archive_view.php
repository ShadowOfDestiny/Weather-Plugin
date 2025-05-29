<?php
// admin/modules/wetter/wetter_archive_view.php

// Direktzugriff verhindern
if(!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

global $mybb, $db, $lang, $page;

// Lade deine ZENTRALE Admin-Sprachdatei (falls noch nicht geschehen oder für spezifische Strings hier)
if(!isset($lang->wetter_admin_view_archive_title)) { // Prüfe auf eine spezifische Variable
    $lang->load("wetter", false, true);
}

// Lade die Haupt-Plugin-Datei, um Zugriff auf die Helferfunktionen zu erhalten
if (!function_exists('wetter_sanitize_city_name_for_table')) {
    if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT . "inc/plugins/wetter.php";
    } else {
        $page->output_header($lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten");
        $page->output_inline_error("Fehler: Haupt-Plugin-Datei (wetter.php) nicht gefunden. Helferfunktionen sind nicht verfügbar.");
        $page->output_footer();
        exit;
    }
}

$page->output_header($lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten");

// Nav-Tabs (konsistent mit anderen ACP-Modulen deines Plugins)
$sub_menu = array();
$sub_menu['overview']     = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry']        = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry');
$sub_menu['archive']      = array('title' => $lang->wetter_admin_manage_archive_title, 'link' => 'index.php?module=wetter-archive');
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view', 'active' => true);
$sub_menu['settings']     = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$sub_menu['cities']       = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$page->output_nav_tabs($sub_menu, 'archive_view');


echo "<h2>" . ($lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten") . "</h2>";
echo "<p>" . ($lang->wetter_admin_view_archive_desc ?: "Hier kannst du die archivierten Wetterdaten für die ausgewählte Stadt einsehen.") . "</p>"; // Neue Sprachvariable

// Tabellen-Präfix
$prefix = TABLE_PREFIX;

// 1. Konfigurierte Städte aus den Plugin-Einstellungen holen
$configured_cities_str = $mybb->settings['wetter_plugin_cities'] ?? '';
$cities_array = wetter_helper_get_cities_array_from_string($configured_cities_str); // Deine Helferfunktion

if (empty($cities_array)) {
    $page->output_inline_error($lang->wetter_admin_no_cities_configured_overview ?: "Es sind noch keine Städte in den Plugin-Einstellungen konfiguriert. Bitte füge zuerst Städte über die <a href=\"index.php?module=wetter-cities\">Städteverwaltung</a> hinzu.");
    $page->output_footer();
    exit;
}
sort($cities_array); // Optional: Städte im Dropdown sortieren

// 2. Aktuell ausgewählte Stadt bestimmen (Parametername 'arch_stadt' beibehalten oder auf 'stadt' vereinheitlichen)
$selected_city_original = trim($mybb->get_input('arch_stadt', MyBB::INPUT_STRING));
if (empty($selected_city_original) || !in_array($selected_city_original, $cities_array)) {
    $selected_city_original = $cities_array[0]; // Erste konfigurierte Stadt als Standard
}

// Formular zur Auswahl der Stadt:
echo '<form method="get" action="index.php" style="margin-bottom:15px;">
      <input type="hidden" name="module" value="wetter-archive_view">
      <label for="arch_stadt_select">'.($lang->wetter_admin_select_city_label ?: "Stadt wählen:").'</label>
      <select name="arch_stadt" id="arch_stadt_select" onchange="this.form.submit()">';

foreach ($cities_array as $city_loop) {
    $selected_attr = ($city_loop === $selected_city_original) ? " selected=\"selected\"" : "";
    echo '<option value="' . htmlspecialchars($city_loop) . '"' . $selected_attr . '>' . htmlspecialchars(ucfirst($city_loop)) . '</option>';
}
echo '</select>
      <noscript><input type="submit" value="'.($lang->wetter_admin_show_button ?: "Anzeigen").'" class="button"></noscript>
      </form>';


// 3. Tabellenname dynamisch generieren und prüfen
$table_name_to_query = "";
$table_exists = false;

if (!empty($selected_city_original)) {
    $city_suffix_sanitized = wetter_sanitize_city_name_for_table($selected_city_original); // Deine Helferfunktion
    if (!empty($city_suffix_sanitized)) {
        $table_name_no_prefix = "wetter_" . $db->escape_string($city_suffix_sanitized) . "_archiv"; // Stelle sicher, dass "_archiv" angehängt wird
        $table_name_to_query = $prefix . $table_name_no_prefix;
        $table_exists = $db->table_exists($table_name_no_prefix);
    }
}

// Tabelle für die Ausgabe der Wetterdaten
$table_output = new Table; // MyBB Table Class für ACP
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

$num_columns_for_colspan = 10; // Anzahl der Spalten

if ($table_exists && !empty($table_name_to_query)) {
    // 4. Abrufen der archivierten Daten
    $query = $db->query("
        SELECT * FROM `{$table_name_to_query}`
        ORDER BY datum DESC, zeitspanne ASC
    ");

    if ($db->num_rows($query) > 0) {
        while ($row = $db->fetch_array($query)) {
            $table_output->construct_cell(htmlspecialchars($row['datum']));
            $table_output->construct_cell(htmlspecialchars($row['zeitspanne']));
            
            // Icon-Anzeige (wie in overview.php)
            $icon_klasse_aus_db = htmlspecialchars($row['icon']);
            if (!empty($icon_klasse_aus_db) && $icon_klasse_aus_db !== 'wi-na') {
                // Stelle sicher, dass die weather-icons.min.css hier geladen wird (siehe wetter.txt für ACP CSS Hooks)
                $table_output->construct_cell('<span class="wetter-icon-display" title="' . $icon_klasse_aus_db . '"><i class="wi ' . $icon_klasse_aus_db . '"></i></span>', array("class" => "align_center"));
            } else {
                $table_output->construct_cell('-', array("class" => "align_center"));
            }
            
            $table_output->construct_cell(htmlspecialchars($row['temperatur']) . "°C");
            $table_output->construct_cell(htmlspecialchars($row['wetterlage']));
            $table_output->construct_cell(!empty($row['sonnenaufgang']) ? htmlspecialchars($row['sonnenaufgang']) : '-');
            $table_output->construct_cell(!empty($row['sonnenuntergang']) ? htmlspecialchars($row['sonnenuntergang']) : '-');
            $table_output->construct_cell(!empty($row['mondphase']) ? htmlspecialchars($row['mondphase']) : '-');
            $table_output->construct_cell(!empty($row['windrichtung']) ? htmlspecialchars($row['windrichtung']) : '-');
            $table_output->construct_cell(!empty($row['windstaerke']) ? htmlspecialchars($row['windstaerke']) . " km/h" : '-');
            $table_output->construct_row();
        }
    } else {
        $table_output->construct_cell(sprintf(($lang->wetter_admin_no_archived_data_for_city ?: "Keine archivierten Wetterdaten für %s gefunden."), htmlspecialchars($selected_city_original)), array("colspan" => $num_columns_for_colspan, "class" => "align_center"));
        $table_output->construct_row();
    }
} else {
    if (!empty($selected_city_original)) {
        $table_output->construct_cell(sprintf(($lang->wetter_admin_archive_table_not_exist ?: "Archivtabelle für %s existiert nicht."), htmlspecialchars($selected_city_original)), array("colspan" => $num_columns_for_colspan, "class" => "align_center"));
    } else {
        $table_output->construct_cell($lang->wetter_admin_select_city_to_view_archive ?: "Bitte wähle eine Stadt, um das Archiv anzuzeigen.", array("colspan" => $num_columns_for_colspan, "class" => "align_center"));
    }
    $table_output->construct_row();
}

$table_title = $lang->wetter_admin_view_archive_title ?: "Archivierte Wetterdaten";
if(!empty($selected_city_original)) {
    $table_title .= ": " . htmlspecialchars(ucfirst($selected_city_original));
}
$table_output->output($table_title);

$page->output_footer();
?>