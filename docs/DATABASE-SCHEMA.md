# AutoDrive AI Database Schema

## Native WordPress Data

- `wp_posts`
  - `vehicle`
  - `dealer`
  - `lead`
- `wp_postmeta`
  - vehicle metadata
  - lead metadata
  - dealer metadata
- `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships`
  - make
  - model
  - body type
  - fuel type
  - transmission
  - province
  - city
  - drivetrain

## Custom Tables

### `wp_autogyrus_favorites`

- `id` bigint unsigned primary key
- `user_id` bigint unsigned
- `vehicle_id` bigint unsigned
- `created_at` datetime

Purpose:
- Stores buyer saved vehicles

### `wp_autogyrus_vehicle_events`

- `id` bigint unsigned primary key
- `vehicle_id` bigint unsigned
- `dealer_user_id` bigint unsigned
- `event_type` varchar(50)
- `session_hash` varchar(64)
- `meta_value` longtext
- `created_at` datetime

Purpose:
- Tracks views
- Tracks favorites
- Tracks contact requests
- Tracks test drive requests
- Supports dealer analytics

## Vehicle Meta Fields

- `vin`
- `make`
- `model`
- `year`
- `trim`
- `mileage`
- `price`
- `transmission`
- `fuel_type`
- `engine`
- `drivetrain`
- `body_type`
- `exterior_color`
- `interior_color`
- `tire_size`
- `province_registration_history`
- `block_heater`
- `remote_start`
- `winter_tires_included`
- `service_history`
- `accident_history`
- `dealer_user_id`
- `city`
- `province`
- `warranty`
- `financing_available`
- `ground_clearance_mm`
- `winter_score`
- `reliability_score`
- `future_value_rating`
- `future_value_summary`
- `reliability_summary`
- `known_issues`
- `common_complaints`
- `recall_history`

## Lead Meta Fields

- `vehicle_id`
- `lead_name`
- `lead_email`
- `lead_phone`
- `lead_type`
- `lead_message`
