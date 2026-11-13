# Security Policy

> [!IMPORTANT]
> We receive a lot of security reports and we need to validate each one - please be patient ;)
> Also, please provide us all information, scripts, etc so we can easily reproduce and test.
> 
> Thank you!

## Reporting

Email **security@glpi-project.org**.

> [!CAUTION]
> Never use public issues, pull requests, or discussions to report security problems. Vulnerabilities are not disclosed before a fix is released.

We do not accept GitHub Security Advisory direct submissions, and we will not create accounts on third-party platforms to receive reports. All information must be sent by email or, once an advisory is opened, posted directly in that advisory.

## One email per vulnerability

Send a separate email for each unrelated vulnerability. Do not bundle unrelated issues into a single thread, and do not reply to an existing report thread to raise a new, unrelated one. This keeps triage, severity assessment, and advisory tracking clean — one report, one thread, one advisory.

This is distinct from the grouping rule below: instances sharing the *same root cause and repair* stay in one report; unrelated vulnerabilities each get their own.

## Required information

Incomplete reports are returned once with a consolidated request for missing evidence and **closed** if not completed within seven days. Reports may be reopened when completed; missing evidence does not make a finding invalid.

Use the following structure for your reports:

- Setup
    - Affected GLPI version (see below), Docker image tag and digest.
    - Preconditions and privileges required for the attacker.
    - Settings changed from defaults, with original/new values and reasons (including profile rights, entity assignments/recursion, and PHP/web-server overrides).
- Description
    - Description of the root cause of the vulnerability, with references to relevant code. Use GitHub permalinks whenever possible.
    - Impact and the security boundary crossed.
- Exploit Scenario
    - Concise, human-readable steps to reproduce the issue from a clean install using the current [official Docker setup](https://github.com/glpi-project/docker-images). Use a freshly pulled image of the latest patch release of the affected supported branch. Focus on reproducing the issue rather than explaining the vulnerability, and perform the steps manually before submitting the report.
    - Working proof of concept (Python, PHP, ...) to validate and reproduce the issue; and then make sure it gets fixed. Attach it as a file (script, HTTP request collection, etc.) rather than pasting source code inline in the email body to keep reports readable and reproducible, and avoids mangling by mail clients (indentation, quoting, encoding).
- Recommendation
    - Suggest how to address the issue.
    - Do not attach a patch to the report. The GLPI team will offer you the opportunity to review its patch before publication.

Omit generic background, repeated impact claims, and CVSS scores.

If you have a GitHub account, include your username so we can add you as observer when the advisory is opened.

## Scope

**In scope:** GLPI core, official releases.

We only accept issues with demonstrated impact on confidentiality or integrity. For bugs only impacting availability and hardening suggestions (see our [threat model](THREAT_MODEL.md)), prefer opening [an issue](https://github.com/glpi-project/glpi/issues) or a pull request.

**Out of scope:** third-party plugins (report to plugin maintainers), the GLPI Inventory Agent, Teclib'-operated websites, self-modified installs, hosting providers, standalone social engineering, hardening suggestions without a working exploit, scanner output without verification, out-of-date versions.

## Supported versions

| Version | Supported |
| ------- | --------- |
| 12.0.x  | ✔️        |
| 11.0.x  | ✔️        |
| < 11.0  | ❌        |

Only the latest patch release of each supported branch is in scope. Reports against older patch versions are closed without review — please upgrade and verify the issue still exists before reporting.

## Scoring 

GLPI assesses severity using CVSS v4 during triage. Do not score your submissions.

During triage, GLPI uses the following convention for the **Privileges Required (PR)** metric:

| Attacker privilege level                                          | PR   |
|-------------------------------------------------------------------|------|
| Unauthenticated                                                   | None |
| Profile using the simplified interface (e.g. *Self-Service*)      | Low  |
| Profile using the standard interface (e.g. *Technician*, *Admin*) | High |

Reports scoring an attacker with a standard-interface profile as PR:L will be re-scored accordingly.

## Disclosure

Critical and High advisories are published one month after the fix release; other advisories are published one week after the fix release. You will receive credit on the advisory unless you request anonymity. Unilateral disclosure before a fix ships ends our engagement.

We do not offer monetary rewards.

While all vulnerabilities will receive fixes and a GHSA identifier, GLPI requests CVE identifiers only for confirmed **High and Critical** vulnerabilities. Please reference lower-severity ones with their GHSA ID.

## AI-assisted reports

AI tools are welcome as part of disciplined research — verify findings against a running install before submitting. Reports that reference functions, hooks, or behaviors that do not exist in the codebase will be closed as hallucinations, and repeat offenders will be deprioritized.
