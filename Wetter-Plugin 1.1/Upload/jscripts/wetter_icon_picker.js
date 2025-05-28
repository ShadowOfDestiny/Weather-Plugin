// wetter_icon_picker.js
(function($){ // jQuery noConflict-Wrapper
    $(document).ready(function() { // Stellt sicher, dass das DOM bereit ist

        console.log("DEBUG: Wetter Icon Picker JavaScript (aus .js) wird ausgeführt UND DOM ist bereit!");

        // Button zum Öffnen des Icon Pickers
        const openButton = $('#wetter_open_icon_picker_button');
        console.log("DEBUG INNERHALB READY: openButton Element:", openButton);
        console.log("DEBUG INNERHALB READY: openButton.length:", openButton.length);

        if(openButton.length) {
            openButton.on('click', function(e) {
                e.preventDefault();
                console.log("DEBUG: Open Picker Button geklickt!");
                $('#wetter_icon_picker_modal').fadeIn(200);
            });
        } else {
            console.error("DEBUG INNERHALB READY: Button #wetter_open_icon_picker_button NICHT gefunden!");
        }

        // Button zum Schließen des Icon Pickers
        const closeButton = $('#wetter_close_icon_picker_button');
        console.log("DEBUG INNERHALB READY: closeButton Element:", closeButton);
        console.log("DEBUG INNERHALB READY: closeButton.length:", closeButton.length);

        if(closeButton.length) {
            closeButton.on('click', function(e) {
                e.preventDefault();
                console.log("DEBUG: Close Picker Button geklickt!");
                $('#wetter_icon_picker_modal').fadeOut(200);
            });
        } else {
            console.error("DEBUG INNERHALB READY: Button #wetter_close_icon_picker_button NICHT gefunden!");
        }

        // Klick auf ein Icon im Picker
        const iconListContainer = $('#wetter_icon_list_container');
        console.log("DEBUG INNERHALB READY: iconListContainer Element:", iconListContainer);
        console.log("DEBUG INNERHALB READY: iconListContainer.length:", iconListContainer.length);

        if(iconListContainer.length) {
            iconListContainer.on('click', '.wetter_icon_picker_item', function(e) {
                // ... (Rest der Logik wie gehabt) ...
                e.preventDefault();
                var selectedIconClass = $(this).data('icon-class');
                console.log("DEBUG: Icon ausgewählt:", selectedIconClass);
                $('#wetter_icon_class_input').val(selectedIconClass);
                $('#wetter_icon_preview').html('<i class="wi ' + selectedIconClass + '"></i>');
                $('#wetter_icon_picker_modal').fadeOut(200);
            });
        } else {
            console.error("DEBUG INNERHALB READY: Container #wetter_icon_list_container NICHT gefunden!");
        }

        // Optional: Modal schließen, wenn auf den Hintergrund des Modals geklickt wird
        const modal = $('#wetter_icon_picker_modal');
        console.log("DEBUG INNERHALB READY: modal Element:", modal);
        console.log("DEBUG INNERHALB READY: modal.length:", modal.length);

        if(modal.length) {
            modal.on('click', function(e) {
                if ($(e.target).is('#wetter_icon_picker_modal')) {
                    console.log("DEBUG: Modal Hintergrund geklickt!");
                    $(this).fadeOut(200);
                }
            });
        } else {
            console.error("DEBUG INNERHALB READY: Modal #wetter_icon_picker_modal NICHT gefunden!");
        }

    }); // Ende von $(document).ready
})(jQuery); // Ende der Kapselung