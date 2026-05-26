#!/bin/bash
set -e

SOURCE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
echo "Source directory: $SOURCE_DIR"

read -p "Target installation directory [/var/www/core]: " TARGET_DIR
TARGET_DIR=${TARGET_DIR:-/var/www/core}
mkdir -p "$TARGET_DIR"
TARGET_DIR="$(cd "$TARGET_DIR" && pwd)"

read -p "Admin email [admin@core.dev]: " ADMIN_EMAIL
ADMIN_EMAIL=${ADMIN_EMAIL:-admin@core.dev}
read -sp "Admin password: " ADMIN_PASS
echo
ADMIN_PASS=${ADMIN_PASS:-password}

read -p "MySQL database name [core_db]: " DB_NAME
DB_NAME=${DB_NAME:-core_db}
read -p "MySQL test database name [core_test]: " TEST_DB_NAME
TEST_DB_NAME=${TEST_DB_NAME:-core_test}
read -p "MySQL username [root]: " DB_USER
DB_USER=${DB_USER:-root}
read -sp "MySQL password (leave empty if no password): " DB_PASS
echo
read -p "MySQL host [127.0.0.1]: " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}
read -p "MySQL port [3306]: " DB_PORT
DB_PORT=${DB_PORT:-3306}

read -p "Configure Nginx for production? (y/n): " SETUP_NGINX
SETUP_NGINX=${SETUP_NGINX:-n}
if [[ "$SETUP_NGINX" == "y" ]]; then
    read -p "Domain name [core.local]: " DOMAIN
    DOMAIN=${DOMAIN:-core.local}
    echo "Ensure $DOMAIN points to this server (add to /etc/hosts or DNS)."
fi

cd "$TARGET_DIR"
composer create-project laravel/laravel . --prefer-dist

rsync -av --exclude="install.sh" --exclude=".env" --exclude="vendor" --exclude="node_modules" "$SOURCE_DIR"/ ./

rm -f tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php

chmod -R 775 storage bootstrap/cache 2>/dev/null || true

sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env
sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=$DB_HOST/" .env
sed -i "s/# DB_PORT=3306/DB_PORT=$DB_PORT/" .env
sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env
sed -i "s/# DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env
sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$DB_PASS/" .env

grep -q "^SESSION_DRIVER=" .env || echo "SESSION_DRIVER=file" >> .env
grep -q "^CACHE_STORE=" .env || echo "CACHE_STORE=file" >> .env
sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=file/" .env
sed -i "s/^CACHE_STORE=.*/CACHE_STORE=file/" .env

mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`" 2>/dev/null || echo "Main database ready"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$TEST_DB_NAME\`" 2>/dev/null || echo "Test database ready"

sed -i "s|{{DB_HOST}}|$DB_HOST|g" phpunit.xml
sed -i "s|{{DB_PORT}}|$DB_PORT|g" phpunit.xml
sed -i "s|{{TEST_DB_NAME}}|$TEST_DB_NAME|g" phpunit.xml
sed -i "s|{{DB_USER}}|$DB_USER|g" phpunit.xml
sed -i "s|{{DB_PASS}}|$DB_PASS|g" phpunit.xml

composer require livewire/livewire doctrine/dbal
npm install
npm install chart.js --save-dev
npm run build

php artisan migrate:fresh
php artisan storage:link
chmod -R 775 storage/app/public

cat > database/seeders/AdminSeeder.php <<EOF
<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class AdminSeeder extends Seeder {
    public function run() {
        User::updateOrCreate(['email'=>'$ADMIN_EMAIL'],[
            'name'=>'Admin','surname'=>'User','password'=>Hash::make('$ADMIN_PASS'),
            'role'=>'admin','created_at'=>now(),'updated_at'=>now()
        ]);
    }
}
EOF

php artisan db:seed --class=DatabaseSeeder
php artisan db:seed --class=AdminSeeder
echo "Admin user created: $ADMIN_EMAIL"

if [ -f bootstrap/app.php ]; then
    if ! grep -q "CoreProvider::class" bootstrap/app.php; then
        sed -i "/->withProviders(/a \\        App\\\\Providers\\\\CoreProvider::class," bootstrap/app.php
    fi
fi

php artisan test --testdox
php artisan core:lint

if [[ "$SETUP_NGINX" == "y" ]]; then
    sudo rm -f /etc/nginx/sites-available/core
    sudo rm -f /etc/nginx/sites-enabled/core
    sed -e "s|{{DOMAIN}}|$DOMAIN|g" -e "s|{{ROOT}}|$TARGET_DIR|g" nginx/core.conf > /tmp/core_nginx.conf
    sudo cp /tmp/core_nginx.conf /etc/nginx/sites-available/core
    sudo ln -sf /etc/nginx/sites-available/core /etc/nginx/sites-enabled/
    sudo systemctl restart nginx 2>/dev/null || sudo service nginx restart 2>/dev/null
    echo "Starting Laravel development server on port 8000..."
    nohup php artisan serve --host=127.0.0.1 --port=8000 > storage/logs/artisan-serve.log 2>&1 &
    sleep 3
    HTTP_CODE=$(curl -o /dev/null -s -w "%{http_code}\n" "http://$DOMAIN/admin" || echo "000")
    if [[ "$HTTP_CODE" -eq 200 ]] || [[ "$HTTP_CODE" -eq 302 ]]; then
        echo "✅ Site accessible (HTTP $HTTP_CODE) at http://$DOMAIN/admin"
    else
        echo "⚠️ Site returned HTTP $HTTP_CODE. Check Nginx and artisan-serve.log."
        echo "You may need to add '$DOMAIN' to /etc/hosts or configure DNS."
    fi
else
    php artisan serve --host=0.0.0.0 --port=8000 &
    SERVER_PID=$!
    sleep 3
    xdg-open "http://localhost:8000/admin" 2>/dev/null || open "http://localhost:8000/admin" 2>/dev/null || echo "Open http://localhost:8000/admin manually"
    wait $SERVER_PID
fi
