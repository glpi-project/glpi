<?php
/* TransRapi 0.3
  
  Interface web pour fichiers de langue au format PHP

  Copyright (C) 2004 Olivier Fraysse.

  This file is part of TransRapi.
  
  TransRapi is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.
 
  TransRapi is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.
 
  You should have received a copy of the GNU General Public License
  along with TransRapi; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 
 (http://www.gnu.org/licenses/gpl.txt)
 
 http://tr.fooye.net
 http://tr.fooye.net/source/
 Contact : tr@fooye.net 
 
Derniere modification : 14 Juillet 2004
******************************************************************

TODOLIST:
garder les commentaires-en-lignes
page d'accueil ?
stylesheet pour impression (sans menu)
*/


$filenames = array ("english" => "english.php", "francais" => "french.php", "allemand" => "deutsch.php");

if($_GET['correct'])
{
	list($choice,$rub,$id) = explode('_',$_GET['correct']);
	if (!$nojs || !$reset)
	{
		function do_correct($lang,$rub,$id,$text)
		{
			global $filenames;
			$file = file($filenames[$lang]);
			$to_put = "[$id] \t= \"$text\"; \n";
			for($i=0;$i<count($file);$i++)
			{
				if (eregi("^(.+lang\[\"$rub\"\])\[([0-9]*)\]",$file[$i],$ereg))
				{
					if($ereg[2]==$id)
					{
						$file[$i] = $ereg[1].$to_put;
						$r++;
					}
					elseif ($ereg[2]<$id)
					{
						$near[0]=$i;
						$near[1]=$ereg[1];
					}
					$o++;
				}
			}
			if(!$r && $near)
			{
				$file[$near[0]].=$near[1].$to_put;
				$r=1;
			}
			if($r==1)
			{
				$fp = fopen($filenames[$lang],'w');
				for($i=0;$i<count($file);$i++)
					fputs($fp,$file[$i]);
				fclose($fp);
				
				return true;
			}
			else
			{
				return false;
			}
		}
		$_GET['text'] = htmlentities($_GET['text']);
		if(do_correct($choice,$rub,$id,$_GET['text']))
		{
			if(!$nojs)
			{
				echo "
				elemc = document.getElementById('{$_GET['correct']}')
				elemc.innerHTML='{$_GET['text']}';
				elemc.style.border='2px solid transparent';
				elemc.onclick=function(){getCorrectForm(\"{$choice}_{$rub}_{$id}\",\"{$_GET['text']}\");};
				";
				die();
			}
			
		}
		elseif(!$nojs)
		{
			die("document.getElementById('{$_GET['correct']}').innerHTML='({$_GET['text']})'");
		}
		else
		{
			$select = $_GET['correct'];
		}
	}
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Transrapidos</title>
<link rel='stylesheet' title='Transrapi' href='transrapi.css' type='text/css' media='screen' />
<script type='text/javascript' src="transrapi.js"></script>
</head>
<body><div>

<form id='lform' onsubmit='return false' action='?'><div id="lformdiv">
<input type='hidden' name="rub" id="lfrub" value="<?=$rub?>"/>
<?php
if (isset($_GET["rub"])) $rub=$_GET["rub"];
else $rub="";
$filenames = array ("english" => "english.php", "francais" => "french.php", "allemand" => "deutsch.php");
if($langsub)
	$lang_select[$langsub]=!$lang_select[$langsub];
while ( $to_require = current($filenames))
{
	$key=key($filenames);
	if($rub)
	{
		echo "\n<input class='hidden' name='lang_select[$key]' ".(!$lang_select || $lang_select[$key] ?" checked='checked'":"")." type='checkbox' id='enable_$key' /><input type='submit' class='lang".(!$lang_select || $lang_select[$key] ?"on":"off")."' id='button_$key'  onclick='choice(\"$key\",document.getElementById(\"enable_$key\").checked)' name='langsub' value='$key' />";
	}
	require($to_require);
	$trans_lang[$key]=$lang;
	$rubs = array_merge($rubs,array_keys($lang));
	unset($lang);
	next($filenames);
}
echo "</div></form>";
$rubs = array_unique($rubs);
natcasesort($rubs);
reset($filenames);
echo "\n\n<ul id='menu'><li class='mainlink'><a href='?'>TransRapi</a></li>\n";
foreach ($rubs as $value)
{
	echo "<li".($value==$rub ? ' class="onrub"':'')
	."><a href='?rub=$value' onclick='changeRub(\"$value\");return false;'>"
	.ucfirst($value)."</a></li>\n";
}
echo "</ul>\n";

if($rub)
{
	
	while ( $to_view = key($filenames))
	{
		$max=max($max,max(array_keys($trans_lang[$to_view][$rub])));
		next($filenames);
	}
	reset($filenames);
	while ( $to_view = key($filenames))
	{
		echo "<ul class='rub' id='{$to_view}'".(!$lang_select || $lang_select[$to_view] ?"":" style='display:none;'")."><li class='titre'>{$to_view}</li>\n";
		for($i=0;$i<=($max+1);$i++)
		{
			$key=$i;
			$value=$trans_lang[$to_view][$rub][$i];
			echo "<li class='li".($value ? $key%2 : 'no')."' id='{$to_view}_{$rub}_{$key}' onclick='getCorrectForm(\"{$to_view}_{$rub}_{$key}\",\"$value\")'>";
			if($select=="{$to_view}_{$rub}_{$key}")
			{
				echo "<form method='get' action='?'><input type='hidden' name='nojs' value='1' /><input type='hidden' name='correct' value='$select'/><input name='text' value=\"$value\" /><br /><input class='nojs' name='reset' type='submit' value='Annuler' /><input class='nojs' type='submit' value='Valider' /></form>";
			}
			else
			{
				echo "<a href='?rub=$rub&select={$to_view}_{$rub}_{$key}' onclick='return false;'>$value</a></li>\n";
			}
		}
	
		echo "</ul>\n";
		
		next($filenames);
	}
	
}
else
{
?><div id='page'>
<h1>Transrapi 0.3</h1>

Selectionnez une section.

</div>
<?php } ?></div></body></html>