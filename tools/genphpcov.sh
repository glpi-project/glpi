#!/bin/sh
#/**
# * ---------------------------------------------------------------------
# * GLPI - Gestionnaire Libre de Parc Informatique
# * Copyright (C) 2015-2017 Teclib' and contributors.
# *
# * http://glpi-project.org
# *
# * based on GLPI - Gestionnaire Libre de Parc Informatique
# * Copyright (C) 2003-2014 by the INDEPNET Development Team.
# *
# * ---------------------------------------------------------------------
# *
# * LICENSE
# *
# * This file is part of GLPI.
# *
# * GLPI is free software; you can redistribute it and/or modify
# * it under the terms of the GNU General Public License as published by
# * the Free Software Foundation; either version 2 of the License, or
# * (at your option) any later version.
# *
# * GLPI is distributed in the hope that it will be useful,
# * but WITHOUT ANY WARRANTY; without even the implied warranty of
# * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# * GNU General Public License for more details.
# *
# * You should have received a copy of the GNU General Public License
# * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
# * ---------------------------------------------------------------------
# */

cd $(dirname $0)/..
if ! which phpunit &>/dev/null
then
   echo -e "\nphpunit not found, see https://phpunit.de/\n"
   exit 1

elif which phpdbg &>/dev/null
then
   # PHP 7 + phpdbg, faster
   phpdbg -qrr $(which phpunit) \
          -d memory_limit=1G \
          --coverage-html tools/htmlcov \
          --whitelist inc \
          --verbose
elif php -m | grep -qi xdebug
then
   # PHP 5 + xdebug
   phpunit -d memory_limit=1G \
           --coverage-html tools/htmlcov \
           --whitelist inc \
           --verbose
else
   echo -e "\nYou need PHP 7 with phpdng or PHP with XDebug\n"
   exit 2
fi

echo "Result in file://$PWD/tools/htmlcov/index.html";

