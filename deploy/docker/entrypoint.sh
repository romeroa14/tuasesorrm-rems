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

exec apache2-foreground
