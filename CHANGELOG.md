# CHANGELOG

Todas las actualizaciones notables de este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.6.0] - 2025-12-30

### 🚀 Añadido
- **Gestión de Espacios - Modificación (HU-006):** Implementación de la actualización de espacios para administradores.
    - Endpoints: `PUT/PATCH api/spaces/{uuid}`.
    - Validación: `UpdateSpaceRequest` con soporte para actualizaciones parciales (`sometimes`) y validación de unicidad de nombre ignorando el registro actual.
    - Testing: Suite completa en `UpdateSpaceTest` cubriendo:
        - Actualización exitosa con datos válidos.
        - Prevención de nombres duplicados (FA-001).
        - Manejo de espacios no encontrados (404) (FA-002).
        - Restricción de acceso para no administradores (403) (FS-001).
        - Validación de tipos de datos y existencia de llaves foráneas.
        - Mantenimiento del nombre original sin conflicto.
    - Cobertura: Configuración de reporte HTML de cobertura habilitada en `phpunit.xml`.

## [0.5.0] - 2025-12-29

### 🚀 Añadido
- **Gestión de Espacios (HU-005):** Implementación completa de la creación de espacios por administradores.
    - Endpoints: `POST api/spaces`.
    - Middleware: `RoleMiddleware` para proteger rutas administrativas.
    - Modelos: `Space` con soporte para UUID y soft deletes (preparado).
    - Testing: Suite completa `RegisterNewSpaceTest` cubriendo validaciones, seguridad y flujos de éxito (TP-HU005-001 al TP-HU005-006).
    - Repositorio: `SpaceRepository` con patrón de abstracción y persistencia atómica.
    - Auditoría: Registro automático en `entity_audit_trails` para cada creación de espacio.
- **Seguridad:**
    - Middleware `isAdmin` para restricción de acceso basado en roles.
    - Protección de rutas de gestión de espacios.

### ⚡ Optimizado
- **Testing:** Mejora en `TestCase` para incluir seeders de `SpaceType` y `PricingRule`.

## [0.4.0] - 2025-12-28

### 🚀 Añadido
- **Autenticación Completa (HU-003, HU-004):** 
    - Endpoints funcionales para Inicio de Sesión (`POST api/auth/login`) y Cierre de Sesión (`POST api/auth/logout`).
    - Integración de **JWT (JSON Web Tokens)** mediante `tymon/jwt-auth` para manejo de sesiones seguras.
- **Seguridad y Control de Acceso:**
    - **Middleware `EnsureUserIsActive`:** Restricción de acceso que impide a usuarios con estatus `pending` utilizar endpoints protegidos.
    - Configuración de guardianes (guards) api/sanctum y proveedores de autenticación en `config/auth.php`.
- **Documentación Interactiva:**
    - Implementación de **Swagger UI** accesible en `/api/docs`.
    - Archivo de definición OpenAPI en `public/api-docs.yaml` actualizado con todas las rutas actuales.
    - Vista dedicada `resources/views/swagger.blade.php`.
- **Testing:**
    - Nuevas suites de pruebas `LoginTest.php` y `LogoutTest.php` cubriendo casos de éxito, credenciales inválidas, usuarios inactivos y estructura de tokens.

### ⚡ Optimizado
- **Modelos y Fábricas:**
    - Actualización de `UserFactory` para generar usuarios con estados y roles consistentes.
    - Mejoras en el modelo `User` para integración con JWT Subject.
- **Configuración:**
    - Publicación y ajuste de configuración de JWT (`config/jwt.php`).


## [0.3.0] - 2025-12-26

### 🚀 Añadido
- **Activación de Cuenta (HU-002):** Implementación completa del flujo de activación de cuentas.
    - Endpoints: `POST api/auth/activate` (con soporte para GET y código en query string).
    - Lógica de Negocio: Validación estricta de tokens (existencia, expiración, uso previo, código secundario y estado del usuario) en `UserUseCases`.
    - Seguridad: Manejo de excepciones HTTP específicas (`NotFoundHttpException`, `UnprocessableEntityHttpException`) para respuestas 404/422 precisas.
- **Testing:** Suite exhaustiva `ValidateAccountTest` con 9 escenarios de prueba (éxito, token inválido, expirado, usado, código incorrecto, etc.).
- **Infraestructura:** Corrección crítica en migración de `users` para manejar índices únicos y prevenir conflictos de integridad (`Duplicate entry`).

### ⚡ Optimizado
- **Controlador Base:** Método `clientError` para manejo estandarizado de errores 4xx.
- **Modelos:** Métodos `isExpired`, `isUsed`, `isValidCode` en `UserActivationToken` encapsulando lógica de dominio.
- **Request Validation:** Validaciones numéricas y de longitud precisas en `ValidateAccountRequest`.

## [0.2.0] - 2025-12-24

### 🚀 Añadido
- **Manejo de Errores:** Implementación de `serverError` y `writeLogError` en `Controller` base para respuestas JSON estandarizadas (500) y logging estructurado.
- **Modelo User:** Asignación automática de UUID, Rol (`user`) y Estatus (`pending`) mediante Eloquent Model Hooks.
- **Testing Feature:** Suite completa de pruebas en `RegisterNewUserTest` cubriendo validación de JSON, persistencia y casos extremos (email duplicado, formato inválido, seguridad).
- **Testing Unitario:** Nuevo test `tests/Unit/UserTest.php` para validar la lógica interna del modelo `User`, generación de UUID y relaciones `role`/`status` por defecto.

### ⚡ Optimizado
- **Rendimiento de Tests:** Migración completa a base de datos en memoria (`:memory:`) para ejecución rápida.
- **Consistencia Documental:** Estandarización de términos en documentación (`pending`/`active`) para alinearse perfectamente con el código, seeders y modelos.
- **Limpieza de Controladores:** Refactorización de `RegisterController` para delegar lógica de error al controlador base.
- **Repositorios:** Desacoplamiento de `UserRepository` para simplificar la lógica de inserción.

### 🔧 Corregido
- **Migraciones:** Eliminada definición redundante de clave primaria en migraciones para compatibilidad con SQLite estricto.
- **Bug en Modelo User:** Corregida lógica en `User::booted` para buscar roles/estatus de manera segura.
- **Estado Inicial de Usuario:** Corregido el estado por defecto de `active` a `pending` en el modelo `User` (HU-001).
- **Pruebas:** Solucionado fallo en ejecución de seeders en `TestCase` y configuración de importaciones en Tests Unitarios.

## [0.1.0] - 2025-12-23

### 🚀 Añadido
- **Estructura Base:** Configuración inicial de Laravel 12.
- **Documentación Técnica:** Incorporación de Historias de Usuario (HU), Casos de Uso, Modelo de Entidades y Matriz de Pruebas en carpeta `docs/`.
- **Arquitectura:** Definición formal de la arquitectura en capas y patrones de diseño (Clean Architecture, Repositories, Use Cases) en el README.
- **Autenticación:** 
    - `RegisterController` con método `register` funcional.
    - Ruta `api/auth/register` configurada.
- **Testing:** 
    - `TestCase.php` con auto-creación de base de datos SQLite y migraciones automáticas.
    - `RegisterNewUserTest.php` para validación de rutas y creación de usuarios.
- **Modelos Core:** Configuración de modelos `Status` y `Role` con soporte nativo para UUIDs y asignación masiva.

### ⚡ Optimizado
- **Entorno de Pruebas:** Migración de base de datos de tests a SQLite `:memory:` para eliminar errores de bloqueo de archivos ("Device or resource busy") en entornos Windows.
- **Infraestructura de Tests:** Automatización de carga de tablas maestras (Roles y Status) en `TestCase.php` para asegurar consistencia en todas las pruebas.
- **Capa de Persistencia:** Refactorización de `StatusSeeder` y `RoleSeeder` utilizando el modelo Eloquent para una inserción de datos más robusta.
- **Limpieza de Código:** Eliminación de funciones de depuración (`dump`) en `UserRepository` y `UserUseCases`.

### 🔧 Corregido
- **Bugs Técnicos:** 
    - Corregido typo en mensaje de éxito en `RegisterController` ("successcully" -> "successfully").
    - Corregido uso de Faker en tests (campo `phone` usaba `email`).
    - Ajustadas rutas de base de datos en `phpunit.xml` y `.env` para coincidir con el nombre del proyecto `reservas-api`.
- **Git:** Resolución de conflicto de "unrelated histories" al sincronizar con el repositorio remoto.
- **Validación de Tests:** Corregida la validación de rutas en `RegisterNewUserTest` para verificar existencia (no 404) en lugar de éxito prematuro sin datos.

### ⚙️ Configuración
- Implementación de versionado semántico en `composer.json` (v0.1.0).
- Unificación de `.gitignore` tras conflicto de merge.

---
*Nota: Este proyecto se encuentra actualmente en fase de desarrollo inicial.*
