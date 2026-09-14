# Black-and-white body with red header and footer

Updated: 2026-09-14. Branch: `ui/red-white-theme-refresh`.

## Scope and visual reference

The supplied screenshot guided the red header/footer, white card surfaces, near-black headings, gray secondary surfaces, and compact inventory grid. The later user clarification set the final color boundary: **the body defaults to black and white, including links, buttons, prices, and value text; red is mainly for the header and footer**. Hover feedback uses a subtle shadow and almost-white surface, with little gray shift. The user also required **template-only styling with no wording or functionality changes**. Accordingly, the implementation changes only `app/public/wp-content/themes/autogyrus/style.css`. No PHP templates, JavaScript, plugin, Python, database, API, or import code changed. The existing vehicle artwork and dynamic vehicle data remain in place; no reference image assets were copied.

## Existing frontend map

- `front-page.php`: hero, standard keyword form, AI shortcode, categories, feature cards, featured inventory, dealer CTA.
- `header.php` / `footer.php`: navigation, mobile drawer markup, global footer.
- `archive-vehicle.php`: inventory search, filters, mobile panels, results.
- `single-vehicle.php` / `template-parts/vehicle-card.php`: details, price, insights, favorite/compare/lead controls, cards.
- `page.php`: dealer dashboard and other frontend page content. Dealer shortcode markup is in `plugins/autogyrus/includes/class-autogyrus-dashboard.php`.
- `style.css`: all theme styling. `assets/js/theme.js` and plugin `templates/search.js` bind the existing drawer, filters, AI search, favorite, lead, and insight hooks.
- Preserved hooks include `#autogyrus-search-form`, `#autogyrus-query`, `#autogyrus-results`, `.search-example`, `.site-menu-toggle`, `#site-drawer`, `[data-mobile-toggle]`, `#inventory-ai-search`, `#inventory-filters`, `#favorite-vehicle`, `[data-lead-type]`, and the insight panel IDs.

## Tokens and component treatment

The `--ag-*` CSS tokens at the top of `style.css` separate brand red `#d90416` / dark red `#a60010` from body primary and heading `#111827`, muted `#596474`, background `#f7f8fa`, surface `#ffffff`, subtle surface `#f2f4f7`, hover surface `#fafbfc`, and borders `#e2e6eb` / `#cbd2db`. Shared shadows, including `--ag-shadow-hover`, radii, and spacing are also tokenized. Legacy theme variable names alias these values so existing rules continue to work.

Header/footer use the restrained red gradient. Body primary actions are near-black with white text at rest and retain those colors on hover while gaining a subtle shadow. Prices, links, depreciation values, and other value text are near-black. Outline actions, search suggestions, categories, and body links gain a nearly white hover surface and small shadow. Forms, cards, detail insights, responsive inventory grids, and dealer dashboard surfaces use the shared tokens. Semantic success/risk score colors remain distinct where they convey status.

The header `AutoGyrus` wordmark uses the existing Plus Jakarta Sans at weight 600 instead of 800. Two clipped diagonal gradients create restrained fractures within the white letters; browsers without text clipping keep the plain white wordmark, and forced-colors mode keeps a readable plain wordmark. No font download, extra logo text, or markup change was introduced.

## Verification

- Chrome/Playwright screenshots: `docs/ui-theme-screenshots/`, `home-{before,after}-{375,768,1024,1440}.png`, `archive-{before,after}-{375,768,1024,1440}.png`, and `vehicle-{before,after}-1440.png`. The "before" captures route the committed baseline stylesheet over the live site; the "after" captures the working stylesheet. `category-hover-1440.png` and `search-button-hover-1440.png` show the final shadow treatment.
- `header-wordmark-after-375.png` and `header-wordmark-after-1440.png` show the lighter fractured logo at mobile and desktop sizes. Browser computed style confirmed weight 600 and text clipping at both widths, with no horizontal overflow or page errors. The logo remained a working link from inventory back to the homepage.
- Home and archive returned HTTP 200 with 8 featured and 10 archive cards at each width. At 375, 768, 1024, and 1440 px, `document.documentElement.scrollWidth === window.innerWidth`. Vehicle detail also returned 200. No page errors, console errors, or failed network requests were observed in these screenshot passes.
- Standard search submitted `make=Toyota&model=RAV4` and displayed one card. Archive body-type filter submitted and rendered; its returned count differed from the first filtered query, an existing search behavior that needs a separate functional review.
- AI search submitted the family-of-four Alberta-winter query to `POST /wp-json/autogyrus/v1/search` and navigated to inventory with parsed filters. It displayed zero matches for that exact query. The existing WordPress search is **not** connected to the separate Python service; Python request verification is therefore not applicable to this CSS-only change.
- Unauthenticated dealer dashboard route returned 200 and displayed its existing login prompt. Authenticated inventory management and lead submission were not exercised, since they would require a dealer session or data mutation.
- Mobile drawer opened and closed; mobile inventory filters opened at 375 px. Vehicle detail retained its two lead buttons and five insight panels. CSS-only scope means no modified PHP files require lint.
- Browser computed-style check after transitions settled: default body search and vehicle-card buttons were `rgb(17, 24, 39)` with white text; prices, links, and section labels were also near-black. Primary buttons retained near-black/white on hover while their shadow rose from `0 8px 18px / 14%` to `0 10px 24px / 16%`. Suggestions, categories, and outline actions hovered to `rgb(250, 251, 252)` with near-black text and the same elevated shadow; body text links gained a smaller shadow. Header/footer gradients remained red. Keyboard focus had a solid outline.
- Lightning CSS parsed the final stylesheet; `php -l` passed for all nine theme PHP files; `node --check` passed for both existing frontend scripts; `git diff --check` passed. Token contrast ratios: brand-red/white 5.28:1, dark-red/white 7.99:1, muted/white 6.0:1, heading/subtle-surface 16.1:1. No `wp-content/debug.log` file was present locally to compare for new messages.

## Known limits and continuation

The local data contains some listings without photos, and this refresh does not add or substitute images. The baseline WordPress implementation does not provide the Python HTTP integration, visible archive sorting/pagination, or a logged-out favorite control; those cannot be claimed as tested. Dealer-authenticated screens and browser keyboard/contrast audits should be checked with a real dealer account before release. No database or Python behavior changed.

Run `node %TEMP%\ag-ui-tools\screens-final.js` and `node %TEMP%\ag-ui-tools\functional.js` in this same local environment to repeat browser checks; these temporary verification scripts are not part of the repository. Run `git diff --check` and inspect `git diff -- app/public/wp-content/themes/autogyrus/style.css` before commit. Roll back the visual change by reverting only the stylesheet change on this branch; preserve pre-existing untracked search-service files. No commit or push was made.
