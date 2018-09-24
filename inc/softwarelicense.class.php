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
 * SoftwareLicense Class
**/
class SoftwareLicense extends CommonTreeDropdown {

   /// TODO move to CommonDBChild ?
   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = ['Infocom'];

   static $rightname                   = 'license';
   protected $usenotepad               = true;



   static function getTypeName($nb = 0) {
      return _n('License', 'Licenses', $nb);
   }


   function pre_updateInDB() {

      // Clean end alert if expire is after old one
      if (isset($this->oldvalues['expire'])
          && ($this->oldvalues['expire'] < $this->fields['expire'])) {

         $alert = new Alert();
         $alert->clear($this->getType(), $this->fields['id'], Alert::END);
      }
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd($input);

      if (!isset($this->input['softwares_id']) || !$this->input['softwares_id']) {
            Session::addMessageAfterRedirect(__("Please select a software for this license"), true,
                                             ERROR, true);
            return false;
      }

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      // Unset to set to default using mysql default value
      if (empty($input['expire'])) {
         unset ($input['expire']);
      }

      return $input;
   }

   /**
    * @since 0.85
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);

      // Update number : compute validity indicator
      if (isset($input['number'])) {
         $input['is_valid'] = self::computeValidityIndicator($input['id'], $input['number']);
      }

      return $input;
   }


   /**
    * Compute licence validity indicator.
    *
    * @param $ID        ID of the licence
    * @param $number    licence count to check (default -1)
    *
    * @since 0.85
    *
    * @return validity indicator
   **/
   static function computeValidityIndicator($ID, $number = -1) {

      if (($number >= 0)
          && ($number < Computer_SoftwareLicense::countForLicense($ID, -1))) {
         return 0;
      }
      // Default return 1
      return 1;
   }


   /**
    * Update validity indicator of a specific license
    * @param $ID ID of the licence
    *
    * @since 0.85
    *
    * @return nothing
   **/
   static function updateValidityIndicator($ID) {

      $lic = new self();
      if ($lic->getFromDB($ID)) {
         $valid = self::computeValidityIndicator($ID, $lic->fields['number']);
         if ($valid != $lic->fields['is_valid']) {
            $lic->update(['id'       => $ID,
                               'is_valid' => $valid]);
         }
      }
   }


   /**
    * @since 0.84
   **/
   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Certificate_Item::class,
            Change_Item::class,
            Computer_SoftwareLicense::class,
         ]
      );

      // Alert does not extends CommonDBConnexity
      $alert = new Alert();
      $alert->cleanDBonItemDelete($this->getType(), $this->fields['id']);
   }


   function post_addItem() {
      global $CFG_GLPI;
      $itemtype = 'Software';
      $dupid    = $this->fields["softwares_id"];

      if (isset($this->input["_duplicate_license"])) {
         $itemtype = 'SoftwareLicense';
         $dupid    = $this->input["_duplicate_license"];
      }

      // Add infocoms if exists for the licence
      Infocom::cloneItem('Software', $dupid, $this->fields['id'], $this->getType());
      Software::updateValidityIndicator($this->fields["softwares_id"]);
   }

   /**
    * @since 0.85
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history = 1) {

      if (in_array("is_valid", $this->updates)) {
         Software::updateValidityIndicator($this->fields["softwares_id"]);
      }
   }


   /**
    * @since 0.85
    * @see CommonDBTM::post_deleteFromDB()
   **/
   function post_deleteFromDB() {
      Software::updateValidityIndicator($this->fields["softwares_id"]);
   }


   /**
    * @since 0.84
    *
    * @see CommonDBTM::getPreAdditionalInfosForName
   **/
   function getPreAdditionalInfosForName() {

      $soft = new Software();
      if ($soft->getFromDB($this->fields['softwares_id'])) {
         return $soft->getName();
      }
      return '';
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('SoftwareLicense', $ong, $options);
      $this->addStandardTab('Computer_SoftwareLicense', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Certificate_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);
      return $ong;
   }


   /**
    * Print the Software / license form
    *
    * @param $ID        integer  Id of the version or the template to print
    * @param $options   array    of possible options:
    *     - target form target
    *     - softwares_id ID of the software for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $softwares_id = -1;
      if (isset($options['softwares_id'])) {
         $softwares_id = $options['softwares_id'];
      }

      if ($ID < 0) {
         // Create item
         $this->fields['softwares_id'] = $softwares_id;
         $this->fields['number']       = 1;
         $soft                         = new Software();
         if ($soft->getFromDB($softwares_id)
             && in_array($_SESSION['glpiactive_entity'], getAncestorsOf('glpi_entities',
                                                                        $soft->getEntityID()))) {
            $options['entities_id'] = $soft->getEntityID();
         }
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      // Restore saved value or override with page parameter
      if (!isset($options['template_preview'])) {
         if (isset($_REQUEST)) {
            $saved = Html::cleanPostForTextArea($_REQUEST);
         }
      }
      foreach ($this->fields as $name => $value) {
         if (isset($saved[$name])
             && empty($this->fields[$name])) {
            $this->fields[$name] = $saved[$name];
         }
      }

      echo "<input type='hidden' name='withtemplate' value='".$options['withtemplate']."'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>".Software::getTypeName(1)."</td>";
      echo "<td>";
      if ($ID > 0) {
         $softwares_id = $this->fields["softwares_id"];
         echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
         echo "<a href='".Software::getFormURLWithID($softwares_id)."'>".
                Dropdown::getDropdownName("glpi_softwares", $softwares_id)."</a>";
      } else {
         Dropdown::show('Software',
                        ['condition'   => "`is_template`='0' AND `is_deleted`='0'",
                              'entity'      => $_SESSION['glpiactive_entity'],
                              'entity_sons' => $_SESSION['glpiactive_entity_recursive'],
                              'on_change'   => 'this.form.submit()',
                              'value'       => $softwares_id]);
      }

      echo "</td>";
      echo "<td colspan='2'>";
      echo "</td></tr>\n";

      $tplmark = $this->getAutofillMark('name', $options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".sprintf(__('%1$s%2$s'), __('Name'), $tplmark).
           "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name",
                             (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'name', ['value' => $objectName]);
      echo "</td>";
      echo "<td>".__('Status')."</td>";
      echo "<td>";
      State::dropdown(['value'     => $this->fields["states_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => "`is_visible_softwarelicense`='1'"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('As child of')."</td><td>";
      self::dropdown(['value'  => $this->fields['softwarelicenses_id'],
                           'name'   => 'softwarelicenses_id',
                           'entity' => $this->fields['entities_id'],
                           'used'   => (($ID > 0) ? getSonsOf($this->getTable(), $ID) : []),
                           'condition' => "`softwares_id`='".$this->fields['softwares_id']."'"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Location') . "</td><td>";
      Location::dropdown(['value'  => $this->fields["locations_id"],
                               'entity' => $this->fields["entities_id"]]);
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      SoftwareLicenseType::dropdown(['value' => $this->fields["softwarelicensetypes_id"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Technician in charge of the license')."</td>";
      echo "<td>";
      User::dropdown(['name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'own_ticket',
                           'entity' => $this->fields["entities_id"]]);
      echo "</td>";
      echo "<td>".__('Publisher')."</td>";
      echo "<td>";
      Manufacturer::dropdown(['value' => $this->fields["manufacturers_id"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Group in charge of the license')."</td>";
      echo "<td>";
      Group::dropdown(['name'      => 'groups_id_tech',
                            'value'     => $this->fields['groups_id_tech'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_assign`']);
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "serial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >" . __('User') . "</td>";
      echo "<td >";
      User::dropdown(['value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all']);
      echo "</td>";

      $tplmark = $this->getAutofillMark('otherserial', $options);
      echo "<td>".sprintf(__('%1$s%2$s'), __('Inventory number'), $tplmark);
      echo "</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial",
                             (isset($options['withtemplate']) && ($options['withtemplate'] == 2)),
                             $this->getType(), $this->fields["entities_id"]);
      Html::autocompletionTextField($this, 'otherserial', ['value' => $objectName]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Group') . "</td><td>";
      Group::dropdown(['value'     => $this->fields["groups_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => '`is_itemgroup`']);
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='45' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Version in use')."</td>";
      echo "<td>";
      SoftwareVersion::dropdownForOneSoftware(['name'         => "softwareversions_id_use",
                                                    'softwares_id' => $this->fields["softwares_id"],
                                                    'value'        => $this->fields["softwareversions_id_use"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Purchase version')."</td>";
      echo "<td>";
      SoftwareVersion::dropdownForOneSoftware(['name'         => "softwareversions_id_buy",
                                                    'softwares_id' => $this->fields["softwares_id"],
                                                    'value'        => $this->fields["softwareversions_id_buy"]]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('quantity', 'Number')."</td>";
      echo "<td>";
      Dropdown::showNumber("number", ['value' => $this->fields["number"],
                                           'min'   => 1,
                                           'max'   => 10000,
                                           'step'  => 1,
                                           'toadd' => [-1 => __('Unlimited')]]);
      if ($ID > 0) {
         echo "&nbsp;";
         if ($this->fields['is_valid']) {
            echo "<span class='green'>"._x('adjective', 'Valid').'<span>';
         } else {
            echo "<span class='red'>"._x('adjective', 'Invalid').'<span>';
         }
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Expiration')."</td>";
      echo "<td>";
      Html::showDateField('expire', ['value' => $this->fields["expire"]]);
      if ($ID
          && is_null($this->fields["expire"])) {
         echo "<br>".__('Never expire')."&nbsp;";
         Html::showToolTip(__('On search engine, use "Expiration contains NULL" to search licenses with no expiration date'));
      }
      Alert::displayLastAlert('SoftwareLicense', $ID);
      echo "</td><td colspan='2'></td></tr>\n";

      $this->showFormButtons($options);

      return true;
   }

   /**
    * Is the license may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive () {

      $soft = new Software();
      if (isset($this->fields["softwares_id"])
          && $soft->getFromDB($this->fields["softwares_id"])) {
         return $soft->isRecursive();
      }

      return true;
   }


   function rawSearchOptions() {
      $tab = [];

      // Only use for History (not by search Engine)
      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'forcegroupby'       => true
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number',
         'forcegroupby'       => true
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'number',
         'name'               => __('Number'),
         'datatype'           => 'number',
         'max'                => 100,
         'toadd'              => [
            '-1'                 => 'Unlimited'
         ]
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => 'glpi_softwarelicensetypes',
         'field'              => 'name',
         'name'               => __('Type'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => 'glpi_softwareversions',
         'field'              => 'name',
         'linkfield'          => 'softwareversions_id_buy',
         'name'               => __('Purchase version'),
         'datatype'           => 'dropdown',
         'displaywith'        => [
            '0'                  => __('states_id')
         ]
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => 'glpi_softwareversions',
         'field'              => 'name',
         'linkfield'          => 'softwareversions_id_use',
         'name'               => __('Version in use'),
         'datatype'           => 'dropdown',
         'displaywith'        => [
            '0'                  => __('states_id')
         ]
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'expire',
         'name'               => __('Expiration'),
         'datatype'           => 'date'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'is_valid',
         'name'               => __('Valid'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => 'glpi_softwares',
         'field'              => 'name',
         'name'               => __('Software'),
         'datatype'           => 'itemlink'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'completename',
         'name'               => __('Father'),
         'datatype'           => 'itemlink',
         'forcegroupby'       => true,
         'joinparams'        => ['condition' => "AND 1=1"]
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the license'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => '`is_visible_softwarelicense`'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the license'),
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
         'id'                 => '162',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));
      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }


   static public function rawSearchOptionsToAdd() {
      $tab = [];
      $name = _n('License', 'Licenses', Session::getPluralNumber());

      if (!self::canView()) {
         return $tab;
      }

      $licjoinexpire = ['jointype'  => 'child',
                              'condition' => getEntitiesRestrictRequest(' AND', "NEWTABLE",
                                                                        '', '', true).
                                             " AND (NEWTABLE.`expire` IS NULL
                                                   OR NEWTABLE.`expire` > NOW())"];

      $tab[] = [
         'id'                 => 'license',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '160',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'dropdown',
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      $tab[] = [
         'id'                 => '161',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'serial',
         'datatype'           => 'string',
         'name'               => __('Serial number'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      $tab[] = [
         'id'                 => '162',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'otherserial',
         'datatype'           => 'string',
         'name'               => __('Inventory number'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      $tab[] = [
         'id'                 => '163',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'number',
         'name'               => __('Number of licenses'),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'datatype'           => 'number',
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      $tab[] = [
         'id'                 => '164',
         'table'              => 'glpi_softwarelicensetypes',
         'field'              => 'name',
         'datatype'           => 'dropdown',
         'name'               => __('Type'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_softwarelicenses',
               'joinparams'         => $licjoinexpire
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '165',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'comment',
         'name'               => __('Comments'),
         'forcegroupby'       => true,
         'datatype'           => 'text',
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      $tab[] = [
         'id'                 => '166',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'expire',
         'name'               => __('Expiration'),
         'forcegroupby'       => true,
         'datatype'           => 'date',
         'emptylabel'         => 'Never expire',
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      $tab[] = [
         'id'                 => '167',
         'table'              => 'glpi_softwarelicenses',
         'field'              => 'is_valid',
         'name'               => __('Valid'),
         'forcegroupby'       => true,
         'datatype'           => 'bool',
         'massiveaction'      => false,
         'joinparams'         => $licjoinexpire
      ];

      return $tab;
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {
      return ['description' => __('Send alarms on expired licenses')];
   }


   /**
    * Cron action on softwares : alert on expired licences
    *
    * @param $task to log, if NULL display (default NULL)
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronSoftware($task = null) {
      global $DB, $CFG_GLPI;

      $cron_status = 1;

      if (!$CFG_GLPI['use_notifications']) {
         return 0;
      }

      $message      = [];
      $items_notice = [];
      $items_end    = [];

      foreach (Entity::getEntitiesToNotify('use_licenses_alert') as $entity => $value) {
         $before = Entity::getUsedConfig('send_licenses_alert_before_delay', $entity);
         // Check licenses
         $query = "SELECT `glpi_softwarelicenses`.*,
                          `glpi_softwares`.`name` AS softname
                   FROM `glpi_softwarelicenses`
                   INNER JOIN `glpi_softwares`
                        ON (`glpi_softwarelicenses`.`softwares_id` = `glpi_softwares`.`id`)
                   LEFT JOIN `glpi_alerts`
                        ON (`glpi_softwarelicenses`.`id` = `glpi_alerts`.`items_id`
                            AND `glpi_alerts`.`itemtype` = 'SoftwareLicense'
                            AND `glpi_alerts`.`type` = '".Alert::END."')
                   WHERE `glpi_alerts`.`date` IS NULL
                         AND `glpi_softwarelicenses`.`expire` IS NOT NULL
                         AND DATEDIFF(`glpi_softwarelicenses`.`expire`,
                                      CURDATE()) < '$before'
                         AND `glpi_softwares`.`is_template` = 0
                         AND `glpi_softwares`.`is_deleted` = 0
                         AND `glpi_softwares`.`entities_id` = '".$entity."'";

         $message = "";
         $items   = [];

         foreach ($DB->request($query) as $license) {
            $name     = $license['softname'].' - '.$license['name'].' - '.$license['serial'];
            //TRANS: %1$s the license name, %2$s is the expiration date
            $message .= sprintf(__('License %1$s expired on %2$s'),
                                Html::convDate($license["expire"]), $name)."<br>\n";
            $items[$license['id']] = $license;
         }

         if (!empty($items)) {
            $alert                  = new Alert();
            $options['entities_id'] = $entity;
            $options['licenses']    = $items;

            if (NotificationEvent::raiseEvent('alert', new self(), $options)) {
               $entityname = Dropdown::getDropdownName("glpi_entities", $entity);
               if ($task) {
                  //TRANS: %1$s is the entity, %2$s is the message
                  $task->log(sprintf(__('%1$s: %2$s')."\n", $entityname, $message));
                  $task->addVolume(1);
               } else {
                  Session::addMessageAfterRedirect(sprintf(__('%1$s: %2$s'),
                                                           $entityname, $message));
               }

               $input["type"]     = Alert::END;
               $input["itemtype"] = 'SoftwareLicense';

               // add alerts
               foreach ($items as $ID => $consumable) {
                  $input["items_id"] = $ID;
                  $alert->add($input);
                  unset($alert->fields['id']);
               }

            } else {
               $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
               //TRANS: %s is entity name
               $msg = sprintf(__('%1$s: %2$s'), $entityname, __('Send licenses alert failed'));
               if ($task) {
                  $task->log($msg);
               } else {
                  Session::addMessageAfterRedirect($msg, false, ERROR);
               }
            }
         }
      }
      return $cron_status;
   }


   /**
    * Get number of bought licenses of a version
    *
    * @param $softwareversions_id   version ID
    * @param $entity                to search for licenses in (default = all active entities)
    *                               (default '')
    *
    * @return number of installations
   */
   static function countForVersion($softwareversions_id, $entity = '') {
      global $DB;

      $query = "SELECT COUNT(*)
                FROM `glpi_softwarelicenses`
                WHERE `softwareversions_id_buy` = '$softwareversions_id' " .
                      getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', $entity);

      $result = $DB->query($query);

      if ($DB->numrows($result) != 0) {
         return $DB->result($result, 0, 0);
      }
      return 0;
   }


   /**
    * Get number of licensesof a software
    *
    * @param $softwares_id software ID
    *
    * @return number of licenses
   **/
   static function countForSoftware($softwares_id) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` = '$softwares_id'
                      AND `number` = '-1' " .
                      getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true);

      $result = $DB->query($query);
      if ($DB->numrows($result)) {
         // At least 1 unlimited license, means unlimited
         return -1;
      }

      $query = "SELECT SUM(`number`)
                FROM `glpi_softwarelicenses`
                WHERE `softwares_id` = '$softwares_id'
                      AND `number` > '0' " .
                      getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true);

      $result = $DB->query($query);
      $nb     = $DB->result($result, 0, 0);
      return ($nb ? $nb : 0);
   }


   /**
    * Show Licenses of a software
    *
    * @param $software Software object
    *
    * @return nothing
   **/
   static function showForSoftware(Software $software) {
      global $DB, $CFG_GLPI;

      $softwares_id  = $software->getField('id');
      $license       = new self();
      $computer      = new Computer();

      if (!$software->can($softwares_id, READ)) {
         return false;
      }

      $columns = ['name'      => __('Name'),
                       'entity'    => __('Entity'),
                       'serial'    => __('Serial number'),
                       'number'    => _x('quantity', 'Number'),
                       '_affected' => __('Affected computers'),
                       'typename'  => __('Type'),
                       'buyname'   => __('Purchase version'),
                       'usename'   => __('Version in use'),
                       'expire'    => __('Expiration')];
      if (!$software->isRecursive()) {
         unset($columns['entity']);
      }

      if (isset($_GET["start"])) {
         $start = $_GET["start"];
      } else {
         $start = 0;
      }

      if (isset($_GET["order"]) && ($_GET["order"] == "DESC")) {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      if (isset($_GET["sort"]) && !empty($_GET["sort"]) && isset($columns[$_GET["sort"]])) {
         $sort = "`".$_GET["sort"]."`";
      } else {
         $sort = "`entity` $order, `name`";
      }

      // Righ type is enough. Can add a License on a software we have Read access
      $canedit             = Software::canUpdate();
      $showmassiveactions  = $canedit;

      // Total Number of events
      $number = countElementsInTable(
         "glpi_softwarelicenses", [
            'glpi_softwarelicenses.softwares_id'   => $softwares_id
         ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', '', '', true)
      );
      echo "<div class='spaced'>";

      Session::initNavigateListItems('SoftwareLicense',
            //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), Software::getTypeName(1),
                                             $software->getName()));

      if ($canedit) {
         echo "<div class='center firstbloc'>";
         echo "<a class='vsubmit' href='".SoftwareLicense::getFormURL()."?softwares_id=$softwares_id'>".
                _x('button', 'Add a license')."</a>";
         echo "</div>";
      }

      $rand  = mt_rand();
      $query = "SELECT `glpi_softwarelicenses`.*,
                       `buyvers`.`name` AS buyname,
                       `usevers`.`name` AS usename,
                       `glpi_entities`.`completename` AS entity,
                       `glpi_softwarelicensetypes`.`name` AS typename
                FROM `glpi_softwarelicenses`
                LEFT JOIN `glpi_softwareversions` AS buyvers
                     ON (`buyvers`.`id` = `glpi_softwarelicenses`.`softwareversions_id_buy`)
                LEFT JOIN `glpi_softwareversions` AS usevers
                     ON (`usevers`.`id` = `glpi_softwarelicenses`.`softwareversions_id_use`)
                LEFT JOIN `glpi_entities`
                     ON (`glpi_entities`.`id` = `glpi_softwarelicenses`.`entities_id`)
                LEFT JOIN `glpi_softwarelicensetypes`
                     ON (`glpi_softwarelicensetypes`.`id`
                          = `glpi_softwarelicenses`.`softwarelicensetypes_id`)
                WHERE (`glpi_softwarelicenses`.`softwares_id` = '$softwares_id') " .
                       getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses', '', '', true) ."
                ORDER BY $sort $order
                LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

      if ($result = $DB->query($query)) {
         if ($num_displayed = $DB->numrows($result)) {
            // Display the pager
            Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);
            if ($showmassiveactions) {
               Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
               $massiveactionparams
                  = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $num_displayed),
                          'container'
                           => 'mass'.__CLASS__.$rand,
                          'extraparams'
                           => ['options'
                                     => ['glpi_softwareversions.name'
                                               => ['condition'
                                                         => "`glpi_softwareversions`.`softwares_id`
                                                                  = $softwares_id"],
                                               'glpi_softwarelicenses.name'
                                               => ['itemlink_as_string' => true]]]];

               Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";

            $header_begin  = "<tr><th>";
            $header_top    = Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom = Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    = '';

            foreach ($columns as $key => $val) {
               // Non order column
               if ($key[0] == '_') {
                  $header_end .= "<th>$val</th>";
               } else {
                  $header_end .= "<th".($sort == "`$key`" ? " class='order_$order'" : '').">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                           (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
               }
            }

            $header_end .= "</tr>\n";
            echo $header_begin.$header_top.$header_end;

            $tot_assoc = 0;
            for ($tot=0; $data=$DB->fetch_assoc($result);) {
               Session::addToNavigateListItems('SoftwareLicense', $data['id']);
               $expired = true;
               if (is_null($data['expire'])
                  || ($data['expire'] > date('Y-m-d'))) {
                  $expired = false;
               }
               echo "<tr class='tab_bg_2".($expired?'_2':'')."'>";

               if ($license->canEdit($data['id'])) {
                  echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }

               echo "<td>";
               echo $license->getLink(['complete' => true, 'comments' => true]);
               echo "</td>";

               if (isset($columns['entity'])) {
                  echo "<td>";
                  echo $data['entity'];
                  echo "</td>";
               }
               echo "<td>".$data['serial']."</td>";
               echo "<td class='numeric'>".
                      (($data['number'] > 0) ?$data['number']:__('Unlimited'))."</td>";
               $nb_assoc   = Computer_SoftwareLicense::countForLicense($data['id']);
               $tot_assoc += $nb_assoc;
               $color = ($data['is_valid']?'green':'red');

               echo "<td class='numeric $color'>".$nb_assoc."</td>";
               echo "<td>".$data['typename']."</td>";
               echo "<td>".$data['buyname']."</td>";
               echo "<td>".$data['usename']."</td>";
               echo "<td class='center'>".Html::convDate($data['expire'])."</td>";
               echo "</tr>";

               if ($data['number'] < 0) {
                  // One illimited license, total is illimited
                  $tot = -1;
               } else if ($tot >= 0) {
                  // Expire license not count
                  if (!$expired) {
                     // Not illimited, add the current number
                     $tot += $data['number'];
                  }
               }
            }
            echo "<tr class='tab_bg_1 noHover'>";
            echo "<td colspan='".
                   ($software->isRecursive()?4:3)."' class='right b'>".__('Total')."</td>";
            echo "<td class='numeric'>".(($tot > 0)?$tot."":__('Unlimited')).
                 "</td>";
            $color = ($software->fields['is_valid']?'green':'red');
            echo "<td class='numeric $color'>".$tot_assoc."</td><td></td><td></td><td></td><td></td>";
            echo "</tr>";
            echo "</table>\n";

            if ($showmassiveactions) {
               $massiveactionparams['ontop'] = false;
               Html::showMassiveActions($massiveactionparams);

               Html::closeForm();
            }
            Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);
         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".__('No item found')."</th></tr></table>";
         }
      }

      echo "</div>";
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      $license = ['softname' => '',
                       'name'     => '',
                       'serial'   => '',
                       'expire'   => ''];

      $options['entities_id'] = $this->getEntityID();
      $options['licenses']    = [$license];
      NotificationEvent::debugEvent($this, $options);
   }


   /**
    * Get fields to display in the unicity error message
    *
    * @return an array which contains field => label
   */
   function getUnicityFieldsToDisplayInErrorMessage() {

      return ['id'           => __('ID'),
                   'serial'       => __('Serial number'),
                   'entities_id'  => __('Entity'),
                   'softwares_id' => _n('Software', 'Software', 1)];
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Software' :
               if (!self::canView()) {
                  return '';
               }
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForSoftware($item->getID());
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
                                           (($nb >= 0) ? $nb : '&infin;'));
            break;
            case 'SoftwareLicense' :
               if (!self::canView()) {
                  return '';
               }
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable(
                     $this->getTable(),
                     ['softwarelicenses_id' => $item->getID()]
                  );
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
                                           (($nb >= 0) ? $nb : '&infin;'));
            break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType()=='Software' && self::canView()) {
         self::showForSoftware($item);
      } else {
         if ($item->getType()=='SoftwareLicense' && self::canView()) {
            self::getSonsOf($item);
            return true;
         }
      }
      return true;
   }


   static function getSonsOf($item) {
      global $DB;
      $entity_assign = $item->isEntityAssign();
      $nb            = 0;
      $ID            = $item->getID();

      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='noHover'><th colspan='".($nb+3)."'>".sprintf(__('Sons of %s'),
                                                                    $item->getTreeLink());
      echo "</th></tr>";

      $header = "<tr><th>".__('Name')."</th>";
      if ($entity_assign) {
         $header .= "<th>".__('Entity')."</th>";
      }

      $header .= "<th>".__('Comments')."</th>";
      $header .= "</tr>\n";
      echo $header;

      $fk   = $item->getForeignKeyField();
      $crit = [$fk     => $ID,
                    'ORDER' => 'name'];

      if ($entity_assign) {
         if ($fk == 'entities_id') {
            $crit['id']  = $_SESSION['glpiactiveentities'];
            $crit['id'] += $_SESSION['glpiparententities'];
         } else {
            foreach ($_SESSION['glpiactiveentities'] as $key => $value) {
               $crit['entities_id'][$key] = (string)$value;
            }
         }
      }
      $nb = 0;

      foreach ($DB->request($item->getTable(), $crit) as $data) {
         $nb++;
         echo "<tr class='tab_bg_1'>";
         echo "<td><a href='".$item->getFormURL();
         echo '?id='.$data['id']."'>".$data['name']."</a></td>";
         if ($entity_assign) {
            echo "<td>".Dropdown::getDropdownName("glpi_entities", $data["entities_id"])."</td>";
         }

         echo "<td>".$data['comment']."</td>";
         echo "</tr>\n";
      }
      if ($nb) {
         echo $header;
      }
      echo "</table></div>\n";
   }
}
