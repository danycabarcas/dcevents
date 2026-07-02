<?php
/**
 * Sistema de emails automáticos
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Email {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'wp_mail_content_type', [ $this, 'set_html_content_type' ] );
    }

    public function set_html_content_type() {
        return 'text/html';
    }

    // ─── Email de confirmación al inscrito ────────────────────────────────────
    public function send_confirmation( $registration_id ) {
        $email    = get_post_meta( $registration_id, '_dcevents_email', true );
        $name     = get_post_meta( $registration_id, '_dcevents_first_name', true );
        $event_id = get_post_meta( $registration_id, '_dcevents_event_id', true );
        $code     = get_post_meta( $registration_id, '_dcevents_code', true );
        $status   = get_post_meta( $registration_id, '_dcevents_reg_status', true );

        if ( ! $email ) return;

        $event      = get_post( $event_id );
        $event_name = $event ? $event->post_title : '';
        $start_date = get_post_meta( $event_id, '_dcevents_start_date', true );
        $start_time = get_post_meta( $event_id, '_dcevents_start_time', true );
        $venue      = get_post_meta( $event_id, '_dcevents_venue', true );
        $city       = get_post_meta( $event_id, '_dcevents_city', true );
        $is_virtual = get_post_meta( $event_id, '_dcevents_is_virtual', true );
        $price      = (float) get_post_meta( $event_id, '_dcevents_price', true );
        $currency   = get_post_meta( $event_id, '_dcevents_currency', true ) ?: 'COP';

        $location = $is_virtual ? __( 'Evento Virtual / Online', 'dc-events' ) : ( $venue . ', ' . $city );
        $date_str = $start_date ? date_i18n( 'l j \d\e F \d\e Y', strtotime( $start_date ) ) : '';
        $time_str = $start_time ? date_i18n( 'g:i a', strtotime( $start_time ) ) : '';

        $status_text = '';
        if ( $status === 'confirmed' ) {
            $status_text = '<div style="background:#d4edda;color:#155724;padding:12px;border-radius:6px;margin:16px 0">
                ✅ <strong>' . __( 'Tu inscripción está CONFIRMADA', 'dc-events' ) . '</strong>
            </div>';
        } elseif ( $status === 'payment_pending' ) {
            $status_text = '<div style="background:#fff3cd;color:#856404;padding:12px;border-radius:6px;margin:16px 0">
                💳 <strong>' . __( 'Tu inscripción está pendiente de PAGO', 'dc-events' ) . '</strong><br>
                ' . sprintf( __( 'Monto a pagar: %s %s', 'dc-events' ), number_format( $price, 0, ',', '.' ), $currency ) . '
            </div>';
        } elseif ( $status === 'pending' ) {
            $status_text = '<div style="background:#cce5ff;color:#004085;padding:12px;border-radius:6px;margin:16px 0">
                ⏳ <strong>' . __( 'Tu inscripción está PENDIENTE de aprobación', 'dc-events' ) . '</strong>
            </div>';
        }

        $site_name  = get_bloginfo( 'name' );
        $site_url   = home_url();
        $settings   = get_option( 'dcevents_settings', [] );
        $primary_color = $settings['primary_color'] ?? '#fae100';
        $text_color    = $settings['text_color']    ?? '#121212';

        $my_reg_page = get_option( 'dcevents_my_registrations_page' );
        $my_reg_url  = $my_reg_page ? get_permalink( $my_reg_page ) : home_url();

        $subject = sprintf( __( '[%s] Inscripción: %s', 'dc-events' ), $site_name, $event_name );

        $template = DCEVENTS_PATH . 'templates/emails/confirmation.html';
        if ( file_exists( $template ) ) {
            $body = file_get_contents( $template );
            $body = str_replace( [
                '{{name}}', '{{event_name}}', '{{date}}', '{{time}}', '{{location}}',
                '{{code}}', '{{status_block}}', '{{site_name}}', '{{site_url}}',
                '{{primary_color}}', '{{text_color}}', '{{my_registrations_url}}',
            ], [
                $name, $event_name, $date_str, $time_str, $location,
                $code, $status_text, $site_name, $site_url,
                $primary_color, $text_color, $my_reg_url,
            ], $body );
        } else {
            $body = $this->fallback_confirmation_email( $name, $event_name, $date_str, $time_str, $location, $code, $status_text, $site_name );
        }

        $headers = [
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
            'Content-Type: text/html; charset=UTF-8',
        ];

        wp_mail( $email, $subject, $body, $headers );
    }

    // ─── Email de bienvenida con credenciales ─────────────────────────────────
    public function send_welcome( $user_id, $password ) {
        $user     = get_user_by( 'id', $user_id );
        if ( ! $user ) return;

        $site_name  = get_bloginfo( 'name' );
        $login_url  = wp_login_url();
        $subject    = sprintf( __( 'Bienvenido/a a %s', 'dc-events' ), $site_name );

        $body = $this->build_email_wrapper(
            sprintf( __( 'Hola %s', 'dc-events' ), $user->display_name ),
            sprintf( __( 'Se ha creado una cuenta en <strong>%s</strong> para gestionar tus inscripciones a eventos.', 'dc-events' ), $site_name ) . '
            <table style="width:100%;margin:16px 0;border-collapse:collapse">
                <tr><td style="padding:8px;background:#f5f5f5;border-radius:4px 0 0 4px;color:#666">' . __( 'Usuario', 'dc-events' ) . '</td>
                    <td style="padding:8px;font-weight:700">' . esc_html( $user->user_login ) . '</td></tr>
                <tr><td style="padding:8px;background:#f5f5f5;border-radius:4px 0 0 4px;color:#666">' . __( 'Email', 'dc-events' ) . '</td>
                    <td style="padding:8px;font-weight:700">' . esc_html( $user->user_email ) . '</td></tr>
                <tr><td style="padding:8px;background:#f5f5f5;border-radius:4px 0 0 4px;color:#666">' . __( 'Contraseña', 'dc-events' ) . '</td>
                    <td style="padding:8px;font-weight:700">' . esc_html( $password ) . '</td></tr>
            </table>
            <p>' . __( 'Te recomendamos cambiar tu contraseña después de iniciar sesión.', 'dc-events' ) . '</p>
            <a href="' . esc_url( $login_url ) . '" style="display:inline-block;background:#fae100;color:#121212;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:700;margin-top:8px">' .
                __( 'Iniciar Sesión →', 'dc-events' ) . '</a>',
            $site_name
        );

        $headers = [
            'From: ' . $site_name . ' <' . get_option( 'admin_email' ) . '>',
            'Content-Type: text/html; charset=UTF-8',
        ];

        wp_mail( $user->user_email, $subject, $body, $headers );
    }

    // ─── Email de notificación al administrador ───────────────────────────────
    public function send_admin_notification( $registration_id ) {
        $event_id    = get_post_meta( $registration_id, '_dcevents_event_id', true );
        $event       = get_post( $event_id );
        $first_name  = get_post_meta( $registration_id, '_dcevents_first_name', true );
        $last_name   = get_post_meta( $registration_id, '_dcevents_last_name', true );
        $email       = get_post_meta( $registration_id, '_dcevents_email', true );
        $phone       = get_post_meta( $registration_id, '_dcevents_phone', true );
        $code        = get_post_meta( $registration_id, '_dcevents_code', true );
        $count       = (int) get_post_meta( $event_id, '_dcevents_registered_count', true );
        $max         = (int) get_post_meta( $event_id, '_dcevents_max_capacity', true );

        $site_name   = get_bloginfo( 'name' );
        $admin_email = get_option( 'admin_email' );
        $detail_url  = admin_url( 'admin.php?page=dc-events-registrations&registration_id=' . $registration_id );

        $subject = sprintf( __( '[%s] Nueva inscripción: %s', 'dc-events' ), $site_name, $event ? $event->post_title : '' );

        $body = $this->build_email_wrapper(
            __( '📢 Nueva Inscripción Recibida', 'dc-events' ),
            '<table style="width:100%;margin:16px 0;border-collapse:collapse">
                <tr style="background:#f9f9f9"><td style="padding:10px;font-weight:700;color:#666;width:40%">' . __( 'Evento', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $event ? $event->post_title : '' ) . '</td></tr>
                <tr><td style="padding:10px;font-weight:700;color:#666">' . __( 'Nombre', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $first_name . ' ' . $last_name ) . '</td></tr>
                <tr style="background:#f9f9f9"><td style="padding:10px;font-weight:700;color:#666">' . __( 'Email', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $email ) . '</td></tr>
                <tr><td style="padding:10px;font-weight:700;color:#666">' . __( 'Teléfono', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $phone ) . '</td></tr>
                <tr style="background:#f9f9f9"><td style="padding:10px;font-weight:700;color:#666">' . __( 'Código', 'dc-events' ) . '</td>
                    <td style="padding:10px;font-family:monospace;font-weight:700">' . esc_html( $code ) . '</td></tr>
                <tr><td style="padding:10px;font-weight:700;color:#666">' . __( 'Inscritos', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . $count . ( $max > 0 ? ' / ' . $max : '' ) . '</td></tr>
            </table>
            <a href="' . esc_url( $detail_url ) . '" style="display:inline-block;background:#0073aa;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:700">' .
                __( 'Ver Inscripción en Admin →', 'dc-events' ) . '</a>',
            $site_name
        );

        $headers = [
            'From: ' . $site_name . ' <' . $admin_email . '>',
            'Content-Type: text/html; charset=UTF-8',
        ];

        wp_mail( $admin_email, $subject, $body, $headers );
    }

    // ─── Constructor de email base ────────────────────────────────────────────
    private function build_email_wrapper( $title, $content, $site_name ) {
        $settings      = get_option( 'dcevents_settings', [] );
        $primary_color = $settings['primary_color'] ?? '#fae100';
        $text_color    = $settings['text_color']    ?? '#121212';

        return '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . esc_html( $title ) . '</title></head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f0;padding:30px 0">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)">
  <tr>
    <td style="background:' . esc_attr( $primary_color ) . ';padding:30px;text-align:center">
      <h1 style="margin:0;color:' . esc_attr( $text_color ) . ';font-size:24px;font-weight:700">' . esc_html( $site_name ) . '</h1>
    </td>
  </tr>
  <tr>
    <td style="padding:30px">
      <h2 style="margin:0 0 20px;color:#121212;font-size:20px">' . $title . '</h2>
      ' . $content . '
    </td>
  </tr>
  <tr>
    <td style="background:#f9f9f9;padding:20px;text-align:center;color:#888;font-size:13px;border-top:1px solid #eee">
      <p style="margin:0">' . sprintf( __( '© %s %s. Todos los derechos reservados.', 'dc-events' ), date( 'Y' ), esc_html( $site_name ) ) . '</p>
      <p style="margin:8px 0 0"><a href="' . home_url() . '" style="color:#888">' . home_url() . '</a></p>
    </td>
  </tr>
</table>
</td></tr>
</table>
</body></html>';
    }

    private function fallback_confirmation_email( $name, $event_name, $date, $time, $location, $code, $status_text, $site_name ) {
        return $this->build_email_wrapper(
            sprintf( __( 'Inscripción: %s', 'dc-events' ), $event_name ),
            sprintf( __( 'Hola %s,', 'dc-events' ), $name ) . '<br><br>' .
            $status_text .
            '<table style="width:100%;margin:16px 0;border-collapse:collapse">
                <tr style="background:#f9f9f9"><td style="padding:10px;font-weight:700;color:#666;width:40%">' . __( 'Evento', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $event_name ) . '</td></tr>
                <tr><td style="padding:10px;font-weight:700;color:#666">' . __( 'Fecha', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $date ) . ( $time ? ' — ' . esc_html( $time ) : '' ) . '</td></tr>
                <tr style="background:#f9f9f9"><td style="padding:10px;font-weight:700;color:#666">' . __( 'Lugar', 'dc-events' ) . '</td>
                    <td style="padding:10px">' . esc_html( $location ) . '</td></tr>
                <tr><td style="padding:10px;font-weight:700;color:#666">' . __( 'Código', 'dc-events' ) . '</td>
                    <td style="padding:10px;font-family:monospace;font-size:18px;font-weight:700;color:#0073aa">' . esc_html( $code ) . '</td></tr>
            </table>
            <p style="color:#666;font-size:14px">' . __( 'Guarda este código, lo necesitarás para el check-in en el evento.', 'dc-events' ) . '</p>',
            $site_name
        );
    }
}
