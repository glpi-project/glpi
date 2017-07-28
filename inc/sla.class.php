<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/** @file
* @brief
* @since version 9.2
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLA Class
**/
class SLA extends CommonDBChild {

   // From CommonDBTM
   var $dohistory                      = true;

   // From CommonDBChild
   static public $itemtype             = 'SLM';
   static public $items_id             = 'slms_id';

   static $rightname                   = 'slm';

   static protected $forward_entity_to = ['SLALevel'];

   static function getTypeName($nb = 0) {
      // Acronymous, no plural
      return __('SLA');
   }


   /**
    * Define calendar of the ticket using the SLA when using this calendar as sla-s calendar
    *
    * @param $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('SlaLevel', $ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);

      return $ong;
   }


   function getFromDB($ID) {
      if (!parent::getFromDB($ID)) {
         return false;
      }

      // get calendar from sla
      $slm = new SLM;
      if ($slm->getFromDB($this->fields['slms_id'])) {
         $this->fields['calendars_id'] = $slm->fields['calendars_id'];
         return true;
      } else {
         return false;
      }
   }


   function post_getEmpty() {
      $this->fields['number_time'] = 4;
      $this->fields['definition_time'] = 'hour';
   }


   function cleanDBonPurge() {
      global $DB;

      // Clean sla_levels
      $slalevel = new SlaLevel();
      $slalevel->deleteByCriteria(['slas_id' => $this->getID()]);

      // Update tickets : clean SLA
      list($dateField, $slaField) = self::getSlaFieldNames($this->fields['type']);
      $query = "SELECT `id`
                FROM `glpi_tickets`
                WHERE `$slaField` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $ticket = new Ticket();
            while ($data = $DB->fetch_assoc($result)) {
               $ticket->deleteSLA($data['id'], $this->fields['type']);
            }
         }
      }

      Rule::cleanForItemAction($this);
   }


   /**
    * Print the sla form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
   **/
   function showForm($ID, $options = []) {

      $rowspan = 4;
      if ($ID > 0) {
         $rowspan = 6;
      }

      // Get SLM object
      $slm = new SLM();
      if (isset($options['parent'])) {
         $slm = $options['parent'];
      } else {
         $slm->getFromDB($this->fields['slms_id']);
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[static::$items_id] = $slm->getField('id');

         $this->check(-1, CREATE, $options);
      }

      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['value' => $this->fields["name"]]);
      echo "<td rowspan='".$rowspan."'>".__('Comments')."</td>";
      echo "<td rowspan='".$rowspan."'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('SLM')."</td>";
      echo "<td>";
      echo $slm->getLink();
      echo "<input type='hidden' name='slms_id' value='".$this->fields['slms_id']."'>";
      echo "</td></tr>";

      if ($ID > 0) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".__('Last update')."</td>";
         echo "<td>".($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
         echo "</td></tr>";
      }

      echo "<tr class='tab_bg_1'><td>".__('Type')."</td>";
      echo "<td>";
      self::getSlaTypeDropdown(['value' => $this->fields["type"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>".__('Maximum time')."</td>";
      echo "<td>";
      Dropdown::showNumber("number_time", ['value' => $this->fields["number_time"],
                                           'min'   => 0]);
      $possible_values = ['minute'   => _n('Minute', 'Minutes', Session::getPluralNumber()),
                          'hour'     => _n('Hour', 'Hours', Session::getPluralNumber()),
                          'day'      => _n('Day', 'Days', Session::getPluralNumber())];
      $rand = Dropdown::showFromArray('definition_time', $possible_values,
                                      ['value'     => $this->fields["definition_time"],
                                       'on_change' => 'appearhideendofworking()']);
      echo "\n<script type='text/javascript' >\n";
      echo "function appearhideendofworking() {\n";
      echo "if ($('#dropdown_definition_time$rand option:selected').val() == 'day') {
               $('#title_endworkingday').show();
               $('#dropdown_endworkingday').show();
            } else {
               $('#title_endworkingday').hide();
               $('#dropdown_endworkingday').hide();
            }";
      echo "}\n";
      echo "appearhideendofworking();\n";
      echo "</script>\n";

      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td><div id='title_endworkingday'>".__('End of working day')."</div></td>";
      echo "<td><div id='dropdown_endworkingday'>";
      Dropdown::showYesNo("end_of_working_day", $this->fields["end_of_working_day"]);
      echo "</div></td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the HTML array for SLAs linked to a SLA
    *
    * @param SLM $slm Slm item
    */
   static function showForSlm(SLM $slm) {
      global $CFG_GLPI;

      $instID   = $slm->fields['id'];
      $sla      = new self();
      $calendar = new Calendar();

      if (!$slm->can($instID, READ)) {
         return false;
      }

      $canedit = ($slm->canEdit($instID)
                  && isset($_SESSION["glpiactiveprofile"])
                  && $_SESSION["glpiactiveprofile"]["interface"] == "central");

      $rand = mt_rand();

      if ($canedit) {
         echo "<div id='viewsla$instID$rand'></div>\n";

         echo "<script type='text/javascript' >";
         echo "function viewAddSla$instID$rand() {";
         $params = ['type'                     => $sla->getType(),
                    'parenttype'               => $slm->getType(),
                    $slm->getForeignKeyField() => $instID,
                    'id'                       => -1];
         Ajax::updateItemJsCode("viewsla$instID$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "}";
         echo "</script>";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddSla$instID$rand();'>";
         echo __('Add a new SLA')."</a></div>\n";
      }

      // SLA list
      $slaList = $sla->find("`slms_id` = '".$instID."'");
      Session::initNavigateListItems('SLA',
      //TRANS : %1$s is the itemtype name,
      //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), $slm::getTypeName(1), $slm->getName()));
      echo "<div class='spaced'>";
      if (count($slaList)) {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['container' => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_top .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_top .= "</th>";
            $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= "</th>";
         }
         $header_end .= "<th>".__('Name')."</th>";
         $header_end .= "<th>".__('Type')."</th>";
         $header_end .= "<th>".__('Maximum time')."</th>";
         $header_end .= "<th>".__('Calendar')."</th>";

         echo $header_begin.$header_top.$header_end;
         foreach ($slaList as $val) {
            $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditSla".
                        $instID.$val["id"]."$rand();\""
                        : '');
            echo "\n<script type='text/javascript' >\n";
            echo "function viewEditSla". $instID.$val["id"]."$rand() {\n";
            $params = ['type'                     => $sla->getType(),
                       'parenttype'               => $slm->getType(),
                       $slm->getForeignKeyField() => $instID,
                       'id'                       => $val["id"]];
            Ajax::updateItemJsCode("viewsla$instID$rand",
                                   $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";

            echo "<tr class='tab_bg_1'>";
            echo "<td width='10' $edit>";
            if ($canedit) {
               Html::showMassiveActionCheckBox($sla->getType(), $val['id']);
            }
            echo "</td>";
            $sla->getFromDB($val['id']);
            echo "<td $edit>".$sla->getLink()."</td>";
            echo "<td $edit>".$sla->getSpecificValueToDisplay('type', $sla->fields['type'])."</td>";
            echo "<td $edit>";
            echo $sla->getSpecificValueToDisplay('number_time',
                  ['number_time'     => $sla->fields['number_time'],
                   'definition_time' => $sla->fields['definition_time']]);
            echo "</td>";
            if (!$slm->fields['calendars_id']) {
               $link =  __('24/7');
            } else if ($slm->fields['calendars_id'] == -1) {
               $link = __('Calendar of the ticket');
            } else if ($calendar->getFromDB($slm->fields['calendars_id'])) {
               $link = $calendar->getLink();
            }
            echo "<td $edit>".$link."</td>";
            echo "</tr>";
         }
         echo $header_begin.$header_bottom.$header_end;
         echo "</table>";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
      } else {
         echo __('No item to display');
      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'SLM' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_slas', ['slms_id' => $item->getField('id')]);
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'SLM' :
            self::showForSlm($item);
            break;
      }
      return true;
   }


   /**
    * Get SLA data by type and ticket
    *
    * @param integer $tickets_id Ticket ID
    * @param integer $type type
    *
    * @return boolean
    */
   function getSlaDataForTicket($tickets_id, $type) {

      switch ($type) {
         case SLM::TTR :
            $field = 'slas_ttr_id';
            break;

         case SLM::TTO :
            $field = 'slas_tto_id';
            break;
      }
      return $this->getFromDBByQuery("INNER JOIN `glpi_tickets` ON (`glpi_tickets`.`$field` = `".$this->getTable()."`.`id`) WHERE `glpi_tickets`.`id` = '".$tickets_id."' LIMIT 1");
   }


   /**
    * Get SLA datas by condition
    *
    * @param string $condition condition used to search if needed (empty get all) (default '')
    *
    * @return array all retrieved data in a associative array by id
    */
   function getSlaData($condition) {
      return $this->find($condition);
   }


   /**
    * Get SLA table fields
    *
    * @param integer $type slm type
    *
    * @return array name of date and sla fields
    */
   static function getSlaFieldNames($type) {

      $dateField = null;
      $slaField  = null;

      switch ($type) {
         case SLM::TTO:
            $dateField = 'time_to_own';
            $slaField  = 'slas_tto_id';
            break;

         case SLM::TTR:
            $dateField = 'time_to_resolve';
            $slaField  = 'slas_ttr_id';
            break;
      }
      return [$dateField, $slaField];
   }


   /**
    * Show SLA for ticket
    *
    * @param  Ticket         $ticket Ticket item
    * @param  integer        $type
    * @param  TicketTemplate $tt ticket template object
    * @param  bool           $canupdate update right
    */
   function showSlaForTicket(Ticket $ticket, $type, $tt, $canupdate) {
      global $CFG_GLPI;

      list($dateField, $slaField) = self::getSlaFieldNames($type);

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_1'>";

      if (!isset($ticket->fields[$dateField]) || $ticket->fields[$dateField] == 'NULL') {
         $ticket->fields[$dateField]='';
      }

      if ($ticket->fields['id']) {
         if ($this->getSlaDataForTicket($ticket->fields['id'], $type)) {
            echo "<td>";
            echo Html::convDateTime($ticket->fields[$dateField]);
            echo "</td>";
            echo "<th>".__('SLA')."</th>";
            echo "<td>";
            echo Dropdown::getDropdownName('glpi_slas', $ticket->fields[$slaField])."&nbsp;";
            $commentsla = "";
            $slalevel   = new SlaLevel();
            $nextaction = new SlaLevel_Ticket();
            if ($nextaction->getFromDBForTicket($ticket->fields["id"], $type)) {
               $commentsla .= '<span class="b spaced">'.
                                sprintf(__('Next escalation: %s'),
                                        Html::convDateTime($nextaction->fields['date'])).
                                           '</span><br>';
               if ($slalevel->getFromDB($nextaction->fields['slalevels_id'])) {
                  $commentsla .= '<span class="b spaced">'.
                                   sprintf(__('%1$s: %2$s'), __('Escalation level'),
                                           $slalevel->getName()).'</span>';
               }
            }

            $slaoptions = [];
            if (Session::haveRight('slm', READ)) {
               $slaoptions['link'] = Toolbox::getItemTypeFormURL('SLA').
                                          "?id=".$this->fields["id"];
            }
            Html::showToolTip($commentsla, $slaoptions);
            if ($canupdate) {
               $fields = ['sla_delete'        => 'sla_delete',
                          'id'                => $ticket->getID(),
                          'type'              => $type,
                          '_glpi_csrf_token'  => Session::getNewCSRFToken(),
                          '_glpi_simple_form' => 1];
               $JS = "  function delete_date$type(){
                           if (nativeConfirm('".addslashes(__('Also delete date ?'))."')) {
                              submitGetLink('".$ticket->getFormURL()."',
                                            ".json_encode(array_merge($fields,
                                                                      ['delete_date' => 1])).");
                           } else {
                              submitGetLink('".$ticket->getFormURL()."',
                                            ".json_encode(array_merge($fields,
                                                                      ['delete_date' => 0])).");
                           }
                        }";
               echo Html::scriptBlock($JS);
               echo "<a class='pointer' onclick='delete_date$type();return false;'>";
               echo "<img src='".$CFG_GLPI['root_doc']."/pics/delete.png' title='".
                      _x('button', 'Delete permanently')."' "
                     . "alt='"._x('button', 'Delete permanently')."' class='pointer'>";
               echo "</a>";
            }
            echo "</td>";

         } else {
            echo "<td width='200px'>";
            echo $tt->getBeginHiddenFieldValue($dateField);
            if ($canupdate) {
               Html::showDateTimeField($dateField, ['value'      => $ticket->fields[$dateField],
                                                    'timestep'   => 1,
                                                    'maybeempty' => true]);
            } else {
               echo Html::convDateTime($ticket->fields[$dateField]);
            }
            echo $tt->getEndHiddenFieldValue($dateField, $ticket);
            echo "</td>";
            $sql_entities = getEntitiesRestrictRequest("", "", "", $ticket->fields['entities_id'], true);
            $sla_data     = $this->getSlaData("`type` = '$type' AND $sql_entities");
            if ($canupdate
                && !empty($sla_data)) {
               echo "<td>";
               echo $tt->getBeginHiddenFieldText($slaField);
               echo "<span id='sla_action$type'>";
               echo "<a ".Html::addConfirmationOnAction([__('The assignment of a SLA to a ticket causes the recalculation of the date.'),
                       __("Escalations defined in the SLA will be triggered under this new date.")],
                                                    "cleanhide('sla_action$type');cleandisplay('sla_choice$type');").
                    " class='pointer' title='".__('SLA')."'><i class='fa fa-clock-o slt'></i><span class='sr-only'>".__('SLA')."</span></a>";
               echo "</span>";
               echo "<div id='sla_choice$type' style='display:none'>";
               echo "<span  class='b'>".__('SLA')."</span>&nbsp;";
               Sla::dropdown(['name'      => $slaField,
                              'entity'    => $ticket->fields["entities_id"],
                              'condition' => "`type` = '".$type."'"]);
               echo "</div>";
               echo $tt->getEndHiddenFieldText($slaField);
               echo "</td>";
            }
         }

      } else { // New Ticket
         echo "<td>";
         echo $tt->getBeginHiddenFieldValue($dateField);
         Html::showDateTimeField($dateField, ['value'      => $ticket->fields[$dateField],
                                              'timestep'   => 1,
                                              'maybeempty' => false,
                                              'canedit'    => $canupdate]);
         echo $tt->getEndHiddenFieldValue($dateField, $ticket);
         echo "</td>";
         $sql_entities = getEntitiesRestrictRequest("", "", "", $ticket->fields['entities_id'], true);
         $sla_data     = $this->getSlaData("`type` = '$type' AND $sql_entities");
         if ($canupdate
             && !empty($sla_data)) {
            echo "<th>".$tt->getBeginHiddenFieldText($slaField);
            if (!$tt->isHiddenField($slaField) || $tt->isPredefinedField($slaField)) {
               echo "<th>".sprintf(__('%1$s%2$s'), __('SLA'), $tt->getMandatoryMark($slaField))."</th>";
            }
            echo $tt->getEndHiddenFieldText($slaField);
            echo "<td class='nopadding'>".$tt->getBeginHiddenFieldValue($slaField);
            Sla::dropdown(['name'      => $slaField,
                           'entity'    => $ticket->fields["entities_id"],
                           'value'     => isset($ticket->fields[$slaField])
                                             ? $ticket->fields[$slaField] : 0,
                           'condition' => "`type` = '".$type."'"]);
            echo $tt->getEndHiddenFieldValue($slaField, $ticket);
            echo "</td>";
         }
      }

      echo "</tr>";
      echo "</table>";
   }


   function getSearchOptionsNew() {
      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'number_time',
         'name'               => __('Time'),
         'datatype'           => 'specific',
         'massiveaction'      => false,
         'nosearch'           => true,
         'additionalfields'   => ['definition_time']
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'end_of_working_day',
         'name'               => __('End of working day'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'type',
         'name'               => __('Type'),
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => 'glpi_slms',
         'field'              => 'name',
         'name'               => __('SLM'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      return $tab;
   }


   /**
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'number_time' :
            switch ($values['definition_time']) {
               case 'minute' :
                  return sprintf(_n('%d minute', '%d minutes', $values[$field]), $values[$field]);

               case 'hour' :
                  return sprintf(_n('%d hour', '%d hours', $values[$field]), $values[$field]);

               case 'day' :
                  return sprintf(_n('%d day', '%d days', $values[$field]), $values[$field]);
            }
            break;

         case 'type' :
            return self::getSlaTypeName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @param $field
    * @param $name            (default '')
    * @param $values          (default '')
    * @param $options   array
    *
    * @return string
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'type':
            $options['value'] = $values[$field];
            return self::getSlaTypeDropdown($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get date based on a sla
    *
    * @param datetime $start_date start date
    * @param integer  $additional_delay additional delay to add or substract (for waiting time)
    *                                     (default 0)
    *
    * @return datetime due date time (null if slm not exists)
    **/
   function computeDate($start_date, $additional_delay = 0) {

      if (isset($this->fields['id'])) {
         $delay = $this->getSLATime();
         // Based on a calendar
         if ($this->fields['calendars_id'] > 0) {
            $cal          = new Calendar();
            $work_in_days = ($this->fields['definition_time'] == 'day');

            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->computeEndDate($start_date, $delay,
                                           $additional_delay, $work_in_days,
                                           $this->fields['end_of_working_day']);
            }
         }

         // No calendar defined or invalid calendar
         if ($this->fields['number_time'] >= 0) {
            $starttime = strtotime($start_date);
            $endtime   = $starttime+$delay+$additional_delay;
            return date('Y-m-d H:i:s', $endtime);
         }
      }

      return null;
   }


   /**
    * Get computed resolution time
    *
    * @return integer resolution time (default 0)
   **/
   function getSLATime() {

      if (isset($this->fields['id'])) {
         if ($this->fields['definition_time'] == "minute") {
            return $this->fields['number_time'] * MINUTE_TIMESTAMP;
         }
         if ($this->fields['definition_time'] == "hour") {
            return $this->fields['number_time'] * HOUR_TIMESTAMP;
         }
         if ($this->fields['definition_time'] == "day") {
            return $this->fields['number_time'] * DAY_TIMESTAMP;
         }
      }
      return 0;
   }


   /**
    * Get execution date of a sla level
    *
    * @param datetime $start_date start date
    * @param integer  $slalevels_id sla level id
    * @param integer  $additional_delay additional delay to add or substract (for waiting time)
    *                                        (default 0)
    *
    * @return datetime execution date time (null if sla not exists)
   **/
   function computeExecutionDate($start_date, $slalevels_id, $additional_delay = 0) {

      if (isset($this->fields['id'])) {
         $slalevel = new SlaLevel();

         if ($slalevel->getFromDB($slalevels_id)) { // sla level exists
            if ($slalevel->fields['slas_id'] == $this->fields['id']) { // correct sla level
               $work_in_days = ($this->fields['definition_time'] == 'day');
               $delay        = $this->getSLATime();

               // Based on a calendar
               if ($this->fields['calendars_id'] > 0) {
                  $cal = new Calendar();
                  if ($cal->getFromDB($this->fields['calendars_id'])) {
                     return $cal->computeEndDate($start_date, $delay,
                                                 $slalevel->fields['execution_time'] + $additional_delay,
                                                 $work_in_days);
                  }
               }
               // No calendar defined or invalid calendar
               $delay    += $additional_delay+$slalevel->fields['execution_time'];
               $starttime = strtotime($start_date);
               $endtime   = $starttime+$delay;
               return date('Y-m-d H:i:s', $endtime);
            }
         }
      }
      return null;
   }


   /**
    * Get active time between to date time for the active calendar
    *
    * @param datetime $start begin
    * @param datetime $end end
    *
    * @return integer timestamp of delay
    **/
   function getActiveTimeBetween($start, $end) {

      if ($end < $start) {
         return 0;
      }

      if (isset($this->fields['id'])) {
         $cal          = new Calendar();
         $work_in_days = ($this->fields['definition_time'] == 'day');

         // Based on a calendar
         if ($this->fields['calendars_id'] > 0) {
            if ($cal->getFromDB($this->fields['calendars_id'])) {
               return $cal->getActiveTimeBetween($start, $end, $work_in_days);
            }

         } else { // No calendar
            $timestart = strtotime($start);
            $timeend   = strtotime($end);
            return ($timeend-$timestart);
         }
      }
      return 0;
   }


   /**
    * Add a level to do for a ticket
    *
    * @param Ticket  $ticket Ticket object
    * @param integer $slalevels_id SlaLevel ID
    *
    * @return datetime execution date time (NULL if sla not exists)
    **/
   function addLevelToDo(Ticket $ticket, $slalevels_id = 0) {

      $slalevels_id = ($slalevels_id ? $slalevels_id
                                     : $ticket->fields["ttr_slalevels_id"]);
      if ($ticket->fields["ttr_slalevels_id"] > 0) {
         $toadd = [];
         $date = $this->computeExecutionDate($ticket->fields['date'], $slalevels_id,
                                             $ticket->fields['sla_waiting_duration']);
         if ($date != null) {
            $toadd['date']         = $date;
            $toadd['slalevels_id'] = $slalevels_id;
            $toadd['tickets_id']   = $ticket->fields["id"];
            $slalevelticket        = new SlaLevel_Ticket();
            $slalevelticket->add($toadd);
         }
      }
   }


   /**
    * Add a level to do for a ticket
    *
    * @param Ticket $ticket object
    *
    * @return datetime execution date time (NULL if sla not exists)
    **/
   static function deleteLevelsToDo(Ticket $ticket) {
      global $DB;

      if ($ticket->fields["ttr_slalevels_id"] > 0) {
         $query = "SELECT *
                   FROM `glpi_slalevels_tickets`
                   WHERE `tickets_id` = '".$ticket->fields["id"]."'";

         $slalevelticket = new SlaLevel_Ticket();
         foreach ($DB->request($query) as $data) {
            $slalevelticket->delete(['id' => $data['id']]);
         }
      }
   }


   function prepareInputForAdd($input) {

      if ($input['definition_time'] != 'day') {
         $input['end_of_working_day'] = 0;
      }
      return $input;
   }


   function prepareInputForUpdate($input) {

      if (isset($input['definition_time']) && $input['definition_time'] != 'day') {
         $input['end_of_working_day'] = 0;
      }
      return $input;
   }

   /**
    * Get SLA types
    *
    * @return array array of types
    **/
   static function getSlaTypes() {

      return [SLM::TTO => __('Time to own'),
              SLM::TTR => __('Time to resolve')];
   }


   /**
    * Get SLA types name
    *
    * @param type $type
    * @return string name
    **/
   static function getSlaTypeName($type) {

      $types = self::getSlaTypes();
      $name  = null;
      if (isset($types[$type])) {
         $name = $types[$type];
      }
      return $name;
   }


   /**
    * Get SLA types dropdown
    *
    * @param array $options
    *
    * @return string
    */
   static function getSlaTypeDropdown($options) {

      $params = ['name'  => 'type'];

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      return Dropdown::showFromArray($params['name'], self::getSlaTypes(), $options);
   }

}
