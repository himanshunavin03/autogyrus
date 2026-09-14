# Search deployment status

Updated: 2026-09-14. **No external deployment or push has occurred.** Docker build: **not run** because the Docker Desktop engine was unavailable. Local unit/API tests and a read-only MySQL + ephemeral Qdrant smoke succeeded. `/health` is covered by TestClient; `/ready` and payload indexes have not been validated against a live Qdrant server.

## Required environment

The service requires Python 3.11+, MySQL 8 with the existing `ag_*` schema, Qdrant, CPU/RAM sufficient for MiniLM and spaCy, and private connectivity from WordPress. Configure `APP_ENV`, `MYSQL_DSN`, `QDRANT_URL`, `QDRANT_COLLECTION`, `PUBLIC_API_KEY`, `ADMIN_API_KEY`; optional `QDRANT_API_KEY`, `EMBEDDING_MODEL`, `REQUEST_TIMEOUT_SECONDS`, `MAX_REQUEST_BYTES`. Secrets belong in deployment secret management, never Git or browser JavaScript. Production API keys must be distinct and at least 32 characters. `.env.example` contains placeholders only.

Use a dedicated **SELECT-only** MySQL account with access only to the required `ag_*` projection and lookup tables: `ag_vehicle_master`, `ag_inventory`, `ag_vehicle_price`, `ag_vehicle_specification`, `ag_vehicle_fuel_economy`, `ag_vehicle_location`, `ag_manufacturer`, `ag_model`, `ag_trim`, `ag_body_style`, `ag_fuel_type`, `ag_drive_type`, `ag_transmission_type`, `ag_dealer_master`, `ag_city`, `ag_state_province`, `ag_vehicle_feature`, and `ag_vehicle_feature_value`. No `INSERT`, `UPDATE`, `DELETE`, DDL, WordPress user-table access, or SQL dump access is required.

## Deployment procedure (not executed)

1. Audit the existing tracked WordPress configuration and SQL dumps before using this public repository for any deployment. Rotate any exposed live credentials and remove sensitive tracked data through an approved repository-history plan.
2. Provision private MySQL/Qdrant networks, a SELECT-only MySQL account, Qdrant storage, independent API keys, TLS/gateway access controls, and edge rate limits. Keep admin indexing routes internal.
3. Build `docker build -t autogyrus-search:0.1.0 services/autogyrus-search` from repository root. The Dockerfile preloads MiniLM and runs one CPU worker; confirm image build and package data in the target environment.
4. Start Qdrant and service with environment secrets; inspect `/health` and `/ready`. Run `autogyrus-search rebuild` as a separate scheduled/one-off worker, not through a long HTTP request.
5. Verify collection dimensions, payload indexes, canonical ID reconciliation, sample hard filters, auth boundaries, failure/retry behavior, and a rollback/rebuild drill.
6. Benchmark with 30–40k representative vehicles. Measure parse, embedding, retrieval, reranking, and total latency before claiming the requested sub-400-ms target.
7. Only after validation, implement a server-side WordPress client using `docs/search/API_CONTRACT.md` and a durable post-commit indexing mechanism.

Known production gaps: no server Qdrant integration test, no Docker build, no live deployment, no benchmark, no durable outbox, no WordPress client, no measured model memory requirement. Existing WordPress security/repository hygiene issues remain separate work.
