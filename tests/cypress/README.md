# Running Cypress tests 

First, make sure the test database is freshly installed:

```sh
make test-db-install
```

You might need to reinstall it from time to time to restore the original data.

Then, you can run the whole tests suite with the following command:

```sh
make cypress
```

To run a specific tests, use the `c` parameter:

```sh
make cypress c="--spec tests/cypress/e2e/ajax_controller.cy.js"
```

To open cypress UI, you'll need to add the following configuration in your compose override file:

```yml
services:
  app:
    environment:
      DISPLAY: :0
    volumes:
      - /tmp/.X11-unix:/tmp/.X11-unix
```

Then, open cypress with this command:

```sh
make cypress-open
```
