<?php
/**
 * Panel de ajustes visuales del plugin
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Settings {

    private static $instance = null;
    private $option_key = 'dcevents_settings';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function register_settings() {
        register_setting( 'dcevents_settings_group', $this->option_key, [
            'sanitize_callback' => [ $this, 'sanitize_settings' ],
        ] );
    }

    public function sanitize_settings( $input ) {
        $sanitized = [];

        // Colores
        $color_fields = [ 'primary_color', 'secondary_color', 'text_color', 'bg_color', 'card_bg', 'button_text_color', 'accent_color' ];
        foreach ( $color_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
            }
        }

        // Textos
        $text_fields = [ 'button_text', 'register_title', 'no_events_text', 'font_family', 'heading_font' ];
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_text_field( $input[ $field ] );
            }
        }

        // Layout
        if ( isset( $input['event_layout'] ) && in_array( $input['event_layout'], [ 'list', 'cards', 'grid', 'timeline' ] ) ) {
            $sanitized['event_layout'] = $input['event_layout'];
        }

        // Numéricos
        $num_fields = [ 'events_per_page', 'border_radius' ];
        foreach ( $num_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $sanitized[ $field ] = absint( $input[ $field ] );
            }
        }

        // Booleanos
        $bool_fields = [ 'show_past_events', 'show_category_filter', 'show_search', 'show_countdown', 'dark_mode' ];
        foreach ( $bool_fields as $field ) {
            $sanitized[ $field ] = isset( $input[ $field ] ) ? '1' : '0';
        }

        return $sanitized;
    }

    /**
     * Obtiene un valor de configuración con fallback al default.
     */
    public static function get( $key, $default = null ) {
        $settings = get_option( 'dcevents_settings', [] );
        $defaults = self::defaults();

        if ( isset( $settings[ $key ] ) && $settings[ $key ] !== '' ) {
            return $settings[ $key ];
        }

        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : $default;
    }

    /**
     * Valores por defecto (inspirados en el branding MCI Santa Marta).
     */
    public static function defaults() {
        return [
            'primary_color'       => '#fae100',   // amarillo MCI
            'secondary_color'     => '#121212',    // negro
            'text_color'          => '#121212',
            'bg_color'            => '#ffffff',
            'card_bg'             => '#ffffff',
            'button_text_color'   => '#121212',
            'accent_color'        => '#0073aa',
            'event_layout'        => 'list',
            'events_per_page'     => 10,
            'border_radius'       => 0,
            'font_family'         => 'inherit',
            'heading_font'        => 'inherit',
            'button_text'         => 'Inscribirse',
            'register_title'      => 'Inscripción al Evento',
            'no_events_text'      => 'No hay eventos próximos.',
            'show_past_events'    => '0',
            'show_category_filter'=> '1',
            'show_search'         => '0',
            'show_countdown'      => '1',
            'dark_mode'           => '0',
        ];
    }

    /**
     * Genera las variables CSS para el frontend.
     */
    public static function get_css_variables() {
        $settings = array_merge( self::defaults(), get_option( 'dcevents_settings', [] ) );

        return sprintf(
            ':root {
                --dce-primary:       %s;
                --dce-secondary:     %s;
                --dce-text:          %s;
                --dce-bg:            %s;
                --dce-card-bg:       %s;
                --dce-btn-text:      %s;
                --dce-accent:        %s;
                --dce-radius:        %dpx;
                --dce-font:          %s;
                --dce-heading-font:  %s;
            }',
            esc_attr( $settings['primary_color'] ),
            esc_attr( $settings['secondary_color'] ),
            esc_attr( $settings['text_color'] ),
            esc_attr( $settings['bg_color'] ),
            esc_attr( $settings['card_bg'] ),
            esc_attr( $settings['button_text_color'] ),
            esc_attr( $settings['accent_color'] ),
            intval( $settings['border_radius'] ),
            esc_attr( $settings['font_family'] ),
            esc_attr( $settings['heading_font'] )
        );
    }
}
