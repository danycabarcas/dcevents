<?php
/**
 * Vista: Formulario de inscripción
 *
 * @package DCEvents
 * @var WP_Post $event
 * @var int     $event_id
 * @var string  $status
 * @var bool    $can_register
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$is_available = DCEvents_Post_Types::has_availability( $event_id );
$dl_passed    = $reg_dl && strtotime( $reg_dl ) < time();
$can_register = $is_available && $reg_open !== '0' && ! $dl_passed && $status === 'active';
$price        = (float) get_post_meta( $event_id, '_dcevents_price', true );
$currency     = get_post_meta( $event_id, '_dcevents_currency', true ) ?: 'COP';
$custom_fields= get_post_meta( $event_id, '_dcevents_custom_fields', true ) ?: [];
$form_title   = DCEvents_Settings::get( 'register_title', __( 'Inscripción al Evento', 'dc-events' ) );
$start_date   = get_post_meta( $event_id, '_dcevents_start_date', true );
$venue        = get_post_meta( $event_id, '_dcevents_venue', true );
$city         = get_post_meta( $event_id, '_dcevents_city', true );
$is_virtual   = get_post_meta( $event_id, '_dcevents_is_virtual', true );
$max          = (int) get_post_meta( $event_id, '_dcevents_max_capacity', true );
$count        = (int) get_post_meta( $event_id, '_dcevents_registered_count', true );
$require_approval = get_post_meta( $event_id, '_dcevents_require_approval', true );
?>
<div class="dce-registration-wrapper" id="dce-registration-<?php echo $event_id; ?>">

    <!-- Header del formulario -->
    <div class="dce-reg-header">
        <div class="dce-reg-header-icon">📋</div>
        <div>
            <h2 class="dce-reg-title"><?php echo esc_html($form_title); ?></h2>
            <div class="dce-reg-event-name"><?php echo esc_html($event->post_title); ?></div>
        </div>
    </div>

    <!-- Info del evento -->
    <div class="dce-reg-event-info">
        <?php if ($start_date) : ?>
        <div class="dce-reg-info-item">
            <span class="dce-ri-icon">📅</span>
            <span><?php echo date_i18n( 'l j \d\e F \d\e Y', strtotime($start_date) ); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($is_virtual) : ?>
        <div class="dce-reg-info-item">
            <span class="dce-ri-icon">🌐</span>
            <span><?php _e('Evento Online', 'dc-events'); ?></span>
        </div>
        <?php elseif ($venue) : ?>
        <div class="dce-reg-info-item">
            <span class="dce-ri-icon">📍</span>
            <span><?php echo esc_html("$venue, $city"); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($price > 0) : ?>
        <div class="dce-reg-info-item dce-ri-price">
            <span class="dce-ri-icon">💳</span>
            <span><strong><?php echo '$' . number_format($price, 0, ',', '.') . ' ' . $currency; ?></strong></span>
        </div>
        <?php else : ?>
        <div class="dce-reg-info-item dce-ri-free">
            <span class="dce-ri-icon">🎉</span>
            <span><strong><?php _e('Evento Gratuito', 'dc-events'); ?></strong></span>
        </div>
        <?php endif; ?>
        <?php if ($max > 0) : ?>
        <div class="dce-reg-info-item">
            <span class="dce-ri-icon">👥</span>
            <span><?php echo "$count / $max " . __('cupos', 'dc-events'); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Estado del evento -->
    <?php if (!$can_register) : ?>
    <div class="dce-reg-blocked">
        <?php if ($status === 'cancelled') : ?>
            <span>❌</span> <?php _e('Este evento ha sido cancelado.', 'dc-events'); ?>
        <?php elseif (!$is_available || $status === 'full') : ?>
            <span>🔴</span> <?php _e('Lo sentimos, este evento está agotado.', 'dc-events'); ?>
        <?php elseif ($dl_passed) : ?>
            <span>⏰</span> <?php _e('La fecha límite de inscripción ha pasado.', 'dc-events'); ?>
        <?php elseif ($reg_open === '0') : ?>
            <span>🔒</span> <?php _e('Las inscripciones para este evento están cerradas.', 'dc-events'); ?>
        <?php endif; ?>
    </div>

    <?php else : ?>

    <!-- Formulario -->
    <form class="dce-registration-form" data-event-id="<?php echo $event_id; ?>">
        <?php wp_nonce_field('dcevents_register_nonce', 'dce_nonce_field'); ?>
        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

        <div class="dce-form-row dce-form-row-2">
            <div class="dce-form-group">
                <label for="dce_first_name_<?php echo $event_id; ?>"><?php _e('Nombre *', 'dc-events'); ?></label>
                <input type="text" id="dce_first_name_<?php echo $event_id; ?>"
                       name="first_name" required
                       placeholder="<?php _e('Tu nombre', 'dc-events'); ?>"
                       class="dce-input">
            </div>
            <div class="dce-form-group">
                <label for="dce_last_name_<?php echo $event_id; ?>"><?php _e('Apellido *', 'dc-events'); ?></label>
                <input type="text" id="dce_last_name_<?php echo $event_id; ?>"
                       name="last_name" required
                       placeholder="<?php _e('Tu apellido', 'dc-events'); ?>"
                       class="dce-input">
            </div>
        </div>

        <div class="dce-form-row dce-form-row-2">
            <div class="dce-form-group">
                <label for="dce_email_<?php echo $event_id; ?>"><?php _e('Email *', 'dc-events'); ?></label>
                <input type="email" id="dce_email_<?php echo $event_id; ?>"
                       name="email" required
                       placeholder="tu@email.com"
                       class="dce-input"
                       <?php echo is_user_logged_in() ? 'value="' . esc_attr(wp_get_current_user()->user_email) . '"' : ''; ?>>
            </div>
            <div class="dce-form-group">
                <label for="dce_phone_<?php echo $event_id; ?>"><?php _e('Teléfono', 'dc-events'); ?></label>
                <input type="tel" id="dce_phone_<?php echo $event_id; ?>"
                       name="phone"
                       placeholder="300 000 0000"
                       class="dce-input">
            </div>
        </div>

        <div class="dce-form-row">
            <div class="dce-form-group">
                <label for="dce_id_number_<?php echo $event_id; ?>"><?php _e('Número de Documento', 'dc-events'); ?></label>
                <input type="text" id="dce_id_number_<?php echo $event_id; ?>"
                       name="id_number"
                       placeholder="CC / TI / Pasaporte"
                       class="dce-input">
            </div>
        </div>

        <!-- Campos personalizados -->
        <?php foreach ($custom_fields as $i => $field) :
            $field_id = 'dce_custom_' . $event_id . '_' . $i;
            $required = $field['required'] === '1';
        ?>
        <div class="dce-form-row">
            <div class="dce-form-group">
                <label for="<?php echo $field_id; ?>">
                    <?php echo esc_html($field['label']); ?> <?php echo $required ? '*' : ''; ?>
                </label>
                <?php if ($field['type'] === 'textarea') : ?>
                    <textarea id="<?php echo $field_id; ?>" name="custom_field_<?php echo $i; ?>"
                              <?php echo $required ? 'required' : ''; ?>
                              class="dce-input dce-textarea" rows="3"></textarea>
                <?php elseif ($field['type'] === 'select') : ?>
                    <select id="<?php echo $field_id; ?>" name="custom_field_<?php echo $i; ?>"
                            <?php echo $required ? 'required' : ''; ?> class="dce-input">
                        <option value=""><?php _e('Selecciona...', 'dc-events'); ?></option>
                    </select>
                <?php else : ?>
                    <input type="<?php echo esc_attr($field['type']); ?>"
                           id="<?php echo $field_id; ?>"
                           name="custom_field_<?php echo $i; ?>"
                           <?php echo $required ? 'required' : ''; ?>
                           class="dce-input">
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($require_approval === '1') : ?>
        <div class="dce-form-notice">
            ℹ️ <?php _e('Tu inscripción quedará pendiente de aprobación.', 'dc-events'); ?>
        </div>
        <?php endif; ?>

        <?php if ($price > 0) : ?>
        <div class="dce-form-notice dce-notice-payment">
            💳 <?php printf( __('Tras inscribirte deberás realizar el pago de <strong>%s %s</strong>.', 'dc-events'), '$' . number_format($price, 0, ',', '.'), $currency ); ?>
        </div>
        <?php endif; ?>

        <!-- Mensaje de error/éxito -->
        <div class="dce-form-message" id="dce-msg-<?php echo $event_id; ?>" style="display:none"></div>

        <div class="dce-form-row">
            <button type="submit" class="dce-submit-btn" id="dce-submit-<?php echo $event_id; ?>">
                <span class="dce-btn-text"><?php echo esc_html(DCEvents_Settings::get('button_text', __('Inscribirse', 'dc-events'))); ?></span>
                <span class="dce-btn-icon">→</span>
            </button>
        </div>

        <p class="dce-form-disclaimer">
            <?php _e('Al inscribirte aceptas recibir información relacionada con el evento por email. Tu información está segura con nosotros.', 'dc-events'); ?>
        </p>
    </form>
    <?php endif; ?>
</div>
