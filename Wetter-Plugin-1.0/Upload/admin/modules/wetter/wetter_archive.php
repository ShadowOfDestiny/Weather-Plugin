<?php
if(!defined("IN_MYBB")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Lade den Header
$page->output_header("Wetterarchivierung");

// Überschrift und kurze Erklärung
echo "<h2>Archivierung vergangener Monate</h2>";
echo "<p>Hier kannst du die Wetterdaten eines bestimmten Monats archivieren.<br>
      Dabei werden die Daten aus der aktiven Tabelle in die Archivtabelle kopiert und anschließend gelöscht.<br>
      Bitte beachte: Vorher solltest du sicherstellen, dass in deiner Datenbank bereits Archivtabellen für jede Stadt existieren.</p>";

// Prüfen, ob das Formular abgeschickt wurde.
if($mybb->request_method == "post")
{
    // Hole den eingegebenen Monat (Format: YYYY-MM, z. B. 2023-04)
    $archive_date = $db->escape_string($mybb->get_input("archive_date", MyBB::INPUT_STRING));
    
    // Überprüfe, ob das Datum im richtigen Format vorliegt
    if(preg_match('/^\d{4}-\d{2}$/', $archive_date))
    {
        // Bestimme den 1. Tag des Monats und den letzten Tag des Monats.
        $from_date = $archive_date . "-01";
        $to_date   = date("Y-m-t", strtotime($from_date)); // z. B. "2023-04-30"
        
        // Kopiere Einträge für New York:
        $query_copy_ny = "INSERT INTO " . TABLE_PREFIX . "wetter_newyork_archiv 
            SELECT * FROM " . TABLE_PREFIX . "wetter_newyork 
            WHERE datum BETWEEN '{$from_date}' AND '{$to_date}'";
        $copy_ny = $db->write_query($query_copy_ny);
        
        // Lösche die archivierten Einträge aus der aktuellen New-York-Tabelle:
        $query_delete_ny = "DELETE FROM " . TABLE_PREFIX . "wetter_newyork 
            WHERE datum BETWEEN '{$from_date}' AND '{$to_date}'";
        $delete_ny = $db->write_query($query_delete_ny);
        
        // Kopiere Einträge für Birnin Zana:
        $query_copy_bz = "INSERT INTO " . TABLE_PREFIX . "wetter_birninzana_archiv 
            SELECT * FROM " . TABLE_PREFIX . "wetter_birninzana 
            WHERE datum BETWEEN '{$from_date}' AND '{$to_date}'";
        $copy_bz = $db->write_query($query_copy_bz);
        
        // Lösche die archivierten Einträge aus der aktuellen Birnin-Zana-Tabelle:
        $query_delete_bz = "DELETE FROM " . TABLE_PREFIX . "wetter_birninzana 
            WHERE datum BETWEEN '{$from_date}' AND '{$to_date}'";
        $delete_bz = $db->write_query($query_delete_bz);
        
        echo "<p>Archivierung für den Zeitraum <strong>{$from_date}</strong> bis <strong>{$to_date}</strong> erfolgreich durchgeführt.</p>";
    }
    else
    {
        echo "<p>Bitte gib ein gültiges Datum im Format YYYY-MM ein (z. B. 2023-04).</p>";
    }
}

// Formular zur Eingabe des Archivierungs-Monats
echo '<form method="post" action="index.php?module=wetter-archive">
    <input type="hidden" name="my_post_key" value="'.$mybb->post_code.'">
    <label>Datum (YYYY-MM): </label>
    <input type="text" name="archive_date" placeholder="2016-10" required>
    <input type="submit" value="Archivieren">
</form>';

// Ausgabe des Footers
$page->output_footer();
?>