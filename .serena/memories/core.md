VAMS: Laravel 13 / PHP 8.5 Inertia+Vue album/mosaic/entry app. Docker for the running API; Pest+Pint on the host.

- HTTP: `app/Http/Controllers` (web) and `app/Http/Controllers/Api`. Entity CRUD: `BaseEntityController` + `AlbumController` / `MosaicController` / `EntryController`. Shared domain type is `App\Models\BaseEntity`, not Eloquent `Model`.
- Domain models: `app/Models`. Policies: `app/Policies` (`BaseEntityPolicy` + empty Album/Mosaic/Entry subclasses).
- Vue: `resources/js/Pages` (Inertia pages), `resources/js/Components`. API resources: `app/Http/Resources`.
- Tests: `tests/Feature`, `tests/Unit`, `tests/Api`, `tests/Browser`.
- Do not treat `app/Console/Commands/StrapiImport.php` as cleanup fodder unless asked.
- Living docs live in `docs/` (Coolify DB migration, etc.). Root `ca-certificate.crt` is DigitalOcean managed-Postgres TLS CA for the old DB dump, not Coolify/Spaces/HTTPS.

See `mem:tech_stack`, `mem:suggested_commands`, `mem:conventions`, `mem:task_completion`.