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
            var selectedIconClass = $(this).data('icon-class');
            
            if (currentTargetInputId && currentTargetPreviewId) {
			$('#' + currentTargetInputId).val(selectedIconClass); // selectedIconClass ist z.B. 'wi-towards-n'
        
			let displayPreviewClass = selectedIconClass;
			// Prüfe, ob es sich um das Vorschaufeld der Windrichtung handelt (anhand der ID oder eines data-Attributs)
			if (currentTargetPreviewId === 'windrichtung_icon_preview' && (selectedIconClass.includes('towards-') || selectedIconClass.includes('from-') || selectedIconClass.includes('beaufort-'))) {
            displayPreviewClass = 'wi-wind ' + selectedIconClass;
			}
			// Für Mond und Hauptwetter bleibt es die einfache Klasse (oder du prüfst hier auch auf 'wi-moon-...' etc., falls die auch eine Basisklasse bräuchten)

			$('#' + currentTargetPreviewId).html('<i class="wi ' + displayPreviewClass + '"></i>');
			console.log("DEBUG: Aktualisiere Input:", currentTargetInputId, "Preview:", currentTargetPreviewId, "mit Klasse:", displayPreviewClass);
		} 
            
            $modal.fadeOut(200);
            currentTargetInputId = null; // Zurücksetzen
            currentTargetPreviewId = null; // Zurücksetzen
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