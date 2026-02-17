# GitHub Copilot Custom Instructions - WP Distraction Free View

This file provides custom instructions for GitHub Copilot when working on the WP Distraction Free View plugin.

---

## Project Overview

**Plugin Name:** WP Distraction Free View  
**Purpose:** Provides distraction-free viewing mode for WordPress posts, pages, and custom post types  
**Current Version:** 1.6.0  
**Namespace:** WPDFV  
**Text Domain:** wpdfv

---

## Architecture & Code Organization

### Directory Structure

```
src/
├── Admin/          # Admin panel functionality
│   ├── Actions.php
│   ├── Filters.php
│   ├── Settings.php
│   ├── SettingsApi.php
│   └── Upgrades.php
├── Includes/       # Frontend functionality
│   ├── Actions.php
│   ├── Filters.php
│   ├── Helpers.php
│   └── Shortcodes/
│       └── Main.php
└── Plugin.php      # Main plugin class

assets/
├── src/            # Source files (before build)
│   ├── js/
│   ├── css/
│   └── images/
└── dist/           # Compiled assets

blocks/             # Gutenberg blocks (to be created)
tests/              # Test files (to be created)
```

### Namespace & Autoloading

- **Namespace:** `WPDFV\` (all plugin classes)
- **Autoloading:** PSR-4 via Composer
- **Mapping:** `WPDFV\` → `src/`

**Example:**
```php
namespace WPDFV\Admin;
// Maps to: src/Admin/ClassName.php
```

---

## WordPress Coding Standards

### PHP Standards

1. **Follow WordPress Coding Standards**
   - Use WordPress-Core ruleset
   - Run PHPCS: `./vendor/bin/phpcs --standard=phpcs.ruleset.xml`
   - Auto-fix: `./vendor/bin/phpcbf --standard=phpcs.ruleset.xml`

2. **Text Domain**
   - Always use: `'wpdfv'`
   - Never hardcode strings, use `__()`, `esc_html__()`, etc.

3. **Type Hints** (Recommended for new code)
   ```php
   public function get_option( string $option, string $section, string $default = '' ): string
   ```

4. **Security - CRITICAL**
   - ✅ **Always** verify nonces for AJAX/form submissions
   - ✅ **Always** sanitize input: `sanitize_text_field()`, `absint()`, `sanitize_key()`
   - ✅ **Always** escape output: `esc_html()`, `esc_attr()`, `esc_url()`
   - ✅ **Always** check capabilities: `current_user_can()`
   
   **Bad Example:**
   ```php
   $post_id = $_POST['id']; // ❌ No sanitization
   echo $title; // ❌ No escaping
   ```
   
   **Good Example:**
   ```php
   check_ajax_referer( 'wpdfv_nonce', 'nonce' );
   $post_id = absint( $_POST['id'] );
   echo esc_html( $title );
   ```

5. **Naming Conventions**
   - Functions: `wpdfv_function_name()`
   - Classes: `PascalCase`
   - Methods: `snake_case()`
   - Variables: `$snake_case`

### JavaScript Standards

1. **Follow WordPress JavaScript Coding Standards**
   - Run ESLint: `npm run lint:js`
   - Auto-fix: `npm run lint:js-fix`

2. **Prefer Modern JavaScript**
   - Use ES6+ syntax
   - Avoid jQuery when possible (use vanilla JS or @wordpress packages)
   - Use `const`/`let` instead of `var`

3. **WordPress Packages**
   - Use `@wordpress/element` instead of React directly
   - Use `@wordpress/components` for UI components
   - Use `@wordpress/i18n` for translations
   - Use `wp.apiFetch` for API calls

### CSS/SCSS Standards

1. **Follow WordPress CSS Coding Standards**
   - Run Stylelint: `npm run lint:scss`
   - Use BEM naming convention where appropriate

2. **Class Naming**
   - Prefix all classes with `wpdfv-`
   - Example: `.wpdfv-fullscreen-container`

---

## Security Best Practices

### Critical Security Rules

1. **AJAX Handlers** (See: `src/Includes/Shortcodes/Main.php:83-102` for what NOT to do)
   ```php
   public function ajax_handler() {
       // 1. Verify nonce
       check_ajax_referer( 'wpdfv_action', 'nonce' );
       
       // 2. Check capabilities
       if ( ! current_user_can( 'read' ) ) {
           wp_send_json_error( 'Insufficient permissions' );
       }
       
       // 3. Sanitize input
       $post_id = absint( $_POST['post_id'] );
       
       // 4. Validate
       if ( ! $post_id ) {
           wp_send_json_error( 'Invalid post ID' );
       }
       
       // 5. Escape output
       $title = esc_html( get_the_title( $post_id ) );
       
       wp_send_json_success( [ 'title' => $title ] );
   }
   ```

2. **Never Trust User Input**
   - Sanitize `$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`
   - Validate against expected types/values
   - Use whitelist validation when possible

3. **Output Escaping Context**
   - HTML: `esc_html()`
   - HTML Attributes: `esc_attr()`
   - URLs: `esc_url()` (not `esc_url_raw()` for output)
   - JavaScript: `esc_js()`
   - Post content: `wp_kses_post()`

---

## Build System

### Current State (Being Modernized)

- **Current:** Custom webpack config with deprecated node-sass
- **Target:** @wordpress/scripts (WordPress standard build tool)

### Build Commands

```bash
# Development
npm run start       # Watch mode
npm run dev         # One-time dev build

# Production
npm run build       # Production build

# Linting
npm run lint        # Run all linters
npm run lint:js     # JavaScript only
npm run lint:php    # PHP only
npm run lint:scss   # SCSS only
```

### Future Build System (In Progress)

When migrating to @wordpress/scripts:
```json
{
  "scripts": {
    "start": "wp-scripts start",
    "build": "wp-scripts build",
    "format": "wp-scripts format",
    "lint:js": "wp-scripts lint-js",
    "lint:pkg-json": "wp-scripts lint-pkg-json"
  }
}
```

---

## Block Development (Future)

### Block Structure

Create blocks in `src/blocks/` directory:

```
src/blocks/distraction-free-view/
├── block.json      # Block metadata
├── index.js        # Block registration
├── edit.js         # Editor component
├── save.js         # Frontend save
├── style.scss      # Frontend styles
└── editor.scss     # Editor styles
```

### Block Registration

```javascript
// index.js
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: Edit,
    save,
} );
```

### Block Metadata (block.json)

```json
{
    "apiVersion": 3,
    "name": "wpdfv/distraction-free-view",
    "title": "Distraction Free View",
    "category": "widgets",
    "icon": "visibility",
    "description": "Add a distraction-free reading mode button",
    "textdomain": "wpdfv",
    "editorScript": "file:./index.js",
    "style": "file:./style-index.css",
    "editorStyle": "file:./index.css"
}
```

---

## Testing (To Be Implemented)

### PHP Testing with PHPUnit

```php
namespace WPDFV\Tests;

class HelpersTest extends \WP_UnitTestCase {
    public function test_get_button_text() {
        $text = \WPDFV\Includes\Helpers::get_button_text();
        $this->assertIsString( $text );
        $this->assertNotEmpty( $text );
    }
}
```

### JavaScript Testing with Jest

```javascript
import { render } from '@testing-library/react';

describe( 'BlockEdit', () => {
    it( 'renders correctly', () => {
        const { container } = render( <Edit /> );
        expect( container ).toMatchSnapshot();
    } );
} );
```

---

## Common Patterns & Helpers

### Getting Settings

```php
use WPDFV\Includes\Helpers;

$settings = Helpers::get_settings();
$button_text = Helpers::get_button_text();
$display_location = Helpers::display_location();
$post_types = Helpers::where_to_display();
```

### Registering Assets

```php
wp_enqueue_script(
    'wpdfv-script',
    WPDFV_PLUGIN_URL . 'assets/dist/js/wpdfv.js',
    [ 'wp-element', 'wp-components' ], // Use WP dependencies
    WPDFV_VERSION,
    true
);

wp_localize_script( 'wpdfv-script', 'wpdfvData', [
    'ajaxurl' => admin_url( 'admin-ajax.php' ),
    'nonce' => wp_create_nonce( 'wpdfv_action' ),
] );
```

### Creating Shortcodes

```php
add_shortcode( 'wpdfv', [ $this, 'render_shortcode' ] );

public function render_shortcode( $atts ) {
    $atts = shortcode_atts( [
        'post_id' => get_the_ID(),
    ], $atts, 'wpdfv' );
    
    return wp_kses_post( $output );
}
```

---

## WordPress API Usage

### Settings API (Recommended Pattern)

```php
// Register setting
register_setting( 'wpdfv_settings', 'wpdfv_settings', [
    'sanitize_callback' => 'wpdfv_sanitize_settings',
    'default' => wpdfv_get_default_settings(),
] );

// Add section
add_settings_section(
    'wpdfv_general',
    __( 'General Settings', 'wpdfv' ),
    '__return_empty_string',
    'wpdfv_settings'
);

// Add field
add_settings_field(
    'button_text',
    __( 'Button Text', 'wpdfv' ),
    'wpdfv_button_text_callback',
    'wpdfv_settings',
    'wpdfv_general'
);
```

### REST API (Preferred over admin-ajax.php)

```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'wpdfv/v1', '/posts/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'wpdfv_get_post_content',
        'permission_callback' => 'wpdfv_check_permission',
        'args' => [
            'id' => [
                'validate_callback' => 'absint',
                'sanitize_callback' => 'absint',
            ],
        ],
    ] );
} );
```

---

## WPVIP Best Practices

1. **Caching**
   - Use WordPress object cache
   - Use transients for expensive operations
   - Don't use PHP sessions

2. **Database**
   - Use `$wpdb` properly
   - Prepare all queries: `$wpdb->prepare()`
   - Use `get_option()` with autoload=false for large data

3. **Performance**
   - Conditional asset loading
   - Minimize external HTTP requests
   - Use WordPress cron for scheduled tasks

4. **Security**
   - Follow all security guidelines above
   - Never output unescaped data
   - Always check capabilities
   - Use nonces for all actions

---

## Known Issues (From Review)

### Critical Security Issues (Fix Immediately)

1. **AJAX Handler** (`src/Includes/Shortcodes/Main.php:83-102`)
   - Missing nonce verification
   - Unsanitized `$_POST['id']`
   - Missing output escaping
   - No capability checks

2. **Settings API** (`src/Admin/SettingsApi.php:70`, `src/Admin/Settings.php:128`)
   - Unsanitized `$_GET['tab']`

3. **Output Escaping** (`src/Includes/Actions.php:64-70`)
   - Using `esc_url_raw()` instead of `esc_url()` in output context

### Technical Debt

1. **PHPCS Violations:** 61 errors (41 auto-fixable)
2. **Build System:** Deprecated node-sass
3. **No Tests:** Zero test coverage
4. **jQuery Dependency:** Should use vanilla JS
5. **Custom Settings API:** Should use WordPress Settings API

---

## When Adding New Features

### Checklist for New Code

- [ ] Follows WordPress Coding Standards
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] Nonces verified for actions
- [ ] Capabilities checked
- [ ] Type hints added (PHP)
- [ ] PHPDoc comments added
- [ ] No jQuery dependencies (use vanilla JS or WP packages)
- [ ] Internationalized (uses `__()`, `esc_html__()`, etc.)
- [ ] Text domain is 'wpdfv'
- [ ] Tests added (when test infrastructure exists)

### Before Committing

```bash
# Run linters
npm run lint
composer lint

# Fix auto-fixable issues
npm run lint:js-fix
./vendor/bin/phpcbf --standard=phpcs.ruleset.xml

# Run tests (when available)
npm test
composer test
```

---

## Useful Constants

```php
WPDFV_VERSION        // Plugin version
WPDFV_PLUGIN_FILE    // Main plugin file path
WPDFV_PLUGIN_DIR     // Plugin directory path
WPDFV_PLUGIN_URL     // Plugin URL
```

---

## Resources

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [WPVIP Documentation](https://docs.wpvip.com/)
- [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)

---

## Quick Reference: Security Checklist

**For Every User Input:**
1. ✅ Verify nonce (if action)
2. ✅ Check capability
3. ✅ Sanitize input
4. ✅ Validate against expected values
5. ✅ Escape output (context-appropriate)

**For Every AJAX Handler:**
```php
// Always include:
check_ajax_referer( 'nonce_action', 'nonce_field' );
if ( ! current_user_can( 'capability' ) ) { wp_die(); }
$input = sanitize_function( $_POST['input'] );
echo esc_html( $output );
```

---

**Last Updated:** February 17, 2026  
**Review Status:** Based on comprehensive codebase review  
**Related Docs:** CODEBASE_REVIEW.md, SUB_ISSUES.md, REFACTORING_SUMMARY.md
