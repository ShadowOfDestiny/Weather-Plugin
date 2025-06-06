<?php

if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Include main plugin file for helper functions
if (!function_exists('wetter_sanitize_city_name_for_table') || !function_exists('wetter_info') || !function_exists('wetter_get_all_icon_classes')) {
    $plugin_main_file = MYBB_ROOT . "inc/plugins/wetter.php";
    if (file_exists($plugin_main_file)) {
        require_once $plugin_main_file;
    } else {
        if (is_object($page)) {
            $page->output_header("Fehler");
            $page->output_inline_error("Kritischer Fehler: Haupt-Plugin-Datei wetter.php nicht gefunden oder unvollständig!");
            $page->output_footer();
        } else {
            die("Kritischer Fehler: Haupt-Plugin-Datei wetter.php nicht gefunden oder unvollständig.");
        }
        exit;
    }
}

global $mybb, $db, $lang, $page;

// Load language file
if (!isset($lang->wetter_admin_edit_entry_title)) {
    $lang->load("wetter", false, true);
}

// CSS für Icons im ACP laden (beide Dateien, nur einmal)
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

$page->output_header($lang->wetter_admin_edit_entry_title);

if (!function_exists('wetter_generate_icon_picker_html_edit')) {
    function wetter_generate_icon_picker_html_edit($field_name, $current_value, $preview_font_size = '1.8em') {
        global $form, $lang;

        // Schritt 1: Fange alle ungültigen oder alten Werte ab und ersetze sie durch 'wi-na'
        // Ein gültiger Wert MUSS mit 'wi-' beginnen.
        if (empty($current_value) || strpos($current_value, 'wi-') !== 0) {
            $current_value = 'wi-na';
        }

        $current_value_escaped = htmlspecialchars($current_value);
        $display_classes_for_preview = $current_value_escaped;
        $icon_type_for_js = 'default';

        // Schritt 2: Setze den icon-type KORREKT nur basierend auf dem Feldnamen
        if ($field_name == 'windrichtung') {
            $icon_type_for_js = 'wind'; // Das ist ein Windfeld, egal was der aktuelle Wert ist!
            
            // Schritt 3: Passe die Anzeige an, wenn es eine spezifische Wind-Klasse ist
            if ($current_value_escaped != 'wi-na') {
                // Die Prüfung auf 'wi-from-' etc. ist hier optional, da wir schon wissen,
                // dass es ein gültiges Wind-Icon sein sollte.
                $display_classes_for_preview = 'wi-wind ' . $current_value_escaped;
            }
            // Wenn der Wert 'wi-na' ist, wird für display_classes_for_preview korrekt 'wi-na' verwendet.
        }

        $picker_html = $form->generate_hidden_field($field_name, $current_value_escaped, array('id' => $field_name . '_input'));
        // Jetzt wird data-icon-type="wind" korrekt für das Windfeld ausgegeben
        $picker_html .= "<div id=\"{$field_name}_preview\" class=\"wetter-icon-preview-box\" data-icon-type=\"{$icon_type_for_js}\" style=\"font-size: {$preview_font_size}; display: inline-block; vertical-align: middle; text-align: center; border: 1px solid #ccc; padding: 5px; min-width: 40px; line-height: 1;\"><i class=\"wi {$display_classes_for_preview}\"></i></div>";
        $picker_html .= "<input type=\"button\" class=\"button open_icon_picker_button\" data-target-input=\"{$field_name}_input\" data-target-preview=\"{$field_name}_preview\" value=\"" . ($lang->wetter_admin_select_icon_button ?: "Icon auswählen") . "\" style=\"margin-left:10px;\" />";
        return $picker_html;
    }
}

// Navigation Tabs
$sub_menu = array();
$sub_menu['overview'] = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry'] = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry');
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['settings'] = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$sub_menu['cities'] = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$page->output_nav_tabs($sub_menu, 'entry'); // 'entry' bleibt aktiv, da 'edit' eine Unterfunktion ist

// Parameter abrufen
$entry_id = $mybb->get_input('id', MyBB::INPUT_INT);
$stadt_original = trim($mybb->get_input('stadt', MyBB::INPUT_STRING));

if ($entry_id <= 0 || empty($stadt_original)) {
    flash_message($lang->wetter_admin_error_invalid_edit_params ?: "Ungültige Parameter zum Bearbeiten des Eintrags.", "error");
    admin_redirect("index.php?module=wetter-overview");
    exit;
}

// Tabellenname generieren und Existenz prüfen
$city_suffix = wetter_sanitize_city_name_for_table($stadt_original);
if (empty($city_suffix)) {
    flash_message($lang->wetter_admin_error_city_name_invalid_chars ?: "Der Stadtname ist nach der Bereinigung ungültig oder leer.", "error");
    admin_redirect("index.php?module=wetter-overview");
    exit;
}
$table_name_no_prefix = "wetter_" . $db->escape_string($city_suffix);

if (!$db->table_exists($table_name_no_prefix)) {
    flash_message(sprintf($lang->wetter_admin_error_table_not_exist_for_city ?: "Datentabelle für Stadt '%s' existiert nicht.", htmlspecialchars_uni($stadt_original)), "error");
    admin_redirect("index.php?module=wetter-overview");
    exit;
}

// Bestehende Daten laden
$query = $db->simple_select($table_name_no_prefix, "*", "id = '" . (int)$entry_id . "'", array("limit" => 1));
$entry_data = $db->fetch_array($query);

if (!$entry_data) {
    flash_message($lang->wetter_admin_error_entry_not_found ?: "Wettereintrag nicht gefunden.", "error");
    admin_redirect("index.php?module=wetter-overview&stadt=" . urlencode($stadt_original));
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
        "windrichtung" => $db->escape_string($mybb->get_input('windrichtung')), // z.B. "wi-towards-n"
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
        $page->output_inline_error($errors);
    }
}

// Formular anzeigen
$form = new Form("index.php?module=wetter-edit&amp;id={$entry_id}&amp;stadt=" . urlencode($stadt_original), "post", "edit_entry_form");
echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

$form_container = new FormContainer($lang->wetter_admin_edit_entry_details ?: "Wettereintrag bearbeiten");

$form_container->output_row($lang->wetter_admin_city, "", htmlspecialchars_uni(ucfirst($stadt_original)));

$current_date_value = htmlspecialchars_uni($mybb->input['datum'] ?? $entry_data['datum']);
$datum_html = '<input type="date" name="datum" id="datum" value="' . $current_date_value . '" style="width: auto; padding: 5px;" />';
$form_container->output_row($lang->wetter_admin_date . " <em>*</em>", $lang->wetter_admin_date_desc, $datum_html, 'datum');

$timeslot_options = array("00-06" => "00:00 - 06:00", "06-12" => "06:00 - 12:00", "12-18" => "12:00 - 18:00", "18-24" => "18:00 - 24:00 Uhr");
$selected_timeslot = $mybb->input['zeitspanne'] ?? $entry_data['zeitspanne'];
$form_container->output_row($lang->wetter_admin_timeslot . " <em>*</em>", "", $form->generate_select_box('zeitspanne', $timeslot_options, $selected_timeslot), 'zeitspanne');

$current_main_icon = $mybb->input['icon'] ?? $entry_data['icon'];
$main_icon_picker_html = wetter_generate_icon_picker_html_edit('icon', $current_main_icon);
$form_container->output_row($lang->wetter_admin_icon_class . " <em>*</em>", $lang->wetter_admin_icon_class_desc, $main_icon_picker_html, 'icon');

$current_temp_value = htmlspecialchars_uni($mybb->input['temperatur'] ?? $entry_data['temperatur']);
$form_container->output_row($lang->wetter_admin_temperature . " <em>*</em>", "", $form->generate_text_box('temperatur', $current_temp_value, array('id' => 'temperatur')), 'temperatur');

$current_wetterlage_value = htmlspecialchars_uni($mybb->input['wetterlage'] ?? $entry_data['wetterlage']);
$form_container->output_row(
    $lang->wetter_admin_condition." <em>*</em>",
    $lang->wetter_admin_condition_long_desc ?: "Ausführliche Beschreibung der Wetterlage...",
    $form->generate_text_area('wetterlage', $current_wetterlage_value, array('id' => 'wetterlage', 'rows' => '4', 'style' => 'width: 98%;')),
    'wetterlage'
);

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

$current_mond_icon = $mybb->input['mondphase'] ?? $entry_data['mondphase'];
$mond_icon_picker_html = wetter_generate_icon_picker_html_edit('mondphase', $current_mond_icon);
$form_container->output_row($lang->wetter_admin_moonphase . " <em>*</em>", $lang->wetter_admin_moonphase_icon_desc ?: "Icon für Mondphase auswählen.", $mond_icon_picker_html, 'mondphase');

$current_wind_icon = $mybb->input['windrichtung'] ?? $entry_data['windrichtung']; // Enthält z.B. "wi-towards-n"
$wind_icon_picker_html = wetter_generate_icon_picker_html_edit('windrichtung', $current_wind_icon);
$form_container->output_row($lang->wetter_admin_winddirection . " <em>*</em>", $lang->wetter_admin_winddirection_icon_desc ?: "Icon für Windrichtung auswählen.", $wind_icon_picker_html, 'windrichtung');

$current_windspeed_value = htmlspecialchars_uni($mybb->input['windstaerke'] ?? $entry_data['windstaerke']);
$form_container->output_row($lang->wetter_admin_windspeed, "", $form->generate_text_box('windstaerke', $current_windspeed_value, array('id' => 'windstaerke')), 'windstaerke');

echo $form_container->end();

// --- MODAL FENSTER FÜR DEN ICON PICKER (angepasst für Wind-Icons) ---
$all_icons_for_picker = array();
if (function_exists('wetter_get_all_icon_classes')) {
    $all_icons_for_picker = wetter_get_all_icon_classes();
}
$icon_picker_items_html = '';
foreach ($all_icons_for_picker as $icon_class_item) {
    $data_icon_class = htmlspecialchars($icon_class_item);
    $display_classes_in_modal = $data_icon_class; 

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
$buttons[] = $form->generate_submit_button($lang->wetter_admin_update_entry_button ?: "Eintrag aktualisieren", array("name" => "submit_wetter_entry"));
echo $form->output_submit_wrapper($buttons);
echo "</form>";

$page->output_footer();
?>