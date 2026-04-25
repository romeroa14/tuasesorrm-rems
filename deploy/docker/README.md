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

## 2. Assets en `public/vendor/` (CSS/JS; no es Composer)

Las vistas cargan Bootstrap, jQuery, Font Awesome, etc. desde **`public/vendor/`** (URL `/vendor/...`). Eso **no** lo instala Composer: vienen de **`package.json`** (npm).

En la máquina donde preparas el deploy (con Node.js):

```bash
cd /ruta/del/repo
npm ci
npm run sync:public-vendor   # o: ya corre en postinstall tras npm ci
```

Sube el repo **incluyendo** la carpeta `public/vendor/` generada; en el VPS solo hace falta el volumen montado (no Node en el contenedor PHP).

Si ves la página sin estilos y 404 en `/vendor/bootstrap/...`, falta ejecutar el paso anterior o no se subió `public/vendor/`.

## 3. MySQL dedicado (incluido en `docker-compose.yml`)

El compose levanta **`rems-mysql`** (imagen `mysql:8.0`, datos persistidos en el volumen `rems_mysql_data`, **sin** abrir 3306 al host salvo que descomentéis `ports` en el yml). La app y MySQL se ven por la red interna `rems-internal`; el contenedor de PHP alcanza el motor con el **nombre de host DNS** `rems-mysql` (no uses `localhost` en CodeIgniter).

### Los **dos** `.env` (sí, son distintos)

| Fichero | Quién lo lee | Qué poner en la **contraseña** si es `Rems20$` |
|--------|----------------|-----------------------------------------------|
| **`deploy/docker/.env`** | Solo **Docker Compose** (crea el contenedor MySQL) | `REMS_MYSQL_PASSWORD=Rems20$$` — aquí **`$$` = un solo `$` real** en MySQL. |
| **`.env` en la raíz del repo** (junto a `app/`, `public/`) | **CodeIgniter** (`spark`, la web) | La clave **tal cual la usa la base**: p. ej. `database.default.password = "Rems20$"` (un dólar). **No** uses `Rems20$$` aquí: no es el mismo lenguaje que Compose; con `$$` la app intentaría una contraseña distinta a la de MySQL. |

Misma lógica para `REMS_MYSQL_ROOT_PASSWORD` vs lo que tú guardes para root; el usuario y base **deben coincidir** (p. ej. `rems` y `rems_db` en ambos lados). En **local**, si no levantas `docker compose` y usas un MySQL en tu máquina, basta con el **`.env` de la raíz** apuntando a `localhost` (o 127.0.0.1) y a tu clave; **`deploy/docker/.env` no hace falta** salvo que arranques el stack con Compose en esa máquina.

1. Crea el fichero de variables **solo** para Docker Compose (no subir a git):

   ```bash
   cp deploy/docker/.env.example deploy/docker/.env
   # Edita y REEMPLAZA los valores: no dejes "cambiar_root_seguro" / "cambiar_user_seguro"
   # (esos son placehold del ejemplo; si el volumen de MySQL ya se creó con ellos, esa ES la
   # contraseña real hasta que la cambies con ALTER USER o recrees el volumen).
   ```

2. En el **`.env` de la raíz del repo** (CodeIgniter), alinea conexión con el **mismo usuario, base y contraseña real** (no el `$$` de Docker). Con contraseña `Rems20$` en el servidor, ejemplo **correcto** en el `.env` de la raíz:

   ```ini
   database.default.DBDriver = MySQLi
   database.default.hostname = rems-mysql
   database.default.port = 3306
   database.default.database = rems_db
   database.default.username = rems
   database.default.password = "Rems20$"
   ```

   En ese mismo `.env` siguen el resto de opciones de la app: `app.baseURL`, `CI_ENVIRONMENT`, `JWT`, webhooks, etc.

3. Construcción y arranque (desde la **raíz del repo**), opcionalmente con `--env-file` si el `.env` de compose no carga (según vuestro directorio de trabajo):

   ```bash
   docker compose -f deploy/docker/docker-compose.yml up -d --build
   ```

4. **Migraciones** (primera vez o tras cambios), dentro del contenedor de la app:

   ```bash
   docker exec rems-app php spark migrate
   ```

   Incluyen la migración `UsersAndRoles` (tablas `users` y `roles` + usuario inicial). Tras un deploy **sin** dump SQL previo, el primer acceso puede ser:
   - **Email:** `admin@rems.local`  
   - **Contraseña:** `CambiarLaClave1!`  
   Luego cámbiala desde la app o actualiza el registro en MySQL. Si ya importaste un dump con `users` propio, no se inserta el admin duplicado (solo si `users` está vacío).

   **Usuario operativo (Cristian Trejo)** Tras las migraciones, crea o actualiza el usuario con email de producción (contraseña por defecto; puedes fijar `REMS_SEED_CTREJO_PASSWORD` en el `.env` de la raíz para no usar el valor por omisión):

   ```bash
   docker exec rems-app php spark db:seed CtrejoUserSeeder
   ```

   (Email: `ctrejo@tuasesorrm.com.ve`; seeder idempotente: si el email ya existe, actualiza nombre y clave.)

5. Comprueba contenedores: `docker ps` debería listar `rems-app` y `rems-mysql` en estado sano.

**Importar un dump** existente: `docker exec -i rems-mysql mysql -u rems -p<password> rems < backup.sql` (o con `root` y redirección a la base que corresponda). Ajustad credenciales y opciones según vuestro dump.

### «Access denied for user … @'172.x.x.x'» al hacer `spark migrate`

Ese error es **autenticación** (usuario/clave) o **nombre de base** desalineado entre los dos `.env`. Revisad en este orden:

0. **Contraseña con el carácter `$`** (muy frecuente)  
   En `deploy/docker/.env` (el que lee **Docker Compose**), un `$` se interpreta como inicio de variable. Si la clave real es p. ej. `Rems20$`, al arrancar el contenedor MySQL puede quedar guardada como `Rems20` mientras en el `.env` de CodeIgniter sigue siendo `Rems20$` → **Access denied**.  
   - En **`deploy/docker/.env`:** escribid cada `$` como **`$$`**. Ejemplo: `REMS_MYSQL_PASSWORD=Rems20$$` define la clave `Rems20$`.  
   - Tras corregir, si el volumen de MySQL ya se creó con la clave truncada, haced **`ALTER USER`** (como abajo) o reconfigurad la contraseña.  
   - En el **`.env` de CodeIgniter**, si usáis caracteres raros, es más seguro poner el valor **entre comillas dobles** (p. ej. `database.default.password = "Rems20$"`).

1. **Misma clave y usuario en ambos sitios**  
   En la **raíz del repo**, el `.env` de CodeIgniter debe usar **exactamente** los mismos valores que `deploy/docker/.env` del compose:
   - `database.default.username` = `REMS_MYSQL_USER`
   - `database.default.password` = `REMS_MYSQL_PASSWORD`
   - `database.default.database` = `REMS_MYSQL_DATABASE`  
   Y `database.default.hostname = rems-mysql`, `port = 3306`.

2. **Nombre de base `rems` vs `rems_db`**  
   Si en CodeIgniter tenéis `database.default.database = rems_db` pero en `deploy/docker/.env` sigue `REMS_MYSQL_DATABASE=rems`, MySQL solo creó la base `rems` al inicio. **Solución A:** en el `.env` de CodeIgniter usad `database.default.database = rems` (coherente con el ejemplo del repo). **Solución B:** mantener `rems_db` y crear la base y permisos (con `root`):

   ```sql
   CREATE DATABASE IF NOT EXISTS rems_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON rems_db.* TO 'rems'@'%';
   FLUSH PRIVILEGES;
   ```

3. **Cambiasteis contraseñas después del primer `docker compose up`**  
   MySQL guarda usuario/clave en el **volumen** (`rems_mysql_data`); al editar `deploy/docker/.env` no se actualizan solas. O bien reconfiguráis el usuario con `mysql` y `ALTER USER`, o —solo si no necesitáis los datos aún— elimináis el volumen y levantáis de nuevo (ver paso 4 del apartado de volumen en este mismo bloque).

4. **Probar credenciales** con el cliente MySQL (la clave **efectiva** que deba tener `rems`, la misma que en CodeIgniter):

   ```bash
   docker exec -it rems-mysql mysql -urems -p -e "SHOW DATABASES;"
   ```

   Si la que probáis es `Rems20$` y falla, pero con `Rems20` (sin dólar) entra, el fallo es el **punto 0** (Compose truncó el `$`).

5. **Arreglar la contraseña de `rems` en MySQL** (si la corregisteis en el `.env` con `$$` pero el volumen ya tenía otra clave), como `root` en el contenedor MySQL:

   ```sql
   ALTER USER 'rems'@'%' IDENTIFIED BY 'Rems20$';
   FLUSH PRIVILEGES;
   ```

### MySQL: «No such file or directory» (o no conecta)

Con `hostname = localhost`, MySQLi intenta un **socket** Unix que **no existe** en la imagen PHP. Usa un host por **TCP**:

| Dónde está MySQL | En `.env` (CodeIgniter) |
|------------------|-----------|
| **Servicio `rems-mysql` en este `docker-compose`** (recomendado) | `database.default.hostname = rems-mysql` |
| Servicio en el **host** (VPS), puerto 3306 | `database.default.hostname = host.docker.internal` (el compose ya añade `extra_hosts: host-gateway`) |
| **Otro** contenedor (otra red) | Conecta `rems` a esa red y el **nombre del servicio** como `hostname` |
| Desarrollo **sin** Docker, PHP y MySQL en la misma máquina | `localhost` o `127.0.0.1` según socket/TCP |

Tras cambiar el `.env` de CodeIgniter, aplica: `docker compose -f deploy/docker/docker-compose.yml up -d` (o `--force-recreate` si hace falta recrear contenedores).

**Nota:** La tabla de *host* MySQL aplica a MySQL en el anfitrión u otros contenedores. Con el servicio **`rems-mysql` incluido** no suele hacer falta tocar el bind de MySQL en el host.

### Misma caja con Laravel (Postgre) y este proyecto

En el servidor, Laravel corre contra **Postgre** (`laravel-postgres`, base `fb_google`, etc.). **Este proyecto (REMS) está hecho y probado con MySQL:** migraciones, consultas, `DATE_FORMAT`, `CONCAT`, `DATEDIFF` y similares **no** son intercambiables con **solo** cambiar el `.env` a `Postgre` en CodeIgniter. Reutilizar el **mismo** Postgre de Laravel con REMS hoy implicaría **portar** el esquema y el SQL, no solo la conexión.

**Recomendación práctica:** usad el servicio **MySQL incluido** en `docker-compose.yml` (`rems-mysql`, tabla arriba) o, si no encaja, MySQL en el host u otro contenedor con un `hostname` alcanzable. Laravel y su Postgre quedan independientes. La app REMS se comporta como en el repositorio (MySQLi).

**Si más adelante** migráis a Postgre, la imagen ya incluye `pdo_pgsql` en el `Dockerfile` y en `.env` podría usarse p. ej. `database.default.DBDriver = Postgre` y `port = 5432`, **después** de adaptar migraciones y consultas.

**Solo** para poner el contenedor `rems-app` en la **misma red** que `laravel-postgres` (p. ej. otras pruebas o un proxy interno al mismo contenedor) existe el override `deploy/docker/docker-compose.laravel.yaml`:

1. Averigua el **nombre** de la red de Docker a la que está unido `laravel-postgres`:

   ```bash
   docker inspect laravel-postgres -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}'
   ```

2. Ese string debe ponerse en `networks: laravel-db: name: ...` dentro de `docker-compose.laravel.yaml` (a veces es `laravel_laravel-network` u otra variante, según el `project` de Compose de Laravel).

3. Arranque con ambos archivos:

   ```bash
   docker compose -f deploy/docker/docker-compose.yml -f deploy/docker/docker-compose.laravel.yaml up -d
   ```

4. Mientras REMS use MySQL, el `hostname` de la base en `.env` **no** debe ser `laravel-postgres` (eso es un servidor **Postgre**).

**Alternativa sin tocar el YAML:** con el contenedor ya en marcha, red manual:

`docker network connect <red_de_laravel_postgres> rems-app`

(útil para pruebas; en producción, declararlo en el override es preferible).

## 4. Error: «The WRITEPATH is not set correctly.»

CodeIgniter needs `writable/` (y subcarpetas) **escribible** para `www-data`. Con volumen hacia el host, el `entrypoint` aplica `chown`/`chmod` al arranque. Si aún falla, en el **host**:

```bash
cd /ruta/del/proyecto
sudo chown -R 33:33 writable
sudo chmod -R 775 writable
```

(`33:33` = `www-data` en Debian; comprobar UID: `docker exec rems-app id www-data`.)

## 5. Construir y levantar (atajo)

Misma ruta que en la sección 3, por si buscas solo el comando:

```bash
# Requisito: deploy/docker/.env (desde .env.example) y .env de CodeIgniter con rems-mysql
docker compose -f deploy/docker/docker-compose.yml up -d --build
```

Comprueba:

```bash
docker ps | grep -E 'rems-app|rems-mysql'
docker exec rems-app curl -sI -H "Host: localhost" http://127.0.0.1/ | head -5
```

## 6. Nginx Proxy Manager (puerto 81)

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

## 7. Probar

Abre `https://rems.admetricas.com/`. Ruta pública de webhook (según [Routes](app/Config/Routes.php)):

`https://rems.admetricas.com/api/webhook/instagram`

## 8. Contenedor en `Restarting` y “sin puerto”

- **No hay columna `PORTS`**: en este `docker-compose` **no** se publica `0.0.0.0:80->80` a propósito; Nginx Proxy Manager habla con `rems-app` por la red Docker `proxy-network`. Eso es correcto.
- **Estado `Restarting`**: el entrypoint o Apache termina con error. En el servidor ejecuta:
  ```bash
  docker logs rems-app --tail 80
  ```
  Causas típicas: **falta `composer.json` en el volumen** (ruta de `..` en `docker-compose` apunta a la carpeta incorrecta), **volumen vacío**, **fallo de `composer install`** (red, memoria) o **`.env` no aplica al arranque** (el fallo luego se ve al abrir la web).

## 9. Depuración de red (NPM)

Si no resuelve el nombre `rems-app` desde NPM, en la misma red debería funcionar. Si NPM está en otra red, conecta el servicio `rems` a la red de NPM o añade un `ports: "127.0.0.1:9080:80"` y en NPM reenvía a `172.17.0.1:9080` o la IP de bridge del host (menos limpio que una sola red compartida).
