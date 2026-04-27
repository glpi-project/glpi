# Security Policy

**⚠️ Never use public issues, pull requests, or discussions to report security problems. Vulnerabilities are not disclosed before a fix is released.**

## Reporting

Email **security@glpi-project.org**.

We do not accept GitHub Security Advisory direct submissions, and we will not create accounts on third-party platforms to receive reports. All information must be sent by email or, once an advisory is opened, posted directly in that advisory.

## Required information

Incomplete reports are returned once and **closed** if not completed within seven days.

1. Affected GLPI version (see below).
2. What the attacker can do and what privilege level they need.
3. Step-by-step reproduction from a clean install.
4. Working proof of concept.
5. Impact and preconditions.
6. AI disclosure: which tools, if any, you used to find or draft the report.

Group related findings into a single report. Multiple instances of the same vulnerability class — for example XSS across different fields, or missing capability checks on related endpoints — belong in one submission, not several.

If you have a GitHub account, include your username so we can add you as observer when the advisory is opened.

## Scope

**In scope:** GLPI core, official releases.

**Out of scope:** third-party plugins (report to plugin maintainers), the GLPI Inventory Agent, Teclib'-operated websites, self-modified installs, hosting providers, social engineering, hardening suggestions without a working exploit, scanner output without verification, out-of-date versions.

## Supported versions

| Version | Supported |
| ------- | --------- |
| 11.0.x  | ✔️        |
| 10.0.x  | ✔️        |
| < 10.0  | ❌        |

Only the latest patch release of each supported branch is in scope. Reports against older patch versions are closed without review — please upgrade and verify the issue still exists before reporting.

## Disclosure

Severity is assessed using CVSS v4.

Critical and High advisories are published one month after the fix release. All other advisories are published one week after the fix release.

CVE is reserved on report acceptance. You will be credited in the published advisory unless you request anonymity. Unilateral disclosure before a fix ships ends our engagement.

We do not offer monetary rewards.

## AI-assisted reports

AI tools are welcome as part of disciplined research — verify findings against a running install before submitting. Reports that reference functions, hooks, or behaviors that do not exist in the codebase will be closed as hallucinations, and repeat offenders will be deprioritized.