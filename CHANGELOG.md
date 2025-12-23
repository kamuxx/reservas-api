# CHANGELOG

Todas las actualizaciones notables de este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-12-23

### 🚀 Añadido
- **Estructura Base:** Configuración inicial de Laravel 12.
- **Documentación Técnica:** Incorporación de Historias de Usuario (HU), Casos de Uso, Modelo de Entidades y Matriz de Pruebas en carpeta `docs/`.
- **Autenticación:** 
    - `RegisterController` con método `register` funcional.
    - Ruta `api/auth/register` configurada.
- **Testing:** 
    - `TestCase.php` con auto-creación de base de datos SQLite y migraciones automáticas.
    - `RegisterNewUserTest.php` para validación de rutas y creación de usuarios.
- **README:** Creación de documentación profesional para el backend.

### 🔧 Corregido
- **Bugs Técnicos:** 
    - Corregido typo en mensaje de éxito en `RegisterController` ("successcully" -> "successfully").
    - Corregido uso de Faker en tests (campo `phone` usaba `email`).
    - Ajustadas rutas de base de datos en `phpunit.xml` y `.env` para coincidir con el nombre del proyecto `reservas-api`.
- **Git:** Resolución de conflicto de "unrelated histories" al sincronizar con el repositorio remoto.

### ⚙️ Configuración
- Implementación de versionado semántico en `composer.json` (v0.1.0).
- Unificación de `.gitignore` tras conflicto de merge.

---
*Nota: Este proyecto se encuentra actualmente en fase de desarrollo inicial.*
