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
function wetter_admin_load_check() {
    global $mybb, $wetter_load_css; 
    if (isset($mybb->input['module']) && strpos($mybb->input['module'], 'wetter') === 0) {
        $wetter_load_css = true; 
    }
}

// Deine CSS-Ausgabefunktion
function wetter_output_acp_css($page_obj) {
    global $mybb, $wetter_load_css;
    if ($wetter_load_css) {
        $plugin_info = wetter_info();
        $css_file_name = 'weather-icons.min.css';
        $css_url = $mybb->settings['bburl'] . '/images/wetter/css/' . $css_file_name;
        $css_url .= '?v=' . (isset($plugin_info['version']) ? $plugin_info['version'] : time());

        if (is_object($page_obj) && method_exists($page_obj, 'add_header_stylesheet')) {
            $page_obj->add_header_stylesheet($css_url);
        }
    }
    return $page_obj;
}

// Deine JS-Ausgabefunktion
function wetter_output_acp_js($page_obj) { 
    global $mybb, $wetter_load_css; 
    if ($wetter_load_css) { 
        $plugin_info = wetter_info(); 
        $js_file_url = $mybb->settings['bburl'] . '/jscripts/wetter_icon_picker.js';
        $js_file_url .= '?v=' . (isset($plugin_info['version']) ? $plugin_info['version'] : time()); 
        echo '<script type="text/javascript" src="' . htmlspecialchars($js_file_url) . '"></script>';
    }
}

// Plugin Informationen
function wetter_info() {
    global $lang;
    // Lade die Sprachdatei nur einmal, wenn sie noch nicht geladen ist.
    if(!isset($lang->wetter_plugin_name)) { // Prüfe eine spezifische Variable aus deiner Sprachdatei
        if(defined("IN_ADMINCP")) { // Für das ACP
             $lang->load("wetter", false, true); // admin/wetter.lang.php
        } else { // Für das Frontend
             $lang->load("wetter"); // inc/languages/.../wetter.lang.php
        }
    }

    return array(
        "name"            => $lang->wetter_plugin_name ?? "Wetter Plugin", // Fallback, falls Sprachdatei nicht geladen
        "description"     => $lang->wetter_plugin_description ?? "Zeigt Wetterdaten für konfigurierte Städte auf einer eigenen Seite an und verwaltet diese im ACP.",
        "website"         => "https://shadow.or.at/index.php",
        "author"          => "Dani",
        "authorsite"      => "https://github.com/ShadowOfDestiny",
        "version"         => "1.2", // Deine aktuelle Zielversion
        "compatibility"   => "18*",
        "codename"        => "wetter" // GUID entfernt, codename ist für MyBB 1.8+ Standard
    );
}

// Plugin Installation
function wetter_install() {
    global $db, $mybb, $lang;

    if(!isset($lang->wetter_settings_title)) { // Lade Admin-Sprachdatei für Titel etc.
        $lang->load("wetter", false, true);
    }
    
    // 1. Einstellungsgruppe erstellen (nur wenn sie nicht existiert)
    $gid = 0; // Initialisiere gid
    $setting_group_check_query = $db->simple_select("settinggroups", "gid", "name='wetter_plugin_settings'");
    if($db->num_rows($setting_group_check_query) > 0) {
        $sg_data = $db->fetch_array($setting_group_check_query);
        $gid = (int)$sg_data['gid'];
    } else {
        $setting_group_data_arr = array( // Umbenannt, um Konflikt zu vermeiden
            "name"        => "wetter_plugin_settings",
            "title"       => $lang->wetter_settings_title ?: "Wetter Plugin Einstellungen",
            "description" => $lang->wetter_settings_description ?: "Globale Einstellungen für das Wetter-Plugin.",
            "disporder"   => 80, 
            "isdefault"   => 0
        );
        $gid = (int)$db->insert_query("settinggroups", $setting_group_data_arr);
    }
    
    // 2. Einstellungen definieren und einfügen (inkl. Versions-Einstellung)
    $settings_to_install = array(
        "wetter_version" => array( // Konsistenter Name
            "title"       => $lang->wetter_setting_version_title ?: 'Installierte Wetter Version',
            "description" => $lang->wetter_setting_version_desc ?: 'Dies speichert die aktuell installierte Version des Wetter Plugins. Nicht manuell ändern!',
            "optionscode" => "text", 
            "value"       => wetter_info()['version'], // Setzt die aktuelle Version des Plugins
            "disporder"   => 1, 
        ),
        "wetter_plugin_active" => array(
            "title"       => $lang->wetter_setting_active_title ?: "Plugin aktiv?",
            "description" => $lang->wetter_setting_active_desc ?: "Ist das Wetter Plugin aktiv und die Frontend-Seite erreichbar?",
            "optionscode" => "yesno", "value" => "1", "disporder"   => 2
        ),
        "wetter_plugin_date_format" => array(
            "title"       => $lang->wetter_setting_date_format_title ?: "Datumsformat (Frontend)",
            "description" => $lang->wetter_setting_date_format_desc ?: "PHP Datumsformat für die Anzeige im Frontend (z.B. d.m.Y).",
            "optionscode" => "text", "value" => "d.m.Y", "disporder"   => 3
        ),
        "wetter_plugin_active_months" => array(
            "title"       => $lang->wetter_setting_active_month_title ?: "Aktive Monate (CSV)",
            "description" => $lang->wetter_setting_active_month_desc ?: "Komma-getrennte Liste der Monate. Für nicht gelistete Monate wird das Archiv verwendet.",
            "optionscode" => "text", "value" => "", "disporder"   => 4
        ),
        "wetter_plugin_cities" => array(
            "title"       => $lang->wetter_setting_cities_title ?: "Konfigurierte Staedte (CSV)",
            "description" => $lang->wetter_setting_cities_desc ?: "Wird vom ACP-Modul Staedte verwalten gepflegt. Hier nicht manuell ändern!",
            "optionscode" => "text", "value" => "", "disporder"   => 5
        ),
        "wetter_plugin_items_per_page_frontend" => array(
            "title" => $lang->wetter_setting_items_per_page_frontend_title ?: "Einträge/Seite (Frontend)",
            "description" => $lang->wetter_setting_items_per_page_frontend_desc ?: "0 für keine Paginierung.",
            "optionscode" => "numeric", "value" => "15", "disporder" => 6
        ),
        "wetter_plugin_items_per_page_acp" => array(
            "title" => $lang->wetter_setting_items_per_page_acp_title ?: "Einträge/Seite (ACP)",
            "description" => $lang->wetter_setting_items_per_page_acp_desc ?: "Anzahl Einträge im ACP.",
            "optionscode" => "numeric", "value" => "20", "disporder" => 7
        )
    );

    foreach($settings_to_install as $name => $setting_item) {
        $setting_check_query = $db->simple_select("settings", "sid", "name='".$db->escape_string($name)."'");
        if($db->num_rows($setting_check_query) == 0) { // Nur einfügen, wenn nicht vorhanden
            $setting_item['name'] = $name;
            $setting_item['gid'] = $gid;
            $db->insert_query('settings', $setting_item);
        }
    }
    rebuild_settings();

    // 3. Template-Gruppe erstellen (nur wenn sie nicht existiert)
    $templategroup_check_query = $db->simple_select("templategroups", "gid", "prefix='wetter'");
    if($db->num_rows($templategroup_check_query) == 0) {
        $templategroup_frontend_data = array("prefix" => "wetter", "title"  => "Wetter Frontend"); // Umbenannt
        $db->insert_query("templategroups", $templategroup_frontend_data);
    }

    // 4. Templates erstellen mit der neuen Funktion
    wetter_manage_templates('install'); 

    // 5. Stylesheet installieren (dein Code hierfür ist gut)
    $css_name_check = 'wetter.css';
    $css_tid_check = 1; 
    $query_check_css = $db->simple_select("themestylesheets", "sid", "name='".$db->escape_string($css_name_check)."' AND tid='".(int)$css_tid_check."'", array("limit" => 1));
    if($db->num_rows($query_check_css) == 0) { 
        $css_content_string = ":root { /* ... dein CSS ... */ }"; // CSS hier einfügen
        $css_array = array( // Umbenannt
            'name' => $css_name_check, 'tid' => (int)$css_tid_check, 'attachedto' => '', 
            "stylesheet" => $db->escape_string($css_content_string),
            'cachefile' => $db->escape_string($css_name_check), 'lastmodified' => time()
        );
        require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
        $sid_css = $db->insert_query("themestylesheets", $css_array); // Umbenannt
        $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . (int)$sid_css), "sid = '" . (int)$sid_css . "'", 1);
        $query_themes_css = $db->simple_select("themes", "tid"); // Umbenannt
        while ($theme_item_css = $db->fetch_array($query_themes_css)) { // Umbenannt
            update_theme_stylesheet_list((int)$theme_item_css['tid']);
        }
    } 
} 

// Plugin ist installiert?
function wetter_is_installed() {
    global $mybb, $db; // $db hinzugefügt
    $query = $db->simple_select("settings", "name", "name='wetter_version'"); // Prüft auf die Versionseinstellung
    return $db->num_rows($query) > 0;
}

// Plugin Deinstallation
function wetter_uninstall() {
    global $db, $mybb;

    if (isset($mybb->settings['wetter_plugin_cities'])) {
        $cities_array = wetter_helper_get_cities_array_from_string($mybb->settings['wetter_plugin_cities']);
        if (!empty($cities_array)) {
            foreach ($cities_array as $city_name) {
                $city_suffix = wetter_sanitize_city_name_for_table($city_name);
                if (!empty($city_suffix)) {
                    if ($db->table_exists("wetter_". $city_suffix)) $db->drop_table("wetter_". $city_suffix);
                    if ($db->table_exists("wetter_". $city_suffix."_archiv")) $db->drop_table("wetter_". $city_suffix."_archiv");
                }
            }
        }
    }
    $db->delete_query('settings', "name = 'wetter_version'");
    $db->delete_query('settings', "name LIKE 'wetter_plugin_%'"); 
    $db->delete_query('settinggroups', "name = 'wetter_plugin_settings'");
    rebuild_settings();
    $db->delete_query("templates", "title LIKE 'wetter_%' AND sid='-2'"); 
    $db->delete_query("templategroups", "prefix = 'wetter'");
    require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'wetter.css'");
    $query_themes_uninstall = $db->simple_select("themes", "tid"); // Umbenannt
    while($theme_item_uninstall = $db->fetch_array($query_themes_uninstall)) { // Umbenannt
        update_theme_stylesheet_list((int)$theme_item_uninstall['tid']);
    }
}

// Plugin Aktivierung (enthält jetzt die Update-Logik)
function wetter_activate() {
    global $mybb, $db, $lang;

    $plugin_info = wetter_info();
    $neue_version = $plugin_info['version']; // Das ist jetzt "1.2"

    $installierte_version = '0.0.0'; 
    if (isset($mybb->settings['wetter_version'])) { 
        $installierte_version = $mybb->settings['wetter_version'];
    } else {
        // Notfall: Versionseinstellung fehlt, versuche sie zu erstellen
        if(!isset($lang->wetter_setting_version_title)) { $lang->load("wetter", false, true); }
        $gid_query_activate = $db->simple_select("settinggroups", "gid", "name='wetter_plugin_settings'", array("limit" => 1)); // Umbenannt
        $sg_data_activate = $db->fetch_array($gid_query_activate); // Umbenannt
        if($sg_data_activate['gid']) {
            $version_setting_data_activate = array( // Umbenannt
                "name" => "wetter_version", 
                "title" => $lang->wetter_setting_version_title ?: "Wetter Plugin Version (intern)",
                "description" => $lang->wetter_setting_version_desc ?: "Installierte Version.",
                "optionscode" => "text", "value" => "1.0", // Setze auf eine bekannte "alte" Version
                "disporder" => 1, "gid" => (int)$sg_data_activate['gid']
            );
            $db->insert_query("settings", $version_setting_data_activate);
            rebuild_settings(); 
            $installierte_version = "1.0"; 
        }
    }

    if (version_compare($installierte_version, $neue_version, '<')) {
        
        // require_once MYBB_ROOT."inc/adminfunctions_templates.php"; // Nur für find_replace_templatesets für Core-Templates nötig

        // --- Update für Versionen < 1.1 auf 1.1 (oder neuer) ---
        // Dieser Block stellt sicher, dass Paginierungs-Einstellungen existieren, falls jemand von <1.1 direkt auf 1.2+ updated.
        if (version_compare($installierte_version, '1.1', '<')) {
            if(!isset($lang->wetter_setting_items_per_page_frontend_title)) { $lang->load("wetter", false, true); } // Lade Sprache, falls nicht schon geschehen
            
            $query_gid_update_1_1 = $db->simple_select("settinggroups", "gid", "name='wetter_plugin_settings'"); // Umbenannt
            $gid_for_update_1_1 = (int)$db->fetch_field($query_gid_update_1_1, "gid"); // Umbenannt

            if($gid_for_update_1_1) {
                $setting_fe_items_data = array( 
                    "name" => "wetter_plugin_items_per_page_frontend",
                    "title" => $lang->wetter_setting_items_per_page_frontend_title ?: "Einträge/Seite (Frontend)",
                    "description" => $lang->wetter_setting_items_per_page_frontend_desc ?: "0 für keine Paginierung.",
                    "optionscode" => "numeric", "value" => "15",
                    "disporder"   => 6, "gid" => $gid_for_update_1_1
                );
                $check_fe = $db->simple_select("settings", "name", "name='wetter_plugin_items_per_page_frontend'");
                if($db->num_rows($check_fe) == 0) $db->insert_query("settings", $setting_fe_items_data);

                $setting_acp_items_data = array( 
                    "name" => "wetter_plugin_items_per_page_acp",
                    "title" => $lang->wetter_setting_items_per_page_acp_title ?: "Einträge/Seite (ACP)",
                    "description" => $lang->wetter_setting_items_per_page_acp_desc ?: "Anzahl Einträge im ACP.",
                    "optionscode" => "numeric", "value" => "20",
                    "disporder"   => 7, "gid" => $gid_for_update_1_1
                );
                $check_acp = $db->simple_select("settings", "name", "name='wetter_plugin_items_per_page_acp'");
                if($db->num_rows($check_acp) == 0) $db->insert_query("settings", $setting_acp_items_data);
            } else {
                if(defined('IN_ADMINCP')) {
                    flash_message($lang->sprintf($lang->wetter_error_settinggroup_not_found, 'wetter_plugin_settings') . " (Update-Vorbereitung)", 'error');
                }
            }
        } 

        // --- Update für Versionen < 1.2 auf 1.2 (oder neuer) ---
        // Dieser Block wird ausgeführt, wenn die installierte Version kleiner als 1.2 ist.
        // Die wetter_manage_templates('update') Funktion stellt sicher, dass die Templates den Stand von Version 1.2 haben.
        if (version_compare($installierte_version, '1.2', '<')) {
            wetter_manage_templates('update'); 

            // Hier könnten weitere spezifische Änderungen für v1.2 hinzukommen (DB-Schema etc.)
        }
        
        // Plugin-Version in der Datenbank auf die aktuelle $neue_version aktualisieren
        $db->update_query("settings", array("value" => $db->escape_string($neue_version)), "name='wetter_version'"); 
        rebuild_settings(); 
    }

    wetter_helper_check_and_create_all_city_tables();
}

// Plugin Deaktivierung
function wetter_deactivate() {
    // Normalerweise nichts zu tun, außer Core-Template-Änderungen rückgängig machen, die du hier nicht hast.
}


// --- HELFERFUNKTION ZUR TEMPLATE-VERWALTUNG ---
/**
 * Verwaltet die Plugin-eigenen Templates (erstellt oder aktualisiert sie).
 * Stellt sicher, dass die Templates den für die aktuelle Plugin-Version definierten Inhalt haben.
 *
 * @param string $mode Entweder 'install' oder 'update'.
 */
function wetter_manage_templates($mode = 'install') {
    global $db;
    $plugin_info = wetter_info(); 
    $current_plugin_version = $db->escape_string($plugin_info['version']);

    $templates_definition = array();

    // Template: wetter_main (für Version 1.2 INKLUSIVE frontend_pagination)
    $wetter_main_content = '<html>
<head>
    <title>{$mybb->settings[\'bbname\']} - {$page_title_for_template}</title> 
    {$headerinclude}
    <link rel="stylesheet" href="{$mybb->settings[\'bburl\']}/images/wetter/css/weather-icons.min.css?v={$mybb->settings[\'wetter_version\']}" type="text/css" />
</head>
<body>
    {$header}
    {$wetter_navigation_output}
    <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder" style="width: 100%; margin: auto;">
        <thead>
            <tr><td class="thead" colspan="11"><strong>{$headline_text_for_page}</strong></td></tr>
            <tr>
                <td class="tcat" width="12%"><span class="smalltext"><strong><center>{$lang->wetter_label_city}</center></strong></span></td>
                <td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_date}</center></strong></span></td>
                <td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_timeslot}</center></strong></span></td>
                <td class="tcat" width="5%"><span class="smalltext"><strong><center>{$lang->wetter_label_icon}</center></strong></span></td>
                <td class="tcat" width="10%"><span class="smalltext"><strong><center>{$lang->wetter_label_temp}</center></strong></span></td>
                <td class="tcat" width="15%"><span class="smalltext"><strong><center>{$lang->wetter_label_condition}</center></strong></span></td>
                <td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_sunrise}</center></strong></span></td>
                <td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_sunset}</center></strong></span></td>
                <td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_moonphase}</center></strong></span></td>
                <td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_winddirection}</center></strong></span></td>
                <td class="tcat" width="8%"><span class="smalltext"><strong><center>{$lang->wetter_label_windspeed}</center></strong></span></td>
            </tr>
        </thead>
        <tbody>{$wetter_table_rows_content}</tbody>
    </table>
    {$frontend_pagination} {$footer}
</body>
</html>';
    $templates_definition['wetter_main'] = array(
        "template" => $db->escape_string($wetter_main_content),
        "sid" => -2, "version" => $current_plugin_version, "dateline" => TIME_NOW
    );

    $wetter_nav_content = '<form method="get" action="wetter.php" class="wetter_filter_bar">	
    <input type="hidden" name="view" value="{$view_param_for_form}" />	
    <strong style="margin-right: 5px;">{$lang->wetter_nav_select_city_label}</strong>
    <select name="stadt" style="padding: 6px; border-radius: 3px;"><option value="">{$lang->wetter_nav_overview_link_text}</option>{$city_options_html}</select>
    <strong style="margin-left: 15px; margin-right: 5px;">{$lang->wetter_nav_filter_by_date_label}</strong>
    <input type="date" name="filter_datum" value="{$filter_datum_html}" style="padding: 5px; border-radius: 3px;">
    <input type="submit" value="{$lang->wetter_nav_filter_button}" class="button" style="margin-left: 10px;">
    <span style="margin-left: 20px; padding-left: 20px; border-left: 1px solid #ccc;">{$archive_toggle_link_html}</span>
</form>';
    $templates_definition['wetter_nav'] = array(
        "template" => $db->escape_string($wetter_nav_content),
        "sid" => -2, "version" => $current_plugin_version, "dateline" => TIME_NOW
    );

    $wetter_data_row_content = '<tr class="{$alt_row_class_name}">
		<td>
			<center>{$wetter_entry_data[\'city_name_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'datum_formatiert\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'zeitspanne_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'icon_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'temperatur_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'wetterlage_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'sonnenaufgang_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'sonnenuntergang_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'mondphase_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'windrichtung_html\']}</center>
		</td>
		<td>
			<center>{$wetter_entry_data[\'windstaerke_html\']}</center>
		</td>
	</tr>';
    $templates_definition['wetter_data_row'] = array(
        "template" => $db->escape_string($wetter_data_row_content),
        "sid" => -2, "version" => $current_plugin_version, "dateline" => TIME_NOW
    );

    $wetter_no_data_content = '<tr><td colspan="11" style="text-align:center; padding:10px;">{$no_data_message}</td></tr>';
    $templates_definition['wetter_no_data'] = array(
        "template" => $db->escape_string($wetter_no_data_content),
        "sid" => -2, "version" => $current_plugin_version, "dateline" => TIME_NOW
    );

    foreach($templates_definition as $title => $template_new_data) {
        $query_tpl_exists = $db->simple_select("templates", "tid, template, version", "title = '".$db->escape_string($title)."' AND sid = '-2'"); // Umbenannt
        $existing_template_data = $db->fetch_array($query_tpl_exists); // Umbenannt

        if($existing_template_data) { 
            if ($mode == 'update') {
                if ($existing_template_data['template'] !== $template_new_data['template'] || $existing_template_data['version'] !== $template_new_data['version']) {
                    $db->update_query("templates", array(
                        'template' => $template_new_data['template'],
                        'version'  => $template_new_data['version'],
                        'dateline' => TIME_NOW
                    ), "tid = '".$existing_template_data['tid']."'");
                }
            }
        } else { 
            $template_new_data['title'] = $title; 
            $db->insert_query("templates", $template_new_data);
        }
    }
}


// --- ANDERE HELFERFUNKTIONEN ---
function wetter_sanitize_city_name_for_table($city_name_raw) {
    if (empty($city_name_raw)) return '';
    $sanitized_name = strtolower(trim((string)$city_name_raw));
    $sanitized_name = preg_replace('/\s+/', '_', $sanitized_name); 
    $sanitized_name = preg_replace('/[^a-z0-9_]/', '', $sanitized_name); 
    $sanitized_name = substr($sanitized_name, 0, 30); 
    return $sanitized_name;
}

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

function wetter_create_tables_for_city($city_suffix_clean) {
    global $db;
    if (empty($city_suffix_clean)) {
        return false;
    }
    $table_name_main_no_prefix    = "wetter_". $city_suffix_clean;
    $table_name_archive_no_prefix = $table_name_main_no_prefix. "_archiv";
    $sql_table_structure = " ( `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT, `datum` DATE NOT NULL, `zeitspanne` VARCHAR(5) NOT NULL, `icon` VARCHAR(100) NOT NULL DEFAULT 'wi-na', `temperatur` VARCHAR(10) NOT NULL DEFAULT '0', `wetterlage` VARCHAR(255) NOT NULL DEFAULT '', `sonnenaufgang` TIME DEFAULT NULL, `sonnenuntergang` TIME DEFAULT NULL, `mondphase` VARCHAR(100) DEFAULT NULL, `windrichtung` VARCHAR(50) DEFAULT NULL, `windstaerke` VARCHAR(50) DEFAULT NULL, PRIMARY KEY (`id`), INDEX `idx_datum_zeitspanne` (`datum`, `zeitspanne`) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $success_main = true; $success_archive = true;
    if (!$db->table_exists($table_name_main_no_prefix)) {
        if (!$db->write_query("CREATE TABLE IF NOT EXISTS `". TABLE_PREFIX . $table_name_main_no_prefix . "`" . $sql_table_structure)) {
            $success_main = false;
        }
    }
    if ($success_main && !$db->table_exists($table_name_archive_no_prefix)) {
        if (!$db->write_query("CREATE TABLE IF NOT EXISTS `". TABLE_PREFIX . $table_name_archive_no_prefix . "`" . $sql_table_structure)) {
            $success_archive = false;
        }
    }
    return ($success_main && $success_archive);
}

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
    global $db, $mybb; 
    $all_weather_data_collected = array(); 
    $cities_to_process = array();
    if ($city_name_original_or_all == '__ALL__') {
        $cities_to_process = wetter_helper_get_cities_array_from_string(); 
        if (empty($cities_to_process)) return array(); 
    } elseif (!empty($city_name_original_or_all)) {
        $cities_to_process[] = $city_name_original_or_all;
    } else { return array(); }
    $date_condition_sql = "";
    if (!empty($filter_datum_raw) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $filter_datum_raw)) {
        $date_condition_sql = " AND datum = '".$db->escape_string($filter_datum_raw)."'";
    }
    foreach ($cities_to_process as $current_city_name_for_query) {
        $city_suffix_sanitized = wetter_sanitize_city_name_for_table($current_city_name_for_query); 
        if (empty($city_suffix_sanitized)) continue; 
        $table_name_no_prefix = "wetter_" . $db->escape_string($city_suffix_sanitized);
        if ($fetch_from_archive) $table_name_no_prefix .= "_archiv";
        if (!$db->table_exists($table_name_no_prefix)) continue; 
        $sql = "SELECT *, '{$db->escape_string($current_city_name_for_query)}' AS city_name FROM ".TABLE_PREFIX.$table_name_no_prefix." WHERE 1=1 {$date_condition_sql} ORDER BY zeitspanne ASC"; 
        $query = $db->query($sql);
        while($row_data = $db->fetch_array($query)) {
            $all_weather_data_collected[] = $row_data; 
        }
    } 
    if (!empty($all_weather_data_collected)) {
        usort($all_weather_data_collected, function($a, $b) {
            if ($a['datum'] != $b['datum']) return strtotime($a['datum']) <=> strtotime($b['datum']);
            $timespan_order = ["00-06" => 1, "06-12" => 2, "12-18" => 3, "18-24" => 4, "00-12" => 5, "12-24" => 6, "00-24" => 7 ];
            $a_ts_val = $timespan_order[$a['zeitspanne']] ?? 99; 
            $b_ts_val = $timespan_order[$b['zeitspanne']] ?? 99;
            return $a_ts_val <=> $b_ts_val; 
        });
    }
    if ($limit > 0 && count($all_weather_data_collected) > $limit) {
        return array_slice($all_weather_data_collected, 0, (int)$limit);
    }
    return $all_weather_data_collected; 
}
?>