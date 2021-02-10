#!/bin/bash -e

echo "Init app container home"
mkdir -p $APPLICATION_HOME

echo "Pull and start app container"
docker-compose --file .github/actions/docker-compose-app.yml --project-directory $APPLICATION_ROOT pull --quiet
docker-compose --file .github/actions/docker-compose-app.yml --project-directory $APPLICATION_ROOT up --no-start
docker-compose --file .github/actions/docker-compose-app.yml --project-directory $APPLICATION_ROOT start

echo "Change files rights to give write access to app container user"
setfacl --recursive --modify u:1000:rwx $APPLICATION_ROOT
setfacl --recursive --modify u:1000:rwx $APPLICATION_HOME
