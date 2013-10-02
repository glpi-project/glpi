<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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
 * Project Class
**/
class Project extends CommonDBTM {

   // From CommonDBTM
   public $dohistory          = true;
   static $rightname          = 'project';
   
   const READMY               = 1;
   const READALL              = 1024;

   /**
    * Name of the type
    *
    * @param $nb : number of item in the type (default 0)
   **/
   static function getTypeName($nb=0) {
      return _n('Project','Project',$nb);
   }

   static function canView() {
      return Session::haveRightsOr(self::$rightname, array(self::READALL, self::READMY));
   }

   /**
    * Is the current user have right to create the current change ?
    *
    * @return boolean
   **/
   function canCreateItem() {

      if (!Session::haveAccessToEntity($this->getEntityID())) {
         return false;
      }
      return Session::haveRight(self::$rightname, CREATE);
   }
   
   /**
    * @since version 0.85
    *
    * @see commonDBTM::getRights()
    **/
   function getRights($interface='central') {

      $values = parent::getRights();
      unset($values[READ]);

      $values[self::READALL] = __('See all');
      $values[self::READMY]  = __('See (author)');

      return $values;
   }
   function defineTabs($options=array()) {
      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }
   
   function post_getEmpty() {
      $this->fields['priority'] = 3;
   }
   

   function getSearchOptions() {

      $tab = array();
      $tab['common']             = __('Characteristics');

      $tab[1]['table']           = $this->getTable();
      $tab[1]['field']           = 'name';
      $tab[1]['name']            = __('Name');
      $tab[1]['datatype']        = 'itemlink';
      $tab[1]['massiveaction']   = false; // implicit key==1

      $tab[2]['table']           = $this->getTable();
      $tab[2]['field']           = 'id';
      $tab[2]['name']            = __('ID');
      $tab[2]['massiveaction']   = false; // implicit field is id
      $tab[2]['datatype']        = 'number';

      $tab[4]['table']         = $this->getTable();
      $tab[4]['field']         = 'code';
      $tab[4]['name']          = __('Code');
      $tab[4]['massiveaction'] = false;
      $tab[4]['datatype']      = 'string';
      
      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'name';
      $tab[13]['name']              = __('Father');
      $tab[13]['datatype']          = 'dropdown';
      $tab[13]['massiveaction']     = false;
      // Add virtual condition to relink table
      $tab[13]['joinparams']        = array('condition' => "AND 1=1");

      $tab[21]['table']         = $this->getTable();
      $tab[21]['field']         = 'content';
      $tab[21]['name']          = __('Description');
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'text';

      $tab[3]['table']          = $this->getTable();
      $tab[3]['field']          = 'priority';
      $tab[3]['name']           = __('Priority');
      $tab[3]['searchtype']     = 'equals';
      $tab[3]['datatype']      = 'specific';

      $tab[14]['table']          = 'glpi_projecttypes';
      $tab[14]['field']          = 'name';
      $tab[14]['name']           = __('Type');
      $tab[14]['datatype']      = 'dropdown';

      $tab[12]['table']          = 'glpi_projectstates';
      $tab[12]['field']          = 'name';
      $tab[12]['name']           = __('State');
      $tab[12]['datatype']      = 'dropdown';
      
      $tab[15]['table']         = $this->getTable();
      $tab[15]['field']         = 'date';
      $tab[15]['name']          = __('Opening date');
      $tab[15]['datatype']      = 'datetime';
      $tab[15]['massiveaction'] = false;

      $tab[5]['table']         = $this->getTable();
      $tab[5]['field']         = 'percent_done';
      $tab[5]['name']          = __('Percent done');
      $tab[5]['datatype']      = 'number';
      $tab[5]['unit']          = '%';
      $tab[5]['min']           = 0;
      $tab[5]['max']           = 100;
      $tab[5]['step']          = 5;

      $tab[6]['table']         = $this->getTable();
      $tab[6]['field']         = 'show_on_global_gantt';
      $tab[6]['name']          = __('Show on global GANTT');
      $tab[6]['datatype']      = 'bool';

      $tab[24]['table']          = 'glpi_users';
      $tab[24]['field']          = 'name';
      $tab[24]['linkfield']      = 'users_id';
      $tab[24]['name']           = __('Manager');
      $tab[24]['datatype']       = 'dropdown';
      $tab[24]['right']          = 'see_project';

      $tab[49]['table']          = 'glpi_groups';
      $tab[49]['field']          = 'completename';
      $tab[49]['linkfield']      = 'groups_id';
      $tab[49]['name']           = __('Manager group');
      $tab[49]['condition']      = '`is_manager`';
      $tab[49]['datatype']       = 'dropdown';
      
      $tab[7]['table']         = $this->getTable();
      $tab[7]['field']         = 'plan_start_date';
      $tab[7]['name']          = __('Planned begin date');
      $tab[7]['datatype']      = 'datetime';

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'plan_end_date';
      $tab[8]['name']          = __('Planned end date');
      $tab[8]['datatype']      = 'datetime';

      $tab[9]['table']         = $this->getTable();
      $tab[9]['field']         = 'real_start_date';
      $tab[9]['name']          = __('Real begin date');
      $tab[9]['datatype']      = 'datetime';

      $tab[10]['table']         = $this->getTable();
      $tab[10]['field']         = 'real_end_date';
      $tab[10]['name']          = __('Real end date');
      $tab[10]['datatype']      = 'datetime';
      
      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';
      
      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[80]['table']          = 'glpi_entities';
      $tab[80]['field']          = 'completename';
      $tab[80]['name']           = __('Entity');
      $tab[80]['datatype']       = 'dropdown';
      
      $tab[86]['table']          = $this->getTable();
      $tab[86]['field']          = 'is_recursive';
      $tab[86]['name']           = __('Child entities');
      $tab[86]['datatype']       = 'bool';

      return $tab;
   }

   function prepareInputForUpdate($input) {

      if (isset($input['plan_start_date']) && isset($input['plan_end_date'])
         && !empty($input['plan_end_date'])
         && ($input['plan_end_date'] < $input['plan_start_date']
               || empty($input['plan_start_date']))) {
         Session::addMessageAfterRedirect(__('Invalid planned dates. Dates not updated.'), false, ERROR);
         unset($input['plan_start_date']);
         unset($input['plan_end_date']);
      }
      if (isset($input['real_start_date']) && isset($input['real_end_date'])
         && !empty($input['real_end_date'])
         && ($input['real_end_date'] < $input['real_start_date']
               || empty($input['real_start_date']))) {
         Session::addMessageAfterRedirect(__('Invalid real dates. Dates not updated.'), false, ERROR);
         unset($input['real_start_date']);
         unset($input['real_end_date']);
      }      
      return $input;
   }
   /**
    * Print the computer form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    *@return Nothing (display)
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI, $DB;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Opening date')."</td>";
      echo "<td>";

      $date = $this->fields["date"];
      if (!$ID) {
         $date = date("Y-m-d H:i:s");
      }
      Html::showDateTimeField("date", array('value'      => $date,
                                            'timestep'   => 1,
                                            'maybeempty' => false));
      echo "</td>";
      if ($ID) {
         echo "<td>".__('Last update')."</td>";
         echo "<td >";
         echo Html::convDateTime($this->fields["date_mod"]);
         echo "</td>";
      } else {
         echo "<td colspan='2'>&nbsp;</td>";
      }
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,'name');
      echo "</td>";
      echo "<td>".__('Code')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this,'code');
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Priority')."</td>";
      echo "<td>";
      CommonITILObject::dropdownPriority(array('value' => $this->fields['priority']));
      echo "</td>";
      echo "<td>".__('As child of')."</td>";
      echo "<td>";
      $this->dropdown(array('comments' => 0,
                            'entity'   => $this->fields['entities_id'],
                            'value'    => $this->fields['projects_id'],
                            'used'     => array($this->fields['id'])));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('State')."</td>";
      echo "<td>";
      ProjectState::dropdown(array('value' => $this->fields["projectstates_id"]));
      echo "</td>";
      echo "<td>".__('Percent done')."</td>";
      echo "<td>";
      Dropdown::showNumber("percent_done", array('value' => $this->fields['percent_done'],
                                                   'min'   => 0,
                                                   'max'   => 100,
                                                   'step'  => 5,
                                                   'unit'  => '%'));

      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Type')."</td>";
      echo "<td>";
      ProjectType::dropdown(array('value' => $this->fields["projecttypes_id"]));
      echo "</td>";
      echo "<td>".__('Show on global GANTT')."</td>";
      echo "<td>";
      Dropdown::showYesNo("show_on_global_gantt", $this->fields["show_on_global_gantt"]);
      echo "</td>";
      echo "</tr>";

      echo "<tr><td colspan='4' class='subheader'>".__('Manager')."</td></tr>";
      
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('User')."</td>";
      echo "<td>";
      User::dropdown(array('name'   => 'users_id',
                           'value'  => $this->fields["users_id"],
                           'right'  => 'see_project',
                           'entity' => $this->fields["entities_id"]));
      echo "</td>";
      echo "<td>".__('Group')."</td>";
      echo "<td>";
      Group::dropdown(array('name'      => 'groups_id',
                            'value'     => $this->fields['groups_id'],
                            'entity'    => $this->fields['entities_id'],
                            'condition' => '`is_manager`'));
      
      echo "</td></tr>\n";      
      echo "<tr><td colspan='4' class='subheader'>".__('Planning')."</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_start_date", array('value' => $this->fields['plan_start_date']));
      echo "</td>";
      echo "<td>".__('Real start date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_start_date", array('value' => $this->fields['real_start_date']));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Planned end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("plan_end_date", array('value' => $this->fields['plan_end_date']));
      echo "</td>";
      echo "<td>".__('Real end date')."</td>";
      echo "<td>";
      Html::showDateTimeField("real_end_date", array('value' => $this->fields['real_end_date']));
      echo "</td></tr>\n";


      $this->showFormButtons($options);

      return true;
   }

   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'priority':
            return CommonITILObject::getPriorityName($values[$field]);

      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }
   /**
    * @since version 0.84
    *
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;

      switch ($field) {
         case 'priority' :
            $options['name']  = $name;
            $options['value'] = $values[$field];
            return CommonITILObject::dropdownPriority($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }   
}
?>