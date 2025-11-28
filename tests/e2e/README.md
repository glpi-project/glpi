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
