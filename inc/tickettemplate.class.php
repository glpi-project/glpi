<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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

/// Ticket Template class
/// since version 0.83
class TicketTemplate extends CommonDropdown {

   // From CommonDBTM
   public $dohistory                   = true;

   // From CommonDropdown
   public $first_level_menu            = "maintain";
   public $second_level_menu           = "ticket";
   public $third_level_menu            = "TicketTemplate";

   public $display_dropdowntitle       = false;


   // Specific fields
   /// Mandatory Fields
   var $mandatory  = array();
   /// Hidden fields
   var $hidden     = array();
   /// Predefined fields
   var $predefined = array();


   /**
    * Retrieve an item from the database with additional datas
    *
    * @since version 0.83
    *
    * @param $ID                    integer  ID of the item to get
    * @param $withtypeandcategory   boolean  with type and category (true by default)
    *
    * @return true if succeed else false
   **/
   function getFromDBWithDatas($ID, $withtypeandcategory=true) {
      global $DB;

      if ($this->getFromDB($ID)) {
         $ticket       = new Ticket();
         $tth          = new TicketTemplateHiddenField();
         $this->hidden = $tth->getHiddenFields($ID, $withtypeandcategory);

         // Force items_id if itemtype is defined
         if (isset($this->hidden['itemtype'])
             && !isset($this->hidden['items_id'])) {
            $this->hidden['items_id'] = $ticket->getSearchOptionIDByField('field', 'items_id',
                                                                          'glpi_tickets');
         }
         // Always get all mandatory fields
         $ttm             = new TicketTemplateMandatoryField();
         $this->mandatory = $ttm->getMandatoryFields($ID);

         // Force items_id if itemtype is defined
         if (isset($this->mandatory['itemtype'])
             && !isset($this->mandatory['items_id'])) {
            $this->mandatory['items_id'] = $ticket->getSearchOptionIDByField('field', 'items_id',
                                                                             'glpi_tickets');
         }

         $ttp              = new TicketTemplatePredefinedField();
         $this->predefined = $ttp->getPredefinedFields($ID, $withtypeandcategory);
         // Compute due_date
         if (isset($this->predefined['due_date'])) {
            $this->predefined['due_date']
                        = Html::computeGenericDateTimeSearch($this->predefined['due_date'], false);
         }
         // Compute date
         if (isset($this->predefined['date'])) {
            $this->predefined['date']
                        = Html::computeGenericDateTimeSearch($this->predefined['date'], false);
         }
         return true;
      }
      return false;
   }


   static function getTypeName($nb=0) {
      return _n('Ticket template', 'Ticket templates', $nb);
   }


   static function canCreate() {
      return Session::haveRight('tickettemplate', 'w');
   }


   static function canView() {
      return Session::haveRight('tickettemplate', 'r');
   }


   /**
    * @param $withtypeandcategory   (default 0)
    * @param $with_items_id         (default 1)
   **/
   static function getAllowedFields($withtypeandcategory=0, $with_items_id=1) {

      static $allowed_fields = array();

      // For integer value for index
      if ($withtypeandcategory) {
         $withtypeandcategory = 1;
      } else {
         $withtypeandcategory = 0;
      }

      if ($with_items_id) {
         $with_items_id = 1;
      } else {
         $with_items_id = 0;
      }

      if (!isset($allowed_fields[$withtypeandcategory][$with_items_id])) {
         $ticket = new Ticket();

         // SearchOption ID => name used for options
         $allowed_fields[$withtypeandcategory][$with_items_id]
             = array($ticket->getSearchOptionIDByField('field', 'name',
                                                       'glpi_tickets')        => 'name',
                     $ticket->getSearchOptionIDByField('field', 'content',
                                                       'glpi_tickets')        => 'content',
                     $ticket->getSearchOptionIDByField('field', 'status',
                                                       'glpi_tickets')        => 'status',
                     $ticket->getSearchOptionIDByField('field', 'urgency',
                                                       'glpi_tickets')        => 'urgency',
                     $ticket->getSearchOptionIDByField('field', 'impact',
                                                       'glpi_tickets')        => 'impact',
                     $ticket->getSearchOptionIDByField('field', 'priority',
                                                       'glpi_tickets')        => 'priority',
                     $ticket->getSearchOptionIDByField('field', 'name',
                                                       'glpi_requesttypes')   => 'requesttypes_id',
                     $ticket->getSearchOptionIDByField('field', 'completename',
                                                       'glpi_locations')      => 'locations_id',
                     $ticket->getSearchOptionIDByField('field', 'name',
                                                       'glpi_slas')           => 'slas_id',
                     $ticket->getSearchOptionIDByField('field', 'due_date',
                                                       'glpi_tickets')        => 'due_date',
                     $ticket->getSearchOptionIDByField('field', 'date',
                                                       'glpi_tickets')        => 'date',
                     $ticket->getSearchOptionIDByField('field', 'actiontime',
                                                       'glpi_tickets')        => 'actiontime',
                     $ticket->getSearchOptionIDByField('field', 'itemtype',
                                                       'glpi_tickets')        => 'itemtype',
                     $ticket->getSearchOptionIDByField('field', 'global_validation',
                                                       'glpi_tickets')        => 'global_validation',

                     4                                                        => '_users_id_requester',
                     71                                                       => '_groups_id_requester',
                     5                                                        => '_users_id_assign',
                     8                                                        => '_groups_id_assign',
                     $ticket->getSearchOptionIDByField('field', 'name',
                                                       'glpi_suppliers')      => '_suppliers_id_assign',

                     66                                                       => '_users_id_observer',
                     65                                                       => '_groups_id_observer',
            );

         if ($withtypeandcategory) {
            $allowed_fields[$withtypeandcategory][$with_items_id]
               [$ticket->getSearchOptionIDByField('field', 'completename',
                                                  'glpi_itilcategories')]  = 'itilcategories_id';
            $allowed_fields[$withtypeandcategory][$with_items_id]
               [$ticket->getSearchOptionIDByField('field', 'type',
                                                  'glpi_tickets')]         = 'type';
         }

         if ($with_items_id) {
            $allowed_fields[$withtypeandcategory][$with_items_id]
               [$ticket->getSearchOptionIDByField('field', 'items_id',
                                                  'glpi_tickets')] = 'items_id';
         }
         // Add validation request
         $allowed_fields[$withtypeandcategory][$with_items_id][-2] = '_add_validation';
      }

      return $allowed_fields[$withtypeandcategory][$with_items_id];

     /// TODO ADD : linked tickets ? : array passed. How to manage it ? store array in DB + add hidden searchOption ?
   }


   /**
    * @param $withtypeandcategory   (default 0)
    * @param $with_items_id         (default 1)
   **/
   function getAllowedFieldsNames($withtypeandcategory=0, $with_items_id=1) {

      $searchOption = Search::getOptions('Ticket');
      $tab          = $this->getAllowedFields($withtypeandcategory, $with_items_id);
      foreach ($tab as $ID => $shortname) {
         switch ($ID) {
            case -2 :
               $tab[-2] = __('Approval request');
               break;

            default :
               if (isset($searchOption[$ID]['name'])) {
                  $tab[$ID] = $searchOption[$ID]['name'];
               }
         }
      }
      return $tab;
   }


   /**
    *  àsince version 0.84
   **/
   function getSimplifiedInterfaceFields() {

      $ticket = new Ticket();
      $fields = array($ticket->getSearchOptionIDByField('field', 'name', 'glpi_tickets'),
                      $ticket->getSearchOptionIDByField('field', 'content', 'glpi_tickets'),
                      $ticket->getSearchOptionIDByField('field', 'urgency', 'glpi_tickets'),
                      $ticket->getSearchOptionIDByField('field', 'completename', 'glpi_locations'),
                      $ticket->getSearchOptionIDByField('field', 'itemtype', 'glpi_tickets'),
                      $ticket->getSearchOptionIDByField('field', 'completename',
                                                        'glpi_itilcategories'),
                      $ticket->getSearchOptionIDByField('field', 'type', 'glpi_tickets'),
                      $ticket->getSearchOptionIDByField('field', 'items_id', 'glpi_tickets'));

      return $fields;
   }


   function defineTabs($options=array()) {

      $ong          = array();
      $ong['empty'] = $this->getTypeName(1);
      $this->addStandardTab('TicketTemplateMandatoryField', $ong, $options);
      $this->addStandardTab('TicketTemplatePredefinedField', $ong, $options);
      $this->addStandardTab('TicketTemplateHiddenField', $ong, $options);
      $this->addStandardTab('TicketTemplate', $ong, $options);
      $this->addStandardTab('ITILCategory', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'TicketTemplate' :
            switch ($tabnum) {
               case 1 :
                  $item->showCentralPreview($item);
                  return true;

               case 2 :
                  $item->showHelpdeskPreview($item);
                  return true;

            }
            break;
      }
      return false;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (Session::haveRight("tickettemplate","r")) {
         switch ($item->getType()) {
            case 'TicketTemplate' :
               $ong[1] = __('Standard interface');
               $ong[2] = __('Simplified interface');
               return $ong;
         }
      }
      return '';
   }



   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {
      return parent::getSearchOptions();
   }


   /**
    * Get mandatory mark if field is mandatory
    *
    * @since version 0.83
    *
    * @param $field  string   field
    * @param $force  boolean  force display based on global config (false by default)
    *
    * @return string to display
   **/
   function getMandatoryMark($field, $force=false) {

      if ($force || $this->isMandatoryField($field)) {
         return "<span class='red'>*</span>";
      }
      return '';
   }


   /**
    * Get hidden field begin enclosure for text
    *
    * @since version 0.83
    *
    * @param $field string field
    *
    * @return string to display
   **/
   function getBeginHiddenFieldText($field) {

      if ($this->isHiddenField($field) && !$this->isPredefinedField($field)) {
         return "<span id='hiddentext$field' style='display:none'>";
      }
      return '';
   }


   /**
    * Get hidden field end enclosure for text
    *
    * @since version 0.83
    *
    * @param $field string field
    *
    * @return string to display
   **/
   function getEndHiddenFieldText($field) {

      if ($this->isHiddenField($field) && !$this->isPredefinedField($field)) {
         return "</span>";
      }
      return '';
   }


   /**
    * Get hidden field begin enclosure for value
    *
    * @since version 0.83
    *
    * @param $field string field
    *
    * @return string to display
   **/
   function getBeginHiddenFieldValue($field) {

      if ($this->isHiddenField($field)) {
         return "<span id='hiddenvalue$field' style='display:none'>";
      }
      return '';
   }


   /**
    * Get hidden field end enclosure with hidden value
    *
    * @since version 0.83
    *
    * @param $field  string   field
    * @param $ticket          ticket object (default NULL)
    *
    * @return string to display
   **/
   function getEndHiddenFieldValue($field, &$ticket=NULL) {

      $output = '';
      if ($this->isHiddenField($field)) {
         $output .= "</span>";
         if ($ticket && isset($ticket->fields[$field])) {
            $output .= "<input type='hidden' name='$field' value=\"".$ticket->fields[$field]."\">";
         }
         if ($this->isPredefinedField($field)
             && !is_null($ticket)) {
            if ($num = array_search($field, $this->getAllowedFields())) {
               $display_options = array('comments' => true,
                                        'html'     => true);
               $output .= $ticket->getValueToDisplay($num, $ticket->fields, $display_options);

               /// Display items_id
               if ($field == 'itemtype') {
                  $output .= "<input type='hidden' name='items_id' value=\"".
                               $ticket->fields['items_id']."\">";
                  if ($num = array_search('items_id',$this->getAllowedFields())) {
                     $output = sprintf(__('%1$s - %2$s'), $output,
                                       $ticket->getValueToDisplay($num, $ticket->fields,
                                                                  $display_options));
                  }
               }
            }
         }
      }
      return $output;
   }


   /**
    * Is it an hidden field ?
    *
    * @since version 0.83
    *
    * @param $field string field
    *
    * @return bool
   **/
   function isHiddenField($field) {

      if (isset($this->hidden[$field])) {
         return true;
      }
      return false;
   }


   /**
    * Is it an predefined field ?
    *
    * @since version 0.83
    *
    * @param $field string field
    *
    * @return bool
   **/
   function isPredefinedField($field) {

      if (isset($this->predefined[$field])) {
         return true;
      }
      return false;
   }


   /**
    * Is it an mandatory field ?
    *
    * @since version 0.83
    *
    * @param $field string field
    *
    * @return bool
   **/
   function isMandatoryField($field) {

      if (isset($this->mandatory[$field])) {
         return true;
      }
      return false;
   }


   /**
    * Print preview for Ticket template
    *
    * @since version 0.83
    *
    * @param $tt TicketTemplate object
    *
    * @return Nothing (call to classes members)
   **/
   static function showCentralPreview(TicketTemplate $tt) {

      if (!$tt->getID()) {
         return false;
      }
      if ($tt->getFromDBWithDatas($tt->getID())) {
         $ticket = new Ticket();
         $ticket->showForm(0, array('template_preview' => $tt->getID()));
      }
   }


   /**
    * Print preview for Ticket template
    *
    * @param $tt TicketTemplate object
    *
    * @return Nothing (call to classes members)
   **/
   static function showHelpdeskPreview(TicketTemplate $tt) {

      if (!$tt->getID()) {
         return false;
      }
      if ($tt->getFromDBWithDatas($tt->getID())) {
         $ticket = new  Ticket();
         $ticket->showFormHelpdesk(Session::getLoginUserID(), $tt->getID());
      }
   }

}
?>
