# Search HTTP API contract

Version: `/api/v1` (2026-09-14). Content type: JSON. WordPress must send requests server-to-server on private networking and never expose either API key in browser JavaScript. `X-API-Key: <PUBLIC_API_KEY>` protects parse/search; a different `ADMIN_API_KEY` protects index operations. `/health` and `/ready` require no key but should remain on private networking. `X-Request-ID` is accepted when safe, otherwise generated; returned on every response. Request JSON is capped at 8 KiB by default and `query` at 500 characters.

| Endpoint | Key | Request | Success |
| --- | --- | --- | --- |
| `GET /health` | None | None | `200 {"status":"ok"}` |
| `GET /ready` | None | None | `200 {"status":"ready"}` or `503` |
| `POST /api/v1/parse` | Public | `QueryRequest` | `200 SearchIntent` |
| `POST /api/v1/search` | Public | `QueryRequest` | `200 SearchResponse` |
| `POST /api/v1/index/rebuild` | Admin | None | `202 {"status":"accepted",...}`; production should use CLI |
| `POST /api/v1/index/vehicles` | Admin | `{"vehicle_ids":[1,2]}` (1–500 IDs) | `200` indexed/skipped/deleted/failed counters |
| `POST /api/v1/index/vehicle/{vehicle_id}` | Admin | None | `200 {"vehicle_id":1,"status":"indexed|skipped|deleted"}` |
| `DELETE /api/v1/index/vehicle/{vehicle_id}` | Admin | None | `200 {"vehicle_id":1,"status":"deleted"}` |

`QueryRequest`: `query` string (1–500 chars), `page` integer (1–1000, default 1), `page_size` integer (1–50, default 12), `sort_order` enum (`relevance`, `price_asc`, `price_desc`, `year_desc`, `mileage_asc`). Extra fields are rejected. `SearchIntent` has typed `hard_filters`, `soft_preferences`, `excluded_values`, `detected_entities`, `intent_profiles`, `confidence`, `unresolved_terms`, and pagination/sort fields. The full OpenAPI schema is served at `/openapi.json` on private networking.

Example search request:

```http
POST /api/v1/search HTTP/1.1
Content-Type: application/json
X-API-Key: <PUBLIC_API_KEY>

{"query":"Toyota RAV4 under $40,000 for a family of four","page":1,"page_size":12}
```

Example response shape (scores and IDs are illustrative, not a live result):

```json
{"intent":{"hard_filters":{"make":"Toyota","model":"RAV4","max_price":40000,"min_seating":4,"availability":"available"},"soft_preferences":{"family_suitability":true,"preferred_seating":5},"excluded_values":{},"detected_entities":{"make":"Toyota","model":"RAV4","max_price":40000,"passenger_count":4},"intent_profiles":["family"],"confidence":1.0,"unresolved_terms":[],"sort_order":"relevance","page":1,"page_size":12},"hits":[{"vehicle_id":42,"score":0.68,"reasons":["Seats 5 people","Within the requested price","Matches your family requirement"]}],"total_candidates":1,"timing":{"parse_ms":1.2,"embedding_ms":15.0,"retrieval_ms":12.0,"rerank_ms":0.4,"total_ms":28.6},"request_id":"example-id"}
```

Only canonical IDs and deterministic match information are returned. WordPress must fetch final titles, prices, images, and links from its own presentation layer after resolving those IDs. If WordPress cannot resolve an ID, omit it and trigger an indexing reconciliation.

Expected errors: `401` invalid/missing key, `413` request too large, `422` invalid schema, `503` not ready, and `500` internal error. Structured errors use `{"error":{"code":"...","message":"...","request_id":"..."}}`; validation errors also include `fields`. Timeouts are bounded by `REQUEST_TIMEOUT_SECONDS`; clients should use a slightly longer timeout, retry only idempotent requests, and use the existing WordPress search as a fallback during outage. New incompatible contracts require `/api/v2`; additive fields may be added within v1.
