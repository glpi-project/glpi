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

/// Version class
class SoftwareVersion extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_softwareversions';
   public $type = SOFTWAREVERSION_TYPE;
   public $dohistory = true;
   public $entity_assign=true;
   public $may_be_recursive=true;

   function cleanDBonPurge($ID) {
      global $DB;

      // Delete Installations
      $query2 = "DELETE
                 FROM `glpi_computers_softwareversions`
                 WHERE `softwareversions_id` = '$ID'";
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
      if (countLicensesForVersion($ID)>0    // Only count softwareversions_id_buy (don't care of softwareversions_id_use if no installation)
          || countInstallationsForVersion($ID)>0) {
             $candel = false;
      }
      $this->showFormButtons($ID,'',2,$candel);
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[2]['table']     = 'glpi_softwareversions';
      $tab[2]['field']     =  'name';
      $tab[2]['linkfield'] ='name';
      $tab[2]['name']      = $LANG['common'][16];

      $tab[16]['table']     = 'glpi_softwareversions';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[31]['table']     = 'glpi_states';
      $tab[31]['field']     = 'name';
      $tab[31]['linkfield'] = 'states_id';
      $tab[31]['name']      = $LANG['state'][0];

      return $tab;
   }

}


?>
