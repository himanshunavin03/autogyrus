# Toyota Field Mapping

## Scope
This mapping covers the Toyota dealer inventory import pipeline implemented for AutoGyrus Phase 1.

The importer is designed to accept Toyota inventory JSON with common dealer-feed shapes such as:
- `dealer`
- `inventory`
- `vehicles`
- `results`
- `items`
- `data`

Any field that does not have a canonical destination is stored in `ag_vehicle_metadata` under `metadata_namespace = toyota_inventory`.

## Canonical Import Rules
- VIN is the preferred identity key.
- If VIN is missing, the importer falls back to the Toyota external record ID.
- Lookup entities are created or reused before fact rows are written.
- Images, features, pricing, inventory, location, aliases, and metadata are all written independently.
- Unmapped Toyota payload data is preserved in JSON metadata.

## Dealer Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `dealer.dealerId`, `dealer.id`, `dealer.code` | `ag_dealer_master` | `dealer_code` | Trim and preserve as dealer identity code |
| `dealer.name`, `dealer.dealerName` | `ag_dealer_master` | `dealer_name` | Trimmed text |
| `dealer.legalName`, `dealer.dealerLegalName` | `ag_dealer_master` | `dealer_legal_name` | Trimmed text |
| `dealer.website`, `dealer.websiteUrl` | `ag_dealer_master` | `website_url` | URL string |
| `dealer.phone`, `dealer.phoneNumber` | `ag_dealer_master` | `phone_number` | Trimmed text |
| `dealer.email`, `dealer.emailAddress` | `ag_dealer_master` | `email_address` | Trimmed text |
| `dealer.address.line1` | `ag_dealer_master` | `address_line1` | Trimmed text |
| `dealer.address.line2` | `ag_dealer_master` | `address_line2` | Trimmed text |
| `dealer.address.city` | `ag_dealer_master` | `city_id` via `ag_city` | Normalized city lookup |
| `dealer.address.province` | `ag_dealer_master` | `province_id` via `ag_state_province` | Normalized province lookup |
| `dealer.address.country` | `ag_dealer_master` | `country_id` via `ag_country` | Normalized country lookup |
| `dealer.address.postalCode` | `ag_dealer_master` | `postal_code` | Uppercase text |
| `dealer.location.latitude` | `ag_dealer_master` | `latitude` | Decimal latitude |
| `dealer.location.longitude` | `ag_dealer_master` | `longitude` | Decimal longitude |
| `dealer.timezone` | `ag_dealer_master` | `timezone` | Trimmed text |
| canonical dealer hash | `ag_dealer_master` | `canonical_dealer_hash` | SHA-256 over normalized dealer identity |
| dealer provider mapping | `ag_dealer_map` | `external_id` | Toyota dealer external ID |

## Vehicle Identity Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `vin`, `VIN`, `vehicleIdentificationNumber` | `ag_vehicle_master` | `vin`, `vin_normalized` | Uppercase VIN |
| fallback record ID | `ag_vehicle_map` | `external_id` | Toyota record ID when VIN is absent |
| `stockNumber`, `stock_number`, `stock` | `ag_inventory` | `stock_number` | Trimmed text |
| `year`, `modelYear` | `ag_vehicle_master` | `model_year` | Integer year |
| `make`, `brand`, `manufacturer` | `ag_manufacturer` | `manufacturer_name` | Lookup by slug |
| `model`, `modelName` | `ag_model` | `model_name` | Lookup by model slug |
| `trim`, `trimName`, `grade` | `ag_trim` | `trim_name` | Lookup by trim slug |
| `generation`, `generationName` | `ag_vehicle_generation` | `generation_name` | Lookup by generation code |
| `series`, `seriesName` | `ag_series` | `series_name` | Lookup by series code |
| `platform`, `platformName` | `ag_platform` | `platform_name` | Lookup by platform code |
| `bodyStyle`, `bodyType` | `ag_body_style` | `body_style_name` | Lookup by body style slug |
| `condition` | `ag_vehicle_condition` | `condition_name` | Used/new normalization |
| `listingStatus`, `status`, `availabilityStatus` | `ag_listing_status` | `status_name` | Active/status normalization |

## Engine Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `engine.code` | `ag_vehicle_engine` | `engine_code` | Trimmed text |
| `engine.name` | `ag_vehicle_engine` | `engine_name` | Trimmed text |
| `engine.displacementCc` | `ag_vehicle_engine` | `displacement_cc` | Numeric cc |
| `engine.horsepowerHp` | `ag_vehicle_engine` | `horsepower_hp` | Numeric hp |
| `engine.torqueNm` | `ag_vehicle_engine` | `torque_nm` | Numeric Nm |
| `engine.compressionRatio` | `ag_vehicle_engine` | `compression_ratio` | Numeric ratio |
| `engine.turbo` | `ag_vehicle_engine` | `is_turbocharged` | Boolean |
| `engine.supercharger` | `ag_vehicle_engine` | `is_supercharged` | Boolean |
| `engine.fuelSystem` | `ag_vehicle_engine` | `fuel_system` | Trimmed text |
| `engine.cylinderCount` | `ag_vehicle_engine` | `cylinder_count` | Integer |
| `engine.cylinderLayout` | `ag_vehicle_engine` | `cylinder_layout` | Trimmed text |
| `engine.emissionStandard` | `ag_vehicle_engine` | `emission_standard` | Trimmed text |
| `engine.startStop` | `ag_vehicle_engine` | `has_start_stop` | Boolean |
| `engine.hybridAssist` | `ag_vehicle_engine` | `has_hybrid_assist` | Boolean |
| `engine.batteryCapacityKwh` | `ag_vehicle_engine` | `battery_capacity_kwh` | Numeric kWh |
| `engine.chargingType` | `ag_vehicle_engine` | `charging_type` | Trimmed text |
| `engine.motorPowerKw` | `ag_vehicle_engine` | `motor_power_kw` | Numeric kW |
| `engine.motorTorqueNm` | `ag_vehicle_engine` | `motor_torque_nm` | Numeric Nm |
| `engine.coolingType` | `ag_vehicle_engine` | `cooling_type` | Trimmed text |

## Transmission Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `transmission` | `ag_vehicle_transmission` | `transmission_name` | Trimmed text |
| transmission code | `ag_vehicle_transmission` | `transmission_code` | `sanitize_title()` |
| gear count | `ag_vehicle_transmission` | `gear_count` | Integer |
| transmission family | `ag_vehicle_transmission` | `transmission_family` | Trimmed text |
| contains "manual" | `ag_vehicle_transmission` | `is_manual` | Boolean inference |
| contains "automatic" | `ag_vehicle_transmission` | `is_automatic` | Boolean inference |
| contains "cvt" | `ag_vehicle_transmission` | `is_cvt` | Boolean inference |
| contains "dct" | `ag_vehicle_transmission` | `is_dct` | Boolean inference |
| `drivetrain` contains `4WD` | `ag_vehicle_transmission` | `has_transfer_case` | Boolean inference |

## Fuel Economy Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `fuelEconomy.cityLPer100km` | `ag_vehicle_fuel_economy` | `city_l_per_100km` | Numeric |
| `fuelEconomy.highwayLPer100km` | `ag_vehicle_fuel_economy` | `highway_l_per_100km` | Numeric |
| `fuelEconomy.combinedLPer100km` | `ag_vehicle_fuel_economy` | `combined_l_per_100km` | Numeric |
| `fuelEconomy.fuelTankL` | `ag_vehicle_fuel_economy` | `fuel_tank_l` | Numeric |
| `fuelEconomy.electricRangeKm` | `ag_vehicle_fuel_economy` | `electric_range_km` | Numeric |
| `fuelEconomy.hybridRangeKm` | `ag_vehicle_fuel_economy` | `hybrid_range_km` | Numeric |
| `fuelEconomy.batteryRangeKm` | `ag_vehicle_fuel_economy` | `battery_range_km` | Numeric |
| `fuelEconomy.mpge` | `ag_vehicle_fuel_economy` | `mpge` | Numeric |
| `fuelEconomy.consumptionLPer100km` | `ag_vehicle_fuel_economy` | `consumption_l_per_100km` | Numeric |

## Specification Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `specifications.wheelbaseMm` | `ag_vehicle_specification` | `wheelbase_mm` | Numeric mm |
| `specifications.lengthMm` | `ag_vehicle_specification` | `length_mm` | Numeric mm |
| `specifications.widthMm` | `ag_vehicle_specification` | `width_mm` | Numeric mm |
| `specifications.heightMm` | `ag_vehicle_specification` | `height_mm` | Numeric mm |
| `specifications.groundClearanceMm` | `ag_vehicle_specification` | `ground_clearance_mm` | Numeric mm |
| `specifications.turningRadiusM` | `ag_vehicle_specification` | `turning_radius_m` | Numeric meters |
| `specifications.cargoCapacityL` | `ag_vehicle_specification` | `cargo_capacity_l` | Numeric liters |
| `specifications.passengerVolumeL` | `ag_vehicle_specification` | `passenger_volume_l` | Numeric liters |
| `specifications.curbWeightKg` | `ag_vehicle_specification` | `curb_weight_kg` | Numeric kg |
| `specifications.gvwrKg` | `ag_vehicle_specification` | `gvwr_kg` | Numeric kg |
| `specifications.payloadKg` | `ag_vehicle_specification` | `payload_kg` | Numeric kg |
| `specifications.towingCapacityKg` | `ag_vehicle_specification` | `towing_capacity_kg` | Numeric kg |
| safety rating | `ag_vehicle_specification` | `safety_rating_overall` | Trimmed text |
| EPA rating | `ag_vehicle_specification` | `epa_rating_overall` | Trimmed text |
| warranty months/km | `ag_vehicle_specification` | warranty columns | Integer preservation |

## Pricing / Inventory / Location Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `price.current`, `price.currentPrice`, `price.price` | `ag_vehicle_price` | `current_price` | Numeric |
| `price.msrp`, `price.listPrice` | `ag_vehicle_price` | `msrp` | Numeric |
| `price.sale`, `price.salePrice`, `price.internetPrice` | `ag_vehicle_price` | `sale_price` | Numeric |
| `price.market`, `price.marketPrice` | `ag_vehicle_price` | `market_price` | Numeric |
| `price.dealer`, `price.dealerPrice` | `ag_vehicle_price` | `dealer_price` | Numeric |
| `price.currency`, `price.currencyCode` | `ag_currency` / `ag_vehicle_price` | `currency_id` | Currency lookup |
| `mileage`, `odometer`, `odometerKm` | `ag_inventory` and `ag_vehicle_location` | `odometer_km` / meta | Numeric kilometers |
| `listingStatus` | `ag_inventory` | `availability_status`, `lot_status` | Normalized status |
| `postalCode` | `ag_vehicle_location` | `postal_code` | Uppercase text |
| `city` | `ag_city` / `ag_vehicle_location` | `city_id` | City lookup |
| `province` | `ag_state_province` / `ag_vehicle_location` | `province_id` | Province lookup |
| `country` | `ag_country` / `ag_vehicle_location` | `country_id` | Country lookup |
| `latitude`, `longitude` | `ag_vehicle_location` / `ag_dealer_master` | geo columns | Decimal coordinates |

## Images Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `images[].url` | `ag_vehicle_image` | `original_image_url` | Preserve original URL |
| `images[].thumbnail` | `ag_vehicle_image` | `thumbnail_image_url` | Preserve thumbnail URL |
| `images[].gallery` | `ag_vehicle_image` | `gallery_image_url` | Preserve gallery URL |
| `images[].role` | `ag_vehicle_image` | `image_role` | `primary` / `gallery` |
| `images[].hash` | `ag_vehicle_image` | `image_hash` | SHA-256 fallback if missing |
| `images[].resolutionWidth` | `ag_vehicle_image` | `resolution_width` | Integer |
| `images[].resolutionHeight` | `ag_vehicle_image` | `resolution_height` | Integer |

## Feature Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `features[]` strings | `ag_vehicle_feature` | `feature_code`, `feature_name` | `sanitize_title()` code, human-readable name |
| `features[].value` | `ag_vehicle_feature_value` | `value_boolean`, `value_number`, `value_text`, `value_json` | Type-aware normalization |
| feature sort order | `ag_vehicle_feature_value` | `sort_order` | Integer order |
| primary feature flag | `ag_vehicle_feature_value` | `is_primary` | Boolean |

## Alias and Metadata Mapping
| Toyota Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| canonical marketing title | `ag_vehicle_alias` | `alias_name` | `YEAR MAKE MODEL TRIM` |
| normalized title | `ag_vehicle_alias` | `alias_name_normalized` | `sanitize_title()` |
| provider raw record | `ag_vehicle_metadata` | `metadata` | Full raw Toyota payload retained |
| unmapped fields | `ag_vehicle_metadata` | `metadata.unmapped_fields` | Preserved as JSON |
| provider audit data | `ag_import_batch`, `ag_import_job`, `ag_import_log` | JSON/log columns | Full import trace retained |

## Compatibility Layer Mapping
| WordPress Field | AutoGyrus Table | Column | Transformation |
|---|---|---|---|
| `vehicle` post ID | `ag_vehicle_map` | `external_id` | `wp-{post_id}` |
| WP VIN/meta fields | `ag_vehicle_master` and current tables | matching columns | Direct sync from WordPress |
| WP vehicle post title | `ag_vehicle_alias` / WP post title | alias/title | Canonical vehicle title |

## Unmapped Field Strategy
1. Keep the original Toyota payload intact in `ag_vehicle_metadata.metadata`.
2. Store any unknown Toyota keys in `metadata.unmapped_fields`.
3. Use field-level mapping only when a canonical AutoGyrus destination exists.
4. Do not discard provider data.

## Notes
- The importer is idempotent by provider + source system + external ID + current record hash.
- Current-state tables use the generated record guard pattern already deployed in the schema.
- The Toyota provider class includes a sample payload for smoke-path validation.
