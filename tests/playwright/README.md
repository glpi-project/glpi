# Playwright tests for GLPI

Here is everything you need to know to write, execute and understand playwright tests for GLPI.  

## Installing the tests

### Docker

If you use the official GLPI image, playwright should already be installed and ready to use.

You'll just need to install the e2e database with `make e2e-db-install`.  
If the base already exist, you can update it with `make e2e-db-update`.  

#### Multiple containers

If you have multiple GLPI containers running at the same time, you might need to override the port used for playwright reports to make sure it is unique, like so:

```yaml
# docker-compose.override.yaml
services:
  app:
    ports: !override
      - "{my-port}:9323" # Playwright reports
```

Note that the `make playwright-report` command will still show you the original port in this case as it is used in the internal container:  

```
$ make playwright-report
Serving HTML report at http://0.0.0.0:9323. Press Ctrl+C to quit.
```

So you'll need to correct the link after clicking it (or keep a dedicated bookmark).

#### Running the UI

If you want to use the UI and not just run headless tests then you'll need to install playwright outside docker (see: `Without docker` section) as the container doesn't support GUI applications.  

Note that I personally think that the UI is not really needed for developers as running headless tests is more convenient 99% the time.  
The remaining 1% is for debugging purpose but the trace/report feature works just as well for that.  

### Without docker

Install playwright with the following command: `npx playwright install chromium --with-deps`

Then, install the e2e database with the `database:install` console command, using the `--env=e2e_testing` argument.  
If the base already exist, you can update it with the `database:update` console command, using the `--env=e2e_testing` argument.  

## Configuring the tests

The tests configuration is found in the `/tests/playwright/.env` file.

If you need to override values, copy it as `/tests/playwright/.env.local` and edit values as needed.

### Docker

The default configuration should be good to go, it will accept request to the e2e server at the `localhost:8090` url.  

If you run multiple containers at the same time, you'll need to change this port to avoid conflicts:  

```yaml
# docker-compose.override.yaml

services:
  app:
    ports: !override
      - "{my-port}:8090" 
```

```sh
# tests/playwright/.env.local

GLPI_BASE_URL="http://localhost:{my-port}"
```

If you run playwright without docker while using the docker image for your GLPI server (for example if you need the UI mode), you'll need to set the `PHP_BINARY` option to `docker compose exe app php` to make sure the glpi bin/console commands run inside the container.  

### Without docker

You'll need an e2e server running at the `http://localhost:8090` url.  

If you want to use another url/port for this, don't forget to change the configuration:  
```sh
# tests/playwright/.env.local

GLPI_BASE_URL="http://localhost:{my-port}"
```

An "e2e server" just mean a GLPI running in the `e2e_testing` environment.  
One way to achieve this is to make apache2 listen on two different ports:
```sh
# /etc/apache2/ports.conf

Listen 80   # dev
Listen 8090 # e2e
```

Then set the correct env variable for the `8090` port:
```sh
# /etc/apache2/sites-available/glpi.conf

<VirtualHost *:80> # dev
    Include vhosts/glpi-common.conf # Your existing configuration
</VirtualHost>

<VirtualHost *:8090> # e2e
    SetEnv GLPI_ENVIRONMENT_TYPE e2e_testing
    Include vhosts/glpi-common.conf # Your existing configuration
</VirtualHost>
```

## Running the tests

General documentation: https://playwright.dev/docs/running-tests.

### Docker

Run all tests:  
`make playwright`

Running a single test:  
`make playwright c=tests/playwright/specs/{path_to_file}/my_test.spec.ts`

To debug a test, run it with the `--trace=on` option, like this:  
`make playwright c="tests/playwright/specs/{path_to_file}/my_test.spec.ts --trace=on"`  
Then display the report, go to your browser and click on the "View Trace" button.  
`make playwright-report`

### Without docker

Run all tests:  
`npx playwright test`

Running a single test:  
`npx playwright test tests/playwright/specs/{path_to_file}/my_test.spec.ts`

To debug a test, run it with the `--trace=on` option, like this:  
`npx playwright test tests/playwright/specs/{path_to_file}/my_test.spec.ts --trace=on`  
Then display the report, go to your browser and click on the "View Trace" button.  
`npx playwright show-report`

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
