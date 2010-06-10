<?php
/*
 
 ----------------------------------------------------------------------
GLPI - Gestionnaire libre de parc informatique
 Copyright (C) 2002 by the INDEPNET Development Team.
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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/
// D'aprés PHPmybakcup
//http://www.nm-service.de/phpmybackup
//Copyright (c) 2000-2001 by Holger Mauermann, mauermann@nm-service.de
?>
<?php


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

checkauthentication("admin");


commonHeader("Setup",$PHP_SELF);

// traduction du javascript a faire ...
?>
<script language="JavaScript">
<!--
function dump(what3){
   if (confirm("<?php echo $lang["backup"][15];?> " + what3 +  "?")) {
         window.location = "index.php?dump=" + what3;
   }
}
function restore(what) {
   if (confirm("<?php echo $lang["backup"][16];?> " + what +  "?")) {
         window.location = "index.php?file=" + what;
   }
}

function erase(what2){
   if (confirm("<?php echo $lang["backup"][17];?> " + what2 +  "?")) {
         window.location = "index.php?delfile=" + what2;
   }
}

function xmlnow(what4){
   if (confirm("<?php echo $lang["backup"][18] ;?> " + what4 +  "?")) {
         window.location = "index.php?xmlnow=" + what4;
   }
}


//-->
</script>


<?php

// mySQL - variables

$db = new DB;
$dbhost="$db->dbhost";
$dbuser="$db->dbuser";
$dbpass="$db->dbpassword";
$dbname="$db->dbdefault";



// les deux options qui suivent devraient être incluses dans le fichier de config plutot non ?
// number of backups to keep
$backups = 6;
// 1 only with ZLib support, else change value to 0
$compression = 0;

// full path to phpMyBackup
$path=$phproot."/backups/";



// DO NOT CHANGE THE LINES BELOW


flush();
$conn = mysql_connect($dbhost,$dbuser,$dbpass) or die(mysql_error());
$path = $path . "dump/";
if (!is_dir($path)) mkdir($path, 0777);


// génére un fichier backup.xml a partir de base dbhost connecté avec l'utilisateur dbuser et le mot de passe
//dbpassword sur le serveur dbdefault
function xmlbackup($dbdefault,$dbhost,$dbuser,$dbpassword)
{
//on inclue le fichier contenant la classe XML.
require('genxml.php');





//on parcoure la DB et on liste tous les noms des tables dans $table
//on incremente $query[] de "select * from $table"  pour chaque occurence de $table

$db = new DB;
$result = $db->list_tables();
$i = 0;
while ($line = $db->fetch_array($result))
   {

   $table = $line[0];
   $query[$i] = "select * from ".$table.";";
   $i++;
   }

//le nom du fichier a generer...
//Si fichier existe deja il sera remplacé par le nouveau

$chemin = "dump/backup.xml";

// Creation d'une nouvelle instance de la classe
// et initialisation des variables
$A=new XML();

// Your query
$A->SqlString=$query;

// Name of Database
$A->DB=$dbdefault;

// Database user
$A->DBuser=$dbuser;

// Database Host
$A->DBhost=$dbhost;

// Database password
$A->DBpassword=$dbpassword;

//File path
$A->FilePath = $chemin;


// Type of layout : 1,2,3,4
// For details about Type see file genxml.php
if ($Type=="")
{
	$A->Type=4;
}
else
{
	$A->Type=$Type;
}

//appelle de la methode générant le fichier XML
$A->DoXML();


// Affichage, si erreur affiche erreur
//sinon affiche un lien vers le fichier XML généré.


if ($A->IsError==1)
{
	echo "ERR : ".$A->ErrorString;
}

//fin de fonction xmlbackup
}

function get_def($dbname, $table) {
    global $conn;
    $def = "";
    $def .= "DROP TABLE IF EXISTS $table;#%%\n";
    $def .= "CREATE TABLE $table (\n";
    $result = mysql_db_query($dbname, "SHOW FIELDS FROM $table",$conn);
    while($row = mysql_fetch_array($result)) {
        $def .= "    $row[Field] $row[Type]";
        if ($row["Default"] != "") $def .= " DEFAULT '$row[Default]'";
        if ($row["Null"] != "YES") $def .= " NOT NULL";
       	if ($row[Extra] != "") $def .= " $row[Extra]";
        	$def .= ",\n";
     }
     $def = ereg_replace(",\n$","", $def);
     $result = mysql_db_query($dbname, "SHOW KEYS FROM $table",$conn);
     while($row = mysql_fetch_array($result)) {
          $kname=$row[Key_name];
          if(($kname != "PRIMARY") && ($row[Non_unique] == 0)) $kname="UNIQUE|$kname";
          if(!isset($index[$kname])) $index[$kname] = array();
          $index[$kname][] = $row[Column_name];
     }
     while(list($x, $columns) = @each($index)) {
          $def .= ",\n";
          if($x == "PRIMARY") $def .= "   PRIMARY KEY (" . implode($columns, ", ") . ")";
          else if (substr($x,0,6) == "UNIQUE") $def .= "   UNIQUE ".substr($x,7)." (" . implode($columns, ", ") . ")";
          else $def .= "   KEY $x (" . implode($columns, ", ") . ")";
     }

     $def .= "\n);#%%";
     return (stripslashes($def));
}

function get_content($dbname, $table) {
     global $conn;
     $content="";
     $result = mysql_db_query($dbname, "SELECT * FROM $table",$conn);
     while($row = mysql_fetch_row($result)) {
         $insert = "INSERT INTO $table VALUES (";
         for($j=0; $j<mysql_num_fields($result);$j++) {
            if(!isset($row[$j])) $insert .= "NULL,";
            else if($row[$j] != "") $insert .= "'".addslashes($row[$j])."',";
            else $insert .= "'',";
         }
         $insert = ereg_replace(",$","",$insert);
         $insert .= ");#%%\n";
         $content .= $insert;
     }
     return $content;
}

if ($compression==1) $filetype = "sql.gz";
else $filetype = "sql";

// #################" DUMP sql#################################

if ($dump!=""){


 $time_file=date("Y-m-d-h-i");
	$cur_time=date("Y-m-d H:i");
	$newfile="#GLPI Dump database on $cur_time\r\n";
	$tables = mysql_list_tables($dbname,$conn);
	$num_tables = @mysql_num_rows($tables);
	$i = 0;
	while($i < $num_tables) {
	   $table = mysql_tablename($tables, $i);
	
	   $newfile .= "\n# ----------------------------------------------------------\n#\n";
	   $newfile .= "# ".$lang["backup"][6]." '$table' \n#\n";
	   $newfile .= get_def($dbname,$table);
	   $newfile .= "\n\n";
	   $newfile .= "#\n# ".$lang["backup"][7]." '$table' \n#\n";
	   $newfile .= get_content($dbname,$table);
	   $newfile .= "\n\n";
	   $i++;
	}
	
	if ($compression==1) {
		$fp = gzopen($path."$time_file.$filetype","w");
		gzwrite ($fp,$newfile);
		gzclose ($fp);
	} else {
		$fp = fopen ($path."$time_file.$filetype","w");
		fwrite ($fp,$newfile);
		fclose ($fp);
	}
}

// ##############################   fin dump sql########################""""


// ################################## dump XML #############################

if ($xmlnow!=""){

xmlbackup($dbname, $dbhost, $dbuser, $dbpass);


}
// ################################## fin dump XML #############################



if ($file!="") {
	$filename = $file;
	set_time_limit(180);
	if ($compression ==1) $file=gzread(gzopen($path.$file, "r"), 10485760);
	else $file=fread(fopen($path.$file, "r"), 10485760);
	$query=explode(";#%%\n",$file);
	for ($i=0;$i < count($query)-1;$i++) {
		mysql_db_query($dbname,$query[$i],$conn) or die(mysql_error());
	}
	echo "<center>".$filename." ".$lang["backup"][8]."</center>";
}

if ($delfile!=""){

   $filename=$delfile;

   unlink($path.$delfile);




   echo "<center>".$filename." ".$lang["backup"][9]."</center>";

}


?>
<div align="center">

 <a href="javascript:dump('<?php echo $lang["backup"][19];?>')"><b><?php echo $lang["backup"][0]; ?></b></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="javascript:xmlnow('<?php echo $lang["backup"][19]; ?>')"><b><?php echo $lang["backup"][1]; ?></b></a>
<br />
<br />
  <table border="0" cellpadding="5">
    <tr align="center"> 
      <th><u><i><?php echo $lang["backup"][10]; ?></i></u></th>
      <th><u><i><?php echo $lang["backup"][11]; ?></i></u></th>
      <th><u><i><?php echo $lang["backup"][12]; ?></i></u></th>
    <th colspan='3'>&nbsp;</th>
    </tr>
    <?
	$dir=opendir($path); 
	while ($file = readdir ($dir)) { 
	    if ($file != "." && $file != ".." && eregi("\.sql",$file)) { 
	        echo "<tr><td>$file&nbsp;</td>
	        	<td align=\"right\">&nbsp;" . bcdiv(filesize($path.$file),1024,1) . " kB&nbsp;</td>
	        	<td>&nbsp;" . date("Y-m-d H:i",filemtime($path.$file)) . "</td>
	       		<td>&nbsp;<a href=\"javascript:erase('$file')\">".$lang["backup"][20]."</a>&nbsp;</td>

			<td>&nbsp;<a href=\"javascript:restore('$file')\">".$lang["backup"][14]."</a>&nbsp;</td>
	        	<td>&nbsp;<a href=\"dump/$file\">".$lang["backup"][13]."</a></td>&nbsp;</tr>";
	    }
	      }
closedir($dir);
$dir=opendir($path);
	while ($file = readdir ($dir)) {
	    if ($file != "." && $file != ".." && eregi("\.xml",$file)) {
	       echo "
	   	        <tr ><td colspan='6' ><hr noshade></td></tr>
	   	    	<tr><td>$file&nbsp;</td>
	        	<td align=\"right\">&nbsp;" . bcdiv(filesize($path.$file),1024,1) . " kB&nbsp;</td>
	        	<td>&nbsp;" . date("Y-m-d H:i",filemtime($path.$file)) . "</td>
	       		<td>&nbsp;<a href=\"javascript:erase('$file')\">".$lang["backup"][20]."</a>&nbsp;</td>
                         	<td>&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;</td>

	        	<td>&nbsp;<a href=\"dump/$file\">".$lang["backup"][13]."</a></td>&nbsp;</tr>";
	    }
	}
	closedir($dir);
?>
  </table>
</div>
<?php

commonFooter();
?>




