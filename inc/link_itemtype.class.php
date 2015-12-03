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

class Link_Itemtype extends CommonDBChild {
   // From CommonDbChild
   static public $itemtype = 'Link';
   static public $items_id = 'links_id';


   /**
    * @since version 0.84
   **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * Print the HTML array for device on link
    *
    * @param $link : Link
    *
    * @return Nothing (display)
   **/
   static function showForLink($link) {
      global $DB,$CFG_GLPI;

      $links_id = $link->getField('id');

      $canedit  = $link->canEdit($links_id);
      $rand     = mt_rand();

      if (!Link::canView()
          || !$link->can($links_id, READ)) {
         return false;
      }

      $query = "SELECT *
                FROM `glpi_links_itemtypes`
                WHERE `links_id` = '$links_id'
                ORDER BY `itemtype`";
      $result = $DB->query($query);
      $types  = array();
      $used   = array();
      if ($numrows = $DB->numrows($result)) {
         while ($data = $DB->fetch_assoc($result)) {
            $types[$data['id']]      = $data;
            $used[$data['itemtype']] = $data['itemtype'];
         }
      }

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='changeticket_form$rand' id='changeticket_form$rand' method='post'
                action='".Toolbox::getItemTypeFormURL(__CLASS__)."'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>".__('Add an item type')."</th></tr>";

         echo "<tr class='tab_bg_2'><td class='right'>";
         echo "<input type='hidden' name='links_id' value='$links_id'>";
         Dropdown::showItemTypes('itemtype', $CFG_GLPI["link_types"], array('used' => $used));
         echo "</td><td class='center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";

         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $numrows) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams = array('num_displayed'  => $numrows,
                                      'container'      => 'mass'.__CLASS__.$rand);
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixe'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $numrows) {
         $header_top    .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>".Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "</tr>";
      echo $header_begin.$header_top.$header_end;

      foreach ($types as $data) {
         $typename = NOT_AVAILABLE;
         if ($item = getItemForItemtype($data['itemtype'])) {
            $typename = $item->getTypeName(1);
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }
            echo "<td class='center'>$typename</td>";
            echo "</tr>";
         }
      }
      echo $header_begin.$header_bottom.$header_end;
      echo "</table>";
      if ($canedit && $numrows) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
            case 'Link' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = countElementsInTable($this->getTable(),
                                             "links_id = '".$item->getID()."'");
               }
               return self::createTabEntry(_n('Associated item type', 'Associated item types',
                                              Session::getPluralNumber()), $nb);
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == 'Link') {
         self::showForLink($item);
      }
      return true;
   }


   /**
    *
    * Remove all associations for an itemtype
    *
    * @since version 0.85
    *
    * @param $itemtype itemtype for which all link associations must be removed
    */
   static function deleteForItemtype($itemtype) {
      global $DB;

      $query = "DELETE
                FROM `".self::getTable()."`
                WHERE `itemtype` LIKE '%Plugin$itemtype%'";
      $DB->query($query);
   }

}
?>