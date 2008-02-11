#!/usr/bin/php
<?php
if (isset($_SERVER["argc"]) && $_SERVER["argc"]==4) {

require $_SERVER["argv"][2];
isset($GLOBALS[$_SERVER["argv"][1]]) or die ($_SERVER["argv"][1] . " not defined in " . $_SERVER["argv"][2] . "\n");
$from = $GLOBALS[$_SERVER["argv"][1]];

unset ($GLOBALS[$_SERVER["argv"][1]]);

require $_SERVER["argv"][3];
isset($GLOBALS[$_SERVER["argv"][1]]) or die ($_SERVER["argv"][1] . " not defined in " . $_SERVER["argv"][3] . "\n");
$dest = $GLOBALS[$_SERVER["argv"][1]];

$nb=0;

printf ("\nContrôle %s\n", $_SERVER["argv"][3]);
foreach ($from as $section => $tab1) {
	foreach ($tab1 as $ligne => $value) {
		if (!isset($dest[$section][$ligne])) {
			printf("\$%s['%s']['%s'] absent (%s)\n", $_SERVER["argv"][1], $section, $ligne, $value);
			$nb++;
		}
		//else	printf("\$%s['%s']['%s'] ok\n", $_SERVER["argv"][1], $section, $ligne, $value);
	}	
}
printf ("\nContrôle de %s\n", $_SERVER["argv"][2]);
foreach ($dest as $section => $tab1) {
	foreach ($tab1 as $ligne => $value) {
		if (!isset($from[$section][$ligne])) {
			printf("\$%s['%s']['%s'] absent (%s)\n", $_SERVER["argv"][1], $section, $ligne, $value);
			$nb++;
		}
		//else	printf("\$%s['%s']['%s'] ok\n", $_SERVER["argv"][1], $section, $ligne, $value);
	}	
}
if ($nb)	echo "\n$nb erreur(s) détectées : au boulot !\n\n";
else		echo "\nC'est bon :)\n\n";

} else {
	echo "\nusage checklang  TABLEAU  langue1   langue2\n\n";
}
?>
