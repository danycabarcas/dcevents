<?php
/**
 * Vista: Panel del asistente — Mis Inscripciones
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="dce-my-registrations">

    <?php if ( ! is_user_logged_in() ) : ?>
    <!-- No logueado -->
    <div class="dce-login-required">
        <div class="dce-login-icon">🔐</div>
        <h2><?php _e('Área privada', 'dc-events'); ?></h2>
        <p><?php _e('Inicia sesión para ver tus inscripciones a eventos.', 'dc-events'); ?></p>
        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="dce-btn-primary-link">
            <?php _e('Iniciar Sesión', 'dc-events'); ?>
        </a>
        <p class="dce-register-note">
            <?php _e('¿No tienes cuenta? Al inscribirte en un evento se crea automáticamente.', 'dc-events'); ?>
        </p>
    </div>

    <?php else :
        $current_user = wp_get_current_user();
        $registration = DCEvents_Registration::instance();
        $my_regs = $registration->get_user_registrations();
        $upcoming = [];
        $past     = [];

        foreach ($my_regs as $reg) {
            $event_id  = get_post_meta($reg->ID, '_dcevents_event_id', true);
            $start     = get_post_meta($event_id, '_dcevents_start_date', true);
            if ($start && strtotime($start) >= strtotime('today')) {
                $upcoming[] = $reg;
            } else {
                $past[] = $reg;
            }
        }
    ?>
    <!-- Logueado: panel de inscripciones -->
    <div class="dce-my-reg-header">
        <div class="dce-my-reg-avatar">
            <?php echo get_avatar($current_user->ID, 60, '', '', ['class' => 'dce-avatar-img']); ?>
        </div>
        <div>
            <h2><?php printf( __('Hola, %s', 'dc-events'), esc_html($current_user->display_name)); ?></h2>
            <p><?php printf( _n('Tienes %d inscripción', 'Tienes %d inscripciones', count($my_regs), 'dc-events'), count($my_regs)); ?></p>
        </div>
    </div>

    <?php if (empty($my_regs)) : ?>
    <div class="dce-empty-regs">
        <div class="dce-empty-icon">🎫</div>
        <h3><?php _e('Aún no te has inscrito en ningún evento', 'dc-events'); ?></h3>
        <p><?php _e('Explora los próximos eventos y regístrate para participar.', 'dc-events'); ?></p>
        <a href="<?php echo home_url('/eventos'); ?>" class="dce-btn-primary-link">
            <?php _e('Ver eventos próximos', 'dc-events'); ?>
        </a>
    </div>

    <?php else : ?>

    <!-- Pestañas -->
    <div class="dce-tabs">
        <button class="dce-tab active" data-tab="upcoming">
            🗓️ <?php _e('Próximos', 'dc-events'); ?>
            <span class="dce-tab-count"><?php echo count($upcoming); ?></span>
        </button>
        <button class="dce-tab" data-tab="past">
            🗃️ <?php _e('Anteriores', 'dc-events'); ?>
            <span class="dce-tab-count"><?php echo count($past); ?></span>
        </button>
    </div>

    <!-- Próximos -->
    <div class="dce-tab-content active" id="dce-tab-upcoming">
        <?php if (empty($upcoming)) : ?>
        <div class="dce-tab-empty"><?php _e('No tienes inscripciones en eventos próximos.', 'dc-events'); ?></div>
        <?php else : ?>
        <?php foreach ($upcoming as $reg) :
            $event_id    = get_post_meta($reg->ID, '_dcevents_event_id', true);
            $event       = get_post($event_id);
            $fname       = get_post_meta($reg->ID, '_dcevents_first_name', true);
            $code        = get_post_meta($reg->ID, '_dcevents_code', true);
            $status      = get_post_meta($reg->ID, '_dcevents_reg_status', true);
            $info        = DCEvents_Registration::get_status_label($status);
            $start_date  = get_post_meta($event_id, '_dcevents_start_date', true);
            $start_time  = get_post_meta($event_id, '_dcevents_start_time', true);
            $venue       = get_post_meta($event_id, '_dcevents_venue', true);
            $city        = get_post_meta($event_id, '_dcevents_city', true);
            $is_virtual  = get_post_meta($event_id, '_dcevents_is_virtual', true);
            $price       = (float) get_post_meta($event_id, '_dcevents_price', true);
            $currency    = get_post_meta($event_id, '_dcevents_currency', true) ?: 'COP';
            $checked     = get_post_meta($reg->ID, '_dcevents_checked_in', true);
            $thumb       = get_the_post_thumbnail_url($event_id, 'medium');
        ?>
        <div class="dce-my-reg-card" data-registration-id="<?php echo $reg->ID; ?>">
            <?php if ($thumb) : ?>
            <div class="dce-reg-card-image" style="background-image:url('<?php echo esc_url($thumb); ?>')"></div>
            <?php endif; ?>
            <div class="dce-reg-card-body">
                <div class="dce-reg-card-header">
                    <div>
                        <div class="dce-reg-card-status"
                             style="background:<?php echo esc_attr($info['color']); ?>">
                            <?php echo esc_html($info['label']); ?>
                        </div>
                        <?php if ($checked === '1') : ?>
                        <div class="dce-reg-checkedin">✅ <?php _e('Check-in realizado', 'dc-events'); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($price > 0) : ?>
                    <div class="dce-reg-price"><?php echo '$' . number_format($price, 0, ',', '.') . ' ' . $currency; ?></div>
                    <?php endif; ?>
                </div>

                <h3 class="dce-reg-event-title">
                    <?php echo $event ? esc_html($event->post_title) : '—'; ?>
                </h3>

                <div class="dce-reg-card-meta">
                    <?php if ($start_date) : ?>
                    <span>📅 <?php echo date_i18n('d M Y', strtotime($start_date)); ?>
                        <?php if ($start_time) : ?> — <?php echo date_i18n('g:i a', strtotime($start_time)); ?><?php endif; ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($is_virtual) : ?>
                    <span>🌐 <?php _e('Online', 'dc-events'); ?></span>
                    <?php elseif ($venue) : ?>
                    <span>📍 <?php echo esc_html("$venue, $city"); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Código de inscripción -->
                <div class="dce-reg-ticket">
                    <div class="dce-ticket-perforated"></div>
                    <div class="dce-ticket-code">
                        <span class="dce-ticket-label"><?php _e('Código de Inscripción', 'dc-events'); ?></span>
                        <span class="dce-ticket-num"><?php echo esc_html($code); ?></span>
                        <span class="dce-ticket-copy" onclick="navigator.clipboard.writeText('<?php echo esc_js($code); ?>');this.textContent='✅'" title="<?php _e('Copiar', 'dc-events'); ?>">📋</span>
                    </div>
                </div>

                <div class="dce-reg-card-actions">
                    <?php if ($event) : ?>
                    <a href="<?php echo get_permalink($event_id); ?>" class="dce-card-link"><?php _e('Ver evento', 'dc-events'); ?></a>
                    <?php endif; ?>
                    <?php if (!in_array($status, ['cancelled', 'attended'])) : ?>
                    <button class="dce-card-cancel-btn dce-cancel-registration" data-id="<?php echo $reg->ID; ?>">
                        <?php _e('Cancelar inscripción', 'dc-events'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Anteriores -->
    <div class="dce-tab-content" id="dce-tab-past" style="display:none">
        <?php if (empty($past)) : ?>
        <div class="dce-tab-empty"><?php _e('No tienes inscripciones en eventos anteriores.', 'dc-events'); ?></div>
        <?php else : ?>
        <?php foreach ($past as $reg) :
            $event_id = get_post_meta($reg->ID, '_dcevents_event_id', true);
            $event    = get_post($event_id);
            $code     = get_post_meta($reg->ID, '_dcevents_code', true);
            $status   = get_post_meta($reg->ID, '_dcevents_reg_status', true);
            $info     = DCEvents_Registration::get_status_label($status);
            $start    = get_post_meta($event_id, '_dcevents_start_date', true);
            $checked  = get_post_meta($reg->ID, '_dcevents_checked_in', true);
        ?>
        <div class="dce-my-reg-card dce-past-card">
            <div class="dce-reg-card-body">
                <div class="dce-reg-card-header">
                    <div class="dce-reg-card-status" style="background:<?php echo esc_attr($info['color']); ?>">
                        <?php echo esc_html($info['label']); ?>
                    </div>
                    <?php if ($checked === '1') : ?>
                    <div class="dce-attended-badge">✅ <?php _e('Asististe', 'dc-events'); ?></div>
                    <?php endif; ?>
                </div>
                <h3 class="dce-reg-event-title"><?php echo $event ? esc_html($event->post_title) : '—'; ?></h3>
                <?php if ($start) : ?><p class="dce-past-date">📅 <?php echo date_i18n('d M Y', strtotime($start)); ?></p><?php endif; ?>
                <code class="dce-past-code"><?php echo esc_html($code); ?></code>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php endif; ?>
    <?php endif; ?>

    <!-- Mensaje de cancelación -->
    <div id="dce-cancel-msg" class="dce-action-message" style="display:none"></div>
</div>
