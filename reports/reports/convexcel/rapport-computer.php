<?php

/*
 
  ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
 Bazile Lebeau, baaz@indepnet.net - Jean-Mathieu Doléans, jmd@indepnet.net
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
    
    Based on :   The example witch come with :
    Spreadsheet::WriteExcel written by John McNamara, jmcnamara@cpan.org
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------


*/



include ("_relpos.php");

include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_computers.php");

checkAuthentication("normal");

$db = new DB;
$query = "select glpi_computers.*, glpi_networking_ports.ifaddr, glpi_networking_ports.ifmac ";
$query.= "from glpi_computers LEFT JOIN glpi_networking_ports ON (glpi_networking_ports.device_type = 1 AND glpi_networking_ports.on_device = glpi_computers.ID) ORDER by glpi_computers.ID";
$result = $db->query($query);
$num_field = $db->num_fields($result);
//set_time_limit(10);

require_once "class.writeexcel_workbook.inc.php";
require_once "class.writeexcel_worksheet.inc.php";

$fname = tempnam("tmp/", "merge2.xls");
$workbook = &new writeexcel_workbook($fname);
$worksheet = &$workbook->addworksheet();

# Set the column width for columns 2 and 3
$worksheet->set_column(1, 2, 20);

# Set the row height for row 2
$worksheet->set_row(2, 30);

# Create a border format
$border1 =& $workbook->addformat();
$border1->set_color('white');
$border1->set_bold();
$border1->set_size(15);
$border1->set_pattern(0x1);
$border1->set_fg_color('green');
$border1->set_border_color('yellow');
$border1->set_top(6);
$border1->set_bottom(6);
$border1->set_left(6);
$border1->set_align('center');
$border1->set_align('vcenter');
$border1->set_merge(); # This is the key feature


/*
# Create another border format. Note you could use copy() here.
$border2 =& $workbook->addformat();
$border2->set_color('white');
$border2->set_bold();
$border2->set_size(15);
$border2->set_pattern(0x1);
$border2->set_fg_color('green');
$border2->set_border_color('yellow');
$border2->set_top(6);
$border2->set_bottom(6);
$border2->set_right(6);
$border2->set_align('center');
$border2->set_align('vcenter');
$border2->set_merge(); # This is the key feature
*/

# Only one cell should contain text, the others should be blank.
$worksheet->write(0, 0, html_entity_decode($lang["computers"][1]), $border1);
$worksheet->write(0, 1, html_entity_decode($lang["computers"][7]),   $border1);
$worksheet->write(0, 2, html_entity_decode($lang["computers"][28]), $border1);
$worksheet->write(0, 3, html_entity_decode($lang["computers"][20]),  $border1);
$worksheet->write(0, 4, html_entity_decode($lang["computers"][22]),  $border1);
$worksheet->write(0, 5, html_entity_decode($lang["computers"][17]), $border1);
$worksheet->write(0, 6, html_entity_decode($lang["computers"][18]), $border1);
$worksheet->write(0, 7, html_entity_decode($lang["computers"][23]), $border1);
$worksheet->write(0, 8, html_entity_decode($lang["computers"][25]), $border1);
$worksheet->write(0, 9, html_entity_decode($lang["computers"][16]), $border1);
$worksheet->write(0, 10, html_entity_decode($lang["computers"][15]), $border1);
$worksheet->write(0, 11, html_entity_decode($lang["computers"][19]), $border1);
$worksheet->write(0, 12, html_entity_decode($lang["computers"][11]), $border1);
$worksheet->write(0, 13, html_entity_decode($lang["computers"][41]), $border1);
$worksheet->write(0, 14, html_entity_decode($lang["computers"][42]), $border1);
$worksheet->write(0, 15, html_entity_decode($lang["computers"][43]), $border1);
$worksheet->write(0, 16, html_entity_decode($lang["computers"][9]), $border1);
$worksheet->write(0, 17, html_entity_decode($lang["computers"][36]), $border1);
$worksheet->write(0, 18, html_entity_decode($lang["computers"][33]), $border1);
$worksheet->write(0, 19, html_entity_decode($lang["computers"][35]), $border1);
$worksheet->write(0, 20, html_entity_decode($lang["computers"][34]), $border1);
$worksheet->write(0, 21, html_entity_decode($lang["computers"][26]), $border1);
$worksheet->write(0, 22, html_entity_decode($lang["computers"][23]), $border1);
$worksheet->write(0, 23, html_entity_decode($lang["computers"][10]), $border1);
$worksheet->write(0, 24, html_entity_decode($lang["computers"][21]), $border1);
$worksheet->write(0, 25, html_entity_decode($lang["computers"][8]), $border1);
$worksheet->write(0, 26, html_entity_decode($lang["networking"][14]), $border1);
$worksheet->write(0, 27, html_entity_decode($lang["networking"][15]), $border1);

$y=1;
$old_ID=-1;
$nb_skip=0; // Skip multiple interface computers
$table=array();
while($ligne = $db->fetch_array($result))
{
	// New computer
	if ($old_ID!=$ligne[0]){
		// reinit data
		for($i=0;$i<$num_field;$i++) {
			$name=$db->field_name($result,$i);
		if (IsDropdown($name)) $table[$i]=html_entity_decode(getDropdownName("glpi_dropdown_".$name,$ligne[$i]));
		elseif($name == "ramtype") {
				$table[$i]=html_entity_decode(getDropdownName("glpi_dropdown_ram",$ligne[$i]));
			}
		elseif($name == "location") {
				$table[$i]=html_entity_decode(getDropdownName("glpi_dropdown_locations",$ligne[$i]));
			}
		elseif($name == "type") {
				$table[$i]=html_entity_decode(getDropdownName("glpi_type_computers",$ligne[$i]));
			}
		else $table[$i]=html_entity_decode($ligne[$i]);
		}
		$old_ID=$ligne[0];
	} 
	else { // Same computer
		$nb_skip++;
		// Add the new interface :
		for($i=$num_field-2;$i<$num_field;$i++)
		if ($ligne[$i]!="") $table[$i].="\n".html_entity_decode($ligne[$i]);
	}
	$worksheet->write_row($y-$nb_skip, 0, $table);
	
	$y++;
}
$workbook->close();

header("Content-Type: application/vnd.ms-excel");
$fh=fopen($fname, "rb");
fpassthru($fh);
unlink($fname);

?>
