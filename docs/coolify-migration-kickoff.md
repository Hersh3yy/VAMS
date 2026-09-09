# Kickoff — Guide VAMS from DigitalOcean → Coolify

**Paste the block below into a fresh Claude Code session opened in `koala/VAMS`.** It's the
immediate-priority follow-on after the cross-project cleanup + mapping. It names the skills to use
and encodes the constraints Hiren has already set, so the session starts grounded instead of
re-deriving everything.

---

You're picking up **VAMS** (Laravel 12 headless CMS, Postgres 15, Inertia+Vue admin, Docker) to
**guide its migration from DigitalOcean to Coolify** — now the immediate priority. Assess and plan
first; this is a careful infra cutover, not a code sprint.

**Read first, in order:**
- `PROJECT.md` — the cockpit (status, issues, roadmap). Coolify is roadmap item **`e`** (far-future today — promote it).
- `docs/vams-renewal-plan.html` — the roadmap, tracks A–F. **E = DO→Coolify.**
- `docs/adversarial-review.md` — the security/correctness backlog (matters before a production cutover).
- `docs/session-kickoff.md` — prior session context.

**Skills to use (call them explicitly):**
- **`project-cockpit`** — first, re-assess VAMS and refresh `PROJECT.md`: promote Coolify from far→near future and split `e` into concrete sub-tasks. Keep the map + diary current every session.
- **`wizard`** (mattpocock-skills) — the migration has steps only Hiren can do: the Coolify dashboard, DNS, SSL, secrets, DB-backup config, the cutover. Generate an interactive bash **wizard** that walks him through those human-only steps. Do **not** try to click the Coolify UI yourself.
- **`research`** (mattpocock-skills) — before wiring anything, research the Coolify specifics against primary docs and capture them in `docs/coolify-notes.md`: Traefik + Let's Encrypt SSL, per-app env/secrets, scheduled Postgres backups, the `/up` health check, and rollback / blue-green deploy on Coolify.
- **`grilling`** (mattpocock-skills, before cutover) — stress-test the cutover + rollback plan so a failed deploy can't take the live site down.

**Hard constraints (Hiren's — do not violate):**
- **No app-code/config changes without an explicit green light.** Assess, plan, write docs, build the wizard — but don't touch `.php`/Dockerfile/config until Hiren says go. **`main` is sacred; work on `AI-REFACTOR`.**
- **CI must stay free** (no revenue yet) — GitHub Actions free tier or Coolify's built-in.
- **Data move = a script, not a heavy migration.** Only a few hundred rows. `pg_dump | pg_restore`, or a re-runnable Artisan streamer. **Spaces objects do NOT move** — both hosts read the same DO Spaces bucket, so only Postgres migrates.
- **Coolify must replace what DO gave for free:** scheduled **DB backups**, **rollback / blue-green** on a failed deploy, and a **`/up` health check**. Domain + SSL via Traefik/Let's Encrypt; set `APP_URL` + the Sanctum stateful domains for the admin.
- The container is missing **`gd`** (breaks 25 image tests) — fold **`a2` (add gd to the Dockerfile)** into the "reproducible image" work, and land **`a3` (free CI)** before/with the cutover so deploys are gated by a green suite.

**Suggested sequence:**
1. `project-cockpit` re-assess → promote Coolify to near-future, split `e` into: reproducible image · secrets · DB backup · `/up` health check · rollback/blue-green · DNS+SSL · data-move script · cutover.
2. `research` Coolify specifics → `docs/coolify-notes.md`.
3. Reproducible Docker image (fold in `gd` = `a2`) + `/up` health check + free CI (`a3`) — behind Hiren's green light.
4. `wizard` for the human-only Coolify setup: create app, env/secrets, DB-backup schedule, DNS + SSL, first deploy to a **staging** domain.
5. Data-move script (`pg_dump|restore` or Artisan streamer); dry-run against staging.
6. `grilling` the cutover + rollback plan → then cut over.
7. Post-Coolify the 2 MB upload limit is gone → unblocks the **WebP pipeline** work (`../shawneyyy.portfolio/docs/webp-pipeline-ticket.md`; VAMS roadmap `f1` image variants).

Keep `PROJECT.md`'s roadmap + diary updated as you go. When the ClickUp token is in the env, run the cockpit's ClickUp sync.

---

_Drafted 2026-09-09 as the handoff from the cleanup/mapping phase. Coolify specifics sourced from the VAMS renewal plan (track E) and Hiren's stated constraints; verify against the live repo before acting._
