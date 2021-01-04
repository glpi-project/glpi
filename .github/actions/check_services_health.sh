#!/bin/bash
# /**
#  * ---------------------------------------------------------------------
#  * GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2015-2021 Teclib' and contributors.
#  *
#  * http://glpi-project.org
#  *
#  * based on GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2003-2014 by the INDEPNET Development Team.
#  *
#  * ---------------------------------------------------------------------
#  *
#  * LICENSE
#  *
#  * This file is part of GLPI.
#  *
#  * GLPI is free software; you can redistribute it and/or modify
#  * it under the terms of the GNU General Public License as published by
#  * the Free Software Foundation; either version 2 of the License, or
#  * (at your option) any later version.
#  *
#  * GLPI is distributed in the hope that it will be useful,
#  * but WITHOUT ANY WARRANTY; without even the implied warranty of
#  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  * GNU General Public License for more details.
#  *
#  * You should have received a copy of the GNU General Public License
#  * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
#  * ---------------------------------------------------------------------
# */

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
