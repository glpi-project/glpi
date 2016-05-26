<?php
/*
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

/**
 * Ticket Recurrent class
 *
 * @since version 0.83
**/
class TicketRecurrent extends CommonDropdown {

   // From CommonDBTM
   public $dohistory              = true;

   // From CommonDropdown
   public $first_level_menu       = "helpdesk";
   public $second_level_menu      = "ticketrecurrent";

   public $display_dropdowntitle  = false;

   static $rightname              = 'ticketrecurrent';

   public $can_be_translated      = false;



   static function getTypeName($nb=0) {
      return __('Recurrent tickets');
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'TicketRecurrent' :
            switch ($tabnum) {
               case 1 :
                  $item->showInfos();
                  return true;
            }
            break;
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (Session::haveRight('tickettemplate', READ)) {
         switch ($item->getType()) {
            case 'TicketRecurrent' :
               $ong[1] = _n('Information', 'Information', Session::getPluralNumber());
               return $ong;
         }
      }
      return '';
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function prepareInputForAdd($input) {

      $input['next_creation_date'] = $this->computeNextCreationDate($input['begin_date'],
                                                                    $input['end_date'],
                                                                    $input['periodicity'],
                                                                    $input['create_before'],
                                                                    $input['calendars_id']);
      return $input;
   }


   function prepareInputForUpdate($input) {

      if (isset($input['begin_date'])
          && isset($input['periodicity'])
          && isset($input['create_before'])) {

         $input['next_creation_date'] = $this->computeNextCreationDate($input['begin_date'],
                                                                       $input['end_date'],
                                                                       $input['periodicity'],
                                                                       $input['create_before'],
                                                                       $input['calendars_id']);
      }
      return $input;
   }


   /**
    * Return Additional Fileds for this type
   **/
   function getAdditionalFields() {

      return array(array('name'  => 'is_active',
                         'label' => __('Active'),
                         'type'  => 'bool',
                         'list'  => false),
                   array('name'  => 'tickettemplates_id',
                         'label' => _n('Ticket template', 'Ticket templates', 1),
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'begin_date',
                         'label' => __('Start date'),
                         'type'  => 'datetime',
                         'list'  => false),
                   array('name'  => 'end_date',
                         'label' => __('End date'),
                         'type'  => 'datetime',
                         'list'  => false),
                   array('name'  => 'periodicity',
                         'label' => __('Periodicity'),
                         'type'  => 'specific_timestamp',
                         'min'   => DAY_TIMESTAMP,
                         'step'  => DAY_TIMESTAMP,
                         'max'   => 2*MONTH_TIMESTAMP),
                   array('name'  => 'create_before',
                         'label' => __('Preliminary creation'),
                         'type'  => 'timestamp',
                         'max'   => 7*DAY_TIMESTAMP,
                         'step'  => HOUR_TIMESTAMP),
                   array('name'  => 'calendars_id',
                         'label' => _n('Calendar', 'Calendars', 1),
                         'type'  => 'dropdownValue',
                         'list'  => true),
                  );
   }


   /**
    * @since version 0.83.1
    *
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {

      switch ($field['name']) {
         case 'periodicity' :
            $possible_values = array();
            for ($i=1 ; $i<24 ; $i++) {
               $possible_values[$i*HOUR_TIMESTAMP] = sprintf(_n('%d hour','%d hours',$i), $i);
            }
            for ($i=1 ; $i<=30 ; $i++) {
               $possible_values[$i*DAY_TIMESTAMP] = sprintf(_n('%d day','%d days',$i), $i);
            }

            for ($i=1 ; $i<12 ; $i++) {
               $possible_values[$i.'MONTH'] = sprintf(_n('%d month','%d months',$i), $i);
            }

            for ($i=1 ; $i<5 ; $i++) {
               $possible_values[$i.'YEAR'] = sprintf(_n('%d year','%d years',$i), $i);
            }

            Dropdown::showFromArray($field['name'], $possible_values,
                                    array('value' => $this->fields[$field['name']]));
            break;
      }
   }

   /**
    * @since version 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }

      switch ($field) {
         case 'periodicity' :
            if (preg_match('/([0-9]+)MONTH/',$values[$field], $matches)) {
               return sprintf(_n('%d month','%d months',$matches[1]), $matches[1]);
            }
            if (preg_match('/([0-9]+)YEAR/',$values[$field], $matches)) {
               return sprintf(_n('%d year','%d years',$matches[1]), $matches[1]);
            }
            return Html::timestampToString($values[$field], false);
         break;
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'is_active';
      $tab[11]['name']     = __('Active');
      $tab[11]['datatype'] = 'bool';

      $tab[12]['table']    = 'glpi_tickettemplates';
      $tab[12]['field']    = 'name';
      $tab[12]['name']     = _n('Ticket template', 'Ticket templates', 1);
      $tab[12]['datatype'] = 'itemlink';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'begin_date';
      $tab[13]['name']     = __('Start date');
      $tab[13]['datatype'] = 'datetime';

      $tab[17]['table']    = $this->getTable();
      $tab[17]['field']    = 'end_date';
      $tab[17]['name']     = __('End date');
      $tab[17]['datatype'] = 'datetime';

      $tab[15]['table']    = $this->getTable();
      $tab[15]['field']    = 'periodicity';
      $tab[15]['name']     = __('Periodicity');
      $tab[15]['datatype'] = 'specific';

      $tab[14]['table']    = $this->getTable();
      $tab[14]['field']    = 'create_before';
      $tab[14]['name']     = __('Preliminary creation');
      $tab[14]['datatype'] = 'timestamp';

      $tab[16]['table']    = 'glpi_calendars';
      $tab[16]['field']    = 'name';
      $tab[16]['name']     = _n('Calendar', 'Calendars', 1);
      $tab[16]['datatype'] = 'itemlink';

      return $tab;
   }


   /**
    * Show next creation date
    *
    * @return nothing only display
   **/
   function showInfos() {

      if (!is_null($this->fields['next_creation_date'])) {
         echo "<div class='center'>";
         //TRANS: %s is the date of next creation
         echo sprintf(__('Next creation on %s'),
                      Html::convDateTime($this->fields['next_creation_date']));
         echo "</div>";
      }
   }


   /**
    * Compute next creation date of a ticket
    *
    * New parameter in  version 0.84 : $calendars_id
    *
    * @param $begin_date      datetime    Begin date of the recurrent ticket
    * @param $end_date        datetime    End date of the recurrent ticket
    * @param $periodicity     timestamp   Periodicity of creation
    * @param $create_before   timestamp   Create before specific timestamp
    * @param $calendars_id    integer     ID of the calendar to used
    *
    * @return datetime next creation date
   **/
   function computeNextCreationDate($begin_date, $end_date, $periodicity, $create_before,
                                    $calendars_id) {

      if (empty($begin_date) || ($begin_date == 'NULL')) {
         return 'NULL';
      }
      if (!empty($end_date) && ($end_date <> 'NULL')) {
         if (strtotime($end_date) < time()) {
            return 'NULL';
         }
      }
      $check = true;
      if (preg_match('/([0-9]+)MONTH/',$periodicity)
          || preg_match('/([0-9]+)YEAR/',$periodicity)) {
         $check = false;
      }

      if ($check
          && ($create_before > $periodicity)) {
         Session::addMessageAfterRedirect(__('Invalid frequency. It must be greater than the preliminary creation.'),
                                          false, ERROR);
         return 'NULL';
      }

      if ($periodicity <> 0) {
         // Standard time computation
         $timestart  = strtotime($begin_date) - $create_before;
         $now        = time();
         if ($now > $timestart) {
            $value = $periodicity;
            $step  = "second";
            if (preg_match('/([0-9]+)MONTH/',$periodicity, $matches)) {
               $value = $matches[1];
               $step  = 'MONTH';
            } else if (preg_match('/([0-9]+)YEAR/',$periodicity, $matches)) {
               $value = $matches[1];
               $step  = 'YEAR';
            } else {
               if (($value%DAY_TIMESTAMP)==0) {
                  $value = $value/DAY_TIMESTAMP;
                  $step  = "DAY";
               } else {
                  $value = $value/HOUR_TIMESTAMP;
                  $step  = "HOUR";
               }
            }

            while ($timestart < $now) {
               $timestart = strtotime("+ $value $step",$timestart);
            }
         }
         // Time start over end date
         if (!empty($end_date) && ($end_date <> 'NULL')) {
            if ($timestart > strtotime($end_date)) {
               return 'NULL';
            }
         }

         $calendar = new Calendar();
         if ($calendars_id
             && $calendar->getFromDB($calendars_id)) {
            $durations = $calendar->getDurationsCache();
            if (array_sum($durations) > 0) { // working days exists
               while (!$calendar->isAWorkingDay($timestart)) {
                  $timestart = strtotime("+ 1 day",$timestart);
               }
            }
         }

         return date("Y-m-d H:i:s", $timestart);
      }

      return 'NULL';
   }


   /**
    * Give cron information
    *
    * @param $name : task's name
    *
    * @return arrray of information
   **/
   static function cronInfo($name) {

      switch ($name) {
         case 'ticketrecurrent' :
            return array('description' => self::getTypeName(Session::getPluralNumber()));
      }
      return array();
   }


   /**
    * Cron for ticket's automatic close
    *
    * @param $task : crontask object
    *
    * @return integer (0 : nothing done - 1 : done)
   **/
   static function cronTicketRecurrent($task) {
      global $DB;

      $tot = 0;

      $query = "SELECT *
                FROM `glpi_ticketrecurrents`
                WHERE `glpi_ticketrecurrents`.`next_creation_date` < NOW()
                      AND `glpi_ticketrecurrents`.`is_active` = 1
                      AND (`glpi_ticketrecurrents`.`end_date` IS NULL
                           OR `glpi_ticketrecurrents`.`end_date` > NOW())";

      foreach ($DB->request($query) as $data) {
         if (self::createTicket($data)) {
            $tot++;
         } else {
            //TRANS: %s is a name
            $task->log(sprintf(__('Failed to create recurrent ticket %s'),
                               $data['name']));
         }
      }

      $task->setVolume($tot);
      return ($tot > 0);
   }


   /**
    * Create a ticket based on ticket recurrent infos
    *
    * @param $data array data of a entry of glpi_ticketrecurrents
    *
    * @return boolean
   **/
   static function createTicket($data) {

      $result = false;
      $tt     = new TicketTemplate();

      // Create ticket based on ticket template and entity information of ticketrecurrent
      if ($tt->getFromDB($data['tickettemplates_id'])) {
         // Get default values for ticket
         $input = Ticket::getDefaultValues($data['entities_id']);
         // Apply tickettemplates predefined values
         $ttp        = new TicketTemplatePredefinedField();
         $predefined = $ttp->getPredefinedFields($data['tickettemplates_id'], true);

         if (count($predefined)) {
            foreach ($predefined as $predeffield => $predefvalue) {
               $input[$predeffield] = $predefvalue;
            }
         }
         // Set date to creation date
         $createtime    = strtotime($data['next_creation_date'])+$data['create_before'];
         $input['date'] = date('Y-m-d H:i:s', $createtime);
         if (isset($predefined['date'])) {
            $input['date'] = Html::computeGenericDateTimeSearch($predefined['date'], false,
                                                                $createtime);
         }
         // Compute due_date if predefined based on create date
         if (isset($predefined['due_date'])) {
            $input['due_date'] = Html::computeGenericDateTimeSearch($predefined['due_date'], false,
                                                                    $createtime);
         }
         // Set entity
         $input['entities_id'] = $data['entities_id'];
         $input['_auto_import'] = true;

         $ticket = new Ticket();
         $input  = Toolbox::addslashes_deep($input);
         if ($tid = $ticket->add($input)) {
            $msg = sprintf(__('Ticket %d successfully created'), $tid);
            $result = true;
         } else {
            $msg = __('Ticket creation failed (check mandatory fields)');
         }
      } else {
         $msg = __('Ticket creation failed (no template)');
      }
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = addslashes($msg);
      Log::history($data['id'], __CLASS__, $changes, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);

      // Compute next creation date
      $tr = new self();
      if ($tr->getFromDB($data['id'])) {
         $input                       = array();
         $input['id']                 = $data['id'];
         $input['next_creation_date'] = $tr->computeNextCreationDate($data['begin_date'],
                                                                     $data['end_date'],
                                                                     $data['periodicity'],
                                                                     $data['create_before'],
                                                                     $data['calendars_id']);
         $tr->update($input);
      }

      return $result;
   }

}
