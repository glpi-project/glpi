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

/// Software class
class Software extends CommonDBTM {


   // From CommonDBTM
   public $dohistory = true;
   protected $forward_entity_to=array('Infocom','SoftwareVersion','ReservationItem');

   static function getTypeName() {
      global $LANG;

      return $LANG['help'][31];
   }

   function canCreate() {
      return haveRight('software', 'w');
   }

   function canView() {
      return haveRight('software', 'r');
   }

   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong=array();
      if ($this->fields['id'] > 0) {
         $ong[1] = $LANG['software'][5]."/".$LANG['software'][11];
         if (empty ($withtemplate)) {
            $ong[2] = $LANG['software'][19];
         }
         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4] = $LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5] = $LANG['Menu'][27];
         }

         if (!isset($options['withtemplate']) || empty($options['withtemplate'])) {
            if (haveRight("show_all_ticket","1")) {
               $ong[6] = $LANG['title'][28];
            }
            if (haveRight("link","r")) {
               $ong[7] = $LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10] = $LANG['title'][37];
            }
            if (haveRight("reservation_central", "r")) {
               $ong[11] = $LANG['Menu'][17];
            }
            $ong[12] = $LANG['title'][38];
            if ($this->isRecursive() && $this->can($this->fields['id'],'w')) {
               $ong[21] = $LANG['software'][47];
            }
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   function prepareInputForUpdate($input) {

      if (isset ($input['is_update']) && ! $input['is_update']) {
         $input['softwares_id'] = 0;
      }
      return $input;
   }

   function prepareInputForAdd($input) {

      if (isset ($input['is_update']) && !$input['is_update']) {
         $input['softwares_id'] = 0;
      }

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"]=$input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      //If category was not set by user (when manually adding a user)
      if (!isset($input["softwarecategories_id"]) || !$input["softwarecategories_id"]) {
         $softcatrule = new RuleSoftwareCategoryCollection;
         $result = $softcatrule->processAllRules(null,null,$input);
         if (!empty($result) && isset($result["softwarecategories_id"])) {
            $input["softwarecategories_id"]=$result["softwarecategories_id"];
         } else {
            $input["softwarecategories_id"]=0;
         }
      }
      return $input;
   }

   function post_addItem() {
      global $DB;

      // Manage add from template
      if (isset($this->input["_oldID"])) {
         // ADD Infocoms
         $ic = new Infocom();
         if ($ic->getFromDBforDevice($this->getType(), $this->input["_oldID"])) {
            $ic->fields["items_id"] = $this->fields['id'];
            unset ($ic->fields["id"]);
            if (isset($ic->fields["immo_number"])) {
               $ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1,
                                                     'Infocom',$this->input['entities_id']);
            }
            if (empty($ic->fields['use_date'])) {
               unset($ic->fields['use_date']);
            }
            if (empty($ic->fields['buy_date'])) {
               unset($ic->fields['buy_date']);
            }
            $ic->fields["entities_id"]=$this->fields['entities_id'];
            $ic->fields["is_recursive"]=$this->fields['is_recursive'];
            $ic->addToDB();
         }

         // ADD Contract
         $query = "SELECT `contracts_id`
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '" . $this->input["_oldID"] . "'
                         AND `itemtype` = '" . $this->getType() . "'";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
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
                   WHERE `items_id` = '" . $this->input["_oldID"] . "'
                         AND `itemtype` = '" . $this->getType() . "'";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
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

      // Delete all licenses
      $query2 = "SELECT `id`
                 FROM `glpi_softwarelicenses`
                 WHERE `softwares_id` = '".$this->fields['id']."'";

      if ($result2 = $DB->query($query2)) {
         if ($DB->numrows($result2)) {
            $lic = new SoftwareLicense;
            while ($data = $DB->fetch_array($result2)) {
               $lic->delete(array("id" => $data["id"]));
            }
         }
      }

      $version = new SoftwareVersion();
      $version->cleanDBonItemDelete(__CLASS__, $this->fields['id']);
   }

   /**
    * Print the Software form
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

      // Show Software or blank form

      if (!haveRight("software", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
      }
      $canedit=$this->can($ID,'w');

      if (isset($options['withtemplate']) && $options['withtemplate'] == 2) {
         $template = "newcomp";
         $datestring = $LANG['computers'][14] . "&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else if (isset($options['withtemplate']) && $options['withtemplate'] == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14] . "&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26] . "&nbsp;: ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      $this->showTabs($options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][16] . "&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>" . $LANG['common'][5] . " / ".$LANG['software'][6]."&nbsp;:</td><td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][15] . "&nbsp;:</td><td>";
      Dropdown::show('Location', array('value'  => $this->fields["locations_id"],
                                       'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>" . $LANG['common'][36] . "&nbsp;:</td><td>";
      Dropdown::show('SoftwareCategory', array('value' => $this->fields["softwarecategories_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][10] . "&nbsp;:</td><td>";
      User::dropdown(array('name'   => 'users_id_tech',
                           'value'  => $this->fields["users_id_tech"],
                           'right'  => 'interface',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>" . $LANG['software'][46] . "&nbsp;:</td><td>";
      Dropdown::showYesNo('is_helpdesk_visible',$this->fields['is_helpdesk_visible']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >" . $LANG['common'][34] . "&nbsp;:</td>";
      echo "<td >";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td><td colspan='2'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][35] . "&nbsp;:</td><td>";
      Dropdown::show('Group', array('value'  => $this->fields["groups_id"],
                                    'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td rowspan='3' class='middle'>".$LANG['common'][25] . "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='3'><textarea cols='45' rows='5' name='comment' >" .
             $this->fields["comment"] . "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$datestring."&nbsp;".$date;
      if (!$template && !empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13]."&nbsp;: ".$this->fields['template_name'].")";
      }
      echo "</td></tr>\n";

      // UPDATE
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['software'][29] . "&nbsp;:</td><td colspan='3'>";
      Dropdown::showYesNo("is_update",$this->fields['is_update']);
      echo "&nbsp;" . $LANG['pager'][2] . "&nbsp;";
      Dropdown::show('Software', array('value' => $this->fields["softwares_id"]));
      echo "</td></tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getEmpty() {
      global $CFG_GLPI;
      parent::getEmpty();

      $this->fields["is_helpdesk_visible"]= $CFG_GLPI["default_software_helpdesk_visible"];
   }

   function getSearchOptions() {
      global $LANG;

      // Only use for History (not by search Engine)
      $tab = array();

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


      $tab[7]['table']     = 'glpi_softwarelicenses';
      $tab[7]['field']     = 'name';
      $tab[7]['linkfield'] = '';
      $tab[7]['name']      = $LANG['common'][19];

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = $this->getTable();
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[62]['table']     = 'glpi_softwarecategories';
      $tab[62]['field']     = 'name';
      $tab[62]['linkfield'] = 'softwarecategories_id';
      $tab[62]['name']      = $LANG['common'][36];

      $tab[19]['table']     = $this->getTable();
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[23]['table']     = 'glpi_manufacturers';
      $tab[23]['field']     = 'name';
      $tab[23]['linkfield'] = 'manufacturers_id';
      $tab[23]['name']      = $LANG['common'][5];

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = $LANG['common'][10];

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][34];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      $tab[61]['table']     = $this->getTable();
      $tab[61]['field']     = 'is_helpdesk_visible';
      $tab[61]['linkfield'] = 'is_helpdesk_visible';
      $tab[61]['name']      = $LANG['software'][46];
      $tab[61]['datatype']  = 'bool';

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      $tab[72]['table']        = 'glpi_computers_softwareversions';
      $tab[72]['field']        = 'count';
      $tab[72]['linkfield']    = '';
      $tab[72]['name']       = $LANG['tracking'][29]." - ".$LANG['software'][19];
      $tab[72]['forcegroupby'] = true;
      $tab[72]['usehaving']    = true;
      $tab[72]['datatype']     = 'number';
      $tab[72]['nometa']       = true;

      $tab[86]['table']     = $this->getTable();
      $tab[86]['field']     = 'is_recursive';
      $tab[86]['linkfield'] = '';
      $tab[86]['name']      = $LANG['entity'][9];
      $tab[86]['datatype']  = 'bool';

      $tab['versions'] = $LANG['software'][5];

      $tab[5]['table']        = 'glpi_softwareversions';
      $tab[5]['field']        = 'name';
      $tab[5]['linkfield']    = '';
      $tab[5]['name']         = $LANG['common'][16]." - ".$LANG['software'][5];
      $tab[5]['forcegroupby'] = true;

      $tab[31]['table']        = 'glpi_states';
      $tab[31]['field']        = 'name';
      $tab[31]['linkfield']    = '';
      $tab[31]['name']         = $LANG['state'][0];
      $tab[31]['forcegroupby'] = true;

      $tab[170]['table']        = 'glpi_softwareversions';
      $tab[170]['field']        = 'comment';
      $tab[170]['linkfield']    = '';
      $tab[170]['name']         = $LANG['common'][25]." - ".$LANG['software'][5];
      $tab[170]['forcegroupby'] = true;
      $tab[170]['datatype']     = 'text';

      $tab[4]['table']     = 'glpi_operatingsystems';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'operatingsystems_id';
      $tab[4]['name']      = $LANG['setup'][5]." - ".$LANG['software'][5];
      $tab[4]['forcegroupby'] = true;


      $tab['license'] = $LANG['software'][11];

      $tab[160]['table']        = 'glpi_softwarelicenses';
      $tab[160]['field']        = 'name';
      $tab[160]['linkfield']    = '';
      $tab[160]['name']         = $LANG['common'][16]." - ".$LANG['software'][11];
      $tab[160]['forcegroupby'] = true;

      $tab[161]['table']        = 'glpi_softwarelicenses';
      $tab[161]['field']        = 'serial';
      $tab[161]['linkfield']    = '';
      $tab[161]['name']         = $LANG['common'][19];
      $tab[161]['forcegroupby'] = true;

      $tab[162]['table']        = 'glpi_softwarelicenses';
      $tab[162]['field']        = 'otherserial';
      $tab[162]['linkfield']    = '';
      $tab[162]['name']         = $LANG['common'][20];
      $tab[162]['forcegroupby'] = true;

      $tab[163]['table']        = 'glpi_softwarelicenses';
      $tab[163]['field']        = 'number';
      $tab[163]['linkfield']    = '';
      $tab[163]['name']      = $LANG['tracking'][29]." - ".$LANG['software'][11];
      $tab[163]['forcegroupby'] = true;
      $tab[163]['usehaving']    = true;
      $tab[163]['datatype']     = 'number';

      $tab[164]['table']        = 'glpi_softwarelicensetypes';
      $tab[164]['field']        = 'name';
      $tab[164]['linkfield']    = '';
      $tab[164]['name']         = $LANG['software'][30];
      $tab[164]['forcegroupby'] = true;

      $tab[165]['table']        = 'glpi_softwarelicenses';
      $tab[165]['field']        = 'comment';
      $tab[165]['linkfield']    = '';
      $tab[165]['name']         = $LANG['common'][25]." - ".$LANG['software'][11];
      $tab[165]['forcegroupby'] = true;
      $tab[165]['datatype']     = 'text';

      $tab[166]['table']        = 'glpi_softwarelicenses';
      $tab[166]['field']        =  'expire';
      $tab[166]['linkfield']    ='';
      $tab[166]['name']         = $LANG['software'][32];
      $tab[166]['forcegroupby'] = true;
      $tab[166]['datatype']     = 'date';

      return $tab;
   }

   /**
    * Make a select box for  software to install
    *
    *
    * @param $myname select name
    * @param $massiveaction is it a massiveaction select ?
    * @param $entity_restrict Restrict to a defined entity
    * @return nothing (print out an HTML select box)
    */
   static function dropdownSoftwareToInstall($myname,$entity_restrict,$massiveaction=0) {
      global $CFG_GLPI;

      $rand=mt_rand();
      $use_ajax=false;

      if ($CFG_GLPI["use_ajax"]) {
         if (countElementsInTableForEntity("glpi_softwares",$entity_restrict)
             > $CFG_GLPI["ajax_limit_count"]) {
            $use_ajax=true;
         }
      }

      $params=array('searchText'       => '__VALUE__',
                    'myname'           => $myname,
                    'entity_restrict'  => $entity_restrict);

      $default="<select name='$myname'><option value='0'>".DROPDOWN_EMPTY_VALUE." </option></select>";
      ajaxDropdown($use_ajax,"/ajax/dropdownSelectSoftware.php",$params,$default,$rand);

      return $rand;
   }

   /**
    * Create a new software
    * @param name the software's name
    * @param manufacturer the software's manufacturer
    * @param entity the entity in which the software must be added
    * @param comment
    * @return the software's ID
    */
   function addSoftware($name, $manufacturer, $entity, $comment = '') {
      global $DB, $CFG_GLPI;

      $manufacturer_id = 0;
      if ($manufacturer != '') {
         $manufacturer_id = Dropdown::importExternal('Manufacturer', $manufacturer);
      }

      $sql = "SELECT `id`
              FROM `glpi_softwares`
              WHERE `manufacturers_id` = '$manufacturer_id'
                    AND `name` = '$name' " .
                    getEntitiesRestrictRequest('AND', 'glpi_softwares', 'entities_id', $entity,
                                               true);

      $res_soft = $DB->query($sql);
      if ($soft = $DB->fetch_array($res_soft)) {
         $id = $soft["id"];
      } else {
         $input["name"] = $name;
         $input["manufacturers_id"] = $manufacturer_id;
         $input["entities_id"] = $entity;
         // No comment
         $input["is_helpdesk_visible"] = $CFG_GLPI["default_software_helpdesk_visible"];

         //Process software's category rules
         $softcatrule = new RuleSoftwareCategoryCollection;
         $result = $softcatrule->processAllRules(null, null, $input);
         if (!empty ($result) && isset ($result["softwarecategories_id"])) {
            $input["softwarecategories_id"] = $result["softwarecategories_id"];
         } else {
            $input["softwarecategories_id"] = 0;
         }

         $id = $this->add($input);
      }
      return $id;
   }


   /**
    * Add a Software. If already exist in trash restore it
    * @param name the software's name
    * @param manufacturer the software's manufacturer
    * @param entity the entity in which the software must be added
    * @param comment comment
    */
   function addOrRestoreFromTrash($name,$manufacturer,$entity,$comment='') {
      global $DB;

      //Look for the software by his name in GLPI for a specific entity
      $query_search = "SELECT `glpi_softwares`.`id`, `glpi_softwares`.`is_deleted`
                       FROM `glpi_softwares`
                       WHERE `name` = '$name'
                             AND `is_template` = '0'
                             AND `entities_id` = '$entity'";

      $result_search = $DB->query($query_search);

      if ($DB->numrows($result_search) > 0) {
         //Software already exists for this entity, get his ID
         $data = $DB->fetch_array($result_search);
         $ID = $data["id"];

         // restore software
         if ($data['is_deleted']) {
            $this->removeFromTrash($ID);
         }
      } else {
         $ID = 0;
      }

      if (!$ID) {
         $ID = $this->addSoftware($name, $manufacturer, $entity, $comment);
      }
      return $ID;
   }


   /**
    * Put software in trash because it's been removed by GLPI software dictionnary
    *
    * @param $ID  the ID of the software to put in trash
    * @param $comment the comment to add to the already existing software's comment
    *
    * @return boolean (success)
    */
   function putInTrash($ID, $comment = '') {
      global $LANG,$CFG_GLPI;

      $this->getFromDB($ID);
      $input["id"] = $ID;
      $input["is_deleted"] = 1;

      //change category of the software on deletion (if defined in glpi_configs)
      if (isset($CFG_GLPI["softwarecategories_id_ondelete"])
          && $CFG_GLPI["softwarecategories_id_ondelete"] != 0) {

         $input["softwarecategories_id"] = $CFG_GLPI["softwarecategories_id_ondelete"];
      }

      //Add dictionnary comment to the current comment
      $input["comment"] = ($this->fields["comment"] != '' ? "\n" : '') . $comment;

      return $this->update($input);
   }


   /**
    * Restore a software from trash
    *
    * @param $ID  the ID of the software to put in trash
    *
    * @return boolean (success)
    */
   function removeFromTrash($ID) {

      $res = $this->restore(array("id" => $ID));

      $softcatrule = new RuleSoftwareCategoryCollection;
      $result = $softcatrule->processAllRules(null, null, $this->fields);

      if (!empty($result)
          && isset($result['softwarecategories_id'])
          && $result['softwarecategories_id']!=$this->fields['softwarecategories_id']) {

         $this->update(array('id'                    => $ID,
                             'softwarecategories_id' => $result['softwarecategories_id']));
      }

      return $res;
   }

   /**
    * Show softwares candidates to be merged with the current
    *
    * @return nothing
    */
   function showMergeCandidates() {
      global $DB, $CFG_GLPI, $LANG;

      $ID = $this->getField('id');
      $this->check($ID,"w");
      $rand=mt_rand();

      echo "<div class='center'>";
      $sql = "SELECT `glpi_softwares`.`id`,
                     `glpi_softwares`.`name`,
                     `glpi_entities`.`completename` AS entity
              FROM `glpi_softwares`
              LEFT JOIN `glpi_entities` ON (`glpi_softwares`.`entities_id` = `glpi_entities`.`id`)
              WHERE (`glpi_softwares`.`id` != '$ID'
                     AND `glpi_softwares`.`name` = '".addslashes($this->fields["name"])."'
                     AND `glpi_softwares`.`is_deleted` = '0'
                     AND `glpi_softwares`.`is_template` = '0' " .
                         getEntitiesRestrictRequest('AND', 'glpi_softwares','entities_id',
                                       getSonsOf("glpi_entities",$this->fields["entities_id"]),false).")
              ORDER BY `entity`";
      $req = $DB->request($sql);

      if ($req->numrows()) {
         $link=getItemTypeFormURL('Software');
         echo "<form method='post' name='mergesoftware_form$rand' id='mergesoftware_form$rand' action='".
                $link."'>";
         echo "<table class='tab_cadre_fixehov'><tr><th>&nbsp;</th>";
         echo "<th>".$LANG['common'][16]."</th>";
         echo "<th>".$LANG['entity'][0]."</th>";
         echo "<th>".$LANG['software'][19]."</th>";
         echo "<th>".$LANG['software'][11]."</th></tr>";

         foreach($req as $data) {
            echo "<tr class='tab_bg_2'>";
            echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
            echo "<td<a href='".$link."?id=".
                      $data["id"]."'>".$data["name"]."</a></td>";
            echo "<td>".$data["entity"]."</td>";
            echo "<td class='right'>".Computer_SoftwareVersion::countForSoftware($data["id"])."</td>";
            echo "<td class='right'>".SoftwareLicense::countForSoftware($data["id"])."</td></tr>\n";
         }
         echo "</table>\n";

         openArrowMassive("mergesoftware_form$rand",true);
         echo "<input type='hidden' name='id' value='$ID'>";
         closeArrowMassive('mergesoftware', $LANG['software'][48]);

         echo "</form>";
      } else {
         echo $LANG['search'][15];
      }

      echo "</div>";
   }

   /**
    * Merge softwares with current
    *
    * @param $item array of software ID to be merged
    *
    * @return boolean about success
    */
   function merge($item) {
      global $DB, $LANG;

      $ID = $this->getField('id');

      echo "<div class='center'>";
      echo "<table class='tab_cadrehov'><tr><th>".$LANG['software'][47]."</th></tr>";
      echo "<tr class='tab_bg_2'><td>";
      createProgressBar($LANG['rulesengine'][90]);
      echo "</td></tr></table></div>\n";

      $item=array_keys($item);

      // Search for software version
      $req = $DB->request("glpi_softwareversions", array("softwares_id"=>$item));
      $i=0;
      if ($nb=$req->numrows()) {
         foreach ($req as $from) {
            $found=false;
            foreach ($DB->request("glpi_softwareversions", array("softwares_id"=>$ID,
                                                                  "name"=>$from["name"])) as $dest) {
               // Update version ID on License
               $sql = "UPDATE
                       `glpi_softwarelicenses`
                       SET `softwareversions_id_buy` = '".$dest["id"]."'
                       WHERE `softwareversions_id_buy` = '".$from["id"]."'";
               $DB->query($sql);

               $sql = "UPDATE
                       `glpi_softwarelicenses`
                       SET `softwareversions_id_use` = '".$dest["id"]."'
                       WHERE `softwareversions_id_use` = '".$from["id"]."'";
               $DB->query($sql);

               // Move installation to existing version in destination software
               $sql = "UPDATE
                       `glpi_computers_softwareversions`
                       SET `softwareversions_id` = '".$dest["id"]."'
                       WHERE `softwareversions_id` = '".$from["id"]."'";
               $found=$DB->query($sql);
            }
            if ($found) {
               // Installation has be moved, delete the source version
               $sql = "DELETE
                       FROM `glpi_softwareversions`
                       WHERE `id` = '".$from["id"]."'";
            } else {
               // Move version to destination software
               $sql = "UPDATE
                       `glpi_softwareversions`
                       SET `softwares_id` = '$ID',
                           `entities_id` = '".$this->getField('entities_id')."'
                       WHERE `id` = '".$from["id"]."'";
            }
            if ($DB->query($sql)) {
               $i++;
            }
            changeProgressBarPosition($i,$nb+1);
         }
      }
      // Move software license
      $sql = "UPDATE
              `glpi_softwarelicenses`
              SET `softwares_id` = '$ID'
              WHERE `softwares_id` IN ('".implode("','",$item)."')";
      if ($DB->query($sql)) {
         $i++;
      }
      if ($i==($nb+1)) {
         //error_log ("All merge operations ok.");
         foreach ($item as $old) {
            $soft = new Software();
            $soft->putInTrash($old,$LANG['software'][49]);
         }
      }
      changeProgressBarPosition($i,$nb+1,$LANG['rulesengine'][91]);
      return $i==($nb+1);
   }


}

?>
