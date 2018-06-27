<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * NetworkPort Class
 *
 * There is two parts for a given NetworkPort.
 * The first one, generic, only contains the link to the item, the name and the type of network port.
 * All specific characteristics are owned by the instanciation of the network port : NetworkPortInstantiation.
 * Whenever a port is display (through its form or though item port listing), the NetworkPort class
 * load its instantiation from the instantiation database to display the elements.
 * Moreover, in NetworkPort form, if there is no more than one NetworkName attached to the current
 * port, then, the fields of NetworkName are display. Thus, NetworkPort UI remain similar to 0.83
**/
class NetworkPort extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype             = 'itemtype';
   static public $items_id             = 'items_id';
   public $dohistory                   = true;

   static public $checkParentRights    = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

   static protected $forward_entity_to = ['NetworkName'];

   static $rightname                   = 'networking';


   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @since 0.84
    *
    * @see CommonDBTM::getPreAdditionalInfosForName
   **/
   function getPreAdditionalInfosForName() {

      if ($item = $this->getItem()) {
         return $item->getName();
      }
      return '';
   }


   /**
    * \brief get the list of available network port type.
    *
    * @since 0.84
    *
    * @return array of available type of network ports
   **/
   static function getNetworkPortInstantiations() {
      global $CFG_GLPI;

      return $CFG_GLPI['networkport_instantiations'];
   }


   static function getNetworkPortInstantiationsWithNames() {

      $types = self::getNetworkPortInstantiations();
      $tab   = [];
      foreach ($types as $itemtype) {
         $tab[$itemtype] = call_user_func([$itemtype, 'getTypeName']);
      }
      return $tab;
   }


   static function getTypeName($nb = 0) {
      return _n('Network port', 'Network ports', $nb);
   }


   /**
    * \brief get the instantiation of the current NetworkPort
    * The instantiation rely on the instantiation_type field and the id of the NetworkPort. If the
    * network port exists, but not its instantiation, then, the instantiation will be empty.
    *
    * @since 0.84
    *
    * @return the instantiation object or false if the type of instantiation is not known
   **/
   function getInstantiation() {

      if (isset($this->fields['instantiation_type'])
          && in_array($this->fields['instantiation_type'], self::getNetworkPortInstantiations())) {
         if ($instantiation = getItemForItemtype($this->fields['instantiation_type'])) {
            if (!$instantiation->getFromDB($this->getID())) {
               if (!$instantiation->getEmpty()) {
                  unset($instantiation);
                  return false;
               }
            }
            return $instantiation;
         }
      }
      return false;
   }


   /**
    * Change the instantion type of a NetworkPort : check validity of the new type of
    * instantiation and that it is not equal to current ones. Update the NetworkPort and delete
    * the previous instantiation. It is up to the caller to create the new instantiation !
    *
    * @since 0.84
    *
    * @param $new_instantiation_type the name of the new instaniation type
    *
    * @return false on error, true if the previous instantiation is not available (ie.: invalid
    *         instantiation type) or the object of the previous instantiation.
   **/
   function switchInstantiationType($new_instantiation_type) {

      // First, check if the new instantiation is a valid one ...
      if (!in_array($new_instantiation_type, self::getNetworkPortInstantiations())) {
         return false;
      }

      // Load the previous instantiation
      $previousInstantiation = $this->getInstantiation();

      // If the previous instantiation is the same than the new one: nothing to do !
      if (($previousInstantiation !== false)
          && ($previousInstantiation->getType() == $new_instantiation_type)) {
         return $previousInstantiation;
      }

      // We update the current NetworkPort
      $input                       = $this->fields;
      $input['instantiation_type'] = $new_instantiation_type;
      $this->update($input);

      // Then, we delete the previous instantiation
      if ($previousInstantiation !== false) {
         $previousInstantiation->delete($previousInstantiation->fields);
         return $previousInstantiation;
      }

      return true;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate
    */
   function prepareInputForUpdate($input) {
      if (!isset($input["_no_history"])) {
         $input['_no_history'] = false;
      }
      if (isset($input['_create_children'])
          && $input['_create_children']) {
         return $this->splitInputForElements($input);
      }

      return $input;
   }

   /**
    * @see CommonDBTM::post_updateItem
    */
   function post_updateItem($history = 1) {
      global $DB;

      if (count($this->updates)) {
         // Update Ticket Tco
         if (in_array("itemtype", $this->updates)
             || in_array("items_id", $this->updates)) {

            $ip = new IPAddress();
            // Update IPAddress
            foreach ($DB->request('glpi_networknames',
                                  ['itemtype' => 'NetworkPort',
                                         'items_id' => $this->getID()]) as $dataname) {
               foreach ($DB->request('glpi_ipaddresses',
                                     ['itemtype' => 'NetworkName',
                                           'items_id' => $dataname['id']]) as $data) {
                  $ip->update(['id'           => $data['id'],
                                    'mainitemtype' => $this->fields['itemtype'],
                                    'mainitems_id' => $this->fields['items_id']]);
               }
            }
         }
      }
      parent::post_updateItem($history);

      $this->updateDependencies(!$this->input['_no_history']);
   }


   /**
    * \brief split input fields when validating a port
    *
    * The form of the NetworkPort can contain the details of the NetworkPortInstantiation as well as
    * NetworkName elements (if no more than one name is attached to this port). Feilds from both
    * NetworkPortInstantiation and NetworkName must not be process by the NetworkPort::add or
    * NetworkPort::update. But they must be kept for adding or updating these elements. This is
    * done after creating or updating the current port. Otherwise, its ID may not be known (in case
    * of new port).
    * To keep the unused fields, we check each field key. If it is owned by NetworkPort (ie :
    * exists inside the $this->fields array), then they remain inside $input. If they are prefix by
    * "Networkname_", then they are added to $this->input_for_NetworkName. Else, they are for the
    * instantiation and added to $this->input_for_instantiation.
    *
    * This method must be call before NetworkPort::add or NetworkPort::update in case of NetworkPort
    * form. Otherwise, the entry of the database may contain wrong values.
    *
    * @since 0.84
    *
    * @param $input
    *
    * @see updateDependencies for the update
   **/
   function splitInputForElements($input) {

      if (isset($this->input_for_instantiation)
          || isset($this->input_for_NetworkName)
          || isset($this->input_for_NetworkPortConnect)
          || !isset($input)) {
         return;
      }

      $this->input_for_instantiation      = [];
      $this->input_for_NetworkName        = [];
      $this->input_for_NetworkPortConnect = [];

      $clone = clone $this;
      $clone->getEmpty();

      foreach ($input as $field => $value) {
         if (array_key_exists($field, $clone->fields) || $field[0] == '_') {
            continue;
         }
         if (preg_match('/^NetworkName_/', $field)) {
            $networkName_field = preg_replace('/^NetworkName_/', '', $field);
            $this->input_for_NetworkName[$networkName_field] = $value;
         } else if (preg_match('/^NetworkPortConnect_/', $field)) {
            $networkName_field = preg_replace('/^NetworkPortConnect_/', '', $field);
            $this->input_for_NetworkPortConnect[$networkName_field] = $value;
         } else {
            $this->input_for_instantiation[$field] = $value;
         }
         unset($input[$field]);
      }

      return $input;
   }


   /**
    * \brief update all related elements after adding or updating an element
    *
    * splitInputForElements() prepare the data for adding or updating NetworkPortInstantiation and
    * NetworkName. This method will update NetworkPortInstantiation and NetworkName. I must be call
    * after NetworkPort::add or NetworkPort::update otherwise, the networkport ID will not be known
    * and the dependencies won't have a valid items_id field.
    *
    * @since 0.84
    *
    * @param $history   (default 1)
    *
    * @see splitInputForElements() for preparing the input
   **/
   function updateDependencies($history = true) {

      $instantiation = $this->getInstantiation();
      if ($instantiation !== false
          && isset($this->input_for_instantiation)
          && count($this->input_for_instantiation) > 0) {
         $this->input_for_instantiation['networkports_id'] = $this->getID();
         if ($instantiation->isNewID($instantiation->getID())) {
            $instantiation->add($this->input_for_instantiation, [], $history);
         } else {
            $instantiation->update($this->input_for_instantiation, $history);
         }
      }
      unset($this->input_for_instantiation);

      if (isset($this->input_for_NetworkName)
          && count($this->input_for_NetworkName) > 0
          && !isset($_POST['several'])) {

         // Check to see if the NetworkName is empty
         $empty_networkName = empty($this->input_for_NetworkName['name'])
                              && empty($this->input_for_NetworkName['fqdns_id']);
         if (($empty_networkName) && is_array($this->input_for_NetworkName['_ipaddresses'])) {
            foreach ($this->input_for_NetworkName['_ipaddresses'] as $ip_address) {
               if (!empty($ip_address)) {
                  $empty_networkName = false;
                  break;
               }
            }
         }

         $network_name = new NetworkName();
         if (isset($this->input_for_NetworkName['id'])) {

            if ($empty_networkName) {
               // If the NetworkName is empty, then delete it !
               $network_name->delete($this->input_for_NetworkName, true, $history);
            } else {
               // Else, update it
               $network_name->update($this->input_for_NetworkName, $history);
            }

         } else {

            if (!$empty_networkName) { // Only create a NetworkName if it is not empty
               $this->input_for_NetworkName['itemtype'] = 'NetworkPort';
               $this->input_for_NetworkName['items_id'] = $this->getID();
               $newid = $network_name->add($this->input_for_NetworkName, [], $history);
            }
         }
      }
      unset($this->input_for_NetworkName);

      if (isset($this->input_for_NetworkPortConnect)
          && count($this->input_for_NetworkPortConnect) > 0) {
         if (isset($this->input_for_NetworkPortConnect['networkports_id_1'])
             && isset($this->input_for_NetworkPortConnect['networkports_id_2'])
             && !empty($this->input_for_NetworkPortConnect['networkports_id_2'])) {
               $nn  = new NetworkPort_NetworkPort();
               $nn->add($this->input_for_NetworkPortConnect, [], $history);
         }
      }
      unset($this->input_for_NetworkPortConnect);

   }


   /**
    * @see CommonDBTM::prepareInputForAdd
    */
   function prepareInputForAdd($input) {

      if (isset($input["logical_number"]) && (strlen($input["logical_number"]) == 0)) {
         unset($input["logical_number"]);
      }

      if (!isset($input["_no_history"])) {
         $input['_no_history'] = false;
      }

      if (isset($input['_create_children'])
          && $input['_create_children']) {
         $input = $this->splitInputForElements($input);
      }

      return parent::prepareInputForAdd($input);
   }

   /**
    * @see CommonDBTM::post_addItem
    */
   function post_addItem() {
      $this->updateDependencies(!$this->input['_no_history']);
   }


   function cleanDBonPurge() {

      $instantiation = $this->getInstantiation();
      if ($instantiation !== false) {
         $instantiation->cleanDBonItemDelete ($this->getType(), $this->getID());
         unset($instantiation);
      }

      $nn = new NetworkPort_NetworkPort();
      $nn->cleanDBonItemDelete ($this->getType(), $this->getID());

      $nv = new NetworkPort_Vlan();
      $nv->cleanDBonItemDelete ($this->getType(), $this->getID());

      $names = new NetworkName();
      $names->cleanDBonItemDelete ($this->getType(), $this->getID());
   }


   /**
    * Get port opposite port ID if linked item
    *
    * @param $ID networking port ID
    *
    * @return ID of the NetworkPort found, false if not found
   **/
   function getContact($ID) {

      $wire = new NetworkPort_NetworkPort();
      if ($contact_id = $wire->getOppositeContact($ID)) {
         return $contact_id;
      }
      return false;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('NetworkName', $ong, $options);
      $this->addStandardTab('NetworkPort_Vlan', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      $this->addStandardTab('NetworkPortInstantiation', $ong, $options);
      $this->addStandardTab('NetworkPort', $ong, $options);

      return $ong;
   }


   /**
    * Delete All connection of the given network port
    *
    * @param $ID ID of the port
    *
    * @return true on success
   **/
   function resetConnections($ID) {
   }


   /**
    * Get available display options array
    *
    * @since 0.84
    *
    * @return all the options
   **/
   static function getAvailableDisplayOptions() {

      $options[__('Global displays')]
         =  ['characteristics' => ['name'    => __('Characteristics'),
                                             'default' => true],
                  'internet'        => ['name'    => __('Internet information'),
                                             'default' => true],
                  'dynamic_import'  => ['name'    => __('Automatic inventory'),
                                             'default' => false]];
      $options[__('Common options')]
         = NetworkPortInstantiation::getGlobalInstantiationNetworkPortDisplayOptions();
      $options[__('Internet information')]
         = ['names'       => ['name'    => NetworkName::getTypeName(Session::getPluralNumber()),
                                        'default' => false],
                 'aliases'     => ['name'    => NetworkAlias::getTypeName(Session::getPluralNumber()),
                                        'default' => false],
                 'ipaddresses' => ['name'    => IPAddress::getTypeName(Session::getPluralNumber()),
                                        'default' => true],
                 'ipnetworks'  => ['name'    => IPNetwork::getTypeName(Session::getPluralNumber()),
                                        'default' => true]];

      foreach (self::getNetworkPortInstantiations() as $portType) {
         $portTypeName           = $portType::getTypeName(0);
         $options[$portTypeName] = $portType::getInstantiationNetworkPortDisplayOptions();
      }
      return $options;
   }


   /**
    * Show ports for an item
    *
    * @param $item                     CommonDBTM object
    * @param $withtemplate   integer   withtemplate param (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $DB, $CFG_GLPI;

      $rand     = mt_rand();

      $itemtype = $item->getType();
      $items_id = $item->getField('id');

      if (!NetworkEquipment::canView()
          || !$item->can($items_id, READ)) {
         return false;
      }

      $netport       = new self();
      $netport->item = $item;

      if (($itemtype == 'NetworkPort')
          || ($withtemplate == 2)) {
         $canedit = false;
      } else {
         $canedit = $item->canEdit($items_id);
      }
      $showmassiveactions = false;
      if ($withtemplate != 2) {
         $showmassiveactions = $canedit;
      }

      // Show Add Form
      if ($canedit
          && (empty($withtemplate) || ($withtemplate != 2))) {

         echo "\n<form method='get' action='" . $netport->getFormURL() ."'>\n";
         echo "<input type='hidden' name='items_id' value='".$item->getID()."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
         echo "<div class='firstbloc'><table class='tab_cadre_fixe'>\n";
         echo "<tr class='tab_bg_2'><td class='center'>\n";
         echo __('Network port type to be added');
         echo "&nbsp;";

         $instantiations = [];
         foreach (self::getNetworkPortInstantiations() as $inst_type) {
            if (call_user_func([$inst_type, 'canCreate'])) {
               $instantiations[$inst_type] = call_user_func([$inst_type, 'getTypeName']);
            }
         }
         Dropdown::showFromArray('instantiation_type', $instantiations,
                                 ['value' => 'NetworkPortEthernet']);

         echo "</td>\n";
         echo "<td class='tab_bg_2 center' width='50%'>";
         echo __('Add several ports');
         echo "&nbsp;<input type='checkbox' name='several' value='1'></td>\n";
         echo "<td>\n";
         echo "<input type='submit' name='create' value=\""._sx('button', 'Add')."\" class='submit'>\n";
         echo "</td></tr></table></div>\n";
         Html::closeForm();
      }

      if ($showmassiveactions) {
         $checkbox_column = true;
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
      } else {
         $checkbox_column = false;
      }

      $is_active_network_port = false;

      Session::initNavigateListItems('NetworkPort',
                                     //TRANS : %1$s is the itemtype name,
                                     //        %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'),
                                             $item->getTypeName(1), $item->getName()));

      if ($itemtype == 'NetworkPort') {
         $porttypes = ['NetworkPortAlias', 'NetworkPortAggregate'];
      } else {
         $porttypes = self::getNetworkPortInstantiations();
         // Manage NetworkportMigration
         $porttypes[] = '';
      }
      $display_options = self::getDisplayOptions($itemtype);
      $table           = new HTMLTableMain();
      $number_port     = self::countForItem($item);
      $table_options   = ['canedit'         => $canedit,
                               'display_options' => &$display_options];

      // Make table name and add the correct show/hide parameters
      $table_name  = sprintf(__('%1$s: %2$d'), self::getTypeName($number_port), $number_port);

      // Add the link to the modal to display the options ...
      $table_namelink = self::getDisplayOptionsLink($itemtype);

      $table_name = sprintf(__('%1$s - %2$s'), $table_name, $table_namelink);

      $table->setTitle($table_name);

      $c_main = $table->addHeader('main', self::getTypeName(Session::getPluralNumber()));

      if (($display_options['dynamic_import']) && ($item->isDynamic())) {
         $table_options['display_isDynamic'] = true;
      } else {
         $table_options['display_isDynamic'] = false;
      }

      if ($display_options['characteristics']) {
         $c_instant = $table->addHeader('Instantiation', __('Characteristics'));
         $c_instant->setHTMLClass('center');
      }

      if ($display_options['internet']) {

         $options = ['names'       => 'NetworkName',
                          'aliases'     => 'NetworkAlias',
                          'ipaddresses' => 'IPAddress',
                          'ipnetworks'  => 'IPNetwork'];

         $table_options['dont_display'] = [];
         foreach ($options as $option => $itemtype_for_option) {
            if (!$display_options[$option]) {
               $table_options['dont_display'][$itemtype_for_option] = true;
            }
         }

         $c_network = $table->addHeader('Internet', __('Internet information'));
         $c_network->setHTMLClass('center');

      } else {
         $c_network = null;
      }

      foreach ($porttypes as $portType) {

         if (empty($portType)) {
            $group_name  = 'Migration';
            $group_title = __('Network ports waiting for manual migration');
         } else {
            $group_name  = $portType;
            $group_title = $portType::getTypeName(Session::getPluralNumber());
         }

         $t_group = $table->createGroup($group_name, $group_title);

         if (($withtemplate != 2)
             && $canedit) {
            $c_checkbox = $t_group->addHeader('checkbox',
                                              Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand,
                                                                          '__RAND__'), $c_main);
         } else {
            $c_checkbox = null;
         }

         $c_number  = $t_group->addHeader('NetworkPort', "#", $c_main);
         $c_name    = $t_group->addHeader("Name", __('Name'), $c_main);
         $c_name->setItemType('NetworkPort');
         $c_name->setHTMLClass('center');

         if ($table_options['display_isDynamic']) {
            $c_dynamic = $t_group->addHeader("Dynamic", __('Automatic inventory'), $c_main);
            $c_dynamic->setHTMLClass('center');
         }

         if ($display_options['characteristics']) {
            if (empty($portType)) {
               NetworkPortMigration::getMigrationInstantiationHTMLTableHeaders($t_group, $c_instant,
                                                                               $c_network, null,
                                                                               $table_options);
            } else {
               $instantiation = new $portType();
               $instantiation->getInstantiationHTMLTableHeaders($t_group, $c_instant, $c_network,
                                                                null, $table_options);
               unset ($instantiation);
            }
         }

         if ($display_options['internet']
             && !$display_options['characteristics']) {
            NetworkName::getHTMLTableHeader(__CLASS__, $t_group, $c_network, null, $table_options);
         }

         if ($itemtype == 'NetworkPort') {
            switch ($portType) {
               case 'NetworkPortAlias' :
                  $search_table   = 'glpi_networkportaliases';
                  $search_request = "`networkports_id_alias`='$items_id'";
                  break;

               case 'NetworkPortAggregate' :
                  $search_table   = 'glpi_networkportaggregates';
                  $search_request = "`networkports_id_list` like '%\"$items_id\"%'";
                  break;
            }
            $query = "SELECT `networkports_id` AS id
                      FROM  `$search_table`
                      WHERE $search_request";

         } else {
            $query = "SELECT `id`
                      FROM `glpi_networkports`
                      WHERE `items_id` = '$items_id'
                            AND `itemtype` = '$itemtype'
                            AND `instantiation_type` = '$portType'
                            AND `is_deleted` = 0
                      ORDER BY `name`,
                               `logical_number`";
         }

         if ($result = $DB->query($query)) {
            echo "<div class='spaced'>";

            $number_port = $DB->numrows($result);

            if ($number_port != 0) {
               $is_active_network_port = true;

               $save_canedit = $canedit;

               if (!empty($portType)) {
                  $name = sprintf(__('%1$s (%2$s)'), self::getTypeName($number_port),
                                  call_user_func([$portType, 'getTypeName']));
                  $name = sprintf(__('%1$s: %2$s'), $name, $number_port);
               } else {
                  $name    = __('Network ports waiting for manual migration');
                  $canedit = false;
               }

               while ($devid = $DB->fetch_row($result)) {
                  $t_row = $t_group->createRow();

                  $netport->getFromDB(current($devid));

                  // No massive action for migration ports
                  if (($withtemplate != 2)
                      && $canedit
                      && !empty($portType)) {
                     $ce_checkbox =  $t_row->addCell($c_checkbox,
                                                     Html::getMassiveActionCheckBox(__CLASS__, $netport->fields["id"]));
                  } else {
                     $ce_checkbox = null;
                  }
                  $content = "<span class='b'>";
                  // Display link based on default rights
                  if ($save_canedit
                      && ($withtemplate != 2)) {

                     if (!empty($portType)) {
                        $content .= "<a href=\"" . NetworkPort::getFormURLWithID($netport->fields["id"]) ."\">";
                     } else {
                        $content .= "<a href=\"" . NetworkportMigration::getFormURLWithID($netport->fields["id"]) ."\">";
                     }
                  }
                  $content .= $netport->fields["logical_number"];

                  if ($canedit
                      && ($withtemplate != 2)) {
                     $content .= "</a>";
                  }
                  $content .= "</span>";
                  $content .= Html::showToolTip($netport->fields['comment'],
                                                ['display' => false]);

                  $t_row->addCell($c_number, $content);

                  $value = $netport->fields["name"];
                  $t_row->addCell($c_name, $value, null, $netport);

                  if ($table_options['display_isDynamic']) {
                     $t_row->addCell($c_dynamic,
                                     Dropdown::getYesNo($netport->fields['is_dynamic']));
                  }

                  $instant_cell = null;
                  if ($display_options['characteristics']) {
                     $instantiation = $netport->getInstantiation();
                     if ($instantiation !== false) {
                        $instantiation->getInstantiationHTMLTable($netport, $t_row, null,
                                                                  $table_options);
                        unset($instantiation);
                     }
                  } else if ($display_options['internet']) {
                     NetworkName::getHTMLTableCellsForItem($t_row, $netport, null, $table_options);
                  }

               }

               $canedit = $save_canedit;
            }
            echo "</div>";
         }
      }
      if ($is_active_network_port
          && $showmassiveactions) {
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $number_port),
                                      'check_itemtype' => $itemtype,
                                      'container'      => 'mass'.__CLASS__.$rand,
                                      'check_items_id' => $items_id];

         Html::showMassiveActions($massiveactionparams);
      }

      $table->display(['display_thead'                         => false,
                            'display_tfoot'                         => false,
                            'display_header_on_foot_for_each_group' => true]);
      unset($table);

      if (!$is_active_network_port) {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No network port found')."</th></tr>";
         echo "</table>";
      }

      if ($is_active_network_port
          && $showmassiveactions) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
      }
      if ($showmassiveactions) {
         Html::closeForm();
      }

   }


   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      if (!isset($options['several'])) {
         $options['several'] = false;
      }

      if (!self::canView()) {
         return false;
      }

      $this->initForm($ID, $options);

      $recursiveItems = $this->recursivelyGetItems();
      if (count($recursiveItems) > 0) {
         $lastItem             = $recursiveItems[count($recursiveItems) - 1];
         $lastItem_entities_id = $lastItem->getField('entities_id');
      } else {
         $lastItem_entities_id = $_SESSION['glpiactive_entity'];
      }

      $options['entities_id'] = $lastItem_entities_id;
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>";
      $this->displayRecursiveItems($recursiveItems, 'Type');
      echo "&nbsp;:</td>\n<td>";

      // Need these to update information
      echo "<input type='hidden' name='items_id' value='".$this->fields["items_id"]."'>\n";
      echo "<input type='hidden' name='itemtype' value='".$this->fields["itemtype"]."'>\n";
      echo "<input type='hidden' name='_create_children' value='1'>\n";
      echo "<input type='hidden' name='instantiation_type' value='" .
             $this->fields["instantiation_type"]."'>\n";

      $this->displayRecursiveItems($recursiveItems, "Link");
      echo "</td>\n";
      $colspan = 2;

      if (!$options['several']) {
         $colspan ++;
      }
      echo "<td rowspan='$colspan'>".__('Comments')."</td>";
      echo "<td rowspan='$colspan' class='middle'>";
      echo "<textarea cols='45' rows='$colspan' name='comment' >" .
             $this->fields["comment"] . "</textarea>";
      echo "</td></tr>\n";

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'><td>". _n('Port number', 'Ports number', 1) ."</td>\n";
         echo "<td>";
         Html::autocompletionTextField($this, "logical_number", ['size' => 5]);
         echo "</td></tr>\n";

      } else {
         echo "<tr class='tab_bg_1'><td>". _n('Port number', 'Port numbers', Session::getPluralNumber()) ."</td>\n";
         echo "<td>";
         echo "<input type='hidden' name='several' value='yes'>";
         echo "<input type='hidden' name='logical_number' value=''>\n";
         echo __('from') . "&nbsp;";
         Dropdown::showNumber('from_logical_number', ['value' => 0]);
         echo "&nbsp;".__('to') . "&nbsp;";
         Dropdown::showNumber('to_logical_number', ['value' => 0]);
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'><td>" . __('Name') . "</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>\n";

      $instantiation = $this->getInstantiation();
      if ($instantiation !== false) {
         echo "<tr class='tab_bg_1'><th colspan='4'>".$instantiation->getTypeName(1)."</th></tr>\n";
         $instantiation->showInstantiationForm($this, $options, $recursiveItems);
         unset($instantiation);
      }

      if (!$options['several']) {
         NetworkName::showFormForNetworkPort($this->getID());
      }

      $this->showFormButtons($options);
   }


   /**
    * @param $itemtype
   **/
   static function rawSearchOptionsToAdd($itemtype = null) {
      $tab = [];

      $tab[] = [
         'id'                 => 'network',
         'name'               => __('Networking')
      ];

      $joinparams = ['jointype' => 'itemtype_item'];

      $tab[] = [
         'id'                 => '21',
         'table'              => 'glpi_networkports',
         'field'              => 'mac',
         'name'               => __('MAC address'),
         'datatype'           => 'mac',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '87',
         'table'              => 'glpi_networkports',
         'field'              => 'instantiation_type',
         'name'               => __('Network port type'),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'networkport_instantiations',
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $networkNameJoin = ['jointype'          => 'itemtype_item',
                               'specific_itemtype' => 'NetworkPort',
                               'condition'         => 'AND NEWTABLE.`is_deleted` = 0',
                               'beforejoin'        => ['table'      => 'glpi_networkports',
                                                            'joinparams' => $joinparams]];
      NetworkName::rawSearchOptionsToAdd($tab, $networkNameJoin, $itemtype);

      $instantjoin = ['jointype'   => 'child',
                           'beforejoin' => ['table'      => 'glpi_networkports',
                                                 'joinparams' => $joinparams]];
      foreach (self::getNetworkPortInstantiations() as $instantiationType) {
         $instantiationType::getSearchOptionsToAddForInstantiation($tab, $instantjoin);
      }

      $netportjoin = [['table'      => 'glpi_networkports',
                                 'joinparams' => ['jointype' => 'itemtype_item']],
                           ['table'      => 'glpi_networkports_vlans',
                                 'joinparams' => ['jointype' => 'child']]];

      $tab[] = [
         'id'                 => '88',
         'table'              => 'glpi_vlans',
         'field'              => 'name',
         'name'               => __('VLAN'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => ['beforejoin' => $netportjoin]
      ];

      return $tab;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = $checkitem->canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $vlan_prefix                    = 'NetworkPort_Vlan'.MassiveAction::CLASS_ACTION_SEPARATOR;
         $actions[$vlan_prefix.'add']    = __('Associate a VLAN');
         $actions[$vlan_prefix.'remove'] = __('Dissociate a VLAN');
      }
      return $actions;
   }


   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'type'               => 'text',
         'massiveaction'      => false,
         'datatype'           => 'itemlink'
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'logical_number',
         'name'               => __('Port number'),
         'datatype'           => 'integer'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'mac',
         'name'               => __('MAC address'),
         'datatype'           => 'mac'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'instantiation_type',
         'name'               => __('Network port type'),
         'datatype'           => 'itemtypename',
         'itemtype_list'      => 'networkport_instantiations',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_netpoints',
         'field'              => 'name',
         'name'               => _n('Network outlet', 'Network outlets', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Type'),
         'datatype'           => 'itemtype',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'items_id',
         'name'               => __('ID'),
         'datatype'           => 'integer',
         'massiveaction'      => false
      ];

      return $tab;
   }


   /**
    * Clone the current NetworkPort when the item is clone
    *
    * @since 0.84
    *
    * @param $itemtype     the type of the item that was clone
    * @param $old_items_id the id of the item that was clone
    * @param $new_items_id the id of the item after beeing cloned
   **/
   static function cloneItem($itemtype, $old_items_id, $new_items_id) {
      global $DB;

      $np = new self();
      // ADD Ports
      foreach ($DB->request('glpi_networkports',
                            ['FIELDS' => 'id',
                                  'WHERE'  => "`items_id` = '$old_items_id'
                                                AND `itemtype` = '$itemtype'"]) as $data) {
         $np->getFromDB($data["id"]);
         $instantiation = $np->getInstantiation();
         unset($np->fields["id"]);
         $np->fields["items_id"] = $new_items_id;
         $portid                 = $np->addToDB();

         if ($instantiation !== false) {
            $input = [];
            $input["networkports_id"] = $portid;
            unset($instantiation->fields["id"]);
            unset($instantiation->fields["networkports_id"]);
            foreach ($instantiation->fields as $key => $val) {
               if (!empty($val)) {
                  $input[$key] = $val;
               }
            }
            $instantiation->add($input);
            unset($instantiation);
         }

         $npv = new NetworkPort_Vlan();
         foreach ($DB->request($npv->getTable(),
                               [$npv::$items_id_1 => $data["id"]]) as $vlan) {

            $input = [$npv::$items_id_1 => $portid,
                           $npv::$items_id_2 => $vlan['vlans_id']];
            if (isset($vlan['tagged'])) {
               $input['tagged'] = $vlan['tagged'];
            }
            $npv->add($input);
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $CFG_GLPI;

      // Can exists on template
      $nb = 0;
      if (NetworkEquipment::canView()) {
         if (in_array($item->getType(), $CFG_GLPI["networkport_types"])) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               $nb = self::countForItem($item);
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }

      if ($item->getType() == 'NetworkPort') {
         $nbAlias = countElementsInTable('glpi_networkportaliases',
                                         ['networkports_id_alias' => $item->getField('id')]);
         if ($nbAlias > 0) {
            $aliases = self::createTabEntry(NetworkPortAlias::getTypeName(Session::getPluralNumber()), $nbAlias);
         } else {
            $aliases = '';
         }
         $nbAggregates = countElementsInTable('glpi_networkportaggregates',
                                              "`networkports_id_list`
                                                   LIKE '%\"".$item->getField('id')."\"%'");
         if ($nbAggregates > 0) {
            $aggregates = self::createTabEntry(NetworkPortAggregate::getTypeName(Session::getPluralNumber()),
                                               $nbAggregates);
         } else {
            $aggregates = '';
         }
         if (!empty($aggregates) && !empty($aliases)) {
            return $aliases.'/'.$aggregates;
         }
         return $aliases.$aggregates;
      }
      return '';
   }


   /**
    * @param CommonDBTM $item
   **/
   static function countForItem(CommonDBTM $item) {

      return countElementsInTable('glpi_networkports',
                                  ['itemtype'   => $item->getType(),
                                   'items_id'   => $item->getField('id'),
                                   'is_deleted' => 0 ]);
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      global $CFG_GLPI;

      if (in_array($item->getType(), $CFG_GLPI["networkport_types"])
          || ($item->getType() == 'NetworkPort')) {
         self::showForItem($item, $withtemplate);
         return true;
      }
   }


   /**
    * @since 0.85
    *
    * @see CommonDBConnexity::getConnexityMassiveActionsSpecificities()
   **/
   static function getConnexityMassiveActionsSpecificities() {

      $specificities                           = parent::getConnexityMassiveActionsSpecificities();

      $specificities['reaffect']               = true;
      $specificities['itemtypes']              = ['Computer', 'NetworkEquipment'];

      $specificities['normalized']['unaffect'] = [];
      $specificities['action_name']['affect']  = _x('button', 'Move');

      return $specificities;
   }

}
