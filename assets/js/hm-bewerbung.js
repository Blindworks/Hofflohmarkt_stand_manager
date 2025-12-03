jQuery(document).ready(function ($) {
    // Create modal HTML
    var modalHTML = `
        <div id="hm-bewerbung-modal" class="hm-modal">
            <div class="hm-modal-content">
                <span class="hm-modal-close">&times;</span>
                <h2>Bewerbung für Standplatz</h2>
                <form id="hm-bewerbung-form">
                    <input type="hidden" id="hm-stand-id" name="stand_id" value="">
                    <input type="hidden" id="hm-stand-type" name="stand_type" value="">
                    
                    <div class="hm-form-group">
                        <label for="hm-vorname">Vorname *</label>
                        <input type="text" id="hm-vorname" name="vorname" required>
                    </div>
                    
                    <div class="hm-form-group">
                        <label for="hm-nachname">Nachname *</label>
                        <input type="text" id="hm-nachname" name="nachname" required>
                    </div>
                    
                    <div class="hm-form-group">
                        <label for="hm-email">E-Mail *</label>
                        <input type="email" id="hm-email" name="email" required>
                    </div>
                    
                    <div class="hm-form-group">
                        <label for="hm-telefon">Telefon</label>
                        <input type="tel" id="hm-telefon" name="telefon">
                    </div>
                    
                    <div class="hm-form-group">
                        <label for="hm-nachricht">Nachricht</label>
                        <textarea id="hm-nachricht" name="nachricht" rows="4"></textarea>
                    </div>
                    
                    <div class="hm-form-actions">
                        <button type="submit" class="hm-btn-primary">Bewerbung absenden</button>
                        <button type="button" class="hm-btn-secondary hm-modal-cancel">Abbrechen</button>
                    </div>
                    
                    <div id="hm-bewerbung-message" class="hm-message"></div>
                </form>
            </div>
        </div>
    `;

    // Append modal to body
    $('body').append(modalHTML);

    var modal = $('#hm-bewerbung-modal');

    // Close modal handlers
    $('.hm-modal-close, .hm-modal-cancel').on('click', function () {
        modal.hide();
        $('#hm-bewerbung-form')[0].reset();
        $('#hm-bewerbung-message').html('').removeClass('success error');
    });

    // Close modal when clicking outside
    $(window).on('click', function (e) {
        if ($(e.target).is('#hm-bewerbung-modal')) {
            modal.hide();
            $('#hm-bewerbung-form')[0].reset();
            $('#hm-bewerbung-message').html('').removeClass('success error');
        }
    });

    // Handle bewerbung button click (delegated event)
    $(document).on('click', '.hm-bewerbung-btn', function (e) {
        e.preventDefault();
        var standId = $(this).data('stand-id');
        var standType = $(this).data('stand-type');

        $('#hm-stand-id').val(standId);
        $('#hm-stand-type').val(standType);
        modal.show();
    });

    // Handle form submission
    $('#hm-bewerbung-form').on('submit', function (e) {
        e.preventDefault();

        var formData = {
            action: 'hm_submit_bewerbung',
            nonce: hmBewerbungData.nonce,
            stand_id: $('#hm-stand-id').val(),
            stand_type: $('#hm-stand-type').val(),
            vorname: $('#hm-vorname').val(),
            nachname: $('#hm-nachname').val(),
            email: $('#hm-email').val(),
            telefon: $('#hm-telefon').val(),
            nachricht: $('#hm-nachricht').val()
        };

        var messageDiv = $('#hm-bewerbung-message');
        messageDiv.html('Wird gesendet...').removeClass('success error');

        $.ajax({
            url: hmBewerbungData.ajax_url,
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    messageDiv.html(response.data.message).addClass('success').removeClass('error');
                    $('#hm-bewerbung-form')[0].reset();

                    // Close modal after 2 seconds
                    setTimeout(function () {
                        modal.hide();
                        messageDiv.html('').removeClass('success error');
                    }, 2000);
                } else {
                    messageDiv.html(response.data.message).addClass('error').removeClass('success');
                }
            },
            error: function () {
                messageDiv.html('Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.').addClass('error').removeClass('success');
            }
        });
    });
});
