# Bingo - Laravel + Vue + Vite

Proyecto Laravel con Vue 3, Vite y Docker.

## Requisitos

-   Docker
-   Docker Compose

## Instalación

### Método 1: Instalación automática (Recomendado)

1. Clonar el repositorio:

```bash
git clone <repository-url>
cd bingo
```

2. Ejecutar el script de setup:

```bash
./docker-setup.sh
```

Este script se encargará de:

-   Crear y configurar el archivo .env
-   Construir las imágenes de Docker
-   Levantar los contenedores
-   Instalar dependencias de PHP y Node
-   Generar la clave de aplicación
-   Ejecutar migraciones
-   Ejecutar seeders (si existen)

### Método 2: Instalación manual

1. Clonar el repositorio:

```bash
git clone <repository-url>
cd bingo
```

2. Copiar el archivo de entorno:

```bash
cp .env.example .env
```

3. Construir y levantar los contenedores:

```bash
docker compose up -d --build
```

4. Instalar dependencias de PHP:

```bash
docker compose exec app composer install
```

5. Generar la clave de aplicación:

```bash
docker compose exec app php artisan key:generate
```

6. Instalar dependencias de Node:

```bash
docker compose exec vite npm install
```

7. Ejecutar las migraciones:

```bash
docker compose exec app php artisan migrate
```

## Servicios

-   **App (Laravel)**: http://localhost:8020
-   **MySQL**: localhost:3326
-   **Redis**: localhost:6399
-   **Mailpit Web UI**: http://localhost:8045
-   **Mailpit SMTP**: localhost:1045
-   **Vite (HMR)**: http://localhost:5193

## Comandos útiles

### Laravel/PHP

```bash
# Ejecutar comandos artisan
docker compose exec app php artisan <command>

# Ejecutar composer
docker compose exec app composer <command>

# Acceder al contenedor
docker compose exec app bash
```

### Base de datos

```bash
# Ejecutar migraciones
docker compose exec app php artisan migrate

# Rollback
docker compose exec app php artisan migrate:rollback

# Fresh migration con seeders
docker compose exec app php artisan migrate:fresh --seed
```

### Frontend

```bash
# Los assets de Vite se compilan automáticamente con HMR
# El servidor de desarrollo está corriendo en el contenedor vite

# Para instalar paquetes npm
docker compose exec vite npm install <package>

# Para construir para producción:
docker compose exec vite npm run build
```

### Docker

```bash
# Levantar servicios
docker compose up -d

# Detener servicios
docker compose down

# Ver logs
docker compose logs -f

# Ver logs de un servicio específico
docker compose logs -f app

# Reconstruir contenedores
docker compose up -d --build
```

## Testing

### Grid Uniformity Test

To validate that the bingo grid generation algorithm produces uniform randomness:

```bash
# Run the uniformity test
docker compose exec app ./vendor/bin/phpunit --filter BingoGridUniformityTest

# Or run all tests
docker compose exec app ./vendor/bin/phpunit
```

**What this test does:**

-   Samples 5000 generated grids to verify randomness distribution.
-   Validates that:
    -   **Count distribution**: Each column should have 1–3 numbers with roughly equal probability (±20% tolerance).
    -   **Value uniformity**: Selected numbers within each column range should appear with equal probability (±20%).
    -   **Row placement**: Chosen row positions (0–2) should be randomly distributed (±20%).

**Tolerance rationale:**

-   ±20% tolerance balances statistical confidence with practical runtime (5000 samples is large enough for reliable assertions while remaining fast).
-   Adjust `samples` and `toleranceRatio` in the test if stricter validation is needed.

## Estructura

-   `app/` - Código de la aplicación Laravel
-   `resources/js/` - Componentes Vue
-   `resources/views/` - Vistas Blade
-   `docker/` - Configuración de Docker
-   `Dockerfile` - Imagen PHP/Laravel
-   `Dockerfile.node` - Imagen Node/Vite

## Tecnologías

-   Laravel 12
-   Vue 3
-   Vite
-   Inertia.js
-   Tailwind CSS
-   MySQL 8.0
-   Redis
-   Mailpit (Email testing)
-   Nginx
-   PHP 8.2-FPM
-   Docker & Docker Compose
