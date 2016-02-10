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
 * SoftwareLicense Class
**/
class SoftwareLicense extends CommonDBTM {

   /// TODO move to CommonDBChild ?
   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = array('Infocom');

   static $rightname                   = 'software';
   protected $usenotepad               = true;



   static function getTypeName($nb=0) {
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


   function prepareInputForAdd($input) {

      // Unset to set to default using mysql default value
      if (empty($input['expire'])) {
         unset ($input['expire']);
      }

      return $input;
   }


   /**
    * @since version 0.85
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

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
    * @since version 0.85
    *
    * @return validity indicator
   **/
   static function computeValidityIndicator($ID, $number=-1) {

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
    * @since version 0.85
    *
    * @return nothing
   **/
   static function updateValidityIndicator($ID) {

      $lic = new self();
      if ($lic->getFromDB($ID)) {
         $valid = self::computeValidityIndicator($ID, $lic->fields['number']);
         if ($valid != $lic->fields['is_valid']) {
            $lic->update(array('id'       => $ID,
                               'is_valid' => $valid));
         }
      }
   }


   /**
    * @since version 0.84
   **/
   function cleanDBonPurge() {

      $csl = new Computer_SoftwareLicense();
      $csl->cleanDBonItemDelete('SoftwareLicense', $this->fields['id']);

      $class = new Alert();
      $class->cleanDBonItemDelete($this->getType(), $this->fields['id']);
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
    * @since version 0.85
    * @see CommonDBTM::post_updateItem()
   **/
   function post_updateItem($history=1) {

      if (in_array("is_valid", $this->updates)) {
         Software::updateValidityIndicator($this->fields["softwares_id"]);
      }
   }


   /**
    * @since version 0.85
    * @see CommonDBTM::post_deleteFromDB()
   **/
   function post_deleteFromDB() {
      Software::updateValidityIndicator($this->fields["softwares_id"]);
   }


   /**
    * @since version 0.84
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

   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Computer_SoftwareLicense', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
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
   function showForm($ID, $options=array()) {
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

      echo "<tr class='tab_bg_1'>";
      echo "<td>".Software::getTypeName(1)."</td>";
      echo "<td>";
      if ($ID > 0) {
         $softwares_id = $this->fields["softwares_id"];
      } else {
         echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
      }
      echo "<a href='software.form.php?id=".$softwares_id."'>".
             Dropdown::getDropdownName("glpi_softwares", $softwares_id)."</a>";
      echo "</td>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      SoftwareLicenseType::dropdown(array('value' => $this->fields["softwarelicensetypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"name");
      echo "</td>";
      echo "<td>".__('Serial number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"serial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Purchase version')."</td>";
      echo "<td>";
      SoftwareVersion::dropdownForOneSoftware(array('name'         => "softwareversions_id_buy",
                                                    'softwares_id' => $this->fields["softwares_id"],
                                                    'value'        => $this->fields["softwareversions_id_buy"]));
      echo "</td>";
      echo "<td>".__('Inventory number')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"otherserial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Version in use')."</td>";
      echo "<td>";
      SoftwareVersion::dropdownForOneSoftware(array('name'         => "softwareversions_id_use",
                                                    'softwares_id' => $this->fields["softwares_id"],
                                                    'value'        => $this->fields["softwareversions_id_use"]));
      echo "</td>";
      echo "<td rowspan='".(($ID > 0) ?'4':'3')."' class='middle'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='".(($ID > 0) ?'4':'3')."'>";
      echo "<textarea cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('quantity', 'Number')."</td>";
      echo "<td>";
      Dropdown::showNumber("number", array('value' => $this->fields["number"],
                                           'min'   => 1,
                                           'max'   => 10000,
                                           'step'  => 1,
                                           'toadd' => array(-1 => __('Unlimited'))));
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
      Html::showDateField('expire', array('value' => $this->fields["expire"]));
      if ($ID
          && is_null($this->fields["expire"])) {
         echo "<br>".__('Never expire')."&nbsp;";
         Html::showToolTip(__('On search engine, use "Expiration contains NULL" to search licenses with no expiration date'));
      }
      Alert::displayLastAlert('SoftwareLicense', $ID);
      echo "</td></tr>\n";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>".($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
         echo "</td></tr>";
      }

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
      return false;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);

      if ($isadmin) {
         MassiveAction::getAddTransferList($actions);
      }

      return $actions;
   }


   function getSearchOptions() {

      // Only use for History (not by search Engine)
      $tab                       = array();
      $tab['common']             = __('Characteristics');

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'name';
      $tab[2]['name']            = __('Name');
      $tab[2]['datatype']        = 'itemlink';

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'serial';
      $tab[3]['name']            = __('Serial number');
      $tab[3]['datatype']        = 'string';

      $tab[162]['table']         = $this->getTable();
      $tab[162]['field']         = 'otherserial';
      $tab[162]['name']          = __('Inventory number');
      $tab[162]['massiveaction'] = false;
      $tab[162]['datatype']      = 'string';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'number';
      $tab[4]['name']            = _x('quantity', 'Number');
      $tab[4]['datatype']        = 'number';
      $tab[4]['max']             = 100;
      $tab[4]['toadd']           = array(-1 => __('Unlimited'));

      $tab[5]['table']           = 'glpi_softwarelicensetypes';
      $tab[5]['field']           = 'name';
      $tab[5]['name']            = __('Type');
      $tab[5]['datatype']        = 'dropdown';

      $tab[6]['table']           = 'glpi_softwareversions';
      $tab[6]['field']           = 'name';
      $tab[6]['linkfield']       = 'softwareversions_id_buy';
      $tab[6]['name']            = __('Purchase version');
      $tab[6]['datatype']        = 'dropdown';
      $tab[6]['displaywith']     = array('states_id');

      $tab[7]['table']           = 'glpi_softwareversions';
      $tab[7]['field']           = 'name';
      $tab[7]['linkfield']       = 'softwareversions_id_use';
      $tab[7]['name']            = __('Version in use');
      $tab[7]['datatype']        = 'dropdown';
      $tab[7]['displaywith']     = array('states_id');

      $tab[8]['table']           = $this->getTable();
      $tab[8]['field']           = 'expire';
      $tab[8]['name']            = __('Expiration');
      $tab[8]['datatype']        = 'date';

      $tab[9]['table']           = $this->getTable();
      $tab[9]['field']           = 'is_valid';
      $tab[9]['name']            = __('Valid');
      $tab[9]['datatype']        = 'bool';

      $tab[16]['table']          = $this->getTable();
      $tab[16]['field']          = 'comment';
      $tab[16]['name']           = __('Comments');
      $tab[16]['datatype']       = 'text';

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
      return array('description' => __('Send alarms on expired licenses'));
   }


   /**
    * Cron action on softwares : alert on expired licences
    *
    * @param $task to log, if NULL display (default NULL)
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronSoftware($task=NULL) {
      global $DB, $CFG_GLPI;

      $cron_status = 1;

      if (!$CFG_GLPI['use_mailing']) {
         return 0;
      }

      $message      = array();
      $items_notice = array();
      $items_end    = array();

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
                         AND `glpi_softwares`.`is_template` = '0'
                         AND `glpi_softwares`.`is_deleted` = '0'
                         AND `glpi_softwares`.`entities_id` = '".$entity."'";

         $message = "";
         $items   = array();

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
   static function countForVersion($softwareversions_id, $entity='') {
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
      $nb     = $DB->result($result,0,0);
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

      $columns = array('name'      => __('Name'),
                       'entity'    => __('Entity'),
                       'serial'    => __('Serial number'),
                       'number'    => _x('quantity', 'Number'),
                       '_affected' => __('Affected computers'),
                       'typename'  => __('Type'),
                       'buyname'   => __('Purchase version'),
                       'usename'   => __('Version in use'),
                       'expire'    => __('Expiration'));
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
      $number = countElementsInTable("glpi_softwarelicenses",
                                     "glpi_softwarelicenses.softwares_id = $softwares_id " .
                                          getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses',
                                                                     '', '', true));
      echo "<div class='spaced'>";

      Session::initNavigateListItems('SoftwareLicense',
            //TRANS : %1$s is the itemtype name, %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), Software::getTypeName(1),
                                             $software->getName()));

      if ($canedit) {
         echo "<div class='center firstbloc'>";
         echo "<a class='vsubmit' href='softwarelicense.form.php?softwares_id=$softwares_id'>".
                _x('button', 'Add a license')."</a>";
         echo "</div>";
      }

      // Display the pager
      Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);

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
            if ($showmassiveactions) {
               Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
               $massiveactionparams
                  = array('num_displayed'
                           => $num_displayed,
                          'container'
                           => 'mass'.__CLASS__.$rand,
                          'extraparams'
                           => array('options'
                                     => array('glpi_softwareversions.name'
                                               => array('condition'
                                                         => "`glpi_softwareversions`.`softwares_id`
                                                                  = $softwares_id"),
                                              'glpi_softwarelicenses.name'
                                               => array('itemlink_as_string' => true))));

               Html::showMassiveActions($massiveactionparams);
            }
            $sort_img = "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
                        (($order == "DESC") ? "puce-down.png" : "puce-up.png") ."\" alt='' title=''>";


            $sort_img = "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
                          (($order == "DESC") ? "puce-down.png" : "puce-up.png") ."\" alt='' title=''>";

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
                  $header_end .= "<th>".(($sort == "`$key`") ?$sort_img:"").
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                           (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
               }
            }

            $header_end .= "</tr>\n";
            echo $header_begin.$header_top.$header_end;

            $tot_assoc = 0;
            for ($tot=0 ; $data=$DB->fetch_assoc($result) ; ) {
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

               echo "<td><a href='softwarelicense.form.php?id=".$data['id']."'>".$data['name'].
                          (empty($data['name']) ?"(".$data['id'].")" :"")."</a></td>";

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

         } else {
            _e('No item found');
         }
      }
      Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, $number);

      echo "</div>";
   }


   /**
    * Display debug information for current object
   **/
   function showDebug() {

      $license = array('softname' => '',
                       'name'     => '',
                       'serial'   => '',
                       'expire'   => '');

      $options['entities_id'] = $this->getEntityID();
      $options['licenses']    = array($license);
      NotificationEvent::debugEvent($this, $options);
   }


   /**
    * Get fields to display in the unicity error message
    *
    * @return an array which contains field => label
   */
   function getUnicityFieldsToDisplayInErrorMessage() {

      return array('id'           => __('ID'),
                   'serial'       => __('Serial number'),
                   'entities_id'  => __('Entity'),
                   'softwares_id' => _n('Software', 'Software', 1));
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Software' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForSoftware($item->getID());
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()),
                                           (($nb >= 0) ? $nb : '&infin;'));
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Software') {
         self::showForSoftware($item);
      }
      return true;
   }

}
?>
