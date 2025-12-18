# E2E tests

E2E tests are written using playwright.  
Official documentation: https://playwright.dev/docs/intro

## Running the tests

### With the official GLPI development docker image (recommanded)

#### Install the e2e database

```sh
make e2e-db-install
```

#### Setup base URL

By default, the E2E tests server is exposed on the `8090` port.  
If you changed this port in your docker override file, copy the `.env` file as 
`.env.local` and update `E2E_BASE_URL` as needed.

#### Execute all tests

```sh
make playwright
```

#### Execute a single test or folder

```sh
make playwright c=tests/e2e/specs/example.spec.ts
```

#### View tests results

```sh
make playwright-report
```

Then, go to `http://127.0.0.1:9323` or click the link displayed in your terminal.  
You can also bookmark the URL for easy access.  

#### Open UI mode

```sh
make playwright-ui
```

Then, go to `http://127.0.0.1:9323` or click the link displayed in your terminal.  
You can also bookmark the URL for easy access.  

### Without docker

#### Install playwright browsers

```sh
npx playwright install chromium
sudo npx playwright install-deps chromium
```

#### Setup GLPI e2e server

You'll need a GLPI server running in the `e2e_testing` environment.

See examples: 
- https://github.com/glpi-project/docker-images/blob/main/glpi-development-env/files/etc/apache2/sites-available/000-default.conf
- https://github.com/glpi-project/docker-images/blob/main/glpi-development-env/files/etc/apache2/ports.conf

#### Install the e2e database

Use the `glpi:database:install` console command with the `--env=e2e_testing`
parameter to setup the test database.

#### Setup base URL

Copy the `.env` file as `.env.local` and replace `E2E_BASE_URL` by the URL to
your GLPI server running in the `e2e_testing` environment.

#### Execute all tests

```sh
npx playwright test
```

#### Execute a single test or folder

```sh
npx playwright test tests/e2e/spec/example.spec.ts
```

#### View tests results

```sh
npx playwright show-report tests/e2e/results
```

#### Open UI mode

```sh
npx playwright test --ui
```

## Writing tests

### Minimal test file

The minimal structure for a test looks like this:

```ts
import { test, expect } from '../../fixtures/glpi_fixture';
import { Profiles } from '../../utils/Profiles';

test('My test', async ({page, profile}) => {
    await profile.set(Profiles.SuperAdmin);
});
```

The first important thing is that we import `glpi_fixture`:

```ts
import { test, expect } from '../../fixtures/glpi_fixture';
```

This "fixture" extends the default `test()` function with some code specific to
GLPI.

You can see it a bit like the `GlpiTestCase` from PHPUnit, some kind of parent
class to share common code.

This allow us to gain access to some specific objects, which you can see on the 
following line:
```ts
test('My test', async ({page, profile}) => {
```

Here, we retrieve the native page fixture (which will allow us to go to a given
url) and our homemade profile fixture which allow switching to another profile.

You can see this as some kind of dependency injection, like symfony does in
controllers with type hinted parameters.

More information:
* Check the `fixtures/glpi_fixture.ts` file
* Official documentation: https://playwright.dev/docs/test-fixtures

Then, the other critical part is setting the expected profile:

```ts
await profile.set(Profiles.SuperAdmin);
```

This is mandatory because it is frequent to switch profiles during tests and each
thread reuse a single GLPI session so you don't know which profile is currently
used by the session when the test start.

### Going to a page

To go to a specific page, use this method:

```ts
await page.goto('/front/preference.php');
```

Note that the page will already be logged into GLPI with a valid session.

If you need to test a feature that required you to be logged out, you can require
the special `anonymousPage` fixture instead:

```ts
test('anonymous GLPI page', async ({anonymousPage}) => {
    await anonymousPage.goto('/');
    await expect(anonymousPage).toHaveTitle("Authentication - GLPI");
});
```

You can also use both pages types at the same test if you need to:
```ts
test('logged and anonymous page in the same test', async ({page, profile, anonymousPage}) => {
    // This can be useful if you need to change some settings as an admin, then
    // validate with an anonymous user that they are applied
    await profile.set(Profiles.SuperAdmin);
    await page.goto('/');
    await expect(page).toHaveTitle("Standard interface - GLPI");

    await anonymousPage.goto('/');
    await expect(anonymousPage).toHaveTitle("Authentication - GLPI");
});
```

### Getting an element

To get an element on a page, you must use a `Locator`, for example:

```ts
page.getByRole('button', { name: 'Sign in' });
```

See all posibilities here: https://playwright.dev/docs/locators.

### Doing an action

When you have locator, you can then apply some actions to it, for example:

```ts
await page.getByRole('button', { name: 'Sign in' }).click();
```

See all posibilities here: https://playwright.dev/docs/input.

### Assertions

Assertions are done by calling `expect` on a `Locator` instance.

Here is an example of an assertion:
```ts
const name_input = page.getByRole('textbox', { name: "Name"});
await expect(name_input).toHaveValue("My computer name");
```

When dealing with a list of objects, you can loop on them like this:
```ts
const buttons = page.getByRole('button');
for (const button of await buttons.all()) {
    await expect(button).toBeVisible();
}
```

See all possibilites here: https://playwright.dev/docs/test-assertions.

### Page object models

Take the following test, which does some changes on the Form editor then clicks
the save button:

```ts
test('Save a form', async ({page, profile}) => {
    await profile.set(Profiles.SuperAdmin);

    // Go to a given form
    const tab = "Glpi\\Form\\Form$main";
    await page.goto(`/front/form/form.form.php?id=1&forcetab=${tab}`);

    // Set as active and save the form
    page.getByRole('checkbox', {
        name: "Active",
        exact: true,
    }).check();
    page.getByRole('button', {
        name: "Save",
        exact: true,
    }).click();
});
```

It may seem simple, but it is not good for maintainability because it manually
define how to set a form as active and save it.  

This mean that any others test that will do the same actions will duplicate this
logic and that if there are some breaking changes on GLPI's side (the save button
might be renamed to something else) then all tests will need to be updated.

A common solution for this is to wrap any actions in a page object that can be
re-used by all tests.

Now, if there are breaking change, we only need to update a single method from
the page object.

The code become:

```ts
import { FormPage } from '../../pages/FormPage';

test('Save a form', async ({page, profile}) => {
    await profile.set(Profiles.SuperAdmin);

    // Go to a given form
    const form_page = new FormPage(page);
    await form_page.goto(id);

    // Set as active and save the form
    await form_page.doSetActive();
    await form_page.doSaveFormEditor();
});
```

There is also a basic `GlpiPage` object that contains basic methods that can be
used on any GLPI page (getting a dropdown or a richtext editor for example).  

Specific pages objects can extends it.  

See `pages/GlpiPage.ts` for more details.

Official documentation regarding this "Page object model" pattern:
https://playwright.dev/docs/pom.


### Using the API to setup a test

Some tests might need a specific item to exist in GLPI.  
In this case, it is better to create the item with the API as it will be much
faster than going manually in the UI.

You can do this with the `api` fixture:

```ts
test('create a computer with the API', async ({page, profile, api}) => {
    const computer_name = `My computer ${randomUUID()}`;
    const id = await api.createItem("Computer", {
        name: computer_name,
        is_recursive: true,
    });

    await profile.set(Profiles.SuperAdmin);
    await page.goto(`/front/computer.form.php?id=${id}`);
    const name_input = page.getByRole('main').getByRole('textbox', {
        name: "Name",
        exact: true
    });
    await expect(name_input).toHaveValue(computer_name);
});
```

### Try to only modify your entity data

When the tests is executed, playwright spawn one browser per thread.  
Each browsers will use a unique user and entity, for example the first thread
will use the `E2E worker account 01` user on the `E2E worker entity 01` entity.

When you create items through the API, make sure to create them inside your 
worker entity to avoid polluting others browsers:  

```ts
import { getWorkerEntityId } from '../../utils/WorkerEntities';

...

const id = await api.createItem("Glpi\\Form\\Form", {
    name: "My form",
    entities_id: getWorkerEntityId(),
    is_active: false,
});
```

Some tests might require global actions that goes beyond entities (for example,
editing GLPI's config).

This will need a special treatment to make sure it doesn't impact other workers.

The documentation will be updated once we encounter such a case to explain how to
deal with it.

## Debugging CI failures

If the CI fails, download the `playwright-report` artifact on github.  
Then, extract it and submit the files from the `data` folder on https://trace.playwright.dev/.

