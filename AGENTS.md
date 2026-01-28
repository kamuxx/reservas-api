# AGENTS.md - Guía para Agentes de Código

## Información General del Proyecto

**Nombre**: Sistema de Reserva de Espacios - API Backend  
**Tipo**: API RESTful con Laravel 12.0 y Clean Architecture  
**Estado**: 100% completado (11 user stories implementadas)  
**Stack**: PHP 8.2+, Laravel 12.0, MySQL 8.0, JWT, Vite, Tailwind CSS  
**Tests**: PHPUnit con 100% coverage (91 tests, 312 assertions)

## Comandos Esenciales

### Desarrollo
```bash
composer run setup    # Instalación completa del proyecto
composer run dev      # Servidor desarrollo + queue + vite
php artisan serve     # Solo servidor
php artisan queue:listen --tries=1  # Procesar colas
```

### Testing
```bash
composer run test     # Ejecutar todos los tests
php artisan test      # Todos los tests (alternativa)
php artisan test --filter TestClassName  # Tests específicos
php artisan test --filter test_method_name  # Método específico
php artisan test tests/Feature/Auth  # Tests por directorio
php artisan test --coverage-html  # Generar reporte cobertura
```

### Base de Datos
```bash
php artisan migrate:fresh --seed  # Recrear BD con datos
php artisan migrate               # Migraciones pendientes
php artisan db:seed              # Ejecutar seeders
```

## Estructura y Patrones de Arquitectura

### Clean Architecture Layers
1. **Presentation**: `app/Http/Controllers/` (Controllers delgados)
2. **Application**: `UseCases/` (Lógica de negocio)
3. **Infrastructure**: `Repositories/` (Persistencia de datos)
4. **Domain**: `app/Models/` (Entidades del dominio)

### Directorios Clave
```
app/Http/Controllers/    # Controllers HTTP (delgados)
UseCases/               # Casos de uso (lógica de negocio)
Repositories/           # Repositorios (acceso a datos)
tests/Feature/          # Tests de integración API
tests/Unit/             # Tests unitarios
database/migrations/    # Estructura BD (migraciones por niveles)
```

## Estilo de Código y Convenciones

### Nomenclatura
- **Controllers**: PascalCase (`AuthController`, `SpaceController`)
- **Models**: PascalCase (`User`, `Space`, `Reservation`)
- **Use Cases**: PascalCase + `UseCases` (`UserUseCases`)
- **Repositories**: PascalCase + `Repository` (`UserRepository`)
- **Methods**: camelCase (`getActiveUsers`, `createSpace`)
- **Database Tables**: snake_case (`reservations`, `space_features`)
- **Primary Keys**: UUID (`id`, `user_uuid`, `space_uuid`)

### Estilo PHP
- **PSR-12**: Estándar de codificación PHP
- **Type Hints**: PHP 8+ con tipos explícitos en parámetros y retornos
- **Nullable Types**: Usar `?Type` para valores opcionales
- **Return Types**: Todos los métodos tienen tipo de retorno declarado
- **Comentarios**: En español para lógica de negocio

### Import Pattern
```php
// 1. Externos (Illuminate, Symfony)
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// 2. Models del dominio
use App\Models\User;
use App\Models\Space;

// 3. Contracts e interfaces
use Repositories\Contracts\RepositoryContract;

// 4. Clases application-specific
use UseCases\UserUseCases;
use Repositories\UserRepository;
```

## Patrones de Código

### Response Pattern (Base Controller)
```php
// Success
{
    "status": "success",
    "message": "Operación exitosa", 
    "data": { ... }
}

// Error
{
    "status": "error",
    "message": "Error descriptivo",
    "errors": { ... }
}
```

### Repository Pattern
```php
interface RepositoryContract
{
    public static function insert(string $modelClassName, array $data): Model;
    public static function getAll(string $modelClassName): Collection;
    public static function getBy(string $modelClassName, array $filters): ?Collection;
    public static function getOneBy(string $modelClassName, array $filters): ?Model;
    public static function update(string $modelClassName, array $filters, array $data): bool;
}
```

### Use Cases Pattern
```php
class UserUseCases
{
    public function __construct(private UserRepository $repository) {}
    
    public function createUser(array $data): User
    {
        // Lógica de negocio
        return $this->repository->insert(User::class, $data);
    }
}
```

## Testing Patterns

### Estructura de Tests
```
tests/Feature/Auth/AuthTest.php      # Tests API autenticación
tests/Unit/Models/UserTest.php       # Tests modelo User
tests/Unit/UseCases/UserUseCasesTest.php # Tests lógica negocio
```

### Naming Convention Tests
```php
test_login_success()                    # Casos exitosos
test_login_failed_by_invalid_password() # Casos fallidos con razón
test_valid_router()                     # Validación rutas
test_user_creation_with_valid_data()    # Creación con datos válidos
```

### Test Pattern (Arrange-Act-Assert)
```php
public function test_space_creation_success(): void
{
    // Arrange
    $spaceData = Space::factory()->make()->toArray();
    
    // Act
    $response = $this->postJson('/api/spaces', $spaceData);
    
    // Assert
    $response->assertStatus(201)
            ->assertJsonStructure(['status', 'message', 'data']);
}
```

## Manejo de Errores

### Error Handling (Base Controller)
```php
protected function serverError(\Throwable $th, string $message = "Error"): JsonResponse
protected function clientError(\Throwable $e, string $message = "Error"): JsonResponse  
protected function writeLogError(\Throwable $th, string $message = "Error"): void
```

### Exception Types
- `NotFoundHttpException`: 404 errores
- `UnprocessableEntityHttpException`: 422 validación
- `UnauthenticatedException`: 401 autenticación
- Excepciones personalizadas en Use Cases

## Base de Datos

### Migration Strategy (Multi-nivel)
- **Level 0**: Tablas core (`roles`, `statuses`, `space_types`)
- **Level 1**: Entidades (`users`, `spaces`) 
- **Level 2**: Relaciones (`reservations`, `features`)
- **Level 3**: Vistas y optimizaciones

### UUID Pattern
- **Primary Keys**: `id` (UUID)
- **Foreign Keys**: `{table}_uuid` (ej: `user_uuid`, `space_uuid`)
- **Generation**: `Str::uuid()` de Laravel

## API Design

### RESTful Routes
```php
/api/auth/*           # Autenticación
/api/spaces/*         # Gestión espacios  
/api/reservations/*   # Gestión reservaciones
/api/docs             # Documentación Swagger
```

### Middleware Usage
- `auth:api` para rutas autenticadas
- `isAdmin` para rutas solo admin
- Middleware personalizado para validación estado usuario

## Seguridad

### Authentication
- JWT vía `tymon/jwt-auth`
- Role-Based Access Control (RBAC)
- Validación estado usuario (active, pending, blocked)
- Auditoría de accesos

### Validation
- Form Request classes para validación
- Reglas de validación personalizadas
- Sanitización de input usuario
- Prevención SQL injection via Eloquent

## Convenciones Específicas del Proyecto

### Mensajes en Español
- Todos los mensajes usuario en español
- Códigos de error con timestamp
- Logging estructurado en español

### TDD Approach
- Tests-first development
- 100% coverage requerido
- Tests de Feature para API endpoints
- Tests Unit para Use Cases y Repositories

### Environment Variables Testing
- `DB_CONNECTION=sqlite` con BD en memoria
- `DB_DATABASE=:memory:` para aislamiento tests

## Build y Quality Assurance

### Commands Required
```bash
php artisan test              # SIEMPRE ejecutar tests completos
php artisan test --coverage   # Verificar cobertura >= 100%
npm run build                 # Build de assets frontend
php artisan config:clear      # Limpiar cache configuración
```

### Code Quality
- PSR-12 compliance
- Type hints obligatorios
- PHPDoc blocks para métodos públicos
- Sin código comentado (remover, no comentar)

## Notas Importantes para Agentes

1. **Idioma**: Todos los mensajes y comentarios en español
2. **UUIDs**: No usar IDs auto-incrementales, siempre UUIDs
3. **Clean Architecture**: Respetar separación de responsabilidades
4. **TDD**: Escribir tests antes de implementar funcionalidad
5. **Coverage**: Mantener 100% coverage del código
6. **Logging**: Usar métodos del base controller para logging consistente
7. **Responses**: Usar formatos response estandarizados del base controller
8. **Security**: Validar roles y permisos en cada endpoint protegido