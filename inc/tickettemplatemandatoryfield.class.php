<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}

/// Mandatory fields for ticket template class
/// since version 0.83
class TicketTemplateMandatoryField extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype  = 'TicketTemplate';
   static public $items_id  = 'tickettemplates_id';
   public $dohistory = true;


   static function getTypeName($nb=0) {
      return _n('Mandatory field', 'Mandatory fields', $nb);
   }


   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @see CommonDBTM::getRawName()
    *
    * @since version 0.85
   **/
   function getRawName() {

      $tt     = new TicketTemplate();
      $fields = $tt->getAllowedFieldsNames(true);

      if (isset($fields[$this->fields["num"]])) {
         return $fields[$this->fields["num"]];
      }
      return '';
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      // can exists for template
      if (($item->getType() == 'TicketTemplate')
          && Session::haveRight("tickettemplate", READ)) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable($this->getTable(),
                                       "`tickettemplates_id` = '".$item->getID()."'");
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      self::showForTicketTemplate($item, $withtemplate);
      return true;
   }


   function post_purgeItem() {
      global $DB;

      parent::post_purgeItem();

      $ticket      = new Ticket();
      $itemtype_id = $ticket->getSearchOptionIDByField('field', 'itemtype', 'glpi_tickets');
      $items_id_id = $ticket->getSearchOptionIDByField('field', 'items_id', 'glpi_tickets');

      // Try to delete itemtype -> delete items_id
      if ($this->fields['num'] == $itemtype_id) {
         $query = "SELECT `id`
                   FROM `".$this->getTable()."`
                   WHERE `".static::$items_id."` = '".$this->fields['tickettemplates_id']."'
                         AND `num` = '$items_id_id'";

         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)) {
               $a = new self();
               $a->delete(array('id' => $DB->result($result,0,0)));
            }
         }
      }
   }


   /**
    * Get mandatory fields for a template
    *
    * @since version 0.83
    *
    * @param $ID                    integer  the template ID
    * @param $withtypeandcategory   boolean  with type and category (true by default)
    *
    * @return an array of mandatory fields
   **/
   function getMandatoryFields($ID, $withtypeandcategory=true) {
      global $DB;

      $sql = "SELECT *
              FROM `".$this->getTable()."`
              WHERE `".static::$items_id."` = '$ID'
              ORDER BY `id`";
      $result = $DB->query($sql);

      $tt             = new TicketTemplate();
      $allowed_fields = $tt->getAllowedFields($withtypeandcategory, true);
      $fields         = array();

      while ($rule = $DB->fetch_assoc($result)) {
         if (isset($allowed_fields[$rule['num']])) {
            $fields[$allowed_fields[$rule['num']]] = $rule['num'];
         }
      }
      return $fields;
   }


   /**
    * Print the mandatory fields
    *
    * @since version 0.83
    *
    * @param $tt                       Ticket Template
    * @param $withtemplate    boolean  Template or basic item (default '')
    *
    * @return Nothing (call to classes members)
   **/
   static function showForTicketTemplate(TicketTemplate $tt, $withtemplate='') {
      global $DB;

      $ID = $tt->fields['id'];

      if (!$tt->getFromDB($ID) || !$tt->can($ID, READ)) {
         return false;
      }
      $canedit           = $tt->canEdit($ID);
      $ttm               = new self();
      $used              = $ttm->getMandatoryFields($ID);
      $fields            = $tt->getAllowedFieldsNames(true, isset($used['itemtype']));
      $simplified_fields = $tt->getSimplifiedInterfaceFields();
      $both_interfaces   = sprintf(__('%1$s + %2$s'), __('Simplified interface'), __
                                   ('Standard interface'));

      $rand  = mt_rand();

      $query = "SELECT `glpi_tickettemplatemandatoryfields`.*
                FROM `glpi_tickettemplatemandatoryfields`
                WHERE (`tickettemplates_id` = '$ID')";

      if ($result = $DB->query($query)) {
         $mandatoryfields = array();
         $used            = array();
         if ($numrows = $DB->numrows($result)) {
            while ($data = $DB->fetch_assoc($result)) {
               $mandatoryfields[$data['id']] = $data;
               $used[$data['num']]           = $data['num'];
            }
         }
         if ($canedit) {
            echo "<div class='firstbloc'>";
            echo "<form name='changeproblem_form$rand' id='changeproblem_form$rand' method='post'
                   action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add a mandatory field')."</th></tr>";
            echo "<tr class='tab_bg_2'><td class='right'>";
            echo "<input type='hidden' name='tickettemplates_id' value='$ID'>";

            $select_fields = $fields;
            foreach ($select_fields as $key => $val) {
               if (in_array($key, $simplified_fields)) {
                  $select_fields[$key] = sprintf(__('%1$s (%2$s)'), $val, $both_interfaces);
               } else {
                  $select_fields[$key] = sprintf(__('%1$s (%2$s)'), $val, __('Standard interface'));
               }
            }
            Dropdown::showFromArray('num', $select_fields, array('used' => $used));
            echo "</td><td class='center'>";
            echo "&nbsp;<input type='submit' name='add' value=\""._sx('button', 'Add').
                         "\" class='submit'>";
            echo "</td></tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";
         }


         echo "<div class='spaced'>";
         if ($canedit && $numrows) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = array('num_displayed' => $numrows,
                                         'container'     => 'mass'.__CLASS__.$rand);
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<table class='tab_cadre_fixehov'>";
         echo "<tr class='noHover'><th colspan='3'>";
         echo self::getTypeName($DB->numrows($result));
         echo "</th></tr>";
         if ($numrows) {
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';
            if ($canedit) {
               $header_top    .= "<th width='10'>";
               $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
               $header_bottom .= "<th width='10'>";
               $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand)."</th>";
            }
            $header_end .= "<th>".__('Name')."</th>";
            $header_end .= "<th>".__("Profile's interface")."</th>";
            $header_end .= "</tr>";
            echo $header_begin.$header_top.$header_end;

            foreach ($mandatoryfields as $data) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td>".Html::getMassiveActionCheckBox(__CLASS__, $data["id"])."</td>";
               }
               echo "<td>".$fields[$data['num']]."</td>";
               echo "<td>";
               if (in_array($data['num'], $simplified_fields)) {
                  echo $both_interfaces;
               } else {
                  _e('Standard interface');
               }
               echo "</td>";
               echo "</tr>";
            }
            echo $header_begin.$header_bottom.$header_end;


         } else {
            echo "<tr><th colspan='2'>".__('No item found')."</th></tr>";
         }

         echo "</table>";
         if ($canedit && $numrows) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
         echo "</div>";
      }
   }

}
?>
