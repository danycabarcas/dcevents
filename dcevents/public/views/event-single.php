<?php
/**
 * Vista: Evento individual
 *
 * @package DCEvents
 * @var WP_Post $event
 * @var int     $event_id
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$start_date   = get_post_meta( $event_id, '_dcevents_start_date', true );
$end_date     = get_post_meta( $event_id, '_dcevents_end_date', true );
$start_time   = get_post_meta( $event_id, '_dcevents_start_time', true );
$end_time     = get_post_meta( $event_id, '_dcevents_end_time', true );
$venue        = get_post_meta( $event_id, '_dcevents_venue', true );
$address      = get_post_meta( $event_id, '_dcevents_address', true );
$city         = get_post_meta( $event_id, '_dcevents_city', true );
$maps_url     = get_post_meta( $event_id, '_dcevents_maps_url', true );
$is_virtual   = get_post_meta( $event_id, '_dcevents_is_virtual', true );
$virtual_url  = get_post_meta( $event_id, '_dcevents_virtual_url', true );
$price        = (float) get_post_meta( $event_id, '_dcevents_price', true );
$currency     = get_post_meta( $event_id, '_dcevents_currency', true ) ?: 'COP';
$status       = get_post_meta( $event_id, '_dcevents_status', true ) ?: 'active';
$max          = (int) get_post_meta( $event_id, '_dcevents_max_capacity', true );
$count        = (int) get_post_meta( $event_id, '_dcevents_registered_count', true );
$categories   = get_the_terms( $event_id, 'event_category' );
$thumbnail    = get_the_post_thumbnail_url( $event_id, 'large' );
?>
<div class="dce-single-event">

    <!-- Header -->
    <?php if ($thumbnail) : ?>
    <div class="dce-single-hero" style="background-image:url('<?php echo esc_url($thumbnail); ?>')">
        <div class="dce-single-hero-overlay"></div>
        <div class="dce-single-hero-content">
    <?php else : ?>
    <div class="dce-single-hero dce-hero-no-img">
        <div class="dce-single-hero-content">
    <?php endif; ?>
            <div class="dce-single-meta-bar">
                <?php if ($categories && ! is_wp_error($categories)) :
                    foreach ($categories as $cat) : ?>
                    <a href="<?php echo get_term_link($cat); ?>" class="dce-single-cat"><?php echo esc_html($cat->name); ?></a>
                <?php endforeach; endif; ?>
                <?php if ($price > 0) : ?>
                <span class="dce-single-price">💳 <?php echo '$' . number_format($price, 0, ',', '.') . ' ' . $currency; ?></span>
                <?php else : ?>
                <span class="dce-single-free">🎉 Gratuito</span>
                <?php endif; ?>
            </div>
            <h1 class="dce-single-title"><?php echo esc_html($event->post_title); ?></h1>
            <div class="dce-single-quick-info">
                <?php if ($start_date) : ?>
                <span>📅 <?php echo date_i18n('l j \d\e F \d\e Y', strtotime($start_date)); ?></span>
                <?php endif; ?>
                <?php if ($start_time) : ?>
                <span>🕐 <?php echo date_i18n('g:i a', strtotime($start_time)); ?></span>
                <?php endif; ?>
                <?php if ($is_virtual) : ?>
                <span>🌐 Evento Online</span>
                <?php elseif ($venue) : ?>
                <span>📍 <?php echo esc_html("$venue, $city"); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Capacidad -->
    <?php if ($max > 0) :
        $pct = round(($count / $max) * 100);
        $left = $max - $count;
    ?>
    <div class="dce-single-capacity">
        <div class="dce-single-cap-bar">
            <div class="dce-single-cap-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $pct >= 90 ? 'var(--dce-danger)' : 'var(--dce-success)'; ?>"></div>
        </div>
        <span class="dce-single-cap-text">
            <?php if ($left > 0) :
                echo sprintf( _n('%d cupo disponible', '%d cupos disponibles', $left, 'dc-events'), $left );
            else : ?>
                <?php _e('⚠️ Evento agotado', 'dc-events'); ?>
            <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

</div>
