# Search architecture

Updated: 2026-09-14. Implementation: `services/autogyrus-search/`.

## Boundaries and topology

WordPress remains the marketplace and presentation application. The independently deployable FastAPI service reads the canonical MySQL `ag_*` tables through a read-only SQLAlchemy/PyMySQL account. Qdrant holds one derived document/vector per searchable canonical vehicle ID. The service never writes to MySQL and never boots WordPress or PHP. WordPress must call the service server-to-server over private networking with a public-search API key. A distinct admin key protects indexing routes. TLS, network ACLs, and edge rate limits belong at the private gateway or reverse proxy; do not expose the administrative routes publicly.

## Indexing flow

`VehicleRepository` reads active `ag_vehicle_master` rows with current available `ag_inventory` and current price, specification, fuel-economy, location, lookup, dealer, and feature rows. `build_document` creates a concise text description, whitelisted payload, version, update timestamp, and SHA-256 content hash. `SentenceTransformer` computes an embedding on CPU. `VectorIndex` upserts it into Qdrant using the stable canonical vehicle ID. Unchanged hashes skip embedding and upsert. Missing/unavailable vehicles are deleted. A full rebuild pages through canonical IDs and also removes Qdrant IDs no longer present in MySQL. Indexing is retry-safe; failed rows are logged and reported in counters.

The existing importer commits each record before firing `autogyrus_vehicle_imported` in `class-autogyrus-import-service.php` (lines 193–201). That post-commit hook is the safest future place to enqueue changed canonical IDs. `ag_import_job` tracks import jobs, not a durable search outbox. This phase does not change WordPress or add a queue migration; schedule rebuilds or call the admin batch route after committed imports. A future durable outbox should be an additive migration with retry and de-duplication.

## Search flow

spaCy tokenization/lemmatization and phrase matching, controlled RapidFuzz typo correction, lookup-table terms, regex numeric parsing, and `ontology.json` yield a validated `SearchIntent`. Unknown fields fail Pydantic validation. Unknown query words are returned as unresolved terms. Hard filters become Qdrant payload filters and are independently rechecked in Python; semantic similarity can never override them. A single query embedding drives bounded dense retrieval; Qdrant text retrieval adds keyword candidates. Configured business scoring reranks at most 500 candidates and returns IDs, scores, reasons, parsed intent, and timing. WordPress must resolve IDs to display data itself.

The configured family profile uses seating, body style, safety and rear-access feature names, and cargo capacity. Two-seat or missing-seating records fail an explicit four-person hard requirement. Coupe penalties and other family preferences affect ranking only. Missing reliability, winter, or future-value canonical scores are left null; the service never asserts an unverified score.

## Failure, caching, and performance

`/ready` fails when MySQL or Qdrant is unavailable. API errors include a request ID and omit credentials and customer data. The service caches up to 2,048 parsed queries per process; search results are not cached because inventory and price can change. A gateway may add short-lived cache/rate-limit policies keyed by normalized query and authorization scope, invalidated after indexing. Search avoids MySQL during live requests. Models load once during lifespan startup, embeddings are precomputed at index time, batches encode together, and candidate reranking is bounded. The requested under-400-ms target has **not** been benchmarked. Deep pagination is intentionally bounded by the 500-candidate retrieval cap.

## Known limitations

The current parser is deterministic and covers the curated automotive ontology, not arbitrary conversational language. Exact matching in Qdrant can miss records when provider spellings differ from canonical lookup labels; synonym coverage should grow from production analytics. `ag_*` has no dedicated seating column or canonical reliability/winter/future-value scores. Seating is derived only from explicit feature names. Radius search requires a configured city coordinate and geocoded vehicle location. No Qdrant server or Docker build has been validated in this session; the in-memory Qdrant test does not validate server payload-index performance.
