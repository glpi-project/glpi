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
   public $type = 'SoftwareVersion';
   public $dohistory = true;
   public $entity_assign=true;
   public $may_be_recursive=true;


   static function getTypeName() {
      global $LANG;

      return $LANG['software'][5];
   }

   static function canCreate() {
      return haveRight('software', 'w');
   }

   static function canView() {
      return haveRight('software', 'r');
   }

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
             Dropdown::getDropdownName("glpi_softwares",$softwares_id)."</a>";
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
      Dropdown::dropdownValue("glpi_states", "states_id", $this->fields["states_id"]);
      echo "</td></tr>\n";

      $candel = true;
      // Only count softwareversions_id_buy (don't care of softwareversions_id_use if no installation)
      if (SoftwareLicense::countForVersion($ID)>0
          || Computer_SoftwareVersion::countForVersion($ID)>0) {
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

   /**
    * Make a select box for  software to install
    *
    * Parameters which could be used in options array :
    *    - softwares_id : integer / ID of the software
    *    - value : integer / value of the selected version
    *
    * @param $myname select name

    * @return nothing (print out an HTML select box)
    */
   static function dropdown($myname,$options=array()) {
      global $CFG_GLPI;

      //$softwares_id,$value=0
      $p['softwares_id']=0;
      $p['value']=0;

      if (count($options)) {
         foreach ($options as $key => $val) {
            $p[$key]=$val;
         }
      }

      $rand=mt_rand();
      $params=array('softwares_id'=>$p['softwares_id'],
                    'myname'=>$myname,
                    'value'=>$p['value']);

      $default="<select name='$myname'><option value='0'>------</option></select>";
      ajaxDropdown(false,"/ajax/dropdownInstallVersion.php",$params,$default,$rand);

      return $rand;
   }

   /**
    * Show Versions of a software
    *
    * @param $softwares_id ID of the software
    * @return nothing
    */
   static function showForSoftware($soft) {
      global $DB, $CFG_GLPI, $LANG;

      $softwares_id = $soft->getField('id');

      if (!$soft->can($softwares_id,'r')) {
         return false;
      }
      $canedit = $soft->can($softwares_id,"w");

      echo "<div class='center'>";

      $query = "SELECT `glpi_softwareversions`.*,
                       `glpi_states`.`name` AS sname
                FROM `glpi_softwareversions`
                LEFT JOIN `glpi_states` ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                WHERE `softwares_id` = '$softwares_id'
                ORDER BY `name`";

      initNavigateListItems('SoftwareVersion',$LANG['help'][31] ." = ". $soft->fields["name"]);

      if ($result=$DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<table class='tab_cadre'><tr>";
            echo "<th>&nbsp;".$LANG['software'][5]."&nbsp;</th>";
            echo "<th>&nbsp;".$LANG['state'][0]."&nbsp;</th>";
            echo "<th>&nbsp;".$LANG['software'][19]."&nbsp;</th>";
            echo "<th>&nbsp;".$LANG['common'][25]."&nbsp;</th>";
            echo "</tr>\n";

            for ($tot=$nb=0 ; $data=$DB->fetch_assoc($result) ; $tot+=$nb) {
               addToNavigateListItems('SoftwareVersion',$data['id']);
               $nb = Computer_SoftwareVersion::countForVersion($data['id']);

               // Show version if canedit (to update/delete) or if nb (to see installations)
               if ($canedit || $nb) {
                  echo "<tr class='tab_bg_2'>";
                  echo "<td><a href='softwareversion.form.php?id=".$data['id']."'>";
                  echo $data['name'].(empty($data['name'])?$data['id']:"")."</a></td>";
                  echo "<td class='right'>".$data['sname']."</td>";
                  echo "<td class='right'>$nb</td>";
                  echo "<td>".$data['comment']."</td></tr>\n";
               }
            }
            echo "<tr class='tab_bg_1'><td class='right b' colspan='2'>".$LANG['common'][33]."</td>";
            echo "<td class='right b'>$tot</td><td>";
            if ($canedit) {
               echo "<a href='softwareversion.form.php?softwares_id=$softwares_id'>".
                      $LANG['software'][7]."</a>";
            }
            echo "</td></tr>";
            echo "</table>\n";
         } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".$LANG['search'][15]."</th></tr>";
            if ($canedit) {
               echo "<tr class='tab_bg_2'><td class='center'>";
               echo "<a href='softwareversion.form.php?softwares_id=$softwares_id'>".
                      $LANG['software'][7]."</a></td></tr>";
            }
            echo "</table>\n";
         }

      }
      echo "</div>";
   }
}


?>
