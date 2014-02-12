<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Netpoint class
class Netpoint extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;


   function getAdditionalFields() {

      return array(array('name'  => 'locations_id',
                         'label' => __('Location'),
                         'type'  => 'dropdownValue',
                         'list'  => true));
   }


   static function getTypeName($nb=0) {
      return _n('Network outlet', 'Network outlets', $nb);
   }


   static function canCreate() {
      return Session::haveRight('entity_dropdown', 'w');
   }


   static function canView() {
      return Session::haveRight('entity_dropdown', 'r');
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab  = parent::getSearchOptions();

      $tab += Location::getSearchOptionsToAdd();

      $tab[3]['datatype']      = 'itemlink';

      return $tab;
   }


   /**
    * Handled Multi add item
    *
    * @since version 0.83 (before addMulti)
    *
    * @param $input array of values
   **/
   function executeAddMulti(array $input) {

      $this->check(-1, 'w', $input);
      for ($i=$input["_from"] ; $i<=$input["_to"] ; $i++) {
         $input["name"] = $input["_before"].$i.$input["_after"];
         $this->add($input);
      }
      Event::log(0, "dropdown", 5, "setup",
                 sprintf(__('%1$s adds several netpoints'), $_SESSION["glpiname"]));
      Ajax::refreshDropdownPopupInMainWindow();
   }


   /**
    * Print out an HTML "<select>" for a dropdown with preselected value
    *
    * @param $myname             the name of the HTML select
    * @param $value              the preselected value we want (default 0)
    * @param $locations_id       default location ID for search (default -1)
    * @param $display_comment    display the comment near the dropdown (default 1)
    * @param $entity_restrict    Restrict to a defined entity(default -1)
    * @param $devtype            (default '')
    *
    * @return nothing (display the select box)
   **/
   static function dropdownNetpoint($myname, $value=0, $locations_id=-1, $display_comment=1,
                                    $entity_restrict=-1, $devtype='') {
      global $CFG_GLPI;

      $rand          = mt_rand();
      $name          = Dropdown::EMPTY_VALUE;
      $comment       = "";
      $limit_length  = $_SESSION["glpidropdown_chars_limit"];
      if (empty($value)) {
         $value = 0;
      }
      if ($value > 0) {
         $tmpname = Dropdown::getDropdownName("glpi_netpoints",$value,1);
         if ($tmpname["name"] != "&nbsp;") {
            $name          = $tmpname["name"];
            $comment       = $tmpname["comment"];
            $limit_length  = max(Toolbox::strlen($name),$_SESSION["glpidropdown_chars_limit"]);
         }
      }
      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         if (($locations_id < 0)
             || ($devtype == 'NetworkEquipment')) {
            $nb = countElementsInTableForEntity("glpi_netpoints", $entity_restrict);
         } else if ($locations_id > 0) {
            $nb = countElementsInTable("glpi_netpoints", "locations_id=$locations_id ");
         } else {
            $nb = countElementsInTable("glpi_netpoints",
                                       "locations_id=0 ".
                                          getEntitiesRestrictRequest(" AND ", "glpi_netpoints",
                                                                     '', $entity_restrict));
         }
         if ($nb > $CFG_GLPI["ajax_limit_count"]) {
            $use_ajax = true;
         }
      }

      $params = array('searchText'      => '__VALUE__',
                      'value'           => $value,
                      'locations_id'    => $locations_id,
                      'myname'          => $myname,
                      'limit'           => $limit_length,
                      'comment'         => $display_comment,
                      'rand'            => $rand,
                      'entity_restrict' => $entity_restrict,
                      'devtype'         => $devtype);

      $default = "<select name='$myname'><option value='$value'>$name</option></select>";
      Ajax::dropdown($use_ajax,"/ajax/dropdownNetpoint.php",$params,$default,$rand);

      // Display comment
      if ($display_comment) {
         Html::showToolTip($comment);

         $item = new self();
         if ($item->canCreate()) {
            echo "<img alt='' title=\"".__s('Add')."\" src='".$CFG_GLPI["root_doc"].
                  "/pics/add_dropdown.png' style='cursor:pointer; margin-left:2px;' ".
                  "onClick=\"var w = window.open('".$item->getFormURL().
                  "?popup=1&amp;rand=$rand' ,'glpipopup', 'height=400, ".
                  "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">";
         }
      }
      return $rand;
   }


   /**
    * check if a netpoint already exists (before import)
    *
    * @param $input array of value to import (name, locations_id, entities_id)
    *
    * @return the ID of the new (or -1 if not found)
   **/
   function findID(array &$input) {
      global $DB;

      if (!empty($input["name"])) {
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `name` = '".$input["name"]."'
                         AND `locations_id` = '".(isset($input["locations_id"])
                                                      ?$input["locations_id"]:0)."'".
                         getEntitiesRestrictRequest(' AND ', $this->getTable(), '',
                                                    $input['entities_id'], $this->maybeRecursive());

         // Check twin :
         if ($result_twin = $DB->query($query) ) {
            if ($DB->numrows($result_twin) > 0) {
               return $DB->result($result_twin,0,"id");
            }
         }
      }
      return -1;
   }


   function post_addItem() {

      $parent = $this->fields['locations_id'];
      if ($parent) {
         $changes[0] = '0';
         $changes[1] = '';
         $changes[2] = addslashes($this->getNameID());
         Log::history($parent, 'Location', $changes, $this->getType(), Log::HISTORY_ADD_SUBITEM);
      }
   }


   function post_deleteFromDB() {

      $parent = $this->fields['locations_id'];
      if ($parent) {
         $changes[0] = '0';
         $changes[1] = addslashes($this->getNameID());
         $changes[2] = '';
         Log::history($parent, 'Location', $changes, $this->getType(), Log::HISTORY_DELETE_SUBITEM);
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Location' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry(self::getTypeName(2),
                                              countElementsInTable($this->getTable(),
                                                                   "locations_id
                                                                        = '".$item->getID()."'"));
               }
               return self::getTypeName(2);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Location') {
         self::showForLocation($item);
      }
      return true;
   }


   /**
    * Print the HTML array of the Netpoint associated to a Location
    *
    * @param $item Location
    *
    * @return Nothing (display)
   **/
   static function showForLocation($item) {
      global $DB, $CFG_GLPI;

      $ID       = $item->getField('id');
      $netpoint = new self();
      $item->check($ID, 'r');
      $canedit  = $item->can($ID, 'w');

      if (isset($_POST["start"])) {
         $start = $_POST["start"];
      } else {
         $start = 0;
      }
      $number = countElementsInTable('glpi_netpoints', "`locations_id`='$ID'");

      if ($canedit) {
         echo "<div class='first-bloc'>";
         // Minimal form for quick input.
         echo "<form action='".$netpoint->getFormURL()."' method='post'>";
         echo "<br><table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td class='b'>"._n('Network outlet', 'Network outlets', 1)."</td>";
         echo "<td>".__('Name')."</td><td>";
         Html::autocompletionTextField($item, "name", array('value' => ''));
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='locations_id' value='$ID'></td>";
         echo "<td><input type='submit' name='add' value=\""._sx('button','Add')."\" class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();

         // Minimal form for massive input.
         echo "<form action='".$netpoint->getFormURL()."' method='post'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2 center'>";
         echo "<td class='b'>"._n('Network outlet', 'Network outlets', 2)."</td>";
         echo "<td>".__('Name')."</td><td>";
         echo "<input type='text' maxlength='100' size='10' name='_before'>&nbsp;";
         Dropdown::showInteger('_from', 0, 0, 400);
         echo "&nbsp;-->&nbsp;";
         Dropdown::showInteger('_to', 0, 0, 400);
         echo "&nbsp;<input type='text' maxlength='100' size='10' name='_after'><br>";
         echo "<input type='hidden' name='entities_id' value='".$_SESSION['glpiactive_entity']."'>";
         echo "<input type='hidden' name='locations_id' value='$ID'></td>";
         echo "<input type='hidden' name='_method' value='AddMulti'></td>";
         echo "<td><input type='submit' name='execute' value=\""._sx('button','Add')."\"
                    class='submit'>";
         echo "</td></tr>\n";
         echo "</table>\n";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".self::getTypeName(1)."</th>";
         echo "<th>".__('No item found')."</th></tr>";
         echo "</table>\n";
      } else {
         Html::printAjaxPager(sprintf(__('Network outlets for %s'), $item->getTreeLink()),
                              $start, $number);

         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $paramsma = array('num_displayed'    => $_SESSION['glpilist_limit'],
                              'specific_actions' => array('purge' => _x('button',
                                                                        'Delete permanently')));
            Html::showMassiveActions(__CLASS__, $paramsma);
         }

         echo "<table class='tab_cadre_fixe'><tr>";

         if ($canedit) {
            echo "<th width='10'>";
            Html::checkAllAsCheckbox('mass'.__CLASS__.$rand);
            echo "</th>";
         }

         echo "<th>".__('Name')."</th>"; // Name
         echo "<th>".__('Comments')."</th>"; // Comment
         echo "</tr>\n";

         $crit = array('locations_id' => $ID,
                       'ORDER'        => 'name',
                       'START'        => $start,
                       'LIMIT'        => $_SESSION['glpilist_limit']);

         Session::initNavigateListItems('Netpoint',
         //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));

         foreach ($DB->request('glpi_netpoints', $crit) as $data) {
            Session::addToNavigateListItems('Netpoint',$data["id"]);
            echo "<tr class='tab_bg_1'>";

            if ($canedit) {
               echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
            }

            echo "<td><a href='".$netpoint->getFormURL();
            echo '?id='.$data['id']."'>".$data['name']."</a></td>";
            echo "<td>".$data['comment']."</td>";
            echo "</tr>\n";
         }

         echo "</table>\n";

         if ($canedit) {
            $paramsma['ontop'] = false;
            Html::showMassiveActions(__CLASS__, $paramsma);
            Html::closeForm();
         }
         Html::printAjaxPager(sprintf(__('Network outlets for %s'), $item->getTreeLink()),
                              $start, $number);

      }

      echo "</div>\n";
   }


   /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $base            HTMLTableBase object
    * @param $super           HTMLTableSuperHeader object (default NULL
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $base->addHeader($column_name, __('Network outlet'), $super, $father);

   }


   /**
    * @since version 0.84
    *
    * @param $row             HTMLTableRow object (default NULL)
    * @param $item            CommonDBTM object (default NULL)
    * @param $father          HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                            HTMLTableCell $father=NULL, array $options) {

      $column_name = __CLASS__;

      if (isset($options['dont_display'][$column_name])) {
         return;
      }

      $row->addCell($row->getHeaderByName($column_name),
                    Dropdown::getDropdownName("glpi_netpoints", $item->fields["netpoints_id"]),
                    $father);
   }

}
?>
