#!/bin/sh
# Logs siempre visibles en: docker logs rems-app
cd /var/www/html || {
  echo "[rems] FATAL: /var/www/html no existe. ¿Volumen montado?"
  exit 1
}

if [ ! -f "composer.json" ]; then
  echo "[rems] FATAL: no hay composer.json (el volumen no es la raíz del proyecto CI4)."
  exit 1
fi

if [ ! -f "public/index.php" ]; then
  echo "[rems] FATAL: falta public/index.php"
  exit 1
fi

if [ ! -f "vendor/autoload.php" ]; then
  echo "[rems] composer install (sin --dev)..."
  if ! composer install --no-dev --no-interaction --optimize-autoloader; then
    echo "[rems] ERROR: composer install falló."
    exit 1
  fi
fi

# writable: si no existe (clone parcial), crear estructura mínima CI4
if [ ! -d "writable" ]; then
  echo "[rems] creando writable/ y subcarpetas..."
  mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar
fi
for d in cache logs session uploads debugbar; do
  mkdir -p "writable/$d"
done
chown -R www-data:www-data writable 2>/dev/null || true
chmod -R 775 writable 2>/dev/null || chmod -R 777 writable 2>/dev/null || true

# Fallo típico de Apache: config rota (raro) o módulos
if ! apache2ctl configtest 2>&1; then
  echo "[rems] FATAL: apache2ctl configtest falló (revisa deploy/docker/apache/000-default.conf)"
  exit 1
fi

echo "[rems] OK — arrancando Apache (DocumentRoot=public/)"
exec apache2-foreground
