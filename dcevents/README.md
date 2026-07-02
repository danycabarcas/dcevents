# DC Events Manager

Plugin WordPress completo para gestión de eventos con inscripciones, roles personalizados y personalización visual.

## Instalación

1. Copia la carpeta `dc-events-manager` (este directorio) a `wp-content/plugins/`
2. Activa el plugin en **WordPress → Plugins**
3. El plugin crea automáticamente:
   - Los roles personalizados
   - La página "Mis Inscripciones" con el shortcode `[dc_my_registrations]`

## Roles Creados

| Rol | Descripción | Capacidades |
|---|---|---|
| `event_attendee` | Asistente a eventos | Ver sus propias inscripciones |
| `event_validator` | Personal interno | Ver, validar y hacer check-in de inscripciones |
| `event_manager` | Gestor | Crear/editar eventos + todas las de validador |

## Shortcodes

```
[dc_events]                          → Lista de eventos próximos
[dc_events limit="5"]                → Máximo 5 eventos
[dc_events layout="cards"]           → Layout en tarjetas
[dc_events layout="grid"]            → Layout en cuadrícula
[dc_events category="tour"]          → Filtrar por categoría
[dc_events featured_only="1"]        → Solo eventos destacados
[dc_events show_past="1"]            → Incluir eventos pasados

[dc_registration_form event_id="X"]  → Formulario de inscripción del evento X
[dc_my_registrations]                → Panel del asistente (login requerido)
[dc_event id="X"]                    → Detalle de evento específico
```

## Panel de Administración

Accede desde el menú lateral **DC Eventos**:

- **Dashboard** — Estadísticas generales y últimas inscripciones
- **Eventos** — Lista de todos los eventos (CRUD)
- **Inscripciones** — Lista completa con filtros por estado, búsqueda, exportar CSV
- **Ajustes Visuales** — Colores, tipografía, layout (con preview en tiempo real)

## Flujo de Inscripción

```
Visitante → Ve evento → Formulario → Inscripción creada
    ↓
¿Ya tiene cuenta WP?
  Sí → Vincula inscripción al usuario existente
  No → Se crea cuenta automáticamente con rol event_attendee
           + Email de bienvenida con credenciales
    ↓
Email de confirmación con código de inscripción
    ↓
Validador → Admin → Lista de inscritos → Check-in
```

## Personalización Visual

Desde **DC Eventos → Ajustes Visuales** puedes cambiar:
- 🎨 Colores (primario, secundario, texto, fondo, botones)
- 🖼️ Layout (lista, tarjetas, cuadrícula)
- 🔤 Tipografía (fuente del cuerpo y de títulos)
- ✏️ Textos personalizables
- ⚙️ Opciones (mostrar/ocultar categorías, búsqueda, cuenta regresiva)

## Futuras Integraciones (Fase 2)

- Pasarelas de pago (Wompi Colombia, PayU, MercadoPago)
- Generación de boleta PDF
- QR code en la boleta para check-in
- Widget de Elementor
- Bloque Gutenberg

## Archivos

```
dc-events-manager/
├── dc-events-manager.php          ← Archivo principal
├── includes/
│   ├── class-roles.php            ← Roles y capacidades
│   ├── class-post-types.php       ← CPTs dc_event, dc_registration
│   ├── class-meta-boxes.php       ← Campos del evento
│   ├── class-registration.php     ← Lógica de inscripciones
│   ├── class-email.php            ← Emails automáticos
│   ├── class-shortcodes.php       ← Shortcodes
│   └── class-settings.php        ← Ajustes visuales
├── admin/
│   ├── class-admin.php            ← Panel admin
│   ├── views/dashboard.php
│   ├── views/registrations.php
│   ├── views/registration-detail.php
│   ├── views/settings.php
│   ├── assets/admin.css
│   └── assets/admin.js
├── public/
│   ├── class-public.php
│   ├── views/event-list.php       ← Layout "lista" (estilo MCI)
│   ├── views/event-single.php
│   ├── views/registration-form.php
│   ├── views/my-registrations.php
│   ├── assets/public.css          ← CSS con variables personalizables
│   └── assets/public.js
└── templates/emails/
    └── confirmation.html
```
