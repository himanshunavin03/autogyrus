# Installation Guide

## Requirements

- LocalWP or production WordPress latest version
- PHP 8.1+ recommended
- MySQL 8+ or MariaDB equivalent
- Advanced Custom Fields plugin

## Install

1. Place the custom theme in:
   - `app/public/wp-content/themes/autogyrus`
2. Place the plugin in:
   - `app/public/wp-content/plugins/autogyrus`
3. In WordPress admin:
   - Activate `AutoDrive AI Core`
   - Activate `AutoGyrus` theme
4. Install and activate Advanced Custom Fields
5. Visit:
   - `Settings > Permalinks`
   - click `Save Changes`
6. Create a page called `Dealer Dashboard`
7. Add shortcode:
   - `[autogyrus_dealer_dashboard]`
8. Build a primary navigation menu and assign it to `Primary Menu`
9. In `AutoDrive AI` admin menu:
   - add OpenAI API key
   - optionally keep default model `gpt-4o-mini`
10. Seed demo data:
   - go to `AutoDrive AI`
   - click `Seed 50 Sample Vehicles`

## Recommended WordPress Settings

- Set a static homepage
- Enable pretty permalinks with post name
- Disable indexing until production if still staging
- Configure media sizes appropriate for listing cards
