# AutoDrive AI Architecture

## WordPress Structure

- `app/public/wp-content/themes/autogyrus`
  - Custom marketplace theme
  - Homepage, inventory archive, single vehicle detail templates
  - Responsive premium UI and REST-driven detail widgets
- `app/public/wp-content/plugins/autogyrus`
  - Marketplace plugin
  - CPTs, taxonomies, ACF local field groups, REST API, AI services, dealer dashboard, sample data seeding

## Phase 1 Capabilities

- Vehicle inventory marketplace
- Dealer dashboard shortcode: `[autogyrus_dealer_dashboard]`
- AI natural language vehicle search
- Future price prediction
- Alberta winter score
- Upcoming maintenance forecast
- Reliability and health score
- Insurance cost estimator
- Lead management
- VIN decoding endpoint

## Core Plugin Classes

- `class-autogyrus-plugin.php`
  - Bootstraps services and activation hooks
- `class-autogyrus-post-types.php`
  - Registers `vehicle`, `dealer`, `lead`
  - Registers taxonomies and meta
  - Syncs SEO slugs and taxonomy terms
- `class-autogyrus-acf.php`
  - Registers ACF local field groups for vehicle, AI, and dealer data
- `class-autogyrus-rest.php`
  - Search, insights, insurance, favorites, leads, VIN decoding, dealer analytics
- `class-autogyrus-services.php`
  - AI parsing and deterministic fallback scoring engine
- `class-autogyrus-db.php`
  - Creates favorites and analytics tables
- `class-autogyrus-dashboard.php`
  - Dealer-facing dashboard UI
- `class-autogyrus-admin.php`
  - OpenAI settings screen and demo-seed action
- `class-autogyrus-sample-data.php`
  - Generates and inserts 50 demo vehicles

## REST Endpoints

- `POST /wp-json/autogyrus/v1/search`
- `GET /wp-json/autogyrus/v1/vehicle/{id}/insights`
- `POST /wp-json/autogyrus/v1/vehicle/{id}/insurance`
- `POST /wp-json/autogyrus/v1/vehicle/{id}/favorite`
- `POST /wp-json/autogyrus/v1/vehicle/{id}/lead`
- `POST /wp-json/autogyrus/v1/vin-decode`
- `GET /wp-json/autogyrus/v1/dealer/dashboard`

## Frontend Pages

- Homepage
  - AI hero search
  - feature grid
  - featured inventory
- Vehicle archive
  - sidebar filters
  - AI search widget
  - listing cards with 3 AI badges
- Vehicle detail
  - photo gallery
  - CTA actions
  - future value, winter, maintenance, reliability, insurance sections
  - dealer and history sections

## Notes

- OpenAI is optional. If no API key is configured, the marketplace still works using deterministic search parsing and score generation.
- ACF is expected to be installed and active for field-group UI. The plugin registers local field definitions automatically.
