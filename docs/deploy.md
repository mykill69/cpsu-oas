# Deploying to is.cpsu.edu.ph/cpsu-oas (CentOS / Rocky / RHEL, Apache httpd)

The site lives at **https://is.cpsu.edu.ph/cpsu-oas**. The project sits in `/var/www/html/cpsu-oas`, next to the other apps.

**Rule:** the address `/cpsu-oas` must be served from the project's **`public/`** folder only, never from the project folder itself. Otherwise `.env` (passwords, keys), logs and uploads can be downloaded.

The project's own root `.htaccess` does this. It passes every request into `public/`, and `public/index.php` tells Laravel its address is `/cpsu-oas`. You need **no Apache `Alias`**; Apache only has to allow `.htaccess` files for `/var/www/html` (`AllowOverride All`):

```
https://is.cpsu.edu.ph/cpsu-oas/...   →   /var/www/html/cpsu-oas/public/...
```

> Do **not** also add an `Alias /cpsu-oas` in `/etc/httpd/conf.d/`. The two methods conflict.

Run everything as root. Apache's user on this server is `apache`; run artisan as that user so cache files stay writable.

---

## First install

```bash
cd /var/www/html
git clone https://github.com/mykill69/cpsu-oas.git cpsu-oas
cd cpsu-oas
composer install --no-dev --optimize-autoloader
```

### Apache: allow .htaccess in /var/www/html

In `/etc/httpd/conf/httpd.conf`, the `<Directory "/var/www/html">` block needs:

```apache
<Directory "/var/www/html">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

```bash
apachectl configtest && systemctl reload httpd
```

### Permissions and SELinux

```bash
cd /var/www/html/cpsu-oas
mkdir -p bootstrap/cache storage/logs storage/framework/{cache,sessions,views} storage/app
chown -R apache:apache storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/html/cpsu-oas/(storage|bootstrap/cache)(/.*)?"
restorecon -Rv /var/www/html/cpsu-oas
setsebool -P httpd_can_network_connect 1      # Google sign-in, Drive, Gmail
setsebool -P httpd_can_network_connect_db 1   # MySQL over 127.0.0.1
```

(`semanage` missing: `dnf install -y policycoreutils-python-utils`.)

### `.env`

```bash
cp .env.example .env && vi .env
```

```dotenv
APP_NAME="CPSU OAS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://is.cpsu.edu.ph/cpsu-oas

DB_DATABASE=cpsu_accreditation
DB_USERNAME=oas
DB_PASSWORD=...

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
OAS_ALLOWED_DOMAIN=cpsu.edu.ph
OAS_DRIVE_ACCOUNT=cpsu_accresys@cpsu.edu.ph
OAS_ALLOW_PASSWORD_LOGIN=false

MAIL_MAILER=gmail
MAIL_FROM_ADDRESS="cpsu_accresys@cpsu.edu.ph"
MAIL_FROM_NAME="CPSU OAS (No-reply)"

QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
TELESCOPE_ENABLED=false
```

```bash
sudo -u apache php artisan key:generate --force
sudo -u apache php artisan migrate --force
sudo -u apache php artisan db:seed --force     # then delete the sample accounts
sudo -u apache php artisan config:cache
sudo -u apache php artisan route:cache
sudo -u apache php artisan view:cache
```

### Google Cloud

Add to the OAuth client's Authorized redirect URIs (must match `sudo -u apache php artisan oas:google-check`):

```
https://is.cpsu.edu.ph/cpsu-oas/auth/google/callback
https://is.cpsu.edu.ph/cpsu-oas/qa/drive/callback
```

### Check

| URL | Expected |
|---|---|
| `https://is.cpsu.edu.ph/cpsu-oas/` | sign-in page |
| `https://is.cpsu.edu.ph/cpsu-oas/.env` | 404 Not Found |
| `https://is.cpsu.edu.ph/cpsu-oas/composer.json` | 404 Not Found |

---

## Updating

```bash
cd /var/www/html/cpsu-oas
git pull
composer install --no-dev --optimize-autoloader
chown -R apache:apache storage bootstrap/cache
sudo -u apache php artisan migrate --force
sudo -u apache php artisan optimize:clear
sudo -u apache php artisan config:cache && sudo -u apache php artisan route:cache && sudo -u apache php artisan view:cache
```
