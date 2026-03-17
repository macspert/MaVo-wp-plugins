# CLAUDE.md — MaVo WordPress Plugins

This file provides guidance for AI assistants (Claude and others) working in this repository.

---

## Repository Overview

**MaVo-wp-plugins** is a monorepo for WordPress plugins. Each plugin lives in its own top-level directory and is independently deployable. This file should be updated whenever the repository structure or conventions change.

---

## Repository Structure

```
MaVo-wp-plugins/
├── CLAUDE.md                  # This file
├── .gitignore
├── composer.json              # Root-level PHP deps (shared tooling)
├── phpcs.xml                  # PHP CodeSniffer config (shared)
├── phpunit.xml.dist           # PHPUnit config template
├── package.json               # Root-level JS tooling (optional)
│
├── plugin-name/               # One directory per plugin
│   ├── plugin-name.php        # Main plugin file (plugin header here)
│   ├── readme.txt             # WordPress.org readme
│   ├── composer.json          # Plugin-level PHP deps (if needed)
│   ├── package.json           # Plugin-level JS/CSS build (if needed)
│   ├── webpack.config.js      # Asset bundler config (if applicable)
│   ├── includes/              # PHP classes
│   │   ├── class-plugin-name.php
│   │   └── ...
│   ├── admin/                 # Admin-specific assets & templates
│   ├── public/                # Front-end assets & templates
│   ├── assets/
│   │   ├── src/               # Source JS/CSS/images
│   │   └── build/             # Compiled assets (gitignored)
│   ├── languages/             # .pot / .po / .mo translation files
│   └── tests/                 # Plugin-specific tests
│       ├── bootstrap.php
│       └── test-*.php
│
└── vendor/                    # Composer deps (gitignored)
```

> **Note:** This structure is the intended convention. Add sections below as plugins are introduced.

---

## Installed Plugins

| Directory | Description | Status |
|-----------|-------------|--------|
| `mavo-cookie-consent` | Implicit cookie consent banner; dismissed on click or 300 px scroll, suppressed for 1 year via cookie | Active |

Update this table when plugins are added.

---

## Development Environment

### Requirements

- PHP >= 8.0
- WordPress >= 6.0 (for development/testing)
- Composer >= 2.x
- Node.js >= 18.x / npm >= 9.x (if building assets)
- WP-CLI (recommended)

### Initial Setup

```bash
# Install PHP dependencies
composer install

# Install JS dependencies (if applicable)
npm install

# Set up a local WordPress install (e.g. with wp-env or LocalWP)
npx @wordpress/env start   # requires Docker
```

---

## Common Commands

### PHP

```bash
# Code style check
composer lint
# or directly:
./vendor/bin/phpcs --standard=phpcs.xml

# Auto-fix code style
./vendor/bin/phpcbf --standard=phpcs.xml

# Run PHP unit tests
./vendor/bin/phpunit -c phpunit.xml.dist

# Run tests for a specific plugin
./vendor/bin/phpunit -c plugin-name/phpunit.xml
```

### JavaScript / CSS

```bash
# Build assets for development (watch mode)
npm run start

# Build assets for production
npm run build

# Run JS linting
npm run lint:js

# Run CSS linting
npm run lint:css
```

### WordPress CLI

```bash
# Activate a plugin in the dev environment
wp plugin activate plugin-name

# Deactivate
wp plugin deactivate plugin-name

# Run WP cron manually
wp cron event run --due-now
```

---

## Coding Conventions

### PHP

- Follow **WordPress Coding Standards** (WPCS): <https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/>
- Class files named `class-{classname}.php` (lowercase, hyphenated).
- One class per file.
- Namespace pattern: `MaVo\PluginName\...` (add namespaces when creating new plugins).
- Use `wp_` prefix for global functions; use class methods otherwise.
- Always sanitize input (`sanitize_text_field`, `absint`, etc.) and escape output (`esc_html`, `esc_url`, `esc_attr`, etc.).
- Nonces required for all form submissions and AJAX requests.
- Capability checks required before any privileged action.

### JavaScript

- ES2020+, transpiled via Babel (if build step exists).
- Follow **WordPress JavaScript Coding Standards**.
- Prefer `const` / `let` over `var`.
- Use `wp.ajax` or the REST API; avoid direct jQuery AJAX where possible.

### CSS / SCSS

- BEM naming convention for new components.
- Prefix all selectors with the plugin slug to avoid conflicts.

### Git

- Branch naming: `feature/short-description`, `fix/issue-description`, `chore/task`.
- Commit messages: imperative mood, ≤72 chars subject line. Example: `Add settings page for plugin-name`.
- Never commit `vendor/`, `node_modules/`, or `assets/build/`.
- Tag releases as `plugin-name/v1.2.3`.

---

## WordPress-Specific Patterns

### Plugin Main File Header

Every plugin's main PHP file must start with the standard WordPress plugin header:

```php
<?php
/**
 * Plugin Name:       My Plugin
 * Plugin URI:        https://example.com/my-plugin
 * Description:       Short description.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MaVo
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       my-plugin
 * Domain Path:       /languages
 */
```

### Hooks & Filters

- Register all hooks in the main plugin class constructor or a dedicated `register_hooks()` method.
- Use priority constants (e.g. `10`, `20`) explicitly rather than relying on defaults.
- Document non-standard priorities with a comment explaining why.

### Internationalization

- Wrap all user-facing strings: `__()`, `_e()`, `esc_html__()`, etc.
- Text domain must match the `Text Domain` header value.
- Generate `.pot` file before releases: `wp i18n make-pot . languages/plugin-name.pot`

### Database

- Use `$wpdb` for custom queries; prefer WP APIs (post meta, options) where appropriate.
- Prefix custom tables with `{$wpdb->prefix}mavo_`.
- Run schema changes through `dbDelta()` on plugin activation.
- Store the DB schema version in a site option and check on each load.

### Assets

- Enqueue scripts/styles via `wp_enqueue_scripts` / `admin_enqueue_scripts`, never inline.
- Use `wp_localize_script()` or `wp_add_inline_script()` to pass PHP data to JS.
- Version assets with the plugin version constant to bust caches on release.

---

## Testing

- Unit tests live in `plugin-name/tests/`.
- Integration tests (requiring a WP environment) are tagged `@group integration`.
- Use WP's built-in `WP_UnitTestCase` or Brain Monkey for unit testing hooks.
- Aim for coverage of all public methods and critical data-transformation logic.

---

## Release Checklist

1. Update `Version:` in the plugin header.
2. Update `readme.txt` changelog.
3. Run `composer lint` and `npm run lint` — fix all errors.
4. Run full test suite — all tests must pass.
5. Build production assets: `npm run build`.
6. Tag the release: `git tag plugin-name/vX.Y.Z`.
7. Push tag: `git push origin plugin-name/vX.Y.Z`.

---

## Notes for AI Assistants

- **Read before editing.** Always read a file before modifying it.
- **One plugin at a time.** Changes to one plugin must not break others.
- **Security first.** Every input must be sanitized; every output must be escaped. Flag any missing nonces or capability checks.
- **No global state pollution.** All plugin code must be wrapped in classes or namespaced functions.
- **Ask before adding dependencies.** New Composer or npm packages affect the whole repo — confirm with the user first.
- **Update this file** when significant structural or convention changes are made.
