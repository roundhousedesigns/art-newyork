# ART/New York — WordPress Customizations

Custom theme and plugins for the [ART/New York](https://art-newyork.org) WordPress site, maintained by [Roundhouse Designs](https://roundhouse-designs.com).

This repository tracks site-specific code only. WordPress core, uploads, and third-party plugins are not versioned here.

## Repository contents

| Path | Description |
| --- | --- |
| `wp-content/themes/rhd-art-new-york/` | Custom block theme for ART/New York |
| `wp-content/plugins/rhd-artny-directory/` | Member directory blocks synced from Xplor (PerfectMind) |

## Requirements

- WordPress 6.9+
- PHP 7.4+ (theme requires PHP 7.2+)
- Node.js 20+ (theme CSS build only)

## Theme: `rhd-art-new-york`

A full-site editing (FSE) block theme for ART/New York, based on Twenty Twenty-Five.

**Highlights**

- Block templates, template parts, and block patterns for site layouts
- Custom color palette, typography (Gibson), and spacing via `theme.json`
- PostCSS build for the main stylesheet (`style.css` → `style.min.css`)

**Development**

```bash
cd wp-content/themes/rhd-art-new-york
npm install
npm run build   # compile minified CSS
npm run watch   # rebuild on change
```

The theme enqueues `style.min.css` in production unless `SCRIPT_DEBUG` is enabled.

## Plugin: `rhd-artny-directory`

Searchable member directory blocks that pull organization and individual data from the Xplor B2C API (PerfectMind).

**Blocks**

- **ART/NY Organizations Directory** (`rhd/artny-directory`) — Xplor `Account` records
- **ART/NY Individuals Directory** (`rhd/artny-individuals-directory`) — Xplor `Contact` records

Both blocks support client-side search, multi-select taxonomy filters, and pagination. Data is cached in WordPress transients and refreshed automatically every hour via WP-Cron.

**Xplor configuration**

Define these constants in `wp-config.php` or the site stack environment:

- `PERFECTMIND_BASE_URL` — Xplor server URL
- `PERFECTMIND_ACCESS_KEY` or `PERFECTMIND_API_KEY` — sent as the `X-Access-Key` header
- `PERFECTMIND_CLIENT_NUMBER` — sent as the `X-Client-Number` header

Administrators can trigger an on-demand sync from the admin bar (**Refresh Xplor Data**).

**Verify cron schedule**

```bash
docker compose run --rm wp-cli wp cron event list --fields=hook,recurrence | grep rhd_artny
```

## License

GPL-2.0-or-later
