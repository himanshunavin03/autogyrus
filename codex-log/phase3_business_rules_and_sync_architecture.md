# AutoGyrus Phase 3
# Business Rules & Synchronization Architecture

## 1. Executive Summary
Phase 3 defines how AutoGyrus behaves after the database schema is locked.

The system is now split cleanly into:
- WordPress as the operational UI and publishing system
- AutoGyrus as the canonical intelligence and synchronization layer
- provider feeds as external inputs, not system owners

This phase does not change schema or code. It defines the permanent operating rules for:
- truth ownership
- field priority
- conflict resolution
- duplicate detection
- synchronization flows
- versioning
- manual overrides
- audit and rollback
- AI preparation

The outcome is a deterministic synchronization architecture that can support marketplace, dealer, mobile, API, graph, vector, and AI workloads without redesign.

## 2. Complete Business Rules Document

### 2.1 System boundaries
- WordPress continues to own vehicle CRUD, dealer CRUD, publishing, dashboard, and current user-facing workflows.
- AutoGyrus owns canonical identity, intelligence facts, mapping, source precedence, and synchronization policy.
- External providers are read sources unless a specific field is explicitly designated as dealer-manual writable.
- No provider is allowed to directly become the system of record for the whole vehicle.

### 2.2 Entity-level business rules

#### Vehicle Identity
- Canonical vehicle identity lives in `ag_vehicle_master`.
- WordPress vehicle posts remain the operational publisher record.
- Provider feeds can create or update canonical mappings only through synchronization rules.
- VIN is the strongest global identity signal when present and valid.

#### Dealer
- Canonical dealer identity lives in `ag_dealer_master`.
- WordPress dealer records remain the operational dealer UI source.
- Provider dealer identities map through `ag_dealer_map`.
- A dealer may have multiple provider identities, but only one canonical dealer identity.

#### Inventory
- Current inventory state is represented by `ag_inventory`.
- Inventory history is append-only.
- Dealer manual updates outrank provider inventory signals for local stock status.

#### Price
- Current price is represented by `ag_vehicle_price`.
- Price history is append-only.
- Dealer manual price changes override provider price feeds unless the manual lock is released.

#### Images
- Current image selection is represented by `ag_vehicle_image`.
- Image history is append-only through source-aware re-ingest and log records.
- Dealer-selected images outrank provider images.

#### Specifications
- Structured specification facts live in `ag_vehicle_specification`, `ag_vehicle_engine`, `ag_vehicle_transmission`, and `ag_vehicle_fuel_economy`.
- OEM and VIN decode are the strongest sources for identity-related spec facts.
- Provider feed values are accepted when they are consistent with existing canonical identity.

#### Features
- Feature definitions live in `ag_vehicle_feature`.
- Vehicle-to-feature values live in `ag_vehicle_feature_value`.
- Dealer manual and OEM sources outrank inferred AI feature extraction.

#### Metadata
- Provider-specific payloads belong in `ag_vehicle_metadata`.
- Provider fields must remain normalized JSON, not free-form application-specific columns.

#### Alias
- Alternative names, regional names, and marketing names live in `ag_vehicle_alias`.
- Alias data is not canonical identity.
- Alias data can be multiple-per-vehicle and multiple-per-provider.

### 2.3 Global operating rules
- Canonical identity is always preferred over inferred identity.
- Verified manual overrides outrank imports.
- Imports never silently replace locked manual values.
- Every synchronization action must record source, reason, and confidence.
- History is never destroyed for operational convenience.
- Soft delete is preferred over hard delete for business data.

## 3. Source of Truth Matrix

| Entity | System of Record | Read Source | Write Source | Update Strategy |
|---|---|---|---|---|
| Vehicle identity | `ag_vehicle_master` | WordPress vehicle, provider feeds, VIN decode | Sync layer only | Merge from strongest identity source |
| Dealer identity | `ag_dealer_master` | WordPress dealer, provider dealer feeds | Sync layer only | Merge by canonical hash and provider map |
| Inventory current state | `ag_inventory` | WordPress inventory, dealer direct, provider feeds | Sync layer | Last valid source by priority and lock state |
| Price current state | `ag_vehicle_price` | WordPress dealer edits, dealer direct, provider feeds | Sync layer | Priority-based overwrite with history append |
| Images | `ag_vehicle_image` | WordPress attachments, dealer direct, provider feeds | Sync layer | Preserve selected primary images, append new sources |
| Specifications | spec tables | OEM, VIN decode, provider feeds | Sync layer | Source-confidence merge by domain |
| Features | `ag_vehicle_feature_value` | WordPress, dealer direct, provider, extracted signals | Sync layer | Merge boolean and value fields by confidence |
| Metadata | `ag_vehicle_metadata` | Provider raw payloads | Sync layer | Append normalized payload per namespace/source |
| Aliases | `ag_vehicle_alias` | WordPress titles, provider names, marketing names | Sync layer | Append aliases, dedupe normalized variants |
| Import tracking | import tables | Sync pipeline | Sync pipeline | Append-only event logging |
| Reviews | future phase | future provider/AI sources | future phase | not implemented in current schema |
| Recalls | future phase | future provider/OEM sources | future phase | not implemented in current schema |
| Complaints | future phase | future provider/public sources | future phase | not implemented in current schema |
| Reliability | future phase | future AI/derived layer | future phase | not implemented in current schema |
| Maintenance | future phase | future AI/derived layer | future phase | not implemented in current schema |
| Insurance | future phase | future AI/derived layer | future phase | not implemented in current schema |
| Resale | future phase | future AI/derived layer | future phase | not implemented in current schema |
| Ownership cost | future phase | future AI/derived layer | future phase | not implemented in current schema |
| Search analytics | future phase | future AI/search telemetry | future phase | not implemented in current schema |
| Prompt history | future phase | future AI layer | future phase | not implemented in current schema |
| Vehicle health score | future phase | derived intelligence | future phase | not implemented in current schema |

## 4. Source Priority Matrix

### 4.1 Vehicle identity fields
| Field | Highest Priority | Next Priority | Fallback |
|---|---|---|---|
| VIN | VIN decode / OEM | WordPress vehicle meta | Provider feed |
| Manufacturer | OEM / VIN decode | WordPress taxonomies | Provider feed |
| Model | OEM / VIN decode | WordPress taxonomies | Provider feed |
| Generation | OEM / VIN decode | provider feed | inferred canonical mapping |
| Trim | OEM / VIN decode | WordPress meta | provider feed |
| Platform | OEM | VIN decode | provider feed |
| Series | OEM | provider feed | inferred mapping |
| Body code | OEM | VIN decode | provider feed |
| Internal manufacturer code | OEM | provider feed | none |

### 4.2 Dealer fields
| Field | Highest Priority | Next Priority | Fallback |
|---|---|---|---|
| Dealer name | WordPress dealer manual | Dealer direct feed | provider dealer feed |
| Dealer phone | WordPress dealer manual | Dealer direct feed | provider dealer feed |
| Dealer address | WordPress dealer manual | Dealer direct feed | provider dealer feed |
| Dealer logo/image | WordPress | Dealer direct feed | provider feed |
| Dealer status | WordPress admin | Sync layer verification | provider feed |

### 4.3 Commerce fields
| Field | Highest Priority | Next Priority | Fallback |
|---|---|---|---|
| Price | Dealer manual / locked dealer override | Dealer direct feed | MarketCheck |
| MSRP | OEM / provider spec feed | MarketCheck | dealer feed |
| Sale price | Dealer manual | dealer direct | provider feed |
| Availability | WordPress dealer/manual | dealer direct | provider feed |
| Listing status | WordPress dealer/manual | sync derived status | provider feed |
| Stock number | Dealer manual | dealer direct | provider feed |

### 4.4 Content fields
| Field | Highest Priority | Next Priority | Fallback |
|---|---|---|---|
| Description | Dealer manual | WordPress content | provider text |
| Images | Dealer selected | WordPress attachments | provider images |
| Features | OEM/spec feed | dealer manual | provider feed |
| Specifications | OEM / VIN decode | provider spec feed | dealer manual |
| Warranty | OEM | provider feed | dealer manual |
| Mileage | Dealer/manual odometer | provider feed | inferred |
| Color | dealer manual / OEM | provider feed | inferred |

## 5. Conflict Resolution Matrix

| Field Group | Rule | Action |
|---|---|---|
| VIN | Exact mismatch on same canonical vehicle | Manual review |
| Manufacturer / model / trim | Canonical identity mismatch | Manual review |
| Price | Higher-priority source vs lower-priority source | Overwrite current, append history |
| Images | Dealer selected image vs provider image | Merge, preserve dealer primary |
| Specifications | OEM conflict with provider | Manual review or overwrite based on trust hierarchy |
| Features | Boolean feature conflict | Merge with source confidence |
| Description | Dealer manual vs provider description | Dealer manual wins, provider stored as alternate |
| Mileage | Dealer odometer vs provider estimate | Dealer odometer wins |
| Color | OEM vs dealer provided | OEM wins if vehicle identity is not locked otherwise manual review |
| Dealer identity | Duplicate provider dealer records | Merge into one canonical dealer |

### 5.1 Conflict actions
- **Overwrite**: used for current-state facts when a higher-priority source is trusted and no manual lock exists.
- **Merge**: used for features, aliases, images, and metadata.
- **Ignore**: used for low-confidence, redundant, or stale updates.
- **Manual Review**: used for identity conflicts, VIN conflicts, and contradictory canonical facts.

## 6. Duplicate Detection Rules

### 6.1 Match levels
- **Exact Match**
  - same VIN
  - same source_system + external_id
  - same canonical identity hash
  - same provider record hash

- **Strong Match**
  - manufacturer + model + trim + year + dealer context
  - VIN partial decode + same dealer/provider context
  - stock number + dealer + source match

- **Probable Match**
  - same generation/platform + same dealer + same region
  - same image hash + near-identical identity facts
  - same alias set and similar provider payload hashes

- **Possible Match**
  - same manufacturer/model/year only
  - same body style and market with similar mileage range
  - same dealer and similar price band

- **Manual Review**
  - any VIN conflict
  - same source external ID attached to different vehicles
  - multiple canonical hashes for a single strong identity
  - multiple strong matches above threshold but inconsistent core facts

### 6.2 Duplicate rules by signal
- VIN is the strongest dedupe signal.
- Stock number is only strong within a dealer context.
- Dealer identity is not enough to dedupe a vehicle by itself.
- Provider ID is never sufficient by itself.
- Image hash is a strong supporting signal, not a canonical identity signal.
- Hash mismatches on canonical identity must trigger review.

### 6.3 Threshold guidance
- exact match: auto-link
- strong match: auto-link if no conflicting canonical field exists
- probable match: queue for automated merge suggestion
- possible match: queue for manual review

## 7. Synchronization Flow Diagrams

### 7.1 WordPress vehicle created
```text
WordPress Vehicle Created
  -> validate WP post and taxonomy completeness
  -> compute canonical identity hash
  -> match or create ag_vehicle_master
  -> create ag_vehicle_map record
  -> seed current intelligence rows if available
  -> write audit/history log
```

### 7.2 Dealer edits price
```text
Dealer Price Edit
  -> validate manual lock state
  -> compare source priority
  -> update ag_vehicle_price current row
  -> append ag_vehicle_price_history
  -> write sync audit record
```

### 7.3 MarketCheck import
```text
MarketCheck Import
  -> validate provider enabled and authenticated
  -> resolve provider and dealer map
  -> dedupe by VIN/external ID/hash
  -> merge current facts by source priority
  -> append history rows
  -> log import row result
```

### 7.4 Dealer Direct import
```text
Dealer Direct Import
  -> validate dealer ownership / mapping
  -> resolve canonical dealer
  -> apply dealer priority rules
  -> overwrite current if allowed
  -> keep prior values in history
```

### 7.5 CSV import
```text
CSV Import
  -> validate file and schema mapping
  -> normalize rows
  -> detect duplicates
  -> batch transform
  -> write current facts and history
  -> capture row-level errors and warnings
```

### 7.6 Vehicle deleted
```text
Vehicle Deleted
  -> soft delete WordPress vehicle if source is WP
  -> mark AutoGyrus current rows inactive
  -> keep all history rows
  -> preserve mappings for replay and audit
```

### 7.7 Vehicle sold
```text
Vehicle Sold
  -> mark listing/inventory inactive
  -> close inventory history event
  -> keep vehicle identity active
  -> retain price and image history
```

### 7.8 Image updated
```text
Image Updated
  -> validate asset hash
  -> compare provider/dealer priority
  -> set primary if allowed
  -> append image row or version signal
  -> preserve previous image selection in history/log
```

## 8. Versioning Strategy

- **Update current row**
  - when the change affects the active operational snapshot
  - when the row is not manual-locked
  - when the source has sufficient priority

- **Create history row**
  - whenever a current fact changes
  - whenever a provider sends a new observation
  - whenever a manual action changes a locked value

- **Archive**
  - history remains in append-only tables
  - archival is operational policy, not schema redesign

- **Soft delete**
  - used for current facts when source records are withdrawn or superseded
  - never destroy historical trace

- **Version increment**
  - increment on any material correction to current-state data
  - do not version noise-only duplicate rows

## 9. Manual Override Strategy

### 9.1 Override types
- price lock
- image lock
- description lock
- specification lock
- feature lock
- dealer override
- provider override
- AI override

### 9.2 Lock behavior
- A locked field cannot be overwritten by lower-priority providers.
- Higher-priority sources may still create history rows and conflict records.
- Manual overrides must be explicitly released before provider-driven changes can become current.

### 9.3 Import behavior after override
- imports continue to be accepted
- conflicting fields are logged
- current row remains locked unless a privileged unlock occurs
- history still records the incoming provider observation

### 9.4 Override precedence
1. explicit dealer manual lock
2. privileged internal admin override
3. provider merge rule
4. inferred/AI suggestion

## 10. Error Handling Strategy

### 10.1 Retry policy
- retry transient provider failures with bounded backoff
- never retry a deterministic validation failure blindly
- keep retry count and error category in logs

### 10.2 Dead-letter strategy
- after retry exhaustion, move the job/row to dead-letter status
- preserve payload and error context
- allow manual reprocessing later

### 10.3 Duplicate imports
- same source_system + external_id should be idempotent
- duplicates should resolve to the same mapping or be rejected cleanly

### 10.4 Invalid VIN
- if VIN format is invalid, do not create a canonical identity record automatically
- store the attempt in logs
- require manual review or a corrected provider payload

### 10.5 Provider outage
- pause that provider’s sync stream
- do not fail unrelated providers
- preserve last good current rows

### 10.6 Partial import
- commit valid row-level facts when safe
- isolate failed rows
- never corrupt the canonical identity layer

### 10.7 Transaction rollback
- use row-level or batch-level rollback depending on ingest mode
- current rows must never end up half-written without logs

## 11. Audit Strategy

- Every sync action must create an audit/event trail.
- Current-state updates and history writes must be separately traceable.
- Log at least:
  - source system
  - provider
  - dealer
  - vehicle
  - action type
  - field changes
  - before/after values
  - result
  - confidence
  - error detail if any

### Audit layers
- **Event logging**: what happened
- **Synchronization logging**: how the sync was processed
- **Import logging**: row/batch level ingest results
- **Change history**: before/after state for material facts
- **Rollback support**: ability to identify the last stable state, not necessarily to hard undo all rows

## 12. AI Readiness Review
The synchronization layer is AI-ready because it captures:
- canonical identity
- structured features
- structured specifications
- pricing history
- inventory history
- aliases
- images
- metadata
- source confidence and data quality
- source precedence and conflict context

That is enough for future AI systems to build:
- AI search
- knowledge graph
- GraphRAG
- embeddings
- recommendations
- reliability scoring
- maintenance scoring
- insurance scoring
- resale scoring
- vehicle health scores

No schema redesign is required to support those future layers, provided the sync layer continues to emit clean structured facts and history.

## 13. Risks
- A bad provider feed can create noisy history if conflict rules are not enforced consistently.
- Manual overrides can create stale snapshots if unlock governance is weak.
- Duplicate detection must remain deterministic; fuzzy-only logic will not scale.
- Without strong sync discipline, JSON payloads can become an escape hatch for unmodeled data.
- History tables can grow very large; retention policy must be operationally enforced.

## 14. Final Recommendations
- Treat source priority as a field-level rule, not a global rule only.
- Keep manual overrides explicit and traceable.
- Make duplicate detection deterministic first, fuzzy second.
- Append history for every meaningful provider observation.
- Never let provider data silently override a locked dealer decision.
- Continue to keep the relational core clean; future AI systems should consume structured facts, not raw operational chaos.

## 15. Implementation Roadmap
1. Implement source priority policy per field group.
2. Implement canonical dedupe and linking rules.
3. Implement current-state update vs history append rules.
4. Implement manual lock governance.
5. Implement audit/event logging schema behavior.
6. Implement retry and dead-letter handling.
7. Implement provider outage suppression.
8. Implement replay/reprocess workflows.
9. Validate AI consumption paths from structured facts.

## 16. Developer Checklist
- [ ] Confirm each business entity has one authoritative source rule
- [ ] Confirm each field group has a priority order
- [ ] Confirm lock rules are explicit
- [ ] Confirm duplicate detection thresholds are deterministic
- [ ] Confirm sync flows append history correctly
- [ ] Confirm provider failures do not corrupt canonical identity
- [ ] Confirm WordPress remains untouched
- [ ] Confirm current-state rows are updated only by rule
- [ ] Confirm history rows are append-only
- [ ] Confirm logs are sufficient for replay and audit

## 17. Production Readiness Decision
GO

The schema is locked, the operating rules are now defined, and the synchronization layer can be implemented without redesigning the database.

The required next step is implementation of synchronization code on top of this blueprint, not more schema work.

