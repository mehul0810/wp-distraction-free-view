# WP Distraction Free View

WP Distraction Free View adds a focused reader mode for WordPress posts, pages, and public custom post types.

[Download WP Distraction Free View on WordPress.org](https://wordpress.org/plugins/wp-distraction-free-view/)

![WordPress version](https://img.shields.io/wordpress/plugin/v/wp-distraction-free-view.svg)
![WordPress Rating](https://img.shields.io/wordpress/plugin/r/wp-distraction-free-view.svg)
![WordPress Downloads](https://img.shields.io/wordpress/plugin/dt/wp-distraction-free-view.svg)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green.svg)](https://github.com/mehul0810/wp-distraction-free-view/blob/master/license.txt)

## Support

This repository is for development. For user support, use the [WordPress.org support forum](https://wordpress.org/support/plugin/wp-distraction-free-view).

## Requirements

- WordPress 6.0 or later
- PHP 8.2 or later
- Node.js 24.15.0
- npm 11 or later
- Composer for PHP development tooling

The Node version is pinned in `.nvmrc` and `.node-version`.

## Local Development

Clone the repository into your local WordPress plugins directory:

```bash
cd /path/to/wp-content/plugins
git clone https://github.com/mehul0810/wp-distraction-free-view.git
cd wp-distraction-free-view
```

Install dependencies:

```bash
composer install
npm install
```

Build assets:

```bash
npm run build
```

Start the watch build:

```bash
npm run start
```

Run checks:

```bash
composer lint
npm run lint:js
npm run lint:css
```

## Available npm Scripts

| Command | Description |
| --- | --- |
| `npm run start` | Starts the WordPress scripts watch build. |
| `npm run build` | Builds production JS and CSS assets. |
| `npm run lint:js` | Lints JavaScript source files. |
| `npm run lint:css` | Lints CSS and SCSS source files. |
| `npm run format` | Formats source files handled by WordPress scripts. |
| `npm run packages-update` | Runs the WordPress packages update helper. |

## Development Notes

- Commit `package-lock.json` whenever npm dependency metadata changes.
- Built assets are generated into `assets/dist`.
- Runtime plugin code does not require Composer autoload files.
- The plugin is licensed under GPL-2.0-or-later.
