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

      foreach ($PLUGIN_HOOKS["add_javascript"] as $plugin => $file) {
         echo "<script type='text/javascript' src='".$CFG_GLPI["root_doc"]."/plugins/$plugin/$file'>
               </script>\n";
      }
   }

   // Add specific css for plugins
   if (isset($PLUGIN_HOOKS['add_css']) && count($PLUGIN_HOOKS['add_css'])) {

      foreach ($PLUGIN_HOOKS["add_css"] as $plugin => $file) {
         echo "<link rel='stylesheet' href='".
                $CFG_GLPI["root_doc"]."/plugins/$plugin/$file' type='text/css' media='screen' >\n";
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
 * Print a nice HTML head for every page
 *
 * @param $title title of the page
 * @param $url not used anymore.
 * @param $sector sector in which the page displayed is
 * @param $item item corresponding to the page displayed
 * @param $option option corresponding to the page displayed
 *
**/
function commonHeader($title, $url='', $sector="none", $item="none", $option="") {
   global $CFG_GLPI, $LANG, $PLUGIN_HOOKS, $HEADER_LOADED, $DB;

   // Print a nice HTML-head for every page
   if ($HEADER_LOADED) {
      return;
   }
   $HEADER_LOADED = true;

   includeCommonHtmlHeader($title);
   // Body
   echo "<body>";
   // Generate array for menu and check right


   // INVENTORY
   $showstate = false;
   $menu['inventory']['title'] = $LANG['Menu'][38];

   if (haveRight("computer","r")) {
      $menu['inventory']['default'] = '/front/computer.php';

      $menu['inventory']['content']['computer']['title']           = $LANG['Menu'][0];
      $menu['inventory']['content']['computer']['shortcut']        = 'c';
      $menu['inventory']['content']['computer']['page']            = '/front/computer.php';
      $menu['inventory']['content']['computer']['links']['search'] = '/front/computer.php';

      if (haveRight("computer","w")) {
         $menu['inventory']['content']['computer']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Computer&amp;add=1';
         $menu['inventory']['content']['computer']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=Computer&amp;add=0';
      }
      $showstate = true;
   }


   if (haveRight("monitor","r")) {
      $menu['inventory']['content']['monitor']['title']           = $LANG['Menu'][3];
      $menu['inventory']['content']['monitor']['shortcut']        = 'm';
      $menu['inventory']['content']['monitor']['page']            = '/front/monitor.php';
      $menu['inventory']['content']['monitor']['links']['search'] = '/front/monitor.php';

      if (haveRight("monitor","w")) {
         $menu['inventory']['content']['monitor']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Monitor&amp;add=1';
         $menu['inventory']['content']['monitor']['links']['template']
                            = '/front/setup.templates.php?'.'itemtype=Monitor&amp;add=0';
      }
      $showstate = true;
   }


   if (haveRight("software","r")) {
      $menu['inventory']['content']['software']['title']           = $LANG['Menu'][4];
      $menu['inventory']['content']['software']['shortcut']        = 's';
      $menu['inventory']['content']['software']['page']            = '/front/software.php';
      $menu['inventory']['content']['software']['links']['search'] = '/front/software.php';

      if (haveRight("software","w")) {
         $menu['inventory']['content']['software']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Software&amp;add=1';
         $menu['inventory']['content']['software']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=Software&amp;add=0';
      }
      $showstate = true;
   }


   if (haveRight("networking","r")) {
      $menu['inventory']['content']['networking']['title']           = $LANG['Menu'][1];
      $menu['inventory']['content']['networking']['shortcut']        = 'n';
      $menu['inventory']['content']['networking']['page']            = '/front/networkequipment.php';
      $menu['inventory']['content']['networking']['links']['search'] = '/front/networkequipment.php';

      if (haveRight("networking","w")) {
         $menu['inventory']['content']['networking']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=NetworkEquipment&amp;add=1';
         $menu['inventory']['content']['networking']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=NetworkEquipment&amp;add=0';
      }
      $showstate = true;
   }


   if (haveRight("peripheral","r")) {
      $menu['inventory']['content']['peripheral']['title']           = $LANG['Menu'][16];
      $menu['inventory']['content']['peripheral']['shortcut']        = 'n';
      $menu['inventory']['content']['peripheral']['page']            = '/front/peripheral.php';
      $menu['inventory']['content']['peripheral']['links']['search'] = '/front/peripheral.php';

      if (haveRight("peripheral","w")) {
         $menu['inventory']['content']['peripheral']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Peripheral&amp;add=1';
         $menu['inventory']['content']['peripheral']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=Peripheral&amp;add=0';
      }
      $showstate = true;
   }


   if (haveRight("printer","r")) {
      $menu['inventory']['content']['printer']['title']           = $LANG['Menu'][2];
      $menu['inventory']['content']['printer']['shortcut']        = 'p';
      $menu['inventory']['content']['printer']['page']            = '/front/printer.php';
      $menu['inventory']['content']['printer']['links']['search'] = '/front/printer.php';

      if (haveRight("printer","w")) {
         $menu['inventory']['content']['printer']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Printer&amp;add=1';
         $menu['inventory']['content']['printer']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=Printer&amp;add=0';
      }
      $showstate = true;
   }


   if (haveRight("cartridge","r")) {
      $menu['inventory']['content']['cartridge']['title']           = $LANG['Menu'][21];
      $menu['inventory']['content']['cartridge']['shortcut']        = 'c';
      $menu['inventory']['content']['cartridge']['page']            = '/front/cartridgeitem.php';
      $menu['inventory']['content']['cartridge']['links']['search'] = '/front/cartridgeitem.php';

      if (haveRight("cartridge","w")) {
         $menu['inventory']['content']['cartridge']['links']['add'] = '/front/cartridgeitem.form.php';
      }
   }


   if (haveRight("consumable","r")) {
      $menu['inventory']['content']['consumable']['title']           = $LANG['Menu'][32];
      $menu['inventory']['content']['consumable']['shortcut']        = 'g';
      $menu['inventory']['content']['consumable']['page']            = '/front/consumableitem.php';
      $menu['inventory']['content']['consumable']['links']['search'] = '/front/consumableitem.php';

      if (haveRight("consumable","w")) {
         $menu['inventory']['content']['consumable']['links']['add']
                                                               = '/front/consumableitem.form.php';
      }

      $menu['inventory']['content']['consumable']['links']['summary']
                                                   = '/front/consumableitem.php?'.'synthese=yes';
   }


   if (haveRight("phone","r")) {
      $menu['inventory']['content']['phone']['title']           = $LANG['Menu'][34];
      $menu['inventory']['content']['phone']['shortcut']        = 't';
      $menu['inventory']['content']['phone']['page']            = '/front/phone.php';
      $menu['inventory']['content']['phone']['links']['search'] = '/front/phone.php';

      if (haveRight("phone","w")) {
         $menu['inventory']['content']['phone']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Phone&amp;add=1';
         $menu['inventory']['content']['phone']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=Phone&amp;add=0';
      }
      $showstate = true;
   }


   if ($showstate) {
      $menu['inventory']['content']['state']['title']            = $LANG['Menu'][28];
      $menu['inventory']['content']['state']['shortcut']         = 'n';
      $menu['inventory']['content']['state']['page']             = '/front/states.php';
      $menu['inventory']['content']['state']['links']['search']  = '/front/states.php';
      $menu['inventory']['content']['state']['links']['summary'] = '/front/states.php?synthese=yes';
   }



   // ASSISTANCE
   $menu['maintain']['title'] = $LANG['title'][24];

   if (haveRight("observe_ticket","1")
       || haveRight("show_all_ticket","1")
       || haveRight("create_ticket","1")) {

      $menu['maintain']['default'] = '/front/ticket.php';

      $menu['maintain']['content']['ticket']['title']           = $LANG['Menu'][5];
      $menu['maintain']['content']['ticket']['shortcut']        = 't';
      $menu['maintain']['content']['ticket']['page']            = '/front/ticket.php';
      $menu['maintain']['content']['ticket']['links']['search'] = '/front/ticket.php';
      $menu['maintain']['content']['ticket']['links']['search'] = '/front/ticket.php';

      if (haveRight('validate_ticket',1)) {
         $opt = array();
         $opt['reset']         = 'reset';
         $opt['field'][0]      = 55; // validation status
         $opt['searchtype'][0] = 'equals';
         $opt['contains'][0]   = 'waiting';
         $opt['link'][0]       = 'AND';

         $opt['field'][1]      = 59; // validation aprobator
         $opt['searchtype'][1] = 'equals';
         $opt['contains'][1]   = getLoginUserID();
         $opt['link'][1]       = 'AND';


         $pic_validate = "<img title=\"".$LANG['validation'][15]."\" alt=\"".$LANG['validation'][15].
                           "\" src='".$CFG_GLPI["root_doc"]."/pics/menu_showall.png'>";

         $menu['maintain']['content']['ticket']['links'][$pic_validate]
                           = '/front/ticket.php?'.append_params($opt, '&amp;');
      }
   }

   if (haveRight("create_ticket","1")) {
      $menu['maintain']['content']['ticket']['links']['add'] = '/front/ticket.form.php';
   }

   if (haveRight("show_planning","1") || haveRight("show_all_planning","1")) {
      $menu['maintain']['content']['planning']['title']           = $LANG['Menu'][29];
      $menu['maintain']['content']['planning']['shortcut']        = 'l';
      $menu['maintain']['content']['planning']['page']            = '/front/planning.php';
      $menu['maintain']['content']['planning']['links']['search'] = '/front/planning.php';
   }

   if (haveRight("statistic","1")) {
      $menu['maintain']['content']['stat']['title']    = $LANG['Menu'][13];
      $menu['maintain']['content']['stat']['shortcut'] = '1';
      $menu['maintain']['content']['stat']['page']     = '/front/stat.php';
   }



   // FINANCIAL
   if (haveRight("budget","r")) {
      $menu['financial']['content']['budget']['title']           = $LANG['financial'][110];
      $menu['financial']['content']['budget']['shortcut']        = 'n';
      $menu['financial']['content']['budget']['page']            = '/front/budget.php';
      $menu['financial']['content']['budget']['links']['search'] = '/front/budget.php';

      if (haveRight("budget","w")) {
         $menu['financial']['content']['budget']['links']['add']
                           = '/front/setup.templates.php?'.'itemtype=Budget&amp;add=1';
         $menu['financial']['content']['budget']['links']['template']
                           = '/front/setup.templates.php?'.'itemtype=Budget&amp;add=0';
      }
   }

   $menu['financial']['title'] = $LANG['Menu'][26];

   if (haveRight("contact_enterprise","r")) {
      $menu['financial']['content']['supplier']['title']           = $LANG['Menu'][23];
      $menu['financial']['content']['supplier']['shortcut']        = 'e';
      $menu['financial']['content']['supplier']['page']            = '/front/supplier.php';
      $menu['financial']['content']['supplier']['links']['search'] = '/front/supplier.php';

      $menu['financial']['default'] = '/front/contact.php';

      $menu['financial']['content']['contact']['title']           = $LANG['Menu'][22];
      $menu['financial']['content']['contact']['shortcut']        = 't';
      $menu['financial']['content']['contact']['page']            = '/front/contact.php';
      $menu['financial']['content']['contact']['links']['search'] = '/front/contact.php';

      if (haveRight("contact_enterprise","w")) {
         $menu['financial']['content']['contact']['links']['add']  = '/front/contact.form.php';
         $menu['financial']['content']['supplier']['links']['add'] = '/front/supplier.form.php';
      }
   }


   if (haveRight("contract","r")) {
      $menu['financial']['content']['contract']['title']           = $LANG['Menu'][25];
      $menu['financial']['content']['contract']['shortcut']        = 'n';
      $menu['financial']['content']['contract']['page']            = '/front/contract.php';
      $menu['financial']['content']['contract']['links']['search'] = '/front/contract.php';

      if (haveRight("contract","w")) {
         $menu['financial']['content']['contract']['links']['add'] = '/front/contract.form.php';
      }
   }


   if (haveRight("document","r")) {
      $menu['financial']['content']['document']['title']           = $LANG['Menu'][27];
      $menu['financial']['content']['document']['shortcut']        = 'd';
      $menu['financial']['content']['document']['page']            = '/front/document.php';
      $menu['financial']['content']['document']['links']['search'] = '/front/document.php';

      if (haveRight("document","w")) {
         $menu['financial']['content']['document']['links']['add'] = '/front/document.form.php';
      }
   }



   // UTILS
   $menu['utils']['title'] = $LANG['Menu'][18];

   $menu['utils']['default'] = '/front/reminder.php';

   $menu['utils']['content']['reminder']['title']           = $LANG['title'][37];
   $menu['utils']['content']['reminder']['page']            = '/front/reminder.php';
   $menu['utils']['content']['reminder']['links']['search'] = '/front/reminder.php';
   $menu['utils']['content']['reminder']['links']['add']    = '/front/reminder.form.php';

   if (haveRight("knowbase","r") || haveRight("faq","r")) {
      $menu['utils']['content']['knowbase']['title']           = $LANG['Menu'][19];
      $menu['utils']['content']['knowbase']['page']            = '/front/knowbaseitem.php';
      $menu['utils']['content']['knowbase']['links']['search'] = '/front/knowbaseitem.php';

      if (haveRight("knowbase","w") || haveRight("faq","w")) {
         $menu['utils']['content']['knowbase']['links']['add']
                                                         = '/front/knowbaseitem.form.php?id=new';
      }
   }


   if (haveRight("reservation_helpdesk","1") || haveRight("reservation_central","r")) {
      $menu['utils']['content']['reservation']['title']            = $LANG['Menu'][17];
      $menu['utils']['content']['reservation']['page']             = '/front/reservationitem.php';
      $menu['utils']['content']['reservation']['links']['search']  = '/front/reservationitem.php';
      $menu['utils']['content']['reservation']['links']['showall'] = '/front/reservation.php';
   }


   if (haveRight("reports","r")) {
      $menu['utils']['content']['report']['title'] = $LANG['Menu'][6];
      $menu['utils']['content']['report']['page']  = '/front/report.php';
   }


   if ($CFG_GLPI["use_ocs_mode"] && haveRight("ocsng","w")) {
      $menu['utils']['content']['ocsng']['title']                      = $LANG['Menu'][33];
      $menu['utils']['content']['ocsng']['page']                       = '/front/ocsng.php';

      $menu['utils']['content']['ocsng']['options']['import']['title'] = $LANG['ocsng'][2];
      $menu['utils']['content']['ocsng']['options']['import']['page']  = '/front/ocsng.import.php';

      $menu['utils']['content']['ocsng']['options']['sync']['title']   = $LANG['ocsng'][1];
      $menu['utils']['content']['ocsng']['options']['sync']['page']    = '/front/ocsng.sync.php';

      $menu['utils']['content']['ocsng']['options']['clean']['title']  = $LANG['ocsng'][3];
      $menu['utils']['content']['ocsng']['options']['clean']['page']   = '/front/ocsng.clean.php';

      $menu['utils']['content']['ocsng']['options']['link']['title']   = $LANG['ocsng'][4];
      $menu['utils']['content']['ocsng']['options']['link']['page']    = '/front/ocsng.link.php';

   }



   // PLUGINS
   if (isset($PLUGIN_HOOKS["menu_entry"]) && count($PLUGIN_HOOKS["menu_entry"])) {
      $menu['plugins']['title'] = $LANG['common'][29];
      $plugins = array();

      foreach  ($PLUGIN_HOOKS["menu_entry"] as $plugin => $active) {
         if ($active) { // true or a string
            $function = "plugin_version_$plugin";

            if (function_exists($function)) {
               $plugins[$plugin] = $function();
            }
         }
      }

      if (count($plugins)) {
         $list = array();

         foreach ($plugins as $key => $val) {
            $list[$key] = $val["name"];
         }
         asort($list);

         foreach ($list as $key => $val) {
            $menu['plugins']['content'][$key]['title'] = $val;
            $menu['plugins']['content'][$key]['page']  = '/plugins/'.$key.'/';

            if (is_string($PLUGIN_HOOKS["menu_entry"][$key])) {
               $menu['plugins']['content'][$key]['page'] .= $PLUGIN_HOOKS["menu_entry"][$key];
            }

            // Set default link for plugins
            if (!isset($menu['plugins']['default'])) {
               $menu['plugins']['default'] = $menu['plugins']['content'][$key]['page'];
            }

            if ($sector=="plugins" && $item==$key) {
               if (isset($PLUGIN_HOOKS["submenu_entry"][$key])
                   && is_array($PLUGIN_HOOKS["submenu_entry"][$key])) {

                  foreach ($PLUGIN_HOOKS["submenu_entry"][$key] as $name => $link) {
                     // New complete option management
                     if ($name=="options") {
                        $menu['plugins']['content'][$key]['options'] = $link;
                     } else { // Keep it for compatibility

                        if (is_array($link)) {
                           // Simple link option
                           if (isset($link[$option])) {
                              $menu['plugins']['content'][$key]['links'][$name]
                                             ='/plugins/'.$key.'/'.$link[$option];
                           }
                        } else {
                           $menu['plugins']['content'][$key]['links'][$name]
                                             ='/plugins/'.$key.'/'.$link;
                        }
                     }
                  }
               }
            }
         }
      }
   }


   /// ADMINISTRATION
   $menu['admin']['title'] = $LANG['Menu'][15];

   if (haveRight("user","r")) {
      $menu['admin']['default'] = '/front/user.php';

      $menu['admin']['content']['user']['title']           = $LANG['Menu'][14];
      $menu['admin']['content']['user']['shortcut']        = 'u';
      $menu['admin']['content']['user']['page']            = '/front/user.php';
      $menu['admin']['content']['user']['links']['search'] = '/front/user.php';

      if (haveRight("user","w")) {
         $menu['admin']['content']['user']['links']['add'] = "/front/user.form.php";
      }

     $menu['admin']['content']['user']['options']['ldap']['title'] = $LANG['login'][2];
     $menu['admin']['content']['user']['options']['ldap']['page']  = "/front/ldap.php";
   }


   if (haveRight("group","r")) {
      $menu['admin']['content']['group']['title']           = $LANG['Menu'][36];
      $menu['admin']['content']['group']['shortcut']        = 'g';
      $menu['admin']['content']['group']['page']            = '/front/group.php';
      $menu['admin']['content']['group']['links']['search'] = '/front/group.php';

      if (haveRight("group","w")) {
         $menu['admin']['content']['group']['links']['add']             = "/front/group.form.php";
         $menu['admin']['content']['group']['options']['ldap']['title'] = $LANG['login'][2];
         $menu['admin']['content']['group']['options']['ldap']['page']  = "/front/ldap.group.php";
      }
   }


   if (haveRight("entity","r")) {
      $menu['admin']['content']['entity']['title']           = $LANG['Menu'][37];
      $menu['admin']['content']['entity']['shortcut']        = 'z';
      $menu['admin']['content']['entity']['page']            = '/front/entity.php';
      $menu['admin']['content']['entity']['links']['search'] = '/front/entity.php';
      $menu['admin']['content']['entity']['links']['add']    = "/front/entity.form.php";
   }


   if (haveRight("rule_ldap","r")
       || haveRight("rule_ocs","r")
       || haveRight("entity_rule_ticket","r")
       || haveRight("rule_softwarecategories","r")
       || haveRight("rule_mailcollector","r")) {

      $menu['admin']['content']['rule']['title']    = $LANG['rulesengine'][17];
      $menu['admin']['content']['rule']['shortcut'] = 'r';
      $menu['admin']['content']['rule']['page']     = '/front/rule.php';

      if ($sector=='admin' && $item == 'rule') {
         foreach ($CFG_GLPI["rulecollections_types"] as $rulecollectionclass) {
            $rulecollection = new $rulecollectionclass;
            if ($rulecollection->canList()) {
               $ruleclassname = $rulecollection->getRuleClassName();
               $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['title']
                              = $rulecollection->getRuleClass()->getTitle();
               $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['page']
                              = getItemTypeSearchURL($ruleclassname,false);
               $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['links']['search']
                              = getItemTypeSearchURL($ruleclassname,false);
               if ($rulecollection->canCreate()) {
                  $menu['admin']['content']['rule']['options'][$rulecollection->menu_option]['links']['add']
                                 = getItemTypeFormURL($ruleclassname,false);
               }
            }
         }
      }
   }


   if (haveRight("transfer","r" ) && isMultiEntitiesMode()) {
      $menu['admin']['content']['rule']['options']['transfer']['title'] = $LANG['transfer'][1];
      $menu['admin']['content']['rule']['options']['transfer']['page']  = "/front/transfer.php";
      $menu['admin']['content']['rule']['options']['transfer']['links']['search']
                                                                        = "/front/transfer.php";

      if (haveRight("transfer","w")) {
         $menu['admin']['content']['rule']['options']['transfer']['links']['summary']
                                                                     = "/front/transfer.action.php";
         $menu['admin']['content']['rule']['options']['transfer']['links']['add']
                                                                     = "/front/transfer.form.php";
      }
   }


   if (haveRight("rule_dictionnary_dropdown","r")
       || haveRight("rule_dictionnary_software","r")
       || haveRight("rule_dictionnary_printer","r")) {

      $menu['admin']['content']['dictionnary']['title']    = $LANG['rulesengine'][77];
      $menu['admin']['content']['dictionnary']['shortcut'] = 'r';
      $menu['admin']['content']['dictionnary']['page']     = '/front/dictionnary.php';

      if ($sector=='admin' && $item == 'dictionnary') {
         $menu['admin']['content']['dictionnary']['options']['manufacturers']['title']
                        = $LANG['common'][5];
         $menu['admin']['content']['dictionnary']['options']['manufacturers']['page']
                        = '/front/ruledictionnarymanufacturer.php';
         $menu['admin']['content']['dictionnary']['options']['manufacturers']['links']['search']
                        = '/front/ruledictionnarymanufacturer.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['manufacturers']['links']['add']
                           = '/front/ruledictionnarymanufacturer.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['software']['title']
                        = $LANG['Menu'][4];
         $menu['admin']['content']['dictionnary']['options']['software']['page']
                        = '/front/ruledictionnarysoftware.php';
         $menu['admin']['content']['dictionnary']['options']['software']['links']['search']
                        = '/front/ruledictionnarysoftware.php';

         if (haveRight("rule_dictionnary_software","w")) {
            $menu['admin']['content']['dictionnary']['options']['software']['links']['add']
                           = '/front/ruledictionnarysoftware.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['model.computer']['title']
                        = $LANG['setup'][91];
         $menu['admin']['content']['dictionnary']['options']['model.computer']['page']
                        = '/front/ruledictionnarycomputermodel.php';
         $menu['admin']['content']['dictionnary']['options']['model.computer']['links']['search']
                        = '/front/ruledictionnarycomputermodel.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['model.computer']['links']['add']
                           = '/front/ruledictionnarycomputermodel.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['model.monitor']['title']
                        = $LANG['setup'][94];
         $menu['admin']['content']['dictionnary']['options']['model.monitor']['page']
                        = '/front/ruledictionnarymodelmonitor.php';
         $menu['admin']['content']['dictionnary']['options']['model.monitor']['links']['search']
                        = '/front/ruledictionnarymonitormodel.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['model.monitor']['links']['add']
                           = '/front/ruledictionnarymonitormodel.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['model.printer']['title']
                        = $LANG['setup'][96];
         $menu['admin']['content']['dictionnary']['options']['model.printer']['page']
                        = '/front/ruledictionnaryprintermodel.php';
         $menu['admin']['content']['dictionnary']['options']['model.printer']['links']['search']
                        = '/front/ruledictionnaryprintermodel.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['model.printer']['links']['add']
                           = '/front/ruledictionnaryprintermodel.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['model.peripheral']['title']
                        = $LANG['setup'][97];
         $menu['admin']['content']['dictionnary']['options']['model.peripheral']['page']
                        = '/front/ruledictionnaryperipheralmodel.php';
         $menu['admin']['content']['dictionnary']['options']['model.peripheral']['links']['search']
                        = '/front/ruledictionnaryperipheralmodel.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['model.peripheral']['links']['add']
                           = '/front/ruledictionnaryperipheralmodel.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['model.networking']['title']
                        = $LANG['setup'][95];
         $menu['admin']['content']['dictionnary']['options']['model.networking']['page']
                        = '/front/ruledictionnarynetworkequipmentmodel.php';
         $menu['admin']['content']['dictionnary']['options']['model.networking']['links']['search']
                        = '/front/ruledictionnarynetworkequipmentmodel.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['model.networking']['links']['add']
                           = '/front/ruledictionnarynetworkequipmentmodel.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['model.phone']['title']
                        = $LANG['setup'][503];
         $menu['admin']['content']['dictionnary']['options']['model.phone']['page']
                        = '/front/ruledictionnaryphonemodel.php';
         $menu['admin']['content']['dictionnary']['options']['model.phone']['links']['search']
                        = '/front/ruledictionnaryphonemodel.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['model.phone']['links']['add']
                           = '/front/ruledictionnaryphonemodel.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['type.computer']['title']
                        = $LANG['setup'][4];
         $menu['admin']['content']['dictionnary']['options']['type.computer']['page']
                        = '/front/ruledictionnarycomputertype.php';
         $menu['admin']['content']['dictionnary']['options']['type.computer']['links']['search']
                        = '/front/ruledictionnarycomputertype.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['type.computer']['links']['add']
                           = '/front/ruledictionnarycomputertype.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['type.monitor']['title']
                        = $LANG['setup'][44];
         $menu['admin']['content']['dictionnary']['options']['type.monitor']['page']
                        = '/front/ruledictionnarymonitortype.php';
         $menu['admin']['content']['dictionnary']['options']['type.monitor']['links']['search']
                        = '/front/ruledictionnarymonitortype.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['type.monitor']['links']['add']
                           = '/front/ruledictionnarymonitortype.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['type.printer']['title']
                        = $LANG['setup'][43];
         $menu['admin']['content']['dictionnary']['options']['type.printer']['page']
                        = '/front/ruledictionnaryprintertype.php';
         $menu['admin']['content']['dictionnary']['options']['type.printer']['links']['search']
                        = '/front/ruledictionnaryprintertype.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['type.printer']['links']['add']
                           = '/front/ruledictionnaryprintertype.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['type.peripheral']['title']
                        = $LANG['setup'][69];
         $menu['admin']['content']['dictionnary']['options']['type.peripheral']['page']
                        = '/front/ruledictionnaryperipheraltype.php';
         $menu['admin']['content']['dictionnary']['options']['type.peripheral']['links']['search']
                        = '/front/ruledictionnaryperipheraltype.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['type.peripheral']['links']['add']
                           = '/front/ruledictionnaryperipheraltype.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['type.networking']['title']
                        = $LANG['setup'][42];
         $menu['admin']['content']['dictionnary']['options']['type.networking']['page']
                        = '/front/ruledictionnarynetworkequipmenttype.php';
         $menu['admin']['content']['dictionnary']['options']['type.networking']['links']['search']
                        = '/front/ruledictionnarynetworkequipmenttype.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['type.networking']['links']['add']
                           = '/front/ruledictionnarynetworkequipmenttype.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['type.phone']['title']
                        = $LANG['setup'][504];
         $menu['admin']['content']['dictionnary']['options']['type.phone']['page']
                        = '/front/ruledictionnaryphonetype.php';
         $menu['admin']['content']['dictionnary']['options']['type.phone']['links']['search']
                        = '/front/ruledictionnaryphonetype.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['type.phone']['links']['add']
                           = '/front/ruledictionnaryphonetype.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['os']['title'] = $LANG['computers'][9];
         $menu['admin']['content']['dictionnary']['options']['os']['page']
                        = '/front/ruledictionnaryoperatingsystem.php';
         $menu['admin']['content']['dictionnary']['options']['os']['links']['search']
                        = '/front/ruledictionnaryoperatingsystem.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['os']['links']['add']
                           = '/front/ruledictionnaryoperatingsystem.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['os_sp']['title']
                        = $LANG['computers'][53];
         $menu['admin']['content']['dictionnary']['options']['os_sp']['page']
                        = '/front/ruledictionnaryoperatingsystemservicepack.php';
         $menu['admin']['content']['dictionnary']['options']['os_sp']['links']['search']
                        = '/front/ruledictionnaryoperatingsystemservicepack.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['os_sp']['links']['add']
                           = '/front/ruledictionnaryoperatingsystemservicepack.form.php';
         }


         $menu['admin']['content']['dictionnary']['options']['os_version']['title']
                        = $LANG['computers'][52];
         $menu['admin']['content']['dictionnary']['options']['os_version']['page']
                        = '/front/ruledictionnaryoperatingsystemversion.php';
         $menu['admin']['content']['dictionnary']['options']['os_version']['links']['search']
                        = '/front/ruledictionnaryoperatingsystemversion.php';

         if (haveRight("rule_dictionnary_dropdown","w")) {
            $menu['admin']['content']['dictionnary']['options']['os_version']['links']['add']
                           = '/front/ruledictionnaryoperatingsystemversion.form.php';
         }

         $menu['admin']['content']['dictionnary']['options']['printer']['title']
                        = $LANG['rulesengine'][39];
         $menu['admin']['content']['dictionnary']['options']['printer']['page']
                        = '/front/ruledictionnaryprinter.php';
         $menu['admin']['content']['dictionnary']['options']['printer']['links']['search']
                        = '/front/ruledictionnaryprinter.php';

         if (haveRight("rule_dictionnary_printer","w")) {
            $menu['admin']['content']['dictionnary']['options']['printer']['links']['add']
                           = '/front/ruledictionnaryprinter.form.php';
         }

      }
   }


   if (haveRight("profile","r")) {
      $menu['admin']['content']['profile']['title']           = $LANG['Menu'][35];
      $menu['admin']['content']['profile']['shortcut']        = 'p';
      $menu['admin']['content']['profile']['page']            = '/front/profile.php';
      $menu['admin']['content']['profile']['links']['search'] = "/front/profile.php";

      if (haveRight("profile","w")) {
         $menu['admin']['content']['profile']['links']['add'] = "/front/profile.form.php";
      }
   }

   if (haveRight("backup","w")) {
      $menu['admin']['content']['backup']['title']    = $LANG['Menu'][12];
      $menu['admin']['content']['backup']['shortcut'] = 'b';
      $menu['admin']['content']['backup']['page']     = '/front/backup.php';
   }


   if (haveRight("logs","r")) {
      $menu['admin']['content']['log']['title']    = $LANG['Menu'][30];
      $menu['admin']['content']['log']['shortcut'] = 'l';
      $menu['admin']['content']['log']['page']     = '/front/event.php';
   }



   /// CONFIG
   $config    = array();
   $addconfig = array();
   $menu['config']['title'] = $LANG['common'][12];

   if (haveRight("dropdown","r") || haveRight("entity_dropdown","r")) {
      $menu['config']['content']['dropdowns']['title'] = $LANG['setup'][0];
      $menu['config']['content']['dropdowns']['page']  = '/front/dropdown.php';

      $menu['config']['default'] = '/front/dropdown.php';

      if ($item=="dropdowns") {
         $dps = Dropdown::getStandardDropdownItemTypes();

         foreach ($dps as $tab) {
            foreach ($tab as $key => $val) {
               if ($key == $option) {
                  $tmp = new $key();
                  $menu['config']['content']['dropdowns']['options'][$option]['title'] = $val;
                  $menu['config']['content']['dropdowns']['options'][$option]['page']
                                 = $tmp->getSearchURL(false);
                  $menu['config']['content']['dropdowns']['options'][$option]['links']['search']
                                 = $tmp->getSearchURL(false);
                  if ($tmp->canCreate()) {
                     $menu['config']['content']['dropdowns']['options'][$option]['links']['add']
                                    = $tmp->getFormURL(false);
                  }
               }
            }
         }
      }
   }


   if (haveRight("device","w")) {
      $menu['config']['content']['device']['title'] = $LANG['title'][30];
      $menu['config']['content']['device']['page']  = '/front/device.php';

      if ($item=="device") {
         $dps = Dropdown::getDeviceItemTypes();

         foreach ($dps as $tab) {
            foreach ($tab as $key => $val) {
               if ($key == $option) {
                  $tmp = new $key();
                  $menu['config']['content']['device']['options'][$option]['title'] = $val;
                  $menu['config']['content']['device']['options'][$option]['page']
                                 = $tmp->getSearchURL(false);
                  $menu['config']['content']['device']['options'][$option]['links']['search']
                                 = $tmp->getSearchURL(false);
                  if ($tmp->canCreate()) {
                     $menu['config']['content']['device']['options'][$option]['links']['add']
                                    = $tmp->getFormURL(false);
                  }
               }
            }
         }
      }
   }


   if (($CFG_GLPI['use_mailing'] && haveRight("notification","r")) || haveRight("config","w")) {
      $menu['config']['content']['mailing']['title'] = $LANG['setup'][704];
      $menu['config']['content']['mailing']['page']  = '/front/setup.notification.php';
      $menu['config']['content']['mailing']['options']['notification']['title']
                                                     = $LANG['setup'][704];
      $menu['config']['content']['mailing']['options']['notification']['page']
                                                     = '/front/notification.php';
      $menu['config']['content']['mailing']['options']['notification']['links']['add']
                                                     = '/front/notification.form.php';
      $menu['config']['content']['mailing']['options']['notification']['links']['search']
                                                     = '/front/notification.php';
   }


   if (haveRight("sla","r")) {
      $menu['config']['content']['sla']['title']           = $LANG['Menu'][43];
      $menu['config']['content']['sla']['page']            = '/front/sla.php';
      $menu['config']['content']['sla']['links']['search'] = "/front/sla.php";
      if (haveRight("sla","w")) {
         $menu['config']['content']['sla']['links']['add']    = "/front/sla.form.php";
      }
   }

   if (haveRight("config","w")) {

      $menu['config']['content']['config']['title'] = $LANG['setup'][703];
      $menu['config']['content']['config']['page']  = '/front/config.form.php';

      $menu['config']['content']['control']['title'] = $LANG['Menu'][41];
      $menu['config']['content']['control']['page']  = '/front/control.php';

      $menu['config']['content']['control']['options']['FieldUnicity']['title']
                     = $LANG['setup'][811];
      $menu['config']['content']['control']['options']['FieldUnicity']['page']
                     = '/front/fieldunicity.php';
      $menu['config']['content']['control']['options']['FieldUnicity']['links']['add']
                     = '/front/fieldunicity.form.php';
      $menu['config']['content']['control']['options']['FieldUnicity']['links']['search']
                     = '/front/fieldunicity.php';

      $menu['config']['content']['crontask']['title']           = $LANG['crontask'][0];
      $menu['config']['content']['crontask']['page']            = '/front/crontask.php';
      $menu['config']['content']['crontask']['links']['search'] = "/front/crontask.php";

      $menu['config']['content']['mailing']['options']['config']['title'] = $LANG['mailing'][118];
      $menu['config']['content']['mailing']['options']['config']['page']
                     = '/front/notificationmailsetting.form.php';

      $menu['config']['content']['mailing']['options']['notificationtemplate']['title']
                     = $LANG['mailing'][113];
      $menu['config']['content']['mailing']['options']['notificationtemplate']['page']
                     = '/front/notificationtemplate.php';
      $menu['config']['content']['mailing']['options']['notificationtemplate']['links']['add']
                     = '/front/notificationtemplate.form.php';
      $menu['config']['content']['mailing']['options']['notificationtemplate']['links']['search']
                     = '/front/notificationtemplate.php';

      $menu['config']['content']['extauth']['title'] = $LANG['login'][10];
      $menu['config']['content']['extauth']['page']  = '/front/setup.auth.php';

      $menu['config']['content']['extauth']['options']['ldap']['title'] = $LANG['login'][2];
      $menu['config']['content']['extauth']['options']['ldap']['page']  = '/front/authldap.php';

      $menu['config']['content']['extauth']['options']['imap']['title'] = $LANG['login'][3];
      $menu['config']['content']['extauth']['options']['imap']['page']  = '/front/authmail.php';

      $menu['config']['content']['extauth']['options']['others']['title'] = $LANG['common'][67];
      $menu['config']['content']['extauth']['options']['others']['page']  = '/front/auth.others.php';

      $menu['config']['content']['extauth']['options']['settings']['title'] = $LANG['common'][12];
      $menu['config']['content']['extauth']['options']['settings']['page']
                     = '/front/auth.settings.php';

      switch ($option) {
         case "ldap" : // LDAP
            $menu['config']['content']['extauth']['options']['ldap']['links']['search']
                           = '/front/authldap.php';
            $menu['config']['content']['extauth']['options']['ldap']['links']['add']
                           = '' .'/front/authldap.form.php';
            break;

         case "imap" : // IMAP
            $menu['config']['content']['extauth']['links']['search'] = '/front/authmail.php';
            $menu['config']['content']['extauth']['links']['add']    = '' .'/front/authmail.form.php';
            break;
      }

      $menu['config']['content']['mailcollector']['title'] = $LANG['Menu'][39];
      $menu['config']['content']['mailcollector']['page']  = '/front/mailcollector.php';

      if (canUseImapPop()) {
         $menu['config']['content']['mailcollector']['links']['search'] = '/front/mailcollector.php';
         $menu['config']['content']['mailcollector']['links']['add']
                                    = '/front/mailcollector.form.php';
         $menu['config']['content']['mailcollector']['options']['rejectedemails']['links']['search']
                                    = '/front/notimportedemail.php';
      }
   }


   if ($CFG_GLPI["use_ocs_mode"] && haveRight("config","w")) {
      $menu['config']['content']['ocsng']['title']           = $LANG['setup'][134];
      $menu['config']['content']['ocsng']['page']            = '/front/ocsserver.php';
      $menu['config']['content']['ocsng']['links']['search'] = '/front/ocsserver.php';
      $menu['config']['content']['ocsng']['links']['add']    = '/front/ocsserver.form.php';
   }


   if (haveRight("link","r")) {
      $menu['config']['content']['link']['title']           = $LANG['title'][33];
      $menu['config']['content']['link']['page']            = '/front/link.php';
      $menu['config']['content']['link']['hide']            = true;
      $menu['config']['content']['link']['links']['search'] = '/front/link.php';

      if (haveRight("link","w")) {
         $menu['config']['content']['link']['links']['add'] = "/front/link.form.php";
      }
   }


   if (haveRight("config","w")) {
      $menu['config']['content']['plugins']['title'] = $LANG['common'][29];
      $menu['config']['content']['plugins']['page']  = '/front/plugin.php';
   }



   // Special items
   $menu['preference']['title']   = $LANG['Menu'][11];
   $menu['preference']['default'] = '/front/preference.php';

   echo "<div id='header'>";
   echo "<div id='c_logo'>";
   echo "<a href='".$CFG_GLPI["root_doc"]."/front/central.php' title=\"".$LANG['central'][5]."\"></a>";
   echo "</div>";

   /// Prefs / Logout link
   echo "<div id='c_preference' >";
   echo "<ul>";

   echo "<li id='deconnexion'><a href='".$CFG_GLPI["root_doc"]."/logout.php";

   /// logout witout noAuto login for extauth
   if (isset($_SESSION['glpiextauth']) && $_SESSION['glpiextauth']) {
      echo "?noAUTO=1";
   }
   echo "' title=\"".$LANG['central'][6]."\">".$LANG['central'][6]."</a>";

   // check user id : header used for display messages when session logout
   if (getLoginUserID()) {
      echo "(";
      echo formatUserName (0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                           $_SESSION["glpifirstname"], 0, 20);
      echo ")";
   }
   echo "</li>\n";

   echo "<li><a href='".
         (empty($CFG_GLPI["central_doc_url"])?"http://glpi-project.org/help-central":
         $CFG_GLPI["central_doc_url"])."' target='_blank' title=\"".$LANG['central'][7]."\">".
         $LANG['central'][7]."</a></li>";

   echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
              $LANG['Menu'][11]."\">".$LANG['Menu'][11]."</a></li>";

   echo "</ul>";
   echo "<div class='sep'></div>";
   echo "</div>\n";

   /// Search engine
   echo "<div id='c_recherche' >\n";
   echo "<form method='get' action='".$CFG_GLPI["root_doc"]."/front/search.php'>\n";
   echo "<div id='boutonRecherche'>";
   echo "<input type='image' src='".$CFG_GLPI["root_doc"]."/pics/ok2.png' value='OK' title=\"".
          $LANG['buttons'][2]."\"  alt=\"".$LANG['buttons'][2]."\"></div>";
   echo "<div id='champRecherche'><input size='15' type='text' name='globalsearch' value='".
          $LANG['buttons'][0]."' onfocus=\"this.value='';\"></div>";
   echo "</form>";

   echo "<div class='sep'></div>\n";
   echo "</div>";

   ///Main menu
   echo "<div id='c_menu'>";
   echo "<ul id='menu'>";

   // Get object-variables and build the navigation-elements
   $i = 1;
   foreach ($menu as $part => $data) {
      if (isset($data['content']) && count($data['content'])) {
         echo "<li id='menu$i' onmouseover=\"javascript:menuAff('menu$i','menu');\" >";
         $link = "#";

         if (isset($data['default'])&&!empty($data['default'])) {
            $link = $CFG_GLPI["root_doc"].$data['default'];
         }

         if (utf8_strlen($data['title'])>14) {
            $data['title'] = utf8_substr($data['title'], 0, 14)."...";
         }
         echo "<a href='$link' class='itemP'>".$data['title']."</a>";
         echo "<ul class='ssmenu'>";

         // list menu item
         foreach ($data['content'] as $key => $val) {
            if (isset($val['page'])&&isset($val['title'])) {
               echo "<li><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

               if (isset($data['shortcut'])&&!empty($data['shortcut'])) {
                  echo " accesskey='".$val['shortcut']."'";
               }
               echo ">".$val['title']."</a></li>\n";
            }
         }
         echo "</ul></li>";
         $i++;
      }
   }

   echo "</ul>";
   echo "<div class='sep'></div>";
   echo "</div>";

   // End navigation bar
   // End headline
   // Le sous menu contextuel 1
   echo "<div id='c_ssmenu1' >";
   echo "<ul>";

   // list sous-menu item
   if (isset($menu[$sector])) {
      if (isset($menu[$sector]['content']) && is_array($menu[$sector]['content'])) {
         $ssmenu = $menu[$sector]['content'];

         if (count($ssmenu)>12) {
            foreach ($ssmenu as $key => $val) {
               if (isset($val['hide'])) {
                  unset($ssmenu[$key]);
               }
            }
            $ssmenu = array_splice($ssmenu,0,12);
         }

         foreach ($ssmenu as $key => $val) {
            if (isset($val['page'])&&isset($val['title'])) {
               echo "<li><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

               if (isset($val['shortcut'])&&!empty($val['shortcut'])) {
                  echo " accesskey='".$val['shortcut']."'";
               }
               echo ">".$val['title']."</a></li>\n";
            }
         }

      } else {
         echo "<li>&nbsp;</li>";
      }

   } else {
      echo "<li>&nbsp;</li>";
   }
   echo "</ul></div>";

   //  Le fil d ariane
   echo "<div id='c_ssmenu2' >";
   echo "<ul>";

   // Display item
   echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/central.php' title=\"".$LANG['central'][5]."\">".
              $LANG['central'][5]."</a> ></li>";

   if (isset($menu[$sector])) {
      $link = "/front/central.php";

      if (isset($menu[$sector]['default'])) {
         $link = $menu[$sector]['default'];
      }
      echo "<li><a href='".$CFG_GLPI["root_doc"].$link."' title=\"".$menu[$sector]['title']."\">".
                 $menu[$sector]['title']."</a> ></li>";
   }

   if (isset($menu[$sector]['content'][$item])) {
      // Title
      $with_option = false;

      if (!empty($option)
          && isset($menu[$sector]['content'][$item]['options'][$option]['title'])
          && isset($menu[$sector]['content'][$item]['options'][$option]['page'])) {

         $with_option = true;
      }

      if (isset($menu[$sector]['content'][$item]['page'])) {
         echo "<li><a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['page']."' ".
                    ($with_option?"":"class='here'")." title=\"".
                    $menu[$sector]['content'][$item]['title']."\" >".
                    $menu[$sector]['content'][$item]['title']."</a>".(!$with_option?"":" > ")."</li>";
      }

      if ($with_option) {
         echo "<li><a href='".$CFG_GLPI["root_doc"].
                    $menu[$sector]['content'][$item]['options'][$option]['page'].
                    "' class='here' title=\"".
                    $menu[$sector]['content'][$item]['options'][$option]['title']."\" >";
         echo resume_name($menu[$sector]['content'][$item]['options'][$option]['title'], 17);
         echo "</a></li>";
      }

      echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

      $links = array();
      // Item with Option case
      if (!empty($option)
          && isset($menu[$sector]['content'][$item]['options'][$option]['links'])
          && is_array($menu[$sector]['content'][$item]['options'][$option]['links'])) {

         $links = $menu[$sector]['content'][$item]['options'][$option]['links'];

      // Without option case : only item links
      } else if (isset($menu[$sector]['content'][$item]['links'])
                 && is_array($menu[$sector]['content'][$item]['links'])) {

         $links = $menu[$sector]['content'][$item]['links'];
      }

      // Add item
      echo "<li>";
      if (isset($links['add'])) {
         echo "<a href='".$CFG_GLPI["root_doc"].$links['add']."'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png' title=\"".
                $LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\"></a>";

      } else {
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add_off.png' title=\"".
                $LANG['buttons'][8]."\" alt=\"".$LANG['buttons'][8]."\">";
      }
      echo "</li>";

      // Search Item
      if (isset($links['search'])) {
         echo "<li><a href='".$CFG_GLPI["root_doc"].$links['search']."'>";
         echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_search.png' title=\"".
                $LANG['buttons'][0]."\" alt=\"".$LANG['buttons'][0]."\"></a></li>";

      } else {
         echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/menu_search_off.png' title=\"".
                    $LANG['buttons'][0]."\" alt=\"".$LANG['buttons'][0]."\"></li>";
      }
      // Links
      if (count($links)>0) {
         foreach ($links as $key => $val) {

            switch ($key) {
               case "add" :
               case "search" :
                  break;

               case "template" :
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                             $LANG['common'][8]."\" alt=\"".$LANG['common'][8]."\" src='".
                             $CFG_GLPI["root_doc"]."/pics/menu_addtemplate.png'></a></li>";
                  break;

               case "showall" :
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                             $LANG['buttons'][40]."\" alt=\"".$LANG['buttons'][40]."\" src='".
                             $CFG_GLPI["root_doc"]."/pics/menu_showall.png'></a></li>";
                  break;

               case "summary" :
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                             $LANG['state'][1]."\" alt=\"".$LANG['state'][1]."\" src='".
                             $CFG_GLPI["root_doc"]."/pics/menu_show.png'></a></li>";
                  break;

               case "config" :
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'><img title=\"".
                             $LANG['common'][12]."\" alt=\"".$LANG['common'][12]."\" src='".
                             $CFG_GLPI["root_doc"]."/pics/menu_config.png'></a></li>";
                  break;

               default :
                  echo "<li><a href='".$CFG_GLPI["root_doc"].$val."'>".$key."</a></li>";
                  break;
            }
         }
      }

   } else {
      echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";
      echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".
            "&nbsp;&nbsp;&nbsp;&nbsp;</li>";
   }

   // Add common items
   echo "<li>";
   // Display MENU ALL
   echo "<div id='show_all_menu' onmouseover=\"completecleandisplay('show_all_menu');\">";
   $items_per_columns = 15;
   $i = -1;
   echo "<table><tr><td class='top'><table>";

   foreach ($menu as $part => $data) {
      if (isset($data['content']) && count($data['content'])) {

         if ($i>$items_per_columns) {
            $i = 0;
            echo "</table></td><td class='top'><table>";
         }
         $link = "#";

         if (isset($data['default']) && !empty($data['default'])) {
            $link = $CFG_GLPI["root_doc"].$data['default'];
         }

         echo "<tr><td class='tab_bg_1 b'>";
         echo "<a href='$link' title=\"".$data['title']."\" class='itemP'>".$data['title']."</a>";
         echo "</td></tr>";
         $i++;

         // list menu item
         foreach ($data['content'] as $key => $val) {

            if ($i>$items_per_columns) {
               $i = 0;
               echo "</table></td><td class='top'><table>";
            }

            if (isset($val['page']) && isset($val['title'])) {
               echo "<tr><td><a href='".$CFG_GLPI["root_doc"].$val['page']."'";

               if (isset($data['shortcut']) && !empty($data['shortcut'])) {
                  echo " accesskey='".$val['shortcut']."'";
               }
               echo ">".$val['title']."</a></td></tr>\n";
               $i++;
            }
         }
      }
   }
   echo "</table></td></tr></table>";

   echo "</div>";
   echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
   echo "</li>";

   /// Bookmark load
   echo "<li>";
   echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
          "/front/popup.php?popup=load_bookmark' ,'glpibookmarks', 'height=400, width=600, top=100,".
          "left=100, scrollbars=yes' );w.focus();\">";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark.png' title=\"".$LANG['buttons'][52]." ".
          $LANG['bookmark'][1]."\"  alt=\"".$LANG['buttons'][52]." ".$LANG['bookmark'][1]."\">";
   echo "</a></li>";

   /// MENU ALL
   echo "<li >";
   echo "<img alt='' src='".$CFG_GLPI["root_doc"]."/pics/menu_all.png' ".
          "onclick=\"completecleandisplay('show_all_menu')\">";
   echo "</li>";
   // check user id : header used for display messages when session logout
   if (getLoginUserID()) {
      showProfileSelecter($CFG_GLPI["root_doc"]."/front/central.php");
   }
   echo "</ul>";
   echo "</div>";

   echo "</div>\n"; // fin header

   echo "<div id='page' >";

   if ($DB->isSlave() && !$DB->first_connection) {
      echo "<div id='dbslave-float'>";
      echo "<a href='#see_debug'>".$LANG['setup'][809]."</a>";
      echo "</div>";
   }

   // call function callcron() every 5min
   callCron();
   displayMessageAfterRedirect();
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
 * Add a message to be displayed after redirect
 *
 * @param $msg Message to add
 * @param $check_once Check if the message is not already added
 * @param $message_type message type (INFO, ERROR)
 * @param $reset clear previous added message
 **/
function addMessageAfterRedirect($msg, $check_once=false, $message_type=INFO, $reset=false) {

   // Do not display of cron jobs messages in user interface
   if (getLoginUserID() === getLoginUserID(false)) {
      if (!empty($msg)) {

         if ($reset) {
            $_SESSION["MESSAGE_AFTER_REDIRECT"]='';
         }
         $toadd = "";

         if ($check_once) {
            if (strstr($_SESSION["MESSAGE_AFTER_REDIRECT"],$msg)===false) {
               $toadd = $msg.'<br>';
            }

         } else {
            $toadd = $msg.'<br>';
         }

         if (!empty($toadd)) {
            switch ($message_type) {
               case ERROR :
                  $_SESSION["MESSAGE_AFTER_REDIRECT"] .= "<h3><span class='red'>$toadd</span></h3>";
                  break;

               default: // INFO
                  $_SESSION["MESSAGE_AFTER_REDIRECT"] .= "<h3>$toadd</h3>";
            }
         }
      }
   }
}


/**
 * Print a nice HTML head for help page
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function helpHeader($title, $url='') {
   global $CFG_GLPI, $LANG, $HEADER_LOADED, $PLUGIN_HOOKS;

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
   echo "<div id='c_logo' >";
   echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' accesskey='0' title=\"".
          $LANG['central'][5]."\"><span class='invisible'>Logo</span></a></div>";

   // Les préférences + lien déconnexion
   echo "<div id='c_preference' >";
   echo "<ul><li id='deconnexion'><a href='".$CFG_GLPI["root_doc"]."/logout.php' title=\"".
               $LANG['central'][6]."\">".$LANG['central'][6]."</a>";

   // check user id : header used for display messages when session logout
   if (getLoginUserID()) {
      echo "&nbsp;(";
      echo formatUserName (0, $_SESSION["glpiname"], $_SESSION["glpirealname"],
                           $_SESSION["glpifirstname"], 0, 20);
      echo ")";
   }
   echo "</li>\n";

   echo "<li><a href='".(empty($CFG_GLPI["helpdesk_doc_url"])?
              "http://glpi-project.org/help-helpdesk":$CFG_GLPI["helpdesk_doc_url"]).
              "' target='_blank' title=\"".$LANG['central'][7]."\"> ".$LANG['central'][7]."</a></li>";
   echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/preference.php' title=\"".
               $LANG['Menu'][11]."\">".$LANG['Menu'][11]."</a></li>\n";

   echo "</ul>";
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

   // Home
   echo "<li id='menu1'>";
   echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php' title=\"".
          $LANG['job'][13]."\" class='itemP'>".$LANG['central'][5]."</a>";
   echo "</li>";

   //  Suivi ticket
   if (haveRight("observe_ticket","1")) {
      echo "<li id='menu2'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/ticket.php' title=\"".
             $LANG['title'][10]."\" class='itemP'>".$LANG['title'][28]."</a>";
      echo "</li>";
   }

   // Reservation
   if (haveRight("reservation_helpdesk","1")) {
      echo "<li id='menu3'>";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/reservationitem.php' title=\"".
             $LANG['Menu'][17]."\" class='itemP'>".$LANG['Menu'][17]."</a>";
      echo "</li>";
   }

   // FAQ
   if (haveRight("faq","r")) {
      echo "<li id='menu4' >";
      echo "<a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.faq.php' title=\"".
             $LANG['knowbase'][1]."\" class='itemP'>".$LANG['Menu'][20]."</a>";
      echo "</li>";
   }

   // PLUGINS
   $plugins = array();
   if (isset($PLUGIN_HOOKS["helpdesk_menu_entry"]) && count($PLUGIN_HOOKS["helpdesk_menu_entry"])) {
      foreach ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {

         if ($active) {
            $function = "plugin_version_$plugin";

            if (function_exists($function)) {
               $plugins[$plugin] = $function();
            }
         }
      }
   }

   if (isset($plugins) && count($plugins)>0) {
      $list = array();

      foreach ($plugins as $key => $val) {
         $list[$key] = $val["name"];
      }

      asort($list);
      echo "<li id='menu5' onmouseover=\"javascript:menuAff('menu5','menu');\">";
      echo "<a href='#' title=\"".$LANG['common'][29]."\" class='itemP'>".
             $LANG['common'][29]."</a>";  // default none
      echo "<ul class='ssmenu'>";

      // list menu item
      foreach ($list as $key => $val) {
         $link = "";

         if (is_string($PLUGIN_HOOKS["helpdesk_menu_entry"][$key])) {
            $link = $PLUGIN_HOOKS["helpdesk_menu_entry"][$key];
         }
         echo "<li><a href='".$CFG_GLPI["root_doc"]."/plugins/".$key.$link."'>".
                    $plugins[$key]["name"]."</a></li>\n";
      }
      echo "</ul></li>";
   }
   echo "</ul>";
   echo "<div class='sep'></div>";

   echo "</div>";

   // End navigation bar
   // End headline
   ///Le sous menu contextuel 1
   echo "<div id='c_ssmenu1'>&nbsp;</div>";

   //  Le fil d ariane
   echo "<div id='c_ssmenu2'>";
   echo "<ul>";
   echo "<li><a href='#' title=''>".$LANG['central'][5]."></a></li>";
   echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

   if (haveRight('validate_ticket',1)) {
      $opt = array();
      $opt['reset']         = 'reset';
      $opt['field'][0]      = 55; // validation status
      $opt['searchtype'][0] = 'equals';
      $opt['contains'][0]   = 'waiting';
      $opt['link'][0]       = 'AND';

      $opt['field'][1]      = 59; // validation aprobator
      $opt['searchtype'][1] = 'equals';
      $opt['contains'][1]   = getLoginUserID();
      $opt['link'][1]       = 'AND';


      $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($opt,'&amp;');
      $pic_validate = "<a href='$url_validate'><img title=\"".$LANG['validation'][15]."\" alt=\"".
                        $LANG['validation'][15]."\" src='".
                        $CFG_GLPI["root_doc"]."/pics/menu_showall.png'></a>";
      echo "<li>$pic_validate</li>\n";

   }
   echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

   if (haveRight('create_ticket',1) && strpos($_SERVER['PHP_SELF'],"ticket")) {
      echo "<li><a href='".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?create_ticket=1'>";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png' title=\"".$LANG['buttons'][8].
             "\" alt=\"".$LANG['buttons'][8]."\"></a></li>";
   }

   echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

   /// Bookmark load
   echo "<li>";
   echo "<a href='#' onClick=\"var w=window.open('".$CFG_GLPI["root_doc"].
          "/front/popup.php?popup=load_bookmark' ,'glpibookmarks', 'height=400, width=600, top=100,".
          "left=100, scrollbars=yes' );w.focus();\">";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/bookmark.png' title=\"".$LANG['buttons'][52]." ".
          $LANG['bookmark'][1]."\" alt=\"".$LANG['buttons'][52]." ".$LANG['bookmark'][1]."\">";
   echo "</a></li>";

   // check user id : header used for display messages when session logout
   if (getLoginUserID()) {
      showProfileSelecter($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
   }
   echo "</ul></div>";

   echo "</div>"; // fin header
   echo "<div id='page' >";

   // call function callcron() every 5min
   callCron();
   displayMessageAfterRedirect();
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

   // call function callcron() every 5min
   callCron();
}


/**
 * Print a nice HTML head with no controls
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function nullHeader($title, $url='') {
   global $CFG_GLPI, $HEADER_LOADED, $LANG;

   if ($HEADER_LOADED) {
      return;
   }
   $HEADER_LOADED = true;
   // Print a nice HTML-head with no controls

   // Detect root_doc in case of error
   Config::detectRootDoc();

   // Send UTF8 Headers
   header("Content-Type: text/html; charset=UTF-8");

   // Send extra expires header if configured
   header_nocache();

   if (isCommandLine()) {
      return true;
   }

   includeCommonHtmlHeader($title);

   // Body with configured stuff
   echo "<body>";
   echo "<div id='page'>";
   echo "<div id='bloc'>";
   echo "<div class='haut'></div>";
}


/**
 * Print a nice HTML head for popup window (nothing to display)
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function popHeader($title, $url='') {
   global $CFG_GLPI, $LANG, $PLUGIN_HOOKS, $HEADER_LOADED;

   // Print a nice HTML-head for every page
   if ($HEADER_LOADED) {
      return;
   }
   $HEADER_LOADED = true;

   includeCommonHtmlHeader($title); // Body
   echo "<body>";
   displayMessageAfterRedirect();
}


/**
 * Print footer for a popup window
 **/
function popFooter() {
   global $FOOTER_LOADED;

   if ($FOOTER_LOADED) {
      return;
   }
   $FOOTER_LOADED = true;

   // Print foot
   echo "</body></html>";
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
 * Print footer for every page
 *
 * @param $keepDB booleen, closeDBConnections if false
 **/
function commonFooter($keepDB=false) {
   global $LANG, $CFG_GLPI, $FOOTER_LOADED, $TIMER_DEBUG;

   // Print foot for every page
   if ($FOOTER_LOADED) {
      return;
   }
   $FOOTER_LOADED = true;

   echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

   echo "<div id='footer' >";
   echo "<table width='100%'><tr><td class='left'><span class='copyright'>";
   echo $TIMER_DEBUG->getTime()."s - ";

   if (function_exists("memory_get_usage")) {
      echo memory_get_usage();
   }
   echo "</span></td>";

   if (!empty($CFG_GLPI["founded_new_version"])) {
      echo "<td class='copyright'>".$LANG['setup'][301].
            "<a href='http://www.glpi-project.org' target='_blank' title=\"".$LANG['setup'][302]."\"> ".
               $CFG_GLPI["founded_new_version"]."</a></td>";
   }
   echo "<td class='right'>";
   echo "<a href='http://glpi-project.org/'>";
   echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y").
          " by the INDEPNET Development Team.</span>";
   echo "</a></td>";
   echo "</tr></table></div>";

   if ($_SESSION['glpi_use_mode']==TRANSLATION_MODE) { // debug mode traduction
      echo "<div id='debug-float'>";
      echo "<a href='#see_debug'>GLPI MODE TRANSLATION</a>";
      echo "</div>";
   }

   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
      echo "<div id='debug-float'>";
      echo "<a href='#see_debug'>GLPI MODE DEBUG</a>";
      echo "</div>";
   }
   displayDebugInfos();
   echo "</body></html>";

   if (!$keepDB) {
      closeDBConnections();
   }
}


/**
 * Display Ajax Footer for debug
 **/
function ajaxFooter() {

   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
      $rand = mt_rand();
      echo "<div class='center' id='debugajax'>";
      echo "<a class='debug-float' href=\"javascript:showHideDiv('see_ajaxdebug$rand','','','');\" >
             AJAX DEBUG</a></div>";
      echo "<div id='see_ajaxdebug$rand' name='see_ajaxdebug$rand' style=\"display:none;\">";
      displayDebugInfos(false);
      echo "</div></div>";
   }
}


/**
 * Print footer for help page
 **/
function helpFooter() {
   global $LANG, $CFG_GLPI, $FOOTER_LOADED;

   // Print foot for help page
   if ($FOOTER_LOADED) {
      return;
   }
   $FOOTER_LOADED = true;

   echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

   echo "<div id='footer'>";
   echo "<table width='100%'><tr><td class='right'>";
   echo "<a href='http://glpi-project.org/'>";
   echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y").
          " by the INDEPNET Development Team.</span>";
   echo "</a></td></tr></table></div>";

   if ($_SESSION['glpi_use_mode']==TRANSLATION_MODE) { // debug mode traduction
      echo "<div id='debug-float'>";
      echo "<a href='#see_debug'>GLPI MODE TRANSLATION</a>";
      echo "</div>";
   }

   if ($_SESSION['glpi_use_mode']==DEBUG_MODE) { // mode debug
      echo "<div id='debug-float'>";
      echo "<a href='#see_debug'>GLPI MODE DEBUG</a>";
      echo "</div>";
   }
   displayDebugInfos();
   echo "</body></html>";
   closeDBConnections();
}


/**
 * Print footer for null page
 **/
function nullFooter() {
   global $CFG_GLPI, $FOOTER_LOADED;

   // Print foot for null page
   if ($FOOTER_LOADED) {
      return;
   }
   $FOOTER_LOADED = true;

   if (!isCommandLine()) {
      echo "<div class='bas'></div></div></div>";

      echo "<div id='footer-login'>";
      echo "<a href='http://glpi-project.org/' title='Powered By Indepnet'>";
      echo 'GLPI version '.(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").
           ' Copyright (C) 2003-'.date("Y").' INDEPNET Development Team.';
      echo "</a></div>";

      echo "</body></html>";
   }
   closeDBConnections();
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
      if ($minimal || !isset ($_SESSION["glpiactiveprofile"]["interface"])) {
         nullHeader($LANG['login'][5], '');

      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
         commonHeader($LANG['login'][5], '');

      } else if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
         helpHeader($LANG['login'][5], '');
      }
   }
   echo "<div class='center'><br><br>";
   echo "<img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='warning'><br><br>";
   echo "<strong>$message</strong></div>";
   nullFooter();
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

   if (!haveRight("create_ticket","1")) {
      return false;
   }

   if (haveRight('validate_ticket',1)) {
      $opt = array();
      $opt['reset']         = 'reset';
      $opt['field'][0]      = 55; // validation status
      $opt['searchtype'][0] = 'equals';
      $opt['contains'][0]   = 'waiting';
      $opt['link'][0]       = 'AND';

      $opt['field'][1]      = 59; // validation aprobator
      $opt['searchtype'][1] = 'equals';
      $opt['contains'][1]   = getLoginUserID();
      $opt['link'][1]       = 'AND';

      $url_validate = $CFG_GLPI["root_doc"]."/front/ticket.php?".append_params($opt, '&amp;');

      if (TicketValidation::getNumberTicketsToValidate(getLoginUserID()) >0) {
         echo "<a href='$url_validate' title=\"".$LANG['validation'][15]."\"
                alt=\"".$LANG['validation'][15]."\">".$LANG['validation'][33]."</a><br><br>";
      }
   }

   $query = "SELECT `email`, `realname`, `firstname`, `name`
             FROM `glpi_users`
             WHERE `id` = '$ID'";
   $result = $DB->query($query);
   $email  = $DB->result($result,0,"email");

   // Get saved data from a back system
   $use_email_notification = 1;
   if ($email=="") {
      $use_email_notification = 0;
   }
   $itemtype            = 0;
   $items_id            = "";
   $content             = "";
   $title               = "";
   $ticketcategories_id = 0;
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
      $content = cleanPostForTextArea($_SESSION["helpdeskSaved"]["content"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["name"])) {
      $title = stripslashes($_SESSION["helpdeskSaved"]["name"]);
   }
   if (isset($_SESSION["helpdeskSaved"]["ticketcategories_id"])) {
      $ticketcategories_id = stripslashes($_SESSION["helpdeskSaved"]["ticketcategories_id"]);
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
   echo "<div class='center'><table class='tab_cadre'>";

   echo "<tr><th colspan='2'>".$LANG['job'][11]."&nbsp;:&nbsp;";
   if (isMultiEntitiesMode()) {

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

      $_REQUEST['value']            = getLoginUserID();
      $_REQUEST['field']            = '_users_id_requester_notif';
      $_REQUEST['use_notification'] = $use_email_notification;
      include (GLPI_ROOT."/ajax/uemailUpdate.php");

      echo "</td></tr>";
   }

   if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0) {
      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['help'][24]."&nbsp;:&nbsp;</td>";
      echo "<td>";
      Ticket::dropdownMyDevices(getLoginUserID(), $_SESSION["glpiactive_entity"]);
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
   Dropdown::show('TicketCategory', array('value'     => $ticketcategories_id,
                                          'condition' => '`is_helpdeskvisible`=1'));
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['common'][57]."&nbsp;:&nbsp;</td>";
   echo "<td><input type='text' maxlength='250' size='50' name='name' value='$title'></td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td colspan='2'><textarea name='content' cols='78' rows='14'>$content</textarea>";
   echo "</td></tr>";

   echo "<tr class='tab_bg_1'>";
   echo "<td>".$LANG['document'][2]." (".Document::getMaxUploadSize().")&nbsp;:&nbsp;";
   echo "<img src='".$CFG_GLPI["root_doc"]."/pics/aide.png' class='pointer' alt='".
          $LANG['central'][7]."' onclick=\"window.open('".$CFG_GLPI["root_doc"].
          "/front/documenttype.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
   echo "</td>";
   echo "<td><input type='file' name='filename' value='' size='25'></td></tr>";

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
         $pos    = utf8_strpos($split[$i],'=');
         $length = utf8_strlen($split[$i]);
         echo "<input type='hidden' name='".utf8_substr($split[$i],0,$pos)."' value='".
                urldecode(utf8_substr($split[$i], $pos+1))."'>";
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

   ajaxUpdateItemOnSelectEvent("genericdate$element$rand",
                               "displaygenericdate$element$rand",
                               $CFG_GLPI["root_doc"]."/ajax/genericdate.php", $params, false);

   $params['value'] = $value;
   ajaxUpdateItem("displaygenericdate$element$rand", $CFG_GLPI["root_doc"]."/ajax/genericdate.php",
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

   $output .= "<table><tr class='top'><td><div id='date$rand-date'></div></td>";
   $output .= "<td><select name='_date$rand-hour' id='date$rand-hour'>";

   for ($i=$minHour ; $i<=$maxHour ; $i++) {
      if ($i<10 && strlen($i)==1) {
         $i = '0'.$i;
      }
      $output .= "<option value='$i'>$i</option>";
   }
   $output .= "</select></td>";
   $output .= "<td><select name='_date$rand-minute' id='date$rand-minute'>";

   for ($i=$minMinute ; $i<=$maxMinute ; $i+=$time_step) {
      if ($i<10  && strlen($i)==1) {
         $i = '0'.$i;
      }
      $output .= "<option value='$i'>$i</option>";
   }
   $output .= "</select></td>";
   $output .= '</tr></table>';


   $output .= "<script type='text/javascript'>";
   $output .= "Ext.onReady(function() {
      var md$rand = new Ext.ux.form.DateTime({
         hiddenName: '$element'
         ,id: 'date$rand'
         ,value: '$value'
         ,hiddenFormat:'Y-m-d H:i:s'
         ,renderTo: 'showdate$rand'
         ,timeFormat:'H:i'
         ,timeWidth: 40
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
      altFormats:'H:i',increment: $time_step,$empty";
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
      $output .= ",minValue: '".convDate($minDate)."'";
   }
   if (!empty($maxDate)) {
      $output .= ",maxValue: '".convDate($maxDate)."'";
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
 *
 * @return rand value used
 **/
function showDateFormItem($element, $value='', $maybeempty=true, $can_edit=true, $minDate='',
                          $maxDate='') {
   global $CFG_GLPI;

   $rand = mt_rand();
   echo "<input id='showdate$rand' type='text' size='10' name='$element'>";

   $output  = "<script type='text/javascript'>\n";
   $output .= "Ext.onReady(function() {
      var md$rand = new Ext.ux.form.XDateField({
         name: '$element'
         ,value: '".convDate($value)."'
         ,applyTo: 'showdate$rand'
         ,id: 'date$rand'
         ,submitFormat:'Y-m-d'
         ,startDay: 1";

   switch ($_SESSION['glpidate_format']) {
      case 1 :
         $output .= ",format: 'd-m-Y'";
         break;

      case 2 :
         $output .= ",format: 'm-d-Y'";
         break;

      default :
         $output .= ",format: 'Y-m-d'";
   }

   if ($maybeempty) {
      $output .= ",allowBlank: true";
   } else {
      $output .= ",allowBlank: false";
   }

   if (!$can_edit) {
      $output .= ",disabled: true";
   }

   if (!empty($minDate)) {
      $output .= ",minValue: '".convDate($minDate)."'";
   }

   if (!empty($maxDate)) {
      $output .= ",maxValue: '".convDate($maxDate)."'";
   }

   $output .= " });
   });";
   $output .= "</script>\n";
   echo $output;
   return $rand;
}


/**
 *  Get active Tab for an itemtype
 *
 * @param $itemtype item type
 *
 * @return nothing
 **/
function getActiveTab($itemtype) {

   if (isset($_SESSION['glpi_tabs'][strtolower($itemtype)])) {
      return $_SESSION['glpi_tabs'][strtolower($itemtype)];
   }
   return "";
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

   $active_tabs = getActiveTab($type);

   if (count($tabs)>0) {
      echo "<script type='text/javascript'>";

         echo " var tabpanel = new Ext.TabPanel({
            applyTo: '$tabdiv_id',
            width:$size,
            enableTabScroll: true,
            resizeTabs: false,
            collapsed: true,
            plain: true,
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
                  id: '$key',
                  autoLoad: {url: '".$val['url']."',
                     scripts: true,
                     nocache: true";
                     if (isset($val['params'])) {
                        echo ", params: '".$val['params']."'";
                     }
                     echo "},";

               echo "  listeners:{ // Force glpi_tab storage
                       beforeshow : function(panel) {
                        /* clean content because append data instead of replace it : no more problem */
                        /* Problem with IE6... But clean data for tabpanel before show. Do it on load default tab ?*/
                        /*tabpanel.body.update('');*/
                        /* update active tab*/
                        Ext.Ajax.request({
                           url : '".$CFG_GLPI['root_doc']."/ajax/updatecurrenttab.php?itemtype=$type&glpi_tab=$key',
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
               var tab = tabpanel.getActiveTab();
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
 *  show notes for item
 *
 * @param $target target page to update item
 * @param $itemtype item type of the device to display notes
 * @param $id id of the device to display notes
 *
 * @return nothing
 */
function showNotesForm($target, $itemtype, $id) {
   global $LANG;

   if (!haveRight("notes","r")) {
      return false;
   }

   if (!class_exists($itemtype)) {
      return false;
   }

   $item = new $itemtype();
   //getFromDB
   $item->getFromDB ($id);
   $canedit = (haveRight("notes", "w")
               && (!$item->isEntityAssign() || haveAccessToEntity($item->getEntityID())));

   if ($canedit) {
      echo "<form name='form' method='post' action='".$target."'>";
   }

   echo "<div class='center'>";
   echo "<table class='tab_cadre_fixe' >";
   echo "<tr><th class='center'>".$LANG['title'][37]."</th></tr>";

   echo "<tr><td class='tab_bg_1 center middle'>";
   echo "<textarea class='textarea_notes' cols='100' rows='35' name='notepad'>".
          $item->getField('notepad')."</textarea></td></tr>";

   echo "<tr><td class='tab_bg_2 center'>";
   echo "<input type='hidden' name='id' value=$id>";

   if ($canedit) {
      echo "<input type='submit' name='update' value=\"".$LANG['buttons'][7]."\" class='submit'>";
   }
   echo "</td></tr>";
   echo "</table></div>";

   if ($canedit) {
      echo "</form>";
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
 * Display a simple progress bar
 *
 * @param $width Width of the progress bar
 * @param $percent Percent of the progress bar
 * @param $options array options :
 *            - title : string title to display (default Progesssion)
 *            - simple : display a simple progress bar (no title / only percent)
 *            - forcepadding : boolean force str_pad to force refresh (default true)
 *
 * @return nothing
 **/
function displayProgressBar($width, $percent, $options=array()) {
   global $CFG_GLPI, $LANG;

   $param['title']        = $LANG['common'][47];
   $param['simple']       = false;
   $param['forcepadding'] = true;

   if (is_array($options) && count($options)) {
      foreach ($options as $key => $val) {
         $param[$key] = $val;
      }
   }

   $percentwidth = floor($percent*$width/100);
   $output       = "<div class='center'><table class='tab_cadre' width='".($width+20)."px'>";

   if (!$param['simple']) {
      $output .= "<tr><th class='center'>".$param['title']."&nbsp;".$percent."%</th></tr>";
   }
   $output .= "<tr><td>
               <table><tr><td class='center' style='background:url(".$CFG_GLPI["root_doc"].
                "/pics/loader.png) repeat-x; padding: 0px;font-size: 10px;' width='".$percentwidth.
                "px' height='12'>";

   if ($param['simple']) {
      $output .= $percent."%";
   } else {
      $output .= '&nbsp;';
   }

   $output .= "</td></tr></table></td>";
   $output .= "</tr></table>";
   $output .= "</div>";

   if (!$param['forcepadding']) {
      echo $output;
   } else {
      echo utf8_str_pad($output, 4096);
      glpi_flush();
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

   if (isMultiEntitiesMode()) {
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
             $_SESSION["glpiactive_entity_name"]."\" class='entity_select' id='global_entity_select'>".
             $_SESSION["glpiactive_entity_shortname"]."</a>";

      echo "</li>";
   }
}


/**
 * Create a Dynamic Progress Bar
 *
 * @param $msg initial message (under the bar)
 *
 * @return nothing
 **/
function createProgressBar ($msg="&nbsp;") {

   echo "<div class='doaction_cadre'>".
        "<div class='doaction_progress' id='doaction_progress'></div>".
        "</div><br>";

   echo "<script type='text/javascript'>";
   echo "var glpi_progressbar=new Ext.ProgressBar({
      text:\"$msg\",
      id:'progress_bar',
      applyTo:'doaction_progress'
   });";
   echo "</script>\n";
}


/**
 * Change the Progress Bar Position
 *
 * @param $crt Current Value (less then $max)
 * @param $tot Maximum Value
 * @param $msg message inside the bar (defaut is %)
 *
 * @return nothing
 **/
function changeProgressBarPosition ($crt, $tot, $msg="") {

   if (!$tot) {
      $pct = 0;

   } else if ($crt>$tot) {
      $pct = 1;

   } else {
      $pct = $crt/$tot;
   }
   echo "<script type='text/javascript'>glpi_progressbar.updateProgress(\"$pct\",\"$msg\");</script>\n";
   glpi_flush();
}


/**
 * Change the Message under the Progress Bar
 *
 * @param $msg message under the bar
 *
 * @return nothing
 **/
function changeProgressBarMessage($msg="&nbsp;") {
   echo "<script type='text/javascript'>glpi_progressbar.updateText(\"$msg\")</script>\n";
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
 * show arrow for massives actions : closing
 *
 * @param $name string name of submit button, none if empty
 * @param $label string label of submit button
 **/
function closeArrowMassive($name='', $label='') {

   if (!empty($name)) {
      echo "<input type='submit' name='$name' value='$label' class='submit'>";
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
            "' value=\"".cleanInputText($params['value'])."\" size='".$params['size']."'>\n";
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
             value=\"".cleanInputText($params['value'])."\" size='".$params['size']."'>\n";
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
