<?php
/**
 * Controlador público
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Public {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_head',            [ $this, 'output_css_variables' ] );
        add_filter( 'the_content',        [ $this, 'single_event_content' ] );
        add_action( 'admin_post_dcevents_export',        [ $this, 'export_csv' ] );
        add_action( 'admin_post_nopriv_dcevents_export', [ $this, 'export_csv' ] );
    }

    public function enqueue_assets() {
        // Solo cargar en páginas que tengan shortcodes de DC Events
        if ( ! $this->page_has_dcevents() ) return;

        wp_enqueue_style(
            'dcevents-public',
            DCEVENTS_URL . 'public/assets/public.css',
            [],
            DCEVENTS_VERSION
        );

        wp_enqueue_script(
            'dcevents-public',
            DCEVENTS_URL . 'public/assets/public.js',
            [ 'jquery' ],
            DCEVENTS_VERSION,
            true
        );

        wp_localize_script( 'dcevents-public', 'DCEvents', [
            'ajax_url'  => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'dcevents_register_nonce' ),
            'cancel_nonce' => wp_create_nonce( 'dcevents_cancel_nonce' ),
            'is_logged_in' => is_user_logged_in() ? '1' : '0',
            'login_url' => wp_login_url( get_permalink() ),
            'strings'   => [
                'submitting'    => __( 'Procesando...', 'dc-events' ),
                'confirm_cancel'=> __( '¿Deseas cancelar tu inscripción?', 'dc-events' ),
                'error_generic' => __( 'Ocurrió un error. Intenta de nuevo.', 'dc-events' ),
            ],
        ] );
    }

    private function page_has_dcevents() {
        global $post;
        if ( ! $post ) return false;

        $shortcodes = [ 'dc_events', 'dc_event', 'dc_registration_form', 'dc_my_registrations' ];
        foreach ( $shortcodes as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) return true;
        }

        // Si es un evento individual
        if ( is_singular( 'dc_event' ) ) return true;

        return false;
    }

    public function output_css_variables() {
        if ( ! $this->page_has_dcevents() ) return;
        echo '<style id="dcevents-css-vars">' . DCEvents_Settings::get_css_variables() . '</style>' . "\n";
    }

    // Auto-insertar shortcode en páginas de evento único
    public function single_event_content( $content ) {
        if ( ! is_singular( 'dc_event' ) || ! in_the_loop() ) return $content;
        global $post;

        // Si ya tiene el shortcode, no duplicar
        if ( has_shortcode( $content, 'dc_registration_form' ) ) return $content;

        $form = do_shortcode( '[dc_registration_form event_id="' . $post->ID . '"]' );
        return $content . $form;
    }

    // Exportar CSV de inscripciones
    public function export_csv() {
        if ( ! isset( $_GET['_nonce'] ) || ! wp_verify_nonce( $_GET['_nonce'], 'dcevents_export' ) ) {
            wp_die( __( 'No autorizado', 'dc-events' ) );
        }
        if ( ! current_user_can( 'dcevents_export_registrations' ) ) {
            wp_die( __( 'Sin permisos', 'dc-events' ) );
        }

        $event_id = intval( $_GET['event_id'] ?? 0 );
        $meta_q   = $event_id ? [ [ 'key' => '_dcevents_event_id', 'value' => $event_id ] ] : [];

        $regs = get_posts( [
            'post_type'      => 'dc_registration',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => $meta_q,
            'orderby'        => 'date',
            'order'          => 'ASC',
        ] );

        $filename = 'inscripciones-' . ( $event_id ? 'evento-' . $event_id . '-' : '' ) . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );

        $out = fopen( 'php://output', 'w' );
        fprintf( $out, chr(0xEF).chr(0xBB).chr(0xBF) ); // BOM para UTF-8

        fputcsv( $out, [
            'Código', 'Nombre', 'Apellido', 'Email', 'Teléfono', 'Documento',
            'Evento', 'Estado', 'Fecha Inscripción', 'Check-in', 'Monto',
        ] );

        foreach ( $regs as $reg ) {
            $event_post = get_post( get_post_meta( $reg->ID, '_dcevents_event_id', true ) );
            fputcsv( $out, [
                get_post_meta( $reg->ID, '_dcevents_code', true ),
                get_post_meta( $reg->ID, '_dcevents_first_name', true ),
                get_post_meta( $reg->ID, '_dcevents_last_name', true ),
                get_post_meta( $reg->ID, '_dcevents_email', true ),
                get_post_meta( $reg->ID, '_dcevents_phone', true ),
                get_post_meta( $reg->ID, '_dcevents_id_number', true ),
                $event_post ? $event_post->post_title : '',
                get_post_meta( $reg->ID, '_dcevents_reg_status', true ),
                get_post_meta( $reg->ID, '_dcevents_registration_date', true ),
                get_post_meta( $reg->ID, '_dcevents_checked_in', true ) === '1' ? 'Sí' : 'No',
                get_post_meta( $reg->ID, '_dcevents_amount', true ),
            ] );
        }

        fclose( $out );
        exit;
    }
}
