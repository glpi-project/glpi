#!/bin/sh
LOCK_ROOT=/tmp/
lockfile -r0 $LOCK_ROOT/ocsng_full.lock

if [ $? -ne 0 ]
then
  echo "Script already running !!"
  exit 1
fi

php -d -f ocsng_fullsync.php >> ocsng_fullsync.log 

rm -f $LOCK_ROOT/ocsng_full.lock
