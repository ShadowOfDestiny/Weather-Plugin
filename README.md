# Wetter-Plugin
Das Wetter-Plugin erweitert das Rollenspielforum um eine Wetterdatenbank für Städte, Orte oder ganze Länder. Es gibt verschiedene Eingabefelder, unter anderem Sonnenaufgang und Sonnenuntergang, Felder für Temperatur, Windstärke und Windrichtung. Zudem kann man ein Wettericon einbinden, welches über ein Java-Script - das mitgeliefert wird - eingefügt werden kann.<br>
<br>
Falls Tabellen gelöscht werden sollen, oder nicht gebraucht werden, ist es recht einfach, die Daten in der Tabellenstruktur zu löschen. Sollten dabei Probleme entstehen, bin ich jederzeit bereit, ein modifiziertes Plugin für jemanden zu erstellen und nicht benötigte Felder raus zu löschen.<br>
<br>
Das Plugin wird vollständig über das ACP gesteuert und bietet dort in einem eigenen Modul alles, was für die Einstellungen nötig sind. Zudem kann man dort beliebig viele Städte erstellen, wobei man nicht darauf beschränkt ist, das diese Städte real sind, denn die Namen werden selbstständig durch die Funktion 'Städte erstellen' in die Datenbank geschrieben und dort werden auch die Daten gespeichert und später als Tabelle im Forum selbst abgerufen.<br>
<br>
<br>
## Funktionen im Überblick
### Wetterübersicht im ACP
Im ACP kann man über ein Modul alle wichtigen Dinge für das Wetter-Plugin einstellen. Seien es Städte, die Wetterdaten oder welche Monate gerade aktiv bespielt werden. Die Tabelle im Forum ist nach Datum, nach Stadt und auch nach allen Städten sortierbar. Und zudem kann man ein Datum eingeben und dies für alle Städte nachsehen.
- Eigene Städte eintragen
- Wetterdaten speichern und editieren

# Datenbank-Änderungen
hinzugefügte Tabelle:
- stadt
- stadt_archiv
- noch mehr Städte, noch mehr Archive

# Neue Sprachdateien
- deutsch_du/admin/wetter.lang.php
- deutsch_du/wetter.lang.php

# Einstellungen
- Stadt eintragen
- Datum
- Monate
- Wetterdaten eintragen

<br>
<br>
<b>HINWEIS:</b><br>
Das Plugin ist vollständig autonom, dennoch ein kleiner Hinweis: Nutzt man von <a href="https://github.com/ItsSparksFly">Sparks Fly</a> das <a href="https://github.com/ItsSparksFly/mybb-schedule">Posts & Themen planen</a> Plugin kommt es beim Absenden der Wetterdaten zu einem kleinen deprecated Fehler. An sich ist das nicht schlimm und mein Plugin ärgert es auch nicht sehr, aber falls benötigt habe ich dafür einen Fix.

# Neue Template-Gruppe innerhalb der Design-Templates
- Wetter Frontend Templates

# Neue Templates (nicht global!)
- wetter_main
- wetter_nav
- wetter_data_row
- wetter_no_data

  
# Neue Variablen
- {$wetter_entry_data['city_name_html']}
- {$wetter_entry_data['datum_formatiert']}
- {$wetter_entry_data['zeitspanne_html']}
- {$wetter_entry_data['icon_html']}
- {$wetter_entry_data['temperatur_html']}
- {$wetter_entry_data['wetterlage_html']}
- {$wetter_entry_data['sonnenaufgang_html']}
- {$wetter_entry_data['sonnenuntergang_html']}>
- {$wetter_entry_data['mondphase_html']}
- {$wetter_entry_data['windrichtung_html']}
- {$wetter_entry_data['windstaerke_html']}

# Neues CSS - wetter.css
Es wird automatisch in jedes bestehende und neue Design hinzugefügt. Man sollte es einfach einmal abspeichern - auch im Default.
```css
:root {
            --wetter-background-color: #f0f0f0;
			--wetter-primary-color: #007acc;
			--wetter-secondary-color: #dddddd;
			--wetter-text-color: #333333;
        }
		
		.wetter_filter_bar {
			text-align: center;
			margin: 10px 0;
			padding: 10px;
			background: #e0e0e0 
			border: 1px solid #c0c0c0
			border-radius: 4px;
		}
        
        .weather {
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
        }
```

# Links
<b>ACP</b><br>
index.php?module=wetter-overview<br>
<br>
<b>Statdverwaltung</b><br>
index.php?module=wetter-cities<br>
<br>
<b>Wetterdaten eintragen</b><br>
index.php?module=wetter-entry<br>
<br>
<b>Einstellungen</b><br>
index.php?module=wetter-settings<br>
<br>
<b>Tabelle im Forum</b><br>
wetter.php<br>
<br>

# Demo
### ACP
<img src="https://s1.directupload.eu/images/user/250528/h3mnn88v.png">
<img src="https://s1.directupload.eu/images/user/250528/76xq5chb.png">
<img src="https://s1.directupload.eu/images/user/250528/gnw2krmu.png">
<img src="https://s1.directupload.eu/images/user/250528/m6ybkqb5.png">
<img src="https://s1.directupload.eu/images/user/250528/4chdxphl.png">
<img src="https://s1.directupload.eu/images/user/250528/c3nf2k37.png">

### Wettertabelle im Forum
<img src="https://s1.directupload.eu/images/user/250528/q9lonsxn.png">
<img src="https://s1.directupload.eu/images/user/250528/8ovqdc8l.png">
<img src="https://s1.directupload.eu/images/user/250528/3b4eru7k.png">
<br>
<br>
<br>
Und Last but not least

Die in diesem Plugin verwendeten Wetter-Icons stammen von "Weather Icons".

Offizielle Webseite und Quelle:
https://erikflowers.github.io/weather-icons/

Lizenzinformationen:

    Icons: SIL OFL 1.1
    Code (LESS/Sass/CSS): MIT License
    Dokumentation: CC BY 3.0

Es wird empfohlen, die Lizenzbedingungen auf der Webseite des Anbieters für detaillierte Informationen zur Nutzung und Attribution zu konsultieren.

---

# Readme: Wetter-Plugin-Integration in den Inplayszenen-Manager

Diese Anleitung beschreibt, wie du das Wetter-Plugin von Dani in den Inplayszenen-Manager von <a href="https://github.com/little-evil-genius">little.evil.genius</a> integrieren kannst. Das Ziel ist es, beim Erstellen einer Szene das Wetter für das gewählte Datum auszuwählen und in der Szenen-Info anzuzeigen.

### Voraussetzungen:

* Der <a href="https://github.com/little-evil-genius/Inplayszenen-Manager">Inplayszenen-Manager</a> muss installiert sein.
* Mein **Wetter-Plugin** ist installiert und konfiguriert.

---

### Schritt 1: Das "Wetter"-Feld im Szenen-Manager erstellen

1.  Gehe ins **Admin-CP** -> **Konfiguration** -> **RPG Erweiterungen** -> **Eigene Inplaytrackerfelder**.
2.  Erstelle ein neues Feld mit den folgenden Eigenschaften:
    * **Identifikator:** `wetter` (Wichtig: genau dieser Name!)
    * **Titel:** `Wetter`
    * **Kurzbeschreibung:** `Das Wetter für die Szene.`
    * **Feldtyp:** `Auswahlbox`
    * Alle anderen Einstellungen können nach Belieben gesetzt werden.

---

### Schritt 2: Die Brücke zur Wetter-Datenbank bauen

Diese Datei sorgt dafür, dass das Formular die Wetterdaten für ein bestimmtes Datum abfragen kann.

1.  Erstelle eine neue, leere Datei im Hauptverzeichnis deines Forums (wo die `index.php` liegt).
2.  Nenne die Datei `ajax_wetter.php`.
3.  Füge folgenden Inhalt in die Datei ein:

```php
<?php
define("IN_MYBB", 1);
require_once "global.php";

// Stellt sicher, dass die Helferfunktionen des Wetter-Plugins geladen sind.
if (file_exists(MYBB_ROOT . "inc/plugins/wetter.php")) {
    require_once MYBB_ROOT . "inc/plugins/wetter.php";
} else {
    echo json_encode(['status' => 'error', 'message' => 'Wetter-Plugin Hauptdatei nicht gefunden.']);
    exit;
}

if ($mybb->user['uid'] == 0) {
    error_no_permission();
}

header("Content-Type: application/json");

$input_date_str = $mybb->get_input('date', MyBB::INPUT_STRING);
$response = ['status' => 'error', 'message' => 'Ungültiges Datum angegeben.'];

if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $input_date_str)) {
    
    // *** NEUE ARCHIV-LOGIK ***
    // Bestimmen, ob das Archiv genutzt werden muss, basierend auf den Plugin-Einstellungen.
    $use_archive = false;
    $active_months_str = strtolower($mybb->settings['wetter_plugin_active_months']);
    
    if (!empty($active_months_str)) {
        $active_months_arr = array_map('trim', explode(",", $active_months_str));
        $timestamp = strtotime($input_date_str);
        // Wichtig: Wir nutzen den englischen Monatsnamen für den Vergleich.
        $month_name_english = strtolower(date('F', $timestamp)); 
        
        if (!in_array($month_name_english, $active_months_arr)) {
            $use_archive = true;
        }
    }
    // *** ENDE ARCHIV-LOGIK ***

    $all_cities = wetter_helper_get_cities_array_from_string();
    $weather_for_date = [];

    foreach ($all_cities as $city) {
        $city_suffix = wetter_sanitize_city_name_for_table($city);
        $table_name = "wetter_" . $db->escape_string($city_suffix);
        
        // Tabellennamen basierend auf der Archiv-Logik anpassen
        if ($use_archive) {
            $table_name .= "_archiv";
        }

        if ($db->table_exists($table_name)) {
            $query = $db->simple_select($table_name, "*", "datum = '" . $db->escape_string($input_date_str) . "'", array('order_by' => 'zeitspanne', 'order_dir' => 'ASC'));
            while ($row = $db->fetch_array($query)) {
                $row['stadt'] = $city;
                $weather_for_date[] = $row;
            }
        }
    }

    if (!empty($weather_for_date)) {
        $response = ['status' => 'success', 'data' => $weather_for_date];
    } else {
        $response = ['status' => 'nodata', 'message' => 'Keine Wetterdaten für diesen Tag gefunden.'];
    }
}

echo json_encode($response);
exit;
?>
```

---

### Schritt 3: Die `inplayscenes.php` anpassen

Als Nächstes öffne die Datei `/inc/plugins/inplayscenes.php`.

#### 1. Funktion `inplayscenes_showthread_start()` anpassen

* **Füge über** der Zeile `// EINSTELLUNGEN` den folgenden Code ein:

    ```php
    // Holen der Plugin-Version für Cache-Busting
    if (function_exists('wetter_info')) {
        $plugin_info_wetter = wetter_info();
        $version_param = '?v=' . (isset($plugin_info_wetter['version']) ? $plugin_info_wetter['version'] : time());
        $headerinclude .= '<link rel="stylesheet" type="text/css" href="'.$mybb->settings['bburl'].'/images/wetter/css/weather-icons.min.css'.$version_param.'" />';
        $headerinclude .= '<link rel="stylesheet" type="text/css" href="'.$mybb->settings['bburl'].'/images/wetter/css/weather-icons-wind.min.css'.$version_param.'" />';
        $headerinclude .= '<style>.wetter-icon-frontend-gross { font-size: 1.8em; vertical-align: middle; line-height: 1; }</style>';
    }
    // Ende der Ergänzung
    ```
	
* **Füge direkt unter** `// Infos aus der DB ziehen` (und der `$info = ...`-Zeile) folgendes ein:
    ```php
    if(!$info) { return; 
    }
    ```
	
* **Suche**
    ```php
// eval("\$newthread_inplayscenes = ...`);
    eval("\$newthread_inplayscenes = \"".$templates->get("inplayscenes_newthread")."\";");
}
    ```

* **Füge darüber ein**

    ```php
global $headerinclude;
$javascript_for_weather = <<<EOT
<script type="text/javascript">
document.addEventListener("DOMContentLoaded", function() {
    const dateInput = document.querySelector('input[name="date"]');
    const wetterSelect = document.querySelector('select[name="wetter"]'); 

    if (dateInput && wetterSelect) {
        // Den gespeicherten Wert aus unserem neuen data-Attribut auslesen
        const savedWetterValue = wetterSelect.dataset.savedValue;

        dateInput.addEventListener('change', function() {
            const selectedDate = this.value;
            wetterSelect.innerHTML = '<option value="">Wetterdaten werden geladen...</option>';
            wetterSelect.disabled = true;

            if (selectedDate) {
                fetch('ajax_wetter.php?date=' + selectedDate)
                    .then(response => response.json())
                    .then(data => {
                        wetterSelect.innerHTML = ''; 
                        if (data.status === 'success' && data.data.length > 0) {
                            wetterSelect.add(new Option('--- Bitte Wetter auswählen ---', ''));
                            data.data.forEach(item => {
                                const optionText = `${item.stadt}: ${item.zeitspanne} Uhr - ${item.temperatur}°C, ${item.wetterlage}`;
                                const optionValue = JSON.stringify(item); 
                                wetterSelect.add(new Option(optionText, optionValue));
                            });

                            // *** NEU: Gespeicherten Wert wieder auswählen ***
                            if (savedWetterValue) {
                                for (let i = 0; i < wetterSelect.options.length; i++) {
                                    if (wetterSelect.options[i].value === savedWetterValue) {
                                        wetterSelect.options[i].selected = true;
                                        break;
                                    }
                                }
                            }
                            
                        } else {
                             wetterSelect.add(new Option('Keine Wetterdaten für diesen Tag gefunden', ''));
                        }
                        wetterSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('Fehler beim Abrufen der Wetterdaten:', error);
                        wetterSelect.innerHTML = '<option value="">Fehler beim Laden der Daten</option>';
                        wetterSelect.disabled = false;
                    });
            } else {
                wetterSelect.innerHTML = '<option value="">Bitte zuerst ein Datum auswählen</option>';
                wetterSelect.disabled = true;
            }
        });
        
        if(dateInput.value) {
            dateInput.dispatchEvent(new Event('change'));
        } else {
            wetterSelect.innerHTML = '<option value="">Bitte zuerst ein Datum auswählen</option>';
            wetterSelect.disabled = true;
        }
    }
});
</script>
EOT;
$headerinclude .= $javascript_for_weather;
    ```
	
* **Suche:**
    ```php
    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $allow_img = "";
        $allow_video = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allow_html'];
        $allow_mybb = $field['allow_mybb'];
        $allow_img = $field['allow_img'];
        $allow_video = $field['allow_video'];

        $value = inplayscenes_parser_fields($info[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);

        // Einzelne Variabeln
        $inplayscene[$identification] = $value;

        if (!empty($value)) {
            eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_showthread_fields") . "\";");
        }
    }
    ```
* **Ersetze es mit:**
    ```php
	
// ******** START DER ANPASSUNG FÜR SHOWTHREAD ********
    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
    $inplayscenesfields = "";
    $formatted_wetter_showthread = ""; // Neue Variable für das formatierte Wetter

    while ($field = $db->fetch_array($fields_query)) {
        $identification = $field['identification'];
        $title = htmlspecialchars_uni($field['title']);
        $raw_value = isset($info[$identification]) ? $info[$identification] : '';

        if ($identification == 'wetter' && !empty($raw_value)) {
            // Wetter-Feld speziell behandeln
            $wetter_data = json_decode($raw_value, true);
            if (is_array($wetter_data)) {
                $wetter_icon_class = htmlspecialchars_uni($wetter_data['icon']);
                $wetter_display_classes = 'wi ' . $wetter_icon_class;
                if (strpos($wetter_icon_class, 'wi-from-') === 0 || strpos($wetter_icon_class, 'wi-towards-') === 0) {
                    $wetter_display_classes = 'wi wi-wind ' . $wetter_icon_class;
                }
                $wetter_icon_html = '<i class="' . $wetter_display_classes . ' wetter-icon-frontend-gross" title="' . $wetter_icon_class . '"></i>';
                
                $mond_icon_html = '<i class="wi ' . htmlspecialchars_uni($wetter_data['mondphase']) . ' wetter-icon-frontend-gross" title="' . htmlspecialchars_uni($wetter_data['mondphase']) . '"></i>';
                $wind_icon_html = '<i class="wi wi-wind ' . htmlspecialchars_uni($wetter_data['windrichtung']) . ' wetter-icon-frontend-gross" title="' . htmlspecialchars_uni($wetter_data['windrichtung']) . '"></i>';
                $wind_text = htmlspecialchars_uni($wetter_data['windstaerke']) . ' km/h';

                $value = $wetter_icon_html . ' ' . htmlspecialchars_uni($wetter_data['wetterlage']) . ' (' . htmlspecialchars_uni($wetter_data['temperatur']) . '°C) in ' . htmlspecialchars_uni($wetter_data['stadt']) . " | Mond: " . $mond_icon_html . " | Wind: " . $wind_icon_html . " " . $wind_text;
                
                eval("\$formatted_wetter_showthread .= \"" . $templates->get("inplayscenes_showthread_fields") . "\";");
            }
        } elseif (!empty($raw_value)) {
            // Andere Felder normal verarbeiten
            $value = inplayscenes_parser_fields($raw_value, $field['allow_html'], $field['allow_mybb'], $field['allow_img'], $field['allow_video']);
            $inplayscene[$identification] = $value;
            if (!empty($value)) {
                eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_showthread_fields") . "\";");
            }
        }
    }
    // ******** ENDE DER ANPASSUNG ********
    ```

####  2. Anpassung der `inplayscenes_editpost()` in `inplayscenes_misc()`

* **Suche**
    ```php
    // SZENEN-INFOS BEARBEITEN
    if($mybb->input['action'] == "inplayscenes_edit"){
    ```

* **füge darunter ein**

    ```php
        // ******** START: DIESEN BLOCK HINZUFÜGEN (INKLUSIVE DER KORREKTUR) ********
        global $headerinclude;
        // WICHTIG: Wir verwenden jetzt <<<'EOT' (mit einfachen Anführungszeichen), um PHP zu sagen,
        // dass es den Inhalt nicht verarbeiten soll. Das behebt den "Fatal error".
        $javascript_for_weather = <<<'EOT'
        <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const dateInput = document.querySelector('input[name="date"]');
            const wetterSelect = document.querySelector('select[name="wetter"]'); 

            if (dateInput && wetterSelect) {
                // Den gespeicherten Wert aus unserem data-Attribut auslesen
                const savedWetterValue = wetterSelect.dataset.savedValue;

                dateInput.addEventListener('change', function() {
                    const selectedDate = this.value;
                    wetterSelect.innerHTML = '<option value="">Wetterdaten werden geladen...</option>';
                    wetterSelect.disabled = true;

                    if (selectedDate) {
                        fetch('ajax_wetter.php?date=' + selectedDate)
                            .then(response => response.json())
                            .then(data => {
                                wetterSelect.innerHTML = ''; 
                                if (data.status === 'success' && data.data.length > 0) {
                                    wetterSelect.add(new Option('--- Bitte Wetter auswählen ---', ''));
                                    
                                    let savedDataObject = null;
                                    if (savedWetterValue) {
                                        try {
                                            savedDataObject = JSON.parse(savedWetterValue);
                                        } catch(e) { console.error("Fehler beim Parsen des gespeicherten Wetterwerts:", e); }
                                    }
                                    
                                    data.data.forEach(item => {
                                        // Diese Zeile verursacht den Fehler, weil PHP `${item.stadt}` als Variable ansieht.
                                        // Die neue EOT-Syntax behebt das.
                                        const optionText = `${item.stadt}: ${item.zeitspanne} Uhr - ${item.temperatur}°C, ${item.wetterlage}`;
                                        const optionValue = JSON.stringify(item); 
                                        const optionElement = new Option(optionText, optionValue);

                                        if (savedDataObject && item.id == savedDataObject.id) {
                                            optionElement.selected = true;
                                        }

                                        wetterSelect.add(optionElement);
                                    });
                                    
                                } else {
                                     wetterSelect.add(new Option('Keine Wetterdaten für diesen Tag gefunden', ''));
                                }
                                wetterSelect.disabled = false;
                            })
                            .catch(error => {
                                console.error('Fehler beim Abrufen der Wetterdaten:', error);
                                wetterSelect.innerHTML = '<option value="">Fehler beim Laden der Daten</option>';
                                wetterSelect.disabled = false;
                            });
                    } else {
                        wetterSelect.innerHTML = '<option value="">Bitte zuerst ein Datum auswählen</option>';
                        wetterSelect.disabled = true;
                    }
                });
                
                if(dateInput.value) {
                    dateInput.dispatchEvent(new Event('change'));
                } else {
                    wetterSelect.innerHTML = '<option value="">Bitte zuerst ein Datum auswählen</option>';
                    wetterSelect.disabled = true;
                }
            }
        });
        </script>
EOT;
        $headerinclude .= $javascript_for_weather;
        // ******** ENDE DES HINZUGEFÜGTEN BLOCKS ********
    ```
	
####  3. Anpassung der Übersicht der eigenen Szenen `if($mybb->input['action'] == "inplayscenes"){` in `inplayscenes_misc()`

* **Suche**
    ```php
                $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
            
                $inplayscenesfields = "";
                while ($field = $db->fetch_array($fields_query)) {

                    // Leer laufen lassen
                    $identification = "";
                    $title = "";
                    $value = "";
                    $allow_html = "";
                    $allow_mybb = "";
                    $allow_img = "";
                    $allow_video = "";
            
                    // Mit Infos füllen
                    $identification = $field['identification'];
                    $title = $field['title'];
                    $allow_html = $field['allow_html'];
                    $allow_mybb = $field['allow_mybb'];
                    $allow_img = $field['allow_img'];
                    $allow_video = $field['allow_video'];

                    $value = inplayscenes_parser_fields($scene[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);
            
                    // Einzelne Variabeln
                    $inplayscene[$identification] = $value;
            
                    if (!empty($value)) {
                        eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_user_scene_fields") . "\";");
                    }
                }
    ```

* **Ersetze**

    ```php
                // ******** START DER ANPASSUNG ********
                $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
                $inplayscenesfields = "";
                $formatted_wetter_user_scene = ""; // Neue Variable

                while ($field = $db->fetch_array($fields_query)) {
                    $identification = $field['identification'];
                    $title = htmlspecialchars_uni($field['title']);
                    $raw_value = isset($scene[$identification]) ? $scene[$identification] : '';

                    if ($identification == 'wetter' && !empty($raw_value)) {
                        // Wetter-Feld speziell behandeln
                        $wetter_data = json_decode($raw_value, true);
                        if (is_array($wetter_data)) {
                             $wetter_icon_html = '<i class="wi ' . htmlspecialchars_uni($wetter_data['icon']) . ' wetter-icon-frontend-gross" title="' . htmlspecialchars_uni($wetter_data['icon']) . '"></i>';
                             $value = $wetter_icon_html . ' ' . htmlspecialchars_uni($wetter_data['wetterlage']) . ' (' . htmlspecialchars_uni($wetter_data['temperatur']) . '°C) in ' . htmlspecialchars_uni($wetter_data['stadt']);
                             eval("\$formatted_wetter_user_scene .= \"" . $templates->get("inplayscenes_user_scene_fields") . "\";");
                        }
                    } elseif (!empty($raw_value)) {
                        // Andere Felder normal verarbeiten
                        $value = inplayscenes_parser_fields($raw_value, $field['allow_html'], $field['allow_mybb'], $field['allow_img'], $field['allow_video']);
                        $inplayscene[$identification] = $value;
                        eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_user_scene_fields") . "\";");
                    }
                }
                // ******** ENDE DER ANPASSUNG ********
    ```
	

#### 4. Funktion `inplayscenes_forumdisplay_thread()` anpassen

* **Suche:**
    ```php
    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        // Leer laufen lassen
        $identification = "";
        $title = "";
        $value = "";
        $allow_html = "";
        $allow_mybb = "";
        $allow_img = "";
        $allow_video = "";

        // Mit Infos füllen
        $identification = $field['identification'];
        $title = $field['title'];
        $allow_html = $field['allow_html'];
        $allow_mybb = $field['allow_mybb'];
        $allow_img = $field['allow_img'];
        $allow_video = $field['allow_video'];

        $value = inplayscenes_parser_fields($info[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);

        // Einzelne Variabeln
        $inplayscene[$identification] = $value;

        if (!empty($value)) {
            eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_forumdisplay_fields") . "\";");
        }
    }
    ```
* **Ersetze es mit:**
    ```php
    // ******** START DER ANPASSUNG FÜR FORUMDISPLAY ********
    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
    $inplayscenesfields = "";
    $formatted_wetter_forumdisplay = ""; // Neue, separate Variable

    while ($field = $db->fetch_array($fields_query)) {
        $identification = $field['identification'];
        $title = htmlspecialchars_uni($field['title']);
        $raw_value = isset($info[$identification]) ? $info[$identification] : '';

        if ($identification == 'wetter' && !empty($raw_value)) {
            // Wetter-Feld speziell behandeln
            $wetter_data = json_decode($raw_value, true);
            if (is_array($wetter_data)) {
                $value = htmlspecialchars_uni($wetter_data['wetterlage']) . ' (' . htmlspecialchars_uni($wetter_data['temperatur']) . '°C)';
                eval("\$formatted_wetter_forumdisplay .= \"" . $templates->get("inplayscenes_forumdisplay_fields") . "\";");
            }
        } elseif (!empty($raw_value)) {
            // Alle anderen Felder so verarbeiten, wie es im Original-Plugin war
            $allow_html = $field['allow_html'];
            $allow_mybb = $field['allow_mybb'];
            $allow_img = $field['allow_img'];
            $allow_video = $field['allow_video'];
            $value = inplayscenes_parser_fields($raw_value, $allow_html, $allow_mybb, $allow_img, $allow_video);
            $inplayscene[$identification] = $value;
            if (!empty($value)) {
                eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_forumdisplay_fields") . "\";");
            }
        }
    }
    // ******** ENDE DER ANPASSUNG ********
    ```

#### 5. Funktion `inplayscenes_misc()` anpassen

* **Suche:**
    ```php
            $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
        
            $inplayscenesfields = "";
            while ($field = $db->fetch_array($fields_query)) {

                // Leer laufen lassen
                $identification = "";
                $title = "";
                $value = "";
                $allow_html = "";
                $allow_mybb = "";
                $allow_img = "";
                $allow_video = "";
        
                // Mit Infos füllen
                $identification = $field['identification'];
                $title = $field['title'];
                $allow_html = $field['allow_html'];
                $allow_mybb = $field['allow_mybb'];
                $allow_img = $field['allow_img'];
                $allow_video = $field['allow_video'];
        
                $value = inplayscenes_parser_fields($scene[$identification], $allow_html, $allow_mybb, $allow_img, $allow_video);
        
                // Einzelne Variabeln
                $inplayscene[$identification] = $value;
        
                if (!empty($value)) {
                    eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_overview_scene_fields") . "\";");
                }
            }
    ```
* **Ersetze es mit:**
    ```php
        // ******** START DER ANPASSUNG FÜR MISC OVERVIEW ********
        $fields_query_overview = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");
        $inplayscenesfields = "";
        $formatted_wetter_overview = "";

        while ($field = $db->fetch_array($fields_query_overview)) {
            $identification = $field['identification'];
            $title = htmlspecialchars_uni($field['title']);
            $raw_value = isset($scene[$identification]) ? $scene[$identification] : '';

            if ($identification == 'wetter' && !empty($raw_value)) {
                $wetter_data = json_decode($raw_value, true);
                if (is_array($wetter_data)) {
                     $value = htmlspecialchars_uni($wetter_data['wetterlage']) . ' (' . htmlspecialchars_uni($wetter_data['temperatur']) . '°C) in ' . htmlspecialchars_uni($wetter_data['stadt']);
                     eval("\$formatted_wetter_overview .= \"" . $templates->get("inplayscenes_overview_scene_fields") . "\";");
                }
            } elseif (!empty($raw_value)) {
                $value = inplayscenes_parser_fields($raw_value, $field['allow_html'], $field['allow_mybb'], $field['allow_img'], $field['allow_video']);
                $inplayscene[$identification] = $value;
                if (!empty($value)) {
                    eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_overview_scene_fields") . "\";");
                }
            }
        }
        // ******** ENDE DER ANPASSUNG ********
    ```

#### 6. Vorschaufunktion anpassen (`inplayscenes_postbit`)

* **Suche:**
    ```php
	// Hole die Felder aus der Datenbank und füge sie dem $post-Array hinzu
    $spalten_query = $db->query("SELECT * FROM ".TABLE_PREFIX."inplayscenes_fields ORDER BY disporder ASC, title ASC");
    while ($spalte = $db->fetch_array($spalten_query)) {
        $post[$spalte['identification']] = ''; // Füge die Felder mit leerem Wert hinzu
    }
    ```

* **Füge darüber ein:**
    ```php
    // *** NEUE VARIABLE FÜR WETTER INITIALISIEREN ***
    $post['formatted_wetter_field'] = '';
    ```

#### 5. Editfunktion anpassen (inplayscenes_generate_input_field)

* **Suche**

    ```php
case 'select':
    ```

* **Füge direkt darunter ein**

    ```php
            // ***** START: MINIMALE ANPASSUNG FÜR WETTER *****
            $data_attribute = '';
            if ($identification == 'wetter' && !empty($value)) {
                $data_attribute = ' data-saved-value=\'' . htmlspecialchars($value, ENT_QUOTES) . '\'';
            }
            // ***** ENDE DER ANPASSUNG *****
			
    ```		

### Schritt 7: Templates anpassen

Füge die jeweils angegebene Variable in einer neuen Zeile **direkt nach** `{$inplayscenesfields}` in den folgenden Templates ein:

* `inplayscenes_showthread` -> Füge hinzu: `{$formatted_wetter_showthread}`
* `inplayscenes_forumdisplay` -> Füge hinzu: `{$formatted_wetter_forumdisplay}`
* `inplayscenes_overview_scene` -> Füge hinzu: `{$formatted_wetter_overview}`
* `inplayscenes_user_scene_infos` -> Füge hinzu: `{$formatted_wetter_overview}`
* `inplayscenes_postbit` (falls genutzt) -> Füge hinzu: `{$post['formatted_wetter_field']}`

#### 8. Postbit anpassen (Optional)

Falls du die Szenen-Informationen auch in jedem einzelnen Beitrag anzeigst (`inplayscenes_postbit`), führe auch hier die Änderung durch.

* **Suche:**
    ```php
    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {
    // ... bis zum Ende der Schleife
    }
    ```
* **Ersetze es mit:**
    ```php
    // *** START der Anpassung ***
    $fields_query = $db->query("SELECT * FROM " . TABLE_PREFIX . "inplayscenes_fields ORDER BY disporder ASC, title ASC");

    $inplayscenesfields = "";
    while ($field = $db->fetch_array($fields_query)) {

        $identification = $field['identification'];
        $title = $field['title']; // Titel für das Template
        $raw_value = isset($info[$identification]) ? $info[$identification] : '';

        // *** HIER IST DIE MINIMALE, NOTWENDIGE ÄNDERUNG ***
        if ($identification == 'wetter' && !empty($raw_value)) {
            $wetter_data = json_decode($raw_value, true);
            if (is_array($wetter_data)) {
                $wetter_icon_class = htmlspecialchars_uni($wetter_data['icon']);
                $wetter_display_classes = 'wi ' . $wetter_icon_class;
                if (strpos($wetter_icon_class, 'wi-from-') === 0 || strpos($wetter_icon_class, 'wi-towards-') === 0) {
                    $wetter_display_classes = 'wi wi-wind ' . $wetter_icon_class;
                }
                $wetter_icon_html = '<i class="' . $wetter_display_classes . ' wetter-icon-frontend-gross" title="' . $wetter_icon_class . '"></i>';
                
                $mond_icon_html = '<i class="wi ' . htmlspecialchars_uni($wetter_data['mondphase']) . ' wetter-icon-frontend-gross" title="' . htmlspecialchars_uni($wetter_data['mondphase']) . '"></i>';
                $wind_icon_html = '<i class="wi wi-wind ' . htmlspecialchars_uni($wetter_data['windrichtung']) . ' wetter-icon-frontend-gross" title="' . htmlspecialchars_uni($wetter_data['windrichtung']) . '"></i>';
                $wind_text = htmlspecialchars_uni($wetter_data['windstaerke']) . ' km/h';

                $value = $wetter_icon_html . ' ' . htmlspecialchars_uni($wetter_data['wetterlage']) . ' (' . htmlspecialchars_uni($wetter_data['temperatur']) . '°C) in ' . htmlspecialchars_uni($wetter_data['stadt']) . " | Mond: " . $mond_icon_html . " | Wind: " . $wind_icon_html . " " . $wind_text;
                
                eval("\$post['formatted_wetter_field'] .= \"" . $templates->get("inplayscenes_postbit_fields") . "\";");
            }
        } elseif (!empty($raw_value)) {
            // Dies ist der Original-Code für alle anderen Felder
            $allow_html = $field['allow_html'];
            $allow_mybb = $field['allow_mybb'];
            $allow_img = $field['allow_img'];
            $allow_video = $field['allow_video'];

            $value = inplayscenes_parser_fields($raw_value, $allow_html, $allow_mybb, $allow_img, $allow_video);

            $post[$identification] = $value;

            if (!empty($value)) {
                eval("\$inplayscenesfields .= \"" . $templates->get("inplayscenes_postbit_fields") . "\";");
            }
        }
    }
    // *** ENDE DER SCHLEIFE ***
    ```

---
