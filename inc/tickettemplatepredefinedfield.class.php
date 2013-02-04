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

/// Predefined fields for ticket template class
/// since version 0.83
class TicketTemplatePredefinedField extends CommonDBChild {

   /// TODO delete items_id if itemtype is deleted

   // From CommonDBChild
   public $itemtype  = 'TicketTemplate';
   public $items_id  = 'tickettemplates_id';
   public $dohistory = true;


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb>1) {
         return $LANG['job'][61];
      }
      return $LANG['job'][64];
   }

   function getName($with_comment=0) {

      $tt = new TicketTemplate();
      $fields = $tt->getAllowedFieldsNames(true);

      if (isset($fields[$this->fields["num"]])) {
         return $fields[$this->fields["num"]];
      }
      return NOT_AVAILABLE;
   }

   function canCreate() {
      return Session::haveRight('tickettemplate', 'w');
   }


   function canView() {
      return Session::haveRight('tickettemplate', 'r');
   }


   function prepareInputForAdd($input) {

      // Use massiveaction system to manage add system.
      // Need to update data : value not set but
      if (!isset($input['value'])) {
         if (isset($input['field']) && isset($input[$input['field']])) {
            $input['value'] = $input[$input['field']];
            unset($input[$input['field']]);
            unset($input['field']);
         }
      }
      return $input;
   }

   function post_purgeItem() {
      global $DB;

      parent::post_purgeItem();

      $ticket = new Ticket();
      $itemtype_id = $ticket->getSearchOptionIDByField('field', 'itemtype',
                                                       'glpi_tickets');
      $items_id_id = $ticket->getSearchOptionIDByField('field', 'items_id',
                                                       'glpi_tickets');

      // Try to delete itemtype -> delete items_id
      if ($this->fields['num'] == $itemtype_id) {

         $query = "SELECT `id`
              FROM `".$this->getTable()."`
              WHERE `".$this->items_id."` = '".$this->fields['tickettemplates_id']."'
              AND `num` = '$items_id_id'";
         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)) {
               $a = new TicketTemplatePredefinedField();
               $a->delete(array('id'=>$DB->result($result,0,0)));
            }
         }
      }
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      // can exists for template
      if ($item->getType() == 'TicketTemplate' && Session::haveRight("tickettemplate","r")) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry($LANG['job'][61],
                                        countElementsInTable($this->getTable(),
                                                             "`tickettemplates_id`
                                                               = '".$item->getID()."'"));
         }
         return $LANG['job'][61];
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForTicketTemplate($item, $withtemplate);
      return true;
   }


   /**
    * Get predefined fields for a template
    *
    * @since version 0.83
    *
    * @param $ID the template ID
    * @param $withtypeandcategory bool with type and category
    *
    * @return an array of predefined fields
   **/
   function getPredefinedFields($ID, $withtypeandcategory=false) {
      global $DB;

      $sql = "SELECT *
              FROM `".$this->getTable()."`
              WHERE `".$this->items_id."` = '$ID'
              ORDER BY `id`";
      $result = $DB->query($sql);

      $tt             = new TicketTemplate();
      $allowed_fields = $tt->getAllowedFields($withtypeandcategory, true);
      $fields         = array();

      while ($rule = $DB->fetch_assoc($result)) {
         if (isset($allowed_fields[$rule['num']])) {
            $fields[$allowed_fields[$rule['num']]] = $rule['value'];
         }
      }
      return $fields;
   }


   /**
    * Print the predefined fields
    *
    * @since version 0.83
    *
    * @param $tt Ticket Template
    * @param $withtemplate=''  boolean : Template or basic item.
    *
    * @return Nothing (call to classes members)
   **/
   static function showForTicketTemplate(TicketTemplate $tt, $withtemplate='') {
      global $DB, $LANG,$CFG_GLPI;

      $ID = $tt->fields['id'];

      if (!$tt->getFromDB($ID) || !$tt->can($ID, "r")) {
         return false;
      }

      $canedit       = $tt->can($ID, "w");

      $ttp           = new self();
      $used_fields   = $ttp->getPredefinedFields($ID, true);

      $itemtype_used = '';
      if (isset($used_fields['itemtype'])) {
         $itemtype_used = $used_fields['itemtype'];
      }

      $fields        = $tt->getAllowedFieldsNames(true, isset($used_fields['itemtype']));
      $searchOption  = Search::getOptions('Ticket');
      $ticket        = new Ticket();
      $rand          = mt_rand();
      echo "<form name='tickettemplatepredefinedfields_form$rand'
                  id='tickettemplatepredefinedfields_form$rand' method='post' action='";
      echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";

      echo "<div class='center'>";

      $query = "SELECT `glpi_tickettemplatepredefinedfields`.*
                FROM `glpi_tickettemplatepredefinedfields`
                WHERE (`tickettemplates_id` = '$ID')
                ORDER BY 'id'";

      $display_datas   = array('itemtype'       => $itemtype_used);
      $display_options = array('relative_dates' => true,
                               'comments'       => true,
                               'html'           => true);
      if ($result=$DB->query($query)) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='3'>";
         echo self::getTypeName($DB->numrows($result));
         echo "</th></tr>";
         $used = array();
         if ($DB->numrows($result)) {
            echo "<tr><th>&nbsp;</th>";
            echo "<th>".$LANG['common'][16]."</th>";
            echo "<th>".$LANG['rulesengine'][13]."</th>";
            echo "</tr>";

            while ($data=$DB->fetch_assoc($result)) {
               if (!isset($fields[$data['num']])) {
                  // could happen when itemtype removed and items_id present
                  continue;
               }
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td><input type='checkbox' name='item[".$data["id"]."]' value='1'></td>";
               } else {
                  echo "<td>&nbsp;</td>";
               }
               echo "<td>".$fields[$data['num']]."</td>";

               echo "<td>";
               $display_datas[$searchOption[$data['num']]['field']] = $data['value'];
               echo $ticket->getValueToDisplay($searchOption[$data['num']], $display_datas,
                                               $display_options);
               echo "</td>";
               $used[$data['num']] = $data['value'];
            }

         } else {
            echo "<tr><th colspan='3'>".$LANG['search'][15]."</th></tr>";
         }

         if ($canedit) {
            echo "<tr class='tab_bg_2'><td class='right top' width='30%'>";
            echo "<input type='hidden' name='tickettemplates_id' value='$ID'>";
            echo "<input type='hidden' name='entities_id' value='".$tt->getEntityID()."'>";
            echo "<input type='hidden' name='is_recursive' value='".$tt->isRecursive()."'>";
            $display_fields[-1] = Dropdown::EMPTY_VALUE;
            $display_fields    += $fields;

            // Force validation request as used
            $used[-2] = -2;
            $rand_dp = Dropdown::showFromArray('num', $display_fields, array('used' => $used,
                                                                             'toadd'));
            echo "</td><td colspan='2' class='top'>";
            $paramsmassaction = array('id_field'        => '__VALUE__',
                                      'itemtype'        => 'Ticket',
                                      'itemtype_used'   => $itemtype_used,
                                      'relative_dates'  => 1);

            Ajax::updateItemOnSelectEvent("dropdown_num".$rand_dp, "show_massiveaction_field",
                                          $CFG_GLPI["root_doc"]."/ajax/dropdownMassiveActionField.php",
                                          $paramsmassaction);

            echo "<span id='show_massiveaction_field'>&nbsp;</span>\n";
            echo "</td></tr>";
         }
         echo "</table></div>";

         if ($canedit) {
            Html::openArrowMassives("tickettemplatepredefinedfields_form$rand", true);
            Html::closeArrowMassives(array('delete' => $LANG['buttons'][6]));
         }
         Html::closeForm();

      }
   }
}
?>