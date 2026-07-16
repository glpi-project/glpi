# Keycloak — test provider for the OAuth (re-)authentication flow

Reproducible OpenID Connect provider used to exercise the OAuth **re-authentication**
(step-up) flow of the `oauthsso` plugin end-to-end. Everything here is committed so
the same provider can be reused for manual and (later) automated tests.

## What it provides

- A realm `glpi` (imported from [`realm-glpi.json`](./realm-glpi.json)) with:
  - a confidential client `glpi-oauthsso` (secret `glpi-oauthsso-secret`), redirect URI
    `http://localhost:8081/plugins/oauthsso/front/authorization.callback.php`;
  - a test user **`alice` / `alice`** (`alice@glpi.test`).
- The GLPI-side OAuth *Application* and the matching GLPI user `alice` (with a central
  Super-Admin profile) are created by a seed script (below).

## Exposing `auth_time` (critical)

The re-authentication check proves the user *actually re-entered their credentials just now*
by verifying the ID token's `auth_time` claim against the moment the round-trip started.

**Keycloak does NOT emit `auth_time` in the ID token by default** — not even with
`prompt=login` / `max_age=0`. Without it, every re-authentication is rejected with
*"Provider did not prove a fresh authentication (missing auth_time)"*.

The realm therefore declares a **User Session Note** protocol mapper on the `glpi-oauthsso`
client (already in [`realm-glpi.json`](./realm-glpi.json)) that copies the session note
`AUTH_TIME` into the `auth_time` claim of the ID token:

```json
{
  "name": "auth_time",
  "protocol": "openid-connect",
  "protocolMapper": "oidc-usersessionmodel-note-mapper",
  "consentRequired": false,
  "config": {
    "user.session.note": "AUTH_TIME",
    "claim.name": "auth_time",
    "jsonType.label": "long",
    "id.token.claim": "true",
    "access.token.claim": "false",
    "userinfo.token.claim": "false"
  }
}
```

In the admin console the equivalent is: *Clients → glpi-oauthsso → Client scopes →
glpi-oauthsso-dedicated → Add mapper → By configuration → User Session Note*, with
*User Session Note* = `AUTH_TIME`, *Token Claim Name* = `auth_time`, *Claim JSON Type* =
`long`, *Add to ID token* = ON.

Any other OIDC provider must likewise be configured to return `auth_time` (and to honor a
forced re-authentication), otherwise it is not eligible for re-authentication.

## Networking note (important)

Keycloak listens on **the same port 9090 inside the container and on the host**, under
the hostname **`keycloak`**. This makes the OIDC `issuer` identical whether Keycloak is
reached from the app container (compose DNS `keycloak:9090`) or from your browser — which
avoids the classic "issuer mismatch" failure.

For the browser to resolve it, add once to your host `/etc/hosts`:

```
127.0.0.1 keycloak
```

(From this Claude Code session you can run: `! sudo sh -c 'echo "127.0.0.1 keycloak" >> /etc/hosts'`.)

## Bring it up

```sh
# Start Keycloak (opt-in profile; not started with the normal stack)
docker compose --profile keycloak up -d keycloak

# Create/refresh the GLPI OAuth Application + test user `alice` (Super-Admin)
# (idempotent; re-run after the tmpfs DB is recreated)
docker compose exec -T app php plugins/oauthsso/tools/seed_reauth_test_app.php
```

Admin console: <http://keycloak:9090/> (admin / admin).
Discovery: <http://keycloak:9090/realms/glpi/.well-known/openid-configuration>.

## Run the manual re-authentication test

Prerequisites (already satisfied in the dev DB): GLPI external auth is configured with
`ssovariables_id = HTTP_AUTH_USER` and `is_users_auto_add = 1`, so `alice` is created on
first login. GLPI `url_base` must be `http://localhost:8081`.

1. **Log in via Keycloak.** Go to <http://localhost:8081>, click the *Keycloak (reauth test)*
   provider on the login page, authenticate as `alice` / `alice`. You are now logged into
   GLPI through OAuth (the session carries the OAuth authorization → the re-auth strategy
   becomes available).

   The seed script pre-creates the GLPI user `alice` with a central-interface profile
   (Super-Admin), so the login reuses that account (matched by login) and keeps the profile —
   no post-login promotion needed. (A brand-new auto-added SSO user would otherwise only get
   the Self-Service profile, which cannot reach most re-auth-protected actions.)
2. **Force a re-authentication.** Open `http://localhost:8081/front/reauth_nfo.php` and click
   **Expire reauth** (sets `glpi_reauth_until` in the past). Then trigger any action that
   requires re-authentication (or simply browse to `/ReAuth/Prompt`).
3. **Observe the flow.** The prompt shows a *Re-authenticate* button (no password field).
   Clicking it redirects to Keycloak, which **re-prompts the credentials** (`prompt=login` +
   `max_age=0`). After re-entering them, GLPI verifies the returned identity and the fresh
   `auth_time`, grants the re-authentication window, and replays the original action.
4. Back on `reauth_nfo.php`, `glpi_reauth_until` is now in the future.

To watch the server side while testing:

```sh
docker compose exec -T app tail -f files/_log/plugin-oauthsso.log   # denials are logged here
```

## Credentials & values

| Item | Value |
|------|-------|
| Realm | `glpi` |
| Client id | `glpi-oauthsso` |
| Client secret | `glpi-oauthsso-secret` |
| Test user | `alice` / `alice` |
| Discovery URL (server-side) | `http://keycloak:9090/realms/glpi/.well-known/openid-configuration` |
| GLPI Application name | `Keycloak (reauth test)` |

## Notes for automated tests (later)

- The `testing` / `e2e_testing` environments have a stricter `GLPI_SERVERSIDE_URL_ALLOWLIST`
  than dev (which is `['/.*/']`). To let GLPI fetch the discovery URL there, the allowlist
  must permit `http://keycloak:9090/...`.
- Some strict OIDC servers reject `max_age=0`; Keycloak honors it and re-prompts the user.
  But it only returns a fresh `auth_time` once the User Session Note mapper above is in place
  (see *Exposing `auth_time`*) — that mapper is what the freshness check depends on.
- Recreating the container regenerates the realm signing keys **and** the users' `sub`
  (dev = in-memory H2). After a recreate: clear the GLPI cache (JWKS/well-known are cached for
  a day) and **log in again** (the session `sub` changes, otherwise the re-auth identity
  binding fails).
