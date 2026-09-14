# AutoGyrus architecture decisions

Updated: 2026-09-14. These accepted decisions are not silently changed; add a superseding ADR if requirements change.

## ADR-001 — Canonical data

- Date/status/milestone: 2026-09-14, accepted, M1/M3.
- Context: WordPress and provider imports already populate normalized `ag_*` tables.
- Decision: MySQL `ag_*` tables remain the canonical vehicle, inventory, and provider source of truth.
- Alternatives: A second search-owned vehicle database; WordPress post meta as the only search source.
- Reasoning: Reuses existing identity, history, provider mapping, and transactions.
- Consequences: Search reads through a projection; MySQL schema migrations remain independently governed.
- Related files: `codex-log/database/*.sql`, `services/autogyrus-search/autogyrus_search/repository.py`.

## ADR-002 — Derived vector index

- Date/status/milestone: 2026-09-14, accepted, M3.
- Context: Semantic retrieval needs filterable vectors.
- Decision: Qdrant is disposable, derived, and rebuildable from MySQL; canonical IDs are point IDs.
- Alternatives: Qdrant as the authoritative store; SQL-only search.
- Reasoning: Prevents split-brain and supports semantic plus payload retrieval.
- Consequences: A rebuild/reconciliation path is mandatory; no independent Qdrant edits.
- Related files: `autogyrus_search/vector.py`, `service.py`.

## ADR-003 — Separate deployment in one repository

- Date/status/milestone: 2026-09-14, accepted, M3/M4.
- Context: WordPress remains a PHP marketplace; search needs Python/ML dependencies.
- Decision: Run `services/autogyrus-search/` as an independent FastAPI service while retaining one AutoGyrus monorepo.
- Alternatives: Embed Python in WordPress; create another GitHub repository.
- Reasoning: Independent scaling and failure boundaries without duplicating project history.
- Consequences: Private HTTP contract, separately managed secrets, deployment, and health checks.
- Related files: `services/autogyrus-search/Dockerfile`, `docs/search/API_CONTRACT.md`.

## ADR-004 — Deterministic NLP

- Date/status/milestone: 2026-09-14, accepted, M3.
- Context: Search must not call a paid or generative LLM API.
- Decision: Use spaCy, controlled lexicons, regular expressions, RapidFuzz, and a versioned ontology; use a local CPU embedding model for retrieval.
- Alternatives: Generative query parsing and explanations; keyword-only search.
- Reasoning: Predictable, inspectable behavior and no paid API dependency.
- Consequences: Curated ontology/evaluation upkeep; unresolved terms are surfaced.
- Related files: `autogyrus_search/parser.py`, `ontology.json`, `tests/evaluation_queries.jsonl`.

## ADR-005 — Validated intent, never generated SQL

- Date/status/milestone: 2026-09-14, accepted, M3.
- Context: Natural language is untrusted input.
- Decision: Convert it to a typed whitelisted `SearchIntent`; SQL is fixed and parameterized.
- Alternatives: Generated SQL or arbitrary JSON filters passed through to MySQL.
- Reasoning: Prevents unrestricted queries and constrains API behavior.
- Consequences: New filters require explicit model, parser, mapping, index, and test changes.
- Related files: `autogyrus_search/models.py`, `parser.py`, `repository.py`.

## ADR-006 — Mandatory hard filters

- Date/status/milestone: 2026-09-14, accepted, M3.
- Context: A semantic match could violate price, seating, or location constraints.
- Decision: Apply hard filters in Qdrant and recheck them before ranking; unknown values do not pass a requested hard constraint.
- Alternatives: Rank all candidates and treat constraints as boosts.
- Reasoning: User requirements must not be overridden by similarity.
- Consequences: Recall may decrease where source data is missing; report missing mappings.
- Related files: `autogyrus_search/vector.py`, `ranking.py`.

## ADR-007 — Deterministic explanations

- Date/status/milestone: 2026-09-14, accepted, M3.
- Context: Buyers need reasons tied to actual vehicle evidence.
- Decision: Generate reasons from verified payload fields and configured profiles, not a language model.
- Alternatives: Free-form model-generated prose.
- Reasoning: Avoids ungrounded assertions, especially for absent canonical reliability/winter/future-value scores.
- Consequences: Explanation vocabulary is intentionally limited.
- Related files: `autogyrus_search/ranking.py`, `docs/search/SEARCH_DATA_MAPPING.md`.

## ADR-008 — Secret boundaries

- Date/status/milestone: 2026-09-14, accepted, M3/M4.
- Context: WordPress, Python, MySQL, and Qdrant require distinct trust boundaries.
- Decision: Pass DB/service credentials only through environment or secret management; never commit values. Use separate public/admin API keys and a least-privilege MySQL reader.
- Alternatives: Browser-exposed keys, shared admin key, committed `.env`.
- Reasoning: Limits compromise and prevents accidental credential publication.
- Consequences: Deployment must provision secrets and private networking.
- Related files: `autogyrus_search/config.py`, `api.py`, `.env.example`.

## ADR-009 — No search queue migration in this phase

- Date/status/milestone: 2026-09-14, accepted for M3; revisit in M4.
- Context: `ag_import_job` is an import queue, not a search outbox. The importer fires a post-commit action with the changed vehicle ID.
- Decision: Provide manual/scheduled full and batch indexing now; design a durable additive outbox with WordPress integration later.
- Alternatives: Reuse `ag_import_job` for search; modify WordPress importer in this phase.
- Reasoning: Avoids invasive and semantically incorrect reuse before integration design.
- Consequences: Production freshness requires a scheduled rebuild or a later durable enqueue mechanism.
- Related files: `class-autogyrus-import-service.php:193`, `autogyrus_search/service.py`, `cli.py`.

## ADR-010 — CSS-only storefront refresh

- Date/status/milestone: 2026-09-14, accepted, M0a.
- Context: The supplied reference calls for a red-and-white storefront, while the user requires wording and functionality to remain unchanged and later clarified that the body should default to black and white.
- Decision: Change only the AutoGyrus theme stylesheet. Centralize color, spacing, radius, and shadow tokens; preserve every template, data source, JS hook, and backend contract. Keep red mainly in the header/footer. Body buttons default near-black/white, body links and prices default near-black, and hover feedback uses a small shadow with little color shift.
- Alternatives: Rebuild the homepage markup or replace the frontend framework.
- Reasoning: Existing templates already expose reusable classes and dynamic content, so CSS is sufficient for this visual scope.
- Consequences: Screenshot layout is a visual guide rather than a pixel copy; existing hero art and exact text stay in place. Authenticated dealer UI requires a separate visual check.
- Related files: `app/public/wp-content/themes/autogyrus/style.css`, `docs/UI_THEME_REDESIGN.md`.
