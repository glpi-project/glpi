#!/bin/sh
# /**
#  * ---------------------------------------------------------------------
#  * GLPI - Gestionnaire Libre de Parc Informatique
#  * Copyright (C) 2015-2017 Teclib' and contributors.
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
# TO BE LAUNCH ON A ROOT OF GLPI REPOSITORY

# First you need to have a complete repository of GLPI
# Ex : svn co https://forge.indepnet.net/svn/glpi/trunk
# Your repository must be used only for locale update usage

VERSION="0.84"
LOG="/tmp/autoupdatelang$VERSION.log"

# Second you need to have transifex client installed 
# and link your repository to transifex
# tx set --execute --auto-local -r GLPI.glpipot084 'locales/<lang>.po' --source-lang en --source-file locales/glpi.pot
# GLPI is the project name and glpipot084 the slug name

TRANSIFEXPROJECT="GLPI"
SLUGNAME="glpipot084"

cat /dev/null > $LOG

date >> $LOG

echo "Update SVN repository" >> $LOG
svn update . >> $LOG

echo "Extract glpi.pot" >> $LOG
tools/locale/extract_template.sh >> $LOG


echo "Push template to transifex" >> $LOG
tx push -s >> $LOG

echo "Pull all po files" >> $LOG
tx pull -a >> $LOG

#echo "Pull en_GB po files" >> $LOG
#tx pull -l en_GB >> $LOG

echo "Update mo files" >> $LOG
tools/locale/update_mo.pl >> $LOG

echo "Update SVN repository " >> $LOG
svn update  >> $LOG

echo "Add new po and mo " >> $LOG
svn add locales/*.po locales/*.mo >> $LOG

echo "commit locales SVN repository " >> $LOG
svn commit locales  -m "Auto update locales" >> $LOG
