# Approval Chain for GLPI 11

Sequential, multi-step approvals on tickets. Step N+1 only opens after step N is approved; a refusal stops the chain.

## Install
1. Copy this folder to `<glpi>/plugins/approvalchain` (folder name must be exactly `approvalchain`).
2. `php bin/console plugin:install approvalchain` then `php bin/console plugin:activate approvalchain`
   (or Setup > Plugins in the UI).

## Use
1. **Tools > Approval chains > +** create a chain, optionally pick a trigger ITIL category (empty = any).
2. Open the **Steps** tab: add steps in order, e.g.
   Validate student record -> Validate device status -> Department approval -> Deposit/fees check -> Final IT release.
   Each step has ONE approver: a user, or a group (any member may decide). Reorder with the arrows.
3. Set **Active = Yes**. (Needs >= 1 step and every step with an approver; an inactive example chain is seeded.)
4. New tickets matching entity + category start the chain automatically. You can also start one manually from the ticket's **Approval chain** tab.
5. Approvers decide on the ticket tab or on **Tools > Approval chains > inbox icon** ("My pending approvals"), which shows the ticket content so they need no ticket rights.

## Behaviour
- The running chain is a **snapshot**: editing a chain later does not alter tickets already in flight.
- Each decision is written to the ticket as a followup (public by default; set `Approval::FOLLOWUP_PRIVATE` to `true` for private).
- While a chain is in progress the ticket cannot be solved/closed (status change and solution are blocked). Refused/cancelled chains do not block.
- Next approver is e-mailed (direct send, best effort, only if GLPI mail notifications are enabled). Failures go to `files/_log/approvalchain.log`.
- Chain definitions need the core `config` right (READ to view, UPDATE to edit).

## Not tested against a live GLPI
This was syntax-linted (PHP 8.3) but not run inside a GLPI 11 instance. Try it on a staging server first.

## Ideas for next versions
Conditional/skippable steps, parallel approvals, escalation/timeouts, requester's supervisor as approver, native GLPI NotificationTarget templates, per-step profile rights.
