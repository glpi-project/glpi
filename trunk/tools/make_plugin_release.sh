#!/bin/bash
# -------------------------------------------------------------------------
# GLPI - Gestionnaire Libre de Parc Informatique
# Copyright (C) 2003-2014 by the INDEPNET Development Team
#
# http://indepnet.net/   http://glpi-project.org
# -------------------------------------------------------------------------
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
# ------------------------------------------------------------------------

if [ $# -lt 2 ]
then
 echo "Usage $0 plugin version [ tagname ]";
 exit ;
fi

tmpdir=$(mktemp --directory)
[ -d "$tmpdir" ] || exit 1

echo "Working in $tmpdir"

plg=$1
ver=$2
tag=${3:-$ver}
arc=glpi-$plg-$ver.tar.gz

echo "Getting $plg version $2 sources from SVN tag $tag"

if svn export -q https://forge.indepnet.net/svn/$plg/tags/$tag $tmpdir/$plg
then
	rm -rf $tmpdir/$plg/tools
	tar --create \
		--gzip \
		--file $arc \
		--directory $tmpdir \
		$plg && echo "Archive $arc created"
else
	echo "Aborting"
fi

rm -rf $tmpdir && echo "Tmp cleaned"

