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

// CLASSES Printers

class Printer  extends CommonDBTM {

   // From CommonDBTM
   public $dohistory=true;
   protected $forward_entity_to=array('Infocom','ReservationItem');

   static function getTypeName() {
      global $LANG;

      return $LANG['help'][27];
   }

   function canCreate() {
      return haveRight('printer', 'w');
   }

   function canView() {
      return haveRight('printer', 'r');
   }

   function defineTabs($options=array()) {
      global $LANG,$CFG_GLPI;

      $ong=array();
      if ($this->fields['id'] > 0) {
         if (haveRight("cartridge","r")) {
            $ong[1]=$LANG['Menu'][21];
         }
         if (haveRight("networking","r") || haveRight("computer","r")) {
            $ong[3]=$LANG['title'][27];
         }
         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4]=$LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            if (haveRight("show_all_ticket","1")) {
               $ong[6]=$LANG['title'][28];
            }
            if (haveRight("link","r")) {
               $ong[7]=$LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10]=$LANG['title'][37];
            }
            if (haveRight("reservation_central","r")) {
               $ong[11]=$LANG['Menu'][17];
            }
            $ong[12]=$LANG['title'][38];
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   /**
    * Can I change recusvive flag to false
    * check if there is "linked" object in another entity
    *
    * Overloaded from CommonDBTM
    *
    * @return booleen
    **/
   function canUnrecurs () {
      global $DB, $CFG_GLPI;

      $ID = $this->fields['id'];

      if ($ID<0 || !$this->fields['is_recursive']) {
         return true;
      }
      if (!parent::canUnrecurs()) {
      return false;
      }
      $entities = "(".$this->fields['entities_id'];
      foreach (getAncestorsOf("glpi_entities",$this->fields['entities_id']) as $papa) {
         $entities .= ",$papa";
      }
      $entities .= ")";

      // RELATION : printers -> _port -> _wire -> _port -> device

      // Evaluate connection in the 2 ways
      for ($tabend=array("networkports_id_1"=>"networkports_id_2",
                         "networkports_id_2"=>"networkports_id_1");
           list($enda,$endb)=each($tabend);) {

         $sql = "SELECT `itemtype`, GROUP_CONCAT(DISTINCT `items_id`) AS ids
                 FROM `glpi_networkports_networkports`, `glpi_networkports`
                 WHERE `glpi_networkports_networkports`.$endb = `glpi_networkports`.`id`
                       AND `glpi_networkports_networkports`.$enda IN (SELECT `id`
                                                                      FROM `glpi_networkports`
                                                                      WHERE `itemtype`=".$this->getType()."
                                                                            AND `items_id`='$ID')
                 GROUP BY `itemtype`";
         $res = $DB->query($sql);

         if ($res) {
            while ($data = $DB->fetch_assoc($res)) {
               $itemtable=getTableForItemType($data["itemtype"]);
               $item = new $data["itemtype"];
               // For each itemtype which are entity dependant
               if ($item->isEntityAssign()) {

                  if (countElementsInTable($itemtable, "`id` IN (".$data["ids"].")
                                           AND `entities_id` NOT IN $entities")>0) {
                     return false;
                  }
               }
            }
         }
      }
      return true;
   }

   function prepareInputForAdd($input) {

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"]=$input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      if (isset($input['init_pages_counter'])) {
         $input['init_pages_counter'] = intval($input['init_pages_counter']);
      } else {
         $input['init_pages_counter'] = 0;
      }

      return $input;
   }

   function prepareInputForUpdate($input) {

      if (isset($input['init_pages_counter'])) {
         $input['init_pages_counter'] = intval($input['init_pages_counter']);
      }

      return $input;
   }

   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Infocoms
         $ic= new Infocom();
         if ($ic->getFromDBforDevice($this->getType(),$this->input["_oldID"])) {
            $ic->fields["items_id"]=$this->fields['id'];
            unset ($ic->fields["id"]);
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
            $ic->addToDB();
         }

         // ADD Ports
         $query = "SELECT `id`
                   FROM `glpi_networkports`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $np= new NetworkPort();
               $npv = new NetworkPort_Vlan();
               $np->getFromDB($data["id"]);
               unset($np->fields["id"]);
               unset($np->fields["ip"]);
               unset($np->fields["mac"]);
               unset($np->fields["netpoints_id"]);
               $np->fields["items_id"]=$this->fields['id'];
               $portid=$np->addToDB();
               foreach ($DB->request('glpi_networkports_vlans',
                                     array('networkports_id'=>$data["id"])) as $vlan) {
                  $npv->assignVlan($portid, $vlan['vlans_id']);
               }
            }
         }

         // ADD Contract
         $query = "SELECT `contracts_id`
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $contractitem=new Contract_Item();
            while ($data=$DB->fetch_array($result)) {
               $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                        'itemtype' => $this->getType(),
                                        'items_id' => $this->fields['id']));
            }
         }

         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '".$this->input["_oldID"]."'
                         AND `itemtype` = '".$this->getType()."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $docitem=new Document_Item();
            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype' => $this->getType(),
                                   'items_id' => $this->fields['id']));
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
               $data['_no_auto_action']=true;
               $conn->delete($data);
            }
         }
      }

      $query = "UPDATE
                `glpi_cartridges`
                SET `printers_id` = NULL
                WHERE `printers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }

   /**
    * Print the printer form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
     *@return boolean item found
    **/
   function showForm ($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      $target = $this->getFormURL();
      $withtemplate = '';
      if (isset($options['target'])) {
        $target = $options['target'];
      }
      if (isset($options['withtemplate'])) {
         $withtemplate = $options['withtemplate'];
      }

      if (!haveRight("printer","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }

      if (isset($options['withtemplate']) && $options['withtemplate'] == 2) {
         $template = "newcomp";
         $datestring = $LANG['computers'][14]."&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else if (isset($options['withtemplate']) && $options['withtemplate'] == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14]."&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26]."&nbsp;: ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16].($template?"*":"")."&nbsp;:</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),
                             $this->getType(),$this->fields["entities_id"]);
      autocompletionTextField($this,'name',array('value'=>$objectName));
      echo "</td>\n";
      echo "<td>".$LANG['state'][0]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('State', array('value' => $this->fields["states_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][15]."&nbsp;: </td>\n";
      echo "<td>";
      Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields["entities_id"]));
      echo "</td>\n";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('PrinterType', array('value' => $this->fields["printertypes_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;:</td>\n";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'interface',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>\n";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][21]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this,"contact_num");
      echo "</td>\n";
      echo "<td>".$LANG['common'][22]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('PrinterModel', array('value' => $this->fields["printermodels_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][18]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this,"contact");
      echo "</td>\n";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this,"serial");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][34]."&nbsp;:</td>\n";
      echo "<td>";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>\n";
      echo "<td>".$LANG['common'][20].($template?"*":"")."&nbsp;:</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"),
                             $this->getType(),$this->fields["entities_id"]);
      autocompletionTextField($this,'otherserial',array('value'=>$objectName));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][35]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('Group', array('value'  => $this->fields["groups_id"],
                                    'entity' => $this->fields["entities_id"]));
      echo "</td>\n";
      echo "<td>".$LANG['peripherals'][33]."&nbsp;:</td>";
      echo "<td>";
      if ($this->can($ID,'w')) {
         Dropdown::showGlobalSwitch($target,$withtemplate,$this->fields["id"],
                                    $this->fields["is_global"],
                                    $CFG_GLPI["printers_management_restrict"]);
      } else {
         // Use printers_management_restrict to disallow change this
         Dropdown::showGlobalSwitch($target,$withtemplate,$this->fields["id"],
                                    $this->fields["is_global"]);
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][89]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('Domain', array('value' => $this->fields["domains_id"]));
      echo "</td>";
      echo "<td>".$LANG['setup'][88]."&nbsp;:</td>\n";
      echo "<td>";
      Dropdown::show('Network', array('value' => $this->fields["networks_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['devices'][6]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this,"memory_size");
      echo "</td>";
      echo "<td rowspan='4'>";
      echo $LANG['common'][25]."&nbsp;:</td>\n";
      echo "<td rowspan='4'><textarea cols='45' rows='8' name='comment' >".
            $this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['printers'][30]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField($this,"init_pages_counter");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['printers'][18]."&nbsp;: </td>";
      echo "<td>\n<table>";
      // serial interface
      echo "<tr><td>".$LANG['printers'][14]."</td><td>";
      Dropdown::showYesNo("have_serial",$this->fields["have_serial"]);
      echo "</td></tr>";
      // parallel interface?
      echo "<tr><td>".$LANG['printers'][15]."</td><td>";
      Dropdown::showYesNo("have_parallel",$this->fields["have_parallel"]);
      echo "</td></tr>";
      // USB interface?
      echo "<tr><td>".$LANG['printers'][27]."</td><td>";
      Dropdown::showYesNo("have_usb",$this->fields["have_usb"]);
      echo "</td></tr>";
      // ethernet interface?
      echo "<tr><td>".$LANG['printers'][28]."</td><td>";
      Dropdown::showYesNo("have_ethernet",$this->fields["have_ethernet"]);
      echo "</td></tr>";
      // wifi ?
      echo "<tr><td>".$LANG['printers'][29]."</td><td>";
      Dropdown::showYesNo("have_wifi",$this->fields["have_wifi"]);
      echo "</td></tr></table>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$datestring."&nbsp;".$date;
      if (!$template && !empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13]."&nbsp;: ".$this->fields['template_name'].")";
      }
      echo "</td></tr>\n";

      $this->showFormButtons($options);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   /*
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
    */
   function getSelectLinkedItem () {

      return "SELECT 'Computer', `computers_id`
              FROM `glpi_computers_items`
              WHERE `itemtype` = '".$this->getType()."'
                    AND `items_id` = '" . $this->fields['id']."'";
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common']           = $LANG['common'][32];;

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab+=Location::getSearchOptionsToAdd();

      $tab[4]['table']     = 'glpi_printertypes';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'printertypes_id';
      $tab[4]['name']      = $LANG['common'][17];

      $tab[40]['table']     = 'glpi_printermodels';
      $tab[40]['field']     = 'name';
      $tab[40]['linkfield'] = 'printermodels_id';
      $tab[40]['name']      = $LANG['common'][22];

      $tab[31]['table']     = 'glpi_states';
      $tab[31]['field']     = 'name';
      $tab[31]['linkfield'] = 'states_id';
      $tab[31]['name']      = $LANG['state'][0];

      $tab[5]['table']     = $this->getTable();
      $tab[5]['field']     = 'serial';
      $tab[5]['linkfield'] = 'serial';
      $tab[5]['name']      = $LANG['common'][19];

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'otherserial';
      $tab[6]['linkfield'] = 'otherserial';
      $tab[6]['name']      = $LANG['common'][20];

      $tab[7]['table']     = $this->getTable();
      $tab[7]['field']     = 'contact';
      $tab[7]['linkfield'] = 'contact';
      $tab[7]['name']      = $LANG['common'][18];

      $tab[8]['table']     = $this->getTable();
      $tab[8]['field']     = 'contact_num';
      $tab[8]['linkfield'] = 'contact_num';
      $tab[8]['name']      = $LANG['common'][21];

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][34];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      $tab[19]['table']     = $this->getTable();
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[42]['table']     = $this->getTable();
      $tab[42]['field']     = 'have_serial';
      $tab[42]['linkfield'] = 'have_serial';
      $tab[42]['name']      = $LANG['printers'][14];
      $tab[42]['datatype']  = 'bool';

      $tab[43]['table']     = $this->getTable();
      $tab[43]['field']     = 'have_parallel';
      $tab[43]['linkfield'] = 'have_parallel';
      $tab[43]['name']      = $LANG['printers'][15];
      $tab[43]['datatype']  = 'bool';

      $tab[44]['table']     = $this->getTable();
      $tab[44]['field']     = 'have_usb';
      $tab[44]['linkfield'] = 'have_usb';
      $tab[44]['name']      = $LANG['printers'][27];
      $tab[44]['datatype']  = 'bool';

      $tab[45]['table']     = $this->getTable();
      $tab[45]['field']     = 'have_ethernet';
      $tab[45]['linkfield'] = 'have_ethernet';
      $tab[45]['name']      = $LANG['printers'][28];
      $tab[45]['datatype']  = 'bool';

      $tab[46]['table']     = $this->getTable();
      $tab[46]['field']     = 'have_wifi';
      $tab[46]['linkfield'] = 'have_wifi';
      $tab[46]['name']      = $LANG['printers'][29];
      $tab[46]['datatype']  = 'bool';

      $tab[90]['table']     = $this->getTable();
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[32]['table']     = 'glpi_networks';
      $tab[32]['field']     = 'name';
      $tab[32]['linkfield'] = 'networks_id';
      $tab[32]['name']      = $LANG['setup'][88];

      $tab[33]['table']     = 'glpi_domains';
      $tab[33]['field']     = 'name';
      $tab[33]['linkfield'] = 'domains_id';
      $tab[33]['name']      = $LANG['setup'][89];

      $tab[23]['table']     = 'glpi_manufacturers';
      $tab[23]['field']     = 'name';
      $tab[23]['linkfield'] = 'manufacturers_id';
      $tab[23]['name']      = $LANG['common'][5];

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = $LANG['common'][10];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = 'is_recursive';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      return $tab;
   }
}

?>