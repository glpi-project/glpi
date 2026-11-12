# Field history search: local GLPI 12 demo

This environment uses its own Compose project, source volume and database volume.
It does not use the existing `glpi-app-1` / `glpi-db-1` containers or their data.
The web server is bound to loopback only. Default credentials below are for this
development environment, not a production deployment.

## Try the running demo

1. Open <http://localhost:8082> and sign in with **glpi / glpi**.
2. Open **Assets > Computers** and expand the search panel (the **Search** or
   **Filtered by ...** button).
3. Select **Characteristics > Name**, **contains**, and enter `DEMO-OLD-PC`.
4. In the new dropdown next to the value, choose a scope and click **Search**.

| Scope | Expected computers |
| --- | --- |
| Current value | `DEMO-OLD-PC` |
| History | `DEMO-CURRENT-PC` |
| Current value and history | Both computers above |

`DEMO-CURRENT-PC` was renamed twice:
`DEMO-OLD-PC` -> `DEMO-MIDDLE-PC` -> `DEMO-CURRENT-PC`.
Open its card and History tab to inspect the changes. The same computer's serial
number changed from `DEMO-OLD-SERIAL` through `DEMO-MIDDLE-SERIAL` to
`DEMO-CURRENT-SERIAL`; try the **Serial number** field with **History** too.

Two controls are included: `DEMO-CONTROL-PC` has `DEMO-OLD-PC` in its comment
history only, and `DEMO-UNTOUCHED-PC` has no name changes. Neither should match
the Name history query. The seed script can be run again without creating duplicates.

## Behavior and limits

- History searches the retained **old and new values** for the selected field.
  A current value can therefore match History if it was recorded in a change.
- **does not contain** excludes an item if any value in the selected scope
  matches. Multiple matching changes still produce just one result row.
- Existing text search syntax is preserved; use `^DEMO-OLD-PC$` for an exact
  match with **contains**.
- This first implementation supports an item's own logged text fields, including
  Name, Serial number and Comments. Related objects, meta criteria, computed
  fields and the Global asset list are not supported. Other operators hide the
  scope dropdown; changing the field resets the scope to Current value.
- The user needs permission to view logs. Normal item/entity permissions and
  trash filtering remain in effect. Saved searches retain the selected scope.
- Purged history and text omitted by GLPI's log-value truncation cannot be
  recovered by this feature. No schema migration is required. Performance on
  production-sized log tables has not been measured.

## Stop and resume

Run these PowerShell commands from the repository root, with Docker Desktop running:

```powershell
docker compose -f .docker/history-search/compose.yaml stop
docker compose -f .docker/history-search/compose.yaml up -d
```

Stopping preserves both volumes. Do not remove volumes if you want to retain
the demo database and installed dependencies.

## First setup on a fresh machine

These steps are for **empty demo volumes**, not for reinstalling the running demo.
The image uses the repository's PHP 8.4 development Dockerfile.

```powershell
docker compose -f .docker/history-search/compose.yaml up -d --build
& .\.docker\history-search\sync.ps1
docker compose -f .docker/history-search/compose.yaml exec -T app php bin/console dependencies install --no-interaction
```

If the final illustration-build step reports an outdated `.package.hash` after
npm dependencies installed successfully, update that marker and finish the build:

```powershell
docker compose -f .docker/history-search/compose.yaml exec -T app npm run dependencies:postinstall
docker compose -f .docker/history-search/compose.yaml exec -T app npm run build:illustration-translations
```

Compile translations, install the demo database, then create sample devices:

```powershell
docker compose -f .docker/history-search/compose.yaml exec -T app php bin/console tools:locales:compile
docker compose -f .docker/history-search/compose.yaml exec -T app php bin/console database:install --db-host=db --db-name=glpi --db-user=glpi --db-password=glpi --no-interaction
docker compose -f .docker/history-search/compose.yaml exec -T app php .docker/history-search/seed.php
```

The sync script copies tracked and nonignored new files, including uncommitted
changes, to the dedicated Linux source volume. It normalizes shell-script line
endings there and leaves generated configuration/dependencies in the volume.
Run it again after PHP/Twig changes, then reload the page. It overlays files;
it does not remove old files deleted from the working tree.

## Automated tests

Install a **separate** test database once. The default demo database is not used
for PHPUnit fixtures:

```powershell
docker compose -f .docker/history-search/compose.yaml exec -T app php bin/console database:install --env=testing --db-host=db --db-name=glpitests --db-user=root --db-password=glpi --no-interaction
```

Run the focused regression tests, or the full search test file:

```powershell
docker compose -f .docker/history-search/compose.yaml exec -T app vendor/bin/phpunit tests/functional/SearchTest.php --filter HistorySearch
docker compose -f .docker/history-search/compose.yaml exec -T app vendor/bin/phpunit tests/functional/SearchTest.php
```

Run database tests sequentially. The complete search test file can take several
minutes. Coverage includes old/intermediate/new values, field and item-type
isolation, duplicate changes, combined scopes, negation, nested criteria,
quoting, entity access, deleted items, log permissions, invalid requests and
rendering the scope control (including AJAX boolean parameters).
