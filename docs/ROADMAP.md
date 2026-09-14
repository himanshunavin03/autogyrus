# AutoGyrus roadmap

Updated: 2026-09-14. Status labels: verified, implemented/unverified, partial, planned, blocked. Historical reports are evidence of earlier local runs, not a fresh production validation.

| Milestone | Objective and dependencies | Status | Acceptance criteria and evidence | Remaining work |
| --- | --- | --- | --- | --- |
| M0 WordPress marketplace | Existing plugin/theme, ACF, pages and REST; no search-service dependency | Partial | Custom PHP lint previously passed; existing local site and theme/plugin are present | Address previously reviewed public REST, dealer isolation, and lead validation issues; integration tests |
| M0a Frontend visual refresh | CSS-only black/white body and red header/footer on existing WordPress theme | Implemented; public-page browser checks verified | Home/archive/detail screenshots at 375/768/1024/1440; no horizontal overflow or browser errors; Standard and existing WordPress AI search submitted | Authenticated dealer visual review and accessibility audit before release; Python integration remains M4 |
| M1 Canonical `ag_*` schema | 45-table MySQL foundation for provider/vehicle/inventory/history | Verified locally | Read-only 2026-09-14 database check found 45 tables, 161 FK references, 347 indexes, and 21 vehicle master rows | Add deployment automation; optional engine-compatible CHECK hardening |
| M2 Toyota provider import | Normalize Toyota feed and mirror into WordPress | Partial | Earlier `codex-log/import_report.md` records 21 Toyota vehicles; code shows post-commit import hook | Fix new-WordPress-post creation and robust mapping; repeat live smoke tests |
| M3 Separate search service | Typed parser, MySQL projection, Qdrant index, ranking, API, tests and docs | Implemented; local tests verified, deployment unverified | 123 tests passed before final documentation pass; read-only MySQL projection and local model load worked; final rerun recorded in `docs/search/TEST_STATUS.md` | Server Qdrant, Docker build, load benchmark, deployment verification |
| M4 WordPress search integration | Authenticated server-side API client, ID resolution, durable post-commit indexing, fallback | Planned; depends on M3 service deployment and M2 sync repair | Search results appear in WordPress with no browser key exposure; changed/deactivated vehicles reconcile; integration tests | Implement client, durable outbox/scheduler, observability |
| M5 Production hardening | Private deployment, rate limits, load test and release controls | Planned; depends on M4 | 30–40k index load and sub-400-ms internal target measured, security review, backup/rebuild drill | Benchmark, monitoring, runbook, secret rotation |

Do not treat a document, placeholder class, or syntax check alone as milestone completion. The current working milestone is M3 validation and handoff.
