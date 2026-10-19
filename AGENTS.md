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
