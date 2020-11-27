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

# Clean existing file
rm -f locales/glpi.pot && touch locales/glpi.pot

# Append locales from PHP
xgettext `find ./ajax ./config ./front ./inc ./install ./tests ./*.php -type f -name "*.php"` -o locales/glpi.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing \
    --keyword=_n:1,2 --keyword=__s --keyword=__ --keyword=_x:1c,2 --keyword=_sx:1c,2 --keyword=_nx:1c,2,3 --keyword=_sn:1,2

# Append locales from JavaScript
xgettext *.js js/*.js -o locales/glpi.pot -L JavaScript --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing \
    --keyword=_n:1,2 --keyword=__ --keyword=_x:1c,2 --keyword=_nx:1c,2,3 \
    --keyword=i18n._n:1,2 --keyword=i18n.__ --keyword=i18n._p:1c,2 \
    --keyword=i18n.ngettext:1,2 --keyword=i18n.gettext --keyword=i18n.pgettext:1c,2

# Append locales from Twig templates
for file in $(find ./templates -type f -name "*.twig")
do
    # 1. Convert file content to replace "{{ function(.*) }}" by "<?php function(.*); ?>" and extract strings via std input
    # 2. Replace "standard input:line_no" by file location in po file comments
    contents=`cat $file | sed -r "s|\{\{\s*([a-z0-9_]+\(.*\))\s*\}\}|<?php \1; ?>|gi"`
    cat $file | perl -0pe "s/\{\{(.*?)\}\}/<?php \1; ?>/gism" | xgettext - -o locales/glpi.pot -L PHP --add-comments=TRANS --from-code=UTF-8 --force-po --join-existing \
        --keyword=_n:1,2 --keyword=__ --keyword=_x:1c,2 --keyword=_nx:1c,2,3
    sed -i -r "s|standard input:([0-9]+)|`echo $file | sed "s|./||"`:\1|g" locales/glpi.pot
done

#Update main language
LANG=C msginit --no-translator -i locales/glpi.pot -l en_GB -o locales/en_GB.po

### for using tx :
##tx set --execute --auto-local -r GLPI.glpipot 'locales/<lang>.po' --source-lang en_GB --source-file locales/glpi.pot
## tx push -s
## tx pull -a


