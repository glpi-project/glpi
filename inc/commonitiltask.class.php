<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

abstract class CommonITILTask  extends CommonDBTM {


   // From CommonDBTM
   public $auto_message_on_action = false;

   function getItilObjectItemType() {
      return str_replace('Task','',$this->getType());
   }


   function canViewPrivates() {
      return false;
   }


   function canEditAll() {
      return false;
   }


   /**
    * can read the parent ITIL Object ?
    *
    * @return boolean
   **/
   function canReadITILItem() {
      $itemtype = $this->getItilObjectItemType();

      $item = new $itemtype();
      if (!$item->can($this->getField($item->getForeignKeyField()),'r')) {
         return false;
      }
      return true;
   }


   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
    *
    * @return $LANG
   **/
   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['mailing'][142];
      }
      return $LANG['job'][7];
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if ($item->getType() == $this->getItilObjectItemType() && $item->canView()) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            $restrict = "`".$item->getForeignKeyField()."` = '".$item->getID()."'";

            if ($this->maybePrivate() && !$this->canViewPrivates()) {
               $restrict .= " AND (`is_private` = '0'
                                   OR `users_id` = '" . Session::getLoginUserID() . "') ";
            }

            return self::createTabEntry($LANG['mailing'][142],
                                        countElementsInTable($this->getTable(), $restrict));
         }
         return $LANG['mailing'][142];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      $itemtype = $item->getType().'Task';
      $task     = new $itemtype();
      $task->showSummary($item);
      return true;
   }


   function post_deleteFromDB() {

      $itemtype = $this->getItilObjectItemType();
      $item     = new $itemtype();
      $item->getFromDB($this->fields[$item->getForeignKeyField()]);
      $item->updateActiontime($this->fields[$item->getForeignKeyField()]);
      $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

      // Add log entry in the ITIL object
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField($item->getForeignKeyField()), $this->getItilObjectItemType(),
                   $changes, $this->getType(), Log::HISTORY_DELETE_SUBITEM);

      $options = array('task_id'     => $this->fields["id"],
                        // Force is_private with data / not available
                        'is_private' => $this->isPrivate());
      NotificationEvent::raiseEvent('delete_task', $item, $options);
   }


   function prepareInputForUpdate($input) {
      global $LANG;

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

//      $input["actiontime"] = $input["hour"]*HOUR_TIMESTAMP+$input["minute"]*MINUTE_TIMESTAMP;

      if (isset($input['update']) && $uid=Session::getLoginUserID()) { // Change from task form
         $input["users_id"] = $uid;
      }
      if (isset($input["plan"])) {
         $input["begin"]         = $input['plan']["begin"];
         $input["end"]           = $input['plan']["end"];
         $input["users_id_tech"] = $input['plan']["users_id"];

         $timestart              = strtotime($input["begin"]);
         $timeend                = strtotime($input["end"]);
         $input["actiontime"]    = $timeend-$timestart;

         unset($input["plan"]);

         if (!$this->test_valid_date($input)) {
            Session::addMessageAfterRedirect($LANG['planning'][1], false, ERROR);
            return false;
         }
         Planning::checkAlreadyPlanned($input["users_id_tech"], $input["begin"], $input["end"],
                                       array($this->getType() => array($input["id"])));
      }

      return $input;
   }


   function post_updateItem($history=1) {
      global $CFG_GLPI;

      $update_done = false;
      $itemtype    = $this->getItilObjectItemType();
      $item        = new $itemtype();

      if ($item->getFromDB($this->fields[$item->getForeignKeyField()])) {
         $item->updateDateMod($this->fields[$item->getForeignKeyField()]);

         if (count($this->updates)) {
            $update_done = true;

            if (in_array("actiontime",$this->updates)) {
               $item->updateActionTime($this->input[$item->getForeignKeyField()]);
            }

            if (!empty($this->fields['begin'])
                && ($item->fields["status"]=="new" || $item->fields["status"]=="assign")) {

               $input2['id']            = $item->getID();
               $input2['status']        = "plan";
               $input2['_disablenotif'] = true;
               $item->update($input2);
            }

            if ($CFG_GLPI["use_mailing"]) {
               $options = array('task_id'    => $this->fields["id"],
                                'is_private' => $this->isPrivate());
               NotificationEvent::raiseEvent('update_task', $item, $options);
            }

         }
      }

      if ($update_done) {
         // Add log entry in the ITIL object
         $changes[0] = 0;
         $changes[1] = '';
         $changes[2] = $this->fields['id'];
         Log::history($this->getField($item->getForeignKeyField()), $itemtype, $changes,
                      $this->getType(), Log::HISTORY_UPDATE_SUBITEM);
      }
   }


   function prepareInputForAdd($input) {
      global $LANG;

      $itemtype = $this->getItilObjectItemType();

      Toolbox::manageBeginAndEndPlanDates($input['plan']);

      if (isset($input["plan"])) {
         $input["begin"]         = $input['plan']["begin"];
         $input["end"]           = $input['plan']["end"];
         $input["users_id_tech"] = $input['plan']["users_id"];

         $timestart              = strtotime($input["begin"]);
         $timeend                = strtotime($input["end"]);
         $input["actiontime"]    = $timeend-$timestart;

         unset($input["plan"]);
         if (!$this->test_valid_date($input)) {
            Session::addMessageAfterRedirect($LANG['planning'][1], false, ERROR);
            return false;
         }
      }

      $input["_job"] = new $itemtype();

      if (!$input["_job"]->getFromDB($input[$input["_job"]->getForeignKeyField()])) {
         return false;
      }

      // Pass old assign From object in case of assign change
      if (isset($input["_old_assign"])) {
         $input["_job"]->fields["_old_assign"] = $input["_old_assign"];
      }

      if (!isset($input["users_id"]) && $uid=Session::getLoginUserID()) {
         $input["users_id"] = $uid;
      }

      if (!isset($input["is_private"])) {
         $input['is_private'] = 0;
      }

      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      $donotif = $CFG_GLPI["use_mailing"];

      if (isset($this->fields["begin"]) && !empty($this->fields["begin"])) {
         Planning::checkAlreadyPlanned($this->fields["users_id_tech"], $this->fields["begin"],
                                       $this->fields["end"],
                                       array($this->getType() => array($this->fields["id"])));
      }

      if (isset($this->input["_no_notif"]) && $this->input["_no_notif"]) {
         $donotif = false;
      }

      $this->input["_job"]->updateDateMod($this->input[$this->input["_job"]->getForeignKeyField()]);

      if (isset($this->input["actiontime"]) && $this->input["actiontime"]>0) {
         $this->input["_job"]->updateActionTime($this->input[$this->input["_job"]->getForeignKeyField()]);
      }

      if (!empty($this->fields['begin'])
          && ($this->input["_job"]->fields["status"]=="new"
              || $this->input["_job"]->fields["status"]=="assign")) {

         $input2['id']            = $this->input["_job"]->getID();
         $input2['status']        = "plan";
         $input2['_disablenotif'] = true;
         $this->input["_job"]->update($input2);
      }

      if ($donotif) {
         $options = array('task_id'    => $this->fields["id"],
                          'is_private' => $this->isPrivate());
         NotificationEvent::raiseEvent('add_task', $this->input["_job"], $options);
      }

      // Add log entry in the ITIL object
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = $this->fields['id'];
      Log::history($this->getField($this->input["_job"]->getForeignKeyField()),
                   $this->input["_job"]->getTYpe(), $changes, $this->getType(),
                   Log::HISTORY_ADD_SUBITEM);
   }


   function post_getEmpty() {

      if ($this->maybePrivate()
          && isset($_SESSION['glpitask_private'])
          && $_SESSION['glpitask_private']) {

         $this->fields['is_private'] = 1;
      }
      // Default is todo
      $this->fields['state'] = 1;
   }


   // SPECIFIC FUNCTIONS

   /**
    * Get the users_id name of the followup
    * @param $link insert link ?
    *
    *@return string of the users_id name
   **/
   function getAuthorName($link=0) {
      return getUserName($this->fields["users_id"], $link);
   }


   function getName($with_comment=0) {
      global $LANG;

      if (!isset($this->fields['taskcategories_id'])) {
         return NOT_AVAILABLE;
      }

      if ($this->fields['taskcategories_id']) {
         $name = Dropdown::getDropdownName('glpi_taskcategories',
                                           $this->fields['taskcategories_id']);
      } else {
         $name = $this->getTypeName();
      }

      if ($with_comment) {
         $name .= ' ('.Html::convDateTime($this->fields['date']);
         $name .= ', '.getUserName($this->fields['users_id']);
         // Manage private case
         if (isset($this->maybeprivate)) {
            $name .= ', '.($this->fields['is_private'] ? $LANG['common'][77] : $LANG['common'][76]);
         }
         $name .= ')';
      }
      return $name;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table'] = $this->getTable();
      $tab[1]['field'] = 'content';
      $tab[1]['name']  = $LANG['job'][7]." - ".$LANG['joblist'][6];

      $tab[2]['table']        = 'glpi_taskcategories';
      $tab[2]['field']        = 'name';
      $tab[2]['name']         = $LANG['job'][7]." - ".$LANG['common'][36];
      $tab[2]['forcegroupby'] = true;

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'date';
      $tab[3]['name']     = $LANG['common'][26];
      $tab[3]['datatype'] = 'datetime';

      if ($this->maybePrivate()) {
         $tab[4]['table']    = $this->getTable();
         $tab[4]['field']    = 'is_private';
         $tab[4]['name']     = $LANG['job'][9]. " ".$LANG['common'][77];
         $tab[4]['datatype'] = 'bool';
      }

      $tab[5]['table'] = 'glpi_users';
      $tab[5]['field'] = 'name';
      $tab[5]['name']  = $LANG['financial'][43];

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'actiontime';
      $tab[6]['name']          = $LANG['job'][20];
      $tab[6]['datatype']      = 'actiontime';
      $tab[6]['massiveaction'] = false;

      return $tab;
   }


   /**
    * Current dates are valid ? begin before end
    *
    *@return boolean
   **/
   function test_valid_date($input) {

      return (!empty($input["begin"])
              && !empty($input["end"])
              && strtotime($input["begin"]) < strtotime($input["end"]));
   }


   /**
    * Populate the planning with planned tasks
    *
    * @param $itemtype itemtype
    * @param $options options array must contains :
    *    - who ID of the user (0 = undefined)
    *    - who_group ID of the group of users (0 = undefined)
    *    - begin Date
    *    - end Date
    *
    * @return array of planning item
   **/
   static function genericPopulatePlanning($itemtype, $options=array()) {
      global $DB, $CFG_GLPI;

      $interv = array();

      if (!isset($options['begin']) || ($options['begin'] == 'NULL')
          || !isset($options['end']) || ($options['end'] == 'NULL')) {
         return $interv;
      }

      $item           = new $itemtype();
      $parentitemtype = $item->getItilObjectItemType();
      $parentitem     = new $parentitemtype();

      $who       = $options['who'];
      $who_group = $options['who_group'];
      $begin     = $options['begin'];
      $end       = $options['end'];

      // Get items to print
      $ASSIGN = "";
      if ($who_group==="mine") {
         if (count($_SESSION["glpigroups"])) {
            $groups = implode("','",$_SESSION['glpigroups']);
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` IN (SELECT DISTINCT `users_id`
                                                                   FROM `glpi_groups_users`
                                                                   INNER JOIN `glpi_groups`
                                                                     ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)
                                                                   WHERE `glpi_groups_users`.`groups_id` IN ('$groups')
                                                                           AND `glpi_groups`.`is_assign`)
                                            AND ";
         } else { // Only personal ones
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` = '$who'
                       AND ";
         }

      } else {
         if ($who>0) {
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` = '$who'
                       AND ";
         }

         if ($who_group>0) {
            $ASSIGN = "`".$item->getTable()."`.`users_id_tech` IN (SELECT `users_id`
                                      FROM `glpi_groups_users`
                                      WHERE `groups_id` = '$who_group')
                                            AND ";
         }
      }
      if (empty($ASSIGN)) {
         $ASSIGN = "`".$item->getTable()."`.`users_id_tech` IN (SELECT DISTINCT `glpi_profiles_users`.`users_id`
                                   FROM `glpi_profiles`
                                   LEFT JOIN `glpi_profiles_users`
                                     ON (`glpi_profiles`.`id` = `glpi_profiles_users`.`profiles_id`)
                                   WHERE `glpi_profiles`.`interface` = 'central' ".
                                         getEntitiesRestrictRequest("AND", "glpi_profiles_users", '',
                                                                    $_SESSION["glpiactive_entity"],
                                                                    1);
         $ASSIGN .= ") AND ";
      }

      $addrestrict = '';
      if ($parentitem->maybeDeleted()) {
         $addrestrict .= 'AND NOT `'.$parentitem->getTable().'`.`is_deleted`';
      }
      
      $query = "SELECT `".$item->getTable()."`.*
                FROM `".$item->getTable()."`
                INNER JOIN `".$parentitem->getTable()."`
                  ON (`".$parentitem->getTable()."`.`id` = `".$item->getTable()."`.`".$parentitem->getForeignKeyField()."`)
                WHERE $ASSIGN
                      '$begin' < `".$item->getTable()."`.`end`
                      AND '$end' > `".$item->getTable()."`.`begin`
                      $addrestrict
                ORDER BY `".$item->getTable()."`.`begin`";

      $result = $DB->query($query);

      $interv = array();

      if ($DB->numrows($result)>0) {
         for ($i=0 ; $data=$DB->fetch_array($result) ; $i++) {
            if ($item->getFromDB($data["id"])) {
               if ($parentitem->getFromDBwithData($item->fields[$parentitem->getForeignKeyField()],0)) {
                  // Do not check entity here because webcal used non authenticated access
//                  if (Session::haveAccessToEntity($item->fields["entities_id"])) {
                     $interv[$data["begin"]."$$$".$i][$item->getForeignKeyField()] = $data["id"];
                     $interv[$data["begin"]."$$$".$i]["id"]                        = $data["id"];
                     if (isset($data["state"])) {
                        $interv[$data["begin"]."$$$".$i]["state"]                     = $data["state"];
                     }
                     $interv[$data["begin"]."$$$".$i][$parentitem->getForeignKeyField()]
                                                = $item->fields[$parentitem->getForeignKeyField()];
                     $interv[$data["begin"]."$$$".$i]["users_id"]                  = $data["users_id"];
                     $interv[$data["begin"]."$$$".$i]["users_id_tech"]             = $data["users_id_tech"];

                     if (strcmp($begin,$data["begin"])>0) {
                        $interv[$data["begin"]."$$$".$i]["begin"] = $begin;
                     } else {
                        $interv[$data["begin"]."$$$".$i]["begin"] = $data["begin"];
                     }

                     if (strcmp($end,$data["end"])<0) {
                        $interv[$data["begin"]."$$$".$i]["end"] = $end;
                     } else {
                        $interv[$data["begin"]."$$$".$i]["end"] = $data["end"];
                     }

                     $interv[$data["begin"]."$$$".$i]["name"]     = $parentitem->fields["name"];
                     $interv[$data["begin"]."$$$".$i]["content"]
                                                      = Html::resume_text($parentitem->fields["content"],
                                                                          $CFG_GLPI["cut"]);
                     $interv[$data["begin"]."$$$".$i]["status"]   = $parentitem->fields["status"];
                     $interv[$data["begin"]."$$$".$i]["priority"] = $parentitem->fields["priority"];
                     /// Specific for tickets
                     $interv[$data["begin"]."$$$".$i]["device"] = '';
                     if (isset($parentitem->hardwaredatas)) {
                        $interv[$data["begin"]."$$$".$i]["device"]
                              = ($parentitem->hardwaredatas ?$parentitem->hardwaredatas->getName()
                                                            :'');
                     }
//                  }
               }
            }
         }
      }
      return $interv;
   }


   /**
    * Display a Planning Item
    *
    * @param $itemtype itemtype
    * @param $val Array of the item to display
    *
    * @return Already planned information
   **/
   static function genericGetAlreadyPlannedInformation($itemtype, $val) {
      global $CFG_GLPI;

      $item = new $itemtype();
      $objectitemtype = $item->getItilObjectItemType();
      $out  = $item->getTypeName().' : '.Html::convDateTime($val["begin"]).' -> '.
              Html::convDateTime($val["end"]).' : ';
      $out .= "<a href='".Toolbox::getItemTypeFormURL($objectitemtype)."?id=".
                $val[getForeignKeyFieldForItemType($objectitemtype)]."&amp;forcetab=".$itemtype."$1'>";
      $out .= Html::resume_text($val["name"],80).'</a>';

      return $out;
   }


   /**
    * Display a Planning Item
    *
    * @param $itemtype itemtype
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
   **/
   static function genericDisplayPlanningItem($itemtype, $val, $who, $type="", $complete=0) {
      global $CFG_GLPI, $LANG;

      $rand      = mt_rand();
      $styleText = "";
      if (isset($val["state"])) {
         switch ($val["state"]) {
            case 2 : // Done
               $styleText = "color:#747474;";
               break;
         }
      }

      $parenttype    = str_replace('Task','',$itemtype);
      $parent        = new $parenttype();
      $parenttype_fk = $parent->getForeignKeyField();

      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/rdv_interv.png' alt='' title=\"".
             $parent->getTypeName()."\">&nbsp;&nbsp;";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/".$val["status"].".png' alt='".
             $parent->getStatus($val["status"])."' title=\"".$parent->getStatus($val["status"])."\">";
      echo "&nbsp;<a id='content_tracking_".$val["id"].$rand."'
                   href='".Toolbox::getItemTypeFormURL($parenttype)."?id=".$val[$parenttype_fk]."'
                   style='$styleText'>";

      switch ($type) {
         case "in" :
            echo date("H:i",strtotime($val["begin"]))."/".date("H:i",strtotime($val["end"])).": ";
            break;

         case "through" :
            break;

         case "begin" :
            echo $LANG['buttons'][33]." ".date("H:i",strtotime($val["begin"])).": ";
            break;

         case "end" :
            echo $LANG['buttons'][32]." ".date("H:i",strtotime($val["end"])).": ";
            break;
      }

      echo "<br>- #".$val[$parenttype_fk]." ";
      echo Html::resume_text($val["name"],80). " ";

      if (!empty($val["device"])) {
         echo "<br>- ".$val["device"];
      }

      if ($who<=0) { // show tech for "show all and show group"
         echo "<br>- ";
         echo $LANG['common'][95]." ".getUserName($val["users_id_tech"]);
      }

      echo "</a>";

      if ($complete) {
         echo "<br><span class='b'>";
         if (isset($val["state"])) {
            echo Planning::getState($val["state"])."<br>";
         }
         echo $LANG['joblist'][2]."&nbsp;:</span> ".$parent->getPriorityName($val["priority"]);
         echo "<br><span class='b'>".$LANG['joblist'][6]."&nbsp;:</span><br>".$val["content"];

      } else {
         $content = "<span class='b'>";
         if (isset($val["state"])) {
            $content .= Planning::getState($val["state"])."<br>";
         }
         $content .= $LANG['joblist'][2]."&nbsp;:</span> ".$parent->getPriorityName($val["priority"]).
                    "<br><span class='b'>".$LANG['joblist'][6]."&nbsp;:</span><br>".$val["content"].
                    "</div>";
         Html::showToolTip($content, array('applyto' => "content_tracking_".$val["id"].$rand));
      }
   }


   function showInObjectSumnary(CommonITILObject $item, $rand, $showprivate = false) {
      global $DB, $CFG_GLPI, $LANG;

      $canedit = $this->can($this->fields['id'],'w');

      echo "<tr class='tab_bg_";
      if ($this->maybePrivate() && $this->fields['is_private'] == 1) {
         echo "4' ";
      } else {
         echo "2' ";
      }

      if ($canedit) {
         echo "style='cursor:pointer' onClick=\"viewEditFollowup".$item->fields['id'].
               $this->fields['id']."$rand();\"";
      }

      echo " id='viewfollowup" . $this->fields[$item->getForeignKeyField()] . $this->fields["id"] .
            "$rand'>";

      echo "<td>".$this->getTypeName();
      if ($this->fields['taskcategories_id']) {
         echo " - " .Dropdown::getDropdownName('glpi_taskcategories',
                                               $this->fields['taskcategories_id']);
      }
      echo "</td>";

      echo "<td>";
      if ($canedit) {
         echo "\n<script type='text/javascript' >\n";
         echo "function viewEditFollowup" . $item->fields['id'] . $this->fields["id"] . "$rand() {\n";
         $params = array('type'       => $this->getType(),
                         'parenttype' => $item->getType(),
                                         $item->getForeignKeyField()
                                             => $this->fields[$item->getForeignKeyField()],
                         'id'         => $this->fields["id"]);
         Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
      }
      //else echo "--no--";
      echo Html::convDateTime($this->fields["date"]) . "</td>";
      echo "<td class='left'>" . nl2br($this->fields["content"]) . "</td>";

      $units = Toolbox::getTimestampTimeUnits($this->fields["actiontime"]);

      $hour   = $units['hour']+24*$units['day'];
      $minute = $units['minute'];
      echo "<td>";
      if ($hour) {
         echo "$hour " . Toolbox::ucfirst($LANG['gmt'][1]) . "<br>";
      }
      if ($minute || !$hour) {
         echo "$minute " . $LANG['job'][22] . "</td>";
      }

      echo "<td>" . getUserName($this->fields["users_id"]) . "</td>";
      if ($this->maybePrivate() && $showprivate) {
         echo "<td>".($this->fields["is_private"]?$LANG['choice'][1]:$LANG['choice'][0])."</td>";
      }

      echo "<td>";

      if (isset($this->fields["state"])) {
         echo Planning::getState($this->fields["state"])."<br>";
      }
      if (empty($this->fields["begin"])) {
         echo $LANG['job'][32];
      } else {
         echo Html::convDateTime($this->fields["begin"])."<br>->".
              Html::convDateTime($this->fields["end"])."<br>".
              getUserName($this->fields["users_id_tech"]);
      }
      echo "</td>";

      echo "</tr>\n";
   }


   /** form for Task
    *
    * @param $ID Integer : Id of the task
    * @param $options array
    *     -  parent Object : the object
   **/
   function showForm($ID, $options=array()) {
      global $DB, $LANG, $CFG_GLPI;

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }

      $fkfield = $item->getForeignKeyField();

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $input = array($fkfield => $item->getField('id'));
         $this->check(-1,'w',$input);
      }

      $canplan = Session::haveRight("show_planning", "1");

      $this->showFormHeader($options);

      $rowspan = 5 ;
      if ($this->maybePrivate()) {
         $rowspan++;
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td rowspan='$rowspan' class='middle right'>".$LANG['joblist'][6]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='$rowspan'>".
           "<textarea name='content' cols='50' rows='$rowspan'>".$this->fields["content"].
           "</textarea></td>";
      if ($ID > 0) {
         echo "<td>".$LANG['common'][27]."&nbsp;:</td>";
         echo "<td>";
         Html::showDateTimeFormItem("date", $this->fields["date"], 1, false);
      } else {
         echo "<td colspan='2'>&nbsp;";
      }
      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][36]."&nbsp;:</td><td>";
      Dropdown::show('TaskCategory', array('value'  => $this->fields["taskcategories_id"],
                                           'entity' => $item->fields["entities_id"]));
      echo "</td></tr>\n";

      if (isset($this->fields["state"])) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['state'][0]."&nbsp;:</td><td>";
         Planning::dropdownState("state", $this->fields["state"]);
         echo "</td></tr>\n";
      }

      if ($this->maybePrivate()) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][77]."&nbsp;:</td>";
         echo "<td><select name='is_private'>";
         echo "<option value='0' ".(!$this->fields["is_private"]?" selected":"").">".
                $LANG['choice'][0]."</option>";
         echo "<option value='1' ".($this->fields["is_private"]?" selected":"").">".
                $LANG['choice'][1]. "</option>";
         echo "</select></td>";
         echo "</tr>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][31]."&nbsp;:</td><td>";
      $toadd = array();
      for ($i=9;$i<=100;$i++) {
         $toadd[] = $i*HOUR_TIMESTAMP;
      }

      Dropdown::showTimeStamp("actiontime",array('min'             => 0,
                                                 'max'             => 8*HOUR_TIMESTAMP,
                                                 'value'           => $this->fields["actiontime"],
                                                 'addfirstminutes' => true,
                                                 'inhours'          => true,
                                                 'toadd'           => $toadd));

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['job'][35]."</td>";
      echo "<td>";

      if (!empty($this->fields["begin"])) {

         if (Session::haveRight('show_planning', 1)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlan".$ID."() {\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'users_id' => $this->fields["users_id_tech"],
                            'id'       => $this->fields["id"],
                            'begin'    => $this->fields["begin"],
                            'end'      => $this->fields["end"],
                            'entity'   => $item->fields["entities_id"]);
            Ajax::updateItemJsCode('viewplan', $CFG_GLPI["root_doc"] . "/ajax/planning.php", $params);
            echo "}";
            echo "</script>\n";
            echo "<div id='plan' onClick='showPlan".$ID."()'>\n";
            echo "<span class='showplan'>";
         }

         if (isset($this->fields["state"])) {
            echo Planning::getState($this->fields["state"])."<br>";
         }
         echo Html::convDateTime($this->fields["begin"]).
               "<br>->".Html::convDateTime($this->fields["end"])."<br>".
               getUserName($this->fields["users_id_tech"]);

         if (Session::haveRight('show_planning', 1)) {
            echo "</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";
         }

      } else {
         if (Session::haveRight('show_planning', 1)) {
            echo "<script type='text/javascript' >\n";
            echo "function showPlanUpdate() {\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params = array('form'     => 'followups',
                            'users_id' => Session::getLoginUserID(),
                            'entity'   => $_SESSION["glpiactive_entity"]);
            Ajax::updateItemJsCode('viewplan', $CFG_GLPI["root_doc"]."/ajax/planning.php", $params);
            echo "};";
            echo "</script>";

            echo "<div id='plan'  onClick='showPlanUpdate()'>\n";
            echo "<span class='showplan'>".$LANG['job'][34]."</span>";
            echo "</div>\n";
            echo "<div id='viewplan'></div>\n";

         } else {
            echo $LANG['job'][32];
         }
      }

      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Show the current task sumnary
   **/
   function showSummary(CommonITILObject $item) {
      global $DB, $LANG, $CFG_GLPI;

      if (!$this->canView()) {
         return false;
      }

      $tID = $item->fields['id'];

      // Display existing Followups
      $showprivate = $this->canViewPrivates();
      $caneditall  = $this->canEditAll();
      $tmp         = array($item->getForeignKeyField() => $tID);
      $canadd      = $this->can(-1, 'w', $tmp);

      $RESTRICT = "";
      if ($this->maybePrivate() && !$showprivate) {
         $RESTRICT = " AND (`is_private` = '0'
                            OR `users_id` ='" . Session::getLoginUserID() . "') ";
      }

      $query = "SELECT `id`, `date`
                FROM `".$this->getTable()."`
                WHERE `".$item->getForeignKeyField()."` = '$tID'
                      $RESTRICT
                ORDER BY `date` DESC";
      $result = $DB->query($query);

      $rand = mt_rand();

      if ($caneditall || $canadd) {
         echo "<div id='viewfollowup" . $tID . "$rand'></div>\n";
      }
      if ($canadd) {
         echo "<script type='text/javascript' >\n";
         echo "function viewAddFollowup" . $item->fields['id'] . "$rand() {\n";
         $params = array('type'                      => $this->getType(),
                         'parenttype'                => $item->getType(),
                         $item->getForeignKeyField() => $item->fields['id'],
                         'id'                        => -1);
         Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         if ($item->fields["status"] != 'solved' && $item->fields["status"] != 'closed') {
            echo "<div class='center'>".
                 "<a href='javascript:viewAddFollowup".$item->fields['id']."$rand();'>";
            echo $LANG['job'][30]."</a></div></p><br>\n";
         }
      }

      //echo "<h3>" . $LANG['job'][37] . "</h3>";

      if ($DB->numrows($result) == 0) {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'><th>" . $LANG['job'][50];
         echo "</th></tr></table>";
      } else {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr><th>".$LANG['common'][17]."</th><th>" . $LANG['common'][27] . "</th>";
         echo "<th>" . $LANG['joblist'][6] . "</th><th>" . $LANG['job'][31] . "</th>";
         echo "<th>" . $LANG['common'][37] . "</th>";
         if ($this->maybePrivate() && $showprivate) {
            echo "<th>" . $LANG['common'][77] . "</th>";
         }
         echo "<th>" . $LANG['job'][35] . "</th></tr>\n";

         while ($data = $DB->fetch_array($result)) {
            if ($this->getFromDB($data['id'])) {
               $this->showInObjectSumnary($item, $rand, $showprivate);
            }
         }
         echo "</table>";
      }
   }


   /**
    * Form for TicketTask on Massive action
   **/
   function showFormMassiveAction() {
      global $LANG;

      echo "&nbsp;".$LANG['common'][36]."&nbsp;: ";
      Dropdown::show('TaskCategory');

      echo "<br>".$LANG['joblist'][6]."&nbsp;: ";
      echo "<textarea name='content' cols='50' rows='6'></textarea>&nbsp;";

      if ($this->maybePrivate()) {
         echo "<input type='hidden' name='is_private' value='".$_SESSION['glpitask_private']."'>";
      }
      echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
   }


}
?>
