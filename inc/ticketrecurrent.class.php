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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Ticket Recurrent class
/// since version 0.83
class TicketRecurrent extends CommonDropdown {

   // From CommonDBTM
   public $dohistory = true;

   // From CommonDropdown
   public $first_level_menu  = "maintain";
   public $second_level_menu = "ticketrecurrent";

   public $display_dropdowntitle  = false;


   static function getTypeName($nb=0) {
      global $LANG;

      return $LANG['jobrecurrent'][1];
   }


   function canCreate() {
      return Session::haveRight('ticketrecurrent', 'w');
   }


   function canView() {
      return Session::haveRight('ticketrecurrent', 'r');
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
      global $LANG;

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
      global $LANG;

      if (Session::haveRight("tickettemplate","r")) {
         switch ($item->getType()) {
            case 'TicketRecurrent' :
               $ong[1] = $LANG['jobrecurrent'][1];
               return $ong;
         }
      }
      return '';
   }


   function defineTabs($options=array()) {
      global $LANG, $CFG_GLPI;

      $ong = array();

      $this->addStandardTab('TicketRecurrent', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function prepareInputForAdd($input) {
      $input['next_creation_date'] = $this->computeNextCreationDate($input['begin_date'],
                                                                    $input['periodicity'],
                                                                    $input['create_before']);
      return $input;
   }


   function prepareInputForUpdate($input) {

      if (isset($input['begin_date']) && isset($input['periodicity'])
          && isset($input['create_before'])) {
         $input['next_creation_date'] = $this->computeNextCreationDate($input['begin_date'],
                                                                       $input['periodicity'],
                                                                       $input['create_before']);
      }
      return $input;
   }


   /**
    * Return Additional Fileds for this type
   **/
   function getAdditionalFields() {
      global $LANG;

      return array(array('name'  => 'is_active',
                         'label' => $LANG['common'][60],
                         'type'  => 'bool',
                         'list'  => false),
                   array('name'  => 'tickettemplates_id',
                         'label' => $LANG['job'][58],
                         'type'  => 'dropdownValue',
                         'list'  => true),
                   array('name'  => 'begin_date',
                         'label' => $LANG['search'][8],
                         'type'  => 'datetime',
                         'list'  => false),
                   array('name'  => 'periodicity',
                         'label' => $LANG['common'][115],
                         'type'  => 'specific_timestamp',
                         'min'   => DAY_TIMESTAMP,
                         'step'  => DAY_TIMESTAMP,
                         'max'   => 2*MONTH_TIMESTAMP),
                   array('name'  => 'create_before',
                         'label' => $LANG['jobrecurrent'][2],
                         'type'  => 'timestamp',
                         'max'   => 7*DAY_TIMESTAMP,
                         'step'  => HOUR_TIMESTAMP),);
   }

   function displaySpecificTypeField($ID, $field = array()) {
      global $LANG;
      switch ($field['name']) {
         case 'periodicity' :
            /// TODO : trouble with variable MONTH / YEAR length
            $possible_values=array();
            for ($i=1 ; $i<24 ; $i++) {
               $possible_values[$i*HOUR_TIMESTAMP] = $i." ".Toolbox::ucfirst($LANG['gmt'][1]);
            }
            for ($i=1 ; $i<=30 ; $i++) {
               $possible_values[$i*DAY_TIMESTAMP] = $i." ".Toolbox::ucfirst($LANG['calendar'][12]);
            }

            for ($i=1 ; $i<12 ; $i++) {
               $possible_values[$i*MONTH_TIMESTAMP] = $i." ".Toolbox::ucfirst($LANG['calendar'][14]);
            }

            for ($i=1 ; $i<5 ; $i++) {
               $possible_values[$i*365*DAY_TIMESTAMP] = $i." ".Toolbox::ucfirst($LANG['calendar'][15]);
            }

            Dropdown::showFromArray($field['name'], $possible_values, array('value' => $this->fields[$field['name']]));
            break;
      }
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      global $LANG;

      $tab = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'is_active';
      $tab[11]['name']     = $LANG['common'][60];
      $tab[11]['datatype'] = 'bool';

      $tab[12]['table']    = 'glpi_tickettemplates';
      $tab[12]['field']    = 'name';
      $tab[12]['name']     = $LANG['job'][58];
      $tab[12]['datatype'] = 'itemlink';

      $tab[13]['table']    = $this->getTable();
      $tab[13]['field']    = 'begin_date';
      $tab[13]['name']     = $LANG['search'][8];
      $tab[13]['datatype'] = 'datetime';

      $tab[15]['table']    = $this->getTable();
      $tab[15]['field']    = 'periodicity';
      $tab[15]['name']     = $LANG['common'][115];
      $tab[15]['datatype'] = 'timestamp';

      $tab[14]['table']    = $this->getTable();
      $tab[14]['field']    = 'create_before';
      $tab[14]['name']     = $LANG['jobrecurrent'][2];
      $tab[14]['datatype'] = 'timestamp';

      return $tab;
   }


   /**
    * Show next creation date
    *
    * @return nothing only display
   **/
   function showInfos() {
      global $LANG;

      if (!is_null($this->fields['next_creation_date'])) {
         echo "<div class='center'>";
         echo $LANG['jobrecurrent'][3].'&nbsp;:&nbsp;';
         echo Html::convDateTime($this->fields['next_creation_date']);
         echo "</div>";
      }
   }


   /**
    * Compute next creation date of a ticket
    *
    * @param $begin_date datetime Begin date of the recurrent ticket
    * @param $periodicity timestamp Periodicity of creation
    * @param $create_before timestamp Create before specific timestamp
    *
    * @return datetime next creation date
   **/
   function computeNextCreationDate($begin_date, $periodicity, $create_before){
      global $LANG;

      if (empty($begin_date)) {
         return 'NULL';
      }
      if ($create_before > $periodicity) {
         Session::addMessageAfterRedirect($LANG['jobrecurrent'][4], false, ERROR);
         return 'NULL';
      }

      if ($periodicity > 0) {
         $timestart  = strtotime($begin_date) - $create_before;
         $now        = time();
         if ($now > $timestart) {
            $times = floor(($now-$timestart) / $periodicity);
            return date("Y-m-d H:i:s", $timestart+($times+1)*$periodicity);
         } else {
            return date("Y-m-d H:i:s", $timestart);
         }

      }

      return 'NULL';
   }


   /**
    * Give cron informations
    *
    * @param $name : task's name
    *
    * @return arrray of informations
   **/
   static function cronInfo($name) {
      global $LANG;

      switch ($name) {
         case 'ticketrecurrent' :
            return array('description' => $LANG['jobrecurrent'][1]);
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
      global $DB, $LANG;

      $tot = 0;

      $query = "SELECT *
                FROM `glpi_ticketrecurrents`
                WHERE `glpi_ticketrecurrents`.`next_creation_date` < NOW()
                      AND `glpi_ticketrecurrents`.`is_active` = 1";

      foreach ($DB->request($query) as $data) {
         if (self::createTicket($data)) {
            $tot++;
         } else {
            $task->log($LANG['common'][118]." (".$data['name'].")");
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
      global $LANG;

      $result = false;
      $tt = new TicketTemplate();

      // Create ticket based on ticket template and entity informations of ticketrecurrent
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
         $createtime    = strtotime($data['next_creation_date']) + $data['create_before'];
         $input['date'] = date('Y-m-d H:i:s', $createtime);
         // Compute due_date if predefined based on create date
         if (isset($predefined['due_date'])) {
            $input['due_date'] = Html::computeGenericDateTimeSearch($predefined['due_date'], false,
                                                                    $createtime);
         }
         // Set entity
         $input['entities_id'] = $data['entities_id'];

         $ticket = new Ticket();
         $input  = Toolbox::addslashes_deep($input);
         if ($tid=$ticket->add($input)) {
            $msg = $LANG['common'][23]." ($tid)"; // Success
            $result = true;
         } else {
            $msg = $LANG['common'][118]; // Failure
         }
      } else {
         $msg = $LANG['common'][24]; // Not defined
      }
      $changes[0] = 0;
      $changes[1] = '';
      $changes[2] = addslashes($msg);
      Log::history($data['id'], __CLASS__, $changes, '', Log::HISTORY_LOG_SIMPLE_MESSAGE);


      // Compute next creation date
      $tr = new self();
      if ($tr->getFromDB($data['id'])) {
         $input = array();
         $input['id']                 = $data['id'];
         $input['next_creation_date'] = $tr->computeNextCreationDate($data['begin_date'],
                                                                     $data['periodicity'],
                                                                     $data['create_before']);
         $tr->update($input);
      }

      return $result;
   }

}
?>