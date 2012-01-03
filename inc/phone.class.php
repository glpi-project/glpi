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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// CLASSES peripherals

class Phone extends CommonDBTM {

   // From CommonDBTM
   public $dohistory            = true;
   protected $forward_entity_to = array('Infocom', 'NetworkPort', 'ReservationItem');


   static function getTypeName($nb=0) {

      //TRANS: Test of comment for translation (mark : //TRANS)
      return _n('Phone','Phones',$nb);
   }


   function canCreate() {
      return Session::haveRight('phone', 'w');
   }


   function canView() {
      return Session::haveRight('phone', 'r');
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addStandardTab('Computer_Item', $ong, $options);
      $this->addStandardTab('NetworkPort', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Note', $ong, $options);
      $this->addStandardTab('Reservation', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function post_addItem() {
      global $DB, $CFG_GLPI;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Infocoms
         $ic = new Infocom();
         $ic->cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Ports
         NetworkPort::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Contract
         $query = "SELECT `contracts_id`
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."'";
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            $contractitem = new Contract_Item();

            while ($data=$DB->fetch_array($result)) {
               $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                        'itemtype'     => $this->getType(),
                                        'items_id'     => $this->fields['id']));
            }
         }

         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."'";
         $result = $DB->query($query);

         if ($DB->numrows($result)>0) {
            $docitem = new Document_Item();

            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype'     => $this->getType(),
                                   'items_id'     => $this->fields['id']));
            }
         }
      }

   }


   function cleanDBonPurge() {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_computers_items`
                WHERE `itemtype` = '".$this->getType()."'
                      AND `items_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $conn = new Computer_Item();

            while ($data = $DB->fetch_array($result)) {
               $data['_no_auto_action'] = true;
               $conn->delete($data);
            }
         }
      }
   }


   /**
    * Print the phone form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
    **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      $target       = $this->getFormURL();
      $withtemplate = '';

      if (isset($options['target'])) {
        $target = $options['target'];
      }

      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf('%1$s%2$s',__('Name'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && $options['withtemplate']==2),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      Dropdown::show('State', array('value' => $this->fields["states_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>";
      echo "<td>";
      Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      Dropdown::show('PhoneType', array('value' => $this->fields["phonetypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'interface',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Dropdown::show('Group', array('name'      => 'groups_id_tech',
                                    'value'     => $this->fields['groups_id_tech'],
                                    'entity'    => $this->fields['entities_id'],
                                    'condition' => '`is_assign`'));
      echo "</td>";
      echo "<td>".__('Model')."</td>";
      echo "<td>";
      Dropdown::show('PhoneModel', array('value' => $this->fields["phonemodels_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "contact_num");
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"serial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Alternate username')."&nbsp;:</td><td>";
      Html::autocompletionTextField($this, "contact");
      echo "</td>";
      echo "<td>".__('Inventory number').
                  (isset($options['withtemplate']) && $options['withtemplate']?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && $options['withtemplate']==2),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'otherserial', array('value' => $objectName));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>";
      echo "<td>".__('Management type')."</td>";
      echo "<td>";
      Dropdown::showGlobalSwitch($this->fields["id"],
                                 array('withtemplate' => $withtemplate,
                                       'value'        => $this->fields["is_global"],
                                       'management_restrict'
                                                      => $CFG_GLPI["phones_management_restrict"],
                                       'target'       => $target));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group')."</td>";
      echo "<td>";
      Dropdown::show('Group', array('value'     => $this->fields["groups_id"],
                                    'entity'    => $this->fields["entities_id"],
                                    'condition' => '`is_itemgroup`'));
      echo "</td>";
      echo "<td rowspan='7'>".__('Comments')."</td>";
      echo "<td rowspan='7'>
            <textarea cols='45' rows='14' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Brand')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"brand");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Power supply')."</td>";
      echo "<td>";
      Dropdown::show('PhonePowerSupply', array('value' => $this->fields["phonepowersupplies_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Firmware', 'Firmwares', 1)."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "firmware");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Number of lines')."</td><td>";
      Html::autocompletionTextField($this, "number_line");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Flags')."</td>";
      echo "<td>";
      // micro?
      echo "\n<table><tr><td>".__('Headset')."</td>";
      echo "<td>&nbsp;";
      Dropdown::showYesNo("have_headset", $this->fields["have_headset"]);
      echo "</td></tr>";
      // hp?
      echo "<tr><td>".__('Speaker')."</td>";
      echo "<td>&nbsp;";
      Dropdown::showYesNo("have_hp", $this->fields["have_hp"]);
      echo "</td></tr></table>\n";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if ((!isset($options['withtemplate']) || $options['withtemplate']==0)
          && !empty($this->fields['template_name'])) {
         echo "<span class='small_space'>";
         printf(__('Created from the template %d'),$this->fields['template_name']);
         echo "</span>";
      } else {
         echo "&nbsp;";
      }
      echo "</td><td>";
      if (isset($options['withtemplate']) && $options['withtemplate']) {
         //TRANS: %s is the datetime of insertion
         printf(__('Created on %s'),Html::convDateTime($_SESSION["glpi_currenttime"]));
      } else {
         //TRANS: %s is the datetime of insertion
         printf(__('Last update on %s'),Html::convDateTime($this->fields["date_mod"]));
      }
      echo "</td></tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
    **/
   function getSelectLinkedItem() {

      return "SELECT 'Computer', `computers_id`
              FROM `glpi_computers_items`
              WHERE `itemtype` = '".$this->getType()."'
                    AND `items_id` = '" . $this->fields['id']."'";
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = __('Characteristics');

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = __('Name');
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = __('ID');
      $tab[2]['massiveaction'] = false;

      $tab+=Location::getSearchOptionsToAdd();

      $tab[4]['table'] = 'glpi_phonetypes';
      $tab[4]['field'] = 'name';
      $tab[4]['name'] = __('Type');

      $tab[40]['table'] = 'glpi_phonemodels';
      $tab[40]['field'] = 'name';
      $tab[40]['name']  = __('Model');

      $tab[31]['table'] = 'glpi_states';
      $tab[31]['field'] = 'completename';
      $tab[31]['name']  = __('Status');

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'serial';
      $tab[5]['name']      = __('Serial Number');
      $tab[5]['datatype']  = 'string';

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'otherserial';
      $tab[6]['name']      = __('Inventory Number');
      $tab[6]['datatype']  = 'string';

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'contact';
      $tab[7]['name']      = __('Alternate username');
      $tab[7]['datatype']  = 'string';

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'contact_num';
      $tab[8]['name']      = __('Alternate username number');
      $tab[8]['datatype']  = 'string';

      $tab[9]['table']     = $this->getTable();
      $tab[9]['field']     = 'number_line';
      $tab[9]['name']      = __('Number of lines');
      $tab[9]['datatype']  = 'string';

      $tab[70]['table'] = 'glpi_users';
      $tab[70]['field'] = 'name';
      $tab[70]['name']  = __('User');

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'completename';
      $tab[71]['name']      = __('Group');
      $tab[71]['condition'] = '`is_itemgroup`';

      $tab[19]['table']         = $this->getTable();
      $tab[19]['field']         = 'date_mod';
      $tab[19]['name']          = __('Last update');
      $tab[19]['datatype']      = 'datetime';
      $tab[19]['massiveaction'] = false;

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = __('Comments');
      $tab[16]['datatype'] = 'text';

      $tab[90]['table']         = $this->getTable();
      $tab[90]['field']         = 'notepad';
      $tab[90]['name']          = __('Notes');
      $tab[90]['massiveaction'] = false;

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'brand';
      $tab[11]['name']     = __('Brand');
      $tab[11]['datatype'] = 'string';

      $tab[23]['table'] = 'glpi_manufacturers';
      $tab[23]['field'] = 'name';
      $tab[23]['name']  = __('Manufacturer');

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = __('Technician in charge of the hardware');

      $tab[49]['table']     = 'glpi_groups';
      $tab[49]['field']     = 'completename';
      $tab[49]['linkfield'] = 'groups_id_tech';
      $tab[49]['name']      = __('Group in charge of the hardware');
      $tab[49]['condition'] = '`is_assign`';

      $tab[42]['table'] = 'glpi_phonepowersupplies';
      $tab[42]['field'] = 'name';
      $tab[42]['name']  = __('Power supply');

      $tab[43]['table']    = $this->getTable();
      $tab[43]['field']    = 'have_headset';
      $tab[43]['name']     = __('Headset');
      $tab[43]['datatype'] = 'bool';

      $tab[44]['table']    = $this->getTable();
      $tab[44]['field']    = 'have_hp';
      $tab[44]['name']     = __('Speaker');
      $tab[44]['datatype'] = 'bool';

      $tab[80]['table']         = 'glpi_entities';
      $tab[80]['field']         = 'completename';
      $tab[80]['name']          = $LANG['entity'][0];
      $tab[80]['massiveaction'] = false;

      $tab[82]['table']         = $this->getTable();
      $tab[82]['field']         = 'is_global';
      $tab[82]['name']          = __('Global management');
      $tab[82]['datatype']      = 'bool';
      $tab[82]['massiveaction'] = false;

      return $tab;
   }

}
?>
