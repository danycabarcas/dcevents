/* jshint esversion: 6 */
(function ($) {
    'use strict';

    if (typeof DCEvents === 'undefined') return;

    var ajaxUrl    = DCEvents.ajax_url;
    var nonce      = DCEvents.nonce;
    var cancelNonce= DCEvents.cancel_nonce;
    var strings    = DCEvents.strings;

    /* ─── Countdown ──────────────────────────────────────────── */
    function initCountdowns() {
        $('.dce-countdown').each(function () {
            var $el      = $(this);
            var target   = parseInt($el.data('target'), 10);

            function tick() {
                var now  = Date.now();
                var diff = target - now;
                if (diff <= 0) {
                    $el.html('<span style="color:var(--dce-success);font-weight:700">¡El evento está iniciando!</span>');
                    return;
                }
                var days    = Math.floor(diff / 864e5);
                var hours   = Math.floor((diff % 864e5) / 36e5);
                var minutes = Math.floor((diff % 36e5) / 6e4);

                $el.find('[data-unit="days"]').text(String(days).padStart(2, '0'));
                $el.find('[data-unit="hours"]').text(String(hours).padStart(2, '0'));
                $el.find('[data-unit="minutes"]').text(String(minutes).padStart(2, '0'));
            }

            tick();
            setInterval(tick, 30000);
        });
    }

    /* ─── Registro (AJAX) ────────────────────────────────────── */
    function initRegistrationForms() {
        $(document).on('submit', '.dce-registration-form', function (e) {
            e.preventDefault();

            var $form    = $(this);
            var eventId  = $form.data('event-id');
            var $btn     = $form.find('.dce-submit-btn');
            var $msg     = $('#dce-msg-' + eventId);
            var formData = $form.serialize();

            $btn.prop('disabled', true)
                .find('.dce-btn-text').text(strings.submitting);

            $msg.hide().removeClass('success error');

            $.post(ajaxUrl, {
                action: 'dcevents_register',
                nonce:   nonce,
            } + '&' + formData, function (res) {
                $btn.prop('disabled', false).find('.dce-btn-text')
                    .text($btn.find('.dce-btn-text').data('original') || 'Inscribirse');

                if (res.success) {
                    $msg.addClass('success').html(
                        '✅ ' + res.data.message +
                        (res.data.registration_code
                            ? '<br><strong>Código: ' + res.data.registration_code + '</strong>'
                            : '')
                    ).show();
                    $form[0].reset();

                    // Scroll al mensaje
                    $('html,body').animate({ scrollTop: $msg.offset().top - 80 }, 300);
                } else {
                    $msg.addClass('error').html('❌ ' + res.data.message).show();
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                $msg.addClass('error').html('❌ ' + strings.error_generic).show();
            });
        });

        // Guardar texto original
        $('.dce-submit-btn .dce-btn-text').each(function () {
            $(this).data('original', $(this).text());
        });
    }

    /* ─── Cancelar inscripción ───────────────────────────────── */
    function initCancelButtons() {
        $(document).on('click', '.dce-cancel-registration', function () {
            var $btn  = $(this);
            var regId = $btn.data('id');

            if (!confirm(strings.confirm_cancel)) return;

            $btn.prop('disabled', true).text('Cancelando...');

            $.post(ajaxUrl, {
                action:          'dcevents_cancel_registration',
                nonce:           cancelNonce,
                registration_id: regId,
            }, function (res) {
                if (res.success) {
                    var $card = $btn.closest('.dce-my-reg-card');
                    $card.css({ opacity: 0, transition: 'opacity .3s' });
                    setTimeout(function () { $card.remove(); }, 300);

                    var $msg = $('#dce-cancel-msg');
                    $msg.addClass('dce-form-message success')
                        .html('✅ ' + res.data.message).show();
                    setTimeout(function () { $msg.fadeOut(); }, 4000);
                } else {
                    alert(res.data.message);
                    $btn.prop('disabled', false).text('Cancelar inscripción');
                }
            });
        });
    }

    /* ─── Tabs de mis inscripciones ──────────────────────────── */
    function initTabs() {
        $(document).on('click', '.dce-tab', function () {
            var tab = $(this).data('tab');
            $('.dce-tab').removeClass('active');
            $(this).addClass('active');
            $('.dce-tab-content').hide();
            $('#dce-tab-' + tab).show();
        });
    }

    /* ─── Animación de entrada ───────────────────────────────── */
    function initAnimations() {
        if (!window.IntersectionObserver) return;

        var obs = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity    = '1';
                    entry.target.style.transform  = 'translateY(0)';
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        $('.dce-event-row').each(function (i) {
            this.style.opacity    = '0';
            this.style.transform  = 'translateY(20px)';
            this.style.transition = 'opacity .4s ease ' + (i * 0.06) + 's, transform .4s ease ' + (i * 0.06) + 's';
            obs.observe(this);
        });
    }

    /* ─── Init ───────────────────────────────────────────────── */
    $(document).ready(function () {
        initCountdowns();
        initRegistrationForms();
        initCancelButtons();
        initTabs();
        initAnimations();
    });

})(jQuery);
