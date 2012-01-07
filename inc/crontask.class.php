<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
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
   static private $lockname = '';
   private $timer           = 0.0;
   private $startlog        = 0;
   private $volume          = 0;

   // Class constant
   const STATE_DISABLE = 0;
   const STATE_WAITING = 1;
   const STATE_RUNNING = 2;

   const MODE_INTERNAL = 1;
   const MODE_EXTERNAL = 2;


   /**
    * Name of the type
   **/
   static function getTypeName($nb=0) {

      return _n('Automatic action', 'Automatic actions', $nb);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('CronTaskLog', $ong, $options);

      return $ong;
   }


   function canCreate() {
      return Session::haveRight('config', 'w');
   }


   function canView() {
      return Session::haveRight('config', 'r');
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
    * @param $itemtype itemtype of the crontask
    * @param $name name of the task
    *
    * @return true if succeed else false
   **/
   function getFromDBbyName($itemtype, $name) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `name` = '$name'
                      AND `itemtype` = '$itemtype'";

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
   **/
   function isDisabled() {

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
   **/
   function start() {
      global $DB;

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
         $this->timer  = microtime(true);
         $this->volume = 0;
         $log = new CronTaskLog();
         $txt = __('Run mode') . " : " .
                $this->getModeName(isCommandLine() ? self::MODE_EXTERNAL
                                                   : self::MODE_INTERNAL);

         $this->startlog = $log->add(array('crontasks_id'    => $this->fields['id'],
                                           'date'            => $_SESSION['glpi_currenttime'],
                                           'content'         => addslashes($txt),
                                           'crontasklogs_id' => 0,
                                           'state'           => CronTaskLog::STATE_START,
                                           'volume'          => 0,
                                           'elapsed'         => 0));
         return true;
      }
      return false;
   }


   /**
    * Set the currently proccessed volume of a running task
    *
    * @param $volume
   **/
   function setVolume ($volume) {
      $this->volume = $volume;
   }


   /**
    * Increase the currently proccessed volume of a running task
    *
    * @param $volume
   **/
   function addVolume ($volume) {
      $this->volume += $volume;
   }


   /**
    * Start a task, timer, stat, log, ...
    *
    * @param $retcode : <0 : need to run again, 0:nothing to do, >0:ok
    *
    * @return bool : true if ok (not start by another)
   **/
   function end($retcode) {
      global $DB;

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
            $content = __('Action completed, partially processed');
         } else if ($retcode > 0) {
            $content = __('Action completed, fully processed');
         } else {
            $content = __('Action completed, no processing required');
         }
         $log = new CronTaskLog();
         $log->add(array('crontasks_id'    => $this->fields['id'],
                         'date'            => $_SESSION['glpi_currenttime'],
                         'content'         => $content,
                         'crontasklogs_id' => $this->startlog,
                         'state'           => CronTaskLog::STATE_STOP,
                         'volume'          => $this->volume,
                         'elapsed'         => (microtime(true)-$this->timer)));
         return true;
      }
      return false;
   }


   /**
    * Add a log message for a running task
    *
    * @param $content
   **/
   function log($content) {

      if (!isset($this->fields['id'])) {
         return false;
      }
      $log     = new CronTaskLog();
      $content = Toolbox::substr($content, 0, 200);
      return $log->add(array('crontasks_id'    => $this->fields['id'],
                             'date'            => $_SESSION['glpi_currenttime'],
                             'content'         => addslashes($content),
                             'crontasklogs_id' => $this->startlog,
                             'state'           => CronTaskLog::STATE_RUN,
                             'volume'          => $this->volume,
                             'elapsed'         => (microtime(true)-$this->timer)));
   }


   /**
    * read the first task which need to be run by cron
    *
    * @param $mode : >0 retrieve task configured for this mode
    *                <0 retrieve task allowed for this mode (force, no time check)
    * @param $name : one specify action
    *
    * @return false if no task to run
   **/
   function getNeedToRun($mode=0, $name='') {
      global $DB;

      $hour = date('H');
      // First core ones
      $query = "SELECT *,
                       LOCATE('Plugin',itemtype) AS ISPLUGIN
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
         $query .= " AND `name` = '".addslashes($name)."' ";
      }

      // In force mode
      if ($mode<0) {
         $query .= " AND `state` != '".self::STATE_RUNNING."'
                     AND (`allowmode` & ".(-intval($mode)).") ";
      } else {
         $query .= " AND `state` = '".self::STATE_WAITING."'";
         if ($mode>0) {
            $query .= " AND `mode` = '$mode' ";
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
         $query .= " AND ((`hourmin` < `hourmax`
                           AND  '$hour' >= `hourmin`
                           AND '$hour' < `hourmax`)
                          OR (`hourmin` > `hourmax`
                              AND ('$hour' >= `hourmin`
                                   OR '$hour' < `hourmax`)))
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
      return false;
   }


   /**
    * Print the contact form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Session::haveRight("config","r") || !$this->getFromDB($ID)) {
         return false;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td class ='b'>";
      if ($isplug=isPluginItemType($this->fields["itemtype"])) {
         echo $isplug["plugin"]." - ";
      }
      echo $this->fields["name"]."</td>";
      echo "<td rowspan='6' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='6'>";
      echo "<textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Description')."</td><td>";
      echo $this->getDescription($ID);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Run frequency')."</td><td>";
      $this->dropdownFrequency('frequency', $this->fields["frequency"]);
      echo "</td></tr>";

      $tmpstate = $this->fields["state"];
      echo "<tr class='tab_bg_1'><td>".__('Status')."</td><td>";
      if (is_file(GLPI_CRON_DIR. '/'.$this->fields["name"].'.lock')
          || is_file(GLPI_CRON_DIR. '/all.lock')) {
         echo "<span class='b'>" . __('System lock')."</span><br>";
         $tmpstate = self::STATE_DISABLE;
      }

      if ($isplug) {
         $plug = new Plugin();
         if (!$plug->isActivated($isplug["plugin"])) {
            echo "<span class='b'>" . __('Disabled plugin')."</span><br>";
            $tmpstate = self::STATE_DISABLE;
         }
      }

      if ($this->fields["state"]==self::STATE_RUNNING) {
         echo "<span class='b'>" . $this->getStateName(self::STATE_RUNNING)."</span>";
      } else {
         self::dropdownState('state', $this->fields["state"]);
      }
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Run mode')."</td><td>";
      $modes = array();
      if ($this->fields['allowmode']&self::MODE_INTERNAL) {
         $modes[self::MODE_INTERNAL] = self::getModeName(self::MODE_INTERNAL);
      }
      if ($this->fields['allowmode']&self::MODE_EXTERNAL) {
         $modes[self::MODE_EXTERNAL] = self::getModeName(self::MODE_EXTERNAL);
      }
      Dropdown::showFromArray('mode', $modes, array('value' => $this->fields['mode']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Run period')."</td><td>";
      Dropdown::showInteger('hourmin', $this->fields['hourmin'], 0, 24);
      echo "&nbsp;->&nbsp;";
      Dropdown::showInteger('hourmax', $this->fields['hourmax'], 0, 24);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Number of days this action logs are stored')."</td><td>";
      Dropdown::showInteger('logs_lifetime', $this->fields['logs_lifetime'], 10, 360, 10,
                            array(0 => __('Infinite')));
      echo "</td><td>".__('Last run')."</td><td>";

      if (empty($this->fields['lastrun'])) {
         _e('Never');
      } else {
         echo Html::convDateTime($this->fields['lastrun']);
         echo " <a href='".$this->getFormURL()."?id=$ID&amp;resetdate=1'>";
         echo "<img src='".GLPI_ROOT."/pics/reset.png' alt=\"".__s('Blank')."\" title=\"".
                __s('Blank')."\"></a>";
      }
      echo "</td></tr>";

      $label = $this->getParameterDescription();
      echo "<tr class='tab_bg_1'><td>";
      if (empty($label)) {
         echo "&nbsp;</td><td>&nbsp;";
      } else {
         echo $label."&nbsp;:&nbsp;</td><td>";
         Dropdown::showInteger('param', $this->fields['param'],0,400,1);
      }
      echo "</td><td>".__('Next run')."</td><td>";

      if ($tmpstate == self::STATE_RUNNING) {
         $launch = false;
      } else {
         $launch = $this->fields['allowmode']&self::MODE_INTERNAL;
      }

      if ($tmpstate!=self::STATE_WAITING) {
         echo $this->getStateName($tmpstate);
      } else if (empty($this->fields['lastrun'])) {
         _e('As soon as possible');
      } else {
         $next = strtotime($this->fields['lastrun'])+$this->fields['frequency'];
         $h=date('H',$next);
         $deb=($this->fields['hourmin'] < 10 ? "0".$this->fields['hourmin']
                                             : $this->fields['hourmin']);
         $fin=($this->fields['hourmax'] < 10 ? "0".$this->fields['hourmax']
                                             : $this->fields['hourmax']);

         if ($deb<$fin && $h<$deb) {
            $disp = date('Y-m-d', $next). " $deb:00:00";
            $next = strtotime($disp);
         } else if ($deb<$fin && $h>=$this->fields['hourmax']) {
            $disp = date('Y-m-d', $next+DAY_TIMESTAMP). " $deb:00:00";
            $next = strtotime($disp);
         }

         if ($deb>$fin && $h<$deb && $h>=$fin) {
            $disp = date('Y-m-d', $next). " $deb:00:00";
            $next = strtotime($disp);
         } else {
            $disp = date("Y-m-d H:i:s", $next);
         }

         if ($next<time()) {
            echo __('As soon as possible').'<br>('.Html::convDateTime($disp).') ';
         } else {
            echo Html::convDateTime($disp);
         }
      }

      if ($launch) {
         echo " - <a href='".GLPI_ROOT."/front/crontask.php?execute=".$this->fields["name"]."'>".
                   __('Execute')."</a>";
      }
      if ($tmpstate == self::STATE_RUNNING) {
         echo " <a href='".$this->getFormURL()."?id=$ID&amp;resetstate=1'>";
         echo "<img src='".GLPI_ROOT."/pics/reset.png' alt=\"".__s('Blank')."\" title=\"".
                __s('Blank')."\"></a>";
      }
      echo "</td></tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * reset the next launch date => for a launch as soon as possible
   **/
   function resetDate () {
      global $DB;

      if (!isset($this->fields['id'])) {
         return false;
      }
      return $this->update(array('id'      => $this->fields['id'],
                                 'lastrun' => 'NULL'));
   }


   /**
    * reset the current state
   **/
   function resetState () {
      global $DB;

      if (!isset($this->fields['id'])) {
         return false;
      }
      return $this->update(array('id'    => $this->fields['id'],
                                 'state' => self::STATE_WAITING));
   }


   /**
    * Translate task description
    *
    * @param $id integer ID of the crontask
    *
    * @return string
   **/
   public function getDescription($id) {

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
   **/
   public function getParameterDescription() {

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
    *
    * @return string
   **/
   static public function getStateName($state) {

      switch ($state) {
         case self::STATE_RUNNING :
            return __('Running');

         case self::STATE_WAITING :
            return __('Scheduled');

         case self::STATE_DISABLE :
            return __('Disabled');
      }

      return '???';
   }


   /**
    * Dropdown of state
    *
    * @param $name select name
    * @param $value default value
    *
    * @return nothing (display)
   **/
   static function dropdownState($name, $value=0) {

      return Dropdown::showFromArray($name,
                                     array(self::STATE_DISABLE => __('Disabled'),
                                           self::STATE_WAITING => __('Scheduled')),
                                     array('value' => $value));
   }


   /**
    * Translate Mode to string
    *
    * @param $mode integer
    *
    * @return string
   **/
   static public function getModeName($mode) {

      switch ($mode) {
         case self::MODE_INTERNAL :
            return __('GLPI');

         case self::MODE_EXTERNAL :
            return __('CLI');
      }

      return '???';
   }


   /**
    * Get a global database lock
   **/
   static private function get_lock() {
      global $DB;

      // Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
      $nom = $DB->dbdefault . ".glpicron." . intval(time()/HOUR_TIMESTAMP-340000);

      $nom    = addslashes($nom);
      $query  = "SELECT GET_LOCK('$nom', 0)";
      $result = $DB->query($query);
      list($lock_ok) = $DB->fetch_array($result);

      if ($lock_ok) {
         self::$lockname = $nom;
      }

      return $lock_ok;
   }


   /**
    * Release the global database lock
   **/
   static private function release_lock() {
      global $DB;

      if (self::$lockname) {
         $nom    = self::$lockname;
         $query  = "SELECT RELEASE_LOCK('$nom')";
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
   **/
   static public function launch($mode, $max=1, $name='') {

      $taskname = '';
      //If cron is launched in command line, and if memory is insufficient, display a warning in
      //the logs
      if ($mode==self::MODE_EXTERNAL && Toolbox::checkMemoryLimit() == 2) {
         Toolbox::logDebug(__('A minimum of 64MB is commonly required for GLPI.'));
      }

      if (self::get_lock()) {
         $crontask = new self();
         for ($i=1 ; $i<=$max ; $i++) {
            $prefix = ($mode==self::MODE_EXTERNAL ? 'External'
                                                  : 'Internal')." #$i: ";

            if ($crontask->getNeedToRun($mode, $name)) {
               $_SESSION["glpicronuserrunning"] = "cron_".$crontask->fields['name'];

               if ($plug=isPluginItemType($crontask->fields['itemtype'])) {
                  Plugin::load($plug['plugin'], true);
               }
               $fonction = array($crontask->fields['itemtype'],
                                 'cron' . $crontask->fields['name']);

               if (is_callable($fonction)) {
                  if ($crontask->start()) { // Lock in DB + log start
                     $taskname = $crontask->fields['name'];
                     Toolbox::logInFile('cron', $prefix."Launch ".$crontask->fields['name']."\n");
                     $retcode = call_user_func($fonction, $crontask);
                     $crontask->end($retcode); // Unlock in DB + log end
                  } else {
                     Toolbox::logInFile('cron', $prefix."Can't start ".$crontask->fields['name']."\n");
                  }

               } else {
                  if (is_array($fonction)) {
                     $fonction = implode('::',$fonction);
                  }
                  Toolbox::logInFile('php-errors', "Undefined function '$fonction' (for cron)\n");
                  Toolbox::logInFile('cron',
                            $prefix."Can't start ".$crontask->fields['name'].
                              "\nUndefined function '$fonction'\n");
               }

            } else if ($i==1) {
               Toolbox::logInFile('cron', $prefix."Nothing to launch\n");
            }
         } // end for
         $_SESSION["glpicronuserrunning"]='';
         self::release_lock();

      } else {
         Toolbox::logInFile('cron', "Can't get DB lock'\n");
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
   **/
   static public function Register($itemtype, $name, $frequency, $options=array()) {

      // Check that hook exists
      if (!isPluginItemType($itemtype)) {
         return false;
      }
      $temp = new self();
      // Avoid duplicate entry
      if ($temp->getFromDBbyName($itemtype, $name)) {
         return false;
      }
      $input = array('itemtype'  => $itemtype,
                     'name'      => $name,
                     'frequency' => $frequency);

      foreach (array('allowmode', 'comment', 'hourmax', 'hourmin', 'logs_lifetime', 'mode',
                     'param', 'state') as $key) {
         if (isset($options[$key])) {
            $input[$key] = $options[$key];
         }
      }
      return $temp->add($input);
   }


   /**
    * Unregister tasks for a plugin (call by glpi after uninstall)
    *
    * @param $plugin : name of the plugin
    *
    * @return bool for success
   **/
   static public function Unregister($plugin) {
      global $DB;

      if (empty($plugin)) {
         return false;
      }
      $temp = new CronTask();
      $ret  = true;

      $query = "SELECT *
                FROM `glpi_crontasks`
                WHERE `itemtype` LIKE 'Plugin$plugin%'";
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
   **/
   function showStatistics() {
      global $DB, $CFG_GLPI;

      echo "<br><div class='center'>";
      echo "<table class='tab_cadre'>";
      echo "<tr><th colspan='2'>&nbsp;".__('Statistics')."</th></tr>\n"; // Date

      $nbstart = countElementsInTable('glpi_crontasklogs',
                                      "`crontasks_id` = '".$this->fields['id']."'
                                          AND `state` = '".CronTaskLog::STATE_START."'");
      $nbstop  = countElementsInTable('glpi_crontasklogs',
                                      "`crontasks_id` = '".$this->fields['id']."'
                                          AND `state` = '".CronTaskLog::STATE_STOP."'");

      echo "<tr class='tab_bg_2'><td>".__('Run count')."</td><td class='right'>";
      if ($nbstart==$nbstop) {
         echo $nbstart;
      } else {
         // This should not appen => task crash ?
         //TRANS: %s is the number of starts
         printf(__('%s starts'),$nbstart);
         echo "<br>";
         //TRANS: %s is the number of stops
         printf(__('%s stops'),$nbstop);
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
                   WHERE `crontasks_id` = '".$this->fields['id']."'
                         AND `state` = '".CronTaskLog::STATE_STOP."'";
         $result = $DB->query($query);

         if ($data = $DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_1'><td>".__('Start date')."</td>";
            echo "<td class='right'>".Html::convDateTime($data['datemin'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Minimal time')."</td>";
            //TRANS: %s is the number of seconds
            echo "<td class='right'>".sprintf(__('%s sec(s)'), number_format($data['elapsedmin'],2));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Maximal time')."</td>";
            echo "<td class='right'>".sprintf(__('%s sec(s)'), number_format($data['elapsedmax'],2));
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Average time')."</td>";
            echo "<td class='right b'>".sprintf(__('%s sec(s)'), number_format($data['elapsedavg'],2));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Total duration')."</td>";
            echo "<td class='right'>".sprintf(__('%s sec(s)'), number_format($data['elapsedtot'],2));
            echo "</td></tr>";
         }

         if ($data && $data['voltot']>0) {
            echo "<tr class='tab_bg_2'><td>".__('Minimal count')."</td>";
            echo "<td class='right'>".sprintf(__('%s element(s)'), $data['volmin'])."</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Maximal count')."</td>";
            echo "<td class='right'>".sprintf(__('%s element(s)'), $data['volmax'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Average count')."</td>";
            echo "<td class='right b'>".sprintf(__('%s element(s)'), number_format($data['volavg'],2)).
                 "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Total count')."</td>";
            echo "<td class='right'>".sprintf(__('%s element(s)'), $data['voltot'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Average speed')."</td>";
            echo "<td class='left'>".sprintf(__('%s elements/sec'), number_format($data['voltot']/$data['elapsedtot'],2));
            echo "</td></tr>";
         }
      }
      echo "</table></div>";
   }


   /**
    * Display list of a runned tasks
    *
    * @return nothing
   **/
   function showHistory() {
      global $DB, $CFG_GLPI;

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
                                     "`crontasks_id` = '".$this->fields['id']."'
                                          AND `state` = '".CronTaskLog::STATE_STOP."'");

      echo "<br><div class='center'>";
      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No item found')."</th></tr>";
         echo "</table>";
         echo "</div>";
         return;
      }

      // Display the pager
      Html::printAjaxPager(__('Last run list'), $start, $number);

      $query = "SELECT *
                FROM `glpi_crontasklogs`
                WHERE `crontasks_id` = '".$this->fields['id']."'
                      AND `state` = '".CronTaskLog::STATE_STOP."'
                ORDER BY `id` DESC
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      if ($result=$DB->query($query)) {
         if ($data=$DB->fetch_assoc($result)) {
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".__('Date')."</th>";
            echo "<th>".__('Total duration')."</th>"; 
            echo "<th>".__('Number')."</th>";
            echo "<th>".__('Description')."</th>"; 
            echo "</tr>\n";

            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td><a href='javascript:reloadTab(\"crontasklogs_id=".
                          $data['crontasklogs_id']."\");'>".Html::convDateTime($data['date']).
                    "</a></td>";
               echo "<td class='right'>".number_format($data['elapsed'], 3)."s</td>";
               echo "<td class='right'>".$data['volume']."</td>";
               echo "<td>".$data['content']."</td>";
               echo "</tr>\n";
            } while ($data=$DB->fetch_assoc($result));

            echo "</table>";

         } else { // Not found
            _e('No item found');
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
   **/
   function showHistoryDetail($logid) {
      global $DB, $CFG_GLPI;

      echo "<br><div class='center'>";
      echo "<p><a href='javascript:reloadTab(\"crontasklogs_id=0\");'>".__('Last run list')."</a>".
           "</p>";

      $query = "SELECT *
                FROM `glpi_crontasklogs`
                WHERE `id` = '$logid'
                      OR `crontasklogs_id` = '$logid'
                ORDER BY `id` ASC";

      if ($result=$DB->query($query)) {
         if ($data=$DB->fetch_assoc($result)) {
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".__('Date')."</th>";
            echo "<th>".__('Status')."</th>"; 
            echo "<th>". __('Duration')."</th>"; 
            echo "<th>".__('Number')."</th>";
            echo "<th>".__('Description')."</th>"; 
            echo "</tr>\n";

            $first = true;
            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td class='center'>".($first ? Html::convDateTime($data['date'])
                                                  : "&nbsp;")."</a></td>";

               switch ($data['state']) {
                  case CronTaskLog::STATE_START :
                     echo "<td>".__('Start')."</td>";
                     break;

                  case CronTaskLog::STATE_STOP :
                     echo "<td>".__('End')."</td>";
                     break;

                  default :
                     echo "<td>".__('Running')."</td>";
               }

               echo "<td class='right'>".number_format($data['elapsed'], 3)."s</td>";
               echo "<td class='right'>".$data['volume']."</td>";
               echo "<td>".$data['content']."</td>";
               echo "</tr>\n";
               $first = false;
            } while ($data=$DB->fetch_assoc($result));

            echo "</table>";

         } else { // Not found
            _e('No item found');
         }
      } // Query

      echo "</div>";
   }


   function getSearchOptions() {

      $tab = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_link'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'description';
      $tab[3]['name']          = __('Description');
      $tab[3]['nosearch']      = true;
      $tab[3]['nosort']        = true;
      $tab[3]['massiveaction'] = false;

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'state';
      $tab[4]['name']          = __('Status');
      $tab[4]['searchtype']    = array('equals');
      $tab[4]['massiveaction'] = false;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'mode';
      $tab[5]['name']          = __('Run mode');

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'frequency';
      $tab[6]['name']          = __('Run frequency');
      $tab[6]['datatype']      = 'timestamp';
      $tab[6]['massiveaction'] = false;

      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'lastrun';
      $tab[7]['name']          = __('Last run');
      $tab[7]['datatype']      = 'datetime';
      $tab[7]['massiveaction'] = false;

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'itemtype';
      $tab[8]['name']          = __('Plugins');
      $tab[8]['massiveaction'] = false;

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['name']      = __('Comments');
      $tab[16]['datatype']  = 'text';

      return $tab;
   }


   /**
    * Garbage collector for expired file session
    *
    * @param $task for log
   **/
   static function cronSession($task) {

      // max time to keep the file session
      $maxlifetime = session_cache_expire();
      $nb          = 0;
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
   **/
   static function cronGraph($task) {
      global $CFG_GLPI;

      // max time to keep the file session
      $maxlifetime = HOUR_TIMESTAMP;
      $nb          = 0;
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
   **/
   static function cronLogs($task) {
      global $DB;

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

      $result = Toolbox::checkNewVersionAvailable(1);
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

      $nb = DBmysql::optimize_tables(NULL, true);
      $task->setVolume($nb);

      return 1;
   }


   /**
    * Check zombie crontask
    *
    * @param $task for log
   **/
   static function cronWatcher($task) {
      global $CFG_GLPI, $DB;

      $cron_status = 0;

      // Crontasks running for more than 1 hour or 2 frequency
      $query = "SELECT *
                FROM `glpi_crontasks`
                WHERE `state` = '".self::STATE_RUNNING."'
                      AND ((unix_timestamp(`lastrun`) + 2 * `frequency` < unix_timestamp(now()))
                           OR (unix_timestamp(`lastrun`) + 2*".HOUR_TIMESTAMP." < unix_timestamp(now())))";
      $crontasks = array();
      foreach ($DB->request($query) as $data) {
         $crontasks[$data['id']] = $data;
      }

      if (count($crontasks)) {
         if (NotificationEvent::raiseEvent("alert", new Crontask(),
                                           array('crontasks' => $crontasks))) {
            $cron_status = 1;
            $task->addVolume(1);
         }
      }

      return 1;
   }


   /**
    * get Cron description parameter for this class
    *
    * @param $name string name of the task
    *
    * @return array of string
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'checkupdate' :
            return array('description' => __('Check for new updates'));

         case 'logs' :
            return array('description' => __('Clean old logs'),
                         'parameter'   => __('How long system logs are keep in database (in days, 0 for infinite)'));

         case 'optimize' :
            return array('description' => __('Database optimization'));

         case 'session' :
            return array('description' => __('Clean expired sessions'));

         case 'graph' :
            return array('description' => __('Clean generated graphics'));

         case 'watcher' :
            return array('description' => __('Monitoring of automatic actions'));
      }
   }


   /**
    * Dropdown for frequency (interval between 2 actions)
    *
    * @param $name select name
    * @param $value default value
   **/
   function dropdownFrequency($name, $value=0) {

      $tab = array();

      $tab[MINUTE_TIMESTAMP] = sprintf(_n('%d minute','%d minutes',1),1);

      // Minutes
      for ($i=5 ; $i<60 ; $i+=5) {
         $tab[$i*MINUTE_TIMESTAMP] = sprintf(_n('%d minute','%d minutes',$i),$i);
      }

      // Heures
      for ($i=1 ; $i<24 ; $i++) {
         $tab[$i*HOUR_TIMESTAMP] = sprintf(_n('%d hour','%d hours',$i),$i);
      }

      // Jours
      $tab[DAY_TIMESTAMP] = __('Each day');
      for ($i=2 ; $i<7 ; $i++) {
         $tab[$i*DAY_TIMESTAMP] = sprintf(_n('%d day','%d days',$i),$i);
      }

      $tab[WEEK_TIMESTAMP]  = __('Each week');
      $tab[MONTH_TIMESTAMP] = __('Each month');

      Dropdown::showFromArray($name, $tab, array('value' => $value));
   }


   /**
    * Call cron without time check
    *
    * @return boolean : true if launched
   **/
   static function callCronForce() {
      global $CFG_GLPI;

      $path = $CFG_GLPI['root_doc']."/front/cron.php";

      echo "<div style=\"background-image: url('$path');\"></div>";
      return true;
   }


   /**
    * Call cron if time since last launch elapsed
    *
    * @return nothing
   **/
   static function callCron() {

      if (isset($_SESSION["glpicrontimer"])) {
         // call static function callcron() every 5min
         if ((time()-$_SESSION["glpicrontimer"])>300) {

            if (self::callCronForce()) {
               // Restart timer
               $_SESSION["glpicrontimer"] = time();
            }
         }

      } else {
         // Start timer
         $_SESSION["glpicrontimer"] = time();
      }
   }
}
?>
