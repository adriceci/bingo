#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🐳 Setting up Docker environment for Bingo...${NC}"

# Copy .env.example to .env if it doesn't exist
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        echo -e "${YELLOW}📋 Copying .env.example to .env...${NC}"
        cp .env.example .env
        
        # Update database configuration for Docker
        echo -e "${YELLOW}🔧 Configuring database settings...${NC}"
        sed -i.bak 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
        sed -i.bak 's/# DB_HOST=127.0.0.1/DB_HOST=mysql/' .env
        sed -i.bak 's/# DB_PORT=3306/DB_PORT=3306/' .env
        sed -i.bak 's/# DB_DATABASE=laravel/DB_DATABASE=bingo_db/' .env
        sed -i.bak 's/# DB_USERNAME=root/DB_USERNAME=bingo_user/' .env
        sed -i.bak 's/# DB_PASSWORD=/DB_PASSWORD=secret/' .env
        
        # Update Redis configuration
        sed -i.bak 's/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/' .env
        
        # Update Mail configuration
        sed -i.bak 's/MAIL_HOST=127.0.0.1/MAIL_HOST=mailpit/' .env
        sed -i.bak 's/MAIL_PORT=2525/MAIL_PORT=1025/' .env
        
        # Update Vite HMR configuration for Docker
        sed -i.bak 's/VITE_HMR_HOST=localhost/VITE_HMR_HOST=localhost/' .env
        sed -i.bak 's/VITE_HMR_PORT=5173/VITE_HMR_PORT=5173/' .env
        sed -i.bak 's/VITE_HMR_PROTOCOL=ws/VITE_HMR_PROTOCOL=ws/' .env
        
        # Clean up backup files
        rm -f .env.bak
    else
        echo -e "${YELLOW}⚠️  .env.example not found, creating basic .env file...${NC}"
        cat > .env << EOF
APP_NAME=Bingo
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8020

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=bingo_db
DB_USERNAME=bingo_user
DB_PASSWORD=secret

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
EOF
    fi
fi

# Build and start containers
echo -e "${GREEN}🔨 Building Docker containers...${NC}"
docker compose build

echo -e "${GREEN}🚀 Starting containers...${NC}"
docker compose up -d

# Wait for database to be ready
echo -e "${YELLOW}⏳ Waiting for database to be ready...${NC}"
sleep 10

# Install PHP dependencies
echo -e "${GREEN}📦 Installing PHP dependencies...${NC}"
docker compose exec -T app composer install

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo -e "${YELLOW}🔑 Generating application key...${NC}"
    docker compose exec -T app php artisan key:generate
fi

# Install Node dependencies
echo -e "${GREEN}📦 Installing Node dependencies...${NC}"
docker compose exec -T app npm install

# Run migrations
echo -e "${GREEN}🗄️  Running database migrations...${NC}"
docker compose exec -T app php artisan migrate --force

# Run seeders (if they exist)
if docker compose exec -T app php artisan db:seed --help &>/dev/null; then
    echo -e "${GREEN}🌱 Seeding database...${NC}"
    docker compose exec -T app php artisan db:seed --force || echo -e "${YELLOW}⚠️  No seeders found or seeding failed${NC}"
fi

echo -e "${GREEN}✅ Setup complete!${NC}"
echo -e "${GREEN}🌐 Application is available at: http://localhost:8020${NC}"
echo -e "${GREEN}📊 Database is available at: localhost:3326${NC}"
echo -e "${GREEN}📧 Mailpit is available at: http://localhost:8045${NC}"
echo -e "${GREEN}⚡ Vite dev server is available at: http://localhost:5173${NC}"
echo -e "${YELLOW}💡 To view logs: docker compose logs -f${NC}"
echo -e "${YELLOW}💡 To start Vite dev server: docker compose exec app npm run dev${NC}"
echo -e "${YELLOW}💡 To stop containers: docker compose down${NC}"
