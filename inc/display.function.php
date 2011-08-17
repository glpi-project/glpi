<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//*************************************************************************************************
//*************************************************************************************************
//***********  Fonctions d'affichage header footer helpdesk pager *********************************
//*************************************************************************************************
//*************************************************************************************************




/**
 * Display Debug Informations
 *
 * @param $with_session with session information
**/
function displayDebugInfos($with_session=true) {
   global $CFG_GLPI, $DEBUG_SQL, $SQL_TOTAL_REQUEST, $SQL_TOTAL_TIMER, $DEBUG_AUTOLOAD;

   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
      echo "<div id='debug'>";
      echo "<h1><a id='see_debug' name='see_debug'>GLPI MODE DEBUG</a></h1>";

      if ($CFG_GLPI["debug_sql"]) {
         echo "<h2>SQL REQUEST : ";
         echo $SQL_TOTAL_REQUEST." Queries ";
         echo "took  ".array_sum($DEBUG_SQL['times'])."s  </h2>";

         echo "<table class='tab_cadre'><tr><th>N&#176; </th><th>Queries</th><th>Time</th>";
         echo "<th>Errors</th></tr>";

         foreach ($DEBUG_SQL['queries'] as $num => $query) {
            echo "<tr class='tab_bg_".(($num%2)+1)."'><td>$num</td><td>";
            echo str_ireplace("ORDER BY","<br>ORDER BY",
                        str_ireplace("SORT","<br>SORT",
                              str_ireplace("LEFT JOIN","<br>LEFT JOIN",
                                    str_ireplace("INNER JOIN","<br>INNER JOIN",
                                          str_ireplace("WHERE","<br>WHERE",
                                                str_ireplace("FROM","<br>FROM",
                                                      str_ireplace("UNION","<br>UNION<br>",
                                                            str_replace(">","&gt;",
                                                                str_replace("<","&lt;",$query)))))))));
            echo "</td><td>";
            echo $DEBUG_SQL['times'][$num];
            echo "</td><td>";
            if (isset($DEBUG_SQL['errors'][$num])) {
               echo $DEBUG_SQL['errors'][$num];
            } else {
               echo "&nbsp;";
            }
            echo "</td></tr>";
         }
         echo "</table>";
      }

      if ($CFG_GLPI["debug_vars"]) {
         echo "<h2>AUTOLOAD</h2>";
         echo "<p>" . implode(', ', $DEBUG_AUTOLOAD) . "</p>";
         echo "<h2>POST VARIABLE</h2>";
         printCleanArray($_POST);
         echo "<h2>GET VARIABLE</h2>";
         printCleanArray($_GET);
         if ($with_session) {
            echo "<h2>SESSION VARIABLE</h2>";
            printCleanArray($_SESSION);
         }
      }
      echo "</div>";
   }
}




/**
 * Print the helpdesk
 *
 * @param $ID int : ID of the user who want to display the Helpdesk
 * @param $from_helpdesk int : is display from the helpdesk.php ?
 *
 * @return nothing (print the helpdesk)
**/
function printHelpDesk ($ID, $from_helpdesk) {
   global $DB, $CFG_GLPI, $LANG;

   if (!Session::haveRight("create_ticket","1")) {
      return false;
   }

   if (Session::haveRight('validate_ticket',1)) {
      $opt = array();
      $opt['reset']         = 'reset';
      $opt['field'][0]      = 55; // validation status
      $opt['searchtype'][0] = 'equals';
      $opt['contains'][0]   = 'waiting';
      $opt['link'][0]       = 'AND';

      $opt['field'][1]      = 59; // validation aprobator
      $opt['searchtype'][1] = 'equals';
      $opt['contains'][1]   = Session::getLoginUserID();
      $opt['link'][1]       = 'AND';

      $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".Toolbox::append_params($opt, '&amp;');

      if (TicketValidation::getNumberTicketsToValidate(Session::getLoginUserID()) >0) {
         echo "<a href='$url_validate' title=\"".$LANG['validation'][15]."\"
                alt=\"".$LANG['validation'][15]."\">".$LANG['validation'][33]."</a><br><br>";
      }
   }

   $query = "SELECT `realname`, `firstname`, `name`
             FROM `glpi_users`
             WHERE `id` = '$ID'";
   $result = $DB->query($query);

   $email  = UserEmail::getDefaultForUser($ID);

   // Get saved data from a back system
   $use_email_notification = 1;
   if ($email=="") {
      $use_email_notification = 0;
   }
   $itemtype            = 0;
   $items_id            = "";
   $content             = "";
   $title               = "";
   $itilcategories_id   = 0;
   $urgency             = 3;
   $type                = 0;

   if (isset($_SESSION["helpdeskSaved"]['_users_id_requester_notif'])
       && isset($_SESSION["helpdeskSaved"]['_users_id_requester_notif']['use_notification'])) {
      $use_email_notification = stripslashes($_SESSION["helpdeskSaved"]['_users_id_requester_notif']['use_notification']);
   }
   if (isset($_SESSION["helpdeskSaved"]["email"])) {
      $email = stripslashes($_SESSION["helpdeskSaved"]["user_email"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["itemtype"])) {
      $itemtype = stripslashes($_SESSION["helpdeskSaved"]["itemtype"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["items_id"])) {
      $items_id = stripslashes($_SESSION["helpdeskSaved"]["items_id"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["content"])) {
      $content = Html::cleanPostForTextArea($_SESSION["helpdeskSaved"]["content"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["name"])) {
      $title = stripslashes($_SESSION["helpdeskSaved"]["name"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["itilcategories_id"])) {
      $itilcategories_id = stripslashes($_SESSION["helpdeskSaved"]["itilcategories_id"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["type"])) {
      $type = stripslashes($_SESSION["helpdeskSaved"]["type"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["urgency"])) {
      $urgency = stripslashes($_SESSION["helpdeskSaved"]["urgency"]);
   }

   unset($_SESSION["helpdeskSaved"]);

   echo "<form method='post' name='helpdeskform' action='".
          $CFG_GLPI["root_doc"]."/front/tracking.injector.php' enctype='multipart/form-data'>";
   echo "<input type='hidden' name='_from_helpdesk' value='$from_helpdesk'>";
   echo "<input type='hidden' name='requesttypes_id' value='".RequestType::getDefault('helpdesk')."'>";

   if ($CFG_GLPI['urgency_mask']==(1<<3)) {
      // Dont show dropdown if only 1 value enabled
      echo "<input type='hidden' name='urgency' value='3'>";
   }
   echo "<input type='hidden' name='entities_id' value='".$_SESSION["glpiactive_entity"]."'>";
   echo "<div class='center'><table class='tab_cadre_fixe'>";

   echo "<tr><th colspan='2'>".$LANG['job'][11]."&nbsp;:&nbsp;";
   if (Session::isMultiEntitiesMode()) {

      echo "&nbsp;(".Dropdown::getDropdownName("glpi_entities", $_SESSION["glpiactive_entity"]).")";
   }
   echo "</th></tr>";

   if ($CFG_GLPI['urgency_mask']!=(1<<3)) {
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['joblist'][29]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Ticket::dropdownUrgency("urgency", $urgency);
      echo "</td></tr>";
   }

   if (NotificationTargetTicket::isAuthorMailingActivatedForHelpdesk()) {
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][8]."&nbsp;:&nbsp;</td>";
      echo "<td>";

      $_REQUEST['value']            = Session::getLoginUserID();
      $_REQUEST['field']            = '_users_id_requester_notif';
      $_REQUEST['use_notification'] = $use_email_notification;
      include (GLPI_ROOT."/ajax/uemailUpdate.php");

      echo "</td></tr>";
   }

   if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0) {
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][24]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Ticket::dropdownMyDevices(Session::getLoginUserID(), $_SESSION["glpiactive_entity"]);
      Ticket::dropdownAllDevices("itemtype", $itemtype, $items_id, 0,
                                 $_SESSION["glpiactive_entity"]);
      echo "</td></tr>";
   }

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['common'][17]."&nbsp;:&nbsp;</td><td>";
   Ticket::dropdownType('type',$type);
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['common'][36]."&nbsp;:&nbsp;</td><td>";
   Dropdown::show('ITILCategory', array('value'     => $itilcategories_id,
                                          'condition' => '`is_helpdeskvisible`=1'));
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['common'][57]."&nbsp;:&nbsp;</td>";
   echo "<td><input type='text' maxlength='250' size='80' name='name' value='$title'></td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['joblist'][6]."&nbsp;:&nbsp;</td>";
   echo "<td><textarea name='content' cols='80' rows='14'>$content</textarea>";
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['document'][2]." (".Document::getMaxUploadSize().")&nbsp;:&nbsp;";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' class='pointer' alt='".
          $LANG['central'][7]."' onclick=\"window.open('".$CFG_GLPI["root_doc"].
          "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";

   echo "&nbsp;";
   Ticket::showDocumentAddButton(60);

   echo "</td>";
   echo "<td><div id='uploadfiles'><input type='file' name='filename[]' value='' size='60'></div>";

   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td colspan='2' class='center'>";
   echo "<input type='submit' value=\"".$LANG['help'][14]."\" class='submit'>";
   echo "</td></tr>";

   echo "</table></div></form>";
}


/**
 * Display the list_limit combo choice
 *
 * @param $action page would be posted when change the value (URL + param)
 * ajax Pager will be displayed if empty
 *
 * @return nothing (print a combo)
**/
function printPagerForm ($action="") {
   global $LANG, $CFG_GLPI;

   if ($action) {
      echo "<form method='POST' action=\"$action\">";
      echo "<span>".$LANG['pager'][4]."&nbsp;</span>";
      echo "<select name='glpilist_limit' onChange='submit()'>";

   } else {
      echo "<form method='POST' action =''>\n";
      echo "<span>".$LANG['pager'][4]."&nbsp;</span>";
      echo "<select name='glpilist_limit' onChange='reloadTab(\"glpilist_limit=\"+this.value)'>";
   }

   if (isset($_SESSION['glpilist_limit'])) {
      $list_limit = $_SESSION['glpilist_limit'];
   } else {
      $list_limit = $CFG_GLPI['list_limit'];
   }

   for ($i=5 ; $i<20 ; $i+=5) {
      echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
   }
   for ($i=20 ; $i<50 ; $i+=10) {
      echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
   }
   for ($i=50 ; $i<250 ; $i+=50) {
      echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
   }
   for ($i=250 ; $i<1000 ; $i+=250) {
      echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
   }
   for ($i=1000 ; $i<5000 ; $i+=1000) {
      echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
   }
   for ($i=5000 ; $i<=10000 ; $i+=5000) {
      echo "<option value='$i' ".(($list_limit==$i)?" selected ":"").">$i</option>";
   }

   echo "<option value='9999999' ".(($list_limit==9999999)?" selected ":"").">9999999</option>";
   echo "</select>";
   echo "<span>&nbsp;".$LANG['pager'][5]."</span>";
   echo "</form>";
}


/**
 * Print pager for search option (first/previous/next/last)
 *
 * @param $start from witch item we start
 * @param $numrows total items
 * @param $target page would be open when click on the option (last,previous etc)
 * @param $parameters parameters would be passed on the URL.
 * @param $item_type_output item type display - if >0 display export PDF et Sylk form
 * @param $item_type_output_param item type parameter for export
 *
 * @return nothing (print a pager)
 *
**/
function printPager($start, $numrows, $target, $parameters, $item_type_output=0,
                    $item_type_output_param=0) {

   global $CFG_GLPI, $LANG;

   $list_limit = $_SESSION['glpilist_limit'];
   // Forward is the next step forward
   $forward = $start+$list_limit;

   // This is the end, my friend
   $end = $numrows-$list_limit;

   // Human readable count starts here

   $current_start = $start+1;

   // And the human is viewing from start to end
   $current_end = $current_start+$list_limit-1;
   if ($current_end>$numrows) {
      $current_end = $numrows;
   }

   // Empty case
   if ($current_end==0) {
      $current_start = 0;
   }

   // Backward browsing
   if ($current_start-$list_limit<=0) {
      $back = 0;
   } else {
      $back = $start-$list_limit;
   }

   // Print it
   echo "<table class='tab_cadre_pager'>";
   echo "<tr>";

   // Back and fast backward button
   if (!$start==0) {
      echo "<th class='left'>";
      echo "<a href='$target?$parameters&amp;start=0'>";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".$LANG['buttons'][33].
            "\" title=\"".$LANG['buttons'][33]."\">";
      echo "</a></th>";
      echo "<th class='left'>";
      echo "<a href='$target?$parameters&amp;start=$back'>";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".$LANG['buttons'][12].
            "\" title=\"".$LANG['buttons'][12]."\">";
      echo "</a></th>";
   }

   // Print the "where am I?"
   echo "<td width='50%' class='tab_bg_2'>";
   printPagerForm("$target?$parameters&amp;start=$start");
   echo "</td>";

   if (!empty($item_type_output)
       && isset($_SESSION["glpiactiveprofile"])
       && $_SESSION["glpiactiveprofile"]["interface"]=="central") {

      echo "<td class='tab_bg_2' width='30%'>";
      echo "<form method='GET' action='".$CFG_GLPI["root_doc"]."/front/report.dynamic.php'
             target='_blank'>";
      echo "<input type='hidden' name='item_type' value='$item_type_output'>";

      if ($item_type_output_param!=0) {
         echo "<input type='hidden' name='item_type_param' value='".
                serialize($item_type_output_param)."'>";
      }
      $split = explode("&amp;",$parameters);

      for ($i=0 ; $i<count($split) ; $i++) {
         $pos    = Toolbox::strpos($split[$i], '=');
         $length = Toolbox::strlen($split[$i]);
         echo "<input type='hidden' name='".Toolbox::substr($split[$i],0,$pos)."' value='".
                urldecode(Toolbox::substr($split[$i], $pos+1))."'>";
      }

      echo "<select name='display_type'>";
      echo "<option value='".PDF_OUTPUT_LANDSCAPE."'>".$LANG['buttons'][27]." ".
             $LANG['common'][68]."</option>";
      echo "<option value='".PDF_OUTPUT_PORTRAIT."'>".$LANG['buttons'][27]." ".
             $LANG['common'][69]."</option>";
      echo "<option value='".SYLK_OUTPUT."'>".$LANG['buttons'][28]."</option>";
      echo "<option value='".CSV_OUTPUT."'>".$LANG['buttons'][44]."</option>";
      echo "<option value='-".PDF_OUTPUT_LANDSCAPE."'>".$LANG['buttons'][29]." ".
             $LANG['common'][68]."</option>";
      echo "<option value='-".PDF_OUTPUT_PORTRAIT."'>".$LANG['buttons'][29]." ".
             $LANG['common'][69]."</option>";
      echo "<option value='-".SYLK_OUTPUT."'>".$LANG['buttons'][30]."</option>";
      echo "<option value='-".CSV_OUTPUT."'>".$LANG['buttons'][45]."</option>";
      echo "</select>&nbsp;";
      echo "<input type='image' name='export'  src='".$CFG_GLPI["root_doc"]."/pics/greenbutton.png'
             title=\"".$LANG['buttons'][31]."\" value=\"".$LANG['buttons'][31]."\">";
      echo "</form>";
      echo "</td>" ;
   }

   echo "<td width='50%' class='tab_bg_2 b'>";
   echo $LANG['pager'][2]."&nbsp;".$current_start."&nbsp;".$LANG['pager'][1]."&nbsp;".$current_end.
        "&nbsp;".$LANG['pager'][3]."&nbsp;".$numrows."&nbsp;";
   echo "</td>\n";

   // Forward and fast forward button
   if ($forward<$numrows) {
      echo "<th class='right'>";
      echo "<a href='$target?$parameters&amp;start=$forward'>";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".$LANG['buttons'][11].
            "\" title=\"".$LANG['buttons'][11]."\">";
      echo "</a></th>\n";

      echo "<th class='right'>";
      echo "<a href='$target?$parameters&amp;start=$end'>";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/last.png' alt=\"".$LANG['buttons'][32].
             "\" title=\"".$LANG['buttons'][32]."\">";
      echo "</a></th>\n";
   }
   // End pager
   echo "</tr></table><br>";
}


/**
 * Print Ajax pager for list in tab panel
 *
 * @param $title displayed above
 * @param $start from witch item we start
 * @param $numrows total items
 *
 * @return nothing (print a pager)
**/
function printAjaxPager($title, $start, $numrows) {
   global $CFG_GLPI, $LANG;

   $list_limit = $_SESSION['glpilist_limit'];
   // Forward is the next step forward
   $forward = $start+$list_limit;

   // This is the end, my friend
   $end = $numrows-$list_limit;

   // Human readable count starts here
   $current_start = $start+1;

   // And the human is viewing from start to end
   $current_end = $current_start+$list_limit-1;
   if ($current_end>$numrows) {
      $current_end = $numrows;
   }
   // Empty case
   if ($current_end==0) {
      $current_start = 0;
   }
   // Backward browsing
   if ($current_start-$list_limit<=0) {
      $back = 0;
   } else {
      $back = $start-$list_limit;
   }

   // Print it
   echo "<table class='tab_cadre_pager'>";
   if ($title) {
      echo "<tr><th colspan='6'>$title</th></tr>";
   }
   echo "<tr>\n";

   // Back and fast backward button
   if (!$start==0) {
      echo "<th class='left'><a href='javascript:reloadTab(\"start=0\");'>
            <img src='".$CFG_GLPI["root_doc"]."/pics/first.png' alt=\"".$LANG['buttons'][33].
             "\" title=\"".$LANG['buttons'][33]."\"></a></th>";
      echo "<th class='left'><a href='javascript:reloadTab(\"start=$back\");'>
            <img src='".$CFG_GLPI["root_doc"]."/pics/left.png' alt=\"".$LANG['buttons'][12].
             "\" title=\"".$LANG['buttons'][12]."\"></th>";
   }

   echo "<td width='50%' class='tab_bg_2'>";
   printPagerForm();
   echo "</td>";

   // Print the "where am I?"
   echo "<td width='50%' class='tab_bg_2 b'>";
   echo $LANG['pager'][2]."&nbsp;".$current_start."&nbsp;".$LANG['pager'][1]."&nbsp;".
        $current_end."&nbsp;".$LANG['pager'][3]."&nbsp;".$numrows."&nbsp;";
   echo "</td>\n";

   // Forward and fast forward button
   if ($forward<$numrows) {
      echo "<th class='right'><a href='javascript:reloadTab(\"start=$forward\");'>
            <img src='".$CFG_GLPI["root_doc"]."/pics/right.png' alt=\"".$LANG['buttons'][11].
             "\" title=\"".$LANG['buttons'][11]."\"></a></th>";
      echo "<th class='right'><a href='javascript:reloadTab(\"start=$end\");'>
            <img src='".$CFG_GLPI["root_doc"]."/pics/last.png' alt=\"".$LANG['buttons'][32].
             "\" title=\"".$LANG['buttons'][32]."\"></th>";
   }

   // End pager
   echo "</tr></table>";
}


/**
 * Show generic date search
 *
 * @param $element name of the html element
 * @param $value default value
 * @param $with_time display with time selection ?
 * @param $with_future display with future date selection ?
 *
 * @return rand value of dropdown
**/
function showGenericDateTimeSearch($element, $value='', $with_time=false, $with_future=false) {
   global $LANG, $CFG_GLPI;

   $rand = mt_rand();

   // Validate value
   if ($value!='NOW'
       && !preg_match("/\d{4}-\d{2}-\d{2}.*/",$value)
       && !strstr($value,'HOUR')
       && !strstr($value,'DAY')
       && !strstr($value,'WEEK')
       && !strstr($value,'MONTH')
       && !strstr($value,'YEAR')) {

      $value = "";
   }

   if (empty($value)) {
      $value = 'NOW';
   }
   $specific_value = date("Y-m-d H:i:s");

   if (preg_match("/\d{4}-\d{2}-\d{2}.*/",$value)) {
      $specific_value = $value;
      $value          = 0;
   }
   echo "<table><tr><td>";
   echo "<select id='genericdate$element$rand' name='_select_$element'>";

   $val = 'NOW';
   echo "<option value='$val' ".($value===$val?'selected':'').">".$LANG['calendar'][16]."</option>";
   echo "<option value='0' ".($value===0?'selected':'').">".$LANG['calendar'][17]."</option>";

   if ($with_time) {
      for ($i=1 ; $i<=24 ; $i++) {
         $val = '-'.$i.'HOUR';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "- $i ".$LANG['gmt'][1]."</option>";
      }
   }

   for ($i=1 ; $i<=7 ; $i++) {
      $val = '-'.$i.'DAY';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][12]."</option>";
   }

   for ($i=1 ; $i<=10 ; $i++) {
      $val = '-'.$i.'WEEK';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][13]."</option>";
   }

   for ($i=1 ; $i<=12 ; $i++) {
      $val = '-'.$i.'MONTH';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][14]."</option>";
   }

   for ($i=1 ; $i<=10 ; $i++) {
      $val = '-'.$i.'YEAR';
      echo "<option value='$val' ".($value===$val?'selected':'').">";
      echo "- $i ".$LANG['calendar'][15]."</option>";
   }

   if ($with_future) {
      if ($with_time) {
         for ($i=1 ; $i<=24 ; $i++) {
            $val = $i.'HOUR';
            echo "<option value='$val' ".($value===$val?'selected':'').">";
            echo "+ $i ".$LANG['gmt'][1]."</option>";
         }
      }

      for ($i=1 ; $i<=7 ; $i++) {
         $val = $i.'DAY';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][12]."</option>";
      }

      for ($i=1 ; $i<=10 ; $i++) {
         $val = $i.'WEEK';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][13]."</option>";
      }

      for ($i=1 ; $i<=12 ; $i++) {
         $val = $i.'MONTH';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][14]."</option>";
      }

      for ($i=1 ; $i<=10 ; $i++) {
         $val = $i.'YEAR';
         echo "<option value='$val' ".($value===$val?'selected':'').">";
         echo "+ $i ".$LANG['calendar'][15]."</option>";
      }
   }

   echo "</select>";

   echo "</td><td>";
   echo "<div id='displaygenericdate$element$rand'></div>";

   $params = array('value'          => '__VALUE__',
                    'name'          => $element,
                    'withtime'      => $with_time,
                    'specificvalue' => $specific_value);

   Ajax::updateItemOnSelectEvent("genericdate$element$rand", "displaygenericdate$element$rand",
                                 $CFG_GLPI["root_doc"]."/ajax/genericdate.php", $params);

   $params['value'] = $value;
   Ajax::updateItem("displaygenericdate$element$rand", $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
                  $params);

   echo "</td></tr></table>";
   return $rand;
}




/**
 *  Create Ajax Tabs apply to 'tabspanel' div. Content is displayed in 'tabcontent'
 *
 * @param $tabdiv_id ID of the div containing the tabs
 * @param $tabdivcontent_id ID of the div containing the content loaded by tabs
 * @param $tabs array of tabs to create : tabs is array( 'key' => array('title'=>'x',url=>'url_toload',params='url_params')...
 * @param $type for active tab
 * @param $size width of tabs panel
 *
 * @return nothing
**/
function createAjaxTabs($tabdiv_id='tabspanel', $tabdivcontent_id='tabcontent', $tabs=array(),
                        $type, $size=950) {
   global $CFG_GLPI;

   $active_tabs = Session::getActiveTab($type);

   if (count($tabs)>0) {
      echo "<script type='text/javascript'>

            var tabpanel = new Ext.TabPanel({
            applyTo: '$tabdiv_id',
            width:$size,
            enableTabScroll: true,
            resizeTabs: false,
            collapsed: true,
            plain: true,
            plugins: [{
                ptype: 'tabscrollermenu',
                maxText  : 50,
                pageSize : 30
            }],
            items: [";
            $first = true;
            $default_tab = $active_tabs;

            if (!isset($tabs[$active_tabs])) {
               $default_tab = key($tabs);
            }

            foreach ($tabs as $key => $val) {
               if ($first) {
                  $first = false;
               } else {
                  echo ",";
               }

               echo "{
                  title: \"".$val['title']."\",
                  id: '$key',";
               if (!empty($key) && $key != 'empty') {
                  echo "autoLoad: {url: '".$val['url']."',
                        scripts: true,
                        nocache: true";
                        if (isset($val['params'])) {
                           echo ", params: '".$val['params']."'";
                        }
                  echo "},";
               }

               echo "  listeners:{ // Force glpi_tab storage
                       beforeshow : function(panel) {
                        /* clean content because append data instead of replace it : no more problem */
                        /* Problem with IE6... But clean data for tabpanel before show. Do it on load default tab ?*/
                        /*tabpanel.body.update('');*/
                        /* update active tab*/
                        Ext.Ajax.request({
                           url : '".$CFG_GLPI['root_doc'].
                                  "/ajax/updatecurrenttab.php?itemtype=$type&glpi_tab=".
                                  urlencode($key)."',
                           success: function(objServerResponse) {
                           //alert(objServerResponse.responseText);
                        }
                        });
                     }
                  }";
               echo "}";
            } // Foreach tabs
         echo "]});";

         echo "/// Define view point";
         echo "tabpanel.expand();";

         echo "// force first load
            function loadDefaultTab() {
               tabpanel.body=Ext.get('$tabdivcontent_id');
               // See before
               tabpanel.body.update('');
               tabpanel.Session::setActiveTab('$default_tab');";
         echo "}";

         echo "// force reload
            function reloadTab(add) {
               var tab = tabpanel.Session::getActiveTab();
               var opt = tab.autoLoad;
               if (add) {
                  if (opt.params)
                     opt.params = opt.params + '&' + add;
                  else
                     opt.params = add;
               }
               tab.getUpdater().update(opt);";
         echo "}";
      echo "</script>";
   }
}


/**
 * Clean Printing of and array in a table
 *
 * @param $tab the array to display
 * @param $pad Pad used
 *
 * @return nothing
**/
function printCleanArray($tab, $pad=0) {

   if (count($tab)) {
      echo "<table class='tab_cadre'>";
      echo "<tr><th>KEY</th><th>=></th><th>VALUE</th></tr>";

      foreach ($tab as $key => $val) {
         echo "<tr class='tab_bg_1'><td class='top right'>";
         echo $key;
         echo "</td><td class='top'>=></td><td class='top tab_bg_1'>";

         if (is_array($val)) {
            printCleanArray($val,$pad+1);
         } else {
            echo $val;
         }
         echo "</td></tr>";
      }
      echo "</table>";
   }
}





?>
