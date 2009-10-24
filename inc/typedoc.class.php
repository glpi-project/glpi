<?php
/*
 * @version $Id$
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// TypeDoc class
class Typedoc  extends CommonDBTM {

   /**
    * Constructor
    **/
   function __construct () {
      $this->table="glpi_documentstypes";
      $this->type=TYPEDOC_TYPE;
   }


   function defineTabs($ID,$withtemplate){
      global $LANG;

      $ong=array();

      $ong[1]=$LANG['title'][26];

      return $ong;
   }

   /**
    * Print the typedoc form
    *
    *@param $target form target
    *@param $ID Integer : Id of the typedoc
    *
    *@return boolean : typedoc found
    **/
   function showForm ($target,$ID) {
      global $CFG_GLPI, $LANG;

      if (!haveRight("typedoc","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, '',getActiveTab($this->type));
      $this->showFormHeader($target,$ID,'',2);

      echo "<tr class='tab_bg_1'><td>".$LANG['common'][16]."&nbsp;: </td><td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td>";
      echo "<td rowspan='6' class='middle'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='6'>";
      echo "<textarea cols='45' rows='7' name='comment' >".$this->fields["comment"];
      echo "</textarea></td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['document'][10]."&nbsp;: </td><td>";
      dropdownIcons("icon",$this->fields["icon"],GLPI_ROOT."/pics/icones");
      if (!empty($this->fields["icon"])) echo "&nbsp;<img style='vertical-align:middle;' alt='' src='".$CFG_GLPI["typedoc_icon_dir"]."/".$this->fields["icon"]."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['document'][11]."&nbsp;: </td><td>";
      dropdownYesNo("is_uploadable",$this->fields["is_uploadable"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['document'][9]."&nbsp;: </td><td>";
      autocompletionTextField("ext",$this->table,"ext",$this->fields["ext"],40);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'><td>".$LANG['document'][4]."&nbsp;: </td><td>";
      autocompletionTextField("mime",$this->table,"mime",$this->fields["mime"],40);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center'>".$LANG['common'][26] . "&nbsp;: ";
      echo convDateTime($this->fields["date_mod"])."</td></tr>\n";

      $this->showFormButtons($ID,'',2);

      return true;
   }

}

?>
