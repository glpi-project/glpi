#!/bin/sh

cd $(dirname $0)

if which apigen &>/dev/null
then
	version=$(php -r '
		require __DIR__ . "/../config/define.php";
		echo GLPI_VERSION;
	')
	apigen generate \
		--todo \
		--deprecated \
		--tree \
		--title "GLPI version $version API" \
		--source ../inc \
		--destination api

else
	echo -e "\nApiGen not found, see http://www.apigen.org/\n"

fi
