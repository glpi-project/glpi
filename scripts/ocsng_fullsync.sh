#!/bin/sh
pid_dir="/tmp"
pidfile="$pid_dir/ocsng_fullsync.pid"
runningpid=""
scriptname="ocsng_fullsync.sh"
glpiroot="/home/walid/sources/svn-glpi/glpi"

# Predefined settings
thread_nbr=4
server_id=

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

[ -d "$glpiroot/scripts" ] && cd "$glpiroot/scripts"

cpt=0

while [ $cpt -lt $thread_nbr ]; do 
  cpt=$(($cpt+1))
  cmd="php -d -f	ocsng_fullsync.php --ocs_server_id=$server_id --thread_nbr=$thread_nbr --thread_id=$cpt >> ocsng_fullsync.log"
  sh -c "$cmd"&
  runningpid="$runningpid $!"
  sleep 1
done

cleanup

