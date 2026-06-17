# Concierge (Domo)

Symfony application for property concierge services.

## Requirements

- **PHP 8.4+** (Symfony 7.4 and several dependencies require 8.4; PHP 8.1 will not work)
- Composer 2
- MySQL 8

## Hostinger deployment

1. **Switch PHP to 8.4** in hPanel:
   - Websites → Manage → **Advanced** → **PHP Configuration**
   - Select **PHP 8.4** and save

   Verify in SSH (CLI must also be 8.4 for Composer):
   ```bash
   php -v
   ```
   If it still shows 8.1, run Composer with the 8.4 binary (path may vary on Hostinger):
   ```bash
   /usr/bin/php84 -v
   /usr/bin/php84 $(which composer) install --no-dev --optimize-autoloader
   ```

2. Clone the repository on your server:
   ```bash
   git clone https://github.com/marakuya73-lang/concierge.git
   cd concierge
   ```

3. Install PHP dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. Configure environment:
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials, APP_SECRET, and ADMIN_PASSWORD
   ```

5. Set up the database:
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

6. Create writable cache/log directories, compile assets, and set permissions:
   ```bash
   mkdir -p var/cache var/log var/sessions
   /opt/alt/php84/usr/bin/php bin/console importmap:install
   /opt/alt/php84/usr/bin/php bin/console asset-map:compile
   /opt/alt/php84/usr/bin/php bin/console cache:clear --env=prod
   /opt/alt/php84/usr/bin/php bin/console cache:warmup --env=prod
   chmod -R 777 var public/uploads
   ```

7. Point your web server document root to the `public/` directory.
