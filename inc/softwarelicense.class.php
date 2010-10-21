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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// License class
class SoftwareLicense extends CommonDBTM {

   // From CommonDBTM
   public $dohistory = true;

   protected $forward_entity_to = array('Infocom');


   static function getTypeName() {
      global $LANG;

      return $LANG['software'][11];
   }


   function canCreate() {
      return haveRight('software', 'w');
   }


   function canView() {
      return haveRight('software', 'r');
   }


   function pre_updateInDB() {

      // Clean end alert if expire is after old one
      if ((isset($this->oldvalues['expire'])
           && ($this->oldvalues['expire'] < $this->fields['expire']))) {

         $alert = new Alert();
         $alert->clear($this->getType(), $this->fields['id'], Alert::END);
      }
   }


   function prepareInputForAdd($input) {

      // Unset to set to default using mysql default value
      if (empty ($input['expire'])) {
         unset ($input['expire']);
      }

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      $itemtype = 'Software';
      $dupid    = $this->fields["softwares_id"];

      if (isset ($this->input["_duplicate_license"])) {
         $itemtype = 'SoftwareLicense';
         $dupid    = $this->input["_duplicate_license"];
      }

      // Add infocoms if exists for the licence
      $ic = new Infocom();
      if ($ic->getFromDBforDevice($itemtype, $dupid)) {
         unset ($ic->fields["id"]);
         $ic->fields["items_id"] = $this->fields['id'];

         if (isset($ic->fields["immo_number"])) {
            $ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1,
                                                  'Infocom', $this->input['entities_id']);
         }

         if (empty($ic->fields['use_date'])) {
            unset($ic->fields['use_date']);
         }

         if (empty($ic->fields['buy_date'])) {
            unset($ic->fields['buy_date']);
         }

         $ic->fields["itemtype"] = $this->getType();
         $ic->addToDB();
      }
   }


   function defineTabs($options=array()) {
      global $LANG;

      $ong[1] = $LANG['title'][26];
      if ($this->fields['id'] > 0) {
         $ong[2] = $LANG['Menu'][0];

         if (haveRight("infocom","r")) {
            $ong[4] = $LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         $ong[12] = $LANG['title'][38];
      }
      return $ong;
   }


   /**
    * Print the Software / license form
    *
    * @param $ID Integer : Id of the version or the template to print
    * @param $options array
    *     - target form target
    *     - softwares_id ID of the software for add process
    *
    * @return true if displayed  false if item not found or not right to display
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      $softwares_id = -1;
      if (isset($options['softwares_id'])) {
         $softwares_id = $options['softwares_id'];
      }

      if (!haveRight("software","w")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->fields['softwares_id'] = $softwares_id;
         $this->fields['number']       = 1;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][31]."&nbsp;:</td>";
      echo "<td>";
      if ($ID>0) {
         $softwares_id = $this->fields["softwares_id"];
      } else {
         echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
      }
      echo "<a href='software.form.php?id=".$softwares_id."'>".
             Dropdown::getDropdownName("glpi_softwares", $softwares_id)."</a>";
      echo "</td>";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::show('SoftwareLicenseType',
                     array('value' => $this->fields["softwarelicensetypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this,"name");
      echo "</td>";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this,"serial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][1]."&nbsp;:</td>";
      echo "<td>";
      SoftwareVersion::dropdown(array('name'         => "softwareversions_id_buy",
                                      'softwares_id' => $this->fields["softwares_id"],
                                      'value'        => $this->fields["softwareversions_id_buy"]));
      echo "</td>";
      echo "<td>".$LANG['common'][20]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this,"otherserial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][2]."&nbsp;:</td>";
      echo "<td>";
      SoftwareVersion::dropdown(array('name'         => "softwareversions_id_use",
                                      'softwares_id' => $this->fields["softwares_id"],
                                      'value'        => $this->fields["softwareversions_id_use"]));
      echo "</td>";
      echo "<td rowspan='".($ID>0?'4':'3')."' class='middle'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='".($ID>0?'4':'3')."'>";
      echo "<textarea cols='45' rows='5' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['tracking'][29]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::showInteger("number", $this->fields["number"], 1, 1000, 1,
                            array(-1 => $LANG['software'][4]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][32]."&nbsp;:</td>";
      echo "<td>";
      showDateFormItem('expire', $this->fields["expire"]);
      Alert::displayLastAlert('SoftwareLicense', $ID);
      echo "</td></tr>\n";

      if ($ID>0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".$LANG['common'][26]."&nbsp;: </td>";
         echo "<td>".($this->fields["date_mod"] ? convDateTime($this->fields["date_mod"])
                                                : $LANG['setup'][307]);
         echo "</td></tr>";
      }

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Is the license may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive () {

      $soft = new Software();
      if (isset($this->fields["softwares_id"]) && $soft->getFromDB($this->fields["softwares_id"])) {
         return $soft->isRecursive();
      }
      return false;
   }


   function getSearchOptions() {
      global $LANG;

      // Only use for History (not by search Engine)
      $tab = array();

      $tab[2]['table'] = $this->getTable();
      $tab[2]['field'] = 'name';
      $tab[2]['name']  = $LANG['common'][16];

      $tab[3]['table'] = $this->getTable();
      $tab[3]['field'] = 'serial';
      $tab[3]['name']  = $LANG['common'][19];

      $tab[162]['table']         = $this->getTable();
      $tab[162]['field']         = 'otherserial';
      $tab[162]['name']          = $LANG['common'][20];
      $tab[162]['massiveaction'] = false;

      $tab[4]['table']    = $this->getTable();
      $tab[4]['field']    = 'number';
      $tab[4]['name']     = $LANG['tracking'][29];
      $tab[4]['datatype'] = 'number';

      $tab[5]['table'] = 'glpi_softwarelicensetypes';
      $tab[5]['field'] = 'name';
      $tab[5]['name']  = $LANG['common'][17];

      $tab[6]['table']     = 'glpi_softwareversions';
      $tab[6]['field']     = 'name';
      $tab[6]['linkfield'] = 'softwareversions_id_buy';
      $tab[6]['name']      = $LANG['software'][1];

      $tab[7]['table']     = 'glpi_softwareversions';
      $tab[7]['field']     = 'name';
      $tab[7]['linkfield'] = 'softwareversions_id_use';
      $tab[7]['name']      = $LANG['software'][2];

      $tab[8]['table']    = $this->getTable();
      $tab[8]['field']    = 'expire';
      $tab[8]['name']     = $LANG['software'][32];
      $tab[8]['datatype'] = 'date';

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = $LANG['common'][25];
      $tab[16]['datatype'] = 'text';

      return $tab;
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][4]);
   }


   /**
    * Cron action on softwares : alert on expired licences
    *
    * @param $task to log, if NULL display
    *
    * @return 0 : nothing to do 1 : done with success
   **/
   static function cronSoftware($task=NULL) {
      global $DB, $CFG_GLPI, $LANG;

      $cron_status = 1;

      if (!$CFG_GLPI['use_mailing']) {
         return 0;
      }

      $message      = array();
      $items_notice = array();
      $items_end    = array();

      foreach (Entity::getEntitiesToNotify('use_licenses_alert') as $entity => $value) {
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
                         AND `glpi_softwarelicenses`.`expire` < CURDATE()
                         AND `glpi_softwares`.`is_template` = '0'
                         AND `glpi_softwares`.`is_deleted` = '0'
                         AND `glpi_softwares`.`entities_id` = '".$entity."'";

         $message = "";
         $items   = array();

         foreach ($DB->request($query) as $license) {
            $name     = $license['softname'].' - '.$license['name'].' - '.$license['serial'];
            $message .= $LANG['mailing'][51]." ".$name.": ".convDate($license["expire"])."<br>\n";
            $items[$license['id']] = $license;
         }

         if (!empty($items)) {
            $alert = new Alert();
            $options['entities_id'] = $entity;
            $options['licenses']    = $items;

            if (NotificationEvent::raiseEvent('alert', new SoftwareLicense(), $options)) {
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity)." :  $message\n");
                   $task->addVolume(1);
                } else {
                  addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities",
                                                                    $entity)." :  $message");
               }

               $input["type"]     = Alert::END;
               $input["itemtype"] = 'SoftwareLicense';

               // add alerts
               foreach ($items as $ID=>$consumable) {
                  $input["items_id"] = $ID;
                  $alert->add($input);
                  unset($alert->fields['id']);
               }

            } else {
               if ($task) {
                  $task->log(Dropdown::getDropdownName("glpi_entities", $entity).
                             " : Send licenses alert failed\n");
               } else {
                  addMessageAfterRedirect(Dropdown::getDropdownName("glpi_entities", $entity).
                                          " : Send licenses alert failed",false,ERROR);
               }
            }
         }
       }
      return $cron_status;
   }


   /**
    * Get number of bought licenses of a version
    *
    * @param $softwareversions_id version ID
    * @param $entity to search for licenses in (default = all active entities)
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
      $nb = $DB->result($result,0,0);
      return ($nb ? $nb : 0);
   }


   /**
    * Show Licenses of a software
    *
    * @param $software software object
    *
    * @return nothing
   **/
   static function showForSoftware($software) {
      global $DB, $CFG_GLPI, $LANG;

      $softwares_id = $software->getField('id');
      $license  = new SoftwareLicense;
      $computer = new Computer();

      if (!$software->can($softwares_id,"r")) {
         return false;
      }

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }


      if (isset($_REQUEST["order"]) && $_REQUEST["order"]=="DESC") {
         $order = "DESC";
      } else {
         $order = "ASC";
      }

      if (isset($_REQUEST["sort"]) && !empty($_REQUEST["sort"])) {
         $sort = "`".$_REQUEST["sort"]."`";
      } else {
         $sort = "`entity` $order, `name`";
      }


      // Righ type is enough. Can add a License on a software we have Read access
      $canedit = haveRight("software", "w");

      // Total Number of events
      $number = countElementsInTable("glpi_softwarelicenses",
                                     "glpi_softwarelicenses.softwares_id = $softwares_id " .
                                          getEntitiesRestrictRequest('AND', 'glpi_softwarelicenses',
                                                                     '', '', true));
      echo "<div class='spaced'>";

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".$LANG['search'][15]."</th></tr>\n";

         if ($canedit) {
            echo "<tr class='tab_bg_2'><td class='center'>";
            echo "<a href='softwarelicense.form.php?softwares_id=$softwares_id'>".
                   $LANG['software'][8]."</a>";
            echo "</td></tr>\n";
         }

         echo "</table></div>\n";
         return;
      }

      // Display the pager
      printAjaxPager($LANG['software'][11], $start, $number);

      $rand = mt_rand();
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

      initNavigateListItems('SoftwareLicense', $LANG['help'][31] ." = ". $software->fields["name"]);

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            if ($canedit) {
               echo "<form method='post' name='massiveactionlicense_form$rand' id='".
                      "massiveactionlicense_form$rand' action=\"".$CFG_GLPI["root_doc"].
                      "/front/massiveaction.php\">";
            }
            $sort_img = "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/" .
                        ($order == "DESC" ? "puce-down.png" : "puce-up.png") ."\" alt='' title=''>";

            echo "<table class='tab_cadre_fixehov'><tr>";
            echo "<th>&nbsp;</th>";
            echo "<th>".($sort=="`name`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=name&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][16]."</a></th>";

            if ($software->isRecursive()) {
               // Ereg to search entity in string for match default order
               echo "<th>".(strstr($sort,"entity")?$sort_img:"").
                    "<a href='javascript:reloadTab(\"sort=entity&amp;order=".
                      ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['entity'][0].
                    "</a></th>";
            }

            echo "<th>".($sort=="`serial`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=serial&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][19]."</a></th>";
            echo "<th>".($sort=="`number`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=number&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['tracking'][29].
                 "</a></th>";
            echo "<th>".$LANG['software'][9]."</th>";
            echo "<th>".($sort=="`typename`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=typename&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['common'][17]."</a></th>";
            echo "<th>".($sort=="`buyname`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=buyname&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['software'][1].
                 "</a></th>";
            echo "<th>".($sort=="`usename`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=usename&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['software'][2]."</a></th>";
            echo "<th>".($sort=="`expire`"?$sort_img:"").
                 "<a href='javascript:reloadTab(\"sort=expire&amp;order=".
                   ($order=="ASC"?"DESC":"ASC")."&amp;start=0\");'>".$LANG['software'][32].
                 "</a></th>";
            echo "</tr>\n";

            $tot_assoc = 0;
            for ($tot=0 ; $data=$DB->fetch_assoc($result) ; ) {
               addToNavigateListItems('SoftwareLicense', $data['id']);
               echo "<tr class='tab_bg_2'>";

               if ($license->can($data['id'], "w")) {
                  echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }

               echo "<td><a href='softwarelicense.form.php?id=".$data['id']."'>".$data['name'].
                          (empty($data['name'])?$data['id']:"")."</a></td>";

               if ($software->isRecursive()) {
                  echo "<td>".$data['entity']."</td>";
               }
               echo "<td>".$data['serial']."</td>";
               echo "<td class='right'>".
                      ($data['number']>0?$data['number']."&nbsp;&nbsp;":$LANG['software'][4])."</td>";
               $nb_assoc   = Computer_SoftwareLicense::countForLicense($data['id']);
               $tot_assoc += $nb_assoc;
               echo "<td class='right'>$nb_assoc&nbsp;&nbsp;</td>";
               echo "<td>".$data['typename']."</td>";
               echo "<td>".$data['buyname']."</td>";
               echo "<td>".$data['usename']."</td>";
               echo "<td class='center'>".convDate($data['expire'])."</td>";
               echo "</tr>";

               if ($data['number']<0) {
                  // One illimited license, total is illimited
                  $tot = -1;
               } else if ($tot>=0) {
                  // Not illimited, add the current number
                  $tot += $data['number'];
               }
            }
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='".
                   ($software->isRecursive()?4:3)."' class='right b'>".$LANG['common'][33]."</td>";
            echo "<td class='right b'>".($tot>0?$tot."&nbsp;&nbsp;":$LANG['software'][4])."</td>";
            echo "<td class='right b'>$tot_assoc&nbsp;&nbsp;</td>";
            echo "<td colspan='4' class='center'>";

            if ($canedit) {
               echo "<a href='softwarelicense.form.php?softwares_id=$softwares_id'>".
                      $LANG['software'][8]."</a>";
            }

            echo "</td></tr>";
            echo "</table>\n";

            if ($canedit) {
               openArrowMassive("massiveactionlicense_form$rand", true);
               Dropdown::showForMassiveAction('SoftwareLicense', 0,
                                              array('softwares_id' => $softwares_id));
               closeArrowMassive();

               echo "</form>";
            }

         } else {
            echo $LANG['search'][15];
         }
      }
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
      $options['licenses'] = array($license);
      NotificationEvent::debugEvent($this, $options);
   }

}

?>
