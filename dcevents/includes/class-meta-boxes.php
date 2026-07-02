<?php
/**
 * Meta Boxes para el CPT dc_event
 *  - Fechas, precios y estado
 *  - Logo del evento (1080×1080 PNG)
 *  - Lugar (físico o virtual)
 *  - Configuración de inscripciones
 *  - Estadísticas (sidebar)
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Meta_Boxes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'add_meta_boxes',              [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_dc_event',          [ $this, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts',       [ $this, 'enqueue_scripts' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'gutenberg_notice' ] );
    }

    // ─── Assets ───────────────────────────────────────────────────────────────
    public function enqueue_scripts( $hook ) {
        global $post;
        if ( ( 'post.php' !== $hook && 'post-new.php' !== $hook )
            || ! isset( $post->post_type )
            || 'dc_event' !== $post->post_type ) {
            return;
        }
        wp_enqueue_media();
    }

    // ─── Aviso flotante en Gutenberg ──────────────────────────────────────────
    public function gutenberg_notice() {
        global $post;
        if ( ! $post || 'dc_event' !== get_post_type( $post ) ) return;

        $script = <<<'JS'
(function() {
    var attempts = 0;
    var interval = setInterval(function() {
        attempts++;
        if (attempts > 30) { clearInterval(interval); return; }
        if (typeof wp === 'undefined' || !wp.blocks) return;
        clearInterval(interval);

        var banner = document.createElement('div');
        banner.id = 'dce-gutenberg-notice';
        banner.style.cssText = [
            'position:fixed','bottom:20px','left:50%','transform:translateX(-50%)',
            'background:#fae100','color:#121212','padding:12px 22px',
            'border-radius:30px','font-weight:700','font-size:13px',
            'z-index:99999','box-shadow:0 6px 24px rgba(0,0,0,.18)',
            'display:flex','align-items:center','gap:12px','cursor:pointer',
            'white-space:nowrap','border:2px solid rgba(0,0,0,.1)'
        ].join(';');
        banner.innerHTML = '📋 Los campos del evento están <u>debajo del editor</u> — haz scroll ↓ &nbsp; <span style="background:rgba(0,0,0,.15);padding:3px 10px;border-radius:20px;font-size:11px">Click para cerrar</span>';
        banner.onclick = function() { this.remove(); };
        document.body.appendChild(banner);
        setTimeout(function() {
            if (document.getElementById('dce-gutenberg-notice')) {
                document.getElementById('dce-gutenberg-notice').remove();
            }
        }, 12000);
    }, 400);
})();
JS;
        wp_add_inline_script( 'wp-blocks', $script );
    }

    // ─── Registrar Meta Boxes ─────────────────────────────────────────────────
    public function add_meta_boxes() {
        add_meta_box(
            'dcevents_event_details',
            __( '📅 Fechas, Precios y Estado del Evento', 'dc-events' ),
            [ $this, 'render_event_details_box' ],
            'dc_event', 'normal', 'high'
        );
        add_meta_box(
            'dcevents_event_logo',
            __( '🎨 Logo del Evento (1080×1080 PNG)', 'dc-events' ),
            [ $this, 'render_logo_box' ],
            'dc_event', 'side', 'high'
        );
        add_meta_box(
            'dcevents_event_location',
            __( '📍 Lugar del Evento', 'dc-events' ),
            [ $this, 'render_location_box' ],
            'dc_event', 'normal', 'default'
        );
        add_meta_box(
            'dcevents_event_registration',
            __( '📋 Configuración de Inscripciones', 'dc-events' ),
            [ $this, 'render_registration_box' ],
            'dc_event', 'normal', 'default'
        );
        add_meta_box(
            'dcevents_event_stats',
            __( '📊 Estadísticas en vivo', 'dc-events' ),
            [ $this, 'render_stats_box' ],
            'dc_event', 'side', 'default'
        );
    }

    // ─── Estilos comunes ──────────────────────────────────────────────────────
    private function styles() {
        static $printed = false;
        if ( $printed ) return;
        $printed = true;
        ?>
        <style id="dcemb-styles">
        .dcemb-wrap { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif; color:#1e1e1e; }
        .dcemb-section { margin-bottom:16px; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; }
        .dcemb-section-title { background:#f8f8f8; border-bottom:1px solid #e0e0e0; padding:9px 14px; font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#555; }
        .dcemb-grid-4 { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; padding:14px; }
        .dcemb-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; padding:14px; }
        .dcemb-grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; padding:14px; }
        @media(max-width:900px){ .dcemb-grid-4 { grid-template-columns:1fr 1fr; } .dcemb-grid-3 { grid-template-columns:1fr 1fr; } }
        .dcemb-field { display:flex; flex-direction:column; gap:5px; }
        .dcemb-field label { font-size:12px; font-weight:600; color:#555; }
        .dcemb-req { color:#dc3232; }
        .dcemb-field input[type="date"],
        .dcemb-field input[type="time"],
        .dcemb-field input[type="number"],
        .dcemb-field input[type="text"],
        .dcemb-field input[type="url"],
        .dcemb-field select {
            padding:8px 10px; border:1.5px solid #ddd; border-radius:6px;
            font-size:13px; width:100%; box-sizing:border-box; transition:border-color .15s;
        }
        .dcemb-field input:focus, .dcemb-field select:focus {
            outline:none; border-color:#fae100; box-shadow:0 0 0 2px rgba(250,225,0,.25);
        }
        .dcemb-toggle-label { display:flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500; padding:6px 0; }

        /* Precio tipo */
        .dcemb-price-type-bar { display:flex; gap:6px; flex-wrap:wrap; padding:12px 14px; border-bottom:1px solid #f0f0f0; }
        .dcemb-pt-opt { display:flex; align-items:center; gap:6px; padding:8px 14px; border:2px solid #e0e0e0; border-radius:20px; cursor:pointer; font-size:13px; font-weight:600; transition:all .15s; user-select:none; }
        .dcemb-pt-opt input[type="radio"] { width:14px; height:14px; cursor:pointer; }
        .dcemb-pt-opt:has(input:checked), .dcemb-pt-opt.active { border-color:#fae100; background:#fef9d0; }
        .dcemb-price-panel { padding:14px; }

        /* Tramos */
        .dcemb-tier-row { background:#f9f9f9; border:1px solid #e8e8e8; border-radius:8px; padding:12px 14px; margin-bottom:8px; }
        .dcemb-tier-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
        .dcemb-tier-num { font-weight:700; font-size:12px; color:#0073aa; text-transform:uppercase; letter-spacing:.5px; }
        .dcemb-tier-remove { background:none; border:1px solid #e0e0e0; color:#dc3232; cursor:pointer; font-size:15px; border-radius:4px; padding:2px 8px; line-height:1.5; }
        .dcemb-tier-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:10px; }
        @media(max-width:900px){ .dcemb-tier-grid { grid-template-columns:1fr 1fr; } }
        .dcemb-tier-grid .dcemb-field label { font-size:11px; }
        .dcemb-tier-grid input { font-size:12px; }
        .dcemb-add-btn { display:inline-flex; align-items:center; gap:6px; background:#0073aa; color:#fff; border:none; border-radius:6px; padding:8px 14px; font-size:12px; font-weight:700; cursor:pointer; }
        .dcemb-add-btn:hover { background:#005a8c; }
        .dcemb-tier-legend { margin-top:12px; background:#f9f9f9; border-radius:6px; padding:10px 12px; border-left:3px solid #fae100; font-size:12px; }
        .dcemb-tier-legend table { width:100%; border-collapse:collapse; margin:6px 0; }
        .dcemb-tier-legend td { padding:3px 6px; border-bottom:1px solid #eee; }
        .dcemb-tier-legend p { margin:4px 0 0; font-size:11px; color:#666; }
        .dcemb-free-badge { background:#d4edda; color:#155724; padding:12px 14px; border-radius:6px; font-weight:600; font-size:13px; }
        .dcemb-help { font-size:12px; color:#555; background:#f0f8ff; padding:8px 12px; border-radius:6px; border-left:3px solid #0073aa; margin-bottom:10px; }

        /* Logo box */
        .dcemb-logo-desc { font-size:12px; color:#555; line-height:1.6; margin-bottom:10px; background:#f9f9f9; padding:8px 10px; border-radius:6px; border-left:3px solid #fae100; }

        /* Custom fields */
        .dcemb-cf-row { display:grid; grid-template-columns:2fr 1fr 90px 30px; gap:8px; align-items:center; margin-bottom:6px; background:#f9f9f9; padding:7px 9px; border-radius:6px; border:1px solid #eee; }
        .dcemb-cf-row input, .dcemb-cf-row select { padding:6px 8px; border:1px solid #ddd; border-radius:4px; font-size:12px; width:100%; box-sizing:border-box; }
        .dcemb-cf-remove { background:#dc3232; color:#fff; border:none; border-radius:4px; padding:5px 8px; cursor:pointer; font-size:13px; }
        </style>
        <?php
    }

    // ─── Box: Fechas, Precios y Estado ────────────────────────────────────────
    public function render_event_details_box( $post ) {
        $this->styles();
        wp_nonce_field( 'dcevents_save_meta', 'dcevents_meta_nonce' );

        $start_date   = get_post_meta( $post->ID, '_dcevents_start_date', true );
        $end_date     = get_post_meta( $post->ID, '_dcevents_end_date', true );
        $start_time   = get_post_meta( $post->ID, '_dcevents_start_time', true );
        $end_time     = get_post_meta( $post->ID, '_dcevents_end_time', true );
        $status       = get_post_meta( $post->ID, '_dcevents_status', true ) ?: 'active';
        $featured     = get_post_meta( $post->ID, '_dcevents_featured', true );
        $currency     = get_post_meta( $post->ID, '_dcevents_currency', true ) ?: 'COP';
        $price_type   = get_post_meta( $post->ID, '_dcevents_price_type', true ) ?: 'single';
        $single_price = get_post_meta( $post->ID, '_dcevents_price', true );
        $price_tiers  = get_post_meta( $post->ID, '_dcevents_price_tiers', true ) ?: [];
        ?>
        <div class="dcemb-wrap">

            <!-- Fechas -->
            <div class="dcemb-section">
                <div class="dcemb-section-title">📅 Fechas y Horarios</div>
                <div class="dcemb-grid-4">
                    <div class="dcemb-field">
                        <label>Fecha de inicio <span class="dcemb-req">*</span></label>
                        <input type="date" name="dcevents_start_date" value="<?php echo esc_attr($start_date); ?>" required>
                    </div>
                    <div class="dcemb-field">
                        <label>Hora de inicio</label>
                        <input type="time" name="dcevents_start_time" value="<?php echo esc_attr($start_time); ?>">
                    </div>
                    <div class="dcemb-field">
                        <label>Fecha de fin (opcional)</label>
                        <input type="date" name="dcevents_end_date" value="<?php echo esc_attr($end_date); ?>">
                    </div>
                    <div class="dcemb-field">
                        <label>Hora de fin</label>
                        <input type="time" name="dcevents_end_time" value="<?php echo esc_attr($end_time); ?>">
                    </div>
                </div>
            </div>

            <!-- Estado -->
            <div class="dcemb-section">
                <div class="dcemb-section-title">⚙️ Estado</div>
                <div class="dcemb-grid-3">
                    <div class="dcemb-field">
                        <label>Estado del evento</label>
                        <select name="dcevents_status">
                            <option value="active"    <?php selected($status,'active'); ?>>✅ Activo</option>
                            <option value="full"      <?php selected($status,'full'); ?>>🔴 Agotado</option>
                            <option value="cancelled" <?php selected($status,'cancelled'); ?>>❌ Cancelado</option>
                            <option value="draft"     <?php selected($status,'draft'); ?>>📝 Borrador interno</option>
                        </select>
                    </div>
                    <div class="dcemb-field">
                        <label>Moneda</label>
                        <select name="dcevents_currency">
                            <option value="COP" <?php selected($currency,'COP'); ?>>🇨🇴 COP — Peso colombiano</option>
                            <option value="USD" <?php selected($currency,'USD'); ?>>🇺🇸 USD — Dólar</option>
                            <option value="EUR" <?php selected($currency,'EUR'); ?>>🇪🇺 EUR — Euro</option>
                        </select>
                    </div>
                    <div class="dcemb-field" style="justify-content:flex-end">
                        <label class="dcemb-toggle-label">
                            <input type="checkbox" name="dcevents_featured" value="1" <?php checked($featured,'1'); ?>>
                            <span>⭐ Evento Destacado</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Precios -->
            <div class="dcemb-section">
                <div class="dcemb-section-title">💳 Precio del Evento</div>

                <div class="dcemb-price-type-bar">
                    <?php
                    $tipos = [
                        'free'   => '🎉 Gratuito',
                        'single' => '💰 Precio único',
                        'tiered' => '📊 Precios por tramos / preventa',
                    ];
                    foreach ( $tipos as $val => $lbl ) : ?>
                    <label class="dcemb-pt-opt <?php echo $price_type === $val ? 'active' : ''; ?>">
                        <input type="radio" name="dcevents_price_type" value="<?php echo $val; ?>"
                               <?php checked($price_type, $val); ?>>
                        <?php echo $lbl; ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Precio único -->
                <div id="dcemb-panel-single" class="dcemb-price-panel" <?php echo $price_type !== 'single' ? 'style="display:none"' : ''; ?>>
                    <div class="dcemb-grid-2">
                        <div class="dcemb-field">
                            <label>Precio del evento <span class="dcemb-req">*</span></label>
                            <input type="number" name="dcevents_price" value="<?php echo esc_attr($single_price); ?>"
                                   min="0" step="100" placeholder="15000">
                        </div>
                        <div style="display:flex;align-items:flex-end;padding-bottom:4px">
                            <p style="margin:0;font-size:12px;color:#666;background:#f9f9f9;padding:10px;border-radius:6px;border-left:3px solid #fae100">
                                💡 Para <strong>gratuito</strong>, usa la opción de arriba. Para múltiples precios (preventa, etc.) usa <strong>Precios por tramos</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Gratuito -->
                <div id="dcemb-panel-free" class="dcemb-price-panel" <?php echo $price_type !== 'free' ? 'style="display:none"' : ''; ?>>
                    <div class="dcemb-free-badge">🎉 Este evento es gratuito — no se cobrarán inscripciones.</div>
                </div>

                <!-- Tramos -->
                <div id="dcemb-panel-tiered" class="dcemb-price-panel" <?php echo $price_type !== 'tiered' ? 'style="display:none"' : ''; ?>>
                    <p class="dcemb-help">
                        ✏️ Define tramos de precio. El sistema aplica <strong>automáticamente</strong> el precio vigente según la fecha de hoy.
                        Deja "Fecha fin" vacío en el último tramo = precio abierto indefinidamente.
                    </p>

                    <div id="dcemb-tiers-container">
                        <?php
                        $initial_tiers = ! empty($price_tiers) ? $price_tiers : [
                            ['name' => 'Preventa',         'price' => '', 'start_date' => '', 'end_date' => ''],
                            ['name' => 'Segunda preventa', 'price' => '', 'start_date' => '', 'end_date' => ''],
                            ['name' => 'Precio Full',      'price' => '', 'start_date' => '', 'end_date' => ''],
                        ];
                        foreach ( $initial_tiers as $i => $tier ) : ?>
                        <div class="dcemb-tier-row">
                            <div class="dcemb-tier-header">
                                <span class="dcemb-tier-num">Tramo <?php echo $i + 1; ?></span>
                                <button type="button" class="dcemb-tier-remove" title="Eliminar tramo">✕ Eliminar</button>
                            </div>
                            <div class="dcemb-tier-grid">
                                <div class="dcemb-field">
                                    <label>Nombre del tramo</label>
                                    <input type="text" name="dcevents_price_tiers[<?php echo $i; ?>][name]"
                                           value="<?php echo esc_attr($tier['name']); ?>"
                                           placeholder="Preventa, Precio Full...">
                                </div>
                                <div class="dcemb-field">
                                    <label>Precio ($)</label>
                                    <input type="number" name="dcevents_price_tiers[<?php echo $i; ?>][price]"
                                           value="<?php echo esc_attr($tier['price']); ?>"
                                           min="0" step="100" placeholder="10000">
                                </div>
                                <div class="dcemb-field">
                                    <label>Fecha inicio tramo</label>
                                    <input type="date" name="dcevents_price_tiers[<?php echo $i; ?>][start_date]"
                                           value="<?php echo esc_attr($tier['start_date']); ?>">
                                </div>
                                <div class="dcemb-field">
                                    <label>Fecha fin <small style="font-weight:400">(vacío=abierto)</small></label>
                                    <input type="date" name="dcevents_price_tiers[<?php echo $i; ?>][end_date]"
                                           value="<?php echo esc_attr($tier['end_date']); ?>">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" id="dcemb-add-tier" class="dcemb-add-btn">
                        + Agregar tramo de precio
                    </button>

                    <div class="dcemb-tier-legend">
                        <strong>📌 Ejemplo de precios por tramos:</strong>
                        <table>
                            <tr><td><strong>Preventa</strong></td><td>$10.000</td><td>01 Dic → 31 Dic</td></tr>
                            <tr><td><strong>Segunda preventa</strong></td><td>$12.000</td><td>01 Ene → 14 Ene</td></tr>
                            <tr><td><strong>Precio Full</strong></td><td>$15.000</td><td>15 Ene → (sin fin)</td></tr>
                        </table>
                        <p>El sistema calcula el precio vigente automáticamente al guardar el evento.</p>
                    </div>

                    <!-- Precio activo calculado -->
                    <?php if ( $price_type === 'tiered' && ! empty($price_tiers) ) :
                        $today = date('Y-m-d');
                        $active_tier = null;
                        foreach ($price_tiers as $t) {
                            $ok = true;
                            if (!empty($t['start_date']) && $today < $t['start_date']) $ok = false;
                            if (!empty($t['end_date'])   && $today > $t['end_date'])   $ok = false;
                            if ($ok) { $active_tier = $t; break; }
                        }
                    ?>
                    <div style="margin-top:12px;background:<?php echo $active_tier ? '#d4edda' : '#fff3cd'; ?>;padding:10px 14px;border-radius:6px;font-size:13px;font-weight:600">
                        <?php if ($active_tier) :
                            echo '✅ Precio activo HOY: <strong>' . esc_html($active_tier['name']) . ' — $' . number_format($active_tier['price'], 0, ',', '.') . '</strong>';
                        else : ?>
                            ⚠️ Ningún tramo está activo para la fecha de hoy. Revisa las fechas de los tramos.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- .dcemb-wrap -->

        <script>
        (function($) {
            // Cambiar tipo de precio
            $('[name="dcevents_price_type"]').on('change', function() {
                $('.dcemb-pt-opt').removeClass('active');
                $(this).closest('.dcemb-pt-opt').addClass('active');
                $('.dcemb-price-panel').hide();
                $('#dcemb-panel-' + this.value).show();
            });

            // Sincronizar clase active al cargar
            $('[name="dcevents_price_type"]:checked').closest('.dcemb-pt-opt').addClass('active');

            var tierIdx = <?php echo count($initial_tiers); ?>;

            // Agregar tramo
            $('#dcemb-add-tier').on('click', function() {
                var n = tierIdx;
                var html = '<div class="dcemb-tier-row">' +
                    '<div class="dcemb-tier-header">' +
                        '<span class="dcemb-tier-num">Tramo ' + (n+1) + '</span>' +
                        '<button type="button" class="dcemb-tier-remove">✕ Eliminar</button>' +
                    '</div>' +
                    '<div class="dcemb-tier-grid">' +
                        '<div class="dcemb-field"><label>Nombre del tramo</label>' +
                            '<input type="text" name="dcevents_price_tiers['+n+'][name]" placeholder="Nombre..."></div>' +
                        '<div class="dcemb-field"><label>Precio ($)</label>' +
                            '<input type="number" name="dcevents_price_tiers['+n+'][price]" min="0" step="100" placeholder="10000"></div>' +
                        '<div class="dcemb-field"><label>Fecha inicio</label>' +
                            '<input type="date" name="dcevents_price_tiers['+n+'][start_date]"></div>' +
                        '<div class="dcemb-field"><label>Fecha fin <small>(vacío=abierto)</small></label>' +
                            '<input type="date" name="dcevents_price_tiers['+n+'][end_date]"></div>' +
                    '</div></div>';
                $('#dcemb-tiers-container').append(html);
                tierIdx++;
                renumberTiers();
            });

            // Eliminar tramo
            $(document).on('click', '.dcemb-tier-remove', function() {
                $(this).closest('.dcemb-tier-row').remove();
                renumberTiers();
            });

            function renumberTiers() {
                $('#dcemb-tiers-container .dcemb-tier-row').each(function(i) {
                    $(this).find('.dcemb-tier-num').text('Tramo ' + (i+1));
                });
            }
        })(jQuery);
        </script>
        <?php
    }

    // ─── Box: Logo del evento ─────────────────────────────────────────────────
    public function render_logo_box( $post ) {
        $this->styles();
        $logo_id  = get_post_meta( $post->ID, '_dcevents_logo_id', true );
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        $meta     = $logo_id ? wp_get_attachment_metadata( $logo_id ) : null;
        ?>
        <div style="font-family:-apple-system,sans-serif">
            <div class="dcemb-logo-desc">
                Sube el <strong>logo o imagen cuadrada</strong> del evento.<br>
                📐 Formato ideal: <strong>PNG 1080×1080 px</strong><br>
                Se usará en la boleta PDF y branding del evento.
            </div>

            <div id="dcemb-logo-preview" style="<?php echo $logo_url ? '' : 'display:none'; ?>;margin-bottom:10px">
                <img id="dcemb-logo-img" src="<?php echo esc_url($logo_url); ?>"
                     style="width:100%;height:auto;border-radius:8px;border:1px solid #e0e0e0;display:block">
                <?php if ($meta && isset($meta['width'])) :
                    $w   = $meta['width'];
                    $h   = $meta['height'];
                    $ok  = ($w >= 1000 && $h >= 1000 && abs($w - $h) < 100);
                ?>
                <p id="dcemb-logo-dims"
                   style="margin:6px 0 0;font-size:12px;color:<?php echo $ok ? '#46b450' : '#dc3232'; ?>;font-weight:600">
                    <?php echo ($ok ? '✅' : '⚠️') . " {$w}×{$h} px" . ($ok ? ' — Perfecto' : ' — Recomendado: 1080×1080'); ?>
                </p>
                <?php endif; ?>
            </div>

            <input type="hidden" name="dcevents_logo_id" id="dcemb-logo-id" value="<?php echo esc_attr($logo_id); ?>">

            <div style="display:flex;gap:8px">
                <button type="button" id="dcemb-logo-select" class="button button-primary">
                    <?php echo $logo_url ? '🔄 Cambiar' : '📤 Subir logo'; ?>
                </button>
                <button type="button" id="dcemb-logo-remove" class="button"
                        style="<?php echo $logo_url ? '' : 'display:none'; ?>">
                    🗑️
                </button>
            </div>
        </div>

        <script>
        (function($) {
            var frame;
            $('#dcemb-logo-select').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({
                    title: 'Logo del evento',
                    button: { text: 'Usar esta imagen' },
                    library: { type: 'image' },
                    multiple: false,
                });
                frame.on('select', function() {
                    var a = frame.state().get('selection').first().toJSON();
                    $('#dcemb-logo-id').val(a.id);
                    $('#dcemb-logo-img').attr('src', a.url);
                    $('#dcemb-logo-preview').show();
                    $('#dcemb-logo-select').text('🔄 Cambiar');
                    $('#dcemb-logo-remove').show();
                    var ok  = a.width >= 1000 && a.height >= 1000 && Math.abs(a.width - a.height) < 100;
                    var $d  = $('#dcemb-logo-dims');
                    if (!$d.length) {
                        $d = $('<p id="dcemb-logo-dims" style="margin:6px 0 0;font-size:12px;font-weight:600"></p>');
                        $('#dcemb-logo-preview').append($d);
                    }
                    $d.css('color', ok ? '#46b450' : '#dc3232')
                      .text((ok ? '✅' : '⚠️') + ' ' + a.width + '×' + a.height + ' px' + (ok ? ' — Perfecto' : ' — Recomendado: 1080×1080'));
                });
                frame.open();
            });

            $('#dcemb-logo-remove').on('click', function() {
                $('#dcemb-logo-id').val('');
                $('#dcemb-logo-preview').hide();
                $('#dcemb-logo-select').text('📤 Subir logo');
                $(this).hide();
            });
        })(jQuery);
        </script>
        <?php
    }

    // ─── Box: Lugar ───────────────────────────────────────────────────────────
    public function render_location_box( $post ) {
        $this->styles();
        $venue       = get_post_meta( $post->ID, '_dcevents_venue', true );
        $address     = get_post_meta( $post->ID, '_dcevents_address', true );
        $city        = get_post_meta( $post->ID, '_dcevents_city', true ) ?: 'Santa Marta';
        $country     = get_post_meta( $post->ID, '_dcevents_country', true ) ?: 'Colombia';
        $maps_url    = get_post_meta( $post->ID, '_dcevents_maps_url', true );
        $is_virtual  = get_post_meta( $post->ID, '_dcevents_is_virtual', true );
        $virtual_url = get_post_meta( $post->ID, '_dcevents_virtual_url', true );
        ?>
        <div class="dcemb-wrap">
            <div class="dcemb-section">
                <div class="dcemb-section-title">Tipo de evento</div>
                <div style="padding:12px 14px">
                    <label class="dcemb-toggle-label">
                        <input type="checkbox" name="dcevents_is_virtual" value="1" id="dcemb_virtual"
                               <?php checked($is_virtual,'1'); ?>>
                        🌐 Evento Virtual / Online
                    </label>
                </div>
            </div>

            <div id="dcemb-loc-physical" class="dcemb-section" <?php echo $is_virtual === '1' ? 'style="display:none"' : ''; ?>>
                <div class="dcemb-section-title">📍 Lugar Físico</div>
                <div class="dcemb-grid-2">
                    <div class="dcemb-field">
                        <label>Nombre del lugar / recinto</label>
                        <input type="text" name="dcevents_venue" value="<?php echo esc_attr($venue); ?>"
                               placeholder="Centro de Convenciones Tayrona">
                    </div>
                    <div class="dcemb-field">
                        <label>Ciudad</label>
                        <input type="text" name="dcevents_city" value="<?php echo esc_attr($city); ?>"
                               placeholder="Santa Marta">
                    </div>
                    <div class="dcemb-field">
                        <label>Dirección completa</label>
                        <input type="text" name="dcevents_address" value="<?php echo esc_attr($address); ?>"
                               placeholder="Calle 10 #2-45, Sector Rodadero">
                    </div>
                    <div class="dcemb-field">
                        <label>País</label>
                        <input type="text" name="dcevents_country" value="<?php echo esc_attr($country); ?>"
                               placeholder="Colombia">
                    </div>
                    <div class="dcemb-field" style="grid-column:1/-1">
                        <label>Enlace de Google Maps (opcional)</label>
                        <input type="url" name="dcevents_maps_url" value="<?php echo esc_url($maps_url); ?>"
                               placeholder="https://maps.google.com/...">
                    </div>
                </div>
            </div>

            <div id="dcemb-loc-virtual" class="dcemb-section" <?php echo $is_virtual !== '1' ? 'style="display:none"' : ''; ?>>
                <div class="dcemb-section-title">🌐 Evento Virtual</div>
                <div style="padding:14px">
                    <div class="dcemb-field">
                        <label>Enlace del evento (Zoom / YouTube / Meet / Teams...)</label>
                        <input type="url" name="dcevents_virtual_url" value="<?php echo esc_url($virtual_url); ?>"
                               placeholder="https://zoom.us/j/...">
                    </div>
                    <p style="font-size:12px;color:#666;margin:6px 0 0">
                        🔒 Este enlace solo se compartirá con los inscritos confirmados.
                    </p>
                </div>
            </div>
        </div>
        <script>
        document.getElementById('dcemb_virtual').addEventListener('change', function() {
            document.getElementById('dcemb-loc-physical').style.display = this.checked ? 'none' : '';
            document.getElementById('dcemb-loc-virtual').style.display  = this.checked ? '' : 'none';
        });
        </script>
        <?php
    }

    // ─── Box: Inscripciones ───────────────────────────────────────────────────
    public function render_registration_box( $post ) {
        $this->styles();
        $max_capacity     = get_post_meta( $post->ID, '_dcevents_max_capacity', true );
        $registration_open= get_post_meta( $post->ID, '_dcevents_registration_open', true );
        $reg_deadline     = get_post_meta( $post->ID, '_dcevents_registration_deadline', true );
        $require_approval = get_post_meta( $post->ID, '_dcevents_require_approval', true );
        $custom_fields    = get_post_meta( $post->ID, '_dcevents_custom_fields', true ) ?: [];
        ?>
        <div class="dcemb-wrap">
            <div class="dcemb-section">
                <div class="dcemb-section-title">⚙️ Configuración General</div>
                <div class="dcemb-grid-3">
                    <div class="dcemb-field">
                        <label>Capacidad máxima de asistentes</label>
                        <input type="number" name="dcevents_max_capacity"
                               value="<?php echo esc_attr($max_capacity); ?>"
                               min="0" placeholder="0 = ilimitado">
                        <span style="font-size:11px;color:#888">0 = sin límite de cupos</span>
                    </div>
                    <div class="dcemb-field">
                        <label>Fecha límite de inscripción</label>
                        <input type="date" name="dcevents_registration_deadline"
                               value="<?php echo esc_attr($reg_deadline); ?>">
                    </div>
                    <div class="dcemb-field" style="justify-content:flex-end;gap:8px">
                        <label class="dcemb-toggle-label">
                            <input type="checkbox" name="dcevents_registration_open" value="1"
                                   <?php checked($registration_open !== '0', true); ?>>
                            ✅ Inscripciones abiertas
                        </label>
                        <label class="dcemb-toggle-label">
                            <input type="checkbox" name="dcevents_require_approval" value="1"
                                   <?php checked($require_approval,'1'); ?>>
                            👤 Requiere aprobación manual
                        </label>
                    </div>
                </div>
            </div>

            <div class="dcemb-section">
                <div class="dcemb-section-title">📝 Campos Adicionales del Formulario</div>
                <div style="padding:12px 14px">
                    <p style="font-size:12px;color:#666;margin:0 0 10px">
                        Siempre se incluyen: <strong>Nombre, Apellido, Email, Teléfono, Documento</strong>.
                        Aquí agrega campos extras para este evento.
                    </p>
                    <div id="dcemb-custom-fields">
                        <?php foreach ($custom_fields as $i => $field) : ?>
                        <div class="dcemb-cf-row">
                            <?php echo $this->cf_row($i, $field); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="dcemb-add-field" class="dcemb-add-btn" style="margin-top:6px">
                        + Campo personalizado
                    </button>
                </div>
            </div>
        </div>

        <script>
        (function($) {
            var cfi = <?php echo count($custom_fields); ?>;
            $('#dcemb-add-field').on('click', function() {
                var i = cfi++;
                var html = '<div class="dcemb-cf-row">' +
                    '<input type="text" name="dcevents_custom_fields['+i+'][label]" placeholder="Nombre del campo">' +
                    '<select name="dcevents_custom_fields['+i+'][type]">' +
                        '<option value="text">Texto corto</option>' +
                        '<option value="textarea">Texto largo</option>' +
                        '<option value="email">Email</option>' +
                        '<option value="number">Número</option>' +
                    '</select>' +
                    '<label style="display:flex;align-items:center;gap:4px;font-size:12px;white-space:nowrap">' +
                        '<input type="checkbox" name="dcevents_custom_fields['+i+'][required]" value="1"> Requerido</label>' +
                    '<button type="button" class="dcemb-cf-remove">✕</button>' +
                    '</div>';
                $('#dcemb-custom-fields').append(html);
            });
            $(document).on('click', '.dcemb-cf-remove', function() {
                $(this).closest('.dcemb-cf-row').remove();
            });
        })(jQuery);
        </script>
        <?php
    }

    private function cf_row( $i, $field ) {
        $label    = isset($field['label'])    ? esc_attr($field['label'])    : '';
        $type     = isset($field['type'])     ? esc_attr($field['type'])     : 'text';
        $required = isset($field['required']) ? $field['required']           : '0';

        $out = '<input type="text" name="dcevents_custom_fields[' . $i . '][label]" value="' . $label . '" placeholder="Nombre del campo">';
        $out .= '<select name="dcevents_custom_fields[' . $i . '][type]">';
        foreach (['text' => 'Texto corto', 'textarea' => 'Texto largo', 'email' => 'Email', 'number' => 'Número'] as $v => $l) {
            $out .= '<option value="' . $v . '" ' . selected($type, $v, false) . '>' . $l . '</option>';
        }
        $out .= '</select>';
        $out .= '<label style="display:flex;align-items:center;gap:4px;font-size:12px;white-space:nowrap"><input type="checkbox" name="dcevents_custom_fields[' . $i . '][required]" value="1" ' . checked($required,'1',false) . '> Requerido</label>';
        $out .= '<button type="button" class="dcemb-cf-remove">✕</button>';
        return $out;
    }

    // ─── Box: Estadísticas ────────────────────────────────────────────────────
    public function render_stats_box( $post ) {
        if ( 'publish' !== $post->post_status ) {
            echo '<div style="text-align:center;padding:16px;color:#888;font-size:13px">';
            echo '<p style="font-size:32px">📊</p>';
            echo '<p>Publica el evento para ver<br>estadísticas en tiempo real.</p>';
            echo '</div>';
            return;
        }

        $max     = (int) get_post_meta( $post->ID, '_dcevents_max_capacity', true );
        $count   = (int) get_post_meta( $post->ID, '_dcevents_registered_count', true );
        $percent = $max > 0 ? round( ($count / $max) * 100 ) : 0;

        $statuses = ['confirmed','pending','paid','attended','cancelled'];
        $counts   = [];
        foreach ($statuses as $s) {
            $counts[$s] = count(get_posts([
                'post_type' => 'dc_registration', 'post_status' => 'publish',
                'posts_per_page' => -1, 'fields' => 'ids',
                'meta_query' => [
                    ['key' => '_dcevents_event_id',   'value' => $post->ID],
                    ['key' => '_dcevents_reg_status', 'value' => $s],
                ],
            ]));
        }
        ?>
        <div style="font-family:-apple-system,sans-serif;padding:4px 0">
            <div style="text-align:center;margin-bottom:14px">
                <div style="font-size:46px;font-weight:900;line-height:1;color:#0073aa"><?php echo $count; ?></div>
                <div style="font-size:12px;color:#888;margin-top:2px">inscritos totales</div>
            </div>

            <?php if ($max > 0) : ?>
            <div style="background:#f0f0f0;border-radius:6px;height:8px;overflow:hidden;margin-bottom:6px">
                <div style="background:<?php echo $percent >= 90 ? '#dc3232' : '#46b450'; ?>;width:<?php echo $percent; ?>%;height:100%;transition:width .5s"></div>
            </div>
            <div style="text-align:center;font-size:12px;color:#666;margin-bottom:14px">
                <?php echo $count . ' / ' . $max . ' (' . $percent . '%)'; ?>
            </div>
            <?php endif; ?>

            <table style="width:100%;font-size:12px;border-collapse:collapse">
                <?php
                $stat_map = [
                    'confirmed' => ['✅ Confirmados',  '#46b450'],
                    'pending'   => ['⏳ Pendientes',   '#f0b849'],
                    'paid'      => ['💳 Pagados',      '#0073aa'],
                    'attended'  => ['🎫 Asistieron',   '#666'],
                    'cancelled' => ['❌ Cancelados',   '#dc3232'],
                ];
                foreach ($stat_map as $key => [$label, $color]) : ?>
                <tr>
                    <td style="padding:5px 0;color:<?php echo $color; ?>;font-weight:600"><?php echo $label; ?></td>
                    <td style="text-align:right;font-weight:800"><?php echo $counts[$key]; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>

            <a href="<?php echo admin_url('admin.php?page=dc-events-registrations&event_id='.$post->ID); ?>"
               style="display:block;margin-top:14px;background:#0073aa;color:#fff;padding:9px;border-radius:6px;text-align:center;text-decoration:none;font-size:13px;font-weight:700">
                Ver inscritos →
            </a>
        </div>
        <?php
    }

    // ─── Guardar todos los campos ─────────────────────────────────────────────
    public function save_meta( $post_id, $post ) {
        if ( ! isset( $_POST['dcevents_meta_nonce'] )
            || ! wp_verify_nonce( $_POST['dcevents_meta_nonce'], 'dcevents_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Campos de texto / fecha / URL
        $text_fields = [
            'dcevents_start_date'            => '_dcevents_start_date',
            'dcevents_end_date'              => '_dcevents_end_date',
            'dcevents_start_time'            => '_dcevents_start_time',
            'dcevents_end_time'              => '_dcevents_end_time',
            'dcevents_status'                => '_dcevents_status',
            'dcevents_currency'              => '_dcevents_currency',
            'dcevents_venue'                 => '_dcevents_venue',
            'dcevents_address'               => '_dcevents_address',
            'dcevents_city'                  => '_dcevents_city',
            'dcevents_country'               => '_dcevents_country',
            'dcevents_maps_url'              => '_dcevents_maps_url',
            'dcevents_virtual_url'           => '_dcevents_virtual_url',
            'dcevents_registration_deadline' => '_dcevents_registration_deadline',
            'dcevents_price_type'            => '_dcevents_price_type',
        ];
        foreach ( $text_fields as $k => $m ) {
            if ( isset( $_POST[$k] ) ) {
                update_post_meta( $post_id, $m, sanitize_text_field( $_POST[$k] ) );
            }
        }

        // Numéricos
        if ( isset( $_POST['dcevents_price'] ) ) {
            update_post_meta( $post_id, '_dcevents_price', floatval( $_POST['dcevents_price'] ) );
        }
        if ( isset( $_POST['dcevents_max_capacity'] ) ) {
            update_post_meta( $post_id, '_dcevents_max_capacity', absint( $_POST['dcevents_max_capacity'] ) );
        }

        // Logo
        $logo_id = absint( $_POST['dcevents_logo_id'] ?? 0 );
        update_post_meta( $post_id, '_dcevents_logo_id', $logo_id );

        // Checkboxes
        foreach ([
            'dcevents_featured'          => '_dcevents_featured',
            'dcevents_is_virtual'        => '_dcevents_is_virtual',
            'dcevents_registration_open' => '_dcevents_registration_open',
            'dcevents_require_approval'  => '_dcevents_require_approval',
        ] as $k => $m ) {
            update_post_meta( $post_id, $m, isset( $_POST[$k] ) ? '1' : '0' );
        }

        // Tramos de precio
        $tiers = [];
        if ( isset( $_POST['dcevents_price_tiers'] ) && is_array( $_POST['dcevents_price_tiers'] ) ) {
            foreach ( $_POST['dcevents_price_tiers'] as $t ) {
                if ( ! empty($t['name']) || floatval($t['price']) > 0 ) {
                    $tiers[] = [
                        'name'       => sanitize_text_field( $t['name']       ?? '' ),
                        'price'      => floatval( $t['price']                 ?? 0  ),
                        'start_date' => sanitize_text_field( $t['start_date'] ?? '' ),
                        'end_date'   => sanitize_text_field( $t['end_date']   ?? '' ),
                    ];
                }
            }
        }
        update_post_meta( $post_id, '_dcevents_price_tiers', $tiers );

        // Calcular precio activo si es por tramos
        $price_type = sanitize_text_field( $_POST['dcevents_price_type'] ?? 'single' );
        if ( $price_type === 'tiered' && ! empty($tiers) ) {
            $today = date('Y-m-d');
            $found = null;
            foreach ($tiers as $t) {
                $ok = true;
                if ( ! empty($t['start_date']) && $today < $t['start_date'] ) $ok = false;
                if ( ! empty($t['end_date'])   && $today > $t['end_date'] )   $ok = false;
                if ($ok) { $found = $t['price']; break; }
            }
            if ($found !== null) {
                update_post_meta( $post_id, '_dcevents_price', $found );
            }
        } elseif ( $price_type === 'free' ) {
            update_post_meta( $post_id, '_dcevents_price', 0 );
        }

        // Campos personalizados
        $custom = [];
        if ( isset( $_POST['dcevents_custom_fields'] ) && is_array( $_POST['dcevents_custom_fields'] ) ) {
            foreach ( $_POST['dcevents_custom_fields'] as $f ) {
                if ( ! empty($f['label']) ) {
                    $custom[] = [
                        'label'    => sanitize_text_field( $f['label'] ),
                        'type'     => sanitize_key( $f['type'] ?? 'text' ),
                        'required' => isset($f['required']) ? '1' : '0',
                    ];
                }
            }
        }
        update_post_meta( $post_id, '_dcevents_custom_fields', $custom );
    }
}

// Inicializar
if ( is_admin() ) {
    add_action( 'init', static function() {
        DCEvents_Meta_Boxes::instance();
    }, 5 );
}
