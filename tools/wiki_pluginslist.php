#!/usr/bin/php
<?php

$liste=array();

function CLean ($buf) {
	$buf=utf8_encode($buf);
	return html_entity_decode($buf,ENT_QUOTES,"utf-8");
}
function ListePlug ($lang, $name, $base, $num) {
	global $liste;

	fputs(STDERR, ".");

	$url=$base.$num;
	$page=fopen($url, "r");
	if ($page) {
		while ($buf=fgets($page, 1000)) {
			$buf=Clean($buf);
			if (preg_match('@<tr class="row_even"><td>(.*)</td><td> <a href="(.*/(glpi-(.*)-([0-9,\.]*)).tar.gz)">(.*)</a> </td><td>(.*)</td><td>.*</td></tr>@', $buf, $regs)) {
				$id=$regs[4];
				$liste[$id]["doc"]=$url;
				$liste[$id]["use"]=$lang.":plugins:".$id."_use";
				$liste[$id]["des"]=$name;
				$liste[$id]["ver"]=$regs[5];
				$liste[$id]["dat"]=$regs[1];
				$liste[$id]["cpt"]=$regs[7];
				$liste[$id]["tgz"]=$regs[2];
				return;
			}
		}
		fclose($page);
	} else {
		echo "*** Cannot read $url\n";
	}
}
function ListeRub ($lang, $name, $base, $num) {

	$url=$base.$num;
	$page=fopen($url, "r");
	if ($page) {
		fputs(STDERR, "$name\n");
		$secteur=false;
		while ($buf=fgets($page, 1000)) {
			$buf=Clean($buf);
			if ($secteur && preg_match('@<li><a href="(spip.php\?article([0-9]*))".*>(.*)</a></li>@', $buf, $regs)) {
				ListePlug($lang, $regs[3], $base, $regs[1]);
			}
			else if (strpos($buf, '<div class="secteur">')) {
				$secteur=true;
			}
		}
		fputs(STDERR, "\r");
		fclose($page);
	} else {
		echo "*** Cannot read $url\n";
	}
} 
function ListeAll ($lang, $base, $num) {

	$url=$base.$num;
	$page=fopen($url, "r");
	if ($page) {
		while ($buf=fgets($page, 1000)) {
			$buf=Clean($buf);
			if (preg_match('@<h4><span class="fond_blanc"><a href="(spip.php\?rubrique([0-9]*))".*>(.*)</a></span></h4>@', $buf, $regs)) {
				ListeRub($lang, $regs[3], $base, $regs[1]);
				//return;
			}
		}
		fclose($page);
	} else {
		echo "*** Cannot read $url\n";
	}
}

function Display ($lang) {
	global $liste;

	echo "\rSTART COPY FROM HERE :\n\n";
	switch ($lang) {
		case "fr":
			echo "Liste des Plugins GLPI\n\n";
			echo "^ Nom ^ Doc. GLPI ^ Mode d'emploi ^ Description ^ Version ^ Date maj. ^ Glpi ^ Source ^\n";
			break;
		case "en":
			echo "GLPI Plugins list\n\n";
			echo "^ Name ^ Doc. ^ Manual ^ Description ^ Version ^ Date ^ Glpi ^ Source ^\n";
			break;
	}
	ksort($liste);
	
	foreach ($liste as $id => $plug) {
		$p=strpos($plug["des"], "(");
		$des= ($p ? substr($plug["des"],0,$p) : $plug["des"]);

		printf ("| **%s** | [[%s|Doc]] | [[%s|Wiki-use]] | %s | %s | %s | %s | [[%s|source]]|\n",
			$id, $plug["doc"], $plug["use"], $des, $plug["ver"], $plug["dat"], $plug["cpt"], $plug["tgz"]);
	}

	switch ($lang) {
		case "fr":
			echo "\nGénéré le ".date("d/m/Y")."\n\n";
			break;
		case "en":
			echo "\nGenerated on ".date("Y-m-d")."\n\n";
			break;
	}
}
if (isset($_SERVER["argv"][1])) switch ($_SERVER["argv"][1]) 
{
	case "fr": 
		ListeAll("fr", "http://www.glpi-project.org/", "spip.php?rubrique20");
		Display("fr");
		break;
	case "en": 
		ListeAll("en", "http://www.glpi-project.org/", "spip.php?rubrique28");
		Display("en");
		break;
	default:
		echo "langues : fr ou en\n";
}
else	echo "usage : " . $_SERVER["argv"][0] . "  langue\n";
?>
