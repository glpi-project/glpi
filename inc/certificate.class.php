<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Class to declare a certificate
 */
class Certificate extends CommonDBTM {

   public $dohistory           = true;
   static $rightname           = "certificate";
   protected $usenotepad       = true;

   /**
    * @param int $nb
    * @return string|translated
    */
   static function getTypeName($nb = 0) {
      return _n('Certificate', 'Certificates', $nb);
   }

   /**
    * Clean certificate items
    */
   function cleanDBonPurge() {
      $cert_item = new Certificate_Item();
      $cert_item->deleteByCriteria(['certificates_id' => $this->fields['id']]);
   }

   function getSearchOptionsNew() {
      global $CFG_GLPI;

      $tab = [];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false // implicit key==1
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false, // implicit field is id
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string'
      ];

      $tab = array_merge($tab, Location::getSearchOptionsToAddNew());

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_certificatetypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'dns_suffix',
         'name'               => __('DNS suffix'),
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'is_autosign',
         'name'               => __('Self-signed'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'date_expiration',
         'name'               => __('Expiration date'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'command',
         'name'               => __('Command used'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'certificate_request',
         'name'               => __('Certificate request (CSR)'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'certificate_item',
         'name'               => self::getTypeName(1),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => 'glpi_certificates_items',
         'field'              => 'items_id',
         'name'               => _n('Associated item', 'Associated items', 2),
         'nosearch'           => true,
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'joinparams'         => ['jointype' => 'child']
      ];

      $tab[] = [
         'id'                 => '15',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => __('Manufacturer'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => '`is_visible_certificate`'
      ];


      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => '`is_assign`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => __('User'),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '71',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => __('Group'),
         'condition'          => '`is_itemgroup`',
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '72',
         'table'              => 'glpi_certificates_items',
         'field'              => 'id',
         'name'               => _x('quantity', 'Number of associated items'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'count',
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::getSearchOptionsToAddNew(get_class($this)));
      $tab = array_merge($tab, Notepad::getSearchOptionsToAddNew());

      return $tab;
   }

   /**
    * @param array $options
    * @return array
    */
   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab(__CLASS__, $ong, $options)
         ->addStandardTab('Certificate_Item', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Link', $ong, $options)
         ->addStandardTab('Lock', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }

   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {

         // ADD Infocoms
         Infocom::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Contract
         Contract_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         // ADD Documents
         Document_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);

         //Add KB links
         KnowbaseItem_Item::cloneItem($this->getType(), $this->input["_oldID"], $this->fields['id']);
      }
   }

   /**
    * @param $ID
    * @param array $options
    * @return bool
    */
    function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      //TRANS: %1$s is a string, %2$s a second one without spaces between them : to change for RTL
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'),
                          (isset($options['withtemplate'])
                             && $options['withtemplate']?"*":"")).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate'])
                                && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', array('value' => $objectName));
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      State::dropdown(array('value'     => $this->fields["states_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => "`is_visible_certificate`"));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Location')."</td>";
      echo "<td>";
      Location::dropdown(array('value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      Dropdown::show('CertificateType',
                     ['name'   => "certificatetypes_id",
                      'value'  => $this->fields["certificatetypes_id"],
                      'entity' => $this->fields["entities_id"]
                     ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the hardware')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Manufacturer')." (" . __('Root CA') . ")";
      echo "<td>";
      Manufacturer::dropdown(array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";


      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'serial');
      echo "<td>".sprintf(__('%1$s%2$s'), __('Inventory number'),
                          (isset($options['withtemplate']) && $options['withtemplate']?"*":"")).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'otherserial', array('value' => $objectName));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the hardware')."</td>";
      echo "<td>";
      Group::dropdown(array('name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`'));

      echo "</td><td colspan='2'></td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>";
      echo "<td>".__('Group')."</td>";
      echo "<td>";
      Group::dropdown(array('value'     => $this->fields["groups_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => '`is_itemgroup`'));

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Self-signed') . "</td>";
      echo "<td>";
      Dropdown::showYesNo('is_autosign', $this->fields["is_autosign"]);
      echo "<td rowspan='4'>".__('Comments')."</td>";
      echo "<td rowspan='4' class='middle'>";
      echo "<textarea cols='45' rows='4' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('DNS name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "dns_name");
      echo "</td></tr>\n";


      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('DNS suffix') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "dns_suffix");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Expiration date');
      echo "&nbsp;";
      Html::showToolTip(nl2br(__('Empty for infinite')));
      echo "&nbsp;</td>";
      echo "<td>";
      Html::showDateField('date_expiration', ['value' => $this->fields["date_expiration"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Command used') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='100%' rows='4' name='command' >";
      echo $this->fields["command"] . "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      echo __('Certificate request (CSR)') . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='100%' rows='4' name='certificate_request' >";
      echo $this->fields["certificate_request"] . "</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". self::getTypeName(1) . "</td>";
      echo "<td colspan='3'>";
      echo "<textarea cols='100%' rows='4' name='certificate_item' >";
      echo $this->fields["certificate_item"] . "</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSpecificMassiveActions()
    * @param null $checkitem
    * @return an
    */
   function getSpecificMassiveActions($checkitem = NULL) {
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
         if (self::canUpdate()) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'install']
               = _x('button', 'Associate certificate');
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'uninstall']
               = _x('button', 'Dissociate certificate');
         }
      }
      return $actions;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
    * @param MassiveAction $ma
    * @return bool|false
    */
   static function showMassiveActionsSubForm(MassiveAction $ma) {

      switch ($ma->getAction()) {
         case __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'install' :
            Dropdown::showSelectItemFromItemtypes(['items_id_name' => 'item_item',
                                                   'itemtype_name' => 'typeitem',
                                                   'itemtypes'     => self::getTypes(true),
                                                    'checkright'   => true
                                                  ]);
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
            break;
         case __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'uninstall' :
            Dropdown::showSelectItemFromItemtypes(['items_id_name' => 'item_item',
                                                   'itemtype_name' => 'typeitem',
                                                   'itemtypes'     => self::getTypes(true),
                                                   'checkright'    => true
                                                  ]);
            echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
            return true;
            break;
      }
      return parent::showMassiveActionsSubForm($ma);
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
    * @param MassiveAction $ma
    * @param CommonDBTM $item
    * @param array $ids
    * @return nothing|void
    */
   static function processMassiveActionsForOneItemtype(MassiveAction $ma,
                                                       CommonDBTM $item,
                                                       array $ids) {

      $certif_item = new Certificate_Item();

      switch ($ma->getAction()) {
         case __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'add_item':
            $input = $ma->getInput();
            foreach ($ids as $id) {
               $input = ['certificates_id' => $input['certificates_id'],
                         'items_id'        => $id,
                         'itemtype'        => $item->getType()
                        ];
               if ($certif_item->can(-1, UPDATE, $input)) {
                  if ($certif_item->add($input)) {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
               }
            }

            return;

         case __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'install' :
            $input = $ma->getInput();
            foreach ($ids as $key) {
               if ($item->can($key, UPDATE)) {
                  $values = array('plugin_certificates_certificates_id' => $key,
                     'items_id' => $input["item_item"],
                     'itemtype' => $input['typeitem']);
                  if ($certif_item->add($values)) {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                  } else {
                     $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                  }
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                  $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
               }
            }
            return;

         case __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'uninstall':
            $input = $ma->getInput();
            foreach ($ids as $key) {
               if ($certif_item->deleteItemByCertificatesAndItem($key, $input['item_item'], $input['typeitem'])) {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
               } else {
                  $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
               }
            }
            return;
      }
      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }

   /**
    * Type than could be linked to a certificate
    *
    * @param $all boolean, all type, or only allowed ones
    *
    * @return array of types
    **/
   static function getTypes($all = false) {
      global $CFG_GLPI;

      $types = $CFG_GLPI['certificate_types'];
      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }

         if (!$type::canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }
}
