# Despluge REMS + Nginx Proxy Manager (red `proxy-network`)

La imagen usa **PHP 8.4** para que `composer install` sea compatible con `google/recaptcha` 1.4.x del `composer.lock` (esas versiones exigen `php >= 8.4`).

## Requisitos en el servidor

- Docker y Docker Compose v2
- Misma red que usa NPM, creada por tu stack en `/opt/docker/nginx-proxy`:

  ```bash
  docker network ls | grep proxy-network
  ```

  Debe existir `proxy-network`. Si tuviera otro nombre, edita `docker-compose.yml` en `name:`.

- DNS: `A` de `rems.admetricas.com` → IP del servidor (ya lo tienes)

## 1. Subir el código

Clona o copia **toda** la raíz del proyecto (donde están `app/`, `public/`, `composer.json`, carpeta `deploy/`) a una ruta fija, por ejemplo:

```bash
cd /opt/docker/rems
# aquí vive el repo subdominio.rems completo
```

## 2. Configurar `.env`

En la raíz del proyecto, copia y edita (base URL, BD, `JWT`, webhooks, etc.):

- `app.baseURL = 'https://rems.admetricas.com/'`
- `CI_ENVIRONMENT = production` (o `development` solo para pruebas)

Asegúrate de que el host de la base de datos sea alcanzable **desde dentro del contenedor** (a veces `host.docker.internal` o la IP del host en la red bridge, o un contenedor `mysql` en la misma red).

## 3. Permisos `writable/`

```bash
chmod -R 775 writable/
chown -R www-data:www-data writable/   # ajusta usuario si aplica
```

Dentro del contenedor, Apache corre como `www-data`.

## 4. Construir y levantar

Desde la **raíz del repo** (carpeta que contiene `app/` y `deploy/`):

```bash
docker compose -f deploy/docker/docker-compose.yml up -d --build
```

Comprueba:

```bash
docker ps | grep rems-app
docker exec rems-app curl -sI -H "Host: localhost" http://127.0.0.1/ | head -5
```

## 5. Nginx Proxy Manager (puerto 81)

**Add Proxy Host**

| Campo | Valor |
|--------|--------|
| Domain Names | `rems.admetricas.com` |
| Scheme | `http` |
| Forward Hostname / IP | `rems-app` (nombre del contenedor; en la misma red `proxy-network`) |
| Forward Port | `80` |
| Block Common Exploits | On |
| Websockets | Off (suficiente para CI4 por ahora) |

Pestaña **SSL**: certificado **Let's Encrypt**, forzar SSL.

## 6. Probar

Abre `https://rems.admetricas.com/`. Ruta pública de webhook (según [Routes](app/Config/Routes.php)):

`https://rems.admetricas.com/api/webhook/instagram`

## 7. Contenedor en `Restarting` y “sin puerto”

- **No hay columna `PORTS`**: en este `docker-compose` **no** se publica `0.0.0.0:80->80` a propósito; Nginx Proxy Manager habla con `rems-app` por la red Docker `proxy-network`. Eso es correcto.
- **Estado `Restarting`**: el entrypoint o Apache termina con error. En el servidor ejecuta:
  ```bash
  docker logs rems-app --tail 80
  ```
  Causas típicas: **falta `composer.json` en el volumen** (ruta de `..` en `docker-compose` apunta a la carpeta incorrecta), **volumen vacío**, **fallo de `composer install`** (red, memoria) o **`.env` no aplica al arranque** (el fallo luego se ve al abrir la web).

## 8. Depuración de red (NPM)

Si no resuelve el nombre `rems-app` desde NPM, en la misma red debería funcionar. Si NPM está en otra red, conecta el servicio `rems` a la red de NPM o añade un `ports: "127.0.0.1:9080:80"` y en NPM reenvía a `172.17.0.1:9080` o la IP de bridge del host (menos limpio que una sola red compartida).
