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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * OLA Class
 * @since version 9.1
**/
class OLA extends CommonDBChild {

   // From CommonDBTM
   var $dohistory                      = true;

   // From CommonDBChild
   static public $itemtype             = 'SLM';
   static public $items_id             = 'slms_id';

   static $rightname                   = 'slm';

   static protected $forward_entity_to = array('OLALevel');

   static function getTypeName($nb=0) {
      return _n('OLA', 'OLAs', $nb);
   }


   /**
    * Define calendar of the ticket using the OLA when using this calendar as ola-s calendar
    *
    * @param $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('OlaLevel', $ong, $options);
      $this->addStandardTab('Rule', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);

      return $ong;
   }

   /**
    * @see CommonDBTM::getFromDB
   **/
   function getFromDB($ID) {
      if (!parent::getFromDB($ID)) {
         return false;
      }

      // get calendar from ola
      $slm = new SLM;
      if ($slm->getFromDB($this->fields['slms_id'])) {
         $this->fields['calendars_id'] = $slm->fields['calendars_id'];
         return true;
      } else {
         return false;
      }
   }


   /**
    * @see CommonDBTM::post_getEmpty()
   */
   function post_getEmpty() {
      $this->fields['number_time'] = 4;
      $this->fields['definition_time'] = 'hour';
   }


   function cleanDBonPurge() {
      global $DB;

      // Clean ola_levels
      $query = "SELECT `id`
                FROM `glpi_olalevels`
                WHERE `olas_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $olalevel = new OlaLevel();
            while ($data = $DB->fetch_assoc($result)) {
               $olalevel->delete($data);
            }
         }
      }

      // Update tickets : clean OLA
      list($dateField, $olaField) = self::getOlaFieldNames($this->fields['type']);
      $query = "SELECT `id`
                FROM `glpi_tickets`
                WHERE `$olaField` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $ticket = new Ticket();
            while ($data = $DB->fetch_assoc($result)) {
               $ticket->deleteOLA($data['id'], $this->fields['type']);
            }
         }
      }

      Rule::cleanForItemAction($this);
   }


   /**
    * Print the ola form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
   **/
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $rowspan = 3;
      if ($ID > 0) {
         $rowspan = 5;
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

         //force itemtype of parent
         static::$itemtype = get_class($slm);

         $this->check(-1, CREATE, $options);
      }

      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", array('value' => $this->fields["name"]));
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
      self::getOlaTypeDropdown(array('value' => $this->fields["type"]));
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>".__('Maximum time')."</td>";
      echo "<td>";
      Dropdown::showNumber("number_time", array('value' => $this->fields["number_time"],
                                                    'min'   => 0));
      $possible_values = array('minute'   => _n('Minute', 'Minutes', Session::getPluralNumber()),
                               'hour'     => _n('Hour', 'Hours', Session::getPluralNumber()),
                               'day'      => _n('Day', 'Days', Session::getPluralNumber()));
      $rand = Dropdown::showFromArray('definition_time', $possible_values,
                                      array('value'     => $this->fields["definition_time"],
                                            'on_change' => 'appearhideendofworking()'));
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
      echo "</div></td>";

      echo "<td colspan='2'>";
      echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='".__s('Warning')."'>";
      echo __('The internal time is recalculated when assigning the OLA')."</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the HTML array for OLAs linked to a OLA
    *
    * @param OLA $ola
    * @return boolean
    */
   static function showForOla(SLM $slm) {
      global $CFG_GLPI;

      $instID   = $slm->fields['id'];
      $ola      = new self();
      $calendar = new Calendar();

      if (!$slm->can($instID, READ)) {
         return false;
      }

      $canedit = ($slm->canEdit($instID)
                  && isset($_SESSION["glpiactiveprofile"])
                  && $_SESSION["glpiactiveprofile"]["interface"] == "central");

      $rand = mt_rand();

      if ($canedit) {
         echo "<div id='viewola$instID$rand'></div>\n";

         echo "<script type='text/javascript' >";
         echo "function viewAddOla$instID$rand() {";
         $params = array('type'                     => $ola->getType(),
                         'parenttype'               => $slm->getType(),
                         $slm->getForeignKeyField() => $instID,
                         'id'                       => -1);
         Ajax::updateItemJsCode("viewola$instID$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "}";
         echo "</script>";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddOla$instID$rand();'>";
         echo __('Add a new OLA')."</a></div>\n";
      }

      // OLA list
      $olaList = $ola->find("`slms_id` = '".$instID."'");
      Session::initNavigateListItems('OLA',
      //TRANS : %1$s is the itemtype name,
      //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), $slm::getTypeName(1), $slm->getName()));
      echo "<div class='spaced'>";
      if (count($olaList)) {
         if ($canedit) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = array('container' => 'mass'.__CLASS__.$rand);
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
         foreach ($olaList as $val) {
            $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditOla".
                        $instID.$val["id"]."$rand();\""
                        : '');
            echo "\n<script type='text/javascript' >\n";
            echo "function viewEditOla". $instID.$val["id"]."$rand() {\n";
            $params = array('type'                     => $ola->getType(),
                            'parenttype'               => $slm->getType(),
                            $slm->getForeignKeyField() => $instID,
                            'id'                       => $val["id"]);
            Ajax::updateItemJsCode("viewola$instID$rand",
                                   $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";

            echo "<tr class='tab_bg_1'>";
            echo "<td width='10' $edit>";
            if ($canedit) {
               Html::showMassiveActionCheckBox($ola->getType(), $val['id']);
            }
            echo "</td>";
            $ola->getFromDB($val['id']);
            echo "<td $edit>".$ola->getLink()."</td>";
            echo "<td $edit>".$ola->getSpecificValueToDisplay('type', $ola->fields['type'])."</td>";
            echo "<td $edit>";
            echo $ola->getSpecificValueToDisplay('number_time',
                  array('number_time'     => $ola->fields['number_time'],
                        'definition_time' => $ola->fields['definition_time']));
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


   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'SLM' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_olas', ['slms_id' => $item->getField('id')]);
               }
               return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   /**
    *
    * @param $item            CommonGLPI item
    * @param $tabnum          (default 1)
    * @param $withtemplate    (default 0)
    *
    * @return boolean
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'SLM' :
            self::showForOla($item);
            break;
      }
      return true;
   }


   /**
    * Get OLA data by type and ticket
    *
    * @param $tickets_id
    * @param $type
    */
   function getOlaDataForTicket($tickets_id, $type) {

      switch ($type) {
         case SLM::TTR :
            $field = 'olas_ttr_id';
            break;

         case SLM::TTO :
            $field = 'olas_tto_id';
            break;
      }
      return $this->getFromDBByQuery("INNER JOIN `glpi_tickets` ON (`glpi_tickets`.`$field` = `".$this->getTable()."`.`id`) WHERE `glpi_tickets`.`id` = '".$tickets_id."' LIMIT 1");
   }


    /**
    * Get OLA datas by condition
    *
    * @param $condition
   **/
   function getOlaData($condition) {
      return $this->find($condition);
   }


   /**
    * Get OLA table fields
    *
    * @param $type
    *
    * @return array
   **/
   static function getOlaFieldNames($type) {

      $dateField = null;
      $olaField  = null;

      switch ($type) {
         case SLM::TTO:
            $dateField = 'internal_time_to_own';
            $olaField  = 'olas_tto_id';
            break;

         case SLM::TTR:
            $dateField = 'internal_time_to_resolve';
            $olaField  = 'olas_ttr_id';
            break;
      }
      return array($dateField, $olaField);
   }


   /**
    * Show OLA for ticket
    *
    * @param $ticket      Ticket item
    * @param $type
    * @param $tt
    * @param $canupdate
   **/
   function showOlaForTicket(Ticket $ticket, $type, $tt, $canupdate) {
      global $CFG_GLPI;

      list($dateField, $olaField) = self::getOlaFieldNames($type);

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_1'>";

      if (!isset($ticket->fields[$dateField]) || $ticket->fields[$dateField] == 'NULL') {
         $ticket->fields[$dateField]='';
      }

      if ($ticket->fields['id']) {
         if ($this->getOlaDataForTicket($ticket->fields['id'], $type)) {
            echo "<td>";
            echo Html::convDateTime($ticket->fields[$dateField]);
            echo "</td>";
            echo "<th>".__('OLA')."</th>";
            echo "<td>";
            echo Dropdown::getDropdownName('glpi_olas', $ticket->fields[$olaField])."&nbsp;";
            $commentola = "";
            $olalevel   = new OlaLevel();
            $nextaction = new OlaLevel_Ticket();
            if ($nextaction->getFromDBForTicket($ticket->fields["id"], $type)) {
               $commentola .= '<span class="b spaced">'.
                                sprintf(__('Next escalation: %s'),
                                        Html::convDateTime($nextaction->fields['date'])).
                                           '</span><br>';
               if ($olalevel->getFromDB($nextaction->fields['olalevels_id'])) {
                  $commentola .= '<span class="b spaced">'.
                                   sprintf(__('%1$s: %2$s'), __('Escalation level'),
                                           $olalevel->getName()).'</span>';
               }
            }

            $olaoptions = array();
            if (Session::haveRight('slm', READ)) {
               $olaoptions['link'] = Toolbox::getItemTypeFormURL('OLA').
                                          "?id=".$this->fields["id"];
            }
            Html::showToolTip($commentola, $olaoptions);
            if ($canupdate) {
               $fields = array('ola_delete'        => 'ola_delete',
                               'id'                => $ticket->getID(),
                               'type'              => $type,
                               '_glpi_csrf_token'  => Session::getNewCSRFToken(),
                               '_glpi_simple_form' => 1);
               $JS = "  function delete_internal_date$type(){
                           if (nativeConfirm('".addslashes(__('Also delete date ?'))."')) {
                              submitGetLink('".$ticket->getFormURL()."',
                                            ".json_encode(array_merge($fields,
                                                                      array('delete_date' => 1))).");
                           } else {
                              submitGetLink('".$ticket->getFormURL()."',
                                            ".json_encode(array_merge($fields,
                                                                      array('delete_date' => 0))).");
                           }
                        }";
               echo Html::scriptBlock($JS);
               echo "<a class='fa fa-times-circle pointer' onclick='delete_internal_date$type();return false;' title='".
                      _x('button', 'Delete permanently')."'>";
               echo "<span class='sr-only'>"._x('button', 'Delete permanently')."</span>";
               echo "</a>";
            }
            echo "</td>";

         } else {
            echo "<td width='200px'>";
            echo $tt->getBeginHiddenFieldValue($dateField);
            if ($canupdate) {
               Html::showDateTimeField($dateField, array('value'      => $ticket->fields[$dateField],
                                                         'timestep'   => 1,
                                                         'maybeempty' => true));
            } else {
               echo Html::convDateTime($ticket->fields[$dateField]);
            }
            echo $tt->getEndHiddenFieldValue($dateField, $ticket);
            echo "</td>";
            $sql_entities = getEntitiesRestrictRequest("", "", "", $ticket->fields['entities_id'], true);
            $ola_data     = $this->getOlaData("`type` = '$type' AND $sql_entities");
            if ($canupdate
                && !empty($ola_data)) {
               echo "<td>";
               echo $tt->getBeginHiddenFieldText($olaField);
               echo "<span id='ola_action$type'>";
               echo "<a ".Html::addConfirmationOnAction(array(__('The assignment of a OLA to a ticket causes the recalculation of the date.'),
                       __("Escalations defined in the OLA will be triggered under this new date.")),
                                                    "cleanhide('ola_action$type');cleandisplay('ola_choice$type');").
                     " class='pointer' title='".__('OLA')."'><i class='fa fa-clock-o slt'></i><span class='sr-only'>".__('OLA')."</span></a>";
               echo "</span>";
               echo "<div id='ola_choice$type' style='display:none'>";
               echo "<span  class='b'>".__('OLA')."</span>&nbsp;";
               Ola::dropdown(array('name'      => $olaField,
                                   'entity'    => $ticket->fields["entities_id"],
                                   'condition' => "`type` = '".$type."'"));
               echo "</div>";
               echo $tt->getEndHiddenFieldText($olaField);
               echo "</td>";
            }
         }

      } else { // New Ticket
         echo "<td>";
         echo $tt->getBeginHiddenFieldValue($dateField);
         Html::showDateTimeField($dateField, array('value'      => $ticket->fields[$dateField],
                                                   'timestep'   => 1,
                                                   'maybeempty' => false,
                                                   'canedit'    => $canupdate,
                                                   'required'   => ($tt->isMandatoryField($dateField) && !$ticket->getID())));
         echo $tt->getEndHiddenFieldValue($dateField, $ticket);
         echo "</td>";
         $sql_entities = getEntitiesRestrictRequest("", "", "", $ticket->fields['entities_id'], true);
         $ola_data     = $this->getOlaData("`type` = '$type' AND $sql_entities");
         if ($canupdate
             && !empty($ola_data)) {
            echo "<th>".$tt->getBeginHiddenFieldText($olaField);
            if (!$tt->isHiddenField($olaField) || $tt->isPredefinedField($olaField)) {
               echo "<th>".sprintf(__('%1$s%2$s'), __('OLA'), $tt->getMandatoryMark($olaField))."</th>";
            }
            echo $tt->getEndHiddenFieldText($olaField);
            echo "<td class='nopadding'>".$tt->getBeginHiddenFieldValue($olaField);
            Ola::dropdown(array('name'      => $olaField,
                                'entity'    => $ticket->fields["entities_id"],
                                'value'     => isset($ticket->fields[$olaField])
                                                  ? $ticket->fields[$olaField] : 0,
                                'condition' => "`type` = '".$type."'"));
            echo $tt->getEndHiddenFieldValue($olaField, $ticket);
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
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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
            return self::getOlaTypeName($values[$field]);
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
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
      }
      $options['display'] = false;
      switch ($field) {
         case 'type':
            $options['value'] = $values[$field];
            return self::getOlaTypeDropdown($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get date based on a ola
    *
    * @param $start_date         datetime start date
    * @param $additional_delay   integer  additional delay to add or substract (for waiting time)
    *                                     (default 0)
    *
    * @return due date time (NULL if ola not exists)
   **/
   function computeDate($start_date, $additional_delay=0) {

      $start_date = date('Y-m-d H:i:s');

      if (isset($this->fields['id'])) {
         $delay = $this->getOLATime();
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

      return NULL;
   }


   /**
    * Get computed resolution time
    *
    * @return resolution time
   **/
   function getOLATime() {

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
    * Get execution date of a ola level
    *
    * @param $start_date         datetime    start date
    * @param $olalevels_id       integer     ola level id
    * @param $additional_delay   integer     additional delay to add or substract (for waiting time)
    *                                        (default 0)
    *
    * @return execution date time (NULL if ola not exists)
   **/
   function computeExecutionDate($start_date, $olalevels_id, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         $olalevel = new OlaLevel();

         if ($olalevel->getFromDB($olalevels_id)) { // ola level exists
            if ($olalevel->fields['olas_id'] == $this->fields['id']) { // correct ola level
               $work_in_days = ($this->fields['definition_time'] == 'day');
               $delay        = $this->getOLATime();

               // Based on a calendar
               if ($this->fields['calendars_id'] > 0) {
                  $cal = new Calendar();
                  if ($cal->getFromDB($this->fields['calendars_id'])) {
                     return $cal->computeEndDate($start_date, $delay,
                                                 $olalevel->fields['execution_time'] + $additional_delay,
                                                 $work_in_days);
                  }
               }
               // No calendar defined or invalid calendar
               $delay    += $additional_delay+$olalevel->fields['execution_time'];
               $starttime = strtotime($start_date);
               $endtime   = $starttime+$delay;
               return date('Y-m-d H:i:s', $endtime);
            }
         }
      }
      return NULL;
   }


   /**
    * Get active time between to date time for the active calendar
    *
    * @param $start  datetime begin
    * @param $end    datetime end
    *
    * @return timestamp of delay
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
    * @param $ticket          Ticket object
    * @param $olalevels_id
    *
    * @return execution date time (NULL if ola not exists)
   **/
   function addLevelToDo(Ticket $ticket, $olalevels_id = 0) {

      $olalevels_id = ($olalevels_id ? $olalevels_id
                                     : $ticket->fields["ttr_olalevels_id"]);
      if ($olalevels_id > 0) {
         $toadd = array();
         $date = $this->computeExecutionDate($ticket->fields['date'], $olalevels_id,
                                             $ticket->fields['ola_waiting_duration']);
         if ($date != null) {
            $toadd['date']         = $date;
            $toadd['olalevels_id'] = $olalevels_id;
            $toadd['tickets_id']   = $ticket->fields["id"];
            $olalevelticket        = new OlaLevel_Ticket();
            $olalevelticket->add($toadd);
         }
      }
   }


   /**
    * Add a level to do for a ticket
    *
    * @param $ticket Ticket object
    *
    * @return execution date time (NULL if ola not exists)
   **/
   static function deleteLevelsToDo(Ticket $ticket) {
      global $DB;

      if ($ticket->fields["ttr_olalevels_id"] > 0) {
         $query = "SELECT *
                   FROM `glpi_olalevels_tickets`
                   WHERE `tickets_id` = '".$ticket->fields["id"]."'";

         $olalevelticket = new OlaLevel_Ticket();
         foreach ($DB->request($query) as $data) {
            $olalevelticket->delete(array('id' => $data['id']));
         }
      }
   }


   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      if ($input['definition_time'] != 'day') {
         $input['end_of_working_day'] = 0;
      }
      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      if (isset($input['definition_time']) && $input['definition_time'] != 'day') {
         $input['end_of_working_day'] = 0;
      }
      return $input;
   }

   /**
    * Get OLA types
    *
    * @return array of types
    **/
   static function getOlaTypes() {

      return array(SLM::TTO => __('Internal time to own'),
                   SLM::TTR => __('Internal time to resolve'));
   }


   /**
    * Get OLA types name
    *
    * @param type $type
    * @return string name
    **/
   static function getOlaTypeName($type) {

      $types = self::getOlaTypes();
      $name  = null;
      if (isset($types[$type])) {
         $name = $types[$type];
      }
      return $name;
   }


   /**
    * Get OLA types dropdown
    *
    * @param $options
    **/
   static function getOlaTypeDropdown($options) {

      $params = array('name'  => 'type');

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      return Dropdown::showFromArray($params['name'], self::getOlaTypes(), $options);
   }

}
