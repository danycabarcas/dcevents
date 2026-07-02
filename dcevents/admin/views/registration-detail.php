<?php
/**
 * Vista: Detalle de inscripción individual
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'dcevents_view_all_registrations' ) ) wp_die( __( 'Sin acceso', 'dc-events' ) );

$registration_id = intval( $_GET['registration_id'] ?? 0 );
$registration    = get_post( $registration_id );

if ( ! $registration || 'dc_registration' !== $registration->post_type ) {
    echo '<div class="wrap"><p>' . __( 'Inscripción no encontrada.', 'dc-events' ) . '</p></div>';
    return;
}

// Datos
$first_name  = get_post_meta( $registration_id, '_dcevents_first_name', true );
$last_name   = get_post_meta( $registration_id, '_dcevents_last_name', true );
$email       = get_post_meta( $registration_id, '_dcevents_email', true );
$phone       = get_post_meta( $registration_id, '_dcevents_phone', true );
$id_number   = get_post_meta( $registration_id, '_dcevents_id_number', true );
$event_id    = get_post_meta( $registration_id, '_dcevents_event_id', true );
$event       = get_post( $event_id );
$code        = get_post_meta( $registration_id, '_dcevents_code', true );
$status      = get_post_meta( $registration_id, '_dcevents_reg_status', true );
$reg_date    = get_post_meta( $registration_id, '_dcevents_registration_date', true );
$checked_in  = get_post_meta( $registration_id, '_dcevents_checked_in', true );
$checkin_t   = get_post_meta( $registration_id, '_dcevents_checkin_time', true );
$custom_data = get_post_meta( $registration_id, '_dcevents_custom_data', true ) ?: [];
$amount      = get_post_meta( $registration_id, '_dcevents_amount', true );
$pay_status  = get_post_meta( $registration_id, '_dcevents_payment_status', true );
$user_id     = get_post_meta( $registration_id, '_dcevents_user_id', true );
$user        = $user_id ? get_user_by( 'id', $user_id ) : null;
$info        = DCEvents_Registration::get_status_label( $status );
$back_url    = add_query_arg( ['page' => 'dc-events-registrations', 'event_id' => $event_id ], admin_url('admin.php') );
?>
<div class="wrap dce-admin-wrap">
    <div class="dce-admin-header">
        <div class="dce-admin-header-left">
            <h1 class="dce-admin-title">
                📋 <?php echo esc_html( "$first_name $last_name" ); ?>
            </h1>
            <a href="<?php echo esc_url($back_url); ?>" class="dce-back-link">← <?php _e('Volver a inscripciones', 'dc-events'); ?></a>
        </div>
        <div class="dce-admin-header-right">
            <?php if ( current_user_can('dcevents_check_in_attendee') && $checked_in !== '1' ) : ?>
            <button class="dce-btn dce-btn-primary dce-btn-checkin" data-id="<?php echo $registration_id; ?>">
                ✅ <?php _e('Hacer Check-in', 'dc-events'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="dce-detail-grid">
        <!-- Info del asistente -->
        <div class="dce-card">
            <div class="dce-card-header"><h2>👤 <?php _e('Datos del Asistente', 'dc-events'); ?></h2></div>
            <table class="dce-detail-table">
                <tr>
                    <td><?php _e('Nombre completo', 'dc-events'); ?></td>
                    <td><strong><?php echo esc_html("$first_name $last_name"); ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('Email', 'dc-events'); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></td>
                </tr>
                <tr>
                    <td><?php _e('Teléfono', 'dc-events'); ?></td>
                    <td><?php echo $phone ? esc_html($phone) : '—'; ?></td>
                </tr>
                <tr>
                    <td><?php _e('Documento', 'dc-events'); ?></td>
                    <td><?php echo $id_number ? esc_html($id_number) : '—'; ?></td>
                </tr>
                <?php if ( $user ) : ?>
                <tr>
                    <td><?php _e('Usuario WP', 'dc-events'); ?></td>
                    <td><a href="<?php echo get_edit_user_link($user_id); ?>"><?php echo esc_html($user->display_name); ?></a></td>
                </tr>
                <?php endif; ?>
                <?php foreach ( $custom_data as $label => $value ) : ?>
                <tr>
                    <td><?php echo esc_html($label); ?></td>
                    <td><?php echo esc_html($value); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Info del evento e inscripción -->
        <div class="dce-detail-right">
            <div class="dce-card">
                <div class="dce-card-header"><h2>📅 <?php _e('Evento', 'dc-events'); ?></h2></div>
                <table class="dce-detail-table">
                    <?php if ( $event ) :
                        $date = get_post_meta($event_id, '_dcevents_start_date', true);
                        $venue = get_post_meta($event_id, '_dcevents_venue', true);
                        $city  = get_post_meta($event_id, '_dcevents_city', true);
                    ?>
                    <tr>
                        <td><?php _e('Evento', 'dc-events'); ?></td>
                        <td><strong><a href="<?php echo get_edit_post_link($event_id); ?>"><?php echo esc_html($event->post_title); ?></a></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Fecha', 'dc-events'); ?></td>
                        <td><?php echo $date ? date_i18n('l j \d\e F Y', strtotime($date)) : '—'; ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Lugar', 'dc-events'); ?></td>
                        <td><?php echo $venue ? esc_html("$venue, $city") : '—'; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="dce-card" style="margin-top:16px">
                <div class="dce-card-header"><h2>📋 <?php _e('Inscripción', 'dc-events'); ?></h2></div>
                <div style="padding:16px">
                    <div class="dce-code-display">
                        <span class="dce-code-label"><?php _e('Código de Inscripción', 'dc-events'); ?></span>
                        <span class="dce-code-value"><?php echo esc_html($code); ?></span>
                    </div>
                </div>
                <table class="dce-detail-table">
                    <tr>
                        <td><?php _e('Estado', 'dc-events'); ?></td>
                        <td>
                            <?php if ( current_user_can('dcevents_edit_registration_status') ) : ?>
                            <select class="dce-status-select" data-id="<?php echo $registration_id; ?>"
                                    style="border-color:<?php echo esc_attr($info['color']); ?>">
                                <?php foreach ( ['pending','confirmed','payment_pending','paid','attended','cancelled'] as $s ) :
                                    $si = DCEvents_Registration::get_status_label($s); ?>
                                    <option value="<?php echo $s; ?>" <?php selected($status,$s); ?>><?php echo esc_html($si['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php else : ?>
                            <span style="background:<?php echo esc_attr($info['color']); ?>;color:#fff;padding:3px 8px;border-radius:12px;font-size:12px">
                                <?php echo esc_html($info['label']); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Fecha de inscripción', 'dc-events'); ?></td>
                        <td><?php echo $reg_date ? date_i18n('d M Y H:i', strtotime($reg_date)) : date_i18n('d M Y H:i', strtotime($registration->post_date)); ?></td>
                    </tr>
                    <?php if ( $amount > 0 ) : ?>
                    <tr>
                        <td><?php _e('Monto', 'dc-events'); ?></td>
                        <td><?php echo '$' . number_format($amount, 0, ',', '.') . ' ' . get_post_meta($event_id, '_dcevents_currency', true); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Estado de pago', 'dc-events'); ?></td>
                        <td><?php echo esc_html($pay_status ?: 'not_required'); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?php _e('Check-in', 'dc-events'); ?></td>
                        <td>
                            <?php if ( $checked_in === '1' ) : ?>
                                <span style="color:#46b450;font-weight:700">✅ <?php _e('Realizado', 'dc-events'); ?></span>
                                <?php if ($checkin_t) : ?><br><small><?php echo date_i18n('d M Y H:i', strtotime($checkin_t)); ?></small><?php endif; ?>
                            <?php else : ?>
                                <span style="color:#ccc">⬜ <?php _e('Pendiente', 'dc-events'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
