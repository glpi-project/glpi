#!/bin/bash
# check tat an error in update scripts does return a non zero code
testfail() {
   sed -e "s/\$migration->setVersion('9.3');/\0\n\$DB->queryOrDie('SELECT * FROM notatable');/" \
      -i install/update_92_93.php
   php scripts/cliupdate.php --config-dir=../tests --dev
   RETVAL=$?
   if [ $RETVAL -ne 0 ]
   then
      return 0
   else
      return 1
   fi
}
testfail
