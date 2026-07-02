<?php
/**
 * Roles y capacidades personalizadas
 *
 * @package DCEvents
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class DCEvents_Roles {

    /**
     * Crea los roles personalizados del plugin.
     * Se llama en la activación del plugin.
     */
    public static function create_roles() {

        // ─── event_attendee ───────────────────────────────────────────────
        // Asistente: persona que se inscribe en eventos
        add_role(
            'event_attendee',
            __( 'Asistente de Evento', 'dc-events' ),
            [
                'read'                         => true,
                'dcevents_view_own_registrations' => true,
                'dcevents_create_registration'    => true,
            ]
        );

        // ─── event_validator ──────────────────────────────────────────────
        // Validador: personal que revisa inscripciones y valida pagos/asistencia
        add_role(
            'event_validator',
            __( 'Validador de Eventos', 'dc-events' ),
            [
                'read'                              => true,
                'dcevents_view_all_registrations'   => true,
                'dcevents_edit_registration_status' => true,
                'dcevents_validate_payment'         => true,
                'dcevents_check_in_attendee'        => true,
                'dcevents_export_registrations'     => true,
            ]
        );

        // ─── event_manager ────────────────────────────────────────────────
        // Gestor: puede crear, editar y publicar eventos
        add_role(
            'event_manager',
            __( 'Gestor de Eventos', 'dc-events' ),
            [
                'read'                              => true,
                'upload_files'                      => true,
                'dcevents_create_event'             => true,
                'dcevents_edit_event'               => true,
                'dcevents_delete_event'             => true,
                'dcevents_publish_event'            => true,
                'dcevents_view_all_registrations'   => true,
                'dcevents_edit_registration_status' => true,
                'dcevents_validate_payment'         => true,
                'dcevents_check_in_attendee'        => true,
                'dcevents_export_registrations'     => true,
                'dcevents_manage_settings'          => true,
            ]
        );

        // ─── Dar capacidades al administrador ─────────────────────────────
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $caps = [
                'dcevents_create_event',
                'dcevents_edit_event',
                'dcevents_delete_event',
                'dcevents_publish_event',
                'dcevents_view_all_registrations',
                'dcevents_view_own_registrations',
                'dcevents_create_registration',
                'dcevents_edit_registration_status',
                'dcevents_validate_payment',
                'dcevents_check_in_attendee',
                'dcevents_export_registrations',
                'dcevents_manage_settings',
            ];
            foreach ( $caps as $cap ) {
                $admin->add_cap( $cap );
            }
        }

        // El Editor también puede gestionar eventos
        $editor = get_role( 'editor' );
        if ( $editor ) {
            $editor->add_cap( 'dcevents_create_event' );
            $editor->add_cap( 'dcevents_edit_event' );
            $editor->add_cap( 'dcevents_publish_event' );
            $editor->add_cap( 'dcevents_view_all_registrations' );
        }
    }

    /**
     * Elimina los roles personalizados.
     * Se llama en la desinstalación del plugin.
     */
    public static function remove_roles() {
        remove_role( 'event_attendee' );
        remove_role( 'event_validator' );
        remove_role( 'event_manager' );

        // Limpiar caps del administrador
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $caps = [
                'dcevents_create_event',
                'dcevents_edit_event',
                'dcevents_delete_event',
                'dcevents_publish_event',
                'dcevents_view_all_registrations',
                'dcevents_view_own_registrations',
                'dcevents_create_registration',
                'dcevents_edit_registration_status',
                'dcevents_validate_payment',
                'dcevents_check_in_attendee',
                'dcevents_export_registrations',
                'dcevents_manage_settings',
            ];
            foreach ( $caps as $cap ) {
                $admin->remove_cap( $cap );
            }
        }

        $editor = get_role( 'editor' );
        if ( $editor ) {
            $editor->remove_cap( 'dcevents_create_event' );
            $editor->remove_cap( 'dcevents_edit_event' );
            $editor->remove_cap( 'dcevents_publish_event' );
            $editor->remove_cap( 'dcevents_view_all_registrations' );
        }
    }

    /**
     * Verifica si el usuario actual puede gestionar eventos.
     */
    public static function current_user_can_manage() {
        return current_user_can( 'dcevents_create_event' );
    }

    /**
     * Verifica si el usuario actual puede validar inscripciones.
     */
    public static function current_user_can_validate() {
        return current_user_can( 'dcevents_view_all_registrations' );
    }
}
