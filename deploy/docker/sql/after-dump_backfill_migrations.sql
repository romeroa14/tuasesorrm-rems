-- Tras importar un mysqldump con el esquema completo, la tabla `migrations` a menudo
-- no refleja todas las migraciones del repositorio. `php spark migrate` entonces
-- intenta volver a crear tablas (p. ej. business_conditions) → "already exists".
--
-- Uso: desde el servidor, con el nombre de base correcto (sustituye a0051406_rems
-- o usa USE antes):
--   docker exec -i rems-mysql mysql -u rems -p'...' a0051406_rems < deploy/docker/sql/after-dump_backfill_migrations.sql
--
-- Ojo: si tu tabla `migrations` no tiene columnas (version, class, group, namespace, time, batch)
-- como en CodeIgniter 4, revisa con DESCRIBE migrations y ajusta.
--
-- Si el INSERT falla por clave duplicada, elimina primero las filas de migraciones de este
-- proyecto (o haz backup, TRUNCATE `migrations` si solo almacenáis CI4 y estáis seguros):
--   DELETE FROM `migrations` WHERE `namespace` = 'App\\Database\\Migrations' AND `group` = 'default';
-- Luego vuelve a ejecutar este script.
-- Valores: batch 1, time fijo; spark migrate:status debe listar todo como aplicado.

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
('2024-02-20-000000', 'UsersAndRoles', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-21-012234', 'RealStateSearches', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-21-192237', 'MyVisits', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-22-151505', 'Leads', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-22-160000', 'RenameLeadsTableToLower', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-22-175144', 'Delegations', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-23-164123', 'AssignedClients', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-02-29-220604', 'RRSSPublications', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-03-01-212715', 'Kindrrss', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-03-25-161514', 'PropertyChecklist', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2024-03-25-162201', 'ManagementPropertyChecklist', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2025-03-19-175806', 'BusinessConditions', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1),
('2026-04-07-000001', 'CrmModule', 'default', 'App\\Database\\Migrations', UNIX_TIMESTAMP(), 1);
