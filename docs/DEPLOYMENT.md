# Production Deployment Checklist

## Prerequisites

- [ ] Docker 20.10+ and Docker Compose v2 installed
- [ ] Domain name with SSL certificate (Let's Encrypt or equivalent)
- [ ] SMTP credentials for email delivery
- [ ] MySQL 8.0+ database server (or use Docker service)

---

## 1. Environment Configuration

Copy the example and fill in production values:

```bash
cp .env.example .env
```

### Required Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_HOST` | Database host | `db` (Docker) or `10.0.1.5` |
| `DB_PORT` | Database port | `3306` |
| `DB_NAME` | Database name | `utp_scholarship` |
| `DB_USER` | Database user | `utp_app` |
| `DB_PASS` | Database password | Use a strong random password |
| `APP_ENV` | Environment | `production` |
| `APP_URL` | Public URL | `https://scholarship.utp.edu.my` |
| `ADMIN_EMAIL` | Default admin email | `admin@utp.edu.my` |
| `ADMIN_PASSWORD` | Default admin password | **Change immediately after first login** |
| `MAIL_HOST` | SMTP server | `smtp.gmail.com` |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USERNAME` | SMTP username | `noreply@utp.edu.my` |
| `MAIL_PASSWORD` | SMTP password | App-specific password |
| `MAIL_FROM` | Sender email | `noreply@utp.edu.my` |
| `SENTRY_DSN` | Sentry error tracking | `https://...@sentry.io/...` |
| `TRUSTED_PROXY` | Reverse proxy IP | `172.18.0.1` |

> [!CAUTION]
> Never commit `.env` to version control. The `.gitignore` already excludes it.

---

## 2. Database Setup

### Option A: Docker (Recommended)

```bash
docker-compose up -d db
# Wait for health check to pass, then:
docker-compose exec db mysql -u root -p$DB_PASS utp_scholarship < sql/setup.sql
docker-compose exec db mysql -u root -p$DB_PASS utp_scholarship < sql/indexes.sql
```

### Option B: Existing MySQL Server

```bash
mysql -h $DB_HOST -u $DB_USER -p $DB_NAME < sql/setup.sql
mysql -h $DB_HOST -u $DB_USER -p $DB_NAME < sql/indexes.sql
```

### Run Migrations

```bash
vendor/bin/phinx migrate -e production
vendor/bin/phinx status
```

---

## 3. Application Deployment

### Docker Deployment

```bash
# Build and start all services
docker-compose up -d --build

# Verify containers are healthy
docker-compose ps
docker-compose logs -f app
```

### Manual Deployment (Apache)

```bash
composer install --no-dev --optimize-autoloader
chown -R www-data:www-data .
chmod -R 755 .
chmod -R 770 uploads/ logs/
```

Apache vhost configuration:
```apache
<VirtualHost *:443>
    ServerName scholarship.utp.edu.my
    DocumentRoot /var/www/html
    
    <Directory /var/www/html>
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/scholarship.utp.edu.my/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/scholarship.utp.edu.my/privkey.pem
</VirtualHost>
```

---

## 4. Security Configuration

- [ ] Set `APP_ENV=production` (disables debug output)
- [ ] Ensure HTTPS is enforced (redirect HTTP → HTTPS)
- [ ] Verify CSP headers work with your domain
- [ ] Set `TRUSTED_PROXY` if behind a load balancer/CDN
- [ ] Change default admin password immediately
- [ ] Verify rate limiting is active (`RATE_LIMIT_MAX_ATTEMPTS=5`)
- [ ] Ensure `uploads/` directory is not web-accessible (use `.htaccess`)
- [ ] Ensure `logs/` directory is not web-accessible

---

## 5. Monitoring Setup

### Sentry Error Tracking

1. Create a project at [sentry.io](https://sentry.io)
2. Set `SENTRY_DSN` in `.env`
3. Errors and performance data will auto-report

### Health Checks

```bash
# Verify application responds
curl -s -o /dev/null -w "%{http_code}" https://scholarship.utp.edu.my/

# Verify database connectivity
docker-compose exec app php -r "require 'config/database.php'; echo getDB() ? 'OK' : 'FAIL';"
```

---

## 6. Backup Strategy

### Database Backup (Daily)

```bash
# Add to crontab: 0 2 * * * /path/to/backup.sh
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME \
    --single-transaction --routines --triggers \
    | gzip > /backups/utp_$(date +%Y%m%d_%H%M%S).sql.gz

# Keep last 30 days
find /backups/ -name "utp_*.sql.gz" -mtime +30 -delete
```

### File Backup

```bash
tar -czf /backups/uploads_$(date +%Y%m%d).tar.gz uploads/documents/
```

---

## 7. Troubleshooting Guide

### Issue 1: "Database connection failed"

**Cause:** MySQL service not running or wrong credentials.

```bash
# Docker: check if DB container is healthy
docker-compose ps db
docker-compose logs db | tail -20

# Verify credentials
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS -e "SELECT 1"
```

### Issue 2: "Emails not sending"

**Cause:** SMTP credentials incorrect or port blocked.

```bash
# Test SMTP connectivity
php -r "
\$m = new \PHPMailer\PHPMailer\PHPMailer(true);
\$m->isSMTP(); \$m->Host = getenv('MAIL_HOST');
\$m->SMTPAuth = true;
echo 'SMTP reachable: ' . (\$m->smtpConnect() ? 'YES' : 'NO');
"
```

### Issue 3: "File uploads failing"

**Cause:** Directory permissions or PHP `upload_max_filesize`.

```bash
# Check permissions
ls -la uploads/documents/
# Should be: drwxrwx--- www-data

# Check PHP limits
php -i | grep upload_max_filesize
php -i | grep post_max_size
```

### Issue 4: "CSS/JS not loading (403 errors)"

**Cause:** Apache `mod_rewrite` not enabled or `.htaccess` not allowed.

```bash
a2enmod rewrite
systemctl restart apache2
# Verify AllowOverride is set to All in vhost config
```

### Issue 5: "Session expired too quickly"

**Cause:** PHP session garbage collection interval too low.

```ini
; In php.ini:
session.gc_maxlifetime = 1800  ; 30 minutes
session.cookie_lifetime = 0    ; Until browser closes
```

---

## Post-Deployment Verification

- [ ] Landing page loads with correct styling
- [ ] Student registration and email verification works
- [ ] Login/logout cycle works (try both student and admin)
- [ ] Eligibility check produces correct results
- [ ] Application submission triggers confirmation email
- [ ] Admin dashboard shows correct statistics
- [ ] PDF/CSV exports generate correctly
- [ ] Audit log records all actions
- [ ] Sentry receives test error (trigger intentionally)
