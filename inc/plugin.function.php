<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.

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

// Based on cacti plugin system
// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------




global $plugin_hooks;
$plugin_hooks = array();
global $cfg_glpi_plugins;
$cfg_glpi_plugins = array();

function initPlugins(){
	global $phproot;

	$_SESSION["glpi_plugins"]=array();
	$dirplug=$phproot."/plugins";
	$dh  = opendir($dirplug);
	while (false !== ($filename = readdir($dh))) {
		if ($filename!=".svn"&&$filename!="."&&$filename!=".."&&is_dir($dirplug."/".$filename)){
			$_SESSION["glpi_plugins"][]=$filename;
		}
	}

}

function use_plugin ($name) {
	global $phproot,$cfg_glpi;
	if (file_exists($phproot . "/plugins/$name/setup.php")) {
		include_once($phproot . "/plugins/$name/setup.php");
		$function = "plugin_init_$name";

		if (function_exists($function)) {
			$function();
		}
	}
}

/**
 * This function executes a hook.
 * @param $name Name of hook to fire
 * @return mixed $data
 */
function do_hook ($name) {
	global $plugin_hooks;
	$data = func_get_args();

	if (isset($plugin_hooks[$name]) && is_array($plugin_hooks[$name])) {
		foreach ($plugin_hooks[$name] as $function) {
			if (function_exists($function)) {
				$function($data);
			}
		}
	}

	/* Variable-length argument lists have a slight problem when */
	/* passing values by reference. Pity. This is a workaround.  */
	return $data;
}

function do_hook_function($name,$parm=NULL) {
	global $plugin_hooks;
	$ret = $parm;

	if (isset($plugin_hooks[$name])
			&& is_array($plugin_hooks[$name])) {
		foreach ($plugin_hooks[$name] as $function) {
			if (function_exists($function)) {
				$ret = $function($ret);
			}
		}
	}

	/* Variable-length argument lists have a slight problem when */
	/* passing values by reference. Pity. This is a workaround.  */
	return $ret;
}

function display_plugin_action($type,$ID,$onglet,$withtemplate=0){
	global $plugin_hooks;
	// Show all Case
	if ($onglet==-1){
		if (isset($plugin_hooks["headings_action"])&&is_array($plugin_hooks["headings_action"])&&count($plugin_hooks["headings_action"]))	
			foreach ($plugin_hooks["headings_action"] as $plug => $function)
				if (function_exists($function)){

					$actions=$function($type);

					if (is_array($actions)&&count($actions))
						foreach ($actions as $key => $action){
							if (function_exists($action)){
								echo "<br>";
								$action($type,$ID,$withtemplate);
							}	

						}
				}
		return true;

	} else {
		$split=split("_",$onglet);
		if (count($split)==2){
			list($plug,$ID_onglet)=$split;
			if (isset($plugin_hooks["headings_action"][$plug])){
				$function=$plugin_hooks["headings_action"][$plug];
				if (function_exists($function)){

					$actions=$function($type);

					if (isset($actions[$ID_onglet])&&function_exists($actions[$ID_onglet])){
						$function=$actions[$ID_onglet];
						$function($type,$ID,$withtemplate);
						return true;
					}	
				}
			}

		}
	}
	return false;
}

function display_plugin_headings($target,$type,$withtemplate,$actif){
	global $plugin_hooks,$lang;

	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}
	$display_onglets=array();
	if (isset($plugin_hooks["headings"]) && is_array($plugin_hooks["headings"])) {
		foreach ($plugin_hooks["headings"] as $plug => $function) {

			if (function_exists($function)) {
				$onglet=$function($type,$withtemplate);

				if (is_array($onglet)&&count($onglet))
					foreach ($onglet as $key => $val)
						$display_onglets[$plug."_".$key]=$val;
				//echo "<li".(($actif==$plug."_".$key)?" class='actif'":"")."><a href='$target&amp;onglet=".$plug."_".$key."$template'>".$val."</a></li>";
			}
		}
		if (count($display_onglets)){
			echo "<li class='invisible'>&nbsp;</li>";

			echo "<li".(ereg($plug,$actif)?" class='actif'":"")." style='position:relative;'  onmouseout=\"cleanhide('onglet_plugins')\" onmouseover=\"cleandisplay('onglet_plugins')\"><a href='#'>".$lang["common"][29]."</a>";

			echo "<div  id='onglet_plugins' ><dl>";
			foreach ($display_onglets as $key => $val)
				echo "<dt><a href='$target&amp;onglet=".$key.$template."'>".$val."</a></dt>";
			echo "</dl></div>";
			echo "</li>";

		}

	} 

}

function get_plugins_cron(){
	global $plugin_hooks;
	$tasks=array();
	if (isset($plugin_hooks["cron"]) && is_array($plugin_hooks["cron"])) {
		foreach ($plugin_hooks["cron"] as $plug => $time) {
			$tasks["plugin_".$plug]=$time;
		}
	}
	return $tasks;
}

?>
