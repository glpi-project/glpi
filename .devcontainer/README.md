# GLPI docker devcontainers

The docker devcontainers are meant to be used by VSCode or in a Github Codespaces environment.

## Services ports

By default, the following ports are exposed:
 - `8080` for the GLPI web server,
 - `8025` for the Mailpit web server,
 - `8090` for the Adminer web server.

You can customize these ports by creating a `.devcontainer/docker-compose.override.yaml` file.

```yaml
services:
  app:
    ports: !override
      - "9000:80"
  mailpit:
    ports: !override
      - "9025:8025"
  adminer:
    ports: !override
      - "9080:8080"
```
