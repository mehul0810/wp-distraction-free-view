# WP Distraction Free View - Implementation Sub-Issues

This document contains detailed sub-issues derived from the codebase review. Each issue can be created in GitHub to track implementation progress.

---

## Priority 0 (Critical) - Security Fixes

### Issue #1: Fix AJAX Handler Security Vulnerabilities

**Priority:** P0 (Critical)  
**Type:** Bug, Security  
**Labels:** security, bug, high-priority  
**Estimated Time:** 4-8 hours

**Description:**

The AJAX handler in `src/Includes/Shortcodes/Main.php` has critical security vulnerabilities that allow unauthorized access and potential XSS attacks.

**Current Issues:**
1. No nonce verification
2. No capability checks
3. Unsanitized input (`$_POST['id']`)
4. Unescaped output (`$post_details->post_title`, `$post_details->post_content`)
5. Missing `ob_end_clean()` or proper output handling

**Location:** `src/Includes/Shortcodes/Main.php:83-102`

**Tasks:**
- [ ] Add nonce generation in JavaScript when making AJAX call
- [ ] Add nonce verification in `display_post_details_callback()`
- [ ] Sanitize `$_POST['id']` using `absint()`
- [ ] Add capability check (e.g., check if post is public or user can read it)
- [ ] Escape `$post_details->post_title` with `esc_html()`
- [ ] Process `$post_details->post_content` with `wp_kses_post()`
- [ ] Fix `ob_get_contents()` to properly output (use `ob_get_clean()` and `echo`)
- [ ] Add error handling for invalid post IDs
- [ ] Add unit tests for the fixed handler

**Success Criteria:**
- All inputs sanitized
- All outputs escaped
- Nonce verification passes
- Proper capability checks in place
- No security vulnerabilities detected by security scanning tools

**References:**
- [WordPress AJAX Security](https://developer.wordpress.org/plugins/javascript/ajax/#security)
- [Data Validation](https://developer.wordpress.org/apis/security/data-validation/)
- [Escaping Output](https://developer.wordpress.org/apis/security/escaping/)

---

### Issue #2: Sanitize and Validate All User Inputs

**Priority:** P0 (Critical)  
**Type:** Security  
**Labels:** security, enhancement, high-priority  
**Estimated Time:** 3-5 hours

**Description:**

Multiple locations in the codebase accept user input without proper sanitization, creating security risks.

**Affected Files:**
1. `src/Admin/SettingsApi.php:70` - `$_GET['tab']` not sanitized
2. `src/Admin/Settings.php:128` - `$_GET['tab']` not sanitized
3. Any other locations accessing `$_GET`, `$_POST`, `$_REQUEST` directly

**Tasks:**
- [ ] Audit all direct superglobal access (`$_GET`, `$_POST`, `$_REQUEST`, `$_COOKIE`)
- [ ] Sanitize `$_GET['tab']` with `sanitize_key()`
- [ ] Add nonce verification to settings save functionality
- [ ] Validate all inputs against expected values/types
- [ ] Add whitelist validation for tab names
- [ ] Implement input validation helper functions
- [ ] Add unit tests for input sanitization

**Success Criteria:**
- No direct superglobal access without sanitization
- All user inputs properly validated
- No security warnings from PHPCS WordPress.Security sniffs

**Example Fix:**
```php
// Before:
$current_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';

// After:
$current_tab = ! empty( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : '';
```

---

### Issue #3: Fix Output Escaping Issues

**Priority:** P0 (Critical)  
**Type:** Security  
**Labels:** security, bug, high-priority  
**Estimated Time:** 2-4 hours

**Description:**

Multiple instances of improper output escaping could lead to XSS vulnerabilities.

**Affected Files:**
1. `src/Includes/Actions.php:64-70` - Using `esc_url_raw()` instead of `esc_url()`
2. `src/Includes/Shortcodes/Main.php:92-95` - Missing output escaping

**Tasks:**
- [ ] Replace `esc_url_raw()` with `esc_url()` for output contexts
- [ ] Use `esc_url_raw()` only for database storage or redirects
- [ ] Audit all `echo` and `print` statements
- [ ] Add appropriate escaping functions based on context:
  - `esc_html()` for HTML content
  - `esc_attr()` for HTML attributes
  - `esc_url()` for URLs in output
  - `wp_kses_post()` for post content
- [ ] Add late escaping pattern documentation
- [ ] Run security scanning tools

**Success Criteria:**
- All dynamic output properly escaped
- Context-appropriate escaping functions used
- No XSS vulnerabilities

**Example Fix:**
```php
// Before:
echo esc_url_raw( WPDFV_PLUGIN_URL . 'assets/dist/images/print.svg' );

// After:
echo esc_url( WPDFV_PLUGIN_URL . 'assets/dist/images/print.svg' );
```

---

## Priority 1 (High) - Build & Infrastructure

### Issue #4: Migrate Build System to @wordpress/scripts

**Priority:** P1 (High)  
**Type:** Enhancement, Infrastructure  
**Labels:** build, infrastructure, modernization  
**Estimated Time:** 8-12 hours

**Description:**

The current build system uses a custom webpack configuration with deprecated dependencies (node-sass). Migrate to @wordpress/scripts for modern WordPress development standards.

**Current Issues:**
- Custom webpack.config.js duplicating WordPress tooling
- node-sass is deprecated (should use dart-sass)
- Underutilizing @wordpress/scripts (already in dependencies)
- Mixed build approach causing confusion

**Tasks:**
- [ ] Remove custom `webpack.config.js`
- [ ] Update package.json scripts to use @wp-scripts:
  ```json
  "start": "wp-scripts start",
  "build": "wp-scripts build",
  "plugin-zip": "wp-scripts plugin-zip"
  ```
- [ ] Create `.wp-env.json` for local development environment
- [ ] Remove node-sass dependency
- [ ] Update all Babel and webpack dependencies to latest
- [ ] Configure `@wordpress/scripts` via `webpack.config.js` override if needed
- [ ] Update build paths and entry points
- [ ] Test production build
- [ ] Update documentation (README.md) with new build commands
- [ ] Configure ESLint and Prettier with WordPress defaults

**Dependencies to Remove:**
- webpack
- webpack-cli
- babel-loader
- node-sass
- sass-loader
- mini-css-extract-plugin
- clean-webpack-plugin
- copy-webpack-plugin
- And other webpack-related packages

**Dependencies to Keep/Update:**
- @wordpress/scripts (update to latest)
- @wordpress/* packages

**Success Criteria:**
- `npm run build` works without errors
- `npm run start` runs dev server successfully
- Assets compiled correctly (JS, CSS, images)
- RTL CSS generated properly
- File sizes similar or smaller than current build

**References:**
- [@wordpress/scripts Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)
- [WordPress Build Tools](https://developer.wordpress.org/block-editor/getting-started/devenv/)

---

### Issue #5: Update Dependencies and Fix Security Vulnerabilities

**Priority:** P1 (High)  
**Type:** Security, Maintenance  
**Labels:** dependencies, security, maintenance  
**Estimated Time:** 4-6 hours

**Description:**

Many npm packages are outdated and may contain security vulnerabilities.

**Tasks:**
- [ ] Run `npm audit` to identify vulnerabilities
- [ ] Run `npm outdated` to check for updates
- [ ] Update @wordpress/scripts to latest stable version
- [ ] Update all @wordpress/* packages to compatible versions
- [ ] Remove deprecated packages (node-sass, etc.)
- [ ] Run `npm audit fix` to auto-fix vulnerabilities
- [ ] Manually update packages with breaking changes
- [ ] Update composer dependencies (phpcs, wpcs)
- [ ] Test build after updates
- [ ] Update package-lock.json
- [ ] Document any breaking changes in CHANGELOG.md

**Specific Updates:**
```json
{
  "@wordpress/scripts": "^28.0.0",  // Update from 12.5.0
  "sass": "^1.77.0",  // Replace node-sass
  // Remove webpack, babel-loader, etc. (provided by @wp-scripts)
}
```

**Success Criteria:**
- `npm audit` shows 0 vulnerabilities
- All packages up to date or with documented reasons for versions
- Build system works correctly after updates

---

### Issue #6: Remove jQuery Dependency from Frontend

**Priority:** P1 (High)  
**Type:** Enhancement, Performance  
**Labels:** performance, modernization, javascript  
**Estimated Time:** 6-10 hours

**Description:**

Frontend JavaScript currently depends on jQuery, which is unnecessary for the simple DOM manipulations and AJAX calls used in the plugin.

**Current File:** `assets/src/js/frontend/wpdfv.js`

**Tasks:**
- [ ] Audit jQuery usage in wpdfv.js
- [ ] Convert `$()` selectors to `document.querySelector()` / `querySelectorAll()`
- [ ] Convert `$.post()` to `fetch()` or `wp.apiFetch()` API
- [ ] Convert jQuery event handlers to `addEventListener()`
- [ ] Convert jQuery animations to CSS transitions/animations
- [ ] Replace jQuery DOM manipulation with vanilla JS
- [ ] Remove jQuery from wp_enqueue_script dependencies
- [ ] Test all frontend functionality
- [ ] Measure performance improvements (bundle size, load time)
- [ ] Update documentation

**Example Conversion:**
```javascript
// Before (jQuery):
jQuery( document ).ready( function( $ ) {
    $( '.wpdfv-fullscreen-btn' ).on( 'click', function( e ) {
        $.post( wpdfv.ajaxurl, data, function(response) {
            $('.wpdfv-overlay-wrap').html(response);
        });
    });
});

// After (Vanilla JS):
document.addEventListener( 'DOMContentLoaded', function() {
    document.querySelector( '.wpdfv-fullscreen-btn' ).addEventListener( 'click', async function( e ) {
        const response = await fetch( wpdfv.ajaxurl, {
            method: 'POST',
            body: new FormData( /* data */ )
        });
        const html = await response.text();
        document.querySelector( '.wpdfv-overlay-wrap' ).innerHTML = html;
    });
});
```

**Success Criteria:**
- No jQuery dependencies
- All functionality working correctly
- Bundle size reduced
- Performance metrics improved

**References:**
- [You Might Not Need jQuery](https://youmightnotneedjquery.com/)
- [Fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)

---

## Priority 1 (High) - Features

### Issue #7: Implement Gutenberg Block for Distraction-Free View

**Priority:** P1 (High)  
**Type:** Feature  
**Labels:** block-editor, feature, enhancement  
**Estimated Time:** 12-16 hours

**Description:**

Add Gutenberg block support to allow users to insert distraction-free view buttons visually in the block editor, improving user experience and aligning with modern WordPress standards.

**Requirements:**
1. Visual block inserter
2. Customizable button text in block settings
3. Post selection capability
4. Style variations (button appearance)
5. Preview in editor
6. Backward compatibility with existing shortcodes

**Tasks:**

**Phase 1: Setup**
- [ ] Create `src/blocks/` directory structure
- [ ] Initialize block using @wordpress/create-block patterns
- [ ] Configure block.json with proper metadata
- [ ] Setup block registration in PHP
- [ ] Configure build for blocks in package.json

**Phase 2: Block Development**
- [ ] Create block edit component (React)
- [ ] Create block save component
- [ ] Add InspectorControls for settings:
  - Button text input
  - Post selector (optional)
  - Style variation picker
- [ ] Add block preview
- [ ] Style block for editor view
- [ ] Style block for frontend

**Phase 3: Features**
- [ ] Add support for custom post types
- [ ] Implement style variations (outlined, filled, etc.)
- [ ] Add alignment support
- [ ] Add color customization (if appropriate)
- [ ] Add icon picker (optional)

**Phase 4: Integration**
- [ ] Ensure block works with existing overlay system
- [ ] Test with different themes
- [ ] Add block patterns/examples
- [ ] Add block to inserter with proper category
- [ ] Add block icon

**Phase 5: Documentation & Testing**
- [ ] Add block documentation
- [ ] Create user guide for block
- [ ] Add E2E tests for block
- [ ] Test block transformations (shortcode to block)
- [ ] Test backward compatibility

**Block Structure:**
```
src/blocks/
└── distraction-free-view/
    ├── block.json
    ├── edit.js
    ├── save.js
    ├── index.js
    ├── style.scss
    └── editor.scss
```

**Example block.json:**
```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "wpdfv/distraction-free-view",
  "title": "Distraction Free View",
  "category": "widgets",
  "icon": "visibility",
  "description": "Add a button to view content in distraction-free mode.",
  "keywords": ["read", "view", "fullscreen", "distraction"],
  "attributes": {
    "buttonText": {
      "type": "string",
      "default": "Read Mode"
    },
    "postId": {
      "type": "number"
    },
    "style": {
      "type": "string",
      "default": "filled"
    }
  },
  "supports": {
    "html": false,
    "align": true,
    "color": {
      "background": true,
      "text": true
    }
  },
  "textdomain": "wpdfv",
  "editorScript": "file:./index.js",
  "style": "file:./style-index.css",
  "editorStyle": "file:./index.css"
}
```

**Success Criteria:**
- Block appears in inserter
- Block settings work correctly
- Preview displays accurately
- Shortcode continues to work
- No conflicts with existing functionality
- Meets WordPress accessibility standards

**References:**
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [@wordpress/create-block](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-create-block/)
- [Block API Reference](https://developer.wordpress.org/block-editor/reference-guides/block-api/)

---

## Priority 2 (Medium) - Code Quality

### Issue #8: Fix PHP Coding Standards Violations

**Priority:** P2 (Medium)  
**Type:** Code Quality, Maintenance  
**Labels:** code-quality, phpcs, standards  
**Estimated Time:** 4-6 hours

**Description:**

PHPCS reports 61 errors and 7 warnings. Clean up the codebase to comply with WordPress Coding Standards and WPVIP best practices.

**Current Status:**
- 61 errors
- 7 warnings
- 41 errors auto-fixable with phpcbf

**Tasks:**

**Phase 1: Auto-fix**
- [ ] Run `./vendor/bin/phpcbf --standard=phpcs.ruleset.xml .`
- [ ] Review changes made by phpcbf
- [ ] Commit auto-fixes

**Phase 2: Manual Fixes**
- [ ] Fix remaining 20 errors manually
- [ ] Add translator comments for all i18n strings
- [ ] Fix array syntax issues (use short array syntax consistently)
- [ ] Fix spacing and indentation issues
- [ ] Update text domain in phpcs.ruleset.xml (change "perform" to "wpdfv")

**Phase 3: Prevention**
- [ ] Add PHPCS to pre-commit hooks
- [ ] Add PHPCS to CI/CD pipeline
- [ ] Document coding standards in CONTRIBUTING.md
- [ ] Setup editor integration (VS Code, PHPStorm)

**Files with Issues:**
- uninstall.php (1 error)
- webpack.config.js (30 errors, 7 warnings) - consider excluding from PHPCS
- wp-textdomain.js (3 errors) - consider excluding from PHPCS
- src/Admin/Filters.php (2 errors)
- src/Admin/Settings.php (10 errors)
- src/Admin/SettingsApi.php (11 errors)
- src/Includes/Actions.php (3 errors)
- src/Includes/Helpers.php (1 error)

**Success Criteria:**
- 0 PHPCS errors
- 0 PHPCS warnings
- Consistent code style throughout
- CI passes PHPCS checks

---

### Issue #9: Refactor Settings API to Use WordPress Standards

**Priority:** P2 (Medium)  
**Type:** Refactoring  
**Labels:** refactoring, architecture, settings  
**Estimated Time:** 8-12 hours

**Description:**

The plugin currently uses a custom Settings API implementation. Refactor to use WordPress Settings API properly for better maintainability and security.

**Current Issues:**
- Custom SettingsApi class reinventing WordPress functionality
- AJAX-based settings save (should use standard form submission)
- Manual field rendering (should use Settings API callbacks)
- No built-in sanitization

**Tasks:**

**Phase 1: Planning**
- [ ] Review current settings structure
- [ ] Map current settings to WordPress Settings API
- [ ] Plan migration path for existing settings

**Phase 2: Implementation**
- [ ] Remove custom AJAX save handler
- [ ] Implement `register_setting()` for each setting
- [ ] Create settings sections with `add_settings_section()`
- [ ] Add setting fields with `add_settings_field()`
- [ ] Implement sanitization callbacks
- [ ] Update settings page to use `settings_fields()` and `do_settings_sections()`
- [ ] Remove or refactor SettingsApi class

**Phase 3: Testing**
- [ ] Test settings save functionality
- [ ] Test sanitization
- [ ] Verify backward compatibility with existing settings
- [ ] Test with multisite

**Example Implementation:**
```php
// Register settings
add_action( 'admin_init', 'wpdfv_register_settings' );
function wpdfv_register_settings() {
    register_setting(
        'wpdfv_settings',
        'wpdfv_settings',
        [
            'sanitize_callback' => 'wpdfv_sanitize_settings',
            'default' => wpdfv_get_default_settings(),
        ]
    );
    
    add_settings_section(
        'wpdfv_display_section',
        __( 'Display Settings', 'wpdfv' ),
        'wpdfv_display_section_callback',
        'wpdfv_settings'
    );
    
    add_settings_field(
        'wpdfv_button_text',
        __( 'Button Text', 'wpdfv' ),
        'wpdfv_button_text_callback',
        'wpdfv_settings',
        'wpdfv_display_section'
    );
}
```

**Success Criteria:**
- Uses WordPress Settings API
- No custom AJAX handlers for settings
- Proper sanitization
- Backward compatible with existing settings
- Code is simpler and more maintainable

---

### Issue #10: Add Type Hints and Strict Types

**Priority:** P2 (Medium)  
**Type:** Enhancement, Code Quality  
**Labels:** php, type-safety, code-quality  
**Estimated Time:** 4-6 hours

**Description:**

Improve code quality and IDE support by adding type hints to all methods and enabling strict types.

**Tasks:**
- [ ] Add `declare(strict_types=1);` to all PHP files
- [ ] Add parameter type hints to all methods
- [ ] Add return type declarations
- [ ] Update PHPDoc blocks to match type hints
- [ ] Handle nullable types properly
- [ ] Test thoroughly (strict types may reveal hidden bugs)

**Example:**
```php
// Before:
public function get_option( $option, $section, $default = '' ) {
    $section = "wpdfv_{$section}";
    $options = get_option( $section );
    return isset( $options[ $option ] ) ? $options[ $option ] : $default;
}

// After:
declare(strict_types=1);

public function get_option( string $option, string $section, string $default = '' ): string {
    $section = "wpdfv_{$section}";
    $options = get_option( $section );
    return isset( $options[ $option ] ) ? (string) $options[ $option ] : $default;
}
```

**Success Criteria:**
- All methods have type hints
- strict_types enabled in all files
- No type errors
- PHPDoc blocks match type hints

---

## Priority 2 (Medium) - Testing & Quality

### Issue #11: Add Automated Testing Infrastructure

**Priority:** P2 (Medium)  
**Type:** Infrastructure, Testing  
**Labels:** testing, infrastructure, quality  
**Estimated Time:** 12-16 hours

**Description:**

Currently, the plugin has no automated tests. Add comprehensive testing infrastructure with PHPUnit for PHP, Jest for JavaScript, and E2E tests.

**Tasks:**

**Phase 1: Setup PHPUnit**
- [ ] Install PHPUnit and WordPress test framework
- [ ] Create `tests/` directory structure
- [ ] Setup `phpunit.xml.dist`
- [ ] Configure test database
- [ ] Create bootstrap file
- [ ] Document how to run tests

**Phase 2: PHP Unit Tests**
- [ ] Add tests for Helpers class methods
- [ ] Add tests for settings sanitization
- [ ] Add tests for AJAX handlers
- [ ] Add tests for shortcode rendering
- [ ] Add tests for filter/action callbacks
- [ ] Aim for >70% code coverage

**Phase 3: JavaScript Tests**
- [ ] Setup Jest configuration
- [ ] Add tests for frontend JavaScript
- [ ] Add tests for block components (when block is created)
- [ ] Test AJAX interactions
- [ ] Test DOM manipulations

**Phase 4: E2E Tests**
- [ ] Setup @wordpress/e2e-tests or Playwright
- [ ] Add tests for settings page
- [ ] Add tests for frontend button
- [ ] Add tests for overlay functionality
- [ ] Add tests for block inserter (when block is created)

**Phase 5: CI Integration**
- [ ] Add test workflow to GitHub Actions
- [ ] Run tests on every PR
- [ ] Add code coverage reporting
- [ ] Setup test matrix (multiple PHP/WP versions)

**Directory Structure:**
```
tests/
├── bootstrap.php
├── phpunit.xml.dist
├── unit/
│   ├── test-helpers.php
│   ├── test-shortcodes.php
│   └── test-settings.php
├── integration/
│   └── test-ajax-handlers.php
└── e2e/
    └── settings.test.js
```

**Success Criteria:**
- Tests run successfully
- Coverage >70%
- CI pipeline configured
- Documentation for running tests

---

### Issue #12: Setup Continuous Integration Pipeline

**Priority:** P2 (Medium)  
**Type:** Infrastructure, DevOps  
**Labels:** ci-cd, infrastructure, automation  
**Estimated Time:** 6-8 hours

**Description:**

Setup comprehensive CI/CD pipeline using GitHub Actions to ensure code quality on every commit.

**Tasks:**
- [ ] Create `.github/workflows/test.yml`
- [ ] Add PHP linting (PHPCS) job
- [ ] Add PHP testing (PHPUnit) job
- [ ] Add JavaScript linting (ESLint) job
- [ ] Add JavaScript testing (Jest) job
- [ ] Add build validation job
- [ ] Add test matrix for multiple PHP versions (7.4, 8.0, 8.1, 8.2)
- [ ] Add test matrix for multiple WordPress versions (6.0, 6.1, 6.2, latest)
- [ ] Add code coverage reporting (Codecov)
- [ ] Add status badges to README.md
- [ ] Configure branch protection rules

**Workflow Example:**
```yaml
name: Test

on:
  pull_request:
  push:
    branches: [main, develop]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run PHPCS
        run: composer install && ./vendor/bin/phpcs

  phpunit:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['7.4', '8.0', '8.1', '8.2']
        wp: ['6.0', '6.2', 'latest']
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
      - name: Run Tests
        run: composer install && ./vendor/bin/phpunit

  javascript:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
      - run: npm install
      - run: npm run lint:js
      - run: npm test
```

**Success Criteria:**
- CI runs on every PR
- All checks must pass before merge
- Clear feedback on failures
- Status badges visible in README

---

## Priority 3 (Low) - Performance & Optimization

### Issue #13: Optimize Asset Loading and Delivery

**Priority:** P3 (Low)  
**Type:** Performance, Enhancement  
**Labels:** performance, optimization, assets  
**Estimated Time:** 6-8 hours

**Description:**

Implement conditional asset loading and optimize delivery to improve performance.

**Current Issues:**
- Assets load on all pages even when button not displayed
- Overlay HTML rendered on every page load
- No lazy loading

**Tasks:**

**Phase 1: Conditional Loading**
- [ ] Only enqueue assets on pages where button will display
- [ ] Check post type and settings before enqueueing
- [ ] Use `wp_script_is()` to check if already loaded
- [ ] Add `'in_footer' => true` for scripts

**Phase 2: Lazy Loading**
- [ ] Remove overlay HTML from footer
- [ ] Load overlay HTML only when button clicked
- [ ] Use JavaScript template or fetch from REST API
- [ ] Cache overlay template in sessionStorage

**Phase 3: Asset Optimization**
- [ ] Review bundle sizes
- [ ] Implement code splitting if beneficial
- [ ] Optimize images (already done, but verify)
- [ ] Add resource hints (preconnect, prefetch)
- [ ] Defer non-critical CSS

**Phase 4: Measurement**
- [ ] Add performance monitoring
- [ ] Measure before/after metrics
- [ ] Test on slow connections
- [ ] Test on mobile devices

**Success Criteria:**
- Assets only load when needed
- Reduced page weight
- Faster page load times
- No functionality broken

---

### Issue #14: Migrate from admin-ajax.php to REST API

**Priority:** P3 (Low)  
**Type:** Enhancement, API  
**Labels:** rest-api, modernization, performance  
**Estimated Time:** 8-10 hours

**Description:**

Replace admin-ajax.php usage with WordPress REST API for better performance, caching, and modern API standards.

**Benefits:**
- Better caching support
- Standard API structure
- Easier to document and test
- Better error handling
- Nonce-less authentication options

**Tasks:**

**Phase 1: Planning**
- [ ] Design REST API endpoint structure
- [ ] Plan authentication strategy
- [ ] Plan permission callbacks
- [ ] Document API schema

**Phase 2: Implementation**
- [ ] Register REST API namespace (`wpdfv/v1`)
- [ ] Create endpoint for fetching post content:
  - Route: `/wpdfv/v1/posts/(?P<id>\d+)`
  - Method: GET
  - Permission callback
- [ ] Implement endpoint callback
- [ ] Add proper sanitization and validation
- [ ] Add response caching headers

**Phase 3: Frontend Integration**
- [ ] Update JavaScript to use REST API
- [ ] Use `wp.apiFetch` or fetch API
- [ ] Handle authentication (nonce in header)
- [ ] Handle errors properly
- [ ] Update localized script data

**Phase 4: Cleanup**
- [ ] Remove old AJAX handlers
- [ ] Remove admin-ajax.php references
- [ ] Update documentation
- [ ] Add API documentation

**Example Implementation:**
```php
// Register REST route
add_action( 'rest_api_init', function() {
    register_rest_route( 'wpdfv/v1', '/posts/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'wpdfv_get_post_content',
        'permission_callback' => 'wpdfv_check_post_permission',
        'args' => [
            'id' => [
                'validate_callback' => function($param) {
                    return is_numeric($param);
                },
                'sanitize_callback' => 'absint',
            ],
        ],
    ]);
});
```

**Success Criteria:**
- REST API endpoint functional
- Frontend uses REST API
- admin-ajax.php removed
- Proper caching headers
- Documentation complete

---

## Priority 3 (Low) - UI/UX

### Issue #15: Implement WordPress Design System in Admin

**Priority:** P3 (Low)  
**Type:** Enhancement, UI/UX  
**Labels:** design-system, ui, admin  
**Estimated Time:** 10-14 hours

**Description:**

Refactor admin UI to use @wordpress/components and follow WordPress Design System guidelines.

**Current Issues:**
- Custom HTML form elements
- Inconsistent with WordPress admin UI
- Not using WordPress components library

**Tasks:**

**Phase 1: Setup**
- [ ] Install @wordpress/components if not present
- [ ] Setup React for admin pages
- [ ] Create admin app entry point

**Phase 2: Component Migration**
- [ ] Replace custom text inputs with TextControl
- [ ] Replace custom checkboxes with CheckboxControl
- [ ] Replace custom radios with RadioControl
- [ ] Use PanelBody for sections
- [ ] Use Card components for layout
- [ ] Add Notice component for messages

**Phase 3: Styling**
- [ ] Use WordPress admin color schemes
- [ ] Follow WordPress spacing guidelines
- [ ] Ensure responsive design
- [ ] Match WordPress admin aesthetics

**Phase 4: Accessibility**
- [ ] Ensure keyboard navigation
- [ ] Add ARIA labels
- [ ] Test with screen readers
- [ ] Follow WCAG 2.1 AA standards

**Example:**
```jsx
import { TextControl, CheckboxControl } from '@wordpress/components';

function SettingsPanel() {
    return (
        <PanelBody title="Display Settings">
            <TextControl
                label="Button Text"
                value={buttonText}
                onChange={(value) => setButtonText(value)}
                help="Text to display on the button"
            />
            <CheckboxControl
                label="Enable on Posts"
                checked={enablePosts}
                onChange={(checked) => setEnablePosts(checked)}
            />
        </PanelBody>
    );
}
```

**Success Criteria:**
- Uses WordPress components
- Consistent with WordPress admin
- Fully accessible
- Responsive design

---

### Issue #16: Improve Frontend Accessibility

**Priority:** P3 (Low)  
**Type:** Enhancement, Accessibility  
**Labels:** accessibility, a11y, frontend  
**Estimated Time:** 6-8 hours

**Description:**

Ensure the plugin meets WCAG 2.1 AA accessibility standards.

**Tasks:**

**Phase 1: Audit**
- [ ] Run automated accessibility tests (axe, WAVE)
- [ ] Manual keyboard navigation testing
- [ ] Screen reader testing (NVDA, JAWS, VoiceOver)
- [ ] Document accessibility issues

**Phase 2: Fixes**
- [ ] Add proper ARIA labels to buttons
- [ ] Add ARIA live regions for dynamic content
- [ ] Ensure proper focus management in overlay
- [ ] Add keyboard shortcuts (Escape to close)
- [ ] Ensure sufficient color contrast
- [ ] Add skip links if needed

**Phase 3: Documentation**
- [ ] Document keyboard shortcuts
- [ ] Add accessibility statement
- [ ] Document screen reader support

**Example:**
```html
<button
    class="wpdfv-fullscreen-btn"
    aria-label="View content in distraction-free mode"
    aria-expanded="false"
>
    Read Mode
</button>

<div
    class="wpdfv-fullscreen-overlay-container"
    role="dialog"
    aria-modal="true"
    aria-labelledby="overlay-title"
    hidden
>
    <!-- Content -->
</div>
```

**Success Criteria:**
- WCAG 2.1 AA compliant
- Keyboard navigable
- Screen reader friendly
- No accessibility errors in automated tests

---

## Priority 3 (Low) - Documentation

### Issue #17: Create Comprehensive Documentation

**Priority:** P3 (Low)  
**Type:** Documentation  
**Labels:** documentation, developer-experience  
**Estimated Time:** 8-12 hours

**Description:**

Create comprehensive documentation for users, developers, and contributors.

**Tasks:**

**Phase 1: Developer Documentation**
- [ ] Create CONTRIBUTING.md with:
  - How to setup development environment
  - Coding standards
  - Git workflow
  - How to run tests
  - How to submit PRs
- [ ] Create CHANGELOG.md following Keep a Changelog format
- [ ] Add inline code documentation (PHPDoc, JSDoc)
- [ ] Document API/hooks for extensibility
- [ ] Add architecture documentation

**Phase 2: User Documentation**
- [ ] Create user guide for settings
- [ ] Add screenshots
- [ ] Document shortcode usage
- [ ] Document block usage
- [ ] Create FAQ section
- [ ] Add troubleshooting guide

**Phase 3: API Documentation**
- [ ] Document available filters
- [ ] Document available actions
- [ ] Document helper functions
- [ ] Add code examples
- [ ] Create developer hooks reference

**Phase 4: Repository Documentation**
- [ ] Update README.md with:
  - Clear description
  - Installation instructions
  - Usage examples
  - Development setup
  - Contributing guidelines
  - License information
  - Support information
- [ ] Add issue templates
- [ ] Add PR template
- [ ] Add security policy (SECURITY.md)

**Success Criteria:**
- Complete CONTRIBUTING.md
- Complete CHANGELOG.md
- User guide complete
- API documented
- README comprehensive

---

## Implementation Notes

### Dependencies Between Issues

Some issues depend on others and should be implemented in order:

1. **Security fixes (Issues #1-3)** should be done first - they're independent and critical
2. **Build system migration (#4)** should be done before **block implementation (#7)**
3. **jQuery removal (#6)** can be done in parallel with security fixes
4. **PHPCS fixes (#8)** can be done anytime but easier after security fixes
5. **Testing infrastructure (#11)** should be setup before major refactoring
6. **CI/CD (#12)** should be setup after testing infrastructure
7. **REST API migration (#14)** should be done after security fixes
8. **Settings refactor (#9)** should be done after PHPCS fixes
9. **Design system (#15)** should be done after settings refactor
10. **Documentation (#17)** should be ongoing throughout

### Suggested Sprint Planning

**Sprint 1 (2 weeks):** Issues #1, #2, #3 (Security fixes)  
**Sprint 2 (2 weeks):** Issues #8, #4 (Code quality + build system)  
**Sprint 3 (2 weeks):** Issues #5, #6 (Dependencies + jQuery)  
**Sprint 4 (2 weeks):** Issues #7 (Block implementation - Part 1)  
**Sprint 5 (2 weeks):** Issues #7 (Block implementation - Part 2)  
**Sprint 6 (2 weeks):** Issues #11, #12 (Testing + CI/CD)  
**Sprint 7 (2 weeks):** Issues #9, #10 (Settings refactor + type hints)  
**Sprint 8 (2 weeks):** Issues #13, #14 (Performance + REST API)  
**Sprint 9 (2 weeks):** Issues #15, #16 (Design system + accessibility)  
**Sprint 10 (2 weeks):** Issue #17 (Documentation)

**Total Estimated Timeline:** 20 weeks (5 months)

---

## Additional Resources

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [WPVIP Best Practices](https://docs.wpvip.com/technical-references/best-practices/)
- [WordPress Security White Paper](https://wordpress.org/about/security/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)
