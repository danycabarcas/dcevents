<?php
/**
 * Vista: Lista de eventos — Layout "list" (estilo MCI Santa Marta)
 *
 * @package DCEvents
 * @var WP_Post[] $events
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="dce-events-container dce-layout-list">

    <?php foreach ( $events as $event ) :
        $start_date  = get_post_meta( $event->ID, '_dcevents_start_date', true );
        $start_time  = get_post_meta( $event->ID, '_dcevents_start_time', true );
        $end_date    = get_post_meta( $event->ID, '_dcevents_end_date', true );
        $venue       = get_post_meta( $event->ID, '_dcevents_venue', true );
        $city        = get_post_meta( $event->ID, '_dcevents_city', true );
        $is_virtual  = get_post_meta( $event->ID, '_dcevents_is_virtual', true );
        $price       = DCEvents_Post_Types::get_active_price( $event->ID );
        $tier_name   = DCEvents_Post_Types::get_active_tier_name( $event->ID );
        $currency    = get_post_meta( $event->ID, '_dcevents_currency', true ) ?: 'COP';
        $status      = get_post_meta( $event->ID, '_dcevents_status', true ) ?: 'active';
        $count       = (int) get_post_meta( $event->ID, '_dcevents_registered_count', true );
        $max         = (int) get_post_meta( $event->ID, '_dcevents_max_capacity', true );
        $featured    = get_post_meta( $event->ID, '_dcevents_featured', true );
        $reg_open    = get_post_meta( $event->ID, '_dcevents_registration_open', true );
        $reg_dl      = get_post_meta( $event->ID, '_dcevents_registration_deadline', true );

        $day    = $start_date ? date_i18n( 'd', strtotime( $start_date ) ) : '';
        $month  = $start_date ? date_i18n( 'M', strtotime( $start_date ) ) : '';
        $year   = $start_date ? date_i18n( 'Y', strtotime( $start_date ) ) : '';

        $is_available = DCEvents_Post_Types::has_availability( $event->ID );
        $dl_passed    = $reg_dl && strtotime( $reg_dl ) < time();
        $can_register = $is_available && $reg_open !== '0' && ! $dl_passed && $status === 'active';

        $location = $is_virtual
            ? '<span class="dce-virtual-badge">🌐 ' . __('Online', 'dc-events') . '</span>'
            : esc_html( trim( ( $venue ? $venue . ', ' : '' ) . $city ) );

        $event_url    = get_permalink( $event->ID );
        $btn_text     = DCEvents_Settings::get('button_text', __('Inscribirse', 'dc-events'));
    ?>
    <div class="dce-event-row <?php echo $featured ? 'dce-featured' : ''; ?> dce-status-<?php echo esc_attr($status); ?>"
         data-event-id="<?php echo $event->ID; ?>">

        <!-- Arte / Imagen -->
        <?php if ( has_post_thumbnail( $event->ID ) ) : 
            $full_img = get_the_post_thumbnail_url( $event->ID, 'full' );
            $thumb_img = get_the_post_thumbnail_url( $event->ID, 'medium' );
        ?>
        <div class="dce-event-art">
            <a href="<?php echo esc_url( $full_img ); ?>" class="dce-lightbox-trigger" data-title="<?php echo esc_attr( $event->post_title ); ?>">
                <img src="<?php echo esc_url( $thumb_img ); ?>" alt="<?php echo esc_attr( $event->post_title ); ?>">
            </a>
        </div>
        <?php endif; ?>

        <!-- Fecha -->
        <div class="dce-event-date">
            <span class="dce-date-day"><?php echo $day; ?></span>
            <span class="dce-date-month"><?php echo $month; ?></span>
            <span class="dce-date-year"><?php echo $year; ?></span>
        </div>

        <!-- Info principal -->
        <div class="dce-event-body">
            <div class="dce-event-badges">
                <?php if ($featured) : ?><span class="dce-badge dce-badge-featured">⭐ Destacado</span><?php endif; ?>
                <?php if ($price > 0) : ?>
                    <span class="dce-badge dce-badge-paid">
                        💳 <?php echo number_format($price, 0, ',', '.') . ' ' . $currency; ?>
                        <?php if ($tier_name) : ?>
                            <span style="opacity:.75;font-size:10px;margin-left:3px">(<?php echo esc_html($tier_name); ?>)</span>
                        <?php endif; ?>
                    </span>
                <?php else : ?>
                    <span class="dce-badge dce-badge-free"><?php _e('Gratuito', 'dc-events'); ?></span>
                <?php endif; ?>
            </div>

            <h3 class="dce-event-title">
                <a href="<?php echo esc_url($event_url); ?>"><?php echo esc_html($event->post_title); ?></a>
            </h3>

            <div class="dce-event-meta">
                <?php if ($location) : ?>
                <span class="dce-meta-item">📍 <?php echo $location; ?></span>
                <?php endif; ?>
                <?php if ($start_time) : ?>
                <span class="dce-meta-item">🕐 <?php echo date_i18n('g:i a', strtotime($start_time)); ?></span>
                <?php endif; ?>
                <?php if ($max > 0) : ?>
                <span class="dce-meta-item">👥 <?php echo $count . ' / ' . $max; ?></span>
                <?php endif; ?>
            </div>

            <?php if ($max > 0 && $max > 0) :
                $pct = round(($count / $max) * 100);
            ?>
            <div class="dce-capacity-bar">
                <div class="dce-capacity-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $pct >= 90 ? 'var(--dce-danger,#dc3232)' : 'var(--dce-primary)'; ?>"></div>
            </div>
            <?php endif; ?>

            <?php if ( DCEvents_Settings::get('show_countdown') === '1' && $start_date && strtotime($start_date) > time() ) : ?>
            <div class="dce-countdown" data-target="<?php echo esc_attr(strtotime($start_date . ' ' . $start_time) * 1000); ?>">
                <span class="dce-cd-item"><span class="dce-cd-num" data-unit="days">—</span><span class="dce-cd-label"><?php _e('días', 'dc-events'); ?></span></span>
                <span class="dce-cd-sep">:</span>
                <span class="dce-cd-item"><span class="dce-cd-num" data-unit="hours">—</span><span class="dce-cd-label"><?php _e('hrs', 'dc-events'); ?></span></span>
                <span class="dce-cd-sep">:</span>
                <span class="dce-cd-item"><span class="dce-cd-num" data-unit="minutes">—</span><span class="dce-cd-label"><?php _e('min', 'dc-events'); ?></span></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Acción -->
        <div class="dce-event-action">
            <?php if ($status === 'cancelled') : ?>
                <span class="dce-status-badge dce-badge-cancelled"><?php _e('Cancelado', 'dc-events'); ?></span>
            <?php elseif (!$is_available || $status === 'full') : ?>
                <span class="dce-status-badge dce-badge-full"><?php _e('Agotado', 'dc-events'); ?></span>
            <?php elseif (!$can_register) : ?>
                <span class="dce-status-badge dce-badge-closed"><?php _e('Inscripciones cerradas', 'dc-events'); ?></span>
            <?php else : ?>
                <a href="<?php echo esc_url($event_url); ?>" class="dce-btn-register">
                    <?php echo esc_html($btn_text); ?> <span class="dce-arrow">›</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
