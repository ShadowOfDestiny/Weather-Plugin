<?php
if(!defined("IN_MYBB")) {
    die("Direkter Zugriff nicht erlaubt.");
}

global $mybb, $db, $lang, $page; // $page für ACP-Funktionen, $lang für Sprachvariablen

// Lade die Haupt-Plugin-Datei, um Zugriff auf Helferfunktionen zu erhalten
if (!function_exists('wetter_sanitize_city_name_for_table') || !function_exists('wetter_helper_get_cities_array_from_string')) {
    if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
        require_once MYBB_ROOT . "inc/plugins/wetter.php";
    } else {
        // Kritischer Fehler, wenn die Hauptdatei fehlt
        $page->output_header("Wetterarchivierung Fehler");
        $page->output_inline_error("Fehler: Haupt-Plugin-Datei (wetter.php) nicht gefunden. Helferfunktionen sind nicht verfügbar.");
        $page->output_footer();
        exit;
    }
}

// Lade deine Admin-Sprachdatei
if(!isset($lang->wetter_admin_archive_title_actual)) { // Prüfe auf eine spezifische Variable für diese Seite
    $lang->load("wetter", false, true);
}

$page->output_header($lang->wetter_admin_archive_title_actual ?: "Wetterarchivierung"); // Neue Sprachvariable für den Seitentitel

// Nav-Tabs (konsistent mit anderen ACP-Modulen deines Plugins)
$sub_menu = array();
$sub_menu['overview']     = array('title' => $lang->wetter_admin_overview_title, 'link' => 'index.php?module=wetter-overview');
$sub_menu['entry']        = array('title' => $lang->wetter_admin_entry_title, 'link' => 'index.php?module=wetter-entry');
$sub_menu['archive']      = array('title' => $lang->wetter_admin_manage_archive_title, 'link' => 'index.php?module=wetter-archive', 'active' => true);
$sub_menu['archive_view'] = array('title' => $lang->wetter_admin_view_archive_title, 'link' => 'index.php?module=wetter-archive_view');
$sub_menu['settings']     = array('title' => $lang->wetter_admin_settings_title, 'link' => 'index.php?module=wetter-settings');
$sub_menu['cities']       = array('title' => $lang->wetter_admin_manage_cities_title, 'link' => 'index.php?module=wetter-cities');
$page->output_nav_tabs($sub_menu, 'archive');

echo "<h2>" . ($lang->wetter_admin_archive_headline ?: "Archivierung vergangener Monate") . "</h2>"; // Neue Sprachvariable
echo "<p>" . ($lang->wetter_admin_archive_description ?: "Hier kannst du die Wetterdaten eines bestimmten Monats für alle konfigurierten Städte archivieren.<br>
Dabei werden die Daten aus den aktiven Tabellen in die jeweiligen Archivtabellen kopiert und anschließend aus den aktiven Tabellen gelöscht.<br>
Bitte stelle sicher, dass für alle Städte bereits Archivtabellen existieren (diese werden beim Anlegen einer Stadt automatisch mit erstellt).") . "</p>"; // Angepasste Beschreibung

// Prüfen, ob das Formular abgeschickt wurde.
if($mybb->request_method == "post" && $mybb->get_input('archive_now_button')) // Name des Submit-Buttons prüfen
{
    verify_post_check($mybb->get_input('my_post_key')); // CSRF-Schutz

    $archive_date = $db->escape_string($mybb->get_input("archive_date", MyBB::INPUT_STRING));
    
    if(preg_match('/^\d{4}-\d{2}$/', $archive_date))
    {
        $from_date = $archive_date . "-01";
        $to_date   = date("Y-m-t", strtotime($from_date));

        $configured_cities = wetter_helper_get_cities_array_from_string(); // Hole alle konfigurierten Städte
        $results_messages = array();
        $has_errors = false;

        if (!empty($configured_cities)) {
            foreach ($configured_cities as $city_name) {
                $city_suffix = wetter_sanitize_city_name_for_table($city_name);
                if (empty($city_suffix)) {
                    $results_messages[] = sprintf($lang->wetter_admin_archive_error_sanitize ?: "Fehler beim Sanitisieren des Stadtnamens für: %s. Übersprungen.", htmlspecialchars($city_name));
                    $has_errors = true;
                    continue;
                }

                $active_table_no_prefix  = "wetter_" . $city_suffix;
                $archive_table_no_prefix = "wetter_" . $city_suffix . "_archiv";

                // Prüfen, ob beide Tabellen existieren
                if (!$db->table_exists($active_table_no_prefix)) {
                    $results_messages[] = sprintf($lang->wetter_admin_archive_error_active_table_missing ?: "Aktive Tabelle für %s nicht gefunden. Übersprungen.", htmlspecialchars($city_name));
                    $has_errors = true;
                    continue;
                }
                if (!$db->table_exists($archive_table_no_prefix)) {
                    $results_messages[] = sprintf($lang->wetter_admin_archive_error_archive_table_missing ?: "Archivtabelle für %s nicht gefunden. Übersprungen.", htmlspecialchars($city_name));
                    $has_errors = true;
                    continue;
                }

                // 1. Daten von der aktiven in die Archivtabelle kopieren
                $query_copy = "INSERT INTO " . TABLE_PREFIX . $archive_table_no_prefix . " 
                               SELECT * FROM " . TABLE_PREFIX . $active_table_no_prefix . " 
                               WHERE datum BETWEEN '{$from_date}' AND '{$to_date}'";
                $db->write_query($query_copy);
                $copied_rows = $db->affected_rows();

                // 2. Daten aus der aktiven Tabelle löschen (nur wenn Kopieren erfolgreich war oder auch so?)
                // Es ist sicherer, nur zu löschen, wenn das Kopieren zumindest keine Fehler erzeugt hat.
                // $db->affected_rows() gibt die Anzahl der beim INSERT betroffenen Zeilen zurück.
                
                $query_delete = "DELETE FROM " . TABLE_PREFIX . $active_table_no_prefix . " 
                                 WHERE datum BETWEEN '{$from_date}' AND '{$to_date}'";
                $db->write_query($query_delete);
                $deleted_rows = $db->affected_rows();

                $results_messages[] = sprintf($lang->wetter_admin_archive_city_summary ?: "Stadt %s: %d Einträge kopiert, %d Einträge gelöscht.", htmlspecialchars($city_name), $copied_rows, $deleted_rows);
            }
        } else {
            $results_messages[] = $lang->wetter_admin_archive_no_cities ?: "Keine Städte zum Archivieren konfiguriert.";
            $has_errors = true;
        }
        
        // Erfolgs- oder Fehlermeldung ausgeben
        if ($has_errors) {
            flash_message(implode("<br />", $results_messages), "error");
        } else {
            flash_message(implode("<br />", $results_messages), "success");
        }
        // Leite zur selben Seite weiter, um erneutes Absenden per Refresh zu verhindern
        admin_redirect("index.php?module=wetter-archive");

    }
    else
    {
        flash_message($lang->wetter_admin_archive_error_invalid_date_format ?: "Bitte gib ein gültiges Datum im Format JJJJ-MM ein (z. B. 2023-04).", "error");
        admin_redirect("index.php?module=wetter-archive");
    }
}

// Formular zur Eingabe des Archivierungs-Monats
$form = new Form("index.php?module=wetter-archive", "post", "archive_form");
echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

$form_container = new FormContainer($lang->wetter_admin_archive_select_month ?: "Monat zur Archivierung auswählen"); // Neue Sprachvariable
$form_container->output_row(
    ($lang->wetter_admin_archive_label_monthyear ?: "Monat und Jahr (JJJJ-MM):"), 
    ($lang->wetter_admin_archive_label_monthyear_desc ?: "Alle Einträge dieses Monats werden für alle Städte archiviert."), 
    $form->generate_text_box('archive_date', $mybb->input['archive_date'] ?? date("Y-m", strtotime("-1 month")), array('placeholder' => date("Y-m", strtotime("-1 month")), 'style' => 'width: 100px;')), // Standard: letzter Monat
    'archive_date'
);
echo $form_container->end();

$buttons = array();
$buttons[] = $form->generate_submit_button($lang->wetter_admin_archive_button_submit ?: "Ausgewählten Monat archivieren", array('name' => 'archive_now_button'));
echo $form->output_submit_wrapper($buttons);

echo $form->end(); // Formular schließen

$page->output_footer();
?>