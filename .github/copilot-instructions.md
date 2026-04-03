# GLPI — Copilot Instructions

## Project Overview

GLPI is a Free Asset and IT Management Software written in PHP 8.3+, Symfony 6.4, Vue 3 for frontend, and Playwright for E2E testing. Use the GLPI framework whenever possible.

## Repository Structure

- `src/Glpi/` — modern PHP code (controllers, API, assets, helpdesk)
- `inc/` — legacy code being migrated
- `templates/` — Twig templates
- `js/` — JavaScript/Vue source
- `tests/functional/` — PHPUnit tests
- `tests/e2e/` — Playwright E2E tests

## Build & Test Commands

All commands run inside Docker containers via `make`:

```bash
make vendor                                         # Install PHP dependencies
make phpunit c='tests/functional/Glpi/MyTest.php'  # Run PHPUnit tests
make playwright c=tests/e2e/specs/<file>            # Run E2E tests
make lint                                           # All linters
make phpcsfixer-fix                                 # Fix PHP coding standards
```

**CRITICAL**: Never run `npx`, `node`, `npm`, `eslint`, or `tsc` directly — all commands must go through `make` targets (Docker).

## General Rules

- Follow PER Coding Style 3.0; use PHP 8.3+ features only; no deprecated code; no GLPI APIs older than v11.0.
- Follow the MVC pattern — do not create `/front/` files; always use controllers and routes.
- Never output raw HTML with `echo` — always use Twig templates.
- Never execute raw SQL — always use GLPI's ORM and database abstraction layer.
- When adding a library, prefer already-imported ones; new dependencies must be GPLv3+ compatible.
- Always ensure generated code is secure and free from vulnerabilities.
- Do not ask clarification questions unless a real technical choice must be made.
- Do not generate tests unless requested.
- Never create `.md` or `.txt` files to explain changes; never explain what you did.
- Do not add unnecessary comments or TODO notes.
