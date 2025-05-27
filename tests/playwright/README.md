# Playwright tests for GLPI

Here is everything you need to know to execute, understand and write playwright tests for GLPI.

## Installing the tests

Execute the following commands to install playwright:

`npx playwright install`  
`npx playwright install-deps`  

Then, make sure the e2e database is installed using `make e2e-db-install`.  
If the base is already installed, make sure it is up to date with `make e2e-db-update`.  

Note: if you don't use docker, you can run the normal glpi install/update commands with the `--env=e2e_testing` argument.

## Configuring the tests

The tests configuration is found in the `/tests/playwright/.env` file.

If you need to override values, copy it as `/tests/playwright/.env.local` and edit values as needed.

### Target URL

The default target URL is `http://localhost:8090`.  

This URL should target a web server running GLPI with the `e2e_testing` environment.  
If you are running the official docker image, you have nothing to do as this should already be configured for you.  

If you are running multiple GLPIs instances at the same time through docker you might need to change this port in your `docker-compose.override.yaml` to make sure it is unique:

```yaml
services:
  app:
    ports: !override
      - "8081:80"
      - "8091:8090" # Use 8091 instead of 8090
```

Don't forget to set the correct port in your env config too:

```env
GLPI_BASE_URL="http://localhost:8091"
```

### PHP binary

The php binary is used to run glpi console commands to setup some tests as needed.

The default configuration will run PHP commands in the main docker container:
```env
PHP="docker compose exec app php"
```

If you prefer using a local php binary, set the following value:
```env
PHP="php"
```

## Running the tests

Run all tests:  
`npx playwright test`

Running a single test:  
`npx playwright test tests/playwright/specs/{path_to_file}/my_test.spec.ts`

Add `--ui` if you need to display the UI, or `--headed` to just display the browser(s).  
More options are available here: https://playwright.dev/docs/running-tests.

Lastly, trace reports generated on CI failures can be viewed using `npx playwright show-report path-to-my-report-folder`.  
You can also generate a trace for a local test by adding the `--trace on` when running tests.

More informations: https://playwright.dev/docs/trace-viewer.

## Writting tests

### Create a new file

Create a new `{test-name}.spec.ts` file in the `tests/playwright/specs/main` folder.

### Add the minimal code structure

This will give you a fresh test with an authenticated GLPI session using the requested profile:  

```ts
import { test, expect } from '../../fixtures/authenticated';

test('my first test', async ({ page, session }) => {
    await session.changeProfile("Super-Admin");
});

test('my second test', async ({ page, session }) => {
    await session.changeProfile("Super-Admin");
});

test('my third test', async ({ page, session }) => {
    await session.changeProfile("Super-Admin");
});
```

Note: authentication has been implemented as recommended here: https://playwright.dev/docs/auth#moderate-one-account-per-parallel-worker.

If you don't need to be authenticated, replace the first line by this one:
```ts
import { test, expect } from 'playwright/test';
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
    public readonly login_field: Locator;
    public readonly password_field: Locator;
    public readonly remember_me_checkbox: Locator;
    public readonly submit_button: Locator;

    public constructor(page: Page)
    {
        super(page);

        // Define all fixed locators used by your page in one place.
        this.login_field          = page.getByRole('textbox', {'name': "Login"});
        this.password_field       = page.getByLabel('Password');
        this.remember_me_checkbox = page.getByRole('checkbox', {name: "Remember me"});
        this.submit_button        = page.getByRole('button', {name: "Sign in"});
    }

    // Add a method to access your page.
    public async goto()
    {
        await this.page.goto(this.base_url);
    }

    // Add one method per action that can be executed on your page.
    public async login(login: string, password: string)
    {
        await this.login_field.fill(login);
        await this.password_field.fill(password);
        await this.remember_me_checkbox.check();
        await this.submit_button.click();
    }
}
```

Then, use your page object in the test:
```ts
import { test, expect } from '../../fixtures/authenticated';
import { LoginPage } from '../../pages/MyPage';

test('my test', async ({ page, session }) => {
    await session.changeProfile("Super-Admin");

    const my_page = new MyPage(page);

    // Then use the object as needed and expect some things.
    await my_page.goto();
    await my_page.doSomeAction();
    await expect(my_page.my_input).toHaveValue('...');
});
```

More details here: https://playwright.dev/docs/pom.  

### Write your tests

Locate items using locator -> https://playwright.dev/docs/locators.  
Do some actions -> https://playwright.dev/docs/input.  
Assert results -> https://playwright.dev/docs/test-assertions.  

### Special case: interacting with select2's dropdowns

Two methods to help interact with select2 dropdown are present in the `GlpiPage` object.
Make sure your custom page extends it so you can use it as needed.

```ts
import { test, expect } from '../../fixtures/authenticated';
import { BasicGlpiPage } from '../../pages/GlpiPage';

test('select2', async ({ page, session }) => {
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

test('richtext', async ({ page, session }) => {
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

This should be done using the `glpi_api` fixture:

```ts
import { test, expect } from '../../fixtures/authenticated';

test('using API', async ({ page, session, glpi_api }) => {
    const id = await glpi_api.createItem('Computer', {
        'name': "My computer"
    });

    await session.changeProfile("Super-Admin");
    await page.goto(`/front/computer.form.php?id=${id}`);
    await expect(page).toHaveTitle(`Computer - My computer - ID ${id} - GLPI`);
});
```

### Best practices

Please read https://playwright.dev/docs/best-practices.  

## How it works

When you run the tests, there are 2 projets defined: `bootstrap` and `main`.

Documentation: https://playwright.dev/docs/test-global-setup-teardown.

### Bootstrap  

The `bootstrap` project will make sure GLPI is ready for the tests to be executed.  
It does so by calling the `php bin/console tools:playwright:bootstrap` command.  

### Main  

The `main` project will contains all tests that do not modify GLPI's global configuration and are thus safe to run in parallel.

## Link to complete documentation

https://playwright.dev/docs.
