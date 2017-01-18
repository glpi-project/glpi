#!/bin/bash

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

