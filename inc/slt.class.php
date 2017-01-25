<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2016 Teclib'.

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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLT Class
 * @since version 9.1
**/
class SLT extends CommonDBChild {

   // From CommonDBTM
   var $dohistory                      = true;

   // From CommonDBChild
   static public $itemtype             = 'SLA';
   static public $items_id             = 'slas_id';

   static $rightname                   = 'sla';

   static protected $forward_entity_to = array('SLALevel');

   const TTR = 0; // Time to resolve
   const TTO = 1; // Time to own


   static function getTypeName($nb=0) {
      // Acronymous, no plural
      return __('SLT');
   }


   /**
    * Define calendar of the ticket using the SLT when using this calendar as slt-s calendar
    *
    * @param $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1 ) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }


   function defineTabs($options=array()) {

      $ong = array();
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('SlaLevel', $ong, $options);
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

      // get calendar from sla
      $sla = new SLA;
      if ($sla->getFromDB($this->fields['slas_id'])) {
         $this->fields['calendars_id'] = $sla->fields['calendars_id'];
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

      // Clean sla_levels
      $query = "SELECT `id`
                FROM `glpi_slalevels`
                WHERE `slts_id` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $slalevel = new SlaLevel();
            while ($data = $DB->fetch_assoc($result)) {
               $slalevel->delete($data);
            }
         }
      }

      // Update tickets : clean SLT
      list($dateField, $sltField) = self::getSltFieldNames($this->fields['type']);
      $query = "SELECT `id`
                FROM `glpi_tickets`
                WHERE `$sltField` = '".$this->fields['id']."'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result) > 0) {
            $ticket = new Ticket();
            while ($data = $DB->fetch_assoc($result)) {
               $ticket->deleteSLT($data['id'], $this->fields['type']);
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
   function showForm($ID, $options=array()) {

      $rowspan = 4;
      if ($ID > 0) {
         $rowspan = 6;
      }

      // Get SLA object
      $sla = new SLA();
      if (isset($options['parent'])) {
         $sla = $options['parent'];
      } else {
         $sla->getFromDB($this->fields['slas_id']);
      }

      if ($ID > 0) {
         $this->check($ID, READ);
      } else {
         // Create item
         $options[static::$items_id] = $sla->getField('id');

         //force itemtype of parent
         static::$itemtype = get_class($sla);

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
      echo "<td>".__('SLA')."</td>";
      echo "<td>";
      echo $sla->getLink();
      echo "<input type='hidden' name='slas_id' value='".$this->fields['slas_id']."'>";
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
      self::getSltTypeDropdown(array('value' => $this->fields["type"]));
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
      echo "</div></td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the HTML array for SLTs linked to a SLA
    *
    * @param SLA $sla
    * @return boolean
    */
   static function showForSla(SLA $sla) {
      global $CFG_GLPI;

      $instID   = $sla->fields['id'];
      $slt      = new self();
      $calendar = new Calendar();

      if (!$sla->can($instID, READ)) {
         return false;
      }

      $canedit = ($sla->canEdit($instID)
                  && isset($_SESSION["glpiactiveprofile"])
                  && $_SESSION["glpiactiveprofile"]["interface"] == "central");

      $rand = mt_rand();

      if ($canedit) {
         echo "<div id='viewslt$instID$rand'></div>\n";

         echo "<script type='text/javascript' >";
         echo "function viewAddSlt$instID$rand() {";
         $params = array('type'                     => $slt->getType(),
                         'parenttype'               => $sla->getType(),
                         $sla->getForeignKeyField() => $instID,
                         'id'                       => -1);
         Ajax::updateItemJsCode("viewslt$instID$rand",
                                $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
         echo "}";
         echo "</script>";
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='javascript:viewAddSlt$instID$rand();'>";
         echo __('Add a new SLT')."</a></div>\n";
      }

      // SLT list
      $sltList = $slt->find("`slas_id` = '".$instID."'");
      Session::initNavigateListItems('SLT',
      //TRANS : %1$s is the itemtype name,
      //       %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'), $sla::getTypeName(1), $sla->getName()));
      echo "<div class='spaced'>";
      if (count($sltList)) {
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
         foreach ($sltList as $val) {
            $edit = ($canedit ? "style='cursor:pointer' onClick=\"viewEditSlt".
                        $instID.$val["id"]."$rand();\""
                        : '');
            echo "\n<script type='text/javascript' >\n";
            echo "function viewEditSlt". $instID.$val["id"]."$rand() {\n";
            $params = array('type'                     => $slt->getType(),
                            'parenttype'               => $sla->getType(),
                            $sla->getForeignKeyField() => $instID,
                            'id'                       => $val["id"]);
            Ajax::updateItemJsCode("viewslt$instID$rand",
                                   $CFG_GLPI["root_doc"]."/ajax/viewsubitem.php", $params);
            echo "};";
            echo "</script>\n";

            echo "<tr class='tab_bg_1'>";
            echo "<td width='10' $edit>";
            if ($canedit) {
               Html::showMassiveActionCheckBox($slt->getType(), $val['id']);
            }
            echo "</td>";
            $slt->getFromDB($val['id']);
            echo "<td $edit>".$slt->getLink()."</td>";
            echo "<td $edit>".$slt->getSpecificValueToDisplay('type', $slt->fields['type'])."</td>";
            echo "<td $edit>";
            echo $slt->getSpecificValueToDisplay('number_time',
                  array('number_time'     => $slt->fields['number_time'],
                        'definition_time' => $slt->fields['definition_time']));
            echo "</td>";
            if (!$sla->fields['calendars_id']) {
               $link =  __('24/7');
            } else if ($sla->fields['calendars_id'] == -1) {
               $link = __('Calendar of the ticket');
            } else if ($calendar->getFromDB($sla->fields['calendars_id'])) {
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
            case 'SLA' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable('glpi_slts', "`slas_id` = '".$item->getField('id')."'");
               }
               return self::createTabEntry(self::getTypeName(1), $nb);
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
         case 'SLA' :
            self::showForSla($item);
            break;
      }
      return true;
   }


   /**
    * Get SLT data by type and ticket
    *
    * @param $tickets_id
    * @param $type
    */
   function getSltDataForTicket($tickets_id, $type) {

      switch($type){
         case SLT::TTR :
            $field = 'slts_ttr_id';
            break;

         case SLT::TTO :
            $field = 'slts_tto_id';
            break;
      }
      return $this->getFromDBByQuery("INNER JOIN `glpi_tickets` ON (`glpi_tickets`.`$field` = `".$this->getTable()."`.`id`) WHERE `glpi_tickets`.`id` = '".$tickets_id."' LIMIT 1");
   }


    /**
    * Get SLT datas by condition
    *
    * @param $condition
   **/
   function getSltData($condition) {
      return $this->find($condition);
   }


   /**
    * Get SLT table fields
    *
    * @param $type
    *
    * @return array
   **/
   static function getSltFieldNames($type){

      $dateField = null;
      $sltField  = null;

      switch ($type) {
         case self::TTO:
            $dateField = 'time_to_own';
            $sltField  = 'slts_tto_id';
            break;

         case self::TTR:
            $dateField = 'due_date';
            $sltField  = 'slts_ttr_id';
            break;
      }
      return array($dateField, $sltField);
   }


   /**
    * Show SLT for ticket
    *
    * @param $ticket      Ticket item
    * @param $type
    * @param $tt
    * @param $canupdate
   **/
   function showSltForTicket(Ticket $ticket, $type, $tt, $canupdate) {
      global $CFG_GLPI;

      list($dateField, $sltField) = self::getSltFieldNames($type);

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_1'>";

      if (!isset($ticket->fields[$dateField]) || $ticket->fields[$dateField] == 'NULL') {
         $ticket->fields[$dateField]='';
      }

      if ($ticket->fields['id']) {
         if ($this->getSltDataForTicket($ticket->fields['id'], $type)) {
            echo "<td>";
            echo Html::convDateTime($ticket->fields[$dateField]);
            echo "</td>";
            echo "<th>".__('SLT')."</th>";
            echo "<td>";
            echo Dropdown::getDropdownName('glpi_slts', $ticket->fields[$sltField])."&nbsp;";
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

            $slaoptions = array();
            if (Session::haveRight('sla', READ)) {
               $slaoptions['link'] = Toolbox::getItemTypeFormURL('SLT').
                                          "?id=".$this->fields["id"];
            }
            Html::showToolTip($commentsla,$slaoptions);
            if ($canupdate) {
               $fields = array('slt_delete'        => 'slt_delete',
                               'id'                => $ticket->getID(),
                               'type'              => $type,
                               '_glpi_csrf_token'  => Session::getNewCSRFToken(),
                               '_glpi_simple_form' => 1);
               $JS = "  function delete_date$type(){
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
               Html::showDateTimeField($dateField, array('value'      => $ticket->fields[$dateField],
                                                         'timestep'   => 1,
                                                         'maybeempty' => true));
            } else {
               echo Html::convDateTime($ticket->fields[$dateField]);
            }
            echo $tt->getEndHiddenFieldValue($dateField, $ticket);
            echo "</td>";
            $sql_entities = getEntitiesRestrictRequest("", "", "", $ticket->fields['entities_id'], true);
            $slt_data     = $this->getSltData("`type` = '$type' AND $sql_entities");
            if ($canupdate
                && !empty($slt_data)) {
               echo "<td>";
               echo $tt->getBeginHiddenFieldText($sltField);
               echo "<span id='slt_action$type'>";
               echo "<a ".Html::addConfirmationOnAction(array(__('The assignment of a SLT to a ticket causes the recalculation of the date.'),
                       __("Escalations defined in the SLT will be triggered under this new date.")),
                                                    "cleanhide('slt_action$type');cleandisplay('slt_choice$type');").
                     "><img src='".$CFG_GLPI['root_doc']."/pics/clock.png' title='".__('SLT')."' alt='".__('SLT')."' class='pointer'></a>";
               echo "</span>";
               echo "<div id='slt_choice$type' style='display:none'>";
               echo "<span  class='b'>".__('SLT')."</span>&nbsp;";
               Slt::dropdown(array('name'      => $sltField,
                                   'entity'    => $ticket->fields["entities_id"],
                                   'condition' => "`type` = '".$type."'"));
               echo "</div>";
               echo $tt->getEndHiddenFieldText($sltField);
               echo "</td>";
            }
         }

      } else { // New Ticket
         echo "<td>";
         echo $tt->getBeginHiddenFieldValue($dateField);
         Html::showDateTimeField($dateField, array('value'      => $ticket->fields[$dateField],
                                                   'timestep'   => 1,
                                                   'maybeempty' => false,
                                                   'canedit'    => $canupdate));
         echo $tt->getEndHiddenFieldValue($dateField, $ticket);
         echo "</td>";
         $sql_entities = getEntitiesRestrictRequest("", "", "", $ticket->fields['entities_id'], true);
         $slt_data     = $this->getSltData("`type` = '$type' AND $sql_entities");
         if ($canupdate
             && !empty($slt_data)) {
            echo $tt->getBeginHiddenFieldText($sltField);
            if (!$tt->isHiddenField($sltField) || $tt->isPredefinedField($sltField)) {
               echo "<th>".sprintf(__('%1$s%2$s'), __('SLT'), $tt->getMandatoryMark($sltField))."</th>";
            }
            echo $tt->getEndHiddenFieldText($sltField);
            echo "<td class='nopadding'>".$tt->getBeginHiddenFieldValue($sltField);
            Slt::dropdown(array('name'      => $sltField,
                                'entity'    => $ticket->fields["entities_id"],
                                'value'     => isset($ticket->fields[$sltField])
                                                  ? $ticket->fields[$sltField] : 0,
                                'condition' => "`type` = '".$type."'"));
            echo $tt->getEndHiddenFieldValue($sltField, $ticket);
            echo "</td>";
         }
      }

      echo "</tr>";
      echo "</table>";
   }


   /**
    * Get SLT types
    *
    * @return array of types
   **/
   static function getSltTypes() {

      return array(self::TTO => __('Time to own'),
                   self::TTR => __('Time to resolve'));
   }


   /**
    * Get SLT types name
    *
    * @param type $type
    * @return string name
   **/
   static function getSltTypeName($type) {

      $types = self::getSltTypes();
      $name  = null;
      if (isset($types[$type])) {
         $name = $types[$type];
      }
      return $name;
   }


   /**
    * Get SLT types dropdown
    *
    * @param $options
   **/
   static function getSltTypeDropdown($options){

      $params = array('name'  => 'type');

      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }

      return Dropdown::showFromArray($params['name'], self::getSltTypes(), $options);
   }


   function getSearchOptions() {

      $tab                        = array();
      $tab['common']              = __('Characteristics');

      $tab[1]['table']            = $this->getTable();
      $tab[1]['field']            = 'name';
      $tab[1]['name']             = __('Name');
      $tab[1]['datatype']         = 'itemlink';
      $tab[1]['massiveaction']    = false;

      $tab[2]['table']            = $this->getTable();
      $tab[2]['field']            = 'id';
      $tab[2]['name']             = __('ID');
      $tab[2]['massiveaction']    = false;
      $tab[2]['datatype']         = 'number';

      $tab[5]['table']            = $this->getTable();
      $tab[5]['field']            = 'number_time';
      $tab[5]['name']             = __('Time');
      $tab[5]['datatype']         = 'specific';
      $tab[5]['massiveaction']    = false;
      $tab[5]['nosearch']         = true;
      $tab[5]['additionalfields'] = array('definition_time');

      $tab[6]['table']            = $this->getTable();
      $tab[6]['field']            = 'end_of_working_day';
      $tab[6]['name']             = __('End of working day');
      $tab[6]['datatype']         = 'bool';
      $tab[6]['massiveaction']    = false;

      $tab[7]['table']            = $this->getTable();
      $tab[7]['field']            = 'type';
      $tab[7]['name']             = __('Type');
      $tab[7]['datatype']         = 'specific';

      $tab[8]['table']            = 'glpi_slas';
      $tab[8]['field']            = 'name';
      $tab[8]['name']             = __('SLA');
      $tab[8]['datatype']         = 'dropdown';

      $tab[16]['table']           = $this->getTable();
      $tab[16]['field']           = 'comment';
      $tab[16]['name']            = __('Comments');
      $tab[16]['datatype']        = 'text';

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
            return self::getSltTypeName($values[$field]);
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
            return self::getSltTypeDropdown($options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * Get date based on a slt
    *
    * @param $start_date         datetime start date
    * @param $additional_delay   integer  additional delay to add or substract (for waiting time)
    *                                     (default 0)
    *
    * @return due date time (NULL if sla not exists)
   **/
   function computeDate($start_date, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         $delay = $this->getSLTTime();
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
            return date('Y-m-d H:i:s',$endtime);
         }
      }

      return NULL;
   }


   /**
    * Get computed resolution time
    *
    * @return resolution time
   **/
   function getSLTTime() {

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
    * @param $start_date         datetime    start date
    * @param $slalevels_id       integer     sla level id
    * @param $additional_delay   integer     additional delay to add or substract (for waiting time)
    *                                        (default 0)
    *
    * @return execution date time (NULL if sla not exists)
   **/
   function computeExecutionDate($start_date, $slalevels_id, $additional_delay=0) {

      if (isset($this->fields['id'])) {
         $slalevel = new SlaLevel();

         if ($slalevel->getFromDB($slalevels_id)) { // sla level exists
            if ($slalevel->fields['slts_id'] == $this->fields['id']) { // correct slt level
               $work_in_days = ($this->fields['definition_time'] == 'day');
               $delay        = $this->getSLTTime();

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
               return date('Y-m-d H:i:s',$endtime);
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
    * @param $slalevels_id
    *
    * @return execution date time (NULL if sla not exists)
   **/
   function addLevelToDo(Ticket $ticket, $slalevels_id = 0) {

      $slalevels_id = ($slalevels_id ? $slalevels_id
                                     : $ticket->fields["ttr_slalevels_id"]);
      if ($slalevels_id > 0) {
         $toadd = array();
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
    * @param $ticket Ticket object
    *
    * @return execution date time (NULL if sla not exists)
   **/
   static function deleteLevelsToDo(Ticket $ticket) {
      global $DB;

      if ($ticket->fields["ttr_slalevels_id"] > 0) {
         $query = "SELECT *
                   FROM `glpi_slalevels_tickets`
                   WHERE `tickets_id` = '".$ticket->fields["id"]."'";

         $slalevelticket = new SlaLevel_Ticket();
         foreach ($DB->request($query) as $data) {
            $slalevelticket->delete(array('id' => $data['id']));
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

}
