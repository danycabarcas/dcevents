<?php
/**
 * Vista: Dashboard del plugin
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'read' ) ) wp_die( __( 'Sin acceso', 'dc-events' ) );

// Stats
$total_events        = wp_count_posts( 'dc_event' )->publish;
$total_registrations = wp_count_posts( 'dc_registration' )->publish;

$confirmed = count( get_posts( [
    'post_type' => 'dc_registration', 'post_status' => 'publish',
    'posts_per_page' => -1, 'fields' => 'ids',
    'meta_query' => [ [ 'key' => '_dcevents_reg_status', 'value' => 'confirmed' ] ],
] ) );

$pending = count( get_posts( [
    'post_type' => 'dc_registration', 'post_status' => 'publish',
    'posts_per_page' => -1, 'fields' => 'ids',
    'meta_query' => [ [ 'key' => '_dcevents_reg_status', 'value' => 'pending' ] ],
] ) );

// Próximos eventos
$upcoming = get_posts( [
    'post_type'      => 'dc_event',
    'post_status'    => 'publish',
    'posts_per_page' => 5,
    'meta_key'       => '_dcevents_start_date',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [ [
        'key' => '_dcevents_start_date', 'value' => date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE',
    ] ],
] );

// Últimas inscripciones
$recent_regs = get_posts( [
    'post_type' => 'dc_registration', 'post_status' => 'publish', 'posts_per_page' => 8, 'orderby' => 'date', 'order' => 'DESC',
] );
?>
<div class="wrap dce-admin-wrap">
    <div class="dce-admin-header">
        <div class="dce-admin-header-left">
            <h1 class="dce-admin-title">
                <span class="dce-logo">📅</span>
                <?php _e( 'DC Eventos — Dashboard', 'dc-events' ); ?>
            </h1>
            <p class="dce-admin-subtitle"><?php echo esc_html( get_bloginfo('name') ); ?></p>
        </div>
        <div class="dce-admin-header-right">
            <?php if ( current_user_can( 'dcevents_create_event' ) ) : ?>
            <a href="<?php echo admin_url('post-new.php?post_type=dc_event'); ?>" class="dce-btn dce-btn-primary">
                + <?php _e( 'Nuevo Evento', 'dc-events' ); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="dce-stats-grid">
        <div class="dce-stat-card dce-stat-primary">
            <div class="dce-stat-icon">📅</div>
            <div class="dce-stat-number"><?php echo $total_events; ?></div>
            <div class="dce-stat-label"><?php _e( 'Eventos Publicados', 'dc-events' ); ?></div>
            <a href="<?php echo admin_url('edit.php?post_type=dc_event'); ?>" class="dce-stat-link"><?php _e( 'Ver todos →', 'dc-events' ); ?></a>
        </div>
        <div class="dce-stat-card">
            <div class="dce-stat-icon">👥</div>
            <div class="dce-stat-number"><?php echo $total_registrations; ?></div>
            <div class="dce-stat-label"><?php _e( 'Inscripciones Totales', 'dc-events' ); ?></div>
            <a href="<?php echo admin_url('admin.php?page=dc-events-registrations'); ?>" class="dce-stat-link"><?php _e( 'Ver todas →', 'dc-events' ); ?></a>
        </div>
        <div class="dce-stat-card dce-stat-success">
            <div class="dce-stat-icon">✅</div>
            <div class="dce-stat-number"><?php echo $confirmed; ?></div>
            <div class="dce-stat-label"><?php _e( 'Confirmadas', 'dc-events' ); ?></div>
        </div>
        <div class="dce-stat-card dce-stat-warning">
            <div class="dce-stat-icon">⏳</div>
            <div class="dce-stat-number"><?php echo $pending; ?></div>
            <div class="dce-stat-label"><?php _e( 'Pendientes', 'dc-events' ); ?></div>
            <?php if ( $pending > 0 ) : ?>
            <a href="<?php echo admin_url('admin.php?page=dc-events-registrations&status=pending'); ?>" class="dce-stat-link dce-stat-link-warn"><?php _e( 'Revisar →', 'dc-events' ); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div class="dce-dashboard-grid">
        <!-- Próximos eventos -->
        <div class="dce-card">
            <div class="dce-card-header">
                <h2><?php _e( '📅 Próximos Eventos', 'dc-events' ); ?></h2>
                <?php if ( current_user_can( 'dcevents_create_event' ) ) : ?>
                <a href="<?php echo admin_url('post-new.php?post_type=dc_event'); ?>" class="dce-btn dce-btn-sm"><?php _e( '+ Nuevo', 'dc-events' ); ?></a>
                <?php endif; ?>
            </div>
            <?php if ( empty( $upcoming ) ) : ?>
                <div class="dce-empty-state">
                    <span>🗓️</span>
                    <p><?php _e( 'No hay eventos próximos.', 'dc-events' ); ?></p>
                    <?php if ( current_user_can( 'dcevents_create_event' ) ) : ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=dc_event'); ?>" class="dce-btn dce-btn-primary"><?php _e( 'Crear primer evento', 'dc-events' ); ?></a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
            <table class="dce-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Evento', 'dc-events' ); ?></th>
                        <th><?php _e( 'Fecha', 'dc-events' ); ?></th>
                        <th><?php _e( 'Inscritos', 'dc-events' ); ?></th>
                        <th><?php _e( 'Estado', 'dc-events' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $upcoming as $event ) :
                        $date     = get_post_meta( $event->ID, '_dcevents_start_date', true );
                        $count    = (int) get_post_meta( $event->ID, '_dcevents_registered_count', true );
                        $max      = (int) get_post_meta( $event->ID, '_dcevents_max_capacity', true );
                        $status   = get_post_meta( $event->ID, '_dcevents_status', true ) ?: 'active';
                        $percent  = ( $max > 0 ) ? round( ($count / $max) * 100 ) : 0;
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo get_edit_post_link( $event->ID ); ?>" class="dce-event-link">
                                <?php echo esc_html( $event->post_title ); ?>
                            </a>
                        </td>
                        <td><?php echo $date ? date_i18n( 'd M Y', strtotime( $date ) ) : '—'; ?></td>
                        <td>
                            <?php echo $count . ( $max > 0 ? ' / ' . $max : '' ); ?>
                            <?php if ( $max > 0 ) : ?>
                            <div class="dce-progress-bar"><div class="dce-progress-fill" style="width:<?php echo $percent; ?>%;background:<?php echo $percent >= 90 ? '#dc3232' : '#46b450'; ?>"></div></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status_map = [ 'active' => ['✅', '#46b450'], 'full' => ['🔴', '#dc3232'], 'cancelled' => ['❌', '#888'] ];
                            $s = $status_map[ $status ] ?? ['—', '#888'];
                            echo '<span style="color:' . $s[1] . '">' . $s[0] . ' ' . ucfirst( $status ) . '</span>';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Últimas inscripciones -->
        <div class="dce-card">
            <div class="dce-card-header">
                <h2><?php _e( '👥 Últimas Inscripciones', 'dc-events' ); ?></h2>
                <a href="<?php echo admin_url('admin.php?page=dc-events-registrations'); ?>" class="dce-btn dce-btn-sm"><?php _e( 'Ver todas →', 'dc-events' ); ?></a>
            </div>
            <?php if ( empty( $recent_regs ) ) : ?>
                <div class="dce-empty-state">
                    <span>👤</span>
                    <p><?php _e( 'Aún no hay inscripciones.', 'dc-events' ); ?></p>
                </div>
            <?php else : ?>
            <div class="dce-reg-list">
                <?php foreach ( $recent_regs as $reg ) :
                    $fname    = get_post_meta( $reg->ID, '_dcevents_first_name', true );
                    $lname    = get_post_meta( $reg->ID, '_dcevents_last_name', true );
                    $email    = get_post_meta( $reg->ID, '_dcevents_email', true );
                    $event_id = get_post_meta( $reg->ID, '_dcevents_event_id', true );
                    $event    = get_post( $event_id );
                    $status   = get_post_meta( $reg->ID, '_dcevents_reg_status', true );
                    $info     = DCEvents_Registration::get_status_label( $status );
                    $detail   = admin_url('admin.php?page=dc-events-registrations&registration_id=' . $reg->ID);
                ?>
                <div class="dce-reg-item">
                    <div class="dce-reg-avatar"><?php echo strtoupper( substr($fname, 0, 1) . substr($lname, 0, 1) ); ?></div>
                    <div class="dce-reg-info">
                        <div class="dce-reg-name"><a href="<?php echo esc_url($detail); ?>"><?php echo esc_html( $fname . ' ' . $lname ); ?></a></div>
                        <div class="dce-reg-meta"><?php echo esc_html( $event ? $event->post_title : '—' ); ?> · <?php echo human_time_diff( strtotime($reg->post_date), current_time('timestamp') ) . ' ' . __('atrás', 'dc-events'); ?></div>
                    </div>
                    <div class="dce-reg-status" style="background:<?php echo esc_attr($info['color']); ?>"><?php echo esc_html($info['label']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Shortcodes de referencia -->
    <div class="dce-card dce-shortcodes-card">
        <div class="dce-card-header">
            <h2><?php _e( '📋 Shortcodes Disponibles', 'dc-events' ); ?></h2>
        </div>
        <div class="dce-shortcodes-grid">
            <div class="dce-shortcode-item">
                <code>[dc_events]</code>
                <p><?php _e( 'Muestra la lista de eventos próximos.', 'dc-events' ); ?></p>
                <details>
                    <summary><?php _e( 'Parámetros', 'dc-events' ); ?></summary>
                    <pre>limit="10"
layout="list|cards|grid"
category="slug-categoria"
show_past="0|1"
featured_only="0|1"</pre>
                </details>
            </div>
            <div class="dce-shortcode-item">
                <code>[dc_registration_form event_id="X"]</code>
                <p><?php _e( 'Muestra el formulario de inscripción de un evento.', 'dc-events' ); ?></p>
            </div>
            <div class="dce-shortcode-item">
                <code>[dc_my_registrations]</code>
                <p><?php _e( 'Panel del asistente con sus inscripciones.', 'dc-events' ); ?></p>
            </div>
            <div class="dce-shortcode-item">
                <code>[dc_event id="X"]</code>
                <p><?php _e( 'Muestra el detalle de un evento específico.', 'dc-events' ); ?></p>
            </div>
        </div>
    </div>
</div>
