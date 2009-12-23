<?php


/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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
   public $table = 'glpi_softwares';
   public $type = 'Software';
   public $dohistory = true;

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

   function defineTabs($ID,$withtemplate) {
      global $LANG, $CFG_GLPI;

      $ong=array();
      if ($ID > 0 ) {
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

         if (empty ($withtemplate)) {
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
            if ($this->isRecursive()) {
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

   function post_addItem($newID, $input) {
      global $DB;

      // Manage add from template
      if (isset($input["_oldID"])) {
         // ADD Infocoms
         $ic = new Infocom();
         if ($ic->getFromDBforDevice($this->type, $input["_oldID"])) {
            $ic->fields["items_id"] = $newID;
            unset ($ic->fields["id"]);
            if (isset($ic->fields["immo_number"])) {
               $ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1,
                                                     'Infocom',$input['entities_id']);
            }
            if (empty($ic->fields['use_date'])) {
               unset($ic->fields['use_date']);
            }
            if (empty($ic->fields['buy_date'])) {
               unset($ic->fields['buy_date']);
            }
            $ic->addToDB();
         }

         // ADD Contract
         $query = "SELECT `contracts_id`
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '" . $input["_oldID"] . "'
                         AND `itemtype` = '" . $this->type . "'";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $contractitem=new Contract_Item();
            while ($data=$DB->fetch_array($result)) {
               $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                        'itemtype' => $this->type,
                                        'items_id' => $newID));
            }
         }

         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '" . $input["_oldID"] . "'
                         AND `itemtype` = '" . $this->type . "'";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            $docitem=new Document_Item();
            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype' => $this->type,
                                   'items_id' => $newID));
            }
         }
      }
   }

   function cleanDBonPurge($ID) {
      global $DB, $CFG_GLPI;
logDebug("Software::cleanDBonPurge($ID)");
      // Delete all licenses
      $query2 = "SELECT `id`
                 FROM `glpi_softwarelicenses`
                 WHERE `softwares_id` = '$ID'";

      if ($result2 = $DB->query($query2)) {
         if ($DB->numrows($result2)) {
            $lic = new SoftwareLicense;
            while ($data = $DB->fetch_array($result2)) {
               $lic->delete(array("id" => $data["id"]));
            }
         }
      }

      $version = new SoftwareVersion();
      $version->cleanDBonItemDelete(__CLASS__, $ID);
   }

   /**
    * Print the Software form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the item to print
    *@param $withtemplate integer template or basic item
    *
    *@return boolean item found
    **/
   function showForm($target, $ID, $withtemplate = '') {
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

      if (!empty ($withtemplate) && $withtemplate == 2) {
         $template = "newcomp";
         $datestring = $LANG['computers'][14] . "&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else if (!empty ($withtemplate) && $withtemplate == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14] . "&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26] . "&nbsp;: ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      $this->showTabs($ID, $withtemplate);
      $this->showFormHeader($target, $ID, $withtemplate, 2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][16] . "&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name", $this->table, "name", $this->fields["name"], 40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>" . $LANG['common'][5] . "&nbsp;:</td><td>";
      Dropdown::show('Manufacturer', array('value' => $this->fields["manufacturers_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][15] . "&nbsp;:</td><td>";
      Dropdown::show('Location',
                     array('value'  => $this->fields["locations_id"],
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
      echo "<td>" . $LANG['software'][3] . "&nbsp;:</td><td>";
      Dropdown::show('OperatingSystem', array('value' => $this->fields["operatingsystems_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >" . $LANG['common'][34] . "&nbsp;:</td>";
      echo "<td >";
      User::dropdown(array('value'  => $this->fields["users_id"],
                           'entity' => $this->fields["entities_id"],
                           'right'  => 'all'));
      echo "</td>";
      echo "<td>" . $LANG['software'][46] . "&nbsp;:</td><td>";
      Dropdown::showYesNo('is_helpdesk_visible',$this->fields['is_helpdesk_visible']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][35] . "&nbsp;:</td><td>";
      Dropdown::show('Group',
                     array('value'  => $this->fields["groups_id"],
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

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

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

      $tab[1]['table']         = 'glpi_softwares';
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'Software';

      $tab[2]['table']     = 'glpi_softwares';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = 'glpi_locations';
      $tab[3]['field']     = 'completename';
      $tab[3]['linkfield'] = 'locations_id';
      $tab[3]['name']      = $LANG['common'][15];

      $tab[4]['table']     = 'glpi_operatingsystems';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'operatingsystems_id';
      $tab[4]['name']      = $LANG['software'][3];

      $tab[7]['table']     = 'glpi_softwarelicenses';
      $tab[7]['field']     = 'name';
      $tab[7]['linkfield'] = '';
      $tab[7]['name']      = $LANG['common'][19];

      $tab[16]['table']     = 'glpi_softwares';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = 'glpi_softwares';
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[62]['table']     = 'glpi_softwarecategories';
      $tab[62]['field']     = 'name';
      $tab[62]['linkfield'] = 'softwarecategories_id';
      $tab[62]['name']      = $LANG['common'][36];

      $tab[19]['table']     = 'glpi_softwares';
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

      $tab[61]['table']     = 'glpi_softwares';
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


      $tab['tracking'] = $LANG['title'][24];

      $tab[60]['table']        = 'glpi_tickets';
      $tab[60]['field']        = 'count';
      $tab[60]['linkfield']    = '';
      $tab[60]['name']         = $LANG['stats'][13];
      $tab[60]['forcegroupby'] = true;
      $tab[60]['usehaving']    = true;
      $tab[60]['datatype']     = 'number';

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

      $default="<select name='$myname'><option value='0'>------</option></select>";
      ajaxDropdown($use_ajax,"/ajax/dropdownSelectSoftware.php",$params,$default,$rand);

      return $rand;
   }

   // TODO : this functions seems not used
   // If really needed, move it to Computer_SoftwareVersion class
   /**
   * Count Installations of a software
   *
   * @return integer installation number
   function countInstallations() {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_softwareversions`
                WHERE `softwares_id` = '".$this->fields["id"]."'";

      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         return $number;
      }
      return false;
   }
   */

   // TODO : this functions seems not used
   /**
    * Count Installations and Licenses of a software and create string to display
    *
    * @param $softwares_id ID of the software
    * @param $nohtml do not use HTML to highlight ?
    *
    * @return string contains counts
   static function countInstallations($softwares_id, $nohtml = 0) {
      global $DB, $CFG_GLPI, $LANG;

      $installed = Computer_SoftwareVersion::countForSoftware($softwares_id);
      $out="";
      if (!$nohtml) {
         $out .= $LANG['software'][19] . ": <strong>$installed</strong>";
      } else {
         $out .= $LANG['software'][19] . ": $installed";
      }

      $total = SoftwareLicense::countForSoftware($softwares_id);

      if ($total < 0 ) {
         if (!$nohtml) {
            $out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": <strong>".$LANG['software'][4]."</strong>";
         } else {
            $out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": ".$LANG['software'][4];
         }
      } else {
         if ($total >=$installed) {
            $color = "green";
         } else {
            $color = "blue";
         }

         if (!$nohtml) {
            $total = "<span class='$color'>$total</span>";
            $out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": <strong>$total</strong>";
         } else {
            $out .= "&nbsp;&nbsp;".$LANG['software'][11] . ": ".$total;
         }
      }

      return $out;
   }
    */
}

?>
