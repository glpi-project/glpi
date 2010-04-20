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

// ----------------------------------------------------------------------
// Original Author of file: Jean-mathieu Doléans
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/// Reminder class
class Reminder extends CommonDBTM {

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

      manageBeginAndEndPlanDates($input['plan']);

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

      if (isset($input['is_recursive']) && $input['is_recursive'] && !$input['is_private']) {
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

      manageBeginAndEndPlanDates($input['plan']);

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
      if (isset($input['is_recursive']) && $input['is_recursive'] && !$input['is_private']) {
         if (!haveRecursiveAccessToEntity($input["entities_id"])) {
            unset($input['is_recursive']);
            addMessageAfterRedirect($LANG['common'][75],false,ERROR);
         }
      }
      return $input;
   }

   function pre_updateInDB() {

      // Set new user if initial user have been deleted
      if ($this->fields['users_id']==0 && $uid=getLoginUserID()) {
         $this->fields['users_id']=$uid;
         $this->updates[]="users_id";
      }
   }

   function post_getEmpty () {
      global $LANG;

      $this->fields["name"]=$LANG['reminder'][6];
      $this->fields["users_id"]=getLoginUserID();
      $this->fields["is_private"]=1;
      $this->fields["entities_id"]=$_SESSION["glpiactive_entity"];
   }

   /**
    * Print the reminder form
    *
    * @param $ID Integer : Id of the item to print
    * @param $options array
    *     - target filename : where to go when done.
    *
    **/
   function showForm ($ID, $options=array()) {
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
         echo "<form method='post' name='remind' action='".$this->getFormURL()."'>";
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
      autocompletionTextField($this,"name",array('size'   => 80,
                                                 'entity' => -1,
                                                 'user'   => $this->fields["users_id"],
                                                 'option' => $onfocus));
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
         echo Planning::getState($this->fields["state"]).": ".convDateTime($this->fields["begin"]).
              "->". convDateTime($this->fields["end"]);
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
      if ($who_group=="mine" || $who===getLoginUserID()) {
         $readpriv="(is_private=1 AND users_id='".getLoginUserID()."')";
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
      echo "<a id='reminder_".$val["reminders_id"].$rand."' href='".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$val["reminders_id"]."'>";

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
         showToolTip("<strong>".Planning::getState($val["state"])."</strong><br>".$val["text"],
                     array('applyto'=>"reminder_".$val["reminders_id"].$rand));
      }
      echo "";
   }

   static function showListForCentral($entity = -1, $parent = false) {
      global $DB,$CFG_GLPI, $LANG;

      // show reminder that are not planned
      $users_id=getLoginUserID();
      $today=$_SESSION["glpi_currenttime"];

      if ($entity < 0) {
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `users_id` = '$users_id'
                        AND `is_private` = '1'
                        AND (`end` >= '$today'
                              OR `is_planned` = '0')
                  ORDER BY `name`";
         $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG['reminder'][0]."</a>";
         $is_private = 1;
      } else if ($entity == $_SESSION["glpiactive_entity"]) {
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `is_private` = '0' ".
                        getEntitiesRestrictRequest("AND","glpi_reminders","",$entity)."
                  ORDER BY `name`";
         $titre = "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.php\">".$LANG['reminder'][1].
                  "</a> (".Dropdown::getDropdownName("glpi_entities", $entity).")";

         if (haveRight("reminder_public","w")) {
            $is_private = 0;
         }
      } else if ($parent) {
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `is_private` = '0'
                        AND `is_recursive` = '1' ".
                        getEntitiesRestrictRequest("AND","glpi_reminders","",$entity)."
                  ORDER BY `name`";
         $titre = $LANG['reminder'][1]." (".Dropdown::getDropdownName("glpi_entities", $entity).")";
      } else { // Filles
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `is_private` = '0' ".
                        getEntitiesRestrictRequest("AND","glpi_reminders","",$entity)."
                  ORDER BY `name`";
         $titre = $LANG['reminder'][1]." (".Dropdown::getDropdownName("glpi_entities", $entity).")";
      }

      $result = $DB->query($query);
      $nb=$DB->numrows($result);

      if ($nb || isset($is_private)) {
         echo "<br><table class='tab_cadrehov'>";
         echo "<tr><th><div class='relative'><span>$titre</span>";
         if (isset($is_private)) {
            echo "<span class='reminder_right'>";
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?is_private=$is_private\">";
            echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/plus.png\" alt='+' title='".
                  $LANG['buttons'][8]."'></a></span>";
         }
         echo "</div></th></tr>\n";
      }
      if ($nb) {
         $rand=mt_rand();
         while ($data =$DB->fetch_array($result)) {
            echo "<tr class='tab_bg_2'><td><div class='relative reminder_list'>";
            echo "<a id='content_reminder_".$data["id"].$rand."'
                  href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".$data["id"]."\">".
                  $data["name"]."</a>&nbsp;";
            showToolTip($data["text"],array('applyto'=>"content_reminder_".$data["id"].$rand));

            if ($data["is_planned"]) {
               $tab=explode(" ",$data["begin"]);
               $date_url=$tab[0];
               echo "<span class='reminder_right'>";
               echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticketplanning.php?date=".$date_url.
                     "&amp;type=day\">";
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG['Menu'][29].
                     "' title='".convDateTime($data["begin"])."=>".convDateTime($data["end"])."'>";
               echo "</a></span>";
            }
            echo "</div></td></tr>\n";
         }
      }

      if ($nb || isset($is_private)) {
         echo "</table>\n";
      }
   }

   static function showList($is_private=1,$is_recursive=0) {
      global $DB,$CFG_GLPI, $LANG;

      // show reminder that are not planned
      $planningRight=haveRight("show_planning","1");
      $users_id=getLoginUserID();

      if (!$is_private && $is_recursive) { // show public reminder
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `is_private` = '0'
                        AND `is_recursive` = '1' ".
                        getEntitiesRestrictRequest("AND","glpi_reminders","","",true);
         $titre=$LANG['reminder'][16];
      } else if (!$is_private && !$is_recursive) { // show public reminder
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `is_private` = '0'
                        AND `is_recursive` = '0' ".
                        getEntitiesRestrictRequest("AND","glpi_reminders");
         $titre=$LANG['reminder'][1];
      } else { // show private reminder
         $query = "SELECT *
                  FROM `glpi_reminders`
                  WHERE `users_id` = '$users_id'
                        AND `is_private` = '1'";
         $titre=$LANG['reminder'][0];
      }
      $result = $DB->query($query);

      $tabremind=array();
      $remind=new Reminder();

      if ($DB->numrows($result)>0) {
         for ($i=0 ; $data=$DB->fetch_array($result) ; $i++) {
            $remind->getFromDB($data["id"]);
            if ($data["is_planned"]) { //Un rdv on va trier sur la date begin
               $sort=$data["begin"];
            } else { // non programmé on va trier sur la date de modif...
               $sort=$data["date"];
            }
            $tabremind[$sort."$$".$i]["reminders_id"]=$remind->fields["id"];
            $tabremind[$sort."$$".$i]["users_id"]=$remind->fields["users_id"];
            $tabremind[$sort."$$".$i]["entity"]=$remind->fields["entities_id"];
            $tabremind[$sort."$$".$i]["begin"]=($data["is_planned"]?"".$data["begin"]."":"".
                                                $data["date"]."");
            $tabremind[$sort."$$".$i]["end"]=($data["is_planned"]?"".$data["end"]."":"");
            $tabremind[$sort."$$".$i]["name"]=resume_text($remind->fields["name"],$CFG_GLPI["cut"]);
            $tabremind[$sort."$$".$i]["text"]=resume_text($remind->fields["text"],$CFG_GLPI["cut"]);
         }
      }
      ksort($tabremind);

      echo "<br><table class='tab_cadre_fixehov'>";
      if ($is_private) {
         echo "<tr><th>"."$titre"."</th><th colspan='2'>".$LANG['common'][27]."</th></tr>\n";
      } else {
         echo "<tr><th colspan='5'>"."$titre"."</th></tr>\n";
         echo "<tr><th>".$LANG['entity'][0]."</th>";
         echo "<th>".$LANG['common'][37]."</th>";
         echo "<th>".$LANG['title'][37]."</th>";
         echo "<th colspan='2'>".$LANG['common'][27]."</th></tr>\n";
      }

      if (count($tabremind)>0) {
         foreach ($tabremind as $key => $val) {
            echo "<tr class='tab_bg_2'>";
            if (!$is_private) {
               // preg to split line (if needed) before ">" sign in completename
               echo "<td>" .preg_replace("/ ([[:alnum:]])/", "&nbsp;\\1",
                                       Dropdown::getDropdownName("glpi_entities", $val["entity"])). "</td>";
               echo "<td>" .Dropdown::getDropdownName("glpi_users", $val["users_id"]) . "</td>";
            }
            echo "<td width='60%' class='left'>";
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/reminder.form.php?id=".
                  $val["reminders_id"]."\">".$val["name"]."</a>";
            echo "<div class='kb_resume'>".resume_text($val["text"],125)."</div></td>";

            if ($val["end"]!="") {
               echo "<td class='center'>";
               $tab=explode(" ",$val["begin"]);
               $date_url=$tab[0];
               if ($planningRight) {
                  echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/ticketplanning.php?date=".$date_url.
                        "&amp;type=day\">";
               }
               echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/rdv.png\" alt='".$LANG['Menu'][29].
                     "' title='".$LANG['Menu'][29]."'>";
               if ($planningRight) {
                  echo "</a>";
               }
               echo "</td>";
               echo "<td class='center' >".convDateTime($val["begin"]);
               echo "<br>".convDateTime($val["end"])."";
            } else {
               echo "<td>&nbsp;</td>";
               echo "<td class='center'>";
               echo "<span style='color:#aaaaaa;'>".convDateTime($val["begin"])."</span>";
            }
            echo "</td></tr>\n";
         }
      }
      echo "</table>\n";
   }


}

?>
