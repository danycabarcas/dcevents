/* jshint esversion: 6 */
(function ($) {
    'use strict';

    var nonce  = DCEventsAdmin.nonce;
    var ajaxUrl = DCEventsAdmin.ajax_url;
    var strings = DCEventsAdmin.strings;

    /* ─── Toast Helper ─────────────────────────────────────────── */
    function toast(message, type) {
        var $t = $('<div class="dce-toast ' + (type || 'success') + '">' + message + '</div>');
        $('body').append($t);
        setTimeout(function() { $t.addClass('show'); }, 10);
        setTimeout(function() {
            $t.removeClass('show');
            setTimeout(function() { $t.remove(); }, 300);
        }, 3500);
    }

    /* ─── Check-in ─────────────────────────────────────────────── */
    $(document).on('click', '.dce-btn-checkin', function () {
        var $btn = $(this);
        var regId = $btn.data('id');

        if (!confirm(strings.confirm_checkin)) return;

        $btn.prop('disabled', true).text(strings.saving);

        $.post(ajaxUrl, {
            action: 'dcevents_checkin',
            registration_id: regId,
            nonce: nonce
        }, function(res) {
            if (res.success) {
                toast(res.data.message, 'success');
                // Update UI
                $btn.closest('tr').find('.dce-btn-checkin')
                    .replaceWith('<span class="dce-checkin-done">✅ Chequeado</span>');
                // If on detail page
                if ($btn.hasClass('dce-btn')) {
                    $btn.remove();
                }
            } else {
                toast(res.data.message, 'error');
                $btn.prop('disabled', false).text('Check-in');
            }
        }).fail(function() {
            toast('Error de conexión', 'error');
            $btn.prop('disabled', false);
        });
    });

    /* ─── Status Change ────────────────────────────────────────── */
    $(document).on('change', '.dce-status-select', function () {
        var $sel   = $(this);
        var regId  = $sel.data('id');
        var status = $sel.val();

        $.post(ajaxUrl, {
            action: 'dcevents_update_status',
            registration_id: regId,
            status: status,
            nonce: nonce
        }, function(res) {
            if (res.success) {
                toast(strings.saved + ' — ' + $sel.find(':selected').text(), 'success');
                // Update border color
                var colors = {
                    pending: '#f0b849', confirmed: '#46b450', payment_pending: '#cc8800',
                    paid: '#0073aa', cancelled: '#dc3232', attended: '#00a32a'
                };
                $sel.css('border-color', colors[status] || '#888');
            } else {
                toast(res.data.message, 'error');
            }
        });
    });

    /* ─── Settings Saved Feedback ─────────────────────────────── */
    $('#dce-save-settings').on('click', function () {
        $(this).val(strings.saving);
    });

    /* ─── Layout option selection ──────────────────────────────── */
    $(document).on('change', '.dce-layout-option input', function () {
        $('.dce-layout-option').removeClass('active');
        $(this).closest('.dce-layout-option').addClass('active');
    });

    /* ─── Copy shortcode on click ──────────────────────────────── */
    $(document).on('click', '.dce-shortcode-item code', function () {
        var text = $(this).text().trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                toast('Shortcode copiado: ' + text, 'success');
            });
        }
    });

    /* ─── Auto-save status from detail page ────────────────────── */
    // (handled inline via change event above)

})(jQuery);
