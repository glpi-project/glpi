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
if (isset($_SERVER["argc"]) && $_SERVER["argc"]==2 && $_SERVER["argv"][1]=="all") {

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
			checkOne($file, (isset($exception[$file]) ? $exception[$file] : ""));
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
foreach ($from as $section => $tab1) {
	if (is_array($tab1)) foreach ($tab1 as $ligne => $value) {
		if (!isset($dest[$section][$ligne])) {
			printf("\$%s['%s']['%s'] absent (%s)\n", $_SERVER["argv"][1], $section, $ligne, $value);
			$nb++;
		}
	} else if (!isset($dest[$section])) {
		printf("\$%s['%s'] absent (%s)\n", $_SERVER["argv"][1], $section, $tab1);
		$nb++;
	}

}
printf ("Contrôle %s dans %s\n", $_SERVER["argv"][1], $_SERVER["argv"][2]);
foreach ($dest as $section => $tab1) {
	if (is_array($tab1)) foreach ($tab1 as $ligne => $value) {
		if (!isset($from[$section][$ligne])) {
			printf("\$%s['%s']['%s'] absent (%s)\n", $_SERVER["argv"][1], $section, $ligne, $value);
			$nb++;
		}
	} else if (!isset($from[$section])) {
		printf("\$%s['%s'] absent (%s)\n", $_SERVER["argv"][1], $section, $tab1);
		$nb++;
	}
}
if ($nb)	echo "$nb erreur(s) détectées : au boulot !\n";
else		echo "C'est bon :)\n";

} else {
	echo "\nusage $cmd  TABLEAU  langue1   langue2\n";
	echo "\nusage $cmd  all\n\n";
}
?>
