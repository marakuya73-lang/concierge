# Concierge (Domo)

Symfony application for property concierge services.

## Hostinger deployment

1. Clone the repository on your server:
   ```bash
   git clone https://github.com/marakuya73-lang/concierge.git
   cd concierge
   ```

2. Install PHP dependencies:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. Configure environment:
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials, APP_SECRET, and ADMIN_PASSWORD
   ```

4. Set up the database:
   ```bash
   php bin/console doctrine:migrations:migrate --no-interaction
   ```

5. Warm cache and set permissions:
   ```bash
   php bin/console cache:clear --env=prod
   chmod -R 775 var public/uploads
   ```

6. Point your web server document root to the `public/` directory.
