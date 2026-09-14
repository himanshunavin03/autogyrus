# AutoGyrus project state

Authoritative local handoff, updated **2026-09-14**. Current branch: `ui/red-white-theme-refresh`. Latest existing validated base commit: `e84de9b` (2026-09-14). The theme change and earlier search-service/continuity files are **uncommitted**; they have not been pushed.

## Product and architecture

AutoGyrus is a WordPress automotive marketplace with a custom plugin and theme under `app/public/wp-content/`. The existing normalized 45-table MySQL `ag_*` schema remains canonical for provider, dealer, vehicle, inventory, price, feature, image, location, history, and import data. The Toyota provider pipeline imports into `ag_*` and has a WordPress compatibility layer. A separate Python/FastAPI application in `services/autogyrus-search/` reads canonical data and builds a derived Qdrant search index. WordPress integration with that HTTP API is planned, not implemented.

## Frontend theme update (2026-09-14)

On branch `ui/red-white-theme-refresh`, the WordPress theme stylesheet has a CSS-only visual refresh: red header/footer and a near-black-and-white body by default, including buttons, links, prices, and value text. Body hover feedback uses subtle shadows and nearly white surfaces. Site wording, PHP markup, JavaScript hooks, plugin, database, import pipeline, and Python service remain unchanged. Screenshots and verification details are in `docs/UI_THEME_REDESIGN.md` and `docs/ui-theme-screenshots/`. This work is uncommitted and unpushed alongside the earlier untracked search-service work. The local browser confirmed home/archive/detail rendering at four target widths, working Standard Search and existing WordPress AI Search, and no new browser errors. The WordPress AI Search still does not call the separate Python service; that integration remains M4.

## Status by evidence

| Area | Status | Evidence / limits |
| --- | --- | --- |
| WordPress plugin/theme | Implemented, partially verified | CSS-only theme refresh locally verified by browser; plugin and PHP/JS unchanged. Earlier review found public REST and dealer-isolation defects. |
| `ag_*` schema | Implemented and verified locally | Read-only 2026-09-14 inspection: 45 expected tables, 161 foreign-key references, 347 indexes; no CHECK constraints. |
| Toyota import | Implemented, partially verified | Historical import report: 21 Toyota vehicles; read-only database check: 21 vehicle master and inventory rows. Current compatibility sync updates matching WordPress posts but does not create a missing post. |
| Python search service | Implemented and locally tested; not deployed | 123 pytest tests passed, including 100 curated queries; real CPU model loaded offline; read-only MySQL + in-memory Qdrant smoke indexed 21 vehicles and found 3 matches. |
| Qdrant server deployment | Planned/unverified | In-memory Qdrant tests pass; no server Qdrant integration, production payload-index check, or Docker image build. |
| WordPress HTTP integration | Planned | Contract documented; no UI/plugin integration changes. |
| Production readiness | Blocked by validation and repository hygiene | No 30–40k load benchmark, no live private deployment, no durable search outbox; see below. |

## Current milestone and known limitations

M3 search-service implementation is locally validated, with server deployment validation still pending. Qdrant is not authoritative. The current `ag_*` schema has no dedicated seating column or canonical reliability, winter, or future-value score columns. Seating is derived only from explicit feature names; missing data fails hard seating filters. Family/winter ranking uses documented evidence and configurable ontology weights. Search caps retrieval at 500 candidates, so deep sorting/pagination is not globally exhaustive. Radius search requires configured city coordinates and vehicle geocodes. The requested sub-400-ms target remains unmeasured.

The existing public Git repository already tracks `app/public/wp-config.php` and SQL dumps such as `app/sql/local.sql` and `codex-log/backup/pre_autogyrus_backup.sql`. This task did not print, alter, or push their contents. The tracked configuration/dumps require a separate secret and data-exposure audit before further publication or production use; `.gitignore` alone cannot remove previously tracked history.

## Tests and deployment

See `docs/search/TEST_STATUS.md` for exact test counts and commands. Docker CLI is installed, but the Docker engine was unavailable, so no image build occurred. No external deployment, Git commit, or push occurred. The local smoke used a read-only query path against the existing MySQL database and an ephemeral in-memory Qdrant instance.

## Key locations

- WordPress core/plugin/theme: `app/public/`, `app/public/wp-content/plugins/autogyrus/`, `app/public/wp-content/themes/autogyrus/`
- Canonical SQL: `codex-log/database/`; historical reports: `codex-log/`
- Search service: `services/autogyrus-search/autogyrus_search/`; Docker/dependencies: `services/autogyrus-search/`
- Search docs: `docs/search/`; decisions/roadmap: `docs/DECISIONS.md`, `docs/ROADMAP.md`
- Restart instructions: `docs/NEXT_SESSION.md`

Next milestones: M3 server validation and repository hygiene, then M4 authenticated WordPress integration and durable indexing, then M5 30–40k performance and production hardening. Status claims must be rechecked against code, Git, and running services at the start of the next session.
