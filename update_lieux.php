<?
include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
//Verifie que le champs $field existe bien dans la table $table
function FieldExists($table, $field) {
	$db = new DB;
	$result = $db->query("SELECT * FROM ". $table ."");
	$fields = mysql_num_fields($result);
	$var1 = false;
	for ($i=0; $i < $fields; $i++) {
		$name  = mysql_field_name($result, $i);
		if($name == $field) {
			$var1 = true;
		}
	}
	return $var1;
}

//Verifie si la table $tablename existe
function TableExists($tablename) {
  
   $db = new DB;
   // Get a list of tables contained within the database.
   $result = $db->list_tables($db);
   $rcount = $db->numrows($result);

   // Check each in list for a match.
   for ($i=0;$i<$rcount;$i++) {
       if (mysql_tablename($result, $i)==$tablename) return true;
   }
   return false;
}

if (!FieldExists("glpi_dropdown_locations", "level")){


function validate_new_location(){
$db=new DB;
$query=" DROP TABLE `glpi_dropdown_locations`";	
//echo $query;
$result=$db->query($query);
$query=" ALTER TABLE `glpi_dropdown_locations_new` RENAME `glpi_dropdown_locations`";	
//echo $query;
$result=$db->query($query);

}


function display_new_locations(){
echo "<center>Nouvelle hierarchie : </center>";

$db=new DB;

$query="SELECT MAX(level) AS MAX from glpi_dropdown_locations_new;";
$result=$db->query($query);
$MAX_LEVEL=$db->result($result,0,"MAX");
$SELECT_ALL="";
$FROM_ALL="";
$WHERE_ALL="";
$ORDER_ALL="";

for ($i=1;$i<=$MAX_LEVEL;$i++){
$SELECT_ALL.=" , location$i.name AS NAME$i ";
$FROM_ALL.=" LEFT JOIN glpi_dropdown_locations_new AS location$i ON location".($i-1).".ID = location$i.level_up ";
//$WHERE_ALL.=" AND location$i.level='$i' ";
$ORDER_ALL.=" , NAME$i";

}

$query="select location0.name AS NAME0 $SELECT_ALL FROM glpi_dropdown_locations_new AS location0 $FROM_ALL  WHERE location0.level='0' $WHERE_ALL  ORDER BY NAME0 $ORDER_ALL";
//echo $query;
//echo "<hr>";
$result=$db->query($query);
$data_old=array();
echo "<table><tr>";
for ($i=0;$i<=$MAX_LEVEL;$i++){
	echo "<th>Niveau $i</th><th>&nbsp;</th>";
	}
echo "</tr>";

while ($data =  $db->fetch_array($result)){
	
	echo "<tr>";
	for ($i=0;$i<=$MAX_LEVEL;$i++){
	if (!isset($data_old["NAME$i"])||$data_old["NAME$i"]!=$data["NAME$i"]){
	$name=$data["NAME$i"];
	if (isset($data["NAME".($i+1)])&&!empty($data["NAME".($i+1)]))
	$arrow="--->";
	else $arrow="";
	}
	else {
		$name="";
		$arrow="";
	}
	echo "<td>".$name."</td>";
	
	echo "<td>$arrow</td>";
	}
	
	echo "</tr>";
$data_old=$data;
}
echo "</table>";
	
}

function display_old_locations(){
$db=new DB;
$query="SELECT * from glpi_dropdown_locations;";
$result=$db->query($query);

echo "<center>Lieux actuels : </center>";
while ($data =  $db->fetch_array($result))
echo "<b>".$data['name']."</b> - ";
}

function location_create_new($split_char,$add_first){

$db=new DB;
$query="SELECT MAX(ID) AS MAX from glpi_dropdown_locations;";
//echo $query."<br>";
$result=$db->query($query);
$new_ID=$db->result($result,0,"MAX");
$new_ID++;


$query="SELECT * from glpi_dropdown_locations;";
$result=$db->query($query);

$query_clear_new="TRUNCATE TABLE `glpi_dropdown_locations_new`";
//echo $query_clear_new."<br>";
$result_clear_new=$db->query($query_clear_new); 

//$split_char="/";
//$add_first="";
if (!empty($add_first)){
$root_ID=$new_ID;
$new_ID++;
$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$root_ID','$add_first',0,-1)";
//echo $query_insert."<br>";
$result_insert=$db->query($query_insert);
$default_level=1;
}
else {
$default_level=0;	
$root_ID=-1;
}

while ($data =  $db->fetch_array($result)){
	if (!empty($split_char))
	$splitter=split($split_char,$data['name']);
	else $splitter=array($data['name']);
	$up_ID=$root_ID;
	for ($i=0;$i<count($splitter)-1;$i++){
	// Entrée existe deja ??
	$query_search="select ID from glpi_dropdown_locations_new WHERE name='".$splitter[$i]."' AND level='".($default_level+$i)."' AND level_up='".$up_ID."'";
//	echo $query_search."<br>";
	$result_search=$db->query($query_search);
	if ($db->numrows($result_search)==1){	// Found
	$up_ID=$db->result($result_search,0,"ID");
	} else { // Not FOUND -> INSERT
	$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('$new_ID','".$splitter[$i]."','".($default_level+$i)."','$up_ID')";
	$up_ID=$new_ID++;
//	echo $query_insert."<br>";
	$result_insert=$db->query($query_insert);
		
	}
	
	
	}

	// Ajout du dernier
	$query_insert="INSERT INTO glpi_dropdown_locations_new VALUES ('".$data["ID"]."','".$splitter[count($splitter)-1]."','".($default_level+count($splitter)-1)."','$up_ID')";
//	echo $query_insert."<br>";

	$result_insert=$db->query($query_insert);

	}

$query_auto_inc= "ALTER TABLE `glpi_dropdown_locations_new` CHANGE `ID` `ID` INT( 11 ) DEFAULT '0' NOT NULL AUTO_INCREMENT";
$result_auto_inc=$db->query($query_auto_inc);
}



if (!isset($_POST['root'])) $_POST['root']='';
if (!isset($_POST['car_sep'])) $_POST['car_sep']='';

if(!TableExists("glpi_dropdown_locations_new")) {
	$query = " CREATE TABLE `glpi_dropdown_locations_new` (`ID` INT NOT NULL ,`name` VARCHAR( 255 ) NOT NULL ,`level` TINYINT NOT NULL ,`level_up` INT NOT NULL ,PRIMARY KEY ( `ID` ),UNIQUE (`name`,`level`,`level_up`) );";
	$db->query($query) or die("LOCATION ".$db->error());
}


	echo "<div align='center'>";
	echo "<h3>Mise à jour des lieux</h3>";
	echo "<p>La nouvelle structure est hierarchique</p>";
	echo "<p>Si vous utilisiez un caractère de séparation vous pouvez l'indiquer pour automatiser la génération de la hierarchie. Vous pouvez aussi spécifier un lieu de base qui incluera tous les lieux générés.</p>";
	echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
	echo "<p>Caractère de séparation <input type=\"text\" name=\"car_sep\" value=\"".$_POST['car_sep']."\"/></p>";
	echo "<p>Lieu racine <input type=\"text\" name=\"root\" value=\"".$_POST['root']."\"/></p>";
	echo "<input type=\"submit\" class='submit' name=\"new_location\" value=\"Valider\" />";
	echo "</form>";
	echo "</div>";



if (isset($_POST["new_location"])){
location_create_new($_POST['car_sep'],$_POST['root']);	
display_old_locations();	
display_new_locations();	
	echo "<p>Valider nouvelle hierarchie</p>";
	echo "<form action=\"".$_SERVER["PHP_SELF"]."\" method=\"post\">";
	echo "<input type=\"submit\" class='submit' name=\"validate_location\" value=\"Valider\" />";
	echo "</form>";


}
else if (isset($_POST["validate_location"])){
validate_new_location();
echo "OK UPDATE LIEU";
} else {
display_old_locations();	
}


}
?>