# Migrating the Database: DigitalOcean → Coolify

This guide walks through moving VAMS's data off the DigitalOcean managed
Postgres cluster and into the Postgres resource running inside your Coolify
project. Read it fully once before running anything — the cutover section
matters more than the dump/restore commands.

## Scope

- **In scope:** Postgres database content (users, albums, entries, mosaics,
  etc.)
- **Out of scope:** file storage. Uploaded media stays on DigitalOcean Spaces
  (`DO_SPACES_*` env vars, `spaces` disk in `config/filesystems.php`) — this
  guide does not touch that, and the app doesn't need it to change.
- **Engine:** both sides are Postgres. This is a same-engine migration, not a
  MySQL→Postgres conversion, so there's no SQL dialect rewriting involved.

## Recommended strategy: let Laravel own the schema, copy only the data

Don't `pg_dump` the DO schema and blindly restore it onto Coolify. Instead:

1. Let `php artisan migrate` create the schema on the new Coolify Postgres
   (this already happens automatically via `RUN_MIGRATIONS=true` on deploy).
2. Copy **data only** from the DO database into that already-migrated schema.

Why: it guarantees the Coolify database ends up exactly matching what your
current migrations produce — no drift, no DO-specific quirks (roles,
extensions, ownership) leaking into the new instance, and no fighting with
Laravel's own `migrations` tracking table.

## Prerequisites

- Coolify Postgres resource created and running, in the same project as the
  `api` service.
- Coolify app deployed at least once with `RUN_MIGRATIONS=true`, so the
  schema already exists on the target database (check with
  `php artisan migrate:status` — see Step 2).
- `psql` / `pg_dump` available locally, or use the bundled Postgres client
  inside this repo's local `postgres` container:
  `docker compose exec postgres sh`.
- Network access from wherever you run the dump to **both** databases. The
  DO cluster is reachable from the internet (with SSL); the Coolify Postgres
  resource is normally internal-only — either run the restore from inside
  the Coolify server/network, or temporarily expose the Postgres port in
  Coolify's UI for the migration window and lock it back down immediately
  after (do not leave a managed database publicly exposed).

### Step 0 — check Postgres versions match (or target ≥ source)

`pg_dump`/`pg_restore` are safest when the client tool version is the same
as, or newer than, both server versions. Mismatched major versions can
produce subtly incompatible dumps.

```bash
# Against DigitalOcean (use your own creds, don't hardcode passwords in
# scripts you might commit — use PGPASSWORD as an exported env var, or a
# .pgpass file, not inline in a command you paste into shared shells)
export PGPASSWORD='<do-db-password>'
psql "sslmode=require host=<do-db-host> port=<do-db-port> dbname=<do-db-name> user=<do-db-user>" -c 'SELECT version();'

# Against Coolify's Postgres resource
export PGPASSWORD='<coolify-db-password>'
psql "host=<coolify-db-host> port=5432 dbname=vams user=<coolify-db-user>" -c 'SELECT version();'
```

If Coolify's Postgres major version is older than DO's, bump it in Coolify
before continuing (safer than trying to downgrade a dump).

## Step 1 — confirm the target schema is current

```bash
php artisan migrate:status
```

Run this against the Coolify database (via `docker exec` into the deployed
`api` container, or locally with `DB_HOST`/`DB_PORT`/etc. pointed at Coolify's
Postgres). Every migration should show `Ran`. If not, deploy first so the
schema exists before you import data into it.

## Step 2 — take a data-only dump from DigitalOcean

Exclude ephemeral/runtime tables that don't need to survive the move —
they'll rebuild themselves naturally:

- `sessions`, `cache`, `cache_locks` — regenerated on first request
- `jobs`, `job_batches`, `failed_jobs` — queue runtime state
- `telescope_entries` (and related `telescope_*` tables) — dev/debug logs,
  often the single biggest table in the dump for no real value

```bash
export PGPASSWORD='<do-db-password>'
pg_dump \
  "sslmode=require host=<do-db-host> port=<do-db-port> dbname=<do-db-name> user=<do-db-user>" \
  --data-only \
  --disable-triggers \
  --no-owner \
  --no-privileges \
  --exclude-table-data='sessions' \
  --exclude-table-data='cache' \
  --exclude-table-data='cache_locks' \
  --exclude-table-data='jobs' \
  --exclude-table-data='job_batches' \
  --exclude-table-data='failed_jobs' \
  --exclude-table-data='telescope_entries*' \
  --exclude-table-data='migrations' \
  -Fc -f vams_data.dump
```

`-Fc` (custom format) is compressed and lets `pg_restore` reorder statements
to satisfy foreign keys automatically — safer than a plain `.sql` file for a
database with as many `foreignUuid()` relationships as this one has.

## Step 3 — restore into Coolify's Postgres

```bash
export PGPASSWORD='<coolify-db-password>'
pg_restore \
  "host=<coolify-db-host> port=5432 dbname=vams user=<coolify-db-user>" \
  --data-only \
  --disable-triggers \
  --no-owner \
  --no-privileges \
  vams_data.dump
```

`--disable-triggers` temporarily turns off foreign-key triggers during load
so insertion order doesn't matter, then re-enables them. This requires the
restoring role to own the tables (Coolify's default Postgres user does).

## Step 4 — reset auto-increment sequences

Most tables in this app use UUID primary keys (`entries`, `entry_types`,
`entry_images`, `users`, `albums`, `album_images`, `mosaics`,
`mosaic_items`) — nothing to do there. A handful use bigint auto-increment
(`media`, `album_media`, `mosaic_media`, `activities`,
`personal_access_tokens`). After a data-only restore, their sequences still
start from wherever Coolify's fresh migration left them, which will collide
with the copied IDs. Reset every sequence in one shot:

```sql
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT
            format('%I.%I', n.nspname, c.relname) AS table_name,
            a.attname AS column_name,
            pg_get_serial_sequence(format('%I.%I', n.nspname, c.relname), a.attname) AS seq
        FROM pg_class c
        JOIN pg_namespace n ON n.oid = c.relnamespace
        JOIN pg_attribute a ON a.attrelid = c.oid
        WHERE c.relkind = 'r'
          AND n.nspname = 'public'
          AND pg_get_serial_sequence(format('%I.%I', n.nspname, c.relname), a.attname) IS NOT NULL
    LOOP
        EXECUTE format(
            'SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %s), 1))',
            r.seq, r.column_name, r.table_name
        );
    END LOOP;
END $$;
```

Run this against the Coolify database with `psql -f reset_sequences.sql` or
paste it into a `psql` session.

## Step 5 — verify

Compare row counts on both sides for the tables that actually matter (not
the excluded ephemeral ones):

```bash
psql <connection> -c "
SELECT 'users', count(*) FROM users
UNION ALL SELECT 'albums', count(*) FROM albums
UNION ALL SELECT 'album_images', count(*) FROM album_images
UNION ALL SELECT 'mosaics', count(*) FROM mosaics
UNION ALL SELECT 'mosaic_items', count(*) FROM mosaic_items
UNION ALL SELECT 'entries', count(*) FROM entries
UNION ALL SELECT 'entry_types', count(*) FROM entry_types
UNION ALL SELECT 'entry_images', count(*) FROM entry_images;
"
```

Run once against DO, once against Coolify, diff the numbers. Then smoke-test
the app itself against the Coolify database: log in as a real user, open an
album, check the dashboard stats (`DashboardController`'s video-count query
uses Postgres-only `->>` JSON syntax — since both sides are Postgres this
just works, but it's worth eyeballing once).

## Step 6 — cutover with minimal downtime

A dump-and-restore captures a snapshot at the moment you ran `pg_dump`. Any
writes to the DO database after that point won't be on Coolify. To avoid
losing them:

1. Put the DigitalOcean app into maintenance mode (`php artisan down`) or
   otherwise stop it from accepting writes.
2. Re-run **Step 2 and Step 3** one final time — this second pass is fast
   since most rows are unchanged; only the incremental writes since your
   first dry-run actually move.
3. Re-run Step 4 (sequence reset — cheap, idempotent, always safe to redo).
4. Point the Coolify app's `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/
   `DB_PASSWORD` env vars at the Coolify Postgres resource (if not already)
   and deploy.
5. Smoke-test against the live Coolify deployment.
6. Switch the `app.use-vams.me` domain / DNS over.
7. Bring the old DO app back up only if you need a rollback path — otherwise
   leave it down.

## Rollback

Don't delete or downsize the DigitalOcean Postgres cluster immediately.
Keep it running (it's cheap relative to the risk) for a week or two after
cutover as a restore point. If something's wrong post-cutover, you can point
`DB_HOST` back at DigitalOcean while you investigate, since nothing in this
guide is destructive to the source.

## After you're confident

- Decommission the DigitalOcean Postgres cluster.
- Revoke/rotate the DO database credentials that were sitting in your local
  `.env` (see the commented-out block there) since they're no longer needed.
