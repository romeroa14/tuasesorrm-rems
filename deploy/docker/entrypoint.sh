#!/bin/sh
# Sin set -e global: un fallo de composer escribe en logs y dejamos traza clara
cd /var/www/html || {
  echo "[rems] FATAL: /var/www/html no existe. ¿Está el volumen del proyecto montado?"
  exit 1
}

if [ ! -f "composer.json" ]; then
  echo "[rems] FATAL: no hay composer.json en /var/www/html. El volumen no apunta a la raíz del proyecto CI4."
  exit 1
fi

# vendor vacío o instalación a medias: sin autoload, composer debe completarse
if [ ! -f "vendor/autoload.php" ]; then
  echo "[rems] Instalando dependencias con Composer (sin --dev)..."
  if ! composer install --no-dev --no-interaction --optimize-autoloader; then
    echo "[rems] ERROR: composer install falló. Revisa: red, memoria, o ejecuta: docker run --rm -v \"\$PWD\":/app -w /app composer:2 install"
    exit 1
  fi
fi

# CI4: WRITEPATH = writable/ debe existir y ser escribible por Apache (www-data).
# Un volumen montado desde el host suele quedar con dueño 1000:1000; sin esto, CI4 responde:
# "The WRITEPATH is not set correctly."
if [ ! -d "writable" ]; then
  echo "[rems] FATAL: no existe la carpeta writable/ en /var/www/html"
  exit 1
fi
for d in cache logs session uploads debugbar; do
  mkdir -p "writable/$d"
done
chown -R www-data:www-data writable 2>/dev/null || true
chmod -R 775 writable 2>/dev/null || chmod -R 777 writable 2>/dev/null || true

exec apache2-foreground
