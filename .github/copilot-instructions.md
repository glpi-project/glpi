Follow GLPI’s latest coding standards and best practices: naming, indentation, comments, and PER Coding Style 3.0 compliance.
Use the GLPI framework whenever possible.
Use the snake_case variable naming convention.
Do not use deprecated code.
Do not use PHP features older than version 8.3.
Do not use GLPI code or APIs older than version 11.0.
Never create .md or .txt files to explain changes.
Never explain what you did.
Do not add unnecessary comments or TODO notes.
Follow the MVC pattern, routing, and controllers wherever possible.
Do not create /front/ files — always use controllers and routes if possible.
Never output raw HTML with echo; always use Twig templates.
Never execute raw SQL — always use GLPI’s ORM and database abstraction layer.
Do not ask clarification questions, except when a real choice between two technical solutions must be made.
Do not generate tests unless requested.
When generating code, always ensure it is secure and free from vulnerabilities.
When importing libraries or packages, prefer already imported ones; if using new ones, they must be compatible with GLPI GPLv3+ License.
Do not introduce service classes, repositories, DTOs, or dependency injection. GLPI uses static methods, CommonDBTM hooks, `global $DB`, and arrays. Follow existing patterns; never "improve" with external architecture patterns.
Always reference item types using `ClassName::class`, never string literals such as `'Computer'`.
Always use `$item->can($id, RIGHT)` for permission checks — never `canUpdateItem()`, `canViewItem()`, or `canDeleteItem()` directly. These methods skip global profile rights verification.
Front controllers are thin routing layers only. Business logic, input validation, and data transformation belong in `prepareInputForAdd()` and `prepareInputForUpdate()`, not in front controllers or AJAX endpoints.
Never use `var_dump()`, `print_r()`, or `echo` for debugging. Use `Toolbox::logDebug()`, `Toolbox::logInfo()`, or `Toolbox::logError()`.

## End-to-end tests (Playwright)

Before writing or modifying any e2e test, read existing tests in `tests/e2e/specs/` and page objects in `tests/e2e/pages/` to understand established patterns and conventions.

Update these instructions if you learn new information.

### Page Object Model

Always use the Page Object Model pattern. Locators and reusable interactions must live in page classes under `tests/e2e/pages/`, not in spec files.

- Every page class extends `GlpiPage` (defined in `tests/e2e/pages/GlpiPage.ts`), which provides helper methods such as `getButton()`, `getLink()`, `getCheckbox()`, `getTextbox()`, `getTab()`, `getRegion()`, `getHeading()`, `getRadio()`, `getSpinButton()`, `getDropdownByLabel()`, `getRichTextByLabel()`, and common actions like `doSetDropdownValue()`, `doAddNote()`, `doLogout()`, etc.
- Declare locators as public readonly properties initialized in the constructor.
- Expose user-facing actions as `async do*()` methods on the page object.
- Spec files should only contain test logic; all element access goes through page objects.

### Selectors

Use Playwright's semantic locators exclusively — prefer `getByRole()`, `getByLabel()`, `getByTestId()`, `getByText()`, `getByTitle()`, `getByAltText()`, and the helper methods inherited from `GlpiPage`.

- **Never use raw CSS or XPath selectors** (`page.locator(...)`) unless there is absolutely no semantic alternative. When a raw locator is unavoidable, add `// eslint-disable-next-line playwright/no-raw-locators` on the line above.
- Before adding a raw locator, **you MUST first attempt all of the following alternatives** in order:
  1. Use existing semantic attributes (`role`, `aria-label`, `title`, `alt`, `data-testid`).
  2. Add an `aria-label` attribute so the element can be targeted with `getByRole()`.
  3. Add a `data-testid` attribute to the element in the Twig template, PHP source, or JavaScript that generates it.
  4. Only after exhausting options 1–3 may you use a raw locator with the eslint-disable comment.
- Targeting elements by `data-glpi-*` attributes with `page.locator('[data-glpi-...]')` **is a raw locator** and is **not allowed**. Add a `data-testid` attribute instead and use `getByTestId()`.


### Tab navigation

Use the `forcetab` URL parameter to navigate directly to a specific tab instead of clicking tab elements:

```typescript
await page.goto(`/front/computer.form.php?id=${id}&forcetab=ItemVirtualMachine$1`);
```

To find forcetab IDs, check `getTabNameForItem()` and `defineTabs()` in the relevant PHP class. The default form tab uses `ClassName$main`; numbered tabs follow the keys defined in `getTabNameForItem()` (e.g., `User$1`, `Glpi\Asset\AssetDefinition$2`).

Page objects should accept an optional `tab` parameter in navigation methods:

```typescript
public async goto(id: number, tab?: string): Promise<void> {
    let url = `/front/computer.form.php?id=${id}`;
    if (tab) {
        url += `&forcetab=${tab}`;
    }
    await this.page.goto(url);
}
```

Only click tabs directly when you need to set up `page.route()` intercepts before the tab content loads.

### Accessibility testing

Use `@axe-core/playwright` (`AxeBuilder`) for accessibility checks, scoped to the relevant page section:

```typescript
import AxeBuilder from '@axe-core/playwright';

const a11y_results = await new AxeBuilder({ page })
    .include('[data-testid="my-component"]')
    .analyze();
expect(a11y_results.violations).toEqual([]);
```

### Linting

All e2e test code must pass lint checks. Run the following command and fix any errors before considering the work done:

```sh
make lint-playwright lint-js
```

### Running tests

Always run the relevant tests to validate changes. Use the following command with the correct path to the spec file:

```sh
make playwright c=tests/e2e/specs/<path-to-spec-file>
```

Do not skip this step — tests must pass before the task is complete.

### CRITICAL: Always use Makefile commands

**NEVER** run `npx`, `node`, `npm`, `eslint`, `tsc`, or any Node.js tool directly. All commands **MUST** go through `make` targets because the tooling runs inside Docker containers. Direct invocations will fail.

### Test fixtures and utilities

Use the custom GLPI fixture (`tests/e2e/fixtures/glpi_fixture.ts`) which provides `page`, `profile`, `entity`, `csrf`, `formImporter`, and `api` helpers. Use the `api` fixture to create test data instead of navigating the UI for setup.
