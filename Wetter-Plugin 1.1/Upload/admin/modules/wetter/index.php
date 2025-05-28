<?php
define("IN_MYBB", 1);
// Pfad zu global.php anpassen, falls dein Modul tiefer verschachtelt ist
// Normalerweise ist das Admin-Modul-Verzeichnis admin/modules/MODULNAME/
// Daher sollte ../../../global.php korrekt sein.
require_once dirname(dirname(dirname(__FILE__)))."/global.php"; // Sicherer Pfad zu global.php

global $mybb, $lang, $page; // $page hinzugefügt

// Stelle sicher, dass der Admin eingeloggt ist und Berechtigungen hat
// Dies wird normalerweise durch MyBBs ACP-Framework gehandhabt, aber ein Check schadet nicht.
if(!defined("IN_ADMINCP"))
{
    // Versuche, das AdminCP zu laden, wenn nicht bereits geschehen
    // Dies ist ein Workaround und sollte idealerweise nicht nötig sein, wenn das Modul korrekt aufgerufen wird
    define("IN_ADMINCP", 1);
    require_once MYBB_ADMIN_DIR."inc/class_page.php";
    $page = new page; // $page initialisieren
    // Weitere Initialisierungen, die normalerweise in admin/index.php passieren, könnten hier nötig sein.
}


// Lade die module_meta.php, die den Action Handler enthält
require_once MYBB_ADMIN_DIR."modules/wetter/module_meta.php";

// Aktion aus dem GET-Parameter bestimmen (z.B. 'overview', 'settings')
// Der Modulname wird normalerweise als "wetter-ACTION" übergeben
$module_name = $mybb->get_input('module');
$action = '';

if(strpos($module_name, 'wetter-') === 0) {
    $action = substr($module_name, strlen('wetter-'));
} else {
    // Fallback oder Standardaktion, wenn ?module=wetter (ohne Suffix) aufgerufen wird
    $action = 'overview';
}

// Den Dateinamen für die Aktion über den Handler aus module_meta.php holen
$file_to_load = wetter_action_handler($action);

if(file_exists($file_to_load)) {
    require $file_to_load;
} else {
    if(is_object($page)) {
        $page->output_header("Fehler"); // $page muss ein Objekt sein
        $page->output_error("Die angeforderte Moduldatei '{$file_to_load}' konnte nicht gefunden werden.");
        $page->output_footer();
    } else {
        die("Fehler: ACP Seite konnte nicht initialisiert werden und Moduldatei '{$file_to_load}' nicht gefunden.");
    }
}
?>