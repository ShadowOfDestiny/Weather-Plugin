<?php
// admin/modules/wetter/overview.php

if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

global $mybb, $db, $lang, $page;

if(!isset($lang->wetter_admin_overview_title)) {
    $lang->load("wetter", false, true);
}
if (is_object($page) && property_exists($page, 'extra_header')) {
    $css_file_name = 'weather-icons.min.css';
    $css_path = $mybb->settings['bburl']. '/images/wetter/css/'. $css_file_name;
    $page->extra_header.= "\n\t".'<link rel="stylesheet" type="text/css" href="'. htmlspecialchars($css_path). '" />';
}

$view_type = $mybb->get_input('view_type', MyBB::INPUT_STRING);
if ($view_type !== 'archive') {
    $view_type = 'current';
}

// Paginierungsparameter ACP
$page_num_acp = $mybb->get_input('page', MyBB::INPUT_INT);
if($page_num_acp <= 0) {
    $page_num_acp = 1;
}
$items_per_page_acp = (int)($mybb->settings['wetter_plugin_items_per_page_acp'] ?? 20);


if ($view_type === 'current' && $mybb->get_input('action_type') == 'delete_entry' && $mybb->get_input('id', MyBB::INPUT_INT) > 0 && !empty($mybb->get_input('stadt', MyBB::INPUT_STRING))) {
    verify_post_check($mybb->get_input('my_post_key'));

    $entry_id_to_delete = $mybb->get_input('id', MyBB::INPUT_INT);
    $city_for_delete_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING));
    
    // Stelle sicher, dass wetter_sanitize_city_name_for_table verfügbar ist (aus Hauptplugin-Datei)
    if (!function_exists('wetter_sanitize_city_name_for_table')) {
        // Lade Haupt-Plugin-Datei, falls nicht schon geschehen (sollte durch Hooks eigentlich der Fall sein)
        if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
            require_once MYBB_ROOT . "inc/plugins/wetter.php";
        } else {
             flash_message("Fehler: Haupt-Plugin-Datei nicht gefunden.", "error");
             admin_redirect("index.php?module=wetter-overview");
        }
    }
    $city_for_delete_slug = wetter_sanitize_city_name_for_table($city_for_delete_original); // Verwende die globale Funktion
    $prefix = TABLE_PREFIX;
    $table_to_delete_from_no_prefix = "wetter_" . $db->escape_string($city_for_delete_slug);
    // Für 'current' view, gibt es kein _archiv Suffix
    
    if ($db->table_exists($table_to_delete_from_no_prefix)) {
        $db->delete_query($table_to_delete_from_no_prefix, "id = '" . $entry_id_to_delete . "'");
        flash_message(sprintf($lang->wetter_admin_entry_deleted_success ?: "Wettereintrag ID %d aus %s erfolgreich gelöscht.", $entry_id_to_delete, htmlspecialchars($city_for_delete_original)), "success");
    } else {
        flash_message(sprintf($lang->wetter_admin_table_not_exist_for_delete ?: "Tabelle für %s nicht gefunden. Eintrag konnte nicht gelöscht werden.", htmlspecialchars($city_for_delete_original)), "error");
    }
    admin_redirect("index.php?module=wetter-overview&stadt=" . urlencode($city_for_delete_original) . "&view_type=current&page=" . $page_num_acp); // page beibehalten
}

$page->output_header($lang->wetter_admin_overview_title ?: "Wetterübersicht");

// Nav-Tabs
$sub_menu = array();
$sub_menu['overview']     = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview', 'active' => true);
$sub_menu['entry']        = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry');
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['cities']       = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$sub_menu['settings']     = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$page->output_nav_tabs($sub_menu, 'overview');


echo "<h2>" . ($lang->wetter_admin_overview_title ?: "Wetterverwaltung - Übersicht") . "</h2>";
echo "<p>" . ($lang->wetter_admin_overview_desc ?: "Hier kannst du die Wetterdaten für verschiedene Städte verwalten.") . "</p>";

$prefix = TABLE_PREFIX;

$configured_cities_str = $mybb->settings['wetter_plugin_cities'];
if (empty($configured_cities_str)) {
    $page->output_inline_error($lang->wetter_admin_no_cities_configured_overview);
    $page->output_footer();
    exit;
}
// Verwende Helferfunktion aus wetter.php
if (!function_exists('wetter_helper_get_cities_array_from_string')) {
    flash_message("Fehler: Helferfunktion nicht gefunden.", "error");
    admin_redirect("index.php?module=config-plugins"); // Zurück zur Plugin-Liste
}
$cities_array = wetter_helper_get_cities_array_from_string($configured_cities_str);
sort($cities_array);

$stadt_param_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING));
$current_stadt_original = "";

if (!empty($stadt_param_original) && in_array($stadt_param_original, $cities_array)) {
    $current_stadt_original = $stadt_param_original;
} elseif (!empty($cities_array[0])) {
    $current_stadt_original = $cities_array[0];
}

if (empty($current_stadt_original)) {
    $page->output_inline_error($lang->wetter_admin_no_city_selected_overview);
    $page->output_footer();
    exit;
}

echo '<form method="get" action="index.php" style="margin-bottom: 10px;">
    <input type="hidden" name="module" value="wetter-overview">
    <input type="hidden" name="view_type" value="' . htmlspecialchars($view_type) . '">
    <label for="stadt_select_overview">' . ($lang->wetter_admin_select_city_label ?: "Stadt anzeigen:") . ' </label>
    <select name="stadt" id="stadt_select_overview" onchange="this.form.submit()">'; // Bei Wechsel auf Seite 1 zurücksetzen
foreach ($cities_array as $city_name_loop) {
    $selected_attr = ($city_name_loop === $current_stadt_original) ? " selected=\"selected\"" : "";
    echo '<option value="' . htmlspecialchars($city_name_loop) . '"' . $selected_attr . '>' . htmlspecialchars(ucfirst($city_name_loop)) . '</option>';
}
echo '</select>
    <noscript><input type="submit" value="' . ($lang->wetter_admin_show_button ?: "Anzeigen") . '" class="button" /></noscript>
</form>';

echo '<div style="margin-bottom: 20px;">
    <strong>' . ($lang->wetter_admin_view_type_label ?: "Daten anzeigen:") . '</strong> &nbsp; ';
$base_url_for_view_toggle = "index.php?module=wetter-overview&amp;stadt=" . urlencode($current_stadt_original); // page wird bei Wechsel zurückgesetzt
if ($view_type === 'current') {
    echo '<strong>' . ($lang->wetter_admin_view_current ?: "Aktuell") . '</strong> | ';
    echo '<a href="'.$base_url_for_view_toggle.'&amp;view_type=archive">' . ($lang->wetter_admin_view_archive ?: "Archiv") . '</a>';
} else {
    echo '<a href="'.$base_url_for_view_toggle.'&amp;view_type=current">' . ($lang->wetter_admin_view_current ?: "Aktuell") . '</a> | ';
    echo '<strong>' . ($lang->wetter_admin_view_archive ?: "Archiv") . '</strong>';
}
echo '</div>';

// Verwende Helferfunktion aus wetter.php
if (!function_exists('wetter_sanitize_city_name_for_table')) {
     flash_message("Fehler: Helferfunktion sanitize nicht gefunden.", "error");
     admin_redirect("index.php?module=config-plugins");
}
$current_stadt_slug = wetter_sanitize_city_name_for_table($current_stadt_original);
$tabelle_name_no_prefix = "wetter_" . $db->escape_string($current_stadt_slug);
if ($view_type === 'archive') {
    $tabelle_name_no_prefix .= "_archiv";
}
$tabelle_name_full = $prefix . $tabelle_name_no_prefix; // Für $db->table_exists() ohne Prefix
$table_exists = $db->table_exists($tabelle_name_no_prefix);

if ($view_type === 'current') {
    $add_entry_link = "index.php?module=wetter-entry&amp;action=add_entry&amp;stadt=" . urlencode($current_stadt_original);
    echo "<div style='margin-bottom:15px;'><a href='{$add_entry_link}' class='button'>" . (sprintf($lang->wetter_admin_add_entry_for_city_button, htmlspecialchars($current_stadt_original))) . "</a></div>";
}

$total_rows = 0;
$query = false;

if ($table_exists) {
    // Gesamtzahl der Zeilen für Paginierung ermitteln
    $count_query = $db->simple_select($tabelle_name_no_prefix, "COUNT(*) as total");
    $total_rows = (int)$db->fetch_field($count_query, "total");

    if ($total_rows > 0) {
        $offset = ($page_num_acp - 1) * $items_per_page_acp;
        $query = $db->query("
            SELECT * FROM `{$tabelle_name_full}`
            ORDER BY datum ASC, zeitspanne ASC
            LIMIT {$items_per_page_acp} OFFSET {$offset}
        ");
    }
} else {
     $page->output_inline_error(sprintf(($lang->wetter_admin_table_not_exist_overview_detailed ?: "Hinweis: Die Tabelle <strong>%s</strong> für die Stadt <strong>%s</strong> (%s) existiert noch nicht."), htmlspecialchars($tabelle_name_full), htmlspecialchars($current_stadt_original), ($view_type === 'archive' ? ($lang->wetter_admin_archive_view_short ?: 'Archiv') : ($lang->wetter_admin_current_view_short ?: 'Aktuell') ) ));
}


$table = new Table;
$num_columns = 10;
if ($view_type === 'current') {
    $num_columns = 11;
}
$colspan = $num_columns;

$table->construct_header($lang->wetter_admin_date ?: "Datum");
$table->construct_header($lang->wetter_admin_timeslot ?: "Zeitspanne");
$table->construct_header($lang->wetter_admin_icon ?: "Icon", array("class" => "align_center"));
$table->construct_header($lang->wetter_admin_temperature ?: "Temp.");
$table->construct_header($lang->wetter_admin_weathercondition ?: "Wetterlage");
$table->construct_header($lang->wetter_admin_sunrise ?: "Sonnenaufgang");
$table->construct_header($lang->wetter_admin_sunset ?: "Sonnenuntergang");
$table->construct_header($lang->wetter_admin_moonphase ?: "Mondphase");
$table->construct_header($lang->wetter_admin_winddirection ?: "Windr.");
$table->construct_header($lang->wetter_admin_windspeed ?: "Windst.");

if ($view_type === 'current') {
    $table->construct_header($lang->wetter_admin_actions ?: "Aktionen", array("class" => "align_center", "width" => "150px"));
}

if ($query && $db->num_rows($query) > 0) {
    while ($row = $db->fetch_array($query)) {
        $formatiertes_datum = htmlspecialchars_uni(my_date($mybb->settings['dateformat'], strtotime($row['datum']))); // ACP Datumsformat
        $table->construct_cell($formatiertes_datum);
        $table->construct_cell(htmlspecialchars($row['zeitspanne']));
        $icon_klasse_aus_db = htmlspecialchars($row['icon']);
        if (!empty($icon_klasse_aus_db) && $icon_klasse_aus_db !== 'wi-na') {
            $table->construct_cell('<span class="wetter-icon-display" title="' . $icon_klasse_aus_db . '"><i class="wi ' . $icon_klasse_aus_db . '"></i></span>', array("class" => "align_center"));
        } else {
            $table->construct_cell('-', array("class" => "align_center"));
        }
        $table->construct_cell(htmlspecialchars($row['temperatur']) . "°C");
        $table->construct_cell(htmlspecialchars($row['wetterlage']));
        $table->construct_cell(htmlspecialchars($row['sonnenaufgang']));
        $table->construct_cell(htmlspecialchars($row['sonnenuntergang']));
        $table->construct_cell(htmlspecialchars($row['mondphase']));
        $table->construct_cell(htmlspecialchars($row['windrichtung']));
        $table->construct_cell(htmlspecialchars($row['windstaerke']) . " km/h");

        if ($view_type === 'current') {
            $entry_id = (int)$row['id'];
            $edit_link = "index.php?module=wetter-edit&amp;id={$entry_id}&amp;stadt=". urlencode($current_stadt_original) . "&amp;view_type=current&amp;page=" . $page_num_acp;
            $delete_link = "index.php?module=wetter-overview&amp;action_type=delete_entry&amp;id={$entry_id}&amp;stadt=" . urlencode($current_stadt_original) . "&amp;my_post_key=" . $mybb->post_key . "&amp;view_type=current&amp;page=" . $page_num_acp;
            $actions_html = "<a href=\"{$edit_link}\">" . ($lang->wetter_admin_edit_link ?: "Bearbeiten") . "</a>";
            $actions_html .= " | <a href=\"{$delete_link}\" onclick=\"return AdminCP.deleteConfirmation(this, '" . ($lang->wetter_admin_delete_confirm_entry ?: "Diesen Wettereintrag wirklich löschen?") . "');\">" . ($lang->wetter_admin_delete_link ?: "Löschen") . "</a>";
            $table->construct_cell($actions_html, array("class" => "align_center"));
        }
        $table->construct_row();
    }
} else {
    if ($table_exists) { // Tabelle existiert, aber keine Daten (oder keine für diese Seite)
        $table->construct_cell(sprintf(($lang->wetter_admin_no_data_for_city_table ?: "Keine Wetterdaten für %s gefunden."), htmlspecialchars($current_stadt_original)), array("colspan" => $colspan, "class" => "align_center"));
    } else { // Tabelle existiert nicht
         // Fehlermeldung wurde bereits oben ausgegeben
         $table->construct_cell(sprintf(($lang->wetter_admin_table_creation_hint_in_table_detailed ?: "Tabelle für %s (%s) nicht vorhanden oder leer."), htmlspecialchars($current_stadt_original), ($view_type === 'archive' ? ($lang->wetter_admin_archive_view_short ?: 'Archiv') : ($lang->wetter_admin_current_view_short ?: 'Aktuell') ) ), array("colspan" => $colspan, "class" => "align_center"));
    }
    $table->construct_row();
}

$view_type_lang_string = ($view_type === 'archive' ? ($lang->wetter_admin_archive_view_short ?: 'Archiv') : ($lang->wetter_admin_current_view_short ?: 'Aktuell'));
$table_title = sprintf(($lang->wetter_admin_weather_data_for_city_title_detailed ?: "Wetterdaten für: %s (%s)"), htmlspecialchars(ucfirst($current_stadt_original)), $view_type_lang_string);
$table->output($table_title);

// Paginierung ausgeben
if ($total_rows > $items_per_page_acp) {
    $pagination_url = "index.php?module=wetter-overview&amp;stadt=" . urlencode($current_stadt_original) . "&amp;view_type=" . $view_type;
    echo draw_admin_pagination($page_num_acp, $items_per_page_acp, $total_rows, $pagination_url);
}


$page->output_footer();
?>
