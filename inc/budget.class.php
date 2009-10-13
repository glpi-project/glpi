<?php

/*
 * @version $Id: bookmark.class.php 8095 2009-03-19 18:27:00Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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

/**
 * Budget class
 */
class Budget extends CommonDBTM{

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_budgets";
      $this->type=BUDGET_TYPE;
      $this->entity_assign = true;
      $this->may_be_recursive = true;
      $this->dohistory=true;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;
      $ong=array();
      $ong[1]=$LANG['title'][26];

      if ($ID>0) {
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if(empty($withtemplate)) {
            $ong[2]=$LANG['common'][1];
            if (haveRight("link","r")) {
               $ong[7]=$LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10]=$LANG['title'][37];
            }
            $ong[12]=$LANG['title'][38];
         }
      }

      return $ong;
   }

   /**
    * Print the contact form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the contact to print
    *@param $withtemplate='' boolean : template or basic item
    *
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {

      global $CFG_GLPI, $LANG;

      if (!haveRight("budget","r")) return false;

      $use_cache=true;

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $use_cache=false;
         $this->getEmpty();
      }

      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]." : </td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],
         40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='4' class='middle right'>".$LANG['common'][25].
         "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='4'>.<textarea cols='45'
      rows='4' name='comment' >".$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['financial'][21]." :</td>";
      echo "<td><input type='text' name='value' size='14'
         value=\"".formatNumber($this->fields["value"],true)."\" ></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['search'][8]." : </td>";
      echo "<td>";
      showDateFormItem("begin_date",$this->fields["begin_date"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['search'][9]." : </td>";
      echo "<td>";
      showDateFormItem("end_date",$this->fields["end_date"]);
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   function prepareInputForAdd($input) {

      if (isset($input["id"])&&$input["id"]>0){
         $input["_oldID"]=$input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }

   function post_addItem($newID,$input) {
      global $DB;

      // Manage add from template
      if (isset($input["_oldID"])) {
         // ADD Documents
         $query="SELECT `documents_id`
                 FROM `glpi_documents_items`
                 WHERE `items_id` = '".$input["_oldID"]."'
                       AND `itemtype` = '".$this->type."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               addDeviceDocument($data["documents_id"],$this->type,$newID);
            }
         }
      }
   }

}

?>