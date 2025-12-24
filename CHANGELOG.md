# CHANGELOG

Todas las actualizaciones notables de este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### 🚀 Añadido
- **Manejo de Errores:** Implementación de `serverError` y `writeLogError` en `Controller` base para respuestas JSON estandarizadas (500) y logging estructurado con trazas de pila filtradas (excluyendo vendor).
- **Modelo User:** Asignación automática de UUID, Rol ('user') y Estatus ('active') mediante Eloquent Model Hooks (`booted`). Relaciones `role` y `status` definidas.

### ⚡ Optimizado
- **Rendimiento de Tests:** Migración completa a base de datos en memoria (`:memory:`) para ejecución rápida de pruebas unitarias y de integración.
- **Limpieza de Controladores:** Refactorización de `RegisterController` para delegar lógica de error al controlador base.
- **Repositorios:** Desacoplamiento de `UserRepository` de `BaseRepository` para simplificar la lógica de inserción.

### 🔧 Corregido
- **Migraciones:** Eliminada definición redundante de clave primaria (`->primary()`) en columnas `id()` que causaba errores en SQLite estricto.
- **Bug en Modelo User:** Corregida lógica en `User::booted` para buscar roles/estatus usando `where` en lugar de `find` (que causaba errores al acceder propiedades en null).
- **Pruebas:** Solucionado fallo en `RegisterNewUserTest` asegurando la ejecución previa de Seeders en `TestCase`.

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
