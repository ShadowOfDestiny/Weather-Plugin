// wetter_icon_picker.js
(function($){
    $(document).ready(function() {
        var currentTargetInputId = null;
        var currentTargetPreviewId = null;
        var $modal = $('#wetter_icon_picker_modal'); // Modal einmalig selektieren

        if(!$modal.length) {
            console.error("Wetter Icon Picker: Modal nicht gefunden!");
            return; 
        }

        // Klick auf einen der "Icon auswählen..." Buttons
        $(document).on('click', '.open_icon_picker_button', function(e) { // Delegierter Event-Handler
            e.preventDefault();
            currentTargetInputId = $(this).data('target-input');
            currentTargetPreviewId = $(this).data('target-preview');
            
            if (!currentTargetInputId || !currentTargetPreviewId) {
                console.error("Wetter Icon Picker: Target Input/Preview nicht im Button-Data definiert.");
                return;
            }
            // Optional: Aktuellen Wert des Inputs im Modal vorselektieren/hervorheben (für später)
            $modal.fadeIn(200);
        });

        // Button zum Schließen des Icon Pickers
        $('#wetter_close_icon_picker_button').on('click', function(e) {
            e.preventDefault();
            $modal.fadeOut(200);
            currentTargetInputId = null; // Zurücksetzen
            currentTargetPreviewId = null; // Zurücksetzen
        });

        // Klick auf ein Icon im Picker
        $('#wetter_icon_list_container').on('click', '.wetter_icon_picker_item', function(e) {
            e.preventDefault();
    var selectedIconClass = $(this).data('icon-class'); // z.B. "wi-from-n" oder "wi-moon-new"
    
    if (currentTargetInputId && currentTargetPreviewId) {
        var $targetInput = $('#' + currentTargetInputId);
        var $targetPreviewDiv = $('#' + currentTargetPreviewId); // Das <div> Element der Vorschau
        var $previewIconTag = $targetPreviewDiv.find('i');   // Das <i>-Element in der Vorschau

        // 1. Verstecktes Input-Feld aktualisieren
        $targetInput.val(selectedIconClass);

        // 2. Icon-Typ vom data-Attribut des Vorschau-Divs lesen
        var iconType = $targetPreviewDiv.data('icon-type'); // Sollte "wind" oder "default" sein

        // 3. Klassen für das Vorschau-Icon <i> Tag zusammensetzen
        var classesForPreviewIcon = "wi "; // Basisklasse

        // Hinweis: Du sagtest, die Größen im Picker selbst sind OK.
        // Wenn Du die Klasse "wetter-icon-acp-gross" auch hier in der Vorschau
        // des Pickers haben wolltest, müsstest Du sie hier hinzufügen:
        // if ($previewIconTag.length && $previewIconTag.hasClass('wetter-icon-acp-gross')) {
        //     classesForPreviewIcon += "wetter-icon-acp-gross ";
        // }
        // Da Du aber sagtest, die Größen im Picker sind ok, lassen wir das hier erstmal weg,
        // die Größe der Picker-Vorschau kann über CSS für .wetter-icon-preview-box i.wi gesteuert werden.

        if (selectedIconClass === 'wi-na' || !selectedIconClass) {
            classesForPreviewIcon += 'wi-na'; // Für "Nicht verfügbar"
        } else if (iconType === 'wind') {
            // Spezifische Behandlung für Wind-Icons
            if (selectedIconClass.startsWith('wi-towards-') || selectedIconClass.startsWith('wi-from-')) {
                classesForPreviewIcon += 'wi-wind ' + selectedIconClass;
            } else {
                // Fallback, falls ein Wind-Feld einen unerwarteten Wert bekommt
                classesForPreviewIcon += selectedIconClass; 
            }
        } else {
            // Für alle anderen Icon-Typen (Mond, Hauptwetter etc.)
            classesForPreviewIcon += selectedIconClass;
        }

        // 4. Klassen des <i>-Tags in der Vorschau aktualisieren
        // Stelle sicher, dass ein <i> Tag vorhanden ist
        if ($previewIconTag.length) {
            $previewIconTag.attr('class', classesForPreviewIcon.trim());
        } else {
            // Fallback, falls das <i> Tag aus irgendeinem Grund fehlen sollte
            $targetPreviewDiv.html('<i class="' + classesForPreviewIcon.trim() + '"></i>');
        }
        
        // Sehr nützliches Debugging für die Browser-Konsole (F12)
        console.log({
            message: "Icon-Picker Vorschau aktualisiert",
            inputId: currentTargetInputId,
            previewId: currentTargetPreviewId,
            dbValue: selectedIconClass,
            iconType: iconType,
            finalClasses: classesForPreviewIcon.trim()
        });

    }  // Ende if (currentTargetInputId && currentTargetPreviewId)
    
    // Modal ausblenden und IDs zurücksetzen
    $modal.fadeOut(200);
    currentTargetInputId = null;
    currentTargetPreviewId = null;
});

        // Modal schließen bei Klick auf Hintergrund
        $modal.on('click', function(e) {
            if ($(e.target).is($modal)) { // Nur wenn direkt auf den Modal-Hintergrund geklickt wird
                $(this).fadeOut(200);
                currentTargetInputId = null; // Zurücksetzen
                currentTargetPreviewId = null; // Zurücksetzen
            }
        });
    });
})(jQuery);