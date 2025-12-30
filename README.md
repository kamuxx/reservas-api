# 🏢 Sistema de Reserva de Espacios - API Backend

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JWT](https://img.shields.io/badge/JWT-black?style=for-the-badge&logo=JSON%20web%20tokens)

## 🎯 Objetivo del Proyecto

Este proyecto constituye el núcleo técnico (Backend) para un sistema integral de gestión y reserva de espacios (salas de reuniones, auditorios, oficinas, etc.). 

El objetivo principal es proporcionar una **API RESTful robusta, escalable y segura** construida con Laravel, diseñada para gestionar de forma eficiente la disponibilidad de recursos, la seguridad mediante autenticación JWT y la integridad de las reservas en tiempo real. El desarrollo se basa estrictamente en los artefactos de diseño técnico (HU, Casos de Uso y Modelo Entidad-Relación) proporcionados en la fase de planeación.

---

## 🛠 Tecnologías y Versiones

*   **Framework:** Laravel 10.x
*   **Lenguaje:** PHP 8.2+
*   **Base de Datos:** MySQL 8.0+
*   **Gestor de Dependencias:** Composer 2.x
*   **Autenticación:** JWT (JSON Web Tokens)
*   **Testing:** PHPUnit / Pest
*   **Documentación:** Swagger UI (OpenAPI 3.0)

---

## 🏗️ Arquitectura y Patrones de Diseño

*   **Patrón Arquitectónico:** Arquitectura en Capas (Layered Architecture) con enfoque en *Clean Architecture*.
*   **Capas Definidas:**
    *   **Capa de Presentación:** Controladores delgados (Slim Controllers) para manejo de Requests/Responses.
    *   **Capa de Aplicación (Use Cases):** Clases dedicadas a orquestar la lógica de negocio pura.
    *   **Capa de Infraestructura (Repositories):** Implementación del patrón *Repository* para la abstracción de la persistencia.
*   **Patrones Adicionales:**
    *   **Inyección de Dependencias:** Desacoplamiento de componentes mediante el contenedor de servicios de Laravel.
    *   **Data Transfer Objects (DTOs):** (Opcional según implementación futura) para el paso de datos entre capas.
    *   **Contract-Based Programming:** Uso de interfaces en repositorios para asegurar la flexibilidad y testeabilidad.

---

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de tener instalado lo siguiente:

| Software | Comando de Verificación |
| :--- | :--- |
| **PHP** | `php -v` (Debe ser >= 8.2) |
| **Composer** | `composer -v` |
| **MySQL** | `mysql --version` |
| **Git** | `git --version` |

---

## 🚀 Instalación y Configuración Local

Sigue estos pasos secuenciales para configurar el entorno de desarrollo:

### 1. Clonar el repositorio
```bash
git clone https://github.com/kamuxx/reservas-api.git
cd reservas-api
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
php artisan key:generate
```
> **Nota:** Edita el archivo `.env` y configura las credenciales de tu base de datos local.

### 4. Configurar Base de Datos
Crea una base de datos en MySQL llamada `space_booking_backend`.

### 5. Ejecutar Migraciones y Seeders
```bash
php artisan migrate --seed
```

### 6. Configurar JWT
```bash
php artisan jwt:secret
```

### 7. Iniciar Servidor
```bash
php artisan serve
```
La API estará disponible en `http://localhost:8000`.

La API estará disponible en `http://localhost:8000`.

### 8. Documentación de la API (Swagger)
Para visualizar la documentación interactiva de los endpoints, asegúrate de que el servidor esté corriendo (Paso 7) y visita:

> **URL:** `http://localhost:8000/api/docs`

Esta interfaz te permitirá explorar los esquemas de datos y probar los endpoints directamente desde el navegador.

### 9. Cobertura de Código (Opcional)
Para generar reportes de cobertura de código, es necesario tener instalada la extensión **Xdebug** o **PCOV** en PHP.

#### **Guía Genérica de Instalación**
1.  Descarga la extensión correspondiente a tu versión de PHP y arquitectura (x64/x86).
2.  Copia el archivo `.dll` (Windows) o `.so` (Linux) en la carpeta de extensiones de PHP (`ext/`).
3.  Habilita la extensión en tu archivo `php.ini`:
    ```ini
    ; Para Xdebug 3.x
    zend_extension=xdebug
    xdebug.mode=coverage
    
    ; Para PCOV
    extension=pcov
    ```
4.  Reinicia tu servidor web o servicio PHP.

#### **Ejemplo: Windows con Laragon**
Si utilizas Laragon, sigue estos pasos específicos:
1.  Ubica tu carpeta de extensiones, por ejemplo: `C:\laragon\bin\php\php-x.x.x-Win32-vs17-x64\ext`.
2.  Asegúrate de tener el archivo `php_xdebug.dll` en esa carpeta.
3.  Desde el panel de Laragon, ve a **PHP > php.ini** y añade al final:
    ```ini
    [xdebug]
    zend_extension="C:\laragon\bin\php\php-x.x.x-Win32-vs17-x64\ext\php_xdebug.dll"
    xdebug.mode=coverage
    ```
4.  Haz clic en **"Stop"** y luego en **"Start"** en Laragon para aplicar los cambios.

---

## 🏗 Funcionalidades del Backend (Dominio de Negocio)

### **🔐 Dominio de Autenticación**
*   **Registro de Usuarios:** Validación estricta de datos y creación de perfiles.
*   **Activación:** Sistema de tokens para verificación de cuentas.
*   **JWT Auth:** Login/Logout seguro con gestión de tiempo de vida de tokens.
*   **Protección:** Middlewares especializados para resguardar rutas privadas.

### **🏢 Dominio de Espacios**
*   **Gestión Administrativa:** CRUD completo de espacios con metadatos técnicos.
*   **Catálogo Público:** Consultas optimizadas con filtros por capacidad, tipo y ubicación.
*   **Disponibilidad:** Motor de cálculo de estados basado en cronogramas.

### **📅 Dominio de Reservas**
*   **Reserva Atómica:** Sistema de creación con validación de concurrencia para evitar colisiones.
*   **Ciclo de Vida:** Flujos de cancelación, confirmación y consulta de historial.
*   **Sincronización:** Consulta de disponibilidad en tiempo real mediante lógica de negocio en BD.

---

## ⏳ Estado de Implementación

Actualmente el proyecto se encuentra en su **etapa de desarrollo activo (40% implementado)**.

*   ✅ **Estructura Base** - Configuración inicial del framework y rutas base.
*   ✅ **HU-001 a HU-004** - Implementación del Sistema de Autenticación JWT (Registro, Activación, Login, Logout).
*   ✅ **HU-005** - Módulo de Creación de Espacios (Admin).
*   ✅ **HU-006** - Módulo de Modificación de Espacios (Admin).
*   ⏳ **HU-007 a HU-008** - Consulta de Espacios y Disponibilidad.
*   ⏳ **HU-009 a HU-011** - Motor de Reservas Atómicas y Disponibilidad.
*   ✅ **🧪 Suite de Tests** - Implementación de pruebas unitarias y de integración para Auth y Espacios.
*   ✅ **📚 Swagger UI** - Documentación interactiva de endpoints de Auth y Espacios implementados.
*   ✅ **🔒 RBAC** - Control de acceso basado en roles (Admin/Cliente) para creación y modificación de espacios.
*   ⏳ **📊 Reportes** - Vistas SQL optimizadas para analítica de uso.

---

## 🗄️ Base de Datos

*   **Motor:** MySQL 8.0+
*   **Collation:** `utf8mb4_unicode_ci`
*   **Estrategia de Desarrollo:**
    *   **Migraciones:** Estructuradas por niveles (Core -> Entidades -> Relaciones -> Vistas).
    *   **Seeders:** Generación de catálogos iniciales y datos de prueba.
    *   **Optimización:** Uso de índices compuestos en tablas de alta concurrencia (Reservas).

---

## 🧪 Ejecución de Pruebas

El proyecto sigue una metodología de desarrollo guiada por pruebas (TDD).

```bash
# Ejecutar toda la suite de pruebas
php artisan test

# Ejecutar tests específicos por Historia de Usuario
php artisan test --filter HU001

# Ejecutar con reporte de cobertura (Requiere Xdebug)
php artisan test --coverage-html coverage/
```

---
⌨️ con ❤️ por [kamuxx](https://github.com/kamuxx)
