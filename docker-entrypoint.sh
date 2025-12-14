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
mkdir -p /var/www/storage/framework/{sessions,views,cache}
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Set ownership to www-data recursively
echo "Setting ownership to www-data..."
chown -R www-data:www-data /var/www/storage 2>/dev/null || true
chown -R www-data:www-data /var/www/bootstrap/cache 2>/dev/null || true

# Set directory permissions (775 allows owner and group to write)
echo "Setting directory and file permissions..."
chmod -R 775 /var/www/storage 2>/dev/null || true
chmod -R 775 /var/www/bootstrap/cache 2>/dev/null || true

# Ensure specific view and cache directories are writable
chmod 777 /var/www/storage/framework/views 2>/dev/null || true
chmod 777 /var/www/storage/framework/cache 2>/dev/null || true
chmod 777 /var/www/storage/framework/sessions 2>/dev/null || true
chmod 777 /var/www/storage/logs 2>/dev/null || true

echo "Permissions set successfully!"

# Install npm dependencies if needed
if [ ! -d "/var/www/node_modules" ]; then
    echo "Installing npm dependencies..."
    cd /var/www && npm install
fi

# Start Vite dev server in the background
echo "Starting Vite dev server..."
cd /var/www && npm run dev > /var/log/vite.log 2>&1 &
VITE_PID=$!
echo "Vite started with PID $VITE_PID"

# Execute the main command in foreground mode
echo "Starting PHP-FPM..."
echo "Entrypoint script completed successfully. Executing: $@"

# Check if PHP-FPM config is valid
if [ "$1" = "php-fpm" ]; then
    php-fpm -t || echo "Warning: PHP-FPM configuration test failed, but continuing..."
fi

exec "$@"
