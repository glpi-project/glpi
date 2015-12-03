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
 * SoftwareVersion Class
**/
class SoftwareVersion extends CommonDBChild {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonDBChild
   static public $itemtype  = 'Software';
   static public $items_id  = 'softwares_id';


   static function getTypeName($nb=0) {
      return _n('Version', 'Versions', $nb);
   }


   function cleanDBonPurge() {
      global $DB;

      $csv = new Computer_SoftwareVersion();
      $csv->cleanDBonItemDelete(__CLASS__, $this->fields['id']);
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Computer_SoftwareVersion',$ong, $options);
      $this->addStandardTab('Log',$ong, $options);

      return $ong;
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


   /**
    * Print the Software / version form
    *
    * @param $ID        integer  Id of the version or the template to print
    * @param $options   array    of possible options:
    *     - target form target
    *     - softwares_id ID of the software for add process
    *
    * @return true if displayed  false if item not found or not right to display
    *
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      if ($ID > 0) {
         $this->check($ID, READ);
         $softwares_id = $this->fields['softwares_id'];
      } else {
         $softwares_id = $options['softwares_id'];
         $this->check(-1, CREATE, $options);
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>"._n('Software', 'Software', Session::getPluralNumber())."</td>";
      echo "<td>";
      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='softwares_id' value='$softwares_id'>";
      }
      echo "<a href='software.form.php?id=".$softwares_id."'>".
             Dropdown::getDropdownName("glpi_softwares", $softwares_id)."</a>";
      echo "</td>";
      echo "<td rowspan='4' class='middle'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='4'>";
      echo "<textarea cols='45' rows='3' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,"name");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . __('Operating system') . "</td><td>";
      OperatingSystem::dropdown(array('value' => $this->fields["operatingsystems_id"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>" . __('Status') . "</td><td>";
      State::dropdown(array('value'     => $this->fields["states_id"],
                            'entity'    => $this->fields["entities_id"],
                            'condition' => "`is_visible_softwareversion`"));
      echo "</td></tr>\n";

      // Only count softwareversions_id_buy (don't care of softwareversions_id_use if no installation)
      if ((SoftwareLicense::countForVersion($ID) > 0)
          || (Computer_SoftwareVersion::countForVersion($ID) > 0)) {
         $options['candel'] = false;
      }
      $this->showFormButtons($options);

      return true;
   }


   function getSearchOptions() {

      $tab                 = array();
      $tab['common']       = __('Characteristics');

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'name';
      $tab[2]['name']      = __('Name');
      $tab[2]['datatype']  = 'string';

      $tab[4]['table']     = 'glpi_operatingsystems';
      $tab[4]['field']     = 'name';
      $tab[4]['name']      = __('Operating system');
      $tab[4]['datatype']  = 'dropdown';

      $tab[16]['table']    = $this->getTable();
      $tab[16]['field']    = 'comment';
      $tab[16]['name']     = __('Comments');
      $tab[16]['datatype'] = 'text';

      $tab[31]['table']     = 'glpi_states';
      $tab[31]['field']     = 'completename';
      $tab[31]['name']      = __('Status');
      $tab[31]['datatype']  = 'dropdown';
      $tab[31]['condition'] = "`is_visible_softwareversion`";

      return $tab;
   }


   /**
    * Make a select box for  software to install
    *
    * @param $options array of possible options:
    *    - name          : string / name of the select (default is softwareversions_id)
    *    - softwares_id  : integer / ID of the software
    *    - value         : integer / value of the selected version
    *    - used          : array / already used items
    *
    * @return nothing (print out an HTML select box)
   **/
   static function dropdown($options=array()) {
      global $CFG_GLPI, $DB;

      //$softwares_id,$value=0
      $p['softwares_id'] = 0;
      $p['value']        = 0;
      $p['name']         = 'softwareversions_id';
      $p['used']         = array();

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $where = '';
      if (count($p['used'])) {
         $where = " AND `glpi_softwareversions`.`id` NOT IN (".implode(",",$p['used']).")";
      }
      // Make a select box
      $query = "SELECT DISTINCT `glpi_softwareversions`.*,
                              `glpi_states`.`name` AS sname
                FROM `glpi_softwareversions`
                LEFT JOIN `glpi_states` ON (`glpi_softwareversions`.`states_id` = `glpi_states`.`id`)
                WHERE `glpi_softwareversions`.`softwares_id` = '".$p['softwares_id']."'
                      $where
                ORDER BY `name`";
      $result = $DB->query($query);
      $number = $DB->numrows($result);

      $values = array(0 => Dropdown::EMPTY_VALUE);
      if ($number) {
         while ($data = $DB->fetch_assoc($result)) {
            $ID     = $data['id'];
            $output = $data['name'];

            if (empty($output) || $_SESSION['glpiis_ids_visible']) {
               $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
            }
            if (!empty($data['sname'])) {
               $output = sprintf(__('%1$s - %2$s'), $output, $data['sname']);
            }
            $values[$ID] = $output;
         }
      }
      return Dropdown::showFromArray($p['name'], $values, $p);
   }


   /**
    * Show Versions of a software
    *
    * @param $soft Software object
    *
    * @return nothing
   **/
   static function showForSoftware(Software $soft) {
      global $DB, $CFG_GLPI;

      $softwares_id = $soft->getField('id');

      if (!$soft->can($softwares_id, READ)) {
         return false;
      }
      $canedit = $soft->canEdit($softwares_id);

      echo "<div class='spaced'>";

      if ($canedit) {
         echo "<div class='center firstbloc'>";
         echo "<a class='vsubmit' href='softwareversion.form.php?softwares_id=$softwares_id'>".
                _x('button', 'Add a version')."</a>";
         echo "</div>";
      }

      $query = "SELECT `glpi_softwareversions`.*,
                       `glpi_states`.`name` AS sname
                FROM `glpi_softwareversions`
                LEFT JOIN `glpi_states` ON (`glpi_states`.`id` = `glpi_softwareversions`.`states_id`)
                WHERE `softwares_id` = '$softwares_id'
                ORDER BY `name`";

      Session::initNavigateListItems('SoftwareVersion',
            //TRANS : %1$s is the itemtype name,
            //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), Software::getTypeName(1),
                                             $soft->getName()));

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            echo "<table class='tab_cadre_fixehov'><tr>";
            echo "<th>".self::getTypeName(Session::getPluralNumber())."</th>";
            echo "<th>".__('Status')."</th>";
            echo "<th>".__('Operating system')."</th>";
            echo "<th>"._n('Installation', 'Installations', Session::getPluralNumber())."</th>";
            echo "<th>".__('Comments')."</th>";
            echo "</tr>\n";

            for ($tot=$nb=0 ; $data=$DB->fetch_assoc($result) ; $tot+=$nb) {
               Session::addToNavigateListItems('SoftwareVersion',$data['id']);
               $nb = Computer_SoftwareVersion::countForVersion($data['id']);

               echo "<tr class='tab_bg_2'>";
               echo "<td><a href='softwareversion.form.php?id=".$data['id']."'>";
               echo $data['name'].(empty($data['name'])?"(".$data['id'].")":"")."</a></td>";
               echo "<td>".$data['sname']."</td>";
               echo "<td class='right'>".Dropdown::getDropdownName('glpi_operatingsystems',
                                                                   $data['operatingsystems_id']);
               echo "</td>";
               echo "<td class='numeric'>$nb</td>";
               echo "<td>".$data['comment']."</td></tr>\n";
            }

            echo "<tr class='tab_bg_1 noHover'><td class='right b' colspan='3'>".__('Total')."</td>";
            echo "<td class='numeric b'>$tot</td><td></td></tr>";
            echo "</table>\n";

         } else {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".__('No item found')."</th></tr>";
            echo "</table>\n";
         }

      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Software' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(), "softwares_id = '".$item->getID()."'");
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Software') {
         self::showForSoftware($item);
      }
      return true;
   }

}
?>
