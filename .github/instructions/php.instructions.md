---
applyTo: "**/*.php"
---

# PHP / GLPI Rules

## Architecture

- Do not introduce service classes, repositories, DTOs, or dependency injection. GLPI uses static methods, CommonDBTM hooks, `global $DB`, and arrays. Follow existing patterns; never "improve" with external architecture patterns.
- Front controllers are thin routing layers only. Business logic, input validation, and data transformation belong in `prepareInputForAdd()` and `prepareInputForUpdate()`, not in front controllers or AJAX endpoints.
- Modern GLPI code lives in `src/` (namespaced as `Glpi\...`). Plugin code goes in `src/` namespaced as `GlpiPlugin\PluginName\...`. Do not add legacy files in `inc/`.
- Use Twig templates (`templates/`) for all HTML rendering. Never concatenate raw HTML strings in PHP.

## Naming Conventions

- Variables and array keys: `snake_case`
- Methods: `camelCase`
- Classes: `PascalCase`
- Constants: `UPPER_SNAKE`
- Always reference item types using `ClassName::class`, never string literals such as `'Computer'`.
- Use `_s()` for all translatable strings; no hardcoded IDs or magic numbers.

## Rights

- In front controllers, use `$item->check($id, RIGHT, $input)` — it throws `AccessDeniedHttpException` automatically on failure.
- Use `Session::haveRight($module, $right)` only for module-level (global) checks outside of item context.
- Use the standard right constants: `READ`, `UPDATE`, `CREATE`, `DELETE`, `PURGE`, `READNOTE`, `UPDATENOTE`, `UNLOCK`, `READ_ASSIGNED`, `UPDATE_ASSIGNED`, `READ_OWNED`, `UPDATE_OWNED`.

## Database

- Always use `$DB->request([...])` with array-based criteria. Never concatenate raw SQL strings or use `$DB->query()` directly.
- Use `QueryParam` for parameterized values, `QueryExpression` for raw SQL fragments, `QuerySubQuery` for sub-queries.
- Use `getTable()` / `static::getTable()` to reference table names — never hardcode `glpi_*` table names.
- Iterate results with `foreach ($DB->request([...]) as $row)` — `DBmysqlIterator` implements `SeekableIterator`.
- Use `$DB->insert()`, `$DB->update()`, `$DB->delete()` for simple DML; use `$DB->request()` for SELECT.
- Wrap multi-step writes in transactions: `$DB->beginTransaction()` / `$DB->commit()` / `$DB->rollBack()`.

## Debugging

- Never use `var_dump()`, `print_r()`, or `echo` for debugging.
- Use `Toolbox::logDebug()`, `Toolbox::logInfo()`, or `Toolbox::logError()`.

## CommonDBTM Lifecycle Hooks

- `prepareInputForAdd(array $input): array|false` — Validate and transform input before insert. Return `false` to abort.
- `prepareInputForUpdate(array $input): array|false` — Validate and transform input before update. Return `false` to abort.
- `post_addItem()` — Side effects after insert (notifications, related records). Access new data via `$this->fields`.
- `post_updateItem(bool $history = true)` — Side effects after update. Use `$this->updates` (changed field keys) and `$this->oldvalues` (previous values).
- `cleanDBonPurge()` — Delete related records before permanent deletion. Always call `parent::cleanDBonPurge()` if overriding.
- `post_getFromDB()` — Enrich `$this->fields` after a DB read. Keep lightweight; do not run extra queries unless necessary.
- Never override `add()`, `update()`, or `delete()` directly — use the hooks above.

## Front Controllers

- Always start with `require_once(__DIR__ . '/_check_webserver_config.php')`.
- Check session access early: `Session::checkCentralAccess()` or `Session::checkLoginUser()`.
- Route on `$_POST` keys (`isset($_POST['add'])`, `isset($_POST['update'])`, `isset($_POST['purge'])`).
- After every write action, redirect with `Html::redirect($url)` or `Html::back()`. Never render HTML in a POST handler.
- If no action matches, throw `new BadRequestHttpException()` — never silently return.

## AJAX Endpoints

- Output JSON directly with `echo json_encode($data)`. Do not use `Html::header()` / `Html::footer()`.
- Always check session and rights before processing: `Session::checkLoginUser()` or `$item->check()`.
- Return structured arrays: `['success' => true, 'data' => [...]]` for success, `['success' => false, 'error' => '...']` for failure.
- Never echo arbitrary user input — always sanitize or encode before output.

## Translations

- `__($str)` — Translate, returns raw string (use only when passing to another escaping layer).
- `__s($str)` — Translate + `htmlspecialchars`. Use for all text output in PHP.
- `_n($sing, $plural, $nb)` — Pluralized translation (raw).
- `_sn($sing, $plural, $nb)` — Pluralized + escaped. Preferred in HTML output.
- `_x($ctx, $str)` — Contextualized translation (raw). Use when the same word has different meanings.
- `_sx($ctx, $str)` — Contextualized + escaped.
- Always pass the plugin domain as second argument in plugin code: `__s('My label', 'mypluginname')`.
- Never use hardcoded user-facing strings without a translation function.

## Error Handling

- Use HTTP exception classes for flow control in controllers:
  - `throw new AccessDeniedHttpException()` — permission denied
  - `throw new NotFoundHttpException()` — item not found
  - `throw new BadRequestHttpException()` — invalid request
- Use `Session::addMessageAfterRedirect(__s('Message'), false, ERROR)` to show feedback after redirect.
- Message severity constants: `INFO` (0), `ERROR` (1), `WARNING` (2).
- Never swallow exceptions silently. Log unexpected errors and re-throw or redirect with a message.

## Security

### CSRF

- Never write or render a `<form>` without including the CSRF token: use `{{ csrf_token() }}` in Twig or `Html::getCsrfTokenInput()` in PHP.
- GLPI validates CSRF automatically via `CheckCsrfListener` on all state-changing requests (POST/PUT/PATCH/DELETE). Do not bypass or disable this listener.
- For AJAX calls, send the token in the `X-Glpi-Csrf-Token` HTTP header, not in the body.
- CSRF tokens are single-use by default; pass `preserve_token: true` only when a single form can trigger multiple AJAX requests without reload.
- Never expose CSRF tokens in URLs, logs, or API responses.

### XSS — Output Encoding

- Every variable echoed in PHP must be wrapped in `htmlescape()`. Never use `echo $user_input` directly.
- In Twig templates, rely on auto-escaping (`{{ var }}`). Use `{{ var|raw }}` only for values that have been explicitly sanitized server-side (e.g., rich text that went through `RichText::getSafeHtml()`).
- Never mark user-supplied content as `|raw` in Twig.
- Do not use `Sanitizer::sanitize()` on new code — it is deprecated. Sanitization happens at the output layer (encoding), not at the input layer.
- Prefer `__s()` / `_sn()` / `_sx()` over `__()` for any string that goes into HTML output.

### SQL Injection

- The only acceptable way to query the database is `$DB->request([...])` with an array-based criteria map. Every value in the criteria array is automatically parameterized.
- When a raw SQL fragment is unavoidable, wrap it in `new QueryExpression(...)` and bind user values via `new QueryParam(...)` — never interpolate `$_GET` / `$_POST` directly into a `QueryExpression`.
- Never use `$DB->query()`, `$DB->queryOrDie()`, or `sprintf` to build SQL strings.

### IDOR — Insecure Direct Object Reference

- Before exposing an item ID to the frontend, generate an IDOR token: `Session::getNewIDORToken(MyItem::class)` in PHP or `{{ idor_token('MyItem') }}` in Twig.
- Validate the token server-side before using the posted ID: `Session::validateIDOR($data)`. If validation fails, throw `new BadRequestHttpException()`.
- Never trust an item ID from `$_GET` / `$_POST` without either an IDOR token or a full `$item->can($id, READ)` check.

### Privilege Escalation & Entity Isolation

- Every database query that returns user data must be scoped to the current user's entities: use `getMyEntities()` or `'entities_id' => $_SESSION['glpiactive_entity']` in criteria.
- When an item has an `entities_id` column, always check `$item->can($id, $right)` — it enforces entity belonging automatically.
- Never accept `entities_id` from user input without verifying the user has access to that entity: `Session::haveAccessToEntity($entities_id)`.

### Sensitive Data & Credential Handling

- Store all secrets (API keys, SMTP passwords, LDAP bind passwords, OAuth secrets) encrypted via `GLPIKey::encrypt()`. Never store them in plaintext in the database.
- Retrieve encrypted secrets with `GLPIKey::decrypt()` only at the point of use — do not carry decrypted values in session or cache.
- Declare sensitive fields in `$undisclosedFields` static property so they are stripped from API responses automatically.
- Never log passwords, tokens, or personal data. Redact before passing to `Toolbox::logError()` / `logDebug()`.

### File Upload

- Validate MIME type and extension against `DocumentType::getUploadableFilePattern()`. Never accept a file based solely on its declared Content-Type.
- Store uploaded files under `GLPI_DOC_DIR` (outside the web root). Never store uploads inside `public/` or any web-accessible directory.
- Derive the storage filename server-side (use the SHA1 hash). Never use the original filename from `$_FILES` as the storage path — it enables path traversal.
- Reject filenames containing `..`, `/`, or `\` before any file operation.
- Enforce `$CFG_GLPI['document_max_size']` limits in upload handlers.

### Session Security

- Never regenerate the session ID manually — it is handled by `Session::init()` after authentication. Do not call `session_regenerate_id()` elsewhere.
- Session cookies are configured with `HttpOnly`, `Secure`, and `SameSite=Lax` by the framework. Do not override `session.cookie_*` ini settings in application code.
- After privilege escalation or profile change, call `Session::changeActiveEntities()` to recompute the entity tree — never reuse a stale entity list.

### Path Traversal

- Resolve all file paths with `realpath()` and verify the result starts with the expected base directory (`GLPI_DOC_DIR`, `GLPI_TMP_DIR`, etc.) before performing any file operation.
- Use `basename()` to extract filenames from user-supplied paths. Never pass `$_GET`/`$_POST` values directly to `file_get_contents()`, `unlink()`, `fopen()`, or `include`.
- `GLPI_ROOT` is the only safe root for PHP includes; never include files from paths derived from user input.

### API Security

- Declare the required OAuth scope in every new API endpoint using the `#[SecurityStrategy]` attribute or by registering the route with the appropriate middleware.
- Validate IP restrictions via `IPRestrictionRequestMiddleware` — do not roll your own IP check.
- Generate API client secrets with `bin2hex(random_bytes(...))` and store them encrypted. Never generate tokens with `rand()`, `uniqid()`, or `mt_rand()`.
- Never return internal stack traces, SQL queries, or filesystem paths in API error responses. Use generic messages and log details server-side.

### General Rules

- Never disable or skip security listeners with `NO_CHECK` strategy on endpoints that can modify data or read personal information.
- Always throw an HTTP exception (`AccessDeniedHttpException`, `NotFoundHttpException`, `BadRequestHttpException`) rather than returning an empty response or `false` for security violations.
- Never trust the `X-Forwarded-For` header for access control without validating that the request comes through a known proxy.
- Do not expose PHP version, GLPI version, or server software in custom headers or error pages.
