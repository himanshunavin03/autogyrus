# AutoGyrus Search

Standalone Python 3.11+ search service for the existing AutoGyrus WordPress repository. MySQL `ag_*` tables remain authoritative; Qdrant is a disposable derived index. No generative LLM or paid AI API is used.

## Local setup

1. Create a Python 3.11 virtual environment and run `pip install -e '.[test]'`.
2. Copy `.env.example` to `.env` and set a **read-only** MySQL DSN plus distinct public/admin API keys. Keep `.env` local.
3. Start Qdrant privately and run `autogyrus-search rebuild` to initialize the collection and index available vehicles.
4. Start the API with `uvicorn autogyrus_search.api:app --host 127.0.0.1 --port 8080`.
5. Check `/health` and `/ready`; call `/api/v1/parse` or `/api/v1/search` with `X-API-Key: <PUBLIC_API_KEY>`.

The initial embedding model is `sentence-transformers/all-MiniLM-L6-v2`. The Docker build predownloads it; local development may download it on first use. CPU deployment is supported. The first startup loads spaCy and the embedding model once. A model change requires rebuilding the index and verifying vector dimensions.

Run `pytest -q` and `ruff check autogyrus_search tests`. The 100-query parser dataset is in `tests/evaluation_queries.jsonl`; edit `tests/generate_evaluation.py` to curate additional cases, then regenerate.

See [architecture](../../docs/search/SEARCH_ARCHITECTURE.md), [data mapping](../../docs/search/SEARCH_DATA_MAPPING.md), [API contract](../../docs/search/API_CONTRACT.md), and [deployment status](../../docs/search/DEPLOYMENT_STATUS.md). WordPress integration is deliberately deferred.
