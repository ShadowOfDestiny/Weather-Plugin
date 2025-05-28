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
