<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2007 by the INDEPNET Development Team.

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


//******************************************************************************************************
//******************************************************************************************************
//***********  Fonctions d'affichage header footer helpdesk pager ***********************
//******************************************************************************************************
//******************************************************************************************************

/**
 * Common Title Function
 *
 * @param $ref_pic_link Path to the image to display
 * @param $ref_pic_text Alt text of the icon
 * @param $ref_title Title to display
 * @param $ref_btts Extra items to display array(link=>text...)
 * @return nothing
 **/
function displayTitle($ref_pic_link="",$ref_pic_text="",$ref_title="",$ref_btts="") {
        echo "<div class='center'><table border='0'><tr>";
        if ($ref_pic_link!="")
                echo "<td><img src=\"".$ref_pic_link."\" alt=\"".$ref_pic_text."\"
title=\"".$ref_pic_text."\" ></td>"; 
        if ($ref_title!="")
                echo "<td><span class='icon_consol'><strong>".$ref_title."</strong></span></td>"; 
	if (is_array($ref_btts)&&count($ref_btts))
        foreach ($ref_btts as $key => $val) { 
                echo "<td><a class='icon_consol_hov' href=\"".$key."\">".$val."</a></td>"; 
        }        
        echo "</tr></table></div>";
}

/**
 * Print a nice HTML head for every page
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 * @param $sector sector in which the page displayed is
 * @param $item item corresponding to the page displayed
 *
 **/
function commonHeader($title,$url,$sector="none",$item="none")
{
	// Print a nice HTML-head for every page

	global $CFG_GLPI,$LANG,$PLUGIN_HOOKS,$HEADER_LOADED,$INFOFORM_PAGES ;
	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;
	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
	}


	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		header_nocache();
	}

	
		// Start the page
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
		 // echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
		echo "\n<html><head><title>GLPI - ".$title."</title>";
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
		// Send extra expires header if configured
		if ($CFG_GLPI["sendexpire"]) {
			echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\" >\n";
			echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
			echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
		}
		//  CSS link
		echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >\n";
		// surcharge CSS hack for IE
		echo "<!--[if lte IE 6]>" ;
 		echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' media='screen' >\n";
 		echo "<![endif]-->";


		echo "<link rel='stylesheet' type='text/css' media='print' href='".$CFG_GLPI["root_doc"]."/css/print.css' >\n";
		echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >\n";
		// AJAX library
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>\n";
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>\n";

		// Some Javascript-Functions which we may need later
		echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>\n";
	
		// Calendar scripts 
		echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>\n";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>\n";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][2].".js\"></script>\n";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>\n";
	
		// Add specific javascript for plugins
		if (isset($PLUGIN_HOOKS['add_javascript'])&&count($PLUGIN_HOOKS['add_javascript'])){
			foreach  ($PLUGIN_HOOKS["add_javascript"] as $plugin => $file) {
				echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/plugins/$plugin/$file'></script>\n";
			}
		}
		// Add specific css for plugins
		if (isset($PLUGIN_HOOKS['add_css'])&&count($PLUGIN_HOOKS['add_css'])){
			foreach  ($PLUGIN_HOOKS["add_css"] as $plugin => $file) {
				echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/plugins/$plugin/$file' type='text/css' media='screen' >\n";
			}
		}
	
		// End of Head
		echo "</head>\n";

	if (!($CFG_GLPI["cache"]->start($sector.'_'.$item,"GLPI_HEADER_".$_SESSION["glpiID"]))) {
		
	// Body 
		echo "<body>";
	
		
		//  Generate array for menu and check right
		

		//////// INVENTORY
		$showstate=false;
		$menu['inventory']['title']=$LANG["Menu"][38];
		if (haveRight("computer","r")){
			$menu['inventory']['default']='/front/computer.php';

			$menu['inventory']['content']['computer']['title']=$LANG["Menu"][0];
			$menu['inventory']['content']['computer']['shortcut']='c';
			$menu['inventory']['content']['computer']['page']='/front/computer.php';
			$menu['inventory']['content']['computer']['links']['search']='/front/computer.php';
			$menu['inventory']['content']['computer']['links']['add']='/front/setup.templates.php?type='.COMPUTER_TYPE.'&amp;add=1';
			$menu['inventory']['content']['computer']['links']['template']='/front/setup.templates.php?type='.COMPUTER_TYPE.'&amp;add=0';
			$showstate=true;
		}
		if (haveRight("monitor","r")){
			$menu['inventory']['content']['monitor']['title']=$LANG["Menu"][3];
			$menu['inventory']['content']['monitor']['shortcut']='m';
			$menu['inventory']['content']['monitor']['page']='/front/monitor.php';
			$menu['inventory']['content']['monitor']['links']['search']='/front/monitor.php';
			$menu['inventory']['content']['monitor']['links']['add']='/front/setup.templates.php?type='.MONITOR_TYPE.'&amp;add=1';
			$menu['inventory']['content']['monitor']['links']['template']='/front/setup.templates.php?type='.MONITOR_TYPE.'&amp;add=0';

			$showstate=true;
		}
		if (haveRight("software","r")){
			$menu['inventory']['content']['software']['title']=$LANG["Menu"][4];
			$menu['inventory']['content']['software']['shortcut']='s';
			$menu['inventory']['content']['software']['page']='/front/software.php';
			$menu['inventory']['content']['software']['links']['search']='/front/software.php';
			$menu['inventory']['content']['software']['links']['add']='/front/setup.templates.php?type='.SOFTWARE_TYPE.'&amp;add=1';
			$menu['inventory']['content']['software']['links']['template']='/front/setup.templates.php?type='.SOFTWARE_TYPE.'&amp;add=0';

			$showstate=true;
		}
		if (haveRight("networking","r")){
			$menu['inventory']['content']['networking']['title']=$LANG["Menu"][1];
			$menu['inventory']['content']['networking']['shortcut']='n';
			$menu['inventory']['content']['networking']['page']='/front/networking.php';
			$menu['inventory']['content']['networking']['links']['search']='/front/networking.php';
			$menu['inventory']['content']['networking']['links']['add']='/front/setup.templates.php?type='.NETWORKING_TYPE.'&amp;add=1';
			$menu['inventory']['content']['networking']['links']['template']='/front/setup.templates.php?type='.NETWORKING_TYPE.'&amp;add=0';
			$showstate=true;
		}
		if (haveRight("peripheral","r")){
			$menu['inventory']['content']['peripheral']['title']=$LANG["Menu"][16];
			$menu['inventory']['content']['peripheral']['shortcut']='n';
			$menu['inventory']['content']['peripheral']['page']='/front/peripheral.php';
			$menu['inventory']['content']['peripheral']['links']['search']='/front/peripheral.php';
			$menu['inventory']['content']['peripheral']['links']['add']='/front/setup.templates.php?type='.PERIPHERAL_TYPE.'&amp;add=1';
			$menu['inventory']['content']['peripheral']['links']['template']='/front/setup.templates.php?type='.PERIPHERAL_TYPE.'&amp;add=0';
			$showstate=true;
		}
		if (haveRight("printer","r")){
			$menu['inventory']['content']['printer']['title']=$LANG["Menu"][2];
			$menu['inventory']['content']['printer']['shortcut']='p';
			$menu['inventory']['content']['printer']['page']='/front/printer.php';
			$menu['inventory']['content']['printer']['links']['search']='/front/printer.php';
			$menu['inventory']['content']['printer']['links']['add']='/front/setup.templates.php?type='.PRINTER_TYPE.'&amp;add=1';
			$menu['inventory']['content']['printer']['links']['template']='/front/setup.templates.php?type='.PRINTER_TYPE.'&amp;add=0';
			$showstate=true;
		}
		if (haveRight("cartridge","r")){
			$menu['inventory']['content']['cartridge']['title']=$LANG["Menu"][21];
			$menu['inventory']['content']['cartridge']['shortcut']='c';
			$menu['inventory']['content']['cartridge']['page']='/front/cartridge.php';
			$menu['inventory']['content']['cartridge']['links']['search']='/front/cartridge.php';
			if (haveRight("cartridge","w")){
				$menu['inventory']['content']['cartridge']['links']['add']='/front/cartridge.form.php';
			}
		}
		if (haveRight("consumable","r")){
			$menu['inventory']['content']['consumable']['title']=$LANG["Menu"][32];
			$menu['inventory']['content']['consumable']['shortcut']='g';
			$menu['inventory']['content']['consumable']['page']='/front/consumable.php';
			if (haveRight("consumable","w")){
				$menu['inventory']['content']['consumable']['links']['add']='/front/consumable.form.php';
				$menu['inventory']['content']['consumable']['links']['summary']='/front/consumable.php?synthese=yes';
			}
		}
		if (haveRight("phone","r")){
			$menu['inventory']['content']['phone']['title']=$LANG["Menu"][34];
			$menu['inventory']['content']['phone']['shortcut']='t';
			$menu['inventory']['content']['phone']['page']='/front/phone.php';
			$menu['inventory']['content']['phone']['links']['search']='/front/phone.php';
			$menu['inventory']['content']['phone']['links']['add']='/front/setup.templates.php?type='.PHONE_TYPE.'&amp;add=1';
			$menu['inventory']['content']['phone']['links']['template']='/front/setup.templates.php?type='.PHONE_TYPE.'&amp;add=0';
			$showstate=true;
		}
		if ($showstate){
			$menu['inventory']['content']['state']['title']=$LANG["Menu"][28];
			$menu['inventory']['content']['state']['shortcut']='n';
			$menu['inventory']['content']['state']['page']='/front/state.php';
			$menu['inventory']['content']['state']['links']['search']='/front/state.php';
			$menu['inventory']['content']['state']['links']['summary']='/front/state.php?synthese=yes';
		}


//////// ASSISTANCE
		$menu['maintain']['title']=$LANG["title"][24];

		if (haveRight("observe_ticket","1")||haveRight("show_ticket","1")||haveRight("create_ticket","1")){
			$menu['maintain']['default']='/front/tracking.php';

			$menu['maintain']['content']['tracking']['title']=$LANG["Menu"][5];
			$menu['maintain']['content']['tracking']['shortcut']='t';
			$menu['maintain']['content']['tracking']['page']='/front/tracking.php';
			$menu['maintain']['content']['tracking']['links']['search']='/front/tracking.php';

			$menu['maintain']['content']['helpdesk']['links']['search']='/front/tracking.php';

		}
		if (haveRight("create_ticket","1")){
			$menu['maintain']['content']['helpdesk']['title']=$LANG["Menu"][31];
			$menu['maintain']['content']['helpdesk']['shortcut']='h';
			$menu['maintain']['content']['helpdesk']['page']='/front/helpdesk.php';
			$menu['maintain']['content']['helpdesk']['links']['add']='/front/helpdesk.php';

			$menu['maintain']['content']['tracking']['links']['add']='/front/helpdesk.php';
		}
		if (haveRight("show_planning","1")||haveRight("show_all_planning","1")){
			$menu['maintain']['content']['planning']['title']=$LANG["Menu"][29];
			$menu['maintain']['content']['planning']['shortcut']='l';
			$menu['maintain']['content']['planning']['page']='/front/planning.php';
			$menu['maintain']['content']['planning']['links']['search']='/front/planning.php';

		}
		if (haveRight("statistic","1")){
			$menu['maintain']['content']['stat']['title']=$LANG["Menu"][13];
			$menu['maintain']['content']['stat']['shortcut']='1';
			$menu['maintain']['content']['stat']['page']='/front/stat.php';
		}
		
		
//////// FINANCIAL
		$menu['financial']['title']=$LANG["Menu"][26];
		if (haveRight("contact_enterprise","r")){
			$menu['financial']['default']='/front/contact.php';

			$menu['financial']['content']['contact']['title']=$LANG["Menu"][22];
			$menu['financial']['content']['contact']['shortcut']='t';
			$menu['financial']['content']['contact']['page']='/front/contact.php';
			$menu['financial']['content']['contact']['links']['search']='/front/contact.php';

			$menu['financial']['content']['enterprise']['title']=$LANG["Menu"][23];
			$menu['financial']['content']['enterprise']['shortcut']='e';
			$menu['financial']['content']['enterprise']['page']='/front/enterprise.php';
			$menu['financial']['content']['enterprise']['links']['search']='/front/enterprise.php';

			if (haveRight("contact_enterprise","w")){
				$menu['financial']['content']['contact']['links']['add']='/front/contact.form.php';
				$menu['financial']['content']['enterprise']['links']['add']='/front/enterprise.form.php';
			}

		}
		if (haveRight("contract_infocom","r")){
			$menu['financial']['content']['contract']['title']=$LANG["Menu"][25];
			$menu['financial']['content']['contract']['shortcut']='n';
			$menu['financial']['content']['contract']['page']='/front/contract.php';
			$menu['financial']['content']['contract']['links']['search']='/front/contract.php';

			if (haveRight("contract_infocom","w")){
				$menu['financial']['content']['contract']['links']['add']='/front/contract.form.php';
			}

		}
		if (haveRight("document","r")){
			$menu['financial']['content']['document']['title']=$LANG["Menu"][27];
			$menu['financial']['content']['document']['shortcut']='d';
			$menu['financial']['content']['document']['page']='/front/document.php';
			$menu['financial']['content']['document']['links']['search']='/front/document.php';

			if (haveRight("document","w")){
				$menu['financial']['content']['document']['links']['add']='/front/document.form.php';
			}
		}
	
//////// UTILS
		$menu['utils']['title']=$LANG["Menu"][18];
		$menu['utils']['default']='/front/reminder.php';

		$menu['utils']['content']['reminder']['title']=$LANG["reminder"][2];
		$menu['utils']['content']['reminder']['page']='/front/reminder.php';
		$menu['utils']['content']['reminder']['links']['search']='/front/reminder.php';
		$menu['utils']['content']['reminder']['links']['add']='/front/reminder.form.php';

		if (haveRight("knowbase","r")||haveRight("faq","r")) {

			$menu['utils']['content']['knowbase']['title']=$LANG["Menu"][19];
			$menu['utils']['content']['knowbase']['page']='/front/knowbase.php';
			$menu['utils']['content']['knowbase']['links']['search']='/front/knowbase.php';

			if (haveRight("knowbase","w")||haveRight("faq","w")){
				$menu['utils']['content']['knowbase']['links']['add']='/front/knowbase.form.php?ID=new';
			}

		}
		if (haveRight("reservation_helpdesk","1")||haveRight("reservation_central","r")){
			$menu['utils']['content']['reservation']['title']=$LANG["Menu"][17];
			$menu['utils']['content']['reservation']['page']='/front/reservation.php';
			$menu['utils']['content']['reservation']['links']['search']='/front/reservation.php';
			$menu['utils']['content']['reservation']['links']['showall']='/front/reservation.php?show=resa&amp;ID';
		}
		if (haveRight("reservation_helpdesk","1")||haveRight("reservation_central","r")){
			$menu['utils']['content']['reservation']['title']=$LANG["Menu"][17];
			$menu['utils']['content']['reservation']['page']='/front/reservation.php';
			$menu['utils']['content']['reservation']['links']['search']='/front/reservation.php';
			$menu['utils']['content']['reservation']['links']['showall']='/front/reservation.php?show=resa&amp;ID';
		}
		if (haveRight("reports","r")){
			$menu['utils']['content']['report']['title']=$LANG["Menu"][6];
			$menu['utils']['content']['report']['page']='/front/report.php';
		}

		if ($CFG_GLPI["ocs_mode"]&&haveRight("ocsng","w")){
			$menu['utils']['content']['ocsng']['title']=$LANG["Menu"][33];
			$menu['utils']['content']['ocsng']['page']='/front/ocsng.php';
		//	$menu['utils']['content']['ocsng']['links']['search']='/front/ocsng.php';
			}
		
		// PLUGINS
		if (isset($PLUGIN_HOOKS["menu_entry"])&&count($PLUGIN_HOOKS["menu_entry"])){	
			$menu['plugins']['title']=$LANG["common"][29];

			$plugins=array();
	
			foreach  ($PLUGIN_HOOKS["menu_entry"] as $plugin => $active) {
				if ($active){
					$function="plugin_version_$plugin";
	
					if (function_exists($function))
						$plugins[$plugin]=$function();
				}
			}
			if (count($plugins)){
				$list=array();
				foreach ($plugins as $key => $val) {
					$list[$key]=$val["name"];
				}
				
				asort($list);
				foreach ($list as $key => $val) {
					$menu['plugins']['content'][$key]['title']=$val;
					$menu['plugins']['content'][$key]['page']='/plugins/'.$key.'/';
					if ($sector=="plugins"&&$item==$key){
						if (isset($PLUGIN_HOOKS["submenu_entry"][$key])&&is_array($PLUGIN_HOOKS["submenu_entry"][$key])){
							foreach ($PLUGIN_HOOKS["submenu_entry"][$key] as $name => $link){
								$menu['plugins']['content'][$key]['links'][$name]='/plugins/'.$key.'/'.$link;
							}
						}
					}
				}
				
			}

		}
		//////// ADMINISTRATION
		$menu['admin']['title']=$LANG["Menu"][15];

		if (haveRight("user","r")){
			$menu['admin']['default']='/front/user.php';

			$menu['admin']['content']['user']['title']=$LANG["Menu"][14];
			$menu['admin']['content']['user']['shortcut']='u';
			$menu['admin']['content']['user']['page']='/front/user.php';
			$menu['admin']['content']['user']['links']['search']='/front/user.php';

			if (haveRight("user","w")){
				$menu['admin']['content']['user']['links']['add']="/front/user.form.php";
			}

		}
		if (haveRight("group","r")){
			$menu['admin']['content']['group']['title']=$LANG["Menu"][36];
			$menu['admin']['content']['group']['shortcut']='g';
			$menu['admin']['content']['group']['page']='/front/group.php';
			$menu['admin']['content']['group']['links']['search']='/front/group.php';

			if (haveRight("group","w")){
				$menu['admin']['content']['group']['links']['add']="/front/group.form.php";
			}

			}

		if (haveRight("entity","r")){
			$menu['admin']['content']['entity']['title']=$LANG["Menu"][37];
			$menu['admin']['content']['entity']['shortcut']='z';
			$menu['admin']['content']['entity']['page']='/front/entity.php';
			$menu['admin']['content']['entity']['links']['search']='/front/entity.php';

			$menu['admin']['content']['entity']['links'][$LANG["entity"][2]]="/front/entity.form.php?ID=0";
			$menu['admin']['content']['entity']['links']['add']="/front/entity.tree.php";
		}

		if (haveRight("rule_ldap","r")||haveRight("rule_ocs","r")||haveRight("rule_tracking","r")){
			$menu['admin']['content']['rule']['title']=$LANG["rulesengine"][17];
			$menu['admin']['content']['rule']['shortcut']='r';
			$menu['admin']['content']['rule']['page']='/front/rule.php';
		}

		if (haveRight("profile","r")){
			$menu['admin']['content']['profile']['title']=$LANG["Menu"][35];
			$menu['admin']['content']['profile']['shortcut']='p';
			$menu['admin']['content']['profile']['page']='/front/profile.php';
			$menu['admin']['content']['profile']['links']['search']="/front/profile.php";
			if (haveRight("profile","w")){
				$menu['admin']['content']['profile']['links']['add']="/front/profile.form.php";
			}

		}

//		$config[$LANG["Menu"][10]]=array("setup.php","2");
		if (haveRight("backup","w")){
			$menu['admin']['content']['backup']['title']=$LANG["Menu"][12];
			$menu['admin']['content']['backup']['shortcut']='b';
			$menu['admin']['content']['backup']['page']='/front/backup.php';
		}
		if (haveRight("logs","r")){
			$menu['admin']['content']['log']['title']=$LANG["Menu"][30];
			$menu['admin']['content']['log']['shortcut']='l';
			$menu['admin']['content']['log']['page']='/front/log.php';

		}
		

// CONFIG
		$config=array();
		$addconfig=array();
		$menu['config']['title']=$LANG["Menu"][10];
		$menu['config']['default']='/front/setup.php';


//		if (haveRight("search_config","w")||haveRight("search_config_global","w")){
//			$menu['config']['content']['display']['title']=$LANG["search"][0];
//			$menu['config']['content']['display']['page']='/front/setup.display.php';
//		}
		
		if (haveRight("dropdown","w")||haveRight("entity_dropdown","w")){
			$menu['config']['content']['dropdowns']['title']=$LANG["setup"][0];
			$menu['config']['content']['dropdowns']['page']='/front/setup.dropdowns.php';
		}
		if (haveRight("device","w")){
			$menu['config']['content']['device']['title']=$LANG["setup"][222];
			$menu['config']['content']['device']['page']='/front/device.php';
		}

		if (haveRight("config","w")){
			$menu['config']['content']['config']['title']=$LANG["setup"][703];
			$menu['config']['content']['config']['page']='/front/setup.config.php';

			$menu['config']['content']['mailing']['title']=$LANG["setup"][704];
			$menu['config']['content']['mailing']['page']='/front/setup.mailing.php';
			
			$menu['config']['content']['extauth']['title']=$LANG["login"][10];
			$menu['config']['content']['extauth']['page']='/front/setup.auth.php';
			$menu['config']['content']['extauth']['links']['search']='/front/setup.auth.php';

			$menu['config']['content']['mailgate']['title']=$LANG["Menu"][39];
			$menu['config']['content']['mailgate']['page']='/front/mailgate.php';
			$menu['config']['content']['mailgate']['links']['search']='/front/mailgate.php';
			$menu['config']['content']['mailgate']['links']['add']='/front/mailgate.form.php';

		}

		if ($CFG_GLPI["ocs_mode"]&&haveRight("ocsng","w")){
			$menu['config']['content']['ocsng']['title']=$LANG["setup"][134];
			$menu['config']['content']['ocsng']['page']='/front/setup.ocsng.php';
			$menu['config']['content']['ocsng']['links']['search']='/front/setup.ocsng.php';
			$menu['config']['content']['ocsng']['links']['add']='/front/setup.templates.php?type='.OCSNG_TYPE.'&amp;add=1';
			$menu['config']['content']['ocsng']['links']['template']='/front/setup.templates.php?type='.OCSNG_TYPE.'&amp;add=0';
		}

		if (haveRight("typedoc","r")){
			$menu['config']['content']['typedoc']['title']=$LANG["document"][7];
			$menu['config']['content']['typedoc']['page']='/front/typedoc.php';
			$menu['config']['content']['typedoc']['hide']=true;
			$menu['config']['content']['typedoc']['links']['search']='/front/typedoc.php';

			if (haveRight("typedoc","w")){
				$menu['config']['content']['typedoc']['links']['add']="/front/typedoc.form.php";
			}

		}
		if (haveRight("link","r")){
			$menu['config']['content']['link']['title']=$LANG["title"][33];
			$menu['config']['content']['link']['page']='/front/link.php';
			$menu['config']['content']['link']['hide']=true;
			$menu['config']['content']['link']['links']['search']='/front/link.php';

			if (haveRight("link","w")){
				$menu['config']['content']['link']['links']['add']="/front/link.form.php";
			}

		}	


		if (isset($PLUGIN_HOOKS['config_page'])&&is_array($PLUGIN_HOOKS['config_page'])&&count($PLUGIN_HOOKS['config_page']))	{
			$menu['config']['content']['plugins']['title']=$LANG["common"][29];
			$menu['config']['content']['plugins']['page']='/front/setup.plugins.php';
		}
		echo "<div id='header'>";
		echo "<div id='c_logo' ><a href='".$CFG_GLPI["root_doc"]."/front/central.php'  title=\"".$LANG["central"][5]."\"></a></div>";
		
		// Les préférences + lien déconnexion 
		echo "<div id='c_preference' >";
		echo" <ul><li id='deconnexion'><a href=\"".$CFG_GLPI["root_doc"]."/logout.php\"  title=\"".$LANG["central"][6]."\">".$LANG["central"][6]."  </a>";
		echo "(";
		echo formatUserName (0,$_SESSION["glpiname"],$_SESSION["glpirealname"],$_SESSION["glpifirstname"],0,20);
		echo ")</li>\n"; 

		echo "	<li><a href='".(empty($CFG_GLPI["centralhelp_url"])?"http://glpi-project.org/help-central":$CFG_GLPI["centralhelp_url"])."' target='_blank' title='".$LANG["central"][7]."'>    ".$LANG["central"][7]."</a></li>\n"; 

		echo "	<li> <a href=\"".$CFG_GLPI["root_doc"]."/front/user.form.my.php\" title=\"".$LANG["Menu"][11]."\" >".$LANG["Menu"][11]."   </a></li>\n"; 
		echo "</ul>\n"; 
		echo "<div class='sep'></div>\n"; 
		echo "</div>\n"; 
		
		//-- Le moteur de recherche -->
		echo "<div id='c_recherche' >\n"; 

		echo "<form method='get' action='".$CFG_GLPI["root_doc"]."/front/search.php'>\n"; 
		echo "	<div id='boutonRecherche'><input type='image' src='".$CFG_GLPI["root_doc"]."/pics/ok2.png'  value='OK'   title=\"".$LANG["buttons"][2]."\"  alt=\"".$LANG["buttons"][2]."\"  /></div>\n"; 
		echo "	<div id='champRecherche'><input size='15' type='text' name='globalsearch' value='".$LANG["buttons"][0]."' onfocus=\"this.value='';\" /></div>	\n"; 		
		echo "</form>\n"; 
		echo "<div class='sep'></div>\n"; 
		echo "</div>";
	
		//<!-- Le menu principal -->
		echo "<div id='c_menu'>";
		echo "	<ul id='menu'>";
		
	
		// Get object-variables and build the navigation-elements
		$i=1;
		foreach ($menu as $part => $data){
			if (isset($data['content'])&&count($data['content'])){
				echo "	<li id='menu$i' onmouseover=\"javascript:menuAff('menu$i','menu');\" >";

				$link="#";
				if (isset($data['default'])&&!empty($data['default'])){
					$link=$CFG_GLPI["root_doc"].$data['default'];
				}
				if (strlen($data['title'])>14){
					$data['title']=utf8_substr($data['title'],0,14)."...";
				}

				echo "<a href=\"$link\" class='itemP'>".$data['title']."</a>"; 
				echo "<ul class='ssmenu'>"; 
				// list menu item 
				foreach ($data['content'] as $key => $val) {
					echo "<li><a href=\"".$CFG_GLPI["root_doc"].$val['page']."\"";
					if (isset($data['shortcut'])&&!empty($data['shortcut'])){
						echo " accesskey=\"".$val['shortcut']."\" ";
					}
						
					 echo ">".$val['title']."</a></li>\n";
				}
	
				echo "</ul>";
				echo "</li>";		
			
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
		if (isset($menu[$sector])){
			if (isset($menu[$sector]['content'])&&is_array($menu[$sector]['content'])){

				$ssmenu=$menu[$sector]['content'];
				if (count($ssmenu)>12){
					foreach ($ssmenu as $key => $val){
						if (isset($val['hide'])){
							unset($ssmenu[$key]);
						}
					}
					$ssmenu=array_splice($ssmenu,0,12);
				}
			
				foreach ($ssmenu as $key => $val) {
					echo "<li><a href=\"".$CFG_GLPI["root_doc"].$val['page']."\" ";
					if (isset($val['shortcut'])&&!empty($val['shortcut'])){
						echo " accesskey=\"".$val['shortcut']."\"";
					}
					echo ">".$val['title']."</a></li>\n";
				}
			} else echo "<li>&nbsp;</li>";
		} else echo "<li>&nbsp;</li>";
		echo "</ul>";
		echo "</div>";
		
		//  Le fil d arianne 
		echo "<div id='c_ssmenu2' >";
		echo "<ul>";

		// Display item
		echo "	<li><a   href='".$CFG_GLPI["root_doc"]."/front/central.php' title='".$LANG["common"][56]."' >".$LANG["common"][56]." </a> ></li>";

		if (isset($menu[$sector])){
			$link="/front/central.php";
			if (isset($menu[$sector]['default'])){
				$link=$menu[$sector]['default'];
			}
			echo "	<li><a href='".$CFG_GLPI["root_doc"].$link."' title='".$menu[$sector]['title']."' >".$menu[$sector]['title']." </a> > </li>";
		}

		if (isset($menu[$sector]['content'][$item])){
			// Title
			echo "	<li><a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['page']."' class='here' title='".$menu[$sector]['content'][$item]['title']."' >".$menu[$sector]['content'][$item]['title']." </a></li>";
			echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";

			// Add item
			echo "<li>";
			if (isset($menu[$sector]['content'][$item]['links']['add'])){
				echo "<a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['links']['add']."'><img  src='".$CFG_GLPI["root_doc"]."/pics/menu_add.png' title='".$LANG["buttons"][8]."' alt='".$LANG["buttons"][8]."'></a>";
			} else {
				echo "<img src='".$CFG_GLPI["root_doc"]."/pics/menu_add_off.png' title='".$LANG["buttons"][8]."' alt='".$LANG["buttons"][8]."'>";
			}
			echo "</li>";
			// Search Item
			if (isset($menu[$sector]['content'][$item]['links']['search'])){
				echo "<li><a href='".$CFG_GLPI["root_doc"].$menu[$sector]['content'][$item]['links']['search']."' ><img  src='".$CFG_GLPI["root_doc"]."/pics/menu_search.png' title='".$LANG["buttons"][0]."' alt='".$LANG["buttons"][0]."'></a></li>";
			} else {
				echo "<li><img src='".$CFG_GLPI["root_doc"]."/pics/menu_search_off.png' title='".$LANG["buttons"][0]."' alt='".$LANG["buttons"][0]."'></li>";
			}
			
			// Links
			if (isset($menu[$sector]['content'][$item]['links'])&&is_array($menu[$sector]['content'][$item]['links'])){
				foreach ($menu[$sector]['content'][$item]['links'] as $key => $val) {
					switch ($key){
						case "add":
						case "search":
							break;
						case "template":
							echo "<li><a href='".$CFG_GLPI["root_doc"].$val."' ><img title='".$LANG["common"][8]."' alt='".$LANG["common"][8]."' src='".$CFG_GLPI["root_doc"]."/pics/menu_addtemplate.png' > </a></li>";
							break;
						case "showall":
							echo "<li><a href='".$CFG_GLPI["root_doc"].$val."' ><img title='".$LANG["buttons"][40]."' alt='".$LANG["buttons"][40]."' src='".$CFG_GLPI["root_doc"]."/pics/menu_showall.png' > </a></li>";
							break;
						case "summary":
							echo "<li><a href='".$CFG_GLPI["root_doc"].$val."' ><img title='".$LANG["state"][11]."' alt='".$LANG["state"][11]."' src='".$CFG_GLPI["root_doc"]."/pics/menu_show.png' > </a></li>";
							break;

						default :
							echo "<li><a href='".$CFG_GLPI["root_doc"].$val."' >".$key." </a></li>";
							break;
					}
				}
			}
		} else {
			echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";
			echo "<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</li>";
		}
		// Add common items 
		echo "<li>";

			// Display MENU ALL
			echo "<div id='show_all_menu' onmouseover=\"completecleandisplay('show_all_menu');\" onmouseout=\"completecleanhide('show_all_menu');\">";
			$items_per_columns=15;
			$i=-1;
			echo "<table><tr><td valign='top'><table>";
			foreach ($menu as $part => $data){
				if (isset($data['content'])&&count($data['content'])){
	
					if ($i>$items_per_columns){
						$i=0;
						echo "</table></td><td valign='top'><table>";
					}
					$link="#";
					if (isset($data['default'])&&!empty($data['default'])){
						$link=$CFG_GLPI["root_doc"].$data['default'];
					}
					echo "<tr><td class='tab_bg_1'><strong><a href=\"$link\" title=\"".$data['title']."\" class='itemP'>".$data['title']."</a></strong></td></tr>"; 
					$i++;
	
					// list menu item 
					foreach ($data['content'] as $key => $val) {
						if ($i>$items_per_columns){
							$i=0;
							echo "</table></td><td valign='top'><table>";
						}
	
						echo "<tr><td><a href=\"".$CFG_GLPI["root_doc"].$val['page']."\"";
						if (isset($data['shortcut'])&&!empty($data['shortcut'])){
							echo " accesskey=\"".$val['shortcut']."\" ";
						}
							
						echo ">".$val['title']."</a></td></tr>\n";
						$i++;
					}			
				}
			}
			echo "</table></td></tr></table>";
			
			echo "</div>";
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "</li>";

/*		echo "<li  id='headercalendar'><img  src='".$CFG_GLPI["root_doc"]."/pics/menu_calendar.png'  alt='".$LANG["buttons"][15]."' title='".$LANG["buttons"][15]."'>";

		echo "<script type='text/javascript'>";
		echo "Calendar.setup(";
		echo "{";
		echo "ifFormat : '%Y-%m-%d',"; // the datetime format
		echo "button : 'headercalendar' "; // ID of the button
		echo "});";
		echo "</script>";
		echo "</li>";
*/		

		// MENU ALL
		echo "<li >";
		echo "<img  alt='' src='".$CFG_GLPI["root_doc"]."/pics/menu_all.png' onclick=\"completecleandisplay('show_all_menu');
		\">";
		echo "</li>";
		showProfileSelecter($CFG_GLPI["root_doc"]."/front/central.php");	
		echo "</ul>";	

		echo "	</div>";
			
		echo "</div>\n"; // fin header

		
		
		echo "<div  id='page' >";
		
		$CFG_GLPI["cache"]->end();
	}
	
	// call function callcron() every 5min
	if (isset($_SESSION["glpicrontimer"])){
		if (abs(time()-$_SESSION["glpicrontimer"])>300){
			callCron();
			$_SESSION["glpicrontimer"]=time();
		} 
	} else $_SESSION["glpicrontimer"]=time();


	displayMessageAfterRedirect();
}

function displayMessageAfterRedirect(){
	// Affichage du message apres redirection
	if (isset($_SESSION["MESSAGE_AFTER_REDIRECT"])&&!empty($_SESSION["MESSAGE_AFTER_REDIRECT"])){
		echo "<div class='center'><strong>".$_SESSION["MESSAGE_AFTER_REDIRECT"]."</strong></div>";
		$_SESSION["MESSAGE_AFTER_REDIRECT"]="";
		unset($_SESSION["MESSAGE_AFTER_REDIRECT"]);
	} else $_SESSION["MESSAGE_AFTER_REDIRECT"]="";
}

/**
 * Print a nice HTML head for help page
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function helpHeader($title,$url) {
	// Print a nice HTML-head for help page

	global $CFG_GLPI,$LANG, $CFG_GLPI,$HEADER_LOADED,$PLUGIN_HOOKS ;

	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;

	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
	}

	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		header_nocache(); 
	}
	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI Helpdesk - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
	// Send extra expires header if configured

	if ($CFG_GLPI["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later

	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";


	// Appel CSS

	echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
	// surcharge CSS hack for IE
	echo "<!--[if lte IE 6]>" ;
 	echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' media='screen' >\n";
 	echo "<![endif]-->";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$CFG_GLPI["root_doc"]."/css/print.css' >";


	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][2].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";


	// Add specific javascript for plugins
	if (isset($PLUGIN_HOOKS['add_javascript'])&&count($PLUGIN_HOOKS['add_javascript'])){
		foreach  ($PLUGIN_HOOKS["add_javascript"] as $plugin => $file) {
			echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/plugins/$plugin/$file'></script>\n";
		}
	}
	// Add specific css for plugins
	if (isset($PLUGIN_HOOKS['add_css'])&&count($PLUGIN_HOOKS['add_css'])){
		foreach  ($PLUGIN_HOOKS["add_css"] as $plugin => $file) {
			echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/plugins/$plugin/$file' type='text/css' media='screen' >\n";
		}
	}

	// End of Head
	echo "</head>\n";

	// Body 
	echo "<body>";

	// Main Headline
	echo "<div id='header'>";
		echo "<div id='c_logo' ><a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php\" accesskey=\"0\"  title=\"".$LANG["central"][5]."\"><span class='invisible'>Logo</span></a></div>";

		// Les préférences + lien déconnexion 
		echo "<div id='c_preference' >";
		echo" <ul><li id='deconnexion'><a href=\"".$CFG_GLPI["root_doc"]."/logout.php\"  title=\"".$LANG["central"][6]."\">".$LANG["central"][6]."  </a>";
		echo "(";
		echo formatUserName (0,$_SESSION["glpiname"],$_SESSION["glpirealname"],$_SESSION["glpifirstname"],0,20);
		echo ")</li>\n"; 

		echo "	<li><a href='".(empty($CFG_GLPI["helpdeskhelp_url"])?"http://glpi-project.org/help-helpdesk":$CFG_GLPI["helpdeskhelp_url"])."' target='_blank' title='".$LANG["central"][7]."'>    ".$LANG["central"][7]."</a></li>\n"; 
		echo "	<li> <a href=\"".$CFG_GLPI["root_doc"]."/front/user.form.my.php\" title=\"".$LANG["Menu"][11]."\" >".$LANG["Menu"][11]."   </a></li>\n"; 
					
		echo "</ul>\n"; 
		echo "<div class='sep'></div>\n"; 
		echo "</div>\n"; 
		//-- Le moteur de recherche -->
		echo "<div id='c_recherche' >\n"; 
		/*
		echo "<form id='recherche' action=''>\n"; 
		echo "	<div id='boutonRecherche'><input type='submit' value='OK' /></div>\n"; 
		echo "	<div id='champRecherche'><input type='text' value='Recherche' /></div>	\n"; 		
		echo "</form>\n"; 
		*/
		echo "<div class='sep'></div>\n"; 
		echo "</div>";
	
		//<!-- Le menu principal -->
		echo "<div id='c_menu'>";
		echo "	<ul id='menu'>";
		
	
		// Build the navigation-elements
	
		// Ticket
		if (haveRight("create_ticket","1")){
			echo "	<li id='menu1' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php\"  title=\"".$LANG["job"][13]."\" class='itemP'>".$LANG["Menu"][31]."</a>";
			
			echo "</li>";		
		}
	
		//  Suivi  ticket
		if (haveRight("observe_ticket","1")){
			echo "	<li id='menu2' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user\" title=\"".$LANG["tracking"][0]."\"   class='itemP'>".$LANG["title"][28]."</a>";
			
			echo "</li>";
		}
		// Reservation
		if (haveRight("reservation_helpdesk","1")){
			echo "	<li id='menu3' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.resa.php\"  title=\"".$LANG["Menu"][17]."\" class='itemP'>".$LANG["Menu"][17]."</a>";
			
			echo "</li>";
		}
	
		// FAQ
		if (haveRight("faq","r")){
			echo "	<li id='menu4' >";
			echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/helpdesk.faq.php\" title=\"".$LANG["knowbase"][1]."\" class='itemP'>".$LANG["Menu"][20]."</a>";
			
			echo "</li>";
		}

		// PLUGINS
		$plugins=array();
		if (isset($PLUGIN_HOOKS["helpdesk_menu_entry"])&&count($PLUGIN_HOOKS["helpdesk_menu_entry"]))
			foreach  ($PLUGIN_HOOKS["helpdesk_menu_entry"] as $plugin => $active) {
				if ($active){
					$function="plugin_version_$plugin";
	
					if (function_exists($function))
						$plugins[$plugin]=$function();
				}
			}
	
		if (isset($plugins)&&count($plugins)>0){
			$list=array();
			foreach ($plugins as $key => $val) {
				$list[$key]=$val["name"];
			}
			asort($list);
			echo "	<li id='menu5' onmouseover=\"javascript:menuAff('menu5','menu');\" >";
			echo "<a href='#' title=\"".$LANG["common"][29]."\"  class='itemP'>".$LANG["common"][29]."</a>";  // default none
			echo "<ul class='ssmenu'>"; 
			// list menu item 
			foreach ($list as $key => $val) {
				echo "<li><a href=\"".$CFG_GLPI["root_doc"]."/plugins/".$key."/\">".$plugins[$key]["name"]."</a></li>\n";
			}
			echo "</ul>";
			echo "</li>";
		}
	
			
		echo "</ul>";		
		echo "<div class='sep'></div>";
		echo "</div>";
	
		// End navigation bar
	
		// End headline
		
		///Le sous menu contextuel 1
		echo "<div id='c_ssmenu1' >";
		//echo "<ul>";
		//echo "	<li><a href='' title='' >Suivi</a></li>";
		//echo "	<li>Planning</li>";
		//echo "	<li>Statistique</li>";
		//echo "	<li>Helpdesk</li>";
		//echo "</ul>";
		echo "</div>";

		//  Le fil d arianne 
		echo "<div id='c_ssmenu2' >";
		echo "<ul>";
		echo "	<li><a href='#' title='' >Helpdesk > </a></li>";
		showProfileSelecter($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");	
		echo "</ul>";	
		echo "	</div>";
			
		echo "</div>\n"; // fin header

		echo "<div  id='page' >";
		
	
	
	

	// call function callcron() every 5min
	if (isset($_SESSION["glpicrontimer"])){
		if (($_SESSION["glpicrontimer"]-time())>300){
			callCron();
			$_SESSION["glpicrontimer"]=time();
		}
	} else $_SESSION["glpicrontimer"]=time();

	displayMessageAfterRedirect();
}

/**
 * Print a nice HTML head with no controls
 *
 *
 * @param $title title of the page
 * @param $url not used anymore.
 **/
function nullHeader($title,$url) {
	global $CFG_GLPI,$HEADER_LOADED,$LANG ;
	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;
	// Print a nice HTML-head with no controls

	// Detect root_doc in case of error
	if (!isset($CFG_GLPI["root_doc"])){
		if ( !isset($_SERVER['REQUEST_URI']) ) {
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		}
		$currentdir=getcwd();
		chdir(GLPI_ROOT);
		$glpidir=str_replace(str_replace('\\', '/',getcwd()),"",str_replace('\\', '/',$currentdir));
		chdir($currentdir);
			
		$globaldir=preg_replace("/\/[0-9a-zA-Z\.\-\_]+\.php/","",$_SERVER['REQUEST_URI']);
		$globaldir=preg_replace("/\?.*/","",$globaldir);
		$CFG_GLPI["root_doc"]=str_replace($glpidir,"",$globaldir);
		$CFG_GLPI["root_doc"]=preg_replace("/\/$/","",$CFG_GLPI["root_doc"]);
		$CFG_GLPI["logotxt"]="";
	}

	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");

	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
	}

	// Send extra expires header if configured
	if (!empty($CFG_GLPI["sendexpire"])) {
		header_nocache();
	}

	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
	// Send extra expires header if configured
	if (!empty($cft_features["sendexpire"])) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}

	// Some Javascript-Functions which we may need later

	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

	// AJAX library
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";

	// Appel CSS

	echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
	// surcharge CSS hack for IE
	echo "<!--[if lte IE 6]>" ;
 	echo "<link rel='stylesheet' href='".$CFG_GLPI["root_doc"]."/css/styles_ie.css' type='text/css' media='screen' >\n";
 	echo "<![endif]-->";

	// Calendar scripts 
	if (isset($_SESSION["glpilanguage"])){
		echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][2].".js\"></script>";
		echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";
	}


	// End of Head
	echo "</head>\n";

	// Body with configured stuff
	echo "<body>";

	echo "<div id='contenu-nullHeader'>";

	echo "<div id='text-nullHeader'>";
		
			
		
	
	
}


function popHeader($title,$url)
{
	// Print a nice HTML-head for every page

	global $CFG_GLPI,$LANG,$PLUGIN_HOOKS,$HEADER_LOADED ;

	if ($HEADER_LOADED) return;
	$HEADER_LOADED=true;


	// Override list-limit if choosen
	if (isset($_POST['list_limit'])) {
		$_SESSION['glpilist_limit']=$_POST['list_limit'];
	}



	// Send UTF8 Headers
	header("Content-Type: text/html; charset=UTF-8");
	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		header_nocache();
	}
	// Start the page
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
	echo "\n<html><head><title>GLPI - ".$title."</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8 \" >";
	// Send extra expires header if configured
	if ($CFG_GLPI["sendexpire"]) {
		echo "<meta http-equiv=\"Expires\" content=\"Fri, Jun 12 1981 08:20:00 GMT\">\n";
		echo "<meta http-equiv=\"Pragma\" content=\"no-cache\">\n";
		echo "<meta http-equiv=\"Cache-Control\" content=\"no-cache\">\n";
	}
	//  CSS link
	echo "<link rel='stylesheet'  href='".$CFG_GLPI["root_doc"]."/css/styles.css' type='text/css' media='screen' >";
	echo "<link rel='stylesheet' type='text/css' media='print' href='".$CFG_GLPI["root_doc"]."/css/print.css' >";
	echo "<link rel='shortcut icon' type='images/x-icon' href='".$CFG_GLPI["root_doc"]."/pics/favicon.ico' >";
	// AJAX library
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/prototype.js'></script>";
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/lib/scriptaculous/scriptaculous.js'></script>";
	// Some Javascript-Functions which we may need later
	echo "<script type=\"text/javascript\" src='".$CFG_GLPI["root_doc"]."/script.js'></script>";

	// Calendar scripts 
	echo "<style type=\"text/css\">@import url(".$CFG_GLPI["root_doc"]."/lib/calendar/aqua/theme.css);</style>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar.js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/lang/calendar-".$CFG_GLPI["languages"][$_SESSION["glpilanguage"]][2].".js\"></script>";
	echo "<script type=\"text/javascript\" src=\"".$CFG_GLPI["root_doc"]."/lib/calendar/calendar-setup.js\"></script>";


	// End of Head
	echo "</head>\n";
	// Body 
	echo "<body>";

	displayMessageAfterRedirect();
}


function popFooter() {
	global $FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	// Print foot 

	echo "</body></html>";
}











/**
 * Print footer for every page
 *
 *
 **/
function commonFooter() {
	// Print foot for every page

	global $LANG,$CFG_GLPI,$DEBUG_SQL,$TIMER_DEBUG,$SQL_TOTAL_TIMER,$SQL_TOTAL_REQUEST,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "</div>"; // fin de la div id ='page' initiée dans la fonction header
	
	echo "<div id='footer' >";
	echo "<table width='100%'><tr><td class='left'><span class='copyright'>";
	echo $TIMER_DEBUG->Get_Time()."s - ";
	if (function_exists("memory_get_usage")){
		echo memory_get_usage();
	}
	echo "</span></td>";

	if (!empty($CFG_GLPI["founded_new_version"]))
		echo "<td class='copyright'>".$LANG["setup"][301]." ".$CFG_GLPI["founded_new_version"]."<br>".$LANG["setup"][302]."</td>";

	echo "<td class='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a>";

	echo "</td></tr>";
	echo "</table></div>";



	if ($CFG_GLPI["debug"]==1){ // debug mode traduction

		echo "<div id='debug-float'>";		
		echo "<a href='#see_debug'>GLPI MODE TRANSLATION</a>";
		echo "</div>";
	}

	if ($CFG_GLPI["debug"]==2){ // mode debug 

		echo "<div id='debug-float'>";		
		echo "<a href='#see_debug'>GLPI MODE DEBUG</a>";
		echo "</div>";



		echo "<div id='debug'>";
		echo "<h1><a id='see_debug' name='see_debug'>GLPI MODE DEBUG</a></h1>";
		
		if ($CFG_GLPI["debug_sql"]){	
			echo "<h2>SQL REQUEST : ";
			
			echo $SQL_TOTAL_REQUEST." Queries ";
			if ($CFG_GLPI["debug_profile"]){
				echo "took  ".array_sum($DEBUG_SQL['times'])."s  </h2>";
			}

			echo "<table class='tab_cadre' ><tr><th>N&#176; </th><th>Queries</th><th>Time</th><th>Errors</th></tr>";

			foreach ($DEBUG_SQL['queries'] as $num => $query){
				echo "<tr class='tab_bg_".(($num%2)+1)."'><td>$num</td><td>";
				echo eregi_replace("ORDER BY","<br>ORDER BY",eregi_replace("SORT","<br>SORT",eregi_replace("LEFT JOIN","<br>LEFT JOIN",eregi_replace("WHERE","<br>WHERE",eregi_replace("FROM","<br>FROM",eregi_replace("UNION","<br>UNION<br>",$query))))));
				echo "</td><td>";
				echo $DEBUG_SQL['times'][$num];
				echo "</td><td>";
				if (isset($DEBUG_SQL['errors'][$num])){
					echo $DEBUG_SQL['errors'][$num];
				} else {
					echo "&nbsp;";
				}
				echo "</td></tr>";
			}
			echo "</table>";
		}
		
		
		
		if ($CFG_GLPI["debug_vars"]){
			echo "<h2>POST VARIABLE</h2>";
			printCleanArray($_POST);
			echo "<h2>GET VARIABLE</h2>";
			printCleanArray($_GET);
			echo "<h2>SESSION VARIABLE</h2>";
			printCleanArray($_SESSION);
		}
		
		
		
		echo "</div>";
	}
	echo "</body></html>";
	closeDBConnections();
}

/**
 * Print footer for help page
 *
 *
 **/
function helpFooter() {
	// Print foot for help page
	global $CFG_GLPI,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;
	
	echo "</div>"; // fin de la div id ='page' initiée dans la fonction header

	echo "<div id='footer'>";
	echo "<table width='100%'><tr>";
	echo "<td class='right'>";
	echo "<a href=\"http://glpi-project.org/\">";
	echo "<span class='copyright'>GLPI ".$CFG_GLPI["version"]." Copyright (C) 2003-".date("Y")." by the INDEPNET Development Team.</span>";
	echo "</a></tr></table>";
	echo "</div>";

	echo "</body></html>";
	closeDBConnections();
}

/**
 * Print footer for null page
 *
 *
 **/
function nullFooter() {
	// Print foot for null page
	global $CFG_GLPI,$FOOTER_LOADED;

	if ($FOOTER_LOADED) return;
	$FOOTER_LOADED=true;

	echo "</div>";  // fin box text-nullHeader ouvert dans le null header
	echo "</div>"; // fin contenu-nullHeader ouvert dans le null header
	

	echo "<div id='footer-login'>";
	echo "<a href=\"http://glpi-project.org/\" title=\"Powered By Indepnet\"  >";
	echo 'GLPI version '.(isset($CFG_GLPI["version"])?$CFG_GLPI["version"]:"").' Copyright (C) 2003-'.date("Y").' INDEPNET Development Team.';
	echo "</a>";
	echo "</div>";
	
	
	echo "</body></html>";
	closeDBConnections();
}


/**
 * Print the helpdesk 
 *
 * @param $ID int : ID of the user who want to display the Helpdesk
 * @param $from_helpdesk int : is display from the helpdesk.php ?
 * @return nothing (print the helpdesk)
 */
function printHelpDesk ($ID,$from_helpdesk) {

	global $DB,$CFG_GLPI,$LANG;

	if (!haveRight("create_ticket","1")) return false;

	$query = "SELECT email,realname,firstname,name FROM glpi_users WHERE (ID = '$ID')";
	$result=$DB->query($query);
	$email = $DB->result($result,0,"email");

	// Get saved data from a back system
	$emailupdates = 1;
	if ($email=="") $emailupdates=0;
	$device_type = 0;
	$computer="";
	$contents="";
	$title="";
	$category = 0;


	if (isset($_SESSION["helpdeskSaved"]["emailupdates"]))
		$emailupdates = stripslashes($_SESSION["helpdeskSaved"]["emailupdates"]);
	if (isset($_SESSION["helpdeskSaved"]["email"]))
		$email = stripslashes($_SESSION["helpdeskSaved"]["uemail"]);
	if (isset($_SESSION["helpdeskSaved"]["device_type"]))
		$device_type = stripslashes($_SESSION["helpdeskSaved"]["device_type"]);
	if (isset($_SESSION["helpdeskSaved"]["contents"]))
		$contents = stripslashes($_SESSION["helpdeskSaved"]["contents"]);
	if (isset($_SESSION["helpdeskSaved"]["name"]))
		$title = stripslashes($_SESSION["helpdeskSaved"]["name"]);
	if (isset($_SESSION["helpdeskSaved"]["category"]))
		$category = stripslashes($_SESSION["helpdeskSaved"]["category"]);

	echo "<form method='post' name=\"helpdeskform\" action=\"".$CFG_GLPI["root_doc"]."/front/tracking.injector.php\"  enctype=\"multipart/form-data\">";
	echo "<input type='hidden' name='_from_helpdesk' value='$from_helpdesk'>";
	echo "<input type='hidden' name='request_type' value='1'>";
	echo "<input type='hidden' name='FK_entities' value='".$_SESSION["glpiactive_entity"]."'>";
	echo "<div class='center'><table  class='tab_cadre'>";

	echo "<tr><th colspan='2'>".$LANG["help"][1].":</th></tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td>".$LANG["help"][2].": </td>";
	echo "<td>";
	dropdownPriority("priority",3);
	echo "</td></tr>";
	if($CFG_GLPI["mailing"] != 0)
	{
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["help"][8].":</td>";
		echo "<td>";
		dropdownYesNo('emailupdates',$emailupdates);
		echo "</td></tr>";
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["help"][11].":</td>";
		echo "<td>	<input name='uemail' value=\"$email\" size='50' onchange=\"emailupdates.value='1'\">";
		echo "</td></tr>";
	}

	if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"]!=0){
		echo "<tr class='tab_bg_1'>";
		echo "<td>".$LANG["help"][24].": </td>";
		echo "<td class='center'>";
		dropdownMyDevices($_SESSION["glpiID"]);
		
		dropdownTrackingAllDevices("device_type",$device_type,0,$_SESSION["glpiactive_entity"]);
		echo "</td></tr>";
	}

	echo "<tr class='tab_bg_1'>";
	echo "<td>".$LANG["common"][36].":</td><td>";

	dropdownValue("glpi_dropdown_tracking_category","category",$category);
	echo "</td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'>".$LANG["help"][13].":</td>";
	echo "</tr>";
	echo "<tr class='tab_bg_1'>";
	echo "<td class='center'>".$LANG["common"][57].":</td>";
	echo "<td class='center'><input type='text' maxlength='250' size='80' name='name' value=\"$title\"></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' align='center'><textarea name='contents' cols='80' rows='14' >$contents</textarea>";
	echo "</td></tr>";

	$max_size=return_bytes_from_ini_vars(ini_get("upload_max_filesize"));
	$max_size/=1024*1024;
	$max_size=round($max_size,1);

	echo "<tr class='tab_bg_1'><td>".$LANG["document"][2]." (".$max_size." Mb max):	";
	echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/aide.png\" class='pointer' alt=\"aide\" onclick=\"window.open('".$CFG_GLPI["root_doc"]."/front/typedoc.list.php','Help','scrollbars=1,resizable=1,width=1000,height=800')\">";
	echo "</td>";
	echo "<td><input type='file' name='filename' value=\"\" size='25'></td>";
	echo "</tr>";

	echo "<tr class='tab_bg_1'>";
	echo "<td colspan='2' class='center'> <input type='submit' value=\"".$LANG["help"][14]."\" class='submit'>";
	echo "</td></tr>";

	echo "</table>";
	echo "</div>";
	echo "</form>";

}


/**
 * Print pager for search option (first/previous/next/last)
 *
 *
 *
 * @param $start from witch item we start
 * @param $numrows total items
 * @param $target page would be open when click on the option (last,previous etc)
 * @param $parameters parameters would be passed on the URL.
 * @param $item_type_output item type display - if >0 display export PDF et Sylk form
 * @param $item_type_output item type display - if >0 display export PDF et Sylk form
 * @param $item_type_output_param item type parameter for export
 * @return nothing (print a pager)
 *
 */
function printPager($start,$numrows,$target,$parameters,$item_type_output=0,$item_type_output_param=0) {

	global $CFG_GLPI, $LANG,$CFG_GLPI;

	// Forward is the next step forward
	$forward = $start+$_SESSION["glpilist_limit"];

	// This is the end, my friend	
	$end = $numrows-$_SESSION["glpilist_limit"];

	// Human readable count starts here
	$current_start=$start+1;

	// And the human is viewing from start to end
	$current_end = $current_start+$_SESSION["glpilist_limit"]-1;
	if ($current_end>$numrows) {
		$current_end = $numrows;
	}

	// Backward browsing 
	if ($current_start-$_SESSION["glpilist_limit"]<=0) {
		$back=0;
	} else {
		$back=$start-$_SESSION["glpilist_limit"];
	}

	// Print it

	echo "<table class='tab_cadre_pager'>\n";
	echo "<tr>\n";

	// Back and fast backward button
	if (!$start==0) {
		echo "<th class='left'>";
		echo "<a href=\"$target?$parameters&amp;start=0\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/first.png\" alt='".$LANG["buttons"][33]."' title='".$LANG["buttons"][33]."'>";


		echo "</a></th>\n";
		echo "<th class='left'>";
		echo "<a href=\"$target?$parameters&amp;start=$back\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/left.png\" alt='".$LANG["buttons"][12]."' title='".$LANG["buttons"][12]."'>";
		echo "</a></th>\n";
	}

	// Print the "where am I?" 
	echo "<td width='50%'  class='tab_bg_2'>";
	echo "<form method='POST' action=\"$target?$parameters&amp;start=$start\">\n";
	echo "<span>".$LANG["pager"][4]."&nbsp;</span>";
	echo "<select name='list_limit' onChange='submit()'>";
	for ($i=5;$i<=200;$i+=5) echo "<option value='$i' ".((isset($_SESSION["glpilist_limit"])&&$_SESSION["glpilist_limit"]==$i)?" selected ":"").">$i</option>\n";
	echo "<option value='9999999' ".((isset($_SESSION["glpilist_limit"])&&$_SESSION["glpilist_limit"]==9999999)?" selected ":"").">9999999</option>\n";	
	echo "</select><span>&nbsp;";
	echo $LANG["pager"][5];
	echo "</span>";
	echo "</form>\n";
	echo "</td>\n";

	if ($item_type_output>0&&isset($_SESSION["glpiactiveprofile"])&&$_SESSION["glpiactiveprofile"]["interface"]=="central"){
		echo "<td class='tab_bg_2' width='30%'>" ;
		echo "<form method='GET' action=\"".$CFG_GLPI["root_doc"]."/front/report.dynamic.php\" target='_blank'>\n";
		echo "<input type='hidden' name='item_type' value='$item_type_output'>";
		if ($item_type_output_param!=0)
			echo "<input type='hidden' name='item_type_param' value='".serialize($item_type_output_param)."'>";
		$split=split("&amp;",$parameters);
		for ($i=0;$i<count($split);$i++){
			$pos=strpos($split[$i],'=');
			echo "<input type='hidden' name=\"".substr($split[$i],0,$pos)."\" value=\"".substr($split[$i],$pos+1)."\">";
		}
		echo "<select name='display_type'>";
		echo "<option value='2'>".$LANG["buttons"][27]."</option>";
		echo "<option value='1'>".$LANG["buttons"][28]."</option>";
		echo "<option value='3'>".$LANG["buttons"][44]."</option>";
		echo "<option value='-2'>".$LANG["buttons"][29]."</option>";
		echo "<option value='-1'>".$LANG["buttons"][30]."</option>";
		echo "<option value='-3'>".$LANG["buttons"][45]."</option>";
		echo "</select>";
		echo "&nbsp;<input type='image' name='export'  src='".$CFG_GLPI["root_doc"]."/pics/export.png' title='".$LANG["buttons"][31]."' value='".$LANG["buttons"][31]."'>";
		echo "</form>";
		echo "</td>" ;
	}

	echo "<td  width='50%'  class='tab_bg_2'><strong>";

	echo $LANG["pager"][2]."&nbsp;".$current_start."&nbsp;".$LANG["pager"][1]."&nbsp;".$current_end."&nbsp;".$LANG["pager"][3]."&nbsp;".$numrows."&nbsp;";
	echo "</strong></td>\n";

	// Forward and fast forward button
	if ($forward<$numrows) {
		echo "<th class='right'>";
		echo "<a href=\"$target?$parameters&amp;start=$forward\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/right.png\" alt='".$LANG["buttons"][11]."' title='".$LANG["buttons"][11]."'>";
		echo "</a></th>\n";
		echo "<th class='right'>";
		echo "<a href=\"$target?$parameters&amp;start=$end\">";
		echo "<img src=\"".$CFG_GLPI["root_doc"]."/pics/last.png\" alt='".$LANG["buttons"][32]."' title='".$LANG["buttons"][32]."'>";
		echo "</a></th>\n";
	}

	// End pager
	echo "</tr>\n";
	echo "</table><br>\n";

}


/**
 * Display calendar form
 *
 * @param $form form in which the calendar is display
 * @param $element name of the element
 * @param $value default value to display
 * @param $withtemplate if = 2 only display (add from template) : could not modify element
 * @param $with_time use datetime format instead of date format ?
 * @return nothing
 */
function showCalendarForm($form,$element,$value='',$withtemplate='',$with_time=0){
	global $LANG,$CFG_GLPI;
	$rand=mt_rand();
	if (empty($value)) {
		if ($with_time) $value=date("Y-m-d H:i");
		else 	$value=date("Y-m-d");
	}

	$size=10;
	$dvalue=$value;
	if ($with_time) {
		$size=16;
		$dvalue=convDateTime($value);
	} else {
		$dvalue=convDate($value);
	}
	
	
	echo "<input id='show$rand' type='text' name='____".$element."_show' readonly size='$size' value=\"".$dvalue."\">";
	echo "<input id='data$rand' type='hidden' name='$element' size='$size' value=\"".$value."\">";

	if ($withtemplate!=2){
		echo "&nbsp;<img id='button$rand' src='".$CFG_GLPI["root_doc"]."/pics/calendar.png' class='calendrier' alt='".$LANG["buttons"][15]."' title='".$LANG["buttons"][15]."'>";

		echo "&nbsp;<img src='".$CFG_GLPI["root_doc"]."/pics/reset.png' class='calendrier' onclick=\"document.getElementById('data$rand').value='0000-00-00';document.getElementById('show$rand').value='".convDate("0000-00-00")."'\" alt='Reset' title='Reset'>";	

		echo "<script type='text/javascript'>";
		echo "Calendar.setup(";
		echo "{";
		echo "inputField : 'data$rand',"; // ID of the input field
		if ($with_time){
			echo "ifFormat : '%Y-%m-%d %H:%M',"; // the date format
			echo "showsTime : true,"; 
		}
		else {
			echo "ifFormat : '%Y-%m-%d',"; // the datetime format
		}
		echo "button : 'button$rand'"; // ID of the button
		echo "});";
		echo "</script>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('data$rand', 1, \n";
		echo "      function(element, value) {\n";
		if (!$CFG_GLPI["dateformat"]){
			echo "document.getElementById('show$rand').value=value;";
		} else {
			if ($with_time){
				echo "var d=Date.parseDate(value,'%Y-%m-%d %H:%M');";
				echo "document.getElementById('show$rand').value=d.print('%d-%m-%Y %H:%M');";
			} else {
				echo "var d=Date.parseDate(value,'%Y-%m-%d');";
				echo "document.getElementById('show$rand').value=d.print('%d-%m-%Y');";
			}
		}
		echo "})\n";
		echo "</script>\n";


	}
}

/**
 *  show notes for item
 *
 * @param $target target page to update item
 * @param $type item type of the device to display notes
 * @param $id id of the device to display notes
 * @return nothing
 */
function showNotesForm($target,$type,$id){
	global $LANG;

	if (!haveRight("notes","r")) return false;
	//new objet
	$ci =new CommonItem;
	//getfromdb
	$ci->getfromDB ($type,$id);


	echo "<form name='form' method='post' action=\"".$target."\">";
	echo "<div class='center'>";
	echo "<table class='tab_cadre_fixe' >";
	echo "<tr><th align='center' >";
	echo $LANG["title"][37];
	echo "</th></tr>";
	echo "<tr><td valign='middle' align='center' class='tab_bg_1' ><textarea class='textarea_notes' cols='100' rows='35' name='notes' >".$ci->getField('notes')."</textarea></td></tr>";
	echo "<tr><td class='tab_bg_2' align='center' >\n";
	echo "<input type='hidden' name='ID' value=$id>";
	if (haveRight("notes","w"))
		echo "<input type='submit' name='update' value=\"".$LANG["buttons"][7]."\" class='submit'>";
	echo "</td></tr>\n";
	echo "</table></div></form>";
}

function header_nocache(){
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date du passe
}

function glpi_flush(){
	flush();
	if (function_exists("ob_flush") && ob_get_length () !== FALSE) ob_flush();
}

function displayProgressBar($width,$percent){
	global $LANG;
	$percentwidth=floor($percent*$width/100);
	echo str_pad("<div class='center'><table class='tab_cadre' width='$width'><tr><td width='$width' align='center'> ".$LANG["common"][47]."&nbsp;".$percent."%</td></tr><tr><td><table><tr><td bgcolor='red'  width='$percentwidth' height='20'>&nbsp;</td></tr></table></td></tr></table></div>\n",4096);
	glpi_flush();
}

function printCleanArray($tab,$pad=0){
	if (count($tab)){
		echo "<table class='tab_cadre'>";
		echo "<tr><th>KEY</th><th>=></th><th>VALUE</th></tr>";
		foreach($tab as $key => $val){
			echo "<tr class='tab_bg_1'><td valign='top' align='right'>";
			echo $key;
			echo "</td><td valign='top'>=></td><td valign='top'  class='tab_bg_1'>";
			if (is_array($val)){
				printCleanArray($val,$pad+1);
			}
			else echo $val;
			echo "</td></tr>";
		}
		echo "</table>";
	}
}
// Display a Link to the last page using http_referer if available else use history.back
function displayBackLink(){
	global $LANG;
	if (isset($_SERVER['HTTP_REFERER'])){
		echo "<a href='".$_SERVER['HTTP_REFERER']."'>".$LANG["buttons"][13]."</a>";
	} else {
		echo "<a href='javascript:history.back();'>".$LANG["buttons"][13]."</a>";
	}
}
/**
 *  show onglet for central
 *
 * @param $target 
 * @param $actif
 * @return nothing
 */
function showCentralOnglets($target,$actif) {
	global $LANG,$PLUGIN_HOOKS;
	echo "<div id='barre_onglets'><ul id='onglet'>";
	echo "<li ".($actif=="my"?"class='actif'":"")."><a href='$target?onglet=my'>".$LANG["central"][12]."</a></li>";
	if (haveRight("show_ticket","1")||haveRight("logs","r")||haveRight("contract_infocom","r"))
		echo "<li ".($actif=="global"?"class='actif'":"")."><a href='$target?onglet=global'>".$LANG["central"][13]."</a></li>";
	if (isset($PLUGIN_HOOKS['central_action'])&&count($PLUGIN_HOOKS['central_action'])){
		echo "<li ".($actif=="plugins"?"class='actif'":"")."><a href='$target?onglet=plugins'>".$LANG["common"][29]."</a></li>";
	}
	echo "<li class='invisible'>&nbsp;</li>";
	echo "<li ".($actif=="all"?"class='actif'":"")."><a href='$target?onglet=all'>".$LANG["title"][29]."</a></li>";

	echo "</ul></div>";
}


function showCentralGlobalView(){

	global $CFG_GLPI,$LANG;

	$showticket=haveRight("show_ticket","1");

	
	echo "<table  class='tab_cadre_central' ><tr>";

	echo "<td class='top'>";
	echo "<table >";
	if ($showticket){
		echo "<tr><td class='top'  width='450px'>";
		showCentralJobCount();
		echo "</td></tr>";
	}
	if (haveRight("contract_infocom","r")){
		echo "<tr>";
		echo "<td class='top'  width='450px'>";
		showCentralContract();
		echo "</td>";	
		echo "</tr>";
	}
	echo "</table>";
	echo "</td>";

	if (haveRight("logs","r")){
		echo "<td class='top'>";
		echo "<table><tr>";
		echo "<td aclass='center'>";
		if ($CFG_GLPI["num_of_events"]>0){

			//Show last add events
			showAddEvents($_SERVER['PHP_SELF'],"","",$_SESSION["glpiname"]);

		} else {
			echo "&nbsp;";
		}
		echo "</td></tr>";
		echo "</table>";
		echo "</td>";
	}
	echo "</tr>";

	echo "</table>";
	echo "</div>";


	if ($CFG_GLPI["jobs_at_login"]&&$showticket){
		echo "<br>";

		echo "<div class='center'><strong>";
		echo $LANG["central"][10];
		echo "</strong></div>";

		showTrackingList($_SERVER['PHP_SELF'],$_GET["start"],$_GET["sort"],$_GET["order"],"new");
	}

}

function showCentralMyView(){

		$showticket=haveRight("show_ticket","1");

		
		echo "<table class='tab_cadre_central' >";
		echo "<tr><td class='top'>";
		echo "<table>";
	
		if ($showticket){
			echo "<tr><td class='top'  width='450px'><br>";
			showCentralJobList($_SERVER['PHP_SELF'],$_GET['start']);
			echo "</td></tr>";
			echo "<tr><td   class='top' width='450px'>";
			showCentralJobList($_SERVER['PHP_SELF'],$_GET['start'],"waiting");
			echo "</td></tr>";
		}
	
		echo "</table></td><td class='top'><table><tr>";
	
		echo "<td class='top'  width='450px'><br>";
		ShowPlanningCentral($_SESSION["glpiID"]);
		echo "</td></tr>";
		echo "<tr>";
	
	
		echo "<td  class='top' width='450px'>";
		showCentralReminder();
		echo "</td>";
		echo "</tr>";
	
		if (haveRight("reminder_public","r")){
			echo "<tr><td class='top'  width='450px'>";
			showCentralReminder("public");
			echo "</td></tr>";
		}
	
	
		echo "</table></td></tr></table>";
		


}

function showProfileSelecter($target){
	global $CFG_GLPI;

	if (count($_SESSION["glpiprofiles"])>1){
		echo '<li><form name="form" method="post" action="'.$target.'">';
		echo '<select name="newprofile" onChange="submit()">';
		foreach ($_SESSION["glpiprofiles"] as $key => $val){
			echo '<option value="'.$key.'" '.($_SESSION["glpiactiveprofile"]["ID"]==$key?'selected':'').'>'.$val['name'].'</option>';
		}
		echo '</select>';
		echo '</form>';
		echo "</li>";


	} //else echo "only one profile -> no select to print";
	
/*	if (count($_SESSION['glpiactiveentities'])>1){
		echo "<li>";
			dropdownActiveEntities("activeentity");
		echo "</li>";
	} //else echo "only one entity -> no select to print";
*/	

	if (isMultiEntitiesMode()){
		echo "<li>";
		echo "<a href='#' onclick=\"completecleandisplay('show_entities');\">";
		echo $_SESSION["glpiactive_entity_name"];
		echo "</a>";
		
		echo "<div id='show_entities' onmouseover=\"completecleandisplay('show_entities');\" onmouseout=\"completecleanhide('show_entities');\">";
		displayActiveEntities($target,$_SESSION['glpi_entities_tree'],"activeentity");
		
		echo "</div>";
		echo "</li>";
	}



} 

?>
