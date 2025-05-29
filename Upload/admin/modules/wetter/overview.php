<?php
// admin/modules/wetter/overview.php

// Direktzugriff verhindern
if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

global $mybb, $db, $lang, $page;

// Lade deine ZENTRALE Admin-Sprachdatei
if(!isset($lang->wetter_admin_overview_title)) {
    $lang->load("wetter", false, true);
}
if (is_object($page) && property_exists($page, 'extra_header')) {
    $css_file_name = 'weather-icons.min.css';
    $css_path = $mybb->settings['bburl']. '/images/wetter/css/'. $css_file_name;
    $page->extra_header.= "\n\t".'<link rel="stylesheet" type="text/css" href="'. htmlspecialchars($css_path). '" />';
}
// Aktuellen Ansichtstyp bestimmen (aktuell oder archiv)
$view_type = $mybb->get_input('view_type', MyBB::INPUT_STRING);
if ($view_type !== 'archive') {
    $view_type = 'current'; // Standard ist aktuelle Daten
}

// --- Verarbeitung von Löschaktionen (nur für aktuelle Daten relevant) ---
if ($view_type === 'current' && $mybb->get_input('action_type') == 'delete_entry' && $mybb->get_input('id', MyBB::INPUT_INT) > 0 && !empty($mybb->get_input('stadt', MyBB::INPUT_STRING))) {
    verify_post_check($mybb->get_input('my_post_key'));

    $entry_id_to_delete = $mybb->get_input('id', MyBB::INPUT_INT);
    $city_for_delete_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING));
    // Stelle sicher, dass wetter_admin_normalize_city_name_for_table() hier verfügbar ist!
    if (!function_exists('wetter_admin_normalize_city_name_for_table')) {
        // Notfall-Definition, sollte aber global in deinem Plugin sein
        function wetter_admin_normalize_city_name_for_table($city_name) {
            $city_name = strtolower(trim($city_name));
            $city_name = preg_replace('/[^a-z0-9_]+/', '_', $city_name);
            $city_name = trim($city_name, '_');
            if (empty($city_name)) return 'default_city';
            return $city_name;
        }
    }
    $city_for_delete_slug = wetter_admin_normalize_city_name_for_table($city_for_delete_original);
    $prefix = TABLE_PREFIX;
    $table_to_delete_from = "{$prefix}wetter_" . $db->escape_string($city_for_delete_slug);

    if ($db->table_exists(str_replace($prefix, '', $table_to_delete_from))) {
        $db->delete_query(str_replace($prefix, '', $table_to_delete_from), "id = '" . $entry_id_to_delete . "'");
        flash_message(sprintf($lang->wetter_admin_entry_deleted_success ?: "Wettereintrag ID %d aus %s erfolgreich gelöscht.", $entry_id_to_delete, htmlspecialchars($city_for_delete_original)), "success");
    } else {
        flash_message(sprintf($lang->wetter_admin_table_not_exist_for_delete ?: "Tabelle für %s nicht gefunden. Eintrag konnte nicht gelöscht werden.", htmlspecialchars($city_for_delete_original)), "error");
    }
    admin_redirect("index.php?module=wetter-overview&stadt=" . urlencode($city_for_delete_original) . "&view_type=current");
}
// --- Ende Verarbeitung von Löschaktionen ---

$page->output_header($lang->wetter_admin_overview_title ?: "Wetterübersicht");

echo "<h2>" . ($lang->wetter_admin_overview_title ?: "Wetterverwaltung - Übersicht") . "</h2>";
echo "<p>" . ($lang->wetter_admin_overview_desc ?: "Hier kannst du die Wetterdaten für verschiedene Städte verwalten.") . "</p>";

$prefix = TABLE_PREFIX;

// 1. Konfigurierte Städte aus den Plugin-Einstellungen holen
$configured_cities_str = $mybb->settings['wetter_plugin_cities'];
if (empty($configured_cities_str)) {
    $page->output_inline_error($lang->wetter_admin_no_cities_configured_overview ?: "Es sind noch keine Städte in den Plugin-Einstellungen konfiguriert. Bitte füge zuerst Städte über die <a href=\"index.php?module=wetter-cities\">Städteverwaltung</a> hinzu.");
    $page->output_footer();
    exit;
}
$cities_array = array_filter(array_map('trim', explode(",", $configured_cities_str)));
sort($cities_array);

// 2. Aktuell ausgewählte Stadt bestimmen
$stadt_param_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING));
$current_stadt_original = "";

if (!empty($stadt_param_original) && in_array($stadt_param_original, $cities_array)) {
    $current_stadt_original = $stadt_param_original;
} elseif (!empty($cities_array[0])) {
    $current_stadt_original = $cities_array[0];
}

if (empty($current_stadt_original)) {
    $page->output_inline_error($lang->wetter_admin_no_city_selected_overview ?: "Keine Stadt ausgewählt oder konfiguriert.");
    $page->output_footer();
    exit;
}

// 3. Formular zum Umschalten der Stadt
echo '<form method="get" action="index.php" style="margin-bottom: 10px;">
    <input type="hidden" name="module" value="wetter-overview">
    <input type="hidden" name="view_type" value="' . htmlspecialchars($view_type) . '">
    <label for="stadt_select_overview">' . ($lang->wetter_admin_select_city_label ?: "Stadt anzeigen:") . ' </label>
    <select name="stadt" id="stadt_select_overview" onchange="this.form.submit()">';
foreach ($cities_array as $city_name_loop) {
    $selected_attr = ($city_name_loop === $current_stadt_original) ? " selected=\"selected\"" : "";
    echo '<option value="' . htmlspecialchars($city_name_loop) . '"' . $selected_attr . '>' . htmlspecialchars(ucfirst($city_name_loop)) . '</option>';
}
echo '</select>
    <noscript><input type="submit" value="' . ($lang->wetter_admin_show_button ?: "Anzeigen") . '" class="button" /></noscript>
</form>';

// NEU: Auswahl zwischen Aktuell und Archiv für die gewählte Stadt
echo '<div style="margin-bottom: 20px;">
    <strong>' . ($lang->wetter_admin_view_type_label ?: "Daten anzeigen:") . '</strong> &nbsp; ';
if ($view_type === 'current') {
    echo '<strong>' . ($lang->wetter_admin_view_current ?: "Aktuell") . '</strong> | ';
    echo '<a href="index.php?module=wetter-overview&amp;stadt=' . urlencode($current_stadt_original) . '&amp;view_type=archive">' . ($lang->wetter_admin_view_archive ?: "Archiv") . '</a>';
} else { // view_type === 'archive'
    echo '<a href="index.php?module=wetter-overview&amp;stadt=' . urlencode($current_stadt_original) . '&amp;view_type=current">' . ($lang->wetter_admin_view_current ?: "Aktuell") . '</a> | ';
    echo '<strong>' . ($lang->wetter_admin_view_archive ?: "Archiv") . '</strong>';
}
echo '</div>';

// 4. Tabellenname sicher generieren
if (!function_exists('wetter_admin_normalize_city_name_for_table')) {
    function wetter_admin_normalize_city_name_for_table($city_name) {
        $city_name = strtolower(trim($city_name));
        $city_name = preg_replace('/[^a-z0-9_]+/', '_', $city_name);
        $city_name = trim($city_name, '_');
        if (empty($city_name)) return 'default_city';
        return $city_name;
    }
}
$current_stadt_slug = wetter_admin_normalize_city_name_for_table($current_stadt_original);

if ($view_type === 'archive') {
    $tabelle_name_suffix = $db->escape_string($current_stadt_slug) . "_archiv";
} else {
    $tabelle_name_suffix = $db->escape_string($current_stadt_slug);
}
$tabelle_name = "{$prefix}wetter_" . $tabelle_name_suffix;
$table_exists = $db->table_exists(str_replace($prefix, '', $tabelle_name));

if ($view_type === 'current') {
    $add_entry_link = "index.php?module=wetter-entry&amp;action=add_entry&amp;stadt=" . urlencode($current_stadt_original);
    echo "<div style='margin-bottom:15px;'><a href='{$add_entry_link}' class='button'>" . (sprintf($lang->wetter_admin_add_entry_for_city_button, htmlspecialchars($current_stadt_original))) . "</a></div>";
}

if (!$table_exists) {
    $page->output_inline_error(sprintf(($lang->wetter_admin_table_not_exist_overview_detailed ?: "Hinweis: Die Tabelle <strong>%s</strong> für die Stadt <strong>%s</strong> (%s) existiert noch nicht. Es können keine Daten angezeigt werden."), htmlspecialchars($tabelle_name), htmlspecialchars($current_stadt_original), ($view_type === 'archive' ? ($lang->wetter_admin_archive_view_short ?: 'Archiv') : ($lang->wetter_admin_current_view_short ?: 'Aktuell') ) ));
    $query = false;
} else {
    $query = $db->query("
        SELECT * FROM `{$tabelle_name}`
        ORDER BY datum ASC, zeitspanne ASC
    ");
}

$table = new Table;
$num_columns = 10; // Deine 10 Standard-Daten-Spalten
if ($view_type === 'current') {
    $num_columns = 11; // + Aktionen
}
$colspan = $num_columns;

// Definiere deine 10 Spalten hier
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
        $table->construct_cell(htmlspecialchars($row['datum']));
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
            $edit_link = "index.php?module=wetter-edit&amp;id={$entry_id}&amp;stadt=". urlencode($current_stadt_original);
            $delete_link = "index.php?module=wetter-overview&amp;action_type=delete_entry&amp;id={$entry_id}&amp;stadt=" . urlencode($current_stadt_original) . "&amp;my_post_key=" . $mybb->post_key . "&amp;view_type=current";
            $actions_html = "<a href=\"{$edit_link}\">" . ($lang->wetter_admin_edit_link ?: "Bearbeiten") . "</a>";
            $actions_html .= " | <a href=\"{$delete_link}\" onclick=\"return AdminCP.deleteConfirmation(this, '" . ($lang->wetter_admin_delete_confirm_entry ?: "Diesen Wettereintrag wirklich löschen?") . "');\">" . ($lang->wetter_admin_delete_link ?: "Löschen") . "</a>";
            $table->construct_cell($actions_html, array("class" => "align_center"));
        }
        $table->construct_row();
    }
} else {
    if ($table_exists) {
        $table->construct_cell(sprintf(($lang->wetter_admin_no_data_for_city_table ?: "Keine Wetterdaten für %s gefunden."), htmlspecialchars($current_stadt_original)), array("colspan" => $colspan, "class" => "align_center"));
    } else {
        $table->construct_cell(sprintf(($lang->wetter_admin_table_creation_hint_in_table_detailed ?: "Tabelle für %s (%s) nicht vorhanden oder leer."), htmlspecialchars($current_stadt_original), ($view_type === 'archive' ? ($lang->wetter_admin_archive_view_short ?: 'Archiv') : ($lang->wetter_admin_current_view_short ?: 'Aktuell') ) ), array("colspan" => $colspan, "class" => "align_center"));
    }
    $table->construct_row();
}

$view_type_lang_string = ($view_type === 'archive' ? ($lang->wetter_admin_archive_view_short ?: 'Archiv') : ($lang->wetter_admin_current_view_short ?: 'Aktuell'));
$table_title = sprintf(($lang->wetter_admin_weather_data_for_city_title_detailed ?: "Wetterdaten für: %s (%s)"), htmlspecialchars(ucfirst($current_stadt_original)), $view_type_lang_string);
$table->output($table_title);

$page->output_footer();
?>