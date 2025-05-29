<?php
// Ggf. den Pfad anpassen, stelle sicher, dass das Verzeichnis schreibbar ist
ini_set('error_log', MYBB_ROOT . 'logs/php_acp_error.log'); // MYBB_ROOT.'logs/...' ist oft besser

if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Lade die Haupt-Plugin-Datei, um Zugriff auf die Helferfunktionen zu erhalten
// Stelle sicher, dass der Pfad korrekt ist.
if (!function_exists('wetter_info')) { // Prüfe, ob die Plugin-Datei nicht schon geladen wurde
    require_once MYBB_ROOT . "inc/plugins/wetter.php";
}

global $mybb, $db, $lang, $page; // $page für ACP-Funktionen

// Lade deine ZENTRALE Admin-Sprachdatei
if(!isset($lang->wetter_admin_entry_title)) { 
    $lang->load("wetter", false, true); 
}
if (is_object($page) && property_exists($page, 'extra_header')) {
    $css_file_name = 'weather-icons.min.css';
    $css_path = $mybb->settings['bburl']. '/images/wetter/css/'. $css_file_name;
    $page->extra_header.= "\n\t".'<link rel="stylesheet" type="text/css" href="'. htmlspecialchars($css_path). '" />';
}
$page->output_header($lang->wetter_admin_entry_title); // Titel der Seite

// Nav-Tabs
$sub_menu = array();
$sub_menu['overview'] = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry'] = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry', 'active' => true);
$sub_menu['archive'] = array('title' => $lang->wetter_admin_manage_archive_title, 'link' => 'index.php?module=wetter-archive');
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['settings'] = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$sub_menu['cities'] = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$page->output_nav_tabs($sub_menu, 'entry');


// Verarbeitung des Formulars (Speichern)
if ($mybb->request_method == "post" && $mybb->get_input('submit_wetter_entry')) { // Prüfe auf einen spezifischen Submit-Button
    verify_post_check($mybb->get_input('my_post_key'));

    $stadt = $db->escape_string($mybb->get_input('stadt'));
    $datum = $db->escape_string($mybb->get_input('datum')); // Sollte YYYY-MM-DD sein
    $zeitspanne = $db->escape_string($mybb->get_input('zeitspanne'));
    $icon = $db->escape_string($mybb->get_input('icon')); // CSS-Klasse
    $temperatur = $db->escape_string($mybb->get_input('temperatur')); // Ggf. intval() oder floatval()
    $wetterlage = $db->escape_string($mybb->get_input('wetterlage'));
    $sonnenaufgang = $db->escape_string($mybb->get_input('sonnenaufgang'));
    $sonnenuntergang = $db->escape_string($mybb->get_input('sonnenuntergang'));
    $mondphase = $db->escape_string($mybb->get_input('mondphase'));
    $windrichtung = $db->escape_string($mybb->get_input('windrichtung'));
    $windstaerke = $db->escape_string($mybb->get_input('windstaerke'));
    $is_archive_entry = ($mybb->get_input('is_archive', MyBB::INPUT_INT) == 1);


    $errors = array();
    if (empty($stadt)) $errors[] = $lang->wetter_admin_error_no_city;
    if (empty($datum) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $datum)) $errors[] = $lang->wetter_admin_error_invalid_date;
    if (empty($icon) || $icon == 'wi-na') $errors[] = $lang->wetter_admin_error_no_icon_selected;
    // ... weitere Validierungen ...
	if(!empty($sonnenaufgang) && !preg_match("/^(?:[01]\d|2[0-3]):[0-5]\d$/", $sonnenaufgang)) {
        $errors[] = $lang->wetter_admin_error_invalid_time_sunrise; // Neue Sprachvariable! z.B. "Ungültiges Zeitformat für Sonnenaufgang (erwartet HH:MM)."
    }
    if(!empty($sonnenuntergang) && !preg_match("/^(?:[01]\d|2[0-3]):[0-5]\d$/", $sonnenuntergang)) {
        $errors[] = $lang->wetter_admin_error_invalid_time_sunset; // Neue Sprachvariable! z.B. "Ungültiges Zeitformat für Sonnenuntergang (erwartet HH:MM)."
    }
    if (empty($errors)) {
        $city_suffix = wetter_sanitize_city_name_for_table($stadt); // Helferfunktion!
        $table_name = "wetter_" . $city_suffix;
        if ($is_archive_entry) {
            $table_name .= "_archiv";
        }

        $neuer_eintrag = [
            "datum" => $datum,
            "zeitspanne" => $zeitspanne,
            "temperatur" => $temperatur,
            "wetterlage" => $wetterlage,
            "windrichtung" => $windrichtung,
            "windstaerke" => $windstaerke,
            "icon" => $icon,
            "sonnenaufgang" => $sonnenaufgang,
            "sonnenuntergang" => $sonnenuntergang,
            "mondphase" => $mondphase
        ];

        if (!$db->table_exists($table_name)) {
            flash_message($lang->sprintf($lang->wetter_admin_error_table_not_exist_for_city, htmlspecialchars($stadt)), "error"); // Neue Sprachvariable
        } else {
            $db->insert_query($table_name, $neuer_eintrag);
            log_admin_action(array('module' => 'wetter-entry', 'action' => 'add_data', 'city' => $stadt, 'datum' => $datum));
            flash_message($lang->wetter_admin_data_added_success, "success");
            // Redirect zur selben Seite, aber mit der Stadt als GET-Parameter für "sticky" Verhalten
            admin_redirect("index.php?module=wetter-entry&city=" . urlencode($stadt));
        }
    } else {
        $page->output_inline_error($errors);
        // Die eingegebenen Werte bleiben dank $mybb->input['feldname'] im Formular erhalten.
    }
}

// Formular für die Eingabe neuer Wetterdaten
$form = new Form("index.php?module=wetter-entry", "post"); // Action bleibt dieselbe Seite
echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
$form_container = new FormContainer($lang->wetter_admin_entry_details);

// Stadt-Auswahl (dynamisch und sticky)
$cities = wetter_helper_get_cities_array_from_string(); // Deine Funktion aus inc/plugins/wetter.php
$city_options = array();
if (!empty($cities)) {
    foreach($cities as $c) { $city_options[$c] = htmlspecialchars($c); } // htmlspecialchars für die Anzeige
}
// Bestimme die vorausgewählte Stadt:
// 1. Wenn das Formular wegen eines Fehlers neu geladen wird, nimm den alten 'stadt'-Wert.
// 2. Wenn ein 'city'-Parameter in der URL ist (nach erfolgreichem Speichern), nimm diesen.
// 3. Sonst nimm die erste verfügbare Stadt oder nichts.
$selected_stadt_for_form = $mybb->input['stadt'] ?? ($mybb->get_input('city', MyBB::INPUT_STRING) ?: ($cities[0] ?? ''));

if (empty($cities)) {
    $form_container->output_row($lang->wetter_admin_city, "", $lang->wetter_admin_please_add_cities_first); // Sprachvariable!
} else {
    $form_container->output_row($lang->wetter_admin_city." <em>*</em>", "", $form->generate_select_box('stadt', $city_options, $selected_stadt_for_form), 'stadt');
}

// Datum
$current_date_value_raw = $mybb->input['datum'] ?? date("Y-m-d");
$current_date_value = htmlspecialchars($current_date_value_raw); // Escapen für das value-Attribut

// Datum
$datum_html = '<input type="date" name="datum" id="datum" value="' . $current_date_value . '" style="width: auto; padding: 5px;" />';

$form_container->output_row(
    $lang->wetter_admin_date." <em>*</em>",
    $lang->wetter_admin_date_desc,
    $datum_html,
    'datum'
);

// Zeitspanne
$timeslot_options = array("00-06" => "00:00 - 06:00", "06-12" => "06:00 - 12:00", "12-18" => "12:00 - 18:00", "18-24" => "18:00 - 24:00 Uhr");
$selected_zeitspanne = $mybb->input['zeitspanne'] ?? ''; // Oder einen sinnvollen Standard-Key aus $timeslot_options
$form_container->output_row($lang->wetter_admin_timeslot." <em>*</em>", "", $form->generate_select_box('zeitspanne', $timeslot_options, $selected_zeitspanne), 'zeitspanne');

// --- ICON PICKER (in wetter_entry.php) ---
// Stelle sicher, dass $mybb, $form, $form_container und $lang hier verfügbar sind.

// $current_icon_value wird die spezifische Klasse wie 'wi-day-sunny' oder 'wi-na' enthalten.
// 'wi-na' (oder ein anderer Platzhalter aus deinem Icon-Set) ist ein guter Standardwert.
$current_icon_value = $mybb->input['icon']?? 'wi-na';

// Das versteckte Feld, das den Wert der ausgewählten Icon-Klasse speichert.
$icon_picker_html = $form->generate_hidden_field('icon', $current_icon_value, array('id' => 'wetter_icon_class_input'));

// Vorschau-Icon:
// Generiert HTML wie: <i class="wi wi-day-sunny"></i>
// Dies ist KORREKT für deine CSS-Struktur.
$icon_picker_html.= "<div id=\"wetter_icon_preview\" style=\"font-size: 2em; min-width: 30px; max-width:30px; display: inline-block; vertical-align: middle; text-align: center; border: 1px solid #ccc; padding: 5px; line-height:30px;\"><i class=\"wi {$current_icon_value}\"></i></div>";

// Button zum Öffnen des Icon-Picker-Modals
$icon_picker_html.= "<input type=\"button\" id=\"wetter_open_icon_picker_button\" value=\"{$lang->wetter_admin_select_icon_button}\" class=\"button\" style=\"margin-left: 10px;\" />";

// Das Modal-Fenster für den Icon Picker
$icon_picker_html.= "<div id=\"wetter_icon_picker_modal\" style=\"display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10010; text-align:left;\">"; // z-index ggf. anpassen
$icon_picker_html.= "  <div style=\"position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:1px solid #333; border-radius:5px; max-height:80vh; overflow-y:auto; width:80%; max-width:600px;\">";
$icon_picker_html.= "    <h3>{$lang->wetter_admin_select_an_icon}</h3><hr/><div id=\"wetter_icon_list_container\" style=\"display:flex; flex-wrap:wrap; gap:8px; justify-content:center;\">";

// Hole alle verfügbaren Icon-Klassen (z.B. 'wi-day-sunny', 'wi-cloudy', etc.)
// Diese Funktion ist in deiner module_meta.php und liefert die korrekten Klassennamen.
$all_icons = wetter_get_all_icon_classes(); // [1]

foreach ($all_icons as $icon_class_option) { // $icon_class_option ist z.B. 'wi-day-sunny'
    // Generiert HTML für jedes auswählbare Icon im Picker: <i class="wi wi-day-sunny"></i>
    // Dies ist KORREKT für deine CSS-Struktur.
    $icon_picker_html.= "<div class=\"wetter_icon_picker_item\" data-icon-class=\"{$icon_class_option}\" title=\"{$icon_class_option}\" style=\"cursor:pointer; padding:5px; font-size:1.5em; border:1px solid transparent;\" onmouseover=\"this.style.borderColor='#999'\" onmouseout=\"this.style.borderColor='transparent'\"><i class=\"wi {$icon_class_option}\"></i></div>";
}

$icon_picker_html.= "    </div><hr/><input type=\"button\" id=\"wetter_close_icon_picker_button\" value=\"{$lang->wetter_admin_close_picker_button}\" class=\"button\" style=\"margin-top:15px;\" />";
$icon_picker_html.= "  </div></div>"; // Schließende Divs für das Modal

// Füge den gesamten HTML-Code des Icon Pickers zur Formularzeile hinzu.
$form_container->output_row($lang->wetter_admin_icon_class." <em>*</em>", $lang->wetter_admin_icon_class_desc, $icon_picker_html, 'icon');

// --- ENDE ICON PICKER ---



// Temperatur, Wetterlage, Sonnenaufgang, Sonnenuntergang, Mondphase (wie gehabt)
$form_container->output_row($lang->wetter_admin_temperature." <em>*</em>", "", $form->generate_text_box('temperatur', $mybb->input['temperatur'] ?? '', array('id' => 'temperatur')), 'temperatur');
$form_container->output_row($lang->wetter_admin_condition." <em>*</em>", "", $form->generate_text_box('wetterlage', $mybb->input['wetterlage'] ?? '', array('id' => 'wetterlage')), 'wetterlage');
// Sonnenaufgang (HH:MM)
$sonnenaufgang_html = '<input type="time" name="sonnenaufgang" id="sonnenaufgang" value="' . htmlspecialchars($mybb->input['sonnenaufgang'] ?? ($entry_data['sonnenaufgang'] ?? '')) . '" step="60" />';
$form_container->output_row(
    $lang->wetter_admin_sunrise, 
    $lang->wetter_admin_time_desc, // z.B. "Format: HH:MM"
    $sonnenaufgang_html,
    'sonnenaufgang' // Label 'for' Attribut
);
// Sonnenuntergang (HH:MM)
$sonnenuntergang_html = '<input type="time" name="sonnenuntergang" id="sonnenuntergang" value="' . htmlspecialchars($mybb->input['sonnenuntergang'] ?? ($entry_data['sonnenuntergang'] ?? '')) . '" step="60" />';
$form_container->output_row(
    $lang->wetter_admin_sunset, 
    $lang->wetter_admin_time_desc, 
    $sonnenuntergang_html,
    'sonnenuntergang'
);

$form_container->output_row($lang->wetter_admin_moonphase, "", $form->generate_text_box('mondphase', $mybb->input['mondphase'] ?? '', array('id' => 'mondphase')), 'mondphase');

// --- WINDRICHTUNG DROPDOWN ---
$wind_directions = wetter_get_wind_directions(); // Helferfunktion muss hier verfügbar sein
$selected_windrichtung = $mybb->input['windrichtung'] ?? ''; // Standard auf Leerstring oder ersten Wert der $wind_directions
$form_container->output_row($lang->wetter_admin_winddirection, "", $form->generate_select_box('windrichtung', $wind_directions, $selected_windrichtung), 'windrichtung');
// --- ENDE WINDRICHTUNG DROPDOWN ---

// Windstärke
$form_container->output_row($lang->wetter_admin_windspeed, "", $form->generate_text_box('windstaerke', $mybb->input['windstaerke'] ?? '', array('id' => 'windstaerke')), 'windstaerke');

// Checkbox für Archiv
$form_container->output_row($lang->wetter_admin_is_archive, "", $form->generate_yes_no_radio('is_archive', $mybb->input['is_archive'] ?? 0), 'is_archive');

echo $form_container->end();

$buttons = array(); // Array initialisieren
$buttons[] = $form->generate_submit_button($lang->wetter_admin_save_entry, array("name" => "submit_wetter_entry")); // Name für den Submit-Button
echo $form->output_submit_wrapper($buttons);
echo "</form>";

$page->output_footer();
?>
