# AutoGyrus changelog

## 2026-09-14 — Frontend visual refresh (unreleased)

### Changed

- Updated only `app/public/wp-content/themes/autogyrus/style.css`: separated red brand tokens for header/footer from near-black-and-white body tokens, and refreshed hero, search controls, categories, cards, inventory, vehicle insights, and dealer dashboard surfaces.
- Set body buttons to near-black/white by default, body links/prices/value text to near-black, and hover feedback to subtle elevation with an almost-white surface for outline controls and links.
- Reduced the header AutoGyrus wordmark from weight 800 to 600 and added a restrained CSS-only fractured-letter effect with readable fallbacks.

### Validation

- Chrome/Playwright before-and-after captures of home/archive at 375, 768, 1024, and 1440 px and vehicle detail at 1440 px; no horizontal overflow, page errors, console errors, or failed requests in these passes.
- Standard search, archive filter, existing WordPress AI Search, unauthenticated dealer route, mobile drawer/filters, and hover/focus styling checked. CSS parser, PHP theme lint, JavaScript syntax checks, and `git diff --check` passed. See `docs/UI_THEME_REDESIGN.md` for scope and limits.
- No site wording, functionality, database, plugin, PHP template, JavaScript, Python service, API, import, commit, or push changed.

## 2026-09-14 — Search service M3 (unreleased)

### Added

- Standalone Python/FastAPI service under `services/autogyrus-search/` with typed automotive intent, CPU embeddings, MySQL projection, Qdrant derived index, deterministic ranking, API and CLI.
- 100-query evaluation JSONL, parser/ranking/document/API/index tests, Dockerfile and environment template.
- Search architecture, exact data mapping, API contract, deployment and continuity documents.

### Changed

- No WordPress PHP/theme code or canonical `ag_*` schema was changed.

### Fixed

- Parser distinguishes mileage from price and recognizes family passenger counts and plural winter intent.
- Full rebuild reconciles deleted canonical IDs from Qdrant.

### Security

- Separate public/admin service keys, request-size and schema limits, fixed parameterized SQL, and no committed credentials in the new service.

### Removed

- None.

### Validation

- See `docs/search/TEST_STATUS.md` for exact commands, counts and remaining untested areas. No external deployment or push occurred.
