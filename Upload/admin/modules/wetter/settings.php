<?php
/**
 * ACP-Modul für das Wetter Plugin
 *
 * Diese Datei muss unter inc/admin/modules/wetter/ abgelegt werden
 */

if (!defined("IN_MYBB")) {
    die("Direkter Zugriff nicht erlaubt.");
}

$page->add_breadcrumb_item("Wetter Plugin", "index.php?module=config-wetter");

// Laden der MyBB-eigenen Form-Klassen
require_once MYBB_ADMIN_DIR."inc/class_form.php";
require_once MYBB_ADMIN_DIR."inc/functions.php";

// Wenn das Formular abgesendet wurde, speichere die Einstellungen
if($mybb->request_method == "post")
{
	verify_post_check($mybb->input['my_post_key']);
  // Die Einstellungen werden über MyBBs eigene Settings-API gespeichert.
  // Du kannst alle Felder einzeln updaten:
  $new_settings = [
  "wetter_plugin_active" => $mybb->get_input('wetter_plugin_active', MyBB::INPUT_INT), // Name angepasst
  "wetter_plugin_date_format" => $db->escape_string($mybb->get_input('wetter_plugin_date_format')), // Name angepasst
  "wetter_plugin_active_months" => $db->escape_string($mybb->get_input('wetter_plugin_active_months')), // Name angepasst
  "wetter_plugin_cities" => $db->escape_string($mybb->get_input('wetter_plugin_cities')) // Name angepasst
  ];
  foreach($new_settings as $name => $value)
  {
    $db->update_query("settings", ["value" => $db->escape_string($value)], "name='{$name}'");
  }
  rebuild_settings();
  flash_message("Einstellungen gespeichert.", "success");
	admin_redirect("index.php?module=wetter-settings");
}

// Ausgabe des Seiten-Headers
$page->output_header("Wetter Plugin Einstellungen");

$form = new Form("index.php?module=wetter-settings", "post");

// Erstelle ein Form Container
$form_container = new FormContainer("Wetter Plugin Einstellungen");

$yes = '<label for="wetter_plugin_active_yes"><input type="radio" name="wetter_plugin_active" id="wetter_plugin_active_yes" class="radio_input" value="1" ' . (($mybb->settings['wetter_plugin_active'] == 1) ? 'checked="checked"' : '') . ' /> ' . $lang->yes . '</label>';
$no = '<label for="wetter_plugin_active_no"><input type="radio" name="wetter_plugin_active" id="wetter_plugin_active_no" class="radio_input" value="0" ' . (($mybb->settings['wetter_plugin_active'] == 0) ? 'checked="checked"' : '') . ' /> ' . $lang->no . '</label>';
$yes_no_radio = $yes . " " . $no;

$form_container->output_row("Plugin aktiv",
    "Soll das Wetter Plugin aktiviert sein?",
    $yes_no_radio,
    "wetter_plugin_active" // Name im HTML-Formular auch anpassen
);

$form_container->output_row("Datumsformat", "Das Format, nach dem das Datum abgefragt wird (z.B. Y-m-d)", $form->generate_text_box("wetter_plugin_date_format", $mybb->settings['wetter_plugin_date_format']), "wetter_plugin_date_format"); // Namen angepasst

$form_container->output_row("Aktive Wettermonate", "Gib die Monate ein, die als aktiv gelten (z.B. November,Dezember). Für andere Monate wird der Archivmodus genutzt.", $form->generate_text_box("wetter_plugin_active_months", $mybb->settings['wetter_plugin_active_months']), "wetter_plugin_active_months");

$form_container->output_row("Städte für Wetterdaten", "Wird vom ACP-Modul Städte verwalten gepflegt. Hier nicht manuell ändern!", $form->generate_text_box("wetter_plugin_cities", $mybb->settings['wetter_plugin_cities']), "wetter_plugin_cities");

$form_container->end();

$buttons[] = $form->generate_submit_button("Einstellungen speichern");
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();
?>


