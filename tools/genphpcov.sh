#!/bin/sh

cd $(dirname $0)/..
if ! which phpunit &>/dev/null
then
   echo -e "\nphpunit not found, see https://phpunit.de/\n"
   exit 1

elif which phpdbg &>/dev/null
then
   # PHP 7 + phpdbg, faster
   phpdbg -qrr $(which phpunit) \
          -d memory_limit=1G \
          --coverage-html tools/htmlcov \
          --whitelist inc \
          --verbose
elif php -m | grep -qi xdebug
then
   # PHP 5 + xdebug
   phpunit -d memory_limit=1G \
           --coverage-html tools/htmlcov \
           --whitelist inc \
           --verbose
else
   echo -e "\nYou need PHP 7 with phpdng or PHP with XDebug\n"
   exit 2
fi

echo "Result in file://$PWD/tools/htmlcov/index.html";

