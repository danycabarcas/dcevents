<?php
/**
 * Vista: Ajustes Visuales
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'dcevents_manage_settings' ) ) wp_die( __( 'Sin acceso', 'dc-events' ) );

$settings = array_merge( DCEvents_Settings::defaults(), get_option( 'dcevents_settings', [] ) );
?>
<div class="wrap dce-admin-wrap">
    <div class="dce-admin-header">
        <div class="dce-admin-header-left">
            <h1 class="dce-admin-title">🎨 <?php _e('Ajustes Visuales', 'dc-events'); ?></h1>
            <p class="dce-admin-subtitle"><?php _e('Personaliza la apariencia de los eventos en tu sitio.', 'dc-events'); ?></p>
        </div>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('dcevents_settings_group'); ?>

        <div class="dce-settings-layout">
            <!-- Panel izquierdo: ajustes -->
            <div class="dce-settings-main">

                <!-- Colores -->
                <div class="dce-card">
                    <div class="dce-card-header"><h2>🎨 <?php _e('Colores', 'dc-events'); ?></h2></div>
                    <div class="dce-settings-grid">
                        <?php
                        $color_fields = [
                            'primary_color'     => __('Color Principal (botones, fechas)', 'dc-events'),
                            'secondary_color'   => __('Color Secundario', 'dc-events'),
                            'text_color'        => __('Color de Texto', 'dc-events'),
                            'bg_color'          => __('Color de Fondo', 'dc-events'),
                            'card_bg'           => __('Fondo de Tarjeta/Fila', 'dc-events'),
                            'button_text_color' => __('Texto de Botones', 'dc-events'),
                            'accent_color'      => __('Color Acento (links)', 'dc-events'),
                        ];
                        foreach ( $color_fields as $key => $label ) : ?>
                        <div class="dce-setting-row">
                            <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                            <div class="dce-color-wrapper">
                                <input type="text" id="<?php echo esc_attr($key); ?>"
                                       name="dcevents_settings[<?php echo esc_attr($key); ?>]"
                                       value="<?php echo esc_attr($settings[$key]); ?>"
                                       class="dce-color-picker"
                                       data-default-color="<?php echo esc_attr(DCEvents_Settings::defaults()[$key]); ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Layout -->
                <div class="dce-card">
                    <div class="dce-card-header"><h2>🖼️ <?php _e('Layout y Display', 'dc-events'); ?></h2></div>
                    <div class="dce-settings-grid">
                        <div class="dce-setting-row">
                            <label><?php _e('Layout de lista', 'dc-events'); ?></label>
                            <div class="dce-layout-selector">
                                <?php foreach (['list' => ['🗒️', 'Lista'], 'cards' => ['🃏', 'Tarjetas'], 'grid' => ['⊞', 'Cuadrícula']] as $val => $lbl) : ?>
                                <label class="dce-layout-option <?php echo $settings['event_layout'] === $val ? 'active' : ''; ?>">
                                    <input type="radio" name="dcevents_settings[event_layout]" value="<?php echo $val; ?>"
                                           <?php checked($settings['event_layout'], $val); ?>>
                                    <span class="dce-layout-icon"><?php echo $lbl[0]; ?></span>
                                    <span><?php echo $lbl[1]; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="dce-setting-row">
                            <label for="events_per_page"><?php _e('Eventos por página', 'dc-events'); ?></label>
                            <input type="number" id="events_per_page" name="dcevents_settings[events_per_page]"
                                   value="<?php echo esc_attr($settings['events_per_page']); ?>" min="1" max="50" style="width:80px">
                        </div>

                        <div class="dce-setting-row">
                            <label for="border_radius"><?php _e('Radio de bordes (px)', 'dc-events'); ?></label>
                            <input type="range" id="border_radius" name="dcevents_settings[border_radius]"
                                   value="<?php echo esc_attr($settings['border_radius']); ?>"
                                   min="0" max="30" step="2"
                                   oninput="document.getElementById('radius_val').textContent=this.value+'px'">
                            <span id="radius_val"><?php echo $settings['border_radius']; ?>px</span>
                        </div>
                    </div>
                </div>

                <!-- Tipografía -->
                <div class="dce-card">
                    <div class="dce-card-header"><h2>🔤 <?php _e('Tipografía', 'dc-events'); ?></h2></div>
                    <div class="dce-settings-grid">
                        <div class="dce-setting-row">
                            <label for="font_family"><?php _e('Fuente del cuerpo', 'dc-events'); ?></label>
                            <select id="font_family" name="dcevents_settings[font_family]">
                                <?php foreach (['inherit' => 'Heredar del tema', 'Barlow' => 'Barlow', 'Inter' => 'Inter', 'Roboto' => 'Roboto', 'Outfit' => 'Outfit', 'Poppins' => 'Poppins', 'Open Sans' => 'Open Sans'] as $val => $lbl) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($settings['font_family'], $val); ?>><?php echo esc_html($lbl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="dce-setting-row">
                            <label for="heading_font"><?php _e('Fuente de títulos', 'dc-events'); ?></label>
                            <select id="heading_font" name="dcevents_settings[heading_font]">
                                <?php foreach (['inherit' => 'Heredar del tema', 'Krona One' => 'Krona One', 'Barlow' => 'Barlow', 'Inter' => 'Inter', 'Outfit' => 'Outfit', 'Playfair Display' => 'Playfair Display'] as $val => $lbl) : ?>
                                <option value="<?php echo esc_attr($val); ?>" <?php selected($settings['heading_font'], $val); ?>><?php echo esc_html($lbl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Textos -->
                <div class="dce-card">
                    <div class="dce-card-header"><h2>✏️ <?php _e('Textos Personalizables', 'dc-events'); ?></h2></div>
                    <div class="dce-settings-grid">
                        <div class="dce-setting-row">
                            <label for="button_text"><?php _e('Texto del botón de inscripción', 'dc-events'); ?></label>
                            <input type="text" id="button_text" name="dcevents_settings[button_text]"
                                   value="<?php echo esc_attr($settings['button_text']); ?>">
                        </div>
                        <div class="dce-setting-row">
                            <label for="register_title"><?php _e('Título del formulario de inscripción', 'dc-events'); ?></label>
                            <input type="text" id="register_title" name="dcevents_settings[register_title]"
                                   value="<?php echo esc_attr($settings['register_title']); ?>">
                        </div>
                        <div class="dce-setting-row">
                            <label for="no_events_text"><?php _e('Texto cuando no hay eventos', 'dc-events'); ?></label>
                            <input type="text" id="no_events_text" name="dcevents_settings[no_events_text]"
                                   value="<?php echo esc_attr($settings['no_events_text']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Opciones de visualización -->
                <div class="dce-card">
                    <div class="dce-card-header"><h2>⚙️ <?php _e('Opciones de Visualización', 'dc-events'); ?></h2></div>
                    <div class="dce-settings-grid">
                        <?php
                        $toggles = [
                            'show_past_events'     => __('Mostrar eventos pasados', 'dc-events'),
                            'show_category_filter' => __('Mostrar filtro de categorías', 'dc-events'),
                            'show_search'          => __('Mostrar buscador de eventos', 'dc-events'),
                            'show_countdown'       => __('Mostrar cuenta regresiva', 'dc-events'),
                        ];
                        foreach ($toggles as $key => $label) : ?>
                        <div class="dce-setting-row dce-toggle-row">
                            <label><?php echo esc_html($label); ?></label>
                            <label class="dce-toggle">
                                <input type="checkbox" name="dcevents_settings[<?php echo esc_attr($key); ?>]" value="1"
                                       <?php checked($settings[$key], '1'); ?>>
                                <span class="dce-toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php submit_button(__('💾 Guardar Ajustes', 'dc-events'), 'primary large', 'submit', true, ['id' => 'dce-save-settings']); ?>
            </div>

            <!-- Panel derecho: preview -->
            <div class="dce-settings-preview">
                <div class="dce-card dce-preview-card">
                    <div class="dce-card-header"><h2>👁️ <?php _e('Vista Previa', 'dc-events'); ?></h2></div>
                    <div class="dce-preview-box" id="dce-preview">
                        <div class="dce-preview-event" id="prev-event">
                            <div class="dce-preview-date">
                                <span class="dce-preview-day">26</span>
                                <span class="dce-preview-month">Jul</span>
                                <span class="dce-preview-year">2025</span>
                            </div>
                            <div class="dce-preview-info">
                                <div class="dce-preview-title">Tour Nazaret 2025</div>
                                <div class="dce-preview-location">🏛️ Santa Marta, Colombia</div>
                            </div>
                            <div class="dce-preview-action">
                                <span class="dce-preview-btn" id="prev-btn">Inscribirse →</span>
                            </div>
                        </div>
                        <div class="dce-preview-event" id="prev-event-2">
                            <div class="dce-preview-date">
                                <span class="dce-preview-day">14</span>
                                <span class="dce-preview-month">Ago</span>
                                <span class="dce-preview-year">2025</span>
                            </div>
                            <div class="dce-preview-info">
                                <div class="dce-preview-title">Conferencia Anual</div>
                                <div class="dce-preview-location">🌐 Online</div>
                            </div>
                            <div class="dce-preview-action">
                                <span class="dce-preview-btn" id="prev-btn-2">Inscribirse →</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shortcodes rápidos -->
                <div class="dce-card" style="margin-top:16px">
                    <div class="dce-card-header"><h2><?php _e('Copia y pega', 'dc-events'); ?></h2></div>
                    <div style="padding:16px">
                        <p style="color:#666;font-size:13px"><?php _e('Añade estos shortcodes en cualquier página o entrada de WordPress:', 'dc-events'); ?></p>
                        <?php foreach ([
                            '[dc_events]'                         => __('Lista de eventos', 'dc-events'),
                            '[dc_events layout="cards"]'          => __('Vista en tarjetas', 'dc-events'),
                            '[dc_events featured_only="1"]'       => __('Solo destacados', 'dc-events'),
                            '[dc_registration_form event_id="X"]' => __('Formulario de inscripción', 'dc-events'),
                            '[dc_my_registrations]'               => __('Panel del asistente', 'dc-events'),
                        ] as $sc => $desc) : ?>
                        <div style="margin-bottom:8px">
                            <div style="font-size:11px;color:#666;margin-bottom:2px"><?php echo esc_html($desc); ?></div>
                            <code style="display:flex;justify-content:space-between;align-items:center;background:#f0f0f0;padding:6px 10px;border-radius:4px;cursor:pointer"
                                  onclick="navigator.clipboard.writeText('<?php echo esc_js($sc); ?>');this.style.background='#d4edda'"
                                  title="<?php _e('Click para copiar', 'dc-events'); ?>">
                                <?php echo esc_html($sc); ?> <span style="font-size:11px;color:#0073aa">📋</span>
                            </code>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function($) {
    // Actualizar preview en tiempo real
    function updatePreview() {
        var primary  = $('#primary_color').val() || '#fae100';
        var textCol  = $('#text_color').val() || '#121212';
        var bg       = $('#bg_color').val() || '#ffffff';
        var cardBg   = $('#card_bg').val() || '#ffffff';
        var btnText  = $('#button_text_color').val() || '#121212';
        var btnLabel = $('[name="dcevents_settings[button_text]"]').val() || 'Inscribirse →';

        $('#prev-event, #prev-event-2').css('background', cardBg);
        $('#prev-event .dce-preview-date, #prev-event-2 .dce-preview-date').css('background', primary);
        $('#prev-event .dce-preview-date, #prev-event-2 .dce-preview-date').css('color', textCol);
        $('#prev-btn, #prev-btn-2').css({ 'background': primary, 'color': btnText });
        $('#prev-btn, #prev-btn-2').text(btnLabel + ' →');
        $('#dce-preview').css('background', bg);
    }

    // Color picker
    $('.dce-color-picker').wpColorPicker({ change: function(e, ui) { setTimeout(updatePreview, 50); } });
    $('[name*="button_text"]').on('input', updatePreview);

    // Layout selector
    $('.dce-layout-option').on('click', function() {
        $('.dce-layout-option').removeClass('active');
        $(this).addClass('active');
    });

    updatePreview();
})(jQuery);
</script>
