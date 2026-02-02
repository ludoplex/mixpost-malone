#!/bin/bash
set -e

echo "🚀 Setting up MixPost Malone development environment..."

cd /workspace

# Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-interaction --prefer-dist

# Copy environment file if not exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env 2>/dev/null || cat > .env << 'EOF'
APP_NAME="MixPost Malone"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=mixpost
DB_USERNAME=mixpost
DB_PASSWORD=mixpost

REDIS_HOST=redis
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Social Provider Credentials (add your own)
TWITCH_CLIENT_ID=
TWITCH_CLIENT_SECRET=

DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_BOT_TOKEN=

YOUTUBE_CLIENT_ID=
YOUTUBE_CLIENT_SECRET=

TIKTOK_CLIENT_KEY=
TIKTOK_CLIENT_SECRET=

WHATNOT_CLIENT_ID=
WHATNOT_CLIENT_SECRET=
EOF
fi

# Generate app key
echo "🔑 Generating application key..."
php artisan key:generate --force

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL..."
until php artisan db:monitor --databases=mysql 2>/dev/null; do
    sleep 2
done

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Install frontend dependencies and build
if [ -f package.json ]; then
    echo "🎨 Building frontend assets..."
    npm install
    npm run build 2>/dev/null || npm run dev 2>/dev/null || true
fi

# Create storage link
php artisan storage:link 2>/dev/null || true

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "✅ Setup complete!"
echo ""
echo "🌐 Start the development server with:"
echo "   php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "👤 Create an admin user with:"
echo "   php artisan mixpost:create-admin"
echo ""
