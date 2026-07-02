<?php
/**
 * Shortcodes del plugin
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Shortcodes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function register() {
        add_shortcode( 'dc_events',             [ $this, 'events_list' ] );
        add_shortcode( 'dc_event',              [ $this, 'single_event' ] );
        add_shortcode( 'dc_registration_form',  [ $this, 'registration_form' ] );
        add_shortcode( 'dc_my_registrations',   [ $this, 'my_registrations' ] );
    }

    // ─── [dc_events] ─────────────────────────────────────────────────────────
    // Parámetros: limit, category, layout, show_past, featured_only
    public function events_list( $atts ) {
        $atts = shortcode_atts( [
            'limit'          => DCEvents_Settings::get( 'events_per_page', 10 ),
            'category'       => '',
            'layout'         => DCEvents_Settings::get( 'event_layout', 'list' ),
            'show_past'      => DCEvents_Settings::get( 'show_past_events', '0' ),
            'featured_only'  => '0',
            'order'          => 'ASC',
        ], $atts, 'dc_events' );

        $query_args = [
            'post_type'      => 'dc_event',
            'post_status'    => 'publish',
            'posts_per_page' => intval( $atts['limit'] ),
            'meta_key'       => '_dcevents_start_date',
            'orderby'        => 'meta_value',
            'order'          => sanitize_key( $atts['order'] ),
            'meta_query'     => [ 'relation' => 'AND' ],
        ];

        // Filtrar por categoría
        if ( ! empty( $atts['category'] ) ) {
            $query_args['tax_query'] = [ [
                'taxonomy' => 'event_category',
                'field'    => is_numeric( $atts['category'] ) ? 'term_id' : 'slug',
                'terms'    => $atts['category'],
            ] ];
        }

        // Filtrar eventos pasados
        if ( $atts['show_past'] !== '1' ) {
            $query_args['meta_query'][] = [
                'key'     => '_dcevents_start_date',
                'value'   => date( 'Y-m-d' ),
                'compare' => '>=',
                'type'    => 'DATE',
            ];
        }

        // Solo destacados
        if ( $atts['featured_only'] === '1' ) {
            $query_args['meta_query'][] = [
                'key'   => '_dcevents_featured',
                'value' => '1',
            ];
        }

        // No mostrar cancelados
        $query_args['meta_query'][] = [
            'relation' => 'OR',
            [ 'key' => '_dcevents_status', 'value' => 'cancelled', 'compare' => '!=' ],
            [ 'key' => '_dcevents_status', 'compare' => 'NOT EXISTS' ],
        ];

        $events = get_posts( $query_args );

        ob_start();

        // Variables CSS
        echo '<style>' . DCEvents_Settings::get_css_variables() . '</style>';

        if ( empty( $events ) ) {
            echo '<div class="dce-no-events"><p>' . esc_html( DCEvents_Settings::get( 'no_events_text', __( 'No hay eventos próximos.', 'dc-events' ) ) ) . '</p></div>';
        } else {
            $layout_file = DCEVENTS_PATH . 'public/views/event-' . sanitize_key( $atts['layout'] ) . '.php';
            if ( ! file_exists( $layout_file ) ) {
                $layout_file = DCEVENTS_PATH . 'public/views/event-list.php';
            }

            // Filtro de categorías
            if ( DCEvents_Settings::get( 'show_category_filter' ) === '1' ) {
                $this->render_category_filter();
            }

            include $layout_file;
        }

        return ob_get_clean();
    }

    // ─── [dc_event id="X"] ────────────────────────────────────────────────────
    public function single_event( $atts ) {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'dc_event' );

        $event_id = intval( $atts['id'] );
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        $event = get_post( $event_id );
        if ( ! $event || 'dc_event' !== $event->post_type ) {
            return '';
        }

        ob_start();
        echo '<style>' . DCEvents_Settings::get_css_variables() . '</style>';
        include DCEVENTS_PATH . 'public/views/event-single.php';
        return ob_get_clean();
    }

    // ─── [dc_registration_form event_id="X"] ─────────────────────────────────
    public function registration_form( $atts ) {
        $atts = shortcode_atts( [
            'event_id' => 0,
        ], $atts, 'dc_registration_form' );

        $event_id = intval( $atts['event_id'] );
        if ( ! $event_id ) {
            $event_id = get_the_ID();
        }

        $event = get_post( $event_id );
        if ( ! $event || 'dc_event' !== $event->post_type || 'publish' !== $event->post_status ) {
            return '<p>' . __( 'Evento no disponible.', 'dc-events' ) . '</p>';
        }

        $status   = get_post_meta( $event_id, '_dcevents_status', true ) ?: 'active';
        $reg_open = get_post_meta( $event_id, '_dcevents_registration_open', true );
        $deadline = get_post_meta( $event_id, '_dcevents_registration_deadline', true );

        ob_start();
        echo '<style>' . DCEvents_Settings::get_css_variables() . '</style>';
        include DCEVENTS_PATH . 'public/views/registration-form.php';
        return ob_get_clean();
    }

    // ─── [dc_my_registrations] ────────────────────────────────────────────────
    public function my_registrations( $atts ) {
        ob_start();
        echo '<style>' . DCEvents_Settings::get_css_variables() . '</style>';
        include DCEVENTS_PATH . 'public/views/my-registrations.php';
        return ob_get_clean();
    }

    // ─── Filtro de categorías ─────────────────────────────────────────────────
    private function render_category_filter() {
        $terms = get_terms( [
            'taxonomy'   => 'event_category',
            'hide_empty' => true,
        ] );

        if ( empty( $terms ) || is_wp_error( $terms ) ) return;

        $current = isset( $_GET['event_cat'] ) ? sanitize_key( $_GET['event_cat'] ) : '';
        $base_url = strtok( $_SERVER['REQUEST_URI'], '?' );
        ?>
        <div class="dce-category-filter">
            <a href="<?php echo esc_url( $base_url ); ?>"
               class="dce-cat-btn <?php echo empty( $current ) ? 'active' : ''; ?>">
                <?php _e( 'Todos', 'dc-events' ); ?>
            </a>
            <?php foreach ( $terms as $term ) : ?>
                <a href="<?php echo esc_url( add_query_arg( 'event_cat', $term->slug, $base_url ) ); ?>"
                   class="dce-cat-btn <?php echo $current === $term->slug ? 'active' : ''; ?>">
                    <?php echo esc_html( $term->name ); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
