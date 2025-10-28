# Docker Commands for VAMS

This project uses Docker for local development. Always run PHP/Laravel commands through Docker.

## PHP/Laravel Artisan Commands

**Always use this format:**
```bash
docker compose exec api php artisan <command>
```

### Common Commands

```bash
# Migrations
docker compose exec api php artisan migrate
docker compose exec api php artisan migrate:status
docker compose exec api php artisan migrate:rollback

# Tinker
docker compose exec api php artisan tinker
docker compose exec api php artisan tinker --execute="your code here"

# Cache
docker compose exec api php artisan cache:clear
docker compose exec api php artisan config:clear
docker compose exec api php artisan route:clear
docker compose exec api php artisan view:clear

# Testing
docker compose exec api php artisan test
docker compose exec api php artisan test --filter=TestName

# Pint (Code formatting)
docker compose exec api vendor/bin/pint
docker compose exec api vendor/bin/pint --dirty
```

## Important Notes

- **Never use `php artisan` directly** - always prefix with `docker compose exec api`
- The container name is `api` as defined in `docker-compose.yml`
- If you get "command not found" errors, you're likely running commands outside Docker

