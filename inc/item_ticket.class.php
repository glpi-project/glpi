<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Item_Ticket Class
 *
 *  Relation between Tickets and Items
**/
class Item_Ticket extends CommonDBRelation{


   // From CommonDBRelation
   static public $itemtype_1          = 'Ticket';
   static public $items_id_1          = 'tickets_id';

   static public $itemtype_2          = 'itemtype';
   static public $items_id_2          = 'items_id';
   static public $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;



   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @since version 0.85.5
    * @see CommonDBRelation::canCreateItem()
   **/
   function canCreateItem() {

      $ticket = new Ticket();
      // Not item linked for closed tickets
      if ($ticket->getFromDB($this->fields['tickets_id'])
          && in_array($ticket->fields['status'],$ticket->getClosedStatusArray())) {
        return false;
      }

      return parent::canCreateItem();
   }


   function post_addItem() {

      $ticket = new Ticket();
      $input  = array('id'            => $this->fields['tickets_id'],
                      'date_mod'      => $_SESSION["glpi_currenttime"],
                      '_donotadddocs' => true);

      if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
         $input['_forcenotif'] = true;
      }
      if (isset($this->input['_disablenotif']) && $this->input['_disablenotif']) {
         $input['_disablenotif'] = true;
      }

      $ticket->update($input);
      parent::post_addItem();
   }


   function post_purgeItem() {

      $ticket = new Ticket();
      $input = array('id'            => $this->fields['tickets_id'],
                     'date_mod'      => $_SESSION["glpi_currenttime"],
                     '_donotadddocs' => true);

      if (!isset($this->input['_do_notif']) || $this->input['_do_notif']) {
         $input['_forcenotif'] = true;
      }
      $ticket->update($input);

      parent::post_purgeItem();
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      // Avoid duplicate entry
      $restrict = " `tickets_id` = '".$input['tickets_id']."'
                   AND `itemtype` = '".$input['itemtype']."'
                   AND `items_id` = '".$input['items_id']."'";
      if (countElementsInTable($this->getTable(), $restrict) > 0) {
         return false;
      }

      $ticket = new Ticket();
      $ticket->getFromDB($input['tickets_id']);

      // Get item location if location is not already set in ticket
      if (empty($ticket->fields['locations_id'])) {
         if (($input["items_id"] > 0) && !empty($input["itemtype"])) {
            if ($item = getItemForItemtype($input["itemtype"])) {
               if ($item->getFromDB($input["items_id"])) {
                  if ($item->isField('locations_id')) {
                     $ticket->fields['items_locations'] = $item->fields['locations_id'];

                     // Process Business Rules
                     $rules = new RuleTicketCollection($ticket->fields['entities_id']);

                     $ticket->fields = $rules->processAllRules(Toolbox::stripslashes_deep($ticket->fields),
                                                Toolbox::stripslashes_deep($ticket->fields),
                                                array('recursive' => true));

                     unset($ticket->fields['items_locations']);
                     $ticket->updateInDB(array('locations_id'));
                  }
               }
            }
         }
      }

      return parent::prepareInputForAdd($input);
   }

   /**
    * @param $item   CommonDBTM object
   **/
   static function countForItem(CommonDBTM $item) {

      $restrict = "`glpi_items_tickets`.`tickets_id` = `glpi_tickets`.`id`
                   AND `glpi_items_tickets`.`items_id` = '".$item->getField('id')."'
                   AND `glpi_items_tickets`.`itemtype` = '".$item->getType()."'".
                   getEntitiesRestrictRequest(" AND ", "glpi_tickets", '', '', true);

      $nb = countElementsInTable(array('glpi_items_tickets', 'glpi_tickets'), $restrict);

      return $nb ;
   }


   /**
    * Print the HTML array for Items linked to a ticket
    *
    * @param $ticket Ticket object
    *
    * @return Nothing (display)
   **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI;

      $instID = $ticket->fields['id'];

      if (!$ticket->can($instID, READ)) {
         return false;
      }

      $canedit = ($ticket->canEdit($instID)
                  && isset($_SESSION["glpiactiveprofile"])
                  && $_SESSION["glpiactiveprofile"]["interface"] == "central");
      $rand    = mt_rand();

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_items_tickets`
                WHERE `glpi_items_tickets`.`tickets_id` = '$instID'
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);


      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='ticketitem_form$rand' id='ticketitem_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item')."</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         // Select hardware on creation or if have update right
         $class        = new $ticket->userlinkclass();
         $tickets_user = $class->getActors($instID);
         $dev_user_id = 0;
         if (isset($tickets_user[CommonITILActor::REQUESTER])
                 && (count($tickets_user[CommonITILActor::REQUESTER]) == 1)) {
            foreach ($tickets_user[CommonITILActor::REQUESTER] as $user_id_single) {
               $dev_user_id = $user_id_single['users_id'];
            }
         }

         if ($dev_user_id > 0) {
            self::dropdownMyDevices($dev_user_id, $ticket->fields["entities_id"], null, 0, $instID);
         }

         $data =  array_keys(getAllDatasFromTable('glpi_items_tickets'));
         $used = array();
         if (!empty($data)) {
            foreach ($data as $val) {
               $used[$val['itemtype']] = $val['id'];
            }
         }

         self::dropdownAllDevices("itemtype", null, 0, 1, $dev_user_id, $ticket->fields["entities_id"], $instID);
         echo "<span id='item_ticket_selection_information'></span>";
         echo "</td><td class='center' width='30%'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "<input type='hidden' name='tickets_id' value='$instID'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('container' => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $number) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__('Entity')."</th>";
      $header_end .= "<th>".__('Name')."</th>";
      $header_end .= "<th>".__('Serial number')."</th>";
      $header_end .= "<th>".__('Inventory number')."</th></tr>";
      echo $header_begin.$header_top.$header_end;

      $totalnb = 0;
      for ($i=0 ; $i<$number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         $itemtable = getTableForItemType($itemtype);
         $query = "SELECT `$itemtable`.*,
                          `glpi_items_tickets`.`id` AS IDD,
                          `glpi_entities`.`id` AS entity
                   FROM `glpi_items_tickets`,
                        `$itemtable`";

         if ($itemtype != 'Entity') {
            $query .= " LEFT JOIN `glpi_entities`
                              ON (`$itemtable`.`entities_id`=`glpi_entities`.`id`) ";
         }

         $query .= " WHERE `$itemtable`.`id` = `glpi_items_tickets`.`items_id`
                           AND `glpi_items_tickets`.`itemtype` = '$itemtype'
                           AND `glpi_items_tickets`.`tickets_id` = '$instID'";

         if ($item->maybeTemplate()) {
            $query .= " AND `$itemtable`.`is_template` = '0'";
         }

         $query .= getEntitiesRestrictRequest(" AND", $itemtable, '', '',
                                              $item->maybeRecursive())."
                   ORDER BY `glpi_entities`.`completename`, `$itemtable`.`name`";

         $result_linked = $DB->query($query);
         $nb            = $DB->numrows($result_linked);

         for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
            $name = $data["name"];
            if ($_SESSION["glpiis_ids_visible"]
                || empty($data["name"])) {
               $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
            }
            if($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk'
               && $itemtype::canView()) {
               $link     = $itemtype::getFormURLWithID($data['id']);
               $namelink = "<a href=\"".$link."\">".$name."</a>";
            } else {
               $namelink = $name;
            }

            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td width='10'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["IDD"]);
               echo "</td>";
            }
            if ($prem) {
               $typename = $item->getTypeName($nb);
               echo "<td class='center top' rowspan='$nb'>".
                      (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename)."</td>";
            }
            echo "<td class='center'>";
            echo Dropdown::getDropdownName("glpi_entities", $data['entity'])."</td>";
            echo "<td class='center".
                     (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
            echo ">".$namelink."</td>";
            echo "<td class='center'>".(isset($data["serial"])? "".$data["serial"]."" :"-").
                 "</td>";
            echo "<td class='center'>".
                   (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
            echo "</tr>";
         }
         $totalnb += $nb;
         
      }

      if ($number) {
         echo $header_begin.$header_bottom.$header_end;
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Ticket' :
               if (($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] != 0)
                   && (count($_SESSION["glpiactiveprofile"]["helpdesk_item_type"]) > 0)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     $nb = countElementsInTable('glpi_items_tickets',
                                                "`tickets_id` = '".$item->getID()."'");
                  }
                  return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);
               }
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Ticket' :
            self::showForTicket($item);
            break;
      }
      return true;
   }

   /**
    * Make a select box for Tracking All Devices
    *
    * @param $myname             select name
    * @param $itemtype           preselected value.for item type
    * @param $items_id           preselected value for item ID (default 0)
    * @param $admin              is an admin access ? (default 0)
    * @param $users_id           user ID used to display my devices (default 0
    * @param $entity_restrict    Restrict to a defined entity (default -1)
    * @param $tickets_id         Id of the ticket
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownAllDevices($myname, $itemtype, $items_id=0, $admin=0, $users_id=0,
                                      $entity_restrict=-1, $tickets_id=0) {
      global $CFG_GLPI, $DB;

      $used = self::getUsedItems($tickets_id);

      $rand = mt_rand();

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] == 0) {
         echo "<input type='hidden' name='$myname' value=''>";
         echo "<input type='hidden' name='items_id' value='0'>";

      } else {
         $rand = mt_rand();
         echo "<div id='tracking_all_devices$rand'>";
         if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,
                                                                     Ticket::HELPDESK_ALL_HARDWARE)) {

            if ($users_id
                &&($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2,
                                                                           Ticket::HELPDESK_MY_HARDWARE))) {
               echo __('Or complete search')."&nbsp;";
            }

            $types = Ticket::getAllTypesForHelpdesk();
            $emptylabel = __('General');
            if ($tickets_id > 0) {
               $emptylabel = Dropdown::EMPTY_VALUE;
            }
            $rand       = Dropdown::showItemTypes($myname, array_keys($types),
                                                  array('emptylabel' => $emptylabel,
                                                        'value'      => $itemtype));
            $found_type = isset($types[$itemtype]);

            $params     = array('itemtype'        => '__VALUE__',
                                'entity_restrict' => $entity_restrict,
                                'admin'           => $admin,
                                'used'            => $used,
                                'myname'          => "items_id",);

            Ajax::updateItemOnSelectEvent("dropdown_$myname$rand","results_$myname$rand",
                                          $CFG_GLPI["root_doc"].
                                             "/ajax/dropdownTrackingDeviceType.php",
                                          $params);
            echo "<span id='results_$myname$rand'>\n";

            // Display default value if itemtype is displayed
            if ($found_type
                && $itemtype) {
                if (($item = getItemForItemtype($itemtype))
                    && $items_id) {
                  if ($item->getFromDB($items_id)) {
                     Dropdown::showFromArray('items_id', array($items_id => $item->getName()),
                                             array('value' => $items_id));
                  }
               } else {
                  $params['itemtype'] = $itemtype;
                  echo "<script type='text/javascript' >\n";
                  Ajax::updateItemJsCode("results_$myname$rand",
                                         $CFG_GLPI["root_doc"].
                                            "/ajax/dropdownTrackingDeviceType.php",
                                         $params);
                  echo '</script>';
               }
            }
            echo "</span>\n";
         }
         echo "</div>";
      }
      return $rand;
   }

   /**
    * Make a select box for Ticket my devices
    *
    * @param $userID          User ID for my device section (default 0)
    * @param $entity_restrict restrict to a specific entity (default -1)
    * @param $itemtype        of selected item (default 0)
    * @param $items_id        of selected item (default 0)
    * @param $tickets_id      Id of the ticket
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdownMyDevices($userID=0, $entity_restrict=-1, $itemtype=0, $items_id=0, $tickets_id=0) {
      global $DB, $CFG_GLPI;

      $used = self::getUsedItems($tickets_id);

      if ($userID == 0) {
         $userID = Session::getLoginUserID();
      }

      $rand        = mt_rand();
      $already_add = $used;

      if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]&pow(2, Ticket::HELPDESK_MY_HARDWARE)) {
         $my_devices = array('' => __('General'));
         if($tickets_id > 0) {
            $my_devices = array('' => Dropdown::EMPTY_VALUE);
         }

         $my_item    = $itemtype.'_'.$items_id;
         $devices    = array();

         // My items
         foreach ($CFG_GLPI["linkuser_types"] as $itemtype) {
            if (($item = getItemForItemtype($itemtype))
                && Ticket::isPossibleToAssignType($itemtype)) {
               $itemtable = getTableForItemType($itemtype);

               $query     = "SELECT *
                             FROM `$itemtable`
                             WHERE `users_id` = '$userID'";
               if ($item->maybeDeleted()) {
                  $query .= " AND `$itemtable`.`is_deleted` = '0' ";
               }
               if ($item->maybeTemplate()) {
                  $query .= " AND `$itemtable`.`is_template` = '0' ";
               }
               if (in_array($itemtype, $CFG_GLPI["helpdesk_visible_types"])) {
                  $query .= " AND `is_helpdesk_visible` = '1' ";
               }

               $query .= getEntitiesRestrictRequest("AND",$itemtable,"",$entity_restrict,
                                                    $item->maybeRecursive())."


                         ORDER BY `name` ";

               $result  = $DB->query($query);
               $nb      = $DB->numrows($result);
               if ($DB->numrows($result) > 0) {
                  $type_name = $item->getTypeName($nb);

                  while ($data = $DB->fetch_assoc($result)) {
                     if (!isset($already_add[$itemtype]) || !in_array($data["id"], $already_add[$itemtype])) {
                        $output = $data["name"];
                        if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                           $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                        }
                        $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                        if ($itemtype != 'Software') {
                           if (!empty($data['serial'])) {
                              $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                           }
                           if (!empty($data['otherserial'])) {
                              $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                           }
                        }
                        $devices[$itemtype."_".$data["id"]] = $output;

                        $already_add[$itemtype][] = $data["id"];
                     }
                  }
               }
            }
         }

         if (count($devices)) {
            $my_devices[__('My devices')] = $devices;
         }
         // My group items
         if (Session::haveRight("show_group_hardware","1")) {
            $group_where = "";
            $query       = "SELECT `glpi_groups_users`.`groups_id`, `glpi_groups`.`name`
                            FROM `glpi_groups_users`
                            LEFT JOIN `glpi_groups`
                              ON (`glpi_groups`.`id` = `glpi_groups_users`.`groups_id`)
                            WHERE `glpi_groups_users`.`users_id` = '$userID' ".
                                  getEntitiesRestrictRequest("AND", "glpi_groups", "",
                                                             $entity_restrict, true);
            $result  = $DB->query($query);

            $first   = true;
            $devices = array();
            if ($DB->numrows($result) > 0) {
               while ($data = $DB->fetch_assoc($result)) {
                  if ($first) {
                     $first = false;
                  } else {
                     $group_where .= " OR ";
                  }
                  $a_groups                     = getAncestorsOf("glpi_groups", $data["groups_id"]);
                  $a_groups[$data["groups_id"]] = $data["groups_id"];
                  $group_where                 .= " `groups_id` IN (".implode(',', $a_groups).") ";
               }

               foreach ($CFG_GLPI["linkgroup_types"] as $itemtype) {
                  if (($item = getItemForItemtype($itemtype))
                      && Ticket::isPossibleToAssignType($itemtype)) {
                     $itemtable  = getTableForItemType($itemtype);
                     $query      = "SELECT *
                                    FROM `$itemtable`
                                    WHERE ($group_where) ".
                                          getEntitiesRestrictRequest("AND", $itemtable, "",
                                                                     $entity_restrict,
                                                                     $item->maybeRecursive());

                     if ($item->maybeDeleted()) {
                        $query .= " AND `is_deleted` = '0' ";
                     }
                     if ($item->maybeTemplate()) {
                        $query .= " AND `is_template` = '0' ";
                     }
                     $query .= ' ORDER BY `name`';

                     $result = $DB->query($query);
                     if ($DB->numrows($result) > 0) {
                        $type_name = $item->getTypeName();
                        if (!isset($already_add[$itemtype])) {
                           $already_add[$itemtype] = array();
                        }
                        while ($data = $DB->fetch_assoc($result)) {
                           if (!in_array($data["id"], $already_add[$itemtype])) {
                              $output = '';
                              if (isset($data["name"])) {
                                 $output = $data["name"];
                              }
                              if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                                 $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                              }
                              $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                              if (isset($data['serial'])) {
                                 $output = sprintf(__('%1$s - %2$s'), $output, $data['serial']);
                              }
                              if (isset($data['otherserial'])) {
                                 $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                              }
                              $devices[$itemtype."_".$data["id"]] = $output;

                              $already_add[$itemtype][] = $data["id"];
                           }
                        }
                     }
                  }
               }
               if (count($devices)) {
                  $my_devices[__('Devices own by my groups')] = $devices;
               }
            }
         }
         // Get linked items to computers
         if (isset($already_add['Computer']) && count($already_add['Computer'])) {
            $search_computer = " XXXX IN (".implode(',',$already_add['Computer']).') ';
            $devices = array();

            // Direct Connection
            $types = array('Monitor', 'Peripheral', 'Phone', 'Printer');
            foreach ($types as $itemtype) {
               if (in_array($itemtype,$_SESSION["glpiactiveprofile"]["helpdesk_item_type"])
                   && ($item = getItemForItemtype($itemtype))) {
                  $itemtable = getTableForItemType($itemtype);
                  if (!isset($already_add[$itemtype])) {
                     $already_add[$itemtype] = array();
                  }
                  $query = "SELECT DISTINCT `$itemtable`.*
                            FROM `glpi_computers_items`
                            LEFT JOIN `$itemtable`
                                 ON (`glpi_computers_items`.`items_id` = `$itemtable`.`id`)
                            WHERE `glpi_computers_items`.`itemtype` = '$itemtype'
                                  AND  ".str_replace("XXXX","`glpi_computers_items`.`computers_id`",
                                                     $search_computer);
                  if ($item->maybeDeleted()) {
                     $query .= " AND `$itemtable`.`is_deleted` = '0' ";
                  }
                  if ($item->maybeTemplate()) {
                     $query .= " AND `$itemtable`.`is_template` = '0' ";
                  }
                  $query .= getEntitiesRestrictRequest("AND",$itemtable,"",$entity_restrict)."
                            ORDER BY `$itemtable`.`name`";

                  $result = $DB->query($query);
                  if ($DB->numrows($result) > 0) {
                     $type_name = $item->getTypeName();
                     while ($data = $DB->fetch_assoc($result)) {
                        if (!in_array($data["id"],$already_add[$itemtype])) {
                           $output = $data["name"];
                           if (empty($output) || $_SESSION["glpiis_ids_visible"]) {
                              $output = sprintf(__('%1$s (%2$s)'), $output, $data['id']);
                           }
                           $output = sprintf(__('%1$s - %2$s'), $type_name, $output);
                           if ($itemtype != 'Software') {
                              $output = sprintf(__('%1$s - %2$s'), $output, $data['otherserial']);
                           }
                           $devices[$itemtype."_".$data["id"]] = $output;

                           $already_add[$itemtype][] = $data["id"];
                        }
                     }
                  }
               }
            }
            if (count($devices)) {
               $my_devices[__('Connected devices')] = $devices;
            }

            // Software
            if (in_array('Software', $_SESSION["glpiactiveprofile"]["helpdesk_item_type"])) {
               $query = "SELECT DISTINCT `glpi_softwareversions`.`name` AS version,
                                `glpi_softwares`.`name` AS name, `glpi_softwares`.`id`
                         FROM `glpi_computers_softwareversions`, `glpi_softwares`,
                              `glpi_softwareversions`
                         WHERE `glpi_computers_softwareversions`.`softwareversions_id` =
                                   `glpi_softwareversions`.`id`
                               AND `glpi_softwareversions`.`softwares_id` = `glpi_softwares`.`id`
                               AND ".str_replace("XXXX",
                                                 "`glpi_computers_softwareversions`.`computers_id`",
                                                 $search_computer)."
                               AND `glpi_softwares`.`is_helpdesk_visible` = '1' ".
                               getEntitiesRestrictRequest("AND","glpi_softwares","",
                                                          $entity_restrict)."
                         ORDER BY `glpi_softwares`.`name`";
               $devices = array();
               $result = $DB->query($query);
               if ($DB->numrows($result) > 0) {
                  $tmp_device = "";
                  $item       = new Software();
                  $type_name  = $item->getTypeName();
                  if (!isset($already_add['Software'])) {
                     $already_add['Software'] = array();
                  }
                  while ($data = $DB->fetch_assoc($result)) {
                     if (!in_array($data["id"], $already_add['Software'])) {
                        $output = sprintf(__('%1$s - %2$s'), $type_name, $data["name"]);
                        $output = sprintf(__('%1$s (%2$s)'), $output,
                                          sprintf(__('%1$s: %2$s'), __('version'),
                                                  $data["version"]));
                        if ($_SESSION["glpiis_ids_visible"]) {
                           $output = sprintf(__('%1$s (%2$s)'), $output, $data["id"]);
                        }
                        $devices["Software_".$data["id"]] = $output;

                        $already_add['Software'][] = $data["id"];
                     }
                  }
                  if (count($devices)) {
                     $my_devices[__('Installed software')] = $devices;
                  }
               }
            }
         }
         echo "<div id='tracking_my_devices'>";
         $rand = Dropdown::showFromArray('my_items', $my_devices);
         echo "</div>";


         // Auto update summary of active or just solved tickets
         $params = array('my_items' => '__VALUE__');

         Ajax::updateItemOnSelectEvent("dropdown_my_items$rand","item_ticket_selection_information",
                                       $CFG_GLPI["root_doc"]."/ajax/ticketiteminformation.php",
                                       $params);

      }
   }

   /**
    * Make a select box with all glpi items
    *
    * @param $options array of possible options:
    *    - name         : string / name of the select (default is users_id)
    *    - value
    *    - comments     : boolean / is the comments displayed near the dropdown (default true)
    *    - entity       : integer or array / restrict to a defined entity or array of entities
    *                      (default -1 : no restriction)
    *    - entity_sons  : boolean / if entity restrict specified auto select its sons
    *                      only available if entity is a single value not an array(default false)
    *    - rand         : integer / already computed rand value
    *    - toupdate     : array / Update a specific item on select change on dropdown
    *                      (need value_fieldname, to_update, url
    *                      (see Ajax::updateItemOnSelectEvent for information)
    *                      and may have moreparams)
    *    - used         : array / Already used items ID: not to display in dropdown (default empty)
    *    - on_change    : string / value to transmit to "onChange"
    *    - display      : boolean / display or get string (default true)
    *    - width        : specific width needed (default 80%)
    *
   **/
 static function dropdown($options = array()) {
      global $DB;

      // Default values
      $p['name']           = 'items';
      $p['value']          = '';
      $p['all']            = 0;
      $p['on_change']      = '';
      $p['comments']       = 1;
      $p['width']          = '80%';
      $p['entity']         = -1;
      $p['entity_sons']    = false;
      $p['used']           = array();
      $p['toupdate']       = '';
      $p['rand']           = mt_rand();
      $p['display']        = true;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $itemtypes = array('Computer', 'Monitor', 'NetworkEquipment', 'Peripheral', 'Phone', 'Printer');

      $query = "";
      foreach ($itemtypes as $type) {
         $table = getTableForItemType($type);
         if (!empty($query)) {
            $query .= " UNION ";
         }
         $query .= " SELECT `$table`.`id` AS id , '$type' AS itemtype , `$table`.`name` AS name
                     FROM `$table`
                     WHERE `$table`.`id` IS NOT NULL AND `$table`.`is_deleted` = '0' AND `$table`.`is_template` = '0' ";
      }

      $result = $DB->query($query);
      $output = array();
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $item = getItemForItemtype($data['itemtype']);
            $output[$data['itemtype']."_".$data['id']] = $item->getTypeName()." - ".$data['name'];
         }
      }

      return Dropdown::showFromArray($p['name'], $output, $p);
   }

   /**
    * Return used items for a ticket
    *
    * @param type $tickets_id
    * @return type
    */
   static function getUsedItems($tickets_id) {

      $data = getAllDatasFromTable('glpi_items_tickets', " `tickets_id` = ".$tickets_id);
      $used = array();
      if (!empty($data)) {
         foreach ($data as $val) {
            $used[$val['itemtype']][] = $val['items_id'];
         }
      }

      return $used;
   }

   /**
    * Form for Followup on Massive action
   **/
   static function showFormMassiveAction($ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'add_item' :
            Dropdown::showAllItems("items_id", 0, 0, $_SESSION['glpiactive_entity'],
                                       $CFG_GLPI["ticket_types"], false, true, 'item_itemtype');
            echo "<br><input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
            break;

         case 'delete_item' :
            Dropdown::showAllItems("items_id", 0, 0, $_SESSION['glpiactive_entity'],
                                       $CFG_GLPI["ticket_types"], false, true, 'item_itemtype');
            echo "<br><input type='submit' name='delete' value=\"".__('Delete permanently')."\" class='submit'>";
            break;
      }

   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case 'add_item' :
            static::showFormMassiveAction($ma);
            return true;

         case 'delete_item' :
            static::showFormMassiveAction($ma);
            return true;
      }

      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {

      switch ($ma->getAction()) {
         case 'add_item' :
            $input = $ma->getInput();

            $item_ticket = new static();
            foreach ($ids as $id) {
               if ($item->getFromDB($id) && !empty($input['items_id'])) {
                  $input['tickets_id'] = $id;
                  $input['itemtype'] = $input['item_itemtype'];

                  if ($item_ticket->can(-1, CREATE, $input)) {
                     $ok = true;
                     if (!$item_ticket->add($input)) {
                        $ok = false;
                     }

                     if ($ok) {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                     }

                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                     $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                  }

               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
               }
            }
            return;

         case 'delete_item' :
            $input = $ma->getInput();
            $item_ticket = new static();
            foreach ($ids as $id) {
               if ($item->getFromDB($id) && !empty($input['items_id'])) {
                  $item_found = $item_ticket->find("`tickets_id` = $id AND `itemtype` = '".$input['item_itemtype']."' AND `items_id` = ".$input['items_id']);
                  if (!empty($item_found)) {
                     $item_founds_id = array_keys($item_found);
                     $input['id'] = $item_founds_id[0];

                     if ($item_ticket->can($input['id'], DELETE, $input)) {
                        $ok = true;
                        if (!$item_ticket->delete($input)) {
                           $ok = false;
                        }

                        if ($ok) {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                           $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                           $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }

                     } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                     }

                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                     $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
                  }

               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  $ma->addMessage($item->getErrorMessage(ERROR_NOT_FOUND));
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   function getSearchOptions() {

      $tab                          = array();

      $tab[13]['table']             = 'glpi_items_tickets';
      $tab[13]['field']             = 'items_id';
      $tab[13]['name']              = _n('Associated element', 'Associated elements', 2);
      $tab[13]['datatype']          = 'specific';
      $tab[13]['comments']          = true;
      $tab[13]['nosort']            = true;
      $tab[13]['additionalfields']  = array('itemtype');

      $tab[131]['table']            = 'glpi_items_tickets';
      $tab[131]['field']            = 'itemtype';
      $tab[131]['name']             = _n('Associated item type', 'Associated item types',2);
      $tab[131]['datatype']         = 'itemtypename';
      $tab[131]['itemtype_list']    = 'ticket_types';
      $tab[131]['nosort']           = true;

      return $tab;
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'items_id':
            if (isset($values['itemtype'])) {
               if (isset($options['comments']) && $options['comments']) {
                  $tmp = Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                   $values[$field], 1);
                  return sprintf(__('%1$s %2$s'), $tmp['name'],
                                 Html::showToolTip($tmp['comment'], array('display' => false)));

               }
               return Dropdown::getDropdownName(getTableForItemtype($values['itemtype']),
                                                $values[$field]);
            }
            break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {
      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'items_id' :
            if (isset($values['itemtype']) && !empty($values['itemtype'])) {
               $options['name']  = $name;
               $options['value'] = $values[$field];
               return Dropdown::show($values['itemtype'], $options);
            }
            break;
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

   /**
    * Add a message on add action
   **/
   function addMessageOnAddAction() {
      global $CFG_GLPI;

      $addMessAfterRedirect = false;
      if (isset($this->input['_add'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $item = getItemForItemtype($this->fields['itemtype']);
         $item->getFromDB($this->fields['items_id']);

         $link = $item->getFormURL();
         if (!isset($link)) {
            return;
         }
         if (($name = $item->getName()) == NOT_AVAILABLE) {
            //TRANS: %1$s is the itemtype, %2$d is the id of the item
            $item->fields['name'] = sprintf(__('%1$s - ID %2$d'),
                                            $item->getTypeName(1), $item->fields['id']);
         }

         $display = (isset($this->input['_no_message_link'])?$item->getNameID()
                                                            :$item->getLink());

         // Do not display quotes
         //TRANS : %s is the description of the added item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully added'),
                                                  stripslashes($display)));

      }
   }

   /**
    * Add a message on delete action
   **/
   function addMessageOnPurgeAction() {

      if (!$this->maybeDeleted()) {
         return;
      }

      $addMessAfterRedirect = false;
      if (isset($this->input['_delete'])) {
         $addMessAfterRedirect = true;
      }

      if (isset($this->input['_no_message'])
          || !$this->auto_message_on_action) {
         $addMessAfterRedirect = false;
      }

      if ($addMessAfterRedirect) {
         $item = getItemForItemtype($this->fields['itemtype']);
         $item->getFromDB($this->fields['items_id']);

         $link = $item->getFormURL();
         if (!isset($link)) {
            return;
         }
         if (isset($this->input['_no_message_link'])) {
            $display = $item->getNameID();
         } else {
            $display = $item->getLink();
         }
         //TRANS : %s is the description of the updated item
         Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'), __('Item successfully deleted'), $display));

      }
   }
}
?>