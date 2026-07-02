<?php
/**
 * Plugin Name:       DC Events Manager
 * Plugin URI:        https://mcisantamarta.com
 * Description:       Gestión completa de eventos con inscripciones, roles personalizados y personalización visual. Desarrollado para MCI Santa Marta.
 * Version:           1.0.0
 * Author:            DC Events
 * Author URI:        https://mcisantamarta.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dc-events
 * Domain Path:       /languages
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // No acceso directo
}

// ─── Constantes ───────────────────────────────────────────────────────────────
define( 'DCEVENTS_VERSION',  '1.0.0' );
define( 'DCEVENTS_FILE',     __FILE__ );
define( 'DCEVENTS_PATH',     plugin_dir_path( __FILE__ ) );
define( 'DCEVENTS_URL',      plugin_dir_url( __FILE__ ) );
define( 'DCEVENTS_BASENAME', plugin_basename( __FILE__ ) );

// ─── Autoload de clases ───────────────────────────────────────────────────────
function dcevents_autoload( $class ) {
    $prefix = 'DCEvents_';
    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $class_name = strtolower( str_replace( [ $prefix, '_' ], [ '', '-' ], $class ) );
    $locations  = [
        DCEVENTS_PATH . 'includes/class-' . $class_name . '.php',
        DCEVENTS_PATH . 'admin/class-'    . $class_name . '.php',
        DCEVENTS_PATH . 'public/class-'   . $class_name . '.php',
    ];

    foreach ( $locations as $file ) {
        if ( file_exists( $file ) ) {
            require_once $file;
            return;
        }
    }
}
spl_autoload_register( 'dcevents_autoload' );

// ─── Clase principal ──────────────────────────────────────────────────────────
final class DC_Events_Manager {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        require_once DCEVENTS_PATH . 'includes/class-roles.php';
        require_once DCEVENTS_PATH . 'includes/class-post-types.php';
        require_once DCEVENTS_PATH . 'includes/class-meta-boxes.php';
        require_once DCEVENTS_PATH . 'includes/class-registration.php';
        require_once DCEVENTS_PATH . 'includes/class-email.php';
        require_once DCEVENTS_PATH . 'includes/class-settings.php';
        require_once DCEVENTS_PATH . 'includes/class-shortcodes.php';
        require_once DCEVENTS_PATH . 'public/class-public.php';

        if ( is_admin() ) {
            require_once DCEVENTS_PATH . 'admin/class-admin.php';
        }
    }

    private function init_hooks() {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'init',           [ $this, 'init_components' ] );
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'dc-events',
            false,
            dirname( DCEVENTS_BASENAME ) . '/languages/'
        );
    }

    public function init_components() {
        DCEvents_Post_Types::instance()->register();
        DCEvents_Shortcodes::instance()->register();
        DCEvents_Registration::instance()->init();
        DCEvents_Public::instance()->init();

        if ( is_admin() ) {
            DCEvents_Admin::instance()->init();
        }
    }
}

// ─── Activación ───────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'dcevents_activate' );
function dcevents_activate() {
    DCEvents_Roles::create_roles();

    // Crear página "Mis Inscripciones" automáticamente
    $page_exists = get_posts( [
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_dcevents_page',
        'meta_value'     => 'my_registrations',
    ] );

    if ( empty( $page_exists ) ) {
        $page_id = wp_insert_post( [
            'post_title'   => __( 'Mis Inscripciones', 'dc-events' ),
            'post_content' => '[dc_my_registrations]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
        if ( $page_id ) {
            update_post_meta( $page_id, '_dcevents_page', 'my_registrations' );
            update_option( 'dcevents_my_registrations_page', $page_id );
        }
    }

    // Guardar versión para futuras migraciones
    update_option( 'dcevents_version', DCEVENTS_VERSION );

    // Flush rewrite rules para CPTs
    DCEvents_Post_Types::instance()->register();
    flush_rewrite_rules();
}

// ─── Desactivación ────────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'dcevents_deactivate' );
function dcevents_deactivate() {
    flush_rewrite_rules();
    // Los roles NO se eliminan al desactivar, solo al desinstalar
}

// ─── Desinstalación ───────────────────────────────────────────────────────────
register_uninstall_hook( __FILE__, 'dcevents_uninstall' );
function dcevents_uninstall() {
    DCEvents_Roles::remove_roles();
    delete_option( 'dcevents_version' );
    delete_option( 'dcevents_settings' );
    delete_option( 'dcevents_my_registrations_page' );
}

// ─── Arranque ─────────────────────────────────────────────────────────────────
function dcevents() {
    return DC_Events_Manager::instance();
}
add_action( 'plugins_loaded', 'dcevents' );
