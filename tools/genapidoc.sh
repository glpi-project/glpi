#!/bin/sh

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2023 Teclib' and contributors.
# @copyright 2003-2014 by the INDEPNET Development Team.
# @licence   https://www.gnu.org/licenses/gpl-3.0.html
#
# ---------------------------------------------------------------------
#
# LICENSE
#
# This file is part of GLPI.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#
# ---------------------------------------------------------------------
#

cd $(dirname $0)

if which apigen &>/dev/null
then
   version=$(php -r '
      require __DIR__ . "/../inc/define.php";
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
