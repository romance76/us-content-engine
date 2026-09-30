#!/bin/bash
# One-time first deploy of us-content-engine onto an existing droplet,
# served at http://info.awesomekorean.com (nginx + php-fpm, sqlite DB).
# Paste this whole file into the DigitalOcean web Console (as root) and run it.
set -e

APP_DIR=/var/www/us-content-engine
DOMAIN=info.awesomekorean.com
PHP_VER=8.3
REPO=https://github.com/romance76/us-content-engine.git
LOG=/var/log/us-content-engine-setup.log

exec > >(tee -a "$LOG") 2>&1
echo "=== us-content-engine first deploy: $(date) ==="

echo "-> Installing PHP $PHP_VER + extensions (if missing)"
if ! command -v php$PHP_VER >/dev/null; then
    apt-get update -qq
    apt-get install -y -qq software-properties-common
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
fi
apt-get install -y -qq php$PHP_VER-fpm php$PHP_VER-cli php$PHP_VER-common \
    php$PHP_VER-curl php$PHP_VER-mbstring php$PHP_VER-xml php$PHP_VER-zip \
    php$PHP_VER-bcmath php$PHP_VER-gd php$PHP_VER-sqlite3 \
    git unzip nginx

echo "-> Installing composer (if missing)"
if ! command -v composer >/dev/null; then
    curl -sS https://getcomposer.org/installer | php$PHP_VER -- --install-dir=/usr/local/bin --filename=composer
fi

echo "-> Installing node/npm (if missing)"
if ! command -v npm >/dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y -qq nodejs
fi

echo "-> Fetching code"
if [ -d "$APP_DIR/.git" ]; then
    cd "$APP_DIR" && git fetch origin main && git reset --hard origin/main
else
    git clone --depth 1 "$REPO" "$APP_DIR"
    cd "$APP_DIR"
fi

echo "-> Composer + npm install"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --optimize-autoloader --no-interaction -q
npm ci --silent
npm run build

echo "-> Configuring .env"
if [ ! -f "$APP_DIR/.env" ]; then
    cp .env.example .env
    ADMIN_PASS=$(openssl rand -base64 12)
    INGEST_TOKEN=$(openssl rand -hex 20)
    sed -i "s#^APP_NAME=.*#APP_NAME=\"Info Awesome Korean\"#" .env
    sed -i "s#^APP_URL=.*#APP_URL=http://$DOMAIN#" .env
    sed -i "s#^APP_ENV=.*#APP_ENV=production#" .env
    sed -i "s#^APP_DEBUG=.*#APP_DEBUG=false#" .env
    sed -i "s#^ADMIN_EMAIL=.*#ADMIN_EMAIL=admin@$DOMAIN#" .env
    sed -i "s#^ADMIN_PASSWORD=.*#ADMIN_PASSWORD=$ADMIN_PASS#" .env
    sed -i "s#^INGEST_API_TOKEN=.*#INGEST_API_TOKEN=$INGEST_TOKEN#" .env
    php$PHP_VER artisan key:generate --force
    echo "$ADMIN_PASS" > /root/us-content-engine-admin-password.txt
    echo "$INGEST_TOKEN" > /root/us-content-engine-ingest-token.txt
else
    echo "   .env already exists, leaving it as-is"
fi

echo "-> Database"
touch database/database.sqlite
php$PHP_VER artisan migrate --force
php$PHP_VER artisan db:seed --force
php$PHP_VER artisan optimize:clear
php$PHP_VER artisan optimize

echo "-> Permissions"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" "$APP_DIR/database"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "-> nginx vhost for $DOMAIN"
cat > /etc/nginx/sites-available/$DOMAIN <<NGINX
server {
    listen 80;
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php$PHP_VER-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX
ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/$DOMAIN
nginx -t
systemctl restart php$PHP_VER-fpm
systemctl reload nginx

echo ""
echo "================================================================"
echo " DONE."
echo " Site (once DNS points here): http://$DOMAIN/"
echo " Admin login: http://$DOMAIN/login"
if [ -f /root/us-content-engine-admin-password.txt ]; then
    echo " Admin email:    admin@$DOMAIN"
    echo " Admin password: $(cat /root/us-content-engine-admin-password.txt)"
    echo " Ingest token:   $(cat /root/us-content-engine-ingest-token.txt)"
    echo " (also saved in /root/us-content-engine-admin-password.txt and"
    echo "  /root/us-content-engine-ingest-token.txt on this server)"
fi
echo ""
echo " Still needed: point info.awesomekorean.com's DNS A record at"
echo " this droplet's IP (wherever awesomekorean.com's DNS is managed)."
echo " Once DNS resolves, run: certbot --nginx -d $DOMAIN"
echo "================================================================"
