# Search test status

Last validated run: **2026-09-14 11:38 MDT**, Python 3.11.9 on Windows. Branch `main`; base commit `e84de9b`; search files uncommitted.

| Check | Command | Result |
| --- | --- | --- |
| Unit/API/evaluation/Qdrant-test-double suite | `python -m pytest -q` from `services/autogyrus-search/` | **123 passed, 0 failed, 0 skipped**, 1 warning, 18.22 s. Warning: Qdrant payload indexes have no effect in the in-memory client. |
| Static lint | `python -m ruff check autogyrus_search tests scripts` | All checks passed. |
| Python syntax | `python -m compileall -q autogyrus_search` | Exit 0. |
| Curated parser evaluation | Included in pytest, `tests/evaluation_queries.jsonl` | 100 distinct queries with expected partial intents passed. |
| Real CPU model, offline | `HF_HUB_OFFLINE=1 python -c "...SentenceTransformer(...).encode(...)..."` | MiniLM loaded locally; 384-dimensional vector produced. |
| Read-only MySQL + ephemeral Qdrant smoke | `MYSQL_DSN=<local read-only DSN> HF_HUB_OFFLINE=1 python scripts/smoke_local.py` | 21 indexed, 0 failed, 3 hits for “Toyota SUV under $50,000”; 384-dimensional query embedding. The existing local credentials were supplied through the process environment and not recorded here. |
| Docker availability | `docker info --format '{{.ServerVersion}}'` | Docker CLI present; engine unavailable, so no build. |

Test coverage is **not measured**. Untested areas include a real Qdrant server with payload indexes and private networking, production MySQL permissions, 30–40k vehicle throughput and latency, WordPress HTTP integration, durable change queue/retry operations, deployment and rollback, and security/load testing. The in-memory smoke does not prove production latency.
