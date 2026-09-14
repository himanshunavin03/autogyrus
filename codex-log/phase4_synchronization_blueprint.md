# AutoGyrus Phase 4
# Synchronization Layer Architecture

## Production Synchronization Blueprint

## 1. Overall Architecture
The synchronization layer is an event-driven, source-aware, idempotent integration engine that sits between WordPress, dealer operations, AutoGyrus master/intelligence tables, and external providers.

It does not own business rules. It executes them.

### Core principles
- WordPress remains the operational UI and publishing source.
- AutoGyrus remains the canonical intelligence and mapping layer.
- External providers are treated as asynchronous data sources.
- Every inbound change becomes an event, not an immediate destructive overwrite.
- Current-state tables and history tables are updated separately.
- No sync action is allowed to create duplicate canonical vehicles or dealers.
- No sync action is allowed to bypass dedupe, conflict, or audit rules.

### Architectural style
- Event-driven ingestion
- Queue-based processing
- Deterministic idempotency
- Source-priority reconciliation
- Append-only history capture
- Replayable execution
- Provider-aware failure isolation

## 2. Event Driven Architecture

### Event sources
- WordPress vehicle CRUD events
- WordPress dealer CRUD events
- Dealer dashboard edits
- MarketCheck provider events
- Dealer Direct provider events
- Future API provider events
- Manual import events
- Replay/reprocess events

### Event lifecycle
1. Source emits event or scheduled pull retrieves changes.
2. Ingestion normalizes the raw payload into a canonical sync event.
3. Event is written to the event log with idempotency keys.
4. Event is placed on the appropriate queue.
5. Worker validates payload and ownership context.
6. Worker maps source identity to canonical identity.
7. Worker performs duplicate detection.
8. Worker applies conflict resolution rules.
9. Worker updates current-state rows if allowed.
10. Worker appends history rows and audit records.
11. Worker records final status, metrics, and retry state.

### Event properties
- event_id
- event_type
- source_system
- provider_id
- dealer_id
- vehicle_id
- external_id
- payload_hash
- canonical_hash
- idempotency_key
- correlation_id
- causation_id
- event_version
- event_timestamp
- received_at
- processed_at
- status
- retry_count

## 3. Synchronization Components

### 3.1 Source adapters
Responsible for receiving or polling data from:
- WordPress
- dealer dashboard
- MarketCheck
- Dealer Direct
- future APIs

### 3.2 Normalizer
Converts source payloads into canonical sync events and domain-neutral field representations.

### 3.3 Validator
Checks:
- schema completeness
- VIN validity
- dealer ownership context
- provider authorization
- source consistency
- field-level constraints

### 3.4 Canonical mapper
Resolves source entities to:
- `ag_vehicle_master`
- `ag_dealer_master`
- `ag_vehicle_map`
- `ag_dealer_map`

### 3.5 Duplicate detector
Evaluates exact, strong, probable, possible, and manual-review matches.

### 3.6 Conflict resolver
Applies source-priority and manual-override rules.

### 3.7 History writer
Appends immutable history rows for any material change.

### 3.8 Audit writer
Records sync execution, source, result, and lineage.

### 3.9 Retry manager
Controls retry timing, limits, and error classification.

### 3.10 Dead-letter manager
Captures unrecoverable events for manual replay.

### 3.11 Replay controller
Reprocesses events safely using the same idempotency rules.

## 4. Queue Architecture

### Queue model
Use logically separated queues to isolate failure domains and preserve throughput.

#### Recommended queue classes
- `vehicle_identity_queue`
- `dealer_identity_queue`
- `vehicle_facts_queue`
- `pricing_queue`
- `inventory_queue`
- `image_queue`
- `metadata_queue`
- `alias_queue`
- `import_batch_queue`
- `import_log_queue`
- `replay_queue`
- `dead_letter_queue`

### Queue routing rules
- Identity events route before fact events.
- Dealer identity events must complete before dealer-dependent vehicle facts.
- Pricing and inventory queues are isolated from identity queues.
- Provider-specific failures must not block unrelated providers.
- Replay traffic must not starve live ingest.

### Queue guarantees
- at-least-once delivery
- idempotent processing
- ordered processing within a key where required
- no global ordering assumption

## 5. Worker Architecture

### Worker types
- identity workers
- dealer workers
- vehicle facts workers
- pricing workers
- inventory workers
- image workers
- metadata workers
- alias workers
- import workers
- replay workers
- dead-letter inspection workers

### Worker responsibilities
- fetch event
- acquire concurrency lock
- validate payload
- compute canonical keys
- resolve duplicates
- apply conflict rules
- write current state
- append history
- emit audit record
- ack or retry

### Worker rules
- one worker should own a canonical key at a time
- no worker may bypass validation
- no worker may write current-state rows before canonical mapping is resolved
- retries must be explicit and bounded

## 6. Import Pipeline

### Import stages
1. batch intake
2. provider/dealer authentication
3. file or feed validation
4. row normalization
5. identity resolution
6. duplicate detection
7. conflict resolution
8. current-state update
9. history append
10. audit logging
11. result summary

### Import modes
- full import
- delta import
- incremental poll
- manual import
- replay import

### Import invariants
- every row is tracked
- every row gets a final disposition
- every duplicate is classified
- every failure is logged with context

## 7. Validation Pipeline

### Validation layers
- transport validation
- payload validation
- identity validation
- dealer ownership validation
- provider authorization validation
- field-level validation
- business rule validation

### Key validations
- VIN format and checksum when available
- stock number scope within dealer context
- provider source authorization
- required mapping relationships
- field normalization sanity checks
- current/manual lock constraints

### Failure classification
- reject
- quarantine
- manual review
- retryable
- accepted with warnings

## 8. Transformation Pipeline

### Transformations
- normalize text casing and punctuation
- standardize manufacturer/model/trim naming
- decode VIN where available
- normalize currency and market context
- map provider fields to canonical schema
- normalize images and metadata references
- standardize dates, units, and numeric formats

### Transformation rule
- transform source data into canonical semantics before conflict detection.
- never transform after overwriting current data.

## 9. Canonical Mapping Pipeline

### Mapping order
1. resolve provider
2. resolve dealer
3. resolve vehicle identity
4. resolve current mapping row
5. bind source data to canonical master record

### Mapping rules
- VIN-first when valid
- external ID second
- canonical identity hash third
- stock number only within dealer scope
- provider mapping never overwrites canonical identity without rule validation

### Mapping outputs
- canonical vehicle reference
- canonical dealer reference
- source mapping reference
- confidence score
- dedupe class

## 10. Duplicate Detection Pipeline

### Detection stages
- exact match
- strong match
- probable match
- possible match
- manual review

### Signals used
- VIN
- normalized VIN
- stock number
- dealer context
- provider external ID
- canonical identity hash
- duplicate group hash
- image hash
- provider record hash
- payload hash
- manufacturer/model/trim/year
- generation/platform/series

### Duplicate policy
- exact matches auto-link
- strong matches auto-link unless core facts conflict
- probable matches suggest merge
- possible matches queue for review
- conflicts on VIN or external ID always escalate

## 11. Conflict Resolution Pipeline

### Conflict categories
- identity conflict
- dealer conflict
- price conflict
- image conflict
- specification conflict
- feature conflict
- description conflict
- mileage conflict
- color conflict
- listing status conflict

### Resolution methods
- overwrite current
- merge into current
- ignore lower-priority source
- manual review

### Priority behavior
- source priority is field-specific
- manual locks override provider updates
- verified canonical identity overrides conflicting inferred data
- current-state row and history row are handled separately

## 12. History Pipeline

### History policy
- append only
- no destructive rewrite
- keep old states queryable
- preserve source and conflict context

### History targets
- price history
- inventory history
- import log
- sync audit history
- identity change history where relevant

### History behavior
- when current row changes, append a history record
- when a provider sends a conflicting observation, append a history record even if current row is unchanged
- when a manual override occurs, append a history record and an audit event

## 13. Audit Pipeline

### Audit contents
- event type
- source system
- provider
- dealer
- vehicle
- before state
- after state
- user or system actor
- decision reason
- confidence score
- status
- timestamps

### Audit guarantees
- every meaningful sync action is traceable
- every rejected row has a reason
- every replay is traceable to its original event
- rollback decisions are auditable

## 14. Retry Pipeline

### Retry policy
- retry only transient failures
- bounded retries with exponential backoff
- separate retry logic by provider and event type
- preserve idempotency on every retry

### Retry classification
- network timeout
- provider 5xx
- temporary auth outage
- lock contention
- database deadlock

### Non-retryable failures
- invalid VIN
- schema validation failure
- unauthorized provider
- deterministic duplicate rejection
- manual lock violation

## 15. Dead Letter Queue

### Purpose
Store events that cannot be processed after the retry policy is exhausted.

### Dead-letter requirements
- preserve full payload
- preserve error summary
- preserve retry history
- preserve correlation and causation IDs
- allow manual replay

### Dead-letter exit criteria
- corrected payload
- restored provider availability
- manual data cleanup
- explicit admin replay

## 16. Replay Strategy

### Replay sources
- dead-letter queue
- operator-selected historical batches
- provider resend windows
- manual correction events

### Replay rules
- replay must use the same idempotency keys
- replay must not create duplicate canonical records
- replay must re-run duplicate detection and conflict resolution
- replay must respect current manual lock state

### Replay safety
- replay is append-safe
- replay is not allowed to bypass audit or history
- replay of a resolved event should be a no-op when state is unchanged

## 17. Idempotency Strategy

### Idempotency keys
Use stable keys derived from:
- source system
- provider ID
- dealer ID
- external ID
- VIN
- event type
- payload hash
- source version or sequence number

### Idempotency behavior
- same event processed twice produces the same final state
- duplicate event delivery is expected and harmless
- current-state rows are not duplicated by retries

### Idempotency store
- event log
- import log
- source mapping tables
- canonical hash records

## 18. Transaction Strategy

### Transaction scope
- use small transactional units around a canonical entity or row batch
- avoid giant all-provider transactions
- keep history + current-state writes in the same logical unit where possible

### Transaction rules
- commit only after mapping, conflict, and validation succeed
- if current-state write succeeds but history write fails, rollback if in the same transaction scope
- if external provider calls fail after internal commit, log and retry reconciliation

### Isolation strategy
- protect canonical keys with row-level locking or logical concurrency guards
- avoid broad table locks

## 19. Concurrency Strategy

### Concurrency model
- concurrent by provider
- concurrent by dealer
- concurrent by vehicle key
- serialized only when rows share the same canonical identity or lock state

### Concurrency controls
- distributed lock or logical ownership on canonical identity
- per-key processing lock
- optimistic version checks on current rows

### Race condition prevention
- canonical identity must be resolved before current-state update
- only one active writer should own a canonical vehicle/dealer key at a time
- duplicate event arrivals should converge to the same state

## 20. Performance Strategy

### Performance goals
- keep identity lookups fast
- keep current-state reads fast
- keep history writes append-only and lightweight
- avoid wide joins in the hot path

### Tactics
- lookup-first processing
- composite keys aligned to query patterns
- route heavy history into append paths
- avoid unnecessary provider payload re-parsing
- cache provider/dealer mappings where safe

### Query patterns optimized for
- vehicle lookup by VIN
- vehicle lookup by canonical identity
- dealer lookup by provider external ID
- current price retrieval
- current inventory retrieval
- latest images
- current specification snapshot

## 21. Scaling Strategy

### Horizontal scale
- scale by provider
- scale by queue class
- scale by dealer or region where needed
- scale replay independently from live ingest

### Large dataset support
- millions of vehicles
- millions of dealers
- very large import/event history
- multiple countries and markets

### Scale boundaries
- identity resolution must remain O(log n) or indexed lookup driven
- history append must remain write-efficient
- provider failures should not bring down the system

## 22. Error Recovery

### Recovery paths
- automatic retry
- dead-letter capture
- replay
- manual correction
- provider resync window

### Recovery objectives
- no data loss
- no duplicate canonical records
- no broken history chain
- no corruption of current-state snapshot

## 23. Provider Failure Recovery

### Failure types
- provider outage
- partial payloads
- stale payloads
- authentication failure
- rate limiting
- schema drift

### Recovery behavior
- isolate the provider
- preserve last known good canonical state
- continue processing other providers
- queue provider-specific retries
- surface provider health metrics

## 24. Manual Override Processing

### Manual override types
- price lock
- image lock
- description lock
- specification lock
- dealer override
- vehicle override

### Processing rules
- manual override creates an audit event
- manual override updates current state if allowed
- imports respect the lock
- providers can still be logged and stored in history

### Unlock rules
- unlock must be explicit
- unlock must be audited
- unlock should re-evaluate queued provider changes

## 25. Partial Import Handling

### Partial import rules
- process valid rows
- isolate invalid rows
- do not fail the entire batch unless the batch-level integrity is broken
- classify each row separately

### Partial import outputs
- inserted
- updated
- ignored
- duplicate
- warning
- error

### Partial import safety
- canonical identity rows must not be half-created
- history must not contradict current state

## 26. Logging Strategy

### Log layers
- transport logs
- sync event logs
- validation logs
- mapping logs
- conflict logs
- duplicate logs
- history logs
- retry logs
- dead-letter logs

### Logging requirements
- structured logs
- correlation IDs
- source references
- enough context for replay
- no sensitive secrets in plaintext

## 27. Monitoring Strategy

### What to monitor
- queue depth
- worker lag
- provider latency
- provider error rate
- duplicate rate
- manual review rate
- retry rate
- dead-letter rate
- current-state update success
- history append success

### Operational alerts
- provider outage
- retry storm
- dead-letter spike
- duplicate spike
- validation failure spike
- lock contention spike

## 28. Metrics

### Core metrics
- events ingested
- events processed
- events failed
- events retried
- events dead-lettered
- canonical identities created
- canonical identities linked
- duplicates auto-resolved
- duplicates sent to manual review
- average processing latency
- provider lag
- history append count

### Business metrics
- vehicle sync freshness
- dealer sync freshness
- price freshness
- inventory freshness
- image freshness
- provider health score

## 29. Future AI Event Hooks

The sync layer should emit clean events for future AI consumers without changing the database schema.

### Future event classes
- vehicle identity resolved
- dealer identity resolved
- price changed
- inventory changed
- feature changed
- specification changed
- image changed
- alias added
- manual override applied
- duplicate detected
- conflict detected

### AI consumers
- AI search
- knowledge graph
- GraphRAG
- embeddings
- recommendations
- reliability scoring
- maintenance scoring
- insurance scoring
- resale scoring
- health scoring

### AI readiness rule
All AI-relevant source facts must already be structured, versioned, and auditable by the time they leave the sync layer.

## 30. Implementation Roadmap

### Phase A - Core engine
- event envelope
- source adapters
- validator
- canonical mapper
- duplicate detector

### Phase B - Current-state sync
- current-state write paths
- history append paths
- conflict resolution
- manual override awareness

### Phase C - Reliability and recovery
- retry manager
- dead-letter queue
- replay controller
- provider failure isolation

### Phase D - Observability
- audit writer
- logging
- metrics
- monitoring

### Phase E - AI hooks
- emit structured events for future AI consumers
- do not implement AI storage in this phase

## Final Notes
This blueprint is intentionally operational and deterministic.

It assumes:
- the schema is locked
- the business rules are locked
- WordPress stays unchanged
- all sync actions are idempotent
- all canonical conflicts are resolved predictably

That is the correct foundation for a long-lived automotive platform.

