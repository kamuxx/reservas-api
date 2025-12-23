# Matriz de Pruebas - Sistema de Reserva de Espacios

## Matriz Completa de Casos de Prueba

### HU-001-UC-001: Registro de Nuevo Usuario

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU001-001 | FP | Registro exitoso de nuevo usuario | 1. Sistema operativo<br>2. Tablas user_roles y user_statuses configuradas | name: "Juan Pérez"<br>email: "juan@test.com"<br>phone: "5551234567"<br>password: "Pass123!" | 1. Enviar POST /api/register con datos válidos<br>2. Verificar respuesta | HTTP 201 Created<br>Usuario creado con status pendiente_activacion<br>No incluye password en respuesta | Usuario creado exitosamente |
| TP-HU001-002 | FA-001 | Email ya registrado | 1. Usuario con email "existente@test.com" ya registrado | email: "existente@test.com"<br>otros campos válidos | 1. Enviar POST /api/register con email existente | HTTP 409 Conflict o 400 Bad Request<br>Mensaje indicando email en uso | Sistema previene duplicados |
| TP-HU001-003 | FA-002 | Password no cumple política | - | password: "123" (corta)<br>password: "password" (sin mayúsculas)<br>password: "PASSWORD" (sin minúsculas)<br>password: "Password" (sin números)<br>password: "Password123" (sin especial) | 1. Enviar POST /api/register con cada password inválida | HTTP 400 Bad Request<br>Mensaje específico de error | Valida todos los criterios de seguridad |
| TP-HU001-004 | FA-003 | Campos requeridos faltantes | - | Omisiones de: name, email, phone, password | 1. Enviar POST /api/register con campos faltantes | HTTP 400 Bad Request<br>Mensaje indicando campos requeridos | Todos los campos son obligatorios |
| TP-HU001-005 | FS-001 | Rate limiting registro | - | - | 1. Enviar >10 solicitudes POST /api/register en 1 minuto desde misma IP | Solicitudes 11+ reciben HTTP 429 Too Many Requests | Previene registros masivos |

### HU-002-UC-001: Activación de Cuenta de Usuario

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU002-001 | FP | Activación exitosa con token válido | 1. Usuario con status pendiente_activacion<br>2. Token válido generado | token: "abc123-token-valido" | 1. Acceder GET /api/activate/{token}<br>2. Verificar respuesta | HTTP 200 OK<br>Usuario actualizado a status activo<br>Token marcado como usado | Activación completa |
| TP-HU002-002 | FA-001 | Token expirado | 1. Token con expires_at en pasado | token expirado | 1. Acceder con token expirado | HTTP 400/410<br>Mensaje "Token expirado" | Rechaza tokens vencidos |
| TP-HU002-003 | FA-002 | Token ya utilizado | 1. Token con used_at no null | token ya usado | 1. Acceder con token ya usado | HTTP 400<br>Mensaje "Token ya utilizado" | Token de un solo uso |
| TP-HU002-004 | FA-003 | Usuario no pendiente | 1. Usuario ya activo/bloqueado | token válido para usuario activo | 1. Acceder con token para usuario no pendiente | HTTP 400<br>Mensaje "Usuario no requiere activación" | Solo activa usuarios pendientes |
| TP-HU002-005 | FA-004 | Token no existe | - | token: "token-inexistente" | 1. Acceder con token inexistente | HTTP 404 Not Found | Valida existencia del token |
| TP-HU002-006 | FS-001 | Fuerza bruta tokens | - | - | 1. Intentar >20 activaciones con tokens inválidos desde misma IP | HTTP 429 o bloqueo temporal<br>Registro en audit trail | Protección contra fuerza bruta |

### HU-003-UC-001: Autenticación de Usuario (Login)

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU003-001 | FP | Login exitoso | 1. Usuario activo<br>2. Credenciales correctas | email: "usuario@test.com"<br>password: "Pass123!" | 1. Enviar POST /api/login con credenciales<br>2. Verificar respuesta | HTTP 200 OK<br>Token JWT válido retornado<br>last_login_at actualizado<br>Audit trail registrado | Autenticación exitosa |
| TP-HU003-002 | FA-001 | Usuario no existe | - | email: "noexiste@test.com"<br>password: cualquier | 1. Enviar POST /api/login | HTTP 401 Unauthorized<br>Mensaje genérico<br>Audit trail con 'failed_user_not_found' | No revela existencia de usuario |
| TP-HU003-003 | FA-002 | Password incorrecta | 1. Usuario activo existe | email válido<br>password: "Incorrecta123!" | 1. Enviar POST /api/login | HTTP 401 Unauthorized<br>Mensaje genérico<br>Audit trail con 'failed_password' | No revela qué falló específicamente |
| TP-HU003-004 | FA-003 | Usuario no activo | 1. Usuario con status pendiente/bloqueado | credenciales correctas para usuario inactivo | 1. Enviar POST /api/login | HTTP 401/403<br>Audit trail con status apropiado | Solo usuarios activos pueden login |
| TP-HU003-005 | FS-001 | Fuerza bruta desde IP | - | - | 1. Realizar >5 intentos fallidos desde misma IP en 5 minutos | Intento 6+ recibe HTTP 429 o bloqueo<br>Registro en audit trail | Protección contra ataques |

### HU-004-UC-001: Cierre de Sesión (Logout)

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU004-001 | FP | Logout exitoso | 1. JWT válido con claim jti | JWT válido | 1. Enviar POST /api/logout con Authorization header<br>2. Verificar token en blacklist | HTTP 200 OK<br>jti agregado a blacklisted_tokens | Invalida sesión correctamente |
| TP-HU004-002 | FA-001 | Token ya expirado | 1. JWT con exp en pasado | JWT expirado | 1. Enviar POST /api/logout con token expirado | HTTP 401 Unauthorized o 200 (según implementación) | Manejo consistente de tokens vencidos |
| TP-HU004-003 | FA-002 | Token sin claim jti | 1. JWT sin claim jti | JWT sin jti | 1. Enviar POST /api/logout | HTTP 400 Bad Request<br>Mensaje "Token sin identificador" | Requiere jti para blacklist |
| TP-HU004-004 | FS-001 | Token ya en blacklist | 1. jti ya en blacklisted_tokens | JWT ya invalidado | 1. Enviar POST /api/logout con token ya en blacklist | HTTP 200 OK (idempotente) o 400 | No permite reutilización |

### HU-005-UC-001: Creación de Espacio (Admin)

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU005-001 | FP | Creación exitosa espacio | 1. Usuario administrador autenticado<br>2. space_types existentes | name: "Sala A"<br>description: "Sala grande"<br>capacity: 20<br>is_active: true<br>space_type_id: 1 | 1. Enviar POST /api/spaces con JWT admin<br>2. Verificar creación | HTTP 201 Created<br>Espacio creado en BD<br>Audit trail registrado | Admin puede crear espacios |
| TP-HU005-002 | FA-001 | Nombre duplicado | 1. Espacio "Sala A" ya existe | name: "Sala A" (duplicado) | 1. Enviar POST /api/spaces con nombre existente | HTTP 409 Conflict<br>Mensaje "Nombre ya en uso" | Previene duplicados de nombre |
| TP-HU005-003 | FA-002 | Capacity inválida | - | capacity: 0<br>capacity: -5 | 1. Enviar POST /api/spaces con capacity inválida | HTTP 400 Bad Request<br>Mensaje "Capacity debe ser >0" | Valida capacity positiva |
| TP-HU005-004 | FA-003 | space_type_id no existe | - | space_type_id: 999 | 1. Enviar POST /api/spaces con type inexistente | HTTP 400/404<br>Mensaje "Tipo de espacio no válido" | Valida referencias |
| TP-HU005-005 | FS-001 | Usuario no administrador | 1. Usuario cliente autenticado | datos válidos | 1. Enviar POST /api/spaces con JWT cliente | HTTP 403 Forbidden | Solo admins pueden crear |
| TP-HU005-006 | FS-002 | JWT inválido | - | JWT inválido o expirado | 1. Enviar POST /api/spaces sin autenticación válida | HTTP 401 Unauthorized | Requiere autenticación |

### HU-006-UC-001: Modificación de Espacio (Admin)

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU006-001 | FP | Modificación exitosa | 1. Admin autenticado<br>2. Espacio existente | id: 1<br>name: "Sala A Modificado"<br>capacity: 25 | 1. Enviar PUT /api/spaces/1 con cambios<br>2. Verificar actualización | HTTP 200 OK<br>Espacio actualizado<br>updated_at modificado | Admin puede modificar espacios |
| TP-HU006-002 | FA-001 | Nombre duplicado al cambiar | 1. Espacio "Sala B" existe<br>2. Modificando espacio 1 | name: "Sala B" (nombre de otro espacio) | 1. Enviar PUT /api/spaces/1 con nombre duplicado | HTTP 409 Conflict<br>Mensaje "Nombre ya en uso" | Previene nombres duplicados |
| TP-HU006-003 | FA-002 | Espacio no encontrado | - | id: 999 (inexistente) | 1. Enviar PUT /api/spaces/999 | HTTP 404 Not Found | Valida existencia del espacio |
| TP-HU006-004 | FS-001 | Usuario no administrador | 1. Cliente autenticado<br>2. Espacio existente | - | 1. Cliente intenta PUT /api/spaces/1 | HTTP 403 Forbidden | Solo admins pueden modificar |

### HU-007-UC-001: Consulta de Listado de Espacios

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU007-001 | FP | Listado exitoso (usuario no autenticado) | 1. Espacios activos en BD | - | 1. Enviar GET /api/spaces sin autenticación | HTTP 200 OK<br>Lista solo espacios con is_active=true<br>Paginación aplicada | Muestra espacios públicos |
| TP-HU007-002 | FP | Listado admin ve todos | 1. Admin autenticado<br>2. Espacios activos e inactivos | - | 1. Enviar GET /api/spaces con JWT admin | HTTP 200 OK<br>Lista todos los espacios (activos e inactivos) | Admin ve todos los espacios |
| TP-HU007-003 | FA-001 | Filtros sin resultados | 1. Espacios existentes | capacity: 100 (mínimo alto)<br>space_type_id: 99 (inexistente) | 1. Enviar GET /api/spaces?capacity=100<br>2. Enviar GET /api/spaces?space_type_id=99 | HTTP 200 OK<br>Lista vacía en ambos casos | Maneja conjuntos vacíos |
| TP-HU007-004 | FA-002 | Parámetros inválidos | - | capacity: "abc" (no numérico)<br>page: -1 (negativo) | 1. Enviar GET /api/spaces?capacity=abc<br>2. Enviar GET /api/spaces?page=-1 | HTTP 400 Bad Request<br>Mensajes de error apropiados | Valida parámetros |
| TP-HU007-005 | FS-001 | Cliente no ve inactivos | 1. Espacios inactivos existentes<br>2. Cliente autenticado | - | 1. Cliente envía GET /api/spaces | No incluye espacios con is_active=false | Filtro automático aplicado |

### HU-007-UC-002: Consulta de Detalle de Espacio

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU007-006 | FP | Detalle espacio activo (cualquier usuario) | 1. Espacio con is_active=true | id: 1 | 1. Enviar GET /api/spaces/1 sin autenticación | HTTP 200 OK<br>Detalles completos del espacio | Acceso público a activos |
| TP-HU007-007 | FA-001 | Espacio inactivo, usuario no admin | 1. Espacio con is_active=false<br>2. Cliente o no autenticado | id: 2 (inactivo) | 1. GET /api/spaces/2 como cliente/no auth | HTTP 404 Not Found (aunque exista) | Oculta existencia de inactivos |
| TP-HU007-008 | FA-002 | Espacio no existe | - | id: 999 | 1. GET /api/spaces/999 | HTTP 404 Not Found | Manejo consistente |
| TP-HU007-009 | FS-001 | Admin ve espacio inactivo | 1. Espacio inactivo<br>2. Admin autenticado | id: 2 (inactivo) | 1. GET /api/spaces/2 como admin | HTTP 200 OK<br>Detalles del espacio inactivo | Admin ve todos |

### HU-008-UC-001: Consulta de Disponibilidad de Espacio

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU008-001 | FP | Consulta disponibilidad exitosa | 1. Usuario autenticado<br>2. Espacio activo<br>3. Reservas existentes | id: 1<br>start_date: "2024-01-15"<br>end_date: "2024-01-20" | 1. GET /api/spaces/1/availability con JWT | HTTP 200 OK<br>Lista de bloques ocupados en rango | Retorna disponibilidad precisa |
| TP-HU008-002 | FA-001 | Fechas inválidas | - | start_date: "2024-01-20"<br>end_date: "2024-01-15" (inverso) | 1. GET /api/spaces/1/availability?start_date=2024-01-20&end_date=2024-01-15 | HTTP 400 Bad Request<br>Mensaje "start_date debe ser <= end_date" | Valida rango temporal |
| TP-HU008-003 | FA-002 | Espacio no encontrado/inactivo | 1. Espacio inactivo o inexistente<br>2. Usuario cliente | id: 999 o id inactivo | 1. GET /api/spaces/999/availability | HTTP 404 Not Found | Valida existencia y acceso |
| TP-HU008-004 | FA-003 | Sin reservas en rango | 1. Espacio activo<br>2. Sin reservas en rango | id: 1<br>rango sin reservas | 1. GET disponibilidad para rango vacío | HTTP 200 OK<br>Lista vacía | Maneja rangos sin reservas |
| TP-HU008-005 | FS-001 | Usuario no autenticado | - | - | 1. GET /api/spaces/1/availability sin JWT | HTTP 401 Unauthorized | Requiere autenticación |

### HU-009-UC-001: Creación de Reserva con Validación Atómica

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU009-001 | FP | Reserva exitosa | 1. Cliente autenticado<br>2. Espacio activo<br>3. Horario disponible | space_id: 1<br>event_name: "Reunión"<br>start_time: "2024-01-15 10:00"<br>end_time: "2024-01-15 12:00" | 1. POST /api/reservations con JWT cliente | HTTP 201 Created<br>Reserva creada con status confirmada<br>Transacción atómica exitosa | Reserva válida creada |
| TP-HU009-002 | FA-001 | Race condition detonada | 1. Dos clientes intentan reservar mismo horario simultáneamente | mismos datos para ambos | 1. Ejecutar dos solicitudes concurrentes de reserva | Una recibe 201, otra 409 Conflict<br>Transacciones mantienen consistencia | Previene doble reserva |
| TP-HU009-003 | FA-002 | Espacio inactivo | 1. Espacio con is_active=false | space_id de espacio inactivo | 1. POST /api/reservations para espacio inactivo | HTTP 400 Bad Request<br>Mensaje "Espacio no disponible" | Solo espacios activos |
| TP-HU009-004 | FA-003 | end_time no posterior | - | start_time: "2024-01-15 12:00"<br>end_time: "2024-01-15 10:00" | 1. POST /api/reservations con end_time <= start_time | HTTP 400 Bad Request<br>Mensaje "end_time debe ser posterior" | Valida lógica temporal |
| TP-HU009-005 | FA-004 | Espacio no existe | - | space_id: 999 | 1. POST /api/reservations con space_id inexistente | HTTP 404 Not Found | Valida existencia |
| TP-HU009-006 | FS-001 | Usuario no cliente | 1. Admin autenticado | datos válidos | 1. Admin intenta POST /api/reservations | HTTP 403 Forbidden | Solo clientes pueden reservar |
| TP-HU009-007 | FS-002 | Token inválido | - | JWT inválido | 1. POST /api/reservations sin autenticación válida | HTTP 401 Unauthorized | Requiere autenticación |

### HU-010-UC-001: Cancelación de Reserva por Cliente

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU010-001 | FP | Cancelación exitosa por cliente | 1. Cliente autenticado<br>2. Reserva confirmada del cliente | id: 1 (reserva del cliente) | 1. DELETE /api/reservations/1 con JWT propietario | HTTP 200 OK<br>Reserva actualizada a status cancelada<br>Horario liberado | Cliente cancela su reserva |
| TP-HU010-002 | FA-001 | Reserva no confirmada | 1. Reserva ya cancelada/finalizada | id de reserva no confirmada | 1. Intentar cancelar reserva no confirmada | HTTP 400 Bad Request<br>Mensaje "Reserva no cancelable" | Solo cancelable si confirmada |
| TP-HU010-003 | FA-002 | Reserva no existe | - | id: 999 | 1. DELETE /api/reservations/999 | HTTP 404 Not Found | Valida existencia |
| TP-HU010-004 | FA-003 | Reserva de otro usuario | 1. Reserva confirmada de otro cliente | id de reserva ajena | 1. Cliente intenta cancelar reserva ajena | HTTP 403 Forbidden<br>Mensaje "No autorizado" | Solo propietario puede cancelar |
| TP-HU010-005 | FS-001 | Intento cancelación ajena | 1. Cliente intenta cancelar reserva de otro | - | 1. Varios intentos de cancelar reservas ajenas | Siempre 403 Forbidden<br>Posible registro en audit trail | Protección robusta |

### HU-010-UC-002: Cancelación de Reserva por Administrador

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU010-006 | FP | Cancelación admin exitosa | 1. Admin autenticado<br>2. Reserva confirmada cualquiera | id: 1 (cualquier reserva) | 1. Admin envía DELETE /api/reservations/1 | HTTP 200 OK<br>Reserva cancelada independientemente de dueño | Admin cancela cualquier reserva |
| TP-HU010-007 | FA-001 | Reserva no confirmada (admin) | 1. Reserva ya cancelada | id de reserva no confirmada | 1. Admin intenta cancelar reserva no confirmada | HTTP 400 Bad Request<br>Mensaje "Reserva no cancelable" | Mismas reglas de estado |
| TP-HU010-008 | FA-002 | Reserva no existe (admin) | - | id: 999 | 1. Admin intenta cancelar reserva inexistente | HTTP 404 Not Found | Valida existencia |
| TP-HU010-009 | FS-001 | Usuario no administrador | 1. Cliente autenticado | - | 1. Cliente intenta usar endpoint admin | HTTP 403 Forbidden<br>Endpoint no accesible | Solo admins |

### HU-011-UC-001: Consulta de Espacios Disponibles por Filtros

| ID Prueba | Flujo | Descripción | Precondiciones | Datos de Entrada | Pasos de Ejecución | Resultado Esperado | Criterios de Aceptación |
|-----------|-------|-------------|----------------|------------------|-------------------|-------------------|-------------------------|
| TP-HU011-001 | FP | Consulta exitosa con fecha | 1. Espacios activos<br>2. Reservas existentes | fecha_deseada: "2024-01-15" | 1. GET /api/spaces/available?fecha_deseada=2024-01-15 | HTTP 200 OK<br>Lista de espacios con disponibilidad | Retorna espacios disponibles |
| TP-HU011-002 | FA-001 | fecha_deseada faltante | - | - | 1. GET /api/spaces/available sin parámetro | HTTP 400 Bad Request<br>Mensaje "fecha_deseada requerida" | Parámetro obligatorio |
| TP-HU011-003 | FA-002 | Formato fecha inválido | - | fecha_deseada: "15-01-2024"<br>fecha_deseada: "fecha" | 1. GET /api/spaces/available?fecha_deseada=15-01-2024 | HTTP 400 Bad Request<br>Mensaje "Formato YYYY-MM-DD requerido" | Valida formato |
| TP-HU011-004 | FA-003 | space_type_id no existe | - | space_type_id: 999 | 1. GET /api/spaces/available?fecha_deseada=2024-01-15&space_type_id=999 | HTTP 400 Bad Request o ignora filtro | Manejo consistente |
| TP-HU011-005 | FA-004 | Ningún espacio disponible | 1. Todos los espacios reservados en fecha | fecha_deseada: "2024-01-15" | 1. GET /api/spaces/available para fecha totalmente ocupada | HTTP 200 OK<br>Lista vacía | Maneja caso sin disponibilidad |
| TP-HU011-006 | FS-001 | Inyección SQL intento | - | fecha_deseada: "2024-01-15'; DROP TABLE users; --" | 1. GET con parámetro malicioso | HTTP 400 Bad Request<br>Consulta segura, no ejecuta SQL | Previene inyecciones |

---

## Resumen de Cobertura

**Total Casos de Prueba: 58**

**Cobertura por Caso de Uso:**
- HU-001-UC-001: 5 pruebas
- HU-002-UC-001: 6 pruebas
- HU-003-UC-001: 5 pruebas
- HU-004-UC-001: 4 pruebas
- HU-005-UC-001: 6 pruebas
- HU-006-UC-001: 4 pruebas
- HU-007-UC-001: 5 pruebas
- HU-007-UC-002: 4 pruebas
- HU-008-UC-001: 5 pruebas
- HU-009-UC-001: 7 pruebas
- HU-010-UC-001: 5 pruebas
- HU-010-UC-002: 4 pruebas
- HU-011-UC-001: 6 pruebas

**Tipos de Prueba Cubiertos:**
- Funcionalidad principal (Flujo Principal)
- Validación de datos (Flujos Alternos)
- Seguridad y autorización (Flujos de Seguridad)
- Casos de error y excepciones
- Pruebas de concurrencia (race conditions)
- Pruebas de rendimiento (rate limiting)

**Criterios de Completitud:**
- ✅ Todos los casos de uso cubiertos
- ✅ Todos los flujos principales cubiertos
- ✅ Todos los flujos alternativos cubiertos
- ✅ Todos los flujos de seguridad cubiertos
- ✅ Datos de prueba específicos para cada escenario
- ✅ Criterios de aceptación claros y verificables