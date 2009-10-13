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

   /**
    * Constructor
   **/
   function __construct() {
      $this->table = "glpi_softwares";
      $this->type = SOFTWARE_TYPE;
      $this->dohistory = true;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
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
      if (!isset($input["softwarescategories_id"]) || !$input["softwarescategories_id"]) {
         $softcatrule = new SoftwareCategoriesRuleCollection;
         $result = $softcatrule->processAllRules(null,null,$input);
         if (!empty($result) && isset($result["softwarescategories_id"])) {
            $input["softwarescategories_id"]=$result["softwarescategories_id"];
         } else {
            $input["softwarescategories_id"]=0;
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
                                                     INFOCOM_TYPE,$input['entities_id']);
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
            while ($data = $DB->fetch_array($result)) {
               addDeviceContract($data["contracts_id"], $this->type, $newID);
            }
         }

         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '" . $input["_oldID"] . "'
                         AND `itemtype` = '" . $this->type . "'";
         $result = $DB->query($query);
         if ($DB->numrows($result) > 0) {
            while ($data = $DB->fetch_array($result)) {
               addDeviceDocument($data["documents_id"], $this->type, $newID);
            }
         }
      }
   }

   function cleanDBonPurge($ID) {
      global $DB, $CFG_GLPI;

      // Delete all licenses
      $query2 = "SELECT `id`
                 FROM `glpi_softwareslicenses`
                 WHERE `softwares_id` = '$ID'";

      if ($result2 = $DB->query($query2)) {
         if ($DB->numrows($result2)) {
            $lic = new SoftwareLicense;
            while ($data = $DB->fetch_array($result2)) {
               $lic->delete(array("id" => $data["id"]));
            }
         }
      }

      // Delete all versions
      $query2 = "SELECT `id`
                 FROM `glpi_softwaresversions`
                 WHERE `softwares_id` = '$ID'";

      if ($result2 = $DB->query($query2)) {
         if ($DB->numrows($result2)) {
            $vers = new SoftwareVersion;
            while ($data = $DB->fetch_array($result2)) {
               $vers->delete(array("id" => $data["id"]));
            }
         }
      }
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
         $this->getEmpty();
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

      $this->showTabs($ID, $withtemplate, getActiveTab($this->type));
      $this->showFormHeader($target, $ID, $withtemplate, 2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][16] . "&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name", $this->table, "name", $this->fields["name"], 40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>" . $LANG['common'][36] . "&nbsp;:</td><td>";
      dropdownValue("glpi_softwarescategories", "softwarescategories_id",
                    $this->fields["softwarescategories_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][15] . "&nbsp;:</td><td>";
      dropdownValue("glpi_locations", "locations_id", $this->fields["locations_id"], 1,
                    $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>" . $LANG['software'][3] . "&nbsp;:</td><td>";
      dropdownValue("glpi_operatingsystems", "operatingsystems_id",
                    $this->fields["operatingsystems_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][10] . "&nbsp;:</td><td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"], "interface", 1,
                      $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>" . $LANG['software'][46] . "&nbsp;:</td><td>";
      dropdownYesNo('is_helpdesk_visible',$this->fields['is_helpdesk_visible']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][5] . "&nbsp;:</td><td>";
      dropdownValue("glpi_manufacturers", "manufacturers_id", $this->fields["manufacturers_id"]);
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".$LANG['common'][25] . "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='4'><textarea cols='45' rows='6' name='comment' >" .
             $this->fields["comment"] . "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >" . $LANG['common'][34] . "&nbsp;:</td>";
      echo "<td >";
      dropdownAllUsers("users_id", $this->fields["users_id"], 1, $this->fields["entities_id"]);
      echo "</td>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['common'][35] . "&nbsp;:</td><td>";
      dropdownValue("glpi_groups", "groups_id", $this->fields["groups_id"], 1,
                    $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$datestring."&nbsp;".$date;
      if (!$template && !empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13]."&nbsp;: ".$this->fields['template_name'].")";
      }
      echo "</td></tr>\n";

      // UPDATE
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . $LANG['software'][29] . "&nbsp;:</td><td colspan='3'>";
      dropdownYesNo("is_update",$this->fields['is_update']);
      echo "&nbsp;" . $LANG['pager'][2] . "&nbsp;";
      dropdownValue("glpi_softwares", "softwares_id", $this->fields["softwares_id"]);
      echo "</td></tr>\n";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }


   // SPECIFIC FUNCTIONS
   /**
   * Count Installations of a software
   *
   * @return integer installation number
   */
   function countInstallations() {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_softwaresversions`
                WHERE `softwares_id` = '".$this->fields["id"]."'";

      if ($result = $DB->query($query)) {
         $number = $DB->numrows($result);
         return $number;
      }
      return false;
   }

   function getEmpty() {
      global $CFG_GLPI;
      parent::getEmpty();

      $this->fields["is_helpdesk_visible"]= $CFG_GLPI["default_software_helpdesk_visible"];
   }
}


/// Version class
class SoftwareVersion extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct() {
      $this->table = "glpi_softwaresversions";
      $this->type = SOFTWAREVERSION_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
      $this->dohistory = true;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      // Delete Installations
      $query2 = "DELETE
                 FROM `glpi_computers_softwaresversions`
                 WHERE `softwaresversions_id` = '$ID'";
      $DB->query($query2);
   }

   function prepareInputForAdd($input) {

      // Not attached to software -> not added
      if (!isset($input['softwares_id']) || $input['softwares_id'] <= 0) {
         return false;
      }
      return $input;
   }

   function getEntityID () {

      $soft=new Software();
      $soft->getFromDB($this->fields["softwares_id"]);
      return $soft->getEntityID();
   }

   function isRecursive () {

      $soft=new Software();
      $soft->getFromDB($this->fields["softwares_id"]);
      return $soft->isRecursive();
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG, $CFG_GLPI;

      $ong[1] = $LANG['title'][26];
      if ($ID) {
         $ong[2] = $LANG['software'][19];
         $ong[12] = $LANG['title'][38];
      }
      return $ong;
   }

   /**
    * Print the Software / version form
    *
    *@param $target form target
    *@param $ID Integer : Id of the version or the template to print
    *@param $softwares_id ID of the software for add process
    *
    *@return true if displayed  false if item not found or not right to display
    **/
   function showForm($target,$ID,$softwares_id=-1) {
      global $CFG_GLPI,$LANG;

      if (!haveRight("software","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, false, getActiveTab($this->type),array(),
                      "softwares_id=".$this->fields['softwares_id']);
      $this->showFormHeader($target,$ID,'',2);

      echo "<tr class='tab_bg_1'><td>".$LANG['help'][31]."&nbsp;:</td>";
      echo "<td>";
      if ($ID>0) {
         $softwares_id=$this->fields["softwares_id"];
      } else {
         echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
      }
      echo "<a href='software.form.php?id=".$softwares_id."'>".
             getDropdownName("glpi_softwares",$softwares_id)."</a>";
      echo "</td>";
      echo "<td rowspan='3' class='middle'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='3'>";
      echo "<textarea cols='45' rows='3' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . $LANG['state'][0] . "&nbsp;:</td><td>";
      dropdownValue("glpi_states", "states_id", $this->fields["states_id"]);
      echo "</td></tr>\n";

      $candel = true;
      if (countLicensesForVersion($ID)>0    // Only count softwaresversions_id_buy (don't care of softwaresversions_id_use if no installation)
          || countInstallationsForVersion($ID)>0) {
             $candel = false;
      }
      $this->showFormButtons($ID,'',2,$candel);
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

}


/// License class
class SoftwareLicense extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct() {
      $this->table = "glpi_softwareslicenses";
      $this->type = SOFTWARELICENSE_TYPE;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
      $this->dohistory = true;
   }

   function pre_updateInDB($input,$updates,$oldvalues=array()) {

      // Clean end alert if expire is after old one
      if ((isset($oldvalues['expire']) && ($oldvalues['expire'] < $this->fields['expire']))) {
         $alert=new Alert();
         $alert->clear($this->type,$this->fields['id'],ALERT_END);
      }
      return array($input,$updates);
   }

   function prepareInputForAdd($input) {

      // Unset to set to default using mysql default value
      if (empty ($input['expire'])) {
         unset ($input['expire']);
      }

      if (!isset($input['computers_id']) || $input['computers_id'] <= 0) {
         $input['computers_id'] = -1;
      } else {
         // Number is 1 for affected license
         $input['number']=1;
      }

      return $input;
   }

   function prepareInputForUpdate($input) {

      if (isset($input['computers_id']) && $input['computers_id'] == 0) {
         $input['computers_id'] = -1;
      }
      if ((isset($input['computers_id']) && $input['computers_id'] > 0)
          || (!isset($input['computers_id']) && isset($this->fields['computers_id'])
              && $this->fields['computers_id']>0)) {
         // Number is 1 for affected license
         $input['number']=1;
      }
      return $input;
   }

   function post_addItem($newID, $input) {

      $itemtype = SOFTWARE_TYPE;
      $dupid = $this->fields["softwares_id"];
      if (isset ($input["_duplicate_license"])) {
         $itemtype = LICENSE_TYPE;
         $dupid = $input["_duplicate_license"];
      }
      // Add infocoms if exists for the licence
      $ic = new Infocom();
      if ($ic->getFromDBforDevice($itemtype, $dupid)) {
         unset ($ic->fields["id"]);
         $ic->fields["items_id"] = $newID;
         if (isset($ic->fields["immo_number"])) {
            $ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1,
                                                  INFOCOM_TYPE,$input['entities_id']);
         }
         if (empty($ic->fields['use_date'])) {
            unset($ic->fields['use_date']);
         }
         if (empty($ic->fields['buy_date'])) {
            unset($ic->fields['buy_date']);
         }
         $ic->fields["itemtype"] = $this->type;
         $ic->addToDB();
      }
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG, $CFG_GLPI;

      $ong[1] = $LANG['title'][26];
      if ($ID) {
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
    *@param $target form target
    *@param $ID Integer : Id of the version or the template to print
    *@param $softwares_id ID of the software for add process
    *
    *@return true if displayed  false if item not found or not right to display
    **/
   function showForm($target,$ID,$softwares_id=-1) {
      global $CFG_GLPI,$LANG;

      if (!haveRight("software","w")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
         $this->fields['softwares_id']=$softwares_id;
         $this->fields['number']=1;
      }

      $this->showTabs($ID, false, getActiveTab($this->type),array(),
                      "softwares_id=".$this->fields['softwares_id']);
      $this->showFormHeader($target,$ID,'',2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][31]."&nbsp;:</td>";
      echo "<td>";
      if ($ID>0) {
         $softwares_id=$this->fields["softwares_id"];
      } else {
         echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
      }
      echo "<a href='software.form.php?id=".$softwares_id."'>".
                 getDropdownName("glpi_softwares",$softwares_id)."</a>";
      echo "</td>";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_softwareslicensestypes", "softwareslicensestypes_id", $this->fields["softwareslicensestypes_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td>";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("serial",$this->table,"serial",$this->fields["serial"],40);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][1]."&nbsp;:</td>";
      echo "<td>";
      dropdownSoftwareVersions("softwaresversions_id_buy",$this->fields["softwares_id"],
                               $this->fields["softwaresversions_id_buy"]);
      echo "</td>";
      echo "<td>".$LANG['common'][20]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("otherserial",$this->table,"otherserial",
                              $this->fields["otherserial"],40);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][2]."&nbsp;:</td>";
      echo "<td>";
      dropdownSoftwareVersions("softwaresversions_id_use",$this->fields["softwares_id"],
                               $this->fields["softwaresversions_id_use"]);
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='45' rows='5' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['tracking'][29]."&nbsp;:</td>";
      echo "<td>";
      if ($this->fields["computers_id"]>0) {
         echo "1  (".$LANG['software'][50].")";
      } else {
         dropdownInteger("number",$this->fields["number"],1,1000,1,array(-1=>$LANG['software'][4]));
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][50]."&nbsp;:</td>";
      echo "<td>";
      if ($this->fields["number"]==1) {
         dropdownValue('glpi_computers','computers_id',$this->fields["computers_id"],1,
                       ($this->fields['is_recursive']
                            ? getSonsOf('glpi_entities', $this->fields['entities_id'])
                            : $this->fields['entities_id']));
      } else {
         echo $LANG['software'][51];
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['software'][32]."&nbsp;:</td>";
      echo "<td>";
      showDateFormItem('expire',$this->fields["expire"]);
      echo "</td></tr>\n";

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   /**
    * Is the license may be recursive
    *
    * @return boolean
   **/
   function maybeRecursive () {

      $soft=new Software();
      if (isset($this->fields["softwares_id"]) && $soft->getFromDB($this->fields["softwares_id"])) {
         return $soft->isRecursive();
      }
      return false;
   }
}

?>
