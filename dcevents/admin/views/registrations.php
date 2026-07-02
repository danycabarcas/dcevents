<?php
/**
 * Vista: Lista de inscripciones
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'dcevents_view_all_registrations' ) ) wp_die( __( 'Sin acceso', 'dc-events' ) );

$event_id      = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : 0;
$status_filter = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
$search        = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$current_page  = isset( $_GET['paged'] ) ? max(1, intval($_GET['paged'])) : 1;
$per_page      = 20;

// Query
$meta_query = [ 'relation' => 'AND' ];
if ( $event_id ) {
    $meta_query[] = [ 'key' => '_dcevents_event_id', 'value' => $event_id ];
}
if ( $status_filter ) {
    $meta_query[] = [ 'key' => '_dcevents_reg_status', 'value' => $status_filter ];
}

$query_args = [
    'post_type'      => 'dc_registration',
    'post_status'    => 'publish',
    'posts_per_page' => $per_page,
    'paged'          => $current_page,
    'orderby'        => 'date',
    'order'          => 'DESC',
];

if ( count( $meta_query ) > 1 ) {
    $query_args['meta_query'] = $meta_query;
}

if ( $search ) {
    $query_args['s'] = $search;
}

$query         = new WP_Query( $query_args );
$registrations = $query->posts;
$total         = $query->found_posts;
$total_pages   = ceil( $total / $per_page );

$event = $event_id ? get_post( $event_id ) : null;
$base_url = admin_url( 'admin.php?page=dc-events-registrations' );
?>
<div class="wrap dce-admin-wrap">
    <div class="dce-admin-header">
        <div class="dce-admin-header-left">
            <h1 class="dce-admin-title">
                <?php if ( $event ) : ?>
                    <?php _e( 'Inscripciones:', 'dc-events' ); ?> <?php echo esc_html( $event->post_title ); ?>
                <?php else : ?>
                    <?php _e( '👥 Todas las Inscripciones', 'dc-events' ); ?>
                <?php endif; ?>
            </h1>
            <?php if ( $event ) : ?>
            <a href="<?php echo esc_url( $base_url ); ?>" class="dce-back-link">← <?php _e( 'Todas las inscripciones', 'dc-events' ); ?></a>
            <?php endif; ?>
        </div>
        <div class="dce-admin-header-right">
            <?php if ( current_user_can( 'dcevents_export_registrations' ) ) : ?>
            <a href="<?php echo esc_url( add_query_arg( [ 'dcevents_export' => 1, 'event_id' => $event_id, '_nonce' => wp_create_nonce('dcevents_export') ], admin_url('admin-post.php?action=dcevents_export') ) ); ?>"
               class="dce-btn dce-btn-secondary">
                📥 <?php _e( 'Exportar CSV', 'dc-events' ); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ( $event_id ) : 
        $all_regs_event = get_posts([
            'post_type' => 'dc_registration',
            'posts_per_page' => -1,
            'meta_query' => [
                [ 'key' => '_dcevents_event_id', 'value' => $event_id ],
                [ 'key' => '_dcevents_reg_status', 'value' => 'cancelled', 'compare' => '!=' ]
            ],
            'fields' => 'ids'
        ]);
        $total_valid = count($all_regs_event);
        $checked_in = 0;
        foreach ($all_regs_event as $rid) {
            if ( get_post_meta($rid, '_dcevents_checked_in', true) === '1' ) {
                $checked_in++;
            }
        }
        $pending = $total_valid - $checked_in;
    ?>
    <!-- Estadísticas en vivo -->
    <div class="dce-admin-stats-board" style="display:flex; gap:20px; background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd; margin-bottom:20px; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        <div style="flex:1; text-align:center;">
            <div style="font-size:12px; color:#888; text-transform:uppercase; font-weight:700; letter-spacing:1px;">🎫 Total Válidos</div>
            <div style="font-size:36px; font-weight:900; color:#333; font-family:monospace;"><?php echo $total_valid; ?></div>
        </div>
        <div style="flex:1; text-align:center; border-left:1px solid #eee; border-right:1px solid #eee;">
            <div style="font-size:12px; color:#1ed760; text-transform:uppercase; font-weight:700; letter-spacing:1px;">✅ Han Ingresado</div>
            <div style="font-size:36px; font-weight:900; color:#1ed760; font-family:monospace;"><?php echo $checked_in; ?></div>
        </div>
        <div style="flex:1; text-align:center;">
            <div style="font-size:12px; color:#ff4444; text-transform:uppercase; font-weight:700; letter-spacing:1px;">⏳ Faltan</div>
            <div style="font-size:36px; font-weight:900; color:#ff4444; font-family:monospace;"><?php echo $pending; ?></div>
        </div>
    </div>
    <?php endif; ?>
    <!-- Filtros -->
    <div class="dce-filters-bar">
        <form method="get" class="dce-search-form">
            <input type="hidden" name="page" value="dc-events-registrations">
            <?php if ($event_id) : ?><input type="hidden" name="event_id" value="<?php echo $event_id; ?>"><?php endif; ?>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>"
                   placeholder="<?php _e('Buscar por nombre o email...', 'dc-events'); ?>"
                   class="dce-search-input">
            <button type="submit" class="dce-btn dce-btn-sm">🔍 <?php _e('Buscar', 'dc-events'); ?></button>
        </form>

        <div class="dce-status-filters">
            <?php
            $statuses = [
                '' => __('Todos', 'dc-events'),
                'pending' => __('Pendientes', 'dc-events'),
                'confirmed' => __('Confirmados', 'dc-events'),
                'payment_pending' => __('Pago Pendiente', 'dc-events'),
                'paid' => __('Pagados', 'dc-events'),
                'attended' => __('Asistieron', 'dc-events'),
                'cancelled' => __('Cancelados', 'dc-events'),
            ];
            foreach ( $statuses as $key => $label ) :
                $url = add_query_arg( array_filter([
                    'page' => 'dc-events-registrations',
                    'event_id' => $event_id ?: null,
                    'status' => $key ?: null,
                ]), admin_url('admin.php') );
            ?>
            <a href="<?php echo esc_url($url); ?>"
               class="dce-filter-link <?php echo $status_filter === $key ? 'active' : ''; ?>">
                <?php echo esc_html($label); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabla -->
    <div class="dce-card">
        <div class="dce-table-info">
            <?php echo sprintf( _n('%d inscripción encontrada', '%d inscripciones encontradas', $total, 'dc-events'), $total ); ?>
        </div>

        <?php if ( empty( $registrations ) ) : ?>
        <div class="dce-empty-state">
            <span>👤</span>
            <p><?php _e( 'No se encontraron inscripciones con los filtros actuales.', 'dc-events' ); ?></p>
        </div>
        <?php else : ?>
        <div class="dce-table-wrapper">
            <table class="dce-table dce-reg-table">
                <thead>
                    <tr>
                        <th><?php _e('Asistente', 'dc-events'); ?></th>
                        <th><?php _e('Evento', 'dc-events'); ?></th>
                        <th><?php _e('Código', 'dc-events'); ?></th>
                        <th><?php _e('Estado', 'dc-events'); ?></th>
                        <th><?php _e('Check-in', 'dc-events'); ?></th>
                        <th><?php _e('Fecha', 'dc-events'); ?></th>
                        <th><?php _e('Acciones', 'dc-events'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $registrations as $reg ) :
                        $fname     = get_post_meta( $reg->ID, '_dcevents_first_name', true );
                        $lname     = get_post_meta( $reg->ID, '_dcevents_last_name', true );
                        $email     = get_post_meta( $reg->ID, '_dcevents_email', true );
                        $phone     = get_post_meta( $reg->ID, '_dcevents_phone', true );
                        $ev_id     = get_post_meta( $reg->ID, '_dcevents_event_id', true );
                        $ev        = get_post( $ev_id );
                        $code      = get_post_meta( $reg->ID, '_dcevents_code', true );
                        $status    = get_post_meta( $reg->ID, '_dcevents_reg_status', true );
                        $checked   = get_post_meta( $reg->ID, '_dcevents_checked_in', true );
                        $info      = DCEvents_Registration::get_status_label( $status );
                        $detail    = add_query_arg( ['page' => 'dc-events-registrations', 'registration_id' => $reg->ID], admin_url('admin.php') );
                    ?>
                    <tr data-registration-id="<?php echo $reg->ID; ?>">
                        <td>
                            <div class="dce-attendee-cell">
                                <div class="dce-reg-avatar-sm"><?php echo strtoupper( substr($fname,0,1).substr($lname,0,1) ); ?></div>
                                <div>
                                    <div class="dce-attendee-name"><?php echo esc_html( "$fname $lname" ); ?></div>
                                    <div class="dce-attendee-email"><?php echo esc_html( $email ); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $ev ? esc_html( $ev->post_title ) : '—'; ?></td>
                        <td><code class="dce-code"><?php echo esc_html($code); ?></code></td>
                        <td>
                            <select class="dce-status-select" data-id="<?php echo $reg->ID; ?>"
                                    <?php echo ! current_user_can('dcevents_edit_registration_status') ? 'disabled' : ''; ?>
                                    style="border-color:<?php echo esc_attr($info['color']); ?>">
                                <?php foreach ( ['pending','confirmed','payment_pending','paid','attended','cancelled'] as $s ) :
                                    $si = DCEvents_Registration::get_status_label($s); ?>
                                    <option value="<?php echo $s; ?>" <?php selected($status,$s); ?>><?php echo esc_html($si['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <?php if ( $checked === '1' ) : ?>
                                <span class="dce-checkin-done">✅ <?php _e('Chequeado', 'dc-events'); ?></span>
                            <?php elseif ( current_user_can('dcevents_check_in_attendee') ) : ?>
                                <button class="dce-btn dce-btn-sm dce-btn-checkin" data-id="<?php echo $reg->ID; ?>">
                                    <?php _e('Check-in', 'dc-events'); ?>
                                </button>
                            <?php else : ?>
                                <span style="color:#ccc">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date_i18n( 'd M Y H:i', strtotime($reg->post_date) ); ?></td>
                        <td>
                            <a href="<?php echo esc_url($detail); ?>" class="dce-btn dce-btn-sm">
                                <?php _e('Ver', 'dc-events'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <?php if ( $total_pages > 1 ) : ?>
        <div class="dce-pagination">
            <?php for ( $i = 1; $i <= $total_pages; $i++ ) :
                $url = add_query_arg( array_filter([
                    'page' => 'dc-events-registrations',
                    'event_id' => $event_id ?: null,
                    'status' => $status_filter ?: null,
                    'paged' => $i > 1 ? $i : null,
                ]), admin_url('admin.php') );
            ?>
            <a href="<?php echo esc_url($url); ?>"
               class="dce-page-link <?php echo $i === $current_page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
