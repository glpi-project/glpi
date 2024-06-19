# GLPI docker devcontainers

The docker devcontainers are meant to be used by VSCode or in a Github Codespaces environment.

## Services ports

By default, the services ports are published to random ports on the host machine, to prevent conflicts with other projects.
You will have to define specific ports in a `docker-compose.override.yml` file located inside the `.devcontainer` directory.
The `.devcontainer/docker-compose.override.yml.dist` file provides an example of ports definition.
