<?php
// inc/languages/deutsch_du/wetter.lang.php
$l['nav_wetter'] = "Wetterliste";
$l['wetter_title_default'] = "Wetterübersicht"; // Für den <title> und Breadcrumb
$l['wetter_plugin_disabled_frontend'] = "Das Wetter-Plugin ist derzeit deaktiviert.";
$l['wetter_nav_select_city_label'] = "Stadt auswählen:"; // Ist vielleicht schon vorhanden
$l['wetter_nav_overview_link_text'] = "Alle Städte anzeigen"; // Text für die Option "Alle Städte" im Dropdown
$l['wetter_nav_filter_by_date_label'] = "Nach Datum filtern:";
$l['wetter_nav_filter_button'] = "Anzeigen"; // Button-Text für das Absenden der Filter
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
$l['wetter_label_sunrise']    = "Auf: Sonne, Mond";
$l['wetter_label_sunset']     = "Unter: Sonne, Mond";
$l['wetter_label_moonphase']  = "Mondphase";
$l['wetter_label_winddirection'] = "Windrichtung";
$l['wetter_label_windspeed']  = "Windstärke";
$l['wetter_label_icon']       = "Icon";

// Für Breadcrumbs/Titel, wenn eine Stadt ausgewählt ist
$l['wetter_archive_short']    = "Archiv";
$l['wetter_current_short']    = "Aktuell";
$l['wetter_breadcrumb_city_view'] = "%s (%s)"; // %1$s = Stadt, %2$s = Ansicht (Aktuell/Archiv)

// Für Paginierung im Frontend (rootwetter.php)
// MyBB's multipage() Funktion verwendet oft globale Sprachvariablen.
// Hier sind Beispiele, falls du sie anpassen oder spezifisch machen möchtest:
$l['wetter_pagination_prev'] = "&laquo; Vorherige";
$l['wetter_pagination_next'] = "Nächste &raquo;";
$l['wetter_pagination_page'] = "Seite";
$l['wetter_pagination_of'] = "von"; // z.B. "Seite X von Y"

// Für die Tabellenüberschriften und Seitenüberschriften
$l['wetter_page_title_default'] = "Wetterinformationen"; // Ist vielleicht schon vorhanden
$l['wetter_label_city'] = "Stadt"; // Neuer Tabellenkopf
$l['wetter_headline_all_cities'] = "Wetterdaten für alle Städte"; // Überschrift, wenn alle Städte angezeigt werden
$l['wetter_headline_city_specific'] = "Wetterdaten für %s"; // %s wird durch den Stadtnamen ersetzt
$l['wetter_headline_filtered_by_date'] = " (gefiltert nach %s)"; // %s wird durch das Datum ersetzt
$l['wetter_view_current'] = "Aktuell"; // Für die Anzeige des Ansichtstyps (aktuell/archiv)
$l['wetter_view_archive'] = "Archiv"; // Für die Anzeige des Ansichtstyps (aktuell/archiv)
$l['wetter_show_current_link'] = "Aktuelle anzeigen";
$l['wetter_show_archive_link'] = "Archiv anzeigen";

// Spaltenköpfe (die meisten sind vermutlich schon da)
$l['wetter_label_timeslot'] = "Zeitfenster";

?>