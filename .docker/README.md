# GLPI docker development environment

The docker development environment can be easilly instanciated by running the command `docker compose up -d`
from the GLPI root directory.

## Custom configuration

You can customize the docker services by creating a `docker-compose.override.yaml` file in the GLPI root directory.

## HTTP server

By default, the HTTP port is published to on the `8080` port on the host machine.
You can change it in the `docker-compose.override.yaml` file.

```yaml
services:
  app:
    ports: !override
      - "9000:80"
```

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

### Database server

By default, the database service is not provided. You can add it in the `docker-compose.override.yaml` file.

```yaml
services:
  database:
    container_name: "db"
    image: "mariadb:11.0"
    restart: "unless-stopped"
    environment:
      MYSQL_ROOT_PASSWORD: "R00tP4ssw0rd"
      MYSQL_DATABASE: "glpi"
      MYSQL_USER: "glpi"
      MYSQL_PASSWORD: "P4ssw0rd"
    ports:
      - "3306:3306"
    volumes:
      - "db:/var/lib/mysql"

volumes:
  db:
```
