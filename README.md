## test-swoole-symfony

```bash
docker-compose up --build --remove-orphans --force-recreate -d
docker-compose exec php sh
composer install
```

- open in browser http://localhost:8000/test-complex
- check out the errors in the console (STDERR)
