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
*/

// Needed for signal handler
declare(ticks = 1);

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * CronTask class
 */
class CronTask extends CommonDBTM{

   // From CommonDBTM
   public $dohistory                   = true;

   // Specific ones
   static private $lockname = '';
   private $timer           = 0.0;
   private $startlog        = 0;
   private $volume          = 0;
   static $rightname        = 'config';

   // Class constant
   const STATE_DISABLE = 0;
   const STATE_WAITING = 1;
   const STATE_RUNNING = 2;

   const MODE_INTERNAL = 1;
   const MODE_EXTERNAL = 2;



   /**
    * @see CommonGLPI::getForbiddenActionsForMenu()
    *
    * @since version 0.85
   **/
   static function getForbiddenActionsForMenu() {
      return array('add');
   }


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'delete';
      $forbidden[] = 'purge';
      $forbidden[] = 'restore';
      return $forbidden;
   }


   static function getTypeName($nb=0) {
      return _n('Automatic action', 'Automatic actions', $nb);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('CronTaskLog', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   static function canDelete() {
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
    * @param $itemtype  itemtype of the crontask
    * @param $name      name of the task
    *
    * @return true if succeed else false
   **/
   function getFromDBbyName($itemtype, $name) {

      return $this->getFromDBByQuery("WHERE `".$this->getTable()."`.`name` = '$name'
                                            AND `".$this->getTable()."`.`itemtype` = '$itemtype'");
   }


   /**
    * Give a task state
    *
    * @return integer 0 : task is enabled
    *    if disable : 1: by config, 2: by system lock, 3: by plugin
   **/
   function isDisabled() {

      if ($this->fields['state'] == self::STATE_DISABLE) {
         return 1;
      }

      if (is_file(GLPI_CRON_DIR. '/all.lock')
          || is_file(GLPI_CRON_DIR. '/'.$this->fields['name'].'.lock')) {
         // Global lock
         return 2;
      }

      if (!($tab = isPluginItemType($this->fields['itemtype']))) {
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
    * Get all itemtypes used
    *
    * @return array of itemtypes
   **/
   static function getUsedItemtypes() {
      global $DB;

      $types= array();
      foreach  ($DB->request("SELECT DISTINCT(`itemtype`)
                            FROM `glpi_crontasks`") as $data) {
        $types[] = $data['itemtype'];
      }
      return $types;
    }

   /**
    * Signal handler callback
    *
    * @since 9.1
    */
   function signal($signo) {
      if ($signo == SIGTERM) {
         pcntl_signal(SIGTERM, SIG_DFL);

         // End of this task
         $this->end(NULL);

         // End of this cron
         $_SESSION["glpicronuserrunning"]='';
         self::release_lock();
         Toolbox::logInFile('cron', __('Action aborted')."\n");
         exit;
      }
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

      if (isCommandLine() && function_exists('pcntl_signal')) {
         pcntl_signal(SIGTERM, [$this, 'signal']);
      }

      $query = "UPDATE `".$this->getTable()."`
                SET `state` = '".self::STATE_RUNNING."',
                    `lastrun` = DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:00')
                WHERE `id` = '".$this->fields['id']."'
                      AND `state` != '".self::STATE_RUNNING."'";
      $result = $DB->query($query);

      if ($DB->affected_rows($result)>0) {
         $this->timer  = microtime(true);
         $this->volume = 0;
         $log = new CronTaskLog();
         // No gettext for log
         $txt = sprintf('%1$s: %2$s', 'Run mode',
                        $this->getModeName(isCommandLine() ? self::MODE_EXTERNAL
                                                           : self::MODE_INTERNAL));

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
   function setVolume($volume) {
      $this->volume = $volume;
   }


   /**
    * Increase the currently proccessed volume of a running task
    *
    * @param $volume
   **/
   function addVolume($volume) {
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
                SET `state` = '".$this->fields['state']."'
                WHERE `id` = '".$this->fields['id']."'
                      AND `state` = '".self::STATE_RUNNING."'";
      $result = $DB->query($query);

      if ($DB->affected_rows($result) > 0) {
         // No gettext for log but add gettext line to be parsed for pot generation
         // order is important for insertion in english in the database
         if (is_null($retcode)) {
            $content = __('Action aborted');
            $content = 'Action aborted';
         }
         else if ($retcode < 0) {
            $content = __('Action completed, partially processed');
            $content = 'Action completed, partially processed';

         } else if ($retcode > 0) {
            $content = __('Action completed, fully processed');
            $content = 'Action completed, fully processed';
         } else {
            $content = __('Action completed, no processing required');
            $content = 'Action completed, no processing required';
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
    * @param $mode   >0 retrieve task configured for this mode
    *                <0 retrieve task allowed for this mode (force, no time check)
    *                (default 0)
    * @param $name   one specify action (default '')
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
      if ($mode < 0) {
         $query .= " AND `state` != '".self::STATE_RUNNING."'
                     AND (`allowmode` & ".(-intval($mode)).") ";
      } else {
         $query .= " AND `state` = '".self::STATE_WAITING."'";
         if ($mode > 0) {
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
                          OR unix_timestamp(`lastrun`) + `frequency` <= unix_timestamp(now()))
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
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if (!Config::canView() || !$this->getFromDB($ID)) {
         return false;
      }
      $options['candel'] = false;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td class ='b'>";
      $name = $this->fields["name"];
      if ($isplug = isPluginItemType($this->fields["itemtype"])) {
         $name = sprintf(__('%1$s - %2$s'), $isplug["plugin"], $name);
      }
      echo $name."</td>";
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

      if ($this->fields["state"] == self::STATE_RUNNING) {
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
      Dropdown::showNumber('hourmin', array('value' => $this->fields['hourmin'],
                                            'min'   => 0,
                                            'max'   => 24));
      echo "&nbsp;->&nbsp;";
      Dropdown::showNumber('hourmax', array('value' => $this->fields['hourmax'],
                                            'min'   => 0,
                                            'max'   => 24));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Number of days this action logs are stored')."</td><td>";
      Dropdown::showNumber('logs_lifetime', array('value' => $this->fields['logs_lifetime'],
                                                  'min'   => 10,
                                                  'max'   => 360,
                                                  'step'  => 10,
                                                  'toadd' => array(0 => __('Infinite'))));
      echo "</td><td>".__('Last run')."</td><td>";

      if (empty($this->fields['lastrun'])) {
         _e('Never');
      } else {
         echo Html::convDateTime($this->fields['lastrun']);
         echo "&nbsp;";
         Html::showSimpleForm(static::getFormURL(), 'resetdate', __('Blank'),
                              array('id' => $ID), $CFG_GLPI['root_doc']."/pics/reset.png");
      }
      echo "</td></tr>";

      $label = $this->getParameterDescription();
      echo "<tr class='tab_bg_1'><td>";
      if (empty($label)) {
         echo "&nbsp;</td><td>&nbsp;";
      } else {
         echo $label."&nbsp;</td><td>";
         Dropdown::showNumber('param', array('value' => $this->fields['param'],
                                             'min'   => 0,
                                             'max'   => 400));
      }
      echo "</td><td>".__('Next run')."</td><td>";

      if ($tmpstate == self::STATE_RUNNING) {
         $launch = false;
      } else {
         $launch = $this->fields['allowmode']&self::MODE_INTERNAL;
      }

      if ($tmpstate != self::STATE_WAITING) {
         echo $this->getStateName($tmpstate);
      } else if (empty($this->fields['lastrun'])) {
         _e('As soon as possible');
      } else {
         $next = strtotime($this->fields['lastrun'])+$this->fields['frequency'];
         $h    = date('H',$next);
         $deb  = ($this->fields['hourmin'] < 10 ? "0".$this->fields['hourmin']
                                                : $this->fields['hourmin']);
         $fin  = ($this->fields['hourmax'] < 10 ? "0".$this->fields['hourmax']
                                                : $this->fields['hourmax']);

         if (($deb < $fin)
             && ($h < $deb)) {
            $disp = date('Y-m-d', $next). " $deb:00:00";
            $next = strtotime($disp);
         } else if (($deb < $fin)
                    && ($h >= $this->fields['hourmax'])) {
            $disp = date('Y-m-d', $next+DAY_TIMESTAMP). " $deb:00:00";
            $next = strtotime($disp);
         }

         if (($deb > $fin)
             && ($h < $deb)
             && ($h >= $fin)) {
            $disp = date('Y-m-d', $next). " $deb:00:00";
            $next = strtotime($disp);
         } else {
            $disp = date("Y-m-d H:i:s", $next);
         }

         if ($next < time()) {
            echo __('As soon as possible').'<br>('.Html::convDateTime($disp).') ';
         } else {
            echo Html::convDateTime($disp);
         }
      }

      if ($launch) {
         echo "&nbsp;";
         Html::showSimpleForm(static::getFormURL(), array('execute' => $this->fields['name']),
                              __('Execute'));
      }
      if ($tmpstate == self::STATE_RUNNING) {
         Html::showSimpleForm(static::getFormURL(), 'resetstate', __('Blank'),
                              array('id' => $ID), $CFG_GLPI['root_doc']."/pics/reset.png");
      }
      echo "</td></tr>";

      $this->showFormButtons($options);

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

      if (!isset($this->fields['id']) || ($this->fields['id'] != $id)) {
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
    * @param $name     select name
    * @param $value    default value (default 0)
    * @param $display  display or get string (true by default)
    *
    * @return nothing (display)
   **/
   static function dropdownState($name, $value=0, $display=true) {

      return Dropdown::showFromArray($name,
                                     array(self::STATE_DISABLE => __('Disabled'),
                                           self::STATE_WAITING => __('Scheduled')),
                                     array('value'   => $value,
                                           'display' => $display));
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
    * Get a global database lock for cron
    *
    * @return Boolean
   **/
   static private function get_lock() {
      global $DB;

      // Changer de nom toutes les heures en cas de blocage MySQL (ca arrive)
      $nom = "glpicron." . intval(time()/HOUR_TIMESTAMP-340000);

      if ($DB->getLock($nom)) {
         self::$lockname = $nom;
         return true;
      }

      return false;
   }


   /**
    * Release the global database lock
   **/
   static private function release_lock() {
      global $DB;

      if (self::$lockname) {
         $DB->releaseLock(self::$lockname);
         self::$lockname = '';
      }
   }


   /**
    * Launch the need cron tasks
    *
    * @param $mode   (internal/external, <0 to force)
    * @param $max    number of task to launch (default 1)
    * @param $name   of task to run (default '')
    *
    * @return the name of last task launched
   **/
   static public function launch($mode, $max=1, $name='') {
      global $CFG_GLPI;

      // No cron in maintenance mode
      if (isset($CFG_GLPI['maintenance_mode']) && $CFG_GLPI['maintenance_mode']) {
         return false;
      }

      $crontask = new self();
      $taskname = '';
      if (abs($mode) == self::MODE_EXTERNAL) {
         // If cron is launched in command line, and if memory is insufficient,
         // display a warning in the logs
         if (Toolbox::checkMemoryLimit() == 2) {
            Toolbox::logInFile('cron', __('A minimum of 64 Mio is commonly required for GLPI.')."\n");
         }
         // If no task in CLI mode, call cron.php from command line is not really usefull ;)
         if (!countElementsInTable($crontask->getTable(), "`mode` = '".abs($mode)."'")) {
            Toolbox::logInFile('cron',
                               __('No task with Run mode = CLI, fix your tasks configuration')."\n");
         }
      }

      if (self::get_lock()) {
         for ($i=1 ; $i<=$max ; $i++) {
            $prefix = (abs($mode) == self::MODE_EXTERNAL ? __('External')
                                                         : __('Internal'));
            if ($crontask->getNeedToRun($mode, $name)) {
               $_SESSION["glpicronuserrunning"] = "cron_".$crontask->fields['name'];

               if ($plug = isPluginItemType($crontask->fields['itemtype'])) {
                  Plugin::load($plug['plugin'], true);
               }
               $fonction = array($crontask->fields['itemtype'],
                                 'cron' . $crontask->fields['name']);

               if (is_callable($fonction)) {
                  if ($crontask->start()) { // Lock in DB + log start
                     $taskname = $crontask->fields['name'];
                     //TRANS: %1$s is mode (external or internal), %2$s is an order number,
                     $msgcron = sprintf(__('%1$s #%2$s'), $prefix, $i);
                     $msgcron = sprintf(__('%1$s: %2$s'), $msgcron,
                                        sprintf(__('%1$s %2$s')."\n",
                                                __('Launch'), $crontask->fields['name']));
                     Toolbox::logInFile('cron', $msgcron);
                     $retcode = call_user_func($fonction, $crontask);
                     $crontask->end($retcode); // Unlock in DB + log end
                  } else {
                     $msgcron = sprintf(__('%1$s #%2$s'), $prefix, $i);
                     $msgcron = sprintf(__('%1$s: %2$s'), $msgcron,
                                        sprintf(__('%1$s %2$s')."\n",
                                                __("Can't start"), $crontask->fields['name']));
                     Toolbox::logInFile('cron', $msgcron);
                  }

               } else {
                  if (is_array($fonction)) {
                     $fonction = implode('::',$fonction);
                  }
                  Toolbox::logInFile('php-errors',
                                     sprintf(__('Undefined function %s (for cron)')."\n",
                                             $fonction));
                  $msgcron = sprintf(__('%1$s #%2$s'), $prefix, $i);
                  $msgcron = sprintf(__('%1$s: %2$s'), $msgcron,
                                     sprintf(__('%1$s %2$s')."\n",
                                             __("Can't start"), $crontask->fields['name']));
                  Toolbox::logInFile('cron', $msgcron ."\n".
                                             sprintf(__('Undefined function %s (for cron)')."\n",
                                                     $fonction));
               }

            } else if ($i==1) {
               $msgcron = sprintf(__('%1$s #%2$s'), $prefix, $i);
               $msgcron = sprintf(__('%1$s: %2$s'), $msgcron, __('Nothing to launch'));
               Toolbox::logInFile('cron', $msgcron."\n");
            }
         } // end for
         $_SESSION["glpicronuserrunning"]='';
         self::release_lock();

      } else {
         Toolbox::logInFile('cron', __("Can't get DB lock")."\n");
      }

      return $taskname;
   }


   /**
    * Register new task for plugin (called by plugin during install)
    *
    * @param $itemtype        itemtype of the plugin object
    * @param $name            of the task
    * @param $frequency       of execution
    * @param $options   array of optional options
    *       (state, mode, allowmode, hourmin, hourmax, logs_lifetime, param, comment)
    *
    * @return bool for success
   **/
   static public function register($itemtype, $name, $frequency, $options=array()) {

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
                     'allowmode' => self::MODE_INTERNAL | self::MODE_EXTERNAL,
                     'frequency' => $frequency);

      foreach (array('allowmode', 'comment', 'hourmax', 'hourmin', 'logs_lifetime', 'mode',
                     'param', 'state') as $key) {
         if (isset($options[$key])) {
            $input[$key] = $options[$key];
         }
      }
      if (defined('GLPI_SYSTEM_CRON')
          && ($input['allowmode'] & self::MODE_EXTERNAL)
          && !isset($input['mode'])) {
         // Downstream packages may provide a good system cron
         $input['mode'] = self::MODE_EXTERNAL;
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
   static public function unregister($plugin) {
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
      echo "<tr><th colspan='2'>&nbsp;".__('Statistics')."</th></tr>\n";

      $nbstart = countElementsInTable('glpi_crontasklogs',
                                      "`crontasks_id` = '".$this->fields['id']."'
                                          AND `state` = '".CronTaskLog::STATE_START."'");
      $nbstop  = countElementsInTable('glpi_crontasklogs',
                                      "`crontasks_id` = '".$this->fields['id']."'
                                          AND `state` = '".CronTaskLog::STATE_STOP."'");

      echo "<tr class='tab_bg_2'><td>".__('Run count')."</td><td class='right'>";
      if ($nbstart == $nbstop) {
         echo $nbstart;
      } else {
         // This should not appen => task crash ?
         //TRANS: %s is the number of starts
         printf(_n('%s start', '%s starts', $nbstart), $nbstart);
         echo "<br>";
         //TRANS: %s is the number of stops
         printf(_n('%s stop', '%s stops', $nbstop), $nbstop);
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
            echo "<td class='right'>".sprintf(_n('%s second', '%s seconds', $data['elapsedmin']),
                                              number_format($data['elapsedmin'], 2));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Maximal time')."</td>";
            echo "<td class='right'>".sprintf(_n('%s second', '%s seconds', $data['elapsedmax']),
                                              number_format($data['elapsedmax'], 2));
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Average time')."</td>";
            echo "<td class='right b'>".sprintf(_n('%s second', '%s seconds', $data['elapsedavg']),
                                                number_format($data['elapsedavg'],2));
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Total duration')."</td>";
            echo "<td class='right'>".sprintf(_n('%s second', '%s seconds', $data['elapsedtot']),
                                              number_format($data['elapsedtot'],2));
            echo "</td></tr>";
         }

         if ($data
             && ($data['voltot'] > 0)) {
            echo "<tr class='tab_bg_2'><td>".__('Minimal count')."</td>";
            echo "<td class='right'>".sprintf(_n('%s item', '%s items', $data['volmin']),
                                              $data['volmin'])."</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Maximal count')."</td>";
            echo "<td class='right'>".sprintf(_n('%s item', '%s items', $data['volmax']),
                                              $data['volmax'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Average count')."</td>";
            echo "<td class='right b'>".sprintf(_n('%s item', '%s items', $data['volavg']),
                                                number_format($data['volavg'],2)).
                 "</td></tr>";

            echo "<tr class='tab_bg_1'><td>".__('Total count')."</td>";
            echo "<td class='right'>". sprintf(_n('%s item', '%s items',$data['voltot']),
                                               $data['voltot'])."</td></tr>";

            echo "<tr class='tab_bg_2'><td>".__('Average speed')."</td>";
            echo "<td class='left'>".sprintf(__('%s items/sec'),
                                             number_format($data['voltot']/$data['elapsedtot'],2));
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

      if (isset($_GET["crontasklogs_id"]) && $_GET["crontasklogs_id"]) {
         return $this->showHistoryDetail($_GET["crontasklogs_id"]);
      }

      if (isset($_GET["start"])) {
         $start = $_GET["start"];
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

      if ($result = $DB->query($query)) {
         if ($data = $DB->fetch_assoc($result)) {
            echo "<table class='tab_cadrehov'>";
            $header = "<tr>";
            $header .= "<th>".__('Date')."</th>";
            $header .= "<th>".__('Total duration')."</th>";
            $header .= "<th>"._x('quantity', 'Number')."</th>";
            $header .= "<th>".__('Description')."</th>";
            $header .= "</tr>\n";
            echo $header;

            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td><a href='javascript:reloadTab(\"crontasklogs_id=".
                          $data['crontasklogs_id']."\");'>".Html::convDateTime($data['date']).
                    "</a></td>";
               echo "<td class='right'>".sprintf(_n('%s second','%s seconds',
                                                    intval($data['elapsed'])),
                                                 number_format($data['elapsed'], 3)).
                    "&nbsp;&nbsp;&nbsp;</td>";
               echo "<td class='numeric'>".$data['volume']."</td>";
               // Use gettext to display
               echo "<td>".__($data['content'])."</td>";
               echo "</tr>\n";
            } while ($data = $DB->fetch_assoc($result));
            echo $header;
            echo "</table>";

         } else { // Not found
            _e('No item found');
         }
      } // Query
      Html::printAjaxPager(__('Last run list'), $start, $number);

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

      if ($result = $DB->query($query)) {
         if ($data = $DB->fetch_assoc($result)) {
            echo "<table class='tab_cadrehov'><tr>";
            echo "<th>".__('Date')."</th>";
            echo "<th>".__('Status')."</th>";
            echo "<th>". __('Duration')."</th>";
            echo "<th>"._x('quantity', 'Number')."</th>";
            echo "<th>".__('Description')."</th>";
            echo "</tr>\n";

            $first = true;
            do {
               echo "<tr class='tab_bg_2'>";
               echo "<td class='center'>".($first ? Html::convDateTime($data['date'])
                                                  : "&nbsp;")."</a></td>";
               $content = $data['content'];
               switch ($data['state']) {
                  case CronTaskLog::STATE_START :
                     echo "<td>".__('Start')."</td>";
                     // Pass content to gettext
                     // implode (Run mode: XXX)
                     $list = explode(':',$data['content']);
                     if (count($list)==2) {
                        $content = sprintf('%1$s: %2$s', __($list[0]), $list[1]);
                     }
                     break;

                  case CronTaskLog::STATE_STOP :
                     echo "<td>".__('End')."</td>";
                     // Pass content to gettext
                     $content = __($data['content']);
                     break;

                  default :
                     echo "<td>".__('Running')."</td>";
                     // Pass content to gettext
                     $content = __($data['content']);
               }


               echo "<td class='right'>".sprintf(_n('%s second', '%s seconds',
                                                    intval($data['elapsed'])),
                                                 number_format($data['elapsed'], 3)).
                    "&nbsp;&nbsp;</td>";
               echo "<td class='numeric'>".$data['volume']."</td>";

               echo "<td>".$content."</td>";
               echo "</tr>\n";
               $first = false;
            } while ($data = $DB->fetch_assoc($result));

            echo "</table>";

         } else { // Not found
            _e('No item found');
         }
      } // Query

      echo "</div>";
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = 0;
      switch ($field) {
         case 'mode':
            $options['value']         = $values[$field];
            $tab[self::MODE_INTERNAL] = self::getModeName(self::MODE_INTERNAL);
            $tab[self::MODE_EXTERNAL] = self::getModeName(self::MODE_EXTERNAL);
            return Dropdown::showFromArray($name, $tab, $options);

         case 'state' :
            return CronTask::dropdownState($name, $values[$field], false);
      }

      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'mode':
            return self::getModeName($values[$field]);

         case 'state':
            return self::getStateName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         $actions[__CLASS__.MassiveAction::CLASS_ACTION_SEPARATOR.'reset'] = __('Reset last run');
      }
      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'reset' :
            if (Config::canUpdate()) {
               foreach ($ids as $key) {
                  if ($item->getFromDB($key)) {
                     if ($item->resetDate()) {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  }
               }
            } else {
               $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
               $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   function getSearchOptions() {

      $tab                     = array();
      $tab['common']           = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;
      $tab[2]['datatype']      = 'number';

      $tab[3]['table']         = $this->getTable();
      $tab[3]['field']         = 'description';
      $tab[3]['name']          = __('Description');
      $tab[3]['nosearch']      = true;
      $tab[3]['nosort']        = true;
      $tab[3]['massiveaction'] = false;
      $tab[3]['datatype']      = 'text';
      $tab[3]['computation']   = "TABLE.`id`"; // Virtual data

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'state';
      $tab[4]['name']          = __('Status');
      $tab[4]['searchtype']    = array('equals', 'notequals');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'specific';

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'mode';
      $tab[5]['name']          = __('Run mode');
      $tab[5]['datatype']      = 'specific';
      $tab[5]['searchtype']    = array('equals', 'notequals');

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
      $tab[8]['name']          = __('Item type');
      $tab[8]['massiveaction'] = false;
      $tab[8]['datatype']      = 'itemtypename';
      $tab[8]['types']         = self::getUsedItemtypes();


      $tab[16]['table']        = $this->getTable();
      $tab[16]['field']        = 'comment';
      $tab[16]['name']         = __('Comments');
      $tab[16]['datatype']     = 'text';

      $tab[17]['table']        = $this->getTable();
      $tab[17]['field']        = 'hourmin';
      $tab[17]['name']         = __('Begin hour of run period');
      $tab[17]['datatype']     = 'integer';
      $tab[17]['min']          = 0;
      $tab[17]['max']          = 24;

      $tab[18]['table']        = $this->getTable();
      $tab[18]['field']        = 'hourmax';
      $tab[18]['name']         = __('End hour of run period');
      $tab[18]['datatype']     = 'integer';
      $tab[18]['min']          = 0;
      $tab[18]['max']          = 24;

      $tab[19]['table']        = $this->getTable();
      $tab[19]['field']        = 'logs_lifetime';
      $tab[19]['name']         = __('Number of days this action logs are stored');
      $tab[19]['datatype']     = 'integer';
      $tab[19]['min']          = 10;
      $tab[19]['max']          = 360;
      $tab[19]['step']         = 10;
      $tab[19]['toadd']        = array(0 => __('Infinite'));

      $tab[20]['table']          = $this->getTable();
      $tab[20]['field']          = 'date_mod';
      $tab[20]['name']           = __('Last update');
      $tab[20]['datatype']       = 'datetime';
      $tab[20]['massiveaction']  = false;

      $tab[121]['table']          = $this->getTable();
      $tab[121]['field']          = 'date_creation';
      $tab[121]['name']           = __('Creation date');
      $tab[121]['datatype']       = 'datetime';
      $tab[121]['massiveaction']  = false;

      return $tab;
   }


   /**
    * Garbage collector for expired file session
    *
    * @param $task for log
   **/
   static function cronSession($task) {

      // max time to keep the file session
      $maxlifetime = ini_get('session.gc_maxlifetime');
      if ($maxlifetime == 0) {
         $maxlifetime == WEEK_TIMESTAMP;
      }
      $nb = 0;
      foreach (glob(GLPI_SESSION_DIR."/sess_*") as $filename) {
         if ((filemtime($filename) + $maxlifetime) < time()) {
            // Delete session file if not delete before
            if (@unlink($filename)) {
               $nb++;
            }
         }
      }

      $task->setVolume($nb);
      if ($nb) {
         //TRANS: % %1$d is a number, %2$s is a number of seconds
         $task->log(sprintf(_n('Clean %1$d session file created since more than %2$s seconds',
                               'Clean %1$d session files created since more than %2$s seconds',
                               $nb)."\n",
                            $nb, $maxlifetime));
         return 1;
      }

      return 0;
   }


   /**
    * Circular logs
    *
    * @since version 0.85
    *
    * @param $task for log
   **/
   static function cronCircularlogs($task) {

      $actionCode = 0; // by default
      $error      = false ;
      $task->setVolume(0); // start with zero

      // compute date in the past for the archived log to be deleted
      $firstdate = date("Ymd", time() - ($task->fields['param'] * DAY_TIMESTAMP)); // compute current date - param as days and format it like YYYYMMDD

      // first look for bak to delete
      $dir       = GLPI_LOG_DIR."/*.bak" ;
      $findfiles = glob( $dir ) ;
      foreach ($findfiles as $file) {
         $shortfile = str_replace(GLPI_LOG_DIR.'/','',$file);
         // now depending on the format of the name we delete the file (for aging archives) or rename it (will add Ymd.log to the end of the file)
         $match = null;
         if (preg_match('/.+[.]log[.](\\d{8})[.]bak$/', $file, $match) > 0) {
            if ($match[1] < $firstdate ) {
               $task->addVolume(1);
               if (unlink($file)) {
                  $task->log(sprintf(__('Deletion of archived log file: %s'), $shortfile));
                  $actionCode = 1 ;
               } else {
                  $task->log(sprintf(__('Unable to delete archived log file: %s'), $shortfile));
                  $error = true ;
               }
            }
         }
      }

      // second look for log to archive
      $dir       = GLPI_LOG_DIR."/*.log" ;
      $findfiles = glob( $dir ) ;
      foreach ($findfiles as $file) {
         $shortfile    = str_replace(GLPI_LOG_DIR.'/','',$file);
         // rename the file
         $newfilename  = $file.".".date("Ymd", time()).".bak"; // will add to filename a string with format YYYYMMDD (= current date)
         $shortnewfile = str_replace(GLPI_LOG_DIR.'/','',$newfilename);

         $task->addVolume(1);
         if (!file_exists($newfilename) && rename($file, $newfilename)) {
            $task->log(sprintf(__('Archiving log file: %1$s to %2$s'), $shortfile, $shortnewfile));
            $actionCode = 1 ;
         } else {
            $task->log(sprintf(__('Unable to archive log file: %1$s. %2$s already exists. Wait till next day.'),
                                 $shortfile, $shortnewfile)) ;
            $error = true ;
         }
      }

      if ($error) {
         return -1 ;
      }
      return $actionCode;
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
         if (basename($filename) == "remove.txt" && is_dir(GLPI_ROOT.'/.git')) {
            continue;
         }
         if ((filemtime($filename) + $maxlifetime) < time()) {
            if (@unlink($filename)) {
               $nb++;
            }
         }
      }

      $task->setVolume($nb);
      if ($nb) {
         $task->log(sprintf(_n('Clean %1$d graph file created since more than %2$s seconds',
                               'Clean %1$d graph files created since more than %2$s seconds',
                               $nb)."\n",
                            $nb, $maxlifetime));
         return 1;
      }

      return 0;
   }

   /**
    * Garbage collector for cleaning tmp files
    *
    * @param $task for log
   **/
   static function cronTemp($task) {
      global $CFG_GLPI;

      // max time to keep the file session
      $maxlifetime = HOUR_TIMESTAMP;
      $nb          = 0;
      foreach (glob(GLPI_TMP_DIR."/*") as $filename) {
         if (basename($filename) == "remove.txt" && is_dir(GLPI_ROOT.'/.git')) {
            continue;
         }
         if ((filemtime($filename) + $maxlifetime) < time()) {
            if (@unlink($filename)) {
               $nb++;
            }
         }
      }

      $task->setVolume($nb);
      if ($nb) {
         $task->log(sprintf(_n('Clean %1$d temporary file created since more than %2$s seconds',
                               'Clean %1$d temporary files created since more than %2$s seconds',
                               $nb)."\n",
                            $nb, $maxlifetime));
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
      return ($vol > 0 ? 1 : 0);
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
         $task = new self();
         $task->getFromDBByQuery("WHERE `itemtype` = 'Crontask' AND `name` = 'watcher'");
         if (NotificationEvent::raiseEvent("alert", $task,  array('items' => $crontasks))) {
            $cron_status = 1;
            $task->addVolume(1);
         }
         QueuedMail::forceSendFor($task->getType(), $task->fields['id']);
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
                         'parameter'
                           => __('System logs retention period (in days, 0 for infinite)'));

         case 'optimize' :
            return array('description' => __('Database optimization'));

         case 'session' :
            return array('description' => __('Clean expired sessions'));

         case 'graph' :
            return array('description' => __('Clean generated graphics'));

         case 'temp' :
            return array('description' => __('Clean temporary files'));

         case 'watcher' :
            return array('description' => __('Monitoring of automatic actions'));

         case 'circularlogs' :
            return array('description' => __("Archives log files and deletes aging ones"),
                         'parameter'   => __("Number of days to keep archived logs"));
      }
   }


   /**
    * Dropdown for frequency (interval between 2 actions)
    *
    * @param $name   select name
    * @param $value  default value (default 0)
   **/
   function dropdownFrequency($name, $value=0) {

      $tab = array();

      $tab[MINUTE_TIMESTAMP] = sprintf(_n('%d minute','%d minutes',1),1);

      // Minutes
      for ($i=5 ; $i<60 ; $i+=5) {
         $tab[$i*MINUTE_TIMESTAMP] = sprintf(_n('%d minute','%d minutes',$i), $i);
      }

      // Heures
      for ($i=1 ; $i<24 ; $i++) {
         $tab[$i*HOUR_TIMESTAMP] = sprintf(_n('%d hour','%d hours',$i), $i);
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
         if ((time() - $_SESSION["glpicrontimer"]) > 300) {

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
