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

function ajaxUpdateOnInputTextEvent($inputtexttoobserve,$toupdate,$url,$parameters=array(),$search_spinner=""){


	// Prototype
	echo "<script type='text/javascript' >\n";
	echo "   new Form.Element.Observer('$inputtexttoobserve', 1, \n";
	echo "      function(element, value) {\n";
	echo "      	new Ajax.Updater('$toupdate','$url',{asynchronous:true, evalScripts:true, \n";
	if (!empty($search_spinner)){
		echo "           onComplete:function(request)\n";
		echo "            {Element.hide('$search_spinner');}, \n";
		echo "           onLoading:function(request)\n";
		echo "            {Element.show('$search_spinner');},\n";
	}
	echo "           method:'post'";
	if (count($parameters)){
		echo ", parameters:'";
		$first=true;
		foreach ($parameters as $key => $val){
			if ($first){
				$first=false;
			} else {
				echo "&";
			}
			echo $key."=";
			if ($val=="__VALUE__"){
				echo "'+value+'";
			} else {
				echo $val;
			}
		}
		echo "'";
	}
	echo "})})\n";
	echo "</script>\n";

	// JQUERY
/*	echo "<script type='text/javascript' >\n";
		echo "function update$toupdate(){\n";
		echo "$.ajax({ url :\"$url\", \n";
		echo "type: \"POST\",\n";
		echo "success: function(data){\n";
			if (!empty($search_spinner)){
				echo "$(\"#$search_spinner\").hide();\n";
			}
			echo "$(\"#$toupdate\").html(data);\n";
		echo "},\n";
		if (!empty($search_spinner)){
			echo "beforeSend: function(){\n";
			echo "$(\"#$search_spinner\").show();\n";
			echo "},\n";
		}
		if (count($parameters)){
			echo "data: 	{\n";
			foreach ($parameters as $key => $val){
				echo "$key: ";
				if ($val=="__VALUE__"){
					echo "$(\"#$inputtexttoobserve\").val()";
				} else {
					echo "\"".$val."\"";
				}
				echo ",\n";
			}
			echo "}";
		}
		echo "})};";
		echo "$(\"#$inputtexttoobserve\").dblclick(update$toupdate);\n";
		echo "$(\"#$inputtexttoobserve\").keyup(update$toupdate);\n";
	echo "</script>\n";
*/
	
}
?>