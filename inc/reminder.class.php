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
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/// Reminder class
class Reminder extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_reminders';
   public $type = 'Reminder';

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][37];
   }

   function canCreate() {
      return haveRight('reminder_public', 'w');
   }

   function canView() {
      return haveRight('reminder_public', 'r');
   }

   function prepareInputForAdd($input) {
      global $LANG;

      $input["name"] = trim($input["name"]);
      if (empty($input["name"])) {
         $input["name"]=$LANG['reminder'][15];
      }
      $input["begin"] = $input["end"] = "NULL";

      if (isset($input['plan'])) {
         if (!empty($input['plan']["begin"]) && !empty($input['plan']["end"])
             && $input['plan']["begin"]<$input['plan']["end"]) {

            $input['_plan']=$input['plan'];
            unset($input['plan']);
            $input['is_planned']=1;
            $input["begin"] = $input['_plan']["begin"];
            $input["end"] = $input['_plan']["end"];
         } else {
            addMessageAfterRedirect($LANG['planning'][1],false,ERROR);
         }
      }

      if ($input['is_recursive'] && !$input['is_private']) {
         if (!haveRecursiveAccessToEntity($input["entities_id"])) {
            unset($input['is_recursive']);
            addMessageAfterRedirect($LANG['common'][75],false,ERROR);
         }
      }

      // set new date.
      $input["date"] = $_SESSION["glpi_currenttime"];

      return $input;
   }

   function prepareInputForUpdate($input) {
      global $LANG;

      $input["name"] = trim($input["name"]);
      if (empty($input["name"])) {
         $input["name"]=$LANG['reminder'][15];
      }

      if (isset($input['plan'])) {
         if (!empty($input['plan']["begin"]) && !empty($input['plan']["end"])
             && $input['plan']["begin"]<$input['plan']["end"]) {

            $input['_plan']=$input['plan'];
            unset($input['plan']);
            $input['is_planned']=1;
            $input["begin"] = $input['_plan']["begin"];
            $input["end"] = $input['_plan']["end"];
            $input["state"] = $input['_plan']["state"];
         } else {
            addMessageAfterRedirect($LANG['planning'][1],false,ERROR);
         }
      }
      if ($input['is_recursive'] && !$input['is_private']) {
         if (!haveRecursiveAccessToEntity($input["entities_id"])) {
            unset($input['is_recursive']);
            addMessageAfterRedirect($LANG['common'][75],false,ERROR);
         }
      }
      return $input;
   }

   function pre_updateInDB($input,$updates,$oldvalues=array()) {

      // Set new user if initial user have been deleted
      if ($this->fields['users_id']==0) {
         $input['users_id']=$_SESSION["glpiID"];
         $this->fields['users_id']=$_SESSION["glpiID"];
         $updates[]="users_id";
      }
      return array($input,$updates);
   }

   function post_getEmpty () {
      global $LANG;

      $this->fields["name"]=$LANG['reminder'][6];
      $this->fields["users_id"]=$_SESSION['glpiID'];
      $this->fields["is_private"]=1;
      $this->fields["entities_id"]=$_SESSION["glpiactive_entity"];
   }

   /**
    * Print the reminder form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the item to print
    *
    **/
   function showForm ($target,$ID) {
      global $CFG_GLPI,$LANG;

      // Show Reminder or blank form
      $onfocus="";

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item : do getempty before check right to set default values
         $this->check(-1,'w');
         $onfocus="onfocus=\"if (this.value=='".$this->fields['name']."') this.value='';\"";
      }

      $canedit=$this->can($ID,'w');

      if ($canedit) {
         echo "<form method='post' name='remind' action=\"$target\">";
      }

      echo "<div class='center'><table class='tab_cadre' width='450'>";
      echo "<tr><th>&nbsp;</th><th>";
      if (!$ID) {
         echo $LANG['reminder'][6];
      } else {
         echo $LANG['common'][2]." $ID";
      }
      echo "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td>".$LANG['common'][57]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name",$this->table,"name",$this->fields['name'],80,-1,
                              $this->fields["users_id"],$onfocus);
      echo "</td></tr>\n";

      if (!$canedit) {
         echo "<tr class='tab_bg_2'><td>".$LANG['planning'][9]."&nbsp;:</td>";
         echo "<td>";
         echo getUserName($this->fields["users_id"]);
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_2'><td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";
      if ($canedit && haveRight("reminder_public","w")) {
         if (!$ID) {
            if (isset($_GET["is_private"])) {
               $this->fields["is_private"]=$_GET["is_private"];
            }
            if (isset($_GET["is_recursive"])) {
               $this->fields["is_recursive"]=$_GET["is_recursive"];
            }
         }
         Dropdown::showPrivatePublicSwitch($this->fields["is_private"],$this->fields["entities_id"],
                             $this->fields["is_recursive"]);
      } else {
         if ($this->fields["is_private"]) {
            echo $LANG['common'][77];
         } else {
            echo $LANG['common'][76];
         }
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td >".$LANG['buttons'][15]."&nbsp;:</td>";
      echo "<td class='center'>";
      if ($canedit) {
         echo "<script type='text/javascript' >\n";
         echo "function showPlan(){\n";
            echo "Ext.get('plan').setDisplayed('none');";
            $params=array('form'=>'remind');
            if ($ID && $this->fields["is_planned"]) {
               $params['state']=$this->fields["state"];
               $params['begin']=$this->fields["begin"];
               $params['end']=$this->fields["end"];
            }
            ajaxUpdateItemJsCode('viewplan',$CFG_GLPI["root_doc"]."/ajax/planning.php",$params,false);
         echo "}";
         echo "</script>\n";
      }

      if (!$ID || !$this->fields["is_planned"]) {
         if ($canedit) {
            echo "<div id='plan' onClick='showPlan()'>\n";
            echo "<span class='showplan'>".$LANG['reminder'][12]."</span>";
         }
      } else {
         if ($canedit) {
            echo "<div id='plan' onClick='showPlan()'>\n";
            echo "<span class='showplan'>";
         }
         echo Planning::getState($this->fields["state"]).": ".convDateTime($this->fields["begin"])."->".
              convDateTime($this->fields["end"]);
         if ($canedit) {
            echo "</span>";
         }
      }
      if ($canedit) {
         echo "</div>\n";
         echo "<div id='viewplan'>\n";
         echo "</div>\n";
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td>".$LANG['reminder'][9]."&nbsp;:</td><td>";
      if ($canedit) {
         echo "<textarea cols='90' rows='15' name='text'>".$this->fields["text"]."</textarea>";
      } else {
         echo nl2br($this->fields["text"]);
      }
      echo "</td></tr>\n";

      if (!$ID) { // add
         echo "<tr><td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='users_id' value=\"".$this->fields['users_id']."\">\n";
         echo "<div class='center'>";
         echo "<input type='submit' name='add' value=\"".$LANG['buttons'][8]."\" class='submit'>";
         echo "</div>";
         echo "</td></tr>\n";
      } elseif ($canedit) {
         echo "<tr><td class='tab_bg_2 top' colspan='2'>";
         echo "<input type='hidden' name='id' value='$ID'>\n";
         echo "<div class='center'>";
         echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
         echo "<input type='hidden' name='id' value='$ID'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
         echo "<input type='submit' name='delete' value=\"".$LANG['buttons'][6]."\" class='submit'>";
         echo "</div>";
         echo "</td></tr>\n";
      }
      echo "</table></div>\n";
      if ($canedit) {
         echo "</form>";
      }
      return true;
   }

   /*
    * Populate the planning with planned reminder
    *
    * @param $who ID of the user (0 = undefined)
    * @param $who_group ID of the group of users (0 = undefined)
    * @param $begin Date
    * @param $end Date
    *
    * @return array of planning item
    */
   static function populatePlanning($who, $who_group, $begin, $end) {
      global $DB, $CFG_GLPI;

      $readpub=$readpriv="";
      $interv = array();

      // See public reminder ?
      if (haveRight("reminder_public","r")) {
         $readpub="(is_private=0 AND".getEntitiesRestrictRequest("","glpi_reminders",'','',true).")";
      }

      // See my private reminder ?
      if ($who_group=="mine" || $who==$_SESSION["glpiID"]) {
         $readpriv="(is_private=1 AND users_id='".$_SESSION["glpiID"]."')";
      }

      if ($readpub && $readpriv) {
         $ASSIGN  = "($readpub OR $readpriv)";
      } else if ($readpub) {
         $ASSIGN  = $readpub;
      } else {
         $ASSIGN  = $readpriv;
      }
      if ($ASSIGN) {
         $query2 = "SELECT *
                    FROM `glpi_reminders`
                    WHERE `is_planned`='1'
                          AND $ASSIGN
                          AND `begin` < '$end'
                          AND `end` > '$begin'
                    ORDER BY `begin`";
         $result2=$DB->query($query2);

         if ($DB->numrows($result2)>0) {
            for ($i=0 ; $data=$DB->fetch_array($result2) ; $i++) {
               $interv[$data["begin"]."$$".$i]["reminders_id"]=$data["id"];
               if (strcmp($begin,$data["begin"])>0) {
                  $interv[$data["begin"]."$$".$i]["begin"]=$begin;
               } else {
                  $interv[$data["begin"]."$$".$i]["begin"]=$data["begin"];
               }
               if (strcmp($end,$data["end"])<0) {
                  $interv[$data["begin"]."$$".$i]["end"]=$end;
               } else {
                  $interv[$data["begin"]."$$".$i]["end"]=$data["end"];
               }
               $interv[$data["begin"]."$$".$i]["name"]=resume_text($data["name"],$CFG_GLPI["cut"]);
               $interv[$data["begin"]."$$".$i]["text"]=resume_text($data["text"],$CFG_GLPI["cut"]);
               $interv[$data["begin"]."$$".$i]["users_id"]=$data["users_id"];
               $interv[$data["begin"]."$$".$i]["is_private"]=$data["is_private"];
               $interv[$data["begin"]."$$".$i]["state"]=$data["state"];
            } //
         }
      }
      return $interv;
   }

   /**
    * Display a Planning Item
    *
    * @param $val Array of the item to display
    * @param $who ID of the user (0 if all)
    * @param $type position of the item in the time block (in, through, begin or end)
    * @param $complete complete display (more details)
    *
    * @return Nothing (display function)
    **/
   static function displayPlanningItem($val,$who,$type="",$complete=0) {
      global $CFG_GLPI, $LANG;

      $rand=mt_rand();
      $users_id="";  // show users_id reminder
      $img="rdv_private.png"; // default icon for reminder

      if (!$val["is_private"]) {
         $users_id="<br>".$LANG['planning'][9]."&nbsp;: ".getUserName($val["users_id"]);
         $img="rdv_public.png";
      }
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/".$img."' alt='' title='".$LANG['title'][37].
            "'>&nbsp;";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$val["reminders_id"]."'";
      if (!$complete) {
         echo "onmouseout=\"cleanhide('content_reminder_".$val["reminders_id"].$rand."')\"
               onmouseover=\"cleandisplay('content_reminder_".$val["reminders_id"].$rand."')\"";
      }
      echo ">";

      switch ($type) {
         case "in" :
            echo date("H:i",strtotime($val["begin"]))." -> ".date("H:i",strtotime($val["end"])).": ";
            break;

         case "through" :
            break;

         case "begin" :
            echo $LANG['buttons'][33]." ".date("H:i",strtotime($val["begin"])).": ";
            break;

         case "end" :
            echo $LANG['buttons'][32]." ".date("H:i",strtotime($val["end"])).": ";
            break;
      }
      echo $val["name"];
      echo $users_id;
      echo "</a>";
      if ($complete) {
         echo "<br><strong>".Planning::getState($val["state"])."</strong><br>";
         echo $val["text"];
      } else {
         echo "<div class='over_link' id='content_reminder_".$val["reminders_id"].$rand."'>";
         echo "<strong>";
         echo Planning::getState($val["state"])."</strong><br>".$val["text"]."</div>";
      }
      echo "";
   }
}

?>