<?php
/*
 * @version $Id$
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

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------




$NEEDED_ITEMS=array("xml");
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

checkRight("backup","w");

// full path 
$path = GLPI_DUMP_DIR ;



commonHeader($LANG["common"][12],$_SERVER['PHP_SELF'],"admin","backup");


$max_time=min(get_cfg_var("max_execution_time"),get_cfg_var("max_input_time"));
if ($max_time==0) {$defaulttimeout=60;$defaultrowlimit=5;}
else if ($max_time>5) {$defaulttimeout=$max_time-2;$defaultrowlimit=5;}
else {$defaulttimeout=max(1,$max_time-2);$defaultrowlimit=2;}



?>
<script language="JavaScript" type="text/javascript">
<!--
function dump(what3){
	if (confirm("<?php echo $LANG["backup"][18];?> " + what3 +  "?")) {
		window.location = "backup.php?dump=" + what3;
	}
}
function restore(what) {
	if (confirm("<?php echo $LANG["backup"][16];?> " + what +  "?")) {
		window.location = "backup.php?file=" + what +"&donotcheckversion=1";
	}
}

function erase(what2){
	if (confirm("<?php echo $LANG["backup"][17];?> " + what2 +  "?")) {
		window.location = "backup.php?delfile=" + what2;
	}
}

function xmlnow(what4){
	if (confirm("<?php echo $LANG["backup"][18] ;?> " + what4 +  "?")) {
		window.location = "backup.php?xmlnow=" + what4;
	}
}


//-->
</script>


<?php



// les deux options qui suivent devraient �re incluses dans le fichier de config plutot non ?
// 1 only with ZLib support, else change value to 0
$compression = 0;



if ($compression==1) $filetype = "sql.gz";
else $filetype = "sql";

/// genere un fichier backup.xml a partir de base dbhost connecte avec l'utilisateur dbuser et le mot de passe dbpassword sur le serveur dbdefault
function xmlbackup()
{
	global $CFG_GLPI,$DB;

	//on parcoure la DB et on liste tous les noms des tables dans $table
	//on incremente $query[] de "select * from $table"  pour chaque occurence de $table

	$result = $DB->list_tables();
	$i = 0;
	while ($line = $DB->fetch_array($result))
	{


		// on se  limite aux tables pr�ix�s _glpi
		if (ereg("glpi_",$line[0])){

			$table = $line[0];


			$query[$i] = "select * from ".$table.";";
			$i++;
		}
	}

	//le nom du fichier a generer...
	//Si fichier existe deja il sera remplac�par le nouveau

	$chemin = GLPI_DUMP_DIR."/backup.xml";

	// Creation d'une nouvelle instance de la classe
	// et initialisation des variables
	$A=new XML();

	// Your query
	$A->SqlString=$query;

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

	//appelle de la methode g��ant le fichier XML
	$A->DoXML();


	// Affichage, si erreur affiche erreur
	//sinon affiche un lien vers le fichier XML g���


	if ($A->IsError==1)
	{
		echo "ERR : ".$A->ErrorString;
	}

	//fin de fonction xmlbackup
}
////////////////////////// DUMP SQL FUNCTIONS
/// Init time to computer time spend
function init_time() {
	global $TPSDEB,$TPSCOUR;


	list ($usec,$sec)=explode(" ",microtime());
	$TPSDEB=$sec;
	$TPSCOUR=0;

}

/// Get current time
function current_time() {
	global $TPSDEB,$TPSCOUR;
	list ($usec,$sec)=explode(" ",microtime());
	$TPSFIN=$sec;
	if (round($TPSFIN-$TPSDEB,1)>=$TPSCOUR+1) //une seconde de plus
	{
		$TPSCOUR=round($TPSFIN-$TPSDEB,1);
	}
}

/**  Get data of a table
* @param $DB DB object
* @param $table table  name
* @param $from begin from
* @param $limit limit to 
*/
function get_content($DB, $table,$from,$limit){
	$content="";
	$result = $DB->query("SELECT * FROM $table LIMIT $from,$limit");
	if($result)
		while($row = $DB->fetch_row($result)) {
			if (get_magic_quotes_runtime()) $row=addslashes_deep($row);
			$insert = "INSERT INTO $table VALUES (";
			for($j=0; $j<$DB->num_fields($result);$j++) {
				if(is_null($row[$j])) $insert .= "NULL,";
				else if($row[$j] != "") $insert .= "'".addslashes($row[$j])."',";
				else $insert .= "'',";
			}
			$insert = preg_replace("/,$/","",$insert);
			$insert .= ");\n";
			$content .= $insert;
		}
	return $content;
}

/**  Get structure of a table
* @param $DB DB object
* @param $table table  name
*/
function get_def($DB, $table) {


	$def = "### Dump table $table\n\n";
	$def .= "DROP TABLE IF EXISTS `$table`;\n";
	$query = "SHOW CREATE TABLE $table";
	$result=$DB->query($query);
	$row=$DB->fetch_array($result);

	// DELETE charset definition : UNEEDED WHEN UTF8 CONVERSION OF THE DATABASE
	$def.=$row[1];
	//$def.=preg_replace("/DEFAULT CHARSET=\w+/i","",$row[1]);
	$def.=";";
	return $def."\n\n";
}


/**  Restore a mysql dump
* @param $DB DB object
* @param $dumpFile dump file
* @param $duree max delay before refresh
*/
function restoreMySqlDump($DB,$dumpFile , $duree){
	// $dumpFile, fichier source
	// $duree=timeout pour changement de page (-1 = aucun)

	// Desactivation pour empecher les addslashes au niveau de la creation des tables
	// En plus, au niveau du dump on considere qu'on est bon
	//set_magic_quotes_runtime(0);

	global $DB,$TPSCOUR,$offset,$cpt,$LANG;

	if(!file_exists($dumpFile)){
		echo $LANG["document"][38].": $dumpFile<br>";
		return FALSE;
	}
	$fileHandle = fopen($dumpFile, "rb");

	if(!$fileHandle)
	{
		echo $LANG["document"][45].": $dumpFile<br>";
		return FALSE;
	}

	if ($offset!=0)
	{
		if (fseek($fileHandle,$offset,SEEK_SET)!=0) //erreur
		{
			echo $LANG["backup"][22]." ".formatNumber($offset,false,0)."<br>";
			return FALSE;
		}
		glpi_flush();
	}

	$formattedQuery = "";

	while(!feof($fileHandle))
	{
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;

		//    echo $TPSCOUR."<br>";

		// specify read length to be able to read long lines
		$buffer=fgets($fileHandle,102400);

		if(substr($buffer, 0, 1) != "#")
		{
			$formattedQuery .= $buffer;
			if (get_magic_quotes_runtime()) $formattedQuery=stripslashes($formattedQuery);
			if (substr(rtrim($formattedQuery),-1)==";"){

				// Do not use the $DB->query 
				if ($DB->query($formattedQuery)) //r�ssie sinon continue �conca&t�er
				{

					$offset=ftell($fileHandle);
					$formattedQuery = "";
					$cpt++;
				}
			}
		}

	}

	if ($DB->error){
		echo "<hr>".$LANG["backup"][23]." [$formattedQuery]<br>".$DB->error()."<hr>";
	}

	fclose($fileHandle);
	$offset=-1;
	return TRUE;
}

/**  Backup a glpi DB
* @param $DB DB object
* @param $dumpFile dump file
* @param $duree max delay before refresh
* @param $rowlimit rowlimit to backup in one time
*/
function backupMySql($DB,$dumpFile, $duree,$rowlimit){
	// $dumpFile, fichier source
	// $duree=timeout pour changement de page (-1 = aucun)

	global $TPSCOUR,$offsettable,$offsetrow,$cpt,$LANG;

	$fileHandle = fopen($dumpFile, "a");

	if(!$fileHandle)
	{
		echo $LANG["document"][45].": $dumpFile<br>";
		return FALSE;
	}

	if ($offsettable==0&&$offsetrow==-1){
		$time_file=date("Y-m-d-H-i");
		$cur_time=date("Y-m-d H:i");
		$todump="#GLPI Dump database on $cur_time\n";
//		$todump.="SET NAMES 'utf8';\n";
		fwrite ($fileHandle,$todump);

	}

	$result=$DB->list_tables();
	$numtab=0;
	while ($t=$DB->fetch_array($result)){

		// on se  limite aux tables pr�ix�s _glpi
		if (ereg("glpi_",$t[0])){
			$tables[$numtab]=$t[0];
			$numtab++;
		}
	}


	for (;$offsettable<$numtab;$offsettable++){

		// Dump de la structure table
		if ($offsetrow==-1){
			$todump="\n".get_def($DB,$tables[$offsettable]);
			fwrite ($fileHandle,$todump);
			$offsetrow++;
			$cpt++;
		}
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;

		$fin=0;
		while (!$fin){
			$todump=get_content($DB,$tables[$offsettable],$offsetrow,$rowlimit);
			$rowtodump=substr_count($todump, "INSERT INTO");
			if ($rowtodump>0){
				fwrite ($fileHandle,$todump);
				$cpt+=$rowtodump;
				$offsetrow+=$rowlimit;
				if ($rowtodump<$rowlimit) $fin=1;
				current_time();
				if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
					return TRUE;
			} else {
				$fin=1;
				$offsetrow=-1;
			}
		}
		if ($fin) $offsetrow=-1;
		current_time();
		if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
			return TRUE;
	}
	if ($DB->error())
		echo "<hr>".$LANG["backup"][23]." [$formattedQuery]<br>".$DB->error()."<hr>";
	$offsettable=-1;
	fclose($fileHandle);
	return TRUE;
}


// #################" DUMP sql#################################

if (isset($_GET["dump"]) && $_GET["dump"] != ""){

	$time_file=date("Y-m-d-H-i");
	$cur_time=date("Y-m-d H:i");
	$filename=$path."/glpi-".GLPI_VERSION."-$time_file.$filetype";


	if (!isset($_GET["duree"])&&is_file($filename)){
		echo "<div align='center'>".$LANG["backup"][21]."</div>";
	} else {
		init_time(); //initialise le temps
		//d�ut de fichier
		if (!isset($_GET["offsettable"])) $offsettable=0; 
		else $offsettable=$_GET["offsettable"]; 
		//d�ut de fichier
		if (!isset($_GET["offsetrow"])) $offsetrow=-1; 
		else $offsetrow=$_GET["offsetrow"];
		//timeout de 5 secondes par d�aut, -1 pour utiliser sans timeout
		if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
		else $duree=$_GET["duree"];
		//Limite de lignes �dumper �chaque fois
		if (!isset($_GET["rowlimit"])) $rowlimit=$defaultrowlimit; 
		else  $rowlimit=$_GET["rowlimit"];

		//si le nom du fichier n'est pas en param�re le mettre ici
		if (!isset($_GET["fichier"])) {
			$fichier=$filename;
		} else $fichier=$_GET["fichier"];

		$tab=$DB->list_tables();
		$tot=$DB->numrows($tab);
		if(isset($offsettable)){
			if ($offsettable>=0)
				$percent=min(100,round(100*$offsettable/$tot,0));
			else $percent=100;
		}
		else $percent=0;

		if ($percent >= 0) {
			displayProgressBar(400,$percent);
		}

		if ($offsettable>=0){
			if (backupMySql($DB,$fichier,$duree,$rowlimit))
			{
				echo "<br> <a href=\"backup.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\">".$LANG["backup"][24]."</a>";
				echo "<script>window.location=\"backup.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\";</script>";
				glpi_flush();    
				exit;

			}
		}
		else  { //echo "<div align='center'><p>Termin� Nombre de requ�es totales trait�s : $cpt</p></div>";

		}

	}	
}

// ##############################   fin dump sql########################""""




// ################################## dump XML #############################

if (isset($_GET["xmlnow"]) && $_GET["xmlnow"] !=""){

	xmlbackup();


}
// ################################## fin dump XML #############################



if (isset($_GET["file"]) && $_GET["file"] != ""&&is_file($path."/".$_GET["file"])) {

	init_time(); //initialise le temps
	//d�ut de fichier
	if (!isset($_GET["offset"])) $offset=0;
	else  $offset=$_GET["offset"];
	//timeout de 5 secondes par d�aut, -1 pour utiliser sans timeout
	if (!isset($_GET["duree"])) $duree=$defaulttimeout; 
	else $duree=$_GET["duree"];

	$fsize=filesize($path."/".$_GET["file"]);
	if(isset($offset)){
		if ($offset==-1)
			$percent=100;
		else $percent=min(100,round(100*$offset/$fsize,0));
	}
	else $percent=0;

	if ($percent >= 0) {

		displayProgressBar(400,$percent);

	}

	if ($offset!=-1){
		if (restoreMySqlDump($DB,$path."/".$_GET["file"],$duree))
		{
			echo "<br><a href=\"backup.php?file=".$_GET["file"]."&amp;duree=$duree&amp;offset=$offset&amp;cpt=$cpt&amp;donotcheckversion=1\">".$LANG["backup"][24]."</a>";
			echo "<script language=\"javascript\" type=\"text/javascript\">window.location=\"backup.php?file=".$_GET["file"]."&duree=$duree&offset=$offset&cpt=$cpt&donotcheckversion=1\";</script>";
			glpi_flush();
			exit;
		}
	} else   { //echo "<div align='center'><p>Termin� Nombre de requ�es totales trait�s : $cpt<p></div>";
		optimize_tables();
		// Compatiblity for old version for utf8 complete conversion
		$cnf=new Config();
		$input['ID']=1;
		$input['utf8_conv']=1;
		$cnf->update($input);
	}


}

if (isset($_GET["delfile"]) && $_GET["delfile"] != ""){

	$filename=$_GET["delfile"];
	if (is_file($path."/".$_GET["delfile"])){
		unlink($path."/".$_GET["delfile"]);
		echo "<div align ='center'>".$filename." ".$LANG["common"][28]."</div>";
	}

}

// Title backup
echo " <div align='center'> <table border='0'><tr><td><img src=\"".$CFG_GLPI["root_doc"]."/pics/sauvegardes.png\" alt='".$LANG["common"][28]."'></td> <td><a href=\"javascript:dump('".$LANG["backup"][19]."')\"  class='icon_consol'><b>". $LANG["backup"][0]."</b></a></td><td><a href=\"javascript:xmlnow('".$LANG["backup"][19]."')\" class='icon_consol'><b>". $LANG["backup"][1]."</b></a></td></tr></table>";


?>




<br>
<table class='tab_cadre'  cellpadding="5">
<tr align="center"> 
<th><u><i><?php echo $LANG["document"][2]; ?></i></u></th>
<th><u><i><?php echo $LANG["backup"][11]; ?></i></u></th>
<th><u><i><?php echo $LANG["common"][27]; ?></i></u></th>
<th colspan='3'>&nbsp;</th>
</tr>
<?php
$dir=opendir($path); 
$files=array();
while ($file = readdir ($dir)) { 
	if ($file != "." && $file != ".." && preg_match("/\.sql$/i",$file)) { 
		$files[$file]=filemtime($path."/".$file);
	}
}
arsort($files);
if (count($files)){
	foreach ($files as $file => $date){
		$taille_fic = filesize($path."/".$file)/1024;
		$taille_fic = (int)$taille_fic;
		echo "<tr class='tab_bg_2'><td>$file&nbsp;</td>
			<td align=\"right\">&nbsp;" . $taille_fic . " kB&nbsp;</td>
			<td>&nbsp;" . convDateTime(date("Y-m-d H:i",$date)) . "</td>
			<td>&nbsp;<a href=\"javascript:erase('$file')\">".$LANG["buttons"][6]."</a>&nbsp;</td>

			<td>&nbsp;<a href=\"javascript:restore('$file')\">".$LANG["buttons"][21]."</a>&nbsp;</td>
			<td>&nbsp;<a href=\"document.send.php?file=_dumps/$file\">".$LANG["backup"][13]."</a></td></tr>";
	}
}
closedir($dir);
$dir=opendir($path);
unset($files);
$files=array();
while ($file = readdir ($dir)) {
	if ($file != "." && $file != ".." && preg_match("/\.xml$/i",$file)) {
		$files[$file]=filemtime($path."/".$file);
	}
}
arsort($files);
if (count($files)){
	foreach ($files as $file => $date){
		$taille_fic = filesize($path."/".$file)/1024;
		$taille_fic = (int)$taille_fic;
		echo "
			<tr class='tab_bg_1'><td colspan='6' ><hr noshade></td></tr>
			<tr class='tab_bg_2'><td>$file&nbsp;</td>
			<td align=\"right\">&nbsp;" . $taille_fic . " kB&nbsp;</td>
			<td>&nbsp;" . convDateTime(date("Y-m-d H:i",$date)) . "</td>
			<td>&nbsp;<a href=\"javascript:erase('$file')\">".$LANG["buttons"][6]."</a>&nbsp;</td>
			<td>&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;</td>

			<td>&nbsp;<a  href=\"document.send.php?file=_dumps/$file\">".$LANG["backup"][13]."</a></td></tr>";
	}
}
closedir($dir);
?>
</table>
</div>
<?php

commonFooter();
?>




