<?php
// admin/modules/wetter/wetter_entry.php

ini_set('error_log', MYBB_ROOT . 'logs/php_acp_error.log');

if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Lade die Haupt-Plugin-Datei, um Zugriff auf die Helferfunktionen zu erhalten
if (!function_exists('wetter_info') || !function_exists('wetter_get_all_icon_classes') || !function_exists('wetter_sanitize_city_name_for_table') || !function_exists('wetter_helper_get_cities_array_from_string')) {
    $plugin_main_file = MYBB_ROOT . "inc/plugins/wetter.php";
    if (!file_exists($plugin_main_file)) {
        if (is_object($page)) {
            $page->output_header("Fehler");
            $page->output_inline_error("Kritischer Fehler: Haupt-Plugin-Datei wetter.php nicht gefunden oder benötigte Funktionen fehlen!");
            $page->output_footer();
        } else {
            die("Kritischer Fehler: Haupt-Plugin-Datei wetter.php nicht gefunden oder benötigte Funktionen fehlen.");
        }
        exit;
    }
    require_once $plugin_main_file;
}

global $mybb, $db, $lang, $page;

if (!isset($lang->wetter_admin_entry_title)) {
    $lang->load("wetter", false, true);
}

// CSS für Icons im ACP laden (beide Dateien)
if (is_object($page) && property_exists($page, 'extra_header')) {
    $plugin_info_css = wetter_info();
    $version_param_css = '?v=' . (isset($plugin_info_css['version']) ? $plugin_info_css['version'] : time());

    $css_file_main_name = 'weather-icons.min.css';
    $css_path_main = $mybb->settings['bburl'] . '/images/wetter/css/' . $css_file_main_name . $version_param_css;
    $page->extra_header .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_path_main) . '" />';

    $css_file_wind_name = 'weather-icons-wind.min.css';
    $css_path_wind = $mybb->settings['bburl'] . '/images/wetter/css/' . $css_file_wind_name . $version_param_css;
    $page->extra_header .= "\n\t" . '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_path_wind) . '" />';
}

$page->output_header($lang->wetter_admin_entry_title);

// Nav-Tabs
$sub_menu = array();
$sub_menu['overview'] = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry'] = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry', 'active' => true);
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['settings'] = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$sub_menu['cities'] = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$page->output_nav_tabs($sub_menu, 'entry');


// Verarbeitung des Formulars (Speichern)
if ($mybb->request_method == "post" && $mybb->get_input('submit_wetter_entry')) {
    verify_post_check($mybb->get_input('my_post_key'));

    $stadt = $db->escape_string($mybb->get_input('stadt'));
    $datum = $db->escape_string($mybb->get_input('datum'));
    $zeitspanne = $db->escape_string($mybb->get_input('zeitspanne'));
    $icon = $db->escape_string($mybb->get_input('icon'));
    $temperatur = $db->escape_string($mybb->get_input('temperatur'));
    $wetterlage = $db->escape_string($mybb->get_input('wetterlage'));
    $sonnenaufgang = $db->escape_string($mybb->get_input('sonnenaufgang'));
    $sonnenuntergang = $db->escape_string($mybb->get_input('sonnenuntergang'));
    $mondphase = $db->escape_string($mybb->get_input('mondphase'));
    $windrichtung = $db->escape_string($mybb->get_input('windrichtung')); // Hier wird z.B. "wi-towards-n" gespeichert
    $windstaerke = $db->escape_string($mybb->get_input('windstaerke'));
    $is_archive_entry = ($mybb->get_input('is_archive', MyBB::INPUT_INT) == 1);

    $errors = array();
    if (empty($stadt)) $errors[] = $lang->wetter_admin_error_no_city;
    if (empty($datum) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $datum)) $errors[] = $lang->wetter_admin_error_invalid_date;
    if (empty($icon)) $errors[] = $lang->wetter_admin_error_no_icon_selected;
    if (empty($mondphase)) $errors[] = $lang->wetter_admin_error_no_moonphase_icon_selected ?: "Bitte wähle ein Mondphasen-Icon.";
    if (empty($windrichtung)) $errors[] = $lang->wetter_admin_error_no_wind_icon_selected ?: "Bitte wähle ein Windrichtungs-Icon."; // Bleibt so, da ein Wert (z.B. 'wi-towards-n' oder 'wi-na') erwartet wird.

    if (!empty($sonnenaufgang) && !preg_match("/^(?:[01]\d|2[0-3]):[0-5]\d$/", $sonnenaufgang)) {
        $errors[] = $lang->wetter_admin_error_invalid_time_sunrise;
    }
    if (!empty($sonnenuntergang) && !preg_match("/^(?:[01]\d|2[0-3]):[0-5]\d$/", $sonnenuntergang)) {
        $errors[] = $lang->wetter_admin_error_invalid_time_sunset;
    }

    if (empty($errors)) {
        $city_suffix = wetter_sanitize_city_name_for_table($stadt);
        $table_name = "wetter_" . $city_suffix;
        if ($is_archive_entry) {
            $table_name .= "_archiv";
        }

        $neuer_eintrag = [
            "datum" => $datum, "zeitspanne" => $zeitspanne, "icon" => $icon,
            "temperatur" => $temperatur, "wetterlage" => $wetterlage,
            "sonnenaufgang" => $sonnenaufgang, "sonnenuntergang" => $sonnenuntergang,
            "mondphase" => $mondphase, "windrichtung" => $windrichtung, "windstaerke" => $windstaerke
        ];

        if (!$db->table_exists($table_name)) {
            flash_message(sprintf($lang->wetter_admin_error_table_not_exist_for_city ?: "Datentabelle für Stadt '%s' existiert nicht.", htmlspecialchars($stadt)), "error");
        } else {
            $db->insert_query($table_name, $neuer_eintrag);
            log_admin_action(array('module' => 'wetter-entry', 'action' => 'add_data', 'city' => $stadt, 'datum' => $datum));
            flash_message($lang->wetter_admin_data_added_success, "success");
            admin_redirect("index.php?module=wetter-entry&stadt=" . urlencode($stadt));
        }
    } else {
        $page->output_inline_error($errors);
    }
}

// --- ICON PICKER HTML GENERIERUNG (angepasst für Wind-Icons) ---
function wetter_generate_icon_picker_html($field_name, $current_value, $preview_font_size = '1.8em') {
    global $form, $lang;

    if (empty($current_value)) {
        $current_value = 'wi-na';
    }
    $current_value_escaped = htmlspecialchars($current_value);
    $display_classes_for_preview = $current_value_escaped; // Standard für die meisten Icons

    // Spezifische Behandlung für die Windrichtungs-VORSCHAU
    // $current_value enthält hier die spezifische Richtungsklasse, z.B. "wi-towards-n"
    if ($field_name == 'windrichtung' && $current_value_escaped != 'wi-na') {
        if (strpos($current_value_escaped, 'wi-towards-') === 0 || strpos($current_value_escaped, 'wi-from-') === 0) {
            $display_classes_for_preview = 'wi-wind ' . $current_value_escaped; // Füge 'wi-wind ' davor ein
        }
    }

    $picker_html = $form->generate_hidden_field($field_name, $current_value_escaped, array('id' => $field_name . '_input'));
    $picker_html .= "<div id=\"{$field_name}_preview\" class=\"wetter-icon-preview-box\" style=\"font-size: {$preview_font_size}; display: inline-block; vertical-align: middle; text-align: center; border: 1px solid #ccc; padding: 5px; min-width: 40px; line-height: 1;\"><i class=\"wi {$display_classes_for_preview}\"></i></div>";
    $picker_html .= "<input type=\"button\" class=\"button open_icon_picker_button\" data-target-input=\"{$field_name}_input\" data-target-preview=\"{$field_name}_preview\" value=\"" . ($lang->wetter_admin_select_icon_button ?: "Icon auswählen") . "\" style=\"margin-left:10px;\" />";
    return $picker_html;
}
// --- ENDE ICON PICKER HTML GENERIERUNG ---


// Formular für die Eingabe neuer Wetterdaten
$form = new Form("index.php?module=wetter-entry", "post");
echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
$form_container = new FormContainer($lang->wetter_admin_entry_details);

// Stadt-Auswahl
$cities = wetter_helper_get_cities_array_from_string();
$city_options = array();
if (!empty($cities)) {
    foreach ($cities as $c) {
        $city_options[$c] = htmlspecialchars(ucfirst($c));
    }
}
$selected_stadt_for_form = $mybb->input['stadt'] ?? ($mybb->get_input('city', MyBB::INPUT_STRING) ?: ($cities[0] ?? ''));
if (empty($cities)) {
    $form_container->output_row($lang->wetter_admin_city, "", $lang->wetter_admin_please_add_cities_first);
} else {
    $form_container->output_row($lang->wetter_admin_city . " <em>*</em>", "", $form->generate_select_box('stadt', $city_options, $selected_stadt_for_form), 'stadt');
}

// Datum
$current_date_value = htmlspecialchars($mybb->input['datum'] ?? date("Y-m-d"));
$datum_html = '<input type="date" name="datum" id="datum" value="' . $current_date_value . '" style="width: auto; padding: 5px;" />';
$form_container->output_row($lang->wetter_admin_date . " <em>*</em>", $lang->wetter_admin_date_desc, $datum_html, 'datum');

// Zeitspanne
$timeslot_options = array("00-06" => "00:00 - 06:00", "06-12" => "06:00 - 12:00", "12-18" => "12:00 - 18:00", "18-24" => "18:00 - 24:00 Uhr");
$selected_zeitspanne = $mybb->input['zeitspanne'] ?? '00-06';
$form_container->output_row($lang->wetter_admin_timeslot . " <em>*</em>", "", $form->generate_select_box('zeitspanne', $timeslot_options, $selected_zeitspanne), 'zeitspanne');

// Haupt-Wetter-Icon
// $edit_data ist in wetter_entry.php nicht relevant, kann entfernt werden oder bleibt für Konsistenz mit edit
$current_main_icon = $mybb->input['icon'] ?? ($edit_data['icon'] ?? 'wi-na');
$main_icon_picker_html = wetter_generate_icon_picker_html('icon', $current_main_icon);
$form_container->output_row($lang->wetter_admin_icon_class . " <em>*</em>", $lang->wetter_admin_icon_class_desc, $main_icon_picker_html, 'icon');

// Temperatur
$form_container->output_row($lang->wetter_admin_temperature . " <em>*</em>", "", $form->generate_text_box('temperatur', $mybb->input['temperatur'] ?? ($edit_data['temperatur'] ?? ''), array('id' => 'temperatur')), 'temperatur');

// Wetterlage
$form_container->output_row(
    $lang->wetter_admin_condition . " <em>*</em>",
    $lang->wetter_admin_condition_long_desc ?: "Ausführliche Beschreibung der Wetterlage...",
    $form->generate_text_area('wetterlage', $mybb->input['wetterlage'] ?? ($edit_data['wetterlage'] ?? ''), array('id' => 'wetterlage', 'rows' => '4', 'style' => 'width: 98%;')),
    'wetterlage'
);

// Sonnenaufgang
$sonnenaufgang_value = htmlspecialchars($mybb->input['sonnenaufgang'] ?? ($edit_data['sonnenaufgang'] ?? ''));
$sonnenaufgang_html = '<input type="time" name="sonnenaufgang" id="sonnenaufgang" value="' . $sonnenaufgang_value . '" step="60" />';
$form_container->output_row($lang->wetter_admin_sunrise, $lang->wetter_admin_time_desc, $sonnenaufgang_html, 'sonnenaufgang');

// Sonnenuntergang
$sonnenuntergang_value = htmlspecialchars($mybb->input['sonnenuntergang'] ?? ($edit_data['sonnenuntergang'] ?? ''));
$sonnenuntergang_html = '<input type="time" name="sonnenuntergang" id="sonnenuntergang" value="' . $sonnenuntergang_value . '" step="60" />';
$form_container->output_row($lang->wetter_admin_sunset, $lang->wetter_admin_time_desc, $sonnenuntergang_html, 'sonnenuntergang');

// Mondphase (Icon-Picker)
$current_mond_icon = $mybb->input['mondphase'] ?? ($edit_data['mondphase'] ?? 'wi-na');
$mond_icon_picker_html = wetter_generate_icon_picker_html('mondphase', $current_mond_icon);
$form_container->output_row($lang->wetter_admin_moonphase . " <em>*</em>", $lang->wetter_admin_moonphase_icon_desc ?: "Icon für Mondphase auswählen.", $mond_icon_picker_html, 'mondphase');

// Windrichtung (Icon-Picker)
$current_wind_icon = $mybb->input['windrichtung'] ?? ($edit_data['windrichtung'] ?? 'wi-na'); // Hier wird z.B. "wi-towards-n" als Wert erwartet
$wind_icon_picker_html = wetter_generate_icon_picker_html('windrichtung', $current_wind_icon);
$form_container->output_row($lang->wetter_admin_winddirection . " <em>*</em>", $lang->wetter_admin_winddirection_icon_desc ?: "Icon für Windrichtung auswählen.", $wind_icon_picker_html, 'windrichtung');

// Windstärke
$form_container->output_row($lang->wetter_admin_windspeed, "", $form->generate_text_box('windstaerke', $mybb->input['windstaerke'] ?? ($edit_data['windstaerke'] ?? ''), array('id' => 'windstaerke')), 'windstaerke');

// Checkbox für Archiv
$form_container->output_row($lang->wetter_admin_is_archive, "", $form->generate_yes_no_radio('is_archive', $mybb->input['is_archive'] ?? 0), 'is_archive');

echo $form_container->end();

// --- MODAL FENSTER FÜR DEN ICON PICKER (angepasst für Wind-Icons) ---
$all_icons_for_picker = array();
if (function_exists('wetter_get_all_icon_classes')) {
    $all_icons_for_picker = wetter_get_all_icon_classes();
}
$icon_picker_items_html = '';
foreach ($all_icons_for_picker as $icon_class_item) {
    // $icon_class_item ist der Wert aus Deiner Liste (z.B. 'wi-day-sunny' oder 'wi-towards-n')
    $data_icon_class = htmlspecialchars($icon_class_item); // Dieser Wert wird gespeichert (z.B. 'wi-towards-n' für Wind)
    $display_classes_in_modal = $data_icon_class; // Standard für normale Icons (z.B. 'wi-day-sunny')

    // Spezifische Behandlung für Windrichtungs-Klassen im Modal
    // Wenn $icon_class_item eine spezifische Wind-Direktions-Klasse ist (z.B. "wi-towards-n"),
    // dann fügen wir "wi-wind " davor für die Anzeige im Modal.
    if (strpos($icon_class_item, 'wi-towards-') === 0 || strpos($icon_class_item, 'wi-from-') === 0) {
        $display_classes_in_modal = 'wi-wind ' . $data_icon_class;
    }

    $icon_picker_items_html .= "<div class=\"wetter_icon_picker_item\" data-icon-class=\"{$data_icon_class}\" title=\"{$data_icon_class}\" style=\"cursor:pointer; padding:5px; font-size:1.5em; border:1px solid transparent;\" onmouseover=\"this.style.borderColor='#999'\" onmouseout=\"this.style.borderColor='transparent'\"><i class=\"wi {$display_classes_in_modal}\"></i></div>";
}

$modal_html = "<div id=\"wetter_icon_picker_modal\" style=\"display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10010; text-align:left;\">";
$modal_html .= "  <div style=\"position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); background:white; padding:20px; border:1px solid #333; border-radius:5px; max-height:80vh; overflow-y:auto; width:80%; max-width:600px;\">";
$modal_html .= "    <div id=\"wetter_icon_picker_header\" style=\"display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;\"><h3>" . ($lang->wetter_admin_select_an_icon ?: "Icon auswählen") . "</h3><button type=\"button\" id=\"wetter_close_icon_picker_button\" class=\"button\">X</button></div><hr/><div id=\"wetter_icon_list_container\" style=\"display:flex; flex-wrap:wrap; gap:8px; justify-content:center;\">";
$modal_html .= $icon_picker_items_html;
$modal_html .= "    </div>"; // wetter_icon_list_container
$modal_html .= "  </div>"; // inner div
$modal_html .= "</div>"; // wetter_icon_picker_modal

echo $modal_html;

$buttons = array();
$buttons[] = $form->generate_submit_button($lang->wetter_admin_save_entry, array("name" => "submit_wetter_entry"));
echo $form->output_submit_wrapper($buttons);
echo "</form>";

$page->output_footer();
?>