<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
* @since versin 0.83
*/

// Ensure current directory when run from crontab
chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if (isset($_SERVER['argv'])) {
   for ($i=1 ; $i<$_SERVER['argc'] ; $i++) {
      $it = explode("=",$_SERVER['argv'][$i], 2);
      $it[0] = preg_replace('/^--/', '', $it[0]);

      $_GET[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}
if (isset($_GET['help'])) {
   echo "Usage : php ldapsync.php --entity=<id> | --ldap=<id> [ others options ]\n";
   echo "Options values :\n";
   echo "\t--entity      only sync user of this entity\n";
   echo "\t--server      only sync user of entities attached to this server (ID or default)\n";
   echo "\t--profile     only sync user with this profile\n";
   echo "\t--process     number of process to launch, one per entity, GNU/Linux only\n";
   echo "\t--verbose     display a lot of information\n";
   echo "\t--mailentity  send a report to the entity administrator\n";
   echo "\t--mailadmin   send a report to the glpi administrator\n";
   echo "\t--limit       max entities to sync (for debug purpose)\n";
   exit (0);
}

$nbproc = (isset($_GET['process']) ? intval($_GET['process']) : 1);
if ($nbproc<1) {
   die("** Invalid number of process ($nbproc)\n");

} else if ($nbproc > 1
           && !(function_exists('pcntl_fork')
           && function_exists('posix_getpid'))) {
   die ("** Multi process need PCNTL and POSIX extension (GNU/Linux only)\n");
}


/**
 * @param $pid
 * @param $data
 * @param $server
 * @param $prof
 * @param $verb
 * @param $mail
**/
function syncEntity ($pid, $data, $server, $prof, $verb, $mail) {
   global $DB, $LANG, $CFG_GLPI;

   // Re-establish DB connexion - mandatory in each forked process
   if (!DBConnection::switchToMaster()) {
      echo " $pid: lost DB connection\n";
      return 0;
   }
   // Server from entity (if not given from option)
   if ($data['authldaps_id'] > 0) {
      $server = $data['authldaps_id'];
   }

   $entity = new Entity();
   if ($entity->getFromDB($id=$data['id'])) {
      $tps = microtime(true);
      if ($verb) {
         echo "  $pid: Synchonizing entity '".$entity->getField('completename')."' ($id, mail=$mail)\n";
      }

      $sql = "SELECT DISTINCT glpi_users.*
              FROM glpi_users
              INNER JOIN glpi_profiles_users
                  ON (glpi_profiles_users.users_id = glpi_users.id
                      AND glpi_profiles_users.entities_id = $id";
      if ($prof > 0) {
         $sql .= "    AND glpi_profiles_users.profiles_id = $prof";
      }
      $sql .= ")
               WHERE glpi_users.authtype = ".Auth::LDAP;

      if ($server > 0) {
         $sql .= " AND glpi_users.auths_id = $server";
      }

      $users   = array();
      $results = array(AuthLDAP::USER_IMPORTED     => 0,
                       AuthLDAP::USER_SYNCHRONIZED => 0,
                       AuthLDAP::USER_DELETED_LDAP => 0);

      $req = $DB->request($sql);
      $i   = 0;
      $nb  = $req->numrows();

      foreach ($req as $row) {
         $i++;

         $result = AuthLdap::ldapImportUserByServerId(array('method' => AuthLDAP::IDENTIFIER_LOGIN,
                                                            'value'  => $row['name']),
                                                      AuthLDAP::ACTION_SYNCHRONIZE,
                                                      $row['auths_id']);
         if ($result) {
            $results[$result['action']] += 1;
            $users[$row['id']]           = $row['name'];

            if ($result['action'] == AuthLDAP::USER_SYNCHRONIZED) {
               if ($verb) {
                  echo "  $pid: User '".$row['name']."' synchronized ($i/$nb)\n";
               }
            } else if ($verb) {
               echo "  $pid: User '".$row['name']."' deleted\n";
            }
         } else if ($verb) {
            echo "  $pid: Problem with LDAP for user '".$row['name']."'\n";
         }
      }
      $tps = microtime(true)-$tps;
      printf("  %d: Entity '%s' - Synchronized: %d, Deleted from LDAP: %d, Time: %.2f\"\n",
             $pid, $entity->getField('completename'), $results[AuthLDAP::USER_SYNCHRONIZED],
             $results[AuthLDAP::USER_DELETED_LDAP], $tps);

      if ($mail) {
         $report = '';
         $user = new User();
         foreach ($users as $id => $name) {
            if ($user->getFromDB($id)) {
               $logs = Log::getHistoryData($user, 0, $_SESSION['glpilist_limit'],
                                           "`date_mod`='".$_SESSION['glpi_currenttime']."'");
               if (count($logs)) {
                  $report .= "\n$name (". $user->getName() .")\n";
                  foreach ($logs as $log) {
                     $report .= "\t";
                     if ($log['field']) {
                        $report .= $log['field'].": ";
                     }
                     $report .= Html::clean($log['change'])."\n";
                  }
               }
            } else {
               $report .= "\n".$name."\n\t deleted\n";
            }
         }
         if ($report) {
            $report  = "Synchronization of already imported users\n ".
                       "Entité: " .$entity->getField('completename') . "\n ".
                       "Date: " . Html::convDateTime($_SESSION['glpi_currenttime']) . "\n " .
                       $report;
            $entdata = new Entity();
            $mmail   = new NotificationMail();
            $mmail->AddCustomHeader("Auto-Submitted: auto-generated");
            $mmail->From      = $CFG_GLPI["admin_email"];
            $mmail->FromName  = "GLPI";
            $mmail->Subject   = "[GLPI] LDAP directory link";
            $mmail->Body      = $report."\n--\n".$CFG_GLPI["mailing_signature"];

            if (($mail & 1)
                && $entdata->getFromDB($entity->getField('id'))
                && $entdata->fields['admin_email']) {
               $mmail->AddAddress($entdata->fields['admin_email']);
            } else {
               if (($mail & 1) && $verb) {
                  echo "  $pid: No address found for email entity\n";
               }
               $mail = ($mail & 2);
            }
            if (($mail & 2)
                && $CFG_GLPI['admin_email']) {
               $mmail->AddAddress($CFG_GLPI['admin_email']);
            } else {
               if (($mail & 2) && $verb) {
                  echo "  $pid: No address found for email admin\n";
               }
               $mail = ($mail & 1);
            }
            if ($mail) {
               if ($mmail->Send() && $verb) {
                  echo "  $pid: Report sent by email\n";
               }
            } else {
               echo "  $pid: Cannot send report (".$entity->getField('completename').") ".
                     "invalid address\n";
            }
         }
      }
      return ($results[AuthLDAP::USER_DELETED_LDAP] + $results[AuthLDAP::USER_SYNCHRONIZED]);
   }
   return 0;
}

include ('../inc/includes.php');

ini_set('display_errors',1);
restore_error_handler();

if (isset($_GET['verbose'])) {
   $verb = $_GET['verbose'];
} else {
   $verb = false;
}
$server = 0;
if (isset($_GET['entity'])) {
   $crit = array('id' => $_GET['entity']);

} else if (isset($_GET['server'])) {
   if (is_numeric($_GET['server'])) {
      $server = $_GET['server'];
      $crit   = array('authldaps_id' => $server);
   } else {
      $server = AuthLdap::getDefault();
      $crit   = array('authldaps_id' => array(0, $server));
      if ($verb) {
         printf("+ Use default LDAP server: %d\n", $server);
      }
   }
} else {
   die("** Entity or server option is mandatory\n");
}

if (isset($_GET['limit'])) {
   $crit['LIMIT'] = intval($_GET['limit']);
}

if (isset($_GET['profile'])) {
   $prof = intval($_GET['profile']);
} else {
   $prof = 0;
}

$mail = 0;
if (isset($_GET['mailentity'])) {
   $mail |= 1;
}
if (isset($_GET['mailadmin'])) {
   $mail |= 2;
}

$tps  = microtime(true);
$nb   = 0;
$pids = array();

$rows = array();
foreach ($DB->request('glpi_entities', $crit) as $row) {
   $rows[] = $row;
}
if ($verb) {
   printf("+ %d entities to synchronize\n", count($rows));
}

// DB connection could not be shared with forked process
$DB->close();

foreach ($rows as $row) {
   if ($nbproc==1) {
      $nb += syncEntity(0, $row, $server, $prof, $verb, $mail);
      continue;
   }
   while (count($pids)>=$nbproc) {
      $pid=pcntl_wait($status);
      if ($pid < 0) {
         die ("** Could not wait\n");
      } else {
         $nb++;
         unset($pids[$pid]);
         if ($verb) {
            echo "- $pid: ended\n";
         }
      }
   }
   $pid = pcntl_fork();
   if ($pid < 0) {
      die("** Could not fork\n");
   } else if ($pid) {
      $pids[$pid] = $pid;
      if ($verb) {
         echo "+ $pid: started, ".count($pids)." running\n";
      }
   } else  {
      syncEntity(posix_getpid(), $row, $server, $prof, $verb, $mail);
      exit(0);
   }
}

while (count($pids) > 0) {
   $pid = pcntl_wait($status);
   if ($pid < 0) {
      die("** Cound not wait\n");
   } else {
      $nb++;
      unset($pids[$pid]);
      if ($verb) {
         echo "+ $pid: ended, waiting for " . count($pids) . " running process\n";
      }
   }
}

$tps = microtime(true)-$tps;
if ($nbproc == 1) {
   printf("%d users synchronized in %s\n", $nb,
          Html::clean(Html::timestampToString(round($tps,0),true)));
} else {
   printf("%d entities synchronized in %s\n", $nb,
          Html::clean(Html::timestampToString(round($tps,0),true)));
}
?>