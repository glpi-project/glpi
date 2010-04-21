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

// ----------------------------------------------------------------------
// Original Author of file: Remi Collet
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/// Netpoint class
class Netpoint extends CommonDropdown {

   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'locations_id',
                         'label' => $LANG['common'][15],
                         'type'  => 'dropdownValue',
                         'list'  => true));
   }

   static function getTypeName() {
      global $LANG;

      return $LANG['setup'][73];
   }

   function canCreate() {
      return haveRight('entity_dropdown','w');
   }

   function canView() {
      return haveRight('entity_dropdown','r');
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
    */
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab+=Location::getSearchOptionsToAdd();

      $tab[3]['datatype']      = 'itemlink';
      $tab[3]['itemlink_type'] = 'Location';

      return $tab;
   }

   /**
    * Handled Multi add item
    *
    * @param $input array of values
    *
    */
   function addMulti ($input) {
      global $LANG;

      $this->check(-1,'w',$input);
      for ($i=$input["_from"] ; $i<=$input["_to"] ; $i++) {
         $input["name"] = $input["_before"].$i.$input["_after"];
         $this->add($input);
      }
      Event::log(0, "dropdown", 5, "setup", $_SESSION["glpiname"]." ".$LANG['log'][20]);
      refreshDropdownPopupInMainWindow();
   }

   /**
    * Print out an HTML "<select>" for a dropdown with preselected value
    *
    *
    * @param $myname the name of the HTML select
    * @param $value the preselected value we want
    * @param $locations_id default location ID for search
    * @param $display_comment display the comment near the dropdown
    * @param $entity_restrict Restrict to a defined entity
    * @param $devtype
    * @return nothing (display the select box)
    *
    */
   static function dropdownNetpoint($myname,$value=0,$locations_id=-1,$display_comment=1,
                                    $entity_restrict=-1,$devtype=-1) {
      global $CFG_GLPI,$LANG;

      $rand = mt_rand();
      $name = "------";
      $comment = "";
      $limit_length = $_SESSION["glpidropdown_chars_limit"];
      if (empty($value)) {
         $value = 0;
      }
      if ($value > 0) {
         $tmpname = Dropdown::getDropdownName("glpi_netpoints",$value,1);
         if ($tmpname["name"] != "&nbsp;") {
            $name = $tmpname["name"];
            $comment = $tmpname["comment"];
            $limit_length = max(utf8_strlen($name),$_SESSION["glpidropdown_chars_limit"]);
         }
      }
      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         if ($locations_id < 0 || $devtype == 'NetworkEquipment') {
            $nb = countElementsInTableForEntity("glpi_netpoints",$entity_restrict);
         } else if ($locations_id > 0) {
            $nb = countElementsInTable("glpi_netpoints", "locations_id=$locations_id ");
         } else {
            $nb = countElementsInTable("glpi_netpoints",
                                       "locations_id=0 ".getEntitiesRestrictRequest(" AND ",
                                                                                    "glpi_netpoints",
                                                                                    '',
                                                                                    $entity_restrict));
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
                      'devtype'         => $devtype,);

      $default = "<select name='$myname'><option value='$value'>$name</option></select>";
      ajaxDropdown($use_ajax,"/ajax/dropdownNetpoint.php",$params,$default,$rand);

      // Display comment
      if ($display_comment) {
         echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/aide.png'".
                "onmouseout='cleanhide('comment_$myname$rand')'".
                "onmouseover='cleandisplay('comment_$myname$rand')'>";

         $item = new Netpoint();
         if ($item->canCreate()) {
            echo "<img alt='' title='".$LANG['buttons'][8]."' src='".$CFG_GLPI["root_doc"].
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
    */
   function getID (&$input) {
      global $DB;

      if (!empty($input["name"])) {
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `name` = '".$input["name"]."'
                     AND `locations_id`='".(isset($input["locations_id"])?$input["locations_id"]:0)."'".
                     getEntitiesRestrictRequest(' AND ',$this->getTable(),'',
                                                $input['entities_id'],$this->maybeRecursive());

         // Check twin :
         if ($result_twin = $DB->query($query) ) {
            if ($DB->numrows($result_twin) > 0) {
               return $DB->result($result_twin,0,"id");
            }
         }
      }
      return -1;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['name']['table'] = $this->getTable();
      $tab[1]['name']['field'] = 'name';
      $tab[1]['name']['name'] = $LANG["common"][16];
      $tab[1]['name']['type'] = 'text';

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table'] = $this->getTable();
      $tab[3]['field'] = 'logical_number';
      $tab[3]['name'] = $LANG["networking"][21];
      $tab[3]['datatype'] = 'integer';

      $tab[4]['table'] = $this->getTable();
      $tab[4]['field'] = 'mac';
      $tab[4]['name'] = $LANG["device_iface"][2];
      $tab[4]['datatype'] = 'text';

      $tab[5]['table'] = $this->getTable();
      $tab[5]['field'] = 'ip';
      $tab[5]['name'] = $LANG["networking"][14];
      $tab[5]['datatype'] = 'text';

      $tab[6]['table'] = $this->getTable();
      $tab[6]['field'] = 'netmask';
      $tab[6]['name'] = $LANG["networking"][60];
      $tab[6]['datatype'] = 'text';

      $tab[7]['table'] = $this->getTable();
      $tab[7]['field'] = 'subnet';
      $tab[7]['name'] = $LANG["networking"][61];
      $tab[7]['datatype'] = 'text';

      $tab[8]['table'] = $this->getTable();
      $tab[8]['field'] = 'gateway';
      $tab[8]['name'] = $LANG["networking"][59];
      $tab[8]['datatype'] = 'text';

      $tab[9]['table'] = 'glpi_netpoints';
      $tab[9]['field'] = 'netpoints_id';
      $tab[9]['netpoint']['name'] = $LANG["networking"][51];
      $tab[9]['netpoint']['datatype'] = 'text';

      $tab[10]['table'] = 'glpi_networkinterfaces';
      $tab[10]['field'] = 'networkinterfaces_id';
      $tab[10]['name'] = $LANG["networking"][51];
      $tab[10]['datatype'] = 'text';

      $tab[20]['table'] = $this->getTable();
      $tab[20]['field'] = 'itemtype';
      $tab[20]['name'] = $LANG['common'][17];
      $tab[20]['datatype'] = 'itemtype';

      $tab[21]['table'] = $this->getTable();
      $tab[21]['field'] = 'items_id';
      $tab[21]['name'] = 'ID';
      $tab[21]['datatype'] = 'integer';

      return $tab;
   }
}

?>