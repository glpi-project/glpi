#!/bin/bash
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

soft='GLPI'
version='0.84'
email=glpi-translation@gna.org
copyright='INDEPNET Development Team'

#xgettext *.php */*.php -copyright-holder='$copyright' --package-name=$soft --package-version=$version --msgid-bugs-address=$email -o locales/en_GB.po -L PHP --from-code=UTF-8 --force-po  -i --keyword=_n:1,2 --keyword=__ --keyword=_e

xgettext *.php */*.php -o locales/glpi.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po \
    --keyword=_n:1,2 --keyword=__s --keyword=__ --keyword=_e --keyword=_x:1c,2 --keyword=_ex:1c,2 --keyword=_sx:1c,2 --keyword=_nx:1c,2,3 --keyword=_sn:1,2


### for using tx :
##tx set --execute --auto-local -r GLPI.glpipot 'locales/<lang>.po' --source-lang en_GB --source-file locales/glpi.pot
## tx push -s
## tx pull -a


