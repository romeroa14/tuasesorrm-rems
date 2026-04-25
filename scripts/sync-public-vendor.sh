#!/bin/sh
# Sincroniza node_modules -> public/vendor con la estructura que esperan las vistas (estilo SB Admin).
# Uso: npm ci && npm run sync:public-vendor
# (No es "composer": son assets JS/CSS de npm.)

set -e
cd "$(dirname "$0")/.."
ROOT=$PWD
NM="$ROOT/node_modules"
DEST="$ROOT/public/vendor"

if [ ! -d "$NM/jquery" ]; then
  echo "ERROR: no hay node_modules. Ejecuta: npm ci"
  exit 1
fi

echo "[sync-public-vendor] limpiando y copiando a public/vendor/ ..."
rm -rf "$DEST"
mkdir -p "$DEST"

# Bootstrap: dist/ -> vendor/bootstrap/
mkdir -p "$DEST/bootstrap"
cp -a "$NM/bootstrap/dist/css" "$NM/bootstrap/dist/js" "$DEST/bootstrap/"

# Font Awesome
cp -a "$NM/@fortawesome/fontawesome-free" "$DEST/fontawesome-free"

# jQuery
mkdir -p "$DEST/jquery"
cp "$NM/jquery/dist/jquery.min.js" "$DEST/jquery/"

# jquery.easing
mkdir -p "$DEST/jquery-easing"
cp "$NM/jquery.easing/jquery.easing.min.js" "$DEST/jquery-easing/"

# bootstrap-datepicker
mkdir -p "$DEST/bootstrap-datepicker"
cp -a "$NM/bootstrap-datepicker/dist/css" "$DEST/bootstrap-datepicker/"

# bootstrap-touchspin: la vista pide .../css/jquery.bootstrap-touchspin.css
mkdir -p "$DEST/bootstrap-touchspin/css"
cp "$NM/bootstrap-touchspin/dist/jquery.bootstrap-touchspin.css" \
  "$DEST/bootstrap-touchspin/css/"

# select2: las vistas usan vendor/select2/dist/...
cp -a "$NM/select2" "$DEST/select2"

# Chart.js: la vista pide vendor/chart.js/Chart.min.js
mkdir -p "$DEST/chart.js"
cp "$NM/chart.js/dist/Chart.min.js" "$DEST/chart.js/"

echo "[sync-public-vendor] listo. Rutas: $DEST"
