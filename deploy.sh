

echo "==> [1/9] Actualizando codigo desde git"
git pull origin main

echo "==> [2/9] Instalando dependencias de PHP (produccion)"
composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader

echo "==> [3/9] Aplicando migraciones"
php artisan migrate --force

echo "==> [4/9] Compilando assets con npm"
npm install
npm run build

echo "==> [5/9] Limpiando caches previos"
php artisan optimize:clear

echo "==> [6/9] Generando cache de configuracion y vistas"
php artisan config:cache
php artisan view:cache

echo "==> [7/9] Creando symlink de storage (idempotente)"
php artisan storage:link

echo "==> [8/9] Ajustando permisos de storage y bootstrap/cache"
chmod -R 775 storage bootstrap/cache
chmod -R 775 storage/logs

# Nota: si el usuario de PHP-FPM difiere, descomentar y ajustar:
# chown -R <usuario>:<grupo> storage bootstrap/cache

echo "==> [9/9] Reiniciando worker de colas"
# Detener workers existentes
pkill -f "artisan queue:work" 2>/dev/null || true

# Relanzar worker en segundo plano
mkdir -p storage/logs
nohup php artisan queue:work --sleep=2 --tries=3 --timeout=300 \
    >> storage/logs/queue.log 2>&1 &

sleep 2

echo ""
echo "==> Estado de migraciones:"
php artisan migrate:status

echo ""
echo "==> Despliegue completado."
echo "    Para detener el worker: pkill -f 'artisan queue:work'"
echo "    Para ver el log del worker: tail -f $APP_DIR/storage/logs/queue.log"
echo "    En hosting compartido, agenda el worker con cron si no se mantiene la sesion."