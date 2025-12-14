#!/bin/bash
set -e

# Wait for MySQL with timeout (max 30 seconds)
echo "Waiting for MySQL to be ready..."
TIMEOUT=30
ELAPSED=0
while ! nc -z mysql 3306 2>/dev/null; do
    if [ $ELAPSED -ge $TIMEOUT ]; then
        echo "Warning: MySQL not available after ${TIMEOUT}s, continuing anyway..."
        break
    fi
    sleep 1
    ELAPSED=$((ELAPSED + 1))
done

if nc -z mysql 3306 2>/dev/null; then
    echo "MySQL is ready!"
else
    echo "MySQL connection not available - application will continue but database may not be accessible"
fi

# Fix permissions for storage and bootstrap/cache directories
# This is necessary because volume mounts override build-time permissions
echo "Setting permissions for storage and cache directories..."

# Create directories if they don't exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# Set ownership to www-data
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Set directory permissions (775 allows owner and group to write)
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;

echo "Permissions set successfully!"

# Execute the main command in foreground mode
echo "Starting PHP-FPM..."
echo "Entrypoint script completed successfully. Executing: $@"

# Check if PHP-FPM config is valid
if [ "$1" = "php-fpm" ]; then
    php-fpm -t || echo "Warning: PHP-FPM configuration test failed, but continuing..."
fi

exec "$@"
