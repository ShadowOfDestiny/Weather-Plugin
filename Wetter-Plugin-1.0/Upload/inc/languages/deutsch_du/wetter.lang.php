<?php
// inc/languages/deutsch_du/wetter.lang.php

$l['wetter_title_default'] = "Wetterübersicht"; // Für den <title> und Breadcrumb
$l['wetter_plugin_disabled_frontend'] = "Das Wetter-Plugin ist derzeit deaktiviert.";
$l['wetter_nav_select_city_label'] = "Stadt wählen:";
$l['wetter_nav_overview_link_text'] = "Alle Städte"; // Oder was hier passend ist
$l['wetter_show_current_link'] = "Aktuelle Daten anzeigen";
$l['wetter_show_archive_link'] = "Archivierte Daten anzeigen";
$l['wetter_invalid_date_format_frontend'] = "Ungültiges Datumsformat.";
$l['wetter_message_no_cities_configured_frontend'] = "Es sind keine Städte für die Wetteranzeige konfiguriert.";
$l['wetter_message_no_data_for_city_view'] = "Keine %s Wetterdaten für %s gefunden."; // %1$s = Ansicht (aktuell/archiv), %2$s = Stadt
$l['wetter_archived_data_term'] = "archivierte"; // Für %1$s in obiger Variable
$l['wetter_current_data_term'] = "aktuelle"; // Für %1$s in obiger Variable


// Beibehalten aus deiner alten wetter.lang.php und angepasst für die neue Frontend-Seite
$l['wetter_main_title']       = "Wetter"; // Wird im Template wetterpage_nav (jetzt wetter_nav) verwendet
$l['wetter_heading']          = "Wetterdaten"; // Allgemeine Überschrift, falls benötigt
$l['wetter_page_title']       = "Wetter"; // Fallback für <title> wenn $page_title_for_template nicht spezifischer ist
$l['wetter_main_desc']        = "Hier findest du aktuelle Wetterdaten sowie Archivdaten, sortiert nach Stadt."; // Kann auf der Seite angezeigt werden
$l['wetter_breadcrumb']       = "Wetter"; // Haupt-Breadcrumb-Name

$l['wetter_label_date']       = "Datum";
$l['wetter_label_timeslot']   = "Zeitspanne";
$l['wetter_label_temp']       = "Temperatur";
$l['wetter_label_condition']  = "Wetterlage";
$l['wetter_label_sunrise']    = "Sonnenaufgang";
$l['wetter_label_sunset']     = "Sonnenuntergang";
$l['wetter_label_moonphase']  = "Mondphase";
$l['wetter_label_winddirection'] = "Windrichtung";
$l['wetter_label_windspeed']  = "Windstärke";
$l['wetter_label_icon']       = "Icon";

// Für Breadcrumbs/Titel, wenn eine Stadt ausgewählt ist
$l['wetter_archive_short']    = "Archiv";
$l['wetter_current_short']    = "Aktuell";
$l['wetter_breadcrumb_city_view'] = "%s (%s)"; // %1$s = Stadt, %2$s = Ansicht (Aktuell/Archiv)
?>