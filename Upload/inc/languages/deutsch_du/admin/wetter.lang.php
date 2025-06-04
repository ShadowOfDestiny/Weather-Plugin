<?php
// inc/languages/deutsch_du/admin/wetter.lang.php

// Allgemeine Modul-Titel
$l['wetter_plugin_name'] = "Wetter Plugin"; // Name im Plugin Manager

// Hauptmenüpunkt im ACP (aus module_meta.php)
$l['wetter_admin_title']               = "Wetterverwaltung";
$l['wetter_plugin_description']        = "Plugin um dynamische Wetterdaten für Spielorte zur Verfügung zu stellen";

// Untermenüpunkte / Seitentitel im ACP
$l['wetter_admin_overview_title']      = "Wetterübersicht";
$l['wetter_admin_entry_title']         = "Wettereintrag hinzufügen/bearbeiten";
$l['wetter_admin_settings_title']      = "Wetter Plugin Einstellungen";
$l['wetter_admin_manage_cities_title'] = "Städte verwalten";
$l['wetter_admin_manage_archive_title']= "Wetterarchiv verwalten";
$l['wetter_admin_view_archive_title']  = "Archivierte Daten anzeigen";

// Beschreibungen für die Nav-Tabs
$l['wetter_admin_overview_desc']       = "Übersicht und Verwaltung der eingetragenen Wetterdaten.";
// ... (weitere Beschreibungen für Tabs)

// Einstellungen-Seite (settings.php)
$l['wetter_settings_title']            = "Wetter Plugin Einstellungen"; // Für die Gruppe in den Plugin Settings
$l['wetter_settings_description']      = "Globale Einstellungen für das Wetter-Plugin.";
$l['wetter_setting_active_title']      = "Plugin aktiv?";
$l['wetter_setting_active_desc']       = "Ist das Wetter Plugin aktiv und die Frontend-Seite erreichbar?";
$l['wetter_setting_date_format_title'] = "Datumsformat (Frontend)";
$l['wetter_setting_date_format_desc']  = "PHP Datumsformat für die Anzeige im Frontend (z.B. d.m.Y).";
$l['wetter_setting_active_month_title'] = "Aktive Monate";
$l['wetter_setting_active_month_desc'] = "Bitte mit Komma getrennt eingeben: November,Dezember";
$l['wetter_setting_cities_title']      = "Konfigurierte Städte (CSV)";
$l['wetter_setting_cities_desc']       = "Wird vom ACP-Modul Städte verwalten gepflegt. Hier nicht manuell ändern!";
// ... (weitere Sprachvariablen für ACP-Seiten, Fehlermeldungen, Erfolgsmeldungen etc. aus deiner cities.php, wetter_entry.php etc.)

// Cities.php
$l['wetter_admin_new_city_details']    = "Neue Stadt hinzufügen";
$l['wetter_admin_city_name']           = "Name der Stadt";
$l['wetter_admin_city_name_desc']      = "Gib den Namen der Stadt ein. Für Tabellennamen wird dieser bereinigt.";
$l['wetter_admin_add_city_button']     = "Stadt hinzufügen";
$l['wetter_admin_existing_cities']     = "Vorhandene Städte";
$l['wetter_admin_city_name_column']    = "Stadt";
$l['wetter_admin_actions_column']      = "Aktionen";
$l['wetter_admin_delete']              = "Löschen";
$l['wetter_admin_edit']                = "Bearbeiten";
$l['wetter_admin_no_cities_yet']       = "Noch keine Städte konfiguriert.";
$l['wetter_admin_configured_cities_table_title'] = "Konfigurierte Städte";
$l['wetter_admin_delete_city_confirm'] = "Möchtest du die Stadt '%s' und alle zugehörigen Wetterdaten wirklich löschen?";
$l['wetter_admin_city_added_success']  = "Stadt '%s' erfolgreich hinzugefügt.";
$l['wetter_admin_city_deleted_success']= "Stadt '%s' erfolgreich gelöscht.";
$l['wetter_admin_error_city_name_empty'] = "Der Stadtname darf nicht leer sein.";
$l['wetter_admin_error_city_name_invalid_chars'] = "Der Stadtname ist nach der Bereinigung ungültig oder leer.";
$l['wetter_admin_error_city_already_exists'] = "Die Stadt '%s' existiert bereits.";
$l['wetter_admin_error_setting_group_not_found'] = "Die Einstellungsgruppe des Wetter-Plugins wurde nicht gefunden.";
$l['wetter_admin_error_creating_tables'] = "Fehler beim Erstellen der Tabellen für die Stadt '%s'.";
$l['wetter_admin_error_no_city_to_delete'] = "Keine Stadt zum Löschen ausgewählt.";

// wetter_entry.php
$l['wetter_admin_add_data_title']         = "Wettereintrag hinzufügen"; // Für Nav-Tab
$l['wetter_admin_entry_details']          = "Details des Wettereintrags";
$l['wetter_admin_city']                   = "Stadt";
$l['wetter_admin_please_add_cities_first']= "Bitte lege zuerst Städte an.";
$l['wetter_admin_date']                   = "Datum";
$l['wetter_admin_date_desc']              = "Format: YYYY-MM-DD";
$l['wetter_admin_timeslot']               = "Zeitspanne";
$l['wetter_admin_icon_class']             = "Icon (CSS-Klasse)";
$l['wetter_admin_icon_class_desc']        = "Wähle ein Icon (z.B. wi-day-sunny).";
$l['wetter_admin_select_icon_button']     = "Icon auswählen...";
$l['wetter_admin_select_an_icon']         = "Wähle ein Icon";
$l['wetter_admin_close_picker_button']    = "Schließen";
$l['wetter_admin_temperature']            = "Temperatur (°C)";
$l['wetter_admin_condition']              = "Wetterlage";
$l['wetter_admin_sunrise']                = "Auf: Sonne, Mond";
$l['wetter_admin_sunset']                 = "Unter: Sonne, Mond";
$l['wetter_admin_time_desc']              = "Format: HH:MM";
$l['wetter_admin_moonphase']              = "Mondphase";
$l['wetter_admin_winddirection']          = "Windrichtung";
$l['wetter_admin_windspeed']              = "Windstärke (km/h)";
$l['wetter_admin_is_archive']             = "Für Archiv?";
$l['wetter_admin_save_entry']             = "Eintrag speichern";
$l['wetter_admin_error_no_city']          = "Bitte wähle eine Stadt.";
$l['wetter_admin_error_invalid_date']     = "Ungültiges Datumsformat.";
$l['wetter_admin_error_no_icon_selected'] = "Bitte wähle ein Icon.";
$l['wetter_admin_error_table_not_exist_for_city'] = "Datentabelle für Stadt '%s' existiert nicht.";
$l['wetter_admin_data_added_success']     = "Wetterdaten erfolgreich hinzugefügt.";
$l['wetter_admin_error_invalid_time_sunrise'] = "Ungültiges Zeitformat für Sonnenaufgang (erwartet HH:MM).";
$l['wetter_admin_error_invalid_time_sunset']  = "Ungültiges Zeitformat für Sonnenuntergang (erwartet HH:MM).";
$l['wetter_admin_entry_deleted_success']  = "Wettereintrag ID %d aus %s erfolgreich gelöscht.";
$l['wetter_admin_table_not_exist_for_delete'] = "Tabelle für %s nicht gefunden. Eintrag konnte nicht gelöscht werden.";

// wetter_edit.php
$l['wetter_admin_edit_entry_title']     		= "Wettereintrag bearbeiten.";
$l['wetter_admin_edit_entry_details']     		= "Details des Wettereintrags bearbeiten.";
$l['wetter_admin_entry_updated_success']    	= "Wettereintrag erfolgreich aktualisiert.";
$l['wetter_admin_error_invalid_edit_params']    = "Ungültige Parameter zum Bearbeiten angegeben.";
$l['wetter_admin_error_entry_not_found']     	= "Angeforderten Wettereintrag nicht gefunden.";
$l['wetter_admin_update_entry_button']     		= "Änderungen speichern.";

// overview.php
$l['wetter_admin_overview_title'] 						= "Wetterübersicht";
$l['wetter_admin_overview_desc'] 						= "Hier kannst du die Wetterdaten für verschiedene Städte verwalten.";
$l['wetter_admin_no_cities_configured_overview'] 		= "Es sind noch keine Städte in den Plugin-Einstellungen konfiguriert. Bitte füge zuerst Städte über die <a href=\"index.php?module=wetter-cities\">Städteverwaltung</a> hinzu.";
$l['wetter_admin_no_city_selected_overview'] 			= "Keine Stadt ausgewählt oder konfiguriert.";
$l['wetter_admin_select_city_label'] 					= "Stadt anzeigen:";
$l['wetter_admin_show_button'] 							= "Anzeigen";
$l['wetter_admin_view_type_label'] 						= "Daten anzeigen:";
$l['wetter_admin_view_current'] 						= "Aktuell";
$l['wetter_admin_view_archive'] 						= "Archiv";
$l['wetter_admin_add_entry_for_city_button'] 			= "Neuen Eintrag für %s hinzufügen";
$l['wetter_admin_table_not_exist_overview_detailed'] 	= "Hinweis: Die Tabelle <strong>%s</strong> für die Stadt <strong>%s</strong> (%s) existiert noch nicht. Es können keine Daten angezeigt werden.";
$l['wetter_admin_archive_view_short'] 					= "Archiv";
$l['wetter_admin_current_view_short'] 					= "Aktuell";
$l['wetter_admin_timespan'] = "Zeitspanne";
$l['wetter_admin_icon'] = "Icon";
$l['wetter_admin_weathercondition'] = "Wetterlage";
$l['wetter_admin_actions'] = "Aktionen";
$l['wetter_admin_edit_link'] = "Bearbeiten";
$l['wetter_admin_delete_link'] = "Löschen";
$l['wetter_admin_delete_confirm_entry'] = "Diesen Wettereintrag wirklich löschen?";
$l['wetter_admin_no_data_for_city_table'] = "Keine Wetterdaten für %s gefunden.";
$l['wetter_admin_table_creation_hint_in_table_detailed'] = "Tabelle für %s (%s) nicht vorhanden oder leer.";
$l['wetter_admin_weather_data_for_city_title_detailed'] = "Wetterdaten für: %s (%s)";

// Für settings.php (NEU)
$l['wetter_setting_items_per_page_frontend_title'] = "Einträge pro Seite (Frontend)";
$l['wetter_setting_items_per_page_frontend_desc'] = "Anzahl der Wettereinträge, die pro Seite im Frontend angezeigt werden (0 für keine Paginierung oder um das Standardlimit der Abfrage zu nutzen).";
$l['wetter_setting_items_per_page_acp_title'] = "Einträge pro Seite (ACP)";
$l['wetter_setting_items_per_page_acp_desc'] = "Anzahl der Wettereinträge, die pro Seite in der ACP Übersicht (Aktuell & Archiv Ansicht) angezeigt werden.";
$l['wetter_setting_version_title'] = "Wetter Plugin Version (intern)"; // Falls noch nicht vorhanden
$l['wetter_setting_version_desc'] = "Speichert die aktuell installierte Version des Wetter Plugins. Nicht manuell ändern."; // Falls noch nicht vorhanden

// Am Ende deiner admin/wetter.lang.php hinzufügen:
$l['wetter_perm_can_manage_overview'] = "Kann Wetterübersicht verwalten?";
$l['wetter_perm_can_manage_cities'] = "Kann Städte verwalten?";
$l['wetter_perm_can_manage_entries'] = "Kann Wettereinträge verwalten?";
$l['wetter_perm_can_manage_archive'] = "Kann Wetterarchiv verwalten?";
$l['wetter_perm_can_manage_settings'] = "Kann Wetter-Plugin Einstellungen verwalten?";
// (Füge hier ALLE benötigten Sprachvariablen für deine ACP-Module ein)
?>