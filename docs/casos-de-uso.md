# Casos de Uso del Sistema de Reserva de Espacios

## Tabla Resumen de Casos de Uso

| Código | Nombre | HU Relacionada | Estado | Objetivo | Descripción |
|--------|--------|----------------|--------|----------|-------------|
| HU-001-UC-001 | Registro de Nuevo Usuario | HU-001 | ✅ Completado | Permitir que nuevos clientes se registren en el sistema | El usuario proporciona sus datos para crear una cuenta con estado pendiente de activación (pending) |
| HU-002-UC-001 | Activación de Cuenta de Usuario | HU-002 | ✅ Completado | Verificar la propiedad del email del usuario | El usuario activa su cuenta mediante un token de activación |
| HU-003-UC-001 | Autenticación de Usuario (Login) | HU-003 | ✅ Completado | Permitir acceso al sistema a usuarios registrados y activos | El usuario ingresa sus credenciales para obtener un token JWT |
| HU-004-UC-001 | Cierre de Sesión (Logout) | HU-004 | ✅ Completado | Invalidar de forma segura la sesión actual | El usuario cierra su sesión invalidando el JWT en el servidor |
| HU-005-UC-001 | Creación de Espacio (Admin) | HU-005 | ✅ Completado | Permitir a administradores añadir nuevos espacios | Un administrador crea un nuevo registro en la entidad `spaces` |
| HU-006-UC-001 | Modificación de Espacio (Admin) | HU-006 | ✅ Completado | Permitir a administradores actualizar información de espacios | Un administrador actualiza atributos de un espacio existente |
| HU-007-UC-001 | Consulta de Listado de Espacios | HU-007 | ✅ Completado | Permitir visualizar espacios disponibles | Cualquier usuario (autenticado o no) lista espacios con filtros |
| HU-007-UC-002 | Consulta de Detalle de Espacio | HU-007 | ✅ Completado | Ver información detallada de un espacio | Consulta individual de un espacio con reglas de autorización |
| HU-008-UC-001 | Consulta de Disponibilidad de Espacio | HU-008 | ⏳ Pendiente | Informar sobre horarios disponibles/ocupados | Usuario autenticado consulta bloques de tiempo para un espacio |
| HU-009-UC-001 | Creación de Reserva con Validación Atómica | HU-009 | ⏳ Pendiente | Formalizar reserva de espacio verificando disponibilidad | Cliente crea reserva con validación atómica de solapamiento |
| HU-010-UC-001 | Cancelación de Reserva por Cliente | HU-010 | ⏳ Pendiente | Permitir anulación de reservas propias | Cliente cancela una reserva confirmada de su propiedad |
| HU-010-UC-002 | Cancelación de Reserva por Administrador | HU-010 | ⏳ Pendiente | Permitir anulación de cualquier reserva | Administrador cancela cualquier reserva confirmada |
| HU-011-UC-001 | Consulta de Espacios Disponibles por Filtros | HU-011 | ⏳ Pendiente | Listar espacios con disponibilidad garantizada | Listado público de espacios disponibles para fecha específica |

---

## HU-001-UC-001: Registro de Nuevo Usuario

### Precondiciones
1. El usuario no tiene una cuenta activa en el sistema
2. El sistema tiene configurado al menos un rol "cliente" en la tabla `user_roles` (entidades.md)
3. El sistema tiene configurado al menos un estado "pending" en la tabla `user_statuses` (entidades.md)

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Envía solicitud `POST /api/register` con JSON body conteniendo: `name`, `email`, `phone`, `password`

#### Respuestas del Sistema
2. Valida que todos los campos requeridos estén presentes
3. Valida unicidad del `email` en la tabla `users` (Historias de Usuario.md - HU-001)
4. Valida que `password` cumpla política de seguridad (mínimo 8 caracteres, 1 mayúscula, 1 minúscula, 1 número, 1 caracter especial permitido: `!#@$%&*`)
5. Obtiene `role_id` correspondiente a "cliente" de la tabla `user_roles` (entidades.md)
6. Obtiene `status_id` correspondiente a "pending" de la tabla `user_statuses` (entidades.md)
7. Hashea la contraseña y crea registro en tabla `users` con `status_id` apropiado
8. (Implícito) Genera y almacena token de activación en `activation_tokens` (HU-002)
9. Retorna respuesta HTTP 201 con datos del usuario creado (sin password)

### Flujos Cubiertos
#### Flujo Principal (HU-001-UC-001-FP)
1. Usuario envía datos válidos y únicos
2. Sistema valida, crea usuario con estado `pending`
3. Sistema retorna éxito (201 Created)

#### Flujos Alternos
**HU-001-UC-001-FA-001: Email ya existente**
1. Usuario envía registro con email existente en `users`
2. Sistema detecta violación de unicidad (índice `idx_users_email`)
3. Sistema retorna error 409 Conflict o 400 Bad Request

**HU-001-UC-001-FA-002: Password no cumple política**
1. Usuario envía password que no cumple requisitos
2. Sistema valida y rechaza
3. Sistema retorna error 400 Bad Request con detalles

**HU-001-UC-001-FA-003: Campos requeridos faltantes**
1. Usuario omite uno o más campos requeridos
2. Sistema valida y rechaza
3. Sistema retorna error 400 Bad Request

#### Flujos de Seguridad
**HU-001-UC-001-FS-001: Intento de registro masivo (rate limiting)**
1. Cliente API realiza múltiples solicitudes de registro en corto tiempo
2. Sistema aplica rate limiting (inferido de buenas prácticas REST)
3. Sistema retorna error 429 Too Many Requests

### Postcondiciones
1. Se crea un nuevo registro en la tabla `users` con `status_id` = "pending"
2. Se genera un registro en `activation_tokens` asociado al usuario (implícito para HU-002)
3. El usuario no puede autenticarse hasta activación

### Caso de Uso Siguiente
**HU-002-UC-001: Activación de Cuenta de Usuario**
Justificación: Tras el registro exitoso, el usuario debe activar su cuenta mediante el token enviado por email (proceso implícito en HU-002).

---

## HU-002-UC-001: Activación de Cuenta de Usuario

### Precondiciones
1. Existe un usuario con estado "pending" en tabla `users`
2. Existe un token de activación válido en tabla `activation_tokens` asociado al usuario
3. El token no ha expirado (`expires_at` > ahora) y no ha sido usado (`used_at` IS NULL)

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Accede al endpoint de activación (ej: `GET /api/activate/{token}`)

#### Respuestas del Sistema
2. Busca token en `activation_tokens` usando índice `idx_activation_tokens_token_hash`
3. Valida que token exista, no haya expirado y no haya sido usado
4. Valida que usuario asociado tenga `status_id` = "pending" en `users`
5. Actualiza `status_id` del usuario a "active" (consulta `user_statuses`)
6. Marca token como usado (`used_at` = NOW())
7. Retorna respuesta de éxito

### Flujos Cubiertos
#### Flujo Principal (HU-002-UC-001-FP)
1. Usuario accede con token válido
2. Sistema activa cuenta
3. Sistema retorna confirmación

#### Flujos Alternos
**HU-002-UC-001-FA-001: Token expirado**
1. Token existe pero `expires_at` < ahora
2. Sistema retorna error 400/410

**HU-002-UC-001-FA-002: Token ya utilizado**
1. Token tiene `used_at` NOT NULL
2. Sistema retorna error 400

**HU-002-UC-001-FA-003: Usuario no en estado pending**
1. Usuario ya está active, bloqueado, etc.
2. Sistema retorna error 400

**HU-002-UC-001-FA-004: Token no existe**
1. Token no encontrado en `activation_tokens`
2. Sistema retorna error 404

#### Flujos de Seguridad
**HU-002-UC-001-FS-001: Intento de fuerza bruta con tokens**
1. Múltiples solicitudes con tokens inválidos desde misma IP
2. Sistema podría registrar en `login_audit_trails` y aplicar rate limiting

### Postcondiciones
1. El usuario tiene `status_id` = "active" en tabla `users`
2. El token de activación tiene `used_at` = fecha actual
3. El usuario puede autenticarse (HU-003)

### Caso de Uso Siguiente
**HU-003-UC-001: Autenticación de Usuario (Login)**
Justificación: Una vez activada la cuenta, el usuario naturalmente procederá a autenticarse para usar el sistema.

---

## HU-003-UC-001: Autenticación de Usuario (Login)

### Precondiciones
1. Existe usuario con estado "active" en tabla `users`
2. El usuario tiene `password_hash` almacenado

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Envía solicitud `POST /api/login` con `email` y `password`

#### Respuestas del Sistema
2. Busca usuario por `email` usando índice `idx_users_email`
3. Verifica que `status_id` corresponda a "active" (consulta `user_statuses`)
4. Compara hash de `password` proporcionada con `password_hash` almacenado
5. Genera token JWT con `user_id`, `role` y `exp`
6. Actualiza `last_login_at` del usuario
7. Registra intento exitoso en `login_audit_trails`
8. Retorna token JWT en respuesta

### Flujos Cubiertos
#### Flujo Principal (HU-003-UC-001-FP)
1. Credenciales correctas y usuario active
2. Sistema genera y retorna JWT
3. Registra auditoría exitosa

#### Flujos Alternos
**HU-003-UC-001-FA-001: Usuario no existe**
1. `email` no encontrado en `users`
2. Sistema registra en `login_audit_trails` con status 'failed_user_not_found'
3. Retorna error 401 Unauthorized (sin revelar si usuario existe)

**HU-003-UC-001-FA-002: Password incorrecta**
1. `email` existe pero password no coincide
2. Sistema registra en `login_audit_trails` con status 'failed_password'
3. Retorna error 401 Unauthorized

**HU-003-UC-001-FA-003: Usuario no active**
1. Usuario existe pero no tiene estado "active" (ej: pending, bloqueado)
2. Sistema registra en `login_audit_trails` con status apropiado
3. Retorna error 401/403

#### Flujos de Seguridad
**HU-003-UC-001-FS-001: Intento de fuerza bruta desde misma IP**
1. Múltiples intentos fallidos desde misma IP
2. Sistema detecta patrón usando `idx_login_audit_trails_ip_time`
3. Podría aplicar bloqueo temporal (inferido de buenas prácticas)

### Postcondiciones
1. Se genera token JWT válido
2. Se actualiza `last_login_at` del usuario
3. Se registra entrada en `login_audit_trails`
4. Usuario puede hacer solicitudes autenticadas

### Caso de Uso Siguiente
**HU-007-UC-001: Consulta de Listado de Espacios** o **HU-011-UC-001: Consulta de Espacios Disponibles por Filtros**
Justificación: Tras autenticarse, el usuario típicamente buscará espacios disponibles para reservar.

---

## HU-004-UC-001: Cierre de Sesión (Logout)

### Precondiciones
1. Usuario tiene un JWT válido actual
2. El JWT tiene claim `jti` (JWT ID)

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Envía solicitud `POST /api/logout` con JWT en header Authorization

#### Respuestas del Sistema
2. Verifica que JWT sea válido (firma, no expirado)
3. Extrae `jti` del token
4. Inserta `jti` y `expires_at` en tabla `blacklisted_tokens`
5. Retorna confirmación de éxito

### Flujos Cubiertos
#### Flujo Principal (HU-004-UC-001-FP)
1. JWT válido proporcionado
2. Sistema agrega `jti` a lista negra
3. Retorna éxito

#### Flujos Alternos
**HU-004-UC-001-FA-001: Token ya expirado**
1. JWT tiene `exp` < ahora
2. Sistema podría rechazar o aceptar logout (depende de implementación)
3. Retorna error 401 o éxito

**HU-004-UC-001-FA-002: Token sin claim jti**
1. JWT no tiene claim `jti`
2. Sistema no puede agregar a lista negra
3. Retorna error 400

#### Flujos de Seguridad
**HU-004-UC-001-FS-001: Token ya en lista negra**
1. `jti` ya existe en `blacklisted_tokens`
2. Sistema podría retornar error o éxito idempotente
3. Registra posible intento de reutilización

### Postcondiciones
1. `jti` del token se almacena en `blacklisted_tokens`
2. Token no puede usarse para solicitudes futuras
3. Cualquier solicitud posterior con mismo token retornará 401

### Caso de Uso Siguiente
**HU-003-UC-001: Autenticación de Usuario (Login)** (si desea autenticarse nuevamente)
Justificación: Tras logout, el usuario deberá autenticarse nuevamente para acceder a funcionalidades protegidas.

---

## HU-005-UC-001: Creación de Espacio (Admin)

### Precondiciones
1. Usuario autenticado tiene `role_id` correspondiente a "administrador" (consulta `user_roles`)
2. Existen registros en `space_types` para `space_type_id` válido
3. (Opcional) Existen registros en `locations` y `pricing_rules` si se implementan

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Administrador envía `POST /api/spaces` con JWT en header
2. JSON body contiene: `name`, `description`, `capacity`, `is_active`, `spaces_type_id`, `status_id`, `pricing_rule_id`

#### Respuestas del Sistema
3. Verifica JWT y rol de administrador
4. Valida unicidad de `name` en tabla `spaces`
5. Valida que `capacity` > 0
6. Valida que `spaces_type_id`, `status_id` y `pricing_rule_id` existan en sus respectivas tablas
7. Crea registro en tabla `spaces`
8. Retorna recurso creado con HTTP 201

### Flujos Cubiertos
#### Flujo Principal (HU-005-UC-001-FP)
1. Administrador con datos válidos
2. Sistema crea espacio
3. Retorna 201 con recurso

#### Flujos Alternos
**HU-005-UC-001-FA-001: Nombre duplicado**
1. `name` ya existe en `spaces`
2. Sistema retorna error 422 Unprocessable Entity

**HU-005-UC-001-FA-002: Capacity no válida**
1. `capacity` <= 0
2. Sistema retorna error 422 Unprocessable Entity

**HU-005-UC-001-FA-003: spaces_type_id no existe**
1. Referencia a `space_types` inválida
2. Sistema retorna error 422 Unprocessable Entity

#### Flujos de Seguridad
**HU-005-UC-001-FS-001: Usuario no administrador**
1. Usuario con rol "cliente" intenta crear espacio
2. Sistema verifica rol usando `users.role_id`
3. Retorna error 403 Forbidden

**HU-005-UC-001-FS-002: JWT inválido o expirado**
1. Token no válido o en `blacklisted_tokens`
2. Sistema retorna error 401 Unauthorized

### Postcondiciones
1. Nuevo registro creado en tabla `spaces`
2. El espacio está disponible para consulta (según `is_active`)
3. Se puede auditar creación en `entity_audit_trails`

### Caso de Uso Siguiente
**HU-007-UC-001: Consulta de Listado de Espacios** o **HU-008-UC-001: Consulta de Disponibilidad de Espacio**
Justificación: Tras crear un espacio, los usuarios podrán consultarlo y ver su disponibilidad.

---

## HU-006-UC-001: Modificación de Espacio (Admin)

### Precondiciones
1. Usuario autenticado es administrador
2. Existe espacio con `id` especificado en tabla `spaces`

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Administrador envía `PUT /api/spaces/{id}` o `PATCH /api/spaces/{id}` con JWT
2. JSON body contiene campos a modificar

#### Respuestas del Sistema
3. Verifica JWT y rol de administrador
4. Busca espacio por `id`
5. Si se proporciona nuevo `name`, valida unicidad excluyendo el actual
6. Aplica mismas validaciones de HU-005 para campos modificados
7. Actualiza registro en `spaces`
8. Retorna recurso actualizado

### Flujos Cubiertos
#### Flujo Principal (HU-006-UC-001-FP)
1. Administrador modifica espacio existente con datos válidos
2. Sistema actualiza y retorna éxito

#### Flujos Alternos
**HU-006-UC-001-FA-001: Nombre duplicado (al cambiarlo)**
1. Nuevo `name` ya existe en otro espacio
2. Sistema retorna error 409 Conflict

**HU-006-UC-001-FA-002: Espacio no encontrado**
1. `id` no existe en `spaces`
2. Sistema retorna error 404 Not Found

#### Flujos de Seguridad
**HU-006-UC-001-FS-001: Usuario no administrador**
1. Cliente intenta modificar espacio
2. Sistema verifica rol
3. Retorna 403 Forbidden

### Postcondiciones
1. Registro en `spaces` actualizado
2. `updated_at` se actualiza automáticamente
3. Cambio se audita en `entity_audit_trails`

### Caso de Uso Siguiente
**HU-007-UC-002: Consulta de Detalle de Espacio**
Justificación: Tras modificar un espacio, es natural verificar los cambios consultando su detalle.

---

## HU-007-UC-001: Consulta de Listado de Espacios

### Precondiciones
1. (Opcional) Usuario puede estar autenticado o no
2. Existen espacios en tabla `spaces`

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Solicita `GET /api/spaces` con parámetros opcionales: paginación, `capacity` (mínima), `space_type_id`

#### Respuestas del Sistema
2. Determina rol del usuario (si está autenticado)
3. Construye query base:
   - Si usuario no autenticado o rol "cliente": filtra por `is_active = true`
   - Si rol "administrador": incluye todos los espacios
4. Aplica filtros por `capacity` y `space_type_id` si se proporcionan
5. Aplica paginación
6. Retorna lista paginada usando índices optimizados (`idx_spaces_type_location_capacity`)

### Flujos Cubiertos
#### Flujo Principal (HU-007-UC-001-FP)
1. Usuario (cualquiera) solicita listado
2. Sistema aplica filtros y autorizaciones
3. Retorna lista paginada

#### Flujos Alternos
**HU-007-UC-001-FA-001: Sin espacios que cumplan criterios**
1. Filtros resultan en conjunto vacío
2. Sistema retorna lista vacía (no es error)

**HU-007-UC-001-FA-002: Parámetros de filtro inválidos**
1. `capacity` no numérico, `space_type_id` no existe
2. Sistema retorna error 400 Bad Request

#### Flujos de Seguridad
**HU-007-UC-001-FS-001: Cliente ve espacios inactivos**
1. Usuario cliente intenta acceder a espacios inactivos mediante manipulación de parámetros
2. Sistema aplica filtro `is_active = true` en query
3. Previene exposición de datos no autorizados

### Postcondiciones
1. Usuario recibe listado según sus permisos
2. La consulta es eficiente gracias a índices de cobertura

### Caso de Uso Siguiente
**HU-008-UC-001: Consulta de Disponibilidad de Espacio** o **HU-011-UC-001: Consulta de Espacios Disponibles por Filtros**
Justificación: Tras ver el listado, el usuario querrá ver disponibilidad específica o filtrar por disponibilidad.

---

## HU-007-UC-002: Consulta de Detalle de Espacio

### Precondiciones
1. Existe espacio con `id` especificado en `spaces`

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Solicita `GET /api/spaces/{id}`

#### Respuestas del Sistema
2. Busca espacio por `id`
3. Verifica `is_active` del espacio
4. Determina rol del usuario:
   - Si espacio `is_active = true`: muestra a todos
   - Si espacio `is_active = false`: solo muestra si usuario es administrador
5. Retorna detalle del espacio o error 404/403

### Flujos Cubiertos
#### Flujo Principal (HU-007-UC-002-FP)
1. Espacio activo, cualquier usuario
2. Sistema retorna detalles

#### Flujos Alternos
**HU-007-UC-002-FA-001: Espacio inactivo, usuario no administrador**
1. Espacio con `is_active = false`
2. Usuario no es administrador
3. Sistema retorna error 404 Not Found (por seguridad)

**HU-007-UC-002-FA-002: Espacio no existe**
1. `id` no encontrado en `spaces`
2. Sistema retorna error 404 Not Found

#### Flujos de Seguridad
**HU-007-UC-002-FS-001: Cliente intenta acceder a espacio inactivo**
1. Usuario cliente conoce ID de espacio inactivo
2. Sistema valida `is_active` y rol
3. Retorna 404 independientemente de existencia real

### Postcondiciones
1. Usuario ve detalles del espacio según permisos
2. Administradores ven todos los espacios

### Caso de Uso Siguiente
**HU-008-UC-001: Consulta de Disponibilidad de Espacio**
Justificación: Tras ver el detalle de un espacio, el usuario querrá conocer su disponibilidad para posibles reservas.

---

## HU-008-UC-001: Consulta de Disponibilidad de Espacio

### Precondiciones
1. Usuario autenticado (cualquier rol)
2. Existe espacio con `id` especificado en `spaces`
3. El espacio tiene `is_active = true` o usuario es administrador

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Solicita `GET /api/spaces/{id}/availability` con parámetros `start_date` y `end_date`

#### Respuestas del Sistema
2. Valida que usuario esté autenticado (JWT válido)
3. Valida formato de fechas y que `start_date` <= `end_date`
4. Consulta tabla `reservations` usando índice `idx_reservations_space_time` para:
   - `space_id` = id proporcionado
   - `status_id` = "confirmada"
   - `start_time` < `end_date` AND `end_time` > `start_date`
5. Retorna lista de bloques de tiempo ocupados

### Flujos Cubiertos
#### Flujo Principal (HU-008-UC-001-FP)
1. Usuario autenticado solicita disponibilidad con fechas válidas
2. Sistema consulta reservas confirmadas en rango
3. Retorna bloques ocupados

#### Flujos Alternos
**HU-008-UC-001-FA-001: Fechas inválidas**
1. `start_date` > `end_date`
2. Sistema retorna error 400 Bad Request

**HU-008-UC-001-FA-002: Espacio no encontrado o inactivo**
1. `id` no existe o `is_active = false` y usuario no administrador
2. Sistema retorna error 404 Not Found

**HU-008-UC-001-FA-003: Sin reservas en rango**
1. No hay reservas confirmadas para el espacio en el rango
2. Sistema retorna lista vacía

#### Flujos de Seguridad
**HU-008-UC-001-FS-001: Usuario no autenticado**
1. Solicitud sin JWT válido
2. Sistema retorna error 401 Unauthorized

### Postcondiciones
1. Usuario recibe lista de intervalos ocupados para el espacio
2. Puede inferir disponibilidad (complemento de los ocupados)

### Caso de Uso Siguiente
**HU-009-UC-001: Creación de Reserva con Validación Atómica**
Justificación: Tras verificar disponibilidad, el usuario procederá a crear una reserva en un horario libre.

---

## HU-009-UC-001: Creación de Reserva con Validación Atómica

### Precondiciones
1. Usuario autenticado con rol "cliente"
2. Espacio con `id` especificado existe y tiene `is_active = true`
3. El rango horario deseado está teóricamente disponible (verificado previamente por HU-008)

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Envía `POST /api/reservations` con JWT
2. JSON body contiene: `space_id`, `event_name`, `start_time`, `end_time`

#### Respuestas del Sistema
3. Inicia transacción de base de datos
4. Verifica que `space_id` corresponda a espacio con `is_active = true`
5. Valida que `end_time` > `start_time`
6. **Validación atómica**: Ejecuta `SELECT ... FOR UPDATE` en `reservations` usando índice `idx_reservations_space_time` para detectar solapamientos:
   - Busca reservas con mismo `space_id` y estado "confirmada"
   - Donde `(new_start < existing_end) AND (new_end > existing_start)`
7. Si no hay solapamientos:
   - Crea reserva con `status_id` = "confirmada"
   - Asocia `user_id` del solicitante
   - Confirma transacción
   - Retorna 201 con reserva creada
8. Si hay solapamiento: rollback y retorna error 409 Conflict

### Flujos Cubiertos
#### Flujo Principal (HU-009-UC-001-FP)
1. Cliente reserva en horario libre
2. Validación atómica exitosa
3. Reserva creada con estado "confirmada"

#### Flujos Alternos
**HU-009-UC-001-FA-001: Solapamiento detectado (race condition)**
1. Otra reserva se creó concurrentemente en mismo horario
2. `SELECT ... FOR UPDATE` encuentra conflicto
3. Sistema hace rollback y retorna 409 Conflict

**HU-009-UC-001-FA-002: Espacio inactivo**
1. `space_id` corresponde a espacio con `is_active = false`
2. Sistema retorna error 400 Bad Request

**HU-009-UC-001-FA-003: end_time no posterior a start_time**
1. `end_time` <= `start_time`
2. Sistema retorna error 400 Bad Request

**HU-009-UC-001-FA-004: Espacio no existe**
1. `space_id` no encontrado en `spaces`
2. Sistema retorna error 404 Not Found

#### Flujos de Seguridad
**HU-009-UC-001-FS-001: Usuario no cliente**
1. Administrador intenta crear reserva (aunque técnicamente podría)
2. Sistema verifica rol "cliente" específicamente (HU-009)
3. Retorna 403 Forbidden

**HU-009-UC-001-FS-002: Token inválido**
1. JWT no válido o en `blacklisted_tokens`
2. Sistema retorna 401 Unauthorized

### Postcondiciones
1. Nueva reserva creada en tabla `reservations` con estado "confirmada"
2. El horario queda ocupado para ese espacio
3. Se registra auditoría en `entity_audit_trails`

### Caso de Uso Siguiente
**HU-010-UC-001: Cancelación de Reserva por Cliente**
Justificación: Tras crear una reserva, el usuario podría necesitar cancelarla si cambian sus planes.

---

## HU-010-UC-001: Cancelación de Reserva por Cliente

### Precondiciones
1. Usuario autenticado con rol "cliente"
2. Existe reserva con `id` especificada en `reservations`
3. Reserva tiene `status_id` = "confirmada"
4. Reserva pertenece al usuario (`user_id` coincide)

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Envía `DELETE /api/reservations/{id}` o `POST /api/reservations/{id}/cancel` con JWT

#### Respuestas del Sistema
2. Verifica JWT y extrae `user_id`
3. Busca reserva por `id`
4. Verifica que `user_id` de reserva coincida con usuario autenticado
5. Verifica que `status_id` = "confirmada"
6. Actualiza `status_id` a "cancelada" (consulta `reservation_statuses`)
7. Opcional: registra `cancellation_reason`
8. Retorna confirmación

### Flujos Cubiertos
#### Flujo Principal (HU-010-UC-001-FP)
1. Cliente cancela su propia reserva confirmada
2. Sistema actualiza estado a "cancelada"
3. Retorna éxito

#### Flujos Alternos
**HU-010-UC-001-FA-001: Reserva no confirmada**
1. Reserva ya está cancelada, finalizada, etc.
2. Sistema retorna error 400 Bad Request

**HU-010-UC-001-FA-002: Reserva no encontrada**
1. `id` no existe en `reservations`
2. Sistema retorna error 404 Not Found

**HU-010-UC-001-FA-003: Reserva de otro usuario**
1. Reserva existe pero `user_id` no coincide
2. Sistema retorna error 403 Forbidden (por reglas de negocio)

#### Flujos de Seguridad
**HU-010-UC-001-FS-001: Intento de cancelar reserva ajena**
1. Cliente intenta cancelar reserva de otro usuario
2. Sistema verifica propiedad
3. Retorna 403 Forbidden

### Postcondiciones
1. Reserva actualizada con `status_id` = "cancelada"
2. El horario queda disponible para nuevas reservas
3. Se registra auditoría en `entity_audit_trails`

### Caso de Uso Siguiente
**HU-007-UC-001: Consulta de Listado de Espacios** (para hacer nueva reserva)
Justificación: Tras cancelar una reserva, el usuario podría buscar otro espacio u horario.

---

## HU-010-UC-002: Cancelación de Reserva por Administrador

### Precondiciones
1. Usuario autenticado con rol "administrador"
2. Existe reserva con `id` especificada en `reservations`
3. Reserva tiene `status_id` = "confirmada"

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Administrador envía solicitud de cancelación con JWT

#### Respuestas del Sistema
2. Verifica JWT y rol de administrador
3. Busca reserva por `id`
4. Verifica que `status_id` = "confirmada"
5. Actualiza `status_id` a "cancelada"
6. Opcional: registra `cancellation_reason` y admin como responsable
7. Retorna confirmación

### Flujos Cubiertos
#### Flujo Principal (HU-010-UC-002-FP)
1. Administrador cancela cualquier reserva confirmada
2. Sistema actualiza estado
3. Retorna éxito

#### Flujos Alternos
**HU-010-UC-002-FA-001: Reserva no confirmada**
1. Reserva ya cancelada o finalizada
2. Sistema retorna error 400 Bad Request

**HU-010-UC-002-FA-002: Reserva no existe**
1. `id` no encontrado
2. Sistema retorna error 404 Not Found

#### Flujos de Seguridad
**HU-010-UC-002-FS-001: Usuario no administrador**
1. Cliente intenta usar endpoint de admin
2. Sistema verifica rol
3. Retorna 403 Forbidden

### Postcondiciones
1. Reserva cancelada independientemente de propietario
2. Horario liberado
3. Auditoría registra acción del administrador

### Caso de Uso Siguiente
**HU-007-UC-001: Consulta de Listado de Espacios** (para el cliente afectado)
Justificación: Tras cancelación por admin, el espacio queda disponible para otros.

---

## HU-011-UC-001: Consulta de Espacios Disponibles por Filtros

### Precondiciones
1. (Ninguna específica de autenticación - endpoint público)
2. Existen espacios en tabla `spaces`
3. Existen reservas en tabla `reservations`

### Desarrollo
#### Acciones del Usuario/Cliente API
1. Solicita `GET /api/spaces/available` con parámetro obligatorio `fecha_deseada` (YYYY-MM-DD) y opcional `space_type_id`

#### Respuestas del Sistema
2. Valida que `fecha_deseada` esté presente y tenga formato válido
3. Calcula horario operativo para la fecha (inferido: ej. 08:00-20:00)
4. Consulta espacios que cumplan:
   - `is_active = true`
   - Si se proporciona `space_type_id`, debe coincidir
   - NO están completamente reservados durante todo el horario operativo
5. Usa índices optimizados (`idx_spaces_type_location_capacity`) y posiblemente vista `v_daily_occupation_report`
6. Retorna lista de espacios disponibles

### Flujos Cubiertos
#### Flujo Principal (HU-011-UC-001-FP)
1. Usuario solicita con `fecha_deseada` válida
2. Sistema calcula y retorna espacios con disponibilidad

#### Flujos Alternos
**HU-011-UC-001-FA-001: fecha_deseada faltante**
1. Parámetro obligatorio no presente
2. Sistema retorna error 400 Bad Request

**HU-011-UC-001-FA-002: fecha_deseada con formato inválido**
1. Formato no YYYY-MM-DD
2. Sistema retorna error 400 Bad Request

**HU-011-UC-001-FA-003: space_type_id no existe**
1. Tipo de espacio no encontrado en `space_types`
2. Sistema podría ignorar filtro o retornar error 400

**HU-011-UC-001-FA-004: Ningún espacio disponible**
1. Todos los espacios activos están completamente reservados
2. Sistema retorna lista vacía

#### Flujos de Seguridad
**HU-011-UC-001-FS-001: Ataque por inyección SQL**
1. Parámetros maliciosos en query string
2. Sistema usa parámetros preparados (inferido de buenas prácticas)
3. Previene inyecciones

### Postcondiciones
1. Usuario recibe lista determinista de espacios con disponibilidad
2. La consulta es eficiente gracias a índices y posible vista materializada

### Caso de Uso Siguiente
**HU-008-UC-001: Consulta de Disponibilidad de Espacio** (para un espacio específico)
Justificación: Tras identificar espacios disponibles, el usuario seleccionará uno para ver horarios específicos libres.

---

## Relaciones y Flujo General de Casos de Uso

El flujo típico de un usuario nuevo sería:
1. **HU-001-UC-001** → **HU-002-UC-001** → **HU-003-UC-001** → **HU-011-UC-001** → **HU-008-UC-001** → **HU-009-UC-001** → **HU-010-UC-001**

El flujo de administrador incluiría además:
- **HU-005-UC-001** y **HU-006-UC-001** para gestión de espacios
- **HU-010-UC-002** para cancelación de cualquier reserva

Todos los casos de uso implementan las reglas de negocio especificadas en *Historias de Usuario.md*, utilizan el modelo de datos definido en *entidades.md*, y se alinean con los requisitos técnicos de *AGENCIA DE RESERVAS.pdf*.