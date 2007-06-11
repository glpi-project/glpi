#!/bin/sh
cd $(dirname $0)

eval $(cat  <<EOF | php -q 
<?php 
define ("GLPI_ROOT","..");
include GLPI_ROOT."/config/based_config.php";
echo "GLPI_LOG_DIR=".GLPI_LOG_DIR."\n";
echo "GLPI_LOCK_DIR=".GLPI_LOCK_DIR."\n";
echo "GLPI_SCRIPT_DIR=".GLPI_SCRIPT_DIR."\n";
?>
EOF)

pid_dir="/tmp"
pidfile="$pid_dir/ocsng_fullsync.pid"
runningpid=""
scriptname="ocsng_fullsync.sh"
logfilename="ocsng_fullsync.log"


# Predefined settings
thread_nbr=4
server_id=1

trap cleanup 1 2 3 6

usage()
{
echo "Usage:"
echo "  $0 [--arg]"
echo
echo "Arguments:"
echo "  --thread_nbr=num: number of threads to launch"
echo "  --server_id=num: GLPI ID of the OCS server to synchronize from. Default is ALL the servers"


}

read_argv()
{
  for i in $@; do
    valname=`echo $i| sed 's/--\(.*\)=.*/\1/'`
    valcontent=`echo $i| sed 's/--.*=\(.*\)/\1/'`

    [ -z $valname ] && usage
    case "$valname" in
      thread_nbr)
      thread_nbr=$valcontent
      ;;
      server_id)
      server_id=$valcontent
      ;;
      *)
      usage
      ;;
    esac
  done

}

cleanup()
{
  echo "cleaning up."
  #  echo "kill pids: $runningpid"
  for pid in $runningpid; do kill $pid 2>/dev/null; done
  rm -f $pidfile
  rm -f "$GLPI_LOCK_DIR/lock_entity*"
  echo "Done cleanup ... quitting."
  exit 0
}

exit_if_already_running()
{
  # No pidfile, probably no daemon present
  #
  if [ ! -f $pidfile ]
  then
    return 1
  fi

  pid=`cat $pidfile`

  # No pid, probably no daemon present
  #
  if [ -z "$pid" ]
  then
    return 1
  fi

  if [ ! -d /proc/$pid ]
  then
    return 1
  fi

  cmd=`cat /proc/$pid/cmdline | grep $scriptname`

  if [ "$cmd" != "" ]
  then
    exit 1
  fi
}
exit_if_already_running
read_argv "$@"

echo $$ > $pidfile 

[ -d "$GLPI_SCRIPT_DIR" ] && cd "$GLPI_SCRIPT_DIR"

rm -f "$GLPI_LOCK_DIR/lock_entity*"
cpt=0

while [ $cpt -lt $thread_nbr ]; do 
  cpt=$(($cpt+1))
  cmd="php -d -f $GLPI_SCRIPT_DIR/ocsng_fullsync.php --ocs_server_id=$server_id --thread_nbr=$thread_nbr --thread_id=$cpt >> $GLPI_LOG_DIR/$logfilename"
  sh -c "$cmd"&
  runningpid="$runningpid $!"
  sleep 1
done

running=1
while [ $running = 1 ]; do
       running=0
       for pid in $runningpid; do
               [ -d "/proc/$pid" ] && running=1
       done
       sleep 1
done
cleanup

