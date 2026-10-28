# Single-main pilot migration plan

This is a proposed operational sequence. Local preparation does not change GitHub
settings, existing PRs, refs, published releases, SVN, or the saved cloud environment.

## Verified snapshot: 2026-10-28

- Default branch: `develop`.
- `main`: `b37779238525bb37eb8062ffb3c2579b5d3f2800`, plugin version `1.9.0`.
- `develop`: `6ef56ef86830b10d2b03ddbe468b2096192e1d6d`, version `1.7.1`;
  158 commits behind `main`, zero unique commits.
- `fix/security-scan-findings`: `8fde501414b16c14adcf664c1a51addb72433a95`;
  one unique commit ahead of `main`. Preserve it for its separate security review.
- Open dependency PRs #143, #144, #145, #149, #152, #154, #163, #164, #165 and #166
  all target `develop`. #163 is `74ec60b1144eefad0934c2df1984ff48bf943fd4`;
  #164 is `02ed6f2667ecee1d10abd2c1ca2953fa6a33159f`.
- No repository Dependabot configuration exists in this `main` snapshot.
  Do not invent an update schedule or assume how existing alerts are configured.

## Ordered steps requiring separate authorization

1. Review the local pilot diff and evidence. Refresh remote SHAs and open PRs;
   stop on unexpected changes. Inspect current rulesets, branch protections,
   required checks, reviewer requirements, release holds and automation settings.
   Their complete remote configuration has not been verified by this local pilot.
2. Obtain approval to publish the pilot as a PR into `main`. Run hosted CI on its
   final head and retain all existing required checks. Approval to prepare this
   diff is not approval to push, create that PR or merge it.
3. Before cutover, have the owner approve any required protection/settings changes
   so `main` retains equivalent or stronger existing protections. Preserve existing
   check identities: `PHP and asset checks`, `WordPress integration tests`, and
   `Production package and Plugin Check`. Do not silently remove a failing gate or
   alter security access. If a deployment environment/reviewer rule is desired,
   review its exact configuration separately; this pilot creates none.
4. With separate merge and settings approval, land the reviewed pilot into `main`,
   require successful CI on the resulting commit, and change the default branch
   from `develop` to `main`. Pause release publication during the coordinated
   cutover: older refs retain their old release-triggered workflows. Verify manual
   workflow availability on the new default branch before authorizing any release.
5. Review each legacy dependency PR against current `main` individually. Compare
   resolved versions and lock changes; the old `develop` compatibility evidence
   does not certify `main` or a retargeted head. Ask for separate approval for each
   retarget/rebase, replacement PR, or closure and rerun checks on its final SHA.
   Do not bulk-merge or close duplicates on assumption. Existing PRs stay open
   and unchanged until that review.
6. Inspect Dependabot/security-update and contributor automation destinations after
   the default-branch change. Any schedule, target, permission or alert-setting
   change needs its own approval. Observe one normal PR into `main`, its CI and
   the post-merge push CI before calling the workflow pilot operational.
7. Retain `develop`, `master`, release branches and the security branch. This plan
   does not require deletion, resetting, force-pushing or history rewriting.
   Review any eventual archival separately. Run the deliberate release procedure
   only after explicit approval for an actual candidate and deployment.

If cutover verification fails, stop release activity and preserve evidence. Any
settings reversal or source revert needs approval; do not improvise a destructive
rollback or bypass a release gate. Local test success is not release readiness.
