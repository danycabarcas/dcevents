# Plugin WordPress: DC Events Manager

## Descripción General

Plugin completo de gestión de eventos para WordPress, diseñado para MCI Santa Marta. Permite crear, mostrar y gestionar eventos con inscripciones, roles especiales y futura integración con pasarelas de pago.

El diseño visual mantendrá el estilo actual del sitio (fondo amarillo `#fae100`, tipografía Barlow/Krona One) pero será completamente personalizable desde el panel de administración.

---

## Decisiones de Arquitectura

### ¿Usuarios WordPress vs. sistema propio?

**Recomendación: Usuarios WordPress nativos con rol personalizado.**

| Opción | Pros | Contras |
|---|---|---|
| Usuarios WP nativos + rol | Sin duplicación de datos, SSO, compatible con otros plugins, escalable | Requiere configuración inicial |
| Sistema de registro propio | Control total | Duplicación, no compatible con WooCommerce/otros |

**Roles propuestos:**
- `event_attendee` → Usuario que se registra en eventos (rol nuevo, capacidades mínimas)
- `event_validator` → Personal interno que valida inscripciones y pagos (rol nuevo)
- `event_manager` → Crea y edita eventos (puede ser Editor o rol propio)
- `administrator` → Control total

---

## Open Questions

> [!IMPORTANT]
> **¿Quieren que los asistentes tengan que iniciar sesión en WordPress para registrarse?** O ¿prefieren un formulario de registro de evento sin login (como un formulario público que crea usuario automáticamente)?
> La recomendación es: **el usuario llena el formulario → se crea automáticamente una cuenta WP con rol `event_attendee`** → recibe email con credenciales → puede ver sus inscripciones en un panel frontal.

> [!IMPORTANT]
> **Moneda y país para pagos futuros:** ¿Colombia (COP)? ¿Usarían Wompi, PayU, MercadoPago o Stripe como pasarela?

> [!NOTE]
> **¿Los eventos son gratuitos ahora?** El plan contempla que algunos eventos tendrán costo en el futuro. El plugin manejará ambos tipos desde el inicio.

> [!NOTE]
> **¿Quieren Shortcodes, Bloque Gutenberg o Widget de Elementor** para mostrar los eventos? Recomiendo los tres para máxima flexibilidad.

---

## Estructura del Plugin

```
dc-events-manager/
├── dc-events-manager.php          # Archivo principal (bootstrap)
├── includes/
│   ├── class-roles.php            # Registro de roles y capacidades
│   ├── class-post-types.php       # CPT: dc_event, dc_registration
│   ├── class-meta-boxes.php       # Campos de evento (fecha, lugar, precio, aforo)
│   ├── class-registration.php     # Lógica de inscripciones
│   ├── class-email.php            # Emails automáticos
│   ├── class-shortcodes.php       # Shortcodes de frontend
│   └── class-settings.php        # Panel de ajustes (colores, textos)
├── admin/
│   ├── class-admin.php            # Menú y páginas admin
│   ├── views/
│   │   ├── dashboard.php          # Panel de control con stats
│   │   ├── registrations.php      # Lista de inscritos (para validador)
│   │   ├── settings.php           # Colores, fuentes, layout
│   │   └── registration-detail.php
│   └── assets/
│       ├── admin.css
│       └── admin.js
├── public/
│   ├── class-public.php
│   ├── views/
│   │   ├── event-list.php         # Lista de eventos (estilo actual MCI)
│   │   ├── event-single.php       # Página individual de evento
│   │   ├── registration-form.php  # Formulario de inscripción
│   │   └── my-registrations.php  # Panel del asistente
│   └── assets/
│       ├── public.css             # Estilos con variables CSS personalizables
│       └── public.js
├── templates/
│   └── emails/
│       ├── confirmation.html      # Email de confirmación al asistente
│       └── notification.html     # Email al administrador
└── languages/
    └── dc-events-es_CO.po
```

---

## Proposed Changes (Fases)

### Fase 1 — Núcleo del Plugin (MVP)
Esta primera entrega incluye todo lo necesario para gestionar eventos y registros.

#### [NEW] `dc-events-manager.php`
Archivo principal: define constantes, carga clases, hooks de activación/desactivación.

#### [NEW] `includes/class-post-types.php`
- CPT `dc_event`: Título, descripción, imagen, fecha inicio/fin, lugar, aforo máximo, precio, estado (activo/inactivo/agotado)
- CPT `dc_registration`: Relación evento-usuario, estado (pendiente/confirmado/cancelado/pagado), datos del inscrito, timestamp

#### [NEW] `includes/class-roles.php`
- Rol `event_attendee`: Capacidades mínimas (leer, ver su panel de inscripciones)
- Rol `event_validator`: Ver y editar inscripciones, validar pagos, NO puede crear eventos
- Hook de activación para crear roles, hook de desactivación para limpiarlos

#### [NEW] `includes/class-meta-boxes.php`
Campos adicionales del evento:
- Fecha y hora de inicio / fin
- Lugar (dirección, ciudad)
- Aforo máximo / inscritos actuales
- Precio (0 = gratuito)
- Imagen de portada
- Formulario de inscripción personalizable (campos: nombre, email, teléfono, campos extras)

#### [NEW] `includes/class-registration.php`
- Procesamiento AJAX del formulario de inscripción
- Validación de aforo
- Creación automática de usuario WP si no existe
- Guardado del registro en CPT `dc_registration`
- Disparo de emails de confirmación

#### [NEW] `includes/class-settings.php`
Panel de configuración visual con variables CSS:
- Color primario (default: `#fae100`)
- Color de texto (default: `#121212`)
- Color de fondo de tarjeta
- Tipografía (selector)
- Layout de lista (tabla estilo MCI actual / tarjetas / grid)

#### [NEW] `public/assets/public.css`
CSS con variables para personalización total, compatible con el tema BeTheme actual.

#### [NEW] `includes/class-shortcodes.php`
- `[dc_events]` → Lista de eventos con filtros
- `[dc_event id="X"]` → Evento individual
- `[dc_registration_form event_id="X"]` → Formulario de inscripción
- `[dc_my_registrations]` → Panel del asistente (requiere login)

---

### Fase 2 — Pagos (Futura)
- Integración con Wompi/PayU Colombia
- Generación de boleta PDF (usando TCPDF o Dompdf)
- Estado de pago en el registro
- QR code en boleta para validación en puerta

---

## Flujo de Usuario

```
Visitante → Ve evento → Formulario de inscripción
    ↓
¿Evento de pago?
  Sí → Redirige a pasarela → Confirmación de pago → Email con boleta
  No → Confirmación directa → Email de confirmación

Validador → Admin WP → Ve lista de inscritos → Marca asistencia
```

---

## Verification Plan

### Automated
- Activación/desactivación del plugin sin errores
- Creación de roles correctamente
- Inscripción vía AJAX funciona
- Emails se envían

### Manual
- Crear evento de prueba desde admin
- Insertar shortcode `[dc_events]` en una página
- Registrar usuario de prueba
- Verificar panel del asistente
- Verificar panel del validador
- Cambiar colores en ajustes y verificar en frontend
