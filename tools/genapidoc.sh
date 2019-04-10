#!/bin/sh
# /**
#  * ---------------------------------------------------------------------
#  * GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2015-2018 Teclib' and contributors.
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

cd $(dirname $0)

if which apigen &>/dev/null
then
   version=$(php -r '
      define("GLPI_ROOT", __DIR__ . "/..");
      require GLPI_ROOT . "/inc/define.php";
      echo GLPI_VERSION;
   ')
   apigen generate \
      --access-levels=public,protected,private \
      --todo \
      --deprecated \
      --tree \
      --title "GLPI version $version API" \
      --source ../inc \
      --destination api

else
   echo -e "\nApiGen not found, see http://www.apigen.org/\n"

fi
