<html>
<head><title>Restauration de base MySql</title></head>
<body>
<?

include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_setup.php");

function td() //temp initial
{
    global $TPSDEB,$TPSCOUR;
    
    
    list ($usec,$sec)=explode(" ",microtime());
    $TPSDEB=$sec;
    $TPSCOUR=0;

}

function tf() //temp final ou intermediaire
{
    global $TPSDEB,$TPSCOUR;
    list ($usec,$sec)=explode(" ",microtime());
    $TPSFIN=$sec;
//    echo $TPSFIN."---".$TPSDEB."<br>";
    if (round($TPSFIN-$TPSDEB,1)>=$TPSCOUR+1) //une seconde de plus
    {
    $TPSCOUR=round($TPSFIN-$TPSDEB,1);
//    echo "<br>".$TPSCOUR."<br>";
    flush();
    }

}

function get_content($dbname, $table,$from,$limit)
{
     $db = new DB;
     global $conn;
     $content="";
     $result = $db->query("SELECT * FROM $table LIMIT $from,$limit",$conn);
     echo "SELECT * FROM $table ORDER BY ID LIMIT $from,$limit<br>";
     if($result)
     while($row = $db->fetch_row($result)) {
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


function get_def($dbname, $table) {
    global $conn;
    $db = new DB;
    $def = "### Dump table $table\n\n";
    $def .= "DROP TABLE IF EXISTS $table;\n";
    $def .= "CREATE TABLE $table (\n";
    $result = $db->query("SHOW FIELDS FROM $table",$conn);
    while($line = $db->fetch_array($result)) {
        $def .= "    $line[Field] $line[Type]";
        if (isset($line["Default"]) && $line["Default"] != "") $def .= " DEFAULT '$line[Default]'";
        if (isset($line["Null"]) && $line["Null"] != "YES") $def .= " NOT NULL";
       	if (isset($line["Extra"]) && $line["Extra"] != "") $def .= " $line[Extra]";
        	$def .= ",\n";
     }
     $def = ereg_replace(",\n$","", $def);
     $result = $db->query("SHOW KEYS FROM $table",$conn);
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
     return (stripslashes($def));
}


function restoreMySqlDump($dumpFile , $database , $mysqlUser , $mysqlPassword , $hostMySql, $duree)
{
// $dumpFile, fichier source
// $database, nom de la base de données cible
// $mysqlUser, login pouyr la connexion au serveur MySql
// $mysqlPassword, mot de passe
// $histMySql, nom de la machine serveur MySQl
// $duree=timeout pour changement de page (-1 = aucun)

global $TPSCOUR,$offset,$cpt;

$mySqlHandle = mysql_connect($hostMySql, $mysqlUser, $mysqlPassword);
if (!$mySqlHandle)
{
     echo "Connexion impossible à $hostMySql pour $mysqlUser";
     return FALSE;
}

if(!file_exists($dumpFile))
{
     echo "$dumpFile non trouvé<br>";
     return FALSE;
}
echo $dumpFile;
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
     else
     echo "Reprise à l'octet ".number_format($offset,0,""," ")."<br>";
    flush();
}
else
{
//     $query = "DROP DATABASE IF EXISTS " . $database;
//     $result = mysql_query($query);
//     $query = "CREATE DATABASE " . $database;
//     $result = mysql_query($query);
    }
    
    $query = "USE " . $database;
    $result = mysql_query($query);


$formattedQuery = "";

while(!feof($fileHandle))
{
    tf();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;
    //echo $TPSCOUR."<br>";
    $buffer=fgets($fileHandle);
    if (substr($buffer,strlen($buffer),1)==0)
        $buffer=substr($buffer,0,strlen($buffer)-1);
    
    $buffer=preg_replace("/#.*$/","",$buffer);
//    echo $buffer."<br>";
    
    if(substr($buffer, 0, 1) != "#")
    {
        $formattedQuery .= $buffer;
        if ($formattedQuery&&substr($formattedQuery,-1)==";")
        if (mysql_query($formattedQuery,$mySqlHandle)) //réussie sinon continue à conca&téner
        {
//           echo $formattedQuery;

            $offset=ftell($fileHandle);
            //echo $offset;
            $formattedQuery = "";
            $cpt++;
            //echo $cpt;
//        echo "<hr>";            
        }
        else echo "ECHEC".mysql_error();

    }
}

if (mysql_error())
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".mysql_error()."<hr>";

fclose($fileHandle);
mysql_close($mySqlHandle);
$offset=0;
return TRUE;
}

function backupMySql($dumpFile , $database , $mysqlUser , $mysqlPassword , $hostMySql, $duree,$rowlimit)
{
// $dumpFile, fichier source
// $database, nom de la base de données cible
// $mysqlUser, login pouyr la connexion au serveur MySql
// $mysqlPassword, mot de passe
// $histMySql, nom de la machine serveur MySQl
// $duree=timeout pour changement de page (-1 = aucun)

global $TPSCOUR,$offsettable,$offsetrow,$cpt;

$mySqlHandle = mysql_connect($hostMySql, $mysqlUser, $mysqlPassword);
if (!$mySqlHandle)
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

$result=mysql_list_tables($database,$mySqlHandle);
$numtab=0;
while ($t=mysql_fetch_array($result)){
	$tables[$numtab]=$t[0];
$numtab++;
}

//print_r($tables);



$query = "USE " . $database;
$result = mysql_query($query);

for (;$offsettable<$numtab;$offsettable++){
//    echo "<br>NUMTABLE".$offsettable."<br>";
    tf();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;
    //echo $TPSCOUR."<br>";
// Dump de la strucutre table
if ($offsetrow==-1){
echo "dump ".$tables[$offsettable]."<br>";

	$todump=get_def("glpi",$tables[$offsettable]);
//	echo $todump."<br>";
	fwrite ($fileHandle,$todump);
	$offsetrow++;
	$cpt++;
	}
//	$query = "SELECT * FROM ". $tables[$offsettable];
//	$result = mysql_query($query);
//	$numrows=mysql_num_rows($result);

	$fin=0;
	for ($offsetrow=$offsetrow;!$fin;$offsetrow+=$rowlimit){
    tf();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;
	$todump=get_content("glpi",$tables[$offsettable],$offsetrow,$rowlimit);
	$rowtodump=substr_count($todump, "INSERT INTO");
	if ($rowtodump>0){
	fwrite ($fileHandle,$todump);
//	echo $todump;
	
//	echo "<hr>";
	$cpt+=$rowtodump;
	if ($rowtodump<$rowlimit) $fin=1;
	}
	else {$fin=1;$offsetrow=-1;}
	}
	if ($fin) $offsetrow=-1;
	
}
if (mysql_error())
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".mysql_error()."<hr>";

if ($offsettable==$numtab) $offsettable=-1;
/*
$formattedQuery = "";



    tf();
    if ($duree>0 and $TPSCOUR>=$duree) //on atteint la fin du temps imparti
        return TRUE;
    //echo $TPSCOUR."<br>";
    $buffer=fgets($fileHandle);
    if (substr($buffer,strlen($buffer),1)==0)
        $buffer=substr($buffer,0,strlen($buffer)-1);
    
    $buffer=preg_replace("/#.*$/","",$buffer);
    echo $buffer."<br>";
    
    if(substr($buffer, 0, 1) != "#")
    {
        $formattedQuery .= $buffer;
        if ($formattedQuery&&substr($formattedQuery,-1)==";")
        if (mysql_query($formattedQuery,$mySqlHandle)) //réussie sinon continue à conca&téner
        {
           echo $formattedQuery;

            $offset=ftell($fileHandle);
            //echo $offset;
            $formattedQuery = "";
            $cpt++;
            //echo $cpt;
        echo "<hr>";            
        }
        else echo "ECHEC".mysql_error();

    }


if (mysql_error())
     echo "<hr>ERREUR à partir de [$formattedQuery]<br>".mysql_error()."<hr>";
*/
fclose($fileHandle);
mysql_close($mySqlHandle);
$offset=0;
return TRUE;
}


///////////// RESTAURATION DE LA BDD
/*
td(); //initialise le temps
if (!isset($offset)) $offset=0; //début de fichier
if (!isset($duree)) $duree=1; //timeout de 5 secondes par défaut, -1 pour utiliser sans timeout

if (!isset($fichier)) $fichier="./backups/dump/smalldump.sql"; //si le nom du fichier n'est pas en paramètre le mettre ici

echo "Restauration de $fichier.<br>Traitement en cours... ";
if ($duree>0) echo "timeout de $duree s.<br>";
flush();
//echo "$offset";
//exit;
//nom du fichier, nom de la base, nom d'utilisateur, mot de passe, serveur, duree
if (restoreMySqlDump($fichier , "glpi" , "dombre" , "coucoucmoi", "localhost",$duree))
{
    if ($offset!=0)
    {
    echo "<br>Nombre de requêtes traitées à ce stade : $cpt<br>";
    echo "<br>mais il faut continuer à l'octet ".number_format($offset,0,""," ");
    echo "<br>Redirection automatique sinon cliquez <a href=\"script.php?duree=$duree&offset=$offset&cpt=$cpt\">ici</a>";
    echo "<script>window.location=\"script.php?duree=$duree&offset=$offset&cpt=$cpt\";</script>";
    }
    else
     echo "<br>Terminé. Nombre de requêtes totales traitées : $cpt<br>";

}
*/

//// BACKUP DE LA BDD

td(); //initialise le temps
if (!isset($offsettable)) $offsettable=0; //début de fichier
if (!isset($offsetrow)) $offsetrow=-1; //début de fichier
if (!isset($duree)) $duree=1; //timeout de 5 secondes par défaut, -1 pour utiliser sans timeout
if (!isset($rowlimit)) $rowlimit=4; //Limite de lignes à dumper à chaque fois

if (!isset($fichier)) $fichier="./backups/dump/temp.sql"; //si le nom du fichier n'est pas en paramètre le mettre ici

echo "Sauvegarde de la BDD dans le $fichier.<br>Traitement en cours... ";
if ($duree>0) echo "Durée limite de $duree s.<br>";
flush();
//echo "$offset";
//exit;
//nom du fichier, nom de la base, nom d'utilisateur, mot de passe, serveur, duree
if (backupMySql($fichier , "glpi" , "dombre" , "coucoucmoi", "localhost",$duree,$rowlimit))
{
echo "ENDDDDDDDDDDDDDDDDD".$offsettable;
    if ($offsettable>=0)
    {
    echo "<br>Nombre de requêtes traitées à ce stade : $cpt<br>";
    echo "<br>mais il faut continuer à la table $offsettable ligne ".number_format($offsetrow,0,""," ");
    echo "<br>Redirection automatique sinon cliquez <a href=\"script.php?duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt\">ici</a>";
    echo "<script>window.location=\"script.php?duree=$duree&rowlimit=$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt\";</script>";
    }
    else
     echo "<br>Terminé. Nombre de requêtes totales traitées : $cpt<br>";

}

?>
</body>
</html>
