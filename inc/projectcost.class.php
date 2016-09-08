<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

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
   die("Sorry. You can't access this file directly");
}

/// ProjectCost class
/// since version 0.85
class ProjectCost extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Project';
   static public $items_id = 'projects_id';
   public $dohistory       = true;


   static function getTypeName($nb=0) {
      return _n('Cost', 'Costs', $nb);
   }


   /**
    * @see CommonDBChild::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $input['end_date'] = $input['begin_date'];
      }

      return parent::prepareInputForAdd($input);
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $input['end_date'] = $input['begin_date'];
      }

      return parent::prepareInputForUpdate($input);
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == 'Project') && Project::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable('glpi_projectcosts', "projects_id = '".$item->getID()."'");
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   /**
    * @param $item            CommonGLPI object
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForProject($item, $withtemplate);
      return true;
   }


   /**
    * @since version 0.85
    *
    * @see CommonDBTM::getSearchOptions()
   **/
   function getSearchOptions() {

      $tab                          = array();

      $tab['common']                = __('Characteristics');

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               =  __('Title');
      $tab[1]['searchtype']         = 'contains';
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['massiveaction']      = false;
      $tab[2]['datatype']           = 'number';

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[12]['table']             = $this->getTable();
      $tab[12]['field']             = 'begin_date';
      $tab[12]['name']              = __('Begin date');
      $tab[12]['datatype']          = 'datetime';

      $tab[10]['table']             = $this->getTable();
      $tab[10]['field']             = 'end_date';
      $tab[10]['name']              = __('End date');
      $tab[10]['datatype']          = 'datetime';

      $tab[14]['table']              = $this->getTable();
      $tab[14]['field']              = 'cost';
      $tab[14]['name']               = __('Cost');
      $tab[14]['datatype']           = 'decimal';

      $tab[18]['table']             = 'glpi_budgets';
      $tab[18]['field']             = 'name';
      $tab[18]['name']              = _n('Budget', 'Budgets', 1);
      $tab[18]['datatype']          = 'dropdown';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['massiveaction']     = false;
      $tab[80]['datatype']          = 'dropdown';

      return $tab;
   }


   /**
    * Duplicate all costs from a project template to its clone
    *
    * @since version 0.84
    *
    * @param $oldid
    * @param $newid
   **/
   static function cloneProject ($oldid, $newid) {
      global $DB;

      $query  = "SELECT *
                 FROM `glpi_projectcosts`
                 WHERE `projects_id` = '$oldid'";
      foreach ($DB->request($query) as $data) {
         $cd                   = new self();
         unset($data['id']);
         $data['projects_id'] = $newid;
         $data                 = Toolbox::addslashes_deep($data);
         $cd->add($data);
      }
   }


   /**
    * Init cost for creation based on previous cost
   **/
   function initBasedOnPrevious() {

      $ticket = new Ticket();
      if (!isset($this->fields['projects_id'])
          || !$ticket->getFromDB($this->fields['projects_id'])) {
         return false;
      }

      $lastdata = $this->getLastCostForProject($this->fields['projects_id']);

      if (isset($lastdata['end_date'])) {
         $this->fields['begin_date'] = $lastdata['end_date'];
      }
      if (isset($lastdata['cost'])) {
         $this->fields['cost'] = $lastdata['cost'];
      }
      if (isset($lastdata['name'])) {
         $this->fields['name'] = $lastdata['name'];
      }
      if (isset($lastdata['budgets_id'])) {
         $this->fields['budgets_id'] = $lastdata['budgets_id'];
      }
   }

   /**
    * Get last datas for a project
    *
    * @param $projects_id        integer  ID of the project
   **/
   function getLastCostForProject($projects_id) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->getTable()."`
                WHERE `projects_id` = '$projects_id'
                ORDER BY 'end_date' DESC, `id` DESC";

      if ($result = $DB->query($query)) {
         return $DB->fetch_assoc($result);
      }

      return array();
   }

   /**
    * Print the project cost form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    options used
   **/
   function showForm($ID, $options=array()) {

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options['projects_id'] = $options['parent']->getField('id');
         $this->check(-1, CREATE, $options);
         $this->initBasedOnPrevious();
      }

      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      echo "<input type='hidden' name='projects_id' value='".$this->fields['projects_id']."'>";
      Html::autocompletionTextField($this,'name');
      echo "</td>";
      echo "<td>".__('Cost')."</td>";
      echo "<td>";
      echo "<input type='text' name='cost' value='".Html::formatNumber($this->fields["cost"], true)."'
             size='14'>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Begin date')."</td>";
      echo "<td>";
      Html::showDateField("begin_date", array('value' => $this->fields['begin_date']));
      echo "</td>";
      $rowspan = 3;
      echo "<td rowspan='$rowspan'>".__('Comments')."</td>";
      echo "<td rowspan='$rowspan' class='middle'>";
      echo "<textarea cols='45' rows='".($rowspan+3)."' name='comment' >".$this->fields["comment"].
           "</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".__('End date')."</td>";
      echo "<td>";
      Html::showDateField("end_date", array('value' => $this->fields['end_date']));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Budget')."</td>";
      echo "<td>";
      Budget::dropdown(array('value' => $this->fields["budgets_id"]));
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the project costs
    *
    * @param $project               Project object
    * @param $withtemplate  boolean  Template or basic item (default '')
    *
    * @return Nothing (call to classes members)
   **/
   static function showForProject(Project $project, $withtemplate='') {
      global $DB, $CFG_GLPI;

      $ID = $project->fields['id'];

      if (!$project->getFromDB($ID)
          || !$project->can($ID, READ)) {
         return false;
      }
      $canedit = $project->can($ID, UPDATE);

      echo "<div class='center'>";

      $query = "SELECT *
                FROM `glpi_projectcosts`
                WHERE `projects_id` = '$ID'
                ORDER BY `begin_date`";

      $rand   = mt_rand();

      if ($canedit) {
         echo "<div id='viewcost".$ID."_$rand'></div>\n";
         echo "<script type='text/javascript' >\n";
         echo "function viewAddCost".$ID."_$rand() {\n";
         $params = array('type'         => __CLASS__,
                         'parenttype'   => 'Project',
                         'projects_id' => $ID,
                         'id'           => -1);
         Ajax::updateItemJsCode("viewcost".$ID."_$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddCost".$ID."_$rand();'>";
         echo __('Add a new cost')."</a></div>\n";
      }
      $total = 0;
      if ($result = $DB->query($query)) {
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'><th colspan='5'>".self::getTypeName($DB->numrows($result)).
              "</th></tr>";

         if ($DB->numrows($result)) {
            echo "<tr><th>".__('Name')."</th>";
            echo "<th>".__('Begin date')."</th>";
            echo "<th>".__('End date')."</th>";
            echo "<th>".__('Budget')."</th>";
            echo "<th>".__('Cost')."</th>";
            echo "</tr>";

         Session::initNavigateListItems(__CLASS__,
                              //TRANS : %1$s is the itemtype name,
                              //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Project::getTypeName(1), $project->getName()));


            while ($data = $DB->fetch_assoc($result)) {
               echo "<tr class='tab_bg_2' ".
                     ($canedit
                      ? "style='cursor:pointer' onClick=\"viewEditCost".$data['projects_id']."_".
                        $data['id']."_$rand();\"": '') .">";
               $name = (empty($data['name'])? sprintf(__('%1$s (%2$s)'),
                                                      $data['name'], $data['id'])
                                            : $data['name']);
               echo "<td>";
               printf(__('%1$s %2$s'), $name,
                        Html::showToolTip($data['comment'], array('display' => false)));
               if ($canedit) {
                  echo "\n<script type='text/javascript' >\n";
                  echo "function viewEditCost" .$data['projects_id']."_". $data["id"]. "_$rand() {\n";
                  $params = array('type'         => __CLASS__,
                                  'parenttype'   => 'Project',
                                  'projects_id' => $data["projects_id"],
                                  'id'           => $data["id"]);
                  Ajax::updateItemJsCode("viewcost".$ID."_$rand",
                                         $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
                  echo "};";
                  echo "</script>\n";
               }
               echo "</td>";
               echo "<td>".Html::convDate($data['begin_date'])."</td>";
               echo "<td>".Html::convDate($data['end_date'])."</td>";
               echo "<td>".Dropdown::getDropdownName('glpi_budgets', $data['budgets_id'])."</td>";
               echo "<td class='numeric'>".Html::formatNumber($data['cost'])."</td>";
               $total += $data['cost'];
               echo "</tr>";
               Session::addToNavigateListItems(__CLASS__, $data['id']);
            }
            echo "<tr class='b noHover'><td colspan='3'>&nbsp;</td>";
            echo "<td class='right'>".__('Total cost').'</td>';
            echo "<td class='numeric'>".Html::formatNumber($total).'</td></tr>';
         } else {
            echo "<tr><th colspan='5'>".__('No item found')."</th></tr>";
         }
         echo "</table>";
      }
      echo "</div>";
      echo "<div>";
      $ticketcost = TicketCost::showForObject($project);
      echo "</div>";
      echo "<div class='b'>";
      printf(__('%1$s: %2$s'), __('Total cost'),$total+$ticketcost);
      echo "</div>";
   }
}
?>
