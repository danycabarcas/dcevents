<?php
/**
 * Lógica de inscripciones a eventos
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Registration {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        add_action( 'wp_ajax_dcevents_register',        [ $this, 'process_registration' ] );
        add_action( 'wp_ajax_nopriv_dcevents_register', [ $this, 'process_registration' ] );
        add_action( 'wp_ajax_dcevents_cancel_registration', [ $this, 'cancel_registration' ] );
        add_action( 'wp_ajax_dcevents_checkin',         [ $this, 'check_in_attendee' ] );
        add_action( 'wp_ajax_dcevents_update_status',   [ $this, 'update_registration_status' ] );
    }

    // ─── Procesar inscripción ─────────────────────────────────────────────────
    public function process_registration() {
        // Verificar nonce
        if ( ! check_ajax_referer( 'dcevents_register_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Solicitud no válida. Recarga la página.', 'dc-events' ) ] );
        }

        $event_id = intval( $_POST['event_id'] ?? 0 );
        if ( ! $event_id || get_post_type( $event_id ) !== 'dc_event' ) {
            wp_send_json_error( [ 'message' => __( 'Evento no válido.', 'dc-events' ) ] );
        }

        // Verificar que el evento existe y acepta inscripciones
        $event = get_post( $event_id );
        if ( ! $event || 'publish' !== $event->post_status ) {
            wp_send_json_error( [ 'message' => __( 'Este evento no está disponible.', 'dc-events' ) ] );
        }

        $status      = get_post_meta( $event_id, '_dcevents_status', true );
        $reg_open    = get_post_meta( $event_id, '_dcevents_registration_open', true );
        $deadline    = get_post_meta( $event_id, '_dcevents_registration_deadline', true );

        if ( $status === 'cancelled' ) {
            wp_send_json_error( [ 'message' => __( 'Este evento ha sido cancelado.', 'dc-events' ) ] );
        }

        if ( $reg_open === '0' ) {
            wp_send_json_error( [ 'message' => __( 'Las inscripciones para este evento están cerradas.', 'dc-events' ) ] );
        }

        if ( $deadline && strtotime( $deadline ) < time() ) {
            wp_send_json_error( [ 'message' => __( 'La fecha límite de inscripción ha pasado.', 'dc-events' ) ] );
        }

        if ( ! DCEvents_Post_Types::has_availability( $event_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Lo sentimos, este evento está agotado.', 'dc-events' ) ] );
        }

        // Datos del formulario
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
        $email      = sanitize_email( $_POST['email'] ?? '' );
        $phone      = sanitize_text_field( $_POST['phone'] ?? '' );
        $id_number  = sanitize_text_field( $_POST['id_number'] ?? '' );

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'Por favor completa todos los campos requeridos.', 'dc-events' ) ] );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( [ 'message' => __( 'El email no es válido.', 'dc-events' ) ] );
        }

        // Verificar si ya está inscrito
        $existing = $this->get_registration_by_email( $event_id, $email );
        if ( $existing ) {
            wp_send_json_error( [ 'message' => __( 'Ya estás inscrito/a en este evento con ese email.', 'dc-events' ) ] );
        }

        // Campos personalizados del evento
        $custom_fields = get_post_meta( $event_id, '_dcevents_custom_fields', true ) ?: [];
        $custom_data   = [];
        foreach ( $custom_fields as $i => $field ) {
            $field_key   = 'custom_field_' . $i;
            $field_value = sanitize_text_field( $_POST[ $field_key ] ?? '' );
            if ( $field['required'] === '1' && empty( $field_value ) ) {
                wp_send_json_error( [
                    'message' => sprintf( __( 'El campo "%s" es requerido.', 'dc-events' ), $field['label'] )
                ] );
            }
            $custom_data[ $field['label'] ] = $field_value;
        }

        // Obtener o crear usuario WordPress
        $user_id = $this->get_or_create_user( $email, $first_name, $last_name );

        // Crear la inscripción
        $require_approval = get_post_meta( $event_id, '_dcevents_require_approval', true );
        $reg_status       = ( $require_approval === '1' ) ? 'pending' : 'confirmed';
        $price            = (float) get_post_meta( $event_id, '_dcevents_price', true );
        $is_paid_event    = $price > 0;

        if ( $is_paid_event && $reg_status === 'confirmed' ) {
            $reg_status = 'payment_pending'; // Si es de pago, queda pendiente de pago
        }

        $registration_code = $this->generate_registration_code( $event_id );

        $registration_id = wp_insert_post( [
            'post_type'   => 'dc_registration',
            'post_title'  => $first_name . ' ' . $last_name . ' — ' . $event->post_title,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ] );

        if ( is_wp_error( $registration_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Error al procesar la inscripción. Intenta de nuevo.', 'dc-events' ) ] );
        }

        // Guardar metadatos de la inscripción
        $meta = [
            '_dcevents_event_id'          => $event_id,
            '_dcevents_user_id'           => $user_id,
            '_dcevents_first_name'        => $first_name,
            '_dcevents_last_name'         => $last_name,
            '_dcevents_email'             => $email,
            '_dcevents_phone'             => $phone,
            '_dcevents_id_number'         => $id_number,
            '_dcevents_reg_status'        => $reg_status,
            '_dcevents_registration_date' => current_time( 'mysql' ),
            '_dcevents_code'              => $registration_code,
            '_dcevents_custom_data'       => $custom_data,
            '_dcevents_amount'            => $price,
            '_dcevents_payment_status'    => $is_paid_event ? 'pending' : 'not_required',
            '_dcevents_checked_in'        => '0',
        ];

        foreach ( $meta as $key => $value ) {
            update_post_meta( $registration_id, $key, $value );
        }

        // Incrementar contador
        DCEvents_Post_Types::increment_registration_count( $event_id );

        // Enviar emails
        $email_handler = DCEvents_Email::instance();
        $email_handler->send_confirmation( $registration_id );
        $email_handler->send_admin_notification( $registration_id );

        // Respuesta
        $message = $reg_status === 'confirmed'
            ? __( '¡Inscripción exitosa! Revisa tu email para más información.', 'dc-events' )
            : __( 'Tu inscripción ha sido recibida y está pendiente de confirmación.', 'dc-events' );

        wp_send_json_success( [
            'message'           => $message,
            'registration_id'   => $registration_id,
            'registration_code' => $registration_code,
            'status'            => $reg_status,
            'is_paid'           => $is_paid_event,
        ] );
    }

    // ─── Cancelar inscripción ─────────────────────────────────────────────────
    public function cancel_registration() {
        if ( ! check_ajax_referer( 'dcevents_cancel_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Solicitud no válida.', 'dc-events' ) ] );
        }

        $registration_id = intval( $_POST['registration_id'] ?? 0 );
        $registration    = get_post( $registration_id );

        if ( ! $registration || 'dc_registration' !== $registration->post_type ) {
            wp_send_json_error( [ 'message' => __( 'Inscripción no encontrada.', 'dc-events' ) ] );
        }

        // Solo el propio usuario o un validador puede cancelar
        $user_id    = get_post_meta( $registration_id, '_dcevents_user_id', true );
        $current_id = get_current_user_id();

        if ( $current_id != $user_id && ! current_user_can( 'dcevents_edit_registration_status' ) ) {
            wp_send_json_error( [ 'message' => __( 'No tienes permiso para cancelar esta inscripción.', 'dc-events' ) ] );
        }

        $event_id = get_post_meta( $registration_id, '_dcevents_event_id', true );
        update_post_meta( $registration_id, '_dcevents_reg_status', 'cancelled' );
        DCEvents_Post_Types::decrement_registration_count( $event_id );

        wp_send_json_success( [ 'message' => __( 'Inscripción cancelada correctamente.', 'dc-events' ) ] );
    }

    // ─── Check-in ─────────────────────────────────────────────────────────────
    public function check_in_attendee() {
        if ( ! check_ajax_referer( 'dcevents_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'No autorizado.', 'dc-events' ) ] );
        }

        if ( ! current_user_can( 'dcevents_check_in_attendee' ) ) {
            wp_send_json_error( [ 'message' => __( 'Sin permiso.', 'dc-events' ) ] );
        }

        $registration_id = intval( $_POST['registration_id'] ?? 0 );
        update_post_meta( $registration_id, '_dcevents_checked_in', '1' );
        update_post_meta( $registration_id, '_dcevents_checkin_time', current_time( 'mysql' ) );

        wp_send_json_success( [ 'message' => __( 'Check-in realizado.', 'dc-events' ) ] );
    }

    // ─── Actualizar estado ────────────────────────────────────────────────────
    public function update_registration_status() {
        if ( ! check_ajax_referer( 'dcevents_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'No autorizado.', 'dc-events' ) ] );
        }

        if ( ! current_user_can( 'dcevents_edit_registration_status' ) ) {
            wp_send_json_error( [ 'message' => __( 'Sin permiso.', 'dc-events' ) ] );
        }

        $registration_id = intval( $_POST['registration_id'] ?? 0 );
        $new_status      = sanitize_key( $_POST['status'] ?? '' );
        $allowed         = [ 'pending', 'confirmed', 'cancelled', 'payment_pending', 'paid', 'attended' ];

        if ( ! in_array( $new_status, $allowed ) ) {
            wp_send_json_error( [ 'message' => __( 'Estado no válido.', 'dc-events' ) ] );
        }

        update_post_meta( $registration_id, '_dcevents_reg_status', $new_status );

        if ( $new_status === 'confirmed' ) {
            DCEvents_Email::instance()->send_confirmation( $registration_id );
        }

        wp_send_json_success( [
            'message' => __( 'Estado actualizado correctamente.', 'dc-events' ),
            'status'  => $new_status,
        ] );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function get_or_create_user( $email, $first_name, $last_name ) {
        $user = get_user_by( 'email', $email );

        if ( $user ) {
            return $user->ID;
        }

        // Crear usuario nuevo con rol event_attendee
        $username = sanitize_user( $first_name . '.' . $last_name . '.' . substr( md5( $email ), 0, 4 ) );
        $password = wp_generate_password( 12, true );

        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            // Intentar con email como username
            $user_id = wp_create_user( $email, $password, $email );
        }

        if ( ! is_wp_error( $user_id ) ) {
            $user = new WP_User( $user_id );
            $user->set_role( 'event_attendee' );

            wp_update_user( [
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $first_name . ' ' . $last_name,
            ] );

            // Enviar email de bienvenida con credenciales
            DCEvents_Email::instance()->send_welcome( $user_id, $password );
        }

        return is_wp_error( $user_id ) ? 0 : $user_id;
    }

    private function get_registration_by_email( $event_id, $email ) {
        $results = get_posts( [
            'post_type'      => 'dc_registration',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => [
                'relation' => 'AND',
                [ 'key' => '_dcevents_event_id', 'value' => $event_id ],
                [ 'key' => '_dcevents_email',    'value' => $email ],
                [
                    'key'     => '_dcevents_reg_status',
                    'value'   => 'cancelled',
                    'compare' => '!=',
                ],
            ],
        ] );

        return ! empty( $results ) ? $results[0] : null;
    }

    private function generate_registration_code( $event_id ) {
        return strtoupper( 'EVT' . $event_id . '-' . substr( md5( uniqid( '', true ) ), 0, 6 ) );
    }

    // ─── Métodos públicos para shortcodes ─────────────────────────────────────

    public function get_user_registrations( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        return get_posts( [
            'post_type'      => 'dc_registration',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [ 'key' => '_dcevents_user_id', 'value' => $user_id ],
            ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
    }

    public function get_event_registrations( $event_id, $status = null ) {
        $meta_query = [
            [ 'key' => '_dcevents_event_id', 'value' => $event_id ],
        ];

        if ( $status ) {
            $meta_query[] = [ 'key' => '_dcevents_reg_status', 'value' => $status ];
        }

        return get_posts( [
            'post_type'      => 'dc_registration',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => $meta_query,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );
    }

    public static function get_status_label( $status ) {
        $labels = [
            'pending'         => [ 'label' => __( 'Pendiente', 'dc-events' ),           'color' => '#f0b849' ],
            'confirmed'       => [ 'label' => __( 'Confirmado', 'dc-events' ),           'color' => '#46b450' ],
            'payment_pending' => [ 'label' => __( 'Pago Pendiente', 'dc-events' ),      'color' => '#cc8800' ],
            'paid'            => [ 'label' => __( 'Pagado', 'dc-events' ),               'color' => '#0073aa' ],
            'cancelled'       => [ 'label' => __( 'Cancelado', 'dc-events' ),           'color' => '#dc3232' ],
            'attended'        => [ 'label' => __( 'Asistió', 'dc-events' ),              'color' => '#00a32a' ],
        ];

        return $labels[ $status ] ?? [ 'label' => ucfirst( $status ), 'color' => '#888' ];
    }
}
