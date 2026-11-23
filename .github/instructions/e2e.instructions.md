---
applyTo: "tests/e2e/**"
---

# End-to-end Tests (Playwright)

Before writing or modifying any e2e test, read existing tests in `tests/e2e/specs/` and page objects in `tests/e2e/pages/` to understand established patterns and conventions.

## Page Object Model

Always use the Page Object Model pattern. Locators and reusable interactions must live in page classes under `tests/e2e/pages/`, not in spec files.

- Every page class extends `GlpiPage` (defined in `tests/e2e/pages/GlpiPage.ts`), which provides helper methods such as `getButton()`, `getLink()`, `getCheckbox()`, `getTextbox()`, `getTab()`, `getRegion()`, `getHeading()`, `getRadio()`, `getSpinButton()`, `getDropdownByLabel()`, `getRichTextByLabel()`, and common actions like `doSetDropdownValue()`, `doAddNote()`, `doLogout()`, etc.
- Declare locators as public readonly properties initialized in the constructor.
- Expose user-facing actions as `async do*()` methods on the page object.
- Spec files should only contain test logic; all element access goes through page objects.

## Selectors

Use Playwright's semantic locators exclusively — prefer `getByRole()`, `getByLabel()`, `getByTestId()`, `getByText()`, `getByTitle()`, `getByAltText()`, and the helper methods inherited from `GlpiPage`.

- **Never use raw CSS or XPath selectors** (`page.locator(...)`) unless there is absolutely no semantic alternative. When a raw locator is unavoidable, add `// eslint-disable-next-line playwright/no-raw-locators` on the line above.
- Before adding a raw locator, **you MUST first attempt all of the following alternatives** in order:
  1. Use existing semantic attributes (`role`, `aria-label`, `title`, `alt`, `data-testid`).
  2. Add an `aria-label` attribute so the element can be targeted with `getByRole()`.
  3. Add a `data-testid` attribute to the element in the Twig template, PHP source, or JavaScript that generates it.
  4. Only after exhausting options 1–3 may you use a raw locator with the eslint-disable comment.
- Targeting elements by `data-glpi-*` attributes with `page.locator('[data-glpi-...]')` **is a raw locator** and is **not allowed**. Add a `data-testid` attribute instead and use `getByTestId()`.

## Tab Navigation

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

## Accessibility Testing

Use `@axe-core/playwright` (`AxeBuilder`) for accessibility checks, scoped to the relevant page section:

```typescript
import AxeBuilder from '@axe-core/playwright';

const a11y_results = await new AxeBuilder({ page })
    .include('[data-testid="my-component"]')
    .analyze();
expect(a11y_results.violations).toEqual([]);
```

## Fixtures and Utilities

Use the custom GLPI fixture (`tests/e2e/fixtures/glpi_fixture.ts`) which provides `page`, `profile`, `entity`,  `formImporter`, and `api` helpers. Use the `api` fixture to create test data instead of navigating the UI for setup.

## Running & Linting

Always run the relevant tests and fix lint errors before considering the work done:

```sh
make playwright c=tests/e2e/specs/<path-to-spec-file>
make lint-playwright lint-js
```
