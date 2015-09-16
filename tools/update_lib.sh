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

git status

echo -e "\nDone: you can now review the changes and commit\n"
