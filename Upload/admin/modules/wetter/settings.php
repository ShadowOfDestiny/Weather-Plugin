<?php
/**
 * ACP-Modul für das Wetter Plugin - Einstellungen
 */

if (!defined("IN_MYBB") || !defined("IN_ADMINCP")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Lade Admin-Sprachdatei, falls nicht schon geschehen (für Titel etc.)
if(!isset($lang->wetter_admin_settings_title)) {
    $lang->load("wetter", false, true);
}


$page->add_breadcrumb_item($lang->wetter_admin_title ?: "Wetterverwaltung", "index.php?module=wetter-overview");
$page->add_breadcrumb_item($lang->wetter_admin_settings_title ?: "Wetter Plugin Einstellungen", "index.php?module=wetter-settings");


// Wenn das Formular abgesendet wurde, speichere die Einstellungen
if($mybb->request_method == "post")
{
	verify_post_check($mybb->get_input('my_post_key'));

    $new_settings_values = [
        "wetter_plugin_active" => $mybb->get_input('wetter_plugin_active', MyBB::INPUT_INT),
        "wetter_plugin_date_format" => $mybb->get_input('wetter_plugin_date_format', MyBB::INPUT_STRING),
        "wetter_plugin_active_months" => $mybb->get_input('wetter_plugin_active_months', MyBB::INPUT_STRING),
        "wetter_plugin_cities" => $mybb->get_input('wetter_plugin_cities', MyBB::INPUT_STRING),
        "wetter_plugin_items_per_page_frontend" => $mybb->get_input('wetter_plugin_items_per_page_frontend', MyBB::INPUT_INT), // NEU
        "wetter_plugin_items_per_page_acp" => $mybb->get_input('wetter_plugin_items_per_page_acp', MyBB::INPUT_INT),       // NEU
    ];

    foreach($new_settings_values as $name => $value)
    {
        // Sicherstellen, dass die Einstellung existiert, bevor versucht wird, sie zu aktualisieren
        $query_setting_exists = $db->simple_select("settings", "name", "name='".$db->escape_string($name)."'");
        if($db->num_rows($query_setting_exists) > 0)
        {
            $db->update_query("settings", ["value" => $db->escape_string($value)], "name='".$db->escape_string($name)."'");
        }
        // Optional: Fehlerbehandlung, falls eine Einstellung nicht existiert (sollte durch Installation/Upgrade abgedeckt sein)
    }

    rebuild_settings();
    flash_message($lang->settings_updated ?: "Einstellungen erfolgreich aktualisiert.", "success"); // Standard MyBB Sprachvariable verwenden
	admin_redirect("index.php?module=wetter-settings");
}

// Ausgabe des Seiten-Headers
$page->output_header($lang->wetter_admin_settings_title ?: "Wetter Plugin Einstellungen");

// Nav-Tabs für Konsistenz im ACP-Modul
$sub_menu = array();
$sub_menu['overview']     = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry']        = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry');
// $sub_menu['archive']      = array('title' => $lang->wetter_admin_manage_archive_title, 'link' => 'index.php?module=wetter-archive'); // Falls du diese Seite hast
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['cities']       = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$sub_menu['settings']     = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings', 'active' => true);
$page->output_nav_tabs($sub_menu, 'settings');


$form = new Form("index.php?module=wetter-settings", "post");

// Erstelle ein Form Container
$form_container = new FormContainer($lang->wetter_settings_title ?: "Wetter Plugin Einstellungen");

// Plugin aktiv
$form_container->output_row(
    $lang->wetter_setting_active_title ?: "Plugin aktiv?",
    $lang->wetter_setting_active_desc ?: "Soll das Wetter Plugin aktiviert sein und die Frontend-Seite erreichbar sein?",
    $form->generate_yes_no_radio("wetter_plugin_active", $mybb->settings['wetter_plugin_active']),
    "wetter_plugin_active"
);

// Datumsformat
$form_container->output_row(
    $lang->wetter_setting_date_format_title ?: "Datumsformat (Frontend)",
    $lang->wetter_setting_date_format_desc ?: "PHP Datumsformat für die Anzeige im Frontend (z.B. d.m.Y).",
    $form->generate_text_box("wetter_plugin_date_format", $mybb->settings['wetter_plugin_date_format']),
    "wetter_plugin_date_format"
);

// Aktive Monate
$form_container->output_row(
    $lang->wetter_setting_active_month_title ?: "Aktive Monate (CSV)",
    $lang->wetter_setting_active_month_desc ?: "Komma-getrennte Liste der Monate (z.B. November,Dezember). Für nicht gelistete Monate wird das Archiv verwendet.",
    $form->generate_text_box("wetter_plugin_active_months", $mybb->settings['wetter_plugin_active_months']),
    "wetter_plugin_active_months"
);

// Konfigurierte Städte (Hinweis: wird durch Modul verwaltet)
$form_container->output_row(
    $lang->wetter_setting_cities_title ?: "Konfigurierte Städte (CSV)",
    $lang->wetter_setting_cities_desc ?: "Wird vom ACP-Modul 'Städte verwalten' gepflegt. Hier nicht manuell ändern!",
    $form->generate_text_box("wetter_plugin_cities", $mybb->settings['wetter_plugin_cities'], array('disabled' => true)), // Deaktiviert für manuelle Bearbeitung
    "wetter_plugin_cities"
);

// NEU: Einträge pro Seite (Frontend)
$form_container->output_row(
    $lang->wetter_setting_items_per_page_frontend_title ?: "Einträge pro Seite (Frontend)",
    $lang->wetter_setting_items_per_page_frontend_desc ?: "Anzahl der Wettereinträge, die pro Seite im Frontend angezeigt werden (0 für keine Paginierung).",
    $form->generate_numeric_field("wetter_plugin_items_per_page_frontend", $mybb->settings['wetter_plugin_items_per_page_frontend'] ?? 15, array('min' => 0)),
    "wetter_plugin_items_per_page_frontend"
);

// NEU: Einträge pro Seite (ACP)
$form_container->output_row(
    $lang->wetter_setting_items_per_page_acp_title ?: "Einträge pro Seite (ACP)",
    $lang->wetter_setting_items_per_page_acp_desc ?: "Anzahl der Wettereinträge, die pro Seite in der ACP Übersicht angezeigt werden.",
    $form->generate_numeric_field("wetter_plugin_items_per_page_acp", $mybb->settings['wetter_plugin_items_per_page_acp'] ?? 20, array('min' => 5)), // Mindestens 5 im ACP
    "wetter_plugin_items_per_page_acp"
);


$form_container->end();

$buttons[] = $form->generate_submit_button($lang->save_settings ?: "Einstellungen speichern");
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();
?>