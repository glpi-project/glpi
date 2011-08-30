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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Tracking class
abstract class CommonITILObject extends CommonDBTM {

   /// Users by type
   protected $users       = array();
   public $userlinkclass  = '';
   /// Groups by type
   protected $groups      = array();
   public $grouplinkclass = '';

   /// Use user entity to select entity of the object
   protected $userentity_oncreate = false;


   // Requester
   const REQUESTER = 1;
   // Assign
   const ASSIGN    = 2;
   // Observer
   const OBSERVER  = 3;

   const MATRIX_FIELD         = '';
   const URGENCY_MASK_FIELD   = '';
   const IMPACT_MASK_FIELD    = '';
   const STATUS_MATRIX_FIELD  = '';


   function post_getFromDB () {

      if (!empty($this->grouplinkclass)) {
         $class = new $this->grouplinkclass();
         $this->groups = $class->getActors($this->fields['id']);
      }

      if (!empty($this->userlinkclass)) {
         $class = new $this->userlinkclass();
         $this->users  = $class->getActors($this->fields['id']);
      }
   }


   /**
    * Retrieve an item from the database with datas associated (hardwares)
    *
    * @param $ID ID of the item to get
    * @param $purecontent boolean : true : nothing change / false : convert to HTML display
    *
    * @return true if succeed else false
   **/
   function getFromDBwithData ($ID, $purecontent) {
      global $DB, $LANG;

      if ($this->getFromDB($ID)) {
         if (!$purecontent) {
            $this->fields["content"] = nl2br(preg_replace("/\r\n\r\n/", "\r\n",
                                             $this->fields["content"]));
         }
         $this->getAdditionalDatas();
         return true;
      }
      return false;
   }


   function getAdditionalDatas() {
   }


   function canAdminActors(){
      return false;
   }


   function canAssign(){
      return false;
   }


   /**
    * Is a user linked to the object ?
    *
    * @param $type type to search (see constants)
    * @param $users_id integer user ID
    *
    * @return boolean
   **/
   function isUser($type, $users_id) {

      if (isset($this->users[$type])) {
         foreach ($this->users[$type] as $data) {
            if ($data['users_id'] == $users_id) {
               return true;
            }
         }
      }

      return false;
   }


   /**
    * get users linked to a object
    *
    * @param $type type to search (see constants)
    *
    * @return array
   **/
   function getUsers($type) {

      if (isset($this->users[$type])) {
         return $this->users[$type];
      }

      return array();
   }


   /**
    * get groups linked to a object
    *
    * @param $type type to search (see constants)
    *
    * @return array
   **/
   function getGroups($type) {

      if (isset($this->groups[$type])) {
         return $this->groups[$type];
      }

      return array();
   }


   /**
    * count users linked to object by type or global
    *
    * @param $type type to search (see constants) / 0 for all
    *
    * @return integer
   **/
   function countUsers($type=0) {

      if ($type>0) {
         if (isset($this->users[$type])) {
            return count($this->users[$type]);
         }

      } else {
         if (count($this->users)) {
            $count = 0;
            foreach ($this->users as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }


   /**
    * count groups linked to object by type or global
    *
    * @param $type type to search (see constants) / 0 for all
    *
    * @return integer
   **/
   function countGroups($type=0) {

      if ($type>0) {
         if (isset($this->groups[$type])) {
            return count($this->groups[$type]);
         }

      } else {
         if (count($this->groups)) {
            $count = 0;
            foreach ($this->groups as $u) {
               $count += count($u);
            }
            return $count;
         }
      }
      return 0;
   }


   /**
    * Is a group linked to the object ?
    *
    * @param $type type to search (see constants)
    * @param $groups_id integer group ID
    *
    * @return boolean
   **/
   function isGroup($type, $groups_id) {

      if (isset($this->groups[$type])) {
         foreach ($this->groups[$type] as $data) {
            if ($data['groups_id']==$groups_id) {
               return true;
            }
         }
      }
      return false;
   }


   /**
    * Is one of groups linked to the object ?
    *
    * @param $type type to search (see constants)
    * @param $groups array of group ID
    *
    * @return boolean
   **/
   function haveAGroup($type, $groups) {

      if (is_array($groups) && count($groups) && isset($this->groups[$type])) {
         foreach ($groups as $groups_id) {
            foreach ($this->groups[$type] as $data) {
               if ($data['groups_id']==$groups_id) {
                  return true;
               }
            }
         }
      }
      return false;
   }


   /**
    * Get Default actor when creating the object
    *
    * @param $type type to search (see constants)
    *
    * @return boolean
   **/
   function getDefaultActor($type) {

      /// TODO own_ticket -> own_itilobject
      if ($type == self::ASSIGN) {
         if (Session::haveRight("own_ticket","1")) {
            return Session::getLoginUserID();
         }
      }
      return 0;
   }


   /**
    * Get Default actor when creating the object
    *
    * @param $type type to search (see constants)
    *
    * @return boolean
   **/
   function getDefaultActorRightSearch($type) {

      if ($type == self::ASSIGN) {
         return "own_ticket";
      }
      return "all";
   }


   /**
    * Count active ITIL Objects requested by a user
    *
    * @param $users_id integer ID of the User
    *
    * @return integer
   **/
   function countActiveObjectsForUser ($users_id) {

      $linkclass = new $this->userlinkclass();
      $itemtable = $this->getTable();
      $itemtype  = $this->getType();
      $itemfk    = $this->getForeignKeyField();
      $linktable = $linkclass->getTable();

      return countElementsInTable(array($itemtable,$linktable),
                                  "`$linktable`.`$itemfk` = `$itemtable`.`id`
                                    AND `$linktable`.`users_id` = '$users_id'
                                    AND `$linktable`.`type` = '".self::REQUESTER."'
                                    AND `$itemtable`.`status`
                                       NOT IN ('".implode("', '",
                                                          array_merge($his->getSolvedStatusArray(),
                                                                      $this->getClosedStatusArray())
                                                          )."')");
   }


   function cleanDBonPurge() {

      if (!empty($this->grouplinkclass)) {
         $class = new $this->grouplinkclass();
         $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      }

      if (!empty($this->userlinkclass)) {
         $class = new $this->userlinkclass();
         $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
      }
   }


   function prepareInputForUpdate($input) {
      global $LANG;

      // Add document if needed
      $this->getFromDB($input["id"]); // entities_id field required
      if (!isset($input['_donotadddocs']) || !$input['_donotadddocs']) {
         $docadded = $this->addFiles($input["id"]);
      }

      if (isset($input["document"]) && $input["document"]>0) {
         $doc = new Document();
         if ($doc->getFromDB($input["document"])) {
            $docitem = new Document_Item();
            if ($docitem->add(array('documents_id' => $input["document"],
                                    'itemtype'     => $this->getType(),
                                    'items_id'     => $input["id"]))) {
               // Force date_mod of tracking
               $input["date_mod"]     = $_SESSION["glpi_currenttime"];
               $input['_doc_added'][] = $doc->fields["name"];
            }
         }
         unset($input["document"]);
      }

      if (isset($input["date"]) && empty($input["date"])) {
         unset($input["date"]);
      }

      if (isset($input["closedate"]) && empty($input["closedate"])) {
         unset($input["closedate"]);
      }

      if (isset($input["solvedate"]) && empty($input["solvedate"])) {
         unset($input["solvedate"]);
      }

      if (isset($input['_itil_requester'])) {
         if (isset($input['_itil_requester']['_type'])) {
            $input['_itil_requester']['type']                      = self::REQUESTER;
            $input['_itil_requester'][$this->getForeignKeyField()] = $input['id'];

            switch ($input['_itil_requester']['_type']) {
               case "user" :
                  if (!empty($this->userlinkclass)) {
                     if (isset($input['_itil_requester']['alternative_email'])
                         && $input['_itil_requester']['alternative_email']
                         && !NotificationMail::isUserAddressValid($input['_itil_requester']['alternative_email'])) {
                        $input['_itil_requester']['alternative_email'] = '';
                        Session::addMessageAfterRedirect($LANG['mailing'][111].' : '.$LANG['mailing'][110],
                                                         false, ERROR);
                     }
                     if ((isset($input['_itil_requester']['alternative_email'])
                          && $input['_itil_requester']['alternative_email'])
                         || $input['_itil_requester']['users_id']>0) {
                        $useractors = new $this->userlinkclass();
                        if ($useractors->can(-1,'w',$input['_itil_requester'])) {
                           $useractors->add($input['_itil_requester']);
                           $input['_forcenotif'] = true;
                        }
                     }
                  }
                  break;

               case "group" :
                  if (!empty($this->grouplinkclass)) {
                     $groupactors = new $this->grouplinkclass();
                     if ($groupactors->can(-1,'w',$input['_itil_requester'])) {
                        $groupactors->add($input['_itil_requester']);
                        $input['_forcenotif'] = true;
                     }
                  }
                  break;
            }
         }
      }

      if (isset($input['_itil_observer'])) {
         if (isset($input['_itil_observer']['_type'])) {
            $input['_itil_observer']['type']                      = self::OBSERVER;
            $input['_itil_observer'][$this->getForeignKeyField()] = $input['id'];

            switch ($input['_itil_observer']['_type']) {
               case "user" :
                  if (!empty($this->userlinkclass)) {
                     if (isset($input['_itil_observer']['alternative_email'])
                         && $input['_itil_observer']['alternative_email']
                         && !NotificationMail::isUserAddressValid($input['_itil_observer']['alternative_email'])) {
                        $input['_itil_observer']['alternative_email'] = '';
                        Session::addMessageAfterRedirect($LANG['mailing'][111].' : '.$LANG['mailing'][110],
                                                         false, ERROR);
                     }
                     if ((isset($input['_itil_observer']['alternative_email'])
                          && $input['_itil_observer']['alternative_email'])
                         || $input['_itil_observer']['users_id']>0) {
                        $useractors = new $this->userlinkclass();
                        if ($useractors->can(-1,'w',$input['_itil_observer'])) {
                           $useractors->add($input['_itil_observer']);
                           $input['_forcenotif'] = true;
                        }
                     }
                  }
                  break;

               case "group" :
                   if (!empty($this->grouplinkclass)) {
                     $groupactors = new $this->grouplinkclass();
                     if ($groupactors->can(-1,'w',$input['_itil_observer'])) {
                        $groupactors->add($input['_itil_observer']);
                        $input['_forcenotif'] = true;
                     }
                  }
                  break;
            }
         }
      }

      if (isset($input['_itil_assign'])) {
         if (isset($input['_itil_assign']['_type'])) {
            $input['_itil_assign']['type']                      = self::ASSIGN;
            $input['_itil_assign'][$this->getForeignKeyField()] = $input['id'];

            switch ($input['_itil_assign']['_type']) {
               case "user" :
                  if (!empty($this->userlinkclass)) {
                     $useractors = new $this->userlinkclass();
                     if ($useractors->can(-1,'w',$input['_itil_assign'])) {
                        $useractors->add($input['_itil_assign']);
                        $input['_forcenotif'] = true;
                        if ((!isset($input['status']) && $this->fields['status']=='new')
                            || (isset($input['status']) && $input['status'] == 'new')) {
                           $input['status'] = 'assign';
                        }
                     }
                  }
                  break;

               case "group" :
                  if (!empty($this->grouplinkclass)) {
                     $groupactors = new $this->grouplinkclass();

                     if ($groupactors->can(-1,'w',$input['_itil_assign'])) {
                        $groupactors->add($input['_itil_assign']);
                        $input['_forcenotif'] = true;
                        if ((!isset($input['status']) && $this->fields['status']=='new')
                            || (isset($input['status']) && $input['status'] == 'new')) {
                           $input['status'] = 'assign';
                        }
                     }
                  }
                  break;
            }
         }
      }

      // set last updater
      if ($lastupdater=Session::getLoginUserID(true)) {
         $input['users_id_lastupdater'] = $lastupdater;
      }

      if (isset($input["status"])
          && !in_array($input["status"],array_merge($this->getSolvedStatusArray(),
                                                    $this->getClosedStatusArray()))) {
         $input['solvedate'] = 'NULL';
      }

      if (isset($input["status"]) && in_array($input["status"],$this->getClosedStatusArray())) {
         $input['closedate'] = 'NULL';
      }

      return $input;
   }


   function pre_updateInDB() {
      global $LANG, $CFG_GLPI;

      if ($this->fields['status'] == 'new') {
         if (in_array("suppliers_id_assign",$this->updates)
             && $this->input["suppliers_id_assign"]>0) {

            if (!in_array('status', $this->updates)) {
               $this->oldvalues['status'] = $this->fields['status'];
               $this->updates[]           = 'status';
            }
            $this->fields['status'] = 'assign';
            $this->input['status']  = 'assign';
         }
      }

      // Setting a solution or solution type means the problem is solved
      if ((in_array("solutiontypes_id",$this->updates) && $this->input["solutiontypes_id"] >0)
          || (in_array("solution",$this->updates) && !empty($this->input["solution"]))) {

         if (!in_array('status', $this->updates)) {
            $this->oldvalues['status'] = $this->fields['status'];
            $this->updates[]           = 'status';
         }

         // Special case for Ticket : use autoclose
         if ($this->getType() == 'Ticket') {
            $entitydata = new EntityData();
            if ($entitydata->getFromDB($this->fields['entities_id'])) {
               $autoclosedelay = $entitydata->getfield('autoclose_delay');
            } else {
               $autoclosedelay = -1;
            }
            // -1 = config
            if ($autoclosedelay == -1) {
               $autoclosedelay = $CFG_GLPI['autoclose_delay'];
            }
            // 0 = immediatly
            if ($autoclosedelay == 0) {
               $this->fields['status'] = 'closed';
               $this->input['status']  = 'closed';
            } else {
               $this->fields['status'] = 'solved';
               $this->input['status']  = 'solved';
            }
         } else {

            $this->fields['status'] = 'solved';
            $this->input['status']  = 'solved';
         }
      }

      // Check dates change interval due to the fact that second are not displayed in form
      if (($key=array_search('date',$this->updates)) !== false
          && (substr($this->fields["date"],0,16) == substr($this->oldvalues['date'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['date']);
      }

      if (($key=array_search('closedate',$this->updates)) !== false
          && (substr($this->fields["closedate"],0,16) == substr($this->oldvalues['closedate'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['closedate']);
      }

      if (($key=array_search('due_date',$this->updates)) !== false
          && (substr($this->fields["due_date"],0,16) == substr($this->oldvalues['due_date'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['due_date']);
      }

      if (($key=array_search('solvedate',$this->updates)) !== false
          && (substr($this->fields["solvedate"],0,16) == substr($this->oldvalues['solvedate'],0,16))) {
         unset($this->updates[$key]);
         unset($this->oldvalues['solvedate']);
      }

      if (isset($this->input["status"])) {
         if ($this->input["status"] != 'waiting'
             && isset($this->input["suppliers_id_assign"])
             && $this->input["suppliers_id_assign"] == 0
             && $this->countUsers(self::ASSIGN) == 0
             && $this->countGroups(self::ASSIGN) == 0
             && !in_array($this->fields['status'],array_merge($this->getSolvedStatusArray(),
                                                              $this->getClosedStatusArray()))
            ) {

            if (!in_array('status', $this->updates)) {
               $this->oldvalues['status'] = $this->fields['status'];
               $this->updates[] = 'status';
            }
            $this->fields['status'] = 'new';
         }

         if (in_array("status",$this->updates) && in_array($this->input["status"],
                                                           $this->getSolvedStatusArray())) {
            $this->updates[]              = "solvedate";
            $this->oldvalues['solvedate'] = $this->fields["solvedate"];
            $this->fields["solvedate"]    = $_SESSION["glpi_currenttime"];
            // If invalid date : set open date
            if ($this->fields["solvedate"] < $this->fields["date"]) {
               $this->fields["solvedate"] = $this->fields["date"];
            }
         }

         if (in_array("status",$this->updates) && in_array($this->input["status"],
                                                           $this->getClosedStatusArray())) {
            $this->updates[]              = "closedate";
            $this->oldvalues['closedate'] = $this->fields["closedate"];
            $this->fields["closedate"]    = $_SESSION["glpi_currenttime"];
            // If invalid date : set open date
            if ($this->fields["closedate"] < $this->fields["date"]) {
               $this->fields["closedate"] = $this->fields["date"];
            }
            // Set solvedate to closedate
            if (empty($this->fields["solvedate"])) {
               $this->updates[]              = "solvedate";
               $this->oldvalues['solvedate'] = $this->fields["solvedate"];
               $this->fields["solvedate"]    = $this->fields["closedate"];
            }
         }

      }

      // check dates

      // check due_date (SLA)
      if ((in_array("date",$this->updates) || in_array("due_date",$this->updates))
          && !is_null($this->fields["due_date"])) { // Date set

         if ($this->fields["due_date"] < $this->fields["date"]) {
            Session::addMessageAfterRedirect($LANG['tracking'][3].$this->fields["due_date"],
                                             false, ERROR);

            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['date']);
            }
            if (($key=array_search('due_date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['due_date']);
            }
         }
      }

      // Status close : check dates
      if (in_array($this->fields["status"], $this->getClosedStatusArray())
          && (in_array("date",$this->updates) || in_array("closedate",$this->updates))) {

         // Invalid dates : no change
         // closedate must be > solvedate
         if ($this->fields["closedate"] < $this->fields["solvedate"]) {
            Session::addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);

            if (($key=array_search('closedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['closedate']);
            }
         }

         // closedate must be > create date
         if ($this->fields["closedate"]<$this->fields["date"]) {
            Session::addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);
            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['date']);
            }
            if (($key=array_search('closedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['closedate']);
            }
         }
      }

      if (($key=array_search('status',$this->updates)) !== false
          && $this->oldvalues['status'] == $this->fields['status']) {
         unset($this->updates[$key]);
         unset($this->oldvalues['status']);
      }

      // Status solved : check dates
      if (in_array($this->fields["status"], $this->getSolvedStatusArray())
          && (in_array("date",$this->updates) || in_array("solvedate",$this->updates))) {

         // Invalid dates : no change
         // solvedate must be > create date
         if ($this->fields["solvedate"] < $this->fields["date"]) {
            Session::addMessageAfterRedirect($LANG['tracking'][3], false, ERROR);

            if (($key=array_search('date',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['date']);
            }
            if (($key=array_search('solvedate',$this->updates)) !== false) {
               unset($this->updates[$key]);
               unset($this->oldvalues['solvedate']);
            }
          }
      }

   }


   function prepareInputForAdd($input) {
      global $CFG_GLPI;

      if (!isset($input["urgency"]) || !($CFG_GLPI['urgency_mask']&(1<<$input["urgency"]))) {
         $input["urgency"] = 3;
      }
      if (!isset($input["impact"]) || !($CFG_GLPI['impact_mask']&(1<<$input["impact"]))) {
         $input["impact"] = 3;
      }
      if (!isset($input["priority"])) {
         $input["priority"] = $this->computePriority($input["urgency"], $input["impact"]);
      }

      // set last updater
      if ($lastupdater=Session::getLoginUserID(true)) {
         $input['users_id_lastupdater'] = $lastupdater;
      }

      // No Auto set Import for external source
      if (!isset($input['_auto_import'])) {
         if (!isset($input["_users_id_requester"])) {
            if ($uid = Session::getLoginUserID()) {
               $input["_users_id_requester"] = $uid;
            }
         }
      }

      // No Auto set Import for external source
      if (($uid=Session::getLoginUserID()) && !isset($input['_auto_import'])) {
         $input["users_id_recipient"] = $uid;
      } else if (isset($input["_users_id_requester"]) && $input["_users_id_requester"]) {
         $input["users_id_recipient"] = $input["_users_id_requester"];
      }

      if (!isset($input["status"])) {
         $input["status"] = "new";
      }

      if (!isset($input["date"]) || empty($input["date"])) {
         $input["date"] = $_SESSION["glpi_currenttime"];
      }

      if (isset($input["status"]) && in_array($this->fields["status"],
                                              $this->getSolvedStatusArray())) {
         if (isset($input["date"])) {
            $input["solvedate"] = $input["date"];
         } else {
            $input["solvedate"] = $_SESSION["glpi_currenttime"];
         }
      }

      if (isset($input["status"]) && in_array($this->fields["status"],
                                              $this->getClosedStatusArray())) {
         if (isset($input["date"])) {
            $input["closedate"] = $input["date"];
         } else {
            $input["closedate"] = $_SESSION["glpi_currenttime"];
         }
         $input['solvedate'] = $input["closedate"];
      }

      // No name set name
      if (empty($input["name"])) {
         $input["name"] = preg_replace('/\r\n/',' ',$input['content']);
         $input["name"] = preg_replace('/\n/',' ',$input['name']);
         $input["name"] = Toolbox::substr($input['name'],0,70);
      }


      // Set default dropdown
      $dropdown_fields = array('entities_id', 'suppliers_id_assign', 'itilcategories_id');
      foreach ($dropdown_fields as $field ) {
         if (!isset($input[$field])) {
            $input[$field] = 0;
         }
      }

      if (((isset($input["_users_id_assign"]) && $input["_users_id_assign"]>0)
           || (isset($input["_groups_id_assign"]) && $input["_groups_id_assign"]>0)
           || (isset($input["suppliers_id_assign"]) && $input["suppliers_id_assign"]>0))
          && $input["status"]=="new") {

         $input["status"] = "assign";
      }

      return $input;
   }


   function post_addItem() {

      // Add document if needed
      $this->addFiles($this->fields['id']);

      $useractors = NULL;
      // Add user groups linked to ITIL objects
      if (!empty($this->userlinkclass)) {
         $useractors = new $this->userlinkclass();
      }
      $groupactors = NULL;
      if (!empty($this->grouplinkclass)) {
         $groupactors = new $this->grouplinkclass();
      }

      if (!is_null($useractors)) {
         if (isset($this->input["_users_id_requester"])
             && ($this->input["_users_id_requester"]>0
                 || (isset($this->input["_users_id_requester_notif"]['alternative_email'])
                     && !empty($this->input["_users_id_requester_notif"]['alternative_email'])))) {

            $input2 = array($useractors->getItilObjectForeignKey()
                                       => $this->fields['id'],
                           'users_id'  => $this->input["_users_id_requester"],
                           'type'      => self::REQUESTER);

            if (isset($this->input["_users_id_requester_notif"])) {
               foreach ($this->input["_users_id_requester_notif"] as $key => $val) {
                  $input2[$key] = $val;
               }
            }

            $useractors->add($input2);
         }

         if (isset($this->input["_users_id_observer"])
             && ($this->input["_users_id_observer"]>0
                 || (isset($this->input["_users_id_observer_notif"]['alternative_email'])
                     && !empty($this->input["_users_id_observer_notif"]['alternative_email'])))) {
            $input2 = array($useractors->getItilObjectForeignKey()
                                       => $this->fields['id'],
                           'users_id'  => $this->input["_users_id_observer"],
                           'type'      => self::OBSERVER);

            if (isset($this->input["_users_id_observer_notif"])) {
               foreach ($this->input["_users_id_observer_notif"] as $key => $val) {
                  $input2[$key] = $val;
               }
            }

            $useractors->add($input2);
         }

         if (isset($this->input["_users_id_assign"]) && $this->input["_users_id_assign"]>0) {
            $input2 = array($useractors->getItilObjectForeignKey()
                                       => $this->fields['id'],
                           'users_id'  => $this->input["_users_id_assign"],
                           'type'      => self::ASSIGN);

            if (isset($this->input["_users_id_assign_notif"])) {
               foreach ($this->input["_users_id_assign_notif"] as $key => $val) {
                  $input2[$key] = $val;
               }
            }

            $useractors->add($input2);
         }
      }

      if (!is_null($groupactors)) {
         if (isset($this->input["_groups_id_requester"]) && $this->input["_groups_id_requester"]>0) {
            $groupactors->add(array($groupactors->getItilObjectForeignKey()
                                                => $this->fields['id'],
                                    'groups_id' => $this->input["_groups_id_requester"],
                                    'type'      => self::REQUESTER));
         }

         if (isset($this->input["_groups_id_assign"]) && $this->input["_groups_id_assign"]>0) {
            $groupactors->add(array($groupactors->getItilObjectForeignKey()
                                                => $this->fields['id'],
                                    'groups_id' => $this->input["_groups_id_assign"],
                                    'type'      => self::ASSIGN));
         }

         if (isset($this->input["_groups_id_observer"]) && $this->input["_groups_id_observer"]>0) {
            $groupactors->add(array($groupactors->getItilObjectForeignKey()
                                                => $this->fields['id'],
                                    'groups_id' => $this->input["_groups_id_observer"],
                                    'type'      => self::OBSERVER));
         }
      }


      // Additional actors : using default notification parameters
      if (!is_null($useractors)) {
         // Observers : for mailcollector
         if (isset($this->input["_additional_observers"])
             && is_array($this->input["_additional_observers"])
             && count($this->input["_additional_observers"])) {

            $input2 = array($useractors->getItilObjectForeignKey() => $this->fields['id'],
                            'type'                                 => self::OBSERVER);

            foreach ($this->input["_additional_observers"] as $tmp) {
               if (isset($tmp['users_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $useractors->add($input2);
               }
            }
         }

         if (isset($this->input["_additional_assigns"])
             && is_array($this->input["_additional_assigns"])
             && count($this->input["_additional_assigns"])) {

            $input2 = array($useractors->getItilObjectForeignKey() => $this->fields['id'],
                            'type'                                 => self::ASSIGN);

            foreach ($this->input["_additional_assigns"] as $tmp) {
               if (isset($tmp['users_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $useractors->add($input2);
               }
            }
         }

         if (isset($this->input["_additional_requesters"])
             && is_array($this->input["_additional_requesters"])
             && count($this->input["_additional_requesters"])) {

            $input2 = array($useractors->getItilObjectForeignKey() => $this->fields['id'],
                            'type'                                 => self::REQUESTER);

            foreach ($this->input["_additional_requesters"] as $tmp) {
               if (isset($tmp['users_id'])) {
                  foreach ($tmp as $key => $val) {
                     $input2[$key] = $val;
                  }
                  $ticket_user->add($input2);
               }
            }
         }
      }
   }


   /**
    * add files (from $_FILES) to an ITIL object
    * create document if needed
    * create link from document to ITIL object
    *
    * @param $id of the ITIL object
    *
    * @return array of doc added name
   **/
   function addFiles($id) {
      global $LANG, $CFG_GLPI;

      if (!isset($_FILES) || !isset($_FILES['filename'])) {
         return array();
      }
      $docadded = array();
      $doc      = new Document();
      $docitem  = new Document_Item();

      // if multiple files are uploaded
      $TMPFILE = array();
      if (is_array($_FILES['filename']['name'])) {
         foreach ($_FILES['filename']['name'] as $key => $filename) {
            if (!empty($filename)) {
               $TMPFILE[$key]['filename']['name']     = $filename;
               $TMPFILE[$key]['filename']['type']     = $_FILES['filename']['type'][$key];
               $TMPFILE[$key]['filename']['tmp_name'] = $_FILES['filename']['tmp_name'][$key];
               $TMPFILE[$key]['filename']['error']    = $_FILES['filename']['error'][$key];
               $TMPFILE[$key]['filename']['size']     = $_FILES['filename']['size'][$key];
            }
         }
      } else {
         $TMPFILE = array( $_FILES );
      }

      foreach ($TMPFILE as $_FILES) {
         if (isset($_FILES['filename'])
             && count($_FILES['filename']) > 0
             && $_FILES['filename']["size"] > 0) {

            // Check for duplicate
            if ($doc->getFromDBbyContent($this->fields["entities_id"],
                                         $_FILES['filename']['tmp_name'])) {
               $docID = $doc->fields["id"];

            } else {
               $input2         = array();
               $input2["name"] = addslashes($LANG['tracking'][24]." $id");

               if ($this->getType() == 'Ticket') {
                  $input2["tickets_id"]           = $id;
               }
               $input2["entities_id"]             = $this->fields["entities_id"];
               $input2["documentcategories_id"]   = $CFG_GLPI["documentcategories_id_forticket"];
               $input2["_only_if_upload_succeed"] = 1;
               $input2["entities_id"]             = $this->fields["entities_id"];
               $docID = $doc->add($input2);
            }

            if ($docID>0) {
               if ($docitem->add(array('documents_id' => $docID,
                                       'itemtype'     => $this->getType(),
                                       'items_id'     => $id))) {
                  $docadded[] = stripslashes($doc->fields["name"]." - ".$doc->fields["filename"]);
               }
            }

         } else if (!empty($_FILES['filename']['name'])
                    && isset($_FILES['filename']['error'])
                    && $_FILES['filename']['error']) {
            Session::addMessageAfterRedirect($LANG['document'][46], false, ERROR);
         }
      }
      unset ($_FILES);
      return $docadded;
   }


   /**
    * Compute Priority
    *
    * @param $itemtype itemtype
    * @param $urgency integer from 1 to 5
    * @param $impact integer from 1 to 5
    *
    * @return integer from 1 to 5 (priority)
   **/
   static function computeGenericPriority($itemtype, $urgency, $impact) {
      global $CFG_GLPI;

      if (isset($CFG_GLPI[constant($itemtype.'::MATRIX_FIELD')][$urgency][$impact])) {
         return $CFG_GLPI[constant($itemtype.'::MATRIX_FIELD')][$urgency][$impact];
      }
      // Failback to trivial
      return round(($urgency+$impact)/2);
   }


   /**
    * Dropdown of ITIL object priority
    *
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection (major included)
    * @param $major display major priority
    *
    * @return string id of the select
   **/
   static function dropdownPriority($name, $value=0, $complete=false, $major=false) {
      global $LANG;

      $id = "select_$name".mt_rand();
      echo "<select id='$id' name='$name'>";
      if ($complete) {
         echo "<option value='0' ".($value==0?" selected ":"").">".$LANG['common'][66]."</option>";
         echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][3]."</option>";
         echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][4]."</option>";
         echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][5]."</option>";
         echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][6]."</option>";
         echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][7]."</option>";
      }

      if ($complete || $major) {
         echo "<option value='6' ".($value==6?" selected ":"").">".$LANG['help'][2]."</option>";
      }

      echo "<option value='5' ".($value==5?" selected ":"").">".$LANG['help'][3]."</option>";
      echo "<option value='4' ".($value==4?" selected ":"").">".$LANG['help'][4]."</option>";
      echo "<option value='3' ".($value==3?" selected ":"").">".$LANG['help'][5]."</option>";
      echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['help'][6]."</option>";
      echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['help'][7]."</option>";

      echo "</select>";

      return $id;
   }


   /**
    * Get ITIL object priority Name
    *
    * @param $value status ID
   **/
   static function getPriorityName($value) {
      global $LANG;

      switch ($value) {
         case 6 :
            return $LANG['help'][2];

         case 5 :
            return $LANG['help'][3];

         case 4 :
            return $LANG['help'][4];

         case 3 :
            return $LANG['help'][5];

         case 2 :
            return $LANG['help'][6];

         case 1 :
            return $LANG['help'][7];
      }
   }


   /**
    * Dropdown of ITIL object Urgency
    *
    * @param $itemtype itemtype
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection
    *
    * @return string id of the select
   **/
   static function dropdownGenericUrgency($itemtype, $name, $value=0, $complete=false) {
      global $LANG, $CFG_GLPI;

      $id = "select_$name".mt_rand();
      echo "<select id='$id' name='$name'>";

      if ($complete) {
         echo "<option value='0' ".($value==0?" selected ":"").">".$LANG['common'][66]."</option>";
         echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][42]."</option>";
         echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][43]."</option>";
         echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][44]."</option>";
         echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][45]."</option>";
         echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][46]."</option>";
      }

      if (isset($CFG_GLPI[constant($itemtype.'::URGENCY_MASK_FIELD')])) {
         if ($complete || ($CFG_GLPI[constant($itemtype.'::URGENCY_MASK_FIELD')] & (1<<5))) {
            echo "<option value='5' ".($value==5?" selected ":"").">".$LANG['help'][42]."</option>";
         }

         if ($complete || ($CFG_GLPI[constant($itemtype.'::URGENCY_MASK_FIELD')] & (1<<4))) {
            echo "<option value='4' ".($value==4?" selected ":"").">".$LANG['help'][43]."</option>";
         }

         echo "<option value='3' ".($value==3?" selected ":"").">".$LANG['help'][44]."</option>";

         if ($complete || ($CFG_GLPI[constant($itemtype.'::URGENCY_MASK_FIELD')] & (1<<2))) {
            echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['help'][45]."</option>";
         }

         if ($complete || ($CFG_GLPI[constant($itemtype.'::URGENCY_MASK_FIELD')] & (1<<1))) {
            echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['help'][46]."</option>";
         }
      }

      echo "</select>";

      return $id;
   }


   /**
    * Get ITIL object Urgency Name
    *
    * @param $value urgency ID
   **/
   static function getUrgencyName($value) {
      global $LANG;

      switch ($value) {
         case 5 :
            return $LANG['help'][42];

         case 4 :
            return $LANG['help'][43];

         case 3 :
            return $LANG['help'][44];

         case 2 :
            return $LANG['help'][45];

         case 1 :
            return $LANG['help'][46];
      }
   }


   /**
    * Dropdown of ITIL object Impact
    *
    * @param $itemtype itemtype
    * @param $name select name
    * @param $value default value
    * @param $complete see also at least selection (major included)
    *
    * @return string id of the select
   **/
   static function dropdownGenericImpact($itemtype, $name, $value=0, $complete=false) {
      global $LANG, $CFG_GLPI;

      $id = "select_$name".mt_rand();
      echo "<select id='$id' name='$name'>";

      if ($complete) {
         echo "<option value='0' ".($value==0?" selected ":"").">".$LANG['common'][66]."</option>";
         echo "<option value='-5' ".($value==-5?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][47]."</option>";
         echo "<option value='-4' ".($value==-4?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][48]."</option>";
         echo "<option value='-3' ".($value==-3?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][49]."</option>";
         echo "<option value='-2' ".($value==-2?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][50]."</option>";
         echo "<option value='-1' ".($value==-1?" selected ":"").">".$LANG['search'][16]." ".
                $LANG['help'][51]."</option>";
      }

      if (isset($CFG_GLPI[constant($itemtype.'::IMPACT_MASK_FIELD')])) {
         if ($complete || ($CFG_GLPI[constant($itemtype.'::IMPACT_MASK_FIELD')] & (1<<5))) {
            echo "<option value='5' ".($value==5?" selected ":"").">".$LANG['help'][47]."</option>";
         }

         if ($complete || ($CFG_GLPI[constant($itemtype.'::IMPACT_MASK_FIELD')] & (1<<4))) {
            echo "<option value='4' ".($value==4?" selected ":"").">".$LANG['help'][48]."</option>";
         }

         echo "<option value='3' ".($value==3?" selected ":"").">".$LANG['help'][49]."</option>";

         if ($complete || ($CFG_GLPI[constant($itemtype.'::IMPACT_MASK_FIELD')] & (1<<2))) {
            echo "<option value='2' ".($value==2?" selected ":"").">".$LANG['help'][50]."</option>";
         }

         if ($complete || ($CFG_GLPI[constant($itemtype.'::IMPACT_MASK_FIELD')] & (1<<1))) {
            echo "<option value='1' ".($value==1?" selected ":"").">".$LANG['help'][51]."</option>";
         }
      }

      echo "</select>";

      return $id;
   }


   /**
    * Get ITIL object Impact Name
    *
    * @param $value status ID
   **/
   static function getImpactName($value) {
      global $LANG;

      switch ($value) {
         case 5 :
            return $LANG['help'][47];

         case 4 :
            return $LANG['help'][48];

         case 3 :
            return $LANG['help'][49];

         case 2 :
            return $LANG['help'][50];

         case 1 :
            return $LANG['help'][51];
      }
   }


   /**
    * Get the ITIL object status list
    *
    * @param $withmetaforsearch boolean
    *
    * @return an array
   **/
   static function getAllStatusArray($withmetaforsearch=false) {

      // To be overridden by class
      $tab = array();

      return $tab;
   }


   /**
    * Get the ITIL object closed status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getClosedStatus () {
      // To be overridden by class
      $tab = array();

      return $tab;
   }


   /**
    * Get the ITIL object solved status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getSolvedStatus () {
      // To be overridden by class
      $tab = array();

      return $tab;
   }


   /**
    * Get the ITIL object process status list
    *
    * @since version 0.83
    *
    * @return an array
   **/
   static function getProcessStatus () {
      // To be overridden by class
      $tab = array();

      return $tab;
   }


   /**
    * check is the user can change from / to a status
    *
    * @param $itemtype itemtype
    * @param $old string value of old/current status
    * @param $new string value of target status
    *
    * @return boolean
   **/
   static function genericIsAllowedStatus($itemtype, $old, $new) {

      if (isset($_SESSION['glpiactiveprofile'][constant($itemtype.'::STATUS_MATRIX_FIELD')][$old][$new])
          && !$_SESSION['glpiactiveprofile'][constant($itemtype.'::STATUS_MATRIX_FIELD')][$old][$new]) {
         return false;
      }

      if (array_key_exists(constant($itemtype.'::STATUS_MATRIX_FIELD'),
                           $_SESSION['glpiactiveprofile'])) { // Not set for post-only)
         return true;
      }

      return false;
   }


   /**
    * Get the ITIL object status allowed for a current status
    *
    * @param $itemtype itemtype
    * @param $current status
    *
    * @return an array
   **/
   static function getAllowedStatusArray($itemtype, $current) {

      $item = new $itemtype();
      $tab = $item->getAllStatusArray();

      if (!isset($current)) {
         $current = 'new';
      }

      foreach ($tab as $status => $label) {
         if ($status != $current
             && !$item->isAllowedStatus($current, $status)) {
            unset($tab[$status]);
         }
      }
      return $tab;
   }


   /**
    * Dropdown of object status
    *
    * @param $itemtype itemtype
    * @param $name select name
    * @param $value default value
    * @param $option list proposed 0:normal, 1:search, 2:allowed
    *
    * @return nothing (display)
   **/
   static function dropdownGenericStatus($itemtype, $name, $value='new', $option=0) {

      $item = new $itemtype();

      if ($option == 2) {
         $tab = $item->getAllowedStatusArray($itemtype, $value);
      } else if ($option == 1) {
         $tab = $item->getAllStatusArray(true);
      } else {
         $tab = $item->getAllStatusArray(false);
      }

      echo "<select name='$name'>";
      foreach ($tab as $key => $val) {
         echo "<option value='$key' ".($value==$key?" selected ":"").">$val</option>";
      }
      echo "</select>";
   }


   /**
    * Get ITIL object status Name
    *
    * @param $itemtype itemtype
    * @param $value status ID
   **/
   static function getGenericStatus($itemtype, $value) {

      $item = new $itemtype();
      $tab  = $item->getAllStatusArray(true);
      return (isset($tab[$value]) ? $tab[$value] : '');
   }


   /**
    * show tooltip for user notification informations
    *
    * @param $type integer : user type
    * @param $canedit boolean : can edit ?
    *
    * @return nothing display
   **/
   function showGroupsAssociated($type, $canedit) {
      global $CFG_GLPI,$LANG;

      $showgrouplink = 0;
      if (Session::haveRight('group','r')) {
         $showgrouplink = 1;
      }

      $groupicon = self::getActorIcon('group',$type);
      $group     = new Group();

      if (isset($this->groups[$type]) && count($this->groups[$type])) {
         foreach ($this->groups[$type] as $d) {
            $k = $d['groups_id'];
            echo "$groupicon&nbsp;";
            if ($group->getFromDB($k)) {
               echo $group->getLink($showgrouplink);
            }
            if ($canedit) {
               echo "&nbsp;<a href='".$this->getFormURL()."?delete_group=delete_group&amp;id=".
                     $d['id']."&amp;".$this->getForeignKeyField()."=".$this->fields['id'].
                     "' title=\"".$LANG['reservation'][6]."\">
                     <img src='".$CFG_GLPI["root_doc"]."/pics/delete.png'
                      alt=\"".$LANG['buttons'][6]."\" title=\"".$LANG['buttons'][6]."\"></a>";
            }
            echo '<br>';
         }
      }
   }


   function getSpecificValueToDisplay($searchopt, $value) {
      if (count($searchopt)) {
         if ($searchopt['table'] == $this->getTable()) {
            switch ($searchopt['field']) {
               case 'urgency':
                  return self::getUrgencyName($value);
                  break;

               case 'impact':
                  return self::getImpactName($value);
                  break;

               case 'priority':
                  return self::getPriorityName($value);
                  break;
            }
         }
      }
   }

   function getSearchOptionsActors () {
      global $LANG;

      $tab = array();

      $tab['requester'] = $LANG['job'][4];

      $tab[4]['table']         = 'glpi_users';
      $tab[4]['field']         = 'name';
      $tab[4]['datatype']      = 'dropdown';
      $tab[4]['name']          = $LANG['job'][4];
      $tab[4]['forcegroupby']  = true;
      $tab[4]['massiveaction'] = false;
      $tab[4]['joinparams']    = array('beforejoin'
                                       => array('table' => getTableForItemType($this->userlinkclass),
                                                'joinparams'
                                                        => array('jointype'  => 'child',
                                                                 'condition' => 'AND NEWTABLE.`type` ' .
                                                                                '= '.self::REQUESTER)));

      $tab[71]['table']         = 'glpi_groups';
      $tab[71]['field']         = 'name';
      $tab[71]['datatype']      = 'dropdown';
      $tab[71]['name']          = $LANG['common'][35];
      $tab[71]['forcegroupby']  = true;
      $tab[71]['massiveaction'] = false;
      $tab[71]['joinparams']    = array('beforejoin'
                                        => array('table' => getTableForItemType($this->grouplinkclass),
                                                 'joinparams'
                                                         => array('jointype'  => 'child',
                                                                  'condition' => 'AND NEWTABLE.`type` ' .
                                                                                 '= '.self::REQUESTER)));

      $tab[22]['table']     = 'glpi_users';
      $tab[22]['field']     = 'name';
      $tab[22]['datatype']      = 'dropdown';
      $tab[22]['linkfield'] = 'users_id_recipient';
      $tab[22]['name']      = $LANG['common'][37];

      $tab['observer'] = $LANG['common'][104];

      $tab[66]['table']         = 'glpi_users';
      $tab[66]['field']         = 'name';
      $tab[66]['datatype']      = 'dropdown';
      $tab[66]['name']          = $LANG['common'][104]." - ".$LANG['common'][34];
      $tab[66]['forcegroupby']  = true;
      $tab[66]['massiveaction'] = false;
      $tab[66]['joinparams']    = array('beforejoin'
                                        => array('table' => getTableForItemType($this->userlinkclass),
                                                 'joinparams'
                                                         => array('jointype'  => 'child',
                                                                  'condition' => 'AND NEWTABLE.`type` ' .
                                                                                 '= '.self::OBSERVER)));

      $tab[65]['table']         = 'glpi_groups';
      $tab[65]['field']         = 'name';
      $tab[65]['datatype']      = 'dropdown';
      $tab[65]['name']          = $LANG['common'][104]." - ".$LANG['common'][35];
      $tab[65]['forcegroupby']  = true;
      $tab[65]['massiveaction'] = false;
      $tab[65]['joinparams']    = array('beforejoin'
                                        => array('table' => getTableForItemType($this->grouplinkclass),
                                                 'joinparams'
                                                         => array('jointype'  => 'child',
                                                                  'condition' => 'AND NEWTABLE.`type` ' .
                                                                                 '= '.self::OBSERVER)));

      $tab['assign'] = $LANG['job'][5];

      $tab[5]['table']         = 'glpi_users';
      $tab[5]['field']         = 'name';
      $tab[5]['datatype']      = 'dropdown';
      $tab[5]['name']          = $LANG['job'][5]." - ".$LANG['job'][6];
      $tab[5]['forcegroupby']  = true;
      $tab[5]['massiveaction'] = false;
      $tab[5]['joinparams']    = array('beforejoin'
                                       => array('table' => getTableForItemType($this->userlinkclass),
                                                'joinparams'
                                                        => array('jointype'  => 'child',
                                                                 'condition' => 'AND NEWTABLE.`type` ' .
                                                                                '= '.self::ASSIGN)));
      $tab[5]['filter']        = 'own_ticket';

      $tab[6]['table']     = 'glpi_suppliers';
      $tab[6]['field']     = 'name';
      $tab[6]['datatype']  = 'dropdown';
      $tab[6]['linkfield'] = 'suppliers_id_assign';
      $tab[6]['name']      = $LANG['job'][5]." - ".$LANG['financial'][26];

      $tab[8]['table']         = 'glpi_groups';
      $tab[8]['field']         = 'name';
      $tab[8]['datatype']      = 'dropdown';
      $tab[8]['name']          = $LANG['job'][5]." - ".$LANG['common'][35];
      $tab[8]['forcegroupby']  = true;
      $tab[8]['massiveaction'] = false;
      $tab[8]['joinparams']    = array('beforejoin'
                                       => array('table' => getTableForItemType($this->grouplinkclass),
                                                'joinparams'
                                                        => array('jointype'  => 'child',
                                                                 'condition' => 'AND NEWTABLE.`type` ' .
                                                                                '= '.self::ASSIGN)));

      $tab['notification'] = $LANG['setup'][704];

      $tab[35]['table']      = getTableForItemType($this->userlinkclass);
      $tab[35]['field']      = 'use_notification';
      $tab[35]['name']       = $LANG['job'][19];
      $tab[35]['datatype']   = 'bool';
      $tab[35]['joinparams'] = array('jointype'  => 'child',
                                     'condition' => 'AND NEWTABLE.`type` = '.self::REQUESTER);


      $tab[34]['table']      = getTableForItemType($this->userlinkclass);
      $tab[34]['field']      = 'alternative_email';
      $tab[34]['name']       = $LANG['joblist'][27];
      $tab[34]['datatype']   = 'email';
      $tab[34]['joinparams'] = array('jointype'  => 'child',
                                     'condition' => 'AND NEWTABLE.`type` = '.self::REQUESTER);



      return $tab;
   }


   /**
    * show Icon for Actor
    *
    * @param $user_group string : 'user or 'group'
    * @param $type integer : user/group type
    *
    * @return nothing display
   **/
   static function getActorIcon($user_group, $type) {
      global $LANG, $CFG_GLPI;

      switch ($user_group) {
         case 'user' :
            $icontitle = $LANG['common'][34].' - '.$type;
            switch ($type) {
               case self::REQUESTER :
                  $icontitle = $LANG['common'][34].' - '.$LANG['job'][4];
                  break;

               case self::OBSERVER :
                  $icontitle = $LANG['common'][34].' - '.$LANG['common'][104];
                  break;

               case self::ASSIGN :
                  $icontitle = $LANG['job'][6];
                  break;
            }
            return "<img width=20 src='".$CFG_GLPI['root_doc']."/pics/users.png'
                     alt=\"$icontitle\" title=\"$icontitle\">";

         case 'group' :
            $icontitle = $LANG['common'][35];
            switch ($type) {
               case self::REQUESTER :
                  $icontitle = $LANG['setup'][249];
                  break;

               case self::OBSERVER :
                  $icontitle = $LANG['setup'][251];
                  break;

               case self::ASSIGN :
                  $icontitle = $LANG['setup'][248];
                  break;
            }
            return  "<img width=20 src='".$CFG_GLPI['root_doc']."/pics/groupes.png'
                      alt=\"$icontitle\" title=\"$icontitle\">";

         case 'supplier' :
            $icontitle = $LANG['financial'][26];
            return  "<img width=20 src='".$CFG_GLPI['root_doc']."/pics/supplier.png'
                      alt=\"$icontitle\" title=\"$icontitle\">";

      }
      return '';

   }


   /**
    * show tooltip for user notification informations
    *
    * @param $type integer : user type
    * @param $canedit boolean : can edit ?
    *
    * @return nothing display
   **/
   function showUsersAssociated($type, $canedit) {
      global $CFG_GLPI, $LANG;

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 2;
      }
      $usericon = self::getActorIcon('user',$type);
      $user     = new User();

      if (isset($this->users[$type]) && count($this->users[$type])) {
         foreach ($this->users[$type] as $d) {
            $k = $d['users_id'];
            $save_showuserlink = $showuserlink;

            echo "$usericon&nbsp;";

            if ($k) {
               $userdata = getUserName($k, $showuserlink);
            } else {
               $email         = $d['alternative_email'];
               $userdata      = "<a href='mailto:$email'>$email</a>";
               $showuserlink  = false;
            }

            if ($showuserlink) {
               echo $userdata['name']."&nbsp;".Html::showToolTip($userdata["comment"],
                                                                 array('link'    => $userdata["link"],
                                                                       'display' => false));
            } else {
               echo $userdata;
            }

            if ($CFG_GLPI['use_mailing']) {
               $text = $LANG['job'][19]."&nbsp;:&nbsp;".Dropdown::getYesNo($d['use_notification']).
                       '<br>';

               if ($d['use_notification']) {
                  $uemail = $d['alternative_email'];
                  if (empty($uemail) && $user->getFromDB($d['users_id'])) {
                     $uemail = $user->getDefaultEmail();
                  }
                  $text .= $LANG['mailing'][118]."&nbsp;:&nbsp;".$uemail;
                  if (!NotificationMail::isUserAddressValid($uemail)) {
                     $text .= "<span class='red'>".$LANG['mailing'][110]."</span>";
                  }
               }
               echo "&nbsp;";

               if ($canedit
                   || $d['users_id'] == Session::getLoginUserID()) {
                  $opt = array('img'   => $CFG_GLPI['root_doc'].'/pics/edit.png',
                               'popup' => 'edit_user_notification&amp;id='.$d['id']);
                  Html::showToolTip($text, $opt);
               }
            }

            if ($canedit) {
               echo "&nbsp;<a href='".$this->getFormURL()."?delete_user=delete_user&amp;id=".
                     $d['id']. "&amp;".$this->getForeignKeyField()."=".$this->fields['id'].
                     "' title=\"".$LANG['buttons'][6]."\">
                     <img src='".$CFG_GLPI["root_doc"]."/pics/delete.png'
                      alt=\"".$LANG['buttons'][6]."\" title=\"".$LANG['buttons'][6]."\"></a>";
            }
            echo "<br>";

            $showuserlink = $save_showuserlink;
         }
      }
   }


   /**
    * show actor add div
    *
    * @param $type string : actor type
    * @param $rand_type integer rand value of div to use
    * @param $entities_id integer entity ID
    * @param $withsupplier boolean : allow adding a supplier (only one possible in ASSIGN case)
    * @param $inobject boolean display in ITIL object ?
    *
    * @return nothing display
   **/
   static function showActorAddForm($type, $rand_type, $entities_id, $withsupplier=false,
                                    $inobject=true) {
      global $LANG, $CFG_GLPI;

      $types = array(''      => Dropdown::EMPTY_VALUE,
                     'user'  => $LANG['common'][34],
                     'group' => $LANG['common'][35]);

      if ($withsupplier && $type == self::ASSIGN) {
         $types['supplier'] = $LANG['financial'][26];
      }

      switch ($type) {
         case self::REQUESTER :
            $typename = 'requester';
            break;

         case self::OBSERVER :
            $typename = 'observer';
            break;

         case self::ASSIGN :
            $typename = 'assign';
            break;

         default :
            return false;
      }

      echo "<div ".($inobject?"style='display:none'":'')." id='itilactor$rand_type'>";
      $rand   = Dropdown::showFromArray("_itil_".$typename."[_type]", $types);
      $params = array('type'            => '__VALUE__',
                      'actortype'       => $typename,
                      'allow_email'     => ($type==self::OBSERVER || $type==self::REQUESTER),
                      'entity_restrict' => $entities_id);

      Ajax::updateItemOnSelectEvent("dropdown__itil_".$typename."[_type]$rand",
                                    "showitilactor".$typename."_$rand",
                                    $CFG_GLPI["root_doc"]."/ajax/dropdownItilActors.php",
                                    $params);
      echo "<span id='showitilactor".$typename."_$rand'>&nbsp;</span>";
      if ($inobject) {
         echo "<hr>";
      }
      echo "</div>";
   }


   /**
    * show user add div on creation
    *
    * @param $type integer : actor type
    * @param $options array options for default values ($options of showForm)
    *
    * @return nothing display
   **/
   function showActorAddFormOnCreate($type, $options) {
      global $LANG, $CFG_GLPI;

      switch ($type) {
         case self::REQUESTER :
            $typename = 'requester';
            break;

         case self::OBSERVER :
            $typename = 'observer';
            break;

         case self::ASSIGN :
            $typename = 'assign';
            break;

         default :
            return false;
      }

      echo self::getActorIcon('user', $type)."&nbsp;";

      $right = $this->getDefaultActorRightSearch($type);

      if ($options["_users_id_".$typename] == 0) {
         $options["_users_id_".$typename] = $this->getDefaultActor($type);
      }
      $rand   = mt_rand();
      $params = array('name'        => '_users_id_'.$typename,
                      'value'       => $options["_users_id_".$typename],
                      'entity'      => $_SESSION['glpiactiveentities'],
                      'right'       => $right,
                      'rand'        => $rand,
                      'ldap_import' => true);

      if ($this->userentity_oncreate && $type == self::REQUESTER) {
         $params['on_change'] = 'submit()';
      }


      if ($CFG_GLPI['use_mailing']) {
         $paramscomment = array('value' => '__VALUE__',
                                'field' => "_users_id_".$typename."_notif",
                                'allow_email'
                                        => ($type==self::REQUESTER || $type==self::OBSERVER),
                                'use_notification'
                                        => $options["_users_id_".$typename."_notif"]['use_notification']);

         $params['toupdate'] = array('value_fieldname' => 'value',
                                     'to_update'       => "notif_".$typename."_$rand",
                                     'url'             => $CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php",
                                     'moreparams'      => $paramscomment);

      }
      //List all users in the active entities
      User::dropdown($params);

      // display opened tickets for user
      if ($this->getType() == 'Ticket'
          && $type == self::REQUESTER
          && $options["_users_id_".$typename] > 0) {

         $options2['field'][0]      = 4; // users_id
         $options2['searchtype'][0] = 'equals';
         $options2['contains'][0]   = $options["_users_id_".$typename];
         $options2['link'][0]       = 'AND';

         $options2['field'][1]      = 12; // status
         $options2['searchtype'][1] = 'equals';
         $options2['contains'][1]   = 'notold';
         $options2['link'][1]       = 'AND';

         $url = $this->getSearchURL()."?".Toolbox::append_params($options2,'&amp;');

         echo "&nbsp;<a href='$url' title=\"".$LANG['job'][21]."\" target='_blank'>(".
            $this->countActiveObjectsForUser($options["_users_id_".$typename]).")</a>";
      }

      if ($CFG_GLPI['use_mailing']) {
         echo "<div id='notif_".$typename."_$rand'>";
         echo "</div>";

         echo "<script type='text/javascript'>";
         Ajax::updateItemJsCode("notif_".$typename."_$rand",
                                $CFG_GLPI["root_doc"]."/ajax/uemailUpdate.php", $paramscomment,
                                "dropdown__users_id_".$typename.$rand);
         echo "</script>";
      }
   }


   /**
    * show actor part in ITIL object form
    *
    * @param $ID integer ITIL object ID
    * @param $options array options for default values ($options of showForm)
    *
    * @return nothing display
   **/
   function showActorsPartForm($ID, $options) {
      global $LANG, $CFG_GLPI;

      $showuserlink = 0;
      if (Session::haveRight('user','r')) {
         $showuserlink = 1;
      }
      // Manage actors : requester and assign
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th rowspan='2' width='10%'>".$LANG['common'][103]."&nbsp;:</th>";
      echo "<th width='30%'>".$LANG['job'][4];
      $rand_requester = -1;
      $candeleterequester    = false;

      if ($ID && $this->canAdminActors()) {
         $rand_requester = mt_rand();
         echo "&nbsp;&nbsp;";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                    onClick=\"Ext.get('itilactor$rand_requester').setDisplayed('block')\"
                    class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
         $candeleterequester = true;
      }
      echo "</th>";

      echo "<th width='30%'>".$LANG['common'][104];
      $rand_observer = -1;
      $candeleteobserver    = false;

      if ($ID && $this->canAdminActors()) {
         $rand_observer = mt_rand();

         echo "&nbsp;&nbsp;";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                    onClick=\"Ext.get('itilactor$rand_observer').setDisplayed('block')\"
                    class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";

         $candeleteobserver = true;

      } else if ($ID > 0
                 && !$this->isUser(self::OBSERVER, Session::getLoginUserID())
                 && !$this->isUser(self::REQUESTER, Session::getLoginUserID())) {
         echo "&nbsp;&nbsp;";
         echo "&nbsp;&nbsp;<a href='".$CFG_GLPI["root_doc"].
              "/front/ticket.form.php?addme_observer=addme_observer".
              "&amp;tickets_id=".$this->fields['id']."' title=\"".$LANG['tracking'][5]."\">".
              $LANG['tracking'][5]."</a>";
      }
      echo "</th>";

      echo "<th width='30%'>".$LANG['job'][5];
      $rand_assign = -1;
      $candeleteassign    = false;
      if ($ID && ($this->canAssign() || $this->canAssignToMe())) {
         $rand_assign = mt_rand();

         echo "&nbsp;&nbsp;";
         echo "<img title=\"".$LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"
                    onClick=\"Ext.get('itilactor$rand_assign').setDisplayed('block')\"
                    class='pointer' src='".$CFG_GLPI["root_doc"]."/pics/add_dropdown.png'>";
      }

      if ($ID && $this->canAssign()) {
         $candeleteassign = true;
      }
      echo "</th></tr>";

      echo "<tr class='tab_bg_1 top'>";
      echo "<td>";

      if ($rand_requester>=0) {
         self::showActorAddForm(self::REQUESTER, $rand_requester,
                                $this->fields['entities_id']);
      }

      // Requester
      if (!$ID) {

         if ($this->canAdminActors()) {
            $this->showActorAddFormOnCreate(self::REQUESTER, $options);
         } else {
            echo self::getActorIcon('user', self::REQUESTER)."&nbsp;";
            echo getUserName($options["_users_id_requester"], $showuserlink);
         }

         //If user have access to more than one entity, then display a combobox : Ticket case
         if ($this->userentity_oncreate
             && isset($this->countentitiesforuser)
             && $this->countentitiesforuser > 1) {
            echo "<br>";
            $rand = Dropdown::show('Entity', array('value'       => $this->fields["entities_id"],
                                                   'entity'      => $this->userentities,
                                                   'on_change'   => 'submit()'));
         } else {
            echo "<input type='hidden' name='entities_id' value='".$this->fields["entities_id"]."'>";
         }
         echo '<hr>';

      } else {
         $this->showUsersAssociated(self::REQUESTER, $candeleterequester);
      }

      // Requester Group
      if (!$ID) {
         echo self::getActorIcon('group', self::REQUESTER)."&nbsp;";
         Dropdown::show('Group', array('name'      => '_groups_id_requester',
                                       'value'     => $options["_groups_id_requester"],
                                       'entity'    => $this->fields["entities_id"],
                                       'condition' => '`is_requester`'));
      } else {
         $this->showGroupsAssociated(self::REQUESTER, $candeleterequester);
      }
      echo "</td>";

      echo "<td>";
      if ($rand_observer>=0) {
         self::showActorAddForm(self::OBSERVER, $rand_observer,
                                $this->fields['entities_id']);
      }

      // Observer
      if (!$ID) {
         if ($this->canAdminActors()) {
            $this->showActorAddFormOnCreate(self::OBSERVER, $options);
            echo '<hr>';
         }
      } else {
         $this->showUsersAssociated(self::OBSERVER, $candeleteobserver);
      }

      // Observer Group
      if (!$ID) {
         echo self::getActorIcon('group', self::OBSERVER)."&nbsp;";
         Dropdown::show('Group', array('name'      => '_groups_id_observer',
                                       'value'     => $options["_groups_id_observer"],
                                       'entity'    => $this->fields["entities_id"],
                                       'condition' => '`is_requester`'));
      } else {
         $this->showGroupsAssociated(self::OBSERVER, $candeleteobserver);
      }
      echo "</td>";

      echo "<td>";
      if ($rand_assign>=0) {
         self::showActorAddForm(self::ASSIGN, $rand_assign, $this->fields['entities_id'],
                                $this->fields["suppliers_id_assign"]==0);
      }

      // Assign User
      if (!$ID) {
         if ($this->canAssign()) {
            $this->showActorAddFormOnCreate(self::ASSIGN, $options);
            echo '<hr>';
         } else if ($this->canAssignToMe()) {
            echo self::getActorIcon('user', self::ASSIGN)."&nbsp;";
            User::dropdown(array('name'        => '_users_id_assign',
                                 'value'       => $options["_users_id_assign"],
                                 'entity'      => $this->fields["entities_id"],
                                 'ldap_import' => true));
            echo '<br>';
         }

      } else {
         $this->showUsersAssociated(self::ASSIGN, $candeleteassign);
      }

      // Assign Groups
      if (!$ID) {
         if ($this->canAssign()) {
            echo self::getActorIcon('group', self::ASSIGN)."&nbsp;";
            Dropdown::show('Group', array('name'      => '_groups_id_assign',
                                          'value'     => $options["_groups_id_assign"],
                                          'entity'    => $this->fields["entities_id"],
                                          'condition' => '`is_assign`'));
            echo '<hr>';
         }

      } else {
         $this->showGroupsAssociated(self::ASSIGN, $candeleteassign);
      }

      // Supplier
      if ($this->fields["suppliers_id_assign"] > 0 || !$ID) {

         if ($this->canAssign()) {
            echo self::getActorIcon('supplier', self::ASSIGN)."&nbsp;";
            Dropdown::show('Supplier', array('name'   => 'suppliers_id_assign',
                                             'value'  => $this->fields["suppliers_id_assign"],
                                             'entity' => $this->fields["entities_id"]));
            echo '<br>';
         } else {
            if ($this->fields["suppliers_id_assign"]) {
               echo self::getActorIcon('supplier', self::ASSIGN)."&nbsp;";
               echo Dropdown::getDropdownName("glpi_suppliers", $this->fields["suppliers_id_assign"]);
               echo '<br>';
            }
         }
      }
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }


   static function getActionTime($actiontime) {
      return Html::timestampToString($actiontime, false);
   }


   static function getAssignName($ID, $itemtype, $link=0) {

      switch ($itemtype) {
         case 'User' :
            if ($ID == 0) {
               return "";
            }
            return getUserName($ID,$link);

         case 'Supplier' :
         case 'Group' :
            $item = new $itemtype();
            if ($item->getFromDB($ID)) {
               $before = "";
               $after  = "";
               if ($link) {
                  return $item->getLink(1);
               }
               return $item->getNameID();
            }
            return "";
      }
   }


   /**
    * Form to add a solution to an ITIL object
    *
    * @param $knowbase_id_toload integer load a kb article as solution (0 = no load)
   **/
   function showSolutionForm($knowbase_id_toload=0) {
      global $LANG, $CFG_GLPI;

      $this->check($this->getField('id'), 'r');

      $canedit = $this->canSolve();
      $options = array();

      if ($knowbase_id_toload > 0) {
         $kb = new KnowbaseItem();
         if ($kb->getFromDB($knowbase_id_toload)) {
            $this->fields['solution'] = $kb->getField('answer');
         }
      }

      $this->showFormHeader($options);

      $show_template = $canedit;
//                        && $this->getField('solutiontypes_id') == 0
//                        && empty($this->fields['solution']);
      $rand_template = mt_rand();
      $rand_text     = $rand_type = 0;
      if ($canedit) {
         $rand_text = mt_rand();
         $rand_type = mt_rand();
      }
      if ($show_template) {
         echo "<tr class='tab_bg_2'>";
         echo "<td>".$LANG['jobresolution'][6]."&nbsp;:&nbsp;</td><td>";

         Dropdown::show('SolutionTemplate',
                        array('value'    => 0,
                              'entity'   => $this->getEntityID(),
                              'rand'     => $rand_template,
                              // Load type and solution from bookmark
                              'toupdate' => array('value_fieldname' => 'value',
                                                  'to_update' => 'solution'.$rand_text,
                                                  'url'       => $CFG_GLPI["root_doc"].
                                                                 "/ajax/solution.php",
                                                  'moreparams' => array('type_id'
                                                                        => 'dropdown_solutiontypes_id'.
                                                                           $rand_type))));

         echo "</td><td colspan='2'>";
         echo "<a title\"".$LANG['job'][23]."\"
                href='".$CFG_GLPI['root_doc']."/front/knowbaseitem.php?itemtype=".$this->getType().
                "&amp;items_id=".$this->getField('id')."'>".$LANG['job'][23]."</a>";
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['job'][48]."&nbsp;:&nbsp;</td><td>";

      $current = $this->fields['status'];
      // Settings a solution will set status to solved
      if ($canedit) {
         Dropdown::show('SolutionType',
                        array('value' => $this->getField('solutiontypes_id'),
                              'rand'  => $rand_type));
      } else {
         echo Dropdown::getDropdownName('glpi_solutiontypes',
                                        $this->getField('solutiontypes_id'));
      }
      echo "</td><td>".$LANG['job'][25]."</td><td>";
      Dropdown::showYesNo('_sol_to_kb', false);
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>".$LANG['joblist'][6]."&nbsp;: </td><td colspan='3'>";

      if ($canedit) {
         Html::initEditorSystem("solution");

         echo "<div id='solution$rand_text'>";
         echo "<textarea id='solution' name='solution' rows='12' cols='80'>".
                $this->getField('solution')."</textarea></div>";

      } else {
         echo Toolbox::unclean_cross_side_scripting_deep($this->getField('solution'));
      }
      echo "</td></tr>";

      $options['candel']  = false;
      $options['canedit'] = $canedit;
      $this->showFormButtons($options);

   }


   /**
    * Update date mod of the ITIL object
    *
    * @param $ID ID of the ITIL object
    * @param $no_stat_computation boolean do not cumpute take into account stat
   **/
   function updateDateMod($ID, $no_stat_computation=false) {
      global $DB;

      if ($this->getFromDB($ID)) {
         // Force date mod and lastupdater
         $query = "UPDATE `".$this->getTable()."`
                   SET `date_mod` = '".$_SESSION["glpi_currenttime"]."'";

         if ($lastupdater=Session::getLoginUserID(true)) {
            $query .= ", `users_id_lastupdater` = '$lastupdater' ";
         }

         $query .= "WHERE `id` = '$ID'";
         $DB->query($query);
      }
   }


   /**
    * Update actiontime of the object based on actiontime of the tasks
    *
    * @param $ID ID of the object
    *
    * @return boolean : success
   **/
   function updateActionTime($ID) {
      global $DB;

      $tot       = 0;
      $tasktable = getTableForItemType($this->getType().'Task');

      $query = "SELECT SUM(`actiontime`)
                FROM `$tasktable`
                WHERE `".$this->getForeignKeyField()."` = '$ID'";

      if ($result = $DB->query($query)) {
         $sum = $DB->result($result,0,0);
         if (!is_null($sum)) {
            $tot += $sum;
         }
      }
      $query2 = "UPDATE `".$this->getTable()."`
                 SET `actiontime` = '$tot'
                 WHERE `id` = '$ID'";

      return $DB->query($query2);
   }


   /**
    * Get all available types to which an ITIL object can be assigned
    *
   **/
   static function getAllTypesForHelpdesk() {
      global $PLUGIN_HOOKS, $CFG_GLPI;

      /// TODO ticket_types -> itil_types

      $types = array();

      //Types of the plugins (keep the plugin hook for right check)
      if (isset($PLUGIN_HOOKS['assign_to_ticket'])) {
         foreach ($PLUGIN_HOOKS['assign_to_ticket'] as $plugin => $value) {
            $types = Plugin::doOneHook($plugin, 'AssignToTicket', $types);
         }
      }

      //Types of the core (after the plugin for robustness)
      foreach($CFG_GLPI["ticket_types"] as $itemtype) {
         if (class_exists($itemtype)) {
            if (!isPluginItemType($itemtype) // No plugin here
                && in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
               $item             = new $itemtype();
               $types[$itemtype] = $item->getTypeName();
            }
         }
      }
      ksort($types); // core type first... asort could be better ?

      return $types;
   }


   /**
    * Check if it's possible to assign ITIL object to a type (core or plugin)
    *
    * @param $itemtype the object's type
    *
    * @return true if ticket can be assign to this type, false if not
   **/
   static function isPossibleToAssignType($itemtype) {
      global $PLUGIN_HOOKS;
      /// TODO : assign_to_ticket to assign_to_itil
      // Plugin case
      if (isPluginItemType($itemtype)) {
         /// TODO maybe only check plugin of itemtype ?
         //If it's not a core's type, then check plugins
         $types = array();
         if (isset($PLUGIN_HOOKS['assign_to_ticket'])) {
            foreach ($PLUGIN_HOOKS['assign_to_ticket'] as $plugin => $value) {
               $types = Plugin::doOneHook($plugin, 'AssignToTicket', $types);
            }
            if (array_key_exists($itemtype,$types)) {
               return true;
            }
         }
      // standard case
      } else {
         if (in_array($itemtype, $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
            return true;
         }
      }

      return false;
   }



}
?>
