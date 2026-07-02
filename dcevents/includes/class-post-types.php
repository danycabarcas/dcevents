<?php
/**
 * Custom Post Types: dc_event y dc_registration
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Post_Types {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register() {
        $this->register_event_cpt();
        $this->register_registration_cpt();
        $this->register_taxonomies();
    }

    // ─── CPT: Evento ─────────────────────────────────────────────────────────
    private function register_event_cpt() {
        $labels = [
            'name'               => __( 'Eventos', 'dc-events' ),
            'singular_name'      => __( 'Evento', 'dc-events' ),
            'menu_name'          => __( 'DC Eventos', 'dc-events' ),
            'add_new'            => __( 'Nuevo Evento', 'dc-events' ),
            'add_new_item'       => __( 'Agregar Nuevo Evento', 'dc-events' ),
            'edit_item'          => __( 'Editar Evento', 'dc-events' ),
            'new_item'           => __( 'Nuevo Evento', 'dc-events' ),
            'view_item'          => __( 'Ver Evento', 'dc-events' ),
            'search_items'       => __( 'Buscar Eventos', 'dc-events' ),
            'not_found'          => __( 'No se encontraron eventos', 'dc-events' ),
            'not_found_in_trash' => __( 'No hay eventos en la papelera', 'dc-events' ),
            'all_items'          => __( 'Todos los Eventos', 'dc-events' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => 'dc-events',
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => [ 'slug' => 'eventos' ],
            'capability_type'     => 'post',
            'capabilities'        => [
                'create_posts'       => 'dcevents_create_event',
                'edit_posts'         => 'dcevents_edit_event',
                'edit_others_posts'  => 'dcevents_edit_event',
                'publish_posts'      => 'dcevents_publish_event',
                'read_private_posts' => 'dcevents_edit_event',
                'delete_posts'       => 'dcevents_delete_event',
            ],
            'map_meta_cap'        => true,
            'has_archive'         => 'eventos',
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
            'menu_icon'           => 'dashicons-calendar-alt',
        ];

        register_post_type( 'dc_event', $args );
    }

    // ─── CPT: Inscripción ─────────────────────────────────────────────────────
    private function register_registration_cpt() {
        $labels = [
            'name'               => __( 'Inscripciones', 'dc-events' ),
            'singular_name'      => __( 'Inscripción', 'dc-events' ),
            'menu_name'          => __( 'Inscripciones', 'dc-events' ),
            'add_new'            => __( 'Nueva Inscripción', 'dc-events' ),
            'add_new_item'       => __( 'Agregar Inscripción', 'dc-events' ),
            'edit_item'          => __( 'Ver Inscripción', 'dc-events' ),
            'all_items'          => __( 'Todas las Inscripciones', 'dc-events' ),
            'not_found'          => __( 'No hay inscripciones', 'dc-events' ),
            'not_found_in_trash' => __( 'No hay inscripciones en la papelera', 'dc-events' ),
        ];

        $args = [
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => 'dc-events',
            'show_in_rest'       => false,
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'capabilities'       => [
                'create_posts'       => 'dcevents_create_registration',
                'edit_posts'         => 'dcevents_view_all_registrations',
                'edit_others_posts'  => 'dcevents_view_all_registrations',
                'publish_posts'      => 'dcevents_edit_registration_status',
                'read_private_posts' => 'dcevents_view_all_registrations',
                'delete_posts'       => 'administrator',
            ],
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => [ 'title', 'custom-fields' ],
            'menu_icon'          => 'dashicons-groups',
        ];

        register_post_type( 'dc_registration', $args );
    }

    // ─── Taxonomías ───────────────────────────────────────────────────────────
    private function register_taxonomies() {

        // Categoría de evento (Tour, Conferencia, Retiro, Concierto, etc.)
        $labels = [
            'name'              => __( 'Categorías de Evento', 'dc-events' ),
            'singular_name'     => __( 'Categoría de Evento', 'dc-events' ),
            'search_items'      => __( 'Buscar categorías', 'dc-events' ),
            'all_items'         => __( 'Todas las categorías', 'dc-events' ),
            'edit_item'         => __( 'Editar categoría', 'dc-events' ),
            'update_item'       => __( 'Actualizar categoría', 'dc-events' ),
            'add_new_item'      => __( 'Agregar categoría', 'dc-events' ),
            'new_item_name'     => __( 'Nueva categoría', 'dc-events' ),
            'menu_name'         => __( 'Categorías', 'dc-events' ),
        ];

        register_taxonomy( 'event_category', 'dc_event', [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'categoria-evento' ],
        ] );

        // Etiquetas de evento
        register_taxonomy( 'event_tag', 'dc_event', [
            'hierarchical'      => false,
            'labels'            => [
                'name'          => __( 'Etiquetas de Evento', 'dc-events' ),
                'singular_name' => __( 'Etiqueta de Evento', 'dc-events' ),
                'menu_name'     => __( 'Etiquetas', 'dc-events' ),
            ],
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'etiqueta-evento' ],
        ] );
    }

    /**
     * Helpers estáticos para obtener datos de eventos
     */

    // Obtener eventos próximos
    public static function get_upcoming_events( $args = [] ) {
        $defaults = [
            'post_type'      => 'dc_event',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'meta_key'       => '_dcevents_start_date',
            'orderby'        => 'meta_value',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'     => '_dcevents_status',
                    'value'   => 'cancelled',
                    'compare' => '!=',
                ],
            ],
        ];

        return get_posts( wp_parse_args( $args, $defaults ) );
    }

    // Verificar si el evento tiene cupo disponible
    public static function has_availability( $event_id ) {
        $max      = (int) get_post_meta( $event_id, '_dcevents_max_capacity', true );
        $current  = (int) get_post_meta( $event_id, '_dcevents_registered_count', true );
        $status   = get_post_meta( $event_id, '_dcevents_status', true );

        if ( $status === 'cancelled' || $status === 'full' ) {
            return false;
        }

        if ( $max > 0 && $current >= $max ) {
            // Actualizar estado a lleno automáticamente
            update_post_meta( $event_id, '_dcevents_status', 'full' );
            return false;
        }

        return true;
    }

    // Incrementar contador de inscritos
    public static function increment_registration_count( $event_id ) {
        $count = (int) get_post_meta( $event_id, '_dcevents_registered_count', true );
        update_post_meta( $event_id, '_dcevents_registered_count', $count + 1 );
    }

    // Decrementar contador de inscritos
    public static function decrement_registration_count( $event_id ) {
        $count = (int) get_post_meta( $event_id, '_dcevents_registered_count', true );
        if ( $count > 0 ) {
            update_post_meta( $event_id, '_dcevents_registered_count', $count - 1 );
        }
    }

    // 
    /**
     * Obtener el precio activo para hoy (tiene en cuenta tramos).
     *
     * @param int $event_id
     * @return float
     */
    public static function get_active_price( $event_id ) {
        $price_type = get_post_meta( $event_id, '_dcevents_price_type', true ) ?: 'single';

        if ( $price_type === 'free' ) return 0.0;

        if ( $price_type === 'tiered' ) {
            $tiers = get_post_meta( $event_id, '_dcevents_price_tiers', true ) ?: [];
            $today = date( 'Y-m-d' );
            foreach ( $tiers as $tier ) {
                $ok = true;
                if ( ! empty($tier['start_date']) && $today < $tier['start_date'] ) $ok = false;
                if ( ! empty($tier['end_date'])   && $today > $tier['end_date'] )   $ok = false;
                if ( $ok ) return floatval( $tier['price'] );
            }
        }

        // Precio único o fallback
        return floatval( get_post_meta( $event_id, '_dcevents_price', true ) );
    }

    /**
     * Obtener el nombre del tramo de precio activo.
     *
     * @param int $event_id
     * @return string|null
     */
    public static function get_active_tier_name( $event_id ) {
        $price_type = get_post_meta( $event_id, '_dcevents_price_type', true ) ?: 'single';
        if ( $price_type !== 'tiered' ) return null;

        $tiers = get_post_meta( $event_id, '_dcevents_price_tiers', true ) ?: [];
        $today = date( 'Y-m-d' );
        foreach ( $tiers as $tier ) {
            $ok = true;
            if ( ! empty($tier['start_date']) && $today < $tier['start_date'] ) $ok = false;
            if ( ! empty($tier['end_date'])   && $today > $tier['end_date'] )   $ok = false;
            if ( $ok ) return sanitize_text_field( $tier['name'] );
        }
        return null;
    }
}

