# Contributing to GLPI

Thank you for considering a contribution. Please read this document before opening an issue or pull request.

## Scope

This repository covers **GLPI core only**. Third-party plugins are owned by their authors — report plugin issues and requests to the relevant plugin repository.

Issues, pull requests, commit messages, and code comments must be in **English**.

Issues are handled on a best-effort basis. For guaranteed support, see [GLPI Network](https://glpi-network.com) or our [official partners](https://glpi-project.org/partners/).

> [!CAUTION]
> ⚠️ For security vulnerabilities, see [SECURITY.md](SECURITY.md). Do not open public issues, pull requests, or discussions for security problems.

## Where things go

- **Bug report** — open an issue using the bug template. Reproducibility is the minimum bar; reports we cannot reproduce will be closed.
- **Feature proposal** — open an issue using the "contribution request" template *before* writing code. We will tell you whether the change belongs in core, in a plugin, or not at all.
- **Idea without implementation** — use [suggest.glpi-project.org](https://suggest.glpi-project.org) and upvote existing entries. Issues opened as "please add X" without a discussion or PR will be redirected there.
- **Usage questions** — use the [forum](https://forum.glpi-project.org). Questions opened as issues will be closed.

## Plugin-first

If a feature can live as a plugin, it should. Core accepts changes that touch shared infrastructure (data model, framework, security, accessibility) or that the majority of users need. Niche features become plugins — we will help you scope the API surface you need.

## Pull requests

Trivial fixes (typos, broken links, small documentation corrections) may be submitted as PRs directly.

For all other changes, a pull request is reviewed only after these conditions are met:

1. A linked issue exists and the change has been agreed.
2. Tests cover the new behavior or the bug being fixed. PRs without tests are closed.
3. CI passes — static analysis, code style, and the test suites.
4. The PR description discloses whether AI tools were used and how.

Keep your branch up to date with the target branch. We rebase and squash on merge.

Coding standards and the local CI workflow are documented in the [GLPI developer documentation](https://glpi-developer-documentation.readthedocs.io).

## AI-assisted contributions

AI tools are welcome when used by someone who understands the change. Disclose tool use in the PR description.

Commits must be authored under your own name and email — not by an AI agent or LLM provider account. PRs containing commits owned by an AI service will be returned for re-authoring.

Contributions that reference functions, hooks, or APIs that do not exist in the codebase will be closed as hallucinations. Repeat offenders will be banned.