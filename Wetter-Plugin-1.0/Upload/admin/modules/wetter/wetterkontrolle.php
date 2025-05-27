<?php
define("IN_MYBB", 1);
require_once __DIR__ . "/../../index.php";

// Sicherstellen, dass der Modulname passt
$page->active_module = "wetter";

// Breadcrumb aktualisieren
$page->add_breadcrumb_item($lang->wetterverwaltung, "index.php?module=wetter-overview");
$page->output_header($lang->wetterverwaltung);

echo "<h2>Übersicht der Wetterdaten</h2>";
// Hier kannst du deinen Code für die Anzeige der Wetterdaten einfügen

$page->output_footer();
?>