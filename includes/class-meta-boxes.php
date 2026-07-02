<?php
/**
 * Meta Boxes para el CPT dc_event
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
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_dc_event', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    public function enqueue_scripts( $hook ) {
        global $post;
        if ( ( 'post.php' === $hook || 'post-new.php' === $hook )
            && isset( $post->post_type )
            && 'dc_event' === $post->post_type ) {
            wp_enqueue_script( 'jquery-ui-datepicker' );
            wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css' );
        }
    }

    public function add_meta_boxes() {
        add_meta_box(
            'dcevents_event_details',
            __( '📅 Detalles del Evento', 'dc-events' ),
            [ $this, 'render_event_details_box' ],
            'dc_event',
            'normal',
            'high'
        );

        add_meta_box(
            'dcevents_event_location',
            __( '📍 Lugar del Evento', 'dc-events' ),
            [ $this, 'render_location_box' ],
            'dc_event',
            'normal',
            'default'
        );

        add_meta_box(
            'dcevents_event_registration',
            __( '📋 Configuración de Inscripciones', 'dc-events' ),
            [ $this, 'render_registration_box' ],
            'dc_event',
            'normal',
            'default'
        );

        add_meta_box(
            'dcevents_event_stats',
            __( '📊 Estadísticas', 'dc-events' ),
            [ $this, 'render_stats_box' ],
            'dc_event',
            'side',
            'default'
        );
    }

    // ─── Box: Detalles ────────────────────────────────────────────────────────
    public function render_event_details_box( $post ) {
        wp_nonce_field( 'dcevents_save_meta', 'dcevents_meta_nonce' );

        $start_date  = get_post_meta( $post->ID, '_dcevents_start_date', true );
        $end_date    = get_post_meta( $post->ID, '_dcevents_end_date', true );
        $start_time  = get_post_meta( $post->ID, '_dcevents_start_time', true );
        $end_time    = get_post_meta( $post->ID, '_dcevents_end_time', true );
        $price       = get_post_meta( $post->ID, '_dcevents_price', true );
        $currency    = get_post_meta( $post->ID, '_dcevents_currency', true ) ?: 'COP';
        $status      = get_post_meta( $post->ID, '_dcevents_status', true ) ?: 'active';
        $featured    = get_post_meta( $post->ID, '_dcevents_featured', true );
        ?>
        <div class="dcevents-meta-grid">
            <div class="dcevents-meta-row">
                <div class="dcevents-meta-col">
                    <label for="dcevents_start_date"><strong><?php _e( 'Fecha de inicio', 'dc-events' ); ?> *</strong></label>
                    <input type="date" id="dcevents_start_date" name="dcevents_start_date"
                           value="<?php echo esc_attr( $start_date ); ?>" required style="width:100%">
                </div>
                <div class="dcevents-meta-col">
                    <label for="dcevents_start_time"><strong><?php _e( 'Hora de inicio', 'dc-events' ); ?></strong></label>
                    <input type="time" id="dcevents_start_time" name="dcevents_start_time"
                           value="<?php echo esc_attr( $start_time ); ?>" style="width:100%">
                </div>
                <div class="dcevents-meta-col">
                    <label for="dcevents_end_date"><strong><?php _e( 'Fecha de fin', 'dc-events' ); ?></strong></label>
                    <input type="date" id="dcevents_end_date" name="dcevents_end_date"
                           value="<?php echo esc_attr( $end_date ); ?>" style="width:100%">
                </div>
                <div class="dcevents-meta-col">
                    <label for="dcevents_end_time"><strong><?php _e( 'Hora de fin', 'dc-events' ); ?></strong></label>
                    <input type="time" id="dcevents_end_time" name="dcevents_end_time"
                           value="<?php echo esc_attr( $end_time ); ?>" style="width:100%">
                </div>
            </div>

            <div class="dcevents-meta-row">
                <div class="dcevents-meta-col">
                    <label for="dcevents_price"><strong><?php _e( 'Precio', 'dc-events' ); ?></strong></label>
                    <input type="number" id="dcevents_price" name="dcevents_price"
                           value="<?php echo esc_attr( $price ); ?>"
                           min="0" step="100" placeholder="0 = gratuito" style="width:100%">
                    <p class="description"><?php _e( '0 para evento gratuito', 'dc-events' ); ?></p>
                </div>
                <div class="dcevents-meta-col">
                    <label for="dcevents_currency"><strong><?php _e( 'Moneda', 'dc-events' ); ?></strong></label>
                    <select id="dcevents_currency" name="dcevents_currency" style="width:100%">
                        <option value="COP" <?php selected( $currency, 'COP' ); ?>>COP - Peso Colombiano</option>
                        <option value="USD" <?php selected( $currency, 'USD' ); ?>>USD - Dólar</option>
                        <option value="EUR" <?php selected( $currency, 'EUR' ); ?>>EUR - Euro</option>
                    </select>
                </div>
                <div class="dcevents-meta-col">
                    <label for="dcevents_status"><strong><?php _e( 'Estado del Evento', 'dc-events' ); ?></strong></label>
                    <select id="dcevents_status" name="dcevents_status" style="width:100%">
                        <option value="active"    <?php selected( $status, 'active' ); ?>><?php _e( '✅ Activo', 'dc-events' ); ?></option>
                        <option value="full"      <?php selected( $status, 'full' ); ?>><?php _e( '🔴 Agotado', 'dc-events' ); ?></option>
                        <option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php _e( '❌ Cancelado', 'dc-events' ); ?></option>
                        <option value="draft"     <?php selected( $status, 'draft' ); ?>><?php _e( '📝 Borrador', 'dc-events' ); ?></option>
                    </select>
                </div>
                <div class="dcevents-meta-col">
                    <label><strong><?php _e( 'Opciones', 'dc-events' ); ?></strong></label>
                    <label style="display:flex;align-items:center;gap:6px;margin-top:8px">
                        <input type="checkbox" name="dcevents_featured" value="1" <?php checked( $featured, '1' ); ?>>
                        <?php _e( '⭐ Evento Destacado', 'dc-events' ); ?>
                    </label>
                </div>
            </div>
        </div>

        <style>
        .dcevents-meta-grid { display:flex; flex-direction:column; gap:16px; }
        .dcevents-meta-row  { display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:12px; }
        .dcevents-meta-col  { display:flex; flex-direction:column; gap:4px; }
        .dcevents-meta-col input, .dcevents-meta-col select { padding:6px 8px; border:1px solid #ddd; border-radius:4px; }
        </style>
        <?php
    }

    // ─── Box: Lugar ───────────────────────────────────────────────────────────
    public function render_location_box( $post ) {
        $venue       = get_post_meta( $post->ID, '_dcevents_venue', true );
        $address     = get_post_meta( $post->ID, '_dcevents_address', true );
        $city        = get_post_meta( $post->ID, '_dcevents_city', true ) ?: 'Santa Marta';
        $country     = get_post_meta( $post->ID, '_dcevents_country', true ) ?: 'Colombia';
        $maps_url    = get_post_meta( $post->ID, '_dcevents_maps_url', true );
        $is_virtual  = get_post_meta( $post->ID, '_dcevents_is_virtual', true );
        $virtual_url = get_post_meta( $post->ID, '_dcevents_virtual_url', true );
        ?>
        <div class="dcevents-meta-grid">
            <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                <input type="checkbox" name="dcevents_is_virtual" value="1" id="dcevents_is_virtual"
                       <?php checked( $is_virtual, '1' ); ?> onchange="toggleVirtualLocation(this)">
                <strong><?php _e( '🌐 Evento Virtual / Online', 'dc-events' ); ?></strong>
            </label>

            <div id="dcevents_physical_location" <?php echo $is_virtual ? 'style="display:none"' : ''; ?>>
                <div class="dcevents-meta-row">
                    <div class="dcevents-meta-col">
                        <label for="dcevents_venue"><strong><?php _e( 'Nombre del lugar', 'dc-events' ); ?></strong></label>
                        <input type="text" id="dcevents_venue" name="dcevents_venue"
                               value="<?php echo esc_attr( $venue ); ?>"
                               placeholder="Ej: Centro de Convenciones Tayrona" style="width:100%">
                    </div>
                    <div class="dcevents-meta-col">
                        <label for="dcevents_city"><strong><?php _e( 'Ciudad', 'dc-events' ); ?></strong></label>
                        <input type="text" id="dcevents_city" name="dcevents_city"
                               value="<?php echo esc_attr( $city ); ?>" style="width:100%">
                    </div>
                </div>
                <div class="dcevents-meta-row" style="margin-top:8px">
                    <div class="dcevents-meta-col">
                        <label for="dcevents_address"><strong><?php _e( 'Dirección', 'dc-events' ); ?></strong></label>
                        <input type="text" id="dcevents_address" name="dcevents_address"
                               value="<?php echo esc_attr( $address ); ?>"
                               placeholder="Calle, Carrera, No." style="width:100%">
                    </div>
                    <div class="dcevents-meta-col">
                        <label for="dcevents_maps_url"><strong><?php _e( 'URL de Google Maps', 'dc-events' ); ?></strong></label>
                        <input type="url" id="dcevents_maps_url" name="dcevents_maps_url"
                               value="<?php echo esc_url( $maps_url ); ?>"
                               placeholder="https://maps.google.com/..." style="width:100%">
                    </div>
                </div>
            </div>

            <div id="dcevents_virtual_location" <?php echo ! $is_virtual ? 'style="display:none"' : ''; ?>>
                <div class="dcevents-meta-col">
                    <label for="dcevents_virtual_url"><strong><?php _e( 'Link del evento virtual', 'dc-events' ); ?></strong></label>
                    <input type="url" id="dcevents_virtual_url" name="dcevents_virtual_url"
                           value="<?php echo esc_url( $virtual_url ); ?>"
                           placeholder="https://zoom.us/... o YouTube Live" style="width:100%">
                    <p class="description"><?php _e( 'Se revelará a los inscritos tras confirmar.', 'dc-events' ); ?></p>
                </div>
            </div>
        </div>
        <script>
        function toggleVirtualLocation(el) {
            document.getElementById('dcevents_physical_location').style.display = el.checked ? 'none' : '';
            document.getElementById('dcevents_virtual_location').style.display  = el.checked ? '' : 'none';
        }
        </script>
        <?php
    }

    // ─── Box: Inscripciones ───────────────────────────────────────────────────
    public function render_registration_box( $post ) {
        $max_capacity      = get_post_meta( $post->ID, '_dcevents_max_capacity', true );
        $registration_open = get_post_meta( $post->ID, '_dcevents_registration_open', true );
        $reg_deadline      = get_post_meta( $post->ID, '_dcevents_registration_deadline', true );
        $require_approval  = get_post_meta( $post->ID, '_dcevents_require_approval', true );
        $custom_fields     = get_post_meta( $post->ID, '_dcevents_custom_fields', true ) ?: [];
        ?>
        <div class="dcevents-meta-grid">
            <div class="dcevents-meta-row">
                <div class="dcevents-meta-col">
                    <label for="dcevents_max_capacity"><strong><?php _e( 'Capacidad máxima', 'dc-events' ); ?></strong></label>
                    <input type="number" id="dcevents_max_capacity" name="dcevents_max_capacity"
                           value="<?php echo esc_attr( $max_capacity ); ?>"
                           min="0" placeholder="0 = sin límite" style="width:100%">
                </div>
                <div class="dcevents-meta-col">
                    <label for="dcevents_registration_deadline"><strong><?php _e( 'Fecha límite inscripción', 'dc-events' ); ?></strong></label>
                    <input type="date" id="dcevents_registration_deadline" name="dcevents_registration_deadline"
                           value="<?php echo esc_attr( $reg_deadline ); ?>" style="width:100%">
                </div>
                <div class="dcevents-meta-col">
                    <label><strong><?php _e( 'Opciones', 'dc-events' ); ?></strong></label>
                    <label style="display:flex;align-items:center;gap:6px;margin-top:8px">
                        <input type="checkbox" name="dcevents_registration_open" value="1"
                               <?php checked( $registration_open !== '0', true ); ?>>
                        <?php _e( 'Inscripciones abiertas', 'dc-events' ); ?>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;margin-top:6px">
                        <input type="checkbox" name="dcevents_require_approval" value="1"
                               <?php checked( $require_approval, '1' ); ?>>
                        <?php _e( 'Requiere aprobación manual', 'dc-events' ); ?>
                    </label>
                </div>
            </div>

            <hr>
            <h4 style="margin:8px 0"><?php _e( '📝 Campos adicionales del formulario', 'dc-events' ); ?></h4>
            <p class="description"><?php _e( 'Campos extras además de nombre, email y teléfono (que siempre se incluyen).', 'dc-events' ); ?></p>

            <div id="dcevents_custom_fields_container">
                <?php if ( ! empty( $custom_fields ) ) : ?>
                    <?php foreach ( $custom_fields as $i => $field ) : ?>
                        <div class="dcevents-custom-field" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center">
                            <input type="text" name="dcevents_custom_fields[<?php echo $i; ?>][label]"
                                   value="<?php echo esc_attr( $field['label'] ); ?>"
                                   placeholder="<?php _e( 'Nombre del campo', 'dc-events' ); ?>">
                            <select name="dcevents_custom_fields[<?php echo $i; ?>][type]">
                                <option value="text"     <?php selected( $field['type'], 'text' ); ?>><?php _e( 'Texto', 'dc-events' ); ?></option>
                                <option value="email"    <?php selected( $field['type'], 'email' ); ?>><?php _e( 'Email', 'dc-events' ); ?></option>
                                <option value="number"   <?php selected( $field['type'], 'number' ); ?>><?php _e( 'Número', 'dc-events' ); ?></option>
                                <option value="select"   <?php selected( $field['type'], 'select' ); ?>><?php _e( 'Opciones', 'dc-events' ); ?></option>
                                <option value="textarea" <?php selected( $field['type'], 'textarea' ); ?>><?php _e( 'Texto largo', 'dc-events' ); ?></option>
                            </select>
                            <label style="display:flex;align-items:center;gap:4px">
                                <input type="checkbox" name="dcevents_custom_fields[<?php echo $i; ?>][required]" value="1"
                                       <?php checked( $field['required'], '1' ); ?>>
                                <?php _e( 'Requerido', 'dc-events' ); ?>
                            </label>
                            <button type="button" onclick="this.closest('.dcevents-custom-field').remove()"
                                    style="background:#dc3232;color:#fff;border:none;border-radius:4px;padding:4px 8px;cursor:pointer">✕</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <button type="button" id="dcevents_add_field"
                    style="background:#0073aa;color:#fff;border:none;border-radius:4px;padding:8px 12px;cursor:pointer;margin-top:8px">
                + <?php _e( 'Agregar campo', 'dc-events' ); ?>
            </button>
        </div>

        <script>
        let fieldIndex = <?php echo count( $custom_fields ); ?>;
        document.getElementById('dcevents_add_field').addEventListener('click', function() {
            const container = document.getElementById('dcevents_custom_fields_container');
            const div = document.createElement('div');
            div.className = 'dcevents-custom-field';
            div.style.cssText = 'display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center';
            div.innerHTML = `
                <input type="text" name="dcevents_custom_fields[${fieldIndex}][label]" placeholder="<?php _e( 'Nombre del campo', 'dc-events' ); ?>">
                <select name="dcevents_custom_fields[${fieldIndex}][type]">
                    <option value="text"><?php _e( 'Texto', 'dc-events' ); ?></option>
                    <option value="email"><?php _e( 'Email', 'dc-events' ); ?></option>
                    <option value="number"><?php _e( 'Número', 'dc-events' ); ?></option>
                    <option value="select"><?php _e( 'Opciones', 'dc-events' ); ?></option>
                    <option value="textarea"><?php _e( 'Texto largo', 'dc-events' ); ?></option>
                </select>
                <label style="display:flex;align-items:center;gap:4px">
                    <input type="checkbox" name="dcevents_custom_fields[${fieldIndex}][required]" value="1">
                    <?php _e( 'Requerido', 'dc-events' ); ?>
                </label>
                <button type="button" onclick="this.closest('.dcevents-custom-field').remove()"
                        style="background:#dc3232;color:#fff;border:none;border-radius:4px;padding:4px 8px;cursor:pointer">✕</button>
            `;
            container.appendChild(div);
            fieldIndex++;
        });
        </script>
        <?php
    }

    // ─── Box: Estadísticas ────────────────────────────────────────────────────
    public function render_stats_box( $post ) {
        if ( 'publish' !== $post->post_status ) {
            echo '<p>' . __( 'Publica el evento para ver estadísticas.', 'dc-events' ) . '</p>';
            return;
        }

        $max      = (int) get_post_meta( $post->ID, '_dcevents_max_capacity', true );
        $count    = (int) get_post_meta( $post->ID, '_dcevents_registered_count', true );
        $percent  = $max > 0 ? round( ( $count / $max ) * 100 ) : 0;

        $confirmed = count( get_posts( [
            'post_type'      => 'dc_registration',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [ 'key' => '_dcevents_event_id', 'value' => $post->ID ],
                [ 'key' => '_dcevents_reg_status', 'value' => 'confirmed' ],
            ],
            'fields' => 'ids',
        ] ) );

        $pending = count( get_posts( [
            'post_type'      => 'dc_registration',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [ 'key' => '_dcevents_event_id', 'value' => $post->ID ],
                [ 'key' => '_dcevents_reg_status', 'value' => 'pending' ],
            ],
            'fields' => 'ids',
        ] ) );
        ?>
        <div style="font-family:sans-serif">
            <div style="text-align:center;font-size:36px;font-weight:700;color:#0073aa"><?php echo $count; ?></div>
            <div style="text-align:center;color:#666;margin-bottom:12px"><?php _e( 'Inscritos totales', 'dc-events' ); ?></div>

            <?php if ( $max > 0 ) : ?>
            <div style="background:#f0f0f0;border-radius:10px;height:10px;margin-bottom:8px">
                <div style="background:<?php echo $percent >= 90 ? '#dc3232' : '#46b450'; ?>;width:<?php echo $percent; ?>%;height:100%;border-radius:10px;transition:width 0.3s"></div>
            </div>
            <div style="text-align:center;font-size:12px;color:#666"><?php echo $count; ?> / <?php echo $max; ?> (<?php echo $percent; ?>%)</div>
            <?php endif; ?>

            <hr>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:4px 0;color:#46b450">✅ <?php _e( 'Confirmados', 'dc-events' ); ?></td>
                    <td style="text-align:right;font-weight:700"><?php echo $confirmed; ?></td>
                </tr>
                <tr>
                    <td style="padding:4px 0;color:#f0b849">⏳ <?php _e( 'Pendientes', 'dc-events' ); ?></td>
                    <td style="text-align:right;font-weight:700"><?php echo $pending; ?></td>
                </tr>
            </table>

            <div style="margin-top:12px">
                <a href="<?php echo admin_url( 'admin.php?page=dc-events-registrations&event_id=' . $post->ID ); ?>"
                   style="display:block;text-align:center;background:#0073aa;color:#fff;padding:8px;border-radius:4px;text-decoration:none">
                    <?php _e( 'Ver inscritos →', 'dc-events' ); ?>
                </a>
            </div>
        </div>
        <?php
    }

    // ─── Guardar datos ────────────────────────────────────────────────────────
    public function save_meta( $post_id, $post ) {
        // Verificar nonce
        if ( ! isset( $_POST['dcevents_meta_nonce'] )
            || ! wp_verify_nonce( $_POST['dcevents_meta_nonce'], 'dcevents_save_meta' ) ) {
            return;
        }

        // No guardar en autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // Verificar capacidades
        if ( ! current_user_can( 'dcevents_edit_event', $post_id ) ) return;

        // Campos simples
        $fields = [
            'dcevents_start_date'              => '_dcevents_start_date',
            'dcevents_end_date'                => '_dcevents_end_date',
            'dcevents_start_time'              => '_dcevents_start_time',
            'dcevents_end_time'                => '_dcevents_end_time',
            'dcevents_venue'                   => '_dcevents_venue',
            'dcevents_address'                 => '_dcevents_address',
            'dcevents_city'                    => '_dcevents_city',
            'dcevents_country'                 => '_dcevents_country',
            'dcevents_maps_url'                => '_dcevents_maps_url',
            'dcevents_virtual_url'             => '_dcevents_virtual_url',
            'dcevents_max_capacity'            => '_dcevents_max_capacity',
            'dcevents_registration_deadline'   => '_dcevents_registration_deadline',
            'dcevents_status'                  => '_dcevents_status',
            'dcevents_currency'                => '_dcevents_currency',
        ];

        foreach ( $fields as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ) );
            }
        }

        // Precio (numérico)
        if ( isset( $_POST['dcevents_price'] ) ) {
            update_post_meta( $post_id, '_dcevents_price', floatval( $_POST['dcevents_price'] ) );
        }

        // Checkboxes
        $checkboxes = [
            'dcevents_featured'           => '_dcevents_featured',
            'dcevents_is_virtual'         => '_dcevents_is_virtual',
            'dcevents_registration_open'  => '_dcevents_registration_open',
            'dcevents_require_approval'   => '_dcevents_require_approval',
        ];

        foreach ( $checkboxes as $post_key => $meta_key ) {
            update_post_meta( $post_id, $meta_key, isset( $_POST[ $post_key ] ) ? '1' : '0' );
        }

        // Campos personalizados del formulario
        $custom_fields = isset( $_POST['dcevents_custom_fields'] ) ? $_POST['dcevents_custom_fields'] : [];
        $sanitized_fields = [];
        foreach ( $custom_fields as $field ) {
            if ( ! empty( $field['label'] ) ) {
                $sanitized_fields[] = [
                    'label'    => sanitize_text_field( $field['label'] ),
                    'type'     => sanitize_key( $field['type'] ),
                    'required' => isset( $field['required'] ) ? '1' : '0',
                ];
            }
        }
        update_post_meta( $post_id, '_dcevents_custom_fields', $sanitized_fields );
    }
}

// Inicializar en admin
if ( is_admin() ) {
    DCEvents_Meta_Boxes::instance();
}
