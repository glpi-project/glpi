#!/usr/bin/php
<?php

$liste=array();

function CLean ($buf) {
	$buf=encodeInUtf8($buf);
	return html_entity_decode($buf,ENT_QUOTES,"utf-8");
}
function ListePlug ($lang, $name, $base, $num,$cat) {
	global $liste;

	fputs(STDERR, ".");

	$url=$base.$num;
	$page=fopen($url, "r");
	if ($page) {
		while ($buf=fgets($page, 1000)) {
			$buf=Clean($buf);
			if (preg_match('@<tr class="row_even"><td>(.*)</td><td> <a href="(.*/(glpi-([a-zA-Z_]*)-([0-9,\.\-]*)).(tar.gz|tgz))">(.*)</a> </td><td>([^<]*)@', $buf, $regs)) {
				$id=$regs[4];
				$liste[$cat][$id]["doc"]=$url;
				$liste[$cat][$id]["use"]=$lang.":plugins:".$id."_use";
				$liste[$cat][$id]["des"]=$name;
				$liste[$cat][$id]["ver"]=str_replace('-','.',$regs[5]);
				if ($lang=='en' && preg_match("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/",trim($regs[1]),$part)) {
					$liste[$cat][$id]["dat"]=$part[3].".".$part[2].".".$part[1];
				} else {
					$liste[$cat][$id]["dat"]=$regs[1];
				}
				$liste[$cat][$id]["cpt"]=$regs[8];
				$liste[$cat][$id]["tgz"]=$regs[2];
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
				$cat=$name;
				ListePlug($lang, $regs[3], $base, $regs[1],$cat);
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
				$regs[3]=trim($regs[3]);
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

	echo "\rSTART COPY AFTER HERE :\n\n";
	
	switch ($lang) {
			case "fr":
				echo "===== Liste des Plugins GLPI =====\n\n";
				break;
			case "en":
				echo "===== GLPI Plugins list =====\n\n";
				break;
		}
		
	ksort($liste);
	
	foreach ($liste as $key => $val) {
		
		switch ($lang) {
			case "fr":
				echo "**".$key."**\n\n";
				echo "^ Nom ^ Doc. GLPI ^ Mode d'emploi ^ Description ^ Version ^ Date maj. ^ Glpi ^ Source ^\n";
				break;
			case "en":
				echo "**".$key."**\n\n";
				echo "^ Name ^ Doc. ^ Manual ^ Description ^ Version ^ Date ^ Glpi ^ Source ^\n";
				break;
		}
		ksort($val);
		foreach ($val as $id => $plug) {
		
			$p=strpos($plug["des"], "(");
			$des= ($p ? substr($plug["des"],0,$p) : $plug["des"]);

			printf ("| **%s**  | [[%s|Doc]] | [[%s|Wiki-use]] | %s | %s | %s | %s | [[%s|source]]|\n",
				$id, $plug["doc"], $plug["use"], $des, $plug["ver"], $plug["dat"], $plug["cpt"], $plug["tgz"]);
		}
		
		echo "\n\n";
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
