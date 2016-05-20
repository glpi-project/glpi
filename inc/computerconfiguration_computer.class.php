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
 * ComputerConfiguration class
**/
class ComputerConfiguration_Computer extends CommonDBChild {
   // From CommonDBChild
   static public $itemtype = "ComputerConfiguration";
   static public $items_id = "computerconfigurations_id";

   static $rightname = 'config';

   static function getTypeName($nb=0) {
      return _n('Computer', 'Computers', $nb);
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      switch ($item->getType()) {
         case "ComputerConfiguration":
            $listofcomputers_id = ComputerConfiguration::getListOfComputersID($item->getID(), 'none', 
                                                                              $item->fields['viewchilds']);
            $nb = count($listofcomputers_id);
            return self::createTabEntry(self::getTypeName($nb), $nb);

         case "Computer":
            $found_comp = $this->find("computers_id = ".$item->getID());
            $nb = count($found_comp);
            return self::createTabEntry(ComputerConfiguration::getTypeName($nb), $nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      switch ($item->getType()) {
         case "ComputerConfiguration" :
            $item->showComputers();
            return true;

         case "Computer":
            self::showForComputer($item->getID());
            return true;
      }
      return false;
   }

   static function showForComputer($computers_id) {
      global $CFG_GLPI;

      $self = new self;
      $found_comp = $self->find("computers_id = $computers_id");

      // init pager
      $number = count($found_comp);
      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }
      Html::printAjaxPager(sprintf(__('%1$s (%2$s)'), ComputerConfiguration::getTypeName(2), __('D=Dynamic')),
                              $start, $number);

      Session::initNavigateListItems("ComputerConfiguration_Computer", sprintf(__('%1$s = %2$s'),
                                                   ComputerConfiguration::getTypeName(1), 
                                                   ComputerConfiguration::getTypeName($number)));

      // display top massive actions
      $rand = mt_rand();
      $classname = "ComputerConfiguration_Computer";
      $massiveactionparams
         = array('container'        => 'mass'.$classname.$rand,
                 'specific_actions' => array('MassiveAction'.MassiveAction::CLASS_ACTION_SEPARATOR.
                                                'purge' => _x('button', 'Delete permanently')));

      Html::openMassiveActionsForm('mass'.$classname.$rand);
      Html::showMassiveActions($massiveactionparams);

      // show configuration list
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr>";
      echo "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.$classname.$rand)."</th>";
      echo "<th>".__('associated to the configuration')."</th>";
      echo "<th width='10'>"._x('item', 'State')."</th>";
      echo "<th>".__('do not match the configuration')."</th>";
      echo "</tr>";
      $configuration = new ComputerConfiguration;
      for ($i=$start, $j=0 ; ($i < $number) && ($j < $_SESSION['glpilist_limit']) ; $i++, $j++) {
         $current_line = array_shift($found_comp);
         $configuration->getFromDB($current_line['computerconfigurations_id']);

         //check if computer match criteria of associated configuration
         $detail = array();
         $match = ComputerConfiguration::isComputerMatchConfiguration($computers_id, 
                                                                      $current_line['computerconfigurations_id'],
                                                                      $detail); 
         
         echo "<tr>";
         echo "<td>";
         Html::showMassiveActionCheckBox($classname, $current_line['id']);
         echo "</td>";

         echo "<td>".$configuration->getLink(array('comments' => false));
         if ($detail['is_dynamic']) {
            echo "&nbsp;<b>(D)</b>";
         }
         echo "</td>";

         if ($match) {
            $pic = "greenbutton.png";
            $title = __('Yes');
         } else {
            $pic = "redbutton.png";
            $title = __('No');
         }
         echo "<td width='10'><img src='".$CFG_GLPI['root_doc']."/pics/$pic' title='$title'></td>";
         echo "</td>";
         echo "<td>";
         if (isset($detail['mismatch_configuration'])) {
            $out = "";
            foreach ($detail['mismatch_configuration'] as $tmp_conf_id) {
               $configuration->getFromDB($tmp_conf_id);
               $out.= $configuration->getLink(array('comments' => true)).", ";
            }
            echo substr($out, 0, -2);
         }
         echo "</td>";
         echo "</tr>";
      }
      echo "</table>";  

      // display bottom massive actions
      $massiveactionparams['ontop'] =false;
      Html::showMassiveActions($massiveactionparams);  
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::showMassiveActionsSubForm()
   **/
   static function showMassiveActionsSubForm(MassiveAction $ma) {
      global $CFG_GLPI;

      switch ($ma->getAction()) {
         case 'add' :
            ComputerConfiguration::dropdown();
            echo Html::submit(_x('button','Post'), array('name' => 'massiveaction'))."</span>";
            return true;
      }
      return parent::showMassiveActionsSubForm($ma);
   }

   /**
    * @since version 0.85
    *
    * @see CommonDBTM::processMassiveActionsForOneItemtype()
   **/
   static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item,
                                                       array $ids) {
      global $DB;

      switch ($ma->getAction()) {
         case 'add' :
            $compconf_comp = new self;
            foreach ($ids as $computers_id) {
               //find already existing conf
               $found_comp = $compconf_comp->find("computerconfigurations_id = ".
                                                    $_POST['computerconfigurations_id'].
                                                    " AND computers_id = $computers_id");
            
               if (count($found_comp) == 0) {
                  $compconf_comp->add(array('computerconfigurations_id' => $_POST['computerconfigurations_id'], 
                                            'computers_id'              => $computers_id));
               }
            }
      }

      parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
   }


   static function linkComputerWithConfigurations($computers_id, $affect_configuration) {
      $compconf_comp = new self;

      //delete old dynamic links
      $found_dynamic_compconf_comps = $compconf_comp->find("is_dynamic = 1 
                                                            AND computers_id = $computers_id");
      foreach ($found_dynamic_compconf_comps as $found_compconf_comp) {
         $compconf_comp->delete(array('id' => $found_compconf_comp['id']));
      }

      // find static link (to avoid adding dynamic links)
      $found_static_compconf_comps = $compconf_comp->find("is_dynamic = 0 
                                                            AND computers_id = $computers_id");
      $static_configurations_id = array();
      foreach ($found_static_compconf_comps as $tmp_comf_comp) {
         $static_configurations_id[] = $tmp_comf_comp['computerconfigurations_id'];
      }

      // add new link
      foreach ($affect_configuration as $computerconfigurations_id) {
         if (in_array($computerconfigurations_id, $static_configurations_id)) {
            continue;
         }

         $compconf_comp->add(array('computerconfigurations_id' => $computerconfigurations_id, 
                                   'computers_id'              => $computers_id, 
                                   'is_dynamic'                => true));
      }
   }
}