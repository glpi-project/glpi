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
 * Include common HTML headers
 *
 * @param $title title used for the page
 *
 * @return nothing
**/
function includeCommonHtmlHeader($title='') {
   global $CFG_GLPI, $PLUGIN_HOOKS, $LANG;

   // Send UTF8 Headers
   header("Content-Type: text/html; charset=UTF-8");
   // Send extra expires header
   header_nocache();

   // Start the page
   echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\"
          \"http://www.w3.org/TR/html4/loose.dtd\">";
   echo "\n<html><head><title>GLPI - ".$title."</title>";
   echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";

   // Send extra expires header
   echo "<meta http-equiv='Expires' content='Fri, Jun 12 1981 08:20:00 GMT'>\n";
   echo "<meta http-equiv='Pragma' content='no-cache'>\n";
   echo "<meta http-equiv='Cache-Control' content='no-cache'>\n";

   //  CSS link
   echo "<link rel='stylesheet' href='".
          $CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >\n";

   // surcharge CSS hack for IE
   echo "<!--[if lte IE 6]>" ;
   echo "<link rel='stylesheet' href='".
          $CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' media='screen' >\n";
   echo "<![endif]-->";
   echo "<link rel='stylesheet' type='text/css' media='print' href='".
          $CFG_GLPI["root_doc"]."/css/print.css' >\n";
   echo "<link rel='shortcut icon' type='images/x-icon' href='".
          $CFG_GLPI["root_doc"]."/pics/favicon.ico' >\n";

   // AJAX library
   echo "<script type=\"text/javascript\" src='".
          $CFG_GLPI["root_doc"]."/lib/extjs/adapter/ext/ext-base.js'></script>\n";

   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) {
      echo "<script type='text/javascript' src='".
             $CFG_GLPI["root_doc"]."/lib/extjs/ext-all-debug.js'></script>\n";
   } else {
      echo "<script type='text/javascript' src='".
             $CFG_GLPI["root_doc"]."/lib/extjs/ext-all.js'></script>\n";
   }

   echo "<link rel='stylesheet' type='text/css' href='".
          $CFG_GLPI["root_doc"]."/lib/extjs/resources/css/ext-all.css' media='screen' >\n";
   echo "<link rel='stylesheet' type='text/css' href='".
          $CFG_GLPI["root_doc"]."/lib/extrajs/starslider/slider.css' media='screen' >\n";
   echo "<link rel='stylesheet' type='text/css' href='".
          $CFG_GLPI["root_doc"]."/css/tab-scroller-menu.css' media='screen' >\n";


   echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"].
         "/lib/tiny_mce/tiny_mce.js'></script>";

   echo "<link rel='stylesheet' type='text/css' href='".
          $CFG_GLPI["root_doc"]."/css/ext-all-glpi.css' media='screen' >\n";

   if (isset($_SESSION['glpilanguage'])) {
      echo "<script type='text/javascript' src='".
             $CFG_GLPI["root_doc"]."/lib/extjs/locale/ext-lang-".
             $CFG_GLPI["languages"][$_SESSION['glpilanguage']][2].".js'></script>\n";
   }

   // EXTRA EXTJS
   echo "<script type='text/javascript' src='".
          $CFG_GLPI["root_doc"]."/lib/extrajs/xdatefield.js'></script>\n";
   echo "<script type='text/javascript' src='".
          $CFG_GLPI["root_doc"]."/lib/extrajs/TabScrollerMenu.js'></script>\n";
   echo "<script type='text/javascript' src='".
          $CFG_GLPI["root_doc"]."/lib/extrajs/datetime.js'></script>\n";
   echo "<script type='text/javascript' src='".
          $CFG_GLPI["root_doc"]."/lib/extrajs/spancombobox.js'></script>\n";
   echo "<script type='text/javascript' src='".
          $CFG_GLPI["root_doc"]."/lib/extrajs/starslider/slider.js'></script>\n";

   echo "<script type='text/javascript'>\n";
   echo "//<![CDATA[ \n";
   // DO not get it from extjs website
   echo "Ext.BLANK_IMAGE_URL = '".$CFG_GLPI["root_doc"]."/lib/extjs/s.gif';\n";
   echo " Ext.Updater.defaults.loadScripts = true;\n";
   // JMD : validator doesn't accept html in script , must escape html element to validate
   echo "Ext.UpdateManager.defaults.indicatorText='<\span class=\"loading-indicator center\">".
         $LANG['common'][80]."<\/span>';\n";
   echo "//]]> \n";
   echo "</script>\n";

   echo "<!--[if IE]>" ;
   echo "<script type='text/javascript'>\n";
   echo "Ext.UpdateManager.defaults.indicatorText='<\span class=\"loading-indicator-ie\">".
         $LANG['common'][80]."<\/span>';\n";
   echo "</script>\n";
   echo "<![endif]-->";

   // Some Javascript-Functions which we may need later
   echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"]."/script.js'></script>\n";

   // Add specific javascript for plugins
   if (isset($PLUGIN_HOOKS['add_javascript']) && count($PLUGIN_HOOKS['add_javascript'])) {

      foreach ($PLUGIN_HOOKS["add_javascript"] as $plugin => $files) {
         if (is_array($files)) {
            foreach ($files as $file) {
               echo "<script type='text/javascript' src='".
                      $CFG_GLPI["root_doc"]."/plugins/$plugin/$file'></script>\n";
            }
         } else {
            echo "<script type='text/javascript' src='".
                   $CFG_GLPI["root_doc"]."/plugins/$plugin/$files'></script>\n";
         }
      }
   }

   // Add specific css for plugins
   if (isset($PLUGIN_HOOKS['add_css']) && count($PLUGIN_HOOKS['add_css'])) {

      foreach ($PLUGIN_HOOKS["add_css"] as $plugin => $files) {
         if (is_array($files)) {
            foreach ($files as $file) {
               echo "<link rel='stylesheet' href='".
                     $CFG_GLPI["root_doc"]."/plugins/$plugin/$file' type='text/css' media='screen'>\n";
            }
         } else {
            echo "<link rel='stylesheet' href='".
                  $CFG_GLPI["root_doc"]."/plugins/$plugin/$files' type='text/css' media='screen'>\n";
         }
      }
   }

   // End of Head
   echo "</head>\n";
}


/**
 * Common Title Function
 *
 * @param $ref_pic_link Path to the image to display
 * @param $ref_pic_text Alt text of the icon
 * @param $ref_title Title to display
 * @param $ref_btts Extra items to display array(link=>text...)
 *
 * @return nothing
**/
function displayTitle($ref_pic_link="", $ref_pic_text="", $ref_title="", $ref_btts="") {

   echo "<div class='center'><table border='0' class='tab_glpi'><tr>";

   if ($ref_pic_link!="") {
      echo "<td><img src='".$ref_pic_link."' alt=\"".$ref_pic_text."\" title=\"".$ref_pic_text."\"></td>";
   }

   if ($ref_title!="") {
      echo "<td><span class='icon_consol b'>".$ref_title."</span></td>";
   }

   if (is_array($ref_btts) && count($ref_btts)) {
      foreach ($ref_btts as $key => $val) {
         echo "<td><a class='icon_consol_hov' href='".$key."'>".$val."</a></td>";
      }
   }
   echo "</tr></table></div>";
}

/**
 * Display a div containing a message set in session in the previous page
**/
function displayMessageAfterRedirect() {

   // Affichage du message apres redirection
   if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"]) && !empty($_SESSION["MESSAGE_AFTER_REDIRECT"])) {
      echo "<div class='box' style='margin-bottom:20px;'>";
      echo "<div class='box-tleft'><div class='box-tright'><div class='box-tcenter'>";
      echo "</div></div></div>";
      echo "<div class='box-mleft'><div class='box-mright'><div class='box-mcenter'>";
      echo $_SESSION["MESSAGE_AFTER_REDIRECT"];
      echo "</div></div></div>";
      echo "<div class='box-bleft'><div class='box-bright'><div class='box-bcenter'>";
      echo "</div></div></div>";
      echo "</div>";
   }

   // Clean message
   $_SESSION["MESSAGE_AFTER_REDIRECT"] = "";
}



/**
 * Print a simple HTML head with links
 *
 * @param $title title of the page
 * @param $links links to display
**/
function simpleHeader($title, $links=array()) {
   global $CFG_GLPI, $LANG, $HEADER_LOADED;

   // Print a nice HTML-head for help page
   if ($HEADER_LOADED) {
      return;
   }
   $HEADER_LOADED = true;

   includeCommonHtmlHeader($title);

   // Body
   echo "<body>";

   // Main Headline
   echo "<div id='header'>";
   echo "<div id='c_logo'>";
   echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' accesskey='0' title=\"".
          $LANG['central'][5]."\"><span class='invisible'>Logo</span></a></div>";

   // Les préférences + lien déconnexion
   echo "<div id='c_preference'>";
   echo "<div class='sep'></div>";
   echo "</div>";

   //-- Le moteur de recherche --
   echo "<div id='c_recherche'>";
   echo "<div class='sep'></div>";
   echo "</div>";

   //-- Le menu principal --
   echo "<div id='c_menu'>";
   echo "<ul id='menu'>";

   // Build the navigation-elements
   if (count($links)) {
      $i = 1;

      foreach ($links as $name => $link) {
         echo "<li id='menu$i'>";
         echo "<a href='$link' title=\"".$name."\" class='itemP'>".$name."</a>";
         echo "</li>";
         $i++;
      }
   }
   echo "</ul></div>";
   // End navigation bar
   // End headline
   ///Le sous menu contextuel 1
   echo "<div id='c_ssmenu1'></div>";

   //  Le fil d ariane
   echo "<div id='c_ssmenu2'></div>";
   echo "</div>"; // fin header
   echo "<div id='page'>";

   // call static function callcron() every 5min
   CronTask::callCron();
}


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
 * Simple Error message page
 *
 * @param $message string displayed before dying
 * @param $minimal set to true do not display app menu
 *
 * @return nothing as function kill script
**/
function displayErrorAndDie ($message, $minimal=false) {
   global $LANG, $CFG_GLPI, $HEADER_LOADED;

   if (!$HEADER_LOADED) {
      if ($minimal || !isset($_SESSION["glpiactiveprofile"]["interface"])) {
         Html::nullHeader($LANG['login'][5], '');

      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         Html::header($LANG['login'][5], '');

      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         Html::helpHeader($LANG['login'][5], '');
      }
   }
   echo "<div class='center'><br><br>";
   echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='warning'><br><br>";
   echo "<strong>$message</strong></div>";
   Html::nullFooter();
   exit ();
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
 * Display DateTime form with calendar
 *
 * @param $element name of the element
 * @param $value default value to display
 * @param $time_step step for time in minute (-1 use default config)
 * @param $maybeempty may be empty ?
 * @param $can_edit could not modify element
 * @param $minDate minimum allowed date
 * @param $maxDate maximum allowed date
 * @param $minTime minimum allowed time
 * @param $maxTime maximum allowed time
 *
 * @return rand value used
**/
function showDateTimeFormItem($element, $value='', $time_step=-1, $maybeempty=true, $can_edit=true,
                              $minDate='', $maxDate='', $minTime='', $maxTime='') {
   global $CFG_GLPI;

   if ($time_step<0) {
      $time_step = $CFG_GLPI['time_step'];
   }


   $rand = mt_rand();
   echo "<input type='hidden' id='showdate$rand' value=''>";

   $minHour   = 0;
   $maxHour   = 23;
   $minMinute = 0;
   $maxMinute = 59;

   $output     = "";
   $date_value = '';
   $hour_value = '';

   if (!empty($value)) {
      list($date_value, $hour_value) = explode(' ', $value);
   }
   if (!empty($minTime)) {
      list($minHour, $minMinute, $minSec) = explode(':', $minTime);
      $minMinute = 0;

      // Check time in interval
      if (!empty($hour_value) && $hour_value<$minTime) {
         $hour_value = $minTime;
      }
   }

   if (!empty($maxTime)) {
      list($maxHour, $maxMinute, $maxSec) = explode(':', $maxTime);
      $maxMinute = 59;

      // Check time in interval
      if (!empty($hour_value) && $hour_value>$maxTime) {
         $hour_value =$maxTime;
      }
   }

   // reconstruct value to be valid
   if (!empty($date_value)) {
      $value = $date_value.' '.$hour_value;
   }

//    $output .= "<table><tr class='top'><td><div id='date$rand-date'></div></td>";
//    $output .= "<td><select name='_date$rand-hour' id='date$rand-hour'>";
//
//    for ($i=$minHour ; $i<=$maxHour ; $i++) {
//       if ($i<10 && strlen($i)==1) {
//          $i = '0'.$i;
//       }
//       $output .= "<option value='$i'>$i</option>";
//    }
//    $output .= "</select></td>";
//    $output .= "<td><select name='_date$rand-minute' id='date$rand-minute'>";
//
//    for ($i=$minMinute ; $i<=$maxMinute ; $i+=$time_step) {
//       if ($i<10  && strlen($i)==1) {
//          $i = '0'.$i;
//       }
//       $output .= "<option value='$i'>$i</option>";
//    }
//    $output .= "</select></td>";
//    $output .= '</tr></table>';


   $output .= "<script type='text/javascript'>";
   $output .= "Ext.onReady(function() {
      var md$rand = new Ext.ux.form.DateTime({
         hiddenName: '$element'
         ,id: 'date$rand'
         ,value: '$value'
         ,hiddenFormat:'Y-m-d H:i:s'
         ,applyTo: 'showdate$rand'
         ,timeFormat:'H:i'
         ,timeWidth: 55
         ,dateWidth: 90
         ,startDay: 1";

   $empty = "";
   if ($maybeempty) {
      $empty = "allowBlank: true";
   } else {
      $empty = "allowBlank: false";
   }
   $output .= ",$empty";
   $output .= ",timeConfig: {
      altFormats:'H:i:s',increment: $time_step,$empty";

   if (!empty($minTime) && $minTime!='00:00:00') {
      $output .= ",minValue: '".$minTime."'";
   }
   if (!empty($maxTime) && $maxTime!='24:00:00') {
      $output .= ",maxValue: '".$maxTime."'";
   }

   $output .= "}";

   switch ($_SESSION['glpidate_format']) {
      case 1 :
         $output .= ",dateFormat: 'd-m-Y',dateConfig: {
            altFormats:'d-m-Y|d-n-Y',$empty";
         break;

      case 2 :
         $output .= ",dateFormat: 'm-d-Y',dateConfig: {
            altFormats:'m-d-Y|n-d-Y',$empty";
         break;

      default :
         $output .= ",dateFormat: 'Y-m-d',dateConfig: {
            altFormats:'Y-m-d|Y-n-d',$empty";
   }

   if (!empty($minDate)) {
      $output .= ",minValue: '".Html::convDate($minDate)."'";
   }
   if (!empty($maxDate)) {
      $output .= ",maxValue: '".Html::convDate($maxDate)."'";
   }
   $output .= "}";

   if (!$can_edit) {
      $output .= ",disabled: true";
   }
   $output .= " });
   });";
   $output .= "</script>\n";

   echo $output;
   return $rand;
}


/**
 * Display Date form with calendar
 *
 * @param $element name of the element
 * @param $value default value to display
 * @param $maybeempty may be empty ?
 * @param $can_edit could not modify element
 * @param $minDate minimum allowed date
 * @param $maxDate maximum allowed date
 * @param $displayYear should we set/diplay the year?
 *
 * @return rand value used
**/
function showDateFormItem($element, $value='', $maybeempty=true, $can_edit=true, $minDate='',
                          $maxDate='', $displayYear=true) {
   global $CFG_GLPI;

   $rand = mt_rand();
   echo "<input id='showdate$rand' type='text' size='10' name='$element'>";

   $output  = "<script type='text/javascript'>\n";
   $output .= "Ext.onReady(function() {
      var md$rand = new Ext.ux.form.XDateField({
         name: '$element'
         ,value: '".Html::convDate($value)."'
         ,applyTo: 'showdate$rand'
         ,id: 'date$rand'
         ,submitFormat:'Y-m-d'
         ,startDay: 1";

   switch ($_SESSION['glpidate_format']) {
      case 1 :
         $displayYear ? $format='d-m-Y' : $format='d-m';
         break;

      case 2 :
         $displayYear ? $format='m-d-Y' : $format='m-d';
         break;

      default :
         $displayYear ? $format='Y-m-d' : $format='m-d';
   }
   $output .= ",format: '".$format."'";

   if ($maybeempty) {
      $output .= ",allowBlank: true";
   } else {
      $output .= ",allowBlank: false";
   }

   if (!$can_edit) {
      $output .= ",disabled: true";
   }

   if (!empty($minDate)) {
      $output .= ",minValue: '".Html::convDate($minDate)."'";
   }

   if (!empty($maxDate)) {
      $output .= ",maxValue: '".Html::convDate($maxDate)."'";
   }

   $output .= " });
   });";
   $output .= "</script>\n";
   echo $output;
   return $rand;
}



/**
 *  Force active Tab for an itemtype
 *
 * @param $itemtype :item type
 * @param $tab : ID of the tab
**/
function setActiveTab($itemtype, $tab) {
   $_SESSION['glpi_tabs'][strtolower($itemtype)] = $tab;
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
               tabpanel.setActiveTab('$default_tab');";
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
 * Set page not to use the cache
**/
function header_nocache() {

   header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
   header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe
}


/**
 * Flush the current displayed items (do not works really fine)
**/
function glpi_flush() {

   flush();
   if (function_exists("ob_flush") && ob_get_length () !== FALSE) {
      ob_flush();
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


/**
 * Display a Link to the last page using http_referer if available else use history.back
**/
function displayBackLink() {
   global $LANG;

   if (isset($_SERVER['HTTP_REFERER'])) {
      echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$LANG['buttons'][13]."</a>";
   } else {
      echo "<a href='javascript:history.back();'>".$LANG['buttons'][13]."</a>";
   }
}


/**
 * Print the form used to select profile if several are available
 *
 * @param $target target of the form
 *
 * @return nothing
**/
function showProfileSelecter($target) {
   global $CFG_GLPI, $LANG;

   if (count($_SESSION["glpiprofiles"])>1) {
      echo '<li><form name="form" method="post" action="'.$target.'">';
      echo '<select name="newprofile" onChange="submit()">';

      foreach ($_SESSION["glpiprofiles"] as $key => $val) {
         echo '<option value="'.$key.'" '.($_SESSION["glpiactiveprofile"]["id"]==$key?'selected':'').
               '>'.$val['name'].'</option>';
      }
      echo '</select></form></li>';
   }

   if (Session::isMultiEntitiesMode()) {
      echo "<li>";

      echo "<script type='text/javascript'>";
      echo "cleanhide('modal_entity_content');";
      echo "var entity_window=new Ext.Window({
         layout:'fit',
         width:800,
         height:400,
         closeAction:'hide',
         modal: true,
         autoScroll: true,
         title: \"".$LANG['entity'][10]."\",
         autoLoad: '".$CFG_GLPI['root_doc']."/ajax/entitytree.php?target=$target'
      });";
      echo "</script>";

      echo "<a onclick='entity_window.show();' href='#modal_entity_content' title=\"".
             htmlentities($_SESSION["glpiactive_entity_name"]).
             "\" class='entity_select' id='global_entity_select'>".
             $_SESSION["glpiactive_entity_shortname"]."</a>";

      echo "</li>";
   }
}




/**
 * show arrow for massives actions : opening
 *
 * @param $formname string
 * @param $fixed boolean - used tab_cadre_fixe in both tables
 * @param $width only for dictionnary
**/
function openArrowMassive($formname, $fixed=false, $width='80%') {
   global $CFG_GLPI, $LANG;

   if ($fixed) {
      echo "<table class='tab_glpi' width='950px'>";
   } else {
      echo "<table class='tab_glpi' width='80%'>";
   }

   echo "<tr><td><img src='".$CFG_GLPI["root_doc"]."/pics/arrow-left.png' alt=''></td>";
   echo "<td class='center'>";
   echo "<a onclick= \"if ( markCheckboxes('$formname') ) return false;\"
          href='#'>".$LANG['buttons'][18]."</a></td>";
   echo "<td>/</td><td class='center'>";
   echo "<a onclick= \"if ( unMarkCheckboxes('$formname') ) return false;\"
          href='#'>".$LANG['buttons'][19]."</a></td>";
   echo "<td class='left' width='".$width."'>";
}
/**
 * DO NOT USE THIS FUNCTION use closeArrowMassives
 * show arrow for massives actions : closing
 *
 * @param $name string name of submit button, none if empty
 * @param $label string label of submit button
**/
function closeArrowMassive($name='', $label='') {

   closeArrowMassives(array($name => $label));

}

/**
 * show arrow for massives actions : closing
 *
 * @param $actions array of action : $name -> $label
**/
function closeArrowMassives($actions) {

   if (count($actions)) {
      foreach($actions as $name => $label) {
         if (!empty($name)) {
            echo "<input type='submit' name='$name' value=\"$label\" class='submit'>&nbsp;";
         }
      }
   }
   echo "</td></tr>";
   echo "</table>";
}


/**
 * Show div with auto completion
 *
 * @param $item item object used for create dropdown
 * @param $field field to search for autocompletion
 * @param $options possible options
 * Parameters which could be used in options array :
 *    - name : string / name of the select (default is field parameter)
 *    - value : integer / preselected value (default value of the item object)
 *    - size : integer / size of the text field
 *    - entity : integer / restrict to a defined entity (default entity of the object if define)
 *              set to -1 not to take into account
 *    - user : integer / restrict to a defined user (default -1 : no restriction)
 *    - option : string / options to add to text field
 *
 * @return nothing (print out an HTML div)
**/
function autocompletionTextField(CommonDBTM $item, $field, $options=array()) {
   global $CFG_GLPI;

   $params['name']   = $field;
   $params['value']  = '';

   if (array_key_exists($field,$item->fields)) {
      $params['value'] = $item->fields[$field];
   }
   $params['size']   = 40;
   $params['entity'] = -1;

   if (array_key_exists('entities_id',$item->fields)) {
      $params['entity'] = $item->fields['entities_id'];
   }
   $params['user']   = -1;
   $params['option'] = '';

   if (is_array($options) && count($options)) {
      foreach ($options as $key => $val) {
         $params[$key] = $val;
      }
   }

   if ($CFG_GLPI["use_ajax"] && $CFG_GLPI["use_ajax_autocompletion"]) {
      $rand = mt_rand();
      $name = "field_".$params['name'].$rand;
      echo "<input ".$params['option']." id='text$name' type='text' name='".$params['name'].
            "' value=\"".Html::cleanInputText($params['value'])."\" size='".$params['size']."'>\n";
      $output = "<script type='text/javascript' >\n";

      $output .= "var text$name = new Ext.data.Store({
         proxy: new Ext.data.HttpProxy(
         new Ext.data.Connection ({
            url: '".$CFG_GLPI["root_doc"]."/ajax/autocompletion.php',
            extraParams : {
               itemtype: '".$item->getType()."',
               field: '$field'";

            if ($params['entity']>=0) {
               $output .= ",entity_restrict: ".$params['entity'];
            }
            if ($params['user']>=0) {
               $output .= ",user_restrict: ".$params['user'];
            }
            $output .= "
            },
            method: 'POST'
            })
         ),
         reader: new Ext.data.JsonReader({
            totalProperty: 'totalCount',
            root: 'items',
            id: 'value'
         }, [
         {name: 'value', mapping: 'value'}
         ])
      });
      ";

      $output .= "var search$name = new Ext.ux.form.SpanComboBox({
         store: text$name,
         displayField:'value',
         pageSize:20,
         hideTrigger:true,
         minChars:3,
         resizable:true,
         width: ".($params['size']*7).",
         minListWidth:".($params['size']*5).", // IE problem : wrong computation of the width of the ComboBox field
         applyTo: 'text$name'
      });";

      $output .= "</script>";

      echo $output;

   } else {
      echo "<input ".$params['option']." type='text' name='".$params['name']."'
             value=\"".Html::cleanInputText($params['value'])."\" size='".$params['size']."'>\n";
   }
}


/**
 * Show a tooltip on an item
 *
 * @param $content string data to put in the tooltip
 * @param $options array possible options
 * Parameters which could be used in options array :
 *   - applyto : string / id of the item to apply tooltip (default empty).
 *                  If not set display an icon
 *   - title : string / title to display (default empty)
 *   - contentid : string / id for the content html container (default auto generated) (used for ajax)
 *   - link : string / link to put on displayed image if contentid is empty
 *   - linkid : string / html id to put to the link link (used for ajax)
 *   - linktarget : string / target for the link
 *   - popup : string / popup action : link not needed to use it
 *   - img : string / url of a specific img to use
 *   - display : boolean / display the item : false return the datas
 *   - autoclose : boolean / autoclose the item : default true (false permit to scroll)
 *
 * @return nothing (print out an HTML div)
**/
function showToolTip($content, $options=array()) {
   global $CFG_GLPI;

   $param['applyto']    = '';
   $param['title']      = '';
   $param['contentid']  = '';
   $param['link']       = '';
   $param['linkid']     = '';
   $param['linktarget'] = '';
   $param['img']        = $CFG_GLPI["root_doc"]."/pics/aide.png";
   $param['popup']      = '';
   $param['ajax']       = '';
   $param['display']    = true;
   $param['autoclose']  = true;

   if (is_array($options) && count($options)) {
      foreach ($options as $key => $val) {
         $param[$key] = $val;
      }
   }

   // No empty content to have a clean display
   if (empty($content)) {
      $content = "&nbsp;";
   }
   $rand = mt_rand();
   $out  = '';

   // Force link for popup
   if (!empty($param['popup'])) {
      $param['link'] = '#';
   }

   if (empty($param['applyto'])) {
      if (!empty($param['link'])) {
         $out .= "<a id='".(!empty($param['linkid'])?$param['linkid']:"tooltiplink$rand")."'";

         if (!empty($param['linktarget'])) {
            $out .= " target='".$param['linktarget']."' ";
         }
         $out .= " href='".$param['link']."'";

         if (!empty($param['popup'])) {
            $out .= " onClick=\"var w=window.open('".$CFG_GLPI["root_doc"]."/front/popup.php?popup=".
                                                  $param['popup']."', 'glpibookmarks', 'height=400, ".
                                                  "width=600, top=100, left=100, scrollbars=yes' ); ".
                    "w.focus();\" ";
         }
         $out .= '>';
      }
      $out .= "<img id='tooltip$rand' alt='' src='".$param['img']."'>";

      if (!empty($param['link'])) {
         $out .= "</a>";
      }
      $param['applyto'] = "tooltip$rand";
   }

   if (empty($param['contentid'])) {
      $param['contentid'] = "content".$param['applyto'];
   }

   $out .= "<span id='".$param['contentid']."' class='x-hidden'>$content</span>";

   $out .= "<script type='text/javascript' >\n";

   $out .= "new Ext.ToolTip({
            target: '".$param['applyto']."',
            anchor: 'left',
            autoShow: true,
            ";

   if ($param['autoclose']) {
      $out .= "autoHide: true,

               dismissDelay: 0";
   } else {
      $out .= "autoHide: false,
               closable: true,
               autoScroll: true";
   }

   if (!empty($param['title'])) {
      $out .= ",title: \"".$param['title']."\"";
   }
   $out .= ",contentEl: '".$param['contentid']."'";
   $out .= "});";
   $out .= "</script>";

   if ($param['display']) {
      echo $out;
   } else {
      return $out;
   }
}



/**
 * Init the Editor System to a textarea
 *
 * @param $name name of the html textarea where to used
 *
 * @return nothing
**/
function initEditorSystem($name) {
   global $CFG_GLPI;

   echo "<script language='javascript' type='text/javascript'>";
   echo "tinyMCE.init({
      language : '".$CFG_GLPI["languages"][$_SESSION['glpilanguage']][3]."',
      mode : 'exact',
      elements: '$name',
      plugins : 'table,directionality,searchreplace',
      theme : 'advanced',
      entity_encoding : 'numeric', ";
      // directionality + search replace plugin
   echo "theme_advanced_buttons1_add : 'ltr,rtl,search,replace',";
   echo "theme_advanced_toolbar_location : 'top',
      theme_advanced_toolbar_align : 'left',
      theme_advanced_buttons1 : 'bold,italic,underline,strikethrough,fontsizeselect,formatselect,separator,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,outdent,indent',
      theme_advanced_buttons2 : 'forecolor,backcolor,separator,hr,separator,link,unlink,anchor,separator,tablecontrols,undo,redo,cleanup,code,separator',
      theme_advanced_buttons3 : ''});";
   echo "</script>";
}

?>
