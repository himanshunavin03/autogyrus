# Local Development Guide

## LocalWP

Project root:
- `C:\Users\himan\Local Sites\autogyrus`

Important paths:
- Theme: `app/public/wp-content/themes/autogyrus`
- Plugin: `app/public/wp-content/plugins/autogyrus`

## Development Flow

1. Start the site in LocalWP
2. Open WP admin
3. Activate theme and plugin
4. Activate ACF
5. Seed demo inventory from `AutoDrive AI` admin page
6. Test:
   - homepage AI search
   - vehicle archive filtering
   - single vehicle AI insights
   - dealer dashboard page

## OpenAI Setup

If you want real natural-language parsing:

1. Open WordPress admin
2. Navigate to `AutoDrive AI`
3. Add `OpenAI API Key`
4. Save settings

Without the key:
- search parsing uses fallback heuristics
- AI insight blocks still render from deterministic marketplace scoring logic

## Suggested Next Local Tasks

- Upload real vehicle images
- Create real dealer users with the `dealer` role
- Link each vehicle to a dealer user
- Replace deterministic VIN decoding with a commercial VIN data provider if required
