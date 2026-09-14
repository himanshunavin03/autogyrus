# Deployment Guide

## Pre-Production Checklist

- Set production database credentials
- Confirm HTTPS
- Activate object/page caching
- Configure CDN or image optimization plugin
- Set OpenAI API key in production
- Verify XML sitemap with your SEO plugin of choice
- Verify robots and indexing rules

## Deployment Steps

1. Deploy WordPress core and database
2. Deploy:
   - `wp-content/themes/autogyrus`
   - `wp-content/plugins/autogyrus`
3. Activate the plugin and theme
4. Install ACF
5. Save permalinks
6. Configure menus and homepage
7. Add OpenAI API key
8. Migrate or seed inventory
9. Create dealer accounts
10. Test REST endpoints and forms

## Performance Recommendations

- Enable full-page caching
- Use WebP/AVIF media where possible
- Lazy load vehicle galleries
- Use server-level compression
- Add Cloudflare or equivalent edge caching

## Security Recommendations

- Restrict admin accounts
- Use application firewall
- Rate-limit REST write endpoints if traffic grows
- Store API keys in environment-managed configuration where possible
