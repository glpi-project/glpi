#!/usr/bin/php
<?php
$cmd=$_SERVER["argv"][0];

function checkOne ($name, $tab="") {
	global $cmd;

	if (empty($tab)) $tab=strtoupper("LANG$name");
	$old=getcwd();
	if (is_dir($name."/trunk/locales") && is_file($name."/trunk/locales/fr_FR.php")) {
		echo "+ ----- $name -----\n";
		$dir=opendir($name."/trunk/locales");
		while (($file = readdir($dir)) !== false) {
			if (strpos($file, ".php") && $file!="fr_FR.php")
				passthru("php $cmd $tab $name/trunk/locales/fr_FR.php $name/trunk/locales/$file\n");
		}
		closedir($dir);
	} else {
		echo ("no $name/trunk/locales/fr_FR.php\n");
	}
	chdir($old);
}

function diffTab ($from, $dest, $name) {

	$nb=0;

	if (is_array($from)) foreach ($from as $ligne => $value) {
		if (isset($dest[$ligne])) {
			$nb += diffTab($from[$ligne], $dest[$ligne], $name."['$ligne']");
		} else {
			echo $name."['$ligne'] absent ($value)\n";
			$nb++;
		}

	} 
	//else  echo "$name ok\n";

	return $nb;
}
if (isset($_SERVER["argc"]) && $_SERVER["argc"]==2 && $_SERVER["argv"][1]=="all") {

	// For 0.71 plugin only
	$exception=array(
		"data_injection" => "DATAINJECTIONLANG",
		"hole" => "LANG_HOLE", 
		"backups" => "LANGBACKUP", 
		"reports" => "GEDIFFREPORTLANG",
		"mass_ocs_import" => "OCSMASSIMPORTLANG");

	$dir=opendir(".");
	while (($file = readdir($dir)) !== false) {
		if (is_dir($file) && substr($file,0,1)!=".") {
			//echo "$file\n";
			checkOne($file, (is_file($file."/trunk/hook.php") ? "LANG" 
				: (isset($exception[$file]) ? $exception[$file] 
					: "")));
		}
	}
	closedir($dir);

} else if (isset($_SERVER["argc"]) && $_SERVER["argc"]==4) {

require $_SERVER["argv"][2];
isset($GLOBALS[$_SERVER["argv"][1]]) or die ($_SERVER["argv"][1] . " not defined in " . $_SERVER["argv"][2] . "\n");
$from = $GLOBALS[$_SERVER["argv"][1]];

unset ($GLOBALS[$_SERVER["argv"][1]]);

require $_SERVER["argv"][3];
isset($GLOBALS[$_SERVER["argv"][1]]) or die ($_SERVER["argv"][1] . " not defined in " . $_SERVER["argv"][3] . "\n");
$dest = $GLOBALS[$_SERVER["argv"][1]];

$nb=0;
//print_r($GLOBALS);

printf ("Contrôle %s dans %s\n", $_SERVER["argv"][1], $_SERVER["argv"][3]);
$nb += diffTab($from, $dest, '$'.$_SERVER["argv"][1]);
printf ("Contrôle %s dans %s\n", $_SERVER["argv"][1], $_SERVER["argv"][2]);
$nb += diffTab($dest, $from, '$'.$_SERVER["argv"][1]);

if ($nb)	echo "$nb erreur(s) détectée(s) : au boulot !\n";
else		echo "C'est bon :)\n";

} else {
	echo "\nusage $cmd  TABLEAU  langue1   langue2\n";
	echo "\nusage $cmd  all\n\n";
}
?>
