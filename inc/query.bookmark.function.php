<?php

/*
 * @version $Id: cron.class.php 6235 2008-01-02 17:57:10Z moyo $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2008 by the INDEPNET Development Team.

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
 function exportSearchParameters($input,$search_parameters)
 {
 	$simple_params[] = "start";
 	$simple_params[] = "deleted";
 	$simple_params[] = "distinct";
 	$simple_params[] = "deleted";
 	$simple_params[] = "type2";
 	$simple_params[] = "sort";
 	
 	$array_params[] = "link";
 	$array_params[] = "field";
 	$array_params[] = "contains";
 	$array_params[] = "link2";
 	$array_params[] = "field2";
 	$array_params[] = "contains2";
 	
 	//Create an array with all the one field only parameters (ie not array) 
 	$simple_array=array();
 	foreach ($search_parameters as $param => $value)
 	{
 		if (in_array($param,$simple_params))
 			$simple_array[$param] = $value;
 	}
 	
 	$input["params"] = exportArrayToDB($simple_array);
 	
 	foreach ($array_params as $param)
 	{
 		if (isset($search_parameters[$param]) && count($search_parameters[$param]))
 			$input[$param] = exportArrayToDB($search_parameters[$param]);
 			
 	}	
 	return $input;
}

function buildRequestUrl($fields)
{
	global $SEARCH_PAGES;

 	$array_params[] = "params"; 	
 	$array_params[] = "link";
 	$array_params[] = "field";
 	$array_params[] = "contains";
 	$array_params[] = "link2";
 	$array_params[] = "field2";
 	$array_params[] = "contains2";
 	
	$url = GLPI_ROOT."/".$SEARCH_PAGES[$fields['type']];
	$first = true;

	foreach ($array_params as $parameter)
	{
		if (isset($fields[$parameter]) && $fields[$parameter] != '')
		{
			$is_params = ($parameter == "params");

			$params = importArrayFromDB($fields[$parameter]);

			foreach ($params as $id=>$value)
			{
				if ($first)
				{
					$url.="?";
					$first=false;
				}
				else
					$url.="&";
					
				$url.=urlencode((!$is_params?$parameter.'['.$id.']':$id)).'='.urlencode($value);
			}
		}
	}
	
	return $url;
}
?>
