# Contributing

## Branches and scope

Base new work on current `main` and target `main` with a small, reviewable PR.
Use short-lived feature, fix, or dependency branches. `main` is the single
integration branch; `develop` and `master` are retained historical refs during
the migration, not additional integration targets. Existing PRs need individual
review under the [migration plan](.github/MAIN-MIGRATION.md).

An optional `release/<version>` branch starts from current `main`, contains only
final release preparation, and targets `main`. Do not route ordinary features
through a long-lived release branch. A merge into `main` is not a release.

Preserve unrelated working-tree changes, existing holds, and security work.
Do not reset, delete, or merge `fix/security-scan-findings` as part of this pilot.
Repository access or passing tests do not authorize a push, merge, tag, release,
deployment, or repository setting change. Obtain the owner's approval for those
actions within the scope of the task.

## Local checks

Use the versions in `.nvmrc`, `.node-version`, `composer.json`, and `package.json`.
Install the committed dependency locks with `composer install` and `npm ci`.
Use the existing WordPress environment or generic project toolkit; keep synthetic
fixtures separate from client or production sites.

Before review, run:

```bash
python3 -m unittest discover -s .github/scripts/tests -v
composer validate --strict
composer lint
composer test
npm run lint:js
npm run lint:css
npm run build
git diff --check
```

Include intended generated assets in the review; do not change dependency locks
incidentally. For runtime changes, also run the WordPress integration suites and
applicable browser checks described in `README.md` and `playwright.config.js`.
CI retains the PHP/asset, WordPress integration (including multisite), and
production-package/Plugin Check jobs. PR checks remain enabled for all target
branches while the legacy PRs are reviewed; integration pushes run on `main`.

Report exact source commits, tool versions, commands, and passed, failed,
skipped, or blocked checks. A missing check is not success. Compatibility metadata
requires evidence for the claimed WordPress version. See the
[release checklist](.github/RELEASING.md) for release-specific requirements.
