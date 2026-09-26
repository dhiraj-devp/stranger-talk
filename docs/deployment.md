# Production deployment

This document is the server setup. Installing Redis, Reverb, Supervisor, or coturn on the machine that runs this repository is a separate step. The application reads those services from environment variables and keeps working with HTTP polling when they are absent.

Production site used in the examples: `https://talk.dvmsoft.in`.

## Application

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Set `.env` for production before caching config:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://talk.dvmsoft.in`
- `SESSION_SECURE_COOKIE=true`
- Database `DB_*`
- After Redis is installed: `CACHE_STORE=redis`, `REDIS_CLIENT=predis`, `REDIS_ENABLED=true`
- After Reverb is running: `BROADCAST_CONNECTION=reverb`
- `QUEUE_CONNECTION=database` (or `redis` once Redis is up)

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do not run `config:cache` until `.env` is the production file. A cached local config will keep debug mode and the wrong URLs.

Writable directories: `storage` and `bootstrap/cache` must be writable by the PHP user.

## Scheduler

```cron
* * * * * cd /var/www/strangertalk && php artisan schedule:run >> /dev/null 2>&1
```

`platform:cleanup` runs every minute. It expires stale matches, lifts expired bans, and deletes old analytics and match events. It does not delete reports, bans, or audit logs.

## Queue worker

```bash
php artisan queue:work --sleep=1 --tries=3 --max-time=3600
```

Analytics and other queued jobs stay in the `jobs` table until a worker runs. WebRTC signaling is not queued.

Supervisor is optional. If it is installed, copy `deploy/supervisor/laravel-worker.conf` and `deploy/supervisor/laravel-reverb.conf` to `/etc/supervisor/conf.d/` and run:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart strangertalk-worker:* strangertalk-reverb
```

Workers restart automatically (`autorestart=true`). This machine does not have Supervisor installed.

## Redis

```bash
sudo apt-get update
sudo apt-get install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping
```

Then set `REDIS_ENABLED=true` and `CACHE_STORE=redis`. Keys used by the app: `matchmaking:waiting`, `presence:user:{id}`, `match:{id}`, `lock:matchmaking`, `ratelimit:{type}:{identifier}`. MySQL remains the permanent store.

If the host cannot run Redis, leave `REDIS_ENABLED=false`. Matchmaking still uses the database lock. Do not point `CACHE_STORE` at Redis until `redis-cli ping` succeeds.

## Laravel Reverb

```bash
php artisan reverb:start
```

Environment:

```
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=talk.dvmsoft.in
REVERB_PORT=8080
REVERB_SCHEME=https
BROADCAST_CONNECTION=reverb
```

Generate the app id, key, and secret yourself. Do not commit them. The browser receives only the public key, host, port, and scheme. The secret stays on the server.

Nginx example in front of Reverb:

```nginx
location /app {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 60s;
}
```

If Reverb is not running, leave `BROADCAST_CONNECTION=log`. The video page keeps signaling with HTTP polling.

## Nginx site

```nginx
server {
    listen 443 ssl http2;
    server_name talk.dvmsoft.in;
    root /var/www/strangertalk/public;

    ssl_certificate     /etc/letsencrypt/live/talk.dvmsoft.in/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/talk.dvmsoft.in/privkey.pem;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    index index.php;
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

Apache equivalent: point the vhost document root at `public/` and allow `mod_rewrite` with the Laravel `public/.htaccess`.

TLS certificates are issued outside this app, for example:

```bash
sudo certbot --nginx -d talk.dvmsoft.in
```

## Health

- `GET /up` — Laravel process
- `GET /health` — database, Redis (disabled, ok, or unavailable), queue table, Reverb configuration

No passwords or tokens are included in that JSON.

## Local Windows note

This project was developed with PHP and MySQL from XAMPP. Redis, Reverb, Supervisor, and coturn are not installed on that machine. `php artisan serve --host=127.0.0.1 --port=8010` is enough for local email/password video chat.
