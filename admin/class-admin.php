<?php
/**
 * Panel de administración
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function init() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'manage_dc_registration_posts_columns',       [ $this, 'registration_columns' ] );
        add_action( 'manage_dc_registration_posts_custom_column', [ $this, 'registration_column_content' ], 10, 2 );
        add_filter( 'manage_dc_event_posts_columns',              [ $this, 'event_columns' ] );
        add_action( 'manage_dc_event_posts_custom_column',        [ $this, 'event_column_content' ], 10, 2 );
        add_filter( 'post_row_actions',      [ $this, 'event_row_actions' ], 10, 2 );

        // Inicializar meta boxes
        DCEvents_Meta_Boxes::instance();
    }

    // ─── Menús ────────────────────────────────────────────────────────────────
    public function register_menus() {
        add_menu_page(
            __( 'DC Eventos', 'dc-events' ),
            __( 'DC Eventos', 'dc-events' ),
            'read',
            'dc-events',
            [ $this, 'render_dashboard' ],
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            'dc-events',
            __( 'Dashboard', 'dc-events' ),
            __( 'Dashboard', 'dc-events' ),
            'read',
            'dc-events',
            [ $this, 'render_dashboard' ]
        );

        add_submenu_page(
            'dc-events',
            __( 'Todos los Eventos', 'dc-events' ),
            __( 'Eventos', 'dc-events' ),
            'dcevents_create_event',
            'edit.php?post_type=dc_event'
        );

        add_submenu_page(
            'dc-events',
            __( 'Nuevo Evento', 'dc-events' ),
            __( '+ Nuevo Evento', 'dc-events' ),
            'dcevents_create_event',
            'post-new.php?post_type=dc_event'
        );

        add_submenu_page(
            'dc-events',
            __( 'Inscripciones', 'dc-events' ),
            __( 'Inscripciones', 'dc-events' ),
            'dcevents_view_all_registrations',
            'dc-events-registrations',
            [ $this, 'render_registrations' ]
        );

        add_submenu_page(
            'dc-events',
            __( 'Categorías', 'dc-events' ),
            __( 'Categorías', 'dc-events' ),
            'dcevents_create_event',
            'edit-tags.php?taxonomy=event_category&post_type=dc_event'
        );

        add_submenu_page(
            'dc-events',
            __( 'Ajustes Visuales', 'dc-events' ),
            __( '🎨 Ajustes Visuales', 'dc-events' ),
            'dcevents_manage_settings',
            'dc-events-settings',
            [ $this, 'render_settings' ]
        );
    }

    // ─── Assets ───────────────────────────────────────────────────────────────
    public function enqueue_assets( $hook ) {
        $is_dce_page = strpos( $hook, 'dc-events' ) !== false
            || ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'dc_event' )
            || ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'dc_registration' );

        if ( ! $is_dce_page ) return;

        wp_enqueue_style( 'dcevents-admin', DCEVENTS_URL . 'admin/assets/admin.css', [], DCEVENTS_VERSION );
        wp_enqueue_script( 'dcevents-admin', DCEVENTS_URL . 'admin/assets/admin.js', [ 'jquery', 'wp-color-picker' ], DCEVENTS_VERSION, true );
        wp_enqueue_style( 'wp-color-picker' );

        wp_localize_script( 'dcevents-admin', 'DCEventsAdmin', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dcevents_admin_nonce' ),
            'strings'  => [
                'confirm_cancel'  => __( '¿Confirmas cancelar esta inscripción?', 'dc-events' ),
                'confirm_checkin' => __( '¿Confirmas el check-in de este asistente?', 'dc-events' ),
                'saving'          => __( 'Guardando...', 'dc-events' ),
                'saved'           => __( 'Guardado ✓', 'dc-events' ),
            ],
        ] );
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────
    public function render_dashboard() {
        include DCEVENTS_PATH . 'admin/views/dashboard.php';
    }

    // ─── Inscripciones ────────────────────────────────────────────────────────
    public function render_registrations() {
        // ¿Ver detalle individual?
        if ( isset( $_GET['registration_id'] ) ) {
            include DCEVENTS_PATH . 'admin/views/registration-detail.php';
        } else {
            include DCEVENTS_PATH . 'admin/views/registrations.php';
        }
    }

    // ─── Ajustes ──────────────────────────────────────────────────────────────
    public function render_settings() {
        include DCEVENTS_PATH . 'admin/views/settings.php';
    }

    // ─── Columnas de inscripciones ────────────────────────────────────────────
    public function registration_columns( $columns ) {
        return [
            'cb'          => $columns['cb'],
            'title'       => __( 'Inscrito', 'dc-events' ),
            'event'       => __( 'Evento', 'dc-events' ),
            'status'      => __( 'Estado', 'dc-events' ),
            'code'        => __( 'Código', 'dc-events' ),
            'checked_in'  => __( 'Check-in', 'dc-events' ),
            'date'        => $columns['date'],
        ];
    }

    public function registration_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'event':
                $event_id = get_post_meta( $post_id, '_dcevents_event_id', true );
                $event    = get_post( $event_id );
                if ( $event ) {
                    echo '<a href="' . get_edit_post_link( $event_id ) . '">' . esc_html( $event->post_title ) . '</a>';
                }
                break;
            case 'status':
                $status = get_post_meta( $post_id, '_dcevents_reg_status', true );
                $info   = DCEvents_Registration::get_status_label( $status );
                echo '<span style="background:' . esc_attr( $info['color'] ) . ';color:#fff;padding:3px 8px;border-radius:12px;font-size:12px">' . esc_html( $info['label'] ) . '</span>';
                break;
            case 'code':
                $code = get_post_meta( $post_id, '_dcevents_code', true );
                echo '<code>' . esc_html( $code ) . '</code>';
                break;
            case 'checked_in':
                $checked = get_post_meta( $post_id, '_dcevents_checked_in', true );
                echo $checked === '1' ? '<span style="color:#46b450;font-weight:700">✅</span>' : '<span style="color:#ccc">—</span>';
                break;
        }
    }

    // ─── Columnas de eventos ──────────────────────────────────────────────────
    public function event_columns( $columns ) {
        $new = [];
        foreach ( $columns as $key => $label ) {
            $new[ $key ] = $label;
            if ( $key === 'title' ) {
                $new['event_date']     = __( 'Fecha', 'dc-events' );
                $new['event_status']   = __( 'Estado', 'dc-events' );
                $new['event_capacity'] = __( 'Inscritos', 'dc-events' );
                $new['event_price']    = __( 'Precio', 'dc-events' );
            }
        }
        return $new;
    }

    public function event_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'event_date':
                $date = get_post_meta( $post_id, '_dcevents_start_date', true );
                echo $date ? date_i18n( 'd M Y', strtotime( $date ) ) : '—';
                break;
            case 'event_status':
                $status = get_post_meta( $post_id, '_dcevents_status', true ) ?: 'active';
                $map    = [
                    'active'    => [ '✅ Activo',    '#46b450' ],
                    'full'      => [ '🔴 Agotado',   '#dc3232' ],
                    'cancelled' => [ '❌ Cancelado', '#888' ],
                    'draft'     => [ '📝 Borrador',  '#ccc' ],
                ];
                $s = $map[ $status ] ?? [ ucfirst( $status ), '#888' ];
                echo '<span style="color:' . $s[1] . ';font-weight:600">' . $s[0] . '</span>';
                break;
            case 'event_capacity':
                $count = (int) get_post_meta( $post_id, '_dcevents_registered_count', true );
                $max   = (int) get_post_meta( $post_id, '_dcevents_max_capacity', true );
                $url   = admin_url( 'admin.php?page=dc-events-registrations&event_id=' . $post_id );
                echo '<a href="' . esc_url( $url ) . '">' . $count . ( $max > 0 ? ' / ' . $max : '' ) . '</a>';
                break;
            case 'event_price':
                $price    = (float) get_post_meta( $post_id, '_dcevents_price', true );
                $currency = get_post_meta( $post_id, '_dcevents_currency', true ) ?: 'COP';
                echo $price > 0 ? number_format( $price, 0, ',', '.' ) . ' ' . $currency : '<em>' . __( 'Gratuito', 'dc-events' ) . '</em>';
                break;
        }
    }

    public function event_row_actions( $actions, $post ) {
        if ( 'dc_event' !== $post->post_type ) return $actions;
        $url = admin_url( 'admin.php?page=dc-events-registrations&event_id=' . $post->ID );
        $count = (int) get_post_meta( $post->ID, '_dcevents_registered_count', true );
        $actions['view_registrations'] = '<a href="' . esc_url( $url ) . '">' .
            sprintf( __( 'Ver inscritos (%d)', 'dc-events' ), $count ) . '</a>';
        return $actions;
    }
}
