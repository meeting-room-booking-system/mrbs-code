# AGENTS.md

## NEWS update requirements

When updating the NEWS file, follow the existing project convention rather than writing a modern release summary.

### Purpose

NEWS is effectively a curated, de-duped recreation of the git history for a release. It is not a marketing changelog and not a condensed feature list.

- One bullet point per significant change.
- Minor cleanup, refactoring, comment-only edits, and other non-user-visible commits are usually omitted.
- Similar or closely related commits should be merged into a single bullet when they describe the same issue or improvement.
- Keep the wording close to the actual git commit narrative and issue/PR descriptions.
- Include GitHub issue or PR references when they are provided in the commit log or developer notes.

### Format

Use the exact style already used in the file:

- Header line: `Version x.y.z (Day DDth Month YYYY):`
- Follow the legend at the top of NEWS:
  - `[+]` Added feature
  - `[*]` Improved/changed feature
  - `[-]` Bug fixed (we hope)
- Keep each bullet on a single logical item in the same format as neighbouring entries.
- Wrap long lines in the same visual style used by existing entries, with continuation lines aligned to the bullet indentation.

### Examples of expected behaviour

- Prefer commit-style wording such as: `Fix bug causing ...` or `Improve performance when using ...`.
- Use issue references like: `See GitHub Issue #4041.` or `See GitHub PR #4060.`
- When multiple related items are in the same area, list them separately rather than summarising them into one generic line.
- Do not replace the release notes with a short marketing summary.

### Examples of what to avoid

- Avoid generic headings like "performance and bug fixes".
- Avoid a short narrative summary of the whole release.
- Avoid omitting important individual fixes that are already identified by the developer or the git history.
- Avoid adding new categories or a different bullet style.

### Release-note source

Use the git history and the developer-provided release notes as the source of truth. When a developer list is available, treat it as the authoritative list of individual changes to include, while still preserving the conventional NEWS wording and grouping.

### Reading commit messages

Commit messages may follow the GitHub convention: the first line is a title, and any following lines are details, often as bullets after a blank line. Not every commit will use this format, so handle both forms:

- Use the title as the basis for the NEWS bullet, rewritten into the NEWS style above if needed (e.g. `Fix LDAP injection in group filter` becomes `Fix LDAP injection ...`).
- If the body has bullets, each bullet describing a distinct user-visible change becomes its own NEWS bullet, and bullets that are only internal detail are omitted.
- If a commit has no body, use the title alone.
- Keep any reporter credit and advisory or issue IDs given in the commit message, as they are part of the NEWS entry.
- Write each NEWS bullet as a complete, readable sentence fragment that a site administrator can understand without seeing the commit. Do not paste raw commit text, such as stray line breaks, bullet markers or internal file names, into NEWS.

## Release process

Releases are made from `main`, using the release tools in the `mrbs-tools` repository that sits alongside this one.

1. Merge the release changes into `main`.
2. Update NEWS for the release, following the NEWS guidance above.
3. Rebuild `ChangeLog` from git just before tagging, using the `mrbs_git_cl` script in `mrbs-tools/changelog/`. Do not hand-edit it.
4. Tag with `tagrelease.pl X.Y.Z` from `mrbs-tools`. This creates a signed tag and lists the outgoing commits.
5. Push `main` and the new tag to `origin` together.
