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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class Link_ItemType extends CommonDBTM{

   // From CommonDBRelation
   public $itemtype_1 = 'Link';
   public $items_id_1 = 'links_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   /**
    * Print the HTML array for device on link
    *
    *@param $link : Link
    *
    *@return Nothing (display)
   **/
   static function showForLink($link) {
      global $DB,$CFG_GLPI, $LANG;

      $links_id = $link->getField('id');

      $canedit = $link->can($links_id, 'w');
      $canrecu = $link->can($links_id, 'recursive');

      if (!Session::haveRight("link","r") || !$link->can($links_id, 'r')) {
         return false;
      }

      $query = "SELECT *
                FROM `glpi_links_itemtypes`
                WHERE `links_id` = '$links_id'
                ORDER BY `itemtype`";
      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $i = 0;
      $used = array();

      echo "<form method='post' action=\"".$CFG_GLPI["root_doc"]."/front/link_itemtype.form.php\">";
      echo "<div class='center'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>".$LANG['links'][4]."&nbsp;:</th></tr>";
      echo "<tr><th>".$LANG['common'][17]."</th>";
      echo "<th>&nbsp;</th></tr>";

      while ($i < $number) {
         $ID       = $DB->result($result, $i, "id");
         $itemtype = $DB->result($result, $i, "itemtype");
         $typename = NOT_AVAILABLE;
         if ($item = getItemForItemtype($itemtype)) {
            $typename = $item->getTypeName();
         }
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>$typename</td>";
         echo "<td class='center b'>";
         echo "<a href='".$CFG_GLPI["root_doc"].
                "/front/link_itemtype.form.php?delete=deletedevice&amp;id=$ID&amp;links_id=$links_id'>
                ".$LANG['buttons'][6]."</a></td></tr>";
         $used[$itemtype] = $itemtype;
         $i++;
      }
      if ($canedit) {
         echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
         echo "<input type='hidden' name='links_id' value='$links_id'>";
         Dropdown::dropdownTypes("itemtype",'',$CFG_GLPI["link_types"],$used);
         echo "&nbsp;&nbsp;<input type='submit' name='add' value=\"".
                            $LANG['buttons'][8]."\" class='submit'>";
         echo "</td></tr>";
      }
      echo "</table></div>";
      Html::closeForm();
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if (!$withtemplate) {
         switch ($item->getType()) {
            case 'Link' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  return self::createTabEntry($LANG['links'][4],
                                              countElementsInTable($this->getTable(),
                                                                   "links_id = '".$item->getID()."'"));
               }
               return $LANG['links'][4];
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType()=='Link') {
         self::showForLink($item);
      }
      return true;
   }

}
?>
