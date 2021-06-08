#!/bin/bash -e
#
# ---------------------------------------------------------------------
# GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2015-2021 Teclib' and contributors.
#
# http://glpi-project.org
#
# based on GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2003-2014 by the INDEPNET Development Team.
#
# ---------------------------------------------------------------------
#
# LICENSE
#
# This file is part of GLPI.
#
# GLPI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# GLPI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with GLPI. If not, see <http://www.gnu.org/licenses/>.
# ---------------------------------------------------------------------
#

cat <<HEADER
---
layout: default
title: GLPI Nightly Builds
---

Built on $( date -u +'%F %H:%M:%S UTC' )

HEADER

for file in $*
do
    NAME="${file#glpi/}"
    BRANCH="${NAME%.*.tar.gz}"
    SIZE=$( stat -c %s "$file" )
    cat <<DESCRIPTION
## $BRANCH

Archive|Size
---|---
[$NAME]($NAME)|$SIZE

DESCRIPTION
done
