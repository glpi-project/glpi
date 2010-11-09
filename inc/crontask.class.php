<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

   // Specific ones
   static private $lockname='';
   private $timer=0.0;
   private $startlog=0;
   private $volume=0;

   // Class constant
   const STATE_DISABLE = 0;
   const STATE_WAITING = 1;
   const STATE_RUNNING = 2;

   const MODE_INTERNAL = 1;
   const MODE_EXTERNAL = 2;

   function defineTabs($options=array()) {
      global $LANG;

      $ong=array();
      $ong[1]=$LANG['Menu'][13]; // Stat
      $ong[2]=$LANG['Menu'][30]; // Logs

      return $ong;
   }

   function canCreate() {
      return haveRight('config','w');
   }

   function canView() {
      return haveRight('config','r');
   }

   function canDelete() {
      return false;
   }

   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_crontasklogs`
                WHERE `crontasks_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }

   /**
    * Read a Crontask by its name
    *
    * Used by plugins to load its crontasks
    *
    *@param $itemtype itemtype of the crontask
    *@param $name name of the task
    *
    *@return true if succeed else false
    *
    */
   function getFromDBbyName($itemtype, $name) {
      global $DB;

      $query = "SELECT * FROM `".$this->getTable()."`
                WHERE `name`='$name' AND `itemtype`='$itemtype'";

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
      if ($this->fields['state']==self::STATE_DISABLE) {
         return 1;
      }
      if (is_file(GLPI_CRON_DIR. '/all.lock')
         || is_file(GLPI_CRON_DIR. '/'.$this->fields['name'].'.lock')) {
         // Global lock
         return 2;
      }
      if (!$tab=isPluginItemType($this->fields['itemtype'])) {
         return 0;
      }

      // Plugin case
      $plug = new Plugin();
      if (!$plug->isActivated($tab["plugin"])) {
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
      global $DB, $LANG;

      if (!isset($this->fields['id']) || ($DB->isSlave())) {
         return false;
      }

      $query = "UPDATE `".$this->getTable()."`
                SET `state` = '".self::STATE_RUNNING."',
                    `lastrun` = NOW()
                WHERE `id` = '".$this->fields['id']."'
                      AND `state` != '".self::STATE_RUNNING."'";
      $result = $DB->query($query);

      if ($DB->affected_rows($result)>0) {
         $this->timer = microtime(true);
         $this->volume = 0;
         $log = new CronTaskLog();
         $txt = $LANG['crontask'][36] . " : " .
                $this->getModeName(isCommandLine() ? self::MODE_EXTERNAL : self::MODE_INTERNAL);
         $this->startlog = $log->add(array('crontasks_id' => $this->fields['id'],
                                           'date' => $_SESSION['glpi_currenttime'],
                                           'content' => addslashes($txt),
                                           'crontasklogs_id' => 0,
                                           'state' => CronTaskLog::STATE_START,
                                           'volume' => 0,
                                           'elapsed' => 0));
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
      $query = "UPDATE `".$this->getTable()."`
                SET `state` = '".$this->fields['state']."',
                    `lastrun` = NOW()
                WHERE `id` = '".$this->fields['id']."'
                      AND `state` = '".self::STATE_RUNNING."'";
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
         $log->add(array('crontasks_id'     => $this->fields['id'],
                         'date'             => $_SESSION['glpi_currenttime'],
                         'content'          => $content,
                         'crontasklogs_id' => $this->startlog,
                         'state'            => CronTaskLog::STATE_STOP,
                         'volume'           => $this->volume,
                         'elapsed'          => (microtime(true)-$this->timer)));
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
      $content = utf8_substr($content, 0, 200);
      return $log->add(array('crontasks_id'     => $this->fields['id'],
                             'date'             => $_SESSION['glpi_currenttime'],
                             'content'          => addslashes($content),
                             'crontasklogs_id' => $this->startlog,
                             'state'            => CronTaskLog::STATE_RUN,
                             'volume'           => $this->volume,
                             'elapsed'          => (microtime(true)-$this->timer)));
   }

   /**
    * read the first task which need to be run by cron
    *
    * @param $mode : >0 retrieve task configured for this mode
    *                <0 retrieve task allowed for this mode (force, no time check)
    * @param $name : one specify action
    *
    * @return false if no task to run
    */
   function getNeedToRun($mode=0, $name='') {
      global $DB;

      $hour=date('H');
      // First core ones
      $query = "SELECT *, LOCATE('Plugin',itemtype) as ISPLUGIN
                FROM `".$this->getTable()."`
                WHERE (`itemtype` NOT LIKE 'Plugin%'";

      if (count($_SESSION['glpi_plugins'])) {
         // Only activated plugins
         foreach ($_SESSION['glpi_plugins'] as $plug) {
            $query .= " OR `itemtype` LIKE 'Plugin$plug%'";
         }
      }
      $query .= ')';

      if ($name) {
         $query .= " AND `name`='".addslashes($name)."' ";
      }

      // In force mode
      if ($mode<0) {
         $query .= " AND `state`!='".self::STATE_RUNNING."'
                     AND (`allowmode` & ".(-intval($mode)).") ";
      } else {
         $query .= " AND `state`='".self::STATE_WAITING."'";
         if ($mode>0) {
            $query .= " AND `mode`='$mode' ";
         }
         // Get system lock
         if (is_file(GLPI_CRON_DIR. '/all.lock')) {
            // Global lock
            return false;
         }
         $locks = array();
         foreach (glob(GLPI_CRON_DIR. '/*.lock') as $lock) {
            if (preg_match('!.*/(.*).lock$!', $lock, $reg)) {
               $locks[] = $reg[1];
            }
         }
         if (count($locks)) {
            $lock = "AND `name` NOT IN ('".implode("','",$locks)."')";
         } else {
            $lock = '';
         }
         // Build query for frequency and allowed hour
         $query .= " AND ((`hourmin` < `hourmax` AND  '$hour' >= `hourmin` AND '$hour' < `hourmax`)
                          OR (`hourmin` > `hourmax`
                              AND ('$hour' >= `hourmin` OR '$hour' < `hourmax`)))
                     AND (`lastrun` IS NULL
                          OR unix_timestamp(`lastrun`) + `frequency` < unix_timestamp(now()))
                     $lock ";
      }
      // Core task before plugins
      $query .= "ORDER BY ISPLUGIN, unix_timestamp(`lastrun`)+`frequency`";

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
   * @param $ID integer ID of the item
   * @param $options array
   *     - target filename : where to go when done.
   *     - withtemplate boolean : template or basic item
   *
   *@return Nothing (display)
   *
   **/
   function showForm ($ID,$options=array()) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("config","r") || !$this->getFromDB($ID)) {
         return false;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]." : </td>";
      echo "<td><strong>";
      if ($isplug=isPluginItemType($this->fields["itemtype"])) {
         echo $isplug["plugin"]." - ";
      }
      echo $this->fields["name"]."</strong></td>";
      echo "<td rowspan='6' class='middle right'>".$LANG['common'][25].
         "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='6'>.<textarea cols='45' ".
         "rows='8' name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['joblist'][6]." : </td><td>";
      echo $this->getDescription($ID);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][37]." : </td><td>";
      $this->dropdownFrequency('frequency',$this->fields["frequency"]);
      echo "</td></tr>";

      $tmpstate = $this->fields["state"];
      echo "<tr class='tab_bg_1'><td>".$LANG['joblist'][0]." : </td><td>";
      if (is_file(GLPI_CRON_DIR. '/'.$this->fields["name"].'.lock')
          || is_file(GLPI_CRON_DIR. '/all.lock')) {
         echo "<strong>" . $LANG['crontask'][60]."</strong><br>";
         $tmpstate = self::STATE_DISABLE;
      }
      if ($isplug) {
         $plug = new Plugin();
         if (!$plug->isActivated($isplug["plugin"])) {
            echo "<strong>" . $LANG['crontask'][61]."</strong><br>";
            $tmpstate = self::STATE_DISABLE;
         }
      }
      if ($this->fields["state"]==self::STATE_RUNNING) {
         echo "<strong>" . $this->getStateName(self::STATE_RUNNING)."</strong>";
      } else {
         Dropdown::showFromArray('state',
                                 array(self::STATE_DISABLE => $this->getStateName(self::STATE_DISABLE),
                                       self::STATE_WAITING => $this->getStateName(self::STATE_WAITING)),
                                 array('value' => $this->fields["state"]));
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][36]." : </td><td>";
      $modes=array();
      if ($this->fields['allowmode']&self::MODE_INTERNAL) {
         $modes[self::MODE_INTERNAL]=$this->getModeName(self::MODE_INTERNAL);
      }
      if ($this->fields['allowmode']&self::MODE_EXTERNAL) {
         $modes[self::MODE_EXTERNAL]=$this->getModeName(self::MODE_EXTERNAL);
      }
      Dropdown::showFromArray('mode', $modes, array('value' => $this->fields['mode']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][38]." : </td><td>";
      Dropdown::showInteger('hourmin', $this->fields['hourmin'],0,24);
      echo "&nbsp;->&nbsp;";
      Dropdown::showInteger('hourmax', $this->fields['hourmax'],0,24);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".$LANG['setup'][130]." : </td><td>";
      Dropdown::showInteger('logs_lifetime', $this->fields['logs_lifetime'],10,360,10,
                            array(0 => $LANG['setup'][200]),
                            array('suffix'=>$LANG['stats'][31]));
      echo "</td><td>".$LANG['crontask'][40]."&nbsp;:</td><td>";
      if (empty($this->fields['lastrun'])) {
         echo $LANG['setup'][307];
      } else {
         echo convDateTime($this->fields['lastrun']);
         echo " <a href='".$this->getFormURL()."?id=$ID&amp;resetdate=1'><img src='".GLPI_ROOT."/pics/reset.png' ";
         echo " alt=\"".$LANG['buttons'][16]."\" title=\"".$LANG['buttons'][16]."\"></a>";
      }
      echo "</td></tr>";

      $label = $this->getParameterDescription();
      echo "<tr class='tab_bg_1'><td>";
      if (empty($label)) {
         echo "&nbsp;</td><td>&nbsp;";
      } else {
         echo $label."&nbsp;:</td><td>";
         Dropdown::showInteger('param', $this->fields['param'],0,400,1);
      }
      echo "</td><td>".$LANG['crontask'][41]."&nbsp;:</td><td>";
      if ($tmpstate == self::STATE_RUNNING) {
         $launch=false;
      } else {
         $launch = $this->fields['allowmode']&self::MODE_INTERNAL;
      }
      if ($tmpstate!=self::STATE_WAITING) {
         echo $this->getStateName($tmpstate);
      } else if (empty($this->fields['lastrun'])) {
         echo $LANG['crontask'][42];
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
         } else {
            echo convDateTime($disp);
         }
      }
      if ($launch) {
         echo " - <a href='".GLPI_ROOT."/front/crontask.php?execute=".$this->fields["name"]."'>";
         echo $LANG['buttons'][57]."</a>";
      }
      if ($tmpstate == self::STATE_RUNNING) {
         echo " <a href='".$this->getFormURL()."?id=$ID&amp;resetstate=1'><img src='".GLPI_ROOT."/pics/reset.png' ";
         echo " alt=\"".$LANG['buttons'][16]."\" title=\"".$LANG['buttons'][16]."\"></a>";
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

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
            'id'        => $this->fields['id'],
            'lastrun'   => 'NULL'));
   }

   /**
    * reset the current state
    *
    */
   function resetState () {
      global $DB;

      if (!isset($this->fields['id'])) {
         return false;
      }
      return $this->update(array(
            'id'        => $this->fields['id'],
            'state'   => self::STATE_WAITING));
   }

   /**
    * Translate task description
    *
    * @param $id integer ID of the crontask
    * @return string
    */
   public function getDescription($id) {
      global $LANG;

      if (!isset($this->fields['id']) || $this->fields['id']!=$id) {
         $this->getFromDB($id);
      }

      $hook = array($this->fields['itemtype'], 'cronInfo');
      if (is_callable($hook)) {
         $info = call_user_func($hook, $this->fields['name']);
      } else {
         $info = false;
      }
      if (isset($info['description'])) {
         return $info['description'];
      }
      return $this->fields['name'];
   }

   /**
    * Translate task parameter description
    *
    * @return string
    */
   public function getParameterDescription() {
      global $LANG;
      $hook = array($this->fields['itemtype'], 'cronInfo');

      if (is_callable($hook)) {
         $info = call_user_func($hook, $this->fields['name']);
      } else {
         $info = false;
      }
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
         case self::STATE_RUNNING:
            return $LANG['crontask'][33];
            break;
         case self::STATE_WAITING:
            return $LANG['crontask'][32];
            break;
         case self::STATE_DISABLE:
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
         case self::MODE_INTERNAL:
            return $LANG['crontask'][34];
            break;
         case self::MODE_EXTERNAL:
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
    * @param $mode (internal/external, <0 to force)
    * @param $max number of task to launch ()
    * @param $name of task to run
    *
    * @return the name of last task launched
    */
   static public function launch($mode, $max=1, $name='') {

      $taskname='';

      if (CronTask::get_lock()) {
         $task = new CronTask();
         for ($i=1 ; $i<=$max ; $i++) {
            $prefix = ($mode==self::MODE_EXTERNAL ? 'External' : 'Internal')." #$i: ";
            if ($task->getNeedToRun($mode, $name)) {
               $_SESSION["glpicronuserrunning"]="cron_".$task->fields['name'];

               if ($plug=isPluginItemType($task->fields['itemtype'])) {
                  Plugin::load($plug['plugin'],true);
               }
               $fonction = array($task->fields['itemtype'], 'cron' . $task->fields['name']);

               if (is_callable($fonction)) {
                  if ($task->start()) { // Lock in DB + log start
                     $taskname = $task->fields['name'];
                     logInFile('cron', $prefix."Launch ".$task->fields['name']."\n");
                     $retcode = call_user_func($fonction,$task);
                     $task->end($retcode); // Unlock in DB + log end
                  } else {
                     logInFile('cron', $prefix."Can't start ".$task->fields['name']."\n");
                  }

               } else {
                  if (is_array($fonction)) {
                     $fonction = implode('::',$fonction);
                  }
                  logInFile('php-errors', "Undefined function '$fonction' (for cron)\n");
                  logInFile('cron', $prefix."Can't start ".$task->fields['name'].
                     "\nUndefined function '$fonction'\n");
               }
            } else if ($i==1) {
               logInFile('cron', $prefix."Nothing to launch\n");
            }
         } // end for
         $_SESSION["glpicronuserrunning"]='';

         CronTask::release_lock();
      } else {
         logInFile('cron', "Can't get DB lock'\n");
      }

      return $taskname;
   }

   /**
    * Register new task for plugin (called by plugin during install)
    *
    * @param $itemtype : itemtype of the plugin object
    * @param $name : of the task
    * @param $frequency : of execution
    * @param $options array of optional options
    *       (state, mode, allowmode, hourmin, hourmax, logs_lifetime, param, comment)
    *
    * @return bool for success
    */
   static public function Register($itemtype, $name, $frequency, $options=array()) {

      // Check that hook exists
      if (!isPluginItemType($itemtype)) {
         return false;
      }
      $input = array (
         'itemtype' => $itemtype,
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

      $query = "SELECT * FROM glpi_crontasks WHERE itemtype LIKE 'Plugin$plugin%';";
      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            if (!$temp->delete($data)) {
               $ret = false;
            }
         }
      }
      return $ret;
   }
   /**
   * Display statistics of a task
   *
   * @return nothing
   */
   function showStatistics() {
      global $DB, $CFG_GLPI, $LANG;

      echo "<br><div class='center'>";
      echo "<table class='tab_cadre'><tr>";
      echo "<th colspan='2'>&nbsp;".$LANG['Menu'][13]."&nbsp;</th>"; // Date
      echo "</tr>\n";

      $nbstart = countElementsInTable('glpi_crontasklogs',
            "`crontasks_id`='".$this->fields['id']."' AND `state`='".CronTaskLog::STATE_START."'");
      $nbstop = countElementsInTable('glpi_crontasklogs',
            "`crontasks_id`='".$this->fields['id']."' AND `state`='".CronTaskLog::STATE_STOP."'");

      echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][50]."&nbsp;:</td><td class='right'>";
      if ($nbstart==$nbstop) {
         echo $nbstart;
      } else {
         // This should not appen => task crash ?
         echo $LANG['crontask'][48]." = $nbstart<br>".$LANG['buttons'][32]." = $nbstop";
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
                   FROM `glpi_crontasklogs`
                   WHERE `crontasks_id`='".$this->fields['id']."'
                         AND `state`='".CronTaskLog::STATE_STOP."'";
         $result = $DB->query($query);

         if ($data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'><td>".$LANG['search'][8]."&nbsp;:</td>";
            echo "<td class='right'>".convDateTime($data['datemin'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][51]."&nbsp;:</td>";
            echo "<td class='right'>".number_format($data['elapsedmin'],2)." ".$LANG['stats'][34];
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][52]."&nbsp;:</td>";
            echo "<td class='right'>".number_format($data['elapsedmax'],2)." ".$LANG['stats'][34];
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][53]."&nbsp;:</td>";
            echo "<td class='right'><strong>".number_format($data['elapsedavg'],2)." ".
                  $LANG['stats'][34]."</strong></td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['job'][20]."&nbsp;:</td>";
            echo "<td class='right'>".number_format($data['elapsedtot'],2)." ".$LANG['stats'][34];
            echo "</td></tr>";
         }
         if ($data && $data['voltot']>0) {
            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][55]."&nbsp;:</td>";
            echo "<td class='right'>".$data['volmin']." ".$LANG['crontask'][62]."</td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][56]."&nbsp;:</td>";
            echo "<td class='right'>".$data['volmax']." ".$LANG['crontask'][62]."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][57]."&nbsp;:</td>";
            echo "<td class='right'><strong>".number_format($data['volavg'],2)." ".
                  $LANG['crontask'][62]."</strong></td></tr>";

            echo "<tr class='tab_bg_1'><td>".$LANG['crontask'][58]."&nbsp;:</td>";
            echo "<td class='right'>".$data['voltot']." ".$LANG['crontask'][62]."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".$LANG['crontask'][59]."&nbsp;:</td>";
            echo "<td class='left'>".number_format($data['voltot']/$data['elapsedtot'],2)." ".
                  $LANG['crontask'][62]." / ".$LANG['stats'][34]."</td></tr>";
         }
      }
      echo "</table></div>";
   }


   /**
   * Display list of a runned tasks
   *
   * @return nothing
   */
   function showHistory() {
      global $DB, $CFG_GLPI, $LANG;

      if (isset($_REQUEST["crontasklogs_id"]) && $_REQUEST["crontasklogs_id"]) {
         return $this->showHistoryDetail($_REQUEST["crontasklogs_id"]);
      }

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }

      // Total Number of events
      $number = countElementsInTable('glpi_crontasklogs',
            "`crontasks_id`='".$this->fields['id']."' AND `state`='".CronTaskLog::STATE_STOP."'");

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
                FROM `glpi_crontasklogs`
                WHERE `crontasks_id`='".$this->fields['id']."'
                      AND `state`='".CronTaskLog::STATE_STOP."'
                ORDER BY `id` DESC
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      if ($result=$DB->query($query)) {
         if ($data=$DB->fetch_assoc($result)) {
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".$LANG['common'][27]."</th>"; // Date
            echo "<th>".$LANG['job'][20]."</th>"; // Duration
            echo "<th>".$LANG['tracking'][29]."</th>"; // Number
            echo "<th>".$LANG['joblist'][6]."</th>"; // Dexcription
            echo "</tr>\n";

            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td><a href='javascript:reloadTab(\"crontasklogs_id=".
                          $data['crontasklogs_id']."\");'>".convDateTime($data['date'])."</a></td>";
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
   * @param $logid : crontasklogs_id
   *
   * @return nothing
   */
   function showHistoryDetail($logid) {
      global $DB, $CFG_GLPI, $LANG;

      echo "<br><div class='center'>";
      echo "<p><a href='javascript:reloadTab(\"crontasklogs_id=0\");'>".$LANG['crontask'][47]."</a></p>";

      $query = "SELECT *
         FROM `glpi_crontasklogs`
         WHERE `id`='$logid' OR `crontasklogs_id`='$logid'
         ORDER BY `id` ASC";

      if ($result=$DB->query($query)) {
         if ($data=$DB->fetch_assoc($result)) {
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".$LANG['common'][27]."</th>"; // Date
            echo "<th>".$LANG['joblist'][0]."</th>"; // statut
            echo "<th>".$LANG['job'][31]."</th>"; // Duration
            echo "<th>".$LANG['tracking'][29]."</th>"; // Number
            echo "<th>".$LANG['joblist'][6]."</th>"; // Dexcription
            echo "</tr>\n";

            $first=true;
            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td class='center'>".($first ? convDateTime($data['date']) : "&nbsp;")."</a></td>";
               switch ($data['state']) {
                  case CronTaskLog::STATE_START:
                     echo "<td>".$LANG['crontask'][48]."</td>";
                     break;

                  case CronTaskLog::STATE_STOP:
                     echo "<td>".$LANG['buttons'][32]."</td>";
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

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;
      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'description';
      $tab[3]['name']          = $LANG['joblist'][6];
      $tab[3]['nosearch']      = true;
      $tab[3]['nosort']        = true;
      $tab[2]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'state';
      $tab[4]['name']          = $LANG['joblist'][0];
      $tab[4]['massiveaction'] = false;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'mode';
      $tab[5]['name']          = $LANG['crontask'][36];
      $tab[5]['massiveaction'] = false;

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'frequency';
      $tab[6]['name']          = $LANG['crontask'][37];
      $tab[6]['datatype']      = 'timestamp';
      $tab[6]['massiveaction'] = false;

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'lastrun';
      $tab[7]['name']          = $LANG['crontask'][40];
      $tab[7]['datatype']      = 'datetime';
      $tab[7]['massiveaction'] = false;

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'itemtype';
      $tab[8]['name']          = $LANG['common'][29];
      $tab[8]['massiveaction'] = false;

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      return $tab;
   }

   /**
    * Garbage collector for expired file session
    *
    * @param $task for log
    *
    **/
   static function cronSession($task) {
      global $CFG_GLPI;

      // max time to keep the file session
      $maxlifetime = session_cache_expire();
      $nb=0;
      foreach (glob(GLPI_SESSION_DIR."/sess_*") as $filename) {
         if (filemtime($filename) + $maxlifetime < time()) {
            // Delete session file if not delete before
            if (@unlink($filename)) {
               $nb++;
            }
         }
      }
      $task->setVolume($nb);
      if ($nb) {
         $task->log("Clean $nb session file(s) created since more than $maxlifetime seconds\n");
         return 1;
      }
      return 0;
   }

   /**
    * Garbage collector for cleaning graph files
    *
    * @param $task for log
    *
    **/
   static function cronGraph($task) {
      global $CFG_GLPI;

      // max time to keep the file session
      $maxlifetime = HOUR_TIMESTAMP;
      $nb=0;
      foreach (glob(GLPI_GRAPH_DIR."/*") as $filename) {
         if (filemtime($filename) + $maxlifetime < time()) {
            // Delete session file if not delete before
            if (@unlink($filename)) {
               $nb++;
            }
         }
      }
      $task->setVolume($nb);
      if ($nb) {
         $task->log("Clean $nb graph file(s) created since more than $maxlifetime seconds\n");
         return 1;
      }
      return 0;
   }

   /**
    * Clean log cron function
    *
    * @param $task instance of CronTask
    *
    **/
   static function cronLogs($task) {
      global $CFG_GLPI,$DB;

      $vol = 0;

      // Expire Event Log
      if ($task->fields['param'] > 0) {
         $vol += Event::cleanOld($task->fields['param']);
      }

      foreach ($DB->request('glpi_crontasks') as $data) {
         if ($data['logs_lifetime']>0) {
            $vol += CronTaskLog::cleanOld($data['id'], $data['logs_lifetime']);
         }
      }
      $task->setVolume($vol);
      return ($vol>0 ? 1 : 0);
   }

   /**
    * Cron job to check if a new version is available
    *
    * @param $task for log
    **/
   static function cronCheckUpdate($task) {
      global $CFG_GLPI;

      $result=checkNewVersionAvailable(1);
      $task->log($result);

      return 1;
   }

   /**
    * Clean log cron function
    *
    * @param $task for log
    *
    **/
   static function cronOptimize($task) {
      global $CFG_GLPI,$DB;

      $nb = optimize_tables();
      $task->setVolume($nb);

      return 1;
   }

   /**
    * get Cron description parameter for this class
    *
    * @param $name string name of the task
    *
    * @return array of string
    */
   static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case 'checkupdate':
            return array('description' => $LANG['crontask'][11]);

         case 'logs':
            return array('description' => $LANG['crontask'][7],
                         'parameter'   => $LANG['setup'][109]);

         case 'optimize':
            return array('description' => $LANG['crontask'][8]);

         case 'session':
            return array('description' => $LANG['crontask'][12]);
         case 'graph':
            return array('description' => $LANG['crontask'][13]);
      }
   }

   /**
   * Dropdown for frequency (interval between 2 actions)
   *
   * @param $name select name
   * @param $value default value
   */
   function dropdownFrequency($name,$value=0) {
      global $LANG;

      $tab = array();

      $tab[MINUTE_TIMESTAMP] = '1 ' .$LANG['job'][22];

      // Minutes
      for ($i=5 ; $i<60 ; $i+=5) {
         $tab[$i*MINUTE_TIMESTAMP] = $i . ' ' .$LANG['job'][22];
      }

      // Heures
      for ($i=1 ; $i<24 ; $i++) {
         $tab[$i*HOUR_TIMESTAMP] = $i . ' ' .$LANG['job'][21];
      }

      // Jours
      $tab[DAY_TIMESTAMP] = $LANG['setup'][305];
      for ($i=2 ; $i<7 ; $i++) {
         $tab[$i*DAY_TIMESTAMP] = $i . ' ' .$LANG['stats'][31];
      }

      $tab[WEEK_TIMESTAMP] = $LANG['setup'][308];
      $tab[MONTH_TIMESTAMP] = $LANG['setup'][309];

      Dropdown::showFromArray($name, $tab, array('value' => $value));
   }
}
?>
