#!/bin/sh

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2024 Teclib' and contributors.
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

cd $(dirname $0)/..
if ! which atoum &>/dev/null
then
   echo -e "\natoum not found, see https://atoum.org/\n"
   exit 1

elif php -m | grep -qi xdebug
then
   # PHP 5 + xdebug
   atoum \
      --debug \
      --max-children-number 1 \
      --bootstrap-file tests/bootstrap.php \
      --directories tests/units \
      --no-code-coverage-for-classes DbTestCase DbFunction Autoload NotificationSettingInstance \
      --no-code-coverage-for-namespaces atoum\\atoum
else
   echo -e "\nYou need PHP with XDebug\n"
   exit 2
fi
