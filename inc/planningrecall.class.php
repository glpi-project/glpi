<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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

// Class PlanningRecall
class PlanningRecall extends CommonDBTM {

   static function getTypeName($nb=0) {
      return _n('Planning recall', 'Planning recalls', $nb);
   }
   
   function canCreate() {
      return true;
   }
   
   function canCreateItem() {      
      return $this->fields['users_id'] == Session::getLoginUserID();
   }
      
   ///TODO create Cron job
   
   
   /**
    * Retrieve an item from the database
    *
    * @param $itemtype string itemtype to get
    * @param $items_id integer id of the item
    * @param $users_id integer id of the user
    *
    * @return true if succeed else false
   **/
   function getFromDBForItemAndUser($itemtype, $items_id, $users_id) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `itemtype` = '$itemtype'
                  AND `items_id` = '$items_id'
                  AND `users_id` = '$users_id'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $this->fields = $DB->fetch_assoc($result);
            return true;
         }
      }
      return false;
   }
   
   function post_updateItem($history=1) {
         $alert = new Alert();
         $alert->clear($this->getType(), $this->fields['id'], Alert::ACTION);   
   }
  
   /**
    * Manage recall set
    *
    * @param $data array of data to manage
   **/        
   static function manageDatas(array $data) {
      // Check data informations
      if (!isset($data['itemtype']) || !isset($data['items_id'])
         || !isset($data['users_id']) || !isset($data['before_time'])) {
         return false;   
      }
      $pr = new self();
      // Datas OK : check if recall already exists
      if ($pr->getFromDBForItemAndUser($data['itemtype'], $data['items_id'], 
                                       $data['users_id'])) {
         if ($data['before_time'] != $pr->fields['before_time']) {
            // Recall exists and is different : update datas and clean alert
            if ($pr->can($pr->fields['id'],'w')) {
               $pr->update(array('id'          => $pr->fields['id'],
                                 'before_time' => $data['before_time']));
            }
         }
      } else {
         // Recall does not exists : create it
         if ($pr->can(-1,'w',$data)) {
            $pr->add($data);
         }
      }
   }
   
   /**
    * Make a select box with recall times
    *
    * Mandatory options : itemtype, items_id
    *
    * Parameters which could be used in options array :
    *    - itemtype : string itemtype 
    *    - items_id : integer id of the item
    *    - users_id : integer id of the user (if not set used login user)
    *
    * @param $options possible options
    *
    * @return nothing (print out an HTML select box) / return false if mandatory fields are not ok
   **/
   static function dropdown($options=array()) {
      global $DB, $CFG_GLPI;

      // Default values
      $p['itemtype']       = '';
      $p['items_id']       = 0;
      $p['users_id']       = Session::getLoginUserID();
      $p['value']          = Entity::CONFIG_NEVER;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }
      if (!($item = getItemForItemtype($p['itemtype']))) {
         return false;
      } // Do not check items_id and item get because may be used when creating item (task for example) 
      
      $pr = new self();
      // Get recall for item and user
      if ($pr->getFromDBForItemAndUser($p['itemtype'], $p['items_id'], $p['users_id'])) {
         $p['value'] = $pr->fields['before_time'];
      }
      
      $possible_values = array();
      $possible_values[Entity::CONFIG_NEVER] = __('No');
      $possible_values[0] = __('Begin');
      for ($i=1 ; $i<24 ; $i++) {
         $possible_values[$i*HOUR_TIMESTAMP] = sprintf(_n('- %1$d hour','+ %1$d hours',$i),
                                                            $i);
      }

      for ($i=1 ; $i<30 ; $i++) {
         $possible_values[$i*DAY_TIMESTAMP] = sprintf(_n('- %1$d day','+ %1$d days',$i), $i);
      }
      ksort($possible_values);
      
      Dropdown::showFromArray('_planningrecall[before_time]', $possible_values, array('value' => $p['value']));
      echo "<input type='hidden' name='_planningrecall[itemtype]' value='".$p['itemtype']."'>";
      echo "<input type='hidden' name='_planningrecall[items_id]' value='".$p['items_id']."'>";
      echo "<input type='hidden' name='_planningrecall[users_id]' value='".$p['users_id']."'>";
      return true;
   }
   
   
   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'planningrecall' :
            return array('description' => __('Send planning recalls'));
      }
      return array();
   }
   
   /**
    * Cron action on contracts : alert depending of the config : on notice and expire
    *
    * @param $task for log, if NULL display (default NULL)
   **/
   static function cronPlanningRecall($task=NULL) {
      global $DB, $CFG_GLPI;

      if (!$CFG_GLPI["use_mailing"]) {
         return 0;
      }

      $cron_status   = 0;

      return $cron_status;
   }   
}
?>
