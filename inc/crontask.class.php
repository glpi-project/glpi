<?php

/*
 * @version $Id: bookmark.class.php 8095 2009-03-19 18:27:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * CronTask class
 */
class CronTask extends CommonDBTM{

   static private $lockname='';
   private $timer=0.0;
   private $startlog=0;
   private $volume=0;

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_crontasks";
      $this->type=CRONTASK_TYPE;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['Menu'][13]; // Stat
      $ong[2]=$LANG['Menu'][30]; // Logs

      return $ong;
   }

   function cleanDBonPurge($id) {
      global $DB;

      $query = "DELETE
                FROM `glpi_crontaskslogs`
                WHERE `crontasks_id` = '$id'";
      $result = $DB->query($query);
   }

   /**
    * Read a Crontask by its name
    *
    *@param $name name of the task
    *@param $plugin name of the plugin
    *
    *@return true if succeed else false
    *
    */
   function getFromDBbyName($name, $plugin=NULL) {
      global $DB;

      $query = "SELECT * FROM `".$this->table."`
                WHERE `name`='$name'";
      if ($plugin) {
         $query .= " AND `plugin`='$plugin'";
      } else {
         $query .= " AND `plugin` IS NULL";
      }

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) != 1) {
            return false;
         }
         $this->fields = $DB->fetch_assoc($result);
         if (is_array($this->fields) && count($this->fields)) {
            return true;
         }
         return false;
      }
      return false;
   }

   /**
    * Give a task state
    *
    * @return interger 0 : task is enabled
    *    if disable : 1: by config, 2: by system lock, 3: by plugin
    *
    */
   function isDisabled () {
      if ($this->fields['state']==CRONTASK_STATE_DISABLE) {
         return 1;
      }
      if (is_file(GLPI_CRON_DIR. '/all.lock')
         || is_file(GLPI_CRON_DIR. '/'.$this->fields['name'].'.lock')) {
         // Global lock
         return 2;
      }
      if (empty($this->fields['plugin'])) {
         return 0;
      }

      // Plugin case
      $plug = new Plugin();
      if (!$plug->isActivated($this->fields["plugin"])) {
         return 3;
      }
      return 0;
   }
   /**
    * Start a task, timer, stat, log, ...
    *
    * @return bool : true if ok (not start by another)
    */
   function start() {
      global $DB;

      if (!isset($this->fields['id'])) {
         return false;
      }
      $query = "UPDATE `".$this->table."`
                SET `state`='".CRONTASK_STATE_RUNNING."', `lastrun`=NOW()
                WHERE `id`='".$this->fields['id']."'
                  AND `state`='".CRONTASK_STATE_WAITING."'";
      $result = $DB->query($query);
      if ($DB->affected_rows($result)>0) {
         $this->timer = microtime(true);
         $log = new CronTaskLog();
         $this->startlog = $log->add(array(
            'crontasks_id' => $this->fields['id'],
            'date' => $_SESSION['glpi_currenttime'],
            'content' => $this->getModeName(isCommandLine() ? CRONTASK_MODE_EXTERNAL
                                                            : CRONTASK_MODE_INTERNAL),
            'crontaskslogs_id' => 0,
            'state' => CRONTASKLOG_STATE_START,
            'volume' => 0,
            'elapsed' => 0
            ));
         return true;
      }
      return false;
   }

   /**
    * Set the currently proccessed volume of a running task
    *
    * @param $volume
    */
   function setVolume ($volume) {
      $this->volume = $volume;
   }

   /**
    * Increase the currently proccessed volume of a running task
    *
    * @param $volume
    */
   function addVolume ($volume) {
      $this->volume += $volume;
   }

   /**
    * Start a task, timer, stat, log, ...
    *
    * @param $retcode : <0 : need to run again, 0:nothing to do, >0:ok
    *
    * @return bool : true if ok (not start by another)
    */
   function end($retcode) {
      global $LANG, $DB;

      if (!isset($this->fields['id'])) {
         return false;
      }
      $query = "UPDATE `".$this->table."`
                SET `state`='".CRONTASK_STATE_WAITING."',
                    `lastrun`=NOW()
                WHERE `id`='".$this->fields['id']."'
                  AND `state`='".CRONTASK_STATE_RUNNING."'";
      $result = $DB->query($query);
      if ($DB->affected_rows($result)>0) {
         if ($retcode < 0) {
            $content = $LANG['crontask'][44]; // Partial
         } else if ($retcode > 0) {
            $content = $LANG['crontask'][45]; // Complete
         } else {
            $content = $LANG['crontask'][46]; // Nothing to do
         }
         $log = new CronTaskLog();
         $log->add(array(
            'crontasks_id' => $this->fields['id'],
            'date' => $_SESSION['glpi_currenttime'],
            'content' => $content,
            'crontaskslogs_id' => $this->startlog,
            'state' => CRONTASKLOG_STATE_STOP,
            'volume' => $this->volume,
            'elapsed' => (microtime(true)-$this->timer)
            ));
         return true;
      }
      return false;
   }

   /**
    * Add a log message for a running task
    *
    * @param $content
    *
    */
   function log($content) {
      global $LANG;

      if (!isset($this->fields['id'])) {
         return false;
      }
      $log = new CronTaskLog();
      return $log->add(array(
            'crontasks_id' => $this->fields['id'],
            'date' => $_SESSION['glpi_currenttime'],
            'content' => $content,
            'crontaskslogs_id' => $this->startlog,
            'state' => CRONTASKLOG_STATE_RUN,
            'volume' => $this->volume,
            'elapsed' => (microtime(true)-$this->timer)
            ));
   }

   /**
    * read the first task which need to be run by cron
    *
    * @param $mode : allow retrieve task for this mode
    * @param $name : one specify action
    *
    * @return false if no task to run
    */
   function getNeedToRun($mode=0, $name='') {
      global $DB;

      $hour=date('H');
      $query = "SELECT * FROM `".$this->table."`
         WHERE `state`='".CRONTASK_STATE_WAITING."'
           AND (`plugin` IS NULL";

      if (count($_SESSION['glpi_plugins'])) {
         $query .= " OR `plugin` IN ('".implode("','", $_SESSION['glpi_plugins'])."'))";
      } else {
         $query .= ')';
      }
      if ($mode) {
         $query .= " AND `mode`='$mode' ";
      }
      if ($name) {
         $query .= " AND `name`='".addslashes($name)."' ";
      }

      // Get system lock
      if (is_file(GLPI_CRON_DIR. '/all.lock')) {
         // Global lock
         return false;
      }
      $locks=array();
      foreach(glob(GLPI_CRON_DIR. '/*.lock') as $lock) {
         if (preg_match('!.*/(.*).lock$!', $lock, $reg)) {
            $locks[]=$reg[1];
         }
      }
      if (count($locks)) {
         $lock = "AND `name` NOT IN ('".implode("','",$locks)."')";
      } else {
         $lock = '';
      }

      // Build query for frequency and allowed hour
      $query .= " AND ((`hourmin`<`hourmax` AND  '$hour'>=`hourmin` AND '$hour'<`hourmax`)
                    OR (`hourmin`>`hourmax` AND ('$hour'>=`hourmin` OR  '$hour'<`hourmax`)))
                  AND (`lastrun` IS NULL
                    OR unix_timestamp(`lastrun`)+`frequency`<unix_timestamp(now()))
                  $lock
                ORDER BY `plugin`, unix_timestamp(`lastrun`)+`frequency`";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $this->fields = $DB->fetch_assoc($result);
            return true;
         }
      }
      return false;;
   }

   /**
    * Print the contact form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {

      global $CFG_GLPI, $LANG;

      if (!haveRight("config","r") || !$this->getFromDB($ID)) {
         return false;
      }

      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]." : </td>";
      echo "<td><strong>";
      if (!empty($this->fields["plugin"])) {
         echo $this->fields["plugin"]." - ";
      }
      echo $this->fields["name"]."</strong></td>";
      echo "<td rowspan='6' class='middle right'>".$LANG['common'][25].
         "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='6'>.<textarea cols='45' ".
         "rows='8' name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][30]." : </td><td>";
      echo $this->getDescription($ID,$this->fields["plugin"],$this->fields["name"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][37]." : </td><td>";
      dropdownFrequency('frequency',$this->fields["frequency"]);
      echo "</td></tr>";

      $tmpstate = $this->fields["state"];
      echo "<tr class='tab_bg_1'><td>".$LANG['joblist'][0]." : </td><td>";
      if (is_file(GLPI_CRON_DIR. '/'.$this->fields["name"].'.lock')
          || is_file(GLPI_CRON_DIR. '/all.lock')) {
         echo "<strong>" . $LANG['crontask'][60]."</strong><br>";
         $tmpstate = CRONTASK_STATE_DISABLE;
      }
      if (!empty($this->fields["plugin"])) {
         $plug = new Plugin();
         if (!$plug->isActivated($this->fields["plugin"])) {
            echo "<strong>" . $LANG['crontask'][61]."</strong><br>";
            $tmpstate = CRONTASK_STATE_DISABLE;
         }
      }
      if ($this->fields["state"]==CRONTASK_STATE_RUNNING) {
         echo "<strong>" . $this->getStateName(CRONTASK_STATE_RUNNING)."</strong>";
      } else {
         dropdownArrayValues('state',
            array(CRONTASK_STATE_DISABLE=>$this->getStateName(CRONTASK_STATE_DISABLE),
                  CRONTASK_STATE_WAITING=>$this->getStateName(CRONTASK_STATE_WAITING)),
            $this->fields["state"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][36]." : </td><td>";
      $modes=array();
      if ($this->fields['allowmode']&CRONTASK_MODE_INTERNAL) {
         $modes[CRONTASK_MODE_INTERNAL]=$this->getModeName(CRONTASK_MODE_INTERNAL);
      }
      if ($this->fields['allowmode']&CRONTASK_MODE_EXTERNAL) {
         $modes[CRONTASK_MODE_EXTERNAL]=$this->getModeName(CRONTASK_MODE_EXTERNAL);
      }
      dropdownArrayValues('mode', $modes, $this->fields['mode']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][38]." : </td><td>";
      dropdownInteger('hourmin', $this->fields['hourmin'],0,24);
      echo "&nbsp;->&nbsp;";
      dropdownInteger('hourmax', $this->fields['hourmax'],0,24);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['setup'][109]." : </td><td>";
      dropdownInteger('logs_lifetime', $this->fields['logs_lifetime'],0,360,10);
      echo "&nbsp;".$LANG['stats'][31]."</td><td>".$LANG['crontask'][40]."&nbsp;:</td><td>";
      if (empty($this->fields['lastrun'])) {
         echo $LANG['setup'][307];
      } else {
         echo convDateTime($this->fields['lastrun']);
         echo " <a href='$target?id=$ID&amp;resetdate=1'><img src='".GLPI_ROOT."/pics/reset.png' ";
         echo " alt='".$LANG['buttons'][16]."' title='".$LANG['buttons'][16]."'></a>";
      }
      echo "</td></tr>";

      $label = $this->getParameterDescription($ID,$this->fields["plugin"],$this->fields["name"]);
      echo "<tr class='tab_bg_1'><td>";
      if (empty($label)) {
         echo "&nbsp;</td><td>&nbsp;";
      } else {
         echo $label."&nbsp;:</td><td>";
         dropdownInteger('param', $this->fields['param'],0,400,1);
      }
      echo "</td><td>".$LANG['crontask'][41]."&nbsp;:</td><td>";
      $launch=false;
      if ($tmpstate!=CRONTASK_STATE_WAITING) {
         echo $this->getStateName($tmpstate);
      } else if (empty($this->fields['lastrun'])) {
         echo $LANG['crontask'][42];
         $launch=true;
      } else {
         $next = strtotime($this->fields['lastrun'])+$this->fields['frequency'];
         $h=date('H',$next);
         $deb=($this->fields['hourmin'] < 10 ? "0".$this->fields['hourmin'] : $this->fields['hourmin']);
         $fin=($this->fields['hourmax'] < 10 ? "0".$this->fields['hourmax'] : $this->fields['hourmax']);
         if ($deb<$fin && $h<$deb) {
            $disp = date('Y-m-d', $next). " $deb:00:00";
            $next = strtotime($disp);
         } else if ($deb<$fin && $h>=$this->fields['hourmax']) {
            $disp = date('Y-m-d', $next+DAY_TIMESTAMP). " $deb:00:00";
            $next = strtotime($disp);
         } if ($deb>$fin && $h<$deb && $h>=$fin) {
            $disp = date('Y-m-d', $next). " $deb:00:00";
            $next = strtotime($disp);
         } else {
            $disp = date("Y-m-d H:i:s", $next);
         }
         if ($next<time()) {
            echo $LANG['crontask'][42].' ('.convDateTime($disp).') ';
            $launch=true;
         } else {
            echo convDateTime($disp);
         }
      }
      if ($launch) {
         echo " - <a href='".GLPI_ROOT."/front/crontask.php?execute=".$this->fields["name"]."'>";
         echo $LANG['buttons'][57]."</a>";
      }
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2,false);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   /**
    * reset the next launch date => for a launch as soon as possible
    *
    */
   function resetDate () {
      global $DB;

      if (!isset($this->fields['id'])) {
         return false;
      }
      return $this->update(array(
            'id'=> $this->fields['id'],
            'lastrun'=>'NULL'));
   }

   /**
    * Translate task description
    *
    * @param $id integer
    * @param $plugin string : name of plugin (empty for glpi core task)
    * @param $name string : name of the task
    * @return string
    */
   static public function getDescription($id, $plugin, $name) {
      global $LANG;

      if (empty($plugin)) {
         if ($id>=1 && $id<=12) {
            return $LANG['crontask'][$id];
         }
         return $LANG['crontask'][30].' '.$id;
      }

      // Plugin case
      loadPluginLang($plugin);
      $info = doOneHook($plugin, "cron_info", $name);
      if (isset($info['description'])) {
         return $info['description'];
      }
      return "$plugin / $name";
   }

   /**
    * Translate task parameter description
    *
    * @param $id integer
    * @param $plugin string : name of plugin (empty for glpi core task)
    * @param $name string : name of the task
    * @return string
    */
   static public function getParameterDescription($id, $plugin, $name) {
      global $LANG;

      if (empty($plugin)) {
         switch ($id) {
            /* TODO set this global ? (no other way to active/inactive server)
            case 1: // ocsng
               return $LANG['ocsconfig'][40];
               break;
            */
            case 7: // events
               return $LANG['setup'][109];
               break;
            case 9: // mailgate
               return $LANG['crontask'][39];
               break;
            case 10: // dbreplicate
               return $LANG['setup'][806];
               break;
         }
         // No parameter
         return '';
      }

      // Plugin case
      loadPluginLang($plugin);
      $info = doOneHook($plugin, "cron_info", $name);
      if (isset($info['parameter'])) {
         return $info['parameter'];
      }
      return '';
   }

   /**
    * Translate state to string
    *
    * @param $state integer
    * @return string
    */
   static public function getStateName($state) {
      global $LANG;

      switch ($state) {
         case CRONTASK_STATE_RUNNING:
            return $LANG['crontask'][33];
            break;
         case CRONTASK_STATE_WAITING:
            return $LANG['crontask'][32];
            break;
         case CRONTASK_STATE_DISABLE:
            return $LANG['crontask'][31];
            break;
      }
      return '???';
   }

   /**
    * Translate Mode to string
    *
    * @param $mode integer
    * @return string
    */
   static public function getModeName($mode) {
      global $LANG;

      switch ($mode) {
         case CRONTASK_MODE_INTERNAL:
            return $LANG['crontask'][34];
            break;
         case CRONTASK_MODE_EXTERNAL:
            return $LANG['crontask'][35];
            break;
      }
      return '???';
   }

   /**
    * Get a global database lock
    */
   static private function get_lock() {
      global $DB;

      // Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
      $nom = $DB->dbdefault . ".glpicron." . intval(time()/HOUR_TIMESTAMP-340000);

      $nom = addslashes($nom);
      $query = "SELECT GET_LOCK('$nom', 0)";
      $result = $DB->query($query);
      list($lock_ok) = $DB->fetch_array($result);
      if ($lock_ok) {
         self::$lockname = $nom;
      }
      return $lock_ok;
   }

   /**
    * Release the global database lock
    */
   static private function release_lock() {
      global $DB;

      if (self::$lockname) {
         $nom = self::$lockname;
         $query = "SELECT RELEASE_LOCK('$nom')";
         $result = $DB->query($query);
      }
   }

   /**
    * Launch the need cron tasks
    *
    * @param $mode (internal/external)
    * @param $max number of task to launch ()
    * @param $name of task to run
    *
    * @return the name of last task launched
    */
   static public function launch($mode, $max=1, $name='') {

      $taskname='';

      if (CronTask::get_lock()) {
         if (isset($_SESSION["glpiID"])) {
            $saveglpiid=$_SESSION["glpiID"];
         }
         $task = new CronTask();
         for ($i=1 ; $i<=$max ; $i++) {
            $prefix = ($mode==CRONTASK_MODE_EXTERNAL ? 'External' : 'Internal')." #$i: ";
            if ($task->getNeedToRun($mode, $name)) {
               $_SESSION["glpiID"]="cron_".$task->fields['name'];

               if (empty($task->fields['plugin'])) {
                  $fonction = 'cron_' . $task->fields['name'];
                  if (!function_exists($fonction)) {
                     // pas trouvé de fonction -> inclusion de la fonction
                     if (file_exists(GLPI_ROOT.'/inc/'.$task->fields['name'].'.function.php')) {
                        include_once(GLPI_ROOT.'/inc/'.$task->fields['name'].'.function.php');
                     }
                     if (file_exists(GLPI_ROOT.'/inc/'.$task->fields['name'].'.class.php')) {
                        include_once(GLPI_ROOT.'/inc/'.$task->fields['name'].'.class.php');
                     }
                  }
               } else {
                  // Plugin case / Load hook
                  usePlugin($task->fields['plugin'],true);
                  $fonction = 'plugin_'.$task->fields['plugin'].'_cron_' . $task->fields['name'].'_run';
               }
               if (function_exists($fonction)) {
                  if ($task->start()) { // Lock in DB + log start
                     $taskname = $task->fields['name'];
                     logInFile('cron', $prefix."Launch ".$task->fields['name']."\n");
                     $retcode = $fonction($task);
                     $task->end($retcode); // Unlock in DB + log end
                  } else {
                     logInFile('cron', $prefix."Can't start ".$task->fields['name']."\n");
                  }

               } else {
                  logInFile('php-errors', "Undefined function '$fonction' (for cron)\n");
                  logInFile('cron', $prefix."Can't start ".$task->fields['name'].
                     "\nUndefined function '$fonction'\n");
                  // TODO disable task ??
               }
            } else if ($i==1) {
               logInFile('cron', $prefix."Nothing to launch\n");
            }
         } // end for

         if (empty($saveglpiid)) {
            unset($_SESSION["glpiID"]);
         } else {
            $_SESSION["glpiID"]=$saveglpiid;
         }

         CronTask::release_lock();
      } else {
         logInFile('cron', "Can't get DB lock'\n");
      }

      return $taskname;
   }

   /**
    * Register new task for plugin (called by plugin during install)
    *
    * @param $plugin : name of the plugin
    * @param $name : of the task
    * @param $frequency : of execution
    * @param $array of optional options
    *       (state, mode, allowmode, hourmin, hourmax, logs_lifetime, param, comment)
    *
    * @return bool for success
    */
   static public function Register($plugin, $name, $frequency, $options=array()) {

      // Check that hook exists
      if (!function_exists("plugin_${plugin}_cron_${name}_run")
          || !function_exists("plugin_${plugin}_cron_info")) {
         return false;
      }
      $input = array (
         'plugin' => $plugin,
         'name' => $name,
         'frequency' => $frequency
      );
      foreach (array ('state', 'mode', 'allowmode', 'hourmin', 'hourmax',
                      'logs_lifetime', 'param', 'comment') as $key) {
         if (isset ($options[$key])) {
            $input[$key] = $options[$key];
         }
      }
      $temp = new CronTask();
      return $temp->add($input);
   }

   /**
    * Unregister tasks for a plugin (call by glpi after uninstall)
    *
    * @param $plugin : name of the plugin
    *
    * @return bool for success
    */
   static public function Unregister($plugin) {
      global $DB;

      if (empty($plugin)) {
         return false;
      }
      $temp = new CronTask();
      $ret = true;
      foreach ($DB->request('glpi_crontasks', array('plugin'=>$plugin)) as $data) {
         if (!$temp->delete($data)) {
            $ret = false;
         }
      }
      return $ret;
   }
   /**
   * Display statistics of a task
   *
   * @param $ID : crontasks_id
   */
   function showStatistics($ID) {
      global $DB, $CFG_GLPI, $LANG;

      echo "<br><div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th colspan='2'>&nbsp;".$LANG['Menu'][13]."&nbsp;</th>"; // Date
      echo "</tr>\n";

      $nbstart = countElementsInTable('glpi_crontaskslogs',
            "`crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_START."'");
      $nbstop = countElementsInTable('glpi_crontaskslogs',
            "`crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'");

      echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][50]."&nbsp;:</td><td class='right'>";
      if ($nbstart==$nbstop) {
         echo $nbstart;
      } else {
         // This should not appen => task crash ?
         echo $LANG['crontask'][48]." = $nbstart<br>".$LANG['crontask'][49]." = $nbstop";
      }
      echo "</td></tr>";

      if ($nbstop) {
         $query = "SELECT MIN(`date`) AS datemin,
                     MIN(`elapsed`) AS elapsedmin,
                     MAX(`elapsed`) AS elapsedmax,
                     AVG(`elapsed`) AS elapsedavg,
                     SUM(`elapsed`) AS elapsedtot,
                     MIN(`volume`) AS volmin,
                     MAX(`volume`) AS volmax,
                     AVG(`volume`) AS volavg,
                     SUM(`volume`) AS voltot
                  FROM `glpi_crontaskslogs`
                  WHERE `crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'";
         $result = $DB->query($query);
         if ($data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'><td>".$LANG['search'][8]."&nbsp;:</td>";
            echo "<td class='right'>".convDateTime($data['datemin'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][51]."&nbsp;:</td>";
            echo "<td class='right'>".number_format($data['elapsedmin'],2)." ".$LANG['stats'][34]."</td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][52]."&nbsp;:</td>";
            echo "<td class='right'>".number_format($data['elapsedmax'],2)." ".$LANG['stats'][34]."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][53]."&nbsp;:</td>";
            echo "<td class='right'><strong>".number_format($data['elapsedavg'],2)." ".$LANG['stats'][34]."</strong></td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][54]."&nbsp;:</td>";
            echo "<td class='right'>".number_format($data['elapsedtot'],2)." ".$LANG['stats'][34]."</td></tr>";
         }
         if ($data && $data['voltot']>0) {
            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][55]."&nbsp;:</td>";
            echo "<td class='right'>".$data['volmin']." ".$LANG['crontask'][62]."</td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][56]."&nbsp;:</td>";
            echo "<td class='right'>".$data['volmax']." ".$LANG['crontask'][62]."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][57]."&nbsp;:</td>";
            echo "<td class='right'><strong>".number_format($data['volavg'],2)." ".$LANG['crontask'][62]."</strong></td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][58]."&nbsp;:</td>";
            echo "<td class='right'>".$data['voltot']." ".$LANG['crontask'][62]."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][59]."&nbsp;:</td>";
            echo "<td class='left'>".number_format($data['voltot']/$data['elapsedtot'],2)." ".$LANG['crontask'][62]." / ".$LANG['stats'][34]."</td></tr>";
         }
      }
      echo "</table></div>";
   }


   /**
   * Display list of a runned tasks
   *
   * @param $ID : crontasks_id
   */
   function showHistory($ID) {
      global $DB, $CFG_GLPI, $LANG;

      if (isset($_REQUEST["crontaskslogs_id"]) && $_REQUEST["crontaskslogs_id"]) {
         return $this->showHistoryDetail($ID,$_REQUEST["crontaskslogs_id"]);
      }

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }

      // Total Number of events
      $number = countElementsInTable('glpi_crontaskslogs',
            "`crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'");

      echo "<br><div class='center'>";
      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['search'][15]."</th></tr>";
         echo "</table>";
         echo "</div>";
         return;
      }

      // Display the pager
      printAjaxPager($LANG['crontask'][47],$start,$number);

      $query = "SELECT *
         FROM `glpi_crontaskslogs`
         WHERE `crontasks_id`='$ID' AND `state`='".CRONTASKLOG_STATE_STOP."'
         ORDER BY `id` DESC
         LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      if ($result=$DB->query($query)){
         if ($data=$DB->fetch_assoc($result)){
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".$LANG['common'][27]."</th>"; // Date
            echo "<th>".$LANG['job'][20]."</th>"; // Duration
            echo "<th>".$LANG['tracking'][29]."</th>"; // Number
            echo "<th>".$LANG['crontask'][30]."</th>"; // Dexcription
            echo "</tr>\n";

            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td><a href='javascript:reloadTab(\"crontaskslogs_id=".
                  $data['crontaskslogs_id']."\");'>".$data['date']."</a></td>";
               echo "<td class='right'>".number_format($data['elapsed'],3)."s</td>";
               echo "<td class='right'>".$data['volume']."</td>";
               echo "<td>".$data['content']."</td>";
               echo "</tr>\n";
            } while ($data=$DB->fetch_assoc($result));

            echo "</table>";

         } else { // Not found
            echo $LANG['search'][15];
         }
      } // Query
      echo "</div>";
   }
   /**
   * Display detail of a runned task
   *
   * @param $ID : crontasks_id
   * @param $logid : crontaskslogs_id
   */
   function showHistoryDetail($ID,$logid) {
      global $DB, $CFG_GLPI, $LANG;

      echo "<br><div class='center'>";
      echo "<p><a href='javascript:reloadTab(\"crontaskslogs_id=0\");'>".$LANG['crontask'][47]."</a></p>";

      $query = "SELECT *
         FROM `glpi_crontaskslogs`
         WHERE `id`='$logid' OR `crontaskslogs_id`='$logid'
         ORDER BY `id` ASC";

      if ($result=$DB->query($query)){
         if ($data=$DB->fetch_assoc($result)){
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".$LANG['common'][27]."</th>"; // Date
            echo "<th>".$LANG['joblist'][0]."</th>"; // statut
            echo "<th>".$LANG['job'][31]."</th>"; // Duration
            echo "<th>".$LANG['tracking'][29]."</th>"; // Number
            echo "<th>".$LANG['crontask'][30]."</th>"; // Dexcription
            echo "</tr>\n";

            $first=true;
            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td class='center'>".($first ? $data['date'] : "&nbsp;")."</a></td>";
               switch ($data['state']) {
                  case CRONTASKLOG_STATE_START:
                     echo "<td>".$LANG['crontask'][48]."</td>";
                     break;
                  case CRONTASKLOG_STATE_STOP:
                     echo "<td>".$LANG['crontask'][49]."</td>";
                     break;
                  default:
                     echo "<td>".$LANG['crontask'][33]."</td>";
               }
               echo "<td class='right'>".number_format($data['elapsed'],3)."s</td>";
               echo "<td class='right'>".$data['volume']."</td>";
               echo "<td>".$data['content']."</td>";
               echo "</tr>\n";
               $first=false;
            } while ($data=$DB->fetch_assoc($result));

            echo "</table>";

         } else { // Not found
            echo $LANG['search'][15];
         }
      } // Query

      echo "</div>";
   }
}

/**
 * CronTaskLog class
 */
class CronTaskLog extends CommonDBTM{

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_crontaskslogs";
      $this->type=CRONTASKLOG_TYPE;
   }
}
?>