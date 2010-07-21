<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
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
    * Print the HTML array for device on link for link $instID
    *
    *@param $links_id array : Link identifier.
    *
    *@return Nothing (display)
    *
    **/
   static function showForItem($links_id) {
      global $DB,$CFG_GLPI, $LANG;

      $link = new Link();
      if ($links_id > 0) {
         $link->check($links_id,'r');
      } else {
         // Create item
         $link->check(-1,'w');
         $link->getEmpty();
      }

      $canedit=$link->can($links_id,'w');
      $canrecu=$link->can($links_id,'recursive');

      if (!haveRight("link","r")) {
         return false;
      }
      //$canedit= haveRight("link","w");
      $query = "SELECT *
                FROM `glpi_links_itemtypes`
                WHERE `links_id`='$links_id'
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
         $ID=$DB->result($result, $i, "id");
         $itemtype = $DB->result($result, $i, "itemtype");
         $typename=NOT_AVAILABLE;
         if (class_exists($itemtype)) {
            $item = new $itemtype();
            $typename = $item->getTypeName();
         }
         echo "<tr class='tab_bg_1'>";
         echo "<td class='center'>$typename</td>";
         echo "<td class='center'>";
         echo "<a href='".$CFG_GLPI["root_doc"]."/front/link_itemtype.form.php?delete=deletedevice&amp;id=$ID&amp;links_id=$links_id'>
               <strong>".$LANG['buttons'][6]."</strong></a></td></tr>";
         $used[$itemtype] = $itemtype;
         $i++;
      }
      if ($canedit) {
         echo "<tr class='tab_bg_1'><td>&nbsp;</td><td class='center'>";
         echo "<div class='software-instal'><input type='hidden' name='links_id' value='$links_id'>";
         Dropdown::dropdownTypes("itemtype",'',$CFG_GLPI["link_types"],$used);
         echo "&nbsp;&nbsp;<input type='submit' name='add' value=\"".
                            $LANG['buttons'][8]."\" class='submit'>";
         echo "</div></td></tr>";
      }
      echo "</table></div></form>";
   }
}
?>
