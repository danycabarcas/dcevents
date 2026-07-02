<?php
/**
 * Vista: Escáner de Códigos QR
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Verificar permisos
if ( ! current_user_can( 'dcevents_check_in_attendee' ) ) {
    echo '<div class="dce-action-message dce-error">⚠️ ' . __('No tienes permisos para validar accesos.', 'dc-events') . '</div>';
    return;
}
?>

<div class="dce-scanner-wrap">
    <div class="dce-scanner-header">
        <h2><?php _e('Escáner de Validación', 'dc-events'); ?></h2>
        <p><?php _e('Apunta la cámara al código QR del ticket.', 'dc-events'); ?></p>
    </div>

    <!-- Contenedor del video del escáner -->
    <div id="dce-qr-reader"></div>

    <!-- Resultados -->
    <div id="dce-scan-result" class="dce-scan-result"></div>

    <!-- Controles -->
    <div class="dce-scan-btn-group">
        <button id="dce-scan-resume" class="dce-btn" style="display:none;"><?php _e('Escanear Siguiente', 'dc-events'); ?></button>
    </div>
</div>

<!-- Librería de escaneo de QR -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let html5QrcodeScanner = new Html5QrcodeScanner(
        "dce-qr-reader", 
        { fps: 10, qrbox: {width: 250, height: 250}, aspectRatio: 1.0 }, 
        /* verbose= */ false
    );
    
    let isProcessing = false;
    let resultBox = document.getElementById('dce-scan-result');
    let btnResume = document.getElementById('dce-scan-resume');

    function showResult(message, type) {
        resultBox.innerHTML = message;
        resultBox.className = 'dce-scan-result ' + type;
        resultBox.style.display = 'block';
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        
        // Esperamos formato: DCEVENT|ID|CODE
        if (!decodedText.startsWith('DCEVENT|')) {
            showResult("QR no válido para este evento.", "error");
            return;
        }

        let parts = decodedText.split('|');
        if (parts.length !== 3) {
            showResult("QR mal formado.", "error");
            return;
        }

        let regId = parts[1];
        let code = parts[2];

        isProcessing = true;
        html5QrcodeScanner.pause();
        showResult("Validando...", "");

        // Petición AJAX a WordPress
        jQuery.post(dcevents_ajax.ajax_url, {
            action: 'dcevents_checkin_qr',
            nonce: dcevents_ajax.nonce,
            registration_id: regId,
            code: code
        }, function(response) {
            if (response.success) {
                showResult("✅ " + response.data.message, "success");
            } else {
                showResult("❌ " + response.data.message, "error");
            }
            btnResume.style.display = 'inline-block';
        }).fail(function() {
            showResult("❌ Error de red al validar.", "error");
            btnResume.style.display = 'inline-block';
        });
    }

    function onScanFailure(error) {
        // Ignorar fallos continuos, solo se lanza cuando no detecta QR en el frame actual
    }

    html5QrcodeScanner.render(onScanSuccess, onScanFailure);

    btnResume.addEventListener('click', function() {
        resultBox.style.display = 'none';
        btnResume.style.display = 'none';
        isProcessing = false;
        html5QrcodeScanner.resume();
    });
});
</script>
