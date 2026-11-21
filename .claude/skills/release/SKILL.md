---
name: release
description: Use when a maintainer asks to release, tag, or publish a new version of mcamara/laravel-localization, to write the release notes for its GitHub release page, or to check that Packagist shows a new version.
---

# Release a New Version

## Overview

A release is a `vMAJOR.MINOR.PATCH` tag on a `master` commit, plus a GitHub release that holds the release notes.
Packagist reads new tags through the repository webhook, so a pushed tag updates Packagist without a token.

## Rules

- Release only a `master` commit that passed CI.
- Get an explicit yes from the maintainer for the version and the release notes before you create the tag.
- Never move, delete, or force-push a tag. The "Release tags" ruleset blocks these actions.
- Never use a Packagist API token.

## Choose the Version

| Change since the last tag | Part to increase |
| --- | --- |
| A change to a public method or a config key breaks existing callers | MAJOR |
| A new feature, or the package adds or drops support for a Laravel or PHP version | MINOR |
| Only fixes, docs, CI, or tests | PATCH |

## Procedure

1. Run `git fetch origin --tags`.
2. Set `PREV` to the output of `git describe --tags --abbrev=0 origin/master`.
3. Set `SHA` to the output of `git rev-parse origin/master`.
4. Run `gh run list --branch master --workflow run-tests.yml --limit 1 --json headSha,conclusion`.
5. Stop if `headSha` is not `SHA` or `conclusion` is not `success`. Tell the maintainer why.
6. Run `git log --first-parent --format='%s' "$PREV"..origin/master` to list the changes.
7. Collect the pull request numbers (`#123`) from those subjects.
8. Run `gh pr view NUMBER --json title,author,body,url` for each pull request number.
9. Choose the version with the table above. Set `TAG` to it, for example `v2.5.0`.
10. If the supported Laravel or PHP versions changed, read the compatibility table in `README.md`.
11. Stop if the table has no row for the new minor version. That change needs a pull request first.
12. Write the release notes to a file outside the repository. Use the format below.
13. Show `TAG`, `SHA`, and the release notes to the maintainer. Wait for an explicit yes.
14. Run `git tag -a "$TAG" -m "$TAG" "$SHA"`.
15. Run `git push origin "$TAG"`. If a ruleset rejects the push, stop. Only the ruleset's bypass actors can create tags.
16. Run `gh release create "$TAG" --verify-tag --title "$TAG" --notes-file NOTES_FILE`.
17. Add `--latest=false` to that command when `TAG` is lower than the current latest release.
18. Check Packagist. Use the section below.

## Release Notes Format

The release notes contain these parts, in this order. Omit a section that has no items.

1. `## Upgrade notes`: each Trade-Offs item, each dropped Laravel or PHP version, and each change that can affect an app.
2. `## Security`
3. `## Fixes`
4. `## Other changes`: docs, CI, and tests.
5. The line `**Full Changelog**: https://github.com/mcamara/laravel-localization/compare/PREV...TAG`.

Write each item as one sentence about the effect on apps, followed by `(#NUMBER)`.
Add `by @LOGIN` when the author is not a maintainer of this repository.

## Check Packagist

Run this command:

```bash
curl -fsS https://repo.packagist.org/p2/mcamara/laravel-localization.json \
    | jq -r '.packages["mcamara/laravel-localization"][].version' | grep -Fx "$TAG"
```

If the command prints nothing, wait one minute and run it again. Stop after five tries.
Then ask the maintainer to click **Update** on https://packagist.org/packages/mcamara/laravel-localization.
Also ask the maintainer to check the recent deliveries of the packagist.org webhook in **Settings › Webhooks**.

## Common Mistakes

| Mistake | Effect | Prevention |
| --- | --- | --- |
| Tag a commit before CI passes | Packagist serves an untested version | Steps 4 and 5 |
| Run `gh release create` without `--verify-tag` | GitHub creates the tag at a different commit | Always pass `--verify-tag` |
| Move a tag after the push | Apps that installed the version have different code | Release a new PATCH version |
| Copy commit subjects into the release notes | The release notes describe code, not the effect on apps | Read each pull request description |
