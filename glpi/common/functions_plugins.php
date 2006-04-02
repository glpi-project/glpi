<?php
/*
 * @version $Id$
 ----------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2006 by the INDEPNET Development Team.
 
 http://indepnet.net/   http://glpi.indepnet.org
 ----------------------------------------------------------------------

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
 ------------------------------------------------------------------------
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
 * @param string $name Name of hook to fire
 * @return mixed $data
 */
function do_hook ($name) {
    global $plugin_hooks;
    $data = func_get_args();
    $ret = '';

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
	return false;
}

function display_plugin_headings($target,$type,$withtemplate,$actif){
	global $plugin_hooks;
	
	$template="";
	if(!empty($withtemplate)){
		$template="&amp;withtemplate=$withtemplate";
	}
	if (isset($plugin_hooks["headings"]) && is_array($plugin_hooks["headings"])) {
        	foreach ($plugin_hooks["headings"] as $plug => $function) {
		
            	if (function_exists($function)) {
	                $onglet=$function($type,$withtemplate);
			
			if (is_array($onglet)&&count($onglet))
			foreach ($onglet as $key => $val)
				echo "<li".(($actif==$plug."_".$key)?" class='actif'":"")."><a href='$target&amp;onglet=".$plug."_".$key."$template'>".$val."</a></li>";
        	    }
        	}
    	}

}

?>
