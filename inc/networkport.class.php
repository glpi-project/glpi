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

/// NetworkPort class : There is two parts for a given NetworkPort. The first one, generic, only
/// contains the link to the item, the name, and the type of network port. All specific
/// characteristics are owned by the instanciation of the network port : NetworkPortInstantiation.
/// Whenever a port is display (through its form or though item port listing), the NetworkPort class
/// load its instantiation from the instantiation database to display the elements.
/// Moreover, in NetworkPort form, if there is no more than one NetworkName attached to the current
/// port, then, the fields of NetworkName are display. Thus, NetworkPort UI remain similar to 0.83
/// version.
class NetworkPort extends CommonDBChild {

   // From CommonDBChild
   public $itemtype  = 'itemtype';
   public $items_id  = 'items_id';
   public $dohistory = true;


   /**
    * \brief get the list of available network port type.
    *
    * @since version 0.84
    *
    * @return array of available type of network ports
   **/
   static function getNetworkPortInstantiations() {

      return array('NetworkPortAggregate', 'NetworkPortAlias', 'NetworkPortEthernet',
                   'NetworkPortLocal', 'NetworkPortWifi');
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['networking'][6];
      }
      return $LANG['networking'][4];
   }


   function canCreate() {

      if (isset($this->fields['itemtype']) && $item=getItemForItemtype($this->fields['itemtype'])) {
         return $item->canCreate();
      }

      return false;
   }


   function canView() {

      if (isset($this->fields['itemtype'])) {
         $item = new $this->fields['itemtype']();
         return $item->canView();
      }

      return false;
   }


   /**
    * \brief get the instantiation of the current NetworkPort
    * The instantiation rely on the instantiation_type field and the id of the NetworkPort. If the
    * network port exists, but not its instantiation, then, the instantiation will be empty.
    *
    * @since version 0.84
    *
    * @return the instantiation object or false if the type of instantiation is not known
   **/
   function getInstantiation() {

      if (!empty($this->fields['instantiation_type'])) {
         $instantiation = new $this->fields['instantiation_type']();
         if (!$instantiation->getFromDB($this->getID())) {
            $instantiation->getEmpty();
         }
         return $instantiation;
      }
      return false;
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
    * @since version 0.84
    *
    * @see updateDependencies for the update
   **/
   function splitInputForElements($input) {

      if (isset($this->input_for_instantiation)
          || isset($this->input_for_NetworkName)
          || !isset($input)) {
         return;
      }

      $this->input_for_instantiation = array();
      $this->input_for_NetworkName   = array();

      foreach ($input as $field => $value) {
         if (array_key_exists($field, $this->fields)) {
            continue;
         }
         if (preg_match('/^NetworkName_/',$field)) {
            $networkName_field = preg_replace('/^NetworkName_/','',$field);
            $this->input_for_NetworkName[$networkName_field] = $value;
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
    * @since version 0.84
    *
    * @see splitInputForElements() for preparing the input
   **/
   function updateDependencies($history=1) {

      $instantiation = $this->getInstantiation();
      if (($instantiation !== false) && (count($this->input_for_instantiation) > 0)) {
         $this->input_for_instantiation['id'] = $this->getID();
         if ($instantiation->isNewID($instantiation->getID())) {
            $instantiation->add($this->input_for_instantiation, $history);
         } else {
            $instantiation->update($this->input_for_instantiation, $history);
         }
      }
      unset($this->input_for_instantiation);

      if (count($this->input_for_NetworkName) > 0) {
         $network_name = new NetworkName();
         if (isset($this->input_for_NetworkName['id'])) {
            $network_name->update($this->input_for_NetworkName, $history);
         } else {
            $this->input_for_NetworkName['itemtype'] = 'NetworkPort';
            $this->input_for_NetworkName['items_id'] = $this->getID();
            $network_name->add($this->input_for_NetworkName, $history);
         }
      }
      unset($this->input_for_NetworkName);

   }


   function prepareInputForAdd($input) {

      // TODO : should use the CommonDBChild::prepareInputForAdd facility
      // Not attached to itemtype -> not added
      if (!isset($input['itemtype'])
          || empty($input['itemtype'])
          || !($item = getItemForItemtype($input['itemtype']))
          || !isset($input['items_id'])
          || $input['items_id'] <= 0) {
         return false;
      }

      if (isset($input["logical_number"]) && strlen($input["logical_number"])==0) {
         unset($input["logical_number"]);
      }

      // TODO : should use the CommonDBChild::prepareInputForAdd facility
      if ($item->getFromDB($input['items_id'])) {
         $input['entities_id']  = $item->getEntityID();
         $input['is_recursive'] = intval($item->isRecursive());
         return $input;
      }

      // Item not found
      return false;
   }


   function pre_deleteItem() {

      $nn = new NetworkPort_NetworkPort();
      $nn->cleanDBonItemDelete ($this->getType(), $this->getID());

      return true;
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

      NetworkName::unaffectAddressesOfItem($this->getID(), $this->getType());
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


   function defineTabs($options=array()) {
      global $LANG;

      $ong = array();
      $this->addStandardTab('NetworkName', $ong, $options);
      $this->addStandardTab('NetworkPort_Vlan', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      $this->addStandardTab('NetworkPortInstantiation', $ong, $options);

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
    * Show ports for an item
    *
    * @param $item CommonDBTM object
    * @param $withtemplate integer : withtemplate param
   **/
   static function showForItem(CommonDBTM $item, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $rand = mt_rand();

      $itemtype = $item->getType();
      $items_id = $item->getField('id');

      if (!Session::haveRight('networking','r') || !$item->can($items_id, 'r')) {
         return false;
      }

      $netport = new self();
      $canedit = $item->can($items_id, 'w');

      // Show Add Form
      if ($canedit
          && (empty($withtemplate) || $withtemplate !=2)) {

         echo "\n<form method='get' action='" . $netport->getFormURL() ."'>\n";
         echo "<input type='hidden' name='items_id' value='".$item->getID()."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>\n";
         echo "<div class='firstbloc'><table class='tab_cadre_fixe'>\n";
         echo "<tr><td class='tab_bg_2 center'>\n";
         echo $LANG['Internet'][36]."&nbsp;: <select name='instantiation_type'>\n";
         foreach (self::getNetworkPortInstantiations() as $network_type) {
            echo "\t<option value='$network_type'";
            if ($network_type == "NetworkPortEthernet") {
               echo " selected";
            }
            echo ">".call_user_func(array($network_type, 'getTypeName'))."</option>\n";
         }
         echo "</select></td>\n";
         echo "<td class='tab_bg_2 center' width='50%'>";
         echo $LANG['Internet'][37] .
              "&nbsp;: <input type='checkbox' name='several' value='1'></td>\n";
         echo "</tr>";

         echo "<tr><td class='tab_bg_2 center' colspan='2'>\n";
         echo "<input type='submit' name='create' value=\"" . __s('Add') .
              "\" class='submit'>\n";
         echo "</td></tr></table></div></form>\n";
      }

      if ($canedit && $withtemplate != 2) {
         $checkbox_column = true;
         echo "\n<form id='networking_ports$rand' name='networking_ports$rand' method='post'
                  action='" . $CFG_GLPI["root_doc"] ."/front/networkport.form.php'>\n";
      } else {
         $checkbox_column = false;
      }

      $is_active_network_port = false;
      foreach (self::getNetworkPortInstantiations() as $portType) {
         Session::initNavigateListItems('NetworkPort', $item->getTypeName()." = ".$item->getName());

         $query = "SELECT `id`
                   FROM `glpi_networkports`
                   WHERE `items_id` = '$items_id'
                         AND `itemtype` = '$itemtype'
                         AND `instantiation_type` = '$portType'
                   ORDER BY `name`,
                            `logical_number`";

         if ($result = $DB->query($query)) {
            echo "<div class='spaced'>";

            $number_port = $DB->numrows($result);

            if ($number_port != 0) {
               $is_active_network_port = true;
               $colspan = 3 + call_user_func(array($portType, 'getShowForItemNumberColums'))
                            + ($checkbox_column ? 1 : 0);

               echo "<table class='tab_cadre_fixe'>\n";

               echo "<tr><th colspan='$colspan'>\n";
               echo NetworkPort::getTypeName($number_port).
                    " (".call_user_func(array($portType, 'getTypeName')).")";
               echo "&nbsp;:&nbsp;$number_port</th></tr>\n";

               echo "<tr>";
               if ($withtemplate != 2 && $canedit) {
                  echo "<th>&nbsp;</th>\n";
               }
               echo "<th>#</th>\n";
               echo "<th>" . $LANG['common'][16] . "</th>\n";
               echo "<th>" . NetworkName::getTypeName() . "</th>\n";
                     call_user_func(array($portType, 'showForItemHeader'));
                echo "</tr>\n";

               $i = 0;

               while ($devid = $DB->fetch_row($result)) {
                  $netport->getFromDB(current($devid));
                  Session::addToNavigateListItems('NetworkPort', $netport->fields["id"]);

                  echo "<tr class='tab_bg_1'>\n";
                  if ($withtemplate != 2 && $canedit) {
                     echo "<td class='center' width='20'>";
                     echo "<input type='checkbox' name='del_port[".$netport->fields["id"].
                            "]' value='1'>";
                     echo "</td>\n";
                  }
                  echo "<td class='center'><span class='b'>";
                  if ($canedit && $withtemplate != 2) {
                     echo "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/networkport.form.php?id=".
                            $netport->fields["id"] . "\">";
                  }
                  echo $netport->fields["logical_number"];
                  if ($canedit && $withtemplate != 2) {
                     echo "</a>";
                  }
                  echo "</span>";
                  Html::showToolTip($netport->fields['comment']);
                  echo "</td>\n";
                  echo "<td>" . $netport->fields["name"] . "</td>\n";
                  echo "<td>";
                  NetworkName::showForItem($netport, false, $canedit, $withtemplate);
                  echo "</td>\n";

                  $instantiation = $netport->getInstantiation();
                  if ($instantiation !== false) {
                     $instantiation->showForItem($netport, $item, $withtemplate);
                     unset($instantiation);
                  }

                  echo "</tr>\n";
               }
               echo "</table>\n";

           }
            echo "</div>";
         }
      }

      if (!$is_active_network_port) {
         echo "<table class='tab_cadre_fixe'><tr><th>".$LANG['networking'][10]."</th></tr>";
         echo "</table>";
      }

      if ($canedit && $withtemplate != 2) {
         Html::openArrowMassives("networking_ports$rand", true);
         Dropdown::showForMassiveAction('NetworkPort');
         $actions = array();
         Html::closeArrowMassives($actions);
         echo "</form>";
      }
   }


   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!isset($options['several'])) {
         $options['several'] = false;
      }

      if (!Session::haveRight("networking", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $input = array('itemtype'           => $options["itemtype"],
                        'items_id'           => $options["items_id"],
                        'instantiation_type' => $options['instantiation_type']);
         // Create item
         $this->check(-1, 'w', $input);
      }

      $recursiveItems = $this->recursivelyGetItems();
      if (count($recursiveItems) == 0) {
         return false;
      }
      $lastItem = $recursiveItems[count($recursiveItems) - 1];

      // TODO : is it usefull ?
      // Ajout des infos deja remplies
      if (isset($_POST) && !empty($_POST)) {
         foreach ($netport->fields as $key => $val) {
            if ($key!='id' && isset($_POST[$key])) {
               $netport->fields[$key] = $_POST[$key];
            }
         }
      }
      $this->showTabs();

      $options['entities_id'] = $lastItem->getField('entities_id');
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>";
      $this->displayRecursiveItems($recursiveItems, 'Type');
      echo "&nbsp;:</td>\n<td>";

      if (!($ID>0)) {
         echo "<input type='hidden' name='items_id' value='".$this->fields["items_id"]."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$this->fields["itemtype"]."'>\n";
         echo "<input type='hidden' name='instantiation_type' value='" .
                $this->fields["instantiation_type"]."'>\n";
      }

      $this->displayRecursiveItems($recursiveItems, "Link");
      echo "</td>\n";
      $colspan = 2;

      if (!$options['several']) {
         $colspan ++;
      }
      echo "<td rowspan='$colspan'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='$colspan' class='middle'>";
      echo "<textarea cols='45' rows='$colspan' name='comment' >" .
             $this->fields["comment"] . "</textarea>";
      echo "</td></tr>\n";

      if (!$options['several']) {
         echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][21] . "&nbsp;:</td>\n";
         echo "<td>";
         Html::autocompletionTextField($this,"logical_number", array('size' => 5));
         echo "</td></tr>\n";

      } else {
         echo "<tr class='tab_bg_1'><td>" . $LANG['networking'][21] . "&nbsp;:</td>\n";
         echo "<td>";
         echo "<input type='hidden' name='several' value='yes'>";
         echo "<input type='hidden' name='logical_number' value=''>\n";
         echo Toolbox::ucfirst($LANG['pager'][2]) . "&nbsp;:&nbsp;";
         Dropdown::showInteger('from_logical_number', 0, 0, 100);
         echo "&nbsp;".$LANG['networking'][48] . "&nbsp;:&nbsp;";
         Dropdown::showInteger('to_logical_number', 0, 0, 100);
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'><td>" . $LANG['common'][16] . "&nbsp;:</td>\n";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>\n";

      $instantiation = $this->getInstantiation();
      if ($instantiation !== false) {
         echo "<tr class='tab_bg_1'><th colspan='4'>" .$instantiation->getTypeName() .
              "</th></tr>\n";
         $instantiation->showForm($this, $options, $recursiveItems);
         unset($instantiation);
      }

      NetworkName::showFormForNetworkPort($this->getID());

      $this->showFormButtons($options);
      $this->addDivForTabs();
   }


   static function getSearchOptionsToAdd($itemtype) {
      global $LANG;

      $tab = array();

      $tab['network'] = $LANG['setup'][88];

      $joinparams = array('jointype' => 'itemtype_item');
      if ($itemtype=='Computer') {
         $joinparams['beforejoin'] = array('table'      => 'glpi_computers_devicenetworkcards',
                                           'joinparams' => array('jointype' => 'child',
                                                                 'nolink'   => true));
      }

      $tab[20]['table']         = 'glpi_networkports';
      $tab[20]['field']         = 'ip';
      $tab[20]['name']          = $LANG['networking'][14];
      $tab[20]['forcegroupby']  = true;
      $tab[20]['massiveaction'] = false;
      $tab[20]['joinparams']    = $joinparams;

      $tab[21]['table']         = 'glpi_networkports';
      $tab[21]['field']         = 'mac';
      $tab[21]['name']          = $LANG['networking'][15];
      $tab[21]['forcegroupby']  = true;
      $tab[21]['massiveaction'] = false;
      $tab[21]['joinparams']    = $joinparams;

      $tab[83]['table']         = 'glpi_networkports';
      $tab[83]['field']         = 'netmask';
      $tab[83]['name']          = $LANG['networking'][60];
      $tab[83]['forcegroupby']  = true;
      $tab[83]['massiveaction'] = false;
      $tab[83]['joinparams']    = $joinparams;

      $tab[84]['table']         = 'glpi_networkports';
      $tab[84]['field']         = 'subnet';
      $tab[84]['name']          = $LANG['networking'][61];
      $tab[84]['forcegroupby']  = true;
      $tab[84]['massiveaction'] = false;
      $tab[84]['joinparams']    = $joinparams;

      $tab[85]['table']         = 'glpi_networkports';
      $tab[85]['field']         = 'gateway';
      $tab[85]['name']          = $LANG['networking'][59];
      $tab[85]['forcegroupby']  = true;
      $tab[85]['massiveaction'] = false;
      $tab[85]['joinparams']    = $joinparams;

      $tab[22]['table']         = 'glpi_netpoints';
      $tab[22]['field']         = 'name';
      $tab[22]['name']          = $LANG['networking'][51];
      $tab[22]['forcegroupby']  = true;
      $tab[22]['massiveaction'] = false;
      $tab[22]['joinparams']    = array('beforejoin' => array('table'      => 'glpi_networkports',
                                                              'joinparams' => $joinparams));

      $tab[87]['table']         = 'glpi_networkinterfaces';
      $tab[87]['field']         = 'name';
      $tab[87]['name']          = $LANG['common'][65];
      $tab[87]['forcegroupby']  = true;
      $tab[87]['massiveaction'] = false;
      $tab[87]['joinparams']    = array('beforejoin' => array('table'      => 'glpi_networkports',
                                                              'joinparams' => $joinparams));

      $netportjoin = array(array('table'      => 'glpi_networkports',
                                 'joinparams' => array('jointype' => 'itemtype_item')),
                           array('table'      => 'glpi_networkports_vlans',
                                 'joinparams' => array('jointype' => 'child')));

      $tab[88]['table']         = 'glpi_vlans';
      $tab[88]['field']         = 'name';
      $tab[88]['name']          = $LANG['networking'][56];
      $tab[88]['forcegroupby']  = true;
      $tab[88]['massiveaction'] = false;
      $tab[88]['joinparams']    = array('beforejoin' => $netportjoin);


      return $tab;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['name']          = $LANG["common"][16];
      $tab[1]['type']          = 'text';
      $tab[1]['massiveaction'] = false;

      $tab[2]['table']         = $this->getTable();
      $tab[2]['field']         = 'id';
      $tab[2]['name']          = $LANG['common'][2];
      $tab[2]['massiveaction'] = false;

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'logical_number';
      $tab[3]['name']     = $LANG["networking"][21];
      $tab[3]['datatype'] = 'integer';

      $tab[4]['table']    = $this->getTable();
      $tab[4]['field']    = 'mac';
      $tab[4]['name']     = $LANG["device_iface"][2];
      $tab[4]['datatype'] = 'mac';

      $tab[5]['table']    = $this->getTable();
      $tab[5]['field']    = 'ip';
      $tab[5]['name']     = $LANG["networking"][14];
      $tab[5]['datatype'] = 'ip';

      $tab[6]['table']    = $this->getTable();
      $tab[6]['field']    = 'netmask';
      $tab[6]['name']     = $LANG["networking"][60];
      $tab[6]['datatype'] = 'ip';

      $tab[7]['table']    = $this->getTable();
      $tab[7]['field']    = 'subnet';
      $tab[7]['name']     = $LANG["networking"][61];
      $tab[7]['datatype'] = 'ip';

      $tab[8]['table'] = $this->getTable();
      $tab[8]['field'] = 'gateway';
      $tab[8]['name']  = $LANG["networking"][59];
      $tab[8]['datatype'] = 'ip';

      $tab[9]['table'] = 'glpi_netpoints';
      $tab[9]['field'] = 'name';
      $tab[9]['name']  = $LANG["networking"][51];

      $tab[10]['table'] = 'glpi_networkinterfaces';
      $tab[10]['field'] = 'name';
      $tab[10]['name']  = $LANG['setup'][9];

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      $tab[20]['table']        = $this->getTable();
      $tab[20]['field']        = 'itemtype';
      $tab[20]['name']         = $LANG['common'][17];
      $tab[20]['datatype']     = 'itemtype';
      $tab[20]['massiveation'] = false;

      $tab[21]['table']        = $this->getTable();
      $tab[21]['field']        = 'items_id';
      $tab[21]['name']         = 'id';
      $tab[21]['datatype']     = 'integer';
      $tab[21]['massiveation'] = false;

      return $tab;
   }


   /**
    * Clone the current NetworkPort when the item is clone
    *
    * @since version 0.84
    *
    * @param $itemtype the type of the item that was clone
    * @param $old_items_id the id of the item that was clone
    * @param $new_items_id the id of the item after beeing cloned
   **/
   static function cloneItem($itemtype, $old_items_id, $new_items_id) {

      $np = new NetworkPort();
      // ADD Ports
      $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = '".$old_items_id."'
                      AND `itemtype` = '".$itemtype."';";

      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {

         while ($data=$DB->fetch_array($result)) {

            $np->getFromDB($data["id"]);
            unset($np->fields["id"]);
            $np->fields["items_id"] = $new_items_id;
            $portid                 = $np->addToDB();

            $instantiation = $np->getInstantiation();
            if ($instantiation !== false) {
               $instantiation->add(array('id' => $portid));
               unset($instantiation);
            }

            $npv = new NetworkPort_Vlan();
            foreach ($DB->request($npv->getTable(),
                                  array($npv->items_id_1 => $data["id"])) as $vlan) {

               $input = array($npv->items_id_1 => $portid,
                              $npv->items_id_2 => $vlan['vlans_id']);
               if (isset($vlan['tagged'])) {
                  $input['tagged'] = $vlan['tagged'];
               }
               $npv->add($input);
            }
         }
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG, $CFG_GLPI;

      // Can exists on template
      if (Session::haveRight('networking','r')) {
         if (in_array($item->getType(), $CFG_GLPI["networkport_types"])) {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry($LANG['networking'][6], self::countForItem($item));
            }
            return $LANG['networking'][6];
         }
      }
      return '';
   }


   static function countForitem(CommonDBTM $item) {

      return countElementsInTable('glpi_networkports',
                                  "`itemtype` = '".$item->getType()."'
                                   AND `items_id` ='".$item->getField('id')."'");
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $CFG_GLPI;

      if (in_array($item->getType(), $CFG_GLPI["networkport_types"])) {
         self::showForItem($item);
         return true;
      }
   }

}

?>
