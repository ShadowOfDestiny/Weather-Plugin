<?php
// admin/modules/wetter/wetter_edit.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
// Ggf. den Pfad anpassen, stelle sicher, dass das Verzeichnis schreibbar ist
if (!defined("IN_MYBB") ||!defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Include main plugin file for helper functions
if (!function_exists('wetter_sanitize_city_name_for_table')) {
    if (file_exists(MYBB_ROOT. "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT. "inc/plugins/wetter.php";
    } else {
        // Fallback or error if wetter.php is critical and not found
        die("Wichtige Plugin-Datei wetter.php nicht gefunden.");
    }
}
if (is_object($page) && property_exists($page, 'extra_header')) {
    $css_file_name = 'weather-icons.min.css';
    $css_path = $mybb->settings['bburl']. '/images/wetter/css/'. $css_file_name;
    $page->extra_header.= "\n\t".'<link rel="stylesheet" type="text/css" href="'. htmlspecialchars($css_path). '" />';
}
global $mybb, $db, $lang, $page;

// Load language file
if (!isset($lang->wetter_admin_edit_entry_title)) {
    $lang->load("wetter", false, true);
}
if (is_object($page) && property_exists($page, 'extra_header')) {
    $css_file_name = 'weather-icons.min.css';
    $css_path = $mybb->settings['bburl']. '/images/wetter/css/'. $css_file_name;
    $page->extra_header.= "\n\t".'<link rel="stylesheet" type="text/css" href="'. htmlspecialchars($css_path). '" />';
}
$page->output_header($lang->wetter_admin_edit_entry_title);

// Navigation Tabs (konsistent mit wetter_entry.php)
// Diese $sub_menu Definition sollte idealerweise aus module_meta.php stammen oder hier konsistent definiert werden.
// Für dieses Beispiel wird eine vereinfachte Version angenommen.
$_sub_menu = array();
$_sub_menu['10'] = array("id" => "overview", "title" => $lang->wetter_admin_overview_title, "link" => "index.php?module=wetter-overview");
$_sub_menu['30'] = array("id" => "entry", "title" => $lang->wetter_admin_entry_title, "link" => "index.php?module=wetter-entry");
// Weitere Menüpunkte wie cities, archive etc. hier einfügen, falls im Original vorhanden
// $_sub_menu['20'] = array( "id" => "cities", "title" => $lang->wetter_admin_manage_cities_title, "link" => "index.php?module=wetter-cities" );
//...
$page->output_nav_tabs($_sub_menu, 'entry'); // Markiert den "Eintrag" Tab als aktiv, auch für "edit"

// Parameter abrufen
$entry_id = $mybb->get_input('id', MyBB::INPUT_INT);
$stadt_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING));

if ($entry_id <= 0 || empty($stadt_original)) {
    flash_message($lang->wetter_admin_error_invalid_edit_params, "error");
    admin_redirect("index.php?module=wetter-overview");
    exit;
}

// Tabellenname generieren und Existenz prüfen
$city_suffix = wetter_sanitize_city_name_for_table($stadt_original);
if (empty($city_suffix)) {
    flash_message($lang->wetter_admin_error_city_name_invalid_chars?: "Der Stadtname ist nach der Bereinigung ungültig oder leer.", "error");
    admin_redirect("index.php?module=wetter-overview");
    exit;
}
$table_name_no_prefix = "wetter_". $db->escape_string($city_suffix);

if (!$db->table_exists($table_name_no_prefix)) {
    flash_message(sprintf($lang->wetter_admin_error_table_not_exist_for_city, htmlspecialchars_uni($stadt_original)), "error");
    admin_redirect("index.php?module=wetter-overview");
    exit;
}

// Bestehende Daten laden
$query = $db->simple_select($table_name_no_prefix, "*", "id = '". (int)$entry_id. "'", array("limit" => 1));
$entry_data = $db->fetch_array($query);

if (!$entry_data) {
    flash_message($lang->wetter_admin_error_entry_not_found, "error");
    admin_redirect("index.php?module=wetter-overview&stadt=". urlencode($stadt_original));
    exit;
}

// Formularverarbeitung bei POST-Request
if ($mybb->request_method == "post" && $mybb->get_input('submit_wetter_entry')) {
    verify_post_check($mybb->get_input('my_post_key'));

    $updated_data = array(
        "datum" => $db->escape_string($mybb->get_input('datum')),
        "zeitspanne" => $db->escape_string($mybb->get_input('zeitspanne')),
        "icon" => $db->escape_string($mybb->get_input('icon')),
        "temperatur" => $db->escape_string($mybb->get_input('temperatur')),
        "wetterlage" => $db->escape_string($mybb->get_input('wetterlage')),
        "sonnenaufgang" => $db->escape_string($mybb->get_input('sonnenaufgang')),
        "sonnenuntergang" => $db->escape_string($mybb->get_input('sonnenuntergang')),
        "mondphase" => $db->escape_string($mybb->get_input('mondphase')),
        "windrichtung" => $db->escape_string($mybb->get_input('windrichtung')),
        "windstaerke" => $db->escape_string($mybb->get_input('windstaerke'))
    );

    // Validierung
    $errors = array();
    if (empty($updated_data['datum']) ||!preg_match("/^\d{4}-\d{2}-\d{2}$/", $updated_data['datum'])) {
        $errors = $lang->wetter_admin_error_invalid_date;
    }
    if (empty($updated_data['icon']) || $updated_data['icon'] == 'wi-na') {
        $errors[] = $lang->wetter_admin_error_no_icon_selected;
    }
   if(!empty($sonnenaufgang) && !preg_match("/^(?:[01]\d|2[0-3]):[0-5]\d$/", $sonnenaufgang)) {
        $errors[] = $lang->wetter_admin_error_invalid_time_sunrise; // Neue Sprachvariable! z.B. "Ungültiges Zeitformat für Sonnenaufgang (erwartet HH:MM)."
    }
    if(!empty($sonnenuntergang) && !preg_match("/^(?:[01]\d|2[0-3]):[0-5]\d$/", $sonnenuntergang)) {
        $errors[] = $lang->wetter_admin_error_invalid_time_sunset; // Neue Sprachvariable! z.B. "Ungültiges Zeitformat für Sonnenuntergang (erwartet HH:MM)."
    }
    if (!is_numeric(str_replace(',', '.', $updated_data['temperatur']))) {
         // $errors = $lang->wetter_admin_error_invalid_temperature; // Neue Sprachvariable nötig
    } else {
        $updated_data['temperatur'] = str_replace(',', '.', $updated_data['temperatur']); // DB-freundlich
    }
    // Windstärke sollte numerisch sein
    if (!empty($updated_data['windstaerke']) &&!is_numeric($updated_data['windstaerke'])) {
        // $errors = $lang->wetter_admin_error_invalid_windspeed; // Neue Sprachvariable nötig
    }


    if (empty($errors)) {
        $db->update_query($table_name_no_prefix, $updated_data, "id = '". (int)$entry_id. "'");
        log_admin_action(array('module' => 'wetter-edit', 'action' => 'update_data', 'entry_id' => $entry_id, 'city' => $stadt_original));
        flash_message($lang->wetter_admin_entry_updated_success, "success");
        admin_redirect("index.php?module=wetter-overview&stadt=". urlencode($stadt_original));
    } else {
        // Bei Fehlern bleiben die $mybb->input Werte in den Feldern, $entry_data wird für die nicht geänderten Felder verwendet
        $page->output_inline_error($errors);
    }
}

// Formular anzeigen
$form = new Form("index.php?module=wetter-edit&amp;id={$entry_id}&amp;stadt=". urlencode($stadt_original), "post", "edit_entry_form");
echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

$form_container = new FormContainer($lang->wetter_admin_edit_entry_details);

// Stadt (als Info)
$form_container->output_row($lang->wetter_admin_city, "", htmlspecialchars_uni(ucfirst($stadt_original)));

// Datum
$current_date_value =!empty($mybb->input['datum'])? htmlspecialchars_uni($mybb->input['datum']) : htmlspecialchars_uni($entry_data['datum']);
$datum_html = '<input type="date" name="datum" id="datum" value="'. $current_date_value. '" style="width: auto; padding: 5px;" />';
$form_container->output_row($lang->wetter_admin_date." <em>*</em>", $lang->wetter_admin_date_desc, $datum_html, 'datum');

// Zeitspanne
$timeslot_options = array("00-06" => "00:00 - 06:00", "06-12" => "06:00 - 12:00", "12-18" => "12:00 - 18:00", "18-24" => "18:00 - 24:00 Uhr");
$selected_timeslot =!empty($mybb->input['zeitspanne'])? $mybb->input['zeitspanne'] : $entry_data['zeitspanne'];
$form_container->output_row($lang->wetter_admin_timeslot." <em>*</em>", "", $form->generate_select_box('zeitspanne', $timeslot_options, $selected_timeslot), 'zeitspanne');

// Icon Picker
$current_icon_value =!empty($mybb->input['icon'])? htmlspecialchars_uni($mybb->input['icon']) : htmlspecialchars_uni($entry_data['icon']);
$icon_picker_html = $form->generate_hidden_field('icon', $current_icon_value, array('id' => 'wetter_icon_class_input'));
$icon_picker_html.= "<div id=\"wetter_icon_preview\" style=\"font-size: 2em; min-width: 30px; max-width:30px; display: inline-block; vertical-align: middle; text-align: center; border: 1px solid #ccc; padding: 5px; line-height:30px;\"><i class=\"wi ". $current_icon_value. "\"></i></div>";
$icon_picker_html.= "<input type=\"button\" id=\"wetter_open_icon_picker_button\" value=\"{$lang->wetter_admin_select_icon_button}\" class=\"button\" style=\"margin-left: 10px;\" />";
$icon_picker_html.= "<div id=\"wetter_icon_picker_modal\" style=\"display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10010; text-align:left;\">";
$icon_picker_html.= "  <div style=\"position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:1px solid #333; border-radius:5px; max-height:80vh; overflow-y:auto; width:80%; max-width:600px;\">";
$icon_picker_html.= "    <h3>{$lang->wetter_admin_select_an_icon}</h3><hr/><div id=\"wetter_icon_list_container\" style=\"display:flex; flex-wrap:wrap; gap:8px; justify-content:center;\">";
if(function_exists('wetter_get_all_icon_classes')) { // Sicherstellen, dass die Funktion existiert
    $all_icons = wetter_get_all_icon_classes();
    foreach ($all_icons as $icon_class_option) {
        $icon_picker_html.= "<div class=\"wetter_icon_picker_item\" data-icon-class=\"". htmlspecialchars_uni($icon_class_option). "\" title=\"". htmlspecialchars_uni($icon_class_option). "\" style=\"cursor:pointer; padding:5px; font-size:1.5em; border:1px solid transparent;\" onmouseover=\"this.style.borderColor='#999'\" onmouseout=\"this.style.borderColor='transparent'\"><i class=\"wi ". htmlspecialchars_uni($icon_class_option). "\"></i></div>";
    }
}
$icon_picker_html.= "    </div><hr/><input type=\"button\" id=\"wetter_close_icon_picker_button\" value=\"{$lang->wetter_admin_close_picker_button}\" class=\"button\" style=\"margin-top:15px;\" />";
$icon_picker_html.= "  </div></div>";
$form_container->output_row($lang->wetter_admin_icon_class." <em>*</em>", $lang->wetter_admin_icon_class_desc, $icon_picker_html, 'icon');

// Temperatur
$current_temp_value =!empty($mybb->input['temperatur'])? htmlspecialchars_uni($mybb->input['temperatur']) : htmlspecialchars_uni($entry_data['temperatur']);
$form_container->output_row($lang->wetter_admin_temperature." <em>*</em>", "", $form->generate_text_box('temperatur', $current_temp_value, array('id' => 'temperatur')), 'temperatur');

// Wetterlage
$current_wetterlage_value =!empty($mybb->input['wetterlage'])? htmlspecialchars_uni($mybb->input['wetterlage']) : htmlspecialchars_uni($entry_data['wetterlage']);
$form_container->output_row($lang->wetter_admin_condition." <em>*</em>", "", $form->generate_text_box('wetterlage', $current_wetterlage_value, array('id' => 'wetterlage')), 'wetterlage');

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

// Mondphase
$current_moonphase_value =!empty($mybb->input['mondphase'])? htmlspecialchars_uni($mybb->input['mondphase']) : htmlspecialchars_uni($entry_data['mondphase']);
$form_container->output_row($lang->wetter_admin_moonphase, "", $form->generate_text_box('mondphase', $current_moonphase_value, array('id' => 'mondphase')), 'mondphase');

// Windrichtung
if(function_exists('wetter_get_wind_directions')) { // Sicherstellen, dass die Funktion existiert
    $wind_directions = wetter_get_wind_directions();
    $selected_wind_direction =!empty($mybb->input['windrichtung'])? $mybb->input['windrichtung'] : $entry_data['windrichtung'];
    $form_container->output_row($lang->wetter_admin_winddirection, "", $form->generate_select_box('windrichtung', $wind_directions, $selected_wind_direction), 'windrichtung');
}

// Windstärke
$current_windspeed_value =!empty($mybb->input['windstaerke'])? htmlspecialchars_uni($mybb->input['windstaerke']) : htmlspecialchars_uni($entry_data['windstaerke']);
$form_container->output_row($lang->wetter_admin_windspeed, "", $form->generate_text_box('windstaerke', $current_windspeed_value, array('id' => 'windstaerke')), 'windstaerke');

echo $form_container->end();

$buttons = array();
$buttons[] = $form->generate_submit_button($lang->wetter_admin_update_entry_button, array("name" => "submit_wetter_entry"));
echo $form->output_submit_wrapper($buttons);
echo "</form>";

$page->output_footer();
?>