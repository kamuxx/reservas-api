# Documento de Deuda Técnica y Pendientes de Desarrollo

Este documento registra las funcionalidades, mejoras o correcciones que se han pospuesto intencionalmente para agilizar el desarrollo de las funcionalidades principales (`Core`), pero que deben ser abordadas posteriormente para completar los requisitos no funcionales, de seguridad o de mantenimiento.

---

## 1. Registro de Auditoría de Login (`login_audit_trails`)

- **Nombre de la técnica:** Logging de seguridad y auditoría.
- **Objetivo:** Mantener un histórico inmutable de todos los intentos de inicio de sesión (exitosos y fallidos) para análisis forense y detección de intrusiones.
- **Descripción:** Implementar la tabla `login_audit_trails` (con campos como IP, User-Agent, Email intentado, Resultado, Fecha) y la lógica en `UserUseCases` para registrar cada intento. Esto permitiría detectar ataques de fuerza bruta.
- **Caso de uso al que pertenece:** HU-003-UC-001: Autenticación de Usuario (Login).
- **Historia de usuario a la que pertenece:** HU-003.
- **Nivel de criticidad:** **Media**. Aunque no bloquea la funcionalidad de negocio (reservas), es un requisito de seguridad estándar importante para un entorno de producción.
