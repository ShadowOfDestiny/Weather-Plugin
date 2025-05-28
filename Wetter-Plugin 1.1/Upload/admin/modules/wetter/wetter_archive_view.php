<?php
// Direktzugriff verhindern
if(!defined("IN_MYBB")) {
    die("Direkter Zugriff nicht erlaubt.");
}

// Lade den Header
$page->output_header("Archivierte Wetterdaten");

echo "<h2>Archivierte Wetterdaten</h2>";
echo "<p>Hier kannst du die archivierten Wetterdaten einsehen.</p>";

// Tabellen-Präfix
$prefix = TABLE_PREFIX;

// Verwende den Parameter "stadt" statt "city"
$arch_stadt = strtolower(trim($mybb->get_input('arch_stadt', MyBB::INPUT_STRING)));
if($arch_stadt !== "newyork" && $arch_stadt !== "birninzana") {
    $arch_stadt = "newyork"; // Standard
}

// Wähle die Archiv-Tabelle basierend auf der Stadt
$table = ($arch_stadt === "newyork") ? "{$prefix}wetter_newyork_archiv" : "{$prefix}wetter_birninzana_archiv";

// Abrufen der archivierten Daten
$query = $db->query("
	SELECT * FROM {$table}
	ORDER BY datum DESC, zeitspanne ASC
");

// Formular zur Auswahl der Stadt:
echo '<form method="get" action="index.php">
	<input type="hidden" name="module" value="wetter">
    <input type="hidden" name="module" value="wetter-archive_view">
    <label>Stadt wählen:</label>
    <select name="arch_stadt" onchange="this.form.submit()">
        <option value="newyork" ' . ($arch_stadt === "newyork" ? "selected" : "") . '>New York</option>
        <option value="birninzana" ' . ($arch_stadt === "birninzana" ? "selected" : "") . '>Birnin Zana</option>
    </select>
</form>';

// Ausgabe der Wetterdaten in einer Tabelle
// ... (Code bis zur Tabellenausgabe) ...
echo '<table border="1" cellspacing="0" cellpadding="5">
    <tr>
        <th>Datum</th>
        <th>Zeitspanne</th>
        <th>Temperatur</th>
        <th>Wetterlage</th>
        <th>Windrichtung</th>
        <th>Windstärke</th>
        <th>Icon</th>
        <th>Sonnenaufgang</th>
		<th>Sonnenuntergang</th>
		<th>Mondphase</th>
	</tr>';

while ($row = $db->fetch_array($query))
{
    echo "<tr>
        <td>" . htmlspecialchars($row['datum']) . "</td>
        <td>" . htmlspecialchars($row['zeitspanne']) . "</td>
        <td>" . htmlspecialchars($row['temperatur']) . "°C</td>
        <td>" . htmlspecialchars($row['wetterlage']) . "</td>
        <td>" . htmlspecialchars($row['windrichtung']) . "</td>
        <td>" . htmlspecialchars($row['windstaerke']) . " km/h</td>
        <td><img src=\"" . htmlspecialchars($row['icon']) . "\" width=\"50\"></td>
        <td>" . htmlspecialchars($row['sonnenaufgang']) . "</td>
		<td>" . htmlspecialchars($row['sonnenuntergang']) . "</td>
		<td>" . htmlspecialchars($row['mondphase']) . "</td>
		</tr>";
}

echo "</table>";

$page->output_footer();
?>