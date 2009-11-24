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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
   }

// FUNCTIONS Computers

/**
 * Print the form for devices linked to a computer or a template
 *
 *
 * Print the form for devices linked to a computer or a template
 *
 *@param $target filename : where to go when done.
 *@param $ID Integer : Id of the computer or the template to print
 *@param $withtemplate='' boolean : template or basic computer
 *
 *
 *@return Nothing (display)
 *
 **/
function showDeviceComputerForm($target,$ID,$withtemplate='') {
   global $LANG,$CFG_GLPI;

   if (!haveRight("computer","r")) {
      return false;
   }
   $canedit=haveRight("computer","w");

   $comp = new Computer;
   if (empty($ID) && $withtemplate == 1) {
      $comp->getEmpty();
   } else {
      $comp->getFromDBwithDevices($ID);
   }

   if (!empty($ID)) {
      echo "<div class='center'>";
      echo "<form name='form_device_action' action=\"$target\" method=\"post\" >";
      echo "<input type='hidden' name='id' value='$ID'>";
      echo "<input type='hidden' name='device_action' value='$ID'>";
      echo "<table class='tab_cadre_fixe' >";
      echo "<tr><th colspan='65'>".$LANG['title'][30]."</th></tr>";
      foreach($comp->devices as $key => $val) {
         $device = new Device($val["devType"]);
         $device->getFromDB($val["devID"]);
         printDeviceComputer($device,$val["quantity"],$val["specificity"],$comp->fields["id"],
                             $val["compDevID"],$withtemplate);

      }

      if ($canedit && !(!empty($withtemplate) && $withtemplate == 2)
                   && count($comp->devices)) {
         echo "<tr><td colspan='65' class='tab_bg_1 center'>";
         echo "<input type='submit' class='submit' name='update_device' value='".
                $LANG['buttons'][7]."'></td></tr>";
      }
      echo "</table>";
      echo "</form>";
      //ADD a new device form.
      device_selecter($target,$comp->fields["id"],$withtemplate);
      echo "</div><br>";
   }
}

/**
 * Print the computers or template local connections form.
 *
 * Print the form for computers or templates connections to printers, screens or peripherals
 *
 *@param $target
 *@param $ID integer: Computer or template ID
 *@param $withtemplate=''  boolean : Template or basic item.
 *
 *@return Nothing (call to classes members)
 *
 **/
function showConnections($target,$ID,$withtemplate='') {
   global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES;

   $ci=new CommonItem;
   $used = array();
   $items=array(PRINTER_TYPE=>$LANG['computers'][39],
                MONITOR_TYPE=>$LANG['computers'][40],
                PERIPHERAL_TYPE=>$LANG['computers'][46],
                PHONE_TYPE=>$LANG['computers'][55]);
   $comp=new Computer();
   $canedit=haveTypeRight(COMPUTER_TYPE,"w");

   if ($comp->getFromDB($ID)) {
      foreach ($items as $itemtype => $title) {
         if (!haveTypeRight($itemtype,"r")) {
            unset($items[$itemtype]);
         }
      }
      if (count($items)){
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='".max(2,count($items))."'>".$LANG['connect'][0].":</th></tr>";

         echo "<tr>";
         $header_displayed=0;
         foreach ($items as $itemtype => $title) {
            if ($header_displayed==2) {
               break;
            }
            echo "<th>".$title.":</th>";
            $header_displayed++;
         }
         echo "</tr>";
         echo "<tr class='tab_bg_1'>";
         $items_displayed=0;
         foreach ($items as $itemtype=>$title) {
            if ($items_displayed==2) {
               echo "</tr><tr>";
               $header_displayed=0;
               foreach ($items as $tmp_title) {
                  if ($header_displayed>=2) {
                     echo "<th>".$tmp_title.":</th>";
                  }
                  $header_displayed++;
               }
               echo "</tr><tr class='tab_bg_1'>";
            }
            echo "<td class='center'>";
            $query = "SELECT *
                      FROM `glpi_computers_items`
                      WHERE `computers_id` = '$ID'
                            AND `itemtype` = '".$itemtype."'";
            if ($result=$DB->query($query)) {
               $resultnum = $DB->numrows($result);
               if ($resultnum>0) {
                  echo "<table width='100%'>";
                  for ($i=0; $i < $resultnum; $i++) {
                     $tID = $DB->result($result, $i, "items_id");
                     $connID = $DB->result($result, $i, "id");
                     $ci->getFromDB($itemtype,$tID);
                     $used[] = $tID;

                     echo "<tr ".($ci->getField('is_deleted')?"class='tab_bg_2_2'":"").">";
                     echo "<td class='center'><strong>";
                     echo $ci->getLink();
                     echo "</strong>";
                     echo " - ".getDropdownName("glpi_states",$ci->getField('state'));
                     echo "</td><td>".$ci->getField('serial');
                     echo "</td><td>".$ci->getField('otherserial');
                     echo "</td><td>";
                     if ($canedit && (empty($withtemplate) || $withtemplate != 2)) {
                        echo "<td class='center'>";
                        echo "<a href=\"".$CFG_GLPI["root_doc"].
                               "/front/computer.form.php?computers_id=$ID&amp;id=$connID&amp;" .
                               "disconnect=1&amp;withtemplate=".$withtemplate."\"><strong>";
                        echo $LANG['buttons'][10];
                        echo "</strong></a></td>";
                     }
                     echo "</tr>";
                  }
                  echo "</table>";
               } else {
                  switch ($itemtype) {
                     case PRINTER_TYPE :
                        echo $LANG['computers'][38];
                        break;

                     case MONITOR_TYPE:
                        echo $LANG['computers'][37];
                        break;

                     case PERIPHERAL_TYPE:
                        echo $LANG['computers'][47];
                        break;

                     case PHONE_TYPE:
                        echo $LANG['computers'][54];
                        break;
                  }
                  echo "<br>";
               }
               if ($canedit) {
                  if(empty($withtemplate) || $withtemplate != 2) {
                     echo "<form method='post' action=\"$target\">";
                     echo "<input type='hidden' name='connect' value='connect'>";
                     echo "<input type='hidden' name='computers_id' value='$ID'>";
                     echo "<input type='hidden' name='itemtype' value='".$itemtype."'>";
                     if (empty($withtemplate)) {
                        echo "<input type='hidden' name='dohistory' value='1'>";
                     } else { // No history for template
                        echo "<input type='hidden' name='dohistory' value='0'>";
                     }
                     dropdownConnect($itemtype,COMPUTER_TYPE,"item",$comp->fields["entities_id"],
                                     $withtemplate,$used);
                     echo "<input type='submit' value=\"".$LANG['buttons'][9]."\" class='submit'>";
                     echo "</form>";
                  }
               }
            }
            echo "</td>";
            $items_displayed++;
         }
         echo "</tr>";
         echo "</table></div><br>";
      }
   }
}

/**
 * Print the computers disks
 *
 *@param $ID integer: Computer or template ID
 *@param $withtemplate=''  boolean : Template or basic item.
 *
 *@return Nothing (call to classes members)
 *
 **/
function showComputerDisks($ID,$withtemplate='') {
   global $DB, $CFG_GLPI, $LANG;

   $comp = new Computer();
   if (!$comp->getFromDB($ID) || ! $comp->can($ID, "r")) {
      return false;
   }
   $canedit = $comp->can($ID, "w");

   echo "<div class='center'>";

   $query = "SELECT `glpi_filesystems`.`name` as fsname, `glpi_computerdisks`.*
             FROM `glpi_computerdisks`
             LEFT JOIN `glpi_filesystems`
                       ON (`glpi_computerdisks`.`filesystems_id` = `glpi_filesystems`.`id`)
             WHERE (`computers_id` = '$ID')";

   if ($result=$DB->query($query)) {
      echo "<table class='tab_cadre_fixe'><tr>";
      echo "<th colspan='6'>".$LANG['computers'][8]."</th></tr>";
      if ($DB->numrows($result)) {
         echo "<tr><th>".$LANG['common'][16]."</th>";
         echo "<th>".$LANG['computers'][6]."</th>";
         echo "<th>".$LANG['computers'][5]."</th>";
         echo "<th>".$LANG['computers'][4]."</th>";
         echo "<th>".$LANG['computers'][3]."</th>";
         echo "<th>".$LANG['computers'][2]."</th>";
         echo "</tr>";

         initNavigateListItems(COMPUTERDISK_TYPE, $LANG['help'][25]." = ".
                               (empty($comp->fields['name']) ? "($ID)" : $comp->fields['name']));

         while ($data=$DB->fetch_assoc($result)) {
            echo "<tr class='tab_bg_2'>";
            if ($canedit) {
               echo "<td><a href='computerdisk.form.php?id=".$data['id']."'>".
                          $data['name'].(empty($data['name'])?$data['id']:"")."</a></td>";
            } else {
               echo "<td>".$data['name'].(empty($data['name'])?$data['id']:"")."</td>";
            }
            echo "<td>".$data['device']."</td>";
            echo "<td>".$data['mountpoint']."</td>";
            echo "<td>".$data['fsname']."</td>";
            echo "<td class='right'>".formatNumber($data['totalsize'], false, 0)."&nbsp;".
                   $LANG['common'][82]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
            echo "<td class='right'>".formatNumber($data['freesize'], false, 0)."&nbsp;".
                   $LANG['common'][82]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";

            addToNavigateListItems(COMPUTERDISK_TYPE,$data['id']);
         }
      } else {
         echo "<tr><th colspan='6'>".$LANG['search'][15]."</th></tr>";
      }
   if ($canedit &&!(!empty($withtemplate) && $withtemplate == 2)) {
      echo "<tr class='tab_bg_2'><th colspan='6'>";
      echo "<a href='computerdisk.form.php?computers_id=$ID&amp;withtemplate=".
             $withtemplate."'>".$LANG['computers'][7]."</a></th></tr>";
   }
   echo "</table>";
   }
   echo "</div><br>";
}
?>