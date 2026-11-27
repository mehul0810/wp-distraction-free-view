# Deliberate releases

This workflow separates integration, GitHub release publication, and WordPress.org
deployment. Owner approval remains required; the confirmation input records an
operator's intent and does not implement an independent owner-review requirement.
Preserve all existing repository protections, approvals, security access and holds.

1. Finish reviewed work on `main`. If needed, prepare version metadata, changelog,
   readme and built assets on a short-lived `release/<version>` PR into `main`.
2. Validate the exact candidate: native PHP/JS/CSS checks, unit tests, WordPress
   integration and multisite tests, applicable frontend/settings/browser checks,
   production package shape and Plugin Check. Record actual versions and results.
   Do not raise `Tested up to` without compatibility evidence or waive a blocked gate.
3. Obtain owner approval for the candidate and release actions. After the release
   PR is merged, verify the resulting `main` SHA and its completed successful `CI`
   push run. Recheck metadata on that commit; PR merge-ref CI is not this evidence.
4. Create a stable `X.Y.Z` (or `vX.Y.Z`) tag from that validated `main` commit, then
   publish its stable GitHub release. These are separately authorized remote writes.
   Publication alone does not deploy under the new stable workflow. Prereleases
   continue to build/upload artifacts through `prerelease.yml` and never deploy.
5. With explicit deployment approval, run **Deploy to WordPress.org** from `main`.
   Supply the existing tag and type `deploy-to-wordpress-org` as confirmation.
   The validation job rejects other dispatch refs, non-stable tags, tags outside
   `main`, missing/draft/prerelease GitHub releases, mismatched versions, and missing,
   pending or unsuccessful latest CI for the tag's exact commit.
6. The deploy job checks out the validated immutable SHA, installs production
   dependencies, builds assets, checks package shape and runs the existing 10up
   action with the validated numeric version. That explicit `VERSION` is required
   because manual dispatch has a branch ref, not a tag ref. Existing SVN secret
   names and slug remain unchanged. The action's ZIP is uploaded to the validated
   GitHub release using `--clobber` for upload replacement. This does not guarantee
   content-correct idempotence.
7. Verify the workflow, SVN release and downloadable artifact before announcing
   success. A failed upload after SVN deployment is a partial outcome. Before any
   approved retry, inspect SVN trunk and the requested `tags/<version>` and verify
   the artifact's version and contents against the intended release, including
   the approved package manifest. Stop if the state is uncertain or mismatched.
   Do not rerun deployment for a stale published version after SVN trunk advances.
   Do not retag, force-push or roll back SVN automatically. Concurrency serializes
   deployment runs without cancelling an in-flight deployment; avoid queuing
   multiple releases.

The existing 10up action generates the ZIP from current SVN trunk when the requested
SVN tag already exists, then exits without redeploying. An old-version rerun can
therefore replace that version's GitHub asset with newer trunk contents. This is
pre-existing upstream behavior, not a defect introduced or fixed by this pilot.
The workflow does not enforce these SVN/artifact retry checks; the release operator
must complete them before any approved retry and verify the downloaded artifact
afterward. See the [upstream implementation](https://github.com/10up/action-wordpress-plugin-deploy/blob/stable/deploy.sh#L87-L105).

The guard reads the `ci.yml` runs endpoint filtered to the exact SHA, `main` and
`push`, checks the workflow identity, and uses the latest returned run/attempt.
It does not grant approval, certify manual checks, prevent an approved downgrade,
or replace repository protection. Keep the candidate tag unchanged throughout
release review and deployment.

Manual dispatch requires the workflow on the repository's default branch. Complete
the separately approved [default-branch migration](MAIN-MIGRATION.md) before using
this procedure. Never dispatch the old workflow as a migration shortcut.

References: [GitHub manual workflows](https://docs.github.com/en/actions/how-tos/manage-workflow-runs/manually-run-a-workflow),
[workflow runs API](https://docs.github.com/en/rest/actions/workflow-runs#list-workflow-runs-for-a-workflow),
[10up deploy configuration](https://github.com/10up/action-wordpress-plugin-deploy#configuration).
