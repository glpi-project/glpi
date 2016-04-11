#!/bin/bash

if [ ! -d vendor ]
then
	echo "Please run 'composer install --no-dev' first"
	exit 1
fi

echo "+ ircmaxell/password-compat"
SRC=vendor/ircmaxell/password-compat
DST=../lib/password_compat
cp $SRC/lib/*php   $DST
cp $SRC/LICENSE.md $DST

echo "+ zendframework/zend-*"

SRC=vendor/zendframework
DST=../lib/Zend
rsync -a -del $SRC/zend-cache/src/          $DST/Cache/
rsync -a -del $SRC/zend-eventmanager/src/   $DST/EventManager/
rsync -a -del $SRC/zend-i18n/src/           $DST/I18n/
rsync -a -del $SRC/zend-json/src/           $DST/Json/
rsync -a -del $SRC/zend-loader/src/         $DST/Loader/
rsync -a -del $SRC/zend-math/src/           $DST/Math/
rsync -a -del $SRC/zend-serializer/src/     $DST/Serializer/
rsync -a -del $SRC/zend-servicemanager/src/ $DST/ServiceManager/
rsync -a -del $SRC/zend-stdlib/src/         $DST/Stdlib/
rsync -a -del $SRC/zend-version/src/        $DST/Version/

echo "+ zetacomponents/**"

SRC=vendor/zetacomponents
DST=../lib/zeta
rsync -a -del $SRC/base/src/         $DST/Base/src/
rsync -a -del $SRC/graph/src/        $DST/Graph/src/
mv $DST/Base/src/base_autoload.php   $DST/autoload/
for fic in $DST/Base/*
do [ $(basename $fic) == src ] || cp $SRC/base/$(basename $fic) $fic
done
mv $DST/Graph/src/graph_autoload.php $DST/autoload/
for fic in $DST/Graph/*
do [ $(basename $fic) == src ] ||  cp $SRC/graph/$(basename $fic) $fic
done

echo "+ phpCas"

SRC="vendor/jasig/phpcas"
DST="../lib/phpcas"
find $SRC -type f -exec chmod -x {} \;
rsync -a -del $SRC/source/         $DST/
for fic in LICENSE NOTICE README.md
do cp $SRC/$fic $DST/$fic
done


echo "+ parsedown"

SRC="vendor/erusev/parsedown"
DST="../lib/parsedown"
cp $SRC/*php $DST
for fic in LICENSE.txt README.md
do cp $SRC/$fic $DST/$fic
done

echo "+ parsedown"

SRC="vendor/erusev/parsedown-extra"
DST="../lib/parsedown/parsedown-extra"
cp $SRC/*php $DST
for fic in LICENSE.txt README.md
do cp $SRC/$fic $DST/$fic
done

git status

echo -e "\nDone: you can now review the changes and commit\n"
