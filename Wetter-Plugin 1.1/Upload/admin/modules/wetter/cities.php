<?php
// admin/modules/wetter/cities.php

if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Deine Haupt-Plugin-Datei einbinden, damit die Helferfunktionen verfügbar sind.
// MyBB lädt Plugin-Dateien aus inc/plugins/ normalerweise automatisch, wenn sie aktiv sind.
// Die Funktionen sollten also global verfügbar sein, WENN das Plugin aktiv ist.
// Ein explizites require_once ist hier meist nicht nötig und kann zu Problemen führen,
// wenn die Datei schon geladen wurde. Verlasse dich darauf, dass MyBB aktive Plugins lädt.

global $mybb, $db, $lang, $page;

// Lade deine ZENTRALE Admin-Sprachdatei (z.B. admin/wetter.lang.php)
if(!isset($lang->wetter_admin_manage_cities_title)) { // Prüfe auf eine Variable aus dieser Datei
    $lang->load("wetter", false, true); // Annahme: Dateiname ist admin/wetter.lang.php
}

// Verarbeitung von Aktionen (Stadt hinzufügen oder löschen)
if ($mybb->request_method == "post" && $mybb->get_input('action_type') == 'add_city') {
    verify_post_check($mybb->input['my_post_key']);
    $new_city_name_original = trim($mybb->get_input('new_city_name', MyBB::INPUT_STRING));
    $errors = array();

    if (empty($new_city_name_original)) {
        $errors[] = $lang->wetter_admin_error_city_name_empty;
    }

    $city_suffix = wetter_sanitize_city_name_for_table($new_city_name_original); // Helferfunktion aufrufen
    if (empty($city_suffix)) {
         $errors[] = $lang->wetter_admin_error_city_name_invalid_chars;
    }

    $configured_cities_str = $mybb->settings['wetter_plugin_cities'];
    $cities_array = array_filter(array_map('trim', explode(",", $configured_cities_str)));
    // Prüfe, ob der *Originalname* schon existiert, um Verwirrung zu vermeiden
    if (in_array($new_city_name_original, $cities_array)) {
        $errors[] = sprintf($lang->wetter_admin_error_city_already_exists, htmlspecialchars($new_city_name_original));
    }
    // Prüfe auch, ob der generierte Suffix schon zu einer anderen Stadt gehört (kann passieren bei ähnlichen Namen)
    // Diese Prüfung ist komplexer und kann für den Anfang weggelassen werden, wenn die Stadtnamen eindeutig genug sind.

    if (empty($errors)) {
        if (wetter_create_tables_for_city($city_suffix)) { // Helferfunktion aufrufen
    // AKTUELLE Städteliste aus den MyBB-Einstellungen holen
    $current_cities_str = $mybb->settings['wetter_plugin_cities'];
    $cities_array = array_filter(array_map('trim', explode(",", $current_cities_str)));

    // Neue Stadt hinzufügen, WENN sie noch nicht existiert (zur Sicherheit, obwohl wir das oben schon geprüft haben)
    if (!in_array($new_city_name_original, $cities_array)) {
        $cities_array[] = $new_city_name_original;
    }

    // Array wieder in einen String umwandeln
    $new_cities_list_str = implode(",", $cities_array);

    // Aktualisiere die MyBB-Einstellung
    $query_sg = $db->simple_select("settinggroups", "gid", "name='wetter_plugin_settings'", 1);
    $setting_group_info = $db->fetch_array($query_sg);

    if($setting_group_info['gid']) {
        $db->update_query("settings", 
            array("value" => $db->escape_string($new_cities_list_str)), 
            "name='wetter_plugin_cities' AND gid='".(int)$setting_group_info['gid']."'"
        );
        rebuild_settings(); // SEHR WICHTIG!

        // --- DEBUG HIER EINFÜGEN ---
        $test_cities_after_rebuild = $mybb->settings['wetter_plugin_cities'];
        echo "";
        // Du kannst hier auch exit; verwenden, um nur diese Ausgabe zu sehen,
        // bevor die flash_message und der redirect kommen.
        // exit; 
        // --- ENDE DEBUG ---

        log_admin_action(array('module' => 'wetter-cities', 'action' => 'add_city', 'city_name' => $new_city_name_original));
        flash_message(sprintf($lang->wetter_admin_city_added_success, htmlspecialchars($new_city_name_original)), "success"); // Stelle sicher, dass $lang->wetter_admin_city_added_success definiert ist
    } else {
        flash_message($lang->wetter_admin_error_setting_group_not_found, "error");
    }
	} else {
    flash_message(sprintf($lang->wetter_admin_error_creating_tables, htmlspecialchars($new_city_name_original)), "error");
	}
    } else {
        flash_message(implode("<br />", $errors), "error");
    }
    admin_redirect("index.php?module=wetter-cities"); // Modulname hier korrigieren

} elseif ($mybb->get_input('action_type') == 'delete_city' && !empty($mybb->get_input('city'))) {
    verify_post_check($mybb->get_input('my_post_key'));
    $city_to_delete_original = $mybb->get_input('city', MyBB::INPUT_STRING);

    if (empty($city_to_delete_original)) {
        flash_message($lang->wetter_admin_error_no_city_to_delete, "error");
        admin_redirect("index.php?module=wetter-cities"); // Modulname hier korrigieren
    }

    $city_suffix_to_delete = wetter_sanitize_city_name_for_table($city_to_delete_original);

    $table_main_to_delete = "wetter_" . $db->escape_string($city_suffix_to_delete);
    $table_archive_to_delete = $table_main_to_delete . "_archiv";

    $db->write_query("DROP TABLE IF EXISTS `" . TABLE_PREFIX . $table_main_to_delete . "`;");
    $db->write_query("DROP TABLE IF EXISTS `" . TABLE_PREFIX . $table_archive_to_delete . "`;");

    $configured_cities_str = $mybb->settings['wetter_plugin_cities'];
    $cities_array = array_filter(array_map('trim', explode(",", $configured_cities_str)));
    if (($key = array_search($city_to_delete_original, $cities_array)) !== false) {
        unset($cities_array[$key]);
    }
    $new_cities_list = implode(",", $cities_array);

    $query_sg = $db->simple_select("settinggroups", "gid", "name='wetter_plugin_settings'", 1);
    $setting_group_info = $db->fetch_array($query_sg);
    if($setting_group_info['gid']) {
        $db->update_query("settings", array("value" => $db->escape_string($new_cities_list)), "name='wetter_plugin_cities' AND gid='".(int)$setting_group_info['gid']."'");
        rebuild_settings();
        log_admin_action(array('module' => 'wetter-cities', 'action' => 'delete_city', 'city_name' => $city_to_delete_original));
        flash_message($lang->sprintf($lang->wetter_admin_city_deleted_success, htmlspecialchars($city_to_delete_original)), "success");
    } else {
        flash_message($lang->wetter_admin_error_setting_group_not_found, "error");
    }
    admin_redirect("index.php?module=wetter-cities"); // Modulname hier korrigieren
}

// ANZEIGE DER SEITE (HTML-Formulare und Liste)
$page->output_header($lang->wetter_admin_manage_cities_title);
$page->add_breadcrumb_item($lang->wetter_admin_manage_cities_title);

// Nav-Tabs
$sub_menu = array();
$sub_menu['overview'] = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry'] = array('title' => $lang->wetter_admin_add_data_title, 'link' => 'index.php?module=wetter-entry');
$sub_menu['archive'] = array('title' => $lang->wetter_admin_manage_archive_title, 'link' => 'index.php?module=wetter-archive');
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['settings'] = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$sub_menu['cities'] = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities', 'active' => true);
$page->output_nav_tabs($sub_menu, 'cities');

// Formular zum Hinzufügen
$form_add = new Form("index.php?module=wetter-cities", "post");
echo $form_add->generate_hidden_field("my_post_key", $mybb->post_code);
echo $form_add->generate_hidden_field("action_type", "add_city");

$form_container_add = new FormContainer($lang->wetter_admin_new_city_details);
$form_container_add->output_row($lang->wetter_admin_city_name . " <em>*</em>", $lang->wetter_admin_city_name_desc, $form_add->generate_text_box('new_city_name', $mybb->input['new_city_name']), 'new_city_name');
echo $form_container_add->end();
$buttons_add[] = $form_add->generate_submit_button($lang->wetter_admin_add_city_button);
echo $form_add->output_submit_wrapper($buttons_add);

// Tabelle der existierenden Städte
$table = new Table;
$table->construct_header($lang->wetter_admin_existing_cities);
$table->construct_header($lang->wetter_admin_actions_column, array("class" => "align_center", "width" => "150px"));

$configured_cities_str = $mybb->settings['wetter_plugin_cities'];
$cities_array_display = array_filter(array_map('trim', explode(",", $configured_cities_str)));

if (!empty($cities_array_display)) {
    sort($cities_array_display);
    foreach ($cities_array_display as $city_name_display) {
        $table->construct_cell(htmlspecialchars($city_name_display));
        $delete_city_link = "index.php?module=wetter-cities&action_type=delete_city&city=" . urlencode($city_name_display) . "&my_post_key=" . $mybb->post_code;
        $table->construct_cell("<a href=\"{$delete_city_link}\" onclick=\"return AdminCP.deleteConfirmation(this, '{$lang->sprintf($lang->wetter_admin_delete_city_confirm, htmlspecialchars($city_name_display))}');\">{$lang->wetter_admin_delete}</a>", array("class" => "align_center"));
        $table->construct_row();
    }
} else {
    $table->construct_cell($lang->wetter_admin_no_cities_yet, array("colspan" => 2));
    $table->construct_row();
}
$table->output($lang->wetter_admin_configured_cities_table_title);

$page->output_footer();
?>
