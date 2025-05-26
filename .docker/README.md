# GLPI docker development environment

The docker development environment can be easily instanciated by running the command `docker compose up -d`
from the GLPI root directory.

## Custom configuration

You can customize the docker services by creating a `docker-compose.override.yaml` file in the GLPI root directory.

### HTTP server

By default, the HTTP port is published to on the `8080` port on the host machine.
You can change it in the `docker-compose.override.yaml` file.

```yaml
services:
  app:
    ports: !override
      - "9000:80"
```

### PHP version

By default, the container runs on the latest available PHP version for the current GLPI branch.
You can change it using the corresponding build arg in the `docker-compose.override.yaml` file.

```yaml
services:
  app:
    build:
      args:
        PHP_VERSION: "7.4"
```

### Files ownership

The default uid/gid used by the docker container is `1000`. If your host user uses different uid/gid, you may encounter
file permissions issues. To prevent this, you can customize them using the corresponding build args in
the `docker-compose.override.yaml` file.

```yaml
services:
  app:
    build:
      args:
        HOST_GROUP_ID: "${HOST_GROUP_ID:-1000}"
        HOST_USER_ID: "${HOST_USER_ID:-1000}"
```

### Services ports

By default, the following ports are exposed:
 - `8080` for the GLPI web server,
 - `8025` for the Mailpit web server,
 - `8090` for the DBGate web server,
 - `9637` for the webpack dev server.

You can customize these ports by creating a `docker-compose.override.yaml` file.

```yaml
services:
  app:
    ports: !override
      - "9000:80"
      - "9001:9637"
  mailpit:
    ports: !override
      - "9025:8025"
  dbgate:
    ports: !override
      - "9080:8080"
```

### Removing services

By default, the compose file will create the following additional services:
 - `db`, for the MariaDB server,
 - `mailpit`, for the Mailpit web server,
 - `dbgate`, for the DBGate web server.

There are included to provide a complete environment but you can disable them
as needed in your `docker-compose.override.yaml` file.

```yaml
services:
  mailpit: !reset null
  dbgate: !reset null
```

To remove the `db` service, you'll also need to remove the `depends_on` directive.

```yaml
services:
  app:
    depends_on: !reset null
  db: !reset null
```

## Makefile

A `Makefile` is provided to interact more easily with the containers.  
It is inspired by https://github.com/dunglas/symfony-docker/blob/main/docs/makefile.md and try to reuse the same syntax.  

### Initial setup

Run `make install` to build the containers and install GLPI's databases.
