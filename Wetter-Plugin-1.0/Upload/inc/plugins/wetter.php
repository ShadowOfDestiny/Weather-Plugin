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
        "name"          => "Wetter Plugin (Neu)",
        "description"   => "Zeigt Wetterdaten für konfigurierte Städte auf einer eigenen Seite an und verwaltet diese im ACP.",
        "website"       => "https://shadow.or.at/index.php",
        "author"        => "Dani",
        "authorsite"    => "https://github.com/ShadowOfDestiny",
        "version"       => "1.0",
        "compatibility" => "18*",
        "guid"          => "", // Optional: Eindeutige GUID für MyBB.com
    );
}

// Plugin Installation
function wetter_install() {
    global $db, $mybb, $lang;

    // Lade die Admin-Sprachdatei für die Settings-Titel etc.
    // MyBB sucht diese im Admin-Sprachordner, wenn $admin_file = true ist (3. Parameter)
    if(!isset($lang->wetter_settings_title)) { // Prüfe auf eine spezifische Variable aus deiner Admin-Sprachdatei
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
    );

    foreach($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }
    rebuild_settings();

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
                <td class="thead" colspan="10"><strong>{$headline_text_for_page}</strong></td>
            </tr>
            <tr>
                <td class="tcat" width="15%"><span class="smalltext"><strong><center>{$lang->wetter_label_date}</center></strong></span></td>
				<td class="tcat" width="15%"><span class="smalltext"><strong><center>{$lang->wetter_label_timeslot}</center></strong></span></td>
                <td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_temp}</center></strong></span></td>
                <td class="tcat" width="20%"><span class="smalltext"><strong><center>{$lang->wetter_label_condition}</center></strong></span></td>
                <td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_sunrise}</center></strong></span></td>
                <td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_sunset}</center></strong></span></td>
                <td class="tcat" width="15%"><span class="smalltext"><strong><center>{$lang->wetter_label_moonphase}</center></strong></span></td>
                <td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_winddirection}</center></strong></span></td>
                <td class="tcat" width="5%"><span class="smalltext"><strong><center>{$lang->wetter_label_windspeed}</center></strong></span></td>
                <td class="tcat" width="5%" style="text-align:center;"><span class="smalltext"><strong>{$lang->wetter_label_icon}</strong></span></td>
            </tr>
        </thead>
        <tbody>
            {$wetter_table_rows_content}
        </tbody>
    </table>
    {$footer}
</body>
</html>'),
        "sid" => -2, "version" => "1.0", "dateline" => TIME_NOW
    );
    $templates_frontend[] = array(
        "title" => "wetter_nav",
        "template" => $db->escape_string('<div style="text-align:center; margin: 10px 0;">
    <strong>{$lang->wetter_nav_select_city_label}</strong>
    <select onchange="if (this.value) window.location.href=this.value;">
        <option value="wetter.php">{$lang->wetter_nav_overview_link_text}</option>
        {$city_options_html}
    </select>
    {$archive_toggle_link_html}
</div>'),
        "sid" => -2, "version" => "1.0", "dateline" => TIME_NOW
    );
    $templates_frontend[] = array(
        "title" => "wetter_data_row",
        "template" => $db->escape_string('<tr class="{$alt_row_class_name}">
    <td><center>{$wetter_entry_data[\'datum_formatiert\']}</center></td>
	<td><center>{$wetter_entry_data[\'zeitspanne\']}</center></td>
    <td><center>{$wetter_entry_data[\'temperatur\']}°C</center></td>
    <td><center>{$wetter_entry_data[\'wetterlage\']}</center></td>
    <td><center>{$wetter_entry_data[\'sonnenaufgang\']}</center></td>
    <td><center>{$wetter_entry_data[\'sonnenuntergang\']}</center></td>
    <td><center>{$wetter_entry_data[\'mondphase\']}</center></td>
    <td><center>{$wetter_entry_data[\'windrichtung\']}</center></td>
    <td><center>{$wetter_entry_data[\'windstaerke\']} km/h</center></td>
    <td style="text-align:center;"><i class="wi {$wetter_entry_data[\'icon_aus_db\']}" style="font-size: 1.5em;" title="{$wetter_entry_data[\'icon_aus_db\']}"></i></td>
</tr>'),
        "sid" => -2, "version" => "1.0", "dateline" => TIME_NOW
    );
    $templates_frontend[] = array(
        "title" => "wetter_no_data",
        "template" => $db->escape_string('<tr><td colspan="10" style="text-align:center;">{$no_data_message}</td></tr>'),
        "sid" => -2, "version" => "1.0", "dateline" => TIME_NOW
    );

    foreach($templates_frontend as $template) {
        $db->insert_query("templates", $template);
    }
    // Hinweis: ACP-Templates werden oft direkt in den ACP-Moduldateien per $page->output_table() etc. erzeugt
    // oder als Teil des Standard-MyBB-ACP-Layouts. Eigene ACP-Templates sind seltener, aber möglich.
/* =========================================================================== 
   Funktion: wetter_install_stylesheet()
   Fügt ein Stylesheet zur Darstellung des Wetter-Plugins hinzu.
============================================================================ */

    
    $css = array(
        'name'         => 'wetter.css',
        'tid'          => 1,
        'attachedto'   => '',
        "stylesheet"   => ":root {
            --background-color: #f0f0f0;
            --primary-color: #007acc;
            --secondary-color: #dddddd;
            --text-color: #333333;
        }
        
        #weather {
            max-width: 1080px;
            margin: 0 auto;
            background-color: var(--background-color);
            padding: 20px;
        }
        
        .weather-entry {
            border: 1px solid var(--secondary-color);
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        
        .weather-entry h2 {
            color: var(--primary-color);
            margin-top: 0;
        }
        
        .weather-entry p {
            color: var(--text-color);
        }",
        'cachefile'    => $db->escape_string(str_replace('/', '', 'wetter.css')),
        'lastmodified' => time()
    );
    
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);
    
    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }
}

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

// Get weather data for a city
function wetter_helper_get_weather_data($city_name_original, $fetch_from_archive = false, $limit = 0) {
    global $db;
    if (empty($city_name_original)) return array();

    $city_suffix_sanitized = wetter_sanitize_city_name_for_table($city_name_original);
    if (empty($city_suffix_sanitized)) return array();

    $table_name_to_query_no_prefix = "wetter_". $db->escape_string($city_suffix_sanitized);
    if ($fetch_from_archive) {
        $table_name_to_query_no_prefix .= "_archiv";
    }

    if (!$db->table_exists($table_name_to_query_no_prefix)) {
        // error_log("Wetter Plugin: Tabelle '{$table_name_to_query_no_prefix}' existiert nicht. Stadt: {$city_name_original}, Archiv: ". (int)$fetch_from_archive);
        return array();
    }

    $query_options = array(
        "order_by" => 'datum DESC, zeitspanne ASC'
    );
    if($limit > 0) {
        $query_options['limit'] = (int)$limit;
    }

    $query = $db->simple_select($table_name_to_query_no_prefix, "*", "", $query_options);

    $weather_data_results = array();
    if ($db->num_rows($query) > 0) {
        while($row_data = $db->fetch_array($query)) {
            $weather_data_results[] = $row_data; // Geändert, um alle Zeilen zurückzugeben
        }
    }
    return $weather_data_results;
}

?>