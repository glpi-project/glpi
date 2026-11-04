---
name: glpi-security-patch
description: Help reporters test an optional patch or maintainers reproduce, fix, and independently verify a GLPI security bug using external patching and verification skills.
---

# GLPI security patch

## Setup

Install `patching-bugs` and `regression-hunt` from the [trailofbits/skills marketplace](https://github.com/trailofbits/skills) using your agent's supported method, including their stage skills and supporting resources. Reuse existing installations and record the versions or source revisions used. Reporters can submit a complete report without using this skill.

For all testing and validation, freshly pull the latest available [official Docker nightly image](https://github.com/glpi-project/docker-images/pkgs/container/glpi/versions) for the affected supported branch (`glpi/glpi:<major>.<minor>-nightly`). Record its tag and digest and the GLPI source revision. Keep the image digest fixed while testing the external workflow's pinned source revisions.

## Workflow

Reporters may use this skill to prepare and test an optional patch before submitting a report. Maintainers use it to investigate reported bugs and prepare verified fixes. Both roles follow the same complete workflow; a reporter-supplied patch still requires maintainer review.

Use the external [patching-bugs](https://github.com/trailofbits/skills/tree/master/plugins/patching-bugs) skill. Read its full SKILL.md and required references. Follow its complete workflow, including its stage skills, independent agents, record validators, proof, regression campaign, and report generation. Do not implement an abbreviated local procedure or skip a stage because a report already claims confirmation.

Require the complete skill package and its [regression-hunt](https://github.com/trailofbits/skills/tree/master/plugins/regression-hunt) dependency, including their scripts, schemas, and references. If a dependency or required execution capability is unavailable, report the blocker and stop; see [setup](#setup).

Pass [AGENTS.md](../../../AGENTS.md), other applicable AGENTS.md files, [SECURITY.md](../../../SECURITY.md), [THREAT_MODEL.md](../../../THREAT_MODEL.md), and [coding/test instructions](../../../.github/copilot-instructions.md) as project guidance through the skill's handoffs. Supply the original report, target checkout, declared repair scope, and private evidence location. Preserve the external skill's provenance and guidance requirements.

Keep execution in isolated local Docker environments; inspect PoCs before running them. Keep unreleased reports, logs, test credentials, and patches private. Do not push, publish, send email, file issues, open pull requests, or calculate severity scores. A failed setup or incomplete proof cannot establish a passing result.

For an existing run, follow the external skill's resume requirements. For explicitly requested standalone proof, use its `prove-patch` skill in a fresh verifier session with its complete required inputs and records; do not invent a GLPI verification mode.

Return the external skill's reports, records, automated verdict, and local patch commit when present. Add at most **150 words** explaining the GLPI invariant, outcome, evidence locations, and remaining gaps. This limit applies only to the cover note; retain all evidence. A maintainer decides acceptance and release.
