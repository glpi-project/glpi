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
 ----------------------------------------------------------------------
 Original Author of file:
 Purpose of file:
 ----------------------------------------------------------------------
*/

?>
<?php
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

checkauthentication("super-admin");

commonHeader("Setup",$_SERVER["PHP_SELF"]);


$max_time=min(get_cfg_var("max_execution_time"),get_cfg_var("max_input_time"));
if ($max_time>5) {$defaulttimeout=$max_time-2;$defaultrowlimit=5;}
else {$defaulttimeout=1;$defaultrowlimit=2;}



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
$dbhost=$db->dbhost;
$dbuser=$db->dbuser;
$dbpass=$db->dbpassword;
$dbname=$db->dbdefault;



// les deux options qui suivent devraient être incluses dans le fichier de config plutot non ?
// 1 only with ZLib support, else change value to 0
$compression = 0;

// full path to phpMyBackup
$path=$phproot."/backups/";


if ($compression==1) $filetype = "sql.gz";
else $filetype = "sql";

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
if (empty($Type))
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
////////////////////////// DUMP SQL FUNCTIONS
function init_time() 
{
    global $TPSDEB,$TPSCOUR;
    
    
    list ($usec,$sec)=explode(" ",microtime());
    $TPSDEB=$sec;
    $TPSCOUR=0;

}

function current_time() 
{
    global $TPSDEB,$TPSCOUR;
    list ($usec,$sec)=explode(" ",microtime());
    $TPSFIN=$sec;
    if (round($TPSFIN-$TPSDEB,1)>=$TPSCOUR+1) //une seconde de plus
    {
    $TPSCOUR=round($TPSFIN-$TPSDEB,1);
    flush();
    }

}

function get_content($db, $table,$from,$limit)
{
     $content="";
     $result = $db->query("SELECT * FROM $table LIMIT $from,$limit");
     if($result)
     while($row = $db->fetch_row($result)) {
     	$row=addslashes_deep($row);
         $insert = "INSERT INTO $table VALUES (";
         for($j=0; $j<$db->num_fields($result);$j++) {
            if(!isset($row[$j])) $insert .= "NULL,";
            else if($row[$j] != "") $insert .= "'".addslashes($row[$j])."',";
            else $insert .= "'',";
         }
         $insert = ereg_replace(",$","",$insert);
         $insert .= ");\n";
         $content .= $insert;
     }
     return $content;
}


function get_def($db, $table) {
    $def = "### Dump table $table\n\n";
    $def .= "DROP TABLE IF EXISTS $table;\n";
    $def .= "CREATE TABLE $table (\n";
    $result = $db->query("SHOW FIELDS FROM $table");
    while($line = $db->fetch_array($result)) {
    	$line=stripslashes_deep($line);
        $def .= "    $line[Field] $line[Type]";
        if (isset($line["Default"]) && $line["Default"] != "") $def .= " DEFAULT '$line[Default]'";
        if (isset($line["Null"]) && $line["Null"] != "YES") $def .= " NOT NULL";
       	if (isset($line["Extra"]) && $line["Extra"] != "") $def .= " $line[Extra]";
        	$def .= ",\n";
     }
     $def = ereg_replace(",\n$","", $def);
     $result = $db->query("SHOW KEYS FROM $table");
     while($line = $db->fetch_array($result)) {
          $kname=$line["Key_name"];
          if(($kname != "PRIMARY") && ($line["Non_unique"] == 0)) $kname="UNIQUE|$kname";
          if(!isset($index[$kname])) $index[$kname] = array();
          $index[$kname][] = $line["Column_name"];
     }
     while(list($x, $columns) = @each($index)) {
          $def .= ",\n";
          if($x == "PRIMARY") $def .= "   PRIMARY KEY (" . implode($columns, ", ") . ")";
          else if (substr($x,0,6) == "UNIQUE") $def .= "   UNIQUE ".substr($x,7)." (" . implode($columns, ", ") . ")";
          else $def .= "   KEY $x (" . implode($columns, ", ") . ")";
     }

     $def .= "\n);\n\n";
     return $def;
}


function restoreMySqlDump($db,$dumpFile , $duree)
{
// $dumpFile, fichier source
// $database, nom de la base de données cible
// $mysqlUser, login pouyr la connexion au serveur MySql
// $mysqlPassword, mot de passe
// $histMySql, nom de la machine serveur MySQl
// $duree=timeout pour changement de page (-1 = aucun)

// Desactivation pour empecher les addslashes au niveau de la creation des tables
set_magic_quotes_runtime(0);

global $TPSCOUR,$offset,$cpt;
$db=new DB;

if ($db->error)
{
     echo "Connexion impossible à $hostMySql pour $mysqlUser";
     return FALSE;
}

if(!file_exists($dumpFile))
{
     echo "$dumpFile non trouvé<br>";
     return FALSE;
}
$fileHandle = fopen($dumpFile, "rb");

if(!$fileHandle)
{
    echo "Ouverture de $dumpFile non trouvé<br>";
    return FALSE;
}

if ($offset!=0)
{
     if (fseek($fileHandle,$offset,SEEK_SET)!=0) //erreur
     {
        echo "Impossible de trouver l'octet ".number_format($offset,0,""," ")."<br>";
        return FALSE;
     }
    flush();
}
    
$formattedQuery = "";

while(!feof($fileHandle))
{
    current_time();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;

//    echo $TPSCOUR."<br>";
    $buffer=fgets($fileHandle);
    if (substr($buffer,strlen($buffer),1)==0)
        $buffer=substr($buffer,0,strlen($buffer)-1);
    
    if(substr($buffer, 0, 1) != "#")
    {
        $formattedQuery .= $buffer;
        if (substr($formattedQuery,-1)==";")
        // Do not use the $db->query 
        if (mysql_query($formattedQuery)) //réussie sinon continue à conca&téner
        {
            $offset=ftell($fileHandle);
            $formattedQuery = "";
            $cpt++;
        }
		
    }
    
}

if ($db->error)
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".mysql_error()."<hr>";

fclose($fileHandle);
$offset=-1;
return TRUE;
}

function backupMySql($db,$dumpFile, $duree,$rowlimit)
{
// $dumpFile, fichier source
// $database, nom de la base de données cible
// $mysqlUser, login pouyr la connexion au serveur MySql
// $mysqlPassword, mot de passe
// $histMySql, nom de la machine serveur MySQl
// $duree=timeout pour changement de page (-1 = aucun)

global $TPSCOUR,$offsettable,$offsetrow,$cpt;
if ($db->error)
{
     echo "Connexion impossible à $hostMySql pour $mysqlUser";
     return FALSE;
}

$fileHandle = fopen($dumpFile, "a");

if(!$fileHandle)
{
    echo "Ouverture de $dumpFile impossible<br>";
    return FALSE;
}

if ($offsettable==0&&$offsetrow==-1){
 	$time_file=date("Y-m-d-h-i");
	$cur_time=date("Y-m-d H:i");
	$todump="#GLPI Dump database on $cur_time\n";
	fwrite ($fileHandle,$todump);

}

$result=$db->list_tables();
$numtab=0;
while ($t=$db->fetch_array($result)){
	$tables[$numtab]=$t[0];
$numtab++;
}


for (;$offsettable<$numtab;$offsettable++){
// Dump de la strucutre table
if ($offsetrow==-1){
	$todump=get_def($db,$tables[$offsettable]);
	fwrite ($fileHandle,$todump);
	$offsetrow++;
	$cpt++;
	}
    current_time();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;

	$fin=0;
	while (!$fin){
	$todump=get_content($db,$tables[$offsettable],$offsetrow,$rowlimit);
	$rowtodump=substr_count($todump, "INSERT INTO");
	if ($rowtodump>0){
	fwrite ($fileHandle,$todump);
	$cpt+=$rowtodump;
	$offsetrow+=$rowlimit;
	if ($rowtodump<$rowlimit) $fin=1;
    current_time();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;

	}
	else {$fin=1;$offsetrow=-1;}
	}
	if ($fin) $offsetrow=-1;
    current_time();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;
	
}
if (mysql_error())
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".mysql_error()."<hr>";
$offsettable=-1;
fclose($fileHandle);
return TRUE;
}


// #################" DUMP sql#################################

if (isset($_GET["dump"]) && $_GET["dump"] != ""){

    $time_file=date("Y-m-d-h-i");
	$cur_time=date("Y-m-d H:i");
	$filename=$path."$time_file.$filetype";

if (!isset($_GET["duree"])&&is_file($filename)){
echo "<center>Le fichier existe déjà</center>";
} else {
init_time(); //initialise le temps
//début de fichier
if (!isset($_GET["offsettable"])) $offsettable=0; 
else $offsettable=$_GET["offsettable"]; 
//début de fichier
if (!isset($_GET["offsetrow"])) $offsetrow=-1; 
else $offsetrow=$_GET["offsetrow"];
//timeout de 5 secondes par défaut, -1 pour utiliser sans timeout
if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
else $duree=$_GET["duree"];
//Limite de lignes à dumper à chaque fois
if (!isset($_GET["rowlimit"])) $rowlimit=$defaultrowlimit; 
else  $rowlimit=$_GET["rowlimit"];

 //si le nom du fichier n'est pas en paramètre le mettre ici
if (!isset($_GET["fichier"])) {
	$fichier=$filename;
} else $fichier=$_GET["fichier"];
	
$tab=$db->list_tables();
$tot=$db->numrows($tab);
if(isset($offsettable)){
if ($offsettable>=0)
$percent=min(100,round(100*$offsettable/$tot,0));
else $percent=100;
}
else $percent=0;

if ($percent >= 0) {
 
 $percentwitdh=$percent*4;

	echo "<div align='center'><table class='tab_cadre' width='400'><tr><td width='400' align='center'> Progression ".$percent."%</td></tr><tr><td><table><tr><td bgcolor='red'  width='$percentwitdh' height='20'>&nbsp;</td></tr></table></td></tr></table></div>";


}

flush();

if ($offsettable>=0){
if (backupMySql($db,$fichier,$duree,$rowlimit))
{
    echo "<br>Redirection automatique sinon cliquez <a href=\"index.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\">ici</a>";
    echo "<script>window.location=\"index.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\";</script>";
	flush();    
	exit;

}
}
else  { //echo "<div align='center'><p>Terminé. Nombre de requêtes totales traitées : $cpt</p></div>";

}

}	
}

// ##############################   fin dump sql########################""""




// ################################## dump XML #############################

if (isset($_GET["xmlnow"]) && $_GET["xmlnow"] !=""){

xmlbackup($dbname, $dbhost, $dbuser, $dbpass);


}
// ################################## fin dump XML #############################



if (isset($_GET["file"]) && $_GET["file"] != ""&&is_file($path.$_GET["file"])) {

init_time(); //initialise le temps
//début de fichier
if (!isset($_GET["offset"])) $offset=0;
else  $offset=$_GET["offset"];
//timeout de 5 secondes par défaut, -1 pour utiliser sans timeout
if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
else $duree=$_GET["duree"];

$fsize=filesize($path.$_GET["file"]);
if(isset($offset)){
if ($offset==-1)
$percent=100;
else $percent=min(100,round(100*$offset/$fsize,0));
}
else $percent=0;

if ($percent >= 0) {
      	
$percentwitdh=$percent*4;

	echo "<div align='center'><table class='tab_cadre' width='400'><tr><td width='400' align='center'> Progression ".$percent."%</td></tr><tr><td><table><tr><td bgcolor='red'  width='$percentwitdh' height='20'>&nbsp;</td></tr></table></td></tr></table></div>";


}
flush();
if ($offset!=-1){
if (restoreMySqlDump($db,$path.$_GET["file"],$duree))
{
    echo "<br>Redirection automatique sinon cliquez <a href=\"index.php?file=".$_GET["file"]."&duree=$duree&offset=$offset&cpt=$cpt\">ici</a>";
    echo "<script>window.location=\"index.php?file=".$_GET["file"]."&duree=$duree&offset=$offset&cpt=$cpt\";</script>";
	flush();
	exit;
}
} else   { //echo "<div align='center'><p>Terminé. Nombre de requêtes totales traitées : $cpt<p></div>";
}


}

if (isset($_GET["delfile"]) && $_GET["delfile"] != ""){

   $filename=$_GET["delfile"];

   unlink($path.$_GET["delfile"]);




   echo "<center>".$filename." ".$lang["backup"][9]."</center>";

}

// Title backup
echo " <div align='center'> <table border='0'><tr><td><b><img src=\"". $HTMLRel."pics/sauvegardes.png\"></td> <td><a href=\"javascript:dump('".$lang["backup"][19]."')\"  class='icon_consol'><b>". $lang["backup"][0]."</b></a></td><td><a href=\"javascript:xmlnow('".$lang["backup"][19]."')\" class='icon_consol'><b>". $lang["backup"][1]."</b></a></td></tr></table>";


?>


 

<br>
  <table class='tab_cadre'  cellpadding="5">
    <tr align="center"> 
      <th><u><i><?php echo $lang["backup"][10]; ?></i></u></th>
      <th><u><i><?php echo $lang["backup"][11]; ?></i></u></th>
      <th><u><i><?php echo $lang["backup"][12]; ?></i></u></th>
    <th colspan='3'>&nbsp;</th>
    </tr>
    <?php
	$dir=opendir($path); 
	while ($file = readdir ($dir)) { 
	    if ($file != "." && $file != ".." && eregi("\.sql",$file)) { 
	    	$taille_fic = filesize($path.$file)/1024;
		$taille_fic = (int)$taille_fic;
	        echo "<tr class='tab_bg_2'><td>$file&nbsp;</td>
	        	<td align=\"right\">&nbsp;" . $taille_fic . " kB&nbsp;</td>
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
	        $taille_fic = filesize($path.$file)/1024;
		$taille_fic = (int)$taille_fic;
	        echo "
	   	        <tr class='tab_bg_1'><td colspan='6' ><hr noshade></td></tr>
	   	    	<tr class='tab_bg_2'><td>$file&nbsp;</td>
	        	<td align=\"right\">&nbsp;" . $taille_fic . " kB&nbsp;</td>
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




