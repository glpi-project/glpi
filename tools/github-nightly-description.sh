#!/bin/bash -eu

#
# ---------------------------------------------------------------------
#
# GLPI - Gestionnaire Libre de Parc Informatique
#
# http://glpi-project.org
#
# @copyright 2015-2025 Teclib' and contributors.
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

cat <<HEADER
---
layout: default
title: GLPI Nightly Builds
---

Version|Archive|Build date|Size
---|---|---|---
HEADER

for file in $*
do
    NAME="${file#glpi/}"
    VERSION="${NAME%-*.tar.gz}"
    SIZE=$( stat -c %s "$file" )
    read DATE TIME TZ <<<$(git log -n1 --pretty=%ci -- $file)
    [ "$TZ" == "+0000" ] && TZ="UTC"
    # Set current date if archive still not commited
    if [ -z "$DATE" ]; then
        DATE=$(date -u +"%F")
        TIME=$(date -u +"%T")
        TZ="UTC"
    fi
    cat <<DESCRIPTION
$VERSION|[$NAME]($NAME)|$DATE $TIME $TZ|$SIZE
DESCRIPTION
done

cat <<FOOTER

<font size="1">Page generated on $( date -u +'%F %H:%M:%S UTC' )</font>
FOOTER
