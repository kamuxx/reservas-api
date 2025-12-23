# Historias de Usuario (Especificación Funcional API-only)

Este documento define el comportamiento de la API REST del sistema de reserva de espacios. Cada Historia de Usuario (HU) representa un contenedor funcional completo y autocontenido, destinado a servir como una especificación técnica determinista.

---
## Dominio: Autenticación

### HU-001: Registro de Nuevo Usuario
- **Dominio funcional:** Autenticación
- **Objetivo de negocio:** Permitir que nuevos clientes se registren en el sistema para que puedan realizar reservas.
- **Alcance funcional:**
    - **Incluye:** Creación de una entidad `user` con un rol por defecto y un estado inicial.
    - **Excluye:** Proceso de activación de cuenta (definido en HU-002).
- **Reglas de negocio estrictas:**
    1. La petición de registro debe incluir `name`, `email`, `phone`  y `password`.
    2. El campo `email` debe ser único en la tabla `users`. Una petición con un email ya existente debe ser rechazada.
    3. La `password` debe cumplir con la política de seguridad del sistema (ej. mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 caracter especial y solo se permitiran: `!#@$%&*`.).
    4. Al crear el usuario, se le asignará el `role_id` correspondiente a "cliente" por defecto.
    5. El nuevo usuario se creará con el estado `pendiente_activacion`.
- **Estados del dominio involucrados:**
    - `user.estado`: `pendiente_activacion` (estado final).
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint `POST /api/register` está implementado y todas las reglas de negocio son validadas por pruebas de integración.

### HU-002: Activación de Cuenta de Usuario
- **Dominio funcional:** Autenticación
- **Objetivo de negocio:** Verificar que el email proporcionado por el usuario es válido y le pertenece, como medida de seguridad.
- **Alcance funcional:**
    - **Incluye:** Validación de un token de activación y cambio de estado del usuario.
    - **Excluye:** Generación y envío del token (se asume que es una acción derivada de HU-001).
- **Reglas de negocio estrictas:**
    1. La API recibirá un token de activación único.
    2. El sistema validará que el token existe, no ha expirado y no ha sido utilizado previamente.
    3. Si el token es válido, el estado del usuario asociado pasará de `pendiente_activacion` a `activo`.
    4. Un token inválido o expirado resultará en una respuesta de error.
    5. No se puede activar un usuario que no esté en estado `pendiente_activacion`.
- **Estados del dominio involucrados:**
    - `user.estado`: `pendiente_activacion` (inicial), `activo` (final).
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint (ej. `GET /api/activate/{token}`) está implementado y valida correctamente los tokens.

### HU-003: Autenticación de Usuario (Login)
- **Dominio funcional:** Autenticación
- **Objetivo de negocio:** Permitir a los usuarios registrados y activos acceder al sistema.
- **Alcance funcional:**
    - **Incluye:** Validación de credenciales y generación de un token de sesión (JWT).
    - **Excluye:** Proceso de registro o recuperación de contraseña.
- **Reglas de negocio estrictas:**
    1. La petición debe contener `email` y `password`.
    2. El `email` debe corresponder a un usuario existente.
    3. La `password` debe ser validada contra el hash almacenado para dicho usuario.
    4. El usuario debe tener el estado `activo`. Peticiones para usuarios `bloqueado` o `pendiente_activacion` deben ser rechazadas.
    5. Una autenticación exitosa debe generar y retornar un token JWT con `user_id`, `role` y una fecha de expiración (`exp`).
- **Estados del dominio involucrados:**
    - `user.estado`: `activo` (requerido).
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint `POST /api/login` está implementado y cumple todas las reglas.

### HU-004: Cierre de Sesión (Logout)
- **Dominio funcional:** Autenticación
- **Objetivo de negocio:** Permitir a un usuario invalidar su sesión actual de forma segura.
- **Alcance funcional:**
    - **Incluye:** Invalidación del JWT actual en el lado del servidor.
- **Reglas de negocio estrictas:**
    1. La petición debe incluir un JWT válido en la cabecera de autorización.
    2. El sistema debe añadir el token a una "lista negra" (blacklist) hasta su fecha de expiración para prevenir su reutilización.
    3. Una vez invalidado, cualquier petición posterior con ese mismo token debe ser rechazada con estado 401.
- **Estados del dominio involucrados:** N/A.
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint `POST /api/logout` (requiriendo autenticación) está implementado y los tokens invalidados no pueden ser reutilizados.

---
## Dominio: Gestión de Espacios

### HU-005: Creación de un Espacio (Admin)
- **Dominio funcional:** Espacios
- **Objetivo de negocio:** Permitir a los administradores añadir nuevos espacios al catálogo de la plataforma.
- **Alcance funcional:**
    - **Incluye:** Creación de un nuevo registro en la entidad `spaces`.
    - **Excluye:** Carga de imágenes para el espacio (definido en HU-009).
- **Reglas de negocio estrictas:**
    1. La petición debe ser realizada por un usuario autenticado con rol `administrador`.
    2. La petición debe contener `name`, `description`, `capacity`, `is_active` y `space_type_id`.
    3. El `name` debe ser único en la tabla `spaces`.
    4. `capacity` debe ser un entero mayor que 0.
    5. `space_type_id` debe ser un ID válido existente en la tabla `space_types`.
    6. Una creación exitosa retornará el recurso completo del espacio creado con un código de estado 201.
- **Estados del dominio involucrados:**
    - `space.is_active`: `true`, `false`.
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint `POST /api/spaces` está implementado y protegido por el control de acceso basado en roles.

### HU-006: Modificación de un Espacio (Admin)
- **Dominio funcional:** Espacios
- **Objetivo de negocio:** Permitir a los administradores actualizar la información de los espacios existentes.
- **Alcance funcional:**
    - **Incluye:** Actualización parcial o total de los atributos de un espacio.
- **Reglas de negocio estrictas:**
    1. La petición debe ser realizada por un usuario autenticado con rol `administrador`.
    2. Se debe especificar el ID del espacio a modificar (ej. `PUT /api/spaces/{id}`).
    3. Si se proporciona un `name`, este debe ser único entre todos los demás espacios.
    4. Las mismas validaciones de la HU-005 aplican para los campos que se intenten modificar.
- **Estados del dominio involucrados:**
    - `space.is_active`: `true`, `false`.
- **Condición de completitud de la HU:** La HU está completa cuando los endpoints `PUT /api/spaces/{id}` y `PATCH /api/spaces/{id}` están implementados.

### HU-007: Consulta de Espacios
- **Dominio funcional:** Espacios
- **Objetivo de negocio:** Permitir a cualquier usuario (autenticado o no) ver los espacios disponibles.
- **Alcance funcional:**
    - **Incluye:** Listado y filtrado de espacios, y consulta de un espacio individual.
    - **Excluye:** Visualización de espacios no activos por parte de usuarios no administradores.
- **Reglas de negocio estrictas:**
    1. Un endpoint `GET /api/spaces` debe retornar una lista paginada de todos los espacios.
    2. Para usuarios no autenticados o con rol "cliente", la lista solo debe contener espacios con `is_active = true`.
    3. Los administradores pueden ver todos los espacios, sin importar su estado `is_active`.
    4. El listado debe soportar filtrado por `capacity` (mínima) y `space_type_id`.
    5. Un endpoint `GET /api/spaces/{id}` debe retornar los detalles de un único espacio. Si el espacio no está activo, solo un administrador puede verlo.
- **Estados del dominio involucrados:**
    - `space.is_active`: `true`, `false`.
- **Condición de completitud de la HU:** La HU está completa cuando los endpoints `GET /api/spaces` y `GET /api/spaces/{id}` están implementados y respetan las reglas de autorización.

### HU-008: Consulta de Disponibilidad de un Espacio
- **Dominio funcional:** Espacios
- **Objetivo de negocio:** Informar a los usuarios sobre los horarios en que un espacio está libre para ser reservado.
- **Alcance funcional:**
    - **Incluye:** Devolver una lista de bloques de tiempo ocupados o disponibles para un espacio en un rango de fechas.
- **Reglas de negocio estrictas:**
    1. El endpoint (ej. `GET /api/spaces/{id}/availability`) recibirá un rango de fechas (`start_date`, `end_date`).
    2. La respuesta debe ser una lista de los bloques de tiempo ya reservados dentro de ese rango para el `space_id` especificado.
    3. El endpoint debe ser accesible por cualquier usuario autenticado.
- **Estados del dominio involucrados:** N/A.
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint de consulta de disponibilidad está implementado y retorna los bloques de tiempo reservados correctamente.

---
## Dominio: Reservas

### HU-009: Creación de una Reserva
- **Dominio funcional:** Reservas
- **Objetivo de negocio:** Permitir a un cliente formalizar la reserva de un espacio que ha sido verificado como disponible.
- **Alcance funcional:**
    - **Incluye:** Creación de un registro en la tabla `reservations` tras una validación atómica de disponibilidad.
    - **Excluye:** Búsqueda o exploración de espacios disponibles. Esta HU no es un mecanismo de descubrimiento.
- **Reglas de negocio estrictas:**
    1. La petición debe ser realizada por un usuario autenticado con rol `cliente`.
    2. La petición debe contener `space_id`, `event_name`, `start_time` y `end_time`.
    3. El `space_id` debe corresponder a un espacio con estado `is_active = true`. El cliente de la API es responsable de verificar esto a través de una consulta previa (HU-007 o HU-011).
    4. `end_time` debe ser estrictamente posterior a `start_time`.
    5. **Dependencia Funcional:** La lógica de negocio asume que el `space_id` y el rango horario `[start_time, end_time]` son el resultado de una consulta de disponibilidad previa (HU-008), donde se le presentaron al usuario los bloques libres.
    6. **Atomicidad y Verificación final:** En el momento de la transacción, el sistema debe re-validar que no existen otras reservas para el mismo `space_id` que se solapen con el rango `[start_time, end_time]`. Esta es la validación definitiva y debe ser a prueba de condiciones de carrera.
    7. Si la validación final es exitosa, la reserva se creará con el estado `confirmada` y el `user_id` del solicitante.
- **Estados del dominio involucrados:**
    - `reservation.status`: `confirmada` (estado final).
    - `space.is_active`: `true` (requerido).
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint `POST /api/reservations` está implementado con la validación de solapamiento de horarios y las reglas de negocio especificadas.

### HU-010: Cancelación de una Reserva
- **Dominio funcional:** Reservas
- **Objetivo de negocio:** Permitir a un cliente o administrador anular una reserva existente.
- **Alcance funcional:**
    - **Incluye:** Cambio de estado de una reserva a `cancelada`.
- **Reglas de negocio estrictas:**
    1. Un usuario con rol `cliente` solo puede cancelar las reservas de las que es propietario (`user_id`).
    2. Un usuario con rol `administrador` puede cancelar cualquier reserva.
    3. La petición debe dirigirse a un endpoint específico (ej. `DELETE /api/reservations/{id}` o `POST /api/reservations/{id}/cancel`).
    4. Solo se pueden cancelar reservas que estén en estado `confirmada`.
    5. La cancelación cambiará el estado de la reserva a `cancelada`. El registro no se elimina físicamente.
- **Estados del dominio involucrados:**
    - `reservation.status`: `confirmada` (inicial), `cancelada` (final).
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint de cancelación está implementado y respeta las reglas de propiedad y estado.

### HU-011: Consulta de Espacios Disponibles por Filtros
- **Dominio funcional:** Espacios
- **Objetivo de negocio:** Proveer a los clientes un listado determinista de espacios que tienen disponibilidad garantizada para una fecha y tipo de espacio específicos.
- **Alcance funcional:**
    - **Incluye:** Retornar una lista de entidades `space` que cumplen con los criterios de filtrado y disponibilidad.
    - **Excluye:** Retornar los bloques horarios específicos disponibles (esa es la función de HU-008). Listar espacios que están 100% reservados para la fecha indicada.
- **Reglas de negocio estrictas:**
    1. El endpoint (ej. `GET /api/spaces/available`) debe ser accesible públicamente.
    2. La petición **debe** incluir un parámetro de consulta `fecha_deseada` con el formato `YYYY-MM-DD`. Una petición sin este parámetro debe resultar en un error 400 Bad Request.
    3. La petición **puede** incluir un parámetro `space_type_id` para filtrar por tipo de espacio.
    4. El resultado será una lista de entidades `space`.
    5. Un `space` solo se incluirá en la lista si cumple todas las siguientes condiciones:
        a. Tiene el estado `is_active = true`.
        b. Si se proveyó `space_type_id`, su tipo coincide.
        c. No está completamente reservado durante todo el horario operativo en la `fecha_deseada`.
    6. Las reglas de autorización existentes se mantienen: los administradores podrían tener vistas más privilegiadas en otros endpoints, pero este es de cara al público.
- **Estados del dominio involucrados:**
    - `space.is_active`: `true` (requerido).
- **Condición de completitud de la HU:** La HU está completa cuando el endpoint `GET /api/spaces/available` está implementado, exige el parámetro `fecha_deseada` y retorna únicamente los espacios con disponibilidad verificada.
