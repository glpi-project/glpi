# Keycloak — OIDC provider for testing

Reproducible OpenID Connect (OIDC) provider that can be used for manual and (later) automated tests.

## What it provides

- A realm `glpi` (imported from [`realm-glpi.json`](./realm-glpi.json)) with:
  - a confidential client `glpi` (secret `glpi-secret`);
  - a test user **`alice` / `alice`** (`alice@glpi.test`).

## Networking note (important)

Keycloak listens on **the same port 9090 inside the container and on the host**, under
the hostname **`keycloak.glpi.local`**. This makes the OIDC `issuer` identical whether Keycloak is
reached from the app container (compose DNS alias `keycloak.glpi.local:9090`) or from your browser — which
avoids the classic "issuer mismatch" failure.

For the browser to resolve it, add once to your host `/etc/hosts`:

```
127.0.0.1 keycloak.glpi.local
```

`sudo sh -c 'echo "127.0.0.1 keycloak.glpi.local" >> /etc/hosts'`

## Bring it up

```sh
# Start Keycloak (opt-in profile; not started with the normal stack)
docker compose --profile keycloak up -d keycloak
```

Admin console: <http://keycloak.glpi.local:9090/> (admin / admin).
Discovery: <http://keycloak.glpi.local:9090/realms/glpi/.well-known/openid-configuration>.

## Configure an OIDC client against this Keycloak

Any component that speaks OpenID Connect (a plugin, a core feature, a script under test) needs
the same handful of values to register itself as a client of this realm:

| Parameter | Value |
|-----------|-------|
| Issuer / Discovery URL | `http://keycloak.glpi.local:9090/realms/glpi/.well-known/openid-configuration` |
| Client ID | `glpi` |
| Client secret | `glpi-secret` |
| Login claim | `preferred_username` |
| Redirect/callback URI | *(whatever the client generates — see below)* |

Whatever value the client uses as its redirect/callback URI, it must match a `redirectUris`
entry declared for the `glpi` client in [`realm-glpi.json`](./realm-glpi.json), or the
authentication flow will fail with a `redirect_uri` mismatch.

For example, with the [`oauthsso`](https://github.com/pluginsGLPI/oauthsso) plugin: **Setup >
OAuth SSO**, click **+** to add an Application, set `Oauth provider` to `OpenID Connect`, and
fill in the table above (`Field used as login` = `Preferred username (preferred_username)`).
The plugin then exposes a read-only **Callback url** field
(`http://<url_base>/plugins/oauthsso/front/authorization.callback.php`) once the Application is
saved — that's the value to add to `redirectUris`.

The realm ships with `redirectUris` / `webOrigins` set to `http://localhost:8080`, the
default GLPI app port from [`docker-compose.yaml`](../../docker-compose.yaml) (`8080:80`).
If you expose GLPI on another port (e.g. via a `docker-compose.override.yaml`), update the
`redirectUris` and `webOrigins` in [`realm-glpi.json`](./realm-glpi.json) accordingly so the
callback URL still matches.

## Credentials & values

| Item | Value |
|------|-------|
| Realm | `glpi` |
| Client id | `glpi` |
| Client secret | `glpi-secret` |
| Test user | `alice` / `alice` |
| Discovery URL (server-side) | `http://keycloak.glpi.local:9090/realms/glpi/.well-known/openid-configuration` |

## Notes for automated tests (later)

- `keycloak.glpi.local` resolves to a private-network address (Docker compose alias), so
  server-side requests to it are rejected by `Glpi\Toolbox\HttpClient`'s
  `NoPrivateNetworkHttpClient` guard unless the calling code passes an allowed `$context` (see
  `GLPI_SERVERSIDE_URL_ALLOWED_PRIVATE_NETWORKS_CONTEXTS` in
  `Glpi\Application\SystemConfigurator`). Whatever code fetches the discovery/JWKS URLs
  server-side must pass one of the allowed contexts (e.g. `Auth::class`) — otherwise the
  request is rejected in every environment, not just production.
- Recreating the container regenerates the realm signing keys **and** the users' `sub`
  (dev = in-memory H2). After a recreate: clear the GLPI cache (JWKS/well-known are cached for
  a day) and **log in again** (the session `sub` changes).
