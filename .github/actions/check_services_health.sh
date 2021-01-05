#!/bin/bash

for CONTAINER in {"db","dovecot","openldap"}; do
  HEALTHY=false
  TOTAL_COUNT=0
  until [ $HEALTHY = true ]; do
    if [ "`/usr/bin/docker inspect --format='{{if .Config.Healthcheck}}{{print .State.Health.Status}}{{else}}{{print \"healthy\"}}{{end}}' $CONTAINER`" == "healthy" ]
    then
      HEALTHY=true
    else
      if [ $TOTAL_COUNT -eq 15 ]
      then
        echo "$CONTAINER fails to start..."
        exit 1
      fi
      echo "Waiting for $CONTAINER to be ready..."
      TOTAL_COUNT=$[$TOTAL_COUNT +1]
    fi
    # Always wait for 2 seconds, even when service is considered as healthy,
    # as it may respond even if its startup script is still running (should not take more than 2 seconds).
    # This problem was encountered on mariadb:10.1 service.
    sleep 2
  done
done
