#!/bin/bash -e

# Import variables from .env file if it exists
if [[ -f .env ]]; then
  . .env
fi

echo "Init app container home"
mkdir -p $APP_CONTAINER_HOME

echo "Pull and start containers"
docker-compose pull --quiet
docker-compose up --no-start
docker-compose start

if [[ "$UPDATE_FILES_ACL" = true ]]; then
  echo "Change files rights to give write access to app container user"
  sudo apt-get install acl
  setfacl --recursive --modify u:1000:rwx $APPLICATION_ROOT
  setfacl --recursive --modify u:1000:rwx $APP_CONTAINER_HOME
fi

echo "Check services health"
for CONTAINER_ID in `docker-compose ps -a -q`; do
  CONTAINER_NAME=`/usr/bin/docker inspect --format='{{print .Name}}{{if .Config.Image}} ({{print .Config.Image}}){{end}}' $CONTAINER_ID`
  HEALTHY=false
  TOTAL_COUNT=0
  until [ $HEALTHY = true ]; do
    if [ "`/usr/bin/docker inspect --format='{{if .Config.Healthcheck}}{{print .State.Health.Status}}{{else}}{{print \"healthy\"}}{{end}}' $CONTAINER_ID`" == "healthy" ]
    then
      HEALTHY=true
      echo "$CONTAINER_NAME is healthy"
    else
      if [ $TOTAL_COUNT -eq 15 ]
      then
        echo "$CONTAINER_NAME fails to start"
        exit 1
      fi
      echo "Waiting for $CONTAINER_NAME to be ready..."
      sleep 2
      TOTAL_COUNT=$[$TOTAL_COUNT +1]
    fi
  done
done

# Always wait for 5 seconds, even when all services are considered as healthy,
# as they may respond even if their startup script is still running (should not take more than 5 seconds).
# This problem was encountered on mariadb:10.1 service.
sleep 5
