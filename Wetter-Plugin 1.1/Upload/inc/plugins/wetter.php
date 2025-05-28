<?php
// Sicherstellen, dass die Datei nicht direkt aufgerufen wird
if(!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

if(defined('IN_ADMINCP')) {
    // ACP-spezifische Hooks
	$plugins->add_hook("admin_load", "wetter_admin_load_check");
	$plugins->add_hook("admin_page_output_header", "wetter_output_acp_css"); 
	$plugins->add_hook("admin_page_output_footer", "wetter_output_acp_js");   
}

// Diese Funktion wird von admin_load aufgerufen, um $wetter_load_css zu setzen
// Sie muss VOR wetter_output_acp_css und wetter_output_acp_js ausgeführt werden.
function wetter_admin_load_check() {
    global $mybb, $wetter_load_css; // $wetter_load_css global machen oder als Rückgabewert/Parameter übergeben
    if (isset($mybb->input['module']) && strpos($mybb->input['module'], 'wetter') === 0) {
        $wetter_load_css = true; // Diese Variable muss in den anderen Funktionen zugänglich sein
    }
}

// Deine CSS-Ausgabefunktion (Beispiel)
function wetter_output_acp_css($page_obj) {
    global $mybb, $wetter_load_css;
    if ($wetter_load_css) {
        $plugin_info = wetter_info();
        $css_file_name = 'weather-icons.min.css'; // Korrigiert nach deiner letzten Info
        $css_url = $mybb->settings['bburl'] . '/images/wetter/css/' . $css_file_name;
        $css_url .= '?v=' . (isset($plugin_info['version']) ? $plugin_info['version'] : time());

        if (is_object($page_obj) && method_exists($page_obj, 'add_header_stylesheet')) {
            $page_obj->add_header_stylesheet($css_url);
        }
    }
    return $page_obj;
}

// Deine JS-Ausgabefunktion
function wetter_output_acp_js($page_obj) { // oder ohne $page_obj, falls nicht benötigt
    global $mybb, $wetter_load_css; 
    if ($wetter_load_css) { 
        $plugin_info = wetter_info(); 
        $js_file_url = $mybb->settings['bburl'] . '/jscripts/wetter_icon_picker.js';
        $js_file_url .= '?v=' . (isset($plugin_info['version']) ? $plugin_info['version'] : time()); 
        echo '<script type="text/javascript" src="' . htmlspecialchars($js_file_url) . '"></script>';
    }
}

// Minimale Plugin Informationen
function wetter_info() {
    global $lang;
    $lang->load("wetter", false, true); // Lädt admin/wetter.lang.php für die Beschreibung im ACP

    return array(
        "name"          => "Wetter Plugin",
        "description"   => "Zeigt Wetterdaten für konfigurierte Städte auf einer eigenen Seite an und verwaltet diese im ACP.",
        "website"       => "https://shadow.or.at/index.php",
        "author"        => "Dani",
        "authorsite"    => "https://github.com/ShadowOfDestiny",
        "version"       => "1.1",
        "compatibility" => "18*",
        "guid"          => "", // Optional: Eindeutige GUID für MyBB.com
    );
}

// Plugin Installation
function wetter_install() {
    global $db, $mybb, $lang;

    if(!isset($lang->wetter_settings_title)) {
        $lang->load("wetter", false, true);
    }

    // 1. Einstellungsgruppe erstellen
    $setting_group = array(
        "name"        => "wetter_plugin_settings",
        "title"       => $lang->wetter_settings_title ?: "Wetter Plugin Einstellungen",
        "description" => $lang->wetter_settings_description ?: "Globale Einstellungen für das Wetter-Plugin.",
        "disporder"   => 80, // Passende Anzeigereihenfolge wählen
        "isdefault"   => 0
    );
    $gid = $db->insert_query("settinggroups", $setting_group);

    // 2. Einstellungen definieren und einfügen

    $setting_array = array(
        "wetter_plugin_active" => array(
            "title"       => $lang->wetter_setting_active_title ?: "Plugin aktiv?",
            "description" => $lang->wetter_setting_active_desc ?: "Ist das Wetter Plugin aktiv und die Frontend-Seite erreichbar?",
            "optionscode" => "yesno",
            "value"       => "1", // Standard: Aktiviert
            "disporder"   => 1
        ),
        "wetter_plugin_date_format" => array(
            "title"       => $lang->wetter_setting_date_format_title ?: "Datumsformat (Frontend)",
            "description" => $lang->wetter_setting_date_format_desc ?: "PHP Datumsformat für die Anzeige im Frontend (z.B. d.m.Y).",
            "optionscode" => "text",
            "value"       => "d.m.Y",
            "disporder"   => 2
        ),
        "wetter_plugin_active_months" => array(
            "title"       => $lang->wetter_setting_active_month_title ?: "Aktive Monate (CSV)",
            "description" => $lang->wetter_setting_active_month_desc ?: "Komma-getrennte Liste der Monate. Für nicht gelistete Monate wird das Archiv verwendet.",
            "optionscode" => "text",
            "value"       => "", 
            "disporder"   => 3
        ),
        "wetter_plugin_cities" => array(
            "title"       => $lang->wetter_setting_cities_title ?: "Konfigurierte Staedte (CSV)",
            "description" => $lang->wetter_setting_cities_desc ?: "Wird vom ACP-Modul Staedte verwalten gepflegt. Hier nicht manuell ändern!",
            "optionscode" => "text",
            "value"       => "",     // Standard: Leer, Staedte werden im ACP hinzugefügt
            "disporder"   => 4
        )
            "wetter_plugin_version" => array( // Hier die Versionseinstellung integriert
            "title"       => "Wetter Plugin Version (intern)",
            "description" => "Speichert die aktuell installierte Version des Wetter Plugins. Nicht manuell ändern.",
            "optionscode" => "text",
            "value"       => $db->escape_string($current_plugin_version), // Setzt den Wert auf "1.1"
            "disporder"   => 99,
            "visibility"  => 0
        )
    );

    foreach($setting_array as $name => $setting) { // Verwende $settings_to_add
        $setting['name'] = $name;
        $setting['gid'] = $gid;
        $db->insert_query('settings', $setting);
    }
    rebuild_settings(); // Einmal am Ende reicht

     // 3. Templates erstellen (Frontend-Seite)
    $templategroup_frontend = array(
        "prefix" => "wetter", // Eindeutiger Prefix für Frontend-Templates
        "title"  => "Wetter Frontend",
    );
    $db->insert_query("templategroups", $templategroup_frontend);

     $templates_frontend = array(); 
	
    $templates_frontend[] = array(
        "title" => "wetter_main",
        "template" => $db->escape_string('<html>
<head>
    <title>{$mybb->settings[\'bbname\']} - {$lang->wetter_page_title}</title>
    {$headerinclude}
    <link rel="stylesheet" href="{$mybb->settings[\'bburl\']}/images/wetter/css/weather-icons.min.css?v={$wetter_version}" type="text/css" />
</head>
<body>
    {$header}
    {$wetter_navigation_output}
    <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="width: 100%; margin: auto;">
        <thead>
            <tr>
                <td class="thead" colspan="11"><strong>{$headline_text_for_page}</strong></td>
            </tr>
            <tr>
				<td class="tcat" width="12%"><span class="smalltext"><strong><center>{$lang->wetter_label_city}</center></strong></span></td>
				<td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_date}</center></strong></span></td>
				<td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_timeslot}</center></strong></span></td>
				<td class="tcat" width="5%"><span class="smalltext"><strong><center>{$lang->wetter_label_icon}</center></strong></span></td>
				<td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_temperature}</center></strong></span></td>
				<td class="tcat" width="15%"><span class="smalltext"><strong><center>{$lang->wetter_label_condition}</center></strong></span></td>
				<td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_sunrise}</center></strong></span></td>
				<td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_sunset}</center></strong></span></td>
				<td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_moonphase}</center></strong></span></td>
				<td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_winddirection}</center></strong></span></td>
				<td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_windspeed}</center></strong></span></td>
            </tr>
        </thead>
        <tbody>
            {$wetter_table_rows_content}
        </tbody>
    </table>
    {$footer}
</body>
</html>'),
        "sid" => -2, "version" => "1.1", "dateline" => TIME_NOW
    );
    $templates_frontend[] = array(
        "title" => "wetter_nav",
        "template" => $db->escape_string('<form method="get" action="wetter.php" class="wetter_filter_bar">
    <strong style="margin-right: 5px;">{$lang->wetter_nav_select_city_label}</strong>
    <select name="stadt" style="padding: 6px; border: 1px solid #ccc; border-radius: 3px;">
        <option value="">{$lang->wetter_nav_overview_link_text}</option>
        {$city_options_html} </select>

    <strong style="margin-left: 15px; margin-right: 5px;">{$lang->wetter_nav_filter_by_date_label}</strong>
    <input type="date" name="filter_datum" value="{$filter_datum_html}" style="padding: 5px; border: 1px solid #ccc; border-radius: 3px;">

    <input type="submit" value="{$lang->wetter_nav_filter_button}" class="button" style="margin-left: 10px;">

    <span style="margin-left: 20px; padding-left: 20px; border-left: 1px solid #ccc;">
        {$archive_toggle_link_html} </span>
</form>'),
        "sid" => -2, "version" => "1.1", "dateline" => TIME_NOW
    );
    $templates_frontend[] = array(
        "title" => "wetter_data_row",
        "template" => $db->escape_string('<tr class="{$alt_row_class_name}">
			<td><center>{$wetter_entry_data[\'city_name_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'datum_formatiert\']}</center></td>
            <td><center>{$wetter_entry_data[\'zeitspanne_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'icon_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'temperatur_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'wetterlage_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'sonnenaufgang_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'sonnenuntergang_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'mondphase_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'windrichtung_html\']}</center></td>
            <td><center>{$wetter_entry_data[\'windstaerke_html\']}</center></td>
</tr>'),
        "sid" => -2, "version" => "1.1", "dateline" => TIME_NOW
    );
    $templates_frontend[] = array(
        "title" => "wetter_no_data",
        "template" => $db->escape_string('<tr><td colspan="11" style="text-align:center;">{$no_data_message}</td></tr>'),
        "sid" => -2, "version" => "1.1", "dateline" => TIME_NOW
    );

    foreach($templates_frontend as $template) {
        $db->insert_query("templates", $template);
    }

    // 4. Stylesheet installieren
    $css_name_check = 'wetter.css';
    $css_tid_check = 1; // Master Theme

    $query_check = $db->simple_select(
        "themestylesheets",
        "sid",
        "name='".$db->escape_string($css_name_check)."' AND tid='".(int)$css_tid_check."'",
        array("limit" => 1)
    );
    $existing_stylesheet_check = $db->fetch_array($query_check);

    // Korrigierte Logik: Stylesheet nur erstellen, wenn es noch NICHT existiert.
    if(!$existing_stylesheet_check) { // ACHTUNG: Das Ausrufezeichen ist hier wichtig!
        $css_content_string = ":root {
            --wetter-background-color: #f0f0f0;
            --wetter-primary-color: #007acc;
            --wetter-secondary-color: #dddddd;
            --wetter-text-color: #333333;
        }
        .wetter_filter_bar {
            text-align: center; margin: 10px 0; padding: 10px;
            background: #e0e0e0; border: 1px solid #c0c0c0; border-radius: 4px;
        }
        .weather {
            max-width: 1080px; margin: 0 auto;
            background-color: var(--wetter-background-color); padding: 20px;
        }
        .weather-entry {
            border: 1px solid var(--wetter-secondary-color); padding: 15px;
            margin-bottom: 20px; background-color: #fff;
        }
        .weather-entry h2 {
            color: var(--wetter-primary-color); margin-top: 0;
        }
        .weather-entry p {
            color: var(--wetter-text-color);
        }"; // Ende des CSS-Strings

        $css = array(
            'name'         => $css_name_check,
            'tid'          => (int)$css_tid_check,
            'attachedto'   => '',
            "stylesheet"   => $db->escape_string($css_content_string), // CSS-Inhalt escapen
            'cachefile'    => 'wetter.css', // Einfacher Name hier
            'lastmodified' => time()
        );
    
        require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
        $sid = $db->insert_query("themestylesheets", $css);
        // Diese Zeile ist gut, um den cachefile-Namen direkt korrekt zu setzen:
        $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . (int)$sid), "sid = '" . (int)$sid . "'", 1);
    
        $tids = $db->simple_select("themes", "tid");
        while ($theme = $db->fetch_array($tids)) {
            update_theme_stylesheet_list((int)$theme['tid']);
        }
    } // Ende if(!$existing_stylesheet_check)
} // Ende wetter_install()

// Plugin ist installiert? (Prüft, ob das Plugin installiert ist)
function wetter_is_installed() {
    global $mybb;
    // Eine einfache Prüfung: Gibt es die von uns erstellte Einstellungsgruppe?
    if(isset($mybb->settings['wetter_plugin_active'])) { // Oder eine andere zentrale Einstellung deines Plugins
        return true;
    }
    return false;
}

// Plugin Deinstallation
function wetter_uninstall() {
    global $db, $mybb;

    // 1. Dynamisch erstellte Stadttabellen löschen
    // Diese Logik sollte aus deiner cities.php adaptiert werden
    if (isset($mybb->settings['wetter_plugin_cities'])) {
        $cities_array = wetter_helper_get_cities_array_from_string($mybb->settings['wetter_plugin_cities']);
        if (!empty($cities_array)) {
            foreach ($cities_array as $city_name) {
                $city_suffix = wetter_sanitize_city_name_for_table($city_name);
                if (!empty($city_suffix)) {
                    $table_main_no_prefix = "wetter_". $city_suffix;
                    $table_archive_no_prefix = $table_main_no_prefix. "_archiv";
                    if ($db->table_exists($table_main_no_prefix)) $db->drop_table($table_main_no_prefix);
                    if ($db->table_exists($table_archive_no_prefix)) $db->drop_table($table_archive_no_prefix);
                }
            }
        }
    }

    // 2. Einstellungen löschen
    $db->delete_query('settings', "name LIKE 'wetter_plugin_%'");
    $db->delete_query('settinggroups', "name = 'wetter_plugin_settings'");
    rebuild_settings();

    // 3. Templates und Template-Gruppe löschen
    $db->delete_query("templates", "title LIKE 'wetter%' AND sid='-2'");
    $db->delete_query("templategroups", "prefix = 'wetter'");
}

// Plugin Aktivierung
function wetter_activate() {
    global $db, $plugins, $mybb;
	
	if (is_object($plugins)) { // Prüfe, ob $plugins ein Objekt ist
    $plugins->add_hook("admin_page_output_header", "wetter_add_acp_icon_picker_js");
}
    // Hier könnten z.B. Überprüfungen stattfinden oder Caches geleert werden
    // Sicherstellen, dass alle Tabellen für konfigurierte Städte existieren
    wetter_helper_check_and_create_all_city_tables();
}

// Plugin Deaktivierung
function wetter_deactivate() {
    global $db, $plugins, $mybb; // $plugins global machen, $db und $mybb sind oft auch nützlich hier

    if (is_object($plugins)) { // Prüfe, ob $plugins ein Objekt ist
        $plugins->remove_hook("admin_page_output_header", "wetter_add_acp_icon_picker_js"); // Hook entfernen!
    }
    // Hier könnten z.B. Caches geleert werden
}

function wetter_upgrade() {
    global $db, $mybb;

    $plugin_info = wetter_info();
    $new_version_code = $plugin_info['version']; // Ist "1.1"

    $old_version_code = $mybb->settings['wetter_plugin_version'] ?? '0';

    if (version_compare($old_version_code, $new_version_code, '<')) {

        // --- UPGRADE AUF VERSION 1.1 (von Versionen < 1.1) ---
        if (version_compare($old_version_code, '1.1', '<')) {
            // Wenn dies das erste Mal ist, dass die Upgrade-Funktion mit Version 1.1 läuft,
            // und die alte Version (aus der DB oder '0') kleiner als 1.1 ist:

            // 1. Stelle sicher, dass die Versionseinstellung existiert (falls sie aus irgendeinem Grund fehlt)
            $query_version_setting = $db->simple_select("settings", "name", "name='wetter_plugin_version'");
            if($db->num_rows($query_version_setting) == 0) {
                // Die Einstellung fehlt, füge sie hinzu (hole GID der Gruppe)
                $gid_query = $db->simple_select("settinggroups", "gid", "name='wetter_plugin_settings'", array("limit" => 1));
                $setting_group_data = $db->fetch_array($gid_query);
                if($setting_group_data['gid']) {
                    $version_setting_data = array(
                        "name"        => "wetter_plugin_version",
                        "title"       => "Wetter Plugin Version (intern)",
                        "description" => "Speichert die aktuell installierte Version des Wetter Plugins. Nicht manuell ändern.",
                        "optionscode" => "text",
                        "value"       => "1.0", // Setze es initial auf eine Version vor 1.1, wird am Ende auf 1.1 aktualisiert
                        "disporder"   => 99,
                        "visibility"  => 0,
                        "gid"         => (int)$setting_group_data['gid']
                    );
                    $db->insert_query("settings", $version_setting_data);
                    // rebuild_settings(); // Wird am Ende der Upgrade-Funktion sowieso gemacht
                }
            }

            // 2. Aktualisiere Templates auf Version 1.1, falls nötig
            // Hole die Template-Inhalte für Version 1.1 (so wie sie in wetter_install() definiert sind)
            // Beachte: Die 'version' in den Template-Arrays in wetter_install() sollte auch "1.1" sein.
            $templates_to_update = array();
            $templates_to_update['wetter_main'] = array(
                "template" => $db->escape_string('<html>... dein aktueller wetter_main HTML Code für V1.1 ...</html>'),
                "version"  => "1.1"
            );
            $templates_to_update['wetter_nav'] = array(
                "template" => $db->escape_string('<form method="get" action="wetter.php" class="wetter_filter_bar">...</form>'),
                "version"  => "1.1"
            );
            $templates_to_update['wetter_data_row'] = array(
                "template" => $db->escape_string('<tr class="{$alt_row_class_name}">...</tr>'),
                "version"  => "1.1"
            );
            $templates_to_update['wetter_no_data'] = array(
                "template" => $db->escape_string('<tr><td colspan="11" style="text-align:center;">{$no_data_message}</td></tr>'),
                "version"  => "1.1"
            );

            foreach($templates_to_update as $title => $data) {
                $db->update_query("templates", $data, "title='".$db->escape_string($title)."' AND sid='-2'");
            }
            // Es ist nicht unbedingt nötig, find_replace_templatesets hier auszuführen,
            // da die globalen Templates (sid=-2) direkt aktualisiert werden.
            // Die Änderungen werden beim nächsten Laden der Templates wirksam.
        }

        // --- UPGRADE AUF ZUKÜNFTIGE VERSION 1.2 (von Versionen < 1.2) ---
        // if (version_compare($old_version_code, '1.2', '<')) {
        //     // Aktionen für Upgrade auf 1.2 (z.B. neue Einstellung für Paginierung)
        //     // $gid_wetter = ... (hole gid) ...
        //     // $setting_pagination = array( ... );
        //     // if($db->num_rows($db->simple_select("settings", "name", "name='wetter_plugin_items_per_page'")) == 0) {
        //     //    $db->insert_query("settings", $setting_pagination);
        //     // }
        // }

        // Am Ende aller spezifischen Upgrade-Schritte:
        // Die Versionsnummer in den Einstellungen auf die NEUE Version setzen.
        $db->update_query("settings", array("value" => $db->escape_string($new_version_code)), "name = 'wetter_plugin_version'");
        rebuild_settings();
    }
}

// --- HELFERFUNKTIONEN ---

// Sanitize city name for table suffix
function wetter_sanitize_city_name_for_table($city_name_raw) {
    if (empty($city_name_raw)) return '';
    $sanitized_name = strtolower(trim((string)$city_name_raw));
    $sanitized_name = preg_replace('/\s+/', '_', $sanitized_name); // Leerzeichen durch Unterstriche ersetzen
    $sanitized_name = preg_replace('/[^a-z0-9_]/', '', $sanitized_name); // Nur alphanumerische Zeichen und Unterstriche erlauben
    $sanitized_name = substr($sanitized_name, 0, 30); // Auf max. 30 Zeichen kürzen
    return $sanitized_name;
}

// Get cities array from settings string
function wetter_helper_get_cities_array_from_string($cities_setting_string = null) {
    global $mybb;
    if ($cities_setting_string === null) {
        if(!isset($mybb->settings['wetter_plugin_cities']) || empty($mybb->settings['wetter_plugin_cities'])) {
            return array();
        }
        $cities_setting_string = $mybb->settings['wetter_plugin_cities'];
    }
    if (empty($cities_setting_string)) return array();
    $cities_array = array_map('trim', explode(",", $cities_setting_string));
    return array_values(array_filter($cities_array, function($city_value) { return !empty($city_value); }));
}

// Create tables for a city
function wetter_create_tables_for_city($city_suffix_clean) {
    global $db;
    if (empty($city_suffix_clean)) {
        // error_log("Wetter Plugin: Versuch, Tabellen für leeren Stadt-Suffix ('{$city_suffix_clean}') zu erstellen.");
        return false;
    }

    $table_name_main_no_prefix    = "wetter_". $city_suffix_clean;
    $table_name_archive_no_prefix = $table_name_main_no_prefix. "_archiv";

    // Struktur der Wetterdatentabelle (aus deiner wetter_entry.php und ACP-Übersicht abgeleitet)
    $sql_table_structure = " (
        `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        `datum` DATE NOT NULL,
        `zeitspanne` VARCHAR(5) NOT NULL,
        `icon` VARCHAR(100) NOT NULL DEFAULT 'wi-na',
        `temperatur` VARCHAR(10) NOT NULL DEFAULT '0',
        `wetterlage` VARCHAR(255) NOT NULL DEFAULT '',
        `sonnenaufgang` TIME DEFAULT NULL,
        `sonnenuntergang` TIME DEFAULT NULL,
        `mondphase` VARCHAR(100) DEFAULT NULL,
        `windrichtung` VARCHAR(50) DEFAULT NULL,
        `windstaerke` VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_datum_zeitspanne` (`datum`, `zeitspanne`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

    $success_main = true;
    $success_archive = true;

    if (!$db->table_exists($table_name_main_no_prefix)) {
        if (!$db->write_query("CREATE TABLE IF NOT EXISTS `". TABLE_PREFIX . $table_name_main_no_prefix . "`" . $sql_table_structure)) {
            $success_main = false;
            // error_log("Wetter Plugin Fehler: Konnte Haupttabelle nicht erstellen: ". TABLE_PREFIX. $table_name_main_no_prefix. " - MySQL Fehler: ". $db->error_string());
        }
    }

    if ($success_main && !$db->table_exists($table_name_archive_no_prefix)) {
        if (!$db->write_query("CREATE TABLE IF NOT EXISTS `". TABLE_PREFIX . $table_name_archive_no_prefix . "`" . $sql_table_structure)) {
            $success_archive = false;
            // error_log("Wetter Plugin Fehler: Konnte Archivtabelle nicht erstellen: ". TABLE_PREFIX. $table_name_archive_no_prefix. " - MySQL Fehler: ". $db->error_string());
        }
    }
    return ($success_main && $success_archive);
}

// Check and create tables for all configured cities
function wetter_helper_check_and_create_all_city_tables() {
    global $mybb;
    $configured_cities_list = wetter_helper_get_cities_array_from_string();
    if (!empty($configured_cities_list)) {
        foreach ($configured_cities_list as $city_name_to_check) {
            $city_suffix_for_table = wetter_sanitize_city_name_for_table($city_name_to_check);
            if (!empty($city_suffix_for_table)) {
                wetter_create_tables_for_city($city_suffix_for_table);
            }
        }
    }
}

function wetter_helper_get_weather_data($city_name_original_or_all, $fetch_from_archive = false, $limit = 0, $filter_datum_raw = null) {
    global $db, $mybb; // $mybb hier hinzufügen, falls du z.B. Plugin-Einstellungen hier auslesen müsstest

    $all_weather_data_collected = array(); // Hier sammeln wir alle Ergebnisse

    // 1. Bestimme, für welche Städte Daten geholt werden sollen
    $cities_to_process = array();
    if ($city_name_original_or_all == '__ALL__') {
        $cities_to_process = wetter_helper_get_cities_array_from_string(); // Deine Helferfunktion, die alle konfigurierten Städte liefert
        if (empty($cities_to_process)) {
            // error_log("Wetter Plugin: Keine Städte konfiguriert für __ALL__ Ansicht."); // Optional: Logging
            return array(); // Keine Städte da, also keine Daten
        }
    } elseif (!empty($city_name_original_or_all)) {
        // Nur eine spezifische Stadt verarbeiten
        $cities_to_process[] = $city_name_original_or_all;
    } else {
        // Kein gültiger Stadtname oder '__ALL__' übergeben
        return array();
    }

    // 2. Bereite die SQL-Bedingung für den Datumsfilter vor
    $date_condition_sql = "";
    if (!empty($filter_datum_raw) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $filter_datum_raw)) {
        // Stelle sicher, dass das Datum korrekt escaped wird, um SQL-Injection zu vermeiden
        $date_condition_sql = " AND datum = '".$db->escape_string($filter_datum_raw)."'";
    }

    // 3. Gehe jede zu verarbeitende Stadt durch und hole die Daten
    foreach ($cities_to_process as $current_city_name_for_query) {
        $city_suffix_sanitized = wetter_sanitize_city_name_for_table($current_city_name_for_query); // Deine Helferfunktion zum Bereinigen des Stadtnamens für Tabellennamen

        if (empty($city_suffix_sanitized)) {
            // error_log("Wetter Plugin: Konnte Stadtnamen nicht sanitisieren: " . $current_city_name_for_query); // Optional
            continue; // Nächste Stadt
        }

        // Tabellenname zusammenbauen
        $table_name_no_prefix = "wetter_" . $db->escape_string($city_suffix_sanitized);
        if ($fetch_from_archive) {
            $table_name_no_prefix .= "_archiv";
        }

        // Prüfen, ob die Tabelle überhaupt existiert
        if (!$db->table_exists($table_name_no_prefix)) {
            // error_log("Wetter Plugin: Tabelle '".TABLE_PREFIX.$table_name_no_prefix."' existiert nicht für Stadt: ".$current_city_name_for_query); // Optional
            continue; // Nächste Stadt
        }

        // SQL-Query zusammenbauen
        // Wichtig: Wir fügen 'city_name' direkt zur SELECT-Liste hinzu, damit wir wissen, woher die Daten stammen.
        // Die Sortierung nach `datum` machen wir später in PHP, wenn wir alle Daten haben (wichtig für '__ALL__').
        // Pro Tabelle sortieren wir primär nach `zeitspanne ASC`, damit bei gleichem Datum die Zeitspannen in der richtigen Reihenfolge sind.
        $sql = "SELECT *, '{$db->escape_string($current_city_name_for_query)}' AS city_name
                FROM ".TABLE_PREFIX.$table_name_no_prefix."
                WHERE 1=1 {$date_condition_sql}
                ORDER BY zeitspanne ASC"; // Sortierung für die einzelne Tabelle

        $query = $db->query($sql);
        while($row_data = $db->fetch_array($query)) {
            $all_weather_data_collected[] = $row_data; // Daten zum Sammelarray hinzufügen
        }
    } // Ende der Schleife über die Städte

    // 4. Globale Sortierung anwenden (wichtig, wenn Daten aus mehreren Städten für '__ALL__' gesammelt wurden)
    //    Sortiert primär nach Datum (neueste zuerst), sekundär nach Zeitspanne (früheste zuerst).
    if (!empty($all_weather_data_collected)) {
        usort($all_weather_data_collected, function($a, $b) {
            // Primäre Sortierung: Datum absteigend (neueste zuerst)
            if ($a['datum'] != $b['datum']) {
                return ($a['datum'] < $b['datum']) ? 1 : -1;
            }

            // Sekundäre Sortierung: Zeitspanne aufsteigend (00-06, 06-12, etc.)
            // Du brauchst eine definierte Reihenfolge für deine Zeitspannen-Strings.
            // Beispielhafte Reihenfolge, passe dies ggf. an deine exakten Zeitspannen-Strings an.
            $timespan_order = [
                "00-06" => 1, "06-12" => 2, "12-18" => 3, "18-24" => 4,
                "00-12" => 5, "12-24" => 6, "00-24" => 7 // Falls du solche übergreifenden Zeitspannen hast
            ];
            $a_ts_val = $timespan_order[$a['zeitspanne']] ?? 99; // Fallback für unbekannte Zeitspannen
            $b_ts_val = $timespan_order[$b['zeitspanne']] ?? 99;

            return $a_ts_val <=> $b_ts_val; // Aufsteigend nach Zeitspanne
        });
    }

    // 5. Limit anwenden, falls gewünscht und Daten vorhanden sind
    if ($limit > 0 && count($all_weather_data_collected) > $limit) {
        return array_slice($all_weather_data_collected, 0, (int)$limit);
    }

    return $all_weather_data_collected; // Die gesammelten, sortierten und ggf. limitierten Daten zurückgeben
}

?>
