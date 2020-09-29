<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
   use Glpi\Features\Clonable {
      post_clone as protected post_cloneTrait;
   }

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
    * @return NetworkPortInstantiation|false  the instantiation object or false if the type of instantiation is not known
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
    * @param string $new_instantiation_type  the name of the new instaniation type
    *
    * @return boolean false on error, true if the previous instantiation is not available
    *                 (ie.: invalid instantiation type) or the object of the previous instantiation.
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
      $this->updateMetrics();
   }

   function post_clone($source, $history) {
      $this->post_cloneTrait($source, $history);
      $instantiation = $source->getInstantiation();
      if ($instantiation !== false) {
         $instantiation->fields[$instantiation->getIndexName()] = $this->getID();
         return $instantiation->clone([], $history);
      }
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
    * @see self::updateDependencies() for the update
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
               $network_name->add($this->input_for_NetworkName, [], $history);
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


   public function updateMetrics() {
      $metrics = new NetworkPortMetrics();
      $metrics->add([
         'networkports_id' => $this->fields['id'],
         'ifinbytes'       => $this->fields['ifinbytes'] ?? 0,
         'ifoutbytes'      => $this->fields['ifoutbytes'] ?? 0,
         'ifinerrors'      => $this->fields['ifinerrors'] ?? 0,
         'ifouterrors'     => $this->fields['ifouterrors'] ?? 0,
      ], [], false);
   }


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

   function post_addItem() {
      $this->updateDependencies(!$this->input['_no_history']);
      $this->updateMetrics();
   }

   function cleanDBonPurge() {

      $instantiation = $this->getInstantiation();
      if ($instantiation !== false) {
         $instantiation->cleanDBonItemDelete ($this->getType(), $this->getID());
         unset($instantiation);
      }

      $this->deleteChildrenAndRelationsFromDb(
         [
            NetworkName::class,
            NetworkPort_NetworkPort::class,
            NetworkPort_Vlan::class,
         ]
      );
   }


   /**
    * Get port opposite port ID if linked item
    *
    * @param integer $ID  networking port ID
    *
    * @return integer|false  ID of the NetworkPort found, false if not found
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
      $this->addStandardTab('NetworkPortMetrics', $ong, $options);
      $this->addStandardTab('NetworkName', $ong, $options);
      $this->addStandardTab('NetworkPort_Vlan', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      $this->addStandardTab('NetworkPortConnectionLog', $ong, $options);
      $this->addStandardTab('NetworkPortInstantiation', $ong, $options);
      $this->addStandardTab('NetworkPort', $ong, $options);

      return $ong;
   }


   /**
    * Delete All connection of the given network port
    *
    * @param integer $ID ID of the port
    *
    * @return boolean true on success
   **/
   function resetConnections($ID) {
   }


   /**
    * Get available display options array
    *
    * @since 0.84
    *
    * @return array  all the options
   **/
   static function getAvailableDisplayOptions() {

      $options = [];
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

      $itemtype = $item->getType();
      $items_id = $item->getField('id');

      if (!NetworkEquipment::canView()
          || !$item->can($items_id, READ)) {
         return false;
      }

      $netport = new self();
      $netport->item = $item;

      if (($itemtype == 'NetworkPort') || ($withtemplate == 2)) {
         $canedit = false;
      } else {
         $canedit = $item->canEdit($items_id);
      }

      $aggegate_iterator = $DB->request([
         'FROM'   => $netport->getTable(),
         'WHERE'  => [
            'itemtype'  => $item->getType(),
            'items_id'  => $item->getID()
         ],
         'ORDER'  => 'logical_number'
      ]);

      $aggregated_ports = [];
      while ($row = $aggegate_iterator->next()) {
         $port_iterator = $DB->request([
            'FROM'   => 'glpi_networkportaggregates',
            'WHERE'  => ['networkports_id' => $row['id']],
            'LIMIT'  => 1
         ]);

         while ($prow = $port_iterator->next()) {
            $aggregated_ports = array_merge(
               $aggregated_ports,
               importArrayFromDB($prow['networkports_id_list'])
            );
         }
      }

      $criteria = [
         'FROM'   => $netport->getTable(),
         'WHERE'  => [
            'items_id'  => $item->getID(),
            'itemtype'  => $item->getType(), [
               'OR' => [
                  ['name' => ['!=', __('Management')]],
                  ['name' => null]
               ]
            ]
         ]
      ];

      $ports_iterator = $DB->request($criteria);

      $so = $netport->rawSearchOptions();
      $dprefs = DisplayPreference::getForTypeUser(
         'Networkport',
         Session::getLoginUserID()
      );
      //hardcode add name column
      array_unshift($dprefs, 1);
      $colspan = count($dprefs);

      $showmassiveactions = false;
      if ($withtemplate != 2) {
         $showmassiveactions = $canedit;
         ++$colspan;
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

      $search_config_top    = '';
      if (Session::haveRightsOr('search_config', [
         DisplayPreference::PERSONAL,
         DisplayPreference::GENERAL
      ])) {
         $options_link = "<span class='fa fa-wrench pointer' title='".
            __s('Select default items to show')."' onClick=\"$('#%id').dialog('open');\">
            <span class='sr-only'>" .  __s('Select default items to show') . "</span></span>";

         $search_config_top .= str_replace('%id', 'search_config_top', $options_link);

         $pref_url = $CFG_GLPI["root_doc"]."/front/displaypreference.form.php?itemtype=".
                     self::getType();
         $search_config_top .= Ajax::createIframeModalWindow(
            'search_config_top',
            $pref_url,
            [
               'title'         => __('Select default items to show'),
               'reloadonclose' => true,
               'display'       => false
            ]
         );
      }

      $rand = mt_rand();
      if ($showmassiveactions) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
      }

      Session::initNavigateListItems(
         'NetworkPort',
         //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
         sprintf(
            __('%1$s = %2$s'),
            $item->getTypeName(1),
            $item->getName()
         )
      );

      if ($showmassiveactions) {
         $massiveactionparams = [
            'num_displayed'  => min($_SESSION['glpilist_limit'], count($ports_iterator)),
            'check_itemtype' => $itemtype,
            'container'      => 'mass'.__CLASS__.$rand,
            'check_items_id' => $items_id
         ];
         Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";

      echo "<thead><tr><td colspan='$colspan'>";
      echo "<table class='legend'>";
      echo "<thead><tr><th colspan='4'>".__('Connections legend')."</th></tr></thead><tr>";
      echo "<td class='netport trunk'>".__('Equipment in trunk or tagged mode')."</td>";
      echo "<td class='netport hub'>".__('Hub ')."</td>";
      echo "<td class='netport cotrunk'>".__('Other equipments')."</td>";
      echo "<td class='netport aggregated'>".__('Aggregated port')."</td>";
      echo "</tr></table>";
      echo "</td></tr>";

      echo "<tr><th colspan='$colspan'>";
      echo sprintf(
         __('%s %s'),
         count($ports_iterator),
         NetworkPort::getTypeName(count($ports_iterator))
      );
      echo ' ' . $search_config_top;
      echo "</td></tr></thead>";

      //display table headers
      echo "<tr>";
      if ($canedit) {
         echo "<td>" . Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand, '__RAND__') . "</td>";
      }
      foreach ($dprefs as $dpref) {
         echo "<th>";
         foreach ($so as $option) {
            if ($option['id'] == $dpref) {
               echo $option['name'];
               continue;
            }
         }
         echo "</th>";
      }
      echo "</tr>";

      //display row contents
      if (!count($ports_iterator)) {
         echo "<tr><th colspan='$colspan'>".__('No network port found')."</th></tr>";
      }
      while ($row = $ports_iterator->next()) {
         echo $netport->showPort(
            $row,
            $dprefs,
            $so,
            $canedit,
            (count($aggregated_ports) && in_array($row['id'], $aggregated_ports)),
            $rand
         );
      }

      echo "</table>";

      if ($showmassiveactions) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }

      //management ports
      $criteria = [
         'FROM'   => $netport->getTable(),
         'WHERE'  => [
            'items_id'  => $item->getID(),
            'itemtype'  => $item->getType(),
            'name'      => __('Management')
         ]
      ];

      $mports_iterator = $DB->request($criteria);

      if (count($mports_iterator)) {
         echo "<hr/>";
         echo "<table class='tab_cadre_fixehov'>";

         //hradcode display preferences form management port
         $dprefs = [
            1, //name
            4, //mac
            127, //network names
            126 // IPs
         ];

         echo "<thead><tr><th colspan='".count($dprefs)."'>";
         echo sprintf(
            __('%s %s'),
            count($mports_iterator),
            _n('Management port', 'Management ports', count($mports_iterator))
         );
         echo "</th></tr></thead>";

         echo "<tr>";
         //display table headers
         foreach ($dprefs as $dpref) {
            echo "<th>";
            foreach ($so as $option) {
               if ($option['id'] == $dpref) {
                  echo $option['name'];
                  continue;
               }
            }
            echo "</th>";
         }
         echo "</tr>";

         //display row contents
         while ($row = $mports_iterator->next()) {
            echo $netport->showPort(
               $row,
               $dprefs,
               $so,
               $canedit,
               (count($aggregated_ports) && in_array($row['id'], $aggregated_ports)),
               $rand,
               false
            );
         }

         echo "</table>";
      }
   }

   /**
    * Display port row
    *
    * @param array $port    Port entry in db
    * @param array $dprefs  Display preferences
    * @param array $so      Search options
    * @param bool  $canedit Can edit ACL
    * @param bool  $agg     Is an aggregated port
    * @param int   $rand    Random value
    * @param bool  $with_ma Flag massive actions
    *
    * @return string
    */
   protected function showPort(array $port, $dprefs, $so, $canedit, $agg, $rand, $with_ma = true) {
      global $DB;

      $css_class = 'netport';
      if ($port['ifstatus'] == 1) {
         if ($port['trunk'] == 1) {
            $css_class .= ' trunk'; // port_trunk.png
         } else if ($this->isHubConnected($port['id'])) {
            $css_class .= ' hub'; //multiple_mac_addresses.png
         } else {
            $css_class .= ' cotrunk'; //connected_trunk.png
         }
      }

      $whole_output = "<tr class='$css_class'>";
      if ($canedit && $with_ma) {
         $whole_output .= "<td>" . Html::getMassiveActionCheckBox(__CLASS__, $port['id']) . "</td>";
      }
      foreach ($dprefs as $dpref) {
         $output = '';
         $td_class = '';
         foreach ($so as $option) {
            if ($option['id'] == $dpref) {
               switch ($dpref) {
                  case 1:
                     if ($agg === true) {
                        $td_class = 'aggregated';
                     }

                     $name = $port['name'];
                     $url = NetworkPort::getFormURLWithID($port['id']);
                     if ($_SESSION["glpiis_ids_visible"] || empty($name)) {
                        $name = sprintf(__('%1$s (%2$s)'), $name, $port['id']);
                     }

                     $output .= "<a href='$url'>$name</a>";
                     break;
                  case 31:
                     $speed = $port[$option['field']];
                     //TRANS: list of unit (bps for bytes per second)
                     $bytes = [__('bps'), __('Kbps'), __('Mbps'), __('Gbps'), __('Tbps')];
                     foreach ($bytes as $val) {
                        if ($speed >= 1000) {
                           $speed = $speed / 1000;
                        } else {
                           break;
                        }
                     }
                     //TRANS: %1$s is a number maybe float or string and %2$s the unit
                     $output .= sprintf(__('%1$s %2$s'), round($speed, 2), $val);
                     break;
                  case 32:
                     $state_class = '';
                     $state_title = __('Unknown');
                     switch ($port[$option['field']]) {
                        case 1: //up
                           $state_class = 'green';
                           $state_title = __('Up');
                           break;
                        case 2: //down
                           $state_class = 'red';
                           $state_title = __('Down');
                           break;
                        case 3: //testing
                           $state_class = 'orange';
                           $state_title = __('Test');
                           break;
                     }
                     $output .= sprintf(
                        "<i class='fas fa-circle %s' title='%s'></i> <span class='sr-only'>%s</span>",
                        $state_class,
                        $state_title,
                        $state_title
                     );
                     break;
                  case 34:
                     $in = $port[$option['field']];
                     $out = $port['ifoutbytes'];

                     if (empty($in) && empty($out)) {
                        break;
                     }

                     if (!empty($in)) {
                        $in = Toolbox::getSize($in, 1000);
                     } else {
                        $in = ' - ';
                     }

                     if (!empty($out)) {
                        $out = Toolbox::getSize($out, 1000);
                     } else {
                        $out = ' - ';
                     }

                     $output .= sprintf('%s / %s', $in, $out);
                     break;
                  case 35:
                     $in = $port[$option['field']];
                     $out = $port['ifouterrors'];

                     if ($in == 0 && $out == 0) {
                        break;
                     }

                     if ($in > 0 || $out > 0) {
                        $td_class = 'orange';
                     }

                     $output .= sprintf('%s / %s', $in, $out);
                     break;
                  case 36:
                     switch ($port[$option['field']]) {
                        case 2: //half
                           $td_class = 'orange';
                           $output .= __('Half');
                           break;
                        case 3: //full
                           $output .= __('Full');
                           break;
                     }
                     break;
                  case 38:
                     $vlans = $DB->request([
                        'SELECT' => [
                           NetworkPort_Vlan::getTable() . '.id',
                           Vlan::getTable() . '.name',
                           NetworkPort_Vlan::getTable() . '.tagged',
                           Vlan::getTable() . '.tag',
                        ],
                        'FROM'   => NetworkPort_Vlan::getTable(),
                        'INNER JOIN'   => [
                           Vlan::getTable() => [
                              'ON' => [
                                 NetworkPort_Vlan::getTable()  => 'vlans_id',
                                 Vlan::getTable()              => 'id'
                              ]
                           ]
                        ],
                        'WHERE'  => ['networkports_id' => $port['id']]
                     ]);

                     if (count($vlans) > 10) {
                        $output .= sprintf(
                           __('%s linked VLANs'),
                           count($vlans)
                        );
                     } else {
                        while ($row = $vlans->next()) {
                           $output .= $row['name'];
                           if (!empty($row['tag'])) {
                              $output .= ' [' . $row['tag'] . ']';
                           }
                           $output .= ($row['tagged'] == 1 ? 'T' : 'U');
                           if ($canedit) {
                              $output .= "<a title='". __('Delete') . "' href='" . NetworkPort::getFormURLWithID($row['id']) . "&unassign_vlan=unassigned'> <i class='fas fa-trash'></i> <span class='sr-only'>" . __('Delete') . "</span></a>";
                           }
                           $output .= '<br/>';
                        }
                     }
                     break;
                  case 39:
                     $netport = new NetworkPort();
                     $netport->getFromDB($port['id']);

                     $device1 = $netport->getItem();

                     if (!$device1->can($device1->getID(), READ)) {
                        break;
                     }

                     $relations_id = 0;
                     $oppositePort = NetworkPort_NetworkPort::getOpposite($netport, $relations_id);

                     if ($oppositePort !== false) {
                        $device2 = $oppositePort->getItem();
                        $output .= $this->getUnmanagedLink($device2, $oppositePort);

                        //equipments connected to hubs
                        if ($device2->getType() == Unmanaged::getType() && $device2->fields['hub'] == 1) {
                           $houtput = "<div class='hub'>";

                           $hub_ports = $DB->request([
                              'FROM'   => NetworkPort::getTable(),
                              'WHERE'  => [
                                 'itemtype'  => $device2->getType(),
                                 'items_id'  => $device2->getID()
                              ]
                           ]);

                           $list_ports = [];
                           while ($hrow = $hub_ports->next()) {
                              $npo = NetworkPort::getContact($hrow['id']);
                              $list_ports[] = $npo;
                           }

                           $hub_equipments = $DB->request([
                              'SELECT' => ['unm.*', 'netp.mac'],
                              'FROM'   => Unmanaged::getTable() . ' AS unm',
                              'INNER JOIN'   => [
                                 NetworkPort::getTable() . ' AS netp' => [
                                    'ON' => [
                                       'netp'   => 'items_id',
                                       'unm'    => 'id', [
                                          'AND' => [
                                             'netp.itemtype' => $device2->getType()
                                          ]
                                       ]
                                    ]
                                 ]
                              ],
                              'WHERE'  => [
                                 'netp.itemtype'  => $device2->getType(),
                                 'netp.id'  => $list_ports
                              ]
                           ]);

                           if (count($hub_equipments) > 10) {
                              $houtput .= '<div>' . sprintf(
                                 __('%s equipments connected to the hub'),
                                 count($hub_equipments)
                              ) . '</div>';
                           } else {
                              while ($hrow = $hub_equipments->next()) {
                                 $hub = new Unmanaged();
                                 $hub->getFromDB($hrow['id']);
                                 $hub->fields['mac'] = $hrow['mac'];
                                 $houtput .= '<div>' . $this->getUnmanagedLink($hub, $hub) . '</div>';
                              }
                           }

                           $houtput .= "</div>";
                           $output .= $houtput;
                        }
                     }
                     break;
                  case 40:
                     $co_class = '';
                     if (empty($port['ifstatus'])) {
                        break;
                     }
                     switch ($port['ifstatus']) {
                        case 1: //up
                           $co_class = 'fa-link netport green';
                           $title = __('Connected');
                           break;
                        case 2: //down
                           $co_class = 'fa-unlink netport red';
                           $title = __('Not connected');
                           break;
                        case 3: //testing
                           $co_class = 'fa-link netport orange';
                           $title = __('Testing');
                           break;
                        case 4: //unknown
                           $co_class = 'fa-question-circle';
                           $title = __('Unknown');
                           break;
                        case 5: //dormant
                           $co_class = 'fa-link netport grey';
                           $title = __('Dormant');
                           break;
                     }
                     $output .= "<i class='fas $co_class' title='$title'></i> <span class='sr-only'>$title</span>";
                     break;
                  case 41:
                     if ($port['ifstatus'] == 1) {
                        $output .= sprintf("<i class='fa fa-circle green' title='%s'></i>", __s('Connected'));
                     } else if (!empty($port['lastup'])) {
                        $time = strtotime(date('Y-m-d H:i:s')) - strtotime($port['lastup']);
                        $output .= Html::timestampToString($time, false);
                     }
                     break;
                  case 126: //IP address
                     $ips_iterator = $this->getIpsForPort('NetworkPort', $port['id']);
                     while ($iprow = $ips_iterator->next()) {
                        $output .= '<br/>' . $iprow['name'];
                     }
                     break;
                  case 127:
                     $names_iterator = $DB->request([
                        'FROM'   => 'glpi_networknames',
                        'WHERE'  => [
                           'itemtype'  => 'NetworkPort',
                           'items_id'  => $port['id']
                        ]
                     ]);
                     while ($namerow = $names_iterator->next()) {
                        $netname = new NetworkName();
                        $netname->getFromDB($namerow['id']);
                        $output .= '<br/>' . $netname->getLink(1);
                     }
                     break;
                  default:
                     $output .= $port[$option['field']];
                     break;
               }
               continue;
            }
         }
         $whole_output .= "<td class='$td_class'>" . $output . "</td>";
      }
      $whole_output .= '</tr>';
      return $whole_output;
   }

   protected function getIpsForPort($itemtype, $items_id) {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'ipa.*',
         'FROM'   => IPAddress::getTable() . ' AS ipa',
         'INNER JOIN'   => [
            NetworkName::getTable() . ' AS netname' => [
               'ON' => [
                  'ipa' => 'items_id',
                  'netname' => 'id', [
                     'AND' => [
                        'ipa.itemtype' => 'NetworkName'
                     ]
                  ]
               ]
            ]
         ],
         'WHERE'  => [
            'netname.items_id'   => $items_id,
            'netname.itemtype'   => $itemtype
         ]
      ]);
      return $iterator;
   }

   protected function getUnmanagedLink($device, $port) {
      $device_link = $device->getLink(1);

      $link_replaces = $device->getName(0);
      if (!empty($port->fields['mac'])) {
         $link_replaces .= '<br/>' . $port->fields['mac'];
      }

      $ips_iterator = $this->getIpsForPort($port->getType(), $port->getID());

      $ips = '';
      while ($ipa = $ips_iterator->next()) {
         $ips .= ' ' . $ipa['name'];
      }
      if (!empty($ips)) {
         $link_replaces .= '<br/>' . $ips;
      }

      $device_link = str_replace(
         $device->getName(0),
         $link_replaces,
         $device->getLink(1)
      );

      $icon = sprintf(
         "<i class='%s'></i> ",
         $device->getIcon()
      );

      return $icon . $device_link;
   }

   function showForm($ID, $options = []) {
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
         echo "<tr class='tab_bg_1'><td>". _n('Port number', 'Port numbers', 1) ."</td>\n";
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
                               'condition'         => ['NEWTABLE.is_deleted' => 0],
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


   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = $checkitem !== null && $checkitem->canUpdate();
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
         'datatype'           => 'itemlink',
         'autocomplete'       => true,
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
         'name'               => _n('Port number', 'Port numbers', 1),
         'datatype'           => 'integer',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'mac',
         'name'               => __('MAC address'),
         'datatype'           => 'mac',
         'autocomplete'       => true,
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

      if ($this->isField('netpoints_id')) {
         $tab[] = [
            'id'                 => '9',
            'table'              => 'glpi_netpoints',
            'field'              => 'name',
            'name'               => Netpoint::getTypeName(1),
            'datatype'           => 'dropdown'
         ];
      }

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
         'name'               => _n('Type', 'Types', 1),
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

      $tab[] = [
         'id'    => '30',
         'table' => $this->getTable(),
         'field' => 'ifmtu',
         'name'  => __('MTU'),
      ];

      $tab[] = [
         'id'    => '31',
         'table' => $this->getTable(),
         'field' => 'ifspeed',
         'name'  => __('Speed'),
      ];

      $tab[] = [
         'id'    => '32',
         'table' => $this->getTable(),
         'field' => 'ifinternalstatus',
         'name'  => __('Internal status'),
      ];

      $tab[] = [
         'id'    => '33',
         'table' => $this->getTable(),
         'field' => 'iflastchange',
         'name'  => __('Last change'),
      ];

      $tab[] = [
         'id'    => '34',
         'table' => $this->getTable(),
         'field' => 'ifinbytes',
         'name'  => __('Number of I/O bytes'),
      ];

      $tab[] = [
         'id'    => '35',
         'table' => $this->getTable(),
         'field' => 'ifinerrors',
         'name'  => __('Number of I/O errors'),
      ];

      $tab[] = [
         'id'    => '36',
         'table' => $this->getTable(),
         'field' => 'portduplex',
         'name'  => __('Duplex'),
      ];

      $netportjoin = [
         [
            'table'      => 'glpi_networkports',
            'joinparams' => ['jointype' => 'itemtype_item']
         ], [
            'table'      => 'glpi_networkports_vlans',
            'joinparams' => ['jointype' => 'child']
         ]
      ];

      $tab[] = [
         'id' => '38',
         'table' => Vlan::getTable(),
         'field' => 'name',
         'name' => Vlan::getTypeName(1),
         'datatype' => 'dropdown',
         'forcegroupby' => true,
         'massiveaction' => false,
         'joinparams' => ['beforejoin' => $netportjoin]
      ];

      if (!defined('TU_USER')) {
         $tab[] = [
            'id'    => '39',
            'table' => $this->getTable(),
            'field' => 'noone',
            'name' => __('Connected to'),
            'nosearch' => true,
            'nodisplay' => true,
            'massiveaction' => false
         ];
      }

      $tab[] = [
         'id'    => '40',
         'table' => $this->getTable(),
         'field' => 'ifconnectionstatus',
         'name'  => __('Connection'),
      ];

      $tab[] = [
         'id'    => '41',
         'table' => $this->getTable(),
         'field' => 'lastup',
         'name'  => __('Last connection'),
      ];

      $tab[] = [
         'id'    => '42',
         'table' => $this->getTable(),
         'field' => 'ifalias',
         'name'  => __('Alias')
      ];

      $joinparams = ['jointype' => 'itemtype_item'];
      $networkNameJoin = ['jointype'          => 'itemtype_item',
                               'specific_itemtype' => 'NetworkPort',
                               'condition'         => ['NEWTABLE.is_deleted' => 0],
                               'beforejoin'        => ['table'      => 'glpi_networkports',
                                                            'joinparams' => $joinparams]];
      NetworkName::rawSearchOptionsToAdd($tab, $networkNameJoin);

      return $tab;
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
         $nbAggregates = countElementsInTable(
            'glpi_networkportaggregates',
            ['networkports_id_list'   => ['LIKE', '%"'.$item->getField('id').'"%']]
         );
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

   public function computeFriendlyName() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['name'],
         'FROM'   => $this->fields['itemtype']::getTable(),
         'WHERE'  => ['id' => $this->fields['items_id']]
      ]);

      if ($iterator->count()) {
         return sprintf(__('%1$s on %2$s'), parent::computeFriendlyName(), $iterator->next()['name']);
      }

      return parent::computeFriendlyName();
   }

   /**
    * Is port connected to a hub?
    *
    * @param integer $networkports_id Port ID
    *
    * @return boolean
    */
   public function isHubConnected($networkports_id): bool {
      global $DB;

      $wired = new NetworkPort_NetworkPort();
      $opposite = $wired->getOppositeContact($networkports_id);

      if (empty($opposite)) {
         return false;
      }

      $result = $DB->request([
         'FROM'         => Unmanaged::getTable(),
         'COUNT'        => 'cpt',
         'INNER JOIN'   => [
            $this->getTable() => [
               'ON' => [
                  $this->getTable()       => 'items_id',
                  Unmanaged::getTable()   => 'id', [
                     'AND' => [
                        $this->getTable() . '.itemtype' => Unmanaged::getType()
                     ]
                  ]
               ]
            ]
         ],
         'WHERE'        => [
            'hub' => 1,
            $this->getTable() . '.id' => $opposite
         ]
      ])->next();

      return ($result['cpt'] > 0);
   }

   public function getNonLoggedFields(): array {
      return [
         'ifinbytes',
         'ifoutbytes',
         'ifinerrors',
         'ifouterrors'
      ];
   }
}
