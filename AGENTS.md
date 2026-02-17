# AI Agents & Automation Documentation

This document describes the AI agents, automation tools, and workflows configured for the WP Distraction Free View plugin.

---

## Overview

The plugin uses various AI-powered tools and agents to maintain code quality, automate reviews, and streamline development workflows.

---

## GitHub Copilot

### Configuration

GitHub Copilot is configured with custom instructions stored in `.github/copilot-instructions.md`.

### What Copilot Knows

Copilot has context about:
- Plugin architecture and namespace structure (`WPDFV\`)
- WordPress Coding Standards requirements
- Security best practices (nonce verification, sanitization, escaping)
- Build system (current and planned migration)
- Known issues from codebase review
- Block development patterns
- Testing conventions (when implemented)

### Using Copilot Effectively

**For Code Generation:**
- Ask Copilot to generate code following WordPress standards
- Request security checks for AJAX handlers
- Generate boilerplate for blocks, settings, or REST endpoints

**Examples:**
```
"Create a secure AJAX handler for fetching post content"
"Generate a Gutenberg block with button text control"
"Add PHPUnit test for Helpers::get_button_text()"
```

**For Code Review:**
- Ask Copilot to review for security issues
- Check for PHPCS compliance
- Verify proper sanitization and escaping

---

## GitHub Copilot Workspace

### Purpose

Used for comprehensive codebase reviews, refactoring planning, and implementation strategy.

### Recent Work

**Codebase Review (February 2026):**
- Analyzed entire plugin architecture
- Identified 3 critical security vulnerabilities
- Documented 61 PHPCS violations
- Created 17 implementation sub-issues
- Designed 5-phase refactoring roadmap

**Deliverables Created:**
- `CODEBASE_REVIEW.md` - Technical deep dive
- `SUB_ISSUES.md` - 17 prioritized implementation issues
- `REFACTORING_SUMMARY.md` - Executive summary
- `REVIEW_README.md` - Navigation guide
- `IMPLEMENTATION_CHECKLIST.md` - Progress tracker

### Using Copilot Workspace

**For Planning:**
```
"Review the security implications of adding a new AJAX endpoint"
"Plan the migration from jQuery to vanilla JavaScript"
"Design the architecture for a new feature"
```

**For Analysis:**
```
"Analyze the performance impact of loading assets on all pages"
"Review all files for proper nonce verification"
"Find all instances of unsanitized user input"
```

---

## Pre-commit Hooks (Husky)

### Configuration

Located in `.huskyrc.json` and uses `lint-staged` for targeted linting.

### What Runs on Commit

From `.lintstagedrc.json`:
- **JavaScript:** ESLint on `*.js` files
- **PHP:** PHPCS on `*.php` files
- **SCSS:** Stylelint on `*.scss` files

### How to Use

```bash
# Hooks run automatically on git commit
git commit -m "Your commit message"

# If hooks are not installed:
npm install
```

### Bypass (Use Sparingly)

```bash
# Only for emergencies
git commit --no-verify -m "Emergency fix"
```

---

## Continuous Integration (GitHub Actions)

### Current Workflows

**Release Workflows:**
- `.github/workflows/release.yml` - Production releases
- `.github/workflows/prerelease.yml` - Pre-release builds

### Planned CI/CD (From Review)

**Recommended Additions:**

1. **Test Workflow** (`test.yml`)
   - Run PHPCS on every PR
   - Run PHPUnit tests
   - Run Jest tests
   - Build validation
   - Code coverage reporting

2. **Test Matrix:**
   - PHP versions: 7.4, 8.0, 8.1, 8.2
   - WordPress versions: 6.0, 6.1, 6.2, latest

3. **Quality Gates:**
   - PHPCS must pass (0 errors)
   - Tests must pass (>80% coverage target)
   - Build must succeed

**Example Workflow:**
```yaml
name: Test
on: [pull_request, push]
jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - run: composer install
      - run: ./vendor/bin/phpcs
  
  phpunit:
    strategy:
      matrix:
        php: ['7.4', '8.0', '8.1', '8.2']
        wp: ['6.0', '6.2', 'latest']
    # ... test steps
```

---

## Code Quality Tools

### PHPCS (PHP CodeSniffer)

**Configuration:** `phpcs.ruleset.xml`

**Usage:**
```bash
# Check code
composer run lint
# or
./vendor/bin/phpcs --standard=phpcs.ruleset.xml

# Auto-fix
composer run format
# or
./vendor/bin/phpcbf --standard=phpcs.ruleset.xml
```

**Current Status:**
- 61 errors, 7 warnings (as of review)
- 41 errors are auto-fixable
- Main issues: Missing translator comments, array syntax, spacing

### ESLint

**Configuration:** `.eslintrc`

**Usage:**
```bash
# Check code
npm run lint:js

# Auto-fix
npm run lint:js-fix
```

**Standards:** WordPress JavaScript Coding Standards

### Stylelint

**Configuration:** `.stylelintrc.json`

**Usage:**
```bash
# Check SCSS
npm run lint:scss
```

**Standards:** WordPress CSS Coding Standards

---

## Security Scanning

### Manual Security Review

**Checklist for Every PR:**
- [ ] All AJAX handlers have nonce verification
- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] Capability checks present where needed
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities

### Recommended Tools

1. **WPScan** - WordPress security scanner
2. **Psalm/PHPStan** - Static analysis (to be added)
3. **npm audit** - JavaScript vulnerability scanning

```bash
# Check for vulnerabilities
npm audit

# Fix vulnerabilities
npm audit fix
```

---

## Automated Testing (Planned)

### PHPUnit

**Setup:**
```bash
# Install
composer require --dev phpunit/phpunit
composer require --dev yoast/phpunit-polyfills

# Configure
# Create: phpunit.xml.dist
# Create: tests/bootstrap.php

# Run
./vendor/bin/phpunit
```

**Test Structure:**
```
tests/
├── bootstrap.php
├── unit/
│   ├── test-helpers.php
│   └── test-settings.php
└── integration/
    └── test-ajax-handlers.php
```

### Jest (JavaScript)

**Setup:**
```bash
# Install
npm install --save-dev @wordpress/jest-preset-default

# Configure in package.json
{
  "jest": {
    "preset": "@wordpress/jest-preset-default"
  }
}

# Run
npm test
```

### E2E Testing

**Recommended:** `@wordpress/e2e-tests` or Playwright

**Test Scenarios:**
- Settings page functionality
- Frontend button rendering
- Overlay display/dismiss
- Block insertion and editing

---

## Development Workflows

### Local Development

**Recommended Setup:**
```bash
# Use @wordpress/env for local environment
npx @wordpress/env start

# Watch mode for development
npm run start
```

### Code Review Process

1. **Before PR:**
   - Run all linters: `npm run lint`
   - Fix auto-fixable issues
   - Run tests (when available)
   - Check security manually

2. **During Review:**
   - Automated checks run via CI
   - Manual security review
   - Code quality assessment

3. **After Approval:**
   - Squash/merge commits
   - Tag release if needed

---

## Agent Prompts & Templates

### For Security Reviews

```
Prompt: "Review this code for security vulnerabilities, focusing on:
1. Nonce verification in AJAX handlers
2. Input sanitization
3. Output escaping
4. Capability checks
5. SQL injection risks"
```

### For Code Generation

```
Prompt: "Generate a WordPress [feature] that:
- Follows WordPress Coding Standards
- Uses WPDFV namespace
- Includes proper security (nonces, sanitization, escaping)
- Has PHPDoc comments
- Uses text domain 'wpdfv'"
```

### For Refactoring

```
Prompt: "Refactor this code to:
- Remove jQuery dependency
- Use @wordpress packages
- Improve type safety with type hints
- Follow PSR-4 autoloading
- Maintain backward compatibility"
```

---

## Best Practices for AI-Assisted Development

### Do's

✅ **Provide context** - Share relevant files, standards, and constraints  
✅ **Be specific** - Describe exact requirements and constraints  
✅ **Verify output** - Always review and test AI-generated code  
✅ **Iterate** - Refine prompts based on output quality  
✅ **Use for boilerplate** - Generate repetitive code structures  

### Don'ts

❌ **Don't trust blindly** - AI can make mistakes  
❌ **Don't skip testing** - Always test generated code  
❌ **Don't ignore standards** - Verify compliance with WordPress standards  
❌ **Don't commit without review** - Always review before committing  
❌ **Don't skip security** - Double-check all security-related code  

---

## Memory & Context Management

### Stored Facts (For Future Sessions)

The following facts are stored in GitHub Copilot's memory for this repository:

1. **Build System:** Use @wordpress/scripts, not custom webpack
2. **Security:** All AJAX handlers need nonce verification and sanitization
3. **Architecture:** PSR-4 with WPDFV namespace, Admin vs Includes separation
4. **Standards:** Follow WordPress Coding Standards, text domain is 'wpdfv'
5. **Blocks:** Store in src/blocks/ with block.json metadata

### Updating Context

When the codebase changes significantly:
- Update `.github/copilot-instructions.md`
- Store new facts via agent sessions
- Document architectural changes
- Update this AGENTS.md file

---

## Troubleshooting

### Copilot Not Following Standards

1. Check `.github/copilot-instructions.md` is up to date
2. Be more explicit in prompts
3. Reference specific standards in requests

### Pre-commit Hooks Failing

1. Check hook configuration: `.huskyrc.json`
2. Verify linters are installed: `npm install`
3. Run linters manually to see errors
4. Fix issues before committing

### CI/CD Failures

1. Check workflow logs in GitHub Actions
2. Run tests locally: `npm test` / `composer test`
3. Verify all dependencies are committed
4. Check for environment-specific issues

---

## Future Enhancements

### Planned Agent Improvements

1. **Automated Security Scanning**
   - Add automated security checks to CI
   - Integrate with vulnerability databases
   - Block PRs with security issues

2. **AI-Powered Code Reviews**
   - Automated PHPCS checking
   - Security pattern detection
   - Performance issue detection

3. **Automated Documentation**
   - Generate API documentation from code
   - Keep CHANGELOG.md updated automatically
   - Generate user documentation from code comments

4. **Intelligent Testing**
   - AI-generated test cases
   - Coverage gap detection
   - Regression test suggestions

---

## Contributing

### For Developers

When adding new features or making changes:
1. Review `.github/copilot-instructions.md` for context
2. Use Copilot for boilerplate generation
3. Always verify generated code for security
4. Update documentation when patterns change

### For Maintainers

When reviewing PRs:
1. Check that code follows WordPress standards
2. Verify security best practices
3. Ensure tests are included (when infrastructure exists)
4. Update agent instructions if patterns change

---

## Resources

### Internal Documentation
- `.github/copilot-instructions.md` - Custom instructions for Copilot
- `CODEBASE_REVIEW.md` - Technical analysis and recommendations
- `SUB_ISSUES.md` - Implementation tasks
- `CONTRIBUTING.md` - Contribution guidelines (to be created)

### External Resources
- [GitHub Copilot Documentation](https://docs.github.com/en/copilot)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WPVIP Best Practices](https://docs.wpvip.com/technical-references/best-practices/)

---

## Maintenance

**This document should be updated when:**
- New AI tools are added to the workflow
- Significant architectural changes occur
- New patterns or standards are adopted
- Agent instructions are modified
- CI/CD workflows change

**Review Schedule:**
- Quarterly review of agent effectiveness
- Update after major refactoring
- Revise when standards change

---

**Last Updated:** February 17, 2026  
**Maintained By:** Development Team  
**Related Documents:** `.github/copilot-instructions.md`, `CODEBASE_REVIEW.md`
