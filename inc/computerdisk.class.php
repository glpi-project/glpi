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

/// Disk class
class ComputerDisk extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_computerdisks';
   public $type = COMPUTERDISK_TYPE;
   public $entity_assign = true;

   function prepareInputForAdd($input) {
      // Not attached to software -> not added
      if (!isset($input['computers_id']) || $input['computers_id'] <= 0) {
         return false;
      }
      return $input;
   }

   function post_getEmpty () {
      $this->fields["totalsize"]='0';
      $this->fields["freesize"]='0';
   }

   function getEntityID () {
      if (isset($this->fields['computers_id']) && $this->fields['computers_id'] >0) {
         $computer=new Computer();

         $computer->getFromDB($this->fields['computers_id']);
         return $computer->fields['entities_id'];
      }
      return -1;
   }

   /**
   * Print the version form
   *
   *@param $target form target
   *@param $ID Integer : Id of the version or the template to print
   *@param $computers_id ID of the computer for add process
   *
   *@return true if displayed  false if item not found or not right to display
   **/
   function showForm($target,$ID,$computers_id=-1) {
      global $CFG_GLPI,$LANG;

      if (!haveRight("computer","w")) {
        return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, false, getActiveTab($this->type),array(),"computers_id="
                      .$this->fields['computers_id']);
      $this->showFormHeader($target,$ID,'',2);

      if ($ID>0) {
        $computers_id=$this->fields["computers_id"];
      } else {
         echo "<input type='hidden' name='computers_id' value='$computers_id'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][25]."&nbsp;:</td>";
      echo "<td colspan='3'>";
      echo "<a href='computer.form.php?id=".$computers_id."'>".
             getDropdownName("glpi_computers",$computers_id)."</a>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields["name"],40);
      echo "</td><td>".$LANG['computers'][6]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("device",$this->table,"device", $this->fields["device"],40);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][5]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("mountpoint",$this->table,"mountpoint",
                              $this->fields["mountpoint"],40);
      echo "</td><td>".$LANG['computers'][4]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_filesystems", "filesystems_id", $this->fields["filesystems_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][3]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("totalsize",$this->table,"totalsize",
                              $this->fields["totalsize"],40);
      echo "&nbsp;".$LANG['common'][82]."</td>";

      echo "<td>".$LANG['computers'][2]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("freesize",$this->table,"freesize",
                              $this->fields["freesize"],40);
      echo "&nbsp;".$LANG['common'][82]."</td></tr>";

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;

   }

   function defineTabs($ID,$withtemplate) {
      global $LANG,$CFG_GLPI;

      $ong[1]=$LANG['title'][26];

      return $ong;
   }
}

?>