# Playwright tests for GLPI

Here is everything you need to know to execute, understand and write playwright tests for GLPI.

## Installing the tests

Execute the following commands to install playwright:

`npx playwright install`  
`npx playwright install-deps`  

## Configuring the tests

By default, the tests will target `http://localhost:80`.  

If you are using another URL, you must specify it in your `/tests/playwright/.env` local configuration.  
If you are running GLPI inside docker, you might also need to redefine the php command using the same file.  

See `.env.example` for other possibles configurations.  

## Running the tests

Run all tests:  
`npx playwright test`

Running a single test:  
`npx playwright test tests/playwright/specs/{path_to_file}/my_test.spec.ts`

Add `--ui` if you need to display the UI, or `--headed` to just display the browser(s).  
More options are available here: https://playwright.dev/docs/running-tests.

Lastly, trace reports generated on CI failures can be viewed using `npx playwright show-report path-to-my-report-folder`.  
You can also generate a trace for a local test by adding the `--trace on` option to `npx playwright test`.

More informations: https://playwright.dev/docs/trace-viewer.

## Writting tests

### Create a new file

Create a new `{test-name}.spec.ts` file in the `tests/playwright/specs/shared` or `tests/playwright/specs/isolated` folder.

The `shared` folder is the default one that you will use most of the time.  
Only use `isolated` if you are sure your test is too dangerous to be executed concurently with others tests  
This is only the case if it does global modifications beyond the scope of the current entity.  

### Add the minimal code structure

This will give you a fresh test with an authenticated GLPI session.

```ts
import { test, expect } from '../../fixtures/authenticated';

test('my first test', async ({ page, request }) => {
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");
});

test('my second test', async ({ page, request }) => {
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");
});

test('my third test', async ({ page, request }) => {
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");
});
```

### Use a page object model

Page object model (POM) is a pattern that allow easier maintainability of tests by grouping page interactions into dedicated page objects.  

You must look into the `tests/playwright/pages/` for a page object that references the feature you are trying to test.  
For example, if you are trying to test the login process you can use `tests/playwright/pages/LoginPage.ts`.  

If no page exist for your feature, you must create your own like this:

```ts
import { type Locator, type Page } from '@playwright/test';
import { GlpiPage } from './GlpiPage';

export class LoginPage extends GlpiPage
{
    public readonly loginField: Locator;
    public readonly passwordField: Locator;
    public readonly rememberMeCheckbox: Locator;
    public readonly submitButton: Locator;

    public constructor(page: Page) {
        super(page);

        // Define all fixed locators used by your page in one place.
        this.loginField         = page.getByRole('textbox', {'name': "Login"});
        this.passwordField      = page.getByLabel('Password');
        this.rememberMeCheckbox = page.getByRole('checkbox', {name: "Remember me"});
        this.submitButton       = page.getByRole('button', {name: "Sign in"});
    }

    // Add a method to access your page.
    public async goto() {
        await this.page.goto(this.base_url);
    }

    // Add one method per action that can be executed on your page.
    public async login(login: string, password: string) {
        await this.loginField.fill(login);
        await this.passwordField.fill(password);
        await this.rememberMeCheckbox.check();
        await this.submitButton.click();
    }
}
```

Then, use your page object in the test:
```ts
import { test, expect } from '../../fixtures/authenticated';
import { LoginPage } from '../../pages/MyPage';

test('my test', async ({ page, request }) => {
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    const my_page = new MyPage(page);

    // Then use the object as needed and expect some things.
    await my_page.goto();
    await my_page.doSomeAction();
    await expect(my_page.nameInput).toHaveValue('...');
});
```

More details here: https://playwright.dev/docs/pom.  

### Write your actual tests

Locate items using locator -> https://playwright.dev/docs/locators.  
Do some actions -> https://playwright.dev/docs/input.  
Assert results -> https://playwright.dev/docs/test-assertions.  

### Special case: interacting with select2's dropdowns

Two methods to help interact with select2 dropdown are present in the `GlpiPage` object.
Make sure your custom page extends it so you can use it as needed.

```ts
import { test, expect } from '../../fixtures/authenticated';
import { BasicGlpiPage } from '../../pages/GlpiPage';

test('select2', async ({ page, request }) => {
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    const glpi = new GlpiPage(page);
    await page.goto('/front/ticket.form.php');

    const type_dropdown = await glpi.getDropdownByLabel("Type");
    await expect(type_dropdown).toContainText("Incident");
    await glpi.setDropdownValue(type_dropdown, "Request");
});
```

### Special case: interacting with tinymce's richtext fields

One method to help interact with tinemce editor are present in the `GlpiPage` object.
Make sure your custom page extends it so you can use it as needed.

```ts
import { test, expect } from '../../fixtures/authenticated';
import { BasicGlpiPage } from '../../pages/GlpiPage';

test('richtext', async ({ page, request }) => {
    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    const glpi = new GlpiPage(page);
    await page.goto('/front/ticket.form.php');

    const description = await glpi.getRichTextByLabel("Description");
    await description.fill('My ticket description');
    await expect(description).toHaveText('My ticket description');
});
```

### Handling items with GLPI's API

To speed up tests execution, you are expected to arrange your required data using the api.

This can be done with the `GlpiApi` object like this:

```ts
import { test, expect } from '../../fixtures/authenticated';
import { GlpiApi } from '../../utils/GlpiApi';

test('using API', async ({ page, request }) => {
    const api = new GlpiApi();
    const id = await api.createItem('Computer', {
        'name': "My computer"
    });

    const session = new SessionManager(request);
    await session.changeProfile("Super-Admin");

    await page.goto(`/front/computer.form.php?id=${id}`);
    await expect(page).toHaveTitle(`Computer - My computer - ID ${id} - GLPI`);
});
```

### Best practices

Please read https://playwright.dev/docs/best-practices.  

## How it works

When you run the tests, there are 3 projets defined: `setup`, `shared` and `isolated`.

The `setup` is a special project without real tests that will make sure GLPI is ready for the tests to be executed.

This is the recommended way of doing this, see https://playwright.dev/docs/test-global-setup-teardown.

The only thing this project does is to execute the `php bin/console tools:playwright:setup` command with the current number of threads used by the tests.

The command will then:
* Enable GLPI's API
* Create a special "Playwright archive" entity
* Create a "Playwright" entity in GLPI
* Create a "Playwright worker {worker_index}" entity and user per threads (under the main "Playwright" entity)

It will also delete old "Playwright" and "Playwright worker {worker_index}" entities and users to avoid cluttering the database and make the tests more resilient when running multiple times locally.  
This way the entities and users are always "fresh" when running the tests.  
Items from these entities are transfered to the "Playwright archive" entity as GLPI do not support deleting all items from a given entity.

The `shared` project is then executed, then the `isolated` project.

Tests can have access to an authenticated session using the `authenticated` fixture.  
This is done as recommended here: https://playwright.dev/docs/auth#moderate-one-account-per-parallel-worker.

## Link to complete documentation

https://playwright.dev/docs.
