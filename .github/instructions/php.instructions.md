---
applyTo: "**/*.php"
---

# PHP / GLPI Rules

## Architecture

- Do not introduce service classes, repositories, DTOs, or dependency injection. GLPI uses static methods, CommonDBTM hooks, `global $DB`, and arrays. Follow existing patterns; never "improve" with external architecture patterns.
- Front controllers are thin routing layers only. Business logic, input validation, and data transformation belong in `prepareInputForAdd()` and `prepareInputForUpdate()`, not in front controllers or AJAX endpoints.

## Naming Conventions

- Variables and array keys: `snake_case`
- Methods: `camelCase`
- Classes: `PascalCase`
- Constants: `UPPER_SNAKE`
- Always reference item types using `ClassName::class`, never string literals such as `'Computer'`.
- Use `_s()` for all translatable strings; no hardcoded IDs or magic numbers.

## Rights

- Always use `$item->can($id, RIGHT)` for permission checks.
- Never use `canUpdateItem()`, `canViewItem()`, or `canDeleteItem()` directly — these methods skip global profile rights verification.

## Debugging

- Never use `var_dump()`, `print_r()`, or `echo` for debugging.
- Use `Toolbox::logDebug()`, `Toolbox::logInfo()`, or `Toolbox::logError()`.
