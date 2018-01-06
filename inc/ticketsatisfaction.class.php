<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

class TicketSatisfaction extends CommonDBTM {

   public $dohistory         = true;
   public $history_blacklist = ['date_answered'];


   static function getTypeName($nb = 0) {
      return __('Satisfaction');
   }


   /**
    * for use showFormHeader
   **/
   static function getIndexName() {
      return 'tickets_id';
   }


   function getLogTypeID() {
      return ['Ticket', $this->fields['tickets_id']];
   }


   static function canUpdate() {
      return (Session::haveRight('ticket', READ));
   }


   /**
    * Is the current user have right to update the current satisfaction
    *
    * @return boolean
   **/
   function canUpdateItem() {

      $ticket = new Ticket();
      if (!$ticket->getFromDB($this->fields['tickets_id'])) {
         return false;
      }

      // you can't change if your answer > 12h
      if (!is_null($this->fields['date_answered'])
          && ((strtotime("now") - strtotime($this->fields['date_answered'])) > (12*HOUR_TIMESTAMP))) {
         return false;
      }

      if ($ticket->isUser(CommonITILActor::REQUESTER, Session::getLoginUserID())
          || ($ticket->fields["users_id_recipient"] === Session::getLoginUserID() && Session::haveRight('ticket', Ticket::SURVEY))
          || (isset($_SESSION["glpigroups"])
              && $ticket->haveAGroup(CommonITILActor::REQUESTER, $_SESSION["glpigroups"]))) {
         return true;
      }
      return false;
   }


   /**
    * form for satisfaction
    *
    * @param $ticket Object : the ticket
   **/
   function showForm($ticket) {

      $tid                 = $ticket->fields['id'];
      $options             = [];
      $options['colspan']  = 1;

      // for external inquest => link
      if ($this->fields["type"] == 2) {
         $url = Entity::generateLinkSatisfaction($ticket);
         echo "<div class='center spaced'>".
              "<a href='$url'>".__('External survey')."</a><br>($url)</div>";

      } else { // for internal inquest => form
         $this->showFormHeader($options);

         // Set default satisfaction to 3 if not set
         if (is_null($this->fields["satisfaction"])) {
            $this->fields["satisfaction"] = 3;
         }
         echo "<tr class='tab_bg_2'>";
         echo "<td>".__('Satisfaction with the resolution of the ticket')."</td>";
         echo "<td>";
         echo "<input type='hidden' name='tickets_id' value='$tid'>";

         echo "<select id='satisfaction_data' name='satisfaction'>";

         for ($i=0; $i<=5; $i++) {
            echo "<option value='$i' ".(($i == $this->fields["satisfaction"])?'selected':'').
                  ">$i</option>";
         }
         echo "</select>";
         echo "<div class='rateit' id='stars'></div>";
         echo  "<script type='text/javascript'>\n";
         echo "$(function() {";
         echo "$('#stars').rateit({value: ".$this->fields["satisfaction"].",
                                   min : 0,
                                   max : 5,
                                   step: 1,
                                   backingfld: '#satisfaction_data',
                                   ispreset: true,
                                   resetable: false});";
         echo "});</script>";

         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td rowspan='1'>".__('Comments')."</td>";
         echo "<td rowspan='1' class='middle'>";
         echo "<textarea cols='45' rows='7' name='comment' >".$this->fields["comment"]."</textarea>";
         echo "</td></tr>\n";

         if ($this->fields["date_answered"] > 0) {
            echo "<tr class='tab_bg_2'>";
            echo "<td>".__('Response date to the satisfaction survey')."</td><td>";
            echo Html::convDateTime($this->fields["date_answered"])."</td></tr>\n";
         }

         $options['candel'] = false;
         $this->showFormButtons($options);
      }
   }


   function prepareInputForUpdate($input) {
      global $CFG_GLPI;

      if ($input['satisfaction'] >= 0) {
         $input["date_answered"] = $_SESSION["glpi_currenttime"];
      }

      return $input;
   }


   function post_addItem() {
      global $CFG_GLPI;

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->fields['tickets_id'])) {
            NotificationEvent::raiseEvent("satisfaction", $ticket);
         }
      }
   }


   /**
    * @since 0.85
   **/
   function post_UpdateItem($history = 1) {
      global $CFG_GLPI;

      if (!isset($this->input['_disablenotif']) && $CFG_GLPI["use_notifications"]) {
         $ticket = new Ticket();
         if ($ticket->getFromDB($this->fields['tickets_id'])) {
            NotificationEvent::raiseEvent("replysatisfaction", $ticket);
         }
      }
   }


   /**
    * display satisfaction value
    *
    * @param $value decimal between 0 and 5
   **/
   static function displaySatisfaction($value) {

      if ($value < 0) {
         $value = 0;
      }
      if ($value > 5) {
         $value = 5;
      }

      $out = "<div class='rateit' data-rateit-value='$value' data-rateit-ispreset='true'
               data-rateit-readonly='true'></div>";

      return $out;
   }


   /**
    * Get name of inquest type
    *
    * @param $value status ID
   **/
   static function getTypeInquestName($value) {

      switch ($value) {
         case 1 :
            return __('Internal survey');

         case 2 :
            return __('External survey');

         default :
            // Get value if not defined
            return $value;
      }
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      switch ($field) {
         case 'type':
            return self::getTypeInquestName($values[$field]);
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name                  (default '')
    * @param $values                (default '')
    * @param $options   array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;

      switch ($field) {
         case 'type' :
            $options['value'] = $values[$field];
            $typeinquest = [1 => __('Internal survey'),
                                 2 => __('External survey')];
            return Dropdown::showFromArray($name, $typeinquest, $options);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }

}
