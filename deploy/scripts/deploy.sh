#!/usr/bin/env bash
# Server-side deploy for REMS (Docker, code mounted as a volume).
# Usage: bash deploy/scripts/deploy.sh <commit-sha>
# Rolls back to the previous commit if the health check fails.
set -euo pipefail

TARGET="${1:?usage: deploy.sh <commit-sha>}"
APP_CONTAINER="${APP_CONTAINER:-rems-app}"
COMPOSE=(docker compose -f deploy/docker/docker-compose.yml)

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  echo "[deploy] ABORT: the server working tree has local changes. Commit or discard them first." >&2
  exit 1
fi

PREVIOUS="$(git rev-parse HEAD)"
echo "[deploy] $PREVIOUS -> $TARGET"

release() {
  git fetch --quiet origin
  git checkout --quiet --detach "$1"
  docker exec "$APP_CONTAINER" composer install --no-dev --no-interaction --optimize-autoloader
  docker exec "$APP_CONTAINER" php spark migrate --all -n || return 1
  # Queue workers keep the old code in memory; recreate them.
  "${COMPOSE[@]}" restart rems-queue-worker-1 rems-queue-worker-2 rems-queue-worker-3
  docker exec "$APP_CONTAINER" php -r 'if (function_exists("opcache_reset")) opcache_reset();' || true
}

healthy() {
  for _ in 1 2 3 4 5 6; do
    code="$(docker exec "$APP_CONTAINER" php -r '$c=@file_get_contents("http://127.0.0.1/login"); echo $c===false?0:200;' || echo 0)"
    [ "$code" = "200" ] && return 0
    sleep 5
  done
  return 1
}

if release "$TARGET" && healthy; then
  echo "[deploy] OK at $TARGET"
else
  echo "[deploy] FAILED, rolling back to $PREVIOUS" >&2
  release "$PREVIOUS" || true
  exit 1
fi
