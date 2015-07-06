#!/bin/bash

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


